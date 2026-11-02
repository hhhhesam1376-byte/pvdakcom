<?php
/**
 * D11 browser fixture: disposable WordPress/MariaDB CI ONLY.
 * Setup and cleanup are separate WP-CLI invocations around two Chrome sessions.
 * Never invoke on staging/production or with private customer data.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
     getenv( 'GITHUB_ACTIONS' ) !== 'true' ||
     getenv( 'PVDAK_CI_D11_ISOLATED' ) !== '1' ||
     ! defined( 'DB_NAME' ) || DB_NAME !== 'pvdak_ci' ||
     ! defined( 'DB_HOST' ) || DB_HOST !== '127.0.0.1' ||
     ! getenv( 'RUNNER_TEMP' ) ||
     realpath( ABSPATH ) !== realpath( getenv( 'RUNNER_TEMP' ) . '/pvdak-wp' ) ||
     wp_parse_url( site_url(), PHP_URL_HOST ) !== '127.0.0.1' ) {
    fwrite( STDERR, "D11_BROWSER_ABORT: disposable GitHub Actions WordPress only\n" );
    exit( 2 );
}
$mode = getenv( 'PVDAK_CI_D11_MODE' );
if ( ! in_array( $mode, array( 'setup', 'cleanup' ), true ) ) {
    WP_CLI::error( 'D11_BROWSER_ABORT: setup or cleanup mode required.' );
}
if ( ! function_exists( 'pvdak_core_my_requests' ) ||
     ! function_exists( 'pvdak_core_professional_dashboard' ) ||
     ! function_exists( 'pvdak_core_intake_open' ) ||
     pvdak_core_intake_open() || pvdak_core_member_intake_open() ) {
    WP_CLI::error( 'D11_BROWSER_ABORT: Core or intake-off prerequisite failed.' );
}
foreach ( array( 'pvdak_client', 'pvdak_lawyer' ) as $role ) {
    if ( ! get_role( $role ) ) { WP_CLI::error( 'D11_BROWSER_ABORT: missing Core role.' ); }
}
foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type ) {
    $obj = get_post_type_object( $type );
    if ( ! $obj || $obj->public || $obj->show_in_rest ) {
        WP_CLI::error( 'D11_BROWSER_ABORT: private Core post type prerequisite failed.' );
    }
}
require_once ABSPATH . 'wp-admin/includes/user.php';
$fixtures = array(
    'client' => array( 'role' => 'pvdak_client', 'login' => 'ci_d11_browser_client' ),
    'lawyer' => array( 'role' => 'pvdak_lawyer', 'login' => 'ci_d11_browser_lawyer' ),
);
$created_users = array();
$created_posts = array();
if ( 'setup' === $mode ) {
    foreach ( $fixtures as $f ) {
        if ( get_user_by( 'login', $f['login'] ) ) {
            WP_CLI::error( 'D11_BROWSER_ABORT: fixture login already exists.' );
        }
    }
    try {
        foreach ( $fixtures as $alias => $f ) {
            $uid = wp_insert_user( array(
                'user_login' => $f['login'],
                'user_pass' => 'pvdak_ci_d11_' . $alias . '_browser_only_2026',
                'user_email' => $f['login'] . '@example.invalid',
                'role' => $f['role'],
                'display_name' => 'D11_BROWSER_' . strtoupper( $alias ),
            ) );
            if ( is_wp_error( $uid ) || ! $uid ) {
                throw new RuntimeException( 'Fixture user creation failed.' );
            }
            $created_users[] = (int) $uid;
            update_user_meta( $uid, '_pvdak_d11_browser_fixture', '1' );
            $ids = array();
            foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type ) {
                $post = wp_insert_post( array(
                    'post_type' => $type,
                    'post_status' => 'private',
                    'post_author' => $uid,
                    'post_title' => 'D11_' . ( 'pvdak_request' === $type ? 'CASE_' : 'PROFILE_' ) . strtoupper( $alias ) . '_ONLY',
                    'meta_input' => 'pvdak_request' === $type ?
                        array( '_pvdak_request_type' => 'lawyer', '_pvdak_request_state' => 'submitted',
                               '_pvdak_d11_browser_fixture' => '1' ) :
                        array( '_pvdak_member_type' => 'student', '_pvdak_member_state' => 'pending',
                               '_pvdak_member_city' => 'D11_CITY_' . strtoupper( $alias ) . '_ONLY',
                               '_pvdak_d11_browser_fixture' => '1' ),
                ), true );
                if ( is_wp_error( $post ) || ! $post ) {
                    throw new RuntimeException( 'Private fixture post creation failed.' );
                }
                $created_posts[] = (int) $post;
                $ids[] = (int) $post;
                if ( 'pvdak_professional' === $type ) {
                    update_user_meta( $uid, '_pvdak_core_application', (int) $post );
                }
            }
            update_user_meta( $uid, '_pvdak_d11_browser_posts', $ids );
        }
    } catch ( Throwable $e ) {
        foreach ( array_reverse( $created_posts ) as $id ) { wp_delete_post( $id, true ); }
        foreach ( array_reverse( $created_users ) as $id ) { wp_delete_user( $id ); }
        WP_CLI::error( 'D11_BROWSER_ABORT: setup failed; partial fixture cleanup attempted.' );
    }
    WP_CLI::line( 'PVDAK_D11_BROWSER_FIXTURE_READY: users=2 posts=4 (disposable CI only)' );
    return;
}
$removed_users = 0;
$removed_posts = 0;
// Verify ownership and marker of every record before any cleanup mutation.
$plan = array();
foreach ( $fixtures as $f ) {
    $u = get_user_by( 'login', $f['login'] );
    if ( ! $u || get_user_meta( $u->ID, '_pvdak_d11_browser_fixture', true ) !== '1' ) {
        WP_CLI::error( 'D11_BROWSER_CLEANUP_ABORT: fixture account marker mismatch.' );
    }
    $ids = get_user_meta( $u->ID, '_pvdak_d11_browser_posts', true );
    if ( ! is_array( $ids ) || count( $ids ) !== 2 ) {
        WP_CLI::error( 'D11_BROWSER_CLEANUP_ABORT: fixture record list mismatch.' );
    }
    foreach ( $ids as $id ) {
        $p = get_post( $id );
        if ( ! $p || (int) $p->post_author !== (int) $u->ID ||
             ! in_array( $p->post_type, array( 'pvdak_request', 'pvdak_professional' ), true ) ||
             get_post_meta( $id, '_pvdak_d11_browser_fixture', true ) !== '1' ) {
            WP_CLI::error( 'D11_BROWSER_CLEANUP_ABORT: private record ownership mismatch.' );
        }
    }
    $plan[] = array( 'uid' => (int) $u->ID, 'posts' => $ids );
}
foreach ( $plan as $item ) {
    foreach ( $item['posts'] as $id ) {
        if ( ! wp_delete_post( $id, true ) || get_post( $id ) ) {
            WP_CLI::error( 'D11_BROWSER_CLEANUP_FAIL: private record deletion failed.' );
        }
        ++$removed_posts;
    }
    if ( ! wp_delete_user( $item['uid'] ) || get_user_by( 'id', $item['uid'] ) ) {
        WP_CLI::error( 'D11_BROWSER_CLEANUP_FAIL: fixture account deletion failed.' );
    }
    ++$removed_users;
}
WP_CLI::line( "PVDAK_D11_BROWSER_CLEANUP_OK: users={$removed_users} posts={$removed_posts}" );
