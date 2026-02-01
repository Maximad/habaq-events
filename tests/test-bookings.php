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

	public function test_cancel_frees_reserved() {
		$booking_id = Habeq_Bookings::create_booking( $this->event_id, 'Test User', 'test@example.com', 3 );
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
