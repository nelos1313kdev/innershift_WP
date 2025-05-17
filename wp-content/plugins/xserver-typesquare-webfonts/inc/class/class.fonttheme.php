<?php
require_once(ABSPATH.'wp-admin/includes/file.php');
require_once(dirname(__FILE__).'/class.fontthemelist.php');

class TypeSquare_ST_Fonttheme {
	private static $fonttheme;
	private static $instance;

	private function __construct(){}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public static function get_fonttheme()
    {
        static $fonttheme;

        $arr = [];
        $fontthemelist = TypeSquare_ST_Fontthemelist::get_instance();
        $arr['fonttheme'] = $fontthemelist->get_fonttheme_list();

        $options = get_option('typesquare_custom_theme');
        if ($options && isset($options['theme']) && is_array($options['theme'])) {
            $arr = $arr['fonttheme'] + $options['theme'];
        }
        return $arr;
    }



	public static function  get_custom_fonttheme() {
		$options = get_option( 'typesquare_custom_theme' );
		if ( $options && isset( $options['theme'] ) && is_array( $options['theme'] ) ) {
			return $options['theme'];
		}
		return null;
	}


	public static function get_custom_theme_json() {
		$options = get_option( 'typesquare_custom_theme' );
		return json_encode($options['theme']);
	}
}
