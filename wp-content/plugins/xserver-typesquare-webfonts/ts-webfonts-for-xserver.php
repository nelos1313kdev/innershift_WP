<?php
/*
Plugin Name: TypeSquare Webfonts for エックスサーバー
Version: 2.0.8
Description: エックスサーバー株式会社が提供する各レンタルサーバーサービスでWebフォントを利用できるプラグインです。
Author: XSERVER Inc.
Author URI: https://www.xserver.ne.jp/
Plugin URI: https://ja.wordpress.org/plugins/xserver-typesquare-webfonts/
Text Domain: typesquare
Domain Path: /languages
*/

require_once(dirname(__FILE__).'/typesquare-admin.php');
require_once(dirname(__FILE__).'/inc/class/class.font.data.php');
require_once(dirname(__FILE__).'/inc/class/class.auth.php');
require_once(dirname(__FILE__).'/inc/class/class.api.php');
define('TYPESQUARE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TYPESQUARE_PLUGIN_URL', plugin_dir_url(__FILE__));
$ts = TypeSquare_ST::get_instance();
$ts->add_hook();
$admin = TypeSquare_Admin::get_instance();
$admin->add_hook();

class TypeSquare_ST
{
    private static $instance;
    private $styles = false;
    const OPTION_NAME = 'ts_settings';
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
        add_action('wp_enqueue_scripts', array( $this, 'load_scripts' ), 0, 3);
        add_action('wp_head', array( $this, 'load_font_styles' ));
        add_action('pre_get_posts', array( $this, 'get_archive_font_styles' ));
    }

    public static function version()
    {
        static $version;

        if (! $version) {
            $data = get_file_data(__FILE__, array( 'version' => 'Version' ));
            $version = $data['version'];
        }
        return $version;
    }

    public static function text_domain()
    {
        static $text_domain;

        if (! $text_domain) {
            $data = get_file_data(__FILE__, array( 'text_domain' => 'Text Domain' ));
            $text_domain = $data['text_domain'];
        }
        return $text_domain;
    }

    public function load_scripts()
    {
        $query = '';
        $uid = str_replace('%', '%25', $this->get_user_id());
        $version = $this->version();
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $fade_in = $fonts->get_fadein_time();
        $auto_load_font = $fonts->get_auto_load_font();
        $apply_to_pseudo = $fonts->get_apply_to_pseudo();
        $apply_to_hidden = $fonts->get_apply_to_hidden();

        if (false !== $fade_in) {
            $query .= "&fadein={$fade_in}";
        } else {
            $query .= "&fadein=-1";
        }

        if (false !== $auto_load_font) {
            $query .= "&auto_load_font=true";
        }

        if (false === $apply_to_pseudo) {
            $query .= "&apply_to_pseudo=false";
        }

        if (false !== $apply_to_hidden) {
            $query .= "&apply_to_hidden=true";
        }

        if (false !== $uid) {
            wp_register_script('typesquare_std', "//webfonts.xserver.jp/js/xserverv3.js?$query", array( 'jquery' ), $version, false);
            wp_enqueue_script( 'typesquare_std' );
        }
    }

    private function get_user_id()
    {
        $auth  = TypeSquare_ST_Auth::get_instance();
        $param = $auth->get_auth_status();
        return $param['typesquare_id'];
    }

    private function get_fonts($type = false, $post_font_data = false, $fonts = false)
    {
        $font_class = TypeSquare_ST_Fonts::get_instance();
        $selected_font = $font_class->get_selected_font($type);

        if ($selected_font) {
            $fonts = $selected_font;
        }
        if ($post_font_data) {
            if (isset($post_font_data[ $type ])) {
                $fonts = $post_font_data[ $type ]['font'];
            }
        }
        if (is_array($fonts)) {
            $text_font = '';
            foreach ($fonts as $key => $font) {
                $text_font .= '"'. esc_attr($font). '"';
                if (count($fonts) - 1 > $key) {
                    $text_font .= ',';
                }
            }
        } else {
            $text_font    = '"'. esc_attr($fonts). '"';
        }
        return $text_font;
    }

    public function load_font_styles()
    {
        if (is_archive() || is_home()) {
            if ($this->styles) {
?>
<style type='text/css'>
<?php echo wp_strip_all_tags(__($this->styles));
?>
</style>
<?php
            }
            return;
        }
        $auth  = TypeSquare_ST_Auth::get_instance();
        $param = $auth->get_auth_status();
        if ('' === $param['typesquare_id']) {
            return;
        }

        $site_style = $this->_get_site_font_styles();
        if (!empty($site_style)) {
?>
<style type='text/css'>
<?php echo wp_strip_all_tags(__($site_style));
?>
</style>
<?php
        }

        $fonts = TypeSquare_ST_Fonts::get_instance();
        $font_pro_settings = $fonts->get_font_pro_setting();
        if ($font_pro_settings && $param['fontThemeUseType'] == 4) {
            $style = $this->_get_pro_font_styles($font_pro_settings);
        } else {
            $fonttheme = $fonts->get_selected_fonttheme();
            if (! isset($fonttheme) && ! $fonttheme) {
                return;
            }
            $use_font = $fonts->load_font_data($fonttheme['font_theme']);
            if (is_singular()) {
                $param = $fonts->get_fonttheme_params();
                if (isset($param['typesquare_themes']['show_post_form']) && 'false' != $param['typesquare_themes']['show_post_form']) {
                    $post_theme = $fonts->get_selected_post_fonttheme(get_the_ID());
                    $post_theme = $fonts->load_font_data($post_theme);
                    if ($post_theme) {
                        $use_font = $post_theme;
                    }
                }
            }
            $style = $this->_get_font_styles($use_font, $fonttheme);
        }

        if ($style) {
?>
<style type='text/css'>
<?php echo wp_strip_all_tags(__($style));
?>
</style>
<?php
        }
    }

    public function get_archive_font_styles($query)
    {
        if (is_admin() || ! $query->is_main_query() || is_singular()) {
            return;
        }
        $auth  = TypeSquare_ST_Auth::get_instance();
        $param = $auth->get_auth_status();
        if ('' === $param['typesquare_id']) {
            return;
        }

        $fonts = TypeSquare_ST_Fonts::get_instance();

        $this->styles = "";
        $site_style = $this->_get_site_font_styles();
        if (!empty($site_style)) {
            $this->styles = $site_style;
        }

        $fonttheme = $fonts->get_selected_fonttheme();
        if (! isset($fonttheme) && ! $fonttheme) {
            return;
        }
        $font_param = $fonts->get_fonttheme_params();
        $use_font = $fonts->load_font_data($fonttheme['font_theme']);
        $font_pro_settings = $fonts->get_font_pro_setting();
        if ($font_pro_settings && $param['fontThemeUseType'] == 4) {
            $style = $this->_get_pro_font_styles($font_pro_settings);
            $this->styles .= $style;
            return;
        }

        if (! $query->query) {
            $query->query = apply_filters('ts-default-query', array(
                'post_type' => 'post',
            ));
        }

        $the_query = new WP_Query($query->query);
        $style = "";
        while ($the_query->have_posts()) : $the_query->the_post();
        $id = get_the_ID();
        if (isset($font_param['typesquare_themes']['show_post_form']) && 'false' != $font_param['typesquare_themes']['show_post_form']) {
            $post_theme = $fonts->get_selected_post_fonttheme($id);
            $post_theme = $fonts->load_font_data($post_theme);
            if ($post_theme) {
                $use_font = $post_theme;
            }
        }
        $style .= $this->_get_font_styles($use_font, $fonttheme, $id);
        endwhile;
        wp_reset_postdata();

        if ($style) {
            $this->styles .= $style;
        }
    }

    private function _merge_post_id_to_target($post_key, $target_text)
    {
        $target_list = explode(',', $target_text);
        $merged_target = false;
        foreach ($target_list as $target) {
            if ('.hentry' == $target) {
                $merged_target .= "{$post_key}{$target},";
            } else {
                $merged_target .= "{$post_key} {$target},";
            }
        }
        $merged_target = rtrim($merged_target, ',');
        return $merged_target;
    }

    private function _get_font_styles($use_font, $fonttheme, $post_id = false, $post_font_data = false)
    {
        $style  = '';
        if ($post_id) {
            $post_key = '#post-'. $post_id;
            $title_target = $this->_merge_post_id_to_target($post_key, esc_attr($fonttheme['title_target']));
            $lead_target  = $this->_merge_post_id_to_target($post_key, esc_attr($fonttheme['lead_target']));
            $text_target  = $this->_merge_post_id_to_target($post_key, esc_attr($fonttheme['text_target']));
            $bold_target  = $this->_merge_post_id_to_target($post_key, esc_attr($fonttheme['bold_target']));
        } else {
            $title_target = esc_attr($fonttheme['title_target']);
            $lead_target  = esc_attr($fonttheme['lead_target']);
            $text_target  = esc_attr($fonttheme['text_target']);
            $bold_target  = esc_attr($fonttheme['bold_target']);
        }

        $title_font = $lead_font = $text_font = $bold_font = false;
        if (isset($use_font['title'])) {
            $title_font   = $this->get_fonts('title', $post_font_data, $use_font['title']);
        }
        if (isset($use_font['lead'])) {
            $lead_font    = $this->get_fonts('lead', $post_font_data, $use_font['lead']);
        }
        if (! isset($use_font['content']) && isset($use_font['text'])) {
            $use_font['content'] = $use_font['text'];
        }
        if (isset($use_font['content'])) {
            $text_font    = $this->get_fonts('text', $post_font_data, $use_font['content']);
        }
        if (isset($use_font['bold'])) {
            $bold_font    = $this->get_fonts('bold', $post_font_data, $use_font['bold']);
        }

        if ($title_target && $title_font) {
            $style .= "{$title_target}{ font-family: {$title_font};}";
        }
        if ($lead_target && $lead_font) {
            $style .= "{$lead_target}{ font-family: {$lead_font};}";
        }
        if ($text_target && $text_font) {
            $style .= "{$text_target}{ font-family: {$text_font};}";
        }
        if ($bold_target && $bold_font) {
            $style .= "{$bold_target}{ font-family: {$bold_font};}";
        }

        return $style;
    }

    private function _get_site_font_styles()
    {
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $site_font_settings = $fonts->get_site_font_setting();
        $title_fontname = '';
        $catchcopy_fontname = '';
        $widget_title_fontname = '';
        $widget_fontname = '';
        if (is_array($site_font_settings)) {
            $title_fontname = $site_font_settings['title_fontname'];
            $catchcopy_fontname = $site_font_settings['catchcopy_fontname'];
            $widget_title_fontname = $site_font_settings['widget_title_fontname'];
            $widget_fontname = $site_font_settings['widget_fontname'];
        }
        $style = '';
        if ($title_fontname) {
            $title_target = ".site-branding .site-title a:lang(ja),.site-title";
            $style .= "{$title_target}{ font-family: '{$title_fontname}';}";
        }
        if ($catchcopy_fontname) {
            $catchcopy_target = ".site-description:lang(ja)";
            $style .= "{$catchcopy_target}{ font-family: '{$catchcopy_fontname}';}";
        }
        if ($widget_title_fontname) {
            $widget_title_target = "section.widget h2:lang(ja),.widget-title";
            $style .= "{$widget_title_target}{ font-family: '{$widget_title_fontname}';}";
        }
        if ($widget_fontname) {
            $widget_target = "section.widget ul li:lang(ja),.widget-content ul li";
            $style .= "{$widget_target}{ font-family: '{$widget_fontname}';}";
        }

        return $style;
    }

    private function _get_pro_font_styles($font_pro_settings, $post_id = false)
    {
        $style = '';
        foreach ($font_pro_settings as $font_pro_setting) {
            foreach ($font_pro_setting['fontlist_cls'] as $fontlist_cls) {
                $target = esc_attr($fontlist_cls);
                if ($post_id) {
                    $post_key = '#post-'. $post_id;
                    if ('.hentry' == $target) {
                        $target = "{$post_key}{$target}";
                    } else {
                        $target = "{$post_key} {$target}";
                    }
                }
                $fontf_amily = $font_pro_setting['fontlist_fontname'];
                $style .= "{$target}{ font-family: '{$fontf_amily}';}";
            }
        }
        return $style;
    }
}

add_filter('mce_buttons', function ($buttons) {
    $auth  = TypeSquare_ST_Auth::get_instance();
    $param = $auth->get_auth_status();
    if ('' !== $param['typesquare_id']) {
        $buttons[] = 'fontfamily_button';
    }
    return $buttons;
});

register_uninstall_hook(__FILE__, 'ts_uninstall');
function ts_uninstall()
{
    delete_option('typesquare_auth');
    delete_option('typesquare_fonttheme');
    delete_option('typesquare_custom_theme');
    delete_option('typesquare_pro_setting');
    delete_option('typesquare_site_font_setting');
    return;
}