<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Disable_XMLRPC extends CloudSecureWP_Admin_Common {
	private $disable_xmlrpc;
	private $constant_settings;

	function __construct( array $info, CloudSecureWP_Disable_XMLRPC $disable_xmlrpc ) {
		parent::__construct( $info );
		$this->disable_xmlrpc    = $disable_xmlrpc;
		$this->constant_settings = $this->disable_xmlrpc->get_constant_settings();
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->disable_xmlrpc->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->disable_xmlrpc->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'disable_xmlrpc':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, self::TF_VALIES ) ) {
							$this->errors[] = '有効・無効の値が不正です';
						}

						if ( ! $this->check_environment() ) {
							$tmp = 'f';
						}

						$this->datas[ $key ] = $tmp;
						break;

					case 'disable_xmlrpc_type':
						$tmp = sanitize_text_field( $_POST[ $key ] ?? '' );
						if ( ! $this->is_selected( $tmp, $this->constant_settings['disable_xmlrpc_type'] ) ) {
							$this->errors[] = '無効化種別の値が不正です';
						}
						$this->datas[ $key ] = $tmp;
						break;
				}
			}

			if ( empty( $this->errors ) ) {

				$this->disable_xmlrpc->save_settings( $this->datas );

				if ( 't' === $this->datas['disable_xmlrpc'] ) {
					if ( $this->datas['disable_xmlrpc_type'] === $this->constant_settings['disable_xmlrpc_type'][0] ) {
						if ( $this->disable_xmlrpc->remove_htaccess() ) {
							$this->messages[] = 'ピンバック無効化機能が有効になりました。';
						} else {
							$this->errors[] = self::MESSAGES['error_htaccess_update'];
							$this->errors[] = 'XML-RPC無効化機能が無効になりました。';

							$this->datas['disable_xmlrpc'] = 'f';
							$this->disable_xmlrpc->save_settings( $this->datas );
						}
					} elseif ( $this->datas['disable_xmlrpc_type'] === $this->constant_settings['disable_xmlrpc_type'][1] ) {

						if ( $this->disable_xmlrpc->update_htaccess() ) {
							$this->messages[] = 'XML-RPC無効化機能が有効になりました。';
						} else {
							$this->errors[] = self::MESSAGES['error_htaccess_update'];
							$this->errors[] = 'XML-RPC無効化機能が無効になりました。';

							$this->datas['disable_xmlrpc'] = 'f';
							$this->disable_xmlrpc->save_settings( $this->datas );
						}
					}
				} elseif ( $this->disable_xmlrpc->remove_htaccess() ) {
						$this->messages[] = 'XML-RPC無効化機能が無効になりました。';
				} else {
					$this->errors[] = self::MESSAGES['error_htaccess_update'];
				}
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'disable_xmlrpc', 'disable_xmlrpc_type' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">XML-RPC無効化</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/disable_xmlrpc.php">こちら</a></p>
			<h1 class="title-block-title">XML-RPC無効化</h1>
		</div>
		<div class="title-bottom-text">
			XML-RPC機能、またはピンバック機能を無効にし、XML-RPC経由での不正ログインを防ぎます。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="disable_xmlrpc" value="t" <?php echo esc_html( $this->datas['disable_xmlrpc_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="disable_xmlrpc" value="f" <?php echo esc_html( $this->datas['disable_xmlrpc_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div class="box">
				<div class="box-bottom">
					<div class="box-row flex-start">
						<div class="box-row-title not-label">無効化種別</div>
						<div class="box-row-content radio-btns">
							<input type="radio" class="circle-radio" id="pin-pack" name="disable_xmlrpc_type" value="<?php echo esc_attr( $this->constant_settings['disable_xmlrpc_type'][0] ); ?>" <?php echo esc_html( $this->datas[ 'disable_xmlrpc_type_' . $this->constant_settings['disable_xmlrpc_type'][0] ] ?? '' ); ?> /><label for="pin-pack">ピンバック無効</label><br />
							<input type="radio" class="circle-radio" id="xmlrpc" name="disable_xmlrpc_type" value="<?php echo esc_attr( $this->constant_settings['disable_xmlrpc_type'][1] ); ?>" <?php echo esc_html( $this->datas[ 'disable_xmlrpc_type_' . $this->constant_settings['disable_xmlrpc_type'][1] ] ?? '' ); ?> /><label for="xmlrpc">XML-RPC無効</label>
						</div>
					</div>
				</div>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->disable_xmlrpc->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
