#!/usr/bin/env bash

set -euo pipefail

DB_NAME=${1:-wordpress_test}
DB_USER=${2:-root}
DB_PASS=${3:-}
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress/}

download() {
	local url="$1"
	local dest="$2"

	if command -v curl >/dev/null 2>&1; then
		curl -sS "$url" -o "$dest"
	elif command -v wget >/dev/null 2>&1; then
		wget -q "$url" -O "$dest"
	else
		echo "curl or wget is required to download files." >&2
		exit 1
	fi
}

install_wp() {
	if [ -d "$WP_CORE_DIR" ]; then
		return
	fi

	mkdir -p "$WP_CORE_DIR"
	local archive="/tmp/wordpress.tar.gz"
	if [ "$WP_VERSION" = "latest" ]; then
		download "https://wordpress.org/latest.tar.gz" "$archive"
	else
		download "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" "$archive"
	fi

	tar --strip-components=1 -zxmf "$archive" -C "$WP_CORE_DIR"
}

install_test_suite() {
	if [ -d "$WP_TESTS_DIR" ]; then
		return
	fi

	mkdir -p "$WP_TESTS_DIR"
	local tests_tag="$WP_VERSION"
	if [ "$WP_VERSION" = "latest" ]; then
		tests_tag="trunk"
	fi

	local test_lib_url="https://develop.svn.wordpress.org/${tests_tag}/tests/phpunit/includes/"
	local test_data_url="https://develop.svn.wordpress.org/${tests_tag}/tests/phpunit/data/"

	mkdir -p "$WP_TESTS_DIR/includes" "$WP_TESTS_DIR/data"

	download "${test_lib_url}functions.php" "$WP_TESTS_DIR/includes/functions.php"
	download "${test_lib_url}bootstrap.php" "$WP_TESTS_DIR/includes/bootstrap.php"
	download "${test_data_url}schema.sql" "$WP_TESTS_DIR/data/schema.sql"
	download "${test_data_url}session-tokens.php" "$WP_TESTS_DIR/data/session-tokens.php"
	download "${test_data_url}functions.php" "$WP_TESTS_DIR/data/functions.php"
	download "${test_data_url}includes.php" "$WP_TESTS_DIR/data/includes.php"

	if [ ! -d "$WP_TESTS_DIR/config" ]; then
		mkdir -p "$WP_TESTS_DIR/config"
	fi

	cat > "$WP_TESTS_DIR/config/wp-tests-config.php" <<CONFIG
<?php

define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'ABSPATH', '${WP_CORE_DIR}/' );
define( 'WP_DEBUG', true );

define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@example.com' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_TESTS_TABLE_PREFIX', 'wptests_' );
CONFIG
}

create_db() {
	local sql="CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
	if command -v mysql >/dev/null 2>&1; then
		mysql --user="${DB_USER}" --password="${DB_PASS}" --host="${DB_HOST}" --execute="$sql" || true
	fi
}

install_wp
install_test_suite
create_db

echo "WordPress test suite installed in ${WP_TESTS_DIR} and core in ${WP_CORE_DIR}".
