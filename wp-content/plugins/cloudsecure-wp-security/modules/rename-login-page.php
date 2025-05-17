<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Rename_Login_Page extends CloudSecureWP_Common {
	private const KEY_FEATURE          = 'rename_login_page';
	private const KEY_NAME             = self::KEY_FEATURE . '_name';
	private const KEY_DISABLE_REDIRECT = self::KEY_FEATURE . '_disable_redirect';
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
			self::KEY_FEATURE          => 'f',
			self::KEY_NAME             => $this->get_new_name(),
			self::KEY_DISABLE_REDIRECT => 'f',
		);
		return $ret;
	}

	/**
	 * 設定値key取得
	 */
	public function get_keys(): array {
		$ret = array(
			self::KEY_FEATURE,
			self::KEY_NAME,
			self::KEY_DISABLE_REDIRECT,
		);
		return $ret;
	}

	/**
	 * 設定値取得
	 */
	public function get_settings(): array {
		$settings = array();
		$keys     = $this->get_keys();

		foreach ( $keys as $key ) {
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
		$keys = $this->get_keys();

		foreach ( $keys as $key ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}

		$this->config->save();
	}

	/**
	 * ランダム値を含んだ新しいログインページ名を取得
	 *
	 * @return string $name
	 */
	public function get_new_name(): string {
		$name  = '';
		$chars = '0123456789abcdefghijklmnopqrstuvwxyz-_';

		for ( $ii = 0; $ii < 8; $ii ++ ) {
			$name .= $chars[ rand( 0, strlen( $chars ) - 1 ) ];
		}

		return $name;
	}

	/**
	 * ファイル名重複判定
	 *
	 * @param string $name
	 */
	public function is_duplicat_file( string $name ): bool {
		$files = array_diff( scandir( ABSPATH ), array( '.', '..' ) );

		if ( in_array( $name, $files ) ) {
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
		$parse   = parse_url( site_url() );
		$base    = ( $parse['path'] ?? '' ) . '/';
		$name    = $this->config->get( self::KEY_NAME );
		$page404 = self::PAGE_404;

		$setting  = "<IfModule mod_rewrite.c>" . "\n";
		$setting .= "    RewriteEngine on" . "\n";
		$setting .= "    RewriteBase {$base}" . "\n";
		$setting .= "    RewriteRule ^wp-activate\.php {$page404} [L]" . "\n";
		$setting .= "    RewriteRule ^wp-signup\.php {$page404} [L]" . "\n";
		$setting .= "    RewriteRule ^{$name}(.*)$ wp-login.php$1 [L]" . "\n";
		$setting .= "</IfModule>" . "\n";

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
	 * login_init
	 */
	public function login_init() {
		$request_uri = sanitize_url( $_SERVER['REQUEST_URI'] ?? '' );
		if ( false !== strpos( $request_uri, 'wp-login.php' ) ) {
			if ( false !== strpos( wp_get_referer(), $this->config->get( self::KEY_NAME ) ) ) {
				wp_redirect( $this->replace_wp_login( $request_uri ) );
				exit;
			} else {
				$this->page404();
				exit;
			}
		}
	}

	/**
	 * site_url
	 */
	public function site_url( $url, $path, $scheme, $blog_id ) {
		return $this->replace_wp_login( $url );
	}

	/**
	 * network_site_url
	 */
	public function network_site_url( $url, $path, $scheme ) {
		return $this->replace_wp_login( $url );
	}

	/**
	 * wp_redirect
	 */
	public function wp_redirect( $location, $status ) {
		return $this->replace_wp_login( $location );
	}

	/**
	 * register
	 */
	public function register( $link ) {
		return $this->replace_wp_login( $link );
	}

	/**
	 * auth_redirect_scheme
	 */
	public function auth_redirect_scheme( $scheme ) {
		if ( 't' === $this->config->get( self::KEY_DISABLE_REDIRECT ) ) {
			if ( ! wp_validate_auth_cookie( '', $scheme ) ) {
				wp_safe_redirect( home_url( self::PAGE_404 ) );
				exit;
			}
		}
		return $scheme;
	}

	/**
	 * wp-register.phpのアクセスを404にリダイレクト
	 */
	public function wp_register_404() {
		$request_uri = sanitize_url( $_SERVER['REQUEST_URI'] ?? '' );

		if ( false !== strpos( $request_uri, 'wp-register.php' ) ) {
			wp_safe_redirect( home_url( self::PAGE_404 ) );
			exit;
		}
	}

	/**
	 * ログインページ名を書き換え
	 *
	 * @param string $uri
	 * @return string $new_uri
	 */
	private function replace_wp_login( $uri ) {
		$uri = str_replace( 'wp-login.php', $this->config->get( self::KEY_NAME ), $uri );
		return $uri;
	}

	/**
	 * 管理画面に通知表示
	 */
	public function admin_notices() {
		$feature = 'ログインURL変更';
		$this->prepare_admin_notices( self::KEY_FEATURE, $feature );
	}

	/**
	 * メール通知
	 *
	 * @return void
	 */
	public function notification( $name ) {
		$login_url = site_url() . '/' . $name;
		$subject   = 'ログインURL変更';

		$body  = "新しいログインURLは、以下のとおりです。" . "\n";
		$body .= "必要に応じてブックマークをお願いいたします。" . "\n";
		$body .= "" . "\n";
		$body .= "{$login_url}" . "\n";
		$body .= "" . "\n";
		$body .= "--" . "\n";
		$body .= "CloudSecure WP Security" . "\n";

		$admins = $this->get_admin_users();

		foreach ( $admins as $admin ) {
			$this->wp_send_mail( $admin->user_email, esc_html( $subject ), esc_html( $body ) );
		}
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$settings = $this->get_default();
		$this->save_settings( $settings );

		if ( $this->is_enabled() ) {
			if ( ! $this->update_htaccess() ) {
				$settings[ $this->get_feature_key() ] = 'f';
			}
		}
		$this->save_settings( $settings );

		if ( 't' === $this->config->get( $this->get_feature_key() ) ) {
			$this->notification( $settings[ self::KEY_NAME ] );
		}
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
