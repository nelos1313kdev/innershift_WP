<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Two_Factor_Authentication extends CloudSecureWP_Common {
	private const KEY_FEATURE = 'two_factor_authentication';

	private $config;

	/**
	 * @var CloudSecureWP_Disable_Login
	 */
	private $disable_login;

	function __construct( array $info, CloudSecureWP_Config $config, CloudSecureWP_Disable_Login $disable_login ) {
		parent::__construct( $info );
		$this->config        = $config;
		$this->disable_login = $disable_login;
	}

	/**
	 * 機能毎のKEY取得
	 *
	 * @return string
	 */
	public function get_feature_key(): string {
		return self::KEY_FEATURE;
	}

	/**
	 *  有効無効判定
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->config->get( $this->get_feature_key() ) === 't';
	}

	/**
	 * 初期設定値取得
	 *
	 * @return array
	 */
	public function get_default(): array {
		return array( self::KEY_FEATURE => 'f' );
	}

	/**
	 * 設定値key取得
	 */
	public function get_keys(): array {
		return array( self::KEY_FEATURE );
	}

	/**
	 * 設定値取得
	 */
	public function get_settings(): array {
		$settings = array();
		$keys     = $this->get_keys();

		foreach ( $keys as $key ) {
			$settings[ $key ] = $this->config->get( $key );
		}

		return $settings;
	}

	/**
	 * 設定値保存
	 *
	 * @param array $settings
	 *
	 * @return void
	 */
	public function save_settings( array $settings ): void {
		$keys = $this->get_keys();

		foreach ( $keys as $key ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}
		$this->config->save();
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$settings = $this->get_default();
		$this->save_settings( $settings );
	}

	/**
	 * 無効化
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$settings                      = $this->get_settings();
		$settings[ self::KEY_FEATURE ] = 'f';
		$this->save_settings( $settings );
	}

	/**
	 * 管理画面上での有効無効判定
	 * 2段階認証の管理画面で「変更を保存」ボタンを押下時、
	 * is_enabled()のみを使うとデバイス登録のメニューが正しく表示されない。
	 *
	 * @return bool
	 */
	public function is_enabled_on_screen(): bool {
		if ( isset( $_POST['two_factor_authentication'] ) && ! empty( $_POST['two_factor_authentication'] ) ) {
			return $this->check_environment() && sanitize_text_field( $_POST['two_factor_authentication'] ) === 't';
		}

		return $this->is_enabled();
	}

	/**
	 * 有効な権限グループに含まれるかどうか
	 *
	 * @param $role
	 *
	 * @return bool
	 */
	private function is_role_enabled( $role ): bool {
		return in_array( $role, get_option( 'cloudsecurewp_two_factor_authentication_roles', array() ) );
	}

	/**
	 * ログインフォーム2段階認証チェック
	 */
	public function wp_login( $user_login, $user ) {
		// 2段階認証が無効なとき
		if ( ! $this->is_enabled() ) {
			return;
		}

		// 有効な権限グループに含まれないとき
		if ( ! $this->is_role_enabled( $user->roles[0] ) ) {
			return;
		}

		$secret = get_user_option( 'cloudsecurewp_two_factor_authentication_secret', $user->ID );
		// ユーザーがデバイス登録をしていないとき
		if ( ! $secret ) {
			return;
		}

		// 2段階認証コードが送られたとき
		if ( ! empty( $_POST['google_authenticator_code'] ) && check_admin_referer( $this->get_feature_key() . '_csrf' ) ) {
			$google_authenticator_code = sanitize_text_field( $_POST['google_authenticator_code'] );
			// 2段階認証コードが有効なとき
			if ( CloudSecureWP_Time_Based_One_Time_Password::verify_code( $secret, $google_authenticator_code, 2 ) ) {
				return;
			}
			// ログイン失敗回数をインクリメントしデータベースに格納
			$this->disable_login->wp_login_failed( $user_login );
		}

		wp_logout();
		login_header( '2段階認証画面' );
		$this->login_error();
		$this->login_form();
		login_footer();
		exit;
	}

	/**
	 * 2段階認証のエラーを出力
	 *
	 * @return void
	 */
	private function login_error() {
		if ( array_key_exists( 'google_authenticator_code', $_REQUEST ) ) {
			if ( sanitize_text_field( $_REQUEST['google_authenticator_code'] ) ) {
				$errors = '認証コードが間違っているか、有効期限が切れています。';
			} else {
				$errors = '認証コードが入力されていません。';
			}
			echo '<div id="login_error">' . esc_html( apply_filters( 'login_errors', $errors ) ) . "</div>\n";
		}
	}

	/**
	 * 2段階認証のログインフォームを出力
	 *
	 * @return void
	 */
	private function login_form() {
		?>
		<form name="loginform" id="loginform"
				action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
			<input type="hidden" name="log" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['log'] ) ); ?>"/>
			<input type="hidden" name="pwd" value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['pwd'] ) ); ?>"/>
			<?php if ( array_key_exists( 'cloudsecurewp_captcha', $_REQUEST ) ) : ?>
				<input type="hidden" name="cloudsecurewp_captcha"
						value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['cloudsecurewp_captcha'] ) ); ?>"/>
			<?php endif; ?>
			<?php if ( array_key_exists( 'cloudsecurewp_captcha_prefix', $_REQUEST ) ) : ?>
				<input type="hidden" name="cloudsecurewp_captcha_prefix"
						value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['cloudsecurewp_captcha_prefix'] ) ); ?>"/>
			<?php endif; ?>
			<?php if ( array_key_exists( 'cloudsecurewp_captcha_wpnonce', $_REQUEST ) ) : ?>
				<input type="hidden" name="cloudsecurewp_captcha_wpnonce"
						value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['cloudsecurewp_captcha_wpnonce'] ) ); ?>"/>
			<?php endif; ?>
			<?php if ( array_key_exists( 'rememberme', $_REQUEST ) && 'forever' === sanitize_text_field( $_REQUEST['rememberme'] ) ) : ?>
				<input name="rememberme" type="hidden" id="rememberme" value="forever"/>
			<?php endif; ?>
			<p>
				<label for="google_authenticator_code">認証コード</label>
				<input type="text" name="google_authenticator_code" id="google_authenticator_code" class="input"
						value="" size="20"/>
			</p>
			<script type="text/javascript">document.getElementById("google_authenticator_code").focus();</script>
			<p>デバイスのGoogle Authenticator アプリケーションに表示されている6桁の認証コードを入力してください。</p>
			<p class="submit">
				<?php wp_nonce_field( $this->get_feature_key() . '_csrf' ); ?>
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large"
						value="<?php esc_attr_e( 'Log In' ); ?>"/>
				<input type="hidden" name="redirect_to"
						value="<?php echo esc_attr( sanitize_text_field( $_REQUEST['redirect_to'] ?? admin_url() ) ); ?>"/>
				<input type="hidden" name="testcookie" value="1"/>
			</p>
		</form>
		<?php
	}

	/**
	 * デバイス登録がまだのユーザーは、デバイス登録画面にリダイレクト
	 *
	 * @param $user_login
	 * @param $user
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function redirect_if_not_two_factor_authentication_registered( $user_login, $user ) {
		$secret = get_user_option( 'cloudsecurewp_two_factor_authentication_secret', $user->ID );
		if ( $this->is_enabled() && $this->is_role_enabled( $user->roles[0] ) && ! $secret && $_SERVER['REQUEST_URI'] !== '/wp-admin/admin.php?page=cloudsecurewp_two_factor_authentication_registration' ) {
			wp_redirect( admin_url( 'admin.php?page=cloudsecurewp_two_factor_authentication_registration' ) );
			exit;
		}
	}
}
