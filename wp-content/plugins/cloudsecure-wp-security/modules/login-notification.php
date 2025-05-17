<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Login_Notification extends CloudSecureWP_Common {
	private const KEY_FEATURE    = 'login_notification';
	private const KEY_SUBJECT    = self::KEY_FEATURE . '_subject';
	private const KEY_BODY       = self::KEY_FEATURE . '_body';
	private const KEY_ADMIN_ONLY = self::KEY_FEATURE . '_admin_only';
	private $config;

	function __construct( array $info, CloudSecureWP_Config $config ) {
		parent::__construct( $info );
		$this->config = $config;
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
	 * 初期設定値取得
	 *
	 * @return array
	 */
	public function get_default(): array {
		$ret = array(
			self::KEY_FEATURE    => $this->check_environment() ? 't' : 'f',
			self::KEY_SUBJECT    => $this->get_default_subject(),
			self::KEY_BODY       => $this->get_default_body(),
			self::KEY_ADMIN_ONLY => 't',
		);
		return $ret;
	}

	/**
	 * 設定値取得
	 */
	public function get_settings(): array {
		$settings = array();
		$default  = $this->get_default();

		foreach ( $default as $key => $val ) {
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
		$default = $this->get_default();

		foreach ( $default as $key => $val ) {
			$this->config->set( $key, $settings[ $key ] ?? '' );
		}

		$this->config->save();
	}

	/**
	 * メール件名デフォルト値取得
	 *
	 * @return string
	 */
	private function get_default_subject(): string {
		return 'ログイン通知';
	}

	/**
	 * メール本文デフォルト値取得
	 *
	 * @return string
	 */
	private function get_default_body(): string {
		$text  = "{\$date} {\$time} に {\$user_name} がログインしました。" . "\n";
		$text .= "" . "\n";
		$text .= "【ログイン情報】" . "\n";
		$text .= "" . "\n";
		$text .= "・日時：{\$date} {\$time}" . "\n";
		$text .= "" . "\n";
		$text .= "・ユーザー名：{\$user_name}" . "\n";
		$text .= "" . "\n";
		$text .= "・IPアドレス：{\$ip}" . "\n";
		$text .= "" . "\n";
		$text .= "・リファラー：{\$http_referer}" . "\n";
		$text .= "" . "\n";
		$text .= "・ユーザーエージェント：{\$http_user_agent}" . "\n";
		$text .= "" . "\n";
		$text .= "--" . "\n";
		$text .= "CloudSecure WP Security" . "\n";

		return $text;
	}

	/**
	 * 値割当て
	 *
	 * @param string $text
	 * @param array  $values
	 * @return string
	 */
	private function bind_values( string $text, array $values ): string {
		foreach ( $values as $key => $val ) {
			$text = str_replace( '{$' . $key . '}', $val, $text );
		}
		return $text;
	}

	/**
	 * 通知
	 */
	public function notification( $user_name, $user ) {
		if ( 't' === $this->config->get( self::KEY_ADMIN_ONLY ) && ! $user->has_cap( 'administrator' ) ) {
			return;
		}

		$values = array(
			'user_name'       => $user_name,
			'date'            => $this->get_date(),
			'time'            => $this->get_time(),
			'ip'              => '' !== $this->get_client_ip() ? $this->get_client_ip() : '--',
			'http_referer'    => '' !== $this->get_http_referer() ? $this->get_http_referer() : '--',
			'http_user_agent' => '' !== $this->get_http_user_agent() ? $this->get_http_user_agent() : '--',
		);

		$mail    = $user->get( 'user_email' );
		$subject = $this->bind_values( $this->config->get( self::KEY_SUBJECT ), $values );
		$body    = $this->bind_values( $this->config->get( self::KEY_BODY ), $values );

		$this->wp_send_mail( $mail, esc_html( $subject ), esc_html( $body ) );
	}

	/**
	 * 有効化
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->save_settings( $this->get_default() );
	}

	/**
	 * 無効化
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->config->set( $this->get_feature_key(), 'f' );
		$this->config->save();
	}
}
