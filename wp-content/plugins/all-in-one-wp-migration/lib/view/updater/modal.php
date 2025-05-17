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

<div id="<?php echo esc_attr( 'ai1wm-modal-dialog-' . $modal ); ?>" class="ai1wm-modal-dialog">
	<div class="ai1wm-modal-container" role="dialog">
		<h2><?php esc_html_e( 'Enter your Purchase ID', 'all-in-one-wp-migration' ); ?></h2>
		<p><?php esc_html_e( 'To update your plugin/extension to the latest version, please fill your Purchase ID below.', 'all-in-one-wp-migration' ); ?></p>
		<p class="ai1wm-modal-error"></p>
		<p>
			<input type="text" class="ai1wm-purchase-id" placeholder="<?php esc_attr_e( 'Purchase ID', 'all-in-one-wp-migration' ); ?>" />
			<input type="hidden" class="ai1wm-update-link" value="<?php echo esc_url( $url ); ?>" />
		</p>
		<p>
			<?php esc_html_e( "Don't have a Purchase ID? You can find your Purchase ID", 'all-in-one-wp-migration' ); ?>
			<a href="https://servmask.com/lost-purchase" target="_blank" class="ai1wm-help-link"><?php esc_html_e( 'here', 'all-in-one-wp-migration' ); ?></a>
		</p>
		<p class="ai1wm-modal-buttons submitbox">
			<button type="button" class="ai1wm-purchase-add ai1wm-button-green">
				<?php esc_html_e( 'Save', 'all-in-one-wp-migration' ); ?>
			</button>
			<a href="#" class="submitdelete ai1wm-purchase-discard"><?php esc_html_e( 'Discard', 'all-in-one-wp-migration' ); ?></a>
		</p>
	</div>
</div>

<span id="<?php echo esc_attr( 'ai1wm-update-section-' . $modal ); ?>">
	<i class="ai1wm-icon-update"></i>
	<?php esc_html_e( 'There is an update available. To update, you must enter your', 'all-in-one-wp-migration' ); ?>
	<a class="ai1wm-modal-dialog-purchase-id" href="<?php echo esc_attr( '#ai1wm-modal-dialog-' . $modal ); ?>"><?php esc_html_e( 'Purchase ID', 'all-in-one-wp-migration' ); ?></a>.
</span>
