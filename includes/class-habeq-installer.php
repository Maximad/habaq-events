<?php
/**
 * Installer for Habaq Events pages and menus.
 *
 * @package Habaq_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Habeq_Installer' ) ) {
	/**
	 * Installer class.
	 *
	 * @package Habaq_Events
	 */
	class Habeq_Installer {
		/**
		 * Marker used to detect plugin-managed content.
		 */
		const MANAGED_MARKER = 'habeq:managed';

		/**
		 * Bootstrap installer hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_setup_page' ) );
			add_action( 'admin_post_habeq_run_setup', array( __CLASS__, 'handle_run_setup' ) );
		}

		/**
		 * Run setup for pages and menus.
		 *
		 * @return array
		 */
		public static function run_setup() {
			$results = array(
				'pages' => array(),
				'menus' => array(),
			);

			$privacy_page_id = absint( get_option( 'wp_page_for_privacy_policy' ) );
			if ( $privacy_page_id && get_post( $privacy_page_id ) ) {
				update_option( 'habeq_privacy_page_id', $privacy_page_id );
				$results['pages']['privacy'] = $privacy_page_id;
			} else {
				$privacy_page_id = self::ensure_page( 'privacy', 'Privacy', self::wrap_content( __( 'Privacy policy details will be added soon.', 'habeq' ) ), 'draft' );
				update_option( 'habeq_privacy_page_id', $privacy_page_id );
				$results['pages']['privacy'] = $privacy_page_id;
			}

			$portal_id = self::ensure_page( 'organizer', 'Organizer Portal', self::wrap_content( '[habeq_portal]' ), 'publish' );
			update_option( 'habeq_portal_page_id', $portal_id );
			$results['pages']['organizer'] = $portal_id;

			$confirm_id = self::ensure_page( 'booking-confirmed', 'Booking Confirmed', self::wrap_content( '[habeq_booking_confirmation]' ), 'publish' );
			update_option( 'habeq_booking_confirmed_page_id', $confirm_id );
			$results['pages']['booking_confirmed'] = $confirm_id;

			$manage_status = shortcode_exists( 'habeq_manage_booking' ) ? 'publish' : 'draft';
			$manage_id     = self::ensure_page( 'manage-booking', 'Manage Booking', self::wrap_content( '[habeq_manage_booking]' ), $manage_status );
			update_option( 'habeq_manage_booking_page_id', $manage_id );
			$results['pages']['manage_booking'] = $manage_id;

			$refunds_id = self::ensure_page( 'refunds', 'Refunds & Cancellations', self::wrap_content( __( 'Refund and cancellation policy details will be added soon.', 'habeq' ) ), 'draft' );
			update_option( 'habeq_refunds_page_id', $refunds_id );
			$results['pages']['refunds'] = $refunds_id;

			$terms_id = self::ensure_page( 'terms', 'Terms', self::wrap_content( __( 'Terms and conditions will be added soon.', 'habeq' ) ), 'draft' );
			update_option( 'habeq_terms_page_id', $terms_id );
			$results['pages']['terms'] = $terms_id;

			$contact_id = self::ensure_page( 'contact', 'Contact', self::wrap_content( __( 'Contact information will be added soon.', 'habeq' ) ), 'draft' );
			update_option( 'habeq_contact_page_id', $contact_id );
			$results['pages']['contact'] = $contact_id;

			$main_menu_id = self::ensure_menu( __( 'Habaq Events – Main', 'habeq' ) );
			update_option( 'habeq_main_menu_id', $main_menu_id );
			$results['menus']['main'] = $main_menu_id;

			$footer_menu_id = self::ensure_menu( __( 'Habaq Events – Footer', 'habeq' ) );
			update_option( 'habeq_footer_menu_id', $footer_menu_id );
			$results['menus']['footer'] = $footer_menu_id;

			$event_archive = get_post_type_archive_link( 'habeq_event' );
			if ( $event_archive ) {
				self::ensure_menu_item_custom( $main_menu_id, __( 'Events', 'habeq' ), $event_archive, 0 );
			}
			self::ensure_menu_item_page( $main_menu_id, $portal_id, 10 );
			if ( 'publish' === get_post_status( $manage_id ) ) {
				self::ensure_menu_item_page( $main_menu_id, $manage_id, 20 );
			}
			if ( 'publish' === get_post_status( $contact_id ) ) {
				self::ensure_menu_item_page( $main_menu_id, $contact_id, 30 );
			}

			self::ensure_menu_item_page( $footer_menu_id, $privacy_page_id, 0 );
			self::ensure_menu_item_page( $footer_menu_id, $refunds_id, 10 );
			self::ensure_menu_item_page( $footer_menu_id, $terms_id, 20 );
			self::ensure_menu_item_page( $footer_menu_id, $contact_id, 30 );

			self::maybe_assign_menu_locations( $main_menu_id, $footer_menu_id );

			return $results;
		}

		/**
		 * Ensure a page exists and is optionally updated.
		 *
		 * @param string $slug      Page slug.
		 * @param string $title     Page title.
		 * @param string $content   Page content.
		 * @param string $status    Post status.
		 * @param int    $parent_id Parent page ID.
		 * @return int
		 */
		public static function ensure_page( $slug, $title, $content, $status = 'publish', $parent_id = 0 ) {
			$slug  = sanitize_title( $slug );
			$title = sanitize_text_field( $title );
			$status = sanitize_key( $status );
			$parent_id = absint( $parent_id );

			$page = get_page_by_path( $slug );
			if ( $page ) {
				if ( self::content_has_marker( $page->post_content ) ) {
					wp_update_post(
						array(
							'ID'           => $page->ID,
							'post_content' => $content,
						)
					);
				}

				return (int) $page->ID;
			}

			$page_id = wp_insert_post(
				array(
					'post_name'    => $slug,
					'post_title'   => $title,
					'post_content' => $content,
					'post_status'  => $status,
					'post_type'    => 'page',
					'post_parent'  => $parent_id,
				),
				true
			);

			if ( is_wp_error( $page_id ) ) {
				return 0;
			}

			return (int) $page_id;
		}

		/**
		 * Ensure a nav menu exists.
		 *
		 * @param string $name Menu name.
		 * @return int
		 */
		public static function ensure_menu( $name ) {
			$name = sanitize_text_field( $name );
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu && isset( $menu->term_id ) ) {
				return (int) $menu->term_id;
			}

			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				return 0;
			}

			return (int) $menu_id;
		}

		/**
		 * Ensure a page menu item exists.
		 *
		 * @param int $menu_id Menu ID.
		 * @param int $page_id Page ID.
		 * @param int $position Position.
		 * @return void
		 */
		public static function ensure_menu_item_page( $menu_id, $page_id, $position = 0 ) {
			$menu_id = absint( $menu_id );
			$page_id = absint( $page_id );
			$position = absint( $position );

			if ( ! $menu_id || ! $page_id ) {
				return;
			}

			$items = wp_get_nav_menu_items( $menu_id );
			if ( $items ) {
				foreach ( $items as $item ) {
					if ( (int) $item->object_id === $page_id && 'page' === $item->object ) {
						return;
					}
				}
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $position,
				)
			);
		}

		/**
		 * Ensure a custom menu item exists.
		 *
		 * @param int    $menu_id Menu ID.
		 * @param string $title   Item title.
		 * @param string $url     Item URL.
		 * @param int    $position Position.
		 * @return void
		 */
		public static function ensure_menu_item_custom( $menu_id, $title, $url, $position = 0 ) {
			$menu_id  = absint( $menu_id );
			$title    = sanitize_text_field( $title );
			$url      = esc_url_raw( $url );
			$position = absint( $position );

			if ( ! $menu_id || '' === $url ) {
				return;
			}

			$items = wp_get_nav_menu_items( $menu_id );
			if ( $items ) {
				foreach ( $items as $item ) {
					if ( 'custom' === $item->type && $item->url === $url ) {
						return;
					}
				}
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $title,
					'menu-item-url'       => $url,
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'custom',
					'menu-item-position'  => $position,
				)
			);
		}

		/**
		 * Assign menu locations when possible.
		 *
		 * @param int $main_menu_id Main menu ID.
		 * @param int $footer_menu_id Footer menu ID.
		 * @return void
		 */
		public static function maybe_assign_menu_locations( $main_menu_id, $footer_menu_id ) {
			$main_menu_id   = absint( $main_menu_id );
			$footer_menu_id = absint( $footer_menu_id );

			if ( ! $main_menu_id && ! $footer_menu_id ) {
				return;
			}

			$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
			foreach ( $locations as $location => $menu_id ) {
				if ( $main_menu_id && 0 === (int) $menu_id ) {
					$locations[ $location ] = $main_menu_id;
					$main_menu_id = 0;
					continue;
				}
				if ( $footer_menu_id && 0 === (int) $menu_id ) {
					$locations[ $location ] = $footer_menu_id;
					$footer_menu_id = 0;
				}
			}

			set_theme_mod( 'nav_menu_locations', $locations );
		}

		/**
		 * Register setup page under Events.
		 *
		 * @return void
		 */
		public static function register_setup_page() {
			add_submenu_page(
				'edit.php?post_type=habeq_event',
				__( 'Habaq Events Setup', 'habeq' ),
				__( 'Habaq Events Setup', 'habeq' ),
				'manage_options',
				'habeq-events-setup',
				array( __CLASS__, 'render_setup_page' )
			);
		}

		/**
		 * Render setup admin page.
		 *
		 * @return void
		 */
		public static function render_setup_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'habeq' ) );
			}

			$status = isset( $_GET['habeq_setup'] ) ? sanitize_key( wp_unslash( $_GET['habeq_setup'] ) ) : '';
			$option_ids = array(
				'habeq_portal_page_id'            => absint( get_option( 'habeq_portal_page_id' ) ),
				'habeq_booking_confirmed_page_id' => absint( get_option( 'habeq_booking_confirmed_page_id' ) ),
				'habeq_manage_booking_page_id'    => absint( get_option( 'habeq_manage_booking_page_id' ) ),
				'habeq_refunds_page_id'           => absint( get_option( 'habeq_refunds_page_id' ) ),
				'habeq_terms_page_id'             => absint( get_option( 'habeq_terms_page_id' ) ),
				'habeq_privacy_page_id'           => absint( get_option( 'habeq_privacy_page_id' ) ),
				'habeq_contact_page_id'           => absint( get_option( 'habeq_contact_page_id' ) ),
				'habeq_main_menu_id'              => absint( get_option( 'habeq_main_menu_id' ) ),
				'habeq_footer_menu_id'            => absint( get_option( 'habeq_footer_menu_id' ) ),
			);
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Habaq Events Setup', 'habeq' ); ?></h1>
				<?php if ( 'success' === $status ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Setup completed successfully.', 'habeq' ); ?></p></div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'habeq_run_setup' ); ?>
					<input type="hidden" name="action" value="habeq_run_setup" />
					<?php submit_button( __( 'Run Setup', 'habeq' ) ); ?>
				</form>
				<h2><?php esc_html_e( 'Stored IDs', 'habeq' ); ?></h2>
				<table class="widefat striped" style="max-width: 600px;">
					<tbody>
						<?php foreach ( $option_ids as $label => $value ) : ?>
							<tr>
								<th><?php echo esc_html( $label ); ?></th>
								<td><?php echo esc_html( $value ? $value : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Handle setup form submission.
		 *
		 * @return void
		 */
		public static function handle_run_setup() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'habeq' ) );
			}

			check_admin_referer( 'habeq_run_setup' );

			self::run_setup();

			$redirect = add_query_arg(
				array(
					'post_type'    => 'habeq_event',
					'page'         => 'habeq-events-setup',
					'habeq_setup'  => 'success',
				),
				admin_url( 'edit.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Wrap content with managed marker.
		 *
		 * @param string $content Content to wrap.
		 * @return string
		 */
		private static function wrap_content( $content ) {
			return "<!-- " . self::MANAGED_MARKER . " -->\n" . $content . "\n<!-- /" . self::MANAGED_MARKER . " -->";
		}

		/**
		 * Check if content contains the managed marker.
		 *
		 * @param string $content Content.
		 * @return bool
		 */
		private static function content_has_marker( $content ) {
			return false !== strpos( (string) $content, self::MANAGED_MARKER );
		}
	}
}
