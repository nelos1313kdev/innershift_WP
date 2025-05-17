<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Restrict_Admin_Page extends CloudSecureWP_Common {
	private const KEY_FEATURE   = 'restrict_admin_page';
	private const KEY_PATHS     = self::KEY_FEATURE . '_paths';
	private const DEFAULT_PATHS = array( 'css', 'images', 'admin-ajax.php', 'load-styles.php', 'site-health.php' );
	private $config;
	private $htaccess;
	private $disable_login;

	function __construct( array $info, CloudSecureWP_Config $config, CloudSecureWP_Htaccess $htaccess, CloudSecureWP_Disable_Login $disable_login ) {
		parent::__construct( $info );
		$this->config        = $config;
		$this->htaccess      = $htaccess;
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
			// self::KEY_FEATURE => $this->check_environment() && $this->htaccess->is_enabled() ? 't' : 'f',
			self::KEY_FEATURE => 'f',
			self::KEY_PATHS   => self::DEFAULT_PATHS,
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
	 * htaccessに書き出す設定を取得
	 *
	 * @param array $allow_ips
	 * @param array $allow_paths
	 * @return string
	 */
	private function get_htaccess_settings( array $allow_ips, array $allow_paths ): string {
		$rules = '';
		foreach ( $allow_paths as $path ) {
			$rules .= '    RewriteRule ^wp-admin/' . str_replace( '.', '\.', $path ) . " - [L]\n";
		}

		$conds = '';
		foreach ( $allow_ips as $ip ) {
			$conds .= '    RewriteCond %{REMOTE_ADDR} !^' . str_replace( '.', '\.', $ip ) . "$\n";
		}

		$parse   = parse_url( site_url() );
		$base    = ( $parse['path'] ?? '' ) . '/';
		$page404 = self::PAGE_404;

		$setting  = "<IfModule mod_rewrite.c>" . "\n";
		$setting .= "    RewriteEngine on" . "\n";
		$setting .= "    RewriteBase {$base}" . "\n";
		$setting .= "    RewriteRule ^{$page404} - [L]\n{$rules}{$conds}    RewriteRule ^wp-admin {$page404} [L]" . "\n";
		$setting .= "</IfModule>" . "\n";

		return $setting;
	}

	/**
	 * htaccessに書き出す設定を更新
	 *
	 * @param string $htaccess_settings
	 * @return bool
	 */
	public function update_htaccess( string $htaccess_settings ): bool {
		$plugin_tag = $this->htaccess->get_plugin_settings_tag();
		$tag        = $this->get_feature_key();

		if ( $this->htaccess->setting_tag_exists( $plugin_tag ) ) {
			if ( $this->htaccess->setting_tag_exists( $tag ) ) {
				if ( ! $this->remove_htaccess() ) {
					return false;
				}
			}
		} else {
			if ( ! $this->htaccess->add_plugin_settings_tag() ) {
				return false;
			}
		}
		$ret = $this->htaccess->add_feature_setting( $tag, $htaccess_settings );

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
	 * 制限除外パスを取得
	 *
	 * @return array
	 */
	public function get_exclude_paths(): array {
		return $this->config->get( self::KEY_PATHS ?? array() );
	}

	/**
	 * プライベートIP判定
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function isPrivateIP( string $ip ): bool {
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		return ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) );
	}

	/**
	 * 設定更新
	 *
	 * @return bool
	 */
	public function update(): bool {
		$allowed_ips = array( '127.0.0.1' );
		$server_ip   = sanitize_text_field( $_SERVER['SERVER_ADDR'] ?? '' );

		if ( false !== filter_var( $server_ip, FILTER_VALIDATE_IP ) ) {
			if ( false === in_array( $server_ip, $allowed_ips ) ) {
				$allowed_ips[] = $server_ip;
			}
		}

		$client_ip = $this->get_client_ip();

		if ( false !== filter_var( $client_ip, FILTER_VALIDATE_IP ) ) {
			if ( false === in_array( $client_ip, $allowed_ips ) ) {
				$allowed_ips[] = $client_ip;
			}
		}

		$success_rows = $this->disable_login->get_rows_status_success();
		foreach ( $success_rows as $success_row ) {
			$tmp_ip = trim( $success_row['ip'] );
			if ( false === in_array( $tmp_ip, $allowed_ips ) ) {
				$allowed_ips[] = $tmp_ip;
			}
		}

		$allowed_paths    = $this->get_exclude_paths();
		$htaccess_setting = $this->get_htaccess_settings( $allowed_ips, $allowed_paths );

		if ( ! $this->update_htaccess( $htaccess_setting ) ) {
			$settings                             = $this->get_settings();
			$settings[ $this->get_feature_key() ] = 'f';
			$this->save_settings( $settings );

			return false;
		}

		return true;
	}

	/**
	 * テキストから配列に変換
	 *
	 * @param string $text
	 * @return array
	 */
	public function text2Array( string $text ): array {
		$searches   = array( "\r\n", "\r" );
		$text       = str_replace( $searches, "\n", $text );
		$text_array = explode( "\n", $text );

		foreach ( $text_array as &$path ) {
			$path = preg_replace( '|\s|', '', $path );
		}
		unset( $path );

		$text_array = array_filter( $text_array, 'strlen' );
		$text_array = array_unique( $text_array );

		return $text_array;
	}

	/**
	 * 管理画面に通知表示
	 */
	public function admin_notices() {
		$feature = '管理画面アクセス制限';
		$this->prepare_admin_notices( self::KEY_FEATURE, $feature );
	}

	/**
	 * 有効化
	 * CloudSecureWP_Disable_Login->activate() が先に実行される必要あり（DBテーブル作成のため）
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
