<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Common extends CloudSecureWP_Common {
	protected $datas;
	protected $messages;
	protected $errors;
	protected $important_messages;
	public const TF_VALIES = array( 't', 'f' );
	private $allowed_notice_html;

	function __construct( array $info ) {
		parent::__construct( $info );
		$this->datas              = array();
		$this->messages           = array();
		$this->errors             = array();
		$this->important_messages = array();
	}

	/**
	 * メッセージ行
	 *
	 * @param array $messages
	 * @return string
	 */
	protected function get_message_view_lines( array $messages ): string {
		$view_lines = '';

		foreach ( $messages as $message ) {
			$view_lines .= '<div>' . $message . '</div>';
		}
		return $view_lines;
	}

	/**
	 * メッセージ表示
	 *
	 * @param array $messages
	 * @return string
	 */
	protected function get_message_notices( array $messages ): string {
		if ( ! empty( $messages ) ) {
			$view_lines = $this->get_message_view_lines( $messages );
			return '<div id="settings_updated" class="success-box ">' . $view_lines . '</div>';
		}
		return '';
	}

	/**
	 * エラー表示
	 *
	 * @param array $messages
	 * @return string
	 */
	protected function get_error_notices( array $messages ): string {
		if ( ! empty( $messages ) ) {
			$view_lines = $this->get_message_view_lines( $messages );
			return '<div class="error-box">' . $view_lines . '</div>';
		}
		return '';
	}

	/**
	 * 重要メッセージ表示
	 *
	 * @param array $messages
	 * @return string
	 */
	protected function get_important_message_notices( array $messages ): string {
		if ( ! empty( $messages ) ) {
			$view_lines = $this->get_message_view_lines( $messages );
			return '<div id="settings_updated" class="success-box green">' . $view_lines . '</div>';
		}
		return '';
	}

	/**
	 * submitボタン取得
	 *
	 * @param string $view_text
	 * @return void
	 */
	protected function submit_button_wp( string $view_text = '' ): void {
		if ( '' === $view_text ) {
			$view_text = '変更を保存';
		}
		submit_button( $view_text );
	}

	/**
	 * wp_nonceのview
	 *
	 * @param string $feature_key
	 * @return void
	 */
	protected function nonce_wp( string $feature_key ): void {
		wp_nonce_field( $feature_key . '_csrf', '_wpnonce' );
	}

	/**
	 * checkbox radioのchecked
	 *
	 * @param array $datas
	 * @param array $names
	 * @return array
	 */
	protected function get_checked( array $datas, array $names ): array {
		$checks = array();

		foreach ( $names as $name ) {
			if ( ! empty( $datas[ $name ] ) ) {
				$checks[ $name . '_' . $datas[ $name ] ] = 'checked';
			}
		}
		$datas = array_merge( $checks, $datas );

		return $datas;
	}

	/**
	 * レンダリング
	 *
	 * @return void
	 */
	protected function render(): void {
		$this->allowed_notice_html = array(
			'div' => array(
				'id'    => array(),
				'class' => array(),
			),
			'a'   => array(
				'href'   => array(),
				'target' => array(),
			),
			'br'  => array(),
		);

		wp_enqueue_script( 'cloudsecurewp', $this->info['plugin_url'] . 'assets/js/script.js', array(), $this->info['version'] );

		if ( is_multisite() ) {
			$this->errors[] = self::MESSAGES['error_multisite'];
		}

		$conflict_plugin_name = $this->get_conflict_plugin_name();
		if ( '' !== $conflict_plugin_name ) {
			$this->errors[] = self::MESSAGES['error_conflict_plugin'] . "（{$conflict_plugin_name}）";
		}

		print( '<div id="cloudsecure-wp-security" class="wrap">' . "\n" . '<div class="header">' . "\n" . '<div class="header-logo">' . "\n" . '</div>' . "\n" . '</div>' . "\n" );
		print( '<div class="top-area ">' . "\n" . '<div class="error-success-area ">' . "\n" );

		if ( ! empty( $this->messages ) ) {
			print( wp_kses( $this->get_message_notices( $this->messages ), $this->allowed_notice_html ) );
		}

		if ( ! empty( $this->important_messages ) ) {
			print( wp_kses( $this->get_important_message_notices( $this->important_messages ), $this->allowed_notice_html ) );
		}

		if ( ! empty( $this->errors ) ) {
			print( wp_kses( $this->get_error_notices( $this->errors ), $this->allowed_notice_html ) );
		}

		if ( ! is_multisite() ) {
			print( '</div>' . "\n" );
			$this->admin_description();
			print( '</div>' . "\n" );

			$this->page();
		}

		print( '</div>' . "\n" );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {}

	/**
	 * 選択肢の値確認
	 *
	 * @param string $selected_value
	 * @param array  $values
	 * @return bool
	 */
	protected function is_selected( string $selected_value, array $values ): bool {
		if ( empty( $selected_value ) ) {
			return false;
		}
		return in_array( $selected_value, $values );
	}
}
