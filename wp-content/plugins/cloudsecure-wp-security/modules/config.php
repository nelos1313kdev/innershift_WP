<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Config extends CloudSecureWP_Common {
	private const OPTION_KEY = 'cloudsecurewp_config';
	private $conf;

	function __construct( array $info ) {
		parent::__construct( $info );
		$this->load_option();
	}

	/**
	 * optionを取得
	 */
	public function load_option(): void {
		$conf_tmp   = get_option( self::OPTION_KEY );
		$this->conf = ( empty( $conf_tmp ) || $conf_tmp === false ) ? array() : $conf_tmp;
	}

	/**
	 * 指定の設定値を取得
	 *
	 * @param string $key
	 * @return
	 */
	public function get( string $key ) {
		return $this->conf[ $key ] ?? '';
	}

	/**
	 * 設定値の指定
	 *
	 * @param string $key
	 * @param $val
	 * @return void
	 */
	public function set( string $key, $val ): void {
		$this->conf[ $key ] = $val;
	}

	/**
	 * 設定値の保存
	 *
	 * @return void
	 */
	public function save(): void {
		update_option( self::OPTION_KEY, $this->conf );
	}

	/**
	 * 削除
	 */
	public function rm(): void {
		$this->conf = array();
	}
}
