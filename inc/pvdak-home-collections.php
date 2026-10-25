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

/**
 * A published Page is not a usable FAQ answer merely because it has a body.
 * Reuse this one predicate in the public accordion and the admin checklist.
 */
function pvdak_home_faq_answer_ready( $page ) {
    if ( ! $page instanceof WP_Post ) { return false; }
    $title = trim( wp_strip_all_tags( (string) get_the_title( $page->ID ) ) );
    $body = trim( wp_strip_all_tags( strip_shortcodes( $page->post_content ) ) );
    // A visually blank answer may consist only of NBSP/zero-width markers.
    $body = trim( preg_replace( '/[\\x{00A0}\\x{200B}\\x{200C}\\x{200D}\\x{FEFF}]/u', ' ', $body ) );
    $length = function_exists( 'mb_strlen' ) ?
        mb_strlen( $body, 'UTF-8' ) : preg_match_all( '/[\\s\\S]/u', $body );
    return '' !== $title && $length >= 15 &&
        false === stripos( $body, 'lorem ipsum' ) &&
        false === strpos( $body, 'این بخش برای پاسخ به پرسش‌های عمومی و راهنمای استفاده از خدمات در حال آماده‌سازی است' ) &&
        false === strpos( $body, 'پاسخ نمونه برای تکمیل' );
}

/** Native hierarchical Pages are suitable for short informational FAQs. */
function pvdak_home_faq_pages( $include_empty = false ) {
    $page_id = (int) get_option( 'page_on_front' );
    $value = $page_id && function_exists( 'get_field' ) ?
        get_field( 'faq_parent_page', $page_id ) : null;
    $id = $value instanceof WP_Post ? (int) $value->ID : absint( $value );
    // The public FAQ Page already exists in WordPress. If ACF has not been
    // configured, use its published children rather than hiding valid FAQs.
    // Never replace an explicitly chosen (even invalid) editorial parent.
    if ( ! $id && ! $value ) {
        $default_faq = get_page_by_path( 'legal-faq', OBJECT, 'page' );
        $id = $default_faq instanceof WP_Post && 'publish' === $default_faq->post_status ?
            (int) $default_faq->ID : 0;
    }
    if ( ! $id || 'page' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
        return array();
    }
    $published = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_parent'    => $id,
        'posts_per_page' => -1,
        'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ) );
    // Read all published direct children before filtering. A fixed 30-item
    // query silently hid valid answers after older placeholders.
    // Admin readiness must also see the published-but-empty source Pages;
    // the public homepage keeps using only the answered subset.
    if ( $include_empty ) { return $published; }
    // D08/D10: a published empty FAQ Page should never become a blank
    // accordion in the employer demo. Keep the source Page untouched.
    $answered = array_values( array_filter( $published, static function( $page ) {
        return pvdak_home_faq_answer_ready( $page );
    } ) );
    return array_slice( $answered, 0, 12 );
}

/**
 * Resolve only published internal Pages with the installed shortcode.
 * A manual ACF URL is a suggestion, not proof that its workflow is usable.
 */
function pvdak_home_shortcode_page_url( $candidate, $tag ) {
    if ( ! is_string( $candidate ) || '' === trim( $candidate ) ||
         ! shortcode_exists( $tag ) ) { return ''; }
    $id = url_to_postid( trim( $candidate ) );
    $page = $id ? get_post( $id ) : null;
    if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ||
         'publish' !== $page->post_status ||
         ! has_shortcode( $page->post_content, $tag ) ) { return ''; }
    return get_permalink( $page->ID );
}

/** Ensure each request CTA matches the exact legal-request type it advertises. */
function pvdak_home_request_page_url( $candidate, $type ) {
    $url = pvdak_home_shortcode_page_url( $candidate, 'pvdak_request_form' );
    if ( ! $url ) { return ''; }
    $page = get_post( url_to_postid( $url ) );
    if ( ! $page instanceof WP_Post ) { return ''; }
    $pattern = '/' . get_shortcode_regex( array( 'pvdak_request_form' ) ) . '/s';
    if ( ! preg_match_all( $pattern, $page->post_content, $matches, PREG_SET_ORDER ) ) {
        return '';
    }
    foreach ( $matches as $match ) {
        if ( 'pvdak_request_form' !== $match[2] ) { continue; }
        $atts = shortcode_parse_atts( $match[3] );
        $actual = is_array( $atts ) && isset( $atts['type'] ) ?
            sanitize_key( $atts['type'] ) : 'lawyer';
        if ( $actual === $type ) { return $url; }
    }
    return '';
}

/**
 * Service CTA overrides and default Pages are gated by a real form and type.
 * A wrong/external/draft editor URL silently falls back to the canonical Page.
 */
function pvdak_home_service_url( $index ) {
    if ( ! function_exists( 'pvdak_core_intake_open' ) || ! pvdak_core_intake_open() ) { return ''; }
    $routes = array(
        1 => array( 'request-lawyer', 'lawyer' ),
        2 => array( 'request-consultation', 'consultation' ),
        3 => array( 'request-question', 'question' ),
    );
    $index = absint( $index );
    if ( ! isset( $routes[ $index ] ) ) { return ''; }
    list( $slug, $type ) = $routes[ $index ];
    $manual = pvdak_home_value( 'service_' . $index . '_url', '' );
    $url = pvdak_home_request_page_url( $manual, $type );
    if ( $url ) { return $url; }
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    return $page instanceof WP_Post ?
        pvdak_home_request_page_url( get_permalink( $page->ID ), $type ) : '';
}

/** Membership CTA must lead to a published internal application form. */
function pvdak_home_join_url() {
    if ( ! function_exists( 'pvdak_core_member_intake_open' ) ||
         ! pvdak_core_member_intake_open() ) { return ''; }
    $manual = pvdak_home_value( 'community_url', '' );
    $url = pvdak_home_shortcode_page_url( $manual, 'pvdak_professional_application' );
    if ( $url ) { return $url; }
    $page = get_page_by_path( 'join-legal-community', OBJECT, 'page' );
    return $page instanceof WP_Post ?
        pvdak_home_shortcode_page_url( get_permalink( $page->ID ), 'pvdak_professional_application' ) : '';
}
