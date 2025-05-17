<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Login_Log extends CloudSecureWP_Admin_Common {
	private $login_log;
	private $login_log_table;

	function __construct( array $info, CloudSecureWP_Login_Log $login_log ) {
		parent::__construct( $info );

		$this->login_log       = $login_log;
		$this->login_log_table = new CloudSecureWP_Login_Log_Table( $login_log );

		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$conditions = array();
		$referer    = wp_get_referer();

		if ( empty( $referer ) ) {
			if ( ! empty( $_POST ) && check_admin_referer( $this->login_log->get_feature_key() . '_csrf' ) ) {
				if ( ! empty( $_POST['done'] ?? '' ) ) {

					if ( isset( $_POST['condition_status'] ) && ! empty( $_POST['condition_status'] ) ) {
						$conditions['condition_status'] = trim( sanitize_text_field( $_POST['condition_status'] ) );
					}

					if ( isset( $_POST['condition_ip'] ) && ! empty( $_POST['condition_ip'] ) ) {
						$conditions['condition_ip'] = trim( sanitize_text_field( $_POST['condition_ip'] ) );
					}

					if ( isset( $_POST['condition_ip_other_than'] ) && ! empty( $_POST['condition_ip_other_than'] ) ) {
						$conditions['condition_ip_other_than'] = trim( sanitize_text_field( $_POST['condition_ip_other_than'] ) );
					}

					if ( isset( $_POST['condition_method'] ) && ! empty( $_POST['condition_method'] ) ) {
						$conditions['condition_method'] = trim( sanitize_text_field( $_POST['condition_method'] ) );
					}

					if ( isset( $_POST['condition_name'] ) && ! empty( $_POST['condition_name'] ) ) {
						$conditions['condition_name'] = trim( sanitize_text_field( $_POST['condition_name'] ) );
					}

					if ( isset( $_POST['condition_name_other_than'] ) && ! empty( $_POST['condition_name_other_than'] ) ) {
						$conditions['condition_name_other_than'] = trim( sanitize_text_field( $_POST['condition_name_other_than'] ) );
					}
				}
			}
		} elseif ( false !== strpos( $referer, 'page=cloudsecurewp_login_log' ) ) {
			$conditions = $this->login_log_table->get_conditions();
		}

		$this->login_log_table->set_condition( $conditions );
		$this->login_log_table->save_conditions();
		$this->login_log_table->prepare_items();
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ログイン履歴</li>
			</ul>
		</nav>
				<div class="title-block mb-12">
					<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/login_log.php">こちら</a></p>
					<h1 class="title-block-title">ログイン履歴</h1>
				</div>
				<div class="title-bottom-text">
					管理画面にログインした履歴を表示します。
				</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		?>

		<form name="frm" method="POST">
			<div id="login-log">

				<?php $this->login_log_table->display(); ?>
			</div>
		</form>

		<?php
	}
}

