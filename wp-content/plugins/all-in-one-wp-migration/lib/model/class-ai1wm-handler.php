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

class Ai1wm_Handler {

	/**
	 * Error handler
	 *
	 * @param  integer $errno   Error level
	 * @param  string  $errstr  Error message
	 * @param  string  $errfile Error file
	 * @param  integer $errline Error line
	 * @return void
	 */
	public static function error( $errno, $errstr, $errfile, $errline ) {
		global $ai1wm_params;
		if ( ! empty( $ai1wm_params['storage'] ) ) {
			Ai1wm_Log::error( $ai1wm_params['storage'], array( 'Number' => $errno, 'Message' => $errstr, 'File' => $errfile, 'Line' => $errline ) );
		}
	}

	/**
	 * Shutdown handler
	 *
	 * @return void
	 */
	public static function shutdown() {
		global $ai1wm_params;
		if ( ! empty( $ai1wm_params['storage'] ) ) {
			if ( ( $error = error_get_last() ) ) {
				Ai1wm_Log::error( $ai1wm_params['storage'], $error );
			}
		}
	}
}
