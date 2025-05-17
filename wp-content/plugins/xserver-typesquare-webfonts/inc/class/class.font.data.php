<?php

require_once(dirname(__FILE__).'/class.fonttheme.php');
class TypeSquare_ST_Fonts
{
    private static $instance;
    private static $text_domain;

    private function __construct()
    {
        self::$text_domain = TypeSquare_ST::text_domain();
    }

    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function load_all_font_data()
    {
        $font_theme = TypeSquare_ST_Fonttheme::get_instance();
        $font_data = $font_theme->get_fonttheme();
        if (isset($font_data['fonttheme'])) {
            return $font_data['fonttheme'];
        }
        return $font_data;
    }

    public function load_coustam_font_data()
    {
        $font_theme = TypeSquare_ST_Fonttheme::get_instance();
        $font_data = $font_theme->get_custom_fonttheme();
        return $font_data;
    }

    public function load_font_data($theme = '')
    {
        if ('' === $theme || false == $theme || 'false' == $theme) {
            $theme = $this->get_selected_fonttheme();
            $theme = $theme['font_theme'];
            if ('' === $theme || false == $theme || 'false' == $theme) {
                return false;
            }
        }
        $font_data = $this->load_all_font_data();
        if (isset($font_data[ $theme ])) {
            return $font_data[ $theme ]['fonts'];
        } else {
            return false;
        }
    }

    public function get_selected_fonttheme()
    {
        $fonththeme = $this->get_fonttheme_params();
        return $fonththeme['typesquare_themes'];
    }

    public function get_selected_post_fonttheme($post_id)
    {
        $meta = get_post_meta($post_id, 'typesquare_fonttheme', true);
        if (isset($meta['theme'])) {
            $theme = $meta['theme'];
        } elseif (isset($meta['fonts'])) {
            $theme = '';
        } else {
            $theme = $meta;
        }
        /*
        if ( '' ==  $theme || 'false' == $theme ) {
            $theme = $this->get_selected_fonttheme();
            $theme = $theme['font_theme'];
        }
        */
        return $theme;
    }

    public function get_fadein_time()
    {
        $param = $this->get_fonttheme_options();
        if (isset($param['fade_in']) && $param['fade_in']) {
            $fade_time = $param['fade_time'];
        } else {
            $fade_time = false;
        }
        return $fade_time;
    }

    public function get_auto_load_font()
    {
        $param = $this->get_fonttheme_options();
        if (isset($param['auto_load_font'])) {
            $auto_load_font = $param['auto_load_font'];
        } else {
            $auto_load_font = false;
        }
        return $auto_load_font;
    }

    public function get_apply_to_pseudo()
    {
        $param = $this->get_fonttheme_options();
        if (isset($param['apply_to_pseudo'])) {
            $apply_to_pseudo = $param['apply_to_pseudo'];
        } else {
            $apply_to_pseudo = false;
        }
        return $apply_to_pseudo;
    }


    public function get_apply_to_hidden()
    {
        $param = $this->get_fonttheme_options();
        if (isset($param['apply_to_hidden'])) {
            $apply_to_hidden = $param['apply_to_hidden'];
        } else {
            $apply_to_hidden = false;
        }
        return $apply_to_hidden;
    }

    public function get_fonttheme_keys()
    {
        return array(
            'font_theme' 			=> __('フォントテーマ', self::$text_domain),
            'title_target'    => __('見出しタグ', self::$text_domain),
            'lead_target'     => __('リードタグ', self::$text_domain),
            'text_target'     => __('本文タグ', self::$text_domain),
            'bold_target'     => __('強調タグ', self::$text_domain),
            'fade_in'         => __('フェードイン', self::$text_domain),
            'fade_time'       => __('フェード時間', self::$text_domain),
            'auto_load_font'         => __('フォント自動読み込み', self::$text_domain),
            'script_onload'	  => __('ロード時動作', self::$text_domain),
            'apply_to_pseudo'	  => __('疑似要素フォント読込', self::$text_domain),
            'apply_to_hidden'	  => __('非表示要素フォント読込', self::$text_domain),
            'show_post_form'  => __('記事ごとにフォントを設定', self::$text_domain),
        );
    }

    public function get_fonttheme_options()
    {
        // タイトル
        $title_base = "h1,h2,h3,h1:lang(ja),h2:lang(ja),h3:lang(ja),.entry-title:lang(ja)";
        //$title_comment = "#comments h2.comments-title:lang(ja)";
        //$title_comment_button = "#comments .comment-reply-link:lang(ja)";
        // リード
        $lead_base = "h4,h5,h6,h4:lang(ja),h5:lang(ja),h6:lang(ja)";

        $lead_page_header = "div.entry-meta span:lang(ja)";
        $lead_page_footer = "footer.entry-footer span:lang(ja)";
        // テキスト
        $text_base = ".hentry,.entry-content p,.post-inner.entry-content p";
        $text_comment = "#comments div:lang(ja)";
        // 強調
        $bold_base = "strong,b";
        $bold_comment = "#comments .comment-author .fn:lang(ja)";

        $default_param = array(
            'font_theme' 		=> false,
            'title_target' 	    => "{$title_base}",
            'lead_target'       => "{$lead_base},{$lead_page_header},{$lead_page_footer}",
            'text_target' 	    => "{$text_base},{$text_comment}",
            'bold_target' 	    => "{$bold_base},{$bold_comment}",
            'fade_in' 			=> true,
            'fade_time'			=> 0,
            'auto_load_font'	=> false,
            'script_onload'		=> false,
            'apply_to_pseudo'   => true,
            'apply_to_hidden'   => false,
            'show_post_form'	=> false,
        );

        $option_name = 'typesquare_fonttheme';
        $param = get_option($option_name);

        if (isset($param['fonts'])) {
            unset($param['fonts']);
        }
        if (is_array($param) && ! isset($param['show_post_form'])) {
            $param['show_post_form'] = false;
        }
        if (! $param) {
            $param = $default_param;
        } else {
            foreach ($default_param as $key => $value) {
                if (! isset($param[ $key ])) {
                    $param[ $key ] = $value;
                }
            }
        }
        // paramの中に選択したフォントのvalue値は入っている
        return $param;
    }

    public function get_fonttheme_params()
    {
        $param['typesquare_themes'] = $this->get_fonttheme_options();
        $param['typesquare_themes_keys'] = $this->get_fonttheme_keys();
        return $param;
    }

    public function update_font_setting()
    {
        if (! isset($_POST['typesquare_custom_theme'])) {
            return;
        }

        $options = get_option('typesquare_custom_theme');
        $custom_theme = sanitize_post($_POST['typesquare_custom_theme']);
        $options = $this->parse_font_setting_param($options, $custom_theme);

        if (isset($options['theme']['']['id'])) {
            $this->update_font_theme_setting();
        } else {
            update_option('typesquare_custom_theme', $options);
        }
        $result = __('フォントテーマの設定に成功しました。', self::$text_domain);
        $this->show_result($result);
    }

    public function delete_custom_theme()
    {
        if (! isset($_POST['typesquare_custom_theme'])) {
            return;
        }
        $options = get_option('typesquare_custom_theme');
        $id = sanitize_post($_POST['typesquare_custom_theme']['id']);
        unset($options['theme'][ $id ]);
        unset($options['fonts'][ $id ]);
        update_option('typesquare_custom_theme', $options);
        $result = __('フォントテーマの削除に成功しました。', self::$text_domain);
        $this->show_result($result);
    }

    public function show_result($result)
    {
        ?>
<div class='notice updated'>
    <ul>
        <li><?php echo esc_html(__($result)); ?></li>
    </ul>
</div>
<?php
    }

    public function parse_font_setting_param($current, $param)
    {
        $name = esc_attr($param['name']);
        $id = (string) esc_attr($param['id']);
        $current['theme'][ $id ]['name'] = $name;
        $current['theme'][ $id ]['id'] = $id;
        foreach ($param['fonts'] as $type => $font) {
            $type = esc_attr($type);
            foreach ($font as $key => $value) {
                if ('false' == $value) {
                    unset($current['theme'][ $id ]['fonts'][ $type ]);
                    unset($current['fonts'][ $id ][ $type ]);
                    continue;
                }
                $key = (string) esc_attr($key);
                $current['fonts'][ $id ][ $type ][ $key ] = esc_attr($value);
                $current['theme'][ $id ]['fonts'][ $type ] = esc_attr($value);
            }
        }
        return $current;
    }

    public function update_typesquare_settings()
    {
        if (! isset($_POST['typesquare_fonttheme'])) {
            return;
        }
        $options = get_option('typesquare_fonttheme');
        foreach (sanitize_post($_POST['typesquare_fonttheme']) as $key => $target) {
            $key = esc_attr($key);
            $options[ $key ] = esc_attr($target);
        }
        if (isset($_POST['typesquare_fonttheme']['fade_time'])) {
            if (! isset($_POST['typesquare_fonttheme']['fade_in'])) {
                $options['fade_in'] = false;
            }
        }
        if (! isset($_POST['typesquare_fonttheme']['auto_load_font'])) {
            $options['auto_load_font'] = false;
        }

        if (! isset($_POST['typesquare_fonttheme']['apply_to_pseudo'])) {
            $options['apply_to_pseudo'] = false;
        }

        if (! isset($_POST['typesquare_fonttheme']['apply_to_hidden'])) {
            $options['apply_to_hidden'] = false;
        }
        update_option('typesquare_fonttheme', $options);
    }

    public function disable_font_theme_setting()
    {
        if (! isset($_POST['typesquare_fonttheme'])) {
            return;
        }
        $options = get_option('typesquare_fonttheme');
        $options['font_theme'] = false;
        update_option('typesquare_fonttheme', $options);
    }

    public function update_font_theme_setting()
    {
        if (! isset($_POST['typesquare_fonttheme'])) {
            return;
        }
        $options = get_option('typesquare_fonttheme');
        foreach (sanitize_post($_POST['typesquare_fonttheme']) as $key => $target) {
            $key = esc_attr($key);
            $options[ $key ] = esc_attr($target);
        }
        update_option('typesquare_fonttheme', $options);
    }

    public function update_show_post_form($status)
    {
        $options = get_option('typesquare_fonttheme');
        $options['show_post_form'] = $status;
        update_option('typesquare_fonttheme', $options);
    }

    public function get_site_font_setting()
    {
        return get_option('typesquare_site_font_setting');
    }

    public function update_site_font_setting()
    {
        $options = array(
            "title_fontname" => isset($_POST["title_fontname"]) ? sanitize_post($_POST["title_fontname"]) : "",
            "catchcopy_fontname" => isset($_POST["catchcopy_fontname"]) ? sanitize_post($_POST["catchcopy_fontname"]) : "",
            "widget_title_fontname" => isset($_POST["widget_title_fontname"]) ? sanitize_post($_POST["widget_title_fontname"]) : "",
            "widget_fontname" => isset($_POST["widget_fontname"]) ? sanitize_post($_POST["widget_fontname"]) : "",
        );
        update_option('typesquare_site_font_setting', $options);
    }

    public function get_font_pro_setting()
    {
        return get_option('typesquare_pro_setting');
    }

    public function update_font_pro_setting()
    {
        $options = array();
        for ($i=1; $i < 20; $i++) {
            if (isset($_POST["fontlist_fontname{$i}"]) && !empty($_POST["fontlist_fontname{$i}"] && !empty($_POST["fontlist_cls{$i}"]))) {

                $post_cls = array();
                for ($j=0; $j < count($_POST["fontlist_cls{$i}"]); $j++) {
                    array_push($post_cls, sanitize_post($_POST["fontlist_cls{$i}"][$j]));
                }

                array_push($options, array(
                    "fontlist_fontname" => sanitize_post($_POST["fontlist_fontname{$i}"]),
                    "fontlist_cls" => $post_cls,
                ));
            }
        }
        update_option('typesquare_pro_setting', $options);
    }

    public function get_selected_font($type)
    {
        $font = false;
        $param = $this->get_fonttheme_options();
        if (! isset($param['fonts']) || ! isset($param['fonts'][ $type ])) {
            return $font;
        }
        $font = $param['fonts'][ $type ]['font'];
        return $font;
    }
}