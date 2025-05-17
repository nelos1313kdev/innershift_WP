<?php

class TypeSquare_ST_Fontthemelist {
  private static $instance;

	private function __construct(){}

  public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

  public function get_fonttheme_list()
    {
      return array(
        "basic" => [
          "name" => "ベーシック",
          "fonts" => [
            "title" => "新ゴ B",
            "lead" => "新ゴ B",
            "content" => "新ゴ R",
            "bold" => "新ゴ B"
          ]
        ],
        "stylish" => [
          "name" => "スタイリッシュ",
          "fonts" => [
            "title" => "見出ゴMB31",
            "lead" => "見出ゴMB31",
            "content" => "TBUDゴシック R",
            "bold" => "TBUDゴシック E"
          ]
        ],
        "news" => [
          "name" => "ニュース",
          "fonts" => [
            "title" => "リュウミン B-KL",
            "lead" => "リュウミン B-KL",
            "content" => "黎ミン M",
            "bold" => "リュウミン B-KL"
          ]
        ],
        "business" => [
          "name" => "ビジネス",
          "fonts" => [
            "title" => "リュウミン B-KL",
            "lead" => "リュウミン B-KL",
            "content" => "TBUDゴシック R",
            "bold" => "TBUDゴシック E"
          ]
        ],
        "fashion" => [
          "name" => "ファッション",
          "fonts" => [
            "title" => "丸フォーク M",
            "lead" => "丸フォーク M",
            "content" => "新ゴ R",
            "bold" => "新ゴ B"
          ]
        ],
        "elegant" => [
          "name" => "エレガント",
          "fonts" => [
            "title" => "A1明朝",
            "lead" => "A1明朝",
            "content" => "黎ミン M",
            "bold" => "リュウミン B-KL"
          ]
        ],
        "pop" => [
          "name" => "ポップ",
          "fonts" => [
            "title" => "ぶらっしゅ",
            "lead" => "ぶらっしゅ",
            "content" => "じゅん 501",
            "bold" => "G2サンセリフ-B"
          ]
        ],
        "comical" => [
          "name" => "コミカル",
          "fonts" => [
            "title" => "新ゴ シャドウ",
            "lead" => "新ゴ シャドウ",
            "content" => "じゅん 501",
            "bold" => "G2サンセリフ-B"
          ]
        ],
        "wafu" => [
          "name" => "和風",
          "fonts" => [
            "title" => "教科書ICA M",
            "lead" => "教科書ICA M",
            "content" => "黎ミン M",
            "bold" => "リュウミン B-KL"
          ]
        ],
        "hannari" => [
          "name" => "はんなり",
          "fonts" => [
            "title" => "那欽",
            "lead" => "那欽",
            "content" => "黎ミン M",
            "bold" => "リュウミン B-KL"
          ]
        ],
        "natural" => [
          "name" => "ナチュラル",
          "fonts" => [
            "title" => "はるひ学園",
            "lead" => "はるひ学園",
            "content" => "シネマレター",
            "bold" => "竹 B"
          ]
        ],
        "retro" => [
          "name" => "レトロ",
          "fonts" => [
            "title" => "シネマレター",
            "lead" => "シネマレター",
            "content" => "トーキング",
            "bold" => "じゅん 501"
          ]
        ],
        "horror" => [
          "name" => "ホラー",
          "fonts" => [
            "title" => "TB古印体",
            "lead" => "TB古印体",
            "content" => "陸隷",
            "bold" => "陸隷"
          ]
        ]
      );
    }

  public function get_url()
    {
      return array(
        "file_domain" => "",
        "font_css" => "",
	      "font_json" => ""
      );
    }
}