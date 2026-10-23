<?php
/** No WP/DB: privacy-safe tests for hero, four editor-picked Pages & topics carousel. */
define( 'ABSPATH', __DIR__ );
class WP_Post { public $ID; function __construct( $id ) { $this->ID = $id; } }
$GLOBALS['quick_test_selection'] = array();
$GLOBALS['quick_test_menu'] = array();
$GLOBALS['quick_test_pages'] = array();
for ( $i = 1; $i <= 17; $i++ ) {
    $GLOBALS['quick_test_pages'][ $i ] = 'publish';
    $GLOBALS['quick_test_menu'][] = (object) array(
        'object' => 'page', 'object_id' => $i, 'title' => 'Menu ' . $i,
        'url' => 'https://example.invalid/page-' . $i
    );
}
function pvdak_home_value( $key, $default = 0 ) {
    return isset( $GLOBALS['quick_test_selection'][ $key ] ) ?
        $GLOBALS['quick_test_selection'][ $key ] : $default;
}
function absint( $v ) { return abs( (int) $v ); }
function get_nav_menu_locations() { return array( 'pvdak_quick' => 21 ); }
function wp_get_nav_menu_items( $id ) { return $GLOBALS['quick_test_menu']; }
function get_post_type( $id ) { return isset( $GLOBALS['quick_test_pages'][ $id ] ) ? 'page' : false; }
function get_post_status( $id ) {
    return isset( $GLOBALS['quick_test_pages'][ $id ] ) ? $GLOBALS['quick_test_pages'][ $id ] : false;
}
function get_the_title( $id ) { return 'Published Page ' . $id; }
function get_permalink( $id ) { return 'https://example.invalid/page-' . $id; }
require dirname( __DIR__, 2 ) . '/inc/pvdak-home-quick-links.php';
$checks = 0;
function pvdak_layout_expect( $okay, $message ) {
    global $checks;
    $checks++;
    if ( ! $okay ) { fwrite( STDERR, "LAYOUT_FAIL: " . $message . "\n" ); exit( 1 ); }
}
$links = pvdak_home_quick_pages();
pvdak_layout_expect( count( $links ) === 4, '17 WP menu entries get capped to four' );
pvdak_layout_expect( array_column( $links, 'id' ) === array( 1, 2, 3, 4 ),
    'Fallback preserves menu order for the first four published Pages' );
pvdak_layout_expect( $links[0]['title'] === 'Published Page 1' &&
    $links[0]['url'] === 'https://example.invalid/page-1',
    'Titles and URLs come from real Page data' );

$GLOBALS['quick_test_selection'] = array(
    'quick_page_1' => 9, 'quick_page_2' => new WP_Post( 7 ),
    'quick_page_3' => 3, 'quick_page_4' => 5,
);
$links = pvdak_home_quick_pages();
pvdak_layout_expect( array_column( $links, 'id' ) === array( 9, 7, 3, 5 ),
    'Explicit ACF page slots override the fallback menu and preserve slot order' );
$GLOBALS['quick_test_selection']['quick_page_4'] = 9;
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 3, 'Duplicates are rejected' );
$GLOBALS['quick_test_selection']['quick_page_4'] = 15;
$GLOBALS['quick_test_pages'][15] = 'draft';
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 3,
    'Unpublished Page is never linked; no fallback menu item gets mixed in' );
$GLOBALS['quick_test_selection'] = array( 'quick_page_1' => 9 );
pvdak_layout_expect( array_column( pvdak_home_quick_pages(), 'id' ) === array( 9 ),
    'Incomplete explicit selection never pulls the rest of the old 17-link menu' );
$GLOBALS['quick_test_selection'] = array();
array_unshift( $GLOBALS['quick_test_menu'], (object) array( 'object' => 'custom', 'object_id' => 0 ) );
pvdak_layout_expect( count( pvdak_home_quick_pages() ) === 4,
    'Custom menu links are ignored; four published Pages still used' );
$root = dirname( __DIR__, 2 );
$front = file_get_contents( $root . '/front-page.php' );
$js = file_get_contents( $root . '/assets/js/pvdak-home.js' );
$css = file_get_contents( $root . '/style.css' );
$acf = file_get_contents( $root . '/inc/pvdak-home-acf.php' );
$functions = file_get_contents( $root . '/functions.php' );
pvdak_layout_expect( false === strpos( $front, 'class="pvdak-home-hero-actions"' ),
    'Three hero service tiles were removed' );
pvdak_layout_expect( false !== strpos( $front, 'pvdak_home_quick_pages()' ) &&
    false === strpos( $front, 'foreach ( $quick_items' ),
    'Hero ribbon uses only the capped four-Page resolver' );
pvdak_layout_expect( false !== strpos( $front, 'data-pvdak-swiper-track' ) &&
    false !== strpos( $front, 'swiper-wrapper' ) &&
    false !== strpos( $front, 'swiper-slide' ) &&
    false !== strpos( $front, 'data-pvdak-topic' ),
    'Legal landing Page links use official swiper/swiper-wrapper/swiper-slide HTML' );
pvdak_layout_expect( false !== strpos( $front, 'data-pvdak-topic-prev' ) &&
    false !== strpos( $front, 'data-pvdak-topic-next' ),
    'Carousel has labelled manual navigation controls' );
pvdak_layout_expect( false !== strpos( $css, '--pvdak-topic-visible: 7' ) &&
    false !== strpos( $css, '--pvdak-topic-visible: 4' ) &&
    false !== strpos( $css, '--pvdak-topic-visible: 2' ),
    'Seven desktop cards and responsive four/two slide counts' );
pvdak_layout_expect( false !== strpos( $css, 'flex-flow: row nowrap' ) &&
    false !== strpos( $css, '.pvdak-topics-swiper.is-ready' ) &&
    false !== strpos( $css, '.pvdak-home-topic.swiper-slide' ),
    'Official Swiper slide widths override the previous fixed-scroll layout' );
pvdak_layout_expect( false !== strpos( $js, 'new window.Swiper(container' ) &&
    false !== strpos( $js, 'loop: true' ) &&
    false !== strpos( $js, 'autoplay:' ) &&
    false !== strpos( $js, 'breakpoints:' ) &&
    false === strpos( $js, 'track.appendChild(first)' ),
    'Native Swiper runtime owns autoplay, responsive layout and infinite loop' );
pvdak_layout_expect( false !== strpos( $js, 'prefers-reduced-motion' ) &&
    false !== strpos( $js, 'document.hidden' ),
    'Reduced motion and background pause are supported' );
pvdak_layout_expect( false !== strpos( $js, 'swiper.destroy(true, true)' ) &&
    false !== strpos( $js, 'filterTopics()' ) &&
    false !== strpos( $js, 'syncSwiper()' ),
    'Search safely destroys and restores true Swiper loop' );
pvdak_layout_expect( false !== strpos( $functions, 'cdn.jsdelivr.net/npm/swiper@' ) &&
    false !== strpos( $functions, "'pvdak-swiper'" ) &&
    false !== strpos( $functions, 'swiper-bundle.min.css' ) &&
    false !== strpos( $functions, 'swiper-bundle.min.js' ),
    'Official version-pinned Swiper CSS and JS are enqueued on the homepage' );
pvdak_layout_expect( false !== strpos( $functions, "array( 'pvdak-swiper' )" ) &&
    false !== strpos( $js, 'unpkg.com/swiper@14.3.0/swiper-bundle.min.js' ),
    'Homepage initializer waits for Swiper and retries pinned CDN on failure' );
// The repeated field is generated by a four-iteration PHP loop, not four
// duplicated static ACF definitions.
pvdak_layout_expect( false !== strpos( $acf, 'for ( $quick_slot = 1; $quick_slot <= 4; $quick_slot++ )' ) &&
    false !== strpos( $acf, "'type' => 'post_object'" ),
    'Four ACF Page selectors are generated in the homepage editor' );
echo "PVDAK_HOMEPAGE_LAYOUT_OK: {$checks} checks (synthetic + source; no WP data)\n";
