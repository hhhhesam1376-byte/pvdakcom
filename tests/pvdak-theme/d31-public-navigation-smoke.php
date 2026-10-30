<?php
/** D31 public Page navigation: original-source checks with isolated WordPress stubs. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );
class WP_Post {
    public $ID, $post_type = 'page', $post_status = 'publish', $post_password = '';
    public $post_name = '', $post_title = 'Public Page', $post_content = '[pvdak_request_form]';
    public $page_template = 'default';
    public function __construct( $id, $slug ) { $this->ID = $id; $this->post_name = $slug; }
}
class WP_Term { public $term_id = 1; }
$GLOBALS['d31_nav_pages'] = array();
$GLOBALS['d31_nav_slots'] = array();
$GLOBALS['d31_nav_menu'] = array();
$GLOBALS['d31_nav_checks'] = 0;
function add_action() {}
function add_filter() {}
$GLOBALS['d31_nav_shortcodes'] = array();
function add_shortcode( $tag, $callback ) {
    $GLOBALS['d31_nav_shortcodes'][ $tag ] = $callback;
}
function pvdak_home_configured_page_id( $v ) {
    return is_int( $v ) && $v > 0 ? $v : 0;
}
function get_post( $id ) { return $GLOBALS['d31_nav_pages'][ $id ] ?? null; }
function get_post_type( $id ) { $p = get_post( $id ); return $p ? $p->post_type : false; }
function get_post_status( $id ) { $p = get_post( $id ); return $p ? $p->post_status : false; }
function get_the_title( $id ) { $p = get_post( $id ); return $p ? $p->post_title : ''; }
function get_permalink( $id ) { return 'https://example.test/page/' . $id; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function pvdak_home_value( $name, $default = 0 ) {
    return $GLOBALS['d31_nav_slots'][ $name ] ?? $default;
}
function get_nav_menu_locations() { return array( 'pvdak_quick' => 30 ); }
function wp_get_nav_menu_items( $id ) { return $GLOBALS['d31_nav_menu']; }
function sanitize_title( $s ) { return $s; }
function get_page_by_path( $slug, $type = OBJECT, $post_type = 'page' ) {
    foreach ( $GLOBALS['d31_nav_pages'] as $page ) {
        if ( $page->post_name === $slug ) { return $page; }
    }
    return null;
}
function locate_template( $template, $load = false, $once = false ) { return $template; }
function get_page_template_slug( $id ) {
    $p = get_post( $id ); return $p ? $p->page_template : '';
}
function get_posts( $args ) {
    return array_slice( array_values( array_filter( $GLOBALS['d31_nav_pages'],
        static function ( $p ) use ( $args ) {
            return $p->post_type === $args['post_type'] &&
                in_array( $p->post_status, (array) $args['post_status'], true ) &&
                $p->page_template === $args['meta_value'];
        } ) ), 0, $args['posts_per_page'] );
}
function url_to_postid( $url ) {
    return preg_match( '~/page/([0-9]+)$~', $url, $m ) ? (int) $m[1] : 0;
}
function shortcode_exists( $tag ) { return 'pvdak_request_form' === $tag; }
function has_shortcode( $content, $tag ) { return strpos( $content, '[' . $tag ) !== false; }
function d31_nav_check( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'D31_NAV_FAIL: ' . $message . "\n" ); exit( 1 ); }
    ++$GLOBALS['d31_nav_checks'];
}
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-public-pages.php';
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php';
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-home-quick-links.php';
d31_nav_check(
    isset( $GLOBALS['d31_nav_shortcodes']['pvdak_forum'] ) &&
    is_callable( $GLOBALS['d31_nav_shortcodes']['pvdak_forum'] ),
    'Public Pages shortcode registration survives isolated WordPress bootstrap'
);
$public = new WP_Post( 11, 'public-quick' );
$protected = new WP_Post( 12, 'protected-quick' ); $protected->post_password = 'secret';
$draft = new WP_Post( 13, 'draft-quick' ); $draft->post_status = 'draft';
$wrong_type = new WP_Post( 14, 'wrong-type' ); $wrong_type->post_type = 'post';
$cta = new WP_Post( 15, 'public-cta' );
$login = new WP_Post( 16, 'account-login' ); $login->post_password = 'secret';
$login_public = new WP_Post( 17, 'other-login' );
$login->page_template = 'page-templates/pvdak-login.php';
$login_public->page_template = 'page-templates/pvdak-login.php';
foreach ( array( $public, $protected, $draft, $wrong_type, $cta, $login, $login_public ) as $p ) {
    $GLOBALS['d31_nav_pages'][ $p->ID ] = $p;
}
d31_nav_check( 11 === pvdak_home_quick_link( 11 )['id'], 'public shortcut allowed' );
d31_nav_check( null === pvdak_home_quick_link( 12 ), 'password shortcut rejected' );
d31_nav_check( null === pvdak_home_quick_link( 13 ), 'draft shortcut rejected' );
d31_nav_check( null === pvdak_home_quick_link( 14 ), 'non-Page shortcut rejected' );
d31_nav_check( null === pvdak_home_quick_link( '011' ), 'noncanonical shortcut rejected' );
$GLOBALS['d31_nav_slots'] = array(
    'quick_page_1' => 11, 'quick_page_2' => 12,
    'quick_page_3' => 13, 'quick_page_4' => 14,
);
d31_nav_check( array( 11 ) === array_column( pvdak_home_quick_pages(), 'id' ),
    'explicit shortcuts never include protected Pages' );
$GLOBALS['d31_nav_slots'] = array();
$GLOBALS['d31_nav_menu'] = array(
    (object) array( 'object' => 'page', 'object_id' => 12 ),
    (object) array( 'object' => 'page', 'object_id' => 11 ),
    (object) array( 'object' => 'page', 'object_id' => 15 ),
);
d31_nav_check( array( 11, 15 ) === array_column( pvdak_home_quick_pages(), 'id' ),
    'legacy menu fallback skips protected Page' );
d31_nav_check( '' === pvdak_public_page_url( 'protected-quick' ), 'public URL protected' );
d31_nav_check( '' === pvdak_public_page_url( 'draft-quick' ), 'public URL draft' );
d31_nav_check( '' === pvdak_public_page_url( 'wrong-type' ), 'public URL post type' );
d31_nav_check( get_permalink( 11 ) === pvdak_public_page_url( 'public-quick' ),
    'ordinary published public URL preserved' );
d31_nav_check( '' === pvdak_public_template_page_url( 'account-login', 'page-templates/pvdak-login.php' ),
    'password-gated canonical login never advertised' );
d31_nav_check( get_permalink( 17 ) ===
    pvdak_public_template_page_url( 'other-login', 'page-templates/pvdak-login.php' ),
    'published unprotected login remains available' );
d31_nav_check( '' === pvdak_home_shortcode_page_url( get_permalink( 12 ), 'pvdak_request_form' ),
    'password-gated service CTA rejected' );
d31_nav_check( '' === pvdak_home_shortcode_page_url( get_permalink( 13 ), 'pvdak_request_form' ),
    'draft service CTA rejected' );
d31_nav_check( get_permalink( 15 ) ===
    pvdak_home_shortcode_page_url( get_permalink( 15 ), 'pvdak_request_form' ),
    'published service CTA preserved' );
d31_nav_check( 2 === count( pvdak_public_account_template_pages(
    'page-templates/pvdak-login.php', true ) ),
    'admin duplicate-detection still sees protected account templates' );
echo 'PVDAK_D31_PUBLIC_NAV_OK: ' . $GLOBALS['d31_nav_checks'] . '/' .
    $GLOBALS['d31_nav_checks'] . " original-source stub cases; WP/DB/HTTP NOT_TESTED.\n";
