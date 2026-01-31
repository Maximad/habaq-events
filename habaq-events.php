<?php
/**
 * Plugin Name: Habaq Events
 * Description: Minimal event + booking system.
 * Version: 0.1.0
 * Author: Habaq
 * License: GPL-2.0-or-later
 * Text Domain: habeq
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! defined( 'HABEQ_VERSION' ) ) {
	define( 'HABEQ_VERSION', '0.1.0' );
}

if ( ! defined( 'HABEQ_PATH' ) ) {
	define( 'HABEQ_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HABEQ_URL' ) ) {
	define( 'HABEQ_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'HABEQ_SLUG' ) ) {
	define( 'HABEQ_SLUG', 'habaq-events' );
}

$habeq_loader = HABEQ_PATH . 'includes/class-habeq-plugin.php';
if ( file_exists( $habeq_loader ) ) {
	require_once $habeq_loader;
}

if ( class_exists( 'Habeq_Plugin' ) ) {
	register_activation_hook( __FILE__, array( 'Habeq_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Habeq_Plugin', 'deactivate' ) );
	Habeq_Plugin::init();
}
