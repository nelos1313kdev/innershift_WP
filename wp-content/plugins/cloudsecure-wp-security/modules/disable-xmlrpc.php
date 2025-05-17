<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Disable_XMLRPC extends CloudSecureWP_Common {
	private const KEY_FEATURE         = 'disable_xmlrpc';
	private const KEY_TYPE            = self::KEY_FEATURE . '_type';
	private const TYPE_VALUE_PINGBACK = '1';
	private const TYPE_VALUE_XMLRPC   = '2';
	private const TYPE_VALUES         = array( self::TYPE_VALUE_PINGBACK, self::TYPE_VALUE_XMLRPC );
	private $config;
	private $htaccess;

	function __construct( array $info, CloudSecureWP_Config $config, CloudSecureWP_Htaccess $htaccess ) {
		parent::__construct( $info );
		$this->config   = $config;
		$this->htaccess = $htaccess;
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
			self::KEY_FEATURE => $this->check_environment() ? 't' : 'f',
			self::KEY_TYPE    => self::TYPE_VALUES[0],
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
		return array( self::KEY_TYPE => self::TYPE_VALUES );
	}

	/**
	 * ピンバック無効化判定
	 *
	 * @return bool
	 */
	public function is_pingback_disabled(): bool {
		$settings = $this->get_settings();
		if ( self::TYPE_VALUE_PINGBACK === $settings[ self::KEY_TYPE ] ) {
			return true;
		}
		return false;
	}

	/**
	 * XMLRPC無効化判定
	 *
	 * @return bool
	 */
	public function is_xmlrpc_disabled(): bool {
		$settings = $this->get_settings();
		if ( self::TYPE_VALUE_XMLRPC === $settings[ self::KEY_TYPE ] ) {
			return true;
		}
		return false;
	}

	/**
	 * htaccessに書き出す設定を取得
	 *
	 * @return string
	 */
	private function get_htaccess_settings(): string {

		$setting  = "<Files xmlrpc.php>" . "\n";
		$setting .= "    <IfModule authz_core_module>" . "\n";
		$setting .= "        Require all denied" . "\n";
		$setting .= "    </IfModule>" . "\n";
		$setting .= "    <IfModule !authz_core_module>" . "\n";
		$setting .= "        Order allow,deny" . "\n";
		$setting .= "        Deny from all" . "\n";
		$setting .= "    </IfModule>" . "\n";
		$setting .= "</Files>" . "\n";

		return $setting;
	}

	/**
	 * htaccessに書き出す設定を更新
	 *
	 * @return bool
	 */
	public function update_htaccess(): bool {
		$plugin_tag = $this->htaccess->get_plugin_settings_tag();
		$tag        = $this->get_feature_key();

		if ( $this->htaccess->setting_tag_exists( $plugin_tag ) ) {
			if ( $this->htaccess->setting_tag_exists( $tag ) ) {
				if ( ! $this->remove_htaccess() ) {
					return false;
				}
			}
		} else {
			$this->htaccess->add_plugin_settings_tag();
		}

		$setting = $this->get_htaccess_settings();
		$ret     = $this->htaccess->add_feature_setting( $tag, $setting );

		return $ret;
	}

	/**
	 * htaccessから設定を削除
	 *
	 * @return bool
	 */
	public function remove_htaccess(): bool {
		return $this->htaccess->remove_settings( array( $this->get_feature_key() ) );
	}

	/**
	 * 管理画面に通知表示
	 */
	public function admin_notices() {
		$feature = 'XML-RPC無効化';
		$this->prepare_admin_notices( self::KEY_FEATURE, $feature );
	}

	/**
	 * xmlrpc_methods
	 */
	function xmlrpc_methods( $methods ) {
		unset( $methods['pingback.ping'] );
		unset( $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->save_settings( $this->get_default() );
	}

	/**
	 * 無効化
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->remove_htaccess();
		$this->config->set( self::KEY_FEATURE, 'f' );
		$this->config->save();
	}
}
