<?php
/**
 * Organizer portal experience.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Portal' ) ) {
	/**
	 * Organizer portal experience.
	 *
	 * @package Habaq_Events
	 */
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
			add_shortcode( 'habeq_booking_confirmation', array( __CLASS__, 'render_booking_confirmation' ) );
			add_shortcode( 'habeq_manage_booking', array( __CLASS__, 'render_manage_booking' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'admin_post_nopriv_habeq_signup', array( __CLASS__, 'handle_signup' ) );
			add_action( 'admin_post_nopriv_habeq_login', array( __CLASS__, 'handle_login' ) );
			add_action( 'admin_post_habeq_logout', array( __CLASS__, 'handle_logout' ) );
			add_action( 'admin_post_habeq_event_create', array( __CLASS__, 'handle_event_create' ) );
			add_action( 'admin_post_habeq_event_update', array( __CLASS__, 'handle_event_update' ) );
			add_action( 'admin_post_habeq_bookings_export', array( __CLASS__, 'handle_bookings_export' ) );
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
			$can_manage_tabs     = self::can_manage_portal_tabs();
			$habeq_status        = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
			$habeq_error         = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
			$habeq_event_id      = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;

			if ( is_user_logged_in() && ( ! $can_manage_tabs ) && in_array( $tab, $restricted_tabs, true ) ) {
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
		 * Render booking confirmation placeholder.
		 *
		 * @return string
		 */
		public static function render_booking_confirmation() {
			$status  = isset( $_GET['booking'] ) ? sanitize_key( wp_unslash( $_GET['booking'] ) ) : '';
			$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

			$success = 'success' === $status;
			$text    = $success
				? __( 'Your booking has been confirmed.', 'habeq' )
				: __( 'Your booking is being processed.', 'habeq' );

			if ( $message ) {
				$text = $message;
			}

			return '<div class=\"habeq-booking-confirmation\">' . esc_html( $text ) . '</div>';
		}

		/**
		 * Render manage booking placeholder.
		 *
		 * @return string
		 */
		public static function render_manage_booking() {
			return '<p>' . esc_html__( 'Manage booking coming soon.', 'habeq' ) . '</p>';
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

			$page_query = new WP_Query(
				array(
					'post_type'      => 'page',
					'post_status'    => 'any',
					'posts_per_page' => 5,
					's'              => 'Organizer Portal',
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $page_query->posts ) ) {
				foreach ( $page_query->posts as $page_id ) {
					$title = get_the_title( $page_id );
					if ( 'Organizer Portal' === $title ) {
						update_option( self::PAGE_OPTION, (int) $page_id );
						return;
					}
				}
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

			$email_raw    = isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '';
			$display_raw  = isset( $_POST['display_name'] ) ? wp_unslash( $_POST['display_name'] ) : '';
			$email        = sanitize_email( $email_raw );
			$display_name = sanitize_text_field( $display_raw );
			$ip_address   = function_exists( 'habeq_get_ip' ) ? habeq_get_ip() : '';

			if ( '' === $email || ! is_email( $email ) ) {
				self::redirect_with_status(
					'signup',
					array(
						'status' => 'error',
						'error'  => 'invalid_email',
					)
				);
			}

			if ( self::is_signup_rate_limited( $email, $ip_address ) ) {
				self::redirect_with_status(
					'signup',
					array(
						'status' => 'error',
						'error'  => 'rate_limited',
					)
				);
			}

			self::set_signup_rate_limit( $email, $ip_address );

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
		 * Handle organizer event creation.
		 *
		 * @return void
		 */
		public static function handle_event_create() {
			check_admin_referer( 'habeq_portal_event_create', 'habeq_portal_event_nonce' );

			if ( ! self::is_current_user_approved_organizer() ) {
				self::redirect_with_status(
					'events',
					array(
						'status' => 'error',
						'error'  => 'not_allowed',
					)
				);
			}

			$event_data = self::get_event_payload();
			if ( is_wp_error( $event_data ) ) {
				self::redirect_with_status(
					'new-event',
					array(
						'status' => 'error',
						'error'  => $event_data->get_error_code(),
					)
				);
			}

			$status = self::auto_publish_enabled() ? 'publish' : 'pending';

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'habeq_event',
					'post_title'   => $event_data['title'],
					'post_content' => $event_data['description'],
					'post_status'  => $status,
					'post_author'  => get_current_user_id(),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				self::redirect_with_status(
					'new-event',
					array(
						'status' => 'error',
						'error'  => 'create_failed',
					)
				);
			}

			self::update_event_meta( $post_id, $event_data );

			self::redirect_with_status(
				'events',
				array(
					'status'   => 'event_created',
					'event_id' => $post_id,
				)
			);
		}

		/**
		 * Handle organizer event updates.
		 *
		 * @return void
		 */
		public static function handle_event_update() {
			check_admin_referer( 'habeq_portal_event_update', 'habeq_portal_event_nonce' );

			if ( ! self::is_current_user_approved_organizer() ) {
				self::redirect_with_status(
					'events',
					array(
						'status' => 'error',
						'error'  => 'not_allowed',
					)
				);
			}

			$event_id = isset( $_POST['event_id'] ) ? absint( wp_unslash( $_POST['event_id'] ) ) : 0;
			if ( 0 === $event_id ) {
				self::redirect_with_status(
					'events',
					array(
						'status' => 'error',
						'error'  => 'invalid_event',
					)
				);
			}

			$post = get_post( $event_id );
			if ( ! $post || 'habeq_event' !== $post->post_type || (int) $post->post_author !== get_current_user_id() ) {
				self::redirect_with_status(
					'events',
					array(
						'status' => 'error',
						'error'  => 'not_allowed',
					)
				);
			}

			$event_data = self::get_event_payload();
			if ( is_wp_error( $event_data ) ) {
				self::redirect_with_status(
					'new-event',
					array(
						'status'   => 'error',
						'error'    => $event_data->get_error_code(),
						'event_id' => $event_id,
					)
				);
			}

			$updated = wp_update_post(
				array(
					'ID'           => $event_id,
					'post_title'   => $event_data['title'],
					'post_content' => $event_data['description'],
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				self::redirect_with_status(
					'new-event',
					array(
						'status'   => 'error',
						'error'    => 'update_failed',
						'event_id' => $event_id,
					)
				);
			}

			self::update_event_meta( $event_id, $event_data );

			self::redirect_with_status(
				'events',
				array(
					'status'   => 'event_updated',
					'event_id' => $event_id,
				)
			);
		}

		/**
		 * Handle bookings CSV export.
		 *
		 * @return void
		 */
		public static function handle_bookings_export() {
			if ( ! isset( $_GET['habeq_bookings_export_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['habeq_bookings_export_nonce'] ), 'habeq_bookings_export' ) ) {
				wp_die( esc_html__( 'Invalid export request.', 'habeq' ) );
			}

			$event_id = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;
			if ( 0 === $event_id ) {
				wp_die( esc_html__( 'Invalid event.', 'habeq' ) );
			}

			if ( ! self::can_view_event( $event_id ) ) {
				wp_die( esc_html__( 'You do not have permission to export this event.', 'habeq' ) );
			}

			$bookings = self::get_bookings_for_event( $event_id );

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=habeq-bookings-' . $event_id . '.csv' );

			$output = fopen( 'php://output', 'w' );
			if ( false === $output ) {
				wp_die( esc_html__( 'Unable to generate export.', 'habeq' ) );
			}

			fputcsv( $output, array( 'Booking ID', 'Name', 'Email', 'Qty', 'Status', 'Created' ) );
			foreach ( $bookings as $booking ) {
				fputcsv(
					$output,
					array(
						$booking['id'],
						$booking['name'],
						$booking['email'],
						$booking['qty'],
						$booking['status'],
						$booking['created_at'],
					)
				);
			}

			fclose( $output );
			exit;
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
		 * Check if the current user is an approved organizer.
		 *
		 * @return bool
		 */
		public static function is_current_user_approved_organizer() {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$user   = wp_get_current_user();
			$status = get_user_meta( $user->ID, 'habeq_organizer_status', true );

			return in_array( 'habeq_event_organizer', (array) $user->roles, true ) && 'approved' === $status;
		}

		/**
		 * Check if the current user can access management tabs.
		 *
		 * @return bool
		 */
		public static function can_manage_portal_tabs() {
			return self::is_admin_user() || self::is_current_user_approved_organizer();
		}

		/**
		 * Check if current user is an admin.
		 *
		 * @return bool
		 */
		private static function is_admin_user() {
			return current_user_can( 'manage_options' );
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
		 * Check if auto-publish is enabled for approved organizers.
		 *
		 * @return bool
		 */
		private static function auto_publish_enabled() {
			return '1' === (string) get_option( 'habeq_autopublish_approved', '0' );
		}

		/**
		 * Get events the current user can access in the portal.
		 *
		 * @return WP_Post[]
		 */
		public static function get_accessible_events() {
			$args = array(
				'post_type'      => 'habeq_event',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);

			if ( ! self::is_admin_user() ) {
				$args['author'] = get_current_user_id();
			}

			return get_posts( $args );
		}

		/**
		 * Determine if the current user can view an event's bookings.
		 *
		 * @param int $event_id Event ID.
		 * @return bool
		 */
		private static function can_view_event( $event_id ) {
			if ( self::is_admin_user() ) {
				return true;
			}

			$post = get_post( $event_id );
			if ( ! $post || 'habeq_event' !== $post->post_type ) {
				return false;
			}

			return (int) $post->post_author === get_current_user_id();
		}

		/**
		 * Get bookings for a specific event.
		 *
		 * @param int $event_id Event ID.
		 * @return array
		 */
		public static function get_bookings_for_event( $event_id ) {
			if ( ! class_exists( 'Habeq_DB' ) ) {
				return array();
			}

			global $wpdb;

			$tables = Habeq_DB::get_table_names();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
					"SELECT id, name, email, qty, status, created_at FROM {$tables['bookings']} WHERE event_id = %d ORDER BY created_at DESC",
					absint( $event_id )
				),
				ARRAY_A
			);

			if ( ! $rows ) {
				return array();
			}

			return array_map(
				static function ( $row ) {
					return array(
						'id'         => absint( $row['id'] ),
						'name'       => (string) $row['name'],
						'email'      => (string) $row['email'],
						'qty'        => absint( $row['qty'] ),
						'status'     => (string) $row['status'],
						'created_at' => (string) $row['created_at'],
					);
				},
				$rows
			);
		}

		/**
		 * Get sanitized event payload from POST data.
		 *
		 * @return array|WP_Error
		 */
		private static function get_event_payload() {
			$title_raw       = isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '';
			$description_raw = isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '';
			$start_raw       = isset( $_POST['start_datetime'] ) ? wp_unslash( $_POST['start_datetime'] ) : '';
			$end_raw         = isset( $_POST['end_datetime'] ) ? wp_unslash( $_POST['end_datetime'] ) : '';
			$venue_name_raw  = isset( $_POST['venue_name'] ) ? wp_unslash( $_POST['venue_name'] ) : '';
			$venue_addr_raw  = isset( $_POST['venue_address'] ) ? wp_unslash( $_POST['venue_address'] ) : '';
			$capacity_raw    = isset( $_POST['capacity'] ) ? wp_unslash( $_POST['capacity'] ) : '';

			$title       = sanitize_text_field( $title_raw );
			$description = wp_kses_post( $description_raw );
			$start       = sanitize_text_field( $start_raw );
			$end         = sanitize_text_field( $end_raw );
			$venue_name  = sanitize_text_field( $venue_name_raw );
			$venue_addr  = sanitize_text_field( $venue_addr_raw );
			$capacity    = '' === trim( (string) $capacity_raw ) ? 0 : absint( $capacity_raw );

			if ( '' === $title ) {
				return new WP_Error( 'missing_title', __( 'Event title is required.', 'habeq' ) );
			}

			return array(
				'title'         => $title,
				'description'   => $description,
				'start'         => $start,
				'end'           => $end,
				'venue_name'    => $venue_name,
				'venue_address' => $venue_addr,
				'capacity'      => $capacity,
			);
		}

		/**
		 * Update event meta and inventory for an event.
		 *
		 * @param int   $post_id Event ID.
		 * @param array $data    Event data.
		 * @return void
		 */
		private static function update_event_meta( $post_id, $data ) {
			update_post_meta( $post_id, '_habeq_start', $data['start'] );
			update_post_meta( $post_id, '_habeq_end', $data['end'] );
			update_post_meta( $post_id, '_habeq_venue_name', $data['venue_name'] );
			update_post_meta( $post_id, '_habeq_venue_address', $data['venue_address'] );
			update_post_meta( $post_id, '_habeq_capacity', $data['capacity'] );

			if ( class_exists( 'Habeq_DB' ) ) {
				Habeq_DB::maybe_ensure_inventory_row( $post_id, $data['capacity'] );
			}
		}

		/**
		 * Redirect back to the portal with status arguments.
		 *
		 * @param string $tab  Portal tab.
		 * @param array  $args Query args.
		 * @return string
		 */
		private static function redirect_with_status( $tab, $args = array() ) {
			$url = function_exists( 'habeq_portal_url' )
				? habeq_portal_url( $tab, $args )
				: add_query_arg( array_merge( array( 'tab' => $tab ), $args ), home_url( '/' ) );
			wp_safe_redirect( $url );

			$should_exit = (bool) apply_filters( 'habeq_portal_exit_on_redirect', true, $tab, $args );
			if ( $should_exit ) {
				exit;
			}

			return $url;
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

		/**
		 * Check if signup attempts are rate limited.
		 *
		 * @param string $email Email.
		 * @param string $ip    IP address.
		 * @return bool
		 */
		private static function is_signup_rate_limited( $email, $ip ) {
			$keys = self::get_signup_rate_limit_keys( $email, $ip );
			foreach ( $keys as $key ) {
				if ( $key && get_transient( $key ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Apply signup rate limit.
		 *
		 * @param string $email Email.
		 * @param string $ip    IP address.
		 * @return void
		 */
		private static function set_signup_rate_limit( $email, $ip ) {
			$keys = self::get_signup_rate_limit_keys( $email, $ip );
			foreach ( $keys as $key ) {
				if ( $key ) {
					set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
				}
			}
		}

		/**
		 * Build signup rate limit keys.
		 *
		 * @param string $email Email.
		 * @param string $ip    IP address.
		 * @return string[]
		 */
		private static function get_signup_rate_limit_keys( $email, $ip ) {
			$keys = array();
			if ( $email ) {
				$keys[] = 'habeq_signup_email_' . md5( strtolower( $email ) );
			}
			if ( $ip ) {
				$keys[] = 'habeq_signup_ip_' . md5( $ip );
			}

			return $keys;
		}
	}
}
