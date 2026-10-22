<?php
/**
 * No DB/no WordPress: test real Page-based Persian homepage search helpers.
 * PHP CLI: php tests/pvdak-theme/legal-topic-search-smoke.php
 */
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $post_title;
    public $post_excerpt;
    public $post_status;
    public function __construct( $title, $excerpt = '', $status = 'publish' ) {
        $this->post_title = $title;
        $this->post_excerpt = $excerpt;
        $this->post_status = $status;
    }
}
function add_action() {}
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function sanitize_text_field( $text ) { return trim( strip_tags( $text ) ); }
function wp_unslash( $text ) { return stripslashes( $text ); }

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
    new WP_Post( 'حقوق خانواده', 'طلاق و مسائل خانوادگی' ),
    new WP_Post( 'دعاوی ملکی', 'مالکیت و املاک' ),
    new WP_Post( 'قراردادها', 'تعهدات' ),
    new WP_Post( 'حقوق کار', '', 'draft' ),
);
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'خانواده' ) ) === 1, 'Persian title matches one Page' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'خانواده‌' ) ) === 1, 'Trailing half-space normalized' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'ملكي' ) ) === 1, 'Arabic kaf and yeh variants match Persian Page' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'طلاق' ) ) === 1, 'Page excerpt can match user term' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'حقوق' ) ) === 1, 'Draft Page excluded from non-empty search' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, 'غیرموجود' ) ) === 0, 'Unmatched query produces no results' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( $pages, '' ) ) === 4, 'Empty query preserves legacy Page collection' );
pvdak_topic_expect( count( pvdak_legal_filter_topic_pages( null, 'test' ) ) === 0, 'Unexpected source is safely ignored' );

$_GET['topic'] = ' ملکی ';
pvdak_topic_expect( pvdak_legal_topic_search_term() === 'ملکی', 'GET search term sanitized and trimmed' );
$_GET['topic'] = array( 'bad' );
pvdak_topic_expect( pvdak_legal_topic_search_term() === '', 'Array query parameter rejected' );
unset( $_GET['topic'] );
pvdak_topic_expect( pvdak_legal_topic_search_term() === '', 'Unspecified query is empty' );
echo "PVDAK_LEGAL_SEARCH_SMOKE_OK: {$checks} checks.\n";
