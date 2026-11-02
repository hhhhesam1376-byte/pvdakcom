<?php
/**
 * D12 test-only WordPress MU plugin: return a session-bound REST nonce to
 * marked synthetic users in the disposable GitHub Actions install.
 * Never install this file on a staging or production WordPress instance.
 */
if ( ! defined( 'ABSPATH' ) ||
     getenv( 'GITHUB_ACTIONS' ) !== 'true' ||
     ! defined( 'DB_NAME' ) || DB_NAME !== 'pvdak_ci' ||
     ! defined( 'DB_HOST' ) || DB_HOST !== '127.0.0.1' ||
     ! getenv( 'RUNNER_TEMP' ) ||
     realpath( ABSPATH ) !== realpath( getenv( 'RUNNER_TEMP' ) . '/pvdak-wp' ) ||
     wp_parse_url( site_url(), PHP_URL_HOST ) !== '127.0.0.1' ) {
    return;
}
add_action( 'wp_ajax_pvdak_ci_d12_rest_nonce', static function () {
    if ( ! is_user_logged_in() ||
         get_user_meta( get_current_user_id(), '_pvdak_d11_browser_fixture', true ) !== '1' ) {
        status_header( 403 );
        wp_die( 'Fixture authorization required.' );
    }
    nocache_headers();
    wp_send_json_success( array( 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
} );
