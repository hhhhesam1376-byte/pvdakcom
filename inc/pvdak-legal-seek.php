<?php
/**
 * D31: bounded keyset queries for public legal-topic navigation.
 * Cursor payloads are signed, parent/search scoped and contain only public
 * ordering fields. No OFFSET, no user-specific state and no writes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_legal_seek_tuple( $post ) {
    if ( ! $post instanceof WP_Post || ! isset( $post->ID, $post->menu_order, $post->post_title ) ||
         ! is_string( $post->post_title ) ||
         ! preg_match( '/^-?(?:0|[1-9][0-9]{0,9})$/D', (string) $post->menu_order ) ) { return null; }
    $id = pvdak_home_configured_page_id( $post->ID );
    if ( ! $id ) { return null; }
    // Encode and decode must share the same contract: a malformed title must
    // never create an unusable signed cursor or a broken next-page link.
    $tuple = array( (int) $post->menu_order, $post->post_title, $id );
    return pvdak_legal_seek_tuple_valid( $tuple ) ? $tuple : null;
}

function pvdak_legal_seek_encode( $post, $context ) {
    $tuple = pvdak_legal_seek_tuple( $post );
    if ( null === $tuple ) { return ''; }
    $json = wp_json_encode( array( 1, $context, $tuple ), JSON_UNESCAPED_UNICODE );
    if ( ! is_string( $json ) ) { return ''; }
    $payload = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
    if ( strlen( $payload ) > 1800 ) { return ''; }
    return $payload . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
}

/** Reject malformed cursors before building a SQL predicate or query. */
function pvdak_legal_seek_tuple_valid( $tuple ) {
    return is_array( $tuple ) && array_keys( $tuple ) === array( 0, 1, 2 ) &&
        is_int( $tuple[0] ) &&
        1 === preg_match( '/^-?(?:0|[1-9][0-9]{0,9})$/D', (string) $tuple[0] ) &&
        is_string( $tuple[1] ) && strlen( $tuple[1] ) <= 1024 &&
        1 === preg_match( '//u', $tuple[1] ) &&
        is_int( $tuple[2] ) && $tuple[2] > 0;
}

function pvdak_legal_seek_decode( $token, $context ) {
    if ( ! is_string( $token ) || strlen( $token ) > 1865 ||
         ! preg_match( '/^([A-Za-z0-9_-]{1,1800})\.([a-f0-9]{64})$/D', $token, $parts ) ||
         ! hash_equals( hash_hmac( 'sha256', $parts[1], wp_salt( 'auth' ) ), $parts[2] ) ) { return null; }
    $raw = base64_decode( strtr( $parts[1], '-_', '+/' ), true );
    $decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
    if ( ! is_array( $decoded ) || array_keys( $decoded ) !== array( 0, 1, 2 ) ||
         1 !== $decoded[0] || $context !== $decoded[1] ||
         ! is_array( $decoded[2] ) || 3 !== count( $decoded[2] ) ) { return null; }
    $tuple = $decoded[2];
    if ( ! pvdak_legal_seek_tuple_valid( $tuple ) ) { return null; }
    return $tuple;
}

/**
 * WP_Query lacks a native compound keyset cursor. Attach the prepared WHERE
 * only to this exact query object and always detach the filter afterward.
 * The same collation and menu_order/title/ID order are used for seek and sort.
 */
function pvdak_legal_seek_rows( $parent_id, $limit, $tuple = null, $direction = 'after', $include_password_for_staff_audit = false ) {
    global $wpdb;
    // Staff audit counts password-protected FAQ Pages. Never expose this mode
    // to callers without the WordPress capability to edit Pages.
    if ( $include_password_for_staff_audit &&
         ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_pages' ) ) ) {
        return array();
    }
    // Internal callers must supply a canonical parent, positive integer limit,
    // known direction and a fully validated keyset tuple. Do not let WP_Query
    // coerce malformed values into a different published Page or cursor.
    $parent_id = pvdak_home_configured_page_id( $parent_id );
    if ( ! $parent_id || ! is_int( $limit ) || $limit < 1 ||
         ! in_array( $direction, array( 'after', 'before' ), true ) ||
         ! is_bool( $include_password_for_staff_audit ) ||
         ( null !== $tuple && ! pvdak_legal_seek_tuple_valid( $tuple ) ) ) {
        return array();
    }
    $scope = new stdClass();
    $reverse = 'before' === $direction;
    $op = $reverse ? '<' : '>';
    $filter = static function ( $where, $query ) use ( $wpdb, $scope, $tuple, $op ) {
        if ( $query->get( 'pvdak_legal_seek_scope' ) !== $scope || null === $tuple ) { return $where; }
        $table = $wpdb->posts;
        return $where . $wpdb->prepare(
            " AND ( {$table}.menu_order {$op} %d OR ( {$table}.menu_order = %d AND " .
            "( {$table}.post_title {$op} %s OR ( {$table}.post_title = %s AND {$table}.ID {$op} %d ) ) ) )",
            $tuple[0], $tuple[0], $tuple[1], $tuple[1], $tuple[2]
        );
    };
    if ( null !== $tuple ) { add_filter( 'posts_where', $filter, 20, 2 ); }
    // Public callers stay capped at 41; the staff-only FAQ audit reads 80 + 1.
    $max_rows = $include_password_for_staff_audit ? 81 : 41;
    $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'posts_per_page' => max( 1, min( $max_rows, (int) $limit ) ),
            'orderby' => array( 'menu_order' => $reverse ? 'DESC' : 'ASC',
                'title' => $reverse ? 'DESC' : 'ASC', 'ID' => $reverse ? 'DESC' : 'ASC' ),
            'no_found_rows' => true,
            'suppress_filters' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'pvdak_legal_seek_scope' => $scope,
    );
    if ( ! $include_password_for_staff_audit ) { $args['has_password'] = false; }
    try {
        $rows = get_posts( $args );
    } finally {
        if ( null !== $tuple ) { remove_filter( 'posts_where', $filter, 20 ); }
    }
    return is_array( $rows ) ? $rows : array();
}
