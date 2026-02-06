<?php
/**
 * Diagnostics status page.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Status' ) ) {
	/**
	 * Status page class.
	 *
	 * @package Habaq_Events
	 */
	class Habeq_Status {
		/**
		 * Register hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_status_page' ) );
		}

		/**
		 * Register status page under Tools.
		 *
		 * @return void
		 */
		public static function register_status_page() {
			add_submenu_page(
				'tools.php',
				__( 'Habaq Events Status', 'habeq' ),
				__( 'Habaq Events Status', 'habeq' ),
				'manage_options',
				'habeq-events-status',
				array( __CLASS__, 'render_status_page' )
			);
		}

		/**
		 * Render status page.
		 *
		 * @return void
		 */
		public static function render_status_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'habeq' ) );
			}

			$tables = class_exists( 'Habeq_DB' ) ? Habeq_DB::get_table_names() : array();
			$inventory_exists = false;
			$bookings_exists  = false;
			if ( ! empty( $tables ) ) {
				global $wpdb;
				if ( ! empty( $tables['inventory'] ) ) {
					$inventory_exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tables['inventory'] ) );
				}
				if ( ! empty( $tables['bookings'] ) ) {
					$bookings_exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tables['bookings'] ) );
				}
			}

			$portal_id  = absint( get_option( 'habeq_portal_page_id' ) );
			$confirm_id = absint( get_option( 'habeq_booking_confirmed_page_id' ) );
			$manage_id  = absint( get_option( 'habeq_manage_booking_page_id' ) );
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Habaq Events Status', 'habeq' ); ?></h1>
				<table class="widefat striped" style="max-width: 700px;">
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Plugin Version', 'habeq' ); ?></th>
							<td><?php echo esc_html( HABEQ_VERSION ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Events CPT Registered', 'habeq' ); ?></th>
							<td><?php echo esc_html( post_type_exists( 'habeq_event' ) ? __( 'Yes', 'habeq' ) : __( 'No', 'habeq' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Inventory Table Exists', 'habeq' ); ?></th>
							<td><?php echo esc_html( $inventory_exists ? __( 'Yes', 'habeq' ) : __( 'No', 'habeq' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Bookings Table Exists', 'habeq' ); ?></th>
							<td><?php echo esc_html( $bookings_exists ? __( 'Yes', 'habeq' ) : __( 'No', 'habeq' ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Organizer Portal Page', 'habeq' ); ?></th>
							<td><?php echo esc_html( $portal_id ? $portal_id : '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Booking Confirmed Page', 'habeq' ); ?></th>
							<td><?php echo esc_html( $confirm_id ? $confirm_id : '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Manage Booking Page', 'habeq' ); ?></th>
							<td><?php echo esc_html( $manage_id ? $manage_id : '—' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
}
