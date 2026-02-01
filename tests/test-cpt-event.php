<?php

class Habeq_CPT_Event_Test extends WP_UnitTestCase {
	protected $admin_id;

	public function set_up(): void {
		parent::set_up();
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
		do_action( 'init' );
		if ( class_exists( 'Habeq_DB' ) ) {
			Habeq_DB::create_or_upgrade_tables();
		}
	}

	public function tear_down(): void {
		parent::tear_down();
		wp_set_current_user( 0 );
		$_POST = array();
	}

	public function test_cpt_registered() {
		$this->assertTrue( post_type_exists( 'habeq_event' ) );
		$this->assertTrue( post_type_supports( 'habeq_event', 'title' ) );
		$this->assertTrue( post_type_supports( 'habeq_event', 'editor' ) );
		$this->assertTrue( post_type_supports( 'habeq_event', 'thumbnail' ) );
		$this->assertTrue( post_type_supports( 'habeq_event', 'excerpt' ) );
	}

	public function test_event_capacity_sync_creates_inventory_row() {
		$post_id = self::factory()->post->create(
			array(
				'post_type' => 'habeq_event',
			)
		);

		$_POST = array(
			'habeq_event_meta_nonce' => wp_create_nonce( 'habeq_event_meta' ),
			'habeq_capacity'         => '25',
			'habeq_start_datetime'   => '2024-01-01 10:00',
			'habeq_end_datetime'     => '2024-01-01 11:00',
			'habeq_venue_name'       => 'Venue',
			'habeq_venue_address'    => 'Address',
		);

		Habeq_CPT_Event::save_meta( $post_id );

		$inventory = Habeq_DB::get_inventory( $post_id );
		$this->assertNotNull( $inventory );
		$this->assertSame( 25, $inventory['capacity'] );
	}

	public function test_capacity_sync_does_not_overwrite_reserved() {
		global $wpdb;

		$post_id = self::factory()->post->create(
			array(
				'post_type' => 'habeq_event',
			)
		);

		$tables = Habeq_DB::get_table_names();
		$wpdb->insert(
			$tables['inventory'],
			array(
				'event_id'   => $post_id,
				'capacity'   => 10,
				'reserved'   => 3,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		$_POST = array(
			'habeq_event_meta_nonce' => wp_create_nonce( 'habeq_event_meta' ),
			'habeq_capacity'         => '20',
			'habeq_start_datetime'   => '2024-01-01 10:00',
			'habeq_end_datetime'     => '2024-01-01 11:00',
			'habeq_venue_name'       => 'Venue',
			'habeq_venue_address'    => 'Address',
		);

		Habeq_CPT_Event::save_meta( $post_id );

		$inventory = Habeq_DB::get_inventory( $post_id );
		$this->assertSame( 3, $inventory['reserved'] );
		$this->assertSame( 20, $inventory['capacity'] );
	}
}
