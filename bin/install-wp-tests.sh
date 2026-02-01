#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 4 ]; then
	echo "Usage: $0 <db-name> <db-user> <db-pass> <db-host> [wp-version] [skip-db-create]" >&2
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=$4
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress/}

DOWNLOAD_CMD=""
if command -v curl >/dev/null 2>&1; then
	DOWNLOAD_CMD='curl -sS'
elif command -v wget >/dev/null 2>&1; then
	DOWNLOAD_CMD='wget -qO-'
else
	echo "curl or wget is required to download files." >&2
	exit 1
fi

svn_checkout() {
	local url="$1"
	local dir="$2"
	if [ -d "$dir" ]; then
		return
	fi
	mkdir -p "$dir"
	svn export --quiet "$url" "$dir"
}

install_wp() {
	if [ -d "$WP_CORE_DIR" ]; then
		return
	fi

	mkdir -p "$WP_CORE_DIR"
	local archive="/tmp/wordpress.tar.gz"
	if [ "$WP_VERSION" = "latest" ]; then
		$DOWNLOAD_CMD "https://wordpress.org/latest.tar.gz" > "$archive"
	else
		$DOWNLOAD_CMD "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" > "$archive"
	fi

	tar --strip-components=1 -zxmf "$archive" -C "$WP_CORE_DIR"
}

install_test_suite() {
	mkdir -p "$WP_TESTS_DIR"

	local tests_tag="$WP_VERSION"
	if [ "$WP_VERSION" = "latest" ]; then
		tests_tag="trunk"
	fi

	svn_checkout "https://develop.svn.wordpress.org/${tests_tag}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
	svn_checkout "https://develop.svn.wordpress.org/${tests_tag}/tests/phpunit/data/" "$WP_TESTS_DIR/data"

	local config_file="$WP_TESTS_DIR/wp-tests-config.php"
	if [ ! -f "$config_file" ]; then
		$DOWNLOAD_CMD "https://develop.svn.wordpress.org/${tests_tag}/wp-tests-config-sample.php" > "$config_file"
		sed -i "s/youremptytestdbnamehere/${DB_NAME}/" "$config_file"
		sed -i "s/yourusernamehere/${DB_USER}/" "$config_file"
		sed -i "s/yourpasswordhere/${DB_PASS}/" "$config_file"
		sed -i "s|localhost|${DB_HOST}|" "$config_file"
		sed -i "s|/path/to/wordpress/|${WP_CORE_DIR}/|" "$config_file"
	fi
}

create_database() {
	if [ "$SKIP_DB_CREATE" = "true" ]; then
		return
	fi

	if command -v mysql >/dev/null 2>&1; then
		mysql --user="${DB_USER}" --password="${DB_PASS}" --host="${DB_HOST}" --execute="CREATE DATABASE IF NOT EXISTS ${DB_NAME};" || true
	fi
}

install_wp
install_test_suite
create_database

echo "Installed WordPress core in: ${WP_CORE_DIR}"
echo "Installed WordPress test suite in: ${WP_TESTS_DIR}"
