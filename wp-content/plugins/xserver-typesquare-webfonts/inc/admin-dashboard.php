<?php

if ( ! class_exists( 'typesquare_dashboard_widget' ) ) {

	/**
	 * Class typesquare_dashboard_widget
	 *
	 * @since 2.3.10
	 */
	class typesquare_dashboard_widget {

		/**
		 * Add the action to the constructor.
		 */
		function __construct() {
			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		}

		/**
		 * @since 2.3.10
		 */
		function add_dashboard_widget() {
			if ( current_user_can( 'install_plugins' ) && false !== $this->show_widget() ) {
				wp_add_dashboard_widget(
					'slug','TypeSquareニュース', array(
						$this,
						'display_rss_dashboard_widget',
					)
				);
			}

		}

		/**
		 * @since 2.3.10.2
		 */
		function show_widget() {

			$show = true;


			global $typesquarep_options;

			return $show;
		}

		/**
		 * @since 2.3.10
		 */
		function display_rss_dashboard_widget() {
			// check if the user has chosen not to display this widget through screen options.
			$current_screen = get_current_screen();
			$hidden_widgets = get_user_meta( get_current_user_id(), 'metaboxhidden_' . $current_screen->id );
			if ( $hidden_widgets && count( $hidden_widgets ) > 0 && is_array( $hidden_widgets[0] ) && in_array( 'slug', $hidden_widgets[0], true ) ) {
				return;
			}

			include_once( ABSPATH . WPINC . '/feed.php' );

			$rss = fetch_feed( 'https://typesquare.com/ja/news/newslist.rss' );
			if ( is_wp_error( $rss ) ) {
				echo 'エラー';

				return;
			}

			$rss_items = $rss->get_items( 0, 5 );

			$cached = array();
			foreach ( $rss_items as $item ) {
				$cached[] = array(
					// 以下はxmlファイルのitemタグ内の要素
					// <link>
					'url'	 => $item->get_permalink(),
					// <title>
					'title'   => $item->get_title(),
					// <pubDate>
					'date'	=> $item->get_date( 'M jS Y' ),
					// <description>
					'content' => substr( strip_tags( $item->get_content() ), 0, 216 ) . '...',
				);
			}
			$rss_items = $cached;

			?>

			<ul>
				<?php
				if ( false === $rss_items ) {
					echo '<li>No items</li>';

					return;
				}

				foreach ( $rss_items as $item ) {
					?>
					<li>
						<a target="_blank" href="<?php echo esc_url( $item['url'] ); ?>" style="font-size:14px;">
							<?php echo esc_html( $item['title'] ); ?>
						</a>
						<span class="typesquarep-rss-date" style="display:block; margin: 4px 0;"><?php echo esc_html(__($item['date'])); ?></span>
						<div class="typesquarep_news" style="color:#777;">
							<?php echo esc_html(__(strip_tags( $item['content'] ) . '...')); ?>
						</div>
					</li>
					<hr>
					<?php
				}

				?>
			</ul>

			<?php

		}
	}

	new typesquare_dashboard_widget();
}


