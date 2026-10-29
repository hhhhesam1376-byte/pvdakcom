<?php
/** D31: standalone source-level regression; no WP boot or live data. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $ID; public $post_title; public $post_excerpt; public $post_content; public $post_status;
    public $post_type = 'page', $post_parent = 10, $post_password = '';
    public function __construct( $id, $content = 'A complete published answer for visitors.' ) {
        $this->ID = $id; $this->post_title = 'Question ' . $id;
        $this->post_excerpt = ''; $this->post_content = $content; $this->post_status = 'publish';
    }
}
class WP_Term {}
$GLOBALS['d31_items'] = array();
$GLOBALS['d31_queries'] = array();
function add_filter() {}
function pvdak_home_page_id() { return 1; }
function pvdak_home_configured_page_id( $id ) { return is_int( $id ) ? $id : 0; }
function get_post_status() { return 'publish'; }
function get_post_type() { return 'page'; }
function get_field( $field ) { return 'faq_parent_page' === $field ? 10 : null; }
function get_posts( $args ) {
    $GLOBALS['d31_queries'][] = $args;
    $offset = isset( $args['offset'] ) ? $args['offset'] : 0;
    $limit = $args['posts_per_page'];
    return array_slice( $GLOBALS['d31_items'], $offset, -1 === $limit ? null : $limit );
}
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function strip_shortcodes( $value ) { return $value; }
function get_the_title( $id ) { return 'Question ' . $id; }
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-home-collections.php';
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-landings.php';
function d31_check( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'D31_PREVIEW_FAIL: ' . $message . "\n" ); exit( 1 ); }
}
$checks = 0;
$GLOBALS['d31_items'] = array();
$GLOBALS['d31_queries'] = array();
d31_check( array() === pvdak_home_faq_pages(), 'empty FAQ' ); $checks++;
d31_check( 1 === count( $GLOBALS['d31_queries'] ), 'empty one batch' ); $checks++;
$GLOBALS['d31_items'] = array_map( static function ( $i ) { return new WP_Post( $i ); }, range( 1, 4 ) );
$GLOBALS['d31_queries'] = array();
d31_check( 4 === count( pvdak_home_faq_pages() ), 'four valid FAQs' ); $checks++;
d31_check( 1 === count( $GLOBALS['d31_queries'] ), 'small set one query' ); $checks++;
$GLOBALS['d31_items'] = array_map( static function ( $i ) {
    return new WP_Post( $i, $i <= 57 ? '' : 'A complete published answer for visitors.' );
}, range( 1, 90 ) );
$GLOBALS['d31_queries'] = array();
$faq = pvdak_home_faq_pages();
d31_check( 12 === count( $faq ), '12 ready answers' ); $checks++;
d31_check( 58 === $faq[0]->ID, 'scan beyond first 40 placeholders' ); $checks++;
d31_check( 2 === count( $GLOBALS['d31_queries'] ), 'stop after second batch' ); $checks++;
$GLOBALS['d31_items'] = array_map( static function ( $i ) { return new WP_Post( $i, '' ); }, range( 1, 500 ) );
$GLOBALS['d31_queries'] = array();
d31_check( array() === pvdak_home_faq_pages(), 'do not display placeholders' ); $checks++;
d31_check( 6 === count( $GLOBALS['d31_queries'] ), 'bounded to 240 scans' ); $checks++;
d31_check( 40 === max( array_column( $GLOBALS['d31_queries'], 'posts_per_page' ) ), 'bounded batches' ); $checks++;
$GLOBALS['d31_queries'] = array();
d31_check( 500 === count( pvdak_home_faq_pages( true ) ), 'staff readiness retains all' ); $checks++;
$GLOBALS['d31_queries'] = array();
d31_check( 49 === count( pvdak_legal_topic_pages( 10, 49 ) ), 'bounded topic preview' ); $checks++;
d31_check( 49 === count( pvdak_legal_topic_pages( 10 ) ), 'default topic lookup remains bounded' ); $checks++;
echo 'PVDAK_D31_PREVIEW_OK: ' . $checks . '/' . $checks . " source-level PHP cases; WP/HTTP/host NOT_TESTED.\n";
