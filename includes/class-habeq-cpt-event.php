<?php
/**
 * Event custom post type.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_CPT_Event' ) ) {
	class Habeq_CPT_Event {
		/**
		 * Initialize hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_type' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
			add_action( 'save_post_habeq_event', array( __CLASS__, 'save_meta' ) );
			add_filter( 'manage_habeq_event_posts_columns', array( __CLASS__, 'add_columns' ) );
			add_action( 'manage_habeq_event_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
			add_action( 'admin_menu', array( __CLASS__, 'register_tools_page' ) );
			add_action( 'template_redirect', array( __CLASS__, 'handle_booking_submission' ) );
			add_filter( 'the_content', array( __CLASS__, 'append_booking_box' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ) );
			add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		}

		/**
		 * Register the event post type.
		 *
		 * @return void
		 */
		public static function register_post_type() {
			$labels = array(
				'name'               => __( 'Events', 'habeq' ),
				'singular_name'      => __( 'Event', 'habeq' ),
				'add_new'            => __( 'Add New', 'habeq' ),
				'add_new_item'       => __( 'Add New Event', 'habeq' ),
				'edit_item'          => __( 'Edit Event', 'habeq' ),
				'new_item'           => __( 'New Event', 'habeq' ),
				'view_item'          => __( 'View Event', 'habeq' ),
				'search_items'       => __( 'Search Events', 'habeq' ),
				'not_found'          => __( 'No events found.', 'habeq' ),
				'not_found_in_trash' => __( 'No events found in Trash.', 'habeq' ),
				'all_items'          => __( 'All Events', 'habeq' ),
				'menu_name'          => __( 'Events', 'habeq' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'has_archive'        => true,
				'menu_position'      => 20,
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'            => array( 'slug' => 'events' ),
				'show_in_rest'       => true,
			);

			register_post_type( 'habeq_event', $args );
		}

		/**
		 * Register meta boxes.
		 *
		 * @return void
		 */
		public static function register_meta_boxes() {
			add_meta_box(
				'habeq-event-details',
				__( 'Event Details', 'habeq' ),
				array( __CLASS__, 'render_meta_box' ),
				'habeq_event',
				'normal',
				'default'
			);
		}

		/**
		 * Render the event details meta box.
		 *
		 * @param WP_Post $post Current post object.
		 * @return void
		 */
		public static function render_meta_box( $post ) {
			wp_nonce_field( 'habeq_event_meta', 'habeq_event_meta_nonce' );

			$start_datetime = get_post_meta( $post->ID, '_habeq_start', true );
			$end_datetime   = get_post_meta( $post->ID, '_habeq_end', true );
			$venue_name     = get_post_meta( $post->ID, '_habeq_venue_name', true );
			$venue_address  = get_post_meta( $post->ID, '_habeq_venue_address', true );
			$capacity       = get_post_meta( $post->ID, '_habeq_capacity', true );

			$fields = array(
				'start_datetime' => array(
					'label' => __( 'Start Date/Time', 'habeq' ),
					'value' => $start_datetime,
					'type'  => 'datetime-local',
				),
				'end_datetime'   => array(
					'label' => __( 'End Date/Time', 'habeq' ),
					'value' => $end_datetime,
					'type'  => 'datetime-local',
				),
				'venue_name'     => array(
					'label' => __( 'Venue Name', 'habeq' ),
					'value' => $venue_name,
					'type'  => 'text',
				),
				'venue_address'  => array(
					'label' => __( 'Venue Address', 'habeq' ),
					'value' => $venue_address,
					'type'  => 'text',
				),
				'capacity'       => array(
					'label' => __( 'Capacity', 'habeq' ),
					'value' => $capacity,
					'type'  => 'number',
					'min'   => 0,
				),
			);

			echo '<table class="form-table" role="presentation">';
			foreach ( $fields as $key => $field ) {
				$input_id = 'habeq_' . $key;
				$min_attr = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';

				echo '<tr>';
				echo '<th scope="row"><label for="' . esc_attr( $input_id ) . '">' . esc_html( $field['label'] ) . '</label></th>';
				echo '<td><input class="regular-text" type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $field['value'] ) . '"' . $min_attr . ' /></td>';
				echo '</tr>';
			}
			echo '</table>';
		}

		/**
		 * Save event meta data.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function save_meta( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! isset( $_POST['habeq_event_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['habeq_event_meta_nonce'] ), 'habeq_event_meta' ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$fields = array(
				'_habeq_start'         => array(
					'key'      => 'habeq_start_datetime',
					'sanitize' => 'sanitize_text_field',
				),
				'_habeq_end'           => array(
					'key'      => 'habeq_end_datetime',
					'sanitize' => 'sanitize_text_field',
				),
				'_habeq_venue_name'    => array(
					'key'      => 'habeq_venue_name',
					'sanitize' => 'sanitize_text_field',
				),
				'_habeq_venue_address' => array(
					'key'      => 'habeq_venue_address',
					'sanitize' => 'sanitize_text_field',
				),
				'_habeq_capacity'      => array(
					'key'      => 'habeq_capacity',
					'sanitize' => 'absint',
				),
			);

			foreach ( $fields as $meta_key => $config ) {
				$field_key = $config['key'];
				$raw_value = isset( $_POST[ $field_key ] ) ? wp_unslash( $_POST[ $field_key ] ) : '';
				$is_empty  = '' === trim( (string) $raw_value );
				$value     = call_user_func( $config['sanitize'], $raw_value );

				if ( $is_empty ) {
					delete_post_meta( $post_id, $meta_key );
					continue;
				}

				update_post_meta( $post_id, $meta_key, $value );
			}

			$capacity_raw = isset( $_POST['habeq_capacity'] ) ? wp_unslash( $_POST['habeq_capacity'] ) : '';
			$capacity     = '' === trim( (string) $capacity_raw ) ? 0 : absint( $capacity_raw );
			if ( class_exists( 'Habeq_DB' ) ) {
				Habeq_DB::maybe_ensure_inventory_row( $post_id, $capacity );
			}
		}

		/**
		 * Add admin columns.
		 *
		 * @param array $columns Columns list.
		 * @return array
		 */
		public static function add_columns( $columns ) {
			$columns['habeq_start']    = __( 'Start', 'habeq' );
			$columns['habeq_venue']    = __( 'Venue', 'habeq' );
			$columns['habeq_capacity'] = __( 'Capacity', 'habeq' );

			return $columns;
		}

		/**
		 * Render admin column values.
		 *
		 * @param string $column  Column key.
		 * @param int    $post_id Post ID.
		 * @return void
		 */
		public static function render_columns( $column, $post_id ) {
			switch ( $column ) {
				case 'habeq_start':
					$start = get_post_meta( $post_id, '_habeq_start', true );
					echo $start ? esc_html( $start ) : '&#8212;';
					break;
				case 'habeq_venue':
					$venue = get_post_meta( $post_id, '_habeq_venue_name', true );
					echo $venue ? esc_html( $venue ) : '&#8212;';
					break;
				case 'habeq_capacity':
					$capacity = get_post_meta( $post_id, '_habeq_capacity', true );
					echo '' !== $capacity ? esc_html( $capacity ) : '&#8212;';
					break;
			}
		}

		/**
		 * Register admin tools page under Events.
		 *
		 * @return void
		 */
		public static function register_tools_page() {
			add_submenu_page(
				'edit.php?post_type=habeq_event',
				__( 'Event Tools', 'habeq' ),
				__( 'Tools', 'habeq' ),
				'manage_options',
				'habeq-event-tools',
				array( __CLASS__, 'render_tools_page' )
			);
		}

		/**
		 * Render the admin tools page.
		 *
		 * @return void
		 */
		public static function render_tools_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'habeq' ) );
			}

			$message = '';
			$error   = '';

			if ( isset( $_POST['habeq_tools_action'] ) && 'create_booking' === $_POST['habeq_tools_action'] ) {
				check_admin_referer( 'habeq_tools_booking', 'habeq_tools_nonce' );

				$event_id = isset( $_POST['habeq_event_id'] ) ? absint( wp_unslash( $_POST['habeq_event_id'] ) ) : 0;
				$name     = isset( $_POST['habeq_name'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_name'] ) ) : '';
				$email    = isset( $_POST['habeq_email'] ) ? sanitize_email( wp_unslash( $_POST['habeq_email'] ) ) : '';
				$qty      = isset( $_POST['habeq_qty'] ) ? absint( wp_unslash( $_POST['habeq_qty'] ) ) : 1;

				if ( ! class_exists( 'Habeq_Bookings' ) ) {
					$error = __( 'Booking service is unavailable.', 'habeq' );
				} else {
					$result = Habeq_Bookings::create_booking( $event_id, $name, $email, $qty, get_current_user_id() );
					if ( is_wp_error( $result ) ) {
						$error = $result->get_error_message();
					} else {
						$message = sprintf(
							/* translators: %d: booking ID */
							__( 'Booking created with ID %d.', 'habeq' ),
							$result
						);
					}
				}
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Event Tools', 'habeq' ); ?></h1>
				<?php if ( $message ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
				<?php elseif ( $error ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
				<?php endif; ?>

				<form method="post">
					<?php wp_nonce_field( 'habeq_tools_booking', 'habeq_tools_nonce' ); ?>
					<input type="hidden" name="habeq_tools_action" value="create_booking" />
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="habeq_event_id"><?php esc_html_e( 'Event ID', 'habeq' ); ?></label></th>
							<td><input type="number" class="small-text" name="habeq_event_id" id="habeq_event_id" min="1" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="habeq_name"><?php esc_html_e( 'Name', 'habeq' ); ?></label></th>
							<td><input type="text" class="regular-text" name="habeq_name" id="habeq_name" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="habeq_email"><?php esc_html_e( 'Email', 'habeq' ); ?></label></th>
							<td><input type="email" class="regular-text" name="habeq_email" id="habeq_email" required /></td>
						</tr>
						<tr>
							<th scope="row"><label for="habeq_qty"><?php esc_html_e( 'Quantity', 'habeq' ); ?></label></th>
							<td><input type="number" class="small-text" name="habeq_qty" id="habeq_qty" min="1" value="1" required /></td>
						</tr>
					</table>
					<?php submit_button( __( 'Create Test Booking', 'habeq' ) ); ?>
				</form>
			</div>
			<?php
		}

	/**
	 * Register query vars for booking responses.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = 'habeq_booking';
		$vars[] = 'habeq_message';
		return $vars;
	}

	/**
	 * Enqueue public assets on single event pages.
	 *
	 * @return void
	 */
	public static function enqueue_public_assets() {
		if ( is_singular( 'habeq_event' ) ) {
			wp_enqueue_style(
				'habeq-events',
				HABEQ_URL . 'assets/habeq-events.css',
				array(),
				HABEQ_VERSION
			);
		}
	}

	/**
	 * Append booking form to single event content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function append_booking_box( $content ) {
		if ( ! is_singular( 'habeq_event' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$booking_box = self::get_booking_box_markup( get_the_ID() );
		if ( '' === $booking_box ) {
			return $content;
		}

		return $content . $booking_box;
	}

	/**
	 * Handle booking submissions from the public form.
	 *
	 * @return void
	 */
	public static function handle_booking_submission() {
		if ( ! is_singular( 'habeq_event' ) ) {
			return;
		}

		if ( empty( $_POST['habeq_booking_action'] ) || 'submit_booking' !== $_POST['habeq_booking_action'] ) {
			return;
		}

		if ( ! isset( $_POST['habeq_booking_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['habeq_booking_nonce'] ), 'habeq_booking' ) ) {
			self::redirect_with_message( 'error', __( 'Security check failed. Please try again.', 'habeq' ) );
		}

		$event_id = get_the_ID();
		$name     = isset( $_POST['habeq_name'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_name'] ) ) : '';
		$email    = isset( $_POST['habeq_email'] ) ? sanitize_email( wp_unslash( $_POST['habeq_email'] ) ) : '';
		$qty      = isset( $_POST['habeq_qty'] ) ? absint( wp_unslash( $_POST['habeq_qty'] ) ) : 0;
		$honeypot = isset( $_POST['habeq_company'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_company'] ) ) : '';

		if ( '' !== $honeypot ) {
			self::redirect_with_message( 'error', __( 'Please leave the extra field blank.', 'habeq' ) );
		}

		if ( '' === $name || '' === $email ) {
			self::redirect_with_message( 'error', __( 'Name and email are required.', 'habeq' ) );
		}

		if ( $qty < 1 ) {
			self::redirect_with_message( 'error', __( 'Please enter a valid quantity.', 'habeq' ) );
		}

		$rate_key = 'habeq_booking_' . $event_id . '_' . md5( strtolower( $email ) );
		if ( get_transient( $rate_key ) ) {
			self::redirect_with_message( 'error', __( 'Please wait a moment before booking again for this event.', 'habeq' ) );
		}

		set_transient( $rate_key, 1, 2 * MINUTE_IN_SECONDS );

		if ( ! class_exists( 'Habeq_Bookings' ) ) {
			self::redirect_with_message( 'error', __( 'Booking service is unavailable. Please try again later.', 'habeq' ) );
		}

		$result = Habeq_Bookings::create_booking( $event_id, $name, $email, $qty );
		if ( is_wp_error( $result ) ) {
			self::redirect_with_message( 'error', $result->get_error_message() );
		}

		self::send_booking_emails( $event_id, $name, $email, $qty, $result );

		self::redirect_with_message( 'success', __( 'Your booking was received. Check your email for confirmation.', 'habeq' ) );
	}

	/**
	 * Build booking form markup.
	 *
	 * @param int $event_id Event ID.
	 * @return string
	 */
	private static function get_booking_box_markup( $event_id ) {
		if ( ! class_exists( 'Habeq_DB' ) ) {
			return '';
		}

		$inventory = Habeq_DB::get_inventory( $event_id );
		$capacity  = $inventory ? $inventory['capacity'] : absint( get_post_meta( $event_id, '_habeq_capacity', true ) );
		$reserved  = $inventory ? $inventory['reserved'] : 0;
		$remaining = max( 0, $capacity - $reserved );

		$status  = get_query_var( 'habeq_booking' );
		$message = get_query_var( 'habeq_message' );
		$message = $message ? rawurldecode( $message ) : '';

		$success = 'success' === $status;
		$error   = ( 'error' === $status ) ? $message : '';

		ob_start();
		$template_path = HABEQ_PATH . 'templates/booking-form.php';
		if ( file_exists( $template_path ) ) {
			$booking_error   = $error;
			$booking_success = $success ? $message : '';
			require $template_path;
		}
		return ob_get_clean();
	}

	/**
	 * Redirect after form submission with status and message.
	 *
	 * @param string $status  Status key.
	 * @param string $message Message text.
	 * @return void
	 */
	private static function redirect_with_message( $status, $message ) {
		$url = add_query_arg(
			array(
				'habeq_booking' => $status,
				'habeq_message' => rawurlencode( $message ),
			),
			get_permalink()
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Send booking confirmation emails.
	 *
	 * @param int    $event_id  Event ID.
	 * @param string $name      Booker name.
	 * @param string $email     Booker email.
	 * @param int    $qty       Quantity.
	 * @param int    $booking_id Booking ID.
	 * @return void
	 */
	private static function send_booking_emails( $event_id, $name, $email, $qty, $booking_id ) {
		$event_title = get_the_title( $event_id );
		$event_link  = get_permalink( $event_id );
		$admin_email = get_option( 'admin_email' );

		$subject_user = sprintf(
			/* translators: %s: event title */
			__( 'Your booking for %s', 'habeq' ),
			$event_title
		);
		$message_user = sprintf(
			/* translators: 1: name, 2: event title, 3: quantity, 4: event link */
			__( "Hi %1\$s,\n\nThanks for your booking for %2\$s.\nQuantity: %3\$d\nEvent link: %4\$s\n\nWe will contact you with updates.", 'habeq' ),
			$name,
			$event_title,
			$qty,
			$event_link
		);

		wp_mail( $email, $subject_user, $message_user );

		$subject_admin = sprintf(
			/* translators: %s: event title */
			__( 'New booking for %s', 'habeq' ),
			$event_title
		);
		$message_admin = sprintf(
			/* translators: 1: event title, 2: name, 3: email, 4: quantity, 5: booking id */
			__( "New booking received for %1\$s.\nName: %2\$s\nEmail: %3\$s\nQuantity: %4\$d\nBooking ID: %5\$d", 'habeq' ),
			$event_title,
			$name,
			$email,
			$qty,
			$booking_id
		);

		wp_mail( $admin_email, $subject_admin, $message_admin );
	}
}
