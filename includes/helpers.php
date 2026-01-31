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
