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

class Ai1wm_Database_Utility {

	protected static $db_client = null;

	public static function set_client( $db_client ) {
		self::$db_client = $db_client;
	}

	/**
	 * Get MySQLClient to be used for DB manipulation
	 *
	 * @return Ai1wm_Database
	 */
	public static function create_client() {
		global $wpdb;

		if ( self::$db_client ) {
			return self::$db_client;
		}

		if ( $wpdb instanceof WP_SQLite_DB || $wpdb instanceof WP_SQLite_DB\wpsqlitedb ) {
			return new Ai1wm_Database_Sqlite( $wpdb );
		}

		if ( PHP_MAJOR_VERSION >= 7 ) {
			return new Ai1wm_Database_Mysqli( $wpdb );
		}

		if ( empty( $wpdb->use_mysqli ) ) {
			return new Ai1wm_Database_Mysql( $wpdb );
		}

		return new Ai1wm_Database_Mysqli( $wpdb );
	}

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * This function is case-sensitive.
	 *
	 * @param  string $data    Data to replace
	 * @param  array  $search  List of string we're looking to replace
	 * @param  array  $replace What we want it to be replaced with
	 * @return string          The original string with all elements replaced as needed
	 */
	public static function replace_values( $data, $search = array(), $replace = array() ) {
		return strtr( $data, array_combine( $search, $replace ) );
	}

	/**
	 * Replace serialized occurrences of the search string with the replacement string.
	 * This function is case-sensitive.
	 *
	 * @param  string $data    Data to replace
	 * @param  array  $search  List of string we're looking to replace
	 * @param  array  $replace What we want it to be replaced with
	 * @return string          The original array with all elements replaced as needed
	 */
	public static function replace_serialized_values( $data, $search = array(), $replace = array() ) {
		$pos = 0;

		$result = self::parse_serialized_values( $data, $pos, $search, $replace );
		if ( $pos !== strlen( $data ) ) {
			// Failed to parse entire data
			return strtr( $data, array_combine( $search, $replace ) );
		}

		return $result;
	}

	/**
	 * Parse serialized string and replace needed substitutions.
	 * This function is case-sensitive.
	 *
	 * @param  string  $data    Serialized data
	 * @param  integer $pos     Character position
	 * @param  array   $search  List of string we're looking to replace
	 * @param  array   $replace What we want it to be replaced with
	 * @return string           The original string with all elements replaced as needed
	 */
	public static function parse_serialized_values( $data, &$pos, $search = array(), $replace = array() ) {
		$length = strlen( $data );
		if ( $pos >= $length ) {
			return '';
		}

		$type = $data[ $pos ];
		$pos++;

		switch ( $type ) {
			case 's':
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$len_end = strpos( $data, ':', $pos );
				if ( $len_end === false ) {
					return '';
				}

				$str_length = (int) substr( $data, $pos, $len_end - $pos );

				$pos = $len_end + 1;
				if ( $data[ $pos ] !== '"' ) {
					return '';
				}

				$pos++;
				$str = substr( $data, $pos, $str_length );

				$pos += $str_length;
				if ( $data[ $pos ] !== '"' ) {
					return '';
				}

				$pos++;
				if ( $data[ $pos ] !== ';' ) {
					return '';
				}

				$pos++;

				// If the string is a single letter, skip any parsing or replacement.
				if ( $str_length === 1 ) {
					return 's:' . $str_length . ':"' . $str . '";';
				}

				// Attempt to parse the string as serialized data
				$pos_inner  = 0;
				$parsed_str = self::parse_serialized_values( $str, $pos_inner, $search, $replace );
				if ( $pos_inner === strlen( $str ) ) {
					// The string is serialized data, use the parsed string
					$new_str = $parsed_str;
				} else {
					// Regular string, perform replacement
					$new_str = strtr( $str, array_combine( $search, $replace ) );
				}

				return 's:' . strlen( $new_str ) . ':"' . $new_str . '";';

			case 'i':
			case 'd':
			case 'b':
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$end = strpos( $data, ';', $pos );
				if ( $end === false ) {
					return '';
				}

				$value = substr( $data, $pos, $end - $pos );
				$pos   = $end + 1;

				return $type . ':' . $value . ';';

			case 'N':
				if ( $data[ $pos ] !== ';' ) {
					return '';
				}

				$pos++;

				return 'N;';

			case 'a':
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$len_end = strpos( $data, ':', $pos );
				if ( $len_end === false ) {
					return '';
				}

				$array_length = (int) substr( $data, $pos, $len_end - $pos );

				$pos = $len_end + 1;
				if ( $data[ $pos ] !== '{' ) {
					return '';
				}

				$pos++;
				$result = 'a:' . $array_length . ':{';
				for ( $i = 0; $i < $array_length * 2; $i++ ) {
					$element = self::parse_serialized_values( $data, $pos, $search, $replace );
					if ( $element === '' ) {
						return '';
					}

					$result .= $element;
				}

				if ( $data[ $pos ] !== '}' ) {
					return '';
				}

				$pos++;
				$result .= '}';

				return $result;

			case 'O':
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$class_len_end = strpos( $data, ':', $pos );
				if ( $class_len_end === false ) {
					return '';
				}

				$class_length = (int) substr( $data, $pos, $class_len_end - $pos );

				$pos = $class_len_end + 1;
				if ( $data[ $pos ] !== '"' ) {
					return '';
				}

				$pos++;
				$class_name = substr( $data, $pos, $class_length );

				$pos += $class_length;
				if ( $data[ $pos ] !== '"' ) {
					return '';
				}

				$pos++;
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$prop_len_end = strpos( $data, ':', $pos );
				if ( $prop_len_end === false ) {
					return '';
				}

				$prop_count = (int) substr( $data, $pos, $prop_len_end - $pos );

				$pos = $prop_len_end + 1;
				if ( $data[ $pos ] !== '{' ) {
					return '';
				}

				$pos++;
				$result = 'O:' . strlen( $class_name ) . ':"' . $class_name . '":' . $prop_count . ':{';
				for ( $i = 0; $i < $prop_count * 2; $i++ ) {
					$element = self::parse_serialized_values( $data, $pos, $search, $replace );
					if ( $element === '' ) {
						return '';
					}

					$result .= $element;
				}

				if ( $data[ $pos ] !== '}' ) {
					return '';
				}

				$pos++;
				$result .= '}';

				return $result;

			case 'R':
			case 'r':
				if ( $data[ $pos ] !== ':' ) {
					return '';
				}

				$pos++;
				$end = strpos( $data, ';', $pos );
				if ( $end === false ) {
					return '';
				}

				$ref = substr( $data, $pos, $end - $pos );
				$pos = $end + 1;

				return $type . ':' . $ref . ';';

			default:
				return '';
		}
	}

	/**
	 * Escape MySQL special characters
	 *
	 * @param  string $data Data to escape
	 * @return string
	 */
	public static function escape_mysql( $data ) {
		return strtr(
			$data,
			array_combine(
				array( "\x00", "\n", "\r", '\\', "'", '"', "\x1a" ),
				array( '\\0', '\\n', '\\r', '\\\\', "\\'", '\\"', '\\Z' )
			)
		);
	}

	/**
	 * Unescape MySQL special characters
	 *
	 * @param  string $data Data to unescape
	 * @return string
	 */
	public static function unescape_mysql( $data ) {
		return strtr(
			$data,
			array_combine(
				array( '\\0', '\\n', '\\r', '\\\\', "\\'", '\\"', '\\Z' ),
				array( "\x00", "\n", "\r", '\\', "'", '"', "\x1a" )
			)
		);
	}

	/**
	 * Encode base64 characters
	 *
	 * @param  string $data Data to encode
	 * @return string
	 */
	public static function base64_encode( $data ) {
		return base64_encode( $data );
	}

	/**
	 * Encode base64 characters
	 *
	 * @param  string $data Data to decode
	 * @return string
	 */
	public static function base64_decode( $data ) {
		return base64_decode( $data );
	}

	/**
	 * Validate base64 data
	 *
	 * @param  string  $data Data to validate
	 * @return boolean
	 */
	public static function base64_validate( $data ) {
		return base64_encode( base64_decode( $data ) ) === $data;
	}
}
