<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * プラグインuninstall時の処理
 */
function cloudsecurewp_uninstall() {
	global $wpdb;

	// cloudsecurewp_で始まるオプションを削除.
	$options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'cloudsecurewp_%'" );
	foreach ( $options as $option ) {
		delete_option( $option->option_name );
	}

	// cloudsecurewp_で始まるユーザーオプションを削除.
	$user_options = $wpdb->get_results( "SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE '" . $wpdb->get_blog_prefix() . "cloudsecurewp_%'" );
	foreach ( $user_options as $user_option ) {
		delete_user_option( (int) $user_option->user_id, $user_option->meta_key, true );
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cloudsecurewp_login" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cloudsecurewp_login_log" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cloudsecurewp_server_error" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cloudsecurewp_waf_log" );
}

cloudsecurewp_uninstall();
