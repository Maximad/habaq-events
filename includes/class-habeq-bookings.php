<?php
/**
 * Booking service.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Bookings' ) ) {
	class Habeq_Bookings {
		/**
		 * Create a booking and reserve inventory.
		 *
		 * @param int         $event_id Event ID.
		 * @param string      $name     Booker name.
		 * @param string      $email    Booker email.
		 * @param int         $qty      Quantity.
		 * @param int|null    $user_id  User ID.
		 * @return int|WP_Error
		 */
		public static function create_booking( $event_id, $name, $email, $qty, $user_id = null ) {
			global $wpdb;

			$event_id = absint( $event_id );
			$qty      = max( 1, absint( $qty ) );
			$name     = sanitize_text_field( $name );
			$email    = sanitize_email( $email );
			$user_id  = null === $user_id ? null : absint( $user_id );

			if ( 0 === $event_id ) {
				return new WP_Error( 'invalid_event', __( 'Invalid event.', 'habeq' ) );
			}

			if ( '' === $name || '' === $email ) {
				return new WP_Error( 'invalid_customer', __( 'Name and email are required.', 'habeq' ) );
			}

			if ( class_exists( 'Habeq_DB' ) ) {
				Habeq_DB::maybe_ensure_inventory_row( $event_id, 0 );
			} else {
				return new WP_Error( 'missing_db', __( 'Database service is unavailable.', 'habeq' ) );
			}

			$tables = Habeq_DB::get_table_names();
			$now    = current_time( 'mysql', true );

			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tables['inventory']} SET reserved = reserved + %d, updated_at = %s WHERE event_id = %d AND reserved + %d <= capacity",
					$qty,
					$now,
					$event_id,
					$qty
				)
			);

			if ( 1 !== $updated ) {
				return new WP_Error( 'sold_out', __( 'Not enough capacity available.', 'habeq' ) );
			}

			$inserted = $wpdb->insert(
				$tables['bookings'],
				array(
					'event_id'         => $event_id,
					'user_id'          => $user_id,
					'name'             => $name,
					'email'            => $email,
					'qty'              => $qty,
					'status'           => 'reserved',
					'payment_status'   => 'none',
					'payment_provider' => null,
					'payment_ref'      => null,
					'created_at'       => $now,
					'updated_at'       => $now,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $inserted ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$tables['inventory']} SET reserved = reserved - %d, updated_at = %s WHERE event_id = %d",
						$qty,
						$now,
						$event_id
					)
				);

				return new WP_Error( 'booking_failed', __( 'Unable to create booking.', 'habeq' ) );
			}

			return (int) $wpdb->insert_id;
		}

		/**
		 * Cancel a booking and release inventory.
		 *
		 * @param int $booking_id Booking ID.
		 * @return true|WP_Error
		 */
		public static function cancel_booking( $booking_id ) {
			global $wpdb;

			$booking_id = absint( $booking_id );
			if ( 0 === $booking_id ) {
				return new WP_Error( 'invalid_booking', __( 'Invalid booking.', 'habeq' ) );
			}

			if ( ! class_exists( 'Habeq_DB' ) ) {
				return new WP_Error( 'missing_db', __( 'Database service is unavailable.', 'habeq' ) );
			}

			$tables = Habeq_DB::get_table_names();

			$booking = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, event_id, qty, status FROM {$tables['bookings']} WHERE id = %d",
					$booking_id
				)
			);

			if ( ! $booking ) {
				return new WP_Error( 'not_found', __( 'Booking not found.', 'habeq' ) );
			}

			if ( 'cancelled' === $booking->status ) {
				return true;
			}

			$now = current_time( 'mysql', true );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tables['bookings']} SET status = %s, updated_at = %s WHERE id = %d",
					'cancelled',
					$now,
					$booking_id
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tables['inventory']} SET reserved = GREATEST(reserved - %d, 0), updated_at = %s WHERE event_id = %d",
					absint( $booking->qty ),
					$now,
					absint( $booking->event_id )
				)
			);

			return true;
		}
	}
}
