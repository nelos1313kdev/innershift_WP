<?php
class TypeSquare_Admin_Root extends TypeSquare_Admin_Base
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

    public function typesquare_post_metabox()
    {
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $param = $fonts->get_fonttheme_params();
        if ('false' == $param['typesquare_themes']['show_post_form'] || ! $param['typesquare_themes']['show_post_form']) {
            return;
        }
        $post_type = array( 'post', 'page' );
        foreach ($post_type as $type) {
            add_meta_box('typesquare_post_metabox', __('TypeSquare Webfonts for XSERVER', self::$text_domain), array( $this, 'typesquare_post_metabox_inside' ), $type, 'advanced', 'low');
        }
    }

    public function typesquare_post_metabox_inside()
    {
        ?>
<p>この記事に適用するフォントを選択してください</p>
<?php $this->_get_post_font_theme_selector(); ?>
<input type="hidden" name="typesquare_nonce_postmeta" id="typesquare_nonce_postmeta"
    value="<?php echo esc_html(__(wp_create_nonce(plugin_basename(__FILE__)))); ?>" />
<?php
    }

    private function _get_post_font_theme_selector()
    {
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $all_font_theme = $fonts->load_all_font_data();
        $selected_theme = $fonts->get_selected_post_fonttheme(get_the_ID()); ?>
<h3>フォントテーマから選ぶ</h3>
<script>
jQuery(document).ready(function() {
    window.onload = function() {
        theme_preview_new();
    };

    jQuery('#choiceThemeNew').change(function() {
        theme_preview_new();
    });

    function theme_preview_new() {
        var select_val = jQuery('.font_theme_select_pre_new option:selected').val();
        if (select_val === "false") {
            jQuery('.pre_title').text("");
            jQuery('.pre_lead').text("");
            jQuery('.pre_body').text("");
            jQuery('.pre_bold').text("");
        } else {
            var val = document.getElementById("selected-get-" + select_val);
            var theme_data = JSON.parse(val.value);

            jQuery('#theme_preview').val(val);

            jQuery('.pre_title').text(theme_data.fonts.title);
            jQuery('.pre_title').css("font-family", `'${theme_data.fonts.title}'`);

            jQuery('.pre_lead').text(theme_data.fonts.lead);
            jQuery('.pre_lead').css("font-family", `'${theme_data.fonts.lead}'`);

            if (theme_data.fonts.content) {
                jQuery('.pre_body').text(theme_data.fonts.content);
                jQuery('.pre_body').css("font-family", `'${theme_data.fonts.content}'`);
            } else {
                jQuery('.pre_body').text(theme_data.fonts.text);
                jQuery('.pre_body').css("font-family", `'${theme_data.fonts.text}'`);
            }

            jQuery('.pre_bold').text(theme_data.fonts.bold);
            jQuery('.pre_bold').css("font-family", `'${theme_data.fonts.bold}'`);
        }
    }
});
</script>
<div id="choiceThemeNew">
    <select name='typesquare_fonttheme[theme]' class='font_theme_select_pre_new'>
        <option value='false'>テーマを設定しない</option>
        <?php
                    foreach ($all_font_theme as $key => $fonttheme) {
                        $fonttheme_name = $this->get_fonts_text($fonttheme['name']);
                        $font_text = $this->_get_fonttheme_text($fonttheme);
                        $selected = '';
                        if ($key === $selected_theme) {
                            $selected = 'selected';
                        } ?>
        <option value='<?php echo esc_html(__($key)); ?>' <?php echo esc_html(__($selected)); ?>>
            <?php echo esc_html(__("{$fonttheme_name} ( {$font_text} )")); ?>
        </option>
        <?php
                    } ?>
    </select>
    <h3>プレビュー</h3>
    <div>
        <p class="title">見出し：
        <p class="pre_title"></p>
        </p>
    </div>
    <div>
        <p class="title">リード：
        <p class="pre_lead"></p>
        </p>
    </div>
    <div>
        <p class="title">本文：
        <p class="pre_body"></p>
        </p>
    </div>
    <div>
        <p class="title">太字：
        <p class="pre_bold"></p>
        </p>
    </div>
    <input type="hidden" id="theme_preview" />
    <?php
        foreach ($all_font_theme as $fonttheme_key => $fonttheme) {
            ?>
    <input type='hidden' id='selected-get-<?php echo esc_html(__($fonttheme_key)); ?>'
        value='<?php echo wp_strip_all_tags(__(wp_json_encode($fonttheme))); ?>'>
    <?php
        } ?>
</div>
<?php
    }

    public function typesquare_save_post($post_id)
    {
        if (! isset($_POST['typesquare_nonce_postmeta'])) {
            return;
        }
        //Verify
        if (! wp_verify_nonce($_POST['typesquare_nonce_postmeta'], plugin_basename(__FILE__))) {
            return $post_id;
        }
        // if auto save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // permission check
        if ('page' == $_POST['post_type']) {
            if (! current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {
            if (! current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }

        // save action
        $fonttheme = sanitize_post($_POST['typesquare_fonttheme']);
        $current_option = get_post_meta($post_id, 'typesquare_fonttheme');
        $fonts = TypeSquare_ST_Fonts::get_instance();
        if (isset($current_option[0])) {
            $current = $current_option[0];
        } else {
            $current = $fonttheme;
        }
        $font['theme'] = esc_attr($fonttheme['theme']);
        update_post_meta($post_id, 'typesquare_fonttheme', $font);
        return $post_id;
    }

    private function get_fonts_text($fonts)
    {
        if (is_array($fonts)) {
            $text_font = '';
            foreach ($fonts as $key => $font) {
                $text_font .= esc_attr($font);
                if (count($fonts) - 1 > $key) {
                    $text_font .= ' + ';
                }
            }
        } else {
            $text_font    = esc_attr($fonts);
        }
        return $text_font;
    }

    public function typesquare_admin_menu()
    {
        $param = $this->get_auth_params();
        $option_name = 'typesquare_auth';
        $nonce_key = TypeSquare_ST::OPTION_NAME;
        $auth_param = $this->get_auth_params(); ?>
<style type='text/css' rel='stylesheet'></style>
<div class='wrap'>
    <h2>TypeSquare Webfonts Plugin for XSERVER</h2>
    <?php
                do_action('typesquare_add_setting_before'); ?>
    <hr />
    <span>
        <h3 class="toggleText toggleAdvanced_font mTop20">投稿記事フォント設定
            <span class="advancedTriangle advancedTriangle_font">▼</span>
        </h3>
    </span>
    <div class='ts-custome_form_font hidden'>
        <?php $this->_get_post_font_form($auth_param); ?>
    </div>
</div>
<hr />
<span>
    <h3 class="toggleText toggleAdvanced_site mTop20">サイトフォント設定
        <span class="advancedTriangle advancedTriangle_site">▼</span>
    </h3>
</span>
<div class='ts-custome_form_site hidden'>
    <?php $this->_get_site_font_form(); ?>
</div>
<hr />
<span>
    <h3 class="toggleText toggleAdvanced_article mTop20">カスタムテーマ編集
        <span class="advancedTriangle advancedTriangle_article">▼</span>
    </h3>
</span>
<div class='ts-custome_form_article hidden'>
    <div class="ts-custum_block">
        <?php $this->_get_font_theme_custom_form(); ?>
    </div>
</div>
<?php do_action('typesquare_add_setting_after'); ?>
<?php
    }

    private function _get_post_font_form($auth_param)
    {?>
<form method='post' action=''>
    <p>投稿記事に対するWebフォント適用方法を設定します。</p>
    <div class="block_wrap">
        <p class="block_title">投稿記事に適用するフォント設定</p>
        <div class="label_wrapper">
            <label class="custum_form_ladio"><input name="fontThemeUseType" type="radio" value="1">指定しない
                <p class="setting_read">フォントテーマを適用しません。</p>
            </label>
            <label class="custum_form_ladio"><input name="fontThemeUseType" type="radio" value="2"
                    class="radio_custum_font_theme">共通テーマ指定
                <p class="setting_read">全ての投稿に設定したフォントテーマが適用されます。</p>
            </label>
            <?php $this->_get_font_theme_form() ?>
            <label class="custum_form_ladio"><input name="fontThemeUseType" type="radio" value="3">個別テーマ指定
                <p class="setting_read">投稿ごとに設定したフォントテーマが適用されます。</p>
            </label>
            <label class="custum_form_ladio"><input name="fontThemeUseType" type="radio" value="4">直接指定（上級者向け）
                <p class="setting_read">CSSセレクターを指定して適用するウェブフォントを選べます。</p>
            </label>
            <?php $this->_get_font_target_form() ?>
            <?php

        $fonts = TypeSquare_ST_Fonts::get_instance();
        $fonttheme_params = $fonts->get_fonttheme_params();

        if (empty($auth_param['typesquare_auth']['fontThemeUseType'])) {
            if (empty($fonttheme_params['typesquare_themes']['font_theme']) || $fonttheme_params['typesquare_themes']['font_theme'] === "false") {
                ?><input type="hidden" id="activeAdvanced_theme" value="1"><?php
            } else {
                ?><input type="hidden" id="activeAdvanced_theme" value="2"><?php
            }
        } else {
            if (!empty($fonttheme_params['typesquare_themes']['font_theme']) && $auth_param['typesquare_auth']['fontThemeUseType'] === "1") {
                ?><input type="hidden" id="activeAdvanced_theme" value="2"><?php
            } else {
                ?><input type="hidden" id="activeAdvanced_theme"
                value="<?php echo esc_html(__($auth_param['typesquare_auth']['fontThemeUseType'])); ?>"><?php
            }
        }
        ?>
        </div>
    </div>
    <?php echo get_submit_button(__('投稿フォント設定を保存する', self::$text_domain)); ?>
</form>
<?php
    }

    private function _get_font_theme_form()
    {
        $option_name = 'typesquare_fonttheme';
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $param = $fonts->get_fonttheme_params(); ?>
<?php echo wp_nonce_field('ts_update_font_settings', 'ts_update_font_settings', true, false); ?>
<div id="choiceTheme" class="font_custum_select">
    <select name='<?php echo esc_html(__($option_name)); ?>[font_theme]' class='font_theme_select_pre'>
        <?php
        $all_font_theme = $fonts->load_all_font_data();
        foreach ($all_font_theme as $fonttheme_key => $fonttheme) {
            $fonttheme_name = esc_attr($fonttheme['name']);
            $font_text = $this->_get_fonttheme_text($fonttheme);
            $selected	= '';
            if ($fonttheme_key == $param['typesquare_themes']['font_theme']) {
                $selected = 'selected';
            } ?>
        <option value='<?php echo esc_html(__($fonttheme_key)); ?>' <?php echo esc_html(__($selected)); ?>>
            <?php echo esc_html(__($fonttheme_name)); ?> ( <?php echo esc_html(__($font_text)); ?> )
        </option>
        <?php
        } ?>
    </select>
    <h3>プレビュー</h3>
    <div>
        <p class="title">見出し：
        <p class="pre_title"></p>
        </p>
    </div>
    <div>
        <p class="title">リード：
        <p class="pre_lead"></p>
        </p>
    </div>
    <div>
        <p class="title">本文：
        <p class="pre_body"></p>
        </p>
    </div>
    <div>
        <p class="title">太字：
        <p class="pre_bold"></p>
        </p>
    </div>
    <input type="hidden" id="theme_preview" />
    <?php
        foreach ($all_font_theme as $fonttheme_key => $fonttheme) {
            ?>
    <input type='hidden' id='selected-get-<?php echo esc_html(__($fonttheme_key)); ?>'
        value='<?php echo wp_strip_all_tags(__(wp_json_encode($fonttheme))); ?>'>
    <?php
        } ?>
    <?php echo wp_nonce_field('ts_update_font_settings', 'ts_update_font_settings', true, false); ?>
    <?php

        $value = esc_attr($param['typesquare_themes']['show_post_form']) == "true" ? true : false; ?>
</div>
<input type="hidden" name="typesquare_nonce_postmeta" id="typesquare_nonce_postmeta"
    value="<?php echo esc_html(__(wp_create_nonce(plugin_basename(__FILE__)))); ?>" />
<?php
    }

    private function _get_font_theme_custom_form()
    {
        $option_name = 'typesquare_fonttheme';
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $param = $fonts->get_fonttheme_params();
        if (isset($_POST['typesquare_fonttheme']['font_theme']) &&  $_POST['typesquare_fonttheme']['font_theme'] !== 'new') {
            $param['typesquare_themes']['font_theme'] = sanitize_post($_POST['typesquare_fonttheme']['font_theme']);
        } ?>
<form method='post' action='' id='custmeFontForm' class='b__font_theme_form'>
    <p>お好きなフォントを組み合わせてテーマを作成できます。また、作成したテーマの編集ができます。</p>
    <?php echo wp_nonce_field('ts_update_font_settings', 'ts_update_font_settings', true, false); ?>
    <div class="font_custum_select">
        <select id='fontThemeSelect' name='<?php echo esc_html(__($option_name)); ?>[font_theme]'>
            <option value='new' selected>新しくテーマを作成する</option>
            <?php
        $coustam_font_theme = $fonts->load_coustam_font_data();
        if (!empty($coustam_font_theme)) {
            foreach ($coustam_font_theme as $fonttheme_key => $fonttheme) {
                $fonttheme_name = esc_attr($fonttheme['name']);
                $font_text = $this->_get_fonttheme_text($fonttheme);
                $selected	= ''; ?>
            <option value='<?php echo esc_html(__($fonttheme_key)); ?>' <?php echo esc_html(__($selected)); ?>>
                <?php echo esc_html(__($fonttheme_name)); ?> ( <?php echo esc_html(__($font_text)); ?> )
            </option>
            <?php
            }
        } ?>
        </select>
        <?php
        if (!empty($coustam_font_theme)) {
            foreach ($coustam_font_theme as $fonttheme_key => $fonttheme) {
                ?>
        <input type='hidden' id='<?php echo esc_html(__($fonttheme_key)); ?>'
            value='<?php echo wp_strip_all_tags(__(wp_json_encode($fonttheme))); ?>'>
        <?php
            }
        } ?>
    </div>
    <?php $this->_get_custome_font_theme_list_form(); ?>
    <table>
        <th>
            <?php echo get_submit_button(__('テーマを保存する', self::$text_domain), 'primary', 'fontThemeUpdateButton'); ?>
        </th>

        <th>
            <?php
            $style = array("style"=>"margin-top:15px; display:none;"); ?>
            <?php echo get_submit_button(__('テーマを削除する', self::$text_domain), 'delete', 'fontThemeDeleteButton', null, $style); ?>
        </th>
    </table>
    <input type="hidden" name="typesquare_nonce_postmeta" id="typesquare_nonce_postmeta"
        value="<?php echo esc_html(__(wp_create_nonce(plugin_basename(__FILE__)))); ?>" />
</form>
<?php
    }

    private function _get_custome_font_theme_list_form()
    {
        ?>
<input type='hidden' name='ts_edit_mode' value='new' />
<input type="hidden" id="ts_custome_theme_id" name="typesquare_custom_theme[id]"
    value="<?php echo esc_html(__(uniqid())); ?>" />
<div id='customeFontThemeForm'>
    <?php echo wp_nonce_field('ts_update_font_name_setting', 'ts_update_font_name_setting', true, false); ?>
    <table class='widefat' style='border: 0px'>
        <tbody>
            <tr>
                <th width='240px' style='padding-left:0;'>テーマタイトル</th>
                <td>
                    <input type='hidden' id='current_custome_font_name' name='typesquare_custom_theme[name]' value='' />
                    <input type='text' id='custome_font_name' name='typesquare_custom_theme[name]' value=''
                        maxlength='16' style='width:50%;' required />
                </td>
            </tr>
            <?php echo esc_html(__($this->_get_site_font_form_tr("見出し", "", "typesquare_custom_theme[fonts][title][type]"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("リード", "", "typesquare_custom_theme[fonts][lead][type]"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("本文", "", "typesquare_custom_theme[fonts][text][type]"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("太字", "", "typesquare_custom_theme[fonts][bold][type]"))); ?>
        </tbody>
    </table>
    <div id='ts-react-search-font'></div>
</div>
<?php
        $font_list = array(); ?>
<?php echo esc_html(__($this->_get_script($font_list))); ?>
<?php
    }

    private function _get_site_font_form()
    {
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $value = $fonts->get_site_font_setting(); ?>
<form method='post' action='' id='siteFontForm'>
    <p>タイトルなど、ウェブサイト共通部分のフォント設定です</p>
    <table class="widefat form-table">
        <tbody>
            <?php
        if (is_array($value)) {
            ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("サイトタイトル", $value['title_fontname'], "title_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("サイトキャッチコピー", $value['catchcopy_fontname'], "catchcopy_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("ウィジェットタイトル", $value['widget_title_fontname'], "widget_title_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("ウィジェット", $value['widget_fontname'], "widget_fontname"))); ?>
            <?php
        } else {
            ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("サイトタイトル", "", "title_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("サイトキャッチコピー", "", "catchcopy_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("ウィジェットタイトル", "", "widget_title_fontname"))); ?>
            <?php echo esc_html(__($this->_get_site_font_form_tr("ウィジェット", "", "widget_fontname"))); ?>
            <?php
        } ?>
        </tbody>
    </table>
    <?php echo get_submit_button(__('サイトフォント設定を保存する', self::$text_domain)); ?>
    <?php echo wp_nonce_field('ts_update_site_font_settings', 'ts_update_site_font_settings', true, false); ?>
</form>
<?php
    }

    private function _get_site_font_form_tr($title, $value, $input_name)
    {
        ?>
<tr>
    <th style='padding-left:0;'><span><?php echo esc_html(__($title)); ?></span></th>
    <td class="font_table_td">
        <div class="w_font_select">
            <?php
        $value_font = '"' . $value . '"'; ?>
            <input class='fontlist_input' autocomplete='off' value='<?php echo esc_html(__($value)); ?>'
                style='font-family: <?php echo esc_html(__($value_font)); ?>' placeholder='未設定' />
            <div class='w_fontlist b_def_fontlist'></div>
        </div>
    </td><input value='<?php echo esc_html(__($value)); ?>' type='hidden'
        name='<?php echo esc_html(__($input_name)); ?>' id='fontlist_select' class='fontlist_select' />
</tr>
<?php
    }

    private function _get_script($font_list)
    {
        $options = get_option('typesquare_custom_theme');
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $all_font_theme = $fonts->load_all_font_data();
        ?>
<script>
<?php
        $endpoint = path_join(TYPESQUARE_PLUGIN_URL, 'inc/font.json'); ?>
var json_endpoint = '<?php echo esc_html(__($endpoint)); ?>';
<?php
        if ($font_list) {
            ?>
var current_font = <?php echo wp_strip_all_tags(__(wp_json_encode($font_list))); ?>;
<?php
        } else {
            ?>
var current_font = false;
<?php
        } ?>
var form_id = '#<?php echo esc_html(__(self::MENU_FONTTHEME)); ?>';
var notify_text = 'フォントを１種類以上選択してください。';
var unique_id = '<?php echo  esc_html(__(uniqid())); ?>';
var option_font_list = <?php echo wp_strip_all_tags(__(wp_json_encode($options))); ?>;
var plugin_base = '<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>';
var all_font_list = <?php echo wp_strip_all_tags(__(wp_json_encode($all_font_theme))); ?>;
jQuery(document).ready(function() {
    jQuery(form_id).submit(function() {
        var title = jQuery('select[name="typesquare_custom_theme[fonts][title][font]"]').val();
        var lead = jQuery('select[name="typesquare_custom_theme[fonts][lead][font]"]').val();
        var text = jQuery('select[name="typesquare_custom_theme[fonts][text][font]"]').val();
        var bold = jQuery('select[name="typesquare_custom_theme[fonts][bold][font]"]').val();
        if (
            title === 'false' &&
            lead === 'false' &&
            text === 'false' &&
            bold === 'false'
        ) {
            alert(notify_text);
            return false;
        }
    });
});
</script>
<?php
    }

    private function _get_fonttheme_text($fonttheme)
    {
        $font_text = '';
        if (isset($fonttheme['fonts']['title'])) {
            $font_text .= __('見出し：', self::$text_domain);
            $font_text .= $this->get_fonts_text($fonttheme['fonts']['title']);
            $font_text .= ',';
        }
        if (isset($fonttheme['fonts']['lead'])) {
            $font_text .= __('リード：', self::$text_domain);
            $font_text .= $this->get_fonts_text($fonttheme['fonts']['lead']);
            $font_text .= ',';
        }
        if (isset($fonttheme['fonts']['content'])) {
            $font_text .= __('本文：', self::$text_domain);
            $font_text .= $this->get_fonts_text($fonttheme['fonts']['content']);
            $font_text .= ',';
        }
        if (isset($fonttheme['fonts']['text'])) {
            $font_text .= __('本文：', self::$text_domain);
            $font_text .= $this->get_fonts_text($fonttheme['fonts']['text']);
            $font_text .= ',';
        }
        if (isset($fonttheme['fonts']['bold'])) {
            $font_text .= __('太字：', self::$text_domain);
            $font_text .= $this->get_fonts_text($fonttheme['fonts']['bold']);
        }
        $font_text = rtrim($font_text, ',');
        $font_text = str_replace(",", " / ", $font_text);
        return $font_text;
    }

    private function _get_font_target_form()
    {
        $fonts = TypeSquare_ST_Fonts::get_instance();
        $array_input = $fonts->get_font_pro_setting(); ?>
<div class='b__font_target_form'>
    <table class='widefat form-table'>
        <thead>
            <tr>
                <th style='width: 25%; padding-left: 45px;'>フォント選択</th>
                <th style='width: 75%; padding-left: 14px;'>適用箇所(CSSセレクターで指定)</th>
            </tr>
        </thead>
        <tbody class='cls_tb'>
            <?php
        if (!empty($array_input)) {
            $i = 0;
            foreach ($array_input as $key => $value) {
                $i++; ?>
            <tr id='font_setting'>
                <td>
                    <div class='cls_delete_btn cls_delete_btn_selected'>×</div>
                    <input id='fontlist_input' class='fontlist_input' autocomplete='off'
                        value='<?php echo esc_html(__($value['fontlist_fontname'])); ?>'
                        style='font-family: <?php echo esc_html(__($value['fontlist_fontname'])); ?>' />
                    <div class='w_fontlist b_def_fontlist'></div>
                </td>
                <td class='add_class_td'><input type='text' class='class_input' value='' /><button type='button'
                        id='add_box' class='add_box'>追加</button>
                    <?php
                $cls_name = $value['fontlist_cls'];
                foreach ($cls_name as $cls_value) {
                    ?>
                    <p class='add_class_label'><?php echo esc_html(__($cls_value)); ?>　×
                        <input name='fontlist_cls<?php echo esc_html(__($i)); ?>[]' type='hidden'
                            value='<?php echo esc_html(__($cls_value)); ?>' />
                    </p>
                    <?php
                }; ?>
                </td>
                <input value='<?php echo esc_html(__($value['fontlist_fontname'])); ?>' type='hidden'
                    name='fontlist_fontname<?php echo esc_html(__($i)); ?>' id='fontlist_select'
                    class='fontlist_select' />
            </tr>
            <?php
            };
        } else {
            ?>
            <tr id='font_setting'>
                <td>
                    <div class='cls_delete_btn cls_delete_btn_selected'>×</div>
                    <input id='fontlist_input' class='fontlist_input' autocomplete='off' placeholder='未設定'>
                    <div class='w_fontlist'>
                        <ul class='font_select_menu'></ul>
                    </div>
                </td>
                <td class='add_class_td'><input type='text' class='class_input' value='' /><button type='button'
                        id='add_box' class='add_box'>追加</button></td>
                <input value='' type='hidden' name='fontlist_fontname1' id='fontlist_select' class='fontlist_select' />
            </tr>
            <?php
        }; ?>
            <tr id='addbtn_tr'>
                <td>
                    <button type='button' id='input_add_btn'>+</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php echo wp_nonce_field('ts_update_font_pro_settings', 'ts_update_font_pro_settings', true, false); ?>
<?php
    }
}