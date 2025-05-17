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

class Ai1wm_Compatibility {

	public static function get( $params ) {
		$extensions = Ai1wm_Extensions::get();

		foreach ( $extensions as $extension_name => $extension_data ) {
			if ( ! isset( $params[ $extension_data['short'] ] ) ) {
				unset( $extensions[ $extension_name ] );
			}
		}

		// If no extension is used, update everything that is available
		if ( empty( $extensions ) ) {
			$extensions = Ai1wm_Extensions::get();
		}

		$messages = array();
		foreach ( $extensions as $extension_name => $extension_data ) {
			if ( ! Ai1wm_Compatibility::check( $extension_data ) ) {
				if ( defined( 'WP_CLI' ) ) {
					/* translators: Extension name. */
					$messages[] = sprintf( __( '%s is out of date. Please update this extension before using it.', 'all-in-one-wp-migration' ), $extension_data['title'] );
				} else {
					/* translators: 1: Extension name, 2: Plugins update page. */
					$messages[] = sprintf( __( '<strong>%1$s</strong> is out of date. You must <a href="%2$s">update this extension</a> before using it.<br />', 'all-in-one-wp-migration' ), $extension_data['title'], network_admin_url( 'plugins.php' ) );
				}
			}
		}

		return $messages;
	}

	public static function check( $extension ) {
		if ( $extension['version'] !== 'develop' ) {
			if ( version_compare( $extension['version'], $extension['requires'], '<' ) ) {
				return false;
			}
		}

		return true;
	}
}
