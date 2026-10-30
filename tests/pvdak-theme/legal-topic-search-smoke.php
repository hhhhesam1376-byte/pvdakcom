<?php
/**
 * No DB/no WordPress: test real Page-based Persian homepage search helpers.
 * PHP CLI: php tests/pvdak-theme/legal-topic-search-smoke.php
 */
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );
class WP_Post {
    public $ID;
    public $post_title;
    public $post_excerpt;
    public $post_status;
    public $post_type = 'page';
    public $post_password = '';
    public $post_parent = 25;
    public $menu_order = 0;
    public function __construct( $title, $excerpt = '', $status = 'publish', $id = 0 ) {
        $this->ID = $id;
        $this->post_title = $title;
        $this->post_excerpt = $excerpt;
        $this->post_status = $status;
    }
}
function add_action() {}
function add_filter() {}
$GLOBALS['pvdak_topic_mock_options'] = array( 'show_on_front' => 'page', 'page_on_front' => '12' );
$GLOBALS['pvdak_topic_mock_selected'] = false;
$GLOBALS['pvdak_topic_mock_front'] = true;
$GLOBALS['pvdak_topic_mock_field_reads'] = 0;
$GLOBALS['pvdak_topic_mock_query'] = array();
function get_option( $key ) { return $GLOBALS['pvdak_topic_mock_options'][ $key ] ?? false; }
function get_post_type( $id ) { return in_array( $id, array( 12, 25, 26, 27 ), true ) ? 'page' : 'post'; }
function get_post_status( $id ) { return 27 === $id ? 'draft' : 'publish'; }
function get_field( $name, $id ) {
    $GLOBALS['pvdak_topic_mock_field_reads']++;
    return $GLOBALS['pvdak_topic_mock_selected'];
}
function get_post( $id ) {
    if ( ! in_array( $id, array( 12, 25, 26, 27, 28 ), true ) ) { return null; }
    $post = new WP_Post( 'برگه آزمایشی', '', 27 === $id ? 'draft' : 'publish', $id );
    if ( 28 === $id ) { $post->post_type = 'post'; }
    return $post;
}
function get_page_by_path( $slug, $output, $type ) {
    return 'legal-topics' === $slug && 'page' === $type ? get_post( 25 ) : null;
}
function get_posts( $args ) { $GLOBALS['pvdak_topic_mock_query'] = $args; return array(); }
function is_front_page() { return $GLOBALS['pvdak_topic_mock_front']; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function sanitize_text_field( $text ) {
    return is_string( $text ) && 1 === preg_match( '//u', $text ) ? trim( strip_tags( $text ) ) : '';
}
function wp_unslash( $text ) { return stripslashes( $text ); }

require dirname( __DIR__, 2 ) . '/inc/pvdak-home-acf.php';
require dirname( __DIR__, 2 ) . '/inc/pvdak-legal-landings.php';

$checks = 0;
function pvdak_topic_expect( $condition, $description ) {
    global $checks;
    $checks++;
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$description}\n" );
        exit( 1 );
    }
    echo "PASS: {$description}\n";
}
$pages = array(
    new WP_Post( 'حقوق خانواده', 'طلاق و مسائل خانوادگی', 'publish', 101 ),
    new WP_Post( 'دعاوی ملکی', 'مالکیت و املاک', 'publish', 102 ),
    new WP_Post( 'قراردادها', 'تعهدات', 'publish', 103 ),
    new WP_Post( 'حقوق کار', '', 'draft', 104 ),
);
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'خانواده' ) ) === 1, 'Persian title matches one Page' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'خانواده‌' ) ) === 1, 'Trailing half-space normalized' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'ملكي' ) ) === 1, 'Arabic kaf and yeh variants match Persian Page' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'طلاق' ) ) === 1, 'Page excerpt can match user term' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'حقوق' ) ) === 1, 'Draft Page excluded from non-empty search' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'غیرموجود' ) ) === 0, 'Unmatched query produces no results' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, '' ) ) === 3, 'Empty query also excludes draft Page' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( null, 'test' ) ) === 0, 'Unexpected source is safely ignored' );
// Preserve current D31 fail-closed public Page guards in the older search smoke.
$password = new WP_Post( 'حقوق خانواده', '', 'publish', 105 );
$password->post_password = 'private';
$wrong_type = new WP_Post( 'حقوق خانواده', '', 'publish', 106 );
$wrong_type->post_type = 'post';
$malformed = new WP_Post( 'حقوق خانواده', '', 'publish', '0107' );
$duplicates = array_merge( $pages, array( $pages[0], $password, $wrong_type, $malformed ) );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $duplicates, '' ) ) === 3,
    'Empty search excludes duplicate, password, non-Page and malformed ID' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $duplicates, 'خانواده' ) ) === 1,
    'Filtered search does not leak protected or duplicate Page' );
pvdak_topic_expect( array() === pvdak_legal_filter_topic_pages( array( $password ), '' ),
    'Password-protected Page never appears in public cards' );
pvdak_topic_expect( array() === pvdak_legal_filter_topic_pages( array( $malformed ), '' ),
    'Noncanonical Page ID is rejected before rendering' );

$_GET['pvdak_topic'] = ' ملکی ';
pvdak_topic_expect( pvdak_legal_topic_search_term() === 'ملکی', 'GET search term sanitized and trimmed' );
$_GET['pvdak_topic'] = array( 'bad' );
pvdak_topic_expect( pvdak_legal_topic_search_term() === '', 'Array query parameter rejected' );
pvdak_topic_expect( pvdak_legal_topic_search_requested(), 'Malformed array GET remains an explicit search' );
$_GET['pvdak_topic'] = "\xC3\x28";
pvdak_topic_expect( '' === pvdak_legal_topic_search_term() &&
    pvdak_legal_topic_search_requested(), 'Malformed UTF-8 GET does not become homepage preview' );
$_GET['pvdak_topic'] = '<b></b>';
pvdak_topic_expect( '' === pvdak_legal_topic_search_term() &&
    pvdak_legal_topic_search_requested(), 'Sanitized-empty nonblank GET stays a filtered request' );
$_GET['pvdak_topic'] = '   ';
pvdak_topic_expect( ! pvdak_legal_topic_search_requested(),
    'Whitespace-only GET still shows homepage preview' );
unset( $_GET['pvdak_topic'] );
pvdak_topic_expect( pvdak_legal_topic_search_term() === '' &&
    ! pvdak_legal_topic_search_requested(), 'Unspecified query is empty and not requested' );

// D31: exercise original-source root resolver, parent query and robots rules.
// These are WordPress stubs, NOT a live database or HTTP response test.
pvdak_topic_expect( 25 === pvdak_legal_topics_root_id(), 'Blank ACF uses published legacy root' );
$GLOBALS['pvdak_topic_mock_selected'] = '26';
pvdak_topic_expect( 26 === pvdak_legal_topics_root_id(), 'Canonical published ACF Page wins' );
$GLOBALS['pvdak_topic_mock_selected'] = new WP_Post( 'منتخب', '', 'publish', 26 );
pvdak_topic_expect( 26 === pvdak_legal_topics_root_id(), 'WP_Post selection is accepted' );
foreach ( array( '26junk', '026', '-26', array( 'ID' => 26 ), '27', '28' ) as $bad ) {
    $GLOBALS['pvdak_topic_mock_selected'] = $bad;
    pvdak_topic_expect( 0 === pvdak_legal_topics_root_id(), 'Explicit invalid/draft/wrong-type root fails closed' );
}
$GLOBALS['pvdak_topic_mock_selected'] = '26';
$GLOBALS['pvdak_topic_mock_options']['show_on_front'] = 'posts';
$before_reads = $GLOBALS['pvdak_topic_mock_field_reads'];
pvdak_topic_expect( 25 === pvdak_legal_topics_root_id() &&
    $before_reads === $GLOBALS['pvdak_topic_mock_field_reads'],
    'Latest-posts mode ignores stale front-page ACF' );
$GLOBALS['pvdak_topic_mock_options']['show_on_front'] = 'page';
$GLOBALS['pvdak_topic_mock_options']['page_on_front'] = '12junk';
pvdak_topic_expect( 25 === pvdak_legal_topics_root_id(), 'Malformed front-page option cannot select ACF' );
$GLOBALS['pvdak_topic_mock_options']['page_on_front'] = '12';
pvdak_topic_expect( array() === pvdak_legal_topic_pages( '26junk' ), 'Malformed explicit parent rejected' );
pvdak_topic_expect( array() === pvdak_legal_topic_pages( '27' ), 'Draft explicit parent rejected' );
pvdak_legal_topic_pages( '26' );
pvdak_topic_expect( 26 === $GLOBALS['pvdak_topic_mock_query']['post_parent'] &&
    'publish' === $GLOBALS['pvdak_topic_mock_query']['post_status'],
    'Canonical parent fetches only published children' );
$_GET['pvdak_topic'] = 'ملکی';
pvdak_topic_expect( true === pvdak_legal_search_robots( array( 'follow' => true ) )['noindex'],
    'Filtered homepage noindexed' );
$GLOBALS['pvdak_topic_mock_front'] = false;
pvdak_topic_expect( array( 'follow' => true ) === pvdak_legal_search_robots( array( 'follow' => true ) ),
    'Other Pages retain robots rules' );
$GLOBALS['pvdak_topic_mock_front'] = true;
unset( $_GET['pvdak_topic'] );
pvdak_topic_expect( array( 'follow' => true ) === pvdak_legal_search_robots( array( 'follow' => true ) ),
    'Clean homepage remains indexable' );

echo "PVDAK_LEGAL_SEARCH_SMOKE_OK: {$checks} checks.\n";
