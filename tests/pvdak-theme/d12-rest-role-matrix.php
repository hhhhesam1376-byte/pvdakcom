<?php
/**
 * D12 isolated WordPress REST and role authorization matrix.
 *
 * Reuses the disposable D11 browser fixture while its EXIT cleanup trap is
 * active. In-process REST dispatch verifies actual WordPress route and
 * permission callbacks; it is NOT a live HTTP/CDN or staging acceptance test.
 * No fixtures, user passwords, or private case contents are printed.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
     getenv( 'GITHUB_ACTIONS' ) !== 'true' ||
     getenv( 'PVDAK_CI_D12_ISOLATED' ) !== '1' ||
     ! defined( 'DB_NAME' ) || DB_NAME !== 'pvdak_ci' ||
     ! defined( 'DB_HOST' ) || DB_HOST !== '127.0.0.1' ||
     ! getenv( 'RUNNER_TEMP' ) ||
     realpath( ABSPATH ) !== realpath( getenv( 'RUNNER_TEMP' ) . '/pvdak-wp' ) ||
     wp_parse_url( site_url(), PHP_URL_HOST ) !== '127.0.0.1' ) {
    fwrite( STDERR, "D12_REST_ABORT: disposable GitHub Actions WordPress only\n" );
    exit( 2 );
}
$users = array(
    'client' => get_user_by( 'login', 'ci_d11_browser_client' ),
    'lawyer' => get_user_by( 'login', 'ci_d11_browser_lawyer' ),
);
$admin = get_user_by( 'login', 'ciadmin' );
if ( ! $admin || ! $users['client'] || ! $users['lawyer'] ||
     ! function_exists( 'pvdak_core_register_post_types' ) ||
     ! function_exists( 'rest_do_request' ) ||
     ! function_exists( 'pvdak_core_intake_open' ) ||
     pvdak_core_intake_open() || pvdak_core_member_intake_open() ) {
    WP_CLI::error( 'D12_REST_ABORT: Core, intake-off, or fixture prerequisites missing.' );
}
$posts = array();
foreach ( $users as $alias => $user ) {
    if ( get_user_meta( $user->ID, '_pvdak_d11_browser_fixture', true ) !== '1' ) {
        WP_CLI::error( 'D12_REST_ABORT: fixture user marker mismatch.' );
    }
    $ids = get_user_meta( $user->ID, '_pvdak_d11_browser_posts', true );
    if ( ! is_array( $ids ) || count( $ids ) !== 2 ) {
        WP_CLI::error( 'D12_REST_ABORT: fixture post list missing.' );
    }
    foreach ( $ids as $id ) {
        $post = get_post( $id );
        if ( ! $post || (int) $post->post_author !== (int) $user->ID ||
             'private' !== $post->post_status ||
             get_post_meta( $id, '_pvdak_d11_browser_fixture', true ) !== '1' ||
             ! in_array( $post->post_type, array( 'pvdak_request', 'pvdak_professional' ), true ) ) {
            WP_CLI::error( 'D12_REST_ABORT: private fixture ownership mismatch.' );
        }
        $posts[ $alias ][ $post->post_type ] = $post;
    }
    if ( count( $posts[ $alias ] ) !== 2 ) {
        WP_CLI::error( 'D12_REST_ABORT: both private fixture types required.' );
    }
}
$original_user = get_current_user_id();
$original_nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? $_SERVER['HTTP_X_WP_NONCE'] : null;
$checks = 0;
$failures = 0;
$assert = function ( $condition, $label ) use ( &$checks, &$failures ) {
    ++$checks;
    if ( ! $condition ) {
        ++$failures;
        WP_CLI::warning( 'D12_REST_FAIL: ' . $label );
    }
};
$as_user = function ( $id ) {
    wp_set_current_user( (int) $id );
    if ( $id ) {
        // In-process request: valid WP REST nonce bound to the simulated
        // current user. This does not substitute for browser cookie testing.
        $_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
    } else {
        unset( $_SERVER['HTTP_X_WP_NONCE'] );
    }
};
$call = function ( $route, $params = array() ) {
    $request = new WP_REST_Request( 'GET', $route );
    foreach ( $params as $key => $value ) { $request->set_param( $key, $value ); }
    return rest_do_request( $request );
};
$not_exposed = function ( $response ) use ( $posts ) {
    $json = wp_json_encode( $response->get_data() );
    if ( ! is_string( $json ) ) { return false; }
    foreach ( $posts as $by_type ) {
        foreach ( $by_type as $post ) {
            if ( false !== strpos( $json, $post->post_title ) ) { return false; }
        }
    }
    return true;
};
try {
    $routes = rest_get_server()->get_routes();
    foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type ) {
        $object = get_post_type_object( $type );
        $assert( $object && ! $object->public && ! $object->publicly_queryable &&
            ! $object->show_in_rest && $object->exclude_from_search,
            'Private CPT registration: ' . $type );
        $assert( ! isset( $routes['/wp/v2/' . $type] ) &&
            ! isset( $routes['/wp/v2/' . $type . '/(?P<id>[\\d]+)'] ),
            'No private CPT REST route: ' . $type );
    }
    foreach ( array( 'subscriber', 'pvdak_client', 'pvdak_lawyer', 'pvdak_trainee', 'pvdak_student' ) as $role_name ) {
        $role = get_role( $role_name );
        $assert( $role && $role->has_cap( 'read' ) &&
            ! $role->has_cap( 'edit_pvdak_requests' ) &&
            ! $role->has_cap( 'edit_pvdak_professionals' ) &&
            ! $role->has_cap( 'manage_options' ),
            'Non-admin role restricted: ' . $role_name );
    }
    $actors = array( 'anonymous' => 0, 'client' => $users['client']->ID,
        'lawyer' => $users['lawyer']->ID );
    foreach ( $actors as $alias => $id ) {
        $as_user( $id );
        foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type ) {
            $response = $call( '/wp/v2/' . $type );
            $assert( 404 === $response->get_status(), 'CPT collection inaccessible: ' . $alias . '/' . $type );
        }
        foreach ( $posts as $owner => $by_type ) {
            foreach ( $by_type as $post ) {
                $response = $call( '/wp/v2/posts/' . $post->ID );
                $assert( in_array( $response->get_status(), array( 403, 404 ), true ) &&
                    $not_exposed( $response ),
                    'Private post not exposed via public posts endpoint: ' . $alias . '/' . $owner . '/' . $post->post_type );
            }
        }
        foreach ( $posts as $owner => $by_type ) {
            $response = $call( '/wp/v2/search', array(
                'search' => $by_type['pvdak_request']->post_title, 'per_page' => 20 ) );
            $assert( 200 === $response->get_status() && $not_exposed( $response ),
                'REST search excludes private title: ' . $alias . '/' . $owner );
        }
        $me = $call( '/wp/v2/users/me' );
        $assert( $id ? ( 200 === $me->get_status() &&
            (int) ( $me->get_data()['id'] ?? 0 ) === (int) $id ) :
            in_array( $me->get_status(), array( 401, 403 ), true ),
            'Current-user REST identity scoped: ' . $alias );
        $users_edit = $call( '/wp/v2/users', array( 'context' => 'edit' ) );
        $assert( in_array( $users_edit->get_status(), array( 401, 403 ), true ),
            'User administration REST denied: ' . $alias );
        if ( $id ) {
            foreach ( $posts as $owner => $by_type ) {
                $assert( ! current_user_can( 'edit_post', $by_type['pvdak_request']->ID ) &&
                    ! current_user_can( 'edit_post', $by_type['pvdak_professional']->ID ),
                    'Private CPT admin edits denied: ' . $alias . '/' . $owner );
            }
        }
    }
    $as_user( $admin->ID );
    foreach ( $posts as $owner => $by_type ) {
        $assert( current_user_can( 'edit_post', $by_type['pvdak_request']->ID ) &&
            current_user_can( 'edit_post', $by_type['pvdak_professional']->ID ),
            'Admin can manage private CPT fixtures: ' . $owner );
    }
} finally {
    wp_set_current_user( $original_user );
    if ( null === $original_nonce ) { unset( $_SERVER['HTTP_X_WP_NONCE'] ); }
    else { $_SERVER['HTTP_X_WP_NONCE'] = $original_nonce; }
}
WP_CLI::line( sprintf( 'PVDAK_D12_REST_MATRIX: checks=%d failures=%d cleanup=delegated_to_D11_EXIT_trap', $checks, $failures ) );
if ( $failures || $checks !== 46 ) {
    WP_CLI::error( 'D12 isolated REST/role matrix failed; no gate promotion.' );
}
WP_CLI::success( 'D12 isolated WordPress REST/role matrix passed; live HTTP/staging not tested.' );
