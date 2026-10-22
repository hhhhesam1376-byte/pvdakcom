<?php
/**
 * PVDak Core live read-only anonymous-access smoke test.
 *
 * Run via WP-CLI eval-file from the private release archive on the same
 * WordPress installation where PVDak Core is active.
 *
 * No users, posts, options, plugins, role metadata or cases are written.
 * Assertions are anonymous only: real multi-user E2E and race tests remain required.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "Must be run with WP-CLI eval-file.\n" );
}
$GLOBALS['pvdak_anon_live_checks'] = 0;
$GLOBALS['pvdak_anon_live_failures'] = 0;
function pvdak_anon_check( $name, $pass ) {
    $GLOBALS['pvdak_anon_live_checks']++;
    if ( ! $pass ) { $GLOBALS['pvdak_anon_live_failures']++; }
    WP_CLI::line( ( $pass ? 'PASS ' : 'FAIL ' ) . $name );
}
$loaded = function_exists( 'pvdak_core_request_form' ) &&
    function_exists( 'pvdak_core_member_application_form' ) &&
    function_exists( 'pvdak_core_my_requests' ) &&
    function_exists( 'pvdak_core_dashboard' ) &&
    function_exists( 'pvdak_core_professional_dashboard' );
pvdak_anon_check( 'Core frontend features loaded', $loaded );
if ( ! $loaded ) {
    WP_CLI::error( 'PVDak Core is not active. Cannot run anonymous-access test.' );
}
pvdak_anon_check( 'Public maintenance remains ON',
    function_exists( 'pvdak_maintenance_enabled' ) && pvdak_maintenance_enabled() );
pvdak_anon_check( 'Legal request intake remains closed', ! pvdak_core_intake_open() );
pvdak_anon_check( 'Professional application intake remains closed', ! pvdak_core_member_intake_open() );

// WordPress CLI only: temporarily impersonate an unauthenticated visitor in memory.
$original_user = get_current_user_id();
wp_set_current_user( 0 );
pvdak_anon_check( 'Anonymous user is logged out', ! is_user_logged_in() );
$blocked_caps = array(
    'edit_pvdak_requests',
    'read_private_pvdak_requests',
    'delete_pvdak_requests',
    'edit_pvdak_professionals',
    'read_private_pvdak_professionals',
    'delete_pvdak_professionals',
);
foreach ( $blocked_caps as $cap ) {
    pvdak_anon_check( 'Anonymous capability denied: ' . $cap, ! current_user_can( $cap ) );
}

$shortcodes = array(
    array( 'Legal request form', '[pvdak_request_form type="lawyer"]' ),
    array( 'Legal consultation form', '[pvdak_request_form type="consultation"]' ),
    array( 'Legal question form', '[pvdak_request_form type="question"]' ),
    array( 'Professional application form', '[pvdak_professional_application]' ),
    array( 'Personal case list', '[pvdak_my_requests]' ),
    array( 'Account dashboard', '[pvdak_account_dashboard]' ),
    array( 'Professional dashboard', '[pvdak_professional_dashboard]' ),
);
foreach ( $shortcodes as $entry ) {
    $html = do_shortcode( $entry[1] );
    $is_safe = is_string( $html ) && '' !== trim( $html ) &&
        false === stripos( $html, '<form' ) &&
        false === strpos( $html, 'pvdak-core-requests' ) &&
        false === strpos( $html, 'pvdak-core-dashboard-actions' ) &&
        false === strpos( $html, '_pvdak_nonce' );
    pvdak_anon_check( 'Anonymous shortcode is locked: ' . $entry[0], $is_safe );
}

// There must be no REST route exposing the sensitive private CPTs.
// Do not output any URI containing user names or case details.
$routes = array_keys( rest_get_server()->get_routes() );
$unsafe_routes = array_filter( $routes, function ( $route ) {
    return (bool) preg_match( '~(?:^|/)(?:pvdak_request|pvdak_requests|pvdak_professional|pvdak_professionals)(?:/|$)~', $route );
} );
pvdak_anon_check( 'No public REST routes for legal cases/profiles', ! $unsafe_routes );
wp_set_current_user( $original_user );

$checks = $GLOBALS['pvdak_anon_live_checks'];
$failures = $GLOBALS['pvdak_anon_live_failures'];
// This script executes a fixed baseline: 4 readiness + 1 login + 6 capabilities
// + 7 shortcodes + 1 REST route check. Never accept an empty test run as PASS.
$expected_checks = 19;
WP_CLI::line( sprintf( 'PVDAK_ANON_LIVE_SMOKE: checks=%d failures=%d expected=%d (read-only; no private content printed)', $checks, $failures, $expected_checks ) );
if ( $checks !== $expected_checks || $failures ) {
    WP_CLI::error( 'Anonymous-access smoke test FAILED. Keep intake disabled and maintenance active.' );
}
WP_CLI::success( 'Anonymous-access baseline passed. Test accounts and browser sessions are still required.' );
