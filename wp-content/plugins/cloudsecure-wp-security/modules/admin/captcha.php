<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_CAPTCHA extends CloudSecureWP_Admin_Common {
	private $captcha;
	private $constant_settings;

	function __construct( array $info, CloudSecureWP_CAPTCHA $captcha ) {
		parent::__construct( $info );
		$this->captcha           = $captcha;
		$this->constant_settings = $this->captcha->get_constant_settings();
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->captcha->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->captcha->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'captcha':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						if ( '$tmp' === $tmp ) {
							$environment_error = $this->captcha->check_modules();

							if ( '' !== $environment_error ) {
								$this->errors[] = $environment_error;
								$tmp            = 'f';
							}
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'captcha_login':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['captcha_login'] ) ) {
							$this->errors[] = 'ログインフォームの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'captcha_comment':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['captcha_comment'] ) ) {
							$this->errors[] = 'コメントフォームの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'captcha_lost_password':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['captcha_lost_password'] ) ) {
							$this->errors[] = 'パスワードリセットフォームフォームの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'captcha_register':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['captcha_register'] ) ) {
							$this->errors[] = 'ユーザー登録フォームフォームの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['captcha'] ) {
					$this->messages[] = '画像認証追加機能が有効になりました。';
				} else {
					$this->messages[] = '画像認証追加機能が無効になりました。';
				}

				$this->captcha->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'captcha', 'captcha_login', 'captcha_comment', 'captcha_lost_password', 'captcha_register' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">画像認証追加</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/captcha.php">こちら</a></p>
			<h1 class="title-block-title">画像認証追加</h1>
		</div>
		<div class="title-bottom-text">
			画像データ上にランダムに表示される文字の入力を求め、一致しなければ次の画面に進めないようにする機能です。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="captcha" value="t" <?php echo esc_html( $this->datas['captcha_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="captcha" value="f" <?php echo esc_html( $this->datas['captcha_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">ログインフォーム</div>
						<div class="box-row-content radio-btns">
							<input type="radio" class="circle-radio" id="captcha_login-off" name="captcha_login" value="<?php echo esc_attr( $this->constant_settings['captcha_login'][0] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_login_' . $this->constant_settings['captcha_login'][0] ] ?? '' ); ?> /><label for="captcha_login-off"><?php echo esc_html( $this->constant_settings['captcha_login'][0] ); ?> 無効</label><br />
							<input type="radio" class="circle-radio" id="captcha_login-on" name="captcha_login" value="<?php echo esc_attr( $this->constant_settings['captcha_login'][1] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_login_' . $this->constant_settings['captcha_login'][1] ] ?? '' ); ?> /><label for="captcha_login-on"><?php echo esc_html( $this->constant_settings['captcha_login'][1] ); ?> 有効</label>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">コメントフォーム</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="captcha_comment-off" class="circle-radio" name="captcha_comment" value="<?php echo esc_attr( $this->constant_settings['captcha_comment'][0] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_comment_' . $this->constant_settings['captcha_comment'][0] ] ?? '' ); ?> /><label for="captcha_comment-off"><?php echo esc_html( $this->constant_settings['captcha_comment'][0] ); ?> 無効</label><br />
							<input type="radio" id="captcha_comment-on" class="circle-radio" name="captcha_comment" value="<?php echo esc_attr( $this->constant_settings['captcha_comment'][1] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_comment_' . $this->constant_settings['captcha_comment'][1] ] ?? '' ); ?> /><label for="captcha_comment-on"><?php echo esc_html( $this->constant_settings['captcha_comment'][1] ); ?> 有効</label><br />
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">パスワードリセットフォーム</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="captcha_lost_password-off" class="circle-radio" name="captcha_lost_password" value="<?php echo esc_attr( $this->constant_settings['captcha_lost_password'][0] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_lost_password_' . $this->constant_settings['captcha_lost_password'][0] ] ?? '' ); ?> /><label for="captcha_lost_password-off"><?php echo esc_html( $this->constant_settings['captcha_lost_password'][0] ); ?> 無効</label><br />
							<input type="radio" id="captcha_lost_password-on" class="circle-radio" name="captcha_lost_password" value="<?php echo esc_attr( $this->constant_settings['captcha_lost_password'][1] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_lost_password_' . $this->constant_settings['captcha_lost_password'][1] ] ?? '' ); ?> /><label for="captcha_lost_password-on"><?php echo esc_html( $this->constant_settings['captcha_lost_password'][1] ); ?> 有効</label>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">ユーザー登録フォーム</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="captcha_register-off" class="circle-radio" name="captcha_register" value="<?php echo esc_attr( $this->constant_settings['captcha_register'][0] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_register_' . $this->constant_settings['captcha_register'][0] ] ?? '' ); ?> /><label for="captcha_register-off"><?php echo esc_html( $this->constant_settings['captcha_register'][0] ); ?> 無効</label><br />
							<input type="radio" id="captcha_register-on" class="circle-radio" name="captcha_register" value="<?php echo esc_attr( $this->constant_settings['captcha_register'][1] ); ?>" <?php echo esc_html( $this->datas[ 'captcha_register_' . $this->constant_settings['captcha_register'][1] ] ?? '' ); ?> /><label for="captcha_register-on"><?php echo esc_html( $this->constant_settings['captcha_register'][1] ); ?> 有効</label><br />
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->captcha->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
