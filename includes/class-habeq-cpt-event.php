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
	}
}
