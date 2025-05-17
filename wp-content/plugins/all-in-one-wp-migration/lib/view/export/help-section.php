<?php
/**
 * Copyright (C) 2014-2025 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Attribution: This code is part of the All-in-One WP Migration plugin, developed by
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}
?>

<p>
	<?php esc_html_e( 'Use this screen to export your database, media, themes, and plugins into one .wpress file. Later, import that file to another WordPress site.', 'all-in-one-wp-migration' ); ?>
</p>

<p>
	<strong><?php esc_html_e( 'Hints', 'all-in-one-wp-migration' ); ?></strong>
</p>

<ul>
	<li>
		<i class="ai1wm-icon-arrow-right"></i>
		<?php esc_html_e( 'In the advanced settings section you can configure more precisely the way of exporting.', 'all-in-one-wp-migration' ); ?>
	</li>
	<li>
		<i class="ai1wm-icon-arrow-right"></i>
		<?php esc_html_e( 'Press "Export" button and the site archive file will pop up in your browser.', 'all-in-one-wp-migration' ); ?>
	</li>
	<li>
		<i class="ai1wm-icon-arrow-right"></i>
		<?php esc_html_e( 'Once the file is successfully downloaded on your computer, you can import it to any of your WordPress sites.', 'all-in-one-wp-migration' ); ?>
	</li>
</ul>
