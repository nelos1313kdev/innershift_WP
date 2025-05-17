<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Unify_Messages extends CloudSecureWP_Admin_Common {
	private $unify_messages;

	function __construct( array $info, CloudSecureWP_Unify_Messages $unify_messages ) {
		parent::__construct( $info );
		$this->unify_messages = $unify_messages;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->unify_messages->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->unify_messages->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'unify_messages':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['unify_messages'] ) {
					$this->messages[] = 'ログインエラーメッセージ統一機能が有効になりました。';
				} else {
					$this->messages[] = 'ログインエラーメッセージ統一機能が無効になりました。';
				}

				$this->unify_messages->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'unify_messages' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ログインエラーメッセージ統一</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/unify_messages.php">こちら</a></p>
			<h1 class="title-block-title">ログインエラーメッセージ統一</h1>
		</div>
		<div class="title-bottom-text">
			ログインに関するエラーメッセージについて、ユーザー名、パスワード、画像認証のどれを間違えても同一のメッセージを表示します。
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		?>
		<form method="post">
			<div class="enabled-or-disabled">
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="unify_messages" value="t" <?php echo esc_html( $this->datas['unify_messages_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="unify_messages" value="f" <?php echo esc_html( $this->datas['unify_messages_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->unify_messages->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
