jQuery(function () {

    /**
     * 投稿記事フォント設定
     */
    
        // 追加ボタンを押してクラスを羅列するための処理
        jQuery(document).on("click", ".add_box", function () {
            var input_text = jQuery(this).prev('input').val();
    
            // 入力フォームに入力されていたらラベル追加
            if (input_text) {
                let label_p = document.createElement('p');
                label_p.innerText = input_text + "　×";
    
                jQuery(label_p).addClass("add_class_label");
                jQuery(this).parent().append(label_p);
    
                // 送信するためにinputに格納
                var fontname_input_num = jQuery(this).parents('td.add_class_td').next('input#fontlist_select').attr('name');
                var num = fontname_input_num.replace(/[^0-9]/g, "");
    
                let font_select_value = document.createElement('input');
                font_select_value.value = input_text;
                font_select_value.type = 'hidden';
                font_select_value.name = 'fontlist_cls' + num + '[]';
                jQuery(label_p).append(font_select_value);
            }
    
            // inputのテキストを空にする
            jQuery(this).prev().val('');
        });
    
        // クラス名のラベルをクリックして消す
        jQuery(document).on("click", ".add_class_label", function () {
            jQuery(this).remove();
        });
    
        var fontlist_input_length = jQuery('input#fontlist_input').length;
        var count = fontlist_input_length + 1;
    
        // 設定を追加するボタンでテーブルの行を追加
        jQuery('#input_add_btn').on('click', function () {
            var tr_length = jQuery("tbody.cls_tb").children('tr#font_setting').length;
            if (tr_length < 10) {
                // 追加された行にフォントリストのデータをいれるため取得
                let add_tr = document.createElement('tr');
                add_tr.id = "font_setting";
                jQuery(add_tr).append('<td><div class="cls_delete_btn cls_delete_btn_selected">×</div><input id="fontlist_input" class="fontlist_input" autocomplete="off" placeholder="未設定"/><div class="w_fontlist" id="w_fontlist' + count + '"></div></td><td class="add_class_td"><input type="text" class="class_input" value=""/><button type="button" class="add_box" id="add_box">追加</button></td>');
    
                var fontselect_hidden = document.createElement('input');
                fontselect_hidden.name = "fontlist_fontname" + count;
                fontselect_hidden.type = "hidden";
                fontselect_hidden.id = "fontlist_select";
                jQuery(add_tr).append(fontselect_hidden);
                jQuery(add_tr).insertBefore('#addbtn_tr');
    
                jQuery('.font_select_menu:last').clone(true).appendTo('#w_fontlist' + count);
                count++;
            }
        });
    
        jQuery(document).on("click", ".cls_delete_btn", function () {
            jQuery(this).parents('#font_setting').remove();
        });
    
    
    
    /**
     * フォント検索・選択共通処理
     */
    
         // フォント選択リストを生成する
        var fontlist_creater = function (data, append_target) {
            // 一段目のフォント名のループ
            for (var i = 0; i < data.length; i++) {
                let subopt = document.createElement('ul');
                jQuery(subopt).addClass('subclass');
                // 二段目のフォント名のループ
                let sub = data[i]["font-family"];
                for (var j = 0; j < sub.length; j++) {
                    let suboptlist = document.createElement('li');
                    jQuery(suboptlist).addClass("font-name");
                    let subfont = document.createElement('a');
                    subfont.text = sub[j]["font-name"];
                    jQuery(subfont).css('font-family', `'${sub[j]["font-name"]}'`);
                    jQuery(suboptlist).append(subfont);
    
                    jQuery(subopt).append(suboptlist);
                }
    
                let opt = document.createElement('li');
                jQuery(opt).addClass("font-family-name");
                let mainfont = document.createElement('a');
                jQuery(mainfont).css('font-family', `'${data[i]['fontstyle-name']}'`);
                mainfont.text = data[i]["main-font-name"];
                jQuery(opt).append(mainfont);
                jQuery(opt).append(subopt);
    
                jQuery(append_target).append(opt);
            }
        }
    
        // フォントデータ取得
        if (typeof json_endpoint !== 'undefined') {     
            jQuery.ajax({
                type: "GET",
                url: json_endpoint,
                dataType: 'json',
                cache: false,
                success: (function (data) {
                    var fontselect_hidden = document.createElement('ul');
                    fontselect_hidden.id = "font_select_menu_2";
                    jQuery(fontselect_hidden).addClass('font_select_menu');
        
                    var add_tr = jQuery('div.b_def_fontlist');
                    jQuery(add_tr).append(fontselect_hidden);
        
                    fontlist_creater(data, 'ul.font_select_menu');
        
                    jQuery("ul.font_select_menu li").hover(
                        function () {
                            jQuery("ul:not(:animated)", this).slideDown("fast")
                        },
                        function () {
                            jQuery("ul", this).slideUp("fast");
                            jQuery("ul", this).css("display", "none");
                        }
                    )
        
                    // 検索で該当フォントが見つからなかった場合
                    jQuery("ul.font_select_menu").prepend('<li class="font-family-name no-match"><a>検索結果なし</a></li>');
                    // 未設定の場合
                    jQuery("ul.font_select_menu").prepend('<li class="font-family-name no-setting"><a>未設定</a></li>');
                }).bind(this),
                error: (function (xhr, status, err) {
                    console.error(this.props.url, status, err.toString());
                }).bind(this)
            });
        }
    
    
        // フォント検索機能
        var font_search = function (listItem) {
            var searchItem = '.fontlist_input';
            var hideClass = 'is-hide';         // 絞り込み対象外の場合に付与されるclass名
            var activeClass = 'is-active';     // 選択中のグループに付与されるclass名
    
            function search_filter(group, search_list) {
                // リスト内の各アイテムをチェック(font-family-name)
                var search_flg = false;
                for (var i = 0; i < jQuery(listItem).length; i++) {
                    var list_data = jQuery(listItem).eq(i);
                    var itemData = list_data.children('a').text();
    
                    if ( itemData.indexOf(group) != -1) {
                        list_data.addClass(activeClass);
                        list_data.removeClass(hideClass);
    
                        // 一つでも該当のフォントが見つかれば変更
                        if (search_flg === false) {
                            search_flg = true;
                        }
                    } else {
                        list_data.addClass(hideClass);
                        list_data.removeClass(activeClass);
                    }
                }
    
                // 該当のフォントがなかったときは、「検索結果なし」を表示させる
                if (search_flg !== true) {
                    jQuery('li.no-match').removeClass(hideClass);
                    jQuery('li.no-match').addClass(activeClass);
                } else {
                    jQuery('li.no-match').removeClass(activeClass);
                    jQuery('li.no-match').addClass(hideClass);
                }
            }
    
            let timeOutId;
    
            // 入力値を変更した時
            jQuery(searchItem).on('input', function() {
                var search_list = jQuery(this).next().children('.font_select_menu');
                var group = jQuery(this).val();
    
                var filter = function(){
                    search_filter(group, search_list);
                }
    
                // filterが何度も実行されるのを制御(clearTimeout)
                if ( timeOutId != null) {
                    clearTimeout(timeOutId);
                }
    
                if (group.length >= 1) {
                    // 非同期にするための処理(setTimeout)
                    timeOutId = setTimeout(filter, 300);
                } else if(group.length === 0) {
                    jQuery(listItem).removeClass(hideClass);
                    jQuery(listItem).addClass(activeClass);
                    jQuery('li.no-match').removeClass(activeClass);
                    jQuery('li.no-match').addClass(hideClass);
                    return;
                }
            });
        };
    
    
        // インプットフォームにフォーカスした時の動作
        jQuery(document).on("focus", "input.fontlist_input", function () {
            jQuery(this).removeAttr('placeholder');
            jQuery(this).attr('placeholder', 'フォント名を入力または検索');
            jQuery(this).next("div").children("ul").css("display", "block");
    
            jQuery('li.no-match').removeClass('is-active');
            jQuery('li.no-match').addClass('is-hide');
    
            var listItem = jQuery(this).next().find('.font-family-name');       // 絞り込み対象のアイテム(font-family-name)
            listItem.removeClass('is-hide');
            font_search(listItem);
        }).on("focusout", "input.fontlist_input", function () {
            // インプットフォームからフォーカスアウトした時の動作
            jQuery(this).removeAttr('placeholder');
            jQuery(this).attr('placeholder', '未設定');
    
            // フォント選択をしていたら、そのフォント名を表示させる
            if(jQuery(this).closest('td').next().val()) {
                // サイトフォント設定・カスタムテーマ編集の場合
                jQuery(this).val(jQuery(this).closest('td').next().val());
            } else if(jQuery(this).closest('td').next().next().val()) {
                // 投稿記事フォント設定の場合
                jQuery(this).val(jQuery(this).closest('td').next().next().val());
            } else {
                // 選択していなかったら空にする
                jQuery(this).val('');
            }
    
            var _this = jQuery(this);
            var focusOut = function(){
                jQuery(_this).next("div").children("ul").css("display", "none");
            }
    
            var userAgent = window.navigator.userAgent.toLowerCase();
            if(userAgent.indexOf('msie') != -1 || userAgent.indexOf('trident') != -1) {
                jQuery(this).next("div").children("ul").css("display", "none");
            } else {
                setTimeout(focusOut, 140);
            }
        });
    
    
        // 「未設定」を選択した場合はフォントリストを非表示にする
        jQuery(document).on("mousedown", ".font-family-name", function () {
            if(jQuery(this).hasClass('no-setting')) {
                jQuery(this).parent().css('display', 'none');
    
                // 直接指定の場合
                if(jQuery(this).closest('.ts-custome_form_font').length > 0) {
                    // インプットフォームに入力されている内容を削除する
                    var input_fontname = jQuery(this).closest('div').prev();
                    input_fontname.val('');
                    input_fontname.closest('td').next().next().val('');
                } else {
                    // サイトフォント・カスタムテーマの場合
                    // インプットフォームに入力されている内容を削除する
                    var input_fontname = jQuery(this).parent().parent().prev();
                    input_fontname.val('');
                    input_fontname.closest('td').next().val('');
                }
            }
        });
    
    
        // リストの二段目をクリックで、インプットフォームにフォント名を代入・送信用のフォームのvalueに値を代入する
        jQuery(document).on("mousedown", ".font-name", function () {
    
            // trが何番目にあるかを調べる
            var cls_tb_tr = jQuery(this).parents('tr#font_setting');
            var cls_tb_num = jQuery("tbody").children('tr#font_setting').index(cls_tb_tr) + 1;
            var custom_input_name = 'typesquare_custom_theme[fonts]'
    
            switch (cls_tb_num) {
                case 1:
                    cls_tb_num = custom_input_name + '[title][type]';
                    cls_name_tb_num = custom_input_name + '[title][type][name]';
                    break;
                case 2:
                    cls_tb_num = custom_input_name + '[lead][type]';
                    cls_name_tb_num = custom_input_name + '[lead][type][name]';
                    break;
                case 3:
                    cls_tb_num = custom_input_name + '[text][type]';
                    cls_name_tb_num = custom_input_name + '[text][type][name]';
                    break;
                case 4:
                    cls_tb_num = custom_input_name + '[bold][type]';
                    cls_name_tb_num = custom_input_name + '[bold][type][name]';
                    break;
                default:
                    cls_tb_num = '';
            }
    
            // 送信するために設置しているinputタグを取得
            var fontlist_input_hidden = jQuery(this).parents('tr').children("input#fontlist_select");
            // fontlist_input_hidden に fontlist_select_fontname を代入
            var fontlist_select_fontname = jQuery(this).children("a").text();
            jQuery(fontlist_input_hidden).val(fontlist_select_fontname);
    
            // フォント選択の入力フォームにフォント名を入れる
            var fontlist_input_form = jQuery(this).closest('div.w_fontlist').prev("input");
            // fontlist_input_form.val("fontlist_select_fontname");
            fontlist_input_form.css("font-family", `'${fontlist_select_fontname}'`);
    
            // 選択されたらリストを非表示にする
            jQuery(".font_select_menu").css("display", "none");
            jQuery(".font_select_menu").removeClass('is-active');
    
            // その番号と組み合わせてnameを設定
            var cls_tb_tr_hidden = jQuery(this).parents('td.font_table_td').children('input.fontselect_input_hidden');
            cls_tb_tr_hidden.attr('name', cls_tb_num);
        });
    
    
        var activeLimit = localStorage.getItem('activeLimit');
        if (activeLimit === null) {
            // 初めてのアクセスなら「フォント利用制限について」を表示
            jQuery('#ts-ad-area-messages').css('display', 'block');
            jQuery('#ts-ad-area-messages').addClass('ts-active');
            jQuery('.limitTriangle').addClass('open');
        } else if (activeLimit === 'true') {
            jQuery('#ts-ad-area-messages').css('display', 'block');
            jQuery('#ts-ad-area-messages').addClass('ts-active');
            jQuery('.limitTriangle').addClass('open');
        }
    
    
        // 「フォント利用制限について」クリック時イベント
        jQuery(".toggleLimit,.limitTriangle").on('click', function () {
            jQuery('#ts-ad-area-messages').slideToggle('normal', function () {
                if (jQuery('#ts-ad-area-messages').hasClass('ts-active')) {
                    jQuery('#ts-ad-area-messages').removeClass('ts-active');
                    localStorage.setItem('activeLimit', false);
                    jQuery('.limitTriangle').removeClass('open');
                } else {
                    jQuery('#ts-ad-area-messages').addClass('ts-active');
                    localStorage.setItem('activeLimit', true);
                    jQuery('.limitTriangle').addClass('open');
                }
            });
        });
    
        // フォントテーマ設定
        jQuery('#fontThemeSelect').change(function () {
            var selectTheme = jQuery(this).val();
            // 新しくテーマを作成する。表示
            if (selectTheme === 'new') {
                jQuery('input[name=ts_edit_mode]').val('new');
                jQuery('#ts_custome_theme_id').val(unique_id);
                jQuery('#fontThemeDeleteButton').hide();
                jQuery('#custmeFontForm').find('input').each(function () {
                    // 空にする
                    if (jQuery(this).attr('type') === undefined) {
                        jQuery(this).val('');
                    }
                });
                jQuery('#custome_font_name').val('');
                jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[title\\]\\[type\\]]').val(fonttheme.fonts.title);
                jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[lead\\]\\[type\\]]').val(fonttheme.fonts.lead);
                jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[text\\]\\[type\\]]').val(fonttheme.fonts.text);
                jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[bold\\]\\[type\\]]').val(fonttheme.fonts.bold);
            } else {
                jQuery('input[name=ts_edit_mode]').val('update');
                jQuery('#ts_custome_theme_id').val('');
                var custome_fonts = option_font_list.theme;
                jQuery.each(custome_fonts, function (i, cs_font) {
                    if (cs_font.id === selectTheme) {
                        jQuery('input[name=ts_edit_mode]').val('update');
                        jQuery('#ts_custome_theme_id').val(cs_font.id);
                        var fonttheme_json = jQuery('#' + cs_font.id).val();
                        fonttheme = JSON.parse(fonttheme_json);
    
                        choiceTheme = jQuery('#choiceTheme');
                        choiceTheme.show();
                        var insertCount = 0;
                        jQuery('#custmeFontForm').find('input').each(function () {
                            if (jQuery(this).attr('type') === undefined) {
                                if (insertCount === 0) {
                                    jQuery(this).val(fonttheme.fonts.title)
                                    jQuery(this).css("font-family", `'${fonttheme.fonts.title}'`);
                                    jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[title\\]\\[type\\]]').val(fonttheme.fonts.title);
                                } else if (insertCount === 1) {
                                    jQuery(this).val(fonttheme.fonts.lead)
                                    jQuery(this).css("font-family", `'${fonttheme.fonts.lead}'`);
                                    jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[lead\\]\\[type\\]]').val(fonttheme.fonts.lead);
                                } else if (insertCount === 2) {
                                    jQuery(this).val(fonttheme.fonts.text)
                                    jQuery(this).css("font-family", `'${fonttheme.fonts.text}'`);
                                    jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[text\\]\\[type\\]]').val(fonttheme.fonts.text);
                                } else if (insertCount === 3) {
                                    jQuery(this).val(fonttheme.fonts.bold)
                                    jQuery(this).css("font-family", `'${fonttheme.fonts.bold}'`);
                                    jQuery('input[name=typesquare_custom_theme\\[fonts\\]\\[bold\\]\\[type\\]]').val(fonttheme.fonts.bold);
                                }
                                insertCount++;
                            }
                        });
                        jQuery('#custome_font_name').val(fonttheme.name);
                        jQuery('#current_custome_font_name').val(fonttheme.name);
                    }
                });
                jQuery('#fontThemeDeleteButton').show();
            }
        });
    
        window.onload = function () {
          theme_preview();
        };
    
        jQuery('#choiceTheme').change(function() {
          theme_preview();
        });
    
        function theme_preview() {
          var select_val = jQuery('.font_theme_select_pre option:selected').val();
          var val = document.getElementById("selected-get-" + select_val);
          if (val == null) {
            return;
          }
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
        };
    
        // フォントテーマ更新ボタン押下処理。
        jQuery('#fontThemeUpdateButton').click(function () {
            var nameDuplicateMessage = "同一名のカスタムフォントテーマは作成できません";
            var countOverMessage = "カスタムフォントテーマは10個を超えて作成できません";
    
            var warningFlg = 0;
    
            // カスタムテーマ以外の選択
            if (jQuery('#customeFontThemeForm').is(':hidden')) {
                return true;
            }
    
            // 同一名チェック
            var all_font_theme = all_font_list;
            var custome_font_name = jQuery('#custome_font_name').val();
            var duble_check_count = 0;
            if (custome_font_name !== jQuery('#current_custome_font_name').val()) {
                jQuery.each(all_font_theme, function (i, af_font) {
                    if (af_font.name === custome_font_name) {
                        duble_check_count++;
                    }
                });
            }
    
            if (jQuery('input[name=ts_edit_mode]').val() === 'new' && duble_check_count > 0) {
                warningFlg = 1;
            } else if (duble_check_count > 1) {
                warningFlg = 1;
            }
            // 個数チェック
            var custome_fonts = option_font_list.theme;
            var custome_font_count = Object.keys(custome_fonts).length;
            if (custome_font_count >= 10 && !jQuery('#current_custome_font_name').val()) {
                warningFlg = 2;
            }
    
            // エラー切り分け
            switch (warningFlg) {
                case 1:
                    // 同一名警告
                    alert(nameDuplicateMessage);
                    return false;
                    break;
                case 2:
                    // １１個以上警告
                    alert(countOverMessage);
                    return false;
                    break;
                default:
                    if (jQuery('#custome_font_name').val()) {
                        if (jQuery('input[name=ts_edit_mode]').val() !== 'new') {
                            var dialogMessage = jQuery('#current_custome_font_name').val() + 'を上書き保存します。よろしいですか？\n※テーマを利用しているすべての投稿に変更が反映されます';
                            var fontDeleteConfirmDialog = window.confirm(dialogMessage);
                            if (fontDeleteConfirmDialog) {
                                jQuery('#custmeFontForm').submit();
                            } else {
                                return false;
                            }
                        }
                    }
            }
        });
    
        // フォントテーマ削除ボタン押下処理。
        jQuery('#updateFontListButton').click(function () {
            jQuery('#update_font_list').val('on');
            jQuery('.update_msg').text('更新中...');
    
        });
    
        // フォントテーマ削除ボタン押下処理。
        jQuery('#fontThemeDeleteButton').click(function () {
            var dialogMessage = jQuery('#custome_font_name').val() + 'を削除します。よろしいですか？\n※テーマを利用している投稿のWebフォント設定がクリアされます';
            var fontDeleteConfirmDialog = window.confirm(dialogMessage);
    
            if (fontDeleteConfirmDialog) {
                jQuery('input[name=ts_edit_mode]').val('delete');
                jQuery('#custome_font_name').removeAttr('required');
                jQuery('#custmeFontForm').submit();
            } else {
                return false;
            }
        });
    
        // localStorageのactiveAuthを取得
        var activeAuth = localStorage.getItem('activeAuth');
        if (activeAuth === null) {
            // 初めてのアクセスなら「アカウントを変更する」を表示
            jQuery('.toggleAuthTargetParent').css('display', 'block');
            jQuery('.toggleAuthTargetParent').addClass('ts-active');
            jQuery('.authTriangle').addClass('open');
        } else if (activeAuth === 'true') {
            jQuery('.toggleAuthTargetParent').css('display', 'block');
            jQuery('.toggleAuthTargetParent').addClass('ts-active');
            jQuery('.authTriangle').addClass('open');
        }
    
        // 「アカウントを変更する」クリック時イベント
        jQuery(".toggleAuth,.authTriangle").on('click', function () {
            jQuery('.toggleAuthTargetParent').slideToggle('normal', function () {
                if (jQuery('.toggleAuthTargetParent').hasClass('ts-active')) {
                    jQuery('.toggleAuthTargetParent').removeClass('ts-active');
                    localStorage.setItem('activeAuth', false);
                    jQuery('.authTriangle').removeClass('open');
                } else {
                    jQuery('.toggleAuthTargetParent').addClass('ts-active');
                    localStorage.setItem('activeAuth', true);
                    jQuery('.authTriangle').addClass('open');
                }
            });
        });
    
        // フェードイン設定
        jQuery("#fade_in").on('click', function () {
            if (jQuery('#hidden_time').hasClass('ts-table-low')) {
                jQuery('#hidden_time').removeClass('ts-table-low');
                jQuery('#hidden_time').addClass('hidden');
            } else {
                jQuery('#hidden_time').addClass('ts-table-low');
            }
        });
    
        // 各設定ブロックのトグル処理共通化
        function toggle_setting(form, triangle) {
            form.slideToggle('normal', function () {
                // アクティブになっていたら外す
                if (form.hasClass('ts-active')) {
                    form.removeClass('ts-active');
                    triangle.removeClass('open');
                } else {
                    // なっていなかったらアクティブにする
                    form.addClass('ts-active');
                    triangle.addClass('open');
                }
            });
        }
    
        // 「投稿記事フォント設定」クリック時イベント
        jQuery(".toggleAdvanced_font").on('click', function () {
            var form = jQuery('.ts-custome_form_font');
            var triangle = jQuery('.advancedTriangle_font');
            toggle_setting(form, triangle);
        });
    
        // 「TypeSquare設定」クリック時イベント
        jQuery(".toggleAdvanced_ts").on('click', function () {
            var form = jQuery('.ts-custome_form');
            var triangle = jQuery('.advancedTriangle_ts');
            toggle_setting(form, triangle);
        });
    
        // 「サイトフォント設定」クリック時イベント
        jQuery(".toggleAdvanced_site").on('click', function () {
            var form = jQuery('.ts-custome_form_site');
            var triangle = jQuery('.advancedTriangle_site');
            toggle_setting(form, triangle);
        });
    
        // 「投稿フォント設定」クリック時イベント
        jQuery(".toggleAdvanced_article").on('click', function () {
            var form = jQuery('.ts-custome_form_article');
            var triangle = jQuery('.advancedTriangle_article');
            toggle_setting(form, triangle);
        });
    
        var switchLabel = jQuery('input[name=fontThemeUseType]');
        switchLabel.on('change', function () {
            var inputField = 'input, textarea';
            var inputValue = jQuery(this).val();
            if (inputValue === '2') {
                choiceTheme = jQuery('#choiceTheme');
                choiceTheme.show();
                choiceTheme.find(inputField).each(function () {
                    jQuery(this).prop('disabled', false);
                });
            } else {
                choiceTheme = jQuery('#choiceTheme');
                choiceTheme.hide();
                choiceTheme.find(inputField).each(function () {
                    jQuery(this).prop('disabled', true);
                });
            }
            if (inputValue === '4') {
                coustomFont = jQuery('.b__font_target_form').show();
                coustomFont.find(inputField).each(function () {
                    jQuery(this).prop('disabled', false);
                });
            } else {
                coustomFont = jQuery('.b__font_target_form').hide();
                coustomFont.find(inputField).each(function () {
                    jQuery(this).prop('disabled', true);
                });
            }
            localStorage.setItem('activeAdvanced_theme', inputValue);
        });
    
        // 投稿フォント設定 localStorageのactiveAdvancedを取得
        var activeAdvanced_theme = jQuery('#activeAdvanced_theme').val();
        jQuery('input[name="fontThemeUseType"]').map(function () {
            if (jQuery(this).val() == activeAdvanced_theme) {
                jQuery(this).prop('checked', true);
                jQuery(this).change();
            }
        });
    });
    
