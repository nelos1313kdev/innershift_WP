<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Disable_RESTAPI extends CloudSecureWP_Common {
	private const KEY_FEATURE     = 'disable_rest_api';
	private const KEY_EXCLUDE     = self::KEY_FEATURE . '_exclude';
	private const DEFAULT_EXCLUDE = array( 'oembed', 'contact-form-7', 'akismet' );
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
			self::KEY_FEATURE => 'f',
			self::KEY_EXCLUDE => self::DEFAULT_EXCLUDE,
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
	 * 除外指定していない有効なプラグイン名リスト取得
	 *
	 * @return array
	 */
	public function get_active_plugin_names(): array {
		$plugin_names    = array();
		$plugins         = get_plugins();
		$exclude_plugins = $this->config->get( self::KEY_EXCLUDE );

		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $plugin_path => $plugin ) {
				if ( is_plugin_active( $plugin_path ) ) {
					if ( false === in_array( $plugin['TextDomain'], $exclude_plugins ) && $plugin['TextDomain'] !== $this->info['text_domain'] ) {
						$plugin_names[] = $plugin['TextDomain'];
					}
				}
			}
		}

		return $plugin_names;
	}

	/**
	 * テキストから配列に変換
	 *
	 * @param string $text
	 * @return array
	 */
	public function text2Array( string $text ): array {
		$searchs    = array( "\r\n", "\r" );
		$text       = str_replace( $searchs, "\n", $text );
		$text_array = explode( "\n", $text );

		foreach ( $text_array as &$name ) {
			$path = trim( $name );
		}
		unset( $path );

		$text_array = array_filter( $text_array, 'strlen' );
		$text_array = array_unique( $text_array );

		return $text_array;
	}

	/**
	 * rest_pre_dispatch
	 */
	function rest_pre_dispatch( $result, $server, $request ) {

		if ( current_user_can( 'edit_pages' ) || current_user_can( 'edit_posts' ) ) {
			return $result;
		}

		$setting         = $this->get_settings();
		$exclude_plugins = $setting[ self::KEY_EXCLUDE ];
		$route           = $request->get_route();

		foreach ( $exclude_plugins as $plugin ) {
			if ($plugin === 'snow-monkey-forms') {
				$plugin = 'snow-monkey-form';
			}

			if ( false !== strpos( $route, "/$plugin/" ) ) {
				return $result;
			}
		}

		return new WP_Error( $this->get_feature_key(), 'REST API が無効化されています', array( 'status' => rest_authorization_required_code() ) );
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
