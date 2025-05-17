<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Update_Notice extends CloudSecureWP_Common {
	private const KEY_FEATURE         = 'update_notice';
	private const KEY_WP              = self::KEY_FEATURE . '_wp';
	private const KEY_PLUGIN          = self::KEY_FEATURE . '_plugin';
	private const KEY_THEME           = self::KEY_FEATURE . '_theme';
	private const WP_VALUES           = array( 1, 2 );    // 無効、有効 .
	private const PLUGIN_VALUES       = array( 1, 2, 3 ); // 無効、有効（全て）、有効（有効化されたもの） .
	private const THEME_VALUES        = array( 1, 2, 3 ); // 無効、有効（全て）、有効（有効化されたもの） .
	private const KEY_CRON            = 'cloudsecurewp_' . self::KEY_FEATURE . '_cron';
	private const KEY_LAST_NOTICE     = self::KEY_FEATURE . '_last_notice';
	private const LAST_NOTICE_WP      = 'wp';
	private const LAST_NOTICE_PLUGINS = 'plugins';
	private const LAST_NOTICE_THEMES  = 'themes';
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
		$flag = false;

		if ( $this->check_environment() && '' === $this->check_cron_error() ) {
			$flag = true;
		}

		$ret = array(
			self::KEY_FEATURE     => $flag ? 't' : 'f',
			self::KEY_WP          => self::WP_VALUES[1],
			self::KEY_PLUGIN      => self::PLUGIN_VALUES[2],
			self::KEY_THEME       => self::THEME_VALUES[2],
			self::KEY_LAST_NOTICE => $this->get_last_notice_default(),
		);
		return $ret;
	}

	/**
	 * 設定値key取得
	 */
	public function get_keys(): array {
		$ret = array(
			self::KEY_FEATURE,
			self::KEY_WP,
			self::KEY_PLUGIN,
			self::KEY_THEME,
			self::KEY_LAST_NOTICE,
		);
		return $ret;
	}

	/**
	 * LAST_NOTICE 初期値取得
	 */
	public function get_last_notice_default(): array {
		$ret = array(
			self::LAST_NOTICE_WP      => '',
			self::LAST_NOTICE_PLUGINS => array(),
			self::LAST_NOTICE_THEMES  => array(),
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
	 * 設定定義値取得
	 *
	 * @return array
	 */
	public function get_constant_settings(): array {
		$ret = array(
			self::KEY_WP     => self::WP_VALUES,
			self::KEY_PLUGIN => self::PLUGIN_VALUES,
			self::KEY_THEME  => self::THEME_VALUES,
		);
		return $ret;
	}

	/**
	 * 実行環境チェック( cron )
	 *
	 * @return string
	 */
	public function check_cron_error(): string {
		if ( false === wp_next_scheduled( 'wp_version_check' ) ) {
			return 'WP_CRON が無効化されています';
		}

		$result = wp_remote_post( site_url( '/wp-cron.php' ) );

		if ( is_wp_error( $result ) || 200 !== (int) $result['response']['code'] ) {
			return 'wp-cron.php へのアクセスに失敗しました';
		}

		return '';
	}

	/**
	 * cron名取得
	 *
	 * @return string
	 */
	public function get_cron_key(): string {
		return self::KEY_CRON;
	}

	public function update_notice(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . WPINC . '/version.php';

		$settings = $this->get_settings();

		$wp = '';
		if ( self::WP_VALUES[0] !== (int) $settings[ self::KEY_WP ] ) {
			$wp = $this->get_update_wp_version();
		}

		$body_update = '';
		if ( ! empty( $wp ) ) {
			$body_update .= "【WordPressのアップデート】\n";
			$body_update .= "・WordPressの新しいバージョンは{$wp}です。\n\n";
			$this->save_last_notice_wp_version( $wp );
		}

		$plugins = array();
		if ( (int) $settings[ self::KEY_PLUGIN ] !== self::PLUGIN_VALUES[0] ) {
			$plugins = $this->get_update_plugin_versions();
		}

		if ( ! empty( $plugins ) ) {
			$body_update .= "【プラグインのアップデート】\n";

			foreach ( $plugins as $plugin ) {
				$body_update .= "・プラグイン{$plugin['name']}の新しいバージョンは{$plugin['new_version']}です。\n";
			}
			$body_update .= "\n";

			$this->save_last_notice_plugin_versions( $plugins );
		}

		$themes = array();
		if ( self::THEME_VALUES[0] !== (int) $settings[ self::KEY_THEME ] ) {
			$themes = $this->get_update_theme_versions();
		}

		if ( ! empty( $themes ) ) {
			$body_update .= "【テーマのアップデート】\n";

			foreach ( $themes as $theme ) {
				$body_update .= "・テーマ{$theme['name']}の新しいバージョンは{$theme['new_version']}です。\n";
			}
			$body_update .= "\n";

			$this->save_last_notice_theme_versions( $themes );
		}

		if ( ! empty( $body_update ) ) {
			$update_page_url = admin_url( 'update-core.php' );

			$subject = 'アップデート通知';

			$body  = get_option( 'blogname' ) . "に新たな更新があります。\n\n";
			$body .= $body_update;
			$body .= "以下のページを確認し、更新してください。\n\n";
			$body .= "{$update_page_url}\n\n";
			$body .= "--\nCloudSecure WP Security\n";

			$admins = $this->get_admin_users();

			foreach ( $admins as $admin ) {
				$this->wp_send_mail( $admin->user_email, esc_html( $subject ), esc_html( $body ) );
			}
		}
	}

	/**
	 * WP 通知済 バージョン保存
	 *
	 * @param string $version
	 * @return void
	 */
	private function save_last_notice_wp_version( string $version ): void {
		$settings = $this->get_settings();
		if ( ! empty( $version ) ) {
			$settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_WP ] = $version;
			$this->save_settings( $settings );
		}
	}

	/**
	 * wp 更新バージョン取得
	 *
	 * @return string
	 */
	private function get_update_wp_version(): string {
		do_action( 'wp_version_check' );
		$info = get_site_transient( 'update_core' );

		if ( 'upgrade' === ( $info->updates[0]->response ?? '' ) ) {
			$update_version      = $info->updates[0]->current ?? '';
			$settings            = $this->get_settings();
			$last_notice_version = $settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_WP ] ?? '';

			if ( $update_version != $last_notice_version ) {
				return $update_version;
			}
		}

		return '';
	}

	/**
	 * プラグイン 通知済 バージョン保存
	 *
	 * @param array $versions
	 * @return void
	 */
	private function save_last_notice_plugin_versions( $versions ): void {
		$settings = $this->get_settings();
		$settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_PLUGINS ] = array_merge( $settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_PLUGINS ], $versions );
		$this->save_settings( $settings );
	}

	/**
	 * プラグイン 更新バージョン取得
	 *
	 * @return array
	 */
	private function get_update_plugin_versions(): array {
		do_action( 'wp_update_plugins' );

		$settings       = $this->get_settings();
		$res            = get_site_transient( 'update_plugins' );
		$update_plugins = $res->response ?? array();
		$notice_plugins = array();

		if ( empty( $update_plugins ) ) {
			$settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_PLUGINS ] = $notice_plugins;
			$this->save_settings( $settings );
			return $notice_plugins;
		}

		$last_notice_plugins = $settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_PLUGINS ] ?? array();
		$plugins             = get_plugins();

		foreach ( $update_plugins as $plugin_path => $plugin ) {
			$new_version = '';

			if ( $plugin->new_version !== ( $last_notice_plugins[ $plugin_path ]['new_version'] ?? '' ) ) {
				if ( self::PLUGIN_VALUES[2] === (int) $settings[ self::KEY_PLUGIN ] ) {
					if ( is_plugin_active( $plugin_path ) ) {
						$new_version = $plugin->new_version;
					}
				} else {
					$new_version = $plugin->new_version;
				}

				if ( '' !== $new_version && array_key_exists( $plugin_path, $plugins ) ) {
					$notice_plugins[ $plugin_path ] = array(
						'name'        => $plugins[ $plugin_path ]['Name'],
						'new_version' => $new_version,
					);
				}
			}
		}

		return $notice_plugins;
	}

	/**
	 * テーマ 通知済 バージョン保存
	 *
	 * @param array $versions
	 * @return void
	 */
	private function save_last_notice_theme_versions( array $versions ): void {
		$settings = $this->get_settings();
		$settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_THEMES ] = array_merge( $settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_THEMES ], $versions );
		$this->save_settings( $settings );
	}

	/**
	 * テーマ 更新バージョン取得
	 *
	 * @return array
	 */
	private function get_update_theme_versions(): array {
		do_action( 'wp_update_themes' );

		$settings      = $this->get_settings();
		$res           = get_site_transient( 'update_themes' );
		$update_themes = $res->response ?? array();
		$notice_themes = array();

		if ( empty( $update_themes ) ) {
			$settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_THEMES ] = $notice_themes;
			$this->save_settings( $settings );
			return $notice_themes;
		}

		$last_notice_themes = $settings[ self::KEY_LAST_NOTICE ][ self::LAST_NOTICE_THEMES ] ?? array();
		$active_theme       = wp_get_theme();
		$active_text_domain = $active_theme->get( 'TextDomain' );
		$themes             = wp_get_themes();

		foreach ( $update_themes as $text_domain => $theme ) {
			$new_version = '';

			if ( $theme['new_version'] !== $last_notice_themes[ $text_domain ]['new_version'] ?? '' ) {

				if ( self::THEME_VALUES[2] === (int) $settings[ self::KEY_THEME ] ) {
					if ( $text_domain === $active_text_domain ) {
						$new_version = $theme['new_version'];
					}
				} else {
					$new_version = $theme['new_version'];
				}

				if ( '' !== $new_version && array_key_exists( $text_domain, $themes ) ) {
					$notice_themes[ $text_domain ] = array(
						'name'        => $themes[ $text_domain ]->get( 'Name' ),
						'new_version' => $new_version,
					);
				}
			}
		}

		return $notice_themes;
	}

	/**
	 * cron 設定
	 */
	public function set_cron(): void {
		if ( false === wp_get_scheduled_event( self::KEY_CRON ) ) {
			wp_schedule_event( time(), 'daily', self::KEY_CRON );
		}
	}

	/**
	 * cron 削除
	 *
	 * @return void
	 */
	public function remove_cron(): void {
		wp_clear_scheduled_hook( self::KEY_CRON );
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$settings = $this->get_default();
		$this->save_settings( $settings );

		if ( 't' === $settings[ self::KEY_FEATURE ] ) {
			$this->set_cron();
		} else {
			$this->remove_cron();
		}
	}

	/**
	 * 無効化
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->remove_cron();
		$settings                          = $this->get_settings();
		$settings[ self::KEY_FEATURE ]     = 'f';
		$settings[ self::KEY_LAST_NOTICE ] = $this->get_last_notice_default();
		$this->save_settings( $settings );
	}
}
