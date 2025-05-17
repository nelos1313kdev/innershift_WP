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

if ( $should_reset_permalinks ) {
	echo wp_kses(
		sprintf(
			/* translators: Url */
			__(
				'» Permalinks are set to default. <a class="ai1wm-no-underline" href="https://help.servmask.com/knowledgebase/permalinks-are-set-to-default/" target="_blank">Why?</a><br />
				» <a class="ai1wm-no-underline" href="https://oxygenbuilder.com/documentation/other/importing-exporting/#resigning" target="_blank">Re-sign Oxygen Builder shortcodes</a>.<br />
				» <a class="ai1wm-no-underline" href="https://wordpress.org/support/view/plugin-reviews/all-in-one-wp-migration?rate=5#postform" target="_blank">Review your migration experience</a>.<br />
				» <a class="ai1wm-no-underline" href="%s" target="_blank">Protect your site with real-time threat protection</a>',
				'all-in-one-wp-migration'
			),
			'https://servmask.com/protect'
		),
		ai1wm_allowed_html_tags()
	);
} else {
	echo wp_kses(
		sprintf(
			/* translators: 1: Admin url, 2: Url */
			__(
				'» <a class="ai1wm-no-underline" href="%1$s" target="_blank">Save permalinks structure</a>.<br />
				» <a class="ai1wm-no-underline" href="https://oxygenbuilder.com/documentation/other/importing-exporting/#resigning" target="_blank">Re-sign Oxygen Builder shortcodes</a>.<br />
				» <a class="ai1wm-no-underline" href="https://wordpress.org/support/view/plugin-reviews/all-in-one-wp-migration?rate=5#postform" target="_blank">Review your migration experience</a>.<br />
				» <a class="ai1wm-no-underline" href="%2$s" target="_blank">Protect your site with real-time threat protection</a>',
				'all-in-one-wp-migration'
			),
			admin_url( 'options-permalink.php#submit' ),
			'https://servmask.com/protect'
		),
		ai1wm_allowed_html_tags()
	);
}
