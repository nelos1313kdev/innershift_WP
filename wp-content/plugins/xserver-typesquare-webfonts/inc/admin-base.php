<?php
class TypeSquare_Admin_Base {
	private static $instance;

	private static $text_domain;

	const MENU_ID = 'typesquare-admin-menu';
	const FONT_THEME = 'typesquare_fonttheme';
	const MESSAGE_KEY = 'typesquare-admin-errors';
	const MENU_FONTTHEME = 'typesquare-admin-fonttheme';
	const MENU_FONTGEN = 'typesquare-admin-fontgen';
	private function __construct(){}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();
		}
		return self::$instance;
	}

	public function get_auth_params() {
		$auth	= TypeSquare_ST_Auth::get_instance();
		$param = $auth->get_auth_params();
		return $param;
	}

	public function set_error_messages( $wp_error ) {
		$msg[] = $wp_error->get_error_message();
		set_transient( self::MESSAGE_KEY , $msg, 10 );
	}

	public function typesquare_admin_notices() {
		$messages = get_transient( self::MESSAGE_KEY );
		if ( ! $messages ) {
			return;
		}
	?>
		<div class="error">
			<ul>
				<?php foreach ( $messages as $message ) : ?>
					<li><?php echo esc_html( $message );?></li>
				<?php endforeach;?>
			</ul>
		</div>
	<?php
	}
}
