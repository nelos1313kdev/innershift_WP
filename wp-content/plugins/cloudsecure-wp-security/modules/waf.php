<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Waf extends CloudSecureWP_Waf_Engine {
	private const KEY_FEATURE            = 'waf';
	private const KEY_SEND_ADMIN_MAIL    = self::KEY_FEATURE . '_send_admin_mail';
	private const SEND_ADMIN_MAIL_VALUES = array( 1, 2 ); // 無効、有効 .
	private const KEY_SEND_AT            = self::KEY_FEATURE . '_send_at';
	private const KEY_AVAILABLE_RULES    = self::KEY_FEATURE . '_available_rules';
	private const RULES_CATEGORY         = self::KEY_FEATURE . '_rules_category';
	private const RULES_CATEGORY_VARUES  = array( 1, 2, 4, 8, 16 );
	private const RULES_CATEGORY_NAMES   = array(
		'SQLインジェクション',
		'クロスサイトスクリプティング',
		'OSコマンドインジェクション',
		'コードインジェクション',
		'メールヘッダインジェクション',
	);
	private const TABLE_NAME             = 'cloudsecurewp_waf_log';
	private const COLUMN_ID              = 'id';
	private const COLUMN_ACCESS_AT       = 'access_at';
	private const COLUMN_ATTACK          = 'attack';
	private const COLUMN_URL             = 'url';
	private const COLUMN_MATCHED         = 'matched';
	private const COLUMN_IP              = 'ip';
	private const COLUMNS                = array(
		self::COLUMN_ID        => 'ID',
		self::COLUMN_ACCESS_AT => '日時',
		self::COLUMN_ATTACK    => '攻撃種別',
		self::COLUMN_URL       => '検知したページのURL',
		self::COLUMN_MATCHED   => 'マッチした文字列',
		self::COLUMN_IP        => '攻撃元IPアドレス',
	);
	private const MAX_LOG                = 10000;
	private $config;
	private $waf_rules;



	function __construct( array $info, CloudSecureWP_Config $config ) {
		parent::__construct( $info );
		$this->config    = $config;
		$this->waf_rules = new CloudSecureWP_Waf_Rules();
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
	 * 有効無効判定
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
			self::KEY_FEATURE         => 'f',
			self::KEY_SEND_ADMIN_MAIL => self::SEND_ADMIN_MAIL_VALUES[1],
			self::KEY_SEND_AT         => array(),
			self::KEY_AVAILABLE_RULES => 63,
		);

		return $ret;
	}


	/**
	 * 設定定義値取得
	 *
	 * @return array
	 */
	public function get_constant_settings(): array {
		$ret = array(
			self::KEY_SEND_ADMIN_MAIL => self::SEND_ADMIN_MAIL_VALUES,
			self::KEY_AVAILABLE_RULES => self::RULES_CATEGORY_VARUES,
			self::RULES_CATEGORY      => array(
				self::RULES_CATEGORY_VARUES[0] => self::RULES_CATEGORY_NAMES[0],
				self::RULES_CATEGORY_VARUES[1] => self::RULES_CATEGORY_NAMES[1],
				self::RULES_CATEGORY_VARUES[2] => self::RULES_CATEGORY_NAMES[2],
				self::RULES_CATEGORY_VARUES[3] => self::RULES_CATEGORY_NAMES[3],
				self::RULES_CATEGORY_VARUES[4] => self::RULES_CATEGORY_NAMES[4],
			),
		);
		return $ret;
	}


	/**
	 * 設定値取得
	 *
	 * @return array
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
	 * テーブル名取得
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}


	/**
	 * wafLogテーブルカラム情報取得
	 *
	 * @return array
	 */
	public function get_cloumns(): array {
		return self::COLUMNS;
	}


	/**
	 * テーブル作成
	 *
	 * @return void
	 */
	public function create_table(): void {
		global $wpdb;
		$table_name      = $this->get_table_name();
		$table           = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
		$charset_collate = $wpdb->get_charset_collate();

		if ( ! is_null( $table ) ) {
			$sql  = "ALTER TABLE {$table_name} ALTER url DROP DEFAULT";
			$sql2 = "ALTER TABLE {$table_name} ALTER matched DROP DEFAULT";
			$wpdb->query( $sql );
			$wpdb->query( $sql2 );

		} else {
			$sql = "CREATE TABLE {$table_name} ( 
				id BIGINT( 20 ) UNSIGNED NOT NULL AUTO_INCREMENT, 
				access_at DATETIME, 
				attack VARCHAR( 255 ) NOT NULL DEFAULT '', 
				url VARCHAR( 32767 ) NOT NULL, 
				matched VARCHAR( 32767 ) NOT NULL, 
				ip VARCHAR( 39 ) NOT NULL DEFAULT '', 
				UNIQUE KEY id ( id ) 
				) {$charset_collate}";

			$wpdb->query( $sql );
		}
	}


	/**
	 * 攻撃種別の名前を取得
	 *
	 * @param string $attack
	 * @return string
	 */
	public function attack2name( $attack ): string {
		$attack_category = $this->get_constant_settings();
		return $attack_category[ self::RULES_CATEGORY ][ $attack ];
	}


	/**
	 * ログ登録
	 *
	 * @param array $match_results
	 * @return void
	 */
	public function write_log( $match_results ): void {
		global $wpdb;
		$table_name = $this->get_table_name();
		$max_log    = self::MAX_LOG;

		if ( mb_strlen( $match_results['url'], 'UTF-8' ) > 32767 ) {
			$match_results['url'] = mb_substr( $match_results['url'], 0, 32767, 'UTF-8' );
		}

		if ( mb_strlen( $match_results['matched'], 'UTF-8' ) > 32767 ) {
			$match_results['matched'] = mb_substr( $match_results['matched'], 0, 32767, 'UTF-8' );
		}

		$data = array(
			self::COLUMN_ACCESS_AT => $match_results['access_at'],
			self::COLUMN_ATTACK    => $this->attack2name( $match_results['attack'] ),
			self::COLUMN_URL       => $match_results['url'],
			self::COLUMN_MATCHED   => $match_results['matched'],
			self::COLUMN_IP        => $match_results['ip'],
		);

		$wpdb->query( 'START TRANSACTION' );
		$wpdb->insert( $table_name, $data );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}cloudsecurewp_waf_log ORDER BY id DESC LIMIT 1 OFFSET %d", $max_log ), ARRAY_A );

		if ( ! empty( $row ?? array() ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cloudsecurewp_waf_log WHERE id <= %d", $row['id'] ) );
		}

		$wpdb->query( 'COMMIT' );
	}


	/**
	 * ログ取得
	 *
	 * @param string $orderby
	 * @param string $order
	 * @param int    $per_page
	 * @param int    $offset
	 * @return array
	 */
	public function get_block_history( $orderby, $order, $per_page, $offset ): array {
		global $wpdb;
		$table_name        = $this->get_table_name();
		$sanitized_orderby = sanitize_sql_orderby( "$orderby $order" );
		$sql               = "SELECT * FROM {$table_name} ORDER BY {$sanitized_orderby} LIMIT %d OFFSET %d";

		return array(
			$wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ), ARRAY_A ),
			$wpdb->get_var( "SELECT count(*) FROM {$table_name}" ),
		);
	}


	/**
	 * ブロック通知処理
	 *
	 * @param array $match_results
	 * @return void
	 */
	public function brock_notice( $match_results ): void {
		$match_access_at = strtotime( $match_results['access_at'] );
		$settings        = $this->get_settings();
		$send_at         = $settings[ self::KEY_SEND_AT ] ?? array();

		if ( ! empty( $send_at ) && is_array( $send_at ) ) {
			foreach ( $send_at as $key => $val ) {
				if ( $match_results['attack'] === $key ) {
					$tmp_send_at = strtotime( $val );
					break;
				}
			}
		} else {
			$tmp_send_at = 0;
		}

		if ( 60 <= $match_access_at - $tmp_send_at || $tmp_send_at === 0 ) {
			$subject = 'アクセスをブロックしました [' . $match_results['access_at'] . ']';

			$body  = $match_results['access_at'] . ' に「' . $this->attack2name( $match_results['attack'] ) . "」の攻撃をブロックしました。\n\n";
			$body .= "詳細はこちらからご確認ください。\n";
			$body .= admin_url( 'admin.php?page=cloudsecurewp_waf&childpage=log' ) . "\n\n";
			$body .= "--\nCloudSecure WP Security\n";

			$admins = $this->get_admin_users();

			foreach ( $admins as $admin ) {
				$this->wp_send_mail( $admin->user_email, esc_html( $subject ), esc_html( $body ) );
			}

			$send_at[ $match_results['attack'] ] = $match_results['access_at'];

			$this->config->set( self::KEY_SEND_AT, $send_at );
			$this->config->save();
		}
	}


	public function waf() :void {
		$settings            = $this->get_settings();
		$waf_rules           = $this->waf_rules->get_waf_rules();
		$locationmatch_rules = $this->waf_rules->get_locationmatch_rules();
		$remove_rules        = array(
			'ajax_editor'    => array('950001', '950901', '950004', '950904', '950006', '950906', '950007', '950907', '950908', '950013', '950019', 'm340095' ),
			'ajax_customize' => array( '950904', '950906', '950004', '950001', '950007' ),
			'rest_api'       => array( '950004', '950001', '950007', '950006' ),
			'comment'        => array( '950004' ),
			'coccon'         => array( '950004' ),
			'emanon'         => array( '950004', '950001', '950007' ),
			'vkexunit'       => array( '950004', '950001', '950007' ),
			'nishiki'        => array( '950004', '950001', '950007' ),
			'swell'          => array( '950004', '950001', '950007' ),
			'woocommerce'    => array( '959006' ),
		);

		$results = $this->waf_engine( $waf_rules, $locationmatch_rules, $settings[ self::KEY_AVAILABLE_RULES ], $remove_rules );

		if ( $results['is_deny'] && $results['is_write_log'] ) {
			$this->write_log( $results );

			if ( self::SEND_ADMIN_MAIL_VALUES[1] === (int) $settings[ self::KEY_SEND_ADMIN_MAIL ] ) {
				$this->brock_notice( $results );
			}

			$this->page403();
			exit;

		} elseif ( $results['is_write_log'] ) {
			$this->write_log( $results );
		}
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
		$this->config->set( self::KEY_FEATURE, 'f' );
		$this->config->save();
	}
}
