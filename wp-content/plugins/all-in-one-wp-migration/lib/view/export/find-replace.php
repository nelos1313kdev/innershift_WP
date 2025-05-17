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

<ul id="ai1wm-queries">
	<li class="ai1wm-query ai1wm-expandable">
		<p>
			<span>
				<strong><?php esc_html_e( 'Search for', 'all-in-one-wp-migration' ); ?></strong>
				<small class="ai1wm-query-find-text ai1wm-tooltip" title="Search the database for this text"><?php echo esc_html( __( '<text>', 'all-in-one-wp-migration' ) ); ?></small>
				<strong><?php esc_html_e( 'Replace with', 'all-in-one-wp-migration' ); ?></strong>
				<small class="ai1wm-query-replace-text ai1wm-tooltip" title="Replace the database with this text"><?php echo esc_html( __( '<another-text>', 'all-in-one-wp-migration' ) ); ?></small>
				<strong><?php esc_html_e( 'in the database', 'all-in-one-wp-migration' ); ?></strong>
			</span>
			<span class="ai1wm-query-arrow ai1wm-icon-chevron-right"></span>
		</p>
		<div>
			<input class="ai1wm-query-find-input" type="text" placeholder="<?php esc_attr_e( 'Search for', 'all-in-one-wp-migration' ); ?>" name="options[replace][old_value][]" />
			<input class="ai1wm-query-replace-input" type="text" placeholder="<?php esc_attr_e( 'Replace with', 'all-in-one-wp-migration' ); ?>" name="options[replace][new_value][]" />
		</div>
	</li>
</ul>

<button type="button" class="ai1wm-button-gray" id="ai1wm-add-new-replace-button">
	<i class="ai1wm-icon-plus2"></i>
	<?php esc_html_e( 'Add', 'all-in-one-wp-migration' ); ?>
</button>
