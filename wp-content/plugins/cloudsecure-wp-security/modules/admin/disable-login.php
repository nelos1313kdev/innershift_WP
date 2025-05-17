<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Disable_Login extends CloudSecureWP_Admin_Common {
	private $disable_login;
	private $constant_settings;

	function __construct( array $info, CloudSecureWP_Disable_Login $disable_login ) {
		parent::__construct( $info );
		$this->disable_login     = $disable_login;
		$this->constant_settings = $this->disable_login->get_constant_settings();
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->disable_login->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->disable_login->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'disable_login':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'disable_login_interval':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['disable_login_interval'] ) ) {
							$this->errors[] = '間隔の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'disable_login_limit':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['disable_login_limit'] ) ) {
							$this->errors[] = 'ログイン失敗回数の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'disable_login_duration':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['disable_login_duration'] ) ) {
							$this->errors[] = 'ログイン無効時間の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['disable_login'] ) {
					$this->messages[] = 'ログイン無効化機能が有効になりました。';
				} else {
					$this->messages[] = 'ログイン無効化機能が無効になりました。';
				}

				$this->disable_login->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'disable_login', 'disable_login_interval', 'disable_login_limit', 'disable_login_duration' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ログイン無効化</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/disable_login.php">こちら</a></p>
			<h1 class="title-block-title">ログイン無効化</h1>
		</div>
		<div class="title-bottom-text">
			指定した期間内にログイン失敗回数が指定した回数に達した場合、指定した時間ログインを無効化（ブロック）します。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="disable_login" value="t" <?php echo esc_html( $this->datas['disable_login_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="disable_login" value="f" <?php echo esc_html( $this->datas['disable_login_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">間隔</div>
						<div class="box-row-content radio-btns">
							<input type="radio" class="circle-radio" id="interval-s" name="disable_login_interval" value="<?php echo esc_attr( $this->constant_settings['disable_login_interval'][0] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_interval_' . $this->constant_settings['disable_login_interval'][0] ] ?? '' ); ?> /><label for="interval-s"><?php echo esc_html( $this->constant_settings['disable_login_interval'][0] ); ?> 秒</label><br />
							<input type="radio" class="circle-radio" id="interval-m" name="disable_login_interval" value="<?php echo esc_attr( $this->constant_settings['disable_login_interval'][1] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_interval_' . $this->constant_settings['disable_login_interval'][1] ] ?? '' ); ?> /><label for="interval-m"><?php echo esc_html( $this->constant_settings['disable_login_interval'][1] ); ?> 秒</label><br />
							<input type="radio" class="circle-radio" id="interval-l" name="disable_login_interval" value="<?php echo esc_attr( $this->constant_settings['disable_login_interval'][2] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_interval_' . $this->constant_settings['disable_login_interval'][2] ] ?? '' ); ?> /><label for="interval-l"><?php echo esc_html( $this->constant_settings['disable_login_interval'][2] ); ?> 秒</label>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">ログイン失敗回数</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="failure-s" class="circle-radio" name="disable_login_limit" value="<?php echo esc_attr( $this->constant_settings['disable_login_limit'][0] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_limit_' . $this->constant_settings['disable_login_limit'][0] ] ?? '' ); ?> /><label for="failure-s"><?php echo esc_html( $this->constant_settings['disable_login_limit'][0] ); ?> 回</label><br />
							<input type="radio" id="failure-m" class="circle-radio" name="disable_login_limit" value="<?php echo esc_attr( $this->constant_settings['disable_login_limit'][1] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_limit_' . $this->constant_settings['disable_login_limit'][1] ] ?? '' ); ?> /><label for="failure-m"><?php echo esc_html( $this->constant_settings['disable_login_limit'][1] ); ?> 回</label><br />
							<input type="radio" id="failure-l" class="circle-radio" name="disable_login_limit" value="<?php echo esc_attr( $this->constant_settings['disable_login_limit'][2] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_limit_' . $this->constant_settings['disable_login_limit'][2] ] ?? '' ); ?> /><label for="failure-l"><?php echo esc_html( $this->constant_settings['disable_login_limit'][2] ); ?> 回</label>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">ログイン無効時間</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="invalid-ss" class="circle-radio" name="disable_login_duration" value="<?php echo esc_attr( $this->constant_settings['disable_login_duration'][0] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_duration_' . $this->constant_settings['disable_login_duration'][0] ] ?? '' ); ?> /><label for="invalid-ss"><?php echo esc_html( $this->constant_settings['disable_login_duration'][0] ); ?> 秒</label><br />
							<input type="radio" id="invalid-s" class="circle-radio" name="disable_login_duration" value="<?php echo esc_attr( $this->constant_settings['disable_login_duration'][1] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_duration_' . $this->constant_settings['disable_login_duration'][1] ] ?? '' ); ?> /><label for="invalid-s"><?php echo esc_html( $this->constant_settings['disable_login_duration'][1] ); ?> 秒</label><br />
							<input type="radio" id="invalid-m" class="circle-radio" name="disable_login_duration" value="<?php echo esc_attr( $this->constant_settings['disable_login_duration'][2] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_duration_' . $this->constant_settings['disable_login_duration'][2] ] ?? '' ); ?> /><label for="invalid-m"><?php echo esc_html( $this->constant_settings['disable_login_duration'][2] ); ?> 秒</label><br />
							<input type="radio" id="invalid-l" class="circle-radio" name="disable_login_duration" value="<?php echo esc_attr( $this->constant_settings['disable_login_duration'][3] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_duration_' . $this->constant_settings['disable_login_duration'][3] ] ?? '' ); ?> /><label for="invalid-l"><?php echo esc_html( $this->constant_settings['disable_login_duration'][3] ); ?> 秒</label><br />
							<input type="radio" id="invalid-ll" class="circle-radio" name="disable_login_duration" value="<?php echo esc_attr( $this->constant_settings['disable_login_duration'][4] ); ?>" <?php echo esc_html( $this->datas[ 'disable_login_duration_' . $this->constant_settings['disable_login_duration'][4] ] ?? '' ); ?> /><label for="invalid-ll"><?php echo esc_html( $this->constant_settings['disable_login_duration'][4] ); ?> 秒</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->disable_login->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
