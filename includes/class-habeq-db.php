<?php
/**
 * Database schema and helpers.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_DB' ) ) {
	class Habeq_DB {
		/**
		 * Schema version.
		 */
		const DB_VERSION = '1.0.0';

		/**
		 * Get table names with prefixes.
		 *
		 * @return array
		 */
		public static function get_table_names() {
			global $wpdb;

			return array(
				'inventory' => $wpdb->prefix . 'habeq_inventory',
				'bookings'  => $wpdb->prefix . 'habeq_bookings',
			);
		}

		/**
		 * Create or upgrade plugin tables.
		 *
		 * @return void
		 */
		public static function create_or_upgrade_tables() {
			global $wpdb;

			$current_version = get_option( 'habeq_db_version', '' );
			if ( self::DB_VERSION === $current_version ) {
				return;
			}

			$charset_collate = $wpdb->get_charset_collate();
			$tables          = self::get_table_names();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_inventory = "CREATE TABLE {$tables['inventory']} (
				event_id BIGINT(20) UNSIGNED NOT NULL,
				capacity INT UNSIGNED NOT NULL DEFAULT 0,
				reserved INT UNSIGNED NOT NULL DEFAULT 0,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (event_id)
			) ENGINE=InnoDB {$charset_collate};";

			$sql_bookings = "CREATE TABLE {$tables['bookings']} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED NULL,
				name VARCHAR(190) NOT NULL,
				email VARCHAR(190) NOT NULL,
				qty INT UNSIGNED NOT NULL DEFAULT 1,
				status VARCHAR(20) NOT NULL DEFAULT 'reserved',
				payment_status VARCHAR(20) NOT NULL DEFAULT 'none',
				payment_provider VARCHAR(50) NULL,
				payment_ref VARCHAR(190) NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				KEY event_id (event_id),
				KEY user_id (user_id),
				KEY email (email),
				KEY status (status),
				KEY payment_status (payment_status),
				KEY event_email (event_id, email)
			) ENGINE=InnoDB {$charset_collate};";

			dbDelta( $sql_inventory );
			dbDelta( $sql_bookings );

			update_option( 'habeq_db_version', self::DB_VERSION );
		}

		/**
		 * Ensure inventory row exists and sync capacity.
		 *
		 * @param int $event_id Event ID.
		 * @param int $capacity Capacity to set.
		 * @return void
		 */
		public static function maybe_ensure_inventory_row( $event_id, $capacity ) {
			global $wpdb;

			$event_id = absint( $event_id );
			$capacity = max( 0, absint( $capacity ) );

			if ( 0 === $event_id ) {
				return;
			}

			$tables = self::get_table_names();
			$table  = $tables['inventory'];

			$existing = $wpdb->get_var(
				$wpdb->prepare( "SELECT event_id FROM {$table} WHERE event_id = %d", $event_id )
			);

			if ( $existing ) {
				$wpdb->update(
					$table,
					array(
						'capacity'   => $capacity,
						'updated_at' => current_time( 'mysql', true ),
					),
					array( 'event_id' => $event_id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				return;
			}

			$wpdb->insert(
				$table,
				array(
					'event_id'   => $event_id,
					'capacity'   => $capacity,
					'reserved'   => 0,
					'updated_at' => current_time( 'mysql', true ),
				),
				array( '%d', '%d', '%d', '%s' )
			);
		}

		/**
		 * Get inventory data for an event.
		 *
		 * @param int $event_id Event ID.
		 * @return array|null
		 */
		public static function get_inventory( $event_id ) {
			global $wpdb;

			$event_id = absint( $event_id );
			if ( 0 === $event_id ) {
				return null;
			}

			$tables = self::get_table_names();
			$table  = $tables['inventory'];

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT event_id, capacity, reserved FROM {$table} WHERE event_id = %d",
					$event_id
				),
				ARRAY_A
			);

			if ( ! $row ) {
				return null;
			}

			return array(
				'event_id' => absint( $row['event_id'] ),
				'capacity' => absint( $row['capacity'] ),
				'reserved' => absint( $row['reserved'] ),
			);
		}
	}
}
