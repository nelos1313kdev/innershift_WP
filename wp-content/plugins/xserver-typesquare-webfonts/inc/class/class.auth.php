<?php
class TypeSquare_ST_Auth {
	private static $instance;
	private static $text_domain;

	private function __construct(){}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public static function text_domain() {
		static $text_domain;

		if ( ! $text_domain ) {
			$data = get_file_data( __FILE__ , array( 'text_domain' => 'Text Domain' ) );
			$text_domain = $data['text_domain'];
		}
		return $text_domain;
	}

	public function get_auth_status() {
		$option_name = 'typesquare_auth';
		$param = get_option( $option_name );
		if ( !$param ) {
			$param = array(
				'typesquare_id' => 'xserver',
				'fontThemeUseType' => 1
			);
		}
		foreach ( $param as $key => $value ) {
			$escaped_data[ $key ] = esc_attr( $value );
		}
		return $escaped_data;
	}

	public function get_auth_params() {
		$option_name = 'typesquare_auth';
		$param['typesquare_auth'] = $this->get_auth_status();

		$param['typesquare_auth_keys'] = array(
			'typesquare_id' => __( '配信タグ設定', self::$text_domain ),
		);
		return $param;
	}

	public function update_typesquare_auth() {
		$typesquare_auth = $this->get_auth_status();

		if (isset($_POST['typesquare_auth']['typesquare_id'])) {
			$typesquare_auth['typesquare_id'] = sanitize_post($_POST['typesquare_auth']['typesquare_id']);
		}

		if (isset($_POST['fontThemeUseType'])) {
			$typesquare_auth['fontThemeUseType'] = sanitize_post($_POST['fontThemeUseType']);
		}

		update_option( 'typesquare_auth', $typesquare_auth );
	}
}
