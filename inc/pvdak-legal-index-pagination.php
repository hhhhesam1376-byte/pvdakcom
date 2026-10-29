<?php
/**
 * D31: bounded, accessible pagination for the published legal-topic index.
 * The homepage's 49-card preview is intentionally separate from this archive.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Reject arrays, signed numbers and unbounded page offsets from public GET. */
function pvdak_legal_index_page_number() {
    if ( ! isset( $_GET['pvdak_topics_page'] ) ) { return 1; }
    $raw = $_GET['pvdak_topics_page'];
    return is_string( $raw ) && preg_match( '/^[1-9][0-9]{0,3}$/D', $raw ) ? (int) $raw : 1;
}

/**
 * Return at most 24 public child Pages, plus a lookahead for the next link.
 * Do not request a count of all Pages or load the full tree into memory.
 * A deterministic ID tie-breaker prevents duplicates within a static dataset.
 */
function pvdak_legal_index_window( $parent_id, $page = 1 ) {
    $result = array( 'pages' => array(), 'has_next' => false );
    $parent_id = pvdak_home_configured_page_id( $parent_id );
    if ( ! $parent_id || 'page' !== get_post_type( $parent_id ) ||
         'publish' !== get_post_status( $parent_id ) ||
         ! is_int( $page ) || $page < 1 || $page > 9999 ) { return $result; }
    $size = 24;
    $rows = get_posts( array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_parent' => $parent_id,
        'has_password' => false,
        'posts_per_page' => $size + 1,
        'offset' => ( $page - 1 ) * $size,
        'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC', 'ID' => 'ASC' ),
        'order' => 'ASC',
        'no_found_rows' => true,
        'suppress_filters' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );
    if ( ! is_array( $rows ) ) { return $result; }
    $result['has_next'] = count( $rows ) > $size && $page < 9999;
    foreach ( array_slice( $rows, 0, $size ) as $post ) {
        if ( $post instanceof WP_Post && 'page' === $post->post_type &&
             'publish' === $post->post_status && (int) $post->post_parent === $parent_id &&
             '' === $post->post_password ) {
            $result['pages'][] = $post;
        }
    }
    return $result;
}

/** Query-string archive pages are navigable but not duplicate SEO landings. */
function pvdak_legal_index_robots( $robots ) {
    if ( isset( $_GET['pvdak_topics_page'] ) &&
         is_page_template( 'page-templates/legal-topics-index.php' ) ) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_legal_index_robots', 20 );
