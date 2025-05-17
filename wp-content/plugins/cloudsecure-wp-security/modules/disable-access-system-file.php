<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Disable_Access_System_File extends CloudSecureWP_Waf_Engine {
	private const KEY_FEATURE     = 'disable_access_system_file';
	private const AVAILABLE_RULES = 1; // waf機能でいう検知する攻撃種別について、本機能では1種類のため1で固定
	private $disable_access_system_file_rules;
	private $config;

	function __construct( array $info, CloudSecureWP_Config $config ) {
		parent::__construct( $info );
		$this->config                           = $config;
		$this->disable_access_system_file_rules = new CloudSecureWP_Disable_Access_System_File_Rules();
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
	 * システム設定ファイルアクセス制限
	 */
	public function init(): void {
		$waf_rules           = $this->disable_access_system_file_rules->get_waf_rules();
		$locationmatch_rules = $this->disable_access_system_file_rules->get_locationmatch_rules();
		$remove_rules        = array(
			'ajax_editor'    => array( '950005' ),
		);

		$results = $this->waf_engine( $waf_rules, $locationmatch_rules, self::AVAILABLE_RULES, $remove_rules );

		if ( $results['is_deny'] ) {
			$this->page403();
			exit;
		}
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
		$this->config->set( $this->get_feature_key(), 'f' );
		$this->config->save();
	}
}
