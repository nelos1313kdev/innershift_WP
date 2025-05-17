<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Disable_Access_System_File extends CloudSecureWP_Admin_Common {
	private $disable_access_system_file;

	function __construct( array $info, CloudSecureWP_Disable_Access_System_File $disable_access_system_file ) {
		parent::__construct( $info );
		$this->disable_access_system_file = $disable_access_system_file;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->disable_access_system_file->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->disable_access_system_file->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'disable_access_system_file':
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
				if ( 't' === $this->datas['disable_access_system_file'] ) {
					$this->messages[] = '設定ファイルアクセス防止機能が有効になりました。';
				} else {
					$this->messages[] = '設定ファイルアクセス防止機能が無効になりました。';
				}

				$this->disable_access_system_file->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'disable_access_system_file' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">設定ファイルアクセス防止</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/disable_access_system_file.php">こちら</a></p>
			<h1 class="title-block-title">設定ファイルアクセス防止</h1>
		</div>
		<div class="title-bottom-text">
			「wp-config.php」等の設定ファイルへのアクセスを検知すると、403エラー（Foribidden）を返します。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="disable_access_system_file" value="t" <?php echo esc_html( $this->datas['disable_access_system_file_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="disable_access_system_file" value="f" <?php echo esc_html( $this->datas['disable_access_system_file_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->disable_access_system_file->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
