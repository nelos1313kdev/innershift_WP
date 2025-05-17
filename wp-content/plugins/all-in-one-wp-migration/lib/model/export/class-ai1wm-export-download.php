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

class Ai1wm_Export_Download {

	public static function execute( $params ) {

		// Set progress
		Ai1wm_Status::info( __( 'Renaming export file...', 'all-in-one-wp-migration' ) );

		// Open the archive file for writing
		$archive = new Ai1wm_Compressor( ai1wm_archive_path( $params ) );

		// Append EOF block
		$archive->close( true );

		// Rename archive file
		if ( rename( ai1wm_archive_path( $params ), ai1wm_backup_path( $params ) ) ) {

			$blog_id = null;

			// Get subsite Blog ID
			if ( isset( $params['options']['sites'] ) && ( $sites = $params['options']['sites'] ) ) {
				if ( count( $sites ) === 1 ) {
					$blog_id = array_shift( $sites );
				}
			}

			// Set archive details
			$file = ai1wm_archive_name( $params );
			$link = ai1wm_backup_url( $params );
			$size = ai1wm_backup_size( $params );
			$name = ai1wm_site_name( $blog_id );

			// Set progress
			if ( ai1wm_direct_download_supported() ) {
				Ai1wm_Status::download(
					sprintf(
						/* translators: 1: Link to archive, 2: Archive title, 3: File name, 4: Archive title, 5: File size. */
						__(
							'<a href="%1$s" class="ai1wm-button-green ai1wm-emphasize ai1wm-button-download" title="%2$s" download="%3$s">
							<span>Download %2$s</span>
							<em>Size: %4$s</em>
							</a>',
							'all-in-one-wp-migration'
						),
						$link,
						$name,
						$file,
						$size
					)
				);
			} else {
				Ai1wm_Status::download(
					sprintf(
						/* translators: 1: Archive title, 2: File name, 3: Archive title, 4: File size. */
						__(
							'<a href="#" class="ai1wm-button-green ai1wm-emphasize ai1wm-direct-download" title="%1$s" download="%2$s">
							<span>Download %3$s</span>
							<em>Size: %4$s</em>
							</a>',
							'all-in-one-wp-migration'
						),
						$name,
						$file,
						$name,
						$size
					)
				);
			}
		}

		do_action( 'ai1wm_status_export_done', $params );

		if ( isset( $params['ai1wm_manual_backup'] ) ) {
			do_action( 'ai1wm_status_backup_created', $params );
		}

		return $params;
	}
}
