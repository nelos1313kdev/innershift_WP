<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Disable_Author_Query extends CloudSecureWP_Admin_Common {
	private $disable_author_query;

	function __construct( array $info, CloudSecureWP_Disable_Author_Query $disable_author_query ) {
		parent::__construct( $info );
		$this->disable_author_query = $disable_author_query;
		$this->prepare_view_data();
		$this->render();
	}

	/**
	 * 画面表示用のデータを準備
	 */
	public function prepare_view_data(): void {
		$this->datas = $this->disable_author_query->get_settings();

		if ( ! empty( $_POST ) && check_admin_referer( $this->disable_author_query->get_feature_key() . '_csrf' ) ) {

			foreach ( $this->datas as $key => $val ) {

				switch ( $key ) {
					case 'disable_author_query':
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
				if ( 't' === $this->datas['disable_author_query'] ) {
					$this->messages[] = 'ユーザー名漏えい防止機能が有効になりました。';
				} else {
					$this->messages[] = 'ユーザー名漏えい防止機能が無効になりました。';
				}

				$this->disable_author_query->save_settings( $this->datas );
			}
		}

		$this->datas = $this->get_checked( $this->datas, array( 'disable_author_query' ) );
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<nav>
			<ul class="breadcrumb">
				<li class="breadcrumb__list"><a href="?page=cloudsecurewp">ダッシュボード</a></li>
				<li class="breadcrumb__list">ユーザー名漏えい防止</li>
			</ul>
		</nav>
		<div class="title-block mb-12">
			<p class="title-block-small-text">この機能のマニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/disable_author_query.php">こちら</a></p>
			<h1 class="title-block-title">ユーザー名漏えい防止</h1>
		</div>
		<div class="title-bottom-text">
			「?author=数字」でのアクセスによるユーザー名の漏えいを防止します。
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
				<input class="enabled-or-disabled__btn" id="enabled" type="radio" name="disable_author_query" value="t" <?php echo esc_html( $this->datas['disable_author_query_t'] ?? '' ); ?> /><label for="enabled">有効</label>
				<input class="enabled-or-disabled__btn" id="disabled" type="radio" name="disable_author_query" value="f" <?php echo esc_html( $this->datas['disable_author_query_f'] ?? '' ); ?> /><label for="disabled">無効</label>
			</div>
			<div id="submit-btn-area">
				<?php $this->nonce_wp( $this->disable_author_query->get_feature_key() ); ?>
				<?php $this->submit_button_wp(); ?>
			</div>
		</form>
		<?php
	}
}
