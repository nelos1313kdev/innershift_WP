<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/htaccess.php';
require_once __DIR__ . '/login-notification.php';
require_once __DIR__ . '/disable-login.php';
require_once __DIR__ . '/rename-login-page.php';
require_once __DIR__ . '/unify-messages.php';
require_once __DIR__ . '/restrict-admin-page.php';
require_once __DIR__ . '/disable-xmlrpc.php';
require_once __DIR__ . '/disable-author-query.php';
require_once __DIR__ . '/disable-restapi.php';
require_once __DIR__ . '/update-notice.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/../really-simple-captcha/really-simple-captcha.php';
require_once __DIR__ . '/lib/class-time-based-one-time-password.php';
require_once __DIR__ . '/login-log.php';
require_once __DIR__ . '/two-factor-authentication.php';
require_once __DIR__ . '/server-error-notification.php';
require_once __DIR__ . '/waf-engine.php';
require_once __DIR__ . '/waf.php';
require_once __DIR__ . '/lib/class-waf-rules.php';
require_once __DIR__ . '/disable-access-system-file.php';
require_once __DIR__ . '/lib/class-disable-access-system-file-rules.php';

class CloudSecureWP extends CloudSecureWP_Common {
	private $config;
	private $htaccess;
	private $login_notification;
	private $disable_login;
	private $rename_login_page;
	private $unify_messages;
	private $restrict_admin_page;
	private $disable_xmlrpc;
	private $disable_author_query;
	private $disable_restapi;
	private $update_notice;
	private $captcha;
	private $login_log;
	private $two_factor_authentication;
	private $server_error_notification;
	private $waf;
	private $disable_access_system_file;

	function __construct( array $info ) {
		parent::__construct( $info );
		$this->config                     = new CloudSecureWP_Config( $info );
		$this->htaccess                   = new CloudSecureWP_Htaccess( $info );
		$this->login_notification         = new CloudSecureWP_Login_Notification( $info, $this->config );
		$this->disable_login              = new CloudSecureWP_Disable_Login( $info, $this->config );
		$this->rename_login_page          = new CloudSecureWP_Rename_Login_Page( $info, $this->config, $this->htaccess );
		$this->unify_messages             = new CloudSecureWP_Unify_Messages( $info, $this->config, $this->disable_login );
		$this->restrict_admin_page        = new CloudSecureWP_Restrict_Admin_Page( $info, $this->config, $this->htaccess, $this->disable_login );
		$this->disable_xmlrpc             = new CloudSecureWP_Disable_XMLRPC( $info, $this->config, $this->htaccess );
		$this->disable_author_query       = new CloudSecureWP_Disable_Author_Query( $info, $this->config );
		$this->disable_restapi            = new CloudSecureWP_Disable_RESTAPI( $info, $this->config );
		$this->update_notice              = new CloudSecureWP_Update_Notice( $info, $this->config );
		$this->captcha                    = new CloudSecureWP_CAPTCHA( $info, $this->config );
		$this->login_log                  = new CloudSecureWP_Login_Log( $info, $this->config, $this->disable_login );
		$this->two_factor_authentication  = new CloudSecureWP_Two_Factor_Authentication( $info, $this->config, $this->disable_login );
		$this->server_error_notification  = new CloudSecureWP_Server_Error_Notification( $info, $this->config );
		$this->waf                        = new CloudSecureWP_Waf( $info, $this->config );
		$this->disable_access_system_file = new CloudSecureWP_Disable_Access_System_File( $info, $this->config );
	}

	/**
	 * プラグイン実行
	 *
	 * @return void
	 */
	public function run() {
		if ( ! $this->check_environment() ) {
			if ( $this->login_notification->is_enabled() ) {
				$this->login_notification->deactivate();
			}

			if ( $this->disable_login->is_enabled() ) {
				$this->disable_login->deactivate();
			}

			if ( $this->rename_login_page->is_enabled() ) {
				$this->rename_login_page->deactivate();
			}

			if ( $this->unify_messages->is_enabled() ) {
				$this->unify_messages->deactivate();
			}

			if ( $this->restrict_admin_page->is_enabled() ) {
				$this->restrict_admin_page->deactivate();
			}

			if ( $this->disable_xmlrpc->is_enabled() ) {
				$this->disable_xmlrpc->deactivate();
			}

			if ( $this->disable_author_query->is_enabled() ) {
				$this->disable_author_query->deactivate();
			}

			if ( $this->disable_restapi->is_enabled() ) {
				$this->disable_restapi->deactivate();
			}

			if ( $this->update_notice->is_enabled() ) {
				$this->update_notice->deactivate();
			}

			if ( $this->captcha->is_enabled() ) {
				$this->captcha->deactivate();
			}

			if ( $this->two_factor_authentication->is_enabled() ) {
				$this->two_factor_authentication->deactivate();
			}

			if ( $this->server_error_notification->is_enabled() ) {
				$this->server_error_notification->deactivate();
			}

			if ( $this->waf->is_enabled() ) {
				$this->waf->deactivate();
			}

			if ( $this->disable_access_system_file->is_enabled() ) {
				$this->disable_access_system_file->deactivate();
			}
		} else {
			if ( $this->waf->is_enabled() ) {
				add_action( 'plugins_loaded', array( $this->waf, 'waf' ), 10 );
			}

			if ( $this->server_error_notification->is_enabled() ) {
				add_filter( 'wp_php_error_args', array( $this->server_error_notification, 'notification' ), 10, 2 );
			}

			if ( $this->disable_access_system_file->is_enabled() ) {
				add_action( 'plugins_loaded', array( $this->disable_access_system_file, 'init' ), 10 );
			}

			add_action( 'wp_login', array( $this->login_log, 'wp_login' ), 1, 1 );
			add_action( 'xmlrpc_call', array( $this->login_log, 'xmlrpc_call' ), 10 );
			add_action( 'wp_login_failed', array( $this->login_log, 'wp_login_failed' ), 20, 1 );

			if ( $this->login_notification->is_enabled() ) {
				add_action( 'wp_login', array( $this->login_notification, 'notification' ), 10, 2 );
			}

			if ( $this->disable_login->is_enabled() ) {
				add_filter( 'shake_error_codes', array( $this->disable_login, 'shake_error_codes' ) );
				add_filter( 'authenticate', array( $this->disable_login, 'authenticate' ), 99, 3 );
				add_action( 'wp_login_failed', array( $this->disable_login, 'wp_login_failed' ), 10, 1 );
			}

			if ( $this->rename_login_page->is_enabled() ) {
				if ( $this->htaccess->setting_tag_exists( $this->rename_login_page->get_feature_key() ) ) {
					remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
					add_action( 'plugins_loaded', array( $this->rename_login_page, 'wp_register_404' ), 10 );
					add_filter( 'login_init', array( $this->rename_login_page, 'login_init' ), 10, 2 );
					add_filter( 'site_url', array( $this->rename_login_page, 'site_url' ), 10, 4 );
					add_filter( 'network_site_url', array( $this->rename_login_page, 'network_site_url' ), 10, 3 );
					add_filter( 'register', array( $this->rename_login_page, 'register' ), 10, 1 );
					add_filter( 'wp_redirect', array( $this->rename_login_page, 'wp_redirect' ), 10, 2 );
					add_filter( 'auth_redirect_scheme', array( $this->rename_login_page, 'auth_redirect_scheme' ), 99, 1 );

				} else {
					add_action( 'admin_notices', array( $this->rename_login_page, 'admin_notices' ), 10, 0 );
					$this->rename_login_page->deactivate();
				}
			} else {
				if ( $this->htaccess->setting_tag_exists( $this->rename_login_page->get_feature_key() ) ) {
					$this->rename_login_page->deactivate();
				}
			}

			if ( $this->unify_messages->is_enabled() ) {
				add_filter( 'login_errors', array( $this->unify_messages, 'login_errors' ), 10, 1 );
			}

			if ( $this->restrict_admin_page->is_enabled() ) {
				if ( $this->htaccess->setting_tag_exists( $this->restrict_admin_page->get_feature_key() ) ) {
					add_action( 'wp_login', array( $this->restrict_admin_page, 'update' ), 1, 2 );

				} else {
					add_action( 'admin_notices', array( $this->restrict_admin_page, 'admin_notices' ), 10, 0 );
					$this->restrict_admin_page->deactivate();
				}
			} else {
				if ( $this->htaccess->setting_tag_exists( $this->restrict_admin_page->get_feature_key() ) ) {
					$this->restrict_admin_page->deactivate();
				}
			}

			if ( $this->disable_xmlrpc->is_enabled() ) {
				if ( $this->disable_xmlrpc->is_pingback_disabled() ) {
					if ( $this->htaccess->setting_tag_exists( $this->disable_xmlrpc->get_feature_key() ) ) {
						add_action( 'admin_notices', array( $this->disable_xmlrpc, 'admin_notices' ), 10, 0 );
						$this->disable_xmlrpc->deactivate();
					} else {
						add_filter( 'xmlrpc_methods', array( $this->disable_xmlrpc, 'xmlrpc_methods' ) );
					}
				}

				if ( $this->disable_xmlrpc->is_xmlrpc_disabled() ) {
					if ( ! $this->htaccess->setting_tag_exists( $this->disable_xmlrpc->get_feature_key() ) ) {
						add_action( 'admin_notices', array( $this->disable_xmlrpc, 'admin_notices' ), 10, 0 );
						$this->disable_xmlrpc->deactivate();
					}
				}
			} else {
				if ( $this->htaccess->setting_tag_exists( $this->disable_xmlrpc->get_feature_key() ) ) {
					$this->disable_xmlrpc->deactivate();
				}
			}

			if ( $this->disable_author_query->is_enabled() ) {
				add_action( 'init', array( $this->disable_author_query, 'init' ) );
			}

			if ( $this->disable_restapi->is_enabled() ) {
				add_filter( 'rest_pre_dispatch', array( $this->disable_restapi, 'rest_pre_dispatch' ), 10, 3 );
			}

			if ( $this->update_notice->is_enabled() ) {
				$this->update_notice->set_cron();
				add_action( $this->update_notice->get_cron_key(), array( $this->update_notice, 'update_notice' ) );
			}

			if ( $this->captcha->is_enabled() && 'xmlrpc.php' !== basename( sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) ) && ! is_admin() ) {
				add_filter( 'shake_error_codes', array( $this->captcha, 'shake_error_codes' ) );

				if ( $this->captcha->is_login_form_enabled() ) {
					add_filter( 'login_form', array( $this->captcha, 'login_form' ) );
					add_action( 'wp_authenticate_user', array( $this->captcha, 'wp_authenticate_user' ), 10, 2 );
				}

				if ( $this->captcha->is_comment_form_enabled() ) {
					add_action( 'comment_form_logged_in_after', array( $this->captcha, 'comment_form_default_fields' ), 1 );
					add_action( 'comment_form_after_fields', array( $this->captcha, 'comment_form_default_fields' ) );
					add_filter( 'preprocess_comment', array( $this->captcha, 'preprocess_comment' ) );
				}

				if ( $this->captcha->is_lost_password_form_enabled() ) {
					add_filter( 'lostpassword_form', array( $this->captcha, 'lostpassword_form' ) );
					add_filter( 'allow_password_reset', array( $this->captcha, 'allow_password_reset' ), 10, 2 );
				}

				if ( $this->captcha->is_register_form_enabled() ) {
					add_action( 'register_form', array( $this->captcha, 'register_form' ) );
					add_action( 'register_post', array( $this->captcha, 'register_post' ), 10, 3 );
				}
			}

			if ( $this->two_factor_authentication->is_enabled() && 'xmlrpc.php' !== basename( $_SERVER['SCRIPT_NAME'] ) && ! is_admin() ) {
				add_action( 'wp_login', array( $this->two_factor_authentication, 'wp_login' ), 0, 2 );
				add_action( 'wp_login', array( $this->two_factor_authentication, 'redirect_if_not_two_factor_authentication_registered' ), 10, 2 );
			}
		}

		if ( is_admin() ) {
			if ( 'cloudsecurewp_rename_login_page' === sanitize_text_field( $_GET['page'] ?? '' ) ) {
				ob_start();
			}

			$this->update();

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
	}

	/**
	 * プラグイン有効化
	 */
	public function activate(): void {
		$this->config->rm();
		$this->config->set( 'version', $this->info['version'] );
		$this->config->save();

		$this->login_notification->activate();
		$this->disable_login->activate();
		$this->rename_login_page->activate();
		$this->unify_messages->activate();
		$this->restrict_admin_page->activate();
		$this->disable_xmlrpc->activate();
		$this->disable_author_query->activate();
		$this->disable_restapi->activate();
		$this->update_notice->activate();
		$this->captcha->activate();
		$this->login_log->activate();
		$this->two_factor_authentication->activate();
		$this->server_error_notification->activate();
		$this->waf->activate();
		$this->disable_access_system_file->activate();
	}

	/**
	 * プラグイン無効化
	 */
	public function deactivate(): void {
		$this->config->load_option();
		$this->login_notification->deactivate();
		$this->disable_login->deactivate();
		$this->rename_login_page->deactivate();
		$this->unify_messages->deactivate();
		$this->restrict_admin_page->deactivate();
		$this->disable_xmlrpc->deactivate();
		$this->disable_author_query->deactivate();
		$this->disable_restapi->deactivate();
		$this->update_notice->deactivate();
		$this->captcha->deactivate();
		$this->two_factor_authentication->deactivate();
		$this->server_error_notification->deactivate();
		$this->waf->deactivate();
		$this->disable_access_system_file->deactivate();
	}

	/**
	 * admin menuを追加
	 */
	public function admin_menu(): void {
		require_once __DIR__ . '/admin/common.php';

		wp_enqueue_style( 'cloudsecurewp', $this->info['plugin_url'] . 'assets/css/style.css', array(), $this->info['version'] );

		$slug = 'cloudsecurewp';
		add_menu_page( $this->info['plugin_name'], $this->info['plugin_name'], 'manage_options', $slug, array( $this, 'dashboard' ), $this->info['plugin_url'] . 'assets/images/plugin-icon.png' );
		add_submenu_page( $slug, 'ダッシュボード', 'ダッシュボード', 'manage_options', $slug, array( $this, 'dashboard' ) );
		add_submenu_page( $slug, 'ログイン無効化', 'ログイン無効化', 'manage_options', $slug . '_disable_login', array( $this, 'm_disable_login' ) );
		add_submenu_page( $slug, 'ログインURL変更', 'ログインURL変更', 'manage_options', $slug . '_rename_login_page', array( $this, 'm_rename_login_page' ) );
		add_submenu_page( $slug, 'ログインエラーメッセージ統一', 'ログインエラーメッセージ統一', 'manage_options', $slug . '_unify_messages', array( $this, 'm_unify_messages' ) );
		add_submenu_page( $slug, '2段階認証', '2段階認証', 'manage_options', $slug . '_two_factor_authentication', array( $this, 'm_two_factor_authentication' ) );
		add_submenu_page( $slug, '画像認証追加', '画像認証追加', 'manage_options', $slug . '_captcha', array( $this, 'm_captcha' ) );
		add_submenu_page( $slug, '管理画面アクセス制限', '管理画面アクセス制限', 'manage_options', $slug . '_restrict_admin_page', array( $this, 'm_restrict_admin_page' ) );
		add_submenu_page( $slug, '設定ファイルアクセス防止', '設定ファイルアクセス防止', 'manage_options', $slug . '_disable_access_system_file', array( $this, 'm_disable_access_system_file' ) );
		add_submenu_page( $slug, 'ユーザー名漏えい防止', 'ユーザー名漏えい防止', 'manage_options', $slug . '_disable_author_query', array( $this, 'm_disable_author_query' ) );
		add_submenu_page( $slug, 'XML-RPC無効化', 'XML-RPC無効化', 'manage_options', $slug . '_disable_xmlrpc', array( $this, 'm_disable_xmlrpc' ) );
		add_submenu_page( $slug, 'REST API無効化', 'REST API無効化', 'manage_options', $slug . '_disable_restapi', array( $this, 'm_disable_restapi' ) );
		add_submenu_page( $slug, 'シンプルWAF', 'シンプルWAF', 'manage_options', $slug . '_waf', array( $this, 'm_waf' ) );
		add_submenu_page( $slug, 'ログイン通知', 'ログイン通知', 'manage_options', $slug . '_login_notification', array( $this, 'm_login_notification' ) );
		add_submenu_page( $slug, 'アップデート通知', 'アップデート通知', 'manage_options', $slug . '_update_notification', array( $this, 'm_update_notice' ) );
		add_submenu_page( $slug, 'サーバーエラー通知', 'サーバーエラー通知', 'manage_options', $slug . '_server_error_notification', array( $this, 'm_server_error_notification' ) );
		add_submenu_page( $slug, 'ログイン履歴', 'ログイン履歴', 'manage_options', $slug . '_login_log', array( $this, 'm_login_log' ) );

		if ( $this->two_factor_authentication->is_enabled_on_screen() ) {
			add_menu_page( 'デバイス登録', '2段階認証', 'read', $slug . '_two_factor_authentication_registration', array( $this, 'm_two_factor_authentication_registration' ), 'dashicons-lock', 72 );
		}
	}

	/**
	 * ダッシュボードページ
	 *
	 * @return void
	 */
	public function dashboard(): void {
		require_once 'admin/dashboard.php';

		$datas = array(
			$this->login_notification->get_feature_key()         => $this->config->get( $this->login_notification->get_feature_key() ),
			$this->disable_login->get_feature_key()              => $this->config->get( $this->disable_login->get_feature_key() ),
			$this->rename_login_page->get_feature_key()          => $this->config->get( $this->rename_login_page->get_feature_key() ),
			$this->unify_messages->get_feature_key()             => $this->config->get( $this->unify_messages->get_feature_key() ),
			$this->restrict_admin_page->get_feature_key()        => $this->config->get( $this->restrict_admin_page->get_feature_key() ),
			$this->disable_xmlrpc->get_feature_key()             => $this->config->get( $this->disable_xmlrpc->get_feature_key() ),
			$this->disable_author_query->get_feature_key()       => $this->config->get( $this->disable_author_query->get_feature_key() ),
			$this->disable_restapi->get_feature_key()            => $this->config->get( $this->disable_restapi->get_feature_key() ),
			$this->update_notice->get_feature_key()              => $this->config->get( $this->update_notice->get_feature_key() ),
			$this->server_error_notification->get_feature_key()  => $this->config->get( $this->server_error_notification->get_feature_key() ),
			$this->captcha->get_feature_key()                    => $this->config->get( $this->captcha->get_feature_key() ),
			$this->two_factor_authentication->get_feature_key()  => $this->config->get( $this->two_factor_authentication->get_feature_key() ),
			$this->waf->get_feature_key()                        => $this->config->get( $this->waf->get_feature_key() ),
			$this->disable_access_system_file->get_feature_key() => $this->config->get( $this->disable_access_system_file->get_feature_key() ),
		);

		new CloudSecureWP_Admin_Dashboard( $this->info, $datas );
	}

	/**
	 * ログイン通知ページ
	 *
	 * @return void
	 */
	public function m_login_notification(): void {
		require_once 'admin/login-notification.php';
		new CloudSecureWP_Admin_Login_Notification( $this->info, $this->login_notification );
	}

	/**
	 * ログイン無効化ページ
	 *
	 * @return void
	 */
	public function m_disable_login(): void {
		require_once 'admin/disable-login.php';
		new CloudSecureWP_Admin_Disable_Login( $this->info, $this->disable_login );
	}

	/**
	 * ログインURL変更
	 *
	 * @return void
	 */
	public function m_rename_login_page(): void {
		require_once 'admin/rename-login-page.php';
		new CloudSecureWP_Admin_Rename_Login_Page( $this->info, $this->rename_login_page );
	}

	/**
	 * エラーメッセージ単一化機能
	 *
	 * @return void
	 */
	public function m_unify_messages(): void {
		require_once 'admin/unify-messages.php';
		new CloudSecureWP_Admin_Unify_Messages( $this->info, $this->unify_messages );
	}

	/**
	 * 管理画面アクセス制限
	 *
	 * @return void
	 */
	public function m_restrict_admin_page(): void {
		require_once 'admin/restrict-admin-page.php';
		new CloudSecureWP_Admin_Restrict_Admin_Page( $this->info, $this->restrict_admin_page );
	}

	/**
	 * XMLRPC無効化
	 *
	 * @return void
	 */
	public function m_disable_xmlrpc(): void {
		require_once 'admin/disable-xmlrpc.php';
		new CloudSecureWP_Admin_Disable_XMLRPC( $this->info, $this->disable_xmlrpc );
	}

	/**
	 * ユーザー名漏えい防止
	 *
	 * @return void
	 */
	public function m_disable_author_query(): void {
		require_once 'admin/disable-author-query.php';
		new CloudSecureWP_Admin_Disable_Author_Query( $this->info, $this->disable_author_query );
	}

	/**
	 * REST API無効化
	 *
	 * @return void
	 */
	public function m_disable_restapi(): void {
		require_once 'admin/disable-restapi.php';
		new CloudSecureWP_Admin_Disable_RESTAPI( $this->info, $this->disable_restapi );
	}

	/**
	 * アップデート通知
	 *
	 * @return void
	 */
	public function m_update_notice(): void {
		require_once 'admin/update-notice.php';
		new CloudSecureWP_Admin_Update_Notice( $this->info, $this->update_notice );
	}

	/**
	 * 画像認証追加
	 *
	 * @return void
	 */
	public function m_captcha(): void {
		require_once 'admin/captcha.php';
		new CloudSecureWP_Admin_CAPTCHA( $this->info, $this->captcha );
	}

	/**
	 * ログイン履歴
	 *
	 * @return void
	 */
	public function m_login_log(): void {
		require_once 'admin/login-log.php';
		require_once 'admin/login-log-table.php';
		new CloudSecureWP_Admin_Login_Log( $this->info, $this->login_log );
	}

	/**
	 * 2段階認証
	 *
	 * @return void
	 */
	public function m_two_factor_authentication(): void {
		require_once 'admin/two-factor-authentication.php';
		new CloudSecureWP_Admin_Two_Factor_Authentication( $this->info, $this->two_factor_authentication );
	}

	/**
	 * 2段階認証のデバイス登録
	 *
	 * @return void
	 */
	public function m_two_factor_authentication_registration(): void {
		require_once 'admin/two-factor-authentication-registration.php';
		new CloudSecureWP_Admin_Two_Factor_Authentication_Registration( $this->info, $this->two_factor_authentication );
	}

	/**
	 * サーバーエラー通知
	 *
	 * @return void
	 */
	public function m_server_error_notification(): void {
		require_once 'admin/server-error-notification.php';
		require_once 'admin/server-error-table.php';
		new CloudSecureWP_Admin_Server_Error_Notification( $this->info, $this->server_error_notification );
	}

	/**
	 * シンプルWAF
	 *
	 * @return void
	 */
	public function m_waf(): void {
		require_once 'admin/waf.php';
		require_once 'admin/waf-table.php';
		new CloudSecureWP_Admin_Waf( $this->info, $this->waf );
	}

	/**
	 * 設定ファイルアクセス防止
	 *
	 * @return void
	 */
	public function m_disable_access_system_file(): void {
		require_once 'admin/disable-access-system-file.php';
		new CloudSecureWP_Admin_Disable_Access_System_File( $this->info, $this->disable_access_system_file );
	}

	/**
	 * プラグイン更新時の処理
	 */
	private function update(): void {
		$old_version = $this->config->get( 'version' );
		$now_version = $this->info['version'];

		if ( empty( $old_version ) ) {
			$old_version = '1.0.0';
		}

		if ( 0 <= version_compare( $old_version, $now_version ) ) {
			return;
		}

		if ( version_compare( $old_version, '1.1.0' ) < 0 ) {
			$this->two_factor_authentication->activate();
			$this->server_error_notification->activate();
		}

		if ( version_compare( $old_version, '1.3.0' ) < 0 ) {
			$this->waf->activate();
			$this->disable_access_system_file->activate();
		}

		if ( version_compare( $old_version, '1.3.7' ) < 0 ) {
			$this->waf->activate();
			$this->server_error_notification->activate();
		}

		$this->config->set( 'version', $now_version );
		$this->config->save();
	}
}
