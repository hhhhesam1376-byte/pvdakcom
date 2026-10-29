<?php
/**
 * Homepage quick-access ribbon: at most FOUR real published WordPress Pages.
 *
 * ACF home slots take priority; WP Menu's pvdak_quick location is a migration
 * fallback only when NO custom selection exists. Never list every menu item.
 * All labels and URLs come from the selected WP content, not hard-coded text.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Skip blank titles and links before counting a Page as a usable shortcut.
 * The readiness panel uses this exact resolver, so it cannot falsely pass.
 */
function pvdak_home_quick_link( $id ) {
    $title = get_the_title( $id );
    $url = get_permalink( $id );
    if ( ! is_string( $title ) || '' === trim( wp_strip_all_tags( $title ) ) ||
         ! is_string( $url ) || '' === trim( $url ) ) { return null; }
    return array( 'title' => $title, 'url' => $url, 'id' => $id );
}

/** A malformed editor selection is still explicit; never reveal stale menu links. */
function pvdak_home_quick_slot_configured( $value ) {
    if ( $value instanceof WP_Post ) { return true; }
    if ( is_string( $value ) ) { return '' !== trim( $value ) && '0' !== $value; }
    if ( is_array( $value ) ) { return ! empty( $value ); }
    return null !== $value && false !== $value && 0 !== $value;
}

function pvdak_home_quick_pages() {
    $slots = array();
    $any_explicit = false;
    $seen = array();
    for ( $i = 1; $i <= 4; $i++ ) {
        $value = pvdak_home_value( 'quick_page_' . $i, 0 );
        if ( pvdak_home_quick_slot_configured( $value ) ) { $any_explicit = true; }
        if ( $value instanceof WP_Post ) { $value = $value->ID; }
        $id = pvdak_home_configured_page_id( $value );
        if ( ! $id || isset( $seen[ $id ] ) || 'page' !== get_post_type( $id ) ||
            'publish' !== get_post_status( $id ) ) { continue; }
        $link = pvdak_home_quick_link( $id );
        if ( ! $link ) { continue; }
        $seen[ $id ] = true;
        $slots[] = $link;
    }
    if ( $any_explicit ) {
        return $slots; // Never mix explicit editor selections with old menu items.
    }

    $locations = get_nav_menu_locations();
    $menu_id = isset( $locations['pvdak_quick'] ) ?
        pvdak_home_configured_page_id( $locations['pvdak_quick'] ) : 0;
    if ( ! $menu_id ) { return array(); }
    $items = wp_get_nav_menu_items( $menu_id );
    if ( ! is_array( $items ) ) { return array(); }
    foreach ( $items as $item ) {
        // Quick ribbon is for Pages only, not custom links or entire submenus.
        if ( ! isset( $item->object, $item->object_id ) ||
             'page' !== $item->object ) { continue; }
        $id = pvdak_home_configured_page_id( $item->object_id );
        if ( ! $id || isset( $seen[ $id ] ) || 'page' !== get_post_type( $id ) ||
             'publish' !== get_post_status( $id ) ) { continue; }
        $link = pvdak_home_quick_link( $id );
        if ( ! $link ) { continue; }
        $seen[ $id ] = true;
        $slots[] = $link;
        if ( count( $slots ) === 4 ) { break; }
    }
    return $slots;
}
