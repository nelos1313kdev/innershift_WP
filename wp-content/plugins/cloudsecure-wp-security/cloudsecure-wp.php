<?php
/**
 * The plugin bootstrap file
 *
 * 管理画面とログインURLをサイバー攻撃から守る、安心の国産・日本語対応プラグインです。
 * かんたんな設定を行うだけで、不正アクセスや不正ログインからあなたのWordPressを保護し、セキュリティが向上します。
 * また、各機能の有効・無効（ON・OFF）や設定などをお好みにカスタマイズし、いつでも保護状態を管理できます。
 *
 * @link              https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security
 * @package           CloudSecure_WP_Security
 *
 * @wordpress-plugin
 * Plugin Name:   CloudSecure WP Security
 * Plugin URI:    https://wpplugin.cloudsecure.ne.jp/cloudsecure_wp_security
 * Description:   管理画面とログインURLをサイバー攻撃から守る、安心の国産・日本語対応プラグインです。かんたんな設定を行うだけで、不正アクセスや不正ログインからあなたのWordPressを保護し、セキュリティが向上します。また、各機能の有効・無効（ON・OFF）や設定などをお好みにカスタマイズし、いつでも保護状態を管理できます。
 * Version:       1.3.12
 * Requires PHP:  7.1
 * Author:        CloudSecure,Inc.
 * Author URI:    https://cloudsecure.co.jp
 * License:       GPLv2 or later
 * License URI:   http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:   cloudsecure_wp_security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cloudsecurewp_info_datas = array(
	'version'     => 'Version',
	'plugin_name' => 'Plugin Name',
	'text_domain' => 'Text Domain',
);

$cloudsecurewp_info                = get_file_data( __FILE__, $cloudsecurewp_info_datas );
$cloudsecurewp_info['plugin_path'] = plugin_dir_path( __FILE__ );
$cloudsecurewp_info['plugin_url']  = plugin_dir_url( __FILE__ );

require_once 'modules/cloudsecure-wp.php';
global $cloudsecurewp;

$cloudsecurewp = new CloudSecureWP( $cloudsecurewp_info );
$cloudsecurewp->run();

/**
 * プラグイン有効化時の処理
 */
function cloudsecurewp_activate() {
	global $cloudsecurewp;
	$cloudsecurewp->activate();
}
register_activation_hook( __FILE__, 'cloudsecurewp_activate' );

/**
 * プラグイン無効化時の処理
 */
function cloudsecurewp_deactivate() {
	global $cloudsecurewp;
	$cloudsecurewp->deactivate();
}
register_deactivation_hook( __FILE__, 'cloudsecurewp_deactivate' );
