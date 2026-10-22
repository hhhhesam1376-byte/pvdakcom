<?php
/**
 * PVDak Core database-backed two-user acceptance (ISOLATED STAGING ONLY).
 *
 * WordPress WP-CLI: wp eval-file /private/scripts/pvdak-staging-multiuser-e2e.php --path=/isolated/staging/wp
 * Preconditions: a separate WordPress installation and database, Core active,
 * and PVDAK_E2E_ISOLATED_STAGING=true explicitly set in that staging wp-config.
 *
 * Never run against pvdak.com / the public_html installation. This script
 * creates two disposable users, four PRIVATE synthetic records, checks
 * account/dashboard isolation with real WordPress queries, then cleans up.
 * No real legal cases, real profiles, documents, user emails or passwords printed.
 *
 * This is WordPress runtime E2E, NOT a real browser-session authorization test.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "WP-CLI only.\n" );
}
$host = strtolower( (string) wp_parse_url( site_url(), PHP_URL_HOST ) );
$home_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
$root = realpath( ABSPATH );
$production_root = realpath( getenv( 'HOME' ) . '/public_html' );
if ( ! defined( 'PVDAK_E2E_ISOLATED_STAGING' ) ||
    true !== PVDAK_E2E_ISOLATED_STAGING ||
    ! $host || ! $home_host || $host !== $home_host ||
    'pvdak.com' === $host || 'www.pvdak.com' === $host ||
    ( $root && $production_root && $root === $production_root ) ) {
    WP_CLI::error( 'STAGING_ONLY: explicit isolated staging guard failed; no fixtures were created.' );
}
if ( ! function_exists( 'pvdak_core_register_post_types' ) ||
    ! function_exists( 'pvdak_core_my_requests' ) ||
    ! function_exists( 'pvdak_core_dashboard' ) ||
    ! function_exists( 'pvdak_core_professional_dashboard' ) ||
    ! function_exists( 'pvdak_core_existing_application' ) ||
    ! function_exists( 'pvdak_maintenance_enabled' ) ||
    ! pvdak_maintenance_enabled() ||
    pvdak_core_intake_open() || pvdak_core_member_intake_open() ) {
    WP_CLI::error( 'STAGING_ONLY: Core/maintenance/intake prerequisites failed; no fixtures were created.' );
}
foreach ( array( 'pvdak_client', 'pvdak_lawyer' ) as $slug ) {
    if ( ! get_role( $slug ) ) {
        WP_CLI::error( 'STAGING_ONLY: missing test role ' . $slug . '; no fixtures were created.' );
    }
}
foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $slug ) {
    $object = get_post_type_object( $slug );
    if ( ! $object || $object->public || $object->show_in_rest ) {
        WP_CLI::error( 'STAGING_ONLY: required private post type unavailable; no fixtures were created.' );
    }
}
require_once ABSPATH . 'wp-admin/includes/user.php';

$GLOBALS['pvdak_e2e_checks'] = 0;
$GLOBALS['pvdak_e2e_failures'] = 0;
function pvdak_e2e_assert( $ok, $label ) {
    $GLOBALS['pvdak_e2e_checks']++;
    if ( ! $ok ) {
        $GLOBALS['pvdak_e2e_failures']++;
        WP_CLI::warning( 'E2E_FAIL: ' . $label );
    }
}
$original_user_id = get_current_user_id();
$created_users = array();
$created_posts = array();
$fatal = '';
$cleanup_failed = false;

try {
    $nonce = strtolower( wp_generate_password( 12, false, false ) );
    $user_ids = array();
    foreach ( array( 'a' => 'pvdak_client', 'b' => 'pvdak_lawyer' ) as $alias => $role ) {
        // Reserved example.invalid avoids accidentally sending mail to real users.
        $login = 'pvdak_e2e_' . $alias . '_' . $nonce;
        $user = wp_insert_user( array(
            'user_login' => $login,
            'user_pass' => wp_generate_password( 48, true, true ),
            'user_email' => $login . '@example.invalid',
            'role' => $role,
            'display_name' => 'PVDak E2E ' . strtoupper( $alias ),
        ) );
        if ( is_wp_error( $user ) || ! $user ) {
            throw new RuntimeException( 'Could not create staging fixture user.' );
        }
        $created_users[] = (int) $user;
        $user_ids[ $alias ] = (int) $user;
    }
    $case_titles = array( 'a' => 'E2E_CLIENT_A_' . $nonce, 'b' => 'E2E_CLIENT_B_' . $nonce );
    $cities = array( 'a' => 'E2E_CITY_A_' . $nonce, 'b' => 'E2E_CITY_B_' . $nonce );
    $case_ids = array();
    $profile_ids = array();
    foreach ( array( 'a', 'b' ) as $alias ) {
        $uid = $user_ids[ $alias ];
        $request = wp_insert_post( array(
            'post_type' => 'pvdak_request',
            'post_status' => 'private',
            'post_author' => $uid,
            'post_title' => $case_titles[ $alias ],
            'post_content' => '',
            'meta_input' => array(
                '_pvdak_request_type' => 'lawyer',
                '_pvdak_request_state' => 'submitted',
            ),
        ), true );
        if ( is_wp_error( $request ) || ! $request ) {
            throw new RuntimeException( 'Could not create private staging test request.' );
        }
        $created_posts[] = (int) $request;
        $case_ids[ $alias ] = (int) $request;
        $profile = wp_insert_post( array(
            'post_type' => 'pvdak_professional',
            'post_status' => 'private',
            'post_author' => $uid,
            'post_title' => 'E2E_PROFILE_' . strtoupper( $alias ) . '_' . $nonce,
            'post_content' => '',
            'meta_input' => array(
                '_pvdak_member_type' => 'student',
                '_pvdak_member_state' => 'pending',
                '_pvdak_member_city' => $cities[ $alias ],
            ),
        ), true );
        if ( is_wp_error( $profile ) || ! $profile ) {
            throw new RuntimeException( 'Could not create private staging test professional profile.' );
        }
        $created_posts[] = (int) $profile;
        $profile_ids[ $alias ] = (int) $profile;
        update_user_meta( $uid, '_pvdak_core_application', (int) $profile );
    }
    pvdak_e2e_assert( 2 === count( $created_users ) && 4 === count( $created_posts ),
        'Exactly two disposable accounts and four records created' );
    foreach ( array( 'a', 'b' ) as $alias ) {
        pvdak_e2e_assert( 'private' === get_post_status( $case_ids[ $alias ] ) &&
            'private' === get_post_status( $profile_ids[ $alias ] ),
            'Staging case and professional record remain private' );
    }
    wp_set_current_user( 0 );
    $anonymous = pvdak_core_my_requests() . pvdak_core_dashboard() .
        pvdak_core_professional_dashboard();
    pvdak_e2e_assert(
        false === strpos( $anonymous, $case_titles['a'] ) &&
        false === strpos( $anonymous, $case_titles['b'] ) &&
        false === strpos( $anonymous, $cities['a'] ) &&
        false === strpos( $anonymous, $cities['b'] ),
        'Anonymous views contain no test records'
    );

    foreach ( array( 'a', 'b' ) as $alias ) {
        $other = 'a' === $alias ? 'b' : 'a';
        wp_set_current_user( $user_ids[ $alias ] );
        pvdak_e2e_assert( is_user_logged_in() && ! current_user_can( 'edit_pvdak_requests' ),
            'Logged-in test user lacks private case admin permissions' );
        $list = pvdak_core_my_requests();
        pvdak_e2e_assert( false !== strpos( $list, $case_titles[ $alias ] ),
            'Own case appears in case list' );
        pvdak_e2e_assert( false === strpos( $list, $case_titles[ $other ] ),
            'Foreign case is excluded from case list' );
        $account = pvdak_core_dashboard();
        pvdak_e2e_assert( false !== strpos( $account, $case_titles[ $alias ] ),
            'Own case appears on account dashboard' );
        pvdak_e2e_assert( false === strpos( $account, $case_titles[ $other ] ) &&
            false === strpos( $account, $cities[ $other ] ),
            'Foreign case and profile data excluded from account dashboard' );
        $professional = pvdak_core_professional_dashboard();
        pvdak_e2e_assert( false !== strpos( $professional, $cities[ $alias ] ),
            'Own professional application is visible' );
        pvdak_e2e_assert( false === strpos( $professional, $cities[ $other ] ) &&
            false === strpos( $professional, $case_titles[ $other ] ),
            'Foreign professional data and case title excluded from professional view' );
        // Explicit forged link to the other account's profile must not grant access.
        update_user_meta( $user_ids[ $alias ], '_pvdak_core_application', $profile_ids[ $other ] );
        $forged = pvdak_core_professional_dashboard();
        pvdak_e2e_assert( false === strpos( $forged, $cities[ $other ] ),
            'Forged cross-owner profile pointer cannot reveal private profile' );
        update_user_meta( $user_ids[ $alias ], '_pvdak_core_application', $profile_ids[ $alias ] );
    }
} catch ( Throwable $e ) {
    $fatal = 'Fixture execution aborted: ' . $e->getMessage();
} finally {
    wp_set_current_user( $original_user_id );
    // Remove only records and accounts that this invocation created.
    foreach ( array_reverse( $created_posts ) as $id ) {
        if ( get_post( $id ) && ! wp_delete_post( $id, true ) ) { $cleanup_failed = true; }
    }
    foreach ( array_reverse( $created_users ) as $id ) {
        if ( get_user_by( 'id', $id ) && ! wp_delete_user( $id ) ) { $cleanup_failed = true; }
    }
    foreach ( $created_posts as $id ) {
        if ( get_post( $id ) ) { $cleanup_failed = true; }
    }
    foreach ( $created_users as $id ) {
        if ( get_user_by( 'id', $id ) ) { $cleanup_failed = true; }
    }
}

$checks = $GLOBALS['pvdak_e2e_checks'];
$failures = $GLOBALS['pvdak_e2e_failures'];
$expected = 20; // 1 setup + 2 privacy + 1 anonymous + 8 per account.
WP_CLI::line( sprintf(
    'PVDAK_STAGING_MULTIUSER: checks=%d failures=%d expected=%d cleanup=%s',
    $checks, $failures, $expected, $cleanup_failed ? 'FAIL' : 'OK'
) );
if ( $fatal || $cleanup_failed || $failures || $checks !== $expected ) {
    WP_CLI::error( 'Isolated staging E2E failed or could not clean all fixtures. Review test site safely.' );
}
WP_CLI::success( 'Real WordPress DB-backed client/professional isolation PASS on isolated staging only.' );
