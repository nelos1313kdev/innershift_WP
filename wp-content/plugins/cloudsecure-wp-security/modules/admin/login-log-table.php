<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CloudSecureWP_Login_Log_Table extends WP_List_Table {
	private $login_log;
	private $per_page   = 50;
	private $conditions = array();
	private $allowed_html;

	function __construct( CloudSecureWP_Login_Log $login_log ) {
		parent::__construct(
			array(
				'ajax' => false,
			)
		);
		$this->login_log = $login_log;
	}

	/**
	 * 条件設定
	 *
	 * @param $conditions
	 * @return void
	 */
	public function set_condition( array $conditions ): void {
		$this->conditions = $conditions;
	}

	/**
	 * 絞込み条件保存
	 *
	 * @return void
	 */
	public function save_conditions(): void {
		$this->login_log->save_conditions( $this->conditions );
	}

	protected function pagination( $which ) {
		if ( 'top' === $which ) {
			return;
		}
		parent::pagination( 'top' );
	}

	protected function get_table_classes() {
		$classes   = parent::get_table_classes();
		$classes[] = 'custom-table-class';
		return $classes;
	}

	/**
	 * 条件取得
	 *
	 * @return array
	 */
	public function get_conditions(): array {
		$this->conditions = $this->login_log->get_conditions();
		return $this->conditions;
	}

	function column_default( $item, $column ) {
		$login_status_datas = $this->login_log->get_login_status_datas();
		$methods            = $this->login_log->get_methods();

		switch ( $column ) {
			case 'status':
				return esc_html( $login_status_datas[ $item[ $column ] ] );
			case 'method':
				return esc_html( $methods[ $item[ $column ] ] );
			case 'id':
			case 'name':
			case 'ip':
			case 'login_at':
				return esc_html( $item[ $column ] );
			default:
				return '';
		}
	}

	function get_columns() {
		$column_datas = $this->login_log->get_cloumns();

		return array(
			'login_at' => $column_datas['login_at'],
			'status'   => $column_datas['status'],
			'ip'       => $column_datas['ip'],
			'method'   => $column_datas['method'],
			'name'     => $column_datas['name'],
			'id'       => $column_datas['id'],
		);
	}

	function get_hidden_columns() {
		return array(
			'id',
		);
	}

	function get_sortable_columns() {
		return array(
			'login_at' => array( 'login_at', false ),
			'status'   => array( 'status', false ),
			'ip'       => array( 'ip', false ),
			'method'   => array( 'method', false ),
			'name'     => array( 'name', false ),
			'id'       => array( 'id', false ),
		);
	}

	function usort_reorder( $a, $b ) {
		$cloumns = $this->login_log->get_cloumns();
		$orderby = 'login_at';

		if ( array_key_exists( sanitize_text_field( $_GET['orderby'] ?? '' ), $cloumns ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
		}

		$order = sanitize_text_field( $_GET['order'] ?? 'desc' );
		if ( 'desc' !== $order ) {
			$order = 'asc';
		}

		switch ( $orderby ) {
			case 'id':
			case 'status':
			case 'method':
				$result = ( (int) $a[ $orderby ] > (int) $b[ $orderby ] ? 1 : ( (int) $a[ $orderby ] < (int) $b[ $orderby ] ? -1 : 0 ) );
				break;
			case 'ip':
				$a_ip   = (int) sprintf( '%u', ip2long( $a[ $orderby ] ) );
				$b_ip   = (int) sprintf( '%u', ip2long( $b[ $orderby ] ) );
				$result = ( $a_ip > $b_ip ? 1 : ( $a_ip < $b_ip ? -1 : 0 ) );
				break;
			default:
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}

		return ( $order === 'asc' ) ? $result : -$result;
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$conditions            = $this->get_conditions();

		$login_log_rows       = $this->login_log->get_login_history( $conditions );
		$login_log_rows_count = count( $login_log_rows );

		$current_page = $this->get_pagenum();
		$max_page     = (int) ceil( $login_log_rows_count / $this->per_page );

		if ( $max_page < $current_page ) {
			$current_page = $max_page;
		}

		$view_rows = array();
		if ( 0 < $login_log_rows_count ) {
			usort( $login_log_rows, array( &$this, 'usort_reorder' ) );
			$view_rows = array_slice( $login_log_rows, ( ( $current_page - 1 ) * $this->per_page ), $this->per_page );
		}
		$this->items = $view_rows;

		$this->set_pagination_args(
			array(
				'total_items' => $login_log_rows_count,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $login_log_rows_count / $this->per_page ),
			)
		);
	}

	function extra_tablenav( $witch ) {
		if ( 'top' === $witch ) {
			$this->condition_view();
		}
	}


	/**
	 * option 作成
	 *
	 * @param array  $datas
	 * @param string $condition
	 * @return string
	 */
	public function make_option( array $datas, string $condition ): string {
		$options = '';
		foreach ( $datas as $key => $val ) {

			$selected = '';
			if ( (string) $condition === (string) $key ) {
				$selected = ' selected';
			}
			$options .= '<option value="' . $key . '"' . $selected . '>' . $val . '</option>';
		}
		return $options . "\n";
	}

	/**
	 * 絞込み条件
	 */
	public function condition_view(): void {
		$this->allowed_html = array(
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);

		$login_status_datas = $this->login_log->get_login_status_datas();
		$condition_status   = $this->conditions['condition_status'] ?? '';
		$status_options     = $this->make_option( $login_status_datas, $condition_status );

		$login_method_datas = $this->login_log->get_methods();
		$condition_method   = $this->conditions['condition_method'] ?? '';
		$method_options     = $this->make_option( $login_method_datas, $condition_method );

		$condition_ip_other_than_t   = '';
		$condition_name_other_than_t = '';

		$condition_ip = $this->conditions['condition_ip'] ?? '';
		if ( '' !== $condition_ip ) {
			$condition_ip_other_than   = $this->conditions['condition_ip_other_than'] ?? '';
			$condition_ip_other_than_t = $condition_ip_other_than === 't' ? 'checked' : '';
		}

		$condition_name = $this->conditions['condition_name'] ?? '';
		if ( '' !== $condition_name ) {
			$condition_name_other_than   = $this->conditions['condition_name_other_than'] ?? '';
			$condition_name_other_than_t = $condition_name_other_than === 't' ? 'checked' : '';
		}
		?>
		<div class="alignleft  bulkactions login-log">
			<div class="login-log-rows">
				<div class="login-log-row">
					<span>ログイン判定</span>
					<select name="condition_status">
						<option value="">--</option>
						<?php echo wp_kses( $status_options, $this->allowed_html ); ?>
					</select>
					<div class="login-log-row-right">
						<span>IPアドレス</span>
						<input type="text" class="mr-12" name="condition_ip" placeholder="例）192.0.2.1" value="<?php echo esc_attr( $condition_ip ); ?>" maxlength="39"><br>
						<input class="checkbox" id="condition_ip" type="checkbox" name="condition_ip_other_than" value="t" <?php echo esc_html( $condition_ip_other_than_t ); ?>><label for="condition_ip">を除く</label>
					</div>
				</div>
				<div class="login-log-row">
					<span>ログイン種別</span>
					<select name="condition_method">
						<option value="">--</option>
						<?php echo wp_kses( $method_options, $this->allowed_html ); ?>
					</select>
					<div class="login-log-row-right">
						<span>ユーザー名</span>
						<input type="text" class="mr-12" name="condition_name" placeholder="例）user_name" value="<?php echo esc_attr( $condition_name ); ?>" maxlength="60"><br>
						<input class="checkbox" id="condition_name" type="checkbox" name="condition_name_other_than" value="t" <?php echo esc_html( $condition_name_other_than_t ); ?>><label for="condition_name">を除く</label>
					</div>
				</div>
			</div>
			<div class="login-log-btns">
				<?php wp_nonce_field( $this->login_log->get_feature_key() . '_csrf' ); ?>
				<input type="submit" class="login-log-reset-btn" name="reset" value="クリア">
					<input type="submit" class="login-log-done-btn" name="done" value="絞り込み">
			</div>
		</div>

		<?php
	}
}
