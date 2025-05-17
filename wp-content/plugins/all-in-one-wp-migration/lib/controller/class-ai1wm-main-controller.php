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

class Ai1wm_Main_Controller {

	/**
	 * Main Application Controller
	 *
	 * @return Ai1wm_Main_Controller
	 */
	public function __construct() {
		register_activation_hook( AI1WM_PLUGIN_BASENAME, array( $this, 'activation_hook' ) );

		// Activate hooks
		$this->activate_actions();
		$this->activate_filters();
	}

	/**
	 * Activation hook callback
	 *
	 * @return void
	 */
	public function activation_hook() {
		if ( extension_loaded( 'litespeed' ) ) {
			$this->create_litespeed_htaccess( AI1WM_WORDPRESS_HTACCESS );
		}

		$this->setup_backups_folder();
		$this->setup_storage_folder();
		$this->setup_secret_key();
	}

	/**
	 * Initializes language domain for the plugin
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( AI1WM_PLUGIN_NAME, false, false );
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	private function activate_actions() {
		// Init
		add_action( 'admin_init', array( $this, 'init' ) );

		// Router
		add_action( 'admin_init', array( $this, 'router' ) );

		// Enable WP importing
		add_action( 'admin_init', array( $this, 'wp_importing' ), 5 );

		// Setup backups folder
		add_action( 'admin_init', array( $this, 'setup_backups_folder' ) );

		// Setup storage folder
		add_action( 'admin_init', array( $this, 'setup_storage_folder' ) );

		// Setup secret key
		add_action( 'admin_init', array( $this, 'setup_secret_key' ) );

		// Check user role capability
		add_action( 'admin_init', array( $this, 'check_user_role_capability' ) );

		// Schedule crons
		add_action( 'admin_init', array( $this, 'schedule_crons' ) );

		// Load text domain
		add_action( 'admin_init', array( $this, 'load_textdomain' ) );

		// Admin header
		add_action( 'admin_head', array( $this, 'admin_head' ) );

		// All-in-One WP Migration
		add_action( 'plugins_loaded', array( $this, 'ai1wm_loaded' ), 10 );

		// Export and import commands
		add_action( 'plugins_loaded', array( $this, 'ai1wm_commands' ), 10 );

		// Export and import buttons
		add_action( 'plugins_loaded', array( $this, 'ai1wm_buttons' ), 10 );

		// WP CLI commands
		add_action( 'plugins_loaded', array( $this, 'wp_cli' ), 10 );

		// Register scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ), 5 );

		// Enqueue export scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 5 );

		// Enqueue import scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 5 );

		// Enqueue backups scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backups_scripts_and_styles' ), 5 );

		// Enqueue schedules scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_schedules_scripts_and_styles' ), 5 );

		// Enqueue reset scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_reset_scripts_and_styles' ), 5 );

		// Enqueue updater scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_updater_scripts_and_styles' ), 5 );

		// Handle export/import exceptions (errors)
		add_action( 'ai1wm_status_export_error', array( $this, 'handle_error_cleanup' ), 5, 2 );
		add_action( 'ai1wm_status_import_error', array( $this, 'handle_error_cleanup' ), 5, 2 );
	}

	/**
	 * Register listeners for filters
	 *
	 * @return void
	 */
	private function activate_filters() {
		// Add links to plugin list page
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		// Add custom schedules
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ), 9999 );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function ai1wm_commands() {
		// Add export commands
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Init::execute', 5 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Compatibility::execute', 10 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Archive::execute', 30 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Config::execute', 50 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Config_File::execute', 60 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Enumerate_Content::execute', 100 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Enumerate_Media::execute', 110 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Enumerate_Plugins::execute', 120 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Enumerate_Themes::execute', 130 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Enumerate_Tables::execute', 140 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Content::execute', 150 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Media::execute', 160 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Plugins::execute', 170 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Themes::execute', 180 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Database::execute', 200 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Database_File::execute', 220 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Download::execute', 250 );
		add_filter( 'ai1wm_export', 'Ai1wm_Export_Clean::execute', 300 );

		// Add import commands
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Upload::execute', 5 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Compatibility::execute', 10 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Validate::execute', 50 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Check_Encryption::execute', 75 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Check_Decryption_Password::execute', 90 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Confirm::execute', 100 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Blogs::execute', 150 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Permalinks::execute', 170 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Enumerate::execute', 200 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Content::execute', 250 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Mu_Plugins::execute', 270 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Database::execute', 300 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Users::execute', 310 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Options::execute', 330 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Done::execute', 350 );
		add_filter( 'ai1wm_import', 'Ai1wm_Import_Clean::execute', 400 );
	}

	/**
	 * Export and import buttons
	 *
	 * @return void
	 */
	public function ai1wm_buttons() {
		add_filter( 'ai1wm_export_buttons', 'Ai1wm_Export_Controller::buttons' );
		add_filter( 'ai1wm_import_buttons', 'Ai1wm_Import_Controller::buttons' );
		add_filter( 'ai1wm_pro', 'Ai1wm_Import_Controller::pro', 10 );
	}

	/**
	 * All-in-One WP Migration loaded
	 *
	 * @return void
	 */
	public function ai1wm_loaded() {
		if ( ! defined( 'AI1WMME_PLUGIN_NAME' ) ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'multisite_notice' ) );
			} else {
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			}
		} else {
			if ( is_multisite() ) {
				add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			} else {
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			}
		}

		// Add in plugin update message
		foreach ( Ai1wm_Extensions::get() as $slug => $extension ) {
			add_action( "in_plugin_update_message-{$extension['basename']}", 'Ai1wm_Updater_Controller::in_plugin_update_message', 10, 2 );
		}

		// Add automatic plugins update
		add_action( 'wp_maybe_auto_update', 'Ai1wm_Updater_Controller::check_for_updates' );

		// Add HTTP export headers
		add_filter( 'ai1wm_http_export_headers', 'ai1wm_auth_headers' );

		// Add HTTP import headers
		add_filter( 'ai1wm_http_import_headers', 'ai1wm_auth_headers' );

		// Add HTTP reset headers
		add_filter( 'ai1wm_http_reset_headers', 'ai1wm_auth_headers' );

		// Add chunk size limit
		add_filter( 'ai1wm_max_chunk_size', 'Ai1wm_Import_Controller::max_chunk_size' );

		// Add plugins API
		add_filter( 'plugins_api', 'Ai1wm_Updater_Controller::plugins_api', 20, 3 );

		// Add plugins updates
		add_filter( 'pre_set_site_transient_update_plugins', 'Ai1wm_Updater_Controller::pre_update_plugins' );

		// Add plugins metadata
		add_filter( 'site_transient_update_plugins', 'Ai1wm_Updater_Controller::update_plugins' );

		// Add "Check for updates" link to plugin list page
		add_filter( 'plugin_row_meta', 'Ai1wm_Updater_Controller::plugin_row_meta', 10, 2 );

		// Add storage folder daily cleanup cron
		add_action( 'ai1wm_storage_cleanup', 'Ai1wm_Export_Controller::cleanup' );
	}

	/**
	 * WP CLI commands
	 *
	 * @return void
	 */
	public function wp_cli() {
		if ( defined( 'WP_CLI' ) && count( Ai1wm_Extensions::get() ) === 0 ) {
			WP_CLI::add_command( 'ai1wm', 'Ai1wm_WP_CLI_Command', array( 'shortdesc' => __( 'All-in-One WP Migration Command', 'all-in-one-wp-migration' ) ) );
		}
	}

	/**
	 * Create backups folder with index.php, index.html, .htaccess and web.config files
	 *
	 * @return void
	 */
	public function setup_backups_folder() {
		$this->create_backups_folder( AI1WM_BACKUPS_PATH );
		$this->create_backups_htaccess( AI1WM_BACKUPS_HTACCESS );
		$this->create_backups_webconfig( AI1WM_BACKUPS_WEBCONFIG );
		$this->create_backups_index_php( AI1WM_BACKUPS_INDEX_PHP );
		$this->create_backups_index_html( AI1WM_BACKUPS_INDEX_HTML );
		$this->create_backups_robots_txt( AI1WM_BACKUPS_ROBOTS_TXT );
	}

	/**
	 * Create storage folder with index.php and index.html files
	 *
	 * @return void
	 */
	public function setup_storage_folder() {
		$this->create_storage_folder( AI1WM_STORAGE_PATH );
		$this->create_storage_index_php( AI1WM_STORAGE_INDEX_PHP );
		$this->create_storage_index_html( AI1WM_STORAGE_INDEX_HTML );
	}

	/**
	 * Create secret key if they don't exist yet
	 *
	 * @return void
	 */
	public function setup_secret_key() {
		if ( ! get_option( AI1WM_SECRET_KEY ) ) {
			update_option( AI1WM_SECRET_KEY, ai1wm_generate_random_string( 12 ) );
		}
	}

	/**
	 * Check user role capability
	 *
	 * @return void
	 */
	public function check_user_role_capability() {
		if ( ( $user = wp_get_current_user() ) && in_array( 'administrator', $user->roles ) ) {
			if ( ! $user->has_cap( 'export' ) || ! $user->has_cap( 'import' ) ) {
				if ( is_multisite() ) {
					return add_action( 'network_admin_notices', array( $this, 'missing_role_capability_notice' ) );
				} else {
					return add_action( 'admin_notices', array( $this, 'missing_role_capability_notice' ) );
				}
			}
		}
	}

	/**
	 * Schedule cron tasks for plugin operation, if not done yet
	 *
	 * @return void
	 */
	public function schedule_crons() {
		if ( ! Ai1wm_Cron::exists( 'ai1wm_storage_cleanup' ) ) {
			Ai1wm_Cron::add( 'ai1wm_storage_cleanup', 'daily', time() );
		}

		Ai1wm_Cron::clear( 'ai1wm_cleanup_cron' );
	}

	/**
	 * Create storage folder
	 *
	 * @param  string Path to folder
	 * @return void
	 */
	public function create_storage_folder( $path ) {
		if ( ! Ai1wm_Directory::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'storage_path_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'storage_path_notice' ) );
			}
		}
	}

	/**
	 * Create backups folder
	 *
	 * @param  string Path to folder
	 * @return void
	 */
	public function create_backups_folder( $path ) {
		if ( ! Ai1wm_Directory::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_path_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_path_notice' ) );
			}
		}
	}

	/**
	 * Create storage index.php file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_storage_index_php( $path ) {
		if ( ! Ai1wm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'storage_index_php_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'storage_index_php_notice' ) );
			}
		}
	}

	/**
	 * Create storage index.html file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_storage_index_html( $path ) {
		if ( ! Ai1wm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'storage_index_html_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'storage_index_html_notice' ) );
			}
		}
	}

	/**
	 * Create backups .htaccess file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_htaccess( $path ) {
		if ( ! Ai1wm_File_Htaccess::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_htaccess_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_htaccess_notice' ) );
			}
		}
	}

	/**
	 * Create backups web.config file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_webconfig( $path ) {
		if ( ! Ai1wm_File_Webconfig::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_webconfig_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_webconfig_notice' ) );
			}
		}
	}

	/**
	 * Create backups index.php file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_index_php( $path ) {
		if ( ! Ai1wm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_index_php_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_index_php_notice' ) );
			}
		}
	}

	/**
	 * Create backups index.html file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_index_html( $path ) {
		if ( ! Ai1wm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_index_html_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_index_html_notice' ) );
			}
		}
	}

	/**
	 * Create backups robots.txt file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_robots_txt( $path ) {
		if ( ! Ai1wm_File_Robots::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_robots_txt_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_robots_txt_notice' ) );
			}
		}
	}

	/**
	 * If the "noabort" environment variable has been set,
	 * the script will continue to run even though the connection has been broken
	 *
	 * @return void
	 */
	public function create_litespeed_htaccess( $path ) {
		if ( ! Ai1wm_File_Htaccess::litespeed( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'wordpress_htaccess_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'wordpress_htaccess_notice' ) );
			}
		}
	}

	/**
	 * Display multisite notice
	 *
	 * @return void
	 */
	public function multisite_notice() {
		Ai1wm_Template::render( 'main/multisite-notice' );
	}

	/**
	 * Display notice for storage directory
	 *
	 * @return void
	 */
	public function storage_path_notice() {
		Ai1wm_Template::render( 'main/storage-path-notice' );
	}

	/**
	 * Display notice for index.php file in storage directory
	 *
	 * @return void
	 */
	public function storage_index_php_notice() {
		Ai1wm_Template::render( 'main/storage-index-php-notice' );
	}

	/**
	 * Display notice for index.html file in storage directory
	 *
	 * @return void
	 */
	public function storage_index_html_notice() {
		Ai1wm_Template::render( 'main/storage-index-html-notice' );
	}

	/**
	 * Display notice for backups directory
	 *
	 * @return void
	 */
	public function backups_path_notice() {
		Ai1wm_Template::render( 'main/backups-path-notice' );
	}

	/**
	 * Display notice for .htaccess file in backups directory
	 *
	 * @return void
	 */
	public function backups_htaccess_notice() {
		Ai1wm_Template::render( 'main/backups-htaccess-notice' );
	}

	/**
	 * Display notice for web.config file in backups directory
	 *
	 * @return void
	 */
	public function backups_webconfig_notice() {
		Ai1wm_Template::render( 'main/backups-webconfig-notice' );
	}

	/**
	 * Display notice for index.php file in backups directory
	 *
	 * @return void
	 */
	public function backups_index_php_notice() {
		Ai1wm_Template::render( 'main/backups-index-php-notice' );
	}

	/**
	 * Display notice for index.html file in backups directory
	 *
	 * @return void
	 */
	public function backups_index_html_notice() {
		Ai1wm_Template::render( 'main/backups-index-html-notice' );
	}

	/**
	 * Display notice for robots.txt file in backups directory
	 *
	 * @return void
	 */
	public function backups_robots_txt_notice() {
		Ai1wm_Template::render( 'main/backups-robots-txt-notice' );
	}

	/**
	 * Display notice for .htaccess file in WordPress directory
	 *
	 * @return void
	 */
	public function wordpress_htaccess_notice() {
		Ai1wm_Template::render( 'main/wordpress-htaccess-notice' );
	}

	/**
	 * Display notice for missing role capability
	 *
	 * @return void
	 */
	public function missing_role_capability_notice() {
		Ai1wm_Template::render( 'main/missing-role-capability-notice' );
	}

	/**
	 * Add links to plugin list page
	 *
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file === AI1WM_PLUGIN_BASENAME ) {
			$links[] = Ai1wm_Template::get_content( 'main/contact-support' );
			$links[] = Ai1wm_Template::get_content( 'main/translate' );
		}

		return $links;
	}

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Top-level WP Migration menu
		add_menu_page(
			'All-in-One WP Migration',
			'All-in-One WP Migration',
			'export',
			'ai1wm_export',
			'Ai1wm_Export_Controller::index',
			'',
			'76.295'
		);

		// Sub-level Export menu
		add_submenu_page(
			'ai1wm_export',
			__( 'Export', 'all-in-one-wp-migration' ),
			__( 'Export', 'all-in-one-wp-migration' ),
			'export',
			'ai1wm_export',
			'Ai1wm_Export_Controller::index'
		);

		// Sub-level Import menu
		add_submenu_page(
			'ai1wm_export',
			__( 'Import', 'all-in-one-wp-migration' ),
			__( 'Import', 'all-in-one-wp-migration' ),
			'import',
			'ai1wm_import',
			'Ai1wm_Import_Controller::index'
		);

		// Sub-level Backups menu
		add_submenu_page(
			'ai1wm_export',
			__( 'Backups', 'all-in-one-wp-migration' ),
			__( 'Backups', 'all-in-one-wp-migration' ) . ' ' . Ai1wm_Template::get_content( 'main/backups', array( 'count' => Ai1wm_Backups::count_files() ) ),
			'import',
			'ai1wm_backups',
			'Ai1wm_Backups_Controller::index'
		);

		if ( ! defined( 'AI1WMVE_PATH' ) ) {
			// Sub-level Reset
			add_submenu_page(
				'ai1wm_export',
				__( 'Reset Hub', 'all-in-one-wp-migration' ),
				__( 'Reset Hub', 'all-in-one-wp-migration' ) . Ai1wm_Template::get_content( 'main/premium-badge' ),
				'export',
				'ai1wm_reset',
				'Ai1wm_Reset_Controller::index'
			);
			// Sub-level Schedules
			add_submenu_page(
				'ai1wm_export',
				__( 'Schedules', 'all-in-one-wp-migration' ),
				__( 'Schedules', 'all-in-one-wp-migration' ) . Ai1wm_Template::get_content( 'main/premium-badge' ),
				'export',
				'ai1wm_schedules',
				'Ai1wm_Schedules_Controller::index'
			);
		}
	}

	/**
	 * Register scripts and styles
	 *
	 * @return void
	 */
	public function register_scripts_and_styles() {
		ai1wm_register_style(
			'ai1wm_servmask',
			Ai1wm_Template::asset_link( 'css/servmask.min.css' )
		);

		wp_register_script(
			'ai1wm_util',
			Ai1wm_Template::asset_link( 'javascript/util.min.js' ),
			array( 'jquery' ),
			AI1WM_VERSION,
			false
		);

		wp_register_script(
			'ai1wm_settings',
			Ai1wm_Template::asset_link( 'javascript/settings.min.js' ),
			array( 'ai1wm_util' ),
			AI1WM_VERSION,
			false
		);

		wp_localize_script(
			'ai1wm_settings',
			'ai1wm_locale',
			array(
				'thanks_for_submitting_your_feedback' => __( 'Thank you! We have received your request and will be in touch soon.', 'all-in-one-wp-migration' ),
			)
		);
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( stripos( 'toplevel_page_ai1wm_export', $hook ) === false ) {
			return;
		}

		// We don't want heartbeat to occur when exporting
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		ai1wm_enqueue_style(
			'ai1wm_export',
			Ai1wm_Template::asset_link( 'css/export.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_export',
			Ai1wm_Template::asset_link( 'javascript/export.min.js' ),
			array( 'ai1wm_util' )
		);

		wp_localize_script(
			'ai1wm_export',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_export',
			'ai1wm_export',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_export' ) ) ),
				),
				'download'   => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_download_file' ) ) ),
				),
				'status'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
				),
				'storage'    => array(
					'url' => AI1WM_STORAGE_URL,
				),
				'error_log'  => array(
					'pattern' => AI1WM_ERROR_NAME,
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_export',
			'ai1wm_locale',
			array(
				'stop_exporting_your_website'         => __( 'Are you sure you want to stop the export?', 'all-in-one-wp-migration' ),
				'preparing_to_export'                 => __( 'Preparing to export...', 'all-in-one-wp-migration' ),
				'unable_to_export'                    => __( 'Export failed', 'all-in-one-wp-migration' ),
				'unable_to_start_the_export'          => __( 'Could not start the export. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_run_the_export'            => __( 'Could not run the export. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_stop_the_export'           => __( 'Could not stop the export. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'please_wait_stopping_the_export'     => __( 'Stopping the export, please wait...', 'all-in-one-wp-migration' ),
				'close_export'                        => __( 'Close', 'all-in-one-wp-migration' ),
				'stop_export'                         => __( 'Stop export', 'all-in-one-wp-migration' ),
				'thanks_for_submitting_your_feedback' => __( 'Thank you! We have received your request and will be in touch soon.', 'all-in-one-wp-migration' ),
				/* translators: 1: Number of backups. */
				'backups_count_singular'              => __( 'You have %d backup', 'all-in-one-wp-migration' ),
				/* translators: 1: Number of backups. */
				'backups_count_plural'                => __( 'You have %d backups', 'all-in-one-wp-migration' ),
				'archive_browser_download_error'      => __( 'Could not download file', 'all-in-one-wp-migration' ),
				'view_error_log_button'               => __( 'View Error Log', 'all-in-one-wp-migration' ),
			)
		);
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_import', $hook ) === false ) {
			return;
		}

		// We don't want heartbeat to occur when importing
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		ai1wm_enqueue_style(
			'ai1wm_import',
			Ai1wm_Template::asset_link( 'css/import.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_import',
			Ai1wm_Template::asset_link( 'javascript/import.min.js' ),
			array( 'ai1wm_util' )
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_uploader',
			array(
				'max_file_size' => wp_max_upload_size(),
				'url'           => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_import' ) ) ),
				'params'        => array(
					'priority'   => 5,
					'secret_key' => get_option( AI1WM_SECRET_KEY ),
				),
			)
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_import',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_import' ) ) ),
				),
				'status'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
				),
				'storage'    => array(
					'url' => AI1WM_STORAGE_URL,
				),
				'error_log'  => array(
					'pattern' => AI1WM_ERROR_NAME,
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_compatibility',
			array(
				'messages' => Ai1wm_Compatibility::get( array() ),
			)
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_disk_space',
			array(
				'free'   => ai1wm_disk_free_space( AI1WM_STORAGE_PATH ),
				'factor' => AI1WM_DISK_SPACE_FACTOR,
				'extra'  => AI1WM_DISK_SPACE_EXTRA,
			)
		);

		$upload_limit_text = sprintf(
			/* translators: 1: Max upload file size. */
			__(
				'Your file exceeds the <strong>%1$s</strong> upload limit set by your host.<br />%2$s<br />%3$s',
				'all-in-one-wp-migration'
			),
			esc_html( ai1wm_size_format( wp_max_upload_size() ) ),
			sprintf(
				/* translators: Link to Unlimited extension. */
				__( 'Our <a href="%s" target="_blank">Unlimited Extension</a> bypasses this!', 'all-in-one-wp-migration' ),
				'https://servmask.com/products/unlimited-extension?utm_source=file-import&utm_medium=plugin&utm_campaign=ai1wm'
			),
			sprintf(
				/* translators: Link to how to article. */
				__( 'If you prefer a manual fix, follow our step-by-step guide on <a href="%s" target="_blank">raising your upload limit</a>.', 'all-in-one-wp-migration' ),
				'https://help.servmask.com/2018/10/27/how-to-increase-maximum-upload-file-size-in-wordpress/'
			)
		);

		wp_localize_script(
			'ai1wm_import',
			'ai1wm_locale',
			array(
				'stop_importing_your_website'         => __( 'Are you sure you want to stop the import?', 'all-in-one-wp-migration' ),
				'preparing_to_import'                 => __( 'Preparing to import...', 'all-in-one-wp-migration' ),
				'unable_to_import'                    => __( 'Import failed', 'all-in-one-wp-migration' ),
				'unable_to_start_the_import'          => __( 'Could not start the import. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_confirm_the_import'        => __( 'Could not confirm the import. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_check_decryption_password' => __( 'Could not check the decryption password. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_prepare_blogs_on_import'   => __( 'Could not prepare blogs for import. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'unable_to_stop_the_import'           => __( 'Could not stop the import. Please refresh and try again.', 'all-in-one-wp-migration' ),
				'please_wait_stopping_the_import'     => __( 'Stopping the import, please wait...', 'all-in-one-wp-migration' ),
				'close_import'                        => __( 'Close', 'all-in-one-wp-migration' ),
				'finish_import'                       => __( 'Finish', 'all-in-one-wp-migration' ),
				'stop_import'                         => __( 'Stop import', 'all-in-one-wp-migration' ),
				'confirm_import'                      => __( 'Proceed', 'all-in-one-wp-migration' ),
				'confirm_disk_space'                  => __( 'I have enough disk space', 'all-in-one-wp-migration' ),
				'continue_import'                     => __( 'Continue', 'all-in-one-wp-migration' ),
				'please_do_not_close_this_browser'    => __( 'Please do not close this browser window or your import will fail', 'all-in-one-wp-migration' ),
				'thanks_for_submitting_your_feedback' => __( 'Thank you! We have received your request and will be in touch soon.', 'all-in-one-wp-migration' ),
				'backup_encrypted'                    => __( 'The backup is encrypted', 'all-in-one-wp-migration' ),
				'backup_encrypted_message'            => __( 'Please enter a password to import the file', 'all-in-one-wp-migration' ),
				'submit'                              => __( 'Submit', 'all-in-one-wp-migration' ),
				'enter_password'                      => __( 'Enter a password', 'all-in-one-wp-migration' ),
				'repeat_password'                     => __( 'Repeat the password', 'all-in-one-wp-migration' ),
				'passwords_do_not_match'              => __( 'The passwords do not match', 'all-in-one-wp-migration' ),
				'view_error_log_button'               => __( 'View Error Log', 'all-in-one-wp-migration' ),
				'upload_failed_connection_lost'       => __( 'Upload failed - connection lost or timeout. Try uploading the file again.', 'all-in-one-wp-migration' ),
				'upload_failed'                       => __( 'Upload failed', 'all-in-one-wp-migration' ),
				'file_too_large'                      => sprintf(
					/* translators: 1: Link to Unlimited extension, 2: Link to how to article. */
					__(
						'Your file exceeds the upload limit set by your host web server.<br />%1$s<br />%2$s',
						'all-in-one-wp-migration'
					),
					sprintf(
						/* translators: Link to Unlimited extension. */
						__( 'Our <a href="%s" target="_blank">Unlimited Extension</a> bypasses this!', 'all-in-one-wp-migration' ),
						'https://servmask.com/products/unlimited-extension?utm_source=file-upload-webserver&utm_medium=plugin&utm_campaign=ai1wm'
					),
					sprintf(
						/* translators: Link to how to article. */
						__( 'If you prefer a manual fix, follow our step-by-step guide on <a href="%s" target="_blank">raising your upload limit</a>.', 'all-in-one-wp-migration' ),
						'https://help.servmask.com/2018/10/27/how-to-increase-maximum-upload-file-size-in-wordpress/'
					)
				),

				'import_from_file'                    => $upload_limit_text,
				'invalid_archive_extension'           => __(
					'Invalid file type. Please ensure that your file is a <strong>.wpress</strong> file created with All-in-One WP Migration.
					<a href="https://help.servmask.com/knowledgebase/invalid-backup-file/" target="_blank">Technical details</a>',
					'all-in-one-wp-migration'
				),
				'upgrade'                             => $upload_limit_text,
				/* translators: Disk space to free up. */
				'out_of_disk_space'                   => __(
					'Not enough disk space.<br />
 					Free up %s before importing.',
					'all-in-one-wp-migration'
				),
			)
		);
	}

	/**
	 * Enqueue scripts and styles for Backups Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_backups_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_backups', $hook ) === false ) {
			return;
		}

		// We don't want heartbeat to occur when restoring
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		ai1wm_enqueue_style(
			'ai1wm_backups',
			Ai1wm_Template::asset_link( 'css/backups.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_backups',
			Ai1wm_Template::asset_link( 'javascript/backups.min.js' ),
			array( 'ai1wm_util' )
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_feedback',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=ai1wm_feedback' ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_import',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_import' ) ) ),
				),
				'status'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
				),
				'storage'    => array(
					'url' => AI1WM_STORAGE_URL,
				),
				'error_log'  => array(
					'pattern' => AI1WM_ERROR_NAME,
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_export',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_export' ) ) ),
				),
				'status'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1, 'secret_key' => get_option( AI1WM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=ai1wm_status' ) ) ),
				),
				'storage'    => array(
					'url' => AI1WM_STORAGE_URL,
				),
				'error_log'  => array(
					'pattern' => AI1WM_ERROR_NAME,
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_backups',
			array(
				'ajax'       => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backups' ) ) ),
				),
				'backups'    => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_list' ) ) ),
				),
				'labels'     => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_add_backup_label' ) ) ),
				),
				'content'    => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_list_content' ) ) ),
				),
				'download'   => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_import' => 1 ), admin_url( 'admin-ajax.php?action=ai1wm_backup_download_file' ) ) ),
				),
				'secret_key' => get_option( AI1WM_SECRET_KEY ),
			)
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_disk_space',
			array(
				'free'   => ai1wm_disk_free_space( AI1WM_STORAGE_PATH ),
				'factor' => AI1WM_DISK_SPACE_FACTOR,
				'extra'  => AI1WM_DISK_SPACE_EXTRA,
			)
		);

		wp_localize_script(
			'ai1wm_backups',
			'ai1wm_locale',
			array(
				'stop_exporting_your_website'         => __( 'Are you sure you want to stop the export?', 'all-in-one-wp-migration' ),
				'preparing_to_export'                 => __( 'Preparing to export...', 'all-in-one-wp-migration' ),
				'unable_to_export'                    => __( 'Export failed', 'all-in-one-wp-migration' ),
				'unable_to_start_the_export'          => __( 'Could not start the export. Please refresh and try again', 'all-in-one-wp-migration' ),
				'unable_to_run_the_export'            => __( 'Could not run the export. Please refresh and try again', 'all-in-one-wp-migration' ),
				'unable_to_stop_the_export'           => __( 'Could not stop the export. Please refresh and try again', 'all-in-one-wp-migration' ),
				'please_wait_stopping_the_export'     => __( 'Stopping the export, please wait...', 'all-in-one-wp-migration' ),
				'close_export'                        => __( 'Close', 'all-in-one-wp-migration' ),
				'stop_export'                         => __( 'Stop export', 'all-in-one-wp-migration' ),
				'stop_importing_your_website'         => __( 'Are you sure you want to stop the import?', 'all-in-one-wp-migration' ),
				'preparing_to_import'                 => __( 'Preparing to import...', 'all-in-one-wp-migration' ),
				'unable_to_import'                    => __( 'Import failed', 'all-in-one-wp-migration' ),
				'unable_to_start_the_import'          => __( 'Could not start the import. Please refresh and try again', 'all-in-one-wp-migration' ),
				'unable_to_confirm_the_import'        => __( 'Could not confirm the import. Please refresh and try again', 'all-in-one-wp-migration' ),
				'unable_to_prepare_blogs_on_import'   => __( 'Could not prepare blogs on import. Please refresh and try again', 'all-in-one-wp-migration' ),
				'unable_to_stop_the_import'           => __( 'Could not stop the import. Please refresh and try again', 'all-in-one-wp-migration' ),
				'please_wait_stopping_the_import'     => __( 'Stopping the import, please wait...', 'all-in-one-wp-migration' ),
				'finish_import'                       => __( 'Finish', 'all-in-one-wp-migration' ),
				'close_import'                        => __( 'Close', 'all-in-one-wp-migration' ),
				'stop_import'                         => __( 'Stop import', 'all-in-one-wp-migration' ),
				'confirm_import'                      => __( 'Proceed', 'all-in-one-wp-migration' ),
				'confirm_disk_space'                  => __( 'I have enough disk space', 'all-in-one-wp-migration' ),
				'continue_import'                     => __( 'Continue', 'all-in-one-wp-migration' ),
				'please_do_not_close_this_browser'    => __( 'Please do not close this browser window or your import will fail', 'all-in-one-wp-migration' ),
				'thanks_for_submitting_your_feedback' => __( 'Thank you! We have received your request and will be in touch soon.', 'all-in-one-wp-migration' ),
				'want_to_delete_this_file'            => __( 'Are you sure you want to delete this backup?', 'all-in-one-wp-migration' ),
				'unlimited'                           => __( 'Backup restore requires the Unlimited extension. <a href="https://servmask.com/products/unlimited-extension" target="_blank">Get it here</a>', 'all-in-one-wp-migration' ),
				'restore_from_file'                   => sprintf(
					/* translators: 1: Link to Unlimited extension. */
					__( '"Restore" functionality is available in our <a href="%s" target="_blank">Unlimited Extension</a>.<br /> If you would rather go the manual route, you can still restore by downloading your backup and using "Import from file".', 'all-in-one-wp-migration' ),
					'https://servmask.com/products/unlimited-extension?utm_source=restore-from-file&utm_medium=plugin&utm_campaign=ai1wm'
				),
				/* translators: Disk space to free up. */
				'out_of_disk_space'                   => __(
					'Not enough disk space.<br />
 					Free up %s before restoring.',
					'all-in-one-wp-migration'
				),
				/* translators: Number of backups. */
				'backups_count_singular'              => __( 'You have %d backup', 'all-in-one-wp-migration' ),
				/* translators: Number of backups. */
				'backups_count_plural'                => __( 'You have %d backups', 'all-in-one-wp-migration' ),
				'archive_browser_error'               => __( 'Error', 'all-in-one-wp-migration' ),
				'archive_browser_list_error'          => __( 'Could not read backup content', 'all-in-one-wp-migration' ),
				'archive_browser_download_error'      => __( 'Could not download backup', 'all-in-one-wp-migration' ),
				'archive_browser_title'               => __( 'List the content of the backup', 'all-in-one-wp-migration' ),
				'progress_bar_title'                  => __( 'Reading...', 'all-in-one-wp-migration' ),
				'backup_encrypted'                    => __( 'The backup is encrypted', 'all-in-one-wp-migration' ),
				'backup_encrypted_message'            => __( 'Please enter a password to restore the backup', 'all-in-one-wp-migration' ),
				'submit'                              => __( 'Submit', 'all-in-one-wp-migration' ),
				'enter_password'                      => __( 'Enter a password', 'all-in-one-wp-migration' ),
				'repeat_password'                     => __( 'Repeat the password', 'all-in-one-wp-migration' ),
				'passwords_do_not_match'              => __( 'The passwords do not match', 'all-in-one-wp-migration' ),
				'view_error_log_button'               => __( 'View Error Log', 'all-in-one-wp-migration' ),
			)
		);
	}

	/**
	 * Enqueue scripts and styles for Schedules page
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_schedules_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_schedules', $hook ) === false ) {
			return;
		}

		// We don't want heartbeat to occur when restoring
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		ai1wm_enqueue_style(
			'ai1wm_schedules',
			Ai1wm_Template::asset_link( 'css/schedules.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_schedules',
			Ai1wm_Template::asset_link( 'javascript/schedules.min.js' )
		);
	}

	/**
	 * Enqueue scripts and styles for Reset page
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_reset_scripts_and_styles( $hook ) {
		if ( stripos( 'all-in-one-wp-migration_page_ai1wm_reset', $hook ) === false ) {
			return;
		}

		// We don't want heartbeat to occur when restoring
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		ai1wm_enqueue_style(
			'ai1wm_reset',
			Ai1wm_Template::asset_link( 'css/reset.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_reset',
			Ai1wm_Template::asset_link( 'javascript/reset.min.js' )
		);
	}

	/**
	 * Enqueue scripts and styles for Updater Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_updater_scripts_and_styles( $hook ) {
		if ( 'plugins.php' !== strtolower( $hook ) ) {
			return;
		}

		ai1wm_enqueue_style(
			'ai1wm_updater',
			Ai1wm_Template::asset_link( 'css/updater.min.css' )
		);

		ai1wm_enqueue_script(
			'ai1wm_updater',
			Ai1wm_Template::asset_link( 'javascript/updater.min.js' ),
			array( 'ai1wm_util' )
		);

		wp_localize_script(
			'ai1wm_updater',
			'ai1wm_updater',
			array(
				'ajax' => array(
					'url' => wp_make_link_relative( add_query_arg( array( 'ai1wm_nonce' => wp_create_nonce( 'ai1wm_updater' ) ), admin_url( 'admin-ajax.php?action=ai1wm_updater' ) ) ),
				),
			)
		);

		wp_localize_script(
			'ai1wm_updater',
			'ai1wm_locale',
			array(
				'check_for_updates'   => __( 'Check for updates', 'all-in-one-wp-migration' ),
				'invalid_purchase_id' => __( 'Your purchase ID is invalid. Please <a href="mailto:support@servmask.com">contact us</a>.', 'all-in-one-wp-migration' ),
			)
		);
	}



	/**
	 * Outputs menu icon between head tags
	 *
	 * @return void
	 */
	public function admin_head() {
		global $wp_version;

		// Admin header
		Ai1wm_Template::render( 'main/admin-head', array( 'version' => $wp_version ) );
	}

	/**
	 * Register initial parameters
	 *
	 * @return void
	 */
	public function init() {
		$user = $password = false;

		// Set username
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$user = $_SERVER['PHP_AUTH_USER'];
		} elseif ( isset( $_SERVER['REMOTE_USER'] ) ) {
			$user = $_SERVER['REMOTE_USER'];
		}

		// Set password
		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$password = $_SERVER['PHP_AUTH_PW'];
		}

		if ( $user !== false && $password !== false ) {
			update_option( AI1WM_AUTH_HEADER, base64_encode( sprintf( '%s:%s', $user, $password ) ) );
		}

		// Check for updates
		if ( isset( $_GET['ai1wm_check_for_updates'] ) ) {
			if ( check_admin_referer( 'ai1wm_check_for_updates', 'ai1wm_nonce' ) ) {
				if ( current_user_can( 'update_plugins' ) ) {
					Ai1wm_Updater::check_for_updates();
				}
			}
		}
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		// Public actions
		add_action( 'wp_ajax_nopriv_ai1wm_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'wp_ajax_nopriv_ai1wm_import', 'Ai1wm_Import_Controller::import' );
		add_action( 'wp_ajax_nopriv_ai1wm_status', 'Ai1wm_Status_Controller::status' );
		add_action( 'wp_ajax_nopriv_ai1wm_backups', 'Ai1wm_Backups_Controller::delete' );
		add_action( 'wp_ajax_nopriv_ai1wm_feedback', 'Ai1wm_Feedback_Controller::feedback' );
		add_action( 'wp_ajax_nopriv_ai1wm_add_backup_label', 'Ai1wm_Backups_Controller::add_label' );
		add_action( 'wp_ajax_nopriv_ai1wm_backup_list', 'Ai1wm_Backups_Controller::backup_list' );

		// Private actions
		add_action( 'wp_ajax_ai1wm_export', 'Ai1wm_Export_Controller::export' );
		add_action( 'wp_ajax_ai1wm_import', 'Ai1wm_Import_Controller::import' );
		add_action( 'wp_ajax_ai1wm_status', 'Ai1wm_Status_Controller::status' );
		add_action( 'wp_ajax_ai1wm_backups', 'Ai1wm_Backups_Controller::delete' );
		add_action( 'wp_ajax_ai1wm_feedback', 'Ai1wm_Feedback_Controller::feedback' );
		add_action( 'wp_ajax_ai1wm_add_backup_label', 'Ai1wm_Backups_Controller::add_label' );
		add_action( 'wp_ajax_ai1wm_backup_list', 'Ai1wm_Backups_Controller::backup_list' );
		add_action( 'wp_ajax_ai1wm_backup_list_content', 'Ai1wm_Backups_Controller::backup_list_content' );
		add_action( 'wp_ajax_ai1wm_backup_download_file', 'Ai1wm_Backups_Controller::download_file' );

		// Update actions
		if ( current_user_can( 'update_plugins' ) ) {
			add_action( 'wp_ajax_ai1wm_updater', 'Ai1wm_Updater_Controller::updater' );
		}
	}

	/**
	 * Enable WP importing
	 *
	 * @return void
	 */
	public function wp_importing() {
		if ( isset( $_GET['ai1wm_import'] ) ) {
			if ( ! defined( 'WP_IMPORTING' ) ) {
				define( 'WP_IMPORTING', true );
			}
		}
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param  array $schedules List of schedules
	 * @return array
	 */
	public function add_cron_schedules( $schedules ) {
		$schedules['weekly']  = array(
			'display'  => __( 'Weekly', 'all-in-one-wp-migration' ),
			'interval' => 60 * 60 * 24 * 7,
		);
		$schedules['monthly'] = array(
			'display'  => __( 'Monthly', 'all-in-one-wp-migration' ),
			'interval' => ( strtotime( '+1 month' ) - time() ),
		);

		return $schedules;
	}

	/**
	 * Handles ai1wm_status_export_error hook
	 *
	 * @param $params
	 * @param $exception
	 *
	 * @return void
	 */
	public function handle_error_cleanup( $params, $exception = null ) {
		if ( ! $exception instanceof Ai1wm_Import_Retry_Exception ) {
			Ai1wm_Directory::delete( ai1wm_storage_path( $params ) );
		}
	}
}
