<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定ファイルアクセス防止で使用するルール設定用のクラス
 */
class CloudSecureWP_Disable_Access_System_File_Rules {
	// ルールの定義
	private const DISABLE_ACCESS_SYSTEM_FILE_RULES = array(
		array(
			'id'               => '1000301',
			'skip'             => 1,
			'skipafter'        => '',
			'chain'            => false,
			'variables'        => array( 'request_filename', 'args', 'args_names', 'request_headers', 'xml' ),
			'remove_variables' => array(),
			'attack'           => '',
			'regex_pattern'    => '\.www_acl|\.htpasswd|\.htaccess|boot\.ini|httpd\.conf|\/etc\/|\.htgroup|global\.asa|\.wwwacl',
			'transformations'  => array( 'htmlentitydecode', 'lowercase' ),
		),
		array(
			'id'               => '1000302',
			'skip'             => 0,
			'skipafter'        => '959005',
			'chain'            => false,
			'variables'        => array(),
			'remove_variables' => array(),
			'attack'           => '',
			'regex_pattern'    => '',
			'transformations'  => array(),
		),
		array(
			'id'               => '950005',
			'skip'             => 0,
			'skipafter'        => '',
			'chain'            => false,
			'variables'        => array( 'request_filename', 'args', 'args_names' ),
			'remove_variables' => array(),
			'attack'           => 1,
			'regex_pattern'    => '(?:\b(?:\.(?:ht(?:access|passwd|group)|www_?acl)|global\.asa|httpd\.conf|boot\.ini)\b|\/etc\/)',
			'transformations'  => array( 'htmlentitydecode', 'lowercase' ),
		),
		array(
			'id'               => '959005',
			'skip'             => 0,
			'skipafter'        => '',
			'chain'            => false,
			'variables'        => array( 'request_headers', 'xml' ),
			'remove_variables' => array(),
			'attack'           => 1,
			'regex_pattern'    => '(?:\b(?:\.(?:ht(?:access|passwd|group)|www_?acl)|global\.asa|httpd\.conf|boot\.ini)\b|\/etc\/)',
			'transformations'  => array( 'htmlentitydecode', 'lowercase' ),
		),
		array(
			'id'               => 'n101001',
			'skip'             => 0,
			'skipafter'        => '',
			'chain'            => false,
			'variables'        => array( 'request_filename' ),
			'remove_variables' => array(),
			'attack'           => 1,
			'regex_pattern'    => '(.env|.git)',
			'transformations'  => array( 'htmlentitydecode', 'lowercase' ),
		),
		array(
			'id'               => 'n102001',
			'skip'             => 0,
			'skipafter'        => '',
			'chain'            => false,
			'variables'        => array( 'request_filename' ),
			'remove_variables' => array(),
			'attack'           => 1,
			'regex_pattern'    => 'wp-config\.php',
			'transformations'  => array( 'htmlentitydecode', 'lowercase' ),
		),
	);

	private const LOCATIONMATCH_RULES = array();

	/**
	 * ルールの取得
	 *
	 * @return array
	 */
	public function get_waf_rules(): array {
		return self::DISABLE_ACCESS_SYSTEM_FILE_RULES;
	}

	/**
	 * LocationMatchルールの取得
	 *
	 * @return array
	 */
	public function get_locationmatch_rules(): array {
		return self::LOCATIONMATCH_RULES;
	}
}
