<?php
/**
 * Static regression checks for the versioned WordPress mega menu.
 * No WP/database/network, no test users or fixture writes.
 */
$root = getenv( 'PVDAK_THEME_SOURCE' );
if ( ! is_string( $root ) || ! is_dir( $root ) ) {
    fwrite( STDERR, "NAV_TEST: theme path not provided\n" );
    exit( 2 );
}
$files = array(
    'css' => $root . '/style.css',
    'func' => $root . '/functions.php',
    'header' => $root . '/header.php',
    'rtl' => $root . '/rtl.css',
);
foreach ( $files as $name => $path ) {
    if ( ! is_readable( $path ) ) {
        fwrite( STDERR, "NAV_TEST: required source missing: " . $name . "\n" );
        exit( 2 );
    }
}
$css = file_get_contents( $files['css'] );
$functions = file_get_contents( $files['func'] );
$header = file_get_contents( $files['header'] );
$rtl = file_get_contents( $files['rtl'] );
$checks = 0;
function pvdak_nav_assert( $okay, $label ) {
    global $checks;
    $checks++;
    if ( ! $okay ) {
        fwrite( STDERR, "NAV_FAIL: " . $label . "\n" );
        exit( 1 );
    }
}
pvdak_nav_assert( false !== strpos( $functions, "'pvdak-style'" ) &&
    false !== strpos( $functions, 'get_stylesheet_uri()' ),
    'Mega navigation is delivered by the standard WordPress theme stylesheet' );
// Validate the minimum version that introduced bundled mega-nav styling.
// This is intentionally not tied to a particular *current* theme release.
$theme_header_version = array();
pvdak_nav_assert(
    preg_match( '/^Version:[ \t]*([0-9]+(?:[.][0-9]+)*)[ \t]*$/mi', $css, $theme_header_version ) === 1 &&
    version_compare( $theme_header_version[1], '0.8.12', '>=' ) &&
    false === strpos( $functions, "'pvdak-navigation'" ) &&
    false === strpos( $functions, "/assets/css/pvdak-navigation.css" ),
    'Main versioned CSS is the single source and no extra stylesheet fetch remains' );
pvdak_nav_assert( 2 === substr_count( $header, "has_nav_menu( 'primary' )" ) &&
    2 === substr_count( $header, "'theme_location'=>'primary'" ),
    'Both desktop and mobile use the WordPress primary menu location' );
pvdak_nav_assert( false !== strpos( $css, '@media (min-width: 1031px)' ) &&
    false !== strpos( $css, '@media (max-width: 1030px)' ),
    'Responsive desktop and mobile modes are mutually exclusive' );
pvdak_nav_assert( false !== strpos( $css, 'menu-item-has-children' ),
    'Dropdown markers depend on native WordPress menu hierarchy' );
pvdak_nav_assert( false !== strpos( $css, 'li:hover > .sub-menu' ) &&
    false !== strpos( $css, 'li:focus-within > .sub-menu' ),
    'Popup opens for mouse hover and keyboard focus' );
pvdak_nav_assert( false !== strpos( $css, 'display: none;' ) &&
    false !== strpos( $css, 'display: grid;' ),
    'Submenus remain collapsed by default on desktop' );
pvdak_nav_assert( false !== strpos( $css, 'grid-template-columns: repeat(2, minmax(0, 1fr))' ),
    'Mega panel displays two RTL columns' );
pvdak_nav_assert( false !== strpos( $css, 'width: min(820px, calc(100vw - 40px))' ) &&
    false !== strpos( $css, 'max-height: min(72vh, 650px)' ),
    'Mega panel is viewport-bounded and scrollable' );
pvdak_nav_assert( false !== strpos( $css, 'var(--pvdak-accent)' ) &&
    false !== strpos( $css, '#087f72' ),
    'Navigation uses the existing PVDak accent and teal colors' );
pvdak_nav_assert( false !== strpos( $css, '.pvdak-mobile-links .sub-menu' ) &&
    false !== strpos( $css, 'position: static;' ),
    'Mobile nested menu stays inside its disclosure instead of floating' );
pvdak_nav_assert( false !== strpos( $header, '<details class="pvdak-mobile-menu">' ) &&
    false !== strpos( $rtl, '.pvdak-mobile-menu[open] > nav' ),
    'Touch/mobile native disclosure and RTL rules remain available' );
pvdak_nav_assert( false !== strpos( $css, 'prefers-reduced-motion' ),
    'Reduced-motion preferences are respected' );

// D06: three WordPress levels, independent nested toggles, real RTL header slots.
// No WordPress account/menus are modified by these source-only checks.
$mobile_js_path = $root . '/assets/js/pvdak-navigation.js';
pvdak_nav_assert(
    is_readable( $mobile_js_path ) &&
    false !== strpos( $functions, "'pvdak-mobile-navigation'" ) &&
    false !== strpos( $functions, "'/assets/js/pvdak-navigation.js'" ),
    'Independent mobile menu logic is enqueued on all pages, not homepage only' );
$mobile_js = file_get_contents( $mobile_js_path );
pvdak_nav_assert(
    2 === substr_count( $header, "'depth'=>3" ) &&
    false !== strpos( $header, "'menu_class'=>'pvdak-mobile-links'" ) &&
    false !== strpos( $header, "'menu_class'=>'menu'" ),
    'The same WordPress primary menu is capped at parent/child/grandchild on both layouts' );
pvdak_nav_assert(
    strpos( $header, '<details class="pvdak-mobile-menu">' ) <
        strpos( $header, '<div class="site-branding">' ) &&
    false !== strpos( $header, 'class="pvdak-login-button"' ) &&
    false !== strpos( $css, 'grid-template-areas:"mobile-menu mobile-brand mobile-account"' ) &&
    false !== strpos( $css, 'grid-area:mobile-menu' ) &&
    false !== strpos( $css, 'grid-area:mobile-brand' ) &&
    false !== strpos( $css, 'grid-area:mobile-account' ),
    'Mobile RTL header has right menu, centered brand and left account button' );
pvdak_nav_assert(
    false !== strpos( $mobile_js, "document.createElement('button')" ) &&
    false !== strpos( $mobile_js, "setAttribute('aria-expanded'" ) &&
    false !== strpos( $mobile_js, "setAttribute('aria-controls'" ) &&
    false !== strpos( $mobile_js, "submenu.hidden" ) &&
    false !== strpos( $mobile_js, "item.insertBefore(button, submenu)" ),
    'Child/grandchild disclosure buttons are separate from navigable parent links' );
pvdak_nav_assert(
    false !== strpos( $mobile_js, "event.key === 'Escape'" ) &&
    false !== strpos( $mobile_js, "menu.contains(event.target)" ) &&
    false !== strpos( $css, '.pvdak-mobile-menu.is-enhanced .pvdak-mobile-links .sub-menu[hidden]' ) &&
    false !== strpos( $css, 'display:none !important;' ),
    'Mobile hierarchy closes by Escape/outside click and hides collapsed descendants' );
pvdak_nav_assert(
    false !== strpos( $css, '.pvdak-mobile-links .sub-menu .sub-menu' ) &&
    false !== strpos( $css, 'max-height:min(75vh,650px)' ) &&
    false !== strpos( $header, 'pvdak-mobile-support-link' ),
    'Third-level links stay in scrollable right-aligned drawer; support remains reachable' );
echo "PVDAK_MEGA_NAV_OK: checks={$checks} (static source checks only)\n";
