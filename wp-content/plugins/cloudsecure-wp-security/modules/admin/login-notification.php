<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Login_Notification extends CloudSecureWP_Admin_Common {
	private $login_notification;

	function __construct( array $info, CloudSecureWP_Login_Notification $login_notification ) {
		parent::__construct( $info );
		$this->login_notification = $login_notification;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->login_notification->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->login_notification->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'login_notification':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'login_notification_subject':
						$tmp = stripslashes( sanitize_textarea_field( $_POST[ $key ] ?? '' ) );
						if ( ! $tmp ) {
							$this->errors[] = '件名を入力してください';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'login_notification_body':
						$tmp = stripslashes( sanitize_textarea_field( $_POST[ $key ] ?? '' ) );
						if ( ! $tmp ) {
							$this->errors[] = '本文を入力してください';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'login_notification_admin_only':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? 'f' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '管理者のみ通知の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['login_notification'] ) {
					$this->messages[] = 'ログイン通知機能が有効になりました。';
				} else {
					$this->messages[] = 'ログイン通知機能が無効になりました。';
				}

				$this->login_notification->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'login_notification', 'login_notification_admin_only' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ログイン通知</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/login_notification.php">こちら</a></p>
			<h1 class="title-block-title">ログイン通知</h1>
		</div>
		<div class="title-bottom-text">
			ログインがあったとき、ユーザーにメールで通知します。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="login_notification" value="t" <?php echo esc_html( $this->datas['login_notification_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="login_notification" value="f" <?php echo esc_html( $this->datas['login_notification_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label pt-12">
							サブジェクト
						</div>
						<div class="box-row-content">
							<input style="width:100%;" type="text" name="login_notification_subject" value="<?php echo esc_attr( sanitize_text_field( $this->datas['login_notification_subject'] ) ); ?>" />
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label pt-12">
							メール本文
						</div>
						<div class="box-row-content">
							<textarea class="login-notification-textarea" style="width:100%; height:148px;" name="login_notification_body"><?php echo esc_textarea( $this->datas['login_notification_body'] ); ?></textarea>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">
						受信者
						</div>
						<div class="box-row-content ">
							<input id="receiver-checkbox" class="checkbox" type="checkbox" name="login_notification_admin_only" value="t" <?php echo esc_html( $this->datas['login_notification_admin_only_t'] ?? '' ); ?> /><label for="receiver-checkbox">管理者のみ通知</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->login_notification->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
