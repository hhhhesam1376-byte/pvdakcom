<?php
/** D31: standalone source-level regression; no WP boot or live data. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $ID; public $post_title; public $post_excerpt; public $post_content; public $post_status;
    public $post_type = 'page', $post_parent = 10, $post_password = '', $menu_order = 0;
    public function __construct( $id, $content = 'A complete published answer for visitors.' ) {
        $this->ID = $id; $this->menu_order = $id; $this->post_title = 'Question ' . $id;
        $this->post_excerpt = ''; $this->post_content = $content; $this->post_status = 'publish';
    }
}
class WP_Term {}
$GLOBALS['d31_items'] = array();
$GLOBALS['d31_queries'] = array();
$GLOBALS['d31_staff_cap'] = true;
function current_user_can( $cap ) {
    return 'edit_pages' === $cap && $GLOBALS['d31_staff_cap'];
}
class D31_Query {
    private $args;
    public function __construct( $args ) { $this->args = $args; }
    public function get( $key ) { return $this->args[ $key ] ?? null; }
}
class D31_DB {
    public $posts = 'wp_posts';
    public function prepare( $sql, ...$values ) {
        $GLOBALS['d31_seek'] = array(
            'op' => false !== strpos( $sql, 'menu_order < %d' ) ? '<' : '>',
            'tuple' => array( $values[0], $values[2], $values[4] ),
        );
        return $sql;
    }
}
$GLOBALS['wpdb'] = new D31_DB();
$GLOBALS['d31_filters'] = array();
function add_filter( $name, $callback, $priority = 10, $accepted = 1 ) {
    $GLOBALS['d31_filters'][ $name ][ $priority ] = $callback;
}
function remove_filter( $name, $callback, $priority = 10 ) {
    if ( ( $GLOBALS['d31_filters'][ $name ][ $priority ] ?? null ) === $callback ) {
        unset( $GLOBALS['d31_filters'][ $name ][ $priority ] );
    }
}
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
    $GLOBALS['d31_seek'] = null;
    foreach ( $GLOBALS['d31_filters']['posts_where'] ?? array() as $filter ) {
        $filter( '', new D31_Query( $args ) );
    }
    $rows = $GLOBALS['d31_items'];
    if ( ! empty( $GLOBALS['d31_replay'] ) ) {
        return array_slice( $rows, 0, $args['posts_per_page'] );
    }
    if ( null !== $GLOBALS['d31_seek'] ) {
        $tuple = $GLOBALS['d31_seek']['tuple'];
        $op = $GLOBALS['d31_seek']['op'];
        $rows = array_values( array_filter( $rows, static function ( $page ) use ( $tuple, $op ) {
            if ( ! $page instanceof WP_Post ) { return false; }
            $cmp = $page->menu_order <=> $tuple[0];
            if ( 0 === $cmp ) { $cmp = strcmp( $page->post_title, $tuple[1] ); }
            if ( 0 === $cmp ) { $cmp = $page->ID <=> $tuple[2]; }
            return '<' === $op ? $cmp < 0 : $cmp > 0;
        } ) );
    }
    return array_slice( $rows, 0, $args['posts_per_page'] );
}
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function strip_shortcodes( $value ) { return $value; }
function get_the_title( $id ) { return 'Question ' . $id; }
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-seek.php';
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
// Replayed public FAQ query must stop at the first non-advancing cursor.
$GLOBALS['d31_items'] = array_map( static function ( $i ) {
    return new WP_Post( $i, 1 === $i ? 'A complete published answer for visitors.' : '' );
}, range( 1, 41 ) );
$GLOBALS['d31_replay'] = true;
$GLOBALS['d31_queries'] = array();
$replayed_faq = pvdak_home_faq_pages();
d31_check( 1 === count( $replayed_faq ) && 2 === count( $GLOBALS['d31_queries'] ),
    'replayed public FAQ batch stops after two bounded queries' ); $checks++;
unset( $GLOBALS['d31_replay'] );
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
d31_check( 41 === max( array_column( $GLOBALS['d31_queries'], 'posts_per_page' ) ), 'bounded seek batches' ); $checks++;
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
// A plugin-modified result can repeat an ID on the next keyset batch.
// Staff counts must not double-count that Page or claim a complete audit.
$saved_audit_items = $GLOBALS['d31_items'];
$duplicate_rows = array_map( static function ( $i ) { return new WP_Post( $i ); }, range( 1, 81 ) );
$duplicate_rows[80]->ID = 1; // Valid ordering tuple, duplicate identity across batches.
$GLOBALS['d31_items'] = $duplicate_rows;
$GLOBALS['d31_queries'] = array();
$duplicate_audit = pvdak_home_faq_audit_counts();
d31_check( ! $duplicate_audit['complete'] && 80 === $duplicate_audit['total'] &&
    80 === $duplicate_audit['written'] && 2 === count( $GLOBALS['d31_queries'] ),
    'staff audit rejects duplicate Page IDs across seek batches' ); $checks++;
$duplicate_rows = array( new WP_Post( 701 ), new WP_Post( 701 ) );
$GLOBALS['d31_items'] = $duplicate_rows;
$GLOBALS['d31_queries'] = array();
$duplicate_audit = pvdak_home_faq_audit_counts();
d31_check( ! $duplicate_audit['complete'] && 1 === $duplicate_audit['total'] &&
    1 === count( $GLOBALS['d31_queries'] ),
    'staff audit rejects duplicate Page IDs inside one batch' ); $checks++;
$GLOBALS['d31_items'] = $saved_audit_items;
$GLOBALS['d31_parent'] = new WP_Post( 10 );
$GLOBALS['d31_parent']->post_password = 'secret';
d31_check( array() === pvdak_home_faq_pages(), 'protected FAQ parent cannot publish children' ); $checks++;
d31_check( array() === pvdak_legal_topic_pages( 10 ), 'protected topic parent cannot list children' ); $checks++;
unset( $GLOBALS['d31_parent'] );
d31_check( ! array_filter( $GLOBALS['d31_queries'], static function ( $q ) {
    return isset( $q['offset'] );
} ), 'no deep SQL OFFSET in FAQ scans' ); $checks++;
d31_check( empty( $GLOBALS['d31_filters']['posts_where'] ), 'all seek filters detached' ); $checks++;
d31_check( ! array_filter( $GLOBALS['d31_queries'], static function ( $q ) {
    return isset( $q['pvdak_legal_seek_scope'] ) &&
        ( empty( $q['no_found_rows'] ) || ! empty( $q['suppress_filters'] ) );
} ), 'seek query is bounded and scoped' ); $checks++;
// Staff-only audit must never enumerate protected Pages for an anonymous visitor.
$GLOBALS['d31_staff_cap'] = false;
$GLOBALS['d31_queries'] = array();
$denied = pvdak_home_faq_audit_counts();
d31_check( ! $denied['complete'] && 0 === $denied['total'] &&
    0 === $denied['written'] && array() === $denied['pages'],
    'unauthorized FAQ audit stays incomplete without private content' ); $checks++;
d31_check( array() === pvdak_home_faq_pages( true ),
    'unauthorized compatibility collector returns no private Pages' ); $checks++;
d31_check( array() === pvdak_legal_seek_rows( 10, 81, null, 'after', true ),
    'direct staff-only seek rejects missing edit_pages capability' ); $checks++;
d31_check( array() === $GLOBALS['d31_queries'],
    'denied staff audit/seek performs no SQL query' ); $checks++;
d31_check( 1 === count( pvdak_home_faq_pages() ),
    'public FAQ preview still works for visitors without staff capability' ); $checks++;
$GLOBALS['d31_staff_cap'] = true;
echo 'PVDAK_D31_PREVIEW_OK: ' . $checks . '/' . $checks . " source-level PHP cases; WP/HTTP/host NOT_TESTED.\n";
