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
    return array( (int) $post->menu_order, $post->post_title, $id );
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

function pvdak_legal_seek_decode( $token, $context ) {
    if ( ! is_string( $token ) || strlen( $token ) > 1865 ||
         ! preg_match( '/^([A-Za-z0-9_-]{1,1800})\.([a-f0-9]{64})$/D', $token, $parts ) ||
         ! hash_equals( hash_hmac( 'sha256', $parts[1], wp_salt( 'auth' ) ), $parts[2] ) ) { return null; }
    $raw = base64_decode( strtr( $parts[1], '-_', '+/' ), true );
    $decoded = is_string( $raw ) ? json_decode( $raw, true ) : null;
    if ( ! is_array( $decoded ) || 3 !== count( $decoded ) ||
         1 !== $decoded[0] || $context !== $decoded[1] ||
         ! is_array( $decoded[2] ) || 3 !== count( $decoded[2] ) ) { return null; }
    $tuple = $decoded[2];
    if ( ! is_int( $tuple[0] ) || ! is_string( $tuple[1] ) ||
         strlen( $tuple[1] ) > 1024 || ! is_int( $tuple[2] ) || $tuple[2] < 1 ) { return null; }
    return $tuple;
}

/**
 * WP_Query lacks a native compound keyset cursor. Attach the prepared WHERE
 * only to this exact query object and always detach the filter afterward.
 * The same collation and menu_order/title/ID order are used for seek and sort.
 */
function pvdak_legal_seek_rows( $parent_id, $limit, $tuple = null, $direction = 'after' ) {
    global $wpdb;
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
    try {
        $rows = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'has_password' => false,
            'posts_per_page' => max( 1, min( 41, (int) $limit ) ),
            'orderby' => array( 'menu_order' => $reverse ? 'DESC' : 'ASC',
                'title' => $reverse ? 'DESC' : 'ASC', 'ID' => $reverse ? 'DESC' : 'ASC' ),
            'no_found_rows' => true,
            'suppress_filters' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'pvdak_legal_seek_scope' => $scope,
        ) );
    } finally {
        if ( null !== $tuple ) { remove_filter( 'posts_where', $filter, 20 ); }
    }
    return is_array( $rows ) ? $rows : array();
}
