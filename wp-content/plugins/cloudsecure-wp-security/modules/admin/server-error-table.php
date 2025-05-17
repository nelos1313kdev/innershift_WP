<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CloudSecureWP_Server_Error_Table extends WP_List_Table {

	private $server_error_notification;

	function __construct( CloudSecureWP_Server_Error_Notification $server_error_notification ) {
		parent::__construct();
		$this->server_error_notification = $server_error_notification;
	}

	public function prepare_items() {
		$per_page                          = 50;
		$orderby                           = sanitize_text_field( $_GET['orderby'] ?? 'created_at' );
		$order                             = sanitize_text_field( $_GET['order'] ?? 'desc' );
		$offset                            = ( $this->get_pagenum() - 1 ) * $per_page;
		list( $this->items, $total_items ) = $this->server_error_notification->get_server_errors( $orderby, $order, $per_page, $offset );
		$this->_column_headers             = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'created_at' => '日時',
			'type'       => 'タイプ',
			'message'    => 'メッセージ',
			'file'       => 'ファイル',
			'line'       => '行',
			'id'         => 'ID',
		);
	}

	public function get_hidden_columns() : array {
		return array(
			'id',
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'created_at' => array( 'created_at', false ),
			'type'       => array( 'type', false ),
			'message'    => array( 'message', false ),
			'file'       => array( 'file', false ),
			'line'       => array( 'line', false ),
			'id'         => array( 'id', false ),
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'message':
				if ( mb_strlen( $item[ $column_name ], 'UTF-8' ) === 65535 ) {
					$item[ $column_name ] = $item[ $column_name ] . '...';
				}
				return nl2br( esc_html( $item[ $column_name ] ) );

			case 'file':
				if ( mb_strlen( $item[ $column_name ], 'UTF-8' ) === 65535 ) {
					$item[ $column_name ] = $item[ $column_name ] . '...';
				}
				return esc_html( $item[ $column_name ] );

			default:
				return esc_html( $item[ $column_name ] );
		}
	}
}
