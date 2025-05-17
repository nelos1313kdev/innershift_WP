<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Login_Log extends CloudSecureWP_Common {
	private const KEY_FEATURE     = 'login_log';
	private const KEY_CONDITIONS  = self::KEY_FEATURE . '_conditions';
	private const TABLE_NAME      = 'cloudsecurewp_' . self::KEY_FEATURE;
	private const COLUMN_ID       = 'id';
	private const COLUMN_NAME     = 'name';
	private const COLUMN_IP       = 'ip';
	private const COLUMN_STATUS   = 'status';
	private const COLUMN_METHOD   = 'method';
	private const COLUMN_LOGIN_AT = 'login_at';
	private const COLUMNS         = array(
		self::COLUMN_ID       => 'ID',
		self::COLUMN_NAME     => 'ユーザー名',
		self::COLUMN_IP       => 'IPアドレス',
		self::COLUMN_STATUS   => 'ログイン判定',
		self::COLUMN_METHOD   => 'ログイン種別',
		self::COLUMN_LOGIN_AT => '日時',
	);

	private const METHOD_PAGE   = 1;
	private const METHOD_XMLRPC = 2;
	// private const METHOD_RESTAPI = 3;
	private const METHODS = array(
		self::METHOD_PAGE   => 'ログインページ',
		self::METHOD_XMLRPC => 'XML-RPC',
		// self::METHOD_RESTAPI => 'REST API',
	);
	private const MAX_LOG = 10000;
	private $config;
	private $disable_login;

	function __construct( array $info, CloudSecureWP_Config $config, CloudSecureWP_Disable_Login $disable_login ) {
		parent::__construct( $info );
		$this->config        = $config;
		$this->disable_login = $disable_login;
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
	 * LoginLogテーブルカラム情報取得
	 */
	public function get_cloumns(): array {
		return self::COLUMNS;
	}

	/**
	 * ログイン種別取得
	 */
	public function get_methods(): array {
		return self::METHODS;
	}

	/**
	 * Xmlrpc判定
	 *
	 * @return bool
	 */
	public function is_xmlrpc(): bool {
		if ( 'xmlrpc.php' === basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * ログインログ登録
	 *
	 * @param string $name
	 * @param string $ip
	 * @param int    $status
	 * @param int    $method
	 * @return void
	 */
	public function write_log( string $name, string $ip, int $status, int $method ): void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$max_log    = self::MAX_LOG;

		$data = array(
			'name'     => $name,
			'ip'       => $ip,
			'status'   => $status,
			'method'   => $method,
			'login_at' => current_time( 'mysql' ),
		);

		$wpdb->query( 'START TRANSACTION' );
		$wpdb->insert( $table_name, $data );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}cloudsecurewp_login_log ORDER BY id DESC LIMIT 1 OFFSET %d", $max_log ), ARRAY_A );

		if ( ! empty( $row ?? array() ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cloudsecurewp_login_log WHERE id <= %d", $row['id'] ) );
		}

		$wpdb->query( 'COMMIT' );
	}

	/**
	 * ログインページログイン成功時
	 */
	public function wp_login( $user_login ) {

		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );
		$this->disable_login->remove_expired_login();

		$ip   = $this->get_client_ip();
		$data = array(
			'ip'           => $ip,
			'status'       => $this->disable_login->get_status_success(),
			'failed_count' => 0,
			'login_at'     => current_time( 'mysql' ),
		);

		$row = $this->disable_login->get_row_by_ip( $ip );
		if ( empty( $row ) ) {
			$wpdb->insert( $this->disable_login->get_table_name(), $data );
		} else {
			$wpdb->update( $this->disable_login->get_table_name(), $data, array( 'ip' => $ip ) );
		}
		$wpdb->query( 'COMMIT' );

		$ip = $this->get_client_ip();
		$this->write_log( $user_login, $ip, self::LOGIN_STATUS_SUCCESS, self::METHOD_PAGE );
	}

	/**
	 * XML-RPCログイン成功時
	 */
	public function xmlrpc_call() {
		$user = wp_get_current_user();

		if ( empty( $user->ID ) ) {
			return;
		}

		$user_login = $user->user_login;
		$ip         = $this->get_client_ip();
		$this->write_log( $user_login, $ip, self::LOGIN_STATUS_SUCCESS, self::METHOD_XMLRPC );
	}

	/**
	 * ログイン失敗時
	 */
	public function wp_login_failed( $user_login ) {
		$ip     = $this->get_client_ip();
		$status = $this->disable_login->get_login_status();
		$method = $this->is_xmlrpc() ? self::METHOD_XMLRPC : self::METHOD_PAGE;
		$this->write_log( $user_login, $ip, $status, $method );
	}

	/**
	 * ログインログ取得
	 *
	 * @param array $conditions
	 * @return array
	 */
	public function get_login_history( array $conditions ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$prepare    = array();

		$sql  = 'SELECT ';
		$sql .= '* ';
		$sql .= "FROM {$table_name} ";
		$sql .= 'WHERE %d ';

		$prepare[] = 1;

		if ( ! empty( $conditions['condition_status'] ?? '' ) ) {
			$sql      .= 'AND status = %d ';
			$prepare[] = (int) $conditions['condition_status'];
		}

		if ( ! empty( $conditions['condition_method'] ?? '' ) ) {
			$sql      .= 'AND method = %d ';
			$prepare[] = (int) $conditions['condition_method'];
		}

		if ( ! empty( $conditions['condition_ip'] ?? '' ) ) {
			if ( ! empty( $conditions['condition_ip_other_than'] ?? '' ) && 't' === $conditions['condition_ip_other_than'] ) {
				$sql .= 'AND ip <> %s ';
			} else {
				$sql .= 'AND ip = %s ';
			}
			$prepare[] = $conditions['condition_ip'];
		}

		if ( ! empty( $conditions['condition_name'] ?? '' ) ) {
			if ( ! empty( $conditions['condition_name_other_than'] ?? '' ) && 't' === $conditions['condition_name_other_than'] ) {
				$sql .= 'AND `name` <> %s ';
			} else {
				$sql .= 'AND `name` = %s ';
			}
			$prepare[] = $conditions['condition_name'];
		}

		array_unshift( $prepare, $sql );
		$rows = $wpdb->get_results( call_user_func_array( array( $wpdb, 'prepare' ), $prepare ), ARRAY_A );

		return $rows ?? array();
	}

	/**
	 * 絞込み条件保存
	 *
	 * @param array $conditions
	 * @return void
	 */
	public function save_conditions( $conditions ): void {
		$this->config->set( self::KEY_CONDITIONS, $conditions );
		$this->config->save();
	}

	/**
	 * 絞込み条件取得
	 *
	 * @return array $conditions
	 */
	public function get_conditions(): array {
		$conditions = $this->config->get( self::KEY_CONDITIONS ) ?? '';
		if ( ! empty( $conditions ) ) {
			return $conditions;
		}
		return array();
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		global $wpdb;
		$table_name = $this->get_table_name();
		$table      = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		if ( is_null( $table ) ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} ( 
				id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT, 
				name VARCHAR( 60 ) NOT NULL DEFAULT '', 
				ip VARCHAR( 39 ) NOT NULL DEFAULT '', 
				status INT NOT NULL DEFAULT 0, 
				method INT NOT NULL DEFAULT 0, 
				login_at DATETIME, 
				UNIQUE KEY id ( id ) 
				) {$charset_collate}";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}
}
