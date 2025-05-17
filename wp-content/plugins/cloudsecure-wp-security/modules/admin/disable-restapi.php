<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Disable_RESTAPI extends CloudSecureWP_Admin_Common {
	private $disable_restapi;
	private $active_plugin_list = '';
	private $allowed_html;

	function __construct( array $info, CloudSecureWP_Disable_RESTAPI $disable_restapi ) {
		parent::__construct( $info );
		$this->disable_restapi = $disable_restapi;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->disable_restapi->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->disable_restapi->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'disable_rest_api':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'disable_rest_api_exclude':
						$tmp                 = stripslashes( sanitize_textarea_field( $_POST[ $key ] ?? '' ) );
						$this->datas[ $key ] = $this->disable_restapi->text2array( $tmp );
						break;
				}
			}

			if ( empty( $this->errors ) ) {
				if ( 't' === $this->datas['disable_rest_api'] ) {
					$this->messages[] = 'REST API無効化機能が有効になりました。';
				} else {
					$this->messages[] = 'REST API無効化機能が無効になりました。';
				}

				$this->disable_restapi->save_settings( $this->datas );
			}
		}

		if ( is_array( $this->datas['disable_rest_api_exclude'] ) ) {
			$this->datas['disable_rest_api_exclude'] = $this->array2text( $this->datas['disable_rest_api_exclude'] );
		}

		$this->datas    = $this->get_checked( $this->datas, array( 'disable_rest_api' ) );
		$active_plugins = $this->disable_restapi->get_active_plugin_names();

		foreach ( $active_plugins as $active_plugin ) {
			$this->active_plugin_list .= '<li><span>' . $active_plugin . '</span>&nbsp;<a class="btn-exclude">[追加]</a></li>' . "\n";
		}

		$this->allowed_html = array(
			'li'   => array(),
			'span' => array(),
			'a'    => array(
				'class' => array(),
			),
		);
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">REST API無効化</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/disable_restapi.php">こちら</a></p>
			<h1 class="title-block-title">REST API無効化</h1>
		</div>
		<div class="title-bottom-text">
			REST APIの悪用を防ぐため、機能自体を無効化します。<br />
			デフォルトでは「oEmbed」「Contact Form 7」「Akismet」を除外プラグインにしています。<br />
			<strong>※上記プラグインが正常に機能しない可能性があるため。</strong>
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="disable_rest_api" value="t" <?php echo esc_html( $this->datas['disable_rest_api_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="disable_rest_api" value="f" <?php echo esc_html( $this->datas['disable_rest_api_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-top">除外プラグイン</div>
				<div class="box-bottom pt-0">
					<div class="remove-plugin-area">
						<div class="remove-plugin-area-text order-1">除外するプラグインを直接入力できます。※プラグインごとに改行してください。</div>
						<div class="remove-plugin-area-text order-3">以下の有効なプラグインの「＋」ボタンをクリックすると、除外するプラグインに追加できます。</div>
						<div class="remove-plugin-area-textarea-wrapper">
							<textarea class="remove-plugin-area-textarea" id="disable_rest_api_exclude" name="disable_rest_api_exclude"><?php echo esc_textarea( $this->datas['disable_rest_api_exclude'] ); ?></textarea>
						</div>
						<div class="remove-plugin-area-like-textarea-wrapper">
							<div class="remove-plugin-area-like-textarea">
								<ul id="active-plugins">
									<?php echo wp_kses( $this->active_plugin_list, $this->allowed_html ); ?>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->disable_restapi->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
