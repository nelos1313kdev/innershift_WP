<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Waf extends CloudSecureWP_Admin_Common {
	private $waf;
	private $waf_table;
	private $constant_settings;

	function __construct( array $info, CloudSecureWP_Waf $waf ) {
		parent::__construct( $info );
		$this->waf               = $waf;
		$this->waf_table         = new CloudSecureWP_Waf_Table( $this->waf );
		$this->constant_settings = $this->waf->get_constant_settings();
		$this->prepare_view_data();
		$this->render();
	}


	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->waf->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->waf->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'waf':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );

						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'waf_send_admin_mail':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );

						if ( ! $this->is_selected( $tmp, $this->constant_settings['waf_send_admin_mail'] ) ) {
							$this->errors[] = 'メール通知の値が不正です';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'waf_available_rules':
						$tmp = 0;

						if ( isset( $_POST[ $key ] ) ) {
							if ( is_array( $_POST[ $key ] ) ) {
								$sanitized_post_values = array_map( 'sanitize_text_field', $_POST[ $key ] );

								foreach ( $sanitized_post_values as $value ) {
									if ( ! in_array( intval( $value ), $this->constant_settings['waf_available_rules'], true ) ) {
										$this->errors[] = '検知する攻撃種別の値が不正です';
										break;
									}

									$tmp = $tmp | intval( $value );
								}
							} else {
								$this->errors[] = '検知する攻撃種別の値が不正です';
							}
						}

						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['waf'] ) {
					$this->messages[] = 'シンプルWAF機能が有効になりました。';
				} else {
					$this->messages[] = 'シンプルWAF機能が無効になりました。';
				}

				$this->waf->save_settings( $this->datas );
			}
		}

		$this->waf_table->prepare_items();
		$this->datas = $this->get_checked( $this->datas, array( 'waf', 'waf_send_admin_mail' ) );
	}

	/**
	 * waf機能画面のデスクリプション表示用データ
	 */
	public function get_waf_description_data(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">シンプルWAF</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/waf.php">こちら</a></p>
			<h1 class="title-block-title">シンプルWAF</h1>
		</div>
		<div class="title-bottom-text">
			WordPressへの基本的な攻撃を検知すると403エラー（Foribidden）を返して検知履歴を記録し、管理者にメールで通知します。<br />
			1分間以内に同じ種別の攻撃を検知した場合、検知履歴は記録しますが、メールでの通知は行いません。<br />
			<strong>※機能を有効にした場合のみ、検知履歴を記録します。</strong>
		</div>
		<?php
	}

	/**
	 * waf履歴一覧画面のデスクリプション表示用データ
	 */
	public function get_log_description_data(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp_waf">シンプルWAF</a></li>
				<li class="breadcrumb__list">検知履歴一覧</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/waf.php">こちら</a></p>
			<h1 class="title-block-title">検知履歴一覧</h1>
		</div>
		<div class="title-bottom-text">
			シンプルWAFで検知・遮断した攻撃の履歴を表示します。<br />
			正常なリクエストが遮断された場合、該当する攻撃種別の設定を変更してください。<br />
		</div>
		<?php
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		if ( isset( $_GET['childpage'] ) && 'log' === $_GET['childpage'] ) {
			$this->get_log_description_data();
		} else {
			$this->get_waf_description_data();
		}
	}

	/**
	 * waf機能画面のページコンテンツ表示用データ
	 */
	public function get_waf_page_data(): void {
		$lastkey = '';

		if ( ! empty( $this->constant_settings['waf_rules_category'] ) ) {
			end( $this->constant_settings['waf_rules_category'] );
			$lastkey = key( $this->constant_settings['waf_rules_category'] );
			reset( $this->constant_settings['waf_rules_category'] );
		}

		?>
		<form method="post">
			<div class="waf-enabled-or-disabled-and-link-button-flex">
				<div class="enabled-or-disabled">
					<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="waf" value="t" <?php echo esc_html( $this->datas['waf_t'] ?? '' ); ?> /><label for="enabled">有効</label>
					<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="waf" value="f" <?php echo esc_html( $this->datas['waf_f'] ?? '' ); ?> /><label for="disabled">無効</label>
				</div>
				<div class="link-button next-page"><a href="admin.php?page=cloudsecurewp_waf&childpage=log">検知履歴を表示</a></div>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">メール通知</div>
						<div class="box-row-content radio-btns">
							<input type="radio" class="circle-radio" id="waf_send_admin_mail-off" name="waf_send_admin_mail" value="<?php echo esc_attr( $this->constant_settings['waf_send_admin_mail'][0] ); ?>" <?php echo esc_html( $this->datas[ 'waf_send_admin_mail_' . $this->constant_settings['waf_send_admin_mail'][0] ] ?? '' ); ?> /><label for="waf_send_admin_mail-off">通知しない</label><br />
							<input type="radio" class="circle-radio" id="waf_send_admin_mail-on" name="waf_send_admin_mail" value="<?php echo esc_attr( $this->constant_settings['waf_send_admin_mail'][1] ); ?>" <?php echo esc_html( $this->datas[ 'waf_send_admin_mail_' . $this->constant_settings['waf_send_admin_mail'][1] ] ?? '' ); ?> /><label for="waf_send_admin_mail-on">通知する</label><br />
						</div>
					</div>
					<div class="box-row flex-start">
						<div class="box-row-title not-label">検知する攻撃種別</div>
						<div class="box-row-content ">
							<?php foreach ( $this->constant_settings['waf_rules_category'] as $key => $value ) : ?>
								<input id="<?php echo esc_attr( $key ); ?>" class="checkbox" type="checkbox" name="waf_available_rules[]" value=<?php echo esc_attr( $key ); ?> <?php echo esc_html( ( ( $key & $this->datas['waf_available_rules'] ) > 0 ) ? 'checked' : '' ); ?> /><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>
								<?php if ( $key !== $lastkey ) : ?>
									<br/>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->waf->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * waf履歴一覧画面のページコンテンツ表示用データ
	 */
	public function get_log_page_data(): void {
		?>
		<div class="waf-table-and-link-button" >
			<div class="waf-link-button-position link-button back-page"><a href="admin.php?page=cloudsecurewp_waf">戻る</a></div>
			<style>
				table.cloudsecure-wp-security_page_cloudsecurewp_waf{
					margin: 20px 0;
				}
				.tablenav {
					height: 30px;
					margin: 6px 0 4px;
					padding-top: 0px;
				}

				th.column-access_at {
					width: 13em;
				}

				th.column-attack {
					width: 14%;
				}

				th.column-ip {
					width: 13em;
				}

				th.sortable a, th.sorted a {
					display: block;
					overflow: hidden;
					padding: 12px 8px 12px 23px;
				}

			</style>
			<?php $this->waf_table->display(); ?>
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		if ( isset( $_GET['childpage'] ) && 'log' === $_GET['childpage'] ) {
			$this->get_log_page_data();
		} else {
			$this->get_waf_page_data();
		}

	}
}
