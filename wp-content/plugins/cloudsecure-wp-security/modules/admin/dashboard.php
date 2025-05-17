<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Admin_Dashboard extends CloudSecureWP_Admin_Common {
	private $config;

	function __construct( array $info, array $datas ) {
		parent::__construct( $info );
		$this->datas = $datas;
		$this->render();
	}

	/**
	 * デスクリプション
	 */
	protected function admin_description(): void {
		?>
		<div class="title-block mb-12">
			<p class="title-block-small-text">マニュアルは<a class="title-block-link" target="_blank" href="https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security/">こちら</a></p>
			<h1 class="title-block-title">ダッシュボード</h1>
		</div>
		<?php
	}

	/**
	 * ページコンテンツ
	 */
	protected function page(): void {
		?>
		<div class="box dash-box">
			<div class="box-top">セキュリティ</div>
			<div class="box-bottom pt-0">
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['disable_login'] ); ?>"><?php echo esc_html( $this->datas['disable_login'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_disable_login">ログイン無効化</a></div>
					<div class="box-row-content">ログイン失敗回数が上限に達した場合、ログインを無効化します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['rename_login_page'] ); ?>"><?php echo esc_html( $this->datas['rename_login_page'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_rename_login_page">ログインURL変更</a></div>
					<div class="box-row-content">ログインURLを変更します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['unify_messages'] ); ?>"><?php echo esc_html( $this->datas['unify_messages'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_unify_messages">ログインエラーメッセージ統一</a></div>
					<div class="box-row-content">エラーごとの詳細なメッセージではなく、単一のメッセージを返します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['two_factor_authentication'] ); ?>"><?php echo esc_html( $this->datas['two_factor_authentication'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_two_factor_authentication">2段階認証</a></div>
					<div class="box-row-content">ユーザー名とパスワードの入力に加え、別のコードで追加認証を行います。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['captcha'] ); ?>"><?php echo esc_html( $this->datas['captcha'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_captcha">画像認証追加</a></div>
					<div class="box-row-content">ログインフォーム、コメントフォームなどに画像認証を追加します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['restrict_admin_page'] ); ?>"><?php echo esc_html( $this->datas['restrict_admin_page'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_restrict_admin_page">管理画面アクセス制限</a></div>
					<div class="box-row-content">管理画面ディレクトリ以下へのアクセスを制限します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['disable_access_system_file'] ); ?>"><?php echo esc_html( $this->datas['waf'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_disable_access_system_file">設定ファイルアクセス防止</a></div>
					<div class="box-row-content">設定ファイルへのアクセスを遮断し、情報漏えいを防止します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['disable_author_query'] ); ?>"><?php echo esc_html( $this->datas['disable_author_query'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_disable_author_query">ユーザー名漏えい防止</a></div>
					<div class="box-row-content">「?author=数字」でのアクセスによるユーザー名漏えいを防止します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['disable_xmlrpc'] ); ?>"><?php echo esc_html( $this->datas['disable_xmlrpc'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_disable_xmlrpc">XML-RPC無効化</a></div>
					<div class="box-row-content">XML-RPC機能を無効化します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['disable_rest_api'] ); ?>"><?php echo esc_html( $this->datas['disable_rest_api'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_disable_restapi">REST API 無効化</a></div>
					<div class="box-row-content">REST APIを無効化します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['waf'] ); ?>"><?php echo esc_html( $this->datas['waf'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_waf">シンプルWAF</a></div>
					<div class="box-row-content">WordPressへの基本的な攻撃を検知した場合、攻撃を遮断します。</div>
				</div>
			</div>
		</div>
		<div class="box">
			<div class="box-top">通知</div>
			<div class="box-bottom pt-0">
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['login_notification'] ); ?>"><?php echo esc_html( $this->datas['login_notification'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_login_notification">ログイン通知</a></div>
					<div class="box-row-content">ログインがあったことをメールで通知します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['update_notice'] ); ?>"><?php echo esc_html( $this->datas['disable_login'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_update_notification">アップデート通知</a></div>
					<div class="box-row-content">WordPress、プラグイン、テーマのアップデートを通知します。</div>
				</div>
				<div class="box-row">
					<div class="flag val-<?php echo esc_attr( $this->datas['server_error_notification'] ); ?>"><?php echo esc_html( $this->datas['server_error_notification'] ); ?></div>
					<div class="box-row-title"><a href="?page=cloudsecurewp_server_error_notification">サーバーエラー通知</a></div>
					<div class="box-row-content">サーバーエラーが発生した場合、エラー履歴を記録し、メールで通知します。</div>
				</div>
			</div>
		</div>
		<div class="box">
			<div class="box-bottom pt-0">
				<div class="box-row pb-0">
					<div class="box-row-title not-label"><a href="?page=cloudsecurewp_login_log">ログイン履歴</a></div>
					<div class="box-row-content">管理画面にログインした履歴を表示します。</div>
				</div>
			</div>
		</div>
		<?php
	}
}
