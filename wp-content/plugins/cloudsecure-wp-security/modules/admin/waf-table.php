<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CloudSecureWP_Waf_Table extends WP_List_Table {
	private $waf;
	private $per_page = 50;

	function __construct( CloudSecureWP_Waf $waf ) {
		parent::__construct();
		$this->waf = $waf;
	}


	public function get_columns() : array {
		$column_datas = $this->waf->get_cloumns();

		return array(
			'access_at' => $column_datas['access_at'],
			'attack'    => $column_datas['attack'],
			'url'       => $column_datas['url'],
			'matched'   => $column_datas['matched'],
			'ip'        => $column_datas['ip'],
			'id'        => $column_datas['id'],
		);
	}


	public function get_hidden_columns() : array {
		return array(
			'id',
		);
	}


	public function get_sortable_columns() {
		return array(
			'access_at' => array( 'access_at', false ),
			'attack'    => array( 'attack', false ),
			'url'       => array( 'url', false ),
			'matched'   => array( 'matched', false ),
			'ip'        => array( 'ip', false ),
			'id'        => array( 'id', false ),
		);
	}


	public function column_default( $item, $column ) {
		switch ( $column ) {
			case 'access_at':
			case 'attack':
			case 'ip':
				return esc_html( $item[ $column ] );

			case 'matched':
			case 'url':
				if ( mb_strlen( $item[ $column ], 'UTF-8' ) === 32767 ) {
					$item[ $column ] = $item[ $column ] . '...';
				}
				return esc_html( $item[ $column ] );

			default:
				return '';
		}
	}


	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby                           = sanitize_text_field( $_GET['orderby'] ?? 'access_at' );
		$order                             = sanitize_text_field( $_GET['order'] ?? 'desc' );
		$offset                            = ( $this->get_pagenum() - 1 ) * $this->per_page;
		list( $this->items, $total_items ) = $this->waf->get_block_history( $orderby, $order, $this->per_page, $offset );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
			)
		);
	}
}
