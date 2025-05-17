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

<div class="ai1wm-reset-container">
	<div class="ai1wm-reset-content">
		<h1>
			<img src="<?php echo esc_url( wp_make_link_relative( AI1WM_URL ) . '/lib/view/assets/img/reset/star.png?v=' . AI1WM_VERSION ); ?>" alt="<?php esc_attr_e( 'Star', 'all-in-one-wp-migration' ); ?>" />
			<?php esc_html_e( 'Experience Full Functionality with Premium!', 'all-in-one-wp-migration' ); ?>
		</h1>
		<p><?php echo wp_kses( __( 'Please note, the features displayed below are part of an image showcasing the potential of Reset Hub in its Premium version. To activate and enjoy these advanced features, <a href="https://servmask.com/products/unlimited-extension" title="Upgrade to Premium" target="_blank">upgrade to Premium now</a>! Elevate your website management experience with these exclusive functionalities and priority support.', 'all-in-one-wp-migration' ), ai1wm_allowed_html_tags() ); ?></p>

		<a href="https://servmask.com/products/unlimited-extension" target="_blank" title="<?php esc_attr_e( 'Upgrade to Premium ', 'all-in-one-wp-migration' ); ?>"><img src="<?php echo esc_url( wp_make_link_relative( AI1WM_URL ) . '/lib/view/assets/img/reset/screen.jpg?v=' . AI1WM_VERSION ); ?>" alt="<?php esc_attr_e( 'Reset Hub Demo', 'all-in-one-wp-migration' ); ?>" /></a>
	</div>
</div>
