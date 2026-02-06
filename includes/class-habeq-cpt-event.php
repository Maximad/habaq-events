<?php
/**
 * Event custom post type.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_CPT_Event' ) ) {
	/**
	 * Event custom post type handlers.
	 *
	 * @package Habaq_Events
	 */
	class Habeq_CPT_Event {
		/**
		 * Initialize hooks.
		 *
		 * @return void
		 */
		public static function init() {
			self::register_post_type();
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
			add_action( 'save_post_habeq_event', array( __CLASS__, 'save_meta' ) );
			add_filter( 'manage_habeq_event_posts_columns', array( __CLASS__, 'add_columns' ) );
			add_action( 'manage_habeq_event_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
			add_action( 'admin_menu', array( __CLASS__, 'register_tools_page' ) );
			add_action( 'template_redirect', array( __CLASS__, 'handle_booking_submission' ) );
			add_filter( 'the_content', array( __CLASS__, 'append_booking_box' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ) );
			add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
			add_shortcode( 'habeq_organizer_dashboard', array( __CLASS__, 'render_organizer_dashboard' ) );
			add_action( 'admin_menu', array( __CLASS__, 'register_bookings_page' ) );
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
				'labels'          => $labels,
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'has_archive'     => true,
				'menu_position'   => 20,
				'menu_icon'       => 'dashicons-calendar',
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'         => array( 'slug' => 'events' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'habeq_event', 'habeq_events' ),
				'map_meta_cap'    => true,
			);

			register_post_type( 'habeq_event', $args );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! post_type_exists( 'habeq_event' ) ) {
				error_log( 'Habaq Events: Failed to register habeq_event post type.' );
			}
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
		 * Register bookings admin page.
		 *
		 * @return void
		 */
		public static function register_bookings_page() {
			add_submenu_page(
				'edit.php?post_type=habeq_event',
				__( 'Bookings', 'habeq' ),
				__( 'Bookings', 'habeq' ),
				'edit_habeq_events',
				'habeq-event-bookings',
				array( __CLASS__, 'render_bookings_page' )
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

			$settings_action = '';
			if ( isset( $_POST['habeq_tools_settings_action'] ) ) {
				$settings_action = sanitize_key( wp_unslash( $_POST['habeq_tools_settings_action'] ) );
			}
			if ( 'save_settings' === $settings_action ) {
				check_admin_referer( 'habeq_tools_settings', 'habeq_tools_settings_nonce' );
				$trusted = ! empty( $_POST['habeq_trusted_autopublish'] ) ? '1' : '0';
				update_option( 'habeq_trusted_autopublish', $trusted );
				$allow_signup = ! empty( $_POST['habeq_allow_signup'] ) ? '1' : '0';
				$require_approval = ! empty( $_POST['habeq_require_approval'] ) ? '1' : '0';
				$autopublish_approved = ! empty( $_POST['habeq_autopublish_approved'] ) ? '1' : '0';
				update_option( 'habeq_allow_signup', $allow_signup );
				update_option( 'habeq_require_approval', $require_approval );
				update_option( 'habeq_autopublish_approved', $autopublish_approved );
				$message = __( 'Settings updated.', 'habeq' );
			}

			$tools_action = '';
			if ( isset( $_POST['habeq_tools_action'] ) ) {
				$tools_action = sanitize_key( wp_unslash( $_POST['habeq_tools_action'] ) );
			}
			if ( 'create_booking' === $tools_action ) {
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

				<h2><?php esc_html_e( 'Organizer Settings', 'habeq' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'habeq_tools_settings', 'habeq_tools_settings_nonce' ); ?>
					<input type="hidden" name="habeq_tools_settings_action" value="save_settings" />
					<label>
						<input type="checkbox" name="habeq_trusted_autopublish" value="1" <?php checked( self::trusted_autopublish_enabled() ); ?> />
						<?php esc_html_e( 'Trusted organizers can auto-publish', 'habeq' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="habeq_allow_signup" value="1" <?php checked( '1' === (string) get_option( 'habeq_allow_signup', '1' ) ); ?> />
						<?php esc_html_e( 'Allow organizer signup', 'habeq' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="habeq_require_approval" value="1" <?php checked( '1' === (string) get_option( 'habeq_require_approval', '1' ) ); ?> />
						<?php esc_html_e( 'Require admin approval for organizers', 'habeq' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="habeq_autopublish_approved" value="1" <?php checked( '1' === (string) get_option( 'habeq_autopublish_approved', '0' ) ); ?> />
						<?php esc_html_e( 'Auto-publish events for approved organizers', 'habeq' ); ?>
					</label>
					<?php submit_button( __( 'Save Settings', 'habeq' ) ); ?>
				</form>

				<hr />

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
		 * Shortcode for organizer dashboard.
		 *
		 * @return string
		 */
		public static function render_organizer_dashboard() {
			if ( ! is_user_logged_in() ) {
				return '<p>' . esc_html__( 'Please log in to manage your events.', 'habeq' ) . '</p>';
			}

			if ( ! current_user_can( 'edit_habeq_events' ) ) {
				return '<p>' . esc_html__( 'You do not have permission to manage events.', 'habeq' ) . '</p>';
			}

			$user_id = get_current_user_id();
			$message = '';
			$error   = '';

			$dashboard_action = '';
			if ( isset( $_POST['habeq_dashboard_action'] ) ) {
				$dashboard_action = sanitize_key( wp_unslash( $_POST['habeq_dashboard_action'] ) );
			}
			if ( 'create_event' === $dashboard_action ) {
				check_admin_referer( 'habeq_dashboard_event', 'habeq_dashboard_nonce' );

				$title   = isset( $_POST['habeq_event_title'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_event_title'] ) ) : '';
				$content = isset( $_POST['habeq_event_description'] ) ? wp_kses_post( wp_unslash( $_POST['habeq_event_description'] ) ) : '';
				$excerpt = isset( $_POST['habeq_event_excerpt'] ) ? wp_kses_post( wp_unslash( $_POST['habeq_event_excerpt'] ) ) : '';

				$start_datetime = isset( $_POST['habeq_start_datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_start_datetime'] ) ) : '';
				$end_datetime   = isset( $_POST['habeq_end_datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_end_datetime'] ) ) : '';
				$venue_name     = isset( $_POST['habeq_venue_name'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_venue_name'] ) ) : '';
				$venue_address  = isset( $_POST['habeq_venue_address'] ) ? sanitize_text_field( wp_unslash( $_POST['habeq_venue_address'] ) ) : '';
				$capacity       = isset( $_POST['habeq_capacity'] ) ? absint( wp_unslash( $_POST['habeq_capacity'] ) ) : 0;

				if ( '' === $title ) {
					$error = __( 'Event title is required.', 'habeq' );
				} else {
					$post_status = self::trusted_autopublish_enabled() ? 'publish' : 'pending';
					$post_id     = wp_insert_post(
						array(
							'post_type'    => 'habeq_event',
							'post_title'   => $title,
							'post_content' => $content,
							'post_excerpt' => $excerpt,
							'post_status'  => $post_status,
							'post_author'  => $user_id,
						),
						true
					);

					if ( is_wp_error( $post_id ) ) {
						$error = $post_id->get_error_message();
					} else {
						update_post_meta( $post_id, '_habeq_start', $start_datetime );
						update_post_meta( $post_id, '_habeq_end', $end_datetime );
						update_post_meta( $post_id, '_habeq_venue_name', $venue_name );
						update_post_meta( $post_id, '_habeq_venue_address', $venue_address );
						update_post_meta( $post_id, '_habeq_capacity', $capacity );

						if ( class_exists( 'Habeq_DB' ) ) {
							Habeq_DB::maybe_ensure_inventory_row( $post_id, $capacity );
						}

						$message = self::trusted_autopublish_enabled()
							? __( 'Event published successfully.', 'habeq' )
							: __( 'Event submitted and pending review.', 'habeq' );
					}
				}
			}

			$events = new WP_Query(
				array(
					'post_type'      => 'habeq_event',
					'author'         => $user_id,
					'posts_per_page' => 20,
					'post_status'    => array( 'publish', 'pending', 'draft' ),
				)
			);

			ob_start();
			?>
			<div class="habeq-organizer-dashboard">
				<h2><?php esc_html_e( 'Your Events', 'habeq' ); ?></h2>
				<?php if ( $message ) : ?>
					<div class="habeq-booking-message habeq-booking-success"><?php echo esc_html( $message ); ?></div>
				<?php elseif ( $error ) : ?>
					<div class="habeq-booking-message habeq-booking-error"><?php echo esc_html( $error ); ?></div>
				<?php endif; ?>

				<?php if ( $events->have_posts() ) : ?>
					<ul class="habeq-organizer-events">
						<?php while ( $events->have_posts() ) : $events->the_post(); ?>
							<li>
								<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
								<span class="habeq-status"><?php echo esc_html( ucfirst( get_post_status() ) ); ?></span>
								<?php if ( get_edit_post_link() ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="habeq-edit-link">
										<?php esc_html_e( 'Edit', 'habeq' ); ?>
									</a>
								<?php endif; ?>
							</li>
						<?php endwhile; ?>
					</ul>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'You have not created any events yet.', 'habeq' ); ?></p>
				<?php endif; ?>

				<h2><?php esc_html_e( 'Create New Event', 'habeq' ); ?></h2>
				<form method="post" class="habeq-organizer-form">
					<?php wp_nonce_field( 'habeq_dashboard_event', 'habeq_dashboard_nonce' ); ?>
					<input type="hidden" name="habeq_dashboard_action" value="create_event" />

					<p class="habeq-field">
						<label for="habeq_event_title"><?php esc_html_e( 'Event Title', 'habeq' ); ?></label>
						<input type="text" name="habeq_event_title" id="habeq_event_title" required />
					</p>
					<p class="habeq-field">
						<label for="habeq_event_description"><?php esc_html_e( 'Description', 'habeq' ); ?></label>
						<textarea name="habeq_event_description" id="habeq_event_description" rows="5"></textarea>
					</p>
					<p class="habeq-field">
						<label for="habeq_event_excerpt"><?php esc_html_e( 'Excerpt', 'habeq' ); ?></label>
						<textarea name="habeq_event_excerpt" id="habeq_event_excerpt" rows="3"></textarea>
					</p>

					<p class="habeq-field">
						<label for="habeq_start_datetime"><?php esc_html_e( 'Start Date/Time', 'habeq' ); ?></label>
						<input type="datetime-local" name="habeq_start_datetime" id="habeq_start_datetime" />
					</p>
					<p class="habeq-field">
						<label for="habeq_end_datetime"><?php esc_html_e( 'End Date/Time', 'habeq' ); ?></label>
						<input type="datetime-local" name="habeq_end_datetime" id="habeq_end_datetime" />
					</p>
					<p class="habeq-field">
						<label for="habeq_venue_name"><?php esc_html_e( 'Venue Name', 'habeq' ); ?></label>
						<input type="text" name="habeq_venue_name" id="habeq_venue_name" />
					</p>
					<p class="habeq-field">
						<label for="habeq_venue_address"><?php esc_html_e( 'Venue Address', 'habeq' ); ?></label>
						<input type="text" name="habeq_venue_address" id="habeq_venue_address" />
					</p>
					<p class="habeq-field">
						<label for="habeq_capacity"><?php esc_html_e( 'Capacity', 'habeq' ); ?></label>
						<input type="number" name="habeq_capacity" id="habeq_capacity" min="0" value="0" />
					</p>

					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Submit Event', 'habeq' ); ?>
					</button>
				</form>
			</div>
			<?php

			return ob_get_clean();
		}

		/**
		 * Check if trusted auto-publish is enabled.
		 *
		 * @return bool
		 */
		private static function trusted_autopublish_enabled() {
			return '1' === get_option( 'habeq_trusted_autopublish', '0' );
		}

		/**
		 * Render bookings list page.
		 *
		 * @return void
		 */
		public static function render_bookings_page() {
			if ( ! current_user_can( 'edit_habeq_events' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'habeq' ) );
			}

			$user_id      = get_current_user_id();
			$is_admin     = current_user_can( 'manage_options' );
			$event_filter = isset( $_GET['event_id'] ) ? absint( wp_unslash( $_GET['event_id'] ) ) : 0;

			$event_ids = $is_admin ? array() : self::get_user_event_ids( $user_id );
			if ( ! $is_admin && $event_filter && ! in_array( $event_filter, $event_ids, true ) ) {
				$event_filter = 0;
			}

			$export_action = '';
			if ( isset( $_GET['habeq_export'] ) ) {
				$export_action = sanitize_key( wp_unslash( $_GET['habeq_export'] ) );
			}
			if ( 'csv' === $export_action ) {
				check_admin_referer( 'habeq_export_bookings', 'habeq_export_nonce' );
				self::export_bookings_csv( $event_filter, $event_ids, $is_admin );
			}

			$bookings = self::get_bookings_list( $event_filter, $event_ids, $is_admin );
			$events   = self::get_events_for_filter( $event_ids, $is_admin );

			$export_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'         => 'habeq-event-bookings',
						'post_type'    => 'habeq_event',
						'event_id'     => $event_filter,
						'habeq_export' => 'csv',
					),
					admin_url( 'edit.php' )
				),
				'habeq_export_bookings',
				'habeq_export_nonce'
			);
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Bookings', 'habeq' ); ?></h1>

				<form method="get">
					<input type="hidden" name="page" value="habeq-event-bookings" />
					<input type="hidden" name="post_type" value="habeq_event" />
					<label for="habeq_event_filter"><?php esc_html_e( 'Filter by event', 'habeq' ); ?></label>
					<select id="habeq_event_filter" name="event_id">
						<option value="0"><?php esc_html_e( 'All events', 'habeq' ); ?></option>
						<?php foreach ( $events as $event ) : ?>
							<option value="<?php echo esc_attr( $event['id'] ); ?>" <?php selected( $event_filter, $event['id'] ); ?>>
								<?php echo esc_html( $event['title'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php submit_button( __( 'Filter', 'habeq' ), 'secondary', '', false ); ?>
					<a class="button" href="<?php echo esc_url( $export_url ); ?>">
						<?php esc_html_e( 'Export CSV', 'habeq' ); ?>
					</a>
				</form>

				<table class="widefat striped" style="margin-top: 1rem;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'habeq' ); ?></th>
							<th><?php esc_html_e( 'Email', 'habeq' ); ?></th>
							<th><?php esc_html_e( 'Qty', 'habeq' ); ?></th>
							<th><?php esc_html_e( 'Status', 'habeq' ); ?></th>
							<th><?php esc_html_e( 'Created', 'habeq' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $bookings ) : ?>
							<?php foreach ( $bookings as $booking ) : ?>
								<tr>
									<td><?php echo esc_html( $booking['name'] ); ?></td>
									<td><?php echo esc_html( $booking['email'] ); ?></td>
									<td><?php echo esc_html( $booking['qty'] ); ?></td>
									<td><?php echo esc_html( $booking['status'] ); ?></td>
									<td><?php echo esc_html( $booking['created_at'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'No bookings found.', 'habeq' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Fetch bookings list for the admin table.
		 *
		 * @param int   $event_filter Event ID filter.
		 * @param array $event_ids    Allowed event IDs.
		 * @param bool  $is_admin     Whether user is admin.
		 * @return array
		 */
		private static function get_bookings_list( $event_filter, $event_ids, $is_admin ) {
			if ( ! class_exists( 'Habeq_DB' ) ) {
				return array();
			}

			global $wpdb;
			$tables = Habeq_DB::get_table_names();

			$where   = '1=1';
			$params  = array();
			$filters = array();

			if ( $event_filter ) {
				$filters[] = 'event_id = %d';
				$params[]  = $event_filter;
			}

			if ( ! $is_admin ) {
				if ( empty( $event_ids ) ) {
					return array();
				}
				$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
				$filters[]    = "event_id IN ($placeholders)";
				$params       = array_merge( $params, $event_ids );
			}

			if ( $filters ) {
				$where = implode( ' AND ', $filters );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is internal.
			$sql = "SELECT name, email, qty, status, created_at FROM {$tables['bookings']} WHERE {$where} ORDER BY created_at DESC";
			$prepared = $params ? $wpdb->prepare( $sql, $params ) : $sql;
			$rows = $wpdb->get_results( $prepared, ARRAY_A );

			return array_map(
				static function ( $row ) {
					return array(
						'name'       => $row['name'],
						'email'      => $row['email'],
						'qty'        => absint( $row['qty'] ),
						'status'     => $row['status'],
						'created_at' => $row['created_at'],
					);
				},
				$rows
			);
		}

		/**
		 * Export bookings as CSV.
		 *
		 * @param int   $event_filter Event ID filter.
		 * @param array $event_ids    Allowed event IDs.
		 * @param bool  $is_admin     Whether user is admin.
		 * @return void
		 */
		private static function export_bookings_csv( $event_filter, $event_ids, $is_admin ) {
			$bookings = self::get_bookings_list( $event_filter, $event_ids, $is_admin );

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=habeq-bookings.csv' );

			$output = fopen( 'php://output', 'w' );
			fputcsv( $output, array( 'Name', 'Email', 'Qty', 'Status', 'Created' ) );
			foreach ( $bookings as $booking ) {
				fputcsv(
					$output,
					array(
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
		 * Get event IDs for the current organizer.
		 *
		 * @param int $user_id User ID.
		 * @return array
		 */
		private static function get_user_event_ids( $user_id ) {
			$events = new WP_Query(
				array(
					'post_type'      => 'habeq_event',
					'author'         => $user_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post_status'    => array( 'publish', 'pending', 'draft' ),
				)
			);

			return array_map( 'absint', $events->posts );
		}

		/**
		 * Get events for filter dropdown.
		 *
		 * @param array $event_ids Allowed event IDs.
		 * @param bool  $is_admin  Whether user is admin.
		 * @return array
		 */
		private static function get_events_for_filter( $event_ids, $is_admin ) {
			$args = array(
				'post_type'      => 'habeq_event',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
			);

			if ( ! $is_admin ) {
				if ( empty( $event_ids ) ) {
					return array();
				}
				$args['post__in'] = $event_ids;
			}

			$query = new WP_Query( $args );
			$events = array();

			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post ) {
					$events[] = array(
						'id'    => absint( $post->ID ),
						'title' => get_the_title( $post->ID ),
					);
				}
			}

			return $events;
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

			$booking_action = '';
			if ( isset( $_POST['habeq_booking_action'] ) ) {
				$booking_action = sanitize_key( wp_unslash( $_POST['habeq_booking_action'] ) );
			}
			if ( 'submit_booking' !== $booking_action ) {
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
			$is_staging  = function_exists( 'habeq_is_staging_mode' ) ? habeq_is_staging_mode() : false;
			$prefix      = $is_staging ? '[STAGING] ' : '';

			$subject_user = sprintf(
				/* translators: %s: event title */
				__( 'Your booking for %s', 'habeq' ),
				$event_title
			);
			$subject_user = $prefix . $subject_user;
			$message_user = sprintf(
				/* translators: 1: name, 2: event title, 3: quantity, 4: event link */
				__( "Hi %1\$s,\n\nThanks for your booking for %2\$s.\nQuantity: %3\$d\nEvent link: %4\$s\n\nWe will contact you with updates.", 'habeq' ),
				$name,
			$event_title,
			$qty,
				$event_link
			);

			if ( ! $is_staging ) {
				wp_mail( $email, $subject_user, $message_user );
			}

			$subject_admin = sprintf(
				/* translators: %s: event title */
				__( 'New booking for %s', 'habeq' ),
				$event_title
			);
			$subject_admin = $prefix . $subject_admin;
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
}
