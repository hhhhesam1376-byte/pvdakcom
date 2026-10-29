<?php
/**
 * D31: public legal-topic index, seek pagination without expensive SQL OFFSET.
 * Preserve editorial menu_order/title/ID ordering and keep navigation stateless.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_legal_index_request() {
    $after = isset( $_GET['pvdak_topics_after'] ) ? $_GET['pvdak_topics_after'] : '';
    $before = isset( $_GET['pvdak_topics_before'] ) ? $_GET['pvdak_topics_before'] : '';
    // An ambiguous or malformed query always returns the first window.
    if ( ( '' !== $after && '' !== $before ) ||
         ( ! is_string( $after ) || ! is_string( $before ) ) ) {
        return array( 'token' => '', 'direction' => 'after' );
    }
    return '' !== $before ? array( 'token' => $before, 'direction' => 'before' ) :
        array( 'token' => $after, 'direction' => 'after' );
}

function pvdak_legal_index_window( $parent_id, $token = '', $direction = 'after' ) {
    $result = array( 'pages' => array(), 'next_cursor' => '', 'prev_cursor' => '', 'active' => false );
    $parent_id = pvdak_home_configured_page_id( $parent_id );
    $parent = $parent_id ? get_post( $parent_id ) : null;
    if ( ! $parent instanceof WP_Post || 'page' !== $parent->post_type ||
         'publish' !== $parent->post_status || ! isset( $parent->post_password ) ||
         '' !== $parent->post_password ) { return $result; }
    $context = 'index:' . $parent_id;
    $tuple = '' !== $token ? pvdak_legal_seek_decode( $token, $context ) : null;
    $result['active'] = null !== $tuple;
    $reverse = $result['active'] && 'before' === $direction;
    $rows = pvdak_legal_seek_rows( $parent_id, 25, $tuple, $reverse ? 'before' : 'after' );
    $has_more = count( $rows ) > 24;
    $visible = array_slice( $rows, 0, 24 );
    if ( $reverse ) { $visible = array_reverse( $visible ); }
    $seen = array();
    foreach ( $visible as $post ) {
        if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ||
             'publish' !== $post->post_status ||
             pvdak_home_configured_page_id( $post->post_parent ) !== $parent_id ||
             ! isset( $post->post_password ) || '' !== $post->post_password ||
             null === pvdak_legal_seek_tuple( $post ) ) { continue; }
        $id = pvdak_home_configured_page_id( $post->ID );
        if ( ! $id || isset( $seen[ $id ] ) ) { continue; }
        $seen[ $id ] = true;
        $result['pages'][] = $post;
    }
    if ( ! $result['pages'] ) { return $result; }
    $first = reset( $result['pages'] );
    $last = end( $result['pages'] );
    $result['prev_cursor'] = ( $reverse ? $has_more : $result['active'] ) ?
        pvdak_legal_seek_encode( $first, $context ) : '';
    $result['next_cursor'] = ( $reverse ? true : $has_more ) ?
        pvdak_legal_seek_encode( $last, $context ) : '';
    return $result;
}

/** Cursor/legacy query URLs are navigable but not separate SEO landings. */
function pvdak_legal_index_robots( $robots ) {
    if ( is_page_template( 'page-templates/legal-topics-index.php' ) &&
         ( isset( $_GET['pvdak_topics_after'] ) || isset( $_GET['pvdak_topics_before'] ) ||
           isset( $_GET['pvdak_topics_page'] ) ) ) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_legal_index_robots', 20 );
