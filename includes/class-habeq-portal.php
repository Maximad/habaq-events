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
			add_action( 'admin_post_nopriv_habeq_signup', array( __CLASS__, 'handle_signup' ) );
			add_action( 'admin_post_nopriv_habeq_login', array( __CLASS__, 'handle_login' ) );
			add_action( 'admin_post_habeq_logout', array( __CLASS__, 'handle_logout' ) );
		}

		/**
		 * Render the portal shortcode.
		 *
		 * @return string
		 */
		public static function render_shortcode() {
			$tab                 = self::get_active_tab();
			$portal_user_status  = self::get_current_user_status();
			$restricted_tabs     = self::get_restricted_tabs();

			if ( is_user_logged_in() && 'approved' !== $portal_user_status && in_array( $tab, $restricted_tabs, true ) ) {
				$tab = 'pending';
			}

			$portal_content_path = self::get_template_path( self::get_template_for_tab( $tab ) );
			$portal_tab          = $tab;
			$portal_status       = $portal_user_status;
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
		 * Handle organizer signup submissions.
		 *
		 * @return void
		 */
		public static function handle_signup() {
			check_admin_referer( 'habeq_portal_signup', 'habeq_portal_signup_nonce' );

			if ( ! self::allow_signup() ) {
				self::redirect_with_status(
					'signup',
					array(
						'status' => 'error',
						'error'  => 'signup_disabled',
					)
				);
			}

			$email_raw       = isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
			$display_raw     = isset( $_POST['display_name'] ) ? wp_unslash( $_POST['display_name'] ) : '';
			$email           = sanitize_email( $email_raw );
			$display_name    = sanitize_text_field( $display_raw );

			if ( '' === $email || ! is_email( $email ) ) {
				self::redirect_with_status(
					'signup',
					array(
						'status' => 'error',
						'error'  => 'invalid_email',
					)
				);
			}

			$username = self::generate_username_from_email( $email );
			$result   = register_new_user( $username, $email );

			if ( is_wp_error( $result ) ) {
				$error_code = $result->get_error_code() ? $result->get_error_code() : 'signup_failed';
				self::redirect_with_status(
					'signup',
					array(
						'status' => 'error',
						'error'  => $error_code,
					)
				);
			}

			$user_id = absint( $result );
			if ( $display_name ) {
				wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $display_name,
					)
				);
			}

			$require_approval = self::require_approval();
			$role             = $require_approval ? 'subscriber' : 'habeq_event_organizer';
			$status           = $require_approval ? 'pending' : 'approved';

			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$user->set_role( $role );
			}

			update_user_meta( $user_id, 'habeq_organizer_status', $status );

			wp_mail(
				get_option( 'admin_email' ),
				__( 'New organizer signup', 'habeq' ),
				sprintf(
					/* translators: %s: user email */
					__( 'A new organizer signed up with email %s.', 'habeq' ),
					$email
				)
			);

			self::redirect_with_status(
				'login',
				array(
					'status' => 'signup_success',
				)
			);
		}

		/**
		 * Handle organizer login submissions.
		 *
		 * @return void
		 */
		public static function handle_login() {
			check_admin_referer( 'habeq_portal_login', 'habeq_portal_login_nonce' );

			$login_raw    = isset( $_POST['login'] ) ? wp_unslash( $_POST['login'] ) : '';
			$password_raw = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
			$login        = sanitize_text_field( $login_raw );
			$password     = (string) $password_raw;

			if ( '' === $login || '' === $password ) {
				self::redirect_with_status(
					'login',
					array(
						'status' => 'error',
						'error'  => 'missing_credentials',
					)
				);
			}

			$user_login = $login;
			if ( is_email( $login ) ) {
				$user = get_user_by( 'email', $login );
				if ( $user ) {
					$user_login = $user->user_login;
				}
			}

			$result = wp_signon(
				array(
					'user_login'    => $user_login,
					'user_password' => $password,
					'remember'      => true,
				),
				false
			);

			if ( is_wp_error( $result ) ) {
				$error_code = $result->get_error_code() ? $result->get_error_code() : 'login_failed';
				self::redirect_with_status(
					'login',
					array(
						'status' => 'error',
						'error'  => $error_code,
					)
				);
			}

			self::redirect_with_status( 'dashboard' );
		}

		/**
		 * Handle organizer logout requests.
		 *
		 * @return void
		 */
		public static function handle_logout() {
			check_admin_referer( 'habeq_portal_logout', 'habeq_portal_logout_nonce' );
			wp_logout();
			self::redirect_with_status(
				'login',
				array(
					'status' => 'logged_out',
				)
			);
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
				'pending'   => 'pending.php',
			);

			if ( isset( $map[ $tab ] ) ) {
				return $map[ $tab ];
			}

			return $map['login'];
		}

		/**
		 * Get current organizer status.
		 *
		 * @return string
		 */
		private static function get_current_user_status() {
			if ( ! is_user_logged_in() ) {
				return 'guest';
			}

			$user_id = get_current_user_id();
			$status  = get_user_meta( $user_id, 'habeq_organizer_status', true );

			return $status ? $status : 'pending';
		}

		/**
		 * Tabs restricted to approved organizers.
		 *
		 * @return string[]
		 */
		private static function get_restricted_tabs() {
			return array(
				'dashboard',
				'events',
				'new-event',
				'bookings',
			);
		}

		/**
		 * Check if organizer signup is allowed.
		 *
		 * @return bool
		 */
		private static function allow_signup() {
			return '1' === (string) get_option( 'habeq_allow_signup', '1' );
		}

		/**
		 * Check if approval is required.
		 *
		 * @return bool
		 */
		private static function require_approval() {
			return '1' === (string) get_option( 'habeq_require_approval', '1' );
		}

		/**
		 * Redirect back to the portal with status arguments.
		 *
		 * @param string $tab  Portal tab.
		 * @param array  $args Query args.
		 * @return void
		 */
		private static function redirect_with_status( $tab, $args = array() ) {
			$url = function_exists( 'habeq_portal_url' )
				? habeq_portal_url( $tab, $args )
				: add_query_arg( array_merge( array( 'tab' => $tab ), $args ), home_url( '/' ) );
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Generate a unique username based on an email address.
		 *
		 * @param string $email Email address.
		 * @return string
		 */
		private static function generate_username_from_email( $email ) {
			$base = sanitize_user( current( explode( '@', $email ) ), true );
			$base = $base ? $base : 'organizer';
			$base = substr( $base, 0, 60 );

			$username = $base;
			$suffix   = 1;

			while ( username_exists( $username ) ) {
				$username = $base . $suffix;
				$suffix++;
			}

			return $username;
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
