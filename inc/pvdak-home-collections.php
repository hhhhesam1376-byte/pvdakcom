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
function pvdak_home_faq_pages( $include_empty = false ) {
    $page_id = (int) get_option( 'page_on_front' );
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return array(); }
    $value = get_field( 'faq_parent_page', $page_id );
    $id = $value instanceof WP_Post ? (int) $value->ID : absint( $value );
    if ( ! $id || 'page' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
        return array();
    }
    $published = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_parent'    => $id,
        'posts_per_page' => 30,
        'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
    // Admin readiness must also see the published-but-empty source Pages;
    // the public homepage keeps using only the answered subset.
    if ( $include_empty ) { return $published; }
    // D08/D10: a published empty FAQ Page should never become a blank
    // accordion in the employer demo. Keep the source Page untouched.
    $answered = array_values( array_filter( $published, static function( $page ) {
        return $page instanceof WP_Post &&
            '' !== trim( wp_strip_all_tags( strip_shortcodes( $page->post_content ) ) );
    } ) );
    return array_slice( $answered, 0, 12 );
}

/**
 * Keep service CTAs synchronized with real published workflow pages.
 * ACF override takes precedence. Never link to drafts or inactive forms.
 */
function pvdak_home_service_url( $index ) {
    // D05/D10: a manually configured link must never make a closed intake
    // look usable in the employer-facing demo.
    if ( ! function_exists( 'pvdak_core_intake_open' ) || ! pvdak_core_intake_open() ) { return ''; }
    $manual = pvdak_home_value( 'service_' . absint( $index ) . '_url', '' );
    if ( is_string( $manual ) && '' !== trim( $manual ) ) { return $manual; }
    $slugs = array( 1 => 'request-lawyer', 2 => 'request-consultation', 3 => 'request-question' );
    if ( ! isset( $slugs[ $index ] ) ) { return ''; }
    $page = get_page_by_path( $slugs[ $index ], OBJECT, 'page' );
    if ( ! $page || 'publish' !== $page->post_status || ! has_shortcode( $page->post_content, 'pvdak_request_form' ) ) { return ''; }
    return get_permalink( $page->ID );
}

function pvdak_home_join_url() {
    if ( ! function_exists( 'pvdak_core_member_intake_open' ) || ! pvdak_core_member_intake_open() ) { return ''; }
    $manual = pvdak_home_value( 'community_url', '' );
    if ( is_string( $manual ) && '' !== trim( $manual ) ) { return $manual; }
    $page = get_page_by_path( 'join-legal-community', OBJECT, 'page' );
    if ( ! $page || 'publish' !== $page->post_status || ! has_shortcode( $page->post_content, 'pvdak_professional_application' ) ) { return ''; }
    return get_permalink( $page->ID );
}
