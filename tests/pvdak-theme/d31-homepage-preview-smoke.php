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
function get_post( $id ) {
    if ( 10 !== $id ) { return null; }
    return isset( $GLOBALS['d31_parent'] ) ? $GLOBALS['d31_parent'] : new WP_Post( 10 );
}
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
d31_check( 500 === count( pvdak_home_faq_pages( true ) ), 'compatibility audit returns all 500' ); $checks++;
d31_check( 81 === max( array_column( $GLOBALS['d31_queries'], 'posts_per_page' ) ), 'staff queries bounded to 81' ); $checks++;
$GLOBALS['d31_queries'] = array();
$summary = pvdak_home_faq_audit_counts();
d31_check( 500 === $summary['total'] && 0 === $summary['written'] && $summary['complete'],
    'staff counts full 500 without collecting Pages' ); $checks++;
d31_check( 7 === count( $GLOBALS['d31_queries'] ) && 0 === count( $summary['pages'] ),
    'seven bounded queries and no staff Page collection' ); $checks++;
$original_items = $GLOBALS['d31_items'];
$GLOBALS['d31_items'] = array_map( static function ( $i ) { return new WP_Post( $i, '' ); }, range( 1, 10080 ) );
$GLOBALS['d31_queries'] = array();
$large = pvdak_home_faq_audit_counts();
d31_check( 10000 === $large['total'] && ! $large['complete'] && 0 === count( $large['pages'] ),
    'audit budget reports incomplete instead of false PASS' ); $checks++;
d31_check( 125 === count( $GLOBALS['d31_queries'] ), 'audit query budget enforced' ); $checks++;
$GLOBALS['d31_items'] = $original_items;
$GLOBALS['d31_queries'] = array();
d31_check( 49 === count( pvdak_legal_topic_pages( 10, 49 ) ), 'bounded topic preview' ); $checks++;
d31_check( 49 === count( pvdak_legal_topic_pages( 10 ) ), 'default topic lookup remains bounded' ); $checks++;
// D31 privacy regression: a published password Page must not leak its
// raw post_content into the public FAQ accordion, even with a modified query.
$public_ok = new WP_Post( 601 );
$protected = new WP_Post( 602 ); $protected->post_password = 'secret';
$draft = new WP_Post( 603 ); $draft->post_status = 'draft';
$foreign = new WP_Post( 604 ); $foreign->post_parent = 11;
$wrong_type = new WP_Post( 605 ); $wrong_type->post_type = 'post';
$bad_id = new WP_Post( 606 ); $bad_id->ID = '0606';
$public_next = new WP_Post( 607 );
$GLOBALS['d31_items'] = array( $public_ok, $protected, $draft, $foreign,
    $wrong_type, $bad_id, $public_ok, $public_next );
$GLOBALS['d31_queries'] = array();
$guarded = pvdak_home_faq_pages();
d31_check( array( 601, 607 ) === array_map( static function ( $p ) { return $p->ID; }, $guarded ),
    'public FAQ rejects password, draft, foreign, non-Page, malformed ID and duplicates' ); $checks++;
d31_check( isset( $GLOBALS['d31_queries'][0]['has_password'] ) &&
    false === $GLOBALS['d31_queries'][0]['has_password'] &&
    false === $GLOBALS['d31_queries'][0]['suppress_filters'],
    'FAQ query enforces public password filter without bypassing WP filters' ); $checks++;
d31_check( ! pvdak_home_faq_answer_ready( $protected ) &&
    ! pvdak_home_faq_answer_ready( $draft ) &&
    ! pvdak_home_faq_answer_ready( $wrong_type ) &&
    ! pvdak_home_faq_answer_ready( $bad_id ),
    'shared FAQ readiness rejects private or invalid answers' ); $checks++;
$GLOBALS['d31_items'] = array( $public_ok, $protected );
$GLOBALS['d31_queries'] = array();
$private_audit = pvdak_home_faq_audit_counts();
d31_check( 2 === $private_audit['total'] && 1 === $private_audit['written'] &&
    $private_audit['complete'], 'protected FAQ is counted for staff but not marked public-ready' ); $checks++;
$GLOBALS['d31_parent'] = new WP_Post( 10 );
$GLOBALS['d31_parent']->post_password = 'secret';
d31_check( array() === pvdak_home_faq_pages(), 'protected FAQ parent cannot publish children' ); $checks++;
d31_check( array() === pvdak_legal_topic_pages( 10 ), 'protected topic parent cannot list children' ); $checks++;
unset( $GLOBALS['d31_parent'] );
echo 'PVDAK_D31_PREVIEW_OK: ' . $checks . '/' . $checks . " source-level PHP cases; WP/HTTP/host NOT_TESTED.\n";
