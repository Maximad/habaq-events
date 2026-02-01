<?php

class Habeq_Portal_Test extends WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();
		do_action( 'init' );
		if ( class_exists( 'Habeq_Capabilities' ) ) {
			Habeq_Capabilities::register_role_and_caps();
		}
	}

	public function tear_down(): void {
		parent::tear_down();
		wp_set_current_user( 0 );
		$_GET = array();
		$_POST = array();
		$_SERVER = array();
	}

	public function test_portal_shortcode_renders_login_when_logged_out() {
		$output = do_shortcode( '[habeq_portal]' );
		$this->assertStringContainsString( 'Email or Username', $output );
	}

	public function test_portal_shortcode_renders_dashboard_when_logged_in_and_approved() {
		$user_id = self::factory()->user->create( array( 'role' => 'habeq_event_organizer' ) );
		update_user_meta( $user_id, 'habeq_organizer_status', 'approved' );
		wp_set_current_user( $user_id );

		$output = do_shortcode( '[habeq_portal]' );
		$this->assertStringContainsString( 'Organizer overview coming soon.', $output );
	}

	public function test_pending_organizer_blocked_from_event_creation_tabs() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_user_meta( $user_id, 'habeq_organizer_status', 'pending' );
		wp_set_current_user( $user_id );

		$_GET['tab'] = 'new-event';
		$output      = do_shortcode( '[habeq_portal]' );

		$this->assertStringContainsString( 'Approval Pending', $output );
	}

	public function test_signup_rate_limit_blocks_request() {
		update_option( 'habeq_allow_signup', '1' );

		$email = 'rate@example.com';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$email_key = 'habeq_signup_email_' . md5( strtolower( $email ) );
		set_transient( $email_key, 1, 10 * MINUTE_IN_SECONDS );

		$_POST = array(
			'habeq_portal_signup_nonce' => wp_create_nonce( 'habeq_portal_signup' ),
			'email'                     => $email,
			'display_name'              => 'Test User',
		);

		$redirect = '';
		add_filter( 'habeq_portal_exit_on_redirect', '__return_false' );
		add_filter(
			'wp_redirect',
			static function ( $location ) use ( &$redirect ) {
				$redirect = $location;
				return $location;
			}
		);

		Habeq_Portal::handle_signup();

		remove_all_filters( 'habeq_portal_exit_on_redirect' );
		remove_all_filters( 'wp_redirect' );

		$this->assertStringContainsString( 'error=rate_limited', $redirect );
	}
}
