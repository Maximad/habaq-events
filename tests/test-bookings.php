<?php

class Habeq_Bookings_Test extends WP_UnitTestCase {
	protected $event_id;

	public function set_up(): void {
		parent::set_up();
		do_action( 'init' );
		if ( class_exists( 'Habeq_DB' ) ) {
			Habeq_DB::create_or_upgrade_tables();
		}

		$this->event_id = self::factory()->post->create(
			array(
				'post_type' => 'habeq_event',
			)
		);

		Habeq_DB::maybe_ensure_inventory_row( $this->event_id, 10 );
	}

	public function test_atomic_reserve_increments_reserved_and_creates_booking() {
		$booking_id = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'test@example.com', 2 );

		$this->assertIsInt( $booking_id );
		$this->assertGreaterThan( 0, $booking_id );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 2, $inventory['reserved'] );

		global $wpdb;
		$tables  = Habeq_DB::get_table_names();
		$booking = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$tables['bookings']} WHERE id = %d", $booking_id ),
			ARRAY_A
		);

		$this->assertSame( (string) $this->event_id, $booking['event_id'] );
		$this->assertSame( 'reserved', $booking['status'] );
	}

	public function test_reserve_fails_when_capacity_exceeded() {
		Habeq_DB::maybe_ensure_inventory_row( $this->event_id, 5 );

		$result = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'test@example.com', 6 );
		$this->assertWPError( $result );
		$this->assertSame( 'sold_out', $result->get_error_code() );
	}

	public function test_reserve_then_insert_failure_rolls_back_reserved() {
		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 0, $inventory['reserved'] );

		add_filter(
			'habeq_booking_force_insert_failure',
			static function () {
				return true;
			}
		);

		$result = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'fail@example.com', 2 );

		remove_all_filters( 'habeq_booking_force_insert_failure' );

		$this->assertWPError( $result );
		$this->assertSame( 'db_insert_failed', $result->get_error_code() );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 0, $inventory['reserved'] );
	}

	public function test_duplicate_booking_does_not_consume_capacity() {
		$booking_id = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'dup@example.com', 2 );
		$this->assertIsInt( $booking_id );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 2, $inventory['reserved'] );

		$result = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'dup@example.com', 1 );
		$this->assertWPError( $result );
		$this->assertSame( 'already_booked', $result->get_error_code() );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 2, $inventory['reserved'] );
	}

	public function test_cancel_is_idempotent() {
		$booking_id = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'cancel@example.com', 3 );
		$this->assertIsInt( $booking_id );

		$result = Habeq_Bookings::cancel_booking( $booking_id );
		$this->assertTrue( $result );
		$result = Habeq_Bookings::cancel_booking( $booking_id );
		$this->assertTrue( $result );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 0, $inventory['reserved'] );
	}

	public function test_booking_rate_limit_blocks_repeat_attempts() {
		$key = 'habeq_booking_' . $this->event_id . '_' . md5( strtolower( 'rate@example.com' ) );
		set_transient( $key, 1, 2 * MINUTE_IN_SECONDS );

		$result = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'rate@example.com', 1 );

		$this->assertWPError( $result );
		$this->assertSame( 'rate_limited', $result->get_error_code() );
	}

	public function test_cancel_frees_reserved() {
		$booking_id = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'cancel2@example.com', 3 );
		$this->assertIsInt( $booking_id );

		$result = Habeq_Bookings::cancel_booking( $booking_id );
		$this->assertTrue( $result );

		$inventory = Habeq_DB::get_inventory( $this->event_id );
		$this->assertSame( 0, $inventory['reserved'] );

		global $wpdb;
		$tables  = Habeq_DB::get_table_names();
		$status  = $wpdb->get_var(
			$wpdb->prepare( "SELECT status FROM {$tables['bookings']} WHERE id = %d", $booking_id )
		);
		$this->assertSame( 'cancelled', $status );
	}
}
