<?php
/**
 * Read-only homepage selectors for native WordPress content.
 * Content creation/management stays in Posts, Categories and Pages.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ACF Free taxonomy-select stores term IDs (but accepts object return too). */
/**
 * Homepage collections must read ACF from the same published static Page
 * as the public homepage. Draft/private Pages and malformed Reading options
 * must never leak an unrelated editorial selection into public sections.
 */
function pvdak_home_collection_page_id() {
    $page_id = pvdak_home_page_id();
    return $page_id && 'publish' === get_post_status( $page_id ) ? $page_id : 0;
}

/** ACF ID, WP_Post and WP_Term return formats; reject lossy PHP casts. */
function pvdak_home_collection_selected_id( $value ) {
    if ( $value instanceof WP_Post ) { $value = $value->ID; }
    elseif ( $value instanceof WP_Term ) { $value = $value->term_id; }
    return pvdak_home_configured_page_id( $value );
}

function pvdak_home_selected_category( $field ) {
    $page_id = pvdak_home_collection_page_id();
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return 0; }
    $value = get_field( $field, $page_id );
    $id = pvdak_home_collection_selected_id( $value );
    $term = $id ? get_term( $id, 'category' ) : null;
    return ( $term && ! is_wp_error( $term ) ) ? $id : 0;
}

/** Featured post selector is an editorial override, not a duplicate article. */
/** D31: never expose protected/draft posts through editorial selections or summaries. */
function pvdak_home_public_post_candidate( $post ) {
    return $post instanceof WP_Post &&
        isset( $post->post_type, $post->post_status, $post->post_password, $post->ID ) &&
        'post' === $post->post_type && 'publish' === $post->post_status &&
        '' === $post->post_password &&
        pvdak_home_configured_page_id( $post->ID ) > 0;
}

function pvdak_home_selected_post( $field ) {
    $page_id = pvdak_home_collection_page_id();
    if ( ! $page_id || ! function_exists( 'get_field' ) ) { return null; }
    $selected = get_field( $field, $page_id );
    $id = pvdak_home_collection_selected_id( $selected );
    $post = $id ? get_post( $id ) : null;
    if ( ! pvdak_home_public_post_candidate( $post ) ) { return null; }
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
    if ( ! $term_id ) { return array(); }
    $limit = max( 1, min( 12, (int) $limit ) );
    $excluded = array_map( 'absint', is_array( $exclude ) ? $exclude : array() );
    $rows = get_posts( array(
        'post_type' => 'post', 'post_status' => 'publish',
        'has_password' => false, 'cat' => $term_id,
        'posts_per_page' => $limit, 'post__not_in' => $excluded,
        'orderby' => 'date', 'order' => 'DESC',
        'ignore_sticky_posts' => true, 'suppress_filters' => false,
        'no_found_rows' => true, 'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ) );
    if ( ! is_array( $rows ) ) { return array(); }
    $safe = array();
    $seen = array_fill_keys( $excluded, true );
    foreach ( $rows as $post ) {
        if ( ! pvdak_home_public_post_candidate( $post ) ||
             ! has_category( $term_id, $post ) ) { continue; }
        $id = pvdak_home_configured_page_id( $post->ID );
        if ( isset( $seen[ $id ] ) ) { continue; }
        $seen[ $id ] = true;
        $safe[] = $post;
        if ( count( $safe ) >= $limit ) { break; }
    }
    return $safe;
}

function pvdak_home_post_summary( $post, $words = 22 ) {
    if ( ! pvdak_home_public_post_candidate( $post ) ) { return ''; }
    $text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
    return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $text ) ), $words, '…' );
}

/**
 * A published Page is not a usable FAQ answer merely because it has a body.
 * Reuse this one predicate in the public accordion and the admin checklist.
 */
function pvdak_home_faq_answer_ready( $page ) {
    // Never treat password-protected, unpublished or malformed content as
    // a public answer. The staff readiness count uses this same predicate.
    if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ||
         'publish' !== $page->post_status || ! isset( $page->post_password ) ||
         '' !== $page->post_password ||
         ! pvdak_home_configured_page_id( $page->ID ) ) { return false; }
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

/**
 * Public homepage FAQ is a 12-answer PREVIEW, not a full FAQ archive.
 * Fetch published children in bounded batches so a large collection of
 * empty placeholders does not allocate every Page on each homepage request.
 * The management/readiness path still inspects the full source separately.
 */
function pvdak_home_faq_public_candidate( $page, $parent_id ) {
    return $page instanceof WP_Post && 'page' === $page->post_type &&
        'publish' === $page->post_status &&
        isset( $page->post_password ) && '' === $page->post_password &&
        pvdak_home_configured_page_id( $page->ID ) > 0 &&
        pvdak_home_configured_page_id( $page->post_parent ) === $parent_id;
}

function pvdak_home_faq_public_preview( $parent_id ) {
    $answered = array();
    $seen = array();
    $tuple = null;
    // Seek by the last scanned row, not by OFFSET; scan at most 240 public FAQs.
    for ( $scanned = 0; $scanned < 240; $scanned += 40 ) {
        $published = pvdak_legal_seek_rows( $parent_id, 41, $tuple );
        $window = array_slice( $published, 0, 40 );
        if ( ! $window ) { break; }
        foreach ( $window as $page ) {
            // WP_Query may be modified by plugins: never trust a returned row.
            if ( ! pvdak_home_faq_public_candidate( $page, $parent_id ) ||
                 ! pvdak_home_faq_answer_ready( $page ) ) { continue; }
            $id = pvdak_home_configured_page_id( $page->ID );
            if ( isset( $seen[ $id ] ) ) { continue; }
            $seen[ $id ] = true;
            $answered[] = $page;
            if ( count( $answered ) >= 12 ) { return $answered; }
        }
        if ( count( $published ) <= 40 ) { break; }
        // A malformed final row must not become a public cursor.
        $tuple = pvdak_legal_seek_tuple( end( $window ) );
        if ( null === $tuple ) { break; }
    }
    return $answered;
}

/** Resolve the published FAQ parent once for both public and staff paths. */
function pvdak_home_faq_parent_id() {
    $page_id = pvdak_home_collection_page_id();
    $value = $page_id && function_exists( 'get_field' ) ?
        get_field( 'faq_parent_page', $page_id ) : null;
    $id = pvdak_home_collection_selected_id( $value );
    // An explicitly invalid editorial selection must never fall back.
    if ( ! $id && ! $value ) {
        $default_faq = get_page_by_path( 'legal-faq', OBJECT, 'page' );
        $id = $default_faq instanceof WP_Post && 'publish' === $default_faq->post_status &&
            isset( $default_faq->post_password ) && '' === $default_faq->post_password ?
            pvdak_home_configured_page_id( $default_faq->ID ) : 0;
    }
    $parent = $id ? get_post( $id ) : null;
    return $parent instanceof WP_Post && 'page' === $parent->post_type &&
        'publish' === $parent->post_status && isset( $parent->post_password ) &&
        '' === $parent->post_password ? $id : 0;
}

/**
 * D31: bounded staff FAQ audit. Count answers without allocating all Pages.
 * The cap is deliberately visible as incomplete, never a false readiness PASS.
 * A compatibility caller can request the collected Pages, but the readiness
 * panel uses counts only. Neither path writes to the database.
 */
function pvdak_home_faq_audit_scan( $parent_id, $collect = false ) {
    $report = array( 'total' => 0, 'written' => 0, 'complete' => false, 'pages' => array() );
    // This audit may include protected Pages and is never a public API.
    // A denied audit stays incomplete rather than reporting false readiness.
    if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'edit_pages' ) ) {
        return $report;
    }
    if ( ! $parent_id ) { $report['complete'] = true; return $report; }
    $tuple = null;
    // A modified query must not count the same FAQ twice, including across batches.
    // Keep only integer IDs (bounded to 10,000), not duplicate Page objects.
    $seen = array();
    // Bounded staff-only keyset scan: include password Pages in total, not written.
    // No deep OFFSET; at most 125 queries of 80 rows plus one look-ahead.
    for ( $scanned = 0; $scanned < 10000; $scanned += 80 ) {
        $rows = pvdak_legal_seek_rows( $parent_id, 81, $tuple, 'after', true );
        $window = array_slice( $rows, 0, 80 );
        foreach ( $window as $page ) {
            if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ||
                 'publish' !== $page->post_status ||
                 pvdak_home_configured_page_id( $page->post_parent ) !== $parent_id ||
                 ! pvdak_home_configured_page_id( $page->ID ) ||
                 null === pvdak_legal_seek_tuple( $page ) ) {
                return $report; // Never report a modified/invalid query as complete.
            }
            $id = pvdak_home_configured_page_id( $page->ID );
            if ( isset( $seen[ $id ] ) ) {
                return $report; // Duplicate rows must never make readiness pass.
            }
            $seen[ $id ] = true;
            ++$report['total'];
            if ( pvdak_home_faq_answer_ready( $page ) ) { ++$report['written']; }
            if ( $collect ) { $report['pages'][] = $page; }
        }
        if ( count( $rows ) <= 80 ) {
            $report['complete'] = true;
            return $report;
        }
        $tuple = pvdak_legal_seek_tuple( end( $window ) );
        if ( null === $tuple ) { return $report; }
    }
    // More than 10,000 published FAQs: partial counts, never a readiness PASS.
    return $report;
}

function pvdak_home_faq_audit_counts() {
    return pvdak_home_faq_audit_scan( pvdak_home_faq_parent_id() );
}

/** Native hierarchical Pages are suitable for short informational FAQs. */
function pvdak_home_faq_pages( $include_empty = false ) {
    $id = pvdak_home_faq_parent_id();
    if ( ! $id ) { return array(); }
    if ( ! $include_empty ) { return pvdak_home_faq_public_preview( $id ); }
    // Compatibility only; readiness must use the count-only API above.
    $audit = pvdak_home_faq_audit_scan( $id, true );
    return $audit['pages'];
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
         ! isset( $page->post_password ) || '' !== $page->post_password ||
         ! is_string( $page->post_content ) ||
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
