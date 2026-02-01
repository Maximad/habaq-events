<?php

class Habeq_Activation_Test extends WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();
		if ( class_exists( 'Habeq_DB' ) ) {
			Habeq_DB::create_or_upgrade_tables();
		}
	}

	public function test_plugin_activates_without_fatal() {
		$this->assertTrue( class_exists( 'Habeq_Plugin' ) );
		Habeq_Plugin::activate();
		$this->assertSame( HABEQ_VERSION, get_option( 'habeq_version' ) );
	}

	public function test_tables_created_on_activation() {
		global $wpdb;

		Habeq_Plugin::activate();

		$tables = Habeq_DB::get_table_names();
		foreach ( $tables as $table_name ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			$this->assertSame( $table_name, $found );
		}
	}

	public function test_db_version_option_set() {
		Habeq_Plugin::activate();
		$this->assertSame( Habeq_DB::DB_VERSION, get_option( 'habeq_db_version' ) );
	}
}
