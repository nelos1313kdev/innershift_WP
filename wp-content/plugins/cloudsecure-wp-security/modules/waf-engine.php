<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CloudSecureWP_Waf_Engine extends CloudSecureWP_Common {
	protected const VARIABLE_ARGS                  = 'args';
	protected const VARIABLE_ARGS_NAMES            = 'args_names';
	protected const VARIABLE_REQUEST_COOKIES       = 'request_cookies';
	protected const VARIABLE_REQUEST_COOKIES_NAMES = 'request_cookies_names';
	protected const VARIABLE_REQUEST_HEADERS       = 'request_headers';
	protected const VARIABLE_REQUEST_FILENAME      = 'request_filename';
	protected const VARIABLE_XML                   = 'xml';
	private $parsed_xml;



	function __construct( array $info ) {
		parent::__construct( $info );

	}


	/**
	 * XML パース時のコールバック関数
	 *
	 * @return void
	 */
	public function char( $parser, $data ): void {
		$this->parsed_xml .= $data;
}


	/**
	 * XML パース
	 *
	 * @return string
	 */
	public function xml_parser(): string {
		$request_body     = file_get_contents( 'php://input' );
		$this->parsed_xml = '';

		if ( ! empty( $request_body ) ) {
			$parser = xml_parser_create();

			xml_set_object( $parser, $this );
			xml_set_character_data_handler( $parser, 'char' );

			if ( ! xml_parse( $parser, $request_body ) ) {
				$this->parsed_xml .= 'xml_parse_failed';
			};

			xml_parser_free( $parser );
		}

		return $this->parsed_xml;
	}


	/**
	 * ARGS、ARGS_NAMES 用 クエリ文字列、POSTデータ取得
	 *
	 * @return array
	 */
	public function get_request_args_data(): array {
		$args = array();

		if ( ! empty( $_GET ) ) {
			$args = $_GET;
		}

		if ( ! empty( $_POST ) ) {
			$args = array_merge( $args, $_POST );

		} else {
			$post_data = file_get_contents( 'php://input' );

			if ( ! empty( $post_data ) ) {
				$json_decoded_post_data = json_decode( $post_data, true );

				// nullでなければjsonとなる
				if ( isset( $json_decoded_post_data ) ) {
					$args = array_merge( $args, $json_decoded_post_data );
				}
			}
		}

		return $args;
	}


	/**
	 * Cookie情報取得
	 *
	 * @return array
	 */
	public function get_request_cookies(): array {
		if ( ! empty( $_COOKIE ) ) {
			return $_COOKIE;
		} else {
			return array();
		}
	}


	/**
	 * リクエスト情報取得
	 * 取得・パースできなかったものに関しては空白で返す
	 *
	 * @return array
	 */
	public function get_request_items(): array {
		$request_items = array(
			'access_at'                     => current_time( 'mysql' ),
			'ip'                            => $this->get_client_ip(),
			self::VARIABLE_XML              => $this->xml_parser(),
			self::VARIABLE_REQUEST_FILENAME => $this->get_request_uri(),
			self::VARIABLE_REQUEST_HEADERS  => $this->get_http_request_headers(),
			self::VARIABLE_ARGS             => $this->get_request_args_data(),
			self::VARIABLE_REQUEST_COOKIES  => $this->get_request_cookies(),
		);

		return $request_items;
	}


	/**
	 * ルールの設定を参考に1つのリクエスト情報に対して複数の変換を行う処理
	 *
	 * @param array  $transformations
	 * @param string $request_item
	 * @return string
	 */
	public function transform( $transformations, $request_item ): string {
		$converted_request_item = $request_item;

		foreach ( $transformations as $transformation ) {
			switch ( $transformation ) {
				case 'htmlentitydecode':
					$converted_request_item = html_entity_decode( $converted_request_item, ENT_QUOTES, 'utf-8' );
					break;
				case 'lowercase':
					$converted_request_item = strtolower( $converted_request_item );
					break;
				case 'replacecomments':
					$converted_request_item = preg_replace( '/(\/\*.*?\*\/|\/\*.*(?!\*\/).*)/s', ' ', $converted_request_item );
					break;
				case 'compresswhitespace':
					$converted_request_item = preg_replace( '/(\s|\xa0)/s', ' ', $converted_request_item );
					$converted_request_item = preg_replace( '/\s{2,}/s', ' ', $converted_request_item );
					break;
				default:
					$converted_request_item = '変換に失敗しました';
					break 2;
			}
		}

		return $converted_request_item;
	}


	/**
	 * LocationMatch設定によるルールのスキップ用関数
	 *
	 * @param array  $locationmatch_rules
	 * @param string $request_uri
	 * @return array
	 */
	public function locationmatch_remove_rules( $locationmatch_rules, $request_uri ): array {
		$locationmatch_removed_rule_ids = array();

		foreach ( $locationmatch_rules as $locationmatch_rule ) {
			if ( preg_match( '/' . $locationmatch_rule['path'] . '/', $request_uri ) ) {
				$locationmatch_removed_rule_ids = array_merge( $locationmatch_removed_rule_ids, $locationmatch_rule['remove_rule_ids'] );
			}
		}

		$locationmatch_removed_rule_ids = array_unique( $locationmatch_removed_rule_ids );

		return $locationmatch_removed_rule_ids;
	}


	/**
	 * skip表記の存在確認
	 *
	 * @param int $skip
	 * @return int
	 */
	public function check_skip( $skip ): int {
		if ( $skip !== 0 ) {
			return $skip;
		} else {
			return 0;
		}
	}


	/**
	 * skipafter表記の存在確認
	 *
	 * @param string $skipafter
	 * @return string
	 */
	public function check_skipafter( $skipafter ): string {
		if ( ! empty( $skipafter ) ) {
			return $skipafter;
		} else {
			return '';
		}
	}


	/**
	 * skipが有効か判定
	 *
	 * @param int $skip
	 * @return bool
	 */
	public function is_skip_enabled( $skip ): bool {
		if ( 0 < $skip ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * skipafterが有効か判定
	 *
	 * @param string $skipafter
	 * @return bool
	 */
	public function is_skipafter_enabled( $skipafter ): bool {
		if ( ! empty( $skipafter ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * ルールが終了したか判定
	 *
	 * @param string $chain
	 * @param string $skip
	 * @param string $skipafter
	 * @return bool
	 */
	public function is_rule_finished( $chain, $skip, $skipafter ): bool {
		if ( ! empty( $chain ) || $this->is_skip_enabled( $skip ) || $this->is_skipafter_enabled( $skipafter ) ) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * ルール内に除外の表記があるか確認
	 *
	 * @param array  $remove_variables
	 * @param string $variable
	 * @param string $request_item
	 * @return bool
	 */
	public function is_remove_variables( $remove_variables, $variable, $request_item ): bool {
		if ( ! empty( $remove_variables ) ) {
			foreach ( $remove_variables as $remove_variable => $remove_values ) {
				if ( $remove_variable === $variable ) {
					foreach ( $remove_values as $remove_value ) {

						// REQUEST_HEADERSの場合は大文字小文字関係なく判定する
						if ( $variable === self::VARIABLE_REQUEST_HEADERS ) {
							if ( preg_match( '/' . $remove_value . '/i', $request_item ) ) {
								return true;
							}
						}

						if ( preg_match( '/^\/.+\/$/', $remove_value ) ) {
							// 除外の値が部分一致(/で囲まれているもの)の場合
							if ( preg_match( $remove_value . 's', $request_item ) ) {
								return true;
							}
						} else {
							// 除外の値が完全一致の場合
							if ( $remove_value === $request_item ) {
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * マッチした結果を配列に格納
	 *
	 * @param array  $rule
	 * @param array  $request_items
	 * @param string $variable
	 * @param string $match_string
	 * @return array
	 */
	public function get_match_results( $rule, $request_items, $variable, $match_string ): array {

		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			$complete_url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		} else {
			$complete_url = '';
		}

		$match_results = array(
			'id'        => $rule['id'],
			'attack'    => $rule['attack'],
			'variable'  => $variable,
			'matched'   => $match_string,
			'ip'        => $request_items['ip'],
			'access_at' => $request_items['access_at'],
			'url'       => $complete_url,
		);

		return $match_results;
	}


	/**
	 * chain_itemsの取得
	 *
	 * @param array $rule
	 * @return array
	 */
	public function get_chain_items( $rule ): array {
		$chain_items = array(
			'id'        => $rule['id'],
			'attack'    => $rule['attack'],
			'skip'      => $rule['skip'],
			'skipafter' => $rule['skipafter'],
		);

		return $chain_items;
	}


	/**
	 * マッチしたルールの設定、結果を取得
	 *
	 * @param array  $waf_rule
	 * @param array  $request_items
	 * @param string $variable
	 * @param array  $chain_items
	 * @param array  $match_string
	 * @return array
	 */
	public function get_rule_settings_and_results( $waf_rule, $request_items, $variable, $chain_items, $match_string ): array {
		$results = array(
			'is_matched'    => true,
			'chain_items'   => array(),
			'skip'          => 0,
			'skipafter'     => '',
			'match_results' => array(),
		);

		// 前ルールからchainの設定を引き継いでいるか確認
		if ( ! empty( $chain_items ) ) {
			if ( $waf_rule['attack'] === '' && $this->is_rule_finished( $waf_rule['chain'], $waf_rule['skip'], $waf_rule['skipafter'] ) ) {
				// ルールの終了判定がtrueではあるが、攻撃種別が設定されていない場合はマッチ用のルールではないのでマッチしていないと判定
				$results['is_matched'] = false;

			} elseif ( $this->is_rule_finished( $waf_rule['chain'], $chain_items['skip'], $chain_items['skipafter'] ) ) {
				$results['match_results'] = $this->get_match_results( $chain_items, $request_items, $variable, $match_string );

			} elseif ( $waf_rule['chain'] ) {
				// 現在のルールにchainの設定がある場合
				$results['chain_items'] = $chain_items;

			} else {
				$results['skip']      = $this->check_skip( $chain_items['skip'] );
				$results['skipafter'] = $this->check_skipafter( $chain_items['skipafter'] );
			}
		} else {
			if ( $waf_rule['attack'] === '' && $this->is_rule_finished( $waf_rule['chain'], $waf_rule['skip'], $waf_rule['skipafter'] ) ) {
				// ルールの終了判定がtrueではあるが、攻撃種別が設定されていない場合はマッチ用のルールではないのでマッチしていないと判定
				$results['is_matched'] = false;

			} elseif ( $this->is_rule_finished( $waf_rule['chain'], $waf_rule['skip'], $waf_rule['skipafter'] ) ) {
				$results['match_results'] = $this->get_match_results( $waf_rule, $request_items, $variable, $match_string );

			} elseif ( $waf_rule['chain'] ) {
				// 現在のルールにchainの設定がある場合
				$results['chain_items'] = $this->get_chain_items( $waf_rule );

			} else {
				$results['skip']      = $this->check_skip( $waf_rule['skip'] );
				$results['skipafter'] = $this->check_skipafter( $waf_rule['skipafter'] );
			}
		}
		return $results;
	}

	/**
	 * ネストした配列をパース
	 *
	 * @param string $name
	 * @param array  $array
	 */
	public function parse_array( $name, $array ) {
		$parsed_array = array();

		foreach ( $array as $key => $val ) {
			if ( is_array( $val ) ) {
				$tmp_name         = $name . '[' . $key . ']';
				$tmp_parsed_array = $this->parse_array( $tmp_name, $val );
				$parsed_array     = array_merge( $parsed_array, $tmp_parsed_array );

			} else {
				$parsed_array_key                  = $name . '[' . $key . ']';
				$parsed_array[ $parsed_array_key ] = $val ?? '';
			}
		}
		return $parsed_array;
	}


	/**
	 * リクエスト情報配列を使用するルール判定
	 *
	 * @param array  $waf_rule
	 * @param array  $request_items
	 * @param string $variable
	 * @param array  $chain_items
	 * @return array
	 */
	public function check_request_item_array( $waf_rule, $request_items, $variable, $chain_items ) {
		$results = array(
			'is_matched'    => false,
			'chain_items'   => array(),
			'skip'          => 0,
			'skipafter'     => '',
			'match_results' => array(),
		);

		$get_request_item_variable = preg_replace( '/_names/', '', $variable );

		if ( ! isset( $get_request_item_variable ) ) {
			return $results;
		}

		foreach ( $request_items[ $get_request_item_variable ] as $key => $val ) {

			if ( ! isset( $val ) ) {
				$val = '';
			}

			switch ( $variable ) {
				case self::VARIABLE_ARGS:
				case self::VARIABLE_REQUEST_COOKIES:
				case self::VARIABLE_REQUEST_HEADERS:
					if ( is_array( $val ) ) {
						$parsed_vals = $this->parse_array( $key, $val );

						foreach ( $parsed_vals as $key => $val ) {
							$checked_request_item = $this->check_request_item_value( $waf_rule, $key, $val, $variable );

							if ( $checked_request_item['is_matched'] ) {
								$results = $this->get_rule_settings_and_results( $waf_rule, $request_items, $variable, $chain_items, $checked_request_item['match_string'] );
								break;
							}
						}
					} else {
						$checked_request_item = $this->check_request_item_value( $waf_rule, $key, $val, $variable );
					}

					break;

				case self::VARIABLE_ARGS_NAMES:
				case self::VARIABLE_REQUEST_COOKIES_NAMES:
					$checked_request_item = $this->check_request_item_key( $waf_rule, $key, $variable );
					break;
			}

			if ( $checked_request_item['is_matched'] ) {
				$results = $this->get_rule_settings_and_results( $waf_rule, $request_items, $variable, $chain_items, $checked_request_item['match_string'] );

				break;
			}
		}
		return $results;
	}



	/**
	 * リクエスト情報配列のうちvalueの値を使用するルール判定
	 *
	 * @param array  $waf_rule
	 * @param string $request_items_key
	 * @param string $request_items_value
	 * @param string $variable
	 * @return array
	 */
	public function check_request_item_value( $waf_rule, $request_items_key, $request_items_value, $variable ): array {
		$results['is_matched'] = false;
		$matches               = array();

		// リクエスト情報配列のkeyとvalueの変換
		$tmp_key = $this->transform( $waf_rule['transformations'], $request_items_key );
		$tmp_val = $this->transform( $waf_rule['transformations'], $request_items_value );

		if ( preg_match( '/' . $waf_rule['regex_pattern'] . '/s', $tmp_val, $matches ) ) {
			if ( ! empty( $matches ) ) {
				if ( false === $this->is_remove_variables( $waf_rule['remove_variables'], $variable, $tmp_key ) ) {
					// 除外の設定が無ければマッチしたと判定
					$results['is_matched']   = true;
					$results['match_string'] = $matches[0];
				}
			}
		}

		return $results;
	}


	/**
	 * リクエスト情報配列のうちkeyの値を使用するルール判定
	 *
	 * @param array  $waf_rule
	 * @param string $request_items_key
	 * @param string $variable
	 * @return array
	 */
	public function check_request_item_key( $waf_rule, $request_items_key, $variable ): array {
		$results['is_matched'] = false;
		$matches               = array();

		// リクエスト情報配列のkeyの変換
		$tmp_key = $this->transform( $waf_rule['transformations'], $request_items_key );

		if ( preg_match( '/' . $waf_rule['regex_pattern'] . '/s', $tmp_key, $matches ) ) {
			if ( ! empty( $matches ) ) {
				if ( false === $this->is_remove_variables( $waf_rule['remove_variables'], $variable, $tmp_key ) ) {
					// 除外の設定が無ければマッチしたと判定
					$results['is_matched']   = true;
					$results['match_string'] = $matches[0];
				}
			}
		}

		return $results;
	}


	/**
	 * リクエスト情報配列のうち文字列の値を使用するルール判定
	 *
	 * @param array  $waf_rule
	 * @param array  $request_items
	 * @param string $variable
	 * @param array  $chain_items
	 * @return array
	 */
	public function check_request_item_strings( $waf_rule, $request_items, $variable, $chain_items ): array {
		$results = array(
			'is_matched'    => false,
			'chain_items'   => array(),
			'skip'          => 0,
			'skipafter'     => '',
			'match_results' => array(),
		);

		if ( empty( $request_items[ $variable ] ) ) {
			return $results;
		}

		if ( $variable === self::VARIABLE_XML ) {
			if ( $request_items[ self::VARIABLE_XML ] === 'xml_parse_failed' ) {
				return $results;
			} else {
				$request_items[ self::VARIABLE_XML ] = str_replace( 'xml_parse_failed', '', $request_items[ self::VARIABLE_XML ] );
			}
		}

		// リクエスト情報の変換
		$tmp_string = $this->transform( $waf_rule['transformations'], $request_items[ $variable ] );

		// REQUEST_FILENAMEに関しては、urlデコード済の値も判定し、マッチしたら結果を出力
		if ( $variable === self::VARIABLE_REQUEST_FILENAME ) {
			// リクエスト情報のデコード&変換
			$request_items_urldecoded = urldecode( $request_items[ $variable ] );
			$tmp_urldecoded           = $this->transform( $waf_rule['transformations'], $request_items_urldecoded );

			if ( preg_match( '/' . $waf_rule['regex_pattern'] . '/s', $tmp_urldecoded, $matches ) ) {
				if ( ! empty( $matches ) ) {
					$results = $this->get_rule_settings_and_results( $waf_rule, $request_items, $variable, $chain_items, $matches[0] );
					return $results;
				}
			}
		}

		if ( false === preg_match( '/' . $waf_rule['regex_pattern'] . '/s', $tmp_string, $matches ) ) {
			// preg_matchが失敗したときは終了
			return $results;
		}

		if ( empty( $matches ) ) {
			// 正規表現パターンにマッチしなければ終了
			return $results;
		}

		$results = $this->get_rule_settings_and_results( $waf_rule, $request_items, $variable, $chain_items, $matches[0] );

	return $results;
	}


	/**
	 * WordPress管理画面での特定の処理に対し、特定のルールを除外する
	 *
	 * @param string $rule_id
	 * @param array  $request_items
	 * @param array  $remove_rules
	 * @return bool
	 */
	public function is_remove_rule( $rule_id, $request_items, $remove_rules, $acf_post_types ): bool {
		$is_rule_removed = false;

		if ( isset( $remove_rules['woocommerce'] ) ) {
			if ( in_array( $rule_id, $remove_rules['woocommerce'], true ) ) {
				$sbjs_cookie_keys = preg_grep( '/^sbjs_.+/', array_keys( $request_items['request_cookies'] ) );

				// sourcebusterの除外（woocommerce）
				if ( ! empty( $sbjs_cookie_keys ) ) {
					foreach ( $sbjs_cookie_keys as $key ) {
						if ( preg_match( '/(\;|\||\`)\W*?\b(?:(?:c(?:h(?:grp|mod|own|sh)|md|pp)|p(?:asswd|ython|erl|ing|s)|n(?:asm|map|c)|f(?:inger|tp)|(?:kil|mai)l|(?:xte)?rm|ls(?:of)?|telnet|uname|echo|id)\b|g(?:\+\+|cc\b))/i', $request_items['request_cookies'][ $key ] ) === 1 ) {
							$is_rule_removed = true;
							break;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['ajax_customize'] ) ) {
			// カスタマイズ操作、オートセーブ時の除外
			if ( preg_match( '/wp-admin\/customize\.php|customize_changeset_uuid/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['ajax_customize'], true ) ) {
					if ( isset( $request_items['args']['customize_autosaved'] ) || isset( $request_items['args']['wp_customize'] ) ) {
						if ( $request_items['args']['customize_autosaved'] === 'on' || $request_items['args']['wp_customize'] === 'on' ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['rest_api'] ) ) {
			// 投稿・編集の操作は特定のルールを除外する(rest_api)
			if ( preg_match( '/templates|blocks|template-parts|navigation|global-styles|pages|posts|batch/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}

			// 投稿・編集の操作は特定のルールを除外する(post.php)
			} elseif ( preg_match( '/wp-admin\/post\.php/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'editpost' ) {
							$is_rule_removed = true;
						}
					}
				}

			// nishikiルール除外
			} elseif ( preg_match( '/wp\/v2\/nishiki_pro_(patterns|content)/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}

			// xeriteルール除外
			} elseif ( preg_match( '/wp\/v2\/xw_block_patterns/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}

			// Lightningルール除外
			} elseif ( preg_match( '/wp\/v2\/(cta|vk-block-patterns)/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}

			// SWELLルール除外
			} elseif ( preg_match( '/wp\/v2\/(lp|blog_parts)/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}

			// Advanced Custom Fields除外
			// カスタム投稿タイプキーは小文字、アンダースコア、ダッシュのみを許容するが、念のためarray_mapで正規表現用にエスケープする
			} elseif ( preg_match( '/wp\/v2\/(' . implode( '|', array_map( 'preg_quote', $acf_post_types ) ) . ')/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['rest_api'], true ) ) {
					if ( preg_match( '/_locale\=user/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}
			}
		}

		if ( isset( $remove_rules['coccon'] ) ) {
			// cocconルール除外
			if ( preg_match( '/wp-admin\/admin\.php\?page\=theme-(settings|func-text|ranking|affiliate-tag)/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['coccon'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'new' || $request_items['args']['action'] === 'edit' ) {
							$is_rule_removed = true;
						}
					}

					if ( isset( $request_items['args']['comment_information_message'] ) ) {
						$is_rule_removed = true;
					}
				}
			}
		}

		if ( isset( $remove_rules['emanon'] ) ) {
			// emanonルール除外
			if ( preg_match( '/wp-admin\/admin.php\?page\=emanon_setting_page/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['emanon'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'delete_transients_emanon_setting' ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['vkexunit'] ) ) {
			// vkExUnitルール除外(メイン設定)
			if ( preg_match( '/wp-admin\/admin.php\?page\=vkExUnit_main_setting/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['vkexunit'], true ) ) {
					if ( preg_match( '/page\=vkExUnit_main_setting/', $_SERVER['QUERY_STRING'] ?? '' ) === 1 ) {
						$is_rule_removed = true;
					}
				}
			}

			// vkExUnitルール除外(cssカスタマイズ)
			if ( preg_match( '/wp-admin\/admin.php\?page\=vkExUnit_css_customize/', $_SERVER['REQUEST_URI'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['vkexunit'], true ) ) {
					if ( isset( $request_items['args']['_wp_http_referer'] ) ) {
						if ( strpos( $request_items['args']['_wp_http_referer'], '/wp-admin/admin.php?page=vkExUnit_css_customize' ) !== false ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['nishiki'] ) ) {
			// nishikiルール除外
			if ( preg_match( '/wp-admin\/admin.php\?page\=nishiki-pro-general\.php/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['nishiki'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'update' ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['swell'] ) ) {
			// swellルール除外
			if ( preg_match( '/wp-admin\/admin.php\?page\=swell_settings_editor/', $_SERVER['HTTP_REFERER'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['swell'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'update' ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['comment'] ) ) {
			// コメント編集時の除外
			if ( preg_match( '/wp-admin\/comment\.php/', $request_items['request_filename'] ?? '' ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['comment'], true ) ) {
					if ( isset( $request_items['args']['action'] ) ) {
						if ( $request_items['args']['action'] === 'editedcomment' ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}

		if ( isset( $remove_rules['ajax_editor'] ) ) {
			// テーマ・プラグインエディターの操作時の除外
			if ( preg_match( '/wp-admin\/admin-ajax\.php/', $request_items['request_filename'] ) === 1 ) {
				if ( in_array( $rule_id, $remove_rules['ajax_editor'], true ) ) {
					if ( isset( $request_items['args']['_wp_http_referer'] ) ) {
						if ( preg_match( '/theme-editor(\.php)?|plugin-editor(\.php)?/', $request_items['args']['_wp_http_referer'] ) === 1 ) {
							$is_rule_removed = true;
						}
					}

					// オートセーブ時
					if ( isset( $request_items['args']['screen_id'] ) ) {
						if ( preg_match( '/theme-editor(\.php)?|plugin-editor(\.php)?/', $request_items['args']['screen_id'] ) === 1 ) {
							$is_rule_removed = true;
						}
					}
				}
			}
		}
		return $is_rule_removed;
	}

	/**
	 * Advanced Custom Fieldsプラグイン除外対応
	 * 有効なカスタム投稿タイプキーを取得する
	 *
	 * @return array
	 */
	public function get_acf_post_types(): array {
		global $wpdb;
		$active_plugins = get_option( 'active_plugins' );
		$acf_post_types = array();

		if ( is_array( $active_plugins ) && preg_match( '/advanced-custom-fields/', implode( ',', $active_plugins ) ) ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_content
					FROM {$wpdb->posts}
					WHERE post_type = %s
					AND post_status = %s",
					'acf-post-type',
					'publish'
				)
			);

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$post_content = unserialize( $result->post_content, [ 'allowed_classes' => false ] );

					if ( ! is_array( $post_content ) ) {
						$acf_post_types[] = $post_content['post_type'];
					}
				}
			}
		}

		return $acf_post_types;
	}


	/**
	 * waf_engine
	 *
	 * @param array $waf_rules
	 * @param array $locationmatch_rules
	 * @param int   $available_rules
	 * @param array $remove_rules
	 * @return array
	 */
	public function waf_engine( $waf_rules, $locationmatch_rules, $available_rules, $remove_rules ): array {
		$request_items = $this->get_request_items();

		$locationmatch_removed_rule_ids = $this->locationmatch_remove_rules( $locationmatch_rules, $_SERVER['REQUEST_URI'] ?? '' );
		$skip                           = 0;
		$skipafter                      = '';
		$chain_items                    = array();

		// Advanced Custom Fieldsプラグイン除外対応で追加
		$acf_post_types = $this->get_acf_post_types();

		foreach ( $waf_rules as $waf_rule ) {
			// 前回マッチしたルールからskipの設定を引き継いでいる場合はスキップ
			if ( $this->is_skip_enabled( $skip ) ) {
				$skip--;
				continue;
			}

			// 前回マッチしたルールからskipafterの設定を引き継いでいる場合は現在のルールIDと比較し、一致するまでスキップ
			// ルールIDの比較結果が同じの場合は、$skipafterを初期化して次のルールから判定を行う
			if ( $this->is_skipafter_enabled( $skipafter ) ) {
				if ( $skipafter !== $waf_rule['id'] ) {
					continue;
				} else {
					$skipafter = '';
					continue;
				}
			}

			// LocationMatchによるルールの除外があるか確認。ある場合は現在のルールIDと比較し、一致する場合はスキップ
			if ( ! empty( $locationmatch_removed_rule_ids ) ) {
				if ( in_array( $waf_rule['id'], $locationmatch_removed_rule_ids, true ) ) {
					continue;
				}
			}

			// ルールにvariables設定がない場合、skip,akipafterの設定を確認して次のルール判定へ
			if ( empty( $waf_rule['variables'] ) ) {
				$skip      = $this->check_skip( $waf_rule['skip'] );
				$skipafter = $this->check_skipafter( $waf_rule['skipafter'] );
				continue;
			}

			// 特定の操作の場合、特定のルールを除外する
			$is_rule_removed = $this->is_remove_rule( $waf_rule['id'], $request_items, $remove_rules, $acf_post_types );

			if ( $is_rule_removed ) {
				continue;
			}

			foreach ( $waf_rule['variables'] as $variable ) {
				switch ( $variable ) {
					case self::VARIABLE_ARGS:
						$results = $this->check_request_item_array( $waf_rule, $request_items, self::VARIABLE_ARGS, $chain_items );
						break;

					case self::VARIABLE_ARGS_NAMES:
						$results = $this->check_request_item_array( $waf_rule, $request_items, self::VARIABLE_ARGS_NAMES, $chain_items );
						break;

					case self::VARIABLE_REQUEST_COOKIES:
						$results = $this->check_request_item_array( $waf_rule, $request_items, self::VARIABLE_REQUEST_COOKIES, $chain_items );
						break;

					case self::VARIABLE_REQUEST_COOKIES_NAMES:
						$results = $this->check_request_item_array( $waf_rule, $request_items, self::VARIABLE_REQUEST_COOKIES_NAMES, $chain_items );
						break;

					case self::VARIABLE_REQUEST_HEADERS:
						$results = $this->check_request_item_array( $waf_rule, $request_items, self::VARIABLE_REQUEST_HEADERS, $chain_items );
						break;

					case self::VARIABLE_REQUEST_FILENAME:
						$results = $this->check_request_item_strings( $waf_rule, $request_items, self::VARIABLE_REQUEST_FILENAME, $chain_items );
						break;

					case self::VARIABLE_XML:
						$results = $this->check_request_item_strings( $waf_rule, $request_items, self::VARIABLE_XML, $chain_items );
						break;
				}

				$is_matched = $results['is_matched'];

				if ( $is_matched ) {
					$skip          = $results['skip'];
					$skipafter     = $results['skipafter'];
					$chain_items   = $results['chain_items'];
					$match_results = $results['match_results'];

					// マッチしたが、chain,skip,skipafterの設定がある場合は次のルール判定へ
					if ( ! empty( $chain_items ) || 0 < $skip || ! empty( $skipafter ) ) {
						break;
					}

					// マッチしたが、除外設定されているルールの場合は値を保持して次のルール判定へ
					if ( ( $waf_rule['attack'] & $available_rules ) === 0 ) {
						$tmp_match_results = $match_results;
						break;
					}

					// マッチした結果がある場合は終了処理へ（ログ記述、通知、画面表示）
					if ( ! empty( $match_results ) ) {
						$match_results['is_deny']      = true;
						$match_results['is_write_log'] = true;

						return $match_results;
					}
				}

				$chain_items = array();
			}
		}

		if ( ! empty( $tmp_match_results ) ) {
			$match_results                 = $tmp_match_results;
			$match_results['is_deny']      = false;
			$match_results['is_write_log'] = true;

		} else {
			$match_results['is_deny']      = false;
			$match_results['is_write_log'] = false;
		}

		return $match_results;
	}
}
