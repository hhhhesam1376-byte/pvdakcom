<?php
/** D31 bounded public topic search: source-level WordPress-stub regression. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $ID, $post_type = 'page', $post_status = 'publish';
    public $post_parent = 7, $post_password = '', $post_title = '', $post_excerpt = '';
}
function add_filter() {}
function add_action() {}
function is_front_page() { return true; }
function pvdak_home_configured_page_id( $id ) { return is_int( $id ) && $id > 0 ? $id : 0; }
function get_post_type( $id ) { return 7 === $id ? 'page' : false; }
function get_post_status( $id ) { return 7 === $id ? 'publish' : false; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function get_posts( $args ) {
    $GLOBALS['d31_queries'][] = $args;
    if ( $args['posts_per_page'] > 41 || $args['offset'] < 0 ) { throw new RuntimeException( 'Unbounded query' ); }
    if ( array_key_exists( 'd31_injected', $GLOBALS ) ) { return $GLOBALS['d31_injected']; }
    $rows = array_values( array_filter( $GLOBALS['d31_rows'], static function ( $page ) use ( $args ) {
        return $page->post_type === $args['post_type'] &&
            $page->post_status === $args['post_status'] &&
            $page->post_parent === $args['post_parent'] &&
            ( ! $args['has_password'] || '' === $page->post_password );
    } ) );
    return array_slice( $rows, $args['offset'], $args['posts_per_page'] );
}
function d31_page( $id, $title, $status = 'publish', $password = '' ) {
    $page = new WP_Post(); $page->ID = $id; $page->post_title = $title;
    $page->post_status = $status; $page->post_password = $password;
    return $page;
}
function d31_check( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'D31_SEARCH_FAIL: ' . $message . "\n" ); exit( 1 ); }
    $GLOBALS['d31_checks']++;
}
$GLOBALS['d31_checks'] = 0;
$GLOBALS['d31_queries'] = array();
$GLOBALS['d31_rows'] = array();
// This loads the actual source file, not a copied implementation.
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-landings.php';
for ( $i = 0; $i < 600; ++$i ) {
    $GLOBALS['d31_rows'][] = d31_page( $i + 1, $i < 120 ? 'حقوق شرکت' : 'كسب و كار' );
}
$first = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت' );
d31_check( 48 === count( $first['pages'] ) && 48 === $first['next_cursor'], 'first 48 and cursor' );
d31_check( count( $GLOBALS['d31_queries'] ) <= 6, 'at most six queries' );
$second = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $first['next_cursor'] );
d31_check( 48 === count( $second['pages'] ) && 96 === $second['next_cursor'], 'no duplicated page' );
$third = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $second['next_cursor'] );
d31_check( 24 === count( $third['pages'] ) && null !== $third['next_cursor'], 'partial third window' );
$fourth = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $third['next_cursor'] );
d31_check( array() === $fourth['pages'] && null !== $fourth['next_cursor'], 'empty window still continues' );
$last = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $fourth['next_cursor'] );
d31_check( array() === $last['pages'] && null === $last['next_cursor'], 'search exhaustion' );
$persian = pvdak_legal_topic_search_batch( 7, 'کسب و کار', 120 );
d31_check( 48 === count( $persian['pages'] ), 'Arabic/Persian variants' );
$GLOBALS['d31_rows'] = array();
for ( $i = 0; $i < 351; ++$i ) {
    $GLOBALS['d31_rows'][] = d31_page( $i + 1, $i >= 290 ? 'پاسخ حقوقی' : 'نمونه' );
}
$empty = pvdak_legal_topic_search_batch( 7, 'پاسخ حقوقی' );
d31_check( array() === $empty['pages'] && 240 === $empty['next_cursor'], 'sparse first window' );
$continued = pvdak_legal_topic_search_batch( 7, 'پاسخ حقوقی', $empty['next_cursor'] );
d31_check( 48 === count( $continued['pages'] ), 'late matches found' );
$_GET['pvdak_topic_offset'] = '1junk';
d31_check( 0 === pvdak_legal_topic_search_cursor(), 'invalid cursor rejected' );
$_GET['pvdak_topic_offset'] = '240';
d31_check( 240 === pvdak_legal_topic_search_cursor(), 'canonical cursor' );
d31_check( 0 === pvdak_legal_topic_search_batch( 8, 'test' )['scanned'], 'invalid parent fails closed' );
$GLOBALS['d31_rows'] = array( d31_page( 1, 'خصوصی', 'publish', 'secret' ), d31_page( 2, 'خصوصی', 'draft' ), d31_page( 3, 'خصوصی' ) );
$public = pvdak_legal_topic_search_batch( 7, 'خصوصی' );
d31_check( 1 === count( $public['pages'] ) && 3 === $public['pages'][0]->ID, 'password/draft excluded' );
// Deliberately simulate a WordPress query filter returning unsafe records.
$foreign = d31_page( 91, 'بیگانه' ); $foreign->post_parent = 99;
$wrong_type = d31_page( 92, 'نوشته' ); $wrong_type->post_type = 'post';
$invalid_id = d31_page( 93, 'شناسه' ); $invalid_id->ID = -3;
$GLOBALS['d31_injected'] = array(
    d31_page( 1, 'منتشر', 'publish' ),
    d31_page( 2, 'پیش‌نویس', 'draft' ),
    d31_page( 3, 'رمزدار', 'publish', 'secret' ),
    $foreign, $wrong_type, $invalid_id,
);
$preview = pvdak_legal_topic_pages( 7 );
d31_check( 1 === count( $preview ) && 1 === $preview[0]->ID, 'filtered homepage preview excludes unsafe injected Pages' );
d31_check( 2 === count( pvdak_legal_filter_topic_pages( $GLOBALS['d31_injected'], '' ) ), 'generic filter rejects draft/password/non-Page/invalid IDs but has no parent context' );
$GLOBALS['d31_injected'] = false;
d31_check( array() === pvdak_legal_topic_pages( 7 ), 'malformed query result fails closed' );
unset( $GLOBALS['d31_injected'] );
unset( $_GET['pvdak_topic'] );
$_GET['pvdak_topic_offset'] = '240';
d31_check( isset( pvdak_legal_search_robots( array() )['noindex'] ), 'cursor-only homepage URL noindex' );
unset( $_GET['pvdak_topic_offset'] );
d31_check( array() === pvdak_legal_search_robots( array() ), 'clean homepage remains indexable' );
echo 'PVDAK_D31_SEARCH_OK: ' . $GLOBALS['d31_checks'] . '/' . $GLOBALS['d31_checks'] . " source-level PHP cases; WP/HTTP/host NOT_TESTED.\n";
