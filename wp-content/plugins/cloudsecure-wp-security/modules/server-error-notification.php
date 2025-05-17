<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Server_Error_Notification extends CloudSecureWP_Common {
	private const KEY_FEATURE = 'server_error_notification';
	private const TABLE_NAME  = 'cloudsecurewp_server_error';
	private const MAX_ITEMS   = 10000;

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
	 * テーブル名取得
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * サーバーエラーを追加
	 *
	 * @param array $error
	 *
	 * @return void
	 */
	private function insert_error( array $error ): void {
		global $wpdb;
		$table_name          = $wpdb->prefix . self::TABLE_NAME;
		$max_items           = self::MAX_ITEMS;
		$error['created_at'] = current_time( 'mysql' );

		if ( is_null( $error['message'] ) ) {
			$error['message'] = '';
		}

		if ( is_null( $error['file'] ) ) {
			$error['file'] = '';
		}

		if ( mb_strlen( $error['message'], 'UTF-8' ) > 65535 ) {
			$error['message'] = mb_substr( $error['message'], 0, 65535, 'UTF-8' );
		}

		if ( mb_strlen( $error['file'], 'UTF-8' ) > 65535 ) {
			$error['file'] = mb_substr( $error['file'], 0, 65535, 'UTF-8' );
		}

		$wpdb->query( 'START TRANSACTION' );
		$wpdb->insert( $table_name, $error );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cloudsecurewp_server_error WHERE id <= (SELECT id FROM (SELECT id FROM {$wpdb->prefix}cloudsecurewp_server_error ORDER BY id DESC LIMIT 1 OFFSET %d) tmp)", $max_items ) );
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * サーバーエラーを取得
	 *
	 * @param string $orderby
	 * @param string $order
	 * @param int    $per_page
	 * @param int    $offset
	 *
	 * @return array
	 */
	public function get_server_errors( string $orderby, string $order, int $per_page, int $offset ): array {
		global $wpdb;
		$table_name        = $wpdb->prefix . self::TABLE_NAME;
		$sanitized_orderby = sanitize_sql_orderby( "$orderby $order" );
		$sql               = "SELECT * FROM $table_name ORDER BY $sanitized_orderby LIMIT %d OFFSET %d";

		return array(
			$wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ), ARRAY_A ),
			$wpdb->get_var( "SELECT count(*) FROM {$wpdb->prefix}cloudsecurewp_server_error" ),
		);
	}

	/**
	 *  有効無効判定
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->config->get( $this->get_feature_key() ) === 't';
	}

	/**
	 * 初期設定値取得
	 *
	 * @return array
	 */
	public function get_default(): array {
		return array( self::KEY_FEATURE => $this->check_environment() ? 't' : 'f' );
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
	 *
	 * @return void
	 */
	public function save_settings( array $settings ): void {
		$default = $this->get_default();

		foreach ( $default as $key => $val ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}

		$this->config->save();
	}

	/**
	 * エラータイプ名称を取得
	 *
	 * @param int $error_type
	 *
	 * @return string
	 */
	private function get_error_type_name( int $error_type ): string {
		// 下記のコードを参考にさせていただきました。
		// https://github.com/WordPress/WordPress/blob/aac1b7c487c9a5fd206f6845e4eba4164b02f02b/wp-includes/error-protection.php#L48
		$constants = get_defined_constants( true );
		$constants = $constants['Core'] ?? $constants['internal'];

		foreach ( $constants as $constant => $value ) {
			if ( 0 === strpos( $constant, 'E_' ) && $error_type === $value ) {
				return $constant;
			}
		}

		return '';
	}

	/**
	 * メール本文取得
	 *
	 * @param array{ type: int , message: string, file: string, line: int } $error
	 *
	 * @return string
	 */
	private function get_body( array $error ): string {
		$date = $this->get_date();
		$time = $this->get_time();

		$text  = "$date $time にサーバーエラーが発生しました。" . PHP_EOL;
		$text .= PHP_EOL;
		$text .= '【サーバーエラー情報】' . PHP_EOL;
		$text .= PHP_EOL;
		$text .= "・エラータイプ：{$error['type']}" . PHP_EOL;
		$text .= PHP_EOL;
		$text .= "・エラーメッセージ：{$error['message']}" . PHP_EOL;
		$text .= PHP_EOL;
		$text .= "・エラー箇所：{$error['file']}:{$error['line']}" . PHP_EOL;
		$text .= PHP_EOL;
		$text .= '--' . PHP_EOL;
		$text .= 'CloudSecure WP Security' . PHP_EOL;

		return $text;
	}

	/**
	 * 通知
	 */
	public function notification( $args, $error ) {

		$error['type'] = $this->get_error_type_name( $error['type'] );

		$this->insert_error( $error );

		$enable_email_server_error_notification = get_option( 'cloudsecurewp_enable_email_server_error_notification', 't' );
		if ( 't' === $enable_email_server_error_notification ) {
			$option_name = "cloudsecurewp_error_type_{$error['type']}_email_last_sent";
			$last_sent   = get_option( $option_name );
			if ( ! $last_sent || time() > $last_sent + HOUR_IN_SECONDS ) {
				update_option( $option_name, time() );
				$to = get_option( 'admin_email' );
				$this->wp_send_mail( $to, esc_html( 'サーバーエラー通知' ), $this->get_body( $error ) );
			}
		}

		return $args;
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->save_settings( $this->get_default() );

		global $wpdb;
		$table_name      = $this->get_table_name();
		$table           = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		$charset_collate = $wpdb->get_charset_collate();

	if ( ! is_null( $table ) ) {
		$sql  = "ALTER TABLE {$table_name} ALTER message DROP DEFAULT";
		$sql2 = "ALTER TABLE {$table_name} ALTER file DROP DEFAULT";
		$wpdb->query( $sql );
		$wpdb->query( $sql2 );

		} else {
			$sql = "CREATE TABLE {$table_name} ( 
				id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT, 
				type VARCHAR( 31 ) NOT NULL DEFAULT 0, 
				message VARCHAR( 65535 ) NOT NULL, 
				file VARCHAR( 65535 ) NOT NULL, 
				line INT NOT NULL DEFAULT 0, 
				created_at DATETIME, 
				UNIQUE KEY id ( id ) 
				) {$charset_collate}";

			$wpdb->query( $sql );
		}
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
