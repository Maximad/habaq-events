<?php
/**
 * Main plugin bootstrapper.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Plugin' ) ) {
	/**
	 * Plugin bootstrap class.
	 *
	 * @package Habaq_Events
	 */
	class Habeq_Plugin {
		/**
		 * Singleton instance.
		 *
		 * @var Habeq_Plugin|null
		 */
		private static $instance = null;

		/**
		 * Initialize the plugin instance.
		 *
		 * @return Habeq_Plugin
		 */
		public static function init() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Activation handler.
		 *
		 * @return void
		 */
			public static function activate() {
				update_option( 'habeq_version', HABEQ_VERSION );
				add_option( 'habeq_allow_signup', '1' );
				add_option( 'habeq_require_approval', '1' );
				add_option( 'habeq_autopublish_approved', '0' );
				if ( class_exists( 'Habeq_DB' ) ) {
					Habeq_DB::create_or_upgrade_tables();
				}
			$caps_path = HABEQ_PATH . 'includes/class-habeq-capabilities.php';
			if ( file_exists( $caps_path ) ) {
				require_once $caps_path;
			}
			if ( class_exists( 'Habeq_Capabilities' ) ) {
				Habeq_Capabilities::register_role_and_caps();
			}
			$portal_path = HABEQ_PATH . 'includes/class-habeq-portal.php';
			if ( file_exists( $portal_path ) ) {
				require_once $portal_path;
			}
			if ( class_exists( 'Habeq_Portal' ) ) {
				Habeq_Portal::maybe_create_portal_page();
			}
			if ( class_exists( 'Habeq_Installer' ) ) {
				Habeq_Installer::run_setup();
			}
		}

		/**
		 * Deactivation handler.
		 *
		 * @return void
		 */
		public static function deactivate() {
			// Placeholder for future deactivation logic.
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			$this->load_helpers();
			$this->load_components();
			$this->register_hooks();
		}

		/**
		 * Load helper functions.
		 *
		 * @return void
		 */
		private function load_helpers() {
			$helpers_path = HABEQ_PATH . 'includes/helpers.php';
			if ( file_exists( $helpers_path ) ) {
				require_once $helpers_path;
			}
		}

		/**
		 * Load core components.
		 *
		 * @return void
		 */
		private function load_components() {
			$cpt_path = HABEQ_PATH . 'includes/class-habeq-cpt-event.php';
			if ( file_exists( $cpt_path ) ) {
				require_once $cpt_path;
			}

			$db_path = HABEQ_PATH . 'includes/class-habeq-db.php';
			if ( file_exists( $db_path ) ) {
				require_once $db_path;
			}

			$caps_path = HABEQ_PATH . 'includes/class-habeq-capabilities.php';
			if ( file_exists( $caps_path ) ) {
				require_once $caps_path;
			}

			$bookings_path = HABEQ_PATH . 'includes/class-habeq-bookings.php';
			if ( file_exists( $bookings_path ) ) {
				require_once $bookings_path;
			}

			$portal_path = HABEQ_PATH . 'includes/class-habeq-portal.php';
			if ( file_exists( $portal_path ) ) {
				require_once $portal_path;
			}

			$installer_path = HABEQ_PATH . 'includes/class-habeq-installer.php';
			if ( file_exists( $installer_path ) ) {
				require_once $installer_path;
			}
		}

		/**
		 * Register WordPress hooks.
		 *
		 * @return void
		 */
		private function register_hooks() {
			add_action( 'init', array( $this, 'load_textdomain' ) );
			add_action( 'init', array( $this, 'register_content_types' ) );
			add_action( 'init', array( $this, 'register_data_stores' ) );
			add_action( 'init', array( $this, 'maybe_upgrade_db' ) );
			add_action( 'init', array( $this, 'register_portal' ) );
			add_action( 'init', array( $this, 'register_installer' ) );
		}

		/**
		 * Load plugin translations.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain(
				'habeq',
				false,
				dirname( plugin_basename( HABEQ_PATH . 'habaq-events.php' ) ) . '/languages'
			);
		}

		/**
		 * Placeholder for registering custom post types.
		 *
		 * @return void
		 */
		public function register_content_types() {
			if ( class_exists( 'Habeq_CPT_Event' ) ) {
				Habeq_CPT_Event::init();
			}
		}

		/**
		 * Placeholder for registering data stores.
		 *
		 * @return void
		 */
		public function register_data_stores() {
			// Future database setup.
		}

		/**
		 * Ensure database schema is up to date.
		 *
		 * @return void
		 */
		public function maybe_upgrade_db() {
			if ( class_exists( 'Habeq_DB' ) ) {
				Habeq_DB::create_or_upgrade_tables();
			}
		}

		/**
		 * Register portal hooks.
		 *
		 * @return void
		 */
		public function register_portal() {
			if ( class_exists( 'Habeq_Portal' ) ) {
				Habeq_Portal::init();
			}
		}

		/**
		 * Register installer hooks.
		 *
		 * @return void
		 */
		public function register_installer() {
			if ( class_exists( 'Habeq_Installer' ) ) {
				Habeq_Installer::init();
			}
		}
	}
}
