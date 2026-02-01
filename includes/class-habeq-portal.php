<?php
/**
 * Organizer portal experience.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Portal' ) ) {
	class Habeq_Portal {
		/**
		 * Option name for storing the portal page ID.
		 *
		 * @var string
		 */
		const PAGE_OPTION = 'habeq_portal_page_id';

		/**
		 * Tabs available in the portal.
		 *
		 * @var string[]
		 */
		private static $tabs = array(
			'login',
			'signup',
			'dashboard',
			'events',
			'new-event',
			'bookings',
		);

		/**
		 * Bootstrap portal hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_shortcode( 'habeq_portal', array( __CLASS__, 'render_shortcode' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		/**
		 * Render the portal shortcode.
		 *
		 * @return string
		 */
		public static function render_shortcode() {
			$tab                 = self::get_active_tab();
			$portal_content_path = self::get_template_path( self::get_template_for_tab( $tab ) );
			$portal_tab          = $tab;
			$portal_wrapper_path = self::get_template_path( 'wrapper.php' );

			if ( '' === $portal_wrapper_path ) {
				return '';
			}

			ob_start();
			require $portal_wrapper_path;
			return ob_get_clean();
		}

		/**
		 * Enqueue portal styles only on the portal page.
		 *
		 * @return void
		 */
		public static function enqueue_assets() {
			$page_id = absint( get_option( self::PAGE_OPTION ) );
			if ( $page_id && is_page( $page_id ) ) {
				wp_enqueue_style(
					'habeq-portal',
					HABEQ_URL . 'assets/portal.css',
					array(),
					HABEQ_VERSION
				);
			}
		}

		/**
		 * Ensure the portal page exists and store its ID.
		 *
		 * @return void
		 */
		public static function maybe_create_portal_page() {
			$page_id = absint( get_option( self::PAGE_OPTION ) );
			if ( $page_id && get_post( $page_id ) ) {
				return;
			}

			$page = get_page_by_title( 'Organizer Portal' );
			if ( $page && isset( $page->ID ) ) {
				update_option( self::PAGE_OPTION, (int) $page->ID );
				return;
			}

			$created_id = wp_insert_post(
				array(
					'post_title'   => 'Organizer Portal',
					'post_content' => '[habeq_portal]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				),
				true
			);

			if ( is_wp_error( $created_id ) ) {
				return;
			}

			update_option( self::PAGE_OPTION, (int) $created_id );
		}

		/**
		 * Determine the active tab.
		 *
		 * @return string
		 */
		private static function get_active_tab() {
			$requested_tab = '';
			if ( isset( $_GET['tab'] ) ) {
				$requested_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			}

			$default_tab = is_user_logged_in() ? 'dashboard' : 'login';
			$tab         = '' === $requested_tab ? $default_tab : $requested_tab;

			if ( ! in_array( $tab, self::$tabs, true ) ) {
				$tab = $default_tab;
			}

			return $tab;
		}

		/**
		 * Map tabs to template names.
		 *
		 * @param string $tab Tab slug.
		 * @return string
		 */
		private static function get_template_for_tab( $tab ) {
			$map = array(
				'login'     => 'login.php',
				'signup'    => 'signup.php',
				'dashboard' => 'dashboard.php',
				'events'    => 'events.php',
				'new-event' => 'new-event.php',
				'bookings'  => 'bookings.php',
			);

			if ( isset( $map[ $tab ] ) ) {
				return $map[ $tab ];
			}

			return $map['login'];
		}

		/**
		 * Resolve portal template path.
		 *
		 * @param string $template Template filename.
		 * @return string
		 */
		private static function get_template_path( $template ) {
			$path = HABEQ_PATH . 'templates/portal/' . $template;
			if ( file_exists( $path ) ) {
				return $path;
			}

			return '';
		}
	}
}
