<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Rename_Login_Page extends CloudSecureWP_Admin_Common {
	private $rename_login_page;
	private $page_url;

	function __construct( array $info, CloudSecureWP_Rename_Login_Page $rename_login_page ) {
		parent::__construct( $info );
		$this->rename_login_page = $rename_login_page;
		$this->page_url          = admin_url( 'admin.php?page=cloudsecurewp_rename_login_page' );
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->rename_login_page->get_settings();

		$is_renamed = sanitize_text_field( $_GET['renamed'] ?? '' );
		if ( 't' === $is_renamed ) {
			$this->messages[]           = 'ログインURL変更機能が有効になりました。';
			$this->important_messages[] = '<a href="' . site_url() . '/' . $this->datas['rename_login_page_name'] . '" target="__blank">新しいログインページURL</a>をブックマークしてください';

		} elseif ( 'f' === $is_renamed ) {
			$this->messages[] = 'ログインURL変更機能が無効になりました。';
		}

		if ( ! empty( $_POST ) && check_admin_referer( $this->rename_login_page->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'rename_login_page':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'rename_login_page_name':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $tmp || ! preg_match( '/^[a-z0-9_\-]{4,12}$/', $tmp ) ) {
							$this->errors[] = 'ログインページ名の値が不正です<br />( 半角英小文字、半角数字、ハイフン、アンダースコアが使用可能で4文字以上12文字以下で入力してください )';
						} elseif ( $this->rename_login_page->is_duplicat_file( $tmp ) ) {
								$this->errors[] = 'ログインページ名の値が不正です<br />( 既に存在する名称です )';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'rename_login_page_disable_redirect':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? 'f' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = 'リダイレクト設定の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {

				$old_data = $this->rename_login_page->get_settings();
				$this->rename_login_page->save_settings( $this->datas );
				$logout_url = $this->page_url . '&renamed=';

				if ( 't' === $this->datas['rename_login_page'] ) {
					if ( $this->rename_login_page->update_htaccess() ) {
						$this->messages[] = 'ログインURL変更機能が有効になりました。';

						if ( $old_data['rename_login_page'] !== $this->datas['rename_login_page'] || $old_data['rename_login_page_name'] !== $this->datas['rename_login_page_name'] ) {
							$this->rename_login_page->notification( $this->datas['rename_login_page_name'] );
							$logout_url .= 't';
							wp_redirect( $logout_url );
							exit;
						}
					} else {
						$this->errors[] = self::MESSAGES['error_htaccess_update'];
						$this->errors[] = 'ログインURL変更機能が無効になりました。';

						$this->datas['rename_login_page']      = 'f';
						$this->datas['rename_login_page_name'] = $old_data['rename_login_page_name'];
						$this->rename_login_page->save_settings( $this->datas );
					}
				} elseif ( $this->rename_login_page->remove_htaccess() ) {
						$logout_url .= 'f';
						wp_redirect( $logout_url );
						exit;

				} else {
					$this->errors[] = self::MESSAGES['error_htaccess_update'];
				}
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'rename_login_page', 'rename_login_page_disable_redirect' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ログインURL変更</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/rename_login_page.php">こちら</a></p>
			<h1 class="title-block-title">ログインURL変更</h1>
		</div>
		<div class="title-bottom-text">
			ログインURL（wp-login.php）を変更します。<br />
			半角英小文字、半角数字、ハイフン、アンダースコアのいずれかを使用し、4文字以上12文字以下でお好みの名前（文字列）に設定できます。<br />
			<strong>※この機能を使用するには「mod_rewrite」がサーバーにロードされている必要があります。</strong>
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		?>
		<form method="post" action="<?php echo esc_url( $this->page_url ); ?>">
			<div class="enabled-or-disabled">
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="rename_login_page" value="t" <?php echo esc_html( $this->datas['rename_login_page_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="rename_login_page" value="f" <?php echo esc_html( $this->datas['rename_login_page_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label pt-12">
						変更後のログインURL
						</div>
						<div class="box-row-content">
							<div class="flex">
								<span class="before-input"><?php echo esc_url( site_url() ); ?>/</span><input type="text" class="rename-login-page-name-input" name="rename_login_page_name" id="rename_login_page_name" value="<?php echo esc_attr( sanitize_text_field( $this->datas['rename_login_page_name'] ) ); ?>" minlength="4" maxlength="12" />
							</div>
							<p class="pale-text">半角英小文字、半角数字、ハイフン、アンダースコアのいずれかを使用し、4文字以上12文字以下で入力してください。</p>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">
						リダイレクト設定
						</div>
						<div class="box-row-content ">
							<input id="redirect-checkbox" class="checkbox" type="checkbox" name="rename_login_page_disable_redirect" value="t" <?php echo esc_html( $this->datas['rename_login_page_disable_redirect_t'] ?? '' ); ?> /><label for="redirect-checkbox">管理者ページからログインページにリダイレクトしない</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->rename_login_page->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
