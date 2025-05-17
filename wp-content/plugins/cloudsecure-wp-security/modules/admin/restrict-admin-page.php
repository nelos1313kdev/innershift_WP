<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Restrict_Admin_Page extends CloudSecureWP_Admin_Common {
	private $restrict_admin_page;

	function __construct( array $info, CloudSecureWP_Restrict_Admin_Page $restrict_admin_page ) {
		parent::__construct( $info );
		$this->restrict_admin_page = $restrict_admin_page;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->restrict_admin_page->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->restrict_admin_page->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'restrict_admin_page':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'restrict_admin_page_paths':
						$tmp                 = stripslashes( sanitize_textarea_field( $_POST[ $key ] ?? '' ) );
						$this->datas[ $key ] = $this->restrict_admin_page->text2array( $tmp );
						break;
				}
			}

			if ( empty( $this->errors ) ) {

				$old_data = $this->restrict_admin_page->get_settings();
				$this->restrict_admin_page->save_settings( $this->datas );

				if ( 't' === $this->datas['restrict_admin_page'] ) {
					if ( $this->restrict_admin_page->update() ) {
						$this->messages[] = '管理画面アクセス制限機能が有効になりました。';
					} else {
						$this->errors[] = self::MESSAGES['error_htaccess_update'];
						$this->errors[] = '管理画面アクセス制限機能が無効になりました。';

						$this->datas['restrict_admin_page']       = 'f';
						$this->datas['restrict_admin_page_paths'] = $old_data['restrict_admin_page_paths'];
						$this->restrict_admin_page->save_settings( $this->datas );
					}
				} elseif ( $this->restrict_admin_page->remove_htaccess() ) {
						$this->messages[] = '管理画面アクセス制限機能が無効になりました。';
				} else {
					$this->errors[] = self::MESSAGES['error_htaccess_update'];
				}
			}
		}

		if ( is_array( $this->datas['restrict_admin_page_paths'] ) ) {
			$this->datas['restrict_admin_page_paths'] = $this->array2text( $this->datas['restrict_admin_page_paths'] );
		}

		$this->datas = $this->get_checked( $this->datas, array( 'restrict_admin_page' ) );
	}


	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">管理画面アクセス制限</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/restrict_admin_page.php">こちら</a></p>
			<h1 class="title-block-title">管理画面アクセス制限</h1>
		</div>
		<div class="title-bottom-text">
			管理画面にログインしていない接続元IPアドレスから管理ページ（/wp-admin/以降）にアクセスすると、404エラー（Not Found）を返します。<br />
			24時間以上管理画面にログインしていない接続元IPアドレスが対象です。<br />
			<strong>※この機能を使用するには「mod_rewrite」がサーバーにロードされている必要があります。</strong>
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="restrict_admin_page" value="t" <?php echo esc_html( $this->datas['restrict_admin_page_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="restrict_admin_page" value="f" <?php echo esc_html( $this->datas['restrict_admin_page_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label pt-12">
						除外パス
						</div>
						<div class="box-row-content">
							<textarea class="restrict-textarea" name="restrict_admin_page_paths"><?php echo esc_textarea( $this->datas['restrict_admin_page_paths'] ); ?></textarea>
							<div class="pale-text">
								この機能を除外したいページがあれば、/wp-admin/以降のパスを入力してください。<br />
								複数のページを登録したい場合、改行して登録してください。
							</div>
						</div>
					</div>
				</div>
			</div>
			<div style="margin-bottom:10px;">
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->restrict_admin_page->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
