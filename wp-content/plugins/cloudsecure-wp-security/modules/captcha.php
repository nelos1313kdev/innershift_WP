<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_CAPTCHA extends CloudSecureWP_Common {
	private const KEY_FEATURE           = 'captcha';
	private const KEY_LOGIN             = self::KEY_FEATURE . '_login';
	private const KEY_COMMENT           = self::KEY_FEATURE . '_comment';
	private const KEY_LOST_PASSWORD     = self::KEY_FEATURE . '_lost_password';
	private const KEY_REGISTER          = self::KEY_FEATURE . '_register';
	private const LOGIN_VALUES          = array( 1, 2 ); // 無効, 有効 .
	private const COMMENT_VALUES        = array( 1, 2 ); // 無効, 有効 .
	private const LOST_PASSWORD_VALUES  = array( 1, 2 ); // 無効, 有効 .
	private const REGISTER_VALUES       = array( 1, 2 ); // 無効, 有効 .
	private const ERROR_CODE            = 'cloudsecurewp_captcha_error';
	private const CAPTCHA_FORM_NAME     = 'cloudsecurewp_captcha';
	private const PREFIX_FORM_NAME      = 'cloudsecurewp_captcha_prefix';
	private const CAPTCHA_ERROR_MESSAGE = 'エラー：画像認証に失敗しました';

	private $config;
	private $captcha;
	private $allowed_html;

	function __construct( array $info, CloudSecureWP_Config $config ) {
		parent::__construct( $info );
		$this->config       = $config;
		$this->captcha      = new CloudSecureWP_ReallySimpleCaptcha();
		$this->allowed_html = array(
			'p'     => array(
				'class' => array(),
			),
			'style' => array(),
			'label' => array(
				'for' => array(),
			),
			'img'   => array(
				'src' => array(),
				'alt' => array(),
			),
			'input' => array(
				'type'          => array(),
				'id'            => array(),
				'name'          => array(),
				'class'         => array(),
				'value'         => array(),
				'size'          => array(),
				'aria-required' => array(),
			),
		);
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
		return $this->config->get( $this->get_feature_key() ) === 't' ? true : false;
	}

	/**
	 * ログインフォームの有効無効判定
	 */
	public function is_login_form_enabled(): bool {
		return ( (int) $this->config->get( self::KEY_LOGIN ) === (int) self::LOGIN_VALUES[1] ) ? true : false;
	}

	/**
	 * コメントフォームの有効無効判定
	 */
	public function is_comment_form_enabled(): bool {
		return ( (int) $this->config->get( self::KEY_COMMENT ) === (int) self::COMMENT_VALUES[1] ) ? true : false;
	}

	/**
	 * パスワードリセットフォームの有効無効判定
	 */
	public function is_lost_password_form_enabled(): bool {
		return ( (int) $this->config->get( self::KEY_LOST_PASSWORD ) === (int) self::LOST_PASSWORD_VALUES[1] ) ? true : false;
	}

	/**
	 * ユーザー登録フォームの有効無効判定
	 */
	public function is_register_form_enabled(): bool {
		return ( (int) $this->config->get( self::KEY_REGISTER ) === (int) self::REGISTER_VALUES[1] ) ? true : false;
	}

	/**
	 * 初期設定値取得
	 *
	 * @return array
	 */
	public function get_default(): array {
		return array(
			self::KEY_FEATURE       => 'f',
			self::KEY_LOGIN         => self::LOGIN_VALUES[1],
			self::KEY_COMMENT       => self::COMMENT_VALUES[1],
			self::KEY_LOST_PASSWORD => self::LOST_PASSWORD_VALUES[1],
			self::KEY_REGISTER      => self::REGISTER_VALUES[1],
		);
	}

	/**
	 * 設定値key取得
	 */
	public function get_keys(): array {
		return array(
			self::KEY_FEATURE,
			self::KEY_LOGIN,
			self::KEY_COMMENT,
			self::KEY_LOST_PASSWORD,
			self::KEY_REGISTER,
		);
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
	 * @return void
	 */
	public function save_settings( $settings ): void {
		$keys = $this->get_keys();

		foreach ( $keys as $key ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}
		$this->config->save();
	}

	/**
	 * 設定定義値取得
	 *
	 * @return array
	 */
	public function get_constant_settings(): array {
		return array(
			self::KEY_LOGIN         => self::LOGIN_VALUES,
			self::KEY_COMMENT       => self::COMMENT_VALUES,
			self::KEY_LOST_PASSWORD => self::LOST_PASSWORD_VALUES,
			self::KEY_REGISTER      => self::REGISTER_VALUES,
		);
	}

	/**
	 * 動作環境チェック
	 *
	 * @return string
	 */
	public function check_modules(): string {
		$ret = $this->check_gd();
		if ( '' !== $ret ) {
			return $ret;
		}

		$ret = $this->check_captcha_save_dir();
		if ( '' !== $ret ) {
			return $ret;
		}

		return $ret;
	}

	/**
	 * GDライブラリチェック
	 *
	 * @return string
	 */
	public function check_gd(): string {
		$gd = gd_info();

		if ( empty( $gd ) ) {
			return 'GDライブラリを利用できません';
		}

		if ( ! array_key_exists( 'FreeType Support', $gd ) || false === $gd['FreeType Support'] ) {
			return 'FreeType が無効です';
		}

		return '';
	}

	/**
	 * 画像認証画像保存確認
	 *
	 * @return string
	 */
	public function check_captcha_save_dir(): string {
		if ( ! $this->captcha->make_tmp_dir() ) {
			return '画像認証画像保存ディレクトリを作成できません: ' . $this->captcha->tmp_dir;
		}

		return '';
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
	 * 画像認証
	 *
	 * @return string
	 */
	function create_captcha(): string {
		$word   = $this->captcha->generate_random_word();
		$prefix = mt_rand();
		$this->captcha->generate_image( $prefix, $word );

		$captcha  = '<p class="cloudsecure-wp-captcha-block">' . "\n";
		$captcha .= '    <style>' . "\n";
		$captcha .= '        .cloudsecure-wp-captcha-block label{' . "\n";
		$captcha .= '            display: block;' . "\n";
		$captcha .= '        }' . "\n";
		$captcha .= '        .cloudsecure-wp-captcha-block label img{' . "\n";
		$captcha .= '            border: 1px solid #CCCCCC;' . "\n";
		$captcha .= '            padding: 8px;' . "\n";
		$captcha .= '            margin-top: 2px;' . "\n";
		$captcha .= '         }' . "\n";
		$captcha .= '    </style>' . "\n";
		$captcha .= '    <label for="' . self::CAPTCHA_FORM_NAME . '">画像に表示された文字を入力してください</label>' . "\n";
		$captcha .= '    <label for="' . self::CAPTCHA_FORM_NAME . '"><img src="' . $this->info['plugin_url'] . 'really-simple-captcha/tmp/' . $prefix . '.png" alt="CAPTCHA"></label>' . "\n";
		$captcha .= '    <input type="text" id="' . self::CAPTCHA_FORM_NAME . '" name="' . self::CAPTCHA_FORM_NAME . '" class="input" value="" size="10" aria-required="true" />' . "\n";
		$captcha .= '    <input type="hidden" id="' . self::PREFIX_FORM_NAME . '" name="' . self::PREFIX_FORM_NAME . '" value="' . $prefix . '" />' . "\n";
		$captcha .= wp_nonce_field( $this->get_feature_key() . '_csrf', 'cloudsecurewp_captcha_wpnonce', true, false ) . "\n";
		$captcha .= '</p>' . "\n";

		return $captcha;
	}

	/**
	 * 画像認証チェック
	 *
	 * @return bool
	 */
	public function check_captcha(): bool {
		if ( ! empty( $_POST ) && check_admin_referer( $this->get_feature_key() . '_csrf', 'cloudsecurewp_captcha_wpnonce' ) ) {
			return $this->captcha->check( sanitize_text_field( $_POST[ self::PREFIX_FORM_NAME ] ?? '' ), sanitize_text_field( $_POST[ self::CAPTCHA_FORM_NAME ] ?? '' ) );
		}

		return false;
	}

	/**
	 * 画像認証エラー
	 */
	public function get_captcha_error() {
		return new WP_Error( self::ERROR_CODE, self::CAPTCHA_ERROR_MESSAGE );
	}

	/**
	 * エラーコード追加
	 */
	public function shake_error_codes( $error_codes ) {
		array_push( $error_codes, self::ERROR_CODE );
		return $error_codes;
	}

	/**
	 * ログインフォーム画像認証
	 */
	public function login_form() {
		echo wp_kses( $this->create_captcha(), $this->allowed_html );
	}

	/**
	 * ログインフォーム画像認証チェック
	 */
	public function wp_authenticate_user( $user, $password ) {
		if ( $this->check_captcha() ) {
			return $user;
		}

		return $this->get_captcha_error();
	}

	/**
	 * コメントフォーム画像認証
	 */
	public function comment_form_default_fields() {
		echo wp_kses( $this->create_captcha(), $this->allowed_html );
	}

	/**
	 * コメントフォーム画像認証チェック
	 */
	public function preprocess_comment( $comment_data ) {
		if ( is_admin() || $this->check_captcha() ) {
			return $comment_data;
		}

		wp_die( esc_html( self::CAPTCHA_ERROR_MESSAGE ), esc_html( 'ERROR' ), array( 'back_link' => true ) );
	}

	/**
	 * パスワードリセットフォーム画像認証
	 */
	public function lostpassword_form() {
		echo wp_kses( $this->create_captcha(), $this->allowed_html );
	}

	/**
	 * パスワードリセットフォーム画像認証チェック
	 */
	public function allow_password_reset( $allow_reset, $user_id ) {
		if ( $this->check_captcha() ) {
			return $allow_reset;
		}

		return $this->get_captcha_error();
	}

	/**
	 * ユーザー登録フォーム画像認証
	 */
	public function register_form() {
		echo wp_kses( $this->create_captcha(), $this->allowed_html );
	}

	/**
	 * ユーザー登録フォーム画像認証チェック
	 */
	public function register_post( $username, $email, $errors ) {
		if ( ! $this->check_captcha() ) {
			$errors->add( self::CAPTCHA_FORM_NAME, self::CAPTCHA_ERROR_MESSAGE );
		}
	}
}
