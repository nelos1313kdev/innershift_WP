<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Disable_Login extends CloudSecureWP_Common {
	private const KEY_FEATURE        = 'disable_login';
	private const KEY_INTERVAL       = self::KEY_FEATURE . '_interval';
	private const KEY_LIMIT          = self::KEY_FEATURE . '_limit';
	private const KEY_DURATION       = self::KEY_FEATURE . '_duration';
	private const INTERVAL_VALUES    = array( '5', '15', '30' );    // 秒 .
	private const LIMIT_VALUES       = array( '5', '15', '30' );    // 回 .
	private const DURATION_VALUES    = array( '60', '180', '300', '600', '3600' ); // 秒 .
	private const LOGIN_EXPIRED_HOUR = 24;
	private const TABLE_NAME         = 'cloudsecurewp_login';
	private const ERROR_CODE         = 'cloudsecurewp_disable_login_error';
	private $login_status            = self::LOGIN_STATUS_FAILED;
	private $config;

	function __construct( array $info, CloudSecureWP_Config $config ) {
		parent::__construct( $info );
		$this->config = $config;
	}

	/**
	 * 機能毎のKEY取得
	 *
	 * @return string
	 */
	public function get_feature_key(): string {
		return self::KEY_FEATURE;
	}

	/**
	 *  有効無効判定
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->config->get( $this->get_feature_key() ) === 't' ? true : false;
	}

	/**
	 * 初期設定値取得
	 *
	 * @return array
	 */
	public function get_default(): array {
		$ret = array(
			self::KEY_FEATURE  => $this->check_environment() ? 't' : 'f',
			self::KEY_INTERVAL => self::INTERVAL_VALUES[0],
			self::KEY_LIMIT    => self::LIMIT_VALUES[0],
			self::KEY_DURATION => self::DURATION_VALUES[0],
		);
		return $ret;
	}

	/**
	 * 設定値取得
	 */
	public function get_settings(): array {
		$settings = array();
		$default  = $this->get_default();

		foreach ( $default as $key => $val ) {
			$settings[ $key ] = $this->config->get( $key );
		}

		return $settings;
	}

	/**
	 * 設定値保存
	 *
	 * @param array $settings
	 * @return void
	 */
	public function save_settings( $settings ): void {
		$default = $this->get_default();

		foreach ( $default as $key => $val ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}
		$this->config->save();
	}

	/**
	 * 設定定義値取得
	 *
	 * @return array
	 */
	public function get_constant_settings(): array {
		$ret = array(
			self::KEY_INTERVAL => self::INTERVAL_VALUES,
			self::KEY_LIMIT    => self::LIMIT_VALUES,
			self::KEY_DURATION => self::DURATION_VALUES,
		);
		return $ret;
	}

	/**
	 * ステータス定義値取得
	 *
	 * @return array
	 */
	public function get_constant_status(): array {
		$ret = array(
			self::LOGIN_STATUS_SUCCESS  => 'ログイン成功',
			self::LOGIN_STATUS_FAILED   => 'ログイン失敗',
			self::LOGIN_STATUS_DISABLED => '無効化',
		);
		return $ret;
	}

	/**
	 * ログイン成功ステータス取得
	 */
	public function get_status_success(): int {
		return self::LOGIN_STATUS_SUCCESS;
	}

	/**
	 * ログインステータス取得
	 */
	public function get_login_status(): int {
		return $this->login_status;
	}

	public function set_login_status( int $status ): void {
		$this->login_status = $status;
	}

	/**
	 * テーブル名取得
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * テーブル作成
	 */
	public function create_table(): void {
		global $wpdb;
		$table_name = $this->get_table_name();
		$table      = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		if ( is_null( $table ) ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} ( 
				ip VARCHAR( 39 ) NOT NULL DEFAULT '', 
				status INT NOT NULL DEFAULT 0, 
				failed_count INT NOT NULL DEFAULT 0, 
				login_at DATETIME, 
				UNIQUE KEY index_ip ( ip ) 
				) {$charset_collate}";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * 期限切れログイン情報削除
	 *
	 * @return void
	 */
	public function remove_expired_login(): void {
		global $wpdb;

		$table_name   = $this->get_table_name();
		$expired_hour = self::LOGIN_EXPIRED_HOUR;
		$sql          = "DELETE FROM {$table_name} WHERE login_at < SYSDATE() - INTERVAL {$expired_hour} HOUR;";

		$wpdb->query( $sql );
	}

	/**
	 * ipアドレスでログイン情報取得
	 *
	 * @param string $ip
	 * @return array $row
	 */
	public function get_row_by_ip( string $ip ): array {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * from {$wpdb->prefix}cloudsecurewp_login WHERE ip = %s", $ip );
		$row = $wpdb->get_row( $sql, ARRAY_A );

		return $row ?? array();
	}

	/**
	 * ログインステータスが成功のレコード全件取得
	 *
	 * @return array $row
	 */
	public function get_rows_status_success(): array {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * from {$wpdb->prefix}cloudsecurewp_login WHERE status = %d", $this->get_status_success() );
		$row = $wpdb->get_results( $sql, ARRAY_A );

		return $row ?? array();
	}

	/**
	 * wp_login_failed
	 *
	 * @param string $user_name
	 * @return void
	 */
	public function wp_login_failed( $user_name ): void {
		global $wpdb;

		$this->set_login_status( self::LOGIN_STATUS_FAILED );

		$ip           = $this->get_client_ip();
		$now_datetime = current_time( 'mysql' );
		$now_time     = strtotime( $now_datetime );
		$data         = array(
			'ip'           => $ip,
			'status'       => self::LOGIN_STATUS_FAILED,
			'failed_count' => 1,
			'login_at'     => $now_datetime,
		);

		$wpdb->query( 'START TRANSACTION' );
		$this->remove_expired_login();
		$row = $this->get_row_by_ip( $ip );

		if ( empty( $row ) ) {
			$wpdb->insert( $this->get_table_name(), $data );

		} else {
			$row['status']       = (int) $row['status'];
			$row['failed_count'] = (int) $row['failed_count'];

			$this->set_login_status( $row['status'] );

			if ( self::LOGIN_STATUS_FAILED === $row['status'] ) {
				$now_failed_count = $row['failed_count'] + 1;
				$interval_time    = strtotime( $row['login_at'] ) + (int) $this->config->get( self::KEY_INTERVAL );

				if ( (int) $this->config->get( self::KEY_LIMIT ) <= $now_failed_count ) {
					$data['status']       = self::LOGIN_STATUS_DISABLED;
					$data['failed_count'] = $now_failed_count;

				} elseif ( $now_time <= $interval_time ) {
					$data['failed_count'] = $now_failed_count;
					$data['login_at']     = $row['login_at'];
				}
			} elseif ( self::LOGIN_STATUS_DISABLED === $row['status'] ) {
				$duration_time = strtotime( $row['login_at'] ) + (int) $this->config->get( self::KEY_DURATION );

				if ( $now_time <= $duration_time ) {
					$data['status']   = $row['status'];
					$data['login_at'] = $row['login_at'];
				}
			}
			$wpdb->update( $this->get_table_name(), $data, array( 'ip' => $ip ) );

		}
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * エラーコード追加
	 */
	public function shake_error_codes( $error_codes ) {
		array_push( $error_codes, self::ERROR_CODE );
		return $error_codes;
	}

	/**
	 * 認証
	 *
	 * @param $user
	 * @param string $user_name
	 * @param string $password
	 */
	public function authenticate( $user, $user_name, $password ) {
		if ( $this->is_disable() ) {
			return new WP_Error( self::ERROR_CODE, 'ログイン失敗回数が上限に達したためログインが無効化されました。' );
		}
		return $user;
	}

	/**
	 * ログイン無効判定
	 *
	 * @return bool
	 */
	public function is_disable(): bool {
		$ip  = $this->get_client_ip();
		$row = $this->get_row_by_ip( $ip );

		if ( ! empty( $row ) && self::LOGIN_STATUS_DISABLED === (int) $row['status'] ) {
			$now_time      = strtotime( current_time( 'mysql' ) );
			$duration_time = strtotime( $row['login_at'] ) + (int) $this->config->get( self::KEY_DURATION );

			if ( $now_time <= $duration_time ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->save_settings( $this->get_default() );
		$this->create_table();
	}

	/**
	 * 無効化
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->config->set( $this->get_feature_key(), 'f' );
		$this->config->save();
	}
}
