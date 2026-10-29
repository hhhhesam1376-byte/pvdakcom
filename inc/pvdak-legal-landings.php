<?php
/**
 * Legal topic LANDING PAGES (not categories/taxonomies).
 *
 * Each topic is a normal hierarchical WordPress Page under a chosen parent.
 * The parent can be selected in the homepage editor (ACF Free), or resolved
 * by the default /legal-topics/ page slug.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * A nonempty ACF selection is authoritative, even when corrupt or unpublished.
 * Do not silently replace a deliberate editorial choice with an old fallback.
 */
function pvdak_legal_topic_selection_configured( $value ) {
    if ( $value instanceof WP_Post ) { return true; }
    if ( is_string( $value ) ) { return '' !== trim( $value ) && '0' !== $value; }
    if ( is_array( $value ) ) { return ! empty( $value ); }
    return null !== $value && false !== $value && 0 !== $value;
}

function pvdak_legal_topics_root_id() {
    // Use the same validated static front Page as the public ACF homepage.
    $front_id = pvdak_home_page_id();
    if ( $front_id && 'publish' === get_post_status( $front_id ) && function_exists( 'get_field' ) ) {
        $selected = get_field( 'legal_topics_parent_page', $front_id );
        if ( pvdak_legal_topic_selection_configured( $selected ) ) {
            $raw = $selected instanceof WP_Post ? $selected->ID : $selected;
            $id = pvdak_home_configured_page_id( $raw );
            $parent = $id ? get_post( $id ) : null;
            return $parent instanceof WP_Post && 'page' === $parent->post_type &&
                'publish' === $parent->post_status && isset( $parent->post_password ) &&
                '' === $parent->post_password ? $id : 0;
        }
    }

    // Only a genuinely blank selection may use the legacy published root.
    $fallback = get_page_by_path( 'legal-topics', OBJECT, 'page' );
    return $fallback instanceof WP_Post &&
        'page' === get_post_type( $fallback->ID ) &&
        'publish' === $fallback->post_status &&
        isset( $fallback->post_password ) && '' === $fallback->post_password ?
        pvdak_home_configured_page_id( $fallback->ID ) : 0;
}

function pvdak_legal_topic_pages( $parent_id = 0, $limit = 49 ) {
    if ( 0 === $parent_id || '0' === $parent_id ) {
        $parent_id = pvdak_legal_topics_root_id();
    } else {
        $parent_id = pvdak_home_configured_page_id( $parent_id );
    }
    $parent = $parent_id ? get_post( $parent_id ) : null;
    if ( ! $parent instanceof WP_Post || 'page' !== $parent->post_type ||
         'publish' !== $parent->post_status || ! isset( $parent->post_password ) ||
         '' !== $parent->post_password ) {
        return array();
    }
    $rows = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_parent'    => $parent_id,
        // All public callers are bounded; the GET search uses cursor batches.
        'posts_per_page' => is_int( $limit ) && $limit > 0 ? min( 96, $limit ) : 49,
        'has_password'   => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC', 'ID' => 'ASC' ),
        'order'          => 'ASC',
        'suppress_filters' => false,
        'no_found_rows'  => true,
    ) );
    // WP_Query filters may alter the result set. Never expose an unexpected
    // draft, password-protected Page, foreign parent or non-Page in public cards.
    if ( ! is_array( $rows ) ) { return array(); }
    $safe = array();
    $seen = array();
    foreach ( $rows as $page ) {
        if ( ! $page instanceof WP_Post ||
             'page' !== $page->post_type || 'publish' !== $page->post_status ||
             pvdak_home_configured_page_id( $page->post_parent ) !== $parent_id ||
             ! isset( $page->post_password ) || '' !== $page->post_password ) { continue; }
        $id = pvdak_home_configured_page_id( $page->ID );
        if ( ! $id || isset( $seen[ $id ] ) ) { continue; }
        $seen[ $id ] = true;
        $safe[] = $page;
        if ( count( $safe ) >= ( is_int( $limit ) && $limit > 0 ? min( 96, $limit ) : 49 ) ) { break; }
    }
    return $safe;
}

/**
 * Keep homepage search scoped to real published child landing Pages.
 * Works without JavaScript; browser filtering is progressive enhancement.
 */
function pvdak_legal_topic_search_term() {
    if ( ! isset( $_GET['pvdak_topic'] ) || ! is_string( $_GET['pvdak_topic'] ) ) { return ''; }
    $term = sanitize_text_field( wp_unslash( $_GET['pvdak_topic'] ) );
    $term = trim( $term );
    if ( function_exists( 'mb_substr' ) ) { return mb_substr( $term, 0, 100 ); }
    $chars = preg_split( '//u', $term, -1, PREG_SPLIT_NO_EMPTY );
    return is_array( $chars ) ? implode( '', array_slice( $chars, 0, 100 ) ) : '';
}

function pvdak_legal_normalize_topic_text( $value ) {
    $value = strtr( (string) $value, array( 'ي' => 'ی', 'ك' => 'ک', '‌' => ' ' ) );
    $value = preg_replace( '/[\x{064B}-\x{065F}\x{0670}]/u', '', $value );
    $value = preg_replace( '/\s+/u', ' ', $value );
    return trim( function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value ) );
}

function pvdak_legal_filter_topic_pages( $pages, $search_term ) {
    if ( ! is_array( $pages ) ) { return array(); }
    // Filter even for an empty search: an accidental draft/object in a
    // caller-supplied collection must not become a public navigation card.
    $seen = array();
    $pages = array_values( array_filter( $pages, static function ( $page ) use ( &$seen ) {
        if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ||
             'publish' !== $page->post_status ||
             ! isset( $page->post_password ) || '' !== $page->post_password ) { return false; }
        $id = pvdak_home_configured_page_id( $page->ID );
        if ( ! $id || isset( $seen[ $id ] ) ) { return false; }
        $seen[ $id ] = true;
        return true;
    } ) );
    $needle = pvdak_legal_normalize_topic_text( $search_term );
    if ( '' === $needle ) { return $pages; }
    return array_values( array_filter( $pages, function ( $page ) use ( $needle ) {
        $haystack = pvdak_legal_normalize_topic_text(
            $page->post_title . ' ' . wp_strip_all_tags( $page->post_excerpt )
        );
        return false !== strpos( $haystack, $needle );
    } ) );
}

/** Signed seek cursor from GET. Legacy numeric offsets are intentionally ignored. */
function pvdak_legal_topic_search_cursor() {
    $raw = isset( $_GET['pvdak_topic_after'] ) ? $_GET['pvdak_topic_after'] : '';
    return is_string( $raw ) && strlen( $raw ) <= 1865 ? $raw : '';
}

/**
 * Inspect at most 240 public Pages and return at most 48 matches per request.
 * Compound keyset navigation preserves menu_order/title/ID and avoids deep
 * SQL OFFSET. A signed token is bound to parent and normalized search term.
 * Empty result windows may still link to later published topics.
 */
function pvdak_legal_topic_search_batch( $parent_id, $search_term, $cursor = '' ) {
    $result = array( 'pages' => array(), 'next_cursor' => null, 'scanned' => 0 );
    $parent_id = pvdak_home_configured_page_id( $parent_id );
    $needle = pvdak_legal_normalize_topic_text( $search_term );
    $parent = $parent_id ? get_post( $parent_id ) : null;
    if ( ! $parent instanceof WP_Post || 'page' !== $parent->post_type ||
         'publish' !== $parent->post_status || ! isset( $parent->post_password ) ||
         '' !== $parent->post_password || '' === $needle ) { return $result; }
    $context = 'search:' . $parent_id . ':' . hash( 'sha256', $needle );
    $tuple = is_string( $cursor ) && '' !== $cursor ?
        pvdak_legal_seek_decode( $cursor, $context ) : null;
    $seen = array();
    $last_scanned = null;
    for ( $offset = 0; $offset < 240; $offset += 40 ) {
        $published = pvdak_legal_seek_rows( $parent_id, 41, $tuple );
        $window = array_slice( $published, 0, 40 );
        if ( ! $window ) { return $result; }
        // A plugin can return the same rows despite a scoped seek predicate.
        // Require progress in every batch, or stop rather than emit a replay cursor.
        $batch_last_scanned = null;
        foreach ( $window as $position => $page ) {
            ++$result['scanned'];
            if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ||
                 'publish' !== $page->post_status ||
                 pvdak_home_configured_page_id( $page->post_parent ) !== $parent_id ||
                 ! isset( $page->post_password ) || '' !== $page->post_password ||
                 null === pvdak_legal_seek_tuple( $page ) ) { continue; }
            $last_scanned = $page;
            $batch_last_scanned = $page;
            $id = pvdak_home_configured_page_id( $page->ID );
            if ( ! $id || isset( $seen[ $id ] ) ) { continue; }
            $seen[ $id ] = true;
            $haystack = pvdak_legal_normalize_topic_text(
                $page->post_title . ' ' . wp_strip_all_tags( $page->post_excerpt )
            );
            if ( false !== strpos( $haystack, $needle ) ) {
                $result['pages'][] = $page;
                if ( count( $result['pages'] ) === 48 ) {
                    if ( $position + 1 < count( $window ) || count( $published ) > 40 ) {
                        $result['next_cursor'] = pvdak_legal_seek_encode( $last_scanned, $context ) ?: null;
                    }
                    return $result;
                }
            }
        }
        if ( null === $batch_last_scanned || count( $published ) <= 40 ) { return $result; }
        $next_tuple = pvdak_legal_seek_tuple( $batch_last_scanned );
        if ( null === $next_tuple || ( null !== $tuple && $next_tuple === $tuple ) ) {
            return $result; // Non-advancing plugin result must not create an endless next link.
        }
        $tuple = $next_tuple;
    }
    if ( null !== $last_scanned ) {
        $result['next_cursor'] = pvdak_legal_seek_encode( $last_scanned, $context ) ?: null;
    }
    return $result;
}

/**
 * Search-result query URLs are transient filters, not separate SEO landings.
 * Keep the clean homepage indexable and preserve other robots directives.
 */
function pvdak_legal_search_robots( $robots ) {
    if ( is_front_page() && ( isset( $_GET['pvdak_topic'] ) || isset( $_GET['pvdak_topic_offset'] ) || isset( $_GET['pvdak_topic_after'] ) ) ) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_legal_search_robots', 20 );

function pvdak_legal_topic_icon( $post_id ) {
    $choices = array( 'family', 'property', 'criminal', 'contract', 'work', 'finance', 'inheritance', 'business' );
    $value = function_exists( 'get_field' ) ? get_field( 'pvdak_topic_icon', (int) $post_id ) : '';
    return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'contract';
}

function pvdak_legal_topic_svg( $name ) {
    $icons = array(
        'family'      => '<circle cx="9" cy="8" r="3"/><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M17 9a3 3 0 0 1 0 6m2 5v-2a4 4 0 0 0-3-3.87"/>',
        'property'    => '<path d="M3 10.5 12 3l9 7.5M5 9v12h14V9M9 21v-9h6v9"/>',
        'criminal'    => '<path d="m14 4 6 6m-9-3 6 6M3 21l10-10m-2-4 3-3 6 6-3 3m-4 3 3 3M4 20h16"/>',
        'contract'    => '<path d="M5 3h14v18H5zM8 7h8m-8 4h8m-8 4h5"/>',
        'work'        => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M9 7V4h6v3m-12 7h18M11 13h2"/>',
        'finance'     => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 11h18m-15 4h4"/>',
        'inheritance' => '<path d="m4 20 8-16 8 16M8 13h8M2 20h20"/>',
        'business'    => '<path d="M5 21V3h14v18M2 21h20M9 7h2m3 0h2M9 11h2m3 0h2M9 15h2m3 0h2"/>'
    );
    $name = isset( $icons[ $name ] ) ? $name : 'contract';
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}

/** Optional landing-page presentation fields; the main article stays in WP editor. */
function pvdak_legal_landing_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
    acf_add_local_field_group( array(
        'key'    => 'group_pvdak_legal_landing_v1',
        'title'  => 'تنظیمات لندینگ موضوع حقوقی',
        'fields' => array(
            array(
                'key' => 'field_pvdak_topic_icon',
                'label' => 'آیکون کارت در صفحه اصلی',
                'name' => 'pvdak_topic_icon',
                'type' => 'select',
                'choices' => array(
                    'family' => 'خانواده',
                    'property' => 'ملک و املاک',
                    'criminal' => 'کیفری',
                    'contract' => 'قرارداد و اسناد',
                    'work' => 'کار و بیمه',
                    'finance' => 'چک و مطالبات',
                    'inheritance' => 'ارث و وصیت',
                    'business' => 'کسب‌وکار',
                ),
                'default_value' => 'contract',
                'allow_null' => 0,
            ),
            array(
                'key' => 'field_pvdak_landing_intro',
                'label' => 'متن کوتاه زیر عنوان لندینگ',
                'name' => 'pvdak_landing_intro',
                'type' => 'textarea',
                'rows' => 3,
                'instructions' => 'اختیاری؛ محتوای اصلی لندینگ را در ویرایشگر معمول برگه بنویسید.',
            ),
        ),
        'location' => array( array( array(
            'param' => 'page_template',
            'operator' => '==',
            'value' => 'page-templates/legal-topic-landing.php',
        ) ) ),
        'active' => true,
        'position' => 'normal',
    ) );
}
add_action( 'acf/init', 'pvdak_legal_landing_fields' );
