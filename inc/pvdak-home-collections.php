<?php
/**
 * Read-only homepage selectors for native WordPress content.
 * Content creation/management stays in Posts, Categories and Pages.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ACF Free taxonomy-select stores term IDs (but accepts object return too). */
function pvdak_home_selected_category( $field ) {
    $page_id = (int) get_option( 'page_on_front' );
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return 0; }
    $value = get_field( $field, $page_id );
    $id = $value instanceof WP_Term ? (int) $value->term_id : absint( $value );
    $term = $id ? get_term( $id, 'category' ) : null;
    return ( $term && ! is_wp_error( $term ) ) ? $id : 0;
}

/** Featured post selector is an editorial override, not a duplicate article. */
function pvdak_home_selected_post( $field ) {
    $page_id = (int) get_option( 'page_on_front' );
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return null; }
    $selected = get_field( $field, $page_id );
    $id = $selected instanceof WP_Post ? (int) $selected->ID : absint( $selected );
    $post = $id ? get_post( $id ) : null;
    if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) { return null; }
    // Featured override must belong to the matching selected category.
    $category_field = 'magazine_featured_post' === $field ? 'magazine_category' :
        ( 'news_featured_post' === $field ? 'news_category' : '' );
    if ( $category_field ) {
        $category_id = pvdak_home_selected_category( $category_field );
        if ( ! $category_id || ! has_category( $category_id, $post ) ) { return null; }
    }
    return $post;
}

function pvdak_home_category_posts( $field, $limit = 3, $exclude = array() ) {
    $term_id = pvdak_home_selected_category( $field );
    if ( ! $term_id ) { return array(); } // Never show unrelated posts.
    return get_posts( array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'cat'                    => $term_id,
        'posts_per_page'         => max( 1, min( 12, (int) $limit ) ),
        'post__not_in'           => array_map( 'absint', $exclude ),
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'ignore_sticky_posts'    => true,
        'suppress_filters'       => false,
        'no_found_rows'         => true,
    ) );
}

function pvdak_home_post_summary( $post, $words = 22 ) {
    if ( ! $post instanceof WP_Post ) { return ''; }
    $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
    return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $text ) ), $words, '…' );
}

/** Native hierarchical Pages are suitable for short informational FAQs. */
function pvdak_home_faq_pages() {
    $page_id = (int) get_option( 'page_on_front' );
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return array(); }
    $value = get_field( 'faq_parent_page', $page_id );
    $id = $value instanceof WP_Post ? (int) $value->ID : absint( $value );
    if ( ! $id || 'page' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
        return array();
    }
    return get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_parent'    => $id,
        'posts_per_page' => 12,
        'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
}
