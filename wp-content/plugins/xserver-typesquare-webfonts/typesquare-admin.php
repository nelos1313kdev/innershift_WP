<?php
require_once(dirname(__FILE__).'/inc/admin-base.php');
require_once(dirname(__FILE__).'/inc/admin-root.php');
require_once(dirname(__FILE__).'/inc/admin-fonttheme.php');

class TypeSquare_Admin extends TypeSquare_Admin_Base
{
    private static $instance;
    private static $text_domain;

    private function __construct()
    {
    }

    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function add_hook()
    {
        self::$text_domain = TypeSquare_ST::text_domain();
        $root = TypeSquare_Admin_Root::get_instance();
        add_action('admin_menu', array( $this, 'typesquare_setting_menu' ));
        add_action('admin_menu', array( $root, 'typesquare_post_metabox' ));
        add_action('admin_init', array( $this, 'typesquare_admin_init' ));
        add_action('admin_notices', array( $this, 'typesquare_admin_init_notices' ));
        add_action('admin_notices', array( $root, 'typesquare_admin_notices' ));
        add_action('save_post', array( $root, 'typesquare_save_post' ));
        add_action('admin_enqueue_scripts', array( $this, 'admin_theme_style' ));
    }

    public function admin_theme_style()
    {
        wp_enqueue_style('ts-styles', path_join(TYPESQUARE_PLUGIN_URL, 'inc/assets/css/admin.css'));
        wp_enqueue_style('ts-common', path_join(TYPESQUARE_PLUGIN_URL, 'inc/assets/css/common.css'));
        wp_enqueue_style('ts-font', path_join(TYPESQUARE_PLUGIN_URL, 'inc/assets/css/font.css'));
    }

    public function admin_theme_script($position = false)
    {
        wp_enqueue_script('ts-admin-react', path_join(TYPESQUARE_PLUGIN_URL, 'inc/app.js'), array( 'jquery' ), '1.0.0', $position);
    }

    public function typesquare_setting_menu()
    {
        $root = TypeSquare_Admin_Root::get_instance();
        $hooks = array(
            add_menu_page(
                __( 'TS Webfonts for XSERVER', self::$text_domain ),
				__( 'TS Webfonts for XSERVER', self::$text_domain ),
                'administrator',
                self::MENU_ID,
                array( $root, 'typesquare_admin_menu' )
            ),
        );

        foreach ($hooks as $hook) {
            add_action($hook, array( $this, 'admin_theme_script' ));
        }
    }

    public function typesquare_admin_init()
    {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if (isset($_POST['update_font_list']) && $_POST['update_font_list'] === 'on') {
            if (check_admin_referer('ts_update_site_font_settings', 'ts_update_site_font_settings')) {
                $api = TypeSquare_ST_Api::get_instance();
                $api->update_font_list();
                return;
            }
        }

        if (isset($_POST[ self::MENU_ID ]) && $_POST[ self::MENU_ID ]) {
            $nonce_key = TypeSquare_ST::OPTION_NAME;
            if (isset($_POST['typesquare_auth']) && $_POST['typesquare_auth']) {
                if (check_admin_referer($nonce_key, self::MENU_ID)) {
                    $auth = TypeSquare_ST_Auth::get_instance();
                    $param = $auth->get_auth_status();
                    if (empty($param['typesquare_id'])) {
                        $api = TypeSquare_ST_Api::get_instance();
                        $api->update_font_list();
                    }
                    $auth->update_typesquare_auth();
                }
                if (isset($_POST['typesquare_fonttheme']) && $_POST['typesquare_fonttheme']) {
                    if (check_admin_referer($nonce_key, self::MENU_ID)) {
                        $fonts = TypeSquare_ST_Fonts::get_instance();
                        $fonts->update_typesquare_settings();
                    }
                }
            }
            wp_safe_redirect(menu_page_url(self::MENU_ID, false));
        } elseif (isset($_POST['ts_update_site_font_settings']) && $_POST['ts_update_site_font_settings']) {
            if (check_admin_referer('ts_update_site_font_settings', 'ts_update_site_font_settings')) {
                $fonts = TypeSquare_ST_Fonts::get_instance();
                $fonts->update_site_font_setting();
            }
        }

        if (isset($_POST['fontThemeUseType'])) {
            if (check_admin_referer("ts_update_font_settings", 'ts_update_font_settings')) {
                $auth = TypeSquare_ST_Auth::get_instance();
                $auth->update_typesquare_auth();
                if ($_POST['fontThemeUseType'] == 1) {
                    if (isset($_POST['ts_update_font_settings']) && $_POST['ts_update_font_settings']) {
                        $fonts = TypeSquare_ST_Fonts::get_instance();
                        $fonts->disable_font_theme_setting();
                        $fonts->update_show_post_form('false');
                    }
                } elseif ($_POST['fontThemeUseType'] == 2) {
                    if (isset($_POST['ts_update_font_settings']) && $_POST['ts_update_font_settings']) {
                        if (check_admin_referer('ts_update_font_settings', 'ts_update_font_settings')) {
                            $fonts = TypeSquare_ST_Fonts::get_instance();
                            $fonts->update_font_theme_setting();
                            $fonts->update_show_post_form('false');
                        }
                    }
                } elseif ($_POST['fontThemeUseType'] == 3) {
                    $fonts = TypeSquare_ST_Fonts::get_instance();
                    $fonts->disable_font_theme_setting();
                    $fonts->update_show_post_form('true');
                } elseif ($_POST['fontThemeUseType'] == 4) {
                    if (isset($_POST['ts_update_font_pro_settings']) && $_POST['ts_update_font_pro_settings']) {
                        if (check_admin_referer('ts_update_font_pro_settings', 'ts_update_font_pro_settings')) {
                            $fonts = TypeSquare_ST_Fonts::get_instance();
                            $fonts->update_font_pro_setting();
                            $fonts->update_show_post_form('false');
                        }
                    }
                }
            }
        }

        if (!file_exists(path_join(TYPESQUARE_PLUGIN_PATH, 'inc/assets/css/font.css'))) {
            $auth = TypeSquare_ST_Auth::get_instance();
            $param = $auth->get_auth_status();
            if (!empty($param['typesquare_id'])) {
                $api = TypeSquare_ST_Api::get_instance();
                $api->update_font_list();
            }
        }
    }

    public function typesquare_admin_init_notices()
    {
        if (isset($_POST['ts_change_edit_theme'])) {
            return;
        }
        if (isset($_POST['ts_update_font_name_setting']) && $_POST['ts_update_font_name_setting']) {
            if (check_admin_referer('ts_update_font_name_setting', 'ts_update_font_name_setting')) {
                $fonts = TypeSquare_ST_Fonts::get_instance();
                if ('delete' == $_POST['ts_edit_mode']) {
                    $fonts->delete_custom_theme();
                } else {
                    $fonts->update_font_setting();
                }
            }
        }
    }
}