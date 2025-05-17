<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Two_Factor_Authentication extends CloudSecureWP_Admin_Common {
	private $two_factor_authentication;

	function __construct( array $info, CloudSecureWP_Two_Factor_Authentication $two_factor_authentication ) {
		parent::__construct( $info );
		$this->two_factor_authentication = $two_factor_authentication;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->two_factor_authentication->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->two_factor_authentication->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				if ( 'two_factor_authentication' === $key ) {
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

				$roles = array_map( 'sanitize_text_field', $_POST['roles'] ?? array() );
				update_option( 'cloudsecurewp_two_factor_authentication_roles', $roles );

				if ( 't' === $this->datas['two_factor_authentication'] ) {
					$this->messages[] = '2段階認証機能が有効になりました。';
				} else {
					$this->messages[] = '2段階認証機能が無効になりました。';
				}

				$this->two_factor_authentication->save_settings( $this->datas );

			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'two_factor_authentication' ) );
	}

	/**
	 * ディスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">2段階認証</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/two_factor_authentication.php">こちら</a></p>
			<h1 class="title-block-title">2段階認証</h1>
		</div>
		<div class="title-bottom-text">
			ユーザー名とパスワードの入力に加え、別のコードで追加認証を行います。<br />
			<b>※利用するには、<a class="title-block-link" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Google Authenticator</a> 　アプリケーションでデバイスを登録する必要があります。</b>
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		$all_roles = array_keys( wp_roles()->roles );
		$roles     = get_option( 'cloudsecurewp_two_factor_authentication_roles', $all_roles );
		$lastkey   = '';

		if ( ! empty( $all_roles ) ) {
			end( $all_roles );
			$lastkey = key( $all_roles );
			reset( $all_roles );
		}

		?>
		<form method="post">
			<div class="enabled-or-disabled">
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="two_factor_authentication"
						value="t" <?php echo esc_html( $this->datas['two_factor_authentication_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="two_factor_authentication"
						value="f" <?php echo esc_html( $this->datas['two_factor_authentication_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">2段階認証が有効な権限グループ</div>
						<div class="box-row-content">
							<?php foreach ( $all_roles as $key => $role ) : ?>
								<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $role ); ?>" name="roles[]"
										value="<?php echo esc_attr( $role ); ?>"<?php checked( in_array( $role, $roles ) ); ?> />
								<label for="<?php echo esc_attr( $role ); ?>"><?php echo esc_html_x( ucfirst( $role ), 'User role' ); ?></label>
								<?php if ( $key !== $lastkey ) : ?>
									<br/>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->two_factor_authentication->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
