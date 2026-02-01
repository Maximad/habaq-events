<?php
/**
 * Capabilities and roles.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Capabilities' ) ) {
	/**
	 * Roles and capabilities handler.
	 *
	 * @package Habaq_Events
	 */
	class Habeq_Capabilities {
		/**
		 * Register roles and capabilities.
		 *
		 * @return void
		 */
		public static function register_role_and_caps() {
			$role = add_role(
				'habeq_event_organizer',
				__( 'Event Organizer', 'habeq' ),
				array(
					'read'                 => true,
					'edit_habeq_event'      => true,
					'edit_habeq_events'     => true,
					'edit_published_habeq_events' => false,
					'edit_others_habeq_events'    => false,
					'publish_habeq_events'  => false,
					'create_habeq_events'   => true,
					'read_habeq_event'      => true,
					'delete_habeq_event'    => true,
					'delete_habeq_events'   => true,
					'delete_others_habeq_events' => false,
				)
			);

			if ( null === $role ) {
				$role = get_role( 'habeq_event_organizer' );
			}

			self::add_caps_to_admin();
		}

		/**
		 * Add capabilities to administrators.
		 *
		 * @return void
		 */
		private static function add_caps_to_admin() {
			$admin = get_role( 'administrator' );
			if ( ! $admin ) {
				return;
			}

			$caps = array(
				'edit_habeq_event',
				'edit_habeq_events',
				'edit_others_habeq_events',
				'edit_published_habeq_events',
				'publish_habeq_events',
				'create_habeq_events',
				'read_habeq_event',
				'delete_habeq_event',
				'delete_habeq_events',
				'delete_others_habeq_events',
			);

			foreach ( $caps as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}
}
