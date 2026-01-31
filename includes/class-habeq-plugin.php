<?php
/**
 * Main plugin bootstrapper.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Plugin' ) ) {
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
	}
}
