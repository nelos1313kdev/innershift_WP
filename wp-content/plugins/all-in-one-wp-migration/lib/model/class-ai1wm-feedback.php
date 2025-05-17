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

class Ai1wm_Feedback {

	/**
	 * Submit customer feedback to servmask.com
	 *
	 * @param  string  $type      Feedback type
	 * @param  string  $email     User e-mail
	 * @param  string  $message   User message
	 * @param  integer $terms     User accept terms
	 * @param  string  $purchases Purchases IDs
	 *
	 * @return array
	 */
	public static function add( $type, $email, $message, $terms, $purchases ) {
		// Validate email
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) === false ) {
			throw new Ai1wm_Feedback_Exception( esc_html__( 'Please enter a valid email address.', 'all-in-one-wp-migration' ) );
		}

		// Validate type
		if ( empty( $type ) ) {
			throw new Ai1wm_Feedback_Exception( esc_html__( 'Please select a feedback type.', 'all-in-one-wp-migration' ) );
		}

		// Validate message
		if ( empty( $message ) ) {
			throw new Ai1wm_Feedback_Exception( esc_html__( 'Please describe your issue or feedback.', 'all-in-one-wp-migration' ) );
		}

		// Validate terms
		if ( empty( $terms ) ) {
			throw new Ai1wm_Feedback_Exception( esc_html__( 'Please check the consent box so we can use your email to respond to you.', 'all-in-one-wp-migration' ) );
		}

		$response = wp_remote_post(
			AI1WM_FEEDBACK_URL,
			array(
				'timeout' => 15,
				'body'    => array(
					'type'      => $type,
					'email'     => $email,
					'message'   => $message,
					'purchases' => $purchases,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			/* translators: Error message. */
			throw new Ai1wm_Feedback_Exception( esc_html( sprintf( __( 'An error occurred while submitting your request: %s', 'all-in-one-wp-migration' ), $response->get_error_message() ) ) );
		}

		return $response;
	}
}
