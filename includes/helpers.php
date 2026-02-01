<?php
/**
 * Helper functions for Habaq Events.
 *
 * @package HabaqEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'habeq_get_ip' ) ) {
	/**
	 * Get the visitor IP address.
	 *
	 * @return string
	 */
	function habeq_get_ip() {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$raw_value = wp_unslash( $_SERVER[ $key ] );
			$parts     = array_map( 'trim', explode( ',', $raw_value ) );

			foreach ( $parts as $part ) {
				$ip = filter_var( $part, FILTER_VALIDATE_IP );
				if ( false !== $ip ) {
					return $ip;
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'habeq_now_mysql' ) ) {
	/**
	 * Get the current time in MySQL format (UTC).
	 *
	 * @return string
	 */
	function habeq_now_mysql() {
		if ( function_exists( 'current_time' ) ) {
			return current_time( 'mysql', true );
		}

		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'habeq_portal_url' ) ) {
	/**
	 * Build a portal URL with the requested tab and arguments.
	 *
	 * @param string $tab  Portal tab slug.
	 * @param array  $args Optional query args.
	 * @return string
	 */
	function habeq_portal_url( $tab, $args = array() ) {
		$tab     = sanitize_key( $tab );
		$page_id = absint( get_option( 'habeq_portal_page_id' ) );
		$base    = $page_id ? get_permalink( $page_id ) : home_url( '/' );
		$params  = is_array( $args ) ? $args : array();

		$params['tab'] = $tab;

		return add_query_arg( $params, $base );
	}
}
