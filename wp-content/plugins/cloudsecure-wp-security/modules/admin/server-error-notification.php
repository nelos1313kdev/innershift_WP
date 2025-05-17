<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Server_Error_Notification extends CloudSecureWP_Admin_Common {
	private $server_error_notification;

	function __construct( array $info, CloudSecureWP_Server_Error_Notification $server_error_notification ) {
		parent::__construct( $info );
		$this->server_error_notification = $server_error_notification;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->server_error_notification->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->server_error_notification->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				if ( $key === 'server_error_notification' ) {
					$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
					if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
						$this->errors[] = '有効・無効の値が不正です';
					}

					if ( ! $this->check_environment() ) {
						$tmp = 'f';
					}

					$this->datas[ $key ] = $tmp;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['server_error_notification'] ) {
					$this->messages[] = 'サーバーエラー通知機能が有効になりました。';
				} else {
					$this->messages[] = 'サーバーエラー通知機能が無効になりました。';
				}

				$enable_email_server_error_notification = sanitize_text_field( $_POST['enable_email_server_error_notification'] ?? 'f' );
				update_option( 'cloudsecurewp_enable_email_server_error_notification', $enable_email_server_error_notification );

				$this->server_error_notification->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'server_error_notification' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">サーバーエラー通知</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/server_error_notification.php">こちら</a></p>
			<h1 class="title-block-title">サーバーエラー通知</h1>
		</div>
		<div class="title-bottom-text">
			サーバーエラー「HTTPステータスコード500（Internal Server Error）」が発生したとき、エラーの履歴を記録し、管理者にメールで通知します。<br />
			<strong>※機能を有効にした場合のみ、エラーの履歴を記録します。</strong><br />
			1時間以内に同じタイプのエラーが発生した場合、エラーの履歴は記録しますが、メールでの通知は行いません。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="server_error_notification" value="t" <?php echo esc_html( $this->datas['server_error_notification_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="server_error_notification" value="f" <?php echo esc_html( $this->datas['server_error_notification_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">
							通知
						</div>
						<div class="box-row-content ">
							<input id="enable_email_server_error_notification" class="checkbox" type="checkbox" name="enable_email_server_error_notification" value="t" <?php checked( get_option( 'cloudsecurewp_enable_email_server_error_notification', 't' ), 't' ); ?> /><label for="enable_email_server_error_notification">メールで通知する</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->server_error_notification->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<style>
			th.column-created_at {
				width: 180px;
			}

			th.column-type {
				width: 200px;
			}

			th.column-line {
				width: 64px;
			}
		</style>
		<?php
		$server_error_table = new CloudSecureWP_Server_Error_Table( $this->server_error_notification );
		$server_error_table->prepare_items();
		$server_error_table->display();
	}
}
