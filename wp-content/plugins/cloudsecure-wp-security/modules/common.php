<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

class CloudSecureWP_Common {
	public const MESSAGES = array(
		'error_multisite'       => 'WordPressマルチサイトには対応していません',
		'error_htaccess_update' => '.htaccessファイルの更新に失敗しました',
		'error_conflict_plugin' => '競合するプラグインが有効化されています',
	);

	public const CONFLICT_PLUGINS = array(
		array(
			'name' => 'SiteGuard WP Plugin',
			'path' => 'siteguard/siteguard.php',
		),
	);

	public const PAGE_404                 = 'cloudsecurewp_404';
	protected const LOGIN_STATUS_SUCCESS  = 1;
	protected const LOGIN_STATUS_FAILED   = 2;
	protected const LOGIN_STATUS_DISABLED = 3;
	protected const LOGIN_STATUS_DATAS    = array(
		self::LOGIN_STATUS_SUCCESS  => '成功',
		self::LOGIN_STATUS_FAILED   => '失敗',
		self::LOGIN_STATUS_DISABLED => '無効',
	);
	protected $info;

	function __construct( array $info ) {
		$this->info = $info;
	}

	/**
	 * エラーログ出力
	 *
	 * @param string $msg
	 * @param string $tag
	 * @return bool
	 */
	protected function error_log( string $msg, string $tag = '' ): bool {
		$file_path = $this->info['plugin_path'] . 'log/' . date_i18n( 'Ymd_' ) . 'error.log';

		if ( '' !== $tag ) {
			$tag = '[' . $tag . '] ';
		} else {
			$tag = ' ';
		}

		if ( false === file_put_contents( $file_path, '[' . date_i18n( 'His' ) . ']' . $tag . $msg . "\n", FILE_APPEND | LOCK_EX ) ) {
			return false;
		}
		return true;
	}

	/**
	 * WP cron 有効判定
	 *
	 * @return bool
	 */
	protected function cron_enabled(): bool {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return false;
		}
		return true;
	}

	/**
	 * WP mail 送信
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $body
	 * @return bool
	 */
	public function wp_send_mail( string $to, string $subject, string $body ): bool {
		$subject = '【' . get_option( 'blogname' ) . '】 ' . $subject;

		if ( @wp_mail( $to, $subject, $body ) ) {
			return true;
		}
		return false;
	}

	/**
	 * クライアントIP取得
	 *
	 * @return string
	 */
	public function get_client_ip(): string {
		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	}

	/**
	 * 競合プラグイン有効判定
	 *
	 * @return string
	 */
	protected function get_conflict_plugin_name(): string {
		$plugins = self::CONFLICT_PLUGINS;
		$ret     = '';

		foreach ( $plugins as $plugin_row ) {
			if ( is_plugin_active( $plugin_row['path'] ) ) {
				$ret = $plugin_row['name'];
				break;
			}
		}

		return $ret;
	}

	/**
	 * 管理者のユーザー情報全件取得
	 */
	public function get_admin_users(): array {
		$args = array(
			'role'   => 'administrator',
			'fields' => 'all',
		);

		return get_users( $args );
	}

	/**
	 * 配列からテキストに変換
	 *
	 * @param array $array
	 * @return string
	 */
	public function array2Text( array $array ): string {
		return implode( "\n", $array );
	}

	/**
	 * 動作環境チェック
	 *
	 * @return bool
	 */
	public function check_environment(): bool {
		if ( ! is_multisite() ) {
			if ( '' === $this->get_conflict_plugin_name() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 機能OFF 管理画面に通知表示
	 */
	public function prepare_admin_notices( $key_feature, $feature ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		?>
		<div class="notice notice-error is-dismissible">
			<p><strong>【CloudSecure WP Security】</strong></p>
			<p>.htaccessファイルに変更が加えられたため、一時的に<strong><?php esc_html( print( $feature ) ); ?>機能</strong>を無効にしました。</p>
			<p>再度有効にする場合は、<a href="<?php esc_html( print( admin_url( 'admin.php?page=cloudsecurewp_' . $key_feature ) ) ); ?>">設定画面</a>から機能を有効にしてください。</p>
		</div>
		<?php
	}


	/**
	 * USERAGENT 取得
	 *
	 * @return string
	 */
	public function get_http_user_agent(): string {
		return sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );
	}

	/**
	 * HTTP_REFERER 取得
	 *
	 * @return string
	 */
	public function get_http_referer(): string {
		return sanitize_url( $_SERVER['HTTP_REFERER'] ?? '' );
	}

	/**
	 * クエリ文字列部分を除いた REQUEST_URI 取得
	 *
	 * @return string
	 */
	public function get_request_uri(): string {
		$request_uri = '';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
				$request_uri = str_replace( '?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI'] );
			} else {
				$request_uri = $_SERVER['REQUEST_URI'];
			}

			if ( false === $request_uri ) {
				$request_uri = '';
			}
		}

		return $request_uri;
	}

	/**
	 * REQAUEST_HEADER 取得
	 *
	 * @return array
	 */
	public function get_http_request_headers(): array {
		$request_headers = array();

		if ( ! empty( $_SERVER ) ) {
			foreach ( $_SERVER as $key => $value ) {
				if ( substr( $key, 0, 5 ) === 'HTTP_' ) {
					$request_headers[ ucwords( str_replace( '_', '-', strtolower( substr( $key, 5 ) ) ), '-' ) ] = $value;
				}
			}

			if ( isset( $_SERVER['CONTENT_TYPE'] ) ) {
				$request_headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
		}

		return $request_headers;
	}

	/**
	 * 年月日取得
	 *
	 * @param string $delimiter
	 * @return string
	 */
	public function get_date( string $delimiter = '/' ): string {
		return date_i18n( "Y{$delimiter}m{$delimiter}d" );
	}

	/**
	 * 時分秒取得
	 *
	 * @return string
	 */
	public function get_time(): string {
		return date_i18n( 'H:i:s' );
	}

	/**
	 * ログインステータス情報取得
	 */
	public function get_login_status_datas(): array {
		return self::LOGIN_STATUS_DATAS;
	}

	/**
	 * 404
	 */
	public function page404(): void {
		$page = get_404_template();

		if ( '' !== $page ) {
			global $wp_query;

			status_header( 404 );
			nocache_headers();
			$wp_query->set_404();
			include $page;

		} else {
			wp_safe_redirect( home_url( self::PAGE_404 ) );
		}

		exit;
	}

	/**
	 * 403
	 */
	public function page403(): void {
		status_header( 403, 'Forbidden' );
		nocache_headers();
		exit;
	}
}
