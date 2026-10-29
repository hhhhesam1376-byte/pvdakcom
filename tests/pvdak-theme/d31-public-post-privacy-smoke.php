<?php
/** D31: original homepage post selectors, isolated WP stubs, no DB/network. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $ID, $post_type = 'post', $post_status = 'publish', $post_password = '';
    public $post_content = 'Visible public summary', $post_excerpt = '';
    public function __construct( $id ) { $this->ID = $id; }
}
class WP_Term { public $term_id = 20; }
$GLOBALS['d31_posts'] = array(); $GLOBALS['d31_feature'] = null; $GLOBALS['d31_calls'] = array();
function pvdak_home_page_id() { return 1; }
function pvdak_home_configured_page_id( $v ) { return is_int( $v ) && $v > 0 ? $v : 0; }
function get_post_status( $id ) { return 'publish'; }
function get_field( $name, $id = 0 ) {
    return 'magazine_category' === $name ? 20 :
        ( 'magazine_featured_post' === $name ? $GLOBALS['d31_feature'] : null );
}
function get_term( $id, $tax ) { return 20 === $id ? new WP_Term() : null; }
function is_wp_error( $term ) { return false; }
function get_post( $id ) { return $GLOBALS['d31_feature']; }
function has_category( $term, $post ) { return 20 === $term && 40 !== $post->ID; }
function get_posts( $args ) { $GLOBALS['d31_calls'][] = $args; return $GLOBALS['d31_posts']; }
function has_excerpt( $post ) { return '' !== $post->post_excerpt; }
function get_the_excerpt( $post ) { return $post->post_excerpt; }
function strip_shortcodes( $s ) { return $s; }
function wp_strip_all_tags( $s ) { return strip_tags( $s ); }
function wp_trim_words( $s, $limit, $more ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php';
function d31_check( $ok, $message ) {
    if ( ! $ok ) { fwrite( STDERR, 'D31_POST_PRIVACY_FAIL: ' . $message . "\n" ); exit( 1 ); }
    $GLOBALS['d31_checks']++;
}
$GLOBALS['d31_checks'] = 0;
$valid = new WP_Post( 30 );
$private = new WP_Post( 31 ); $private->post_password = 'protected'; $private->post_content = 'Secret body';
$draft = new WP_Post( 32 ); $draft->post_status = 'draft';
$wrong = new WP_Post( 33 ); $wrong->post_type = 'page';
$invalid = new WP_Post( '034' );
$foreign = new WP_Post( 40 );
$GLOBALS['d31_feature'] = $private;
d31_check( null === pvdak_home_selected_post( 'magazine_featured_post' ), 'protected featured post rejected' );
$GLOBALS['d31_feature'] = $valid;
d31_check( $valid === pvdak_home_selected_post( 'magazine_featured_post' ), 'public featured post retained' );
$GLOBALS['d31_posts'] = array( $private, $draft, $wrong, $invalid, $foreign, $valid, $valid );
$list = pvdak_home_category_posts( 'magazine_category', 5 );
d31_check( array( $valid ) === $list, 'injected private or foreign posts rejected' );
d31_check( false === $GLOBALS['d31_calls'][0]['has_password'] &&
    false === $GLOBALS['d31_calls'][0]['suppress_filters'] &&
    5 === $GLOBALS['d31_calls'][0]['posts_per_page'], 'bounded SQL password filter' );
d31_check( array() === pvdak_home_category_posts( 'magazine_category', 5, array( 30 ) ),
    'excluded post stays excluded even after altered query' );
d31_check( '' === pvdak_home_post_summary( $private ), 'protected body not summarized' );
d31_check( '' === pvdak_home_post_summary( $draft ), 'draft body not summarized' );
d31_check( 'Visible public summary' === pvdak_home_post_summary( $valid ), 'public summary retained' );
echo 'PVDAK_D31_POST_PRIVACY_OK: ' . $GLOBALS['d31_checks'] . '/' .
    $GLOBALS['d31_checks'] . " original-source cases; WordPress/HTTP NOT_TESTED.\n";
