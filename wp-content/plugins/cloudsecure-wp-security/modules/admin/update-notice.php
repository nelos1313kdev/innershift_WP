<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Update_Notice extends CloudSecureWP_Admin_Common {
	private $update_notice;
	private $constant_settings;

	function __construct( array $info, CloudSecureWP_Update_Notice $update_notice ) {
		parent::__construct( $info );
		$this->update_notice     = $update_notice;
		$this->constant_settings = $this->update_notice->get_constant_settings();
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas     = $this->update_notice->get_settings();
		$flag_cron_error = false;

		if ( ! empty( $_POST ) && check_admin_referer( $this->update_notice->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'update_notice':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						if ( 't' === $tmp ) {
							$cron_error = $this->update_notice->check_cron_error();

							if ( '' !== $cron_error ) {
								$this->errors[]  = $cron_error;
								$tmp             = 'f';
								$flag_cron_error = true;
							}
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'update_notice_wp':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['update_notice_wp'] ) ) {
							$this->errors[] = 'WordPressアップデートの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'update_notice_plugin':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['update_notice_plugin'] ) ) {
							$this->errors[] = 'プラグインアップデートの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;

					case 'update_notice_theme':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['update_notice_theme'] ) ) {
							$this->errors[] = 'テーマアップデートの値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['update_notice'] ) {
					$this->messages[] = 'アップデート通知機能が有効になりました。';
					$this->update_notice->set_cron();
				} else {
					$this->messages[] = 'アップデート通知機能が無効になりました。';
					$this->update_notice->remove_cron();
					$this->datas['update_notice_last_notice'] = $this->update_notice->get_last_notice_default();
				}

				$this->update_notice->save_settings( $this->datas );

			} elseif ( $flag_cron_error === true ) {
				$this->update_notice->remove_cron();
				$this->update_notice->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'update_notice', 'update_notice_wp', 'update_notice_plugin', 'update_notice_theme' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">アップデート通知</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/update_notification.php">こちら</a></p>
			<h1 class="title-block-title">アップデート通知</h1>
		</div>
		<div class="title-bottom-text">
			WordPress、プラグイン、テーマの更新が必要になったとき、管理者にメールで通知します。<br />
			更新の確認は24時間ごとに行われます。<br />
			<strong>※「/cron.php」へのhttpアクセスが発生するため、アクセスできない環境では機能を有効化できません。<br />
			　Basic認証（ベーシック認証 / 基本認証）を設定している場合など、ご注意ください。</strong>
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="update_notice" value="t" <?php echo esc_html( $this->datas['update_notice_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="update_notice" value="f" <?php echo esc_html( $this->datas['update_notice_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">WordPressアップデート</div>
						<div class="box-row-content radio-btns">
							<input type="radio" class="circle-radio" id="update_notice_wp-off" name="update_notice_wp" value="<?php echo esc_attr( $this->constant_settings['update_notice_wp'][0] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_wp_' . $this->constant_settings['update_notice_wp'][0] ] ?? '' ); ?> /><label for="update_notice_wp-off"><?php echo esc_html( $this->constant_settings['update_notice_wp'][0] ); ?> 通知しない</label><br />
							<input type="radio" class="circle-radio" id="update_notice_wp-on" name="update_notice_wp" value="<?php echo esc_attr( $this->constant_settings['update_notice_wp'][1] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_wp_' . $this->constant_settings['update_notice_wp'][1] ] ?? '' ); ?> /><label for="update_notice_wp-on"><?php echo esc_html( $this->constant_settings['update_notice_wp'][1] ); ?> 通知する</label><br />
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">プラグインアップデート</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="update_notice_plugin-off" class="circle-radio" name="update_notice_plugin" value="<?php echo esc_attr( $this->constant_settings['update_notice_plugin'][0] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_plugin_' . $this->constant_settings['update_notice_plugin'][0] ] ?? '' ); ?> /><label for="update_notice_plugin-off"><?php echo esc_html( $this->constant_settings['update_notice_plugin'][0] ); ?> 通知しない</label><br />
							<input type="radio" id="update_notice_plugin-all-on" class="circle-radio" name="update_notice_plugin" value="<?php echo esc_attr( $this->constant_settings['update_notice_plugin'][1] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_plugin_' . $this->constant_settings['update_notice_plugin'][1] ] ?? '' ); ?> /><label for="update_notice_plugin-all-on"><?php echo esc_html( $this->constant_settings['update_notice_plugin'][1] ); ?> すべて通知する</label><br />
							<input type="radio" id="update_notice_plugin-exclusive-on" class="circle-radio" name="update_notice_plugin" value="<?php echo esc_attr( $this->constant_settings['update_notice_plugin'][2] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_plugin_' . $this->constant_settings['update_notice_plugin'][2] ] ?? '' ); ?> /><label for="update_notice_plugin-exclusive-on"><?php echo esc_html( $this->constant_settings['update_notice_plugin'][2] ); ?> 有効化されたプラグインのみ通知する</label>
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">テーマアップデート</div>
						<div class="box-row-content radio-btns">
							<input type="radio" id="update_notice_theme-off" class="circle-radio" name="update_notice_theme" value="<?php echo esc_attr( $this->constant_settings['update_notice_theme'][0] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_theme_' . $this->constant_settings['update_notice_theme'][0] ] ?? '' ); ?> /><label for="update_notice_theme-off"><?php echo esc_html( $this->constant_settings['update_notice_theme'][0] ); ?> 通知しない</label><br />
							<input type="radio" id="update_notice_theme-all-on" class="circle-radio" name="update_notice_theme" value="<?php echo esc_attr( $this->constant_settings['update_notice_theme'][1] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_theme_' . $this->constant_settings['update_notice_theme'][1] ] ?? '' ); ?> /><label for="update_notice_theme-all-on"><?php echo esc_html( $this->constant_settings['update_notice_theme'][1] ); ?> すべて通知する</label><br />
							<input type="radio" id="update_notice_theme-exclusive-on" class="circle-radio" name="update_notice_theme" value="<?php echo esc_attr( $this->constant_settings['update_notice_theme'][2] ); ?>" <?php echo esc_html( $this->datas[ 'update_notice_theme_' . $this->constant_settings['update_notice_theme'][2] ] ?? '' ); ?> /><label for="update_notice_theme-exclusive-on"><?php echo esc_html( $this->constant_settings['update_notice_theme'][2] ); ?> 有効化されたテーマのみ通知する</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->update_notice->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
