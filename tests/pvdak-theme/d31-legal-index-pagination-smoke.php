<?php
/** Standalone original-source D31 index regression; no WordPress/DB/network. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
class WP_Post {
    public $ID, $post_type = 'page', $post_status = 'publish', $post_parent = 7;
    public $post_password = '', $post_title = '', $menu_order = 0;
}
$GLOBALS['rows'] = array();
$GLOBALS['queries'] = array();
$GLOBALS['index_template'] = true;
function add_filter() {}
function pvdak_home_configured_page_id( $id ) { return is_int( $id ) && $id > 0 ? $id : 0; }
function get_post_type( $id ) { return 7 === $id ? 'page' : false; }
function get_post_status( $id ) { return 7 === $id ? 'publish' : false; }
function is_page_template( $slug ) { return $GLOBALS['index_template'] && 'page-templates/legal-topics-index.php' === $slug; }
function get_posts( $args ) {
    $GLOBALS['queries'][] = $args;
    if ( array_key_exists( 'injected', $GLOBALS ) ) { return $GLOBALS['injected']; }
    if ( $args['posts_per_page'] > 25 || $args['offset'] < 0 ) { throw new RuntimeException( 'Unbounded query' ); }
    $rows = array_values( array_filter( $GLOBALS['rows'], static function ( $p ) use ( $args ) {
        return $p->post_type === $args['post_type'] && $p->post_status === $args['post_status'] &&
            $p->post_parent === $args['post_parent'] && ( false !== $args['has_password'] || '' === $p->post_password );
    } ) );
    return array_slice( $rows, $args['offset'], $args['posts_per_page'] );
}
function check_d31( $condition, $description ) {
    if ( ! $condition ) { fwrite( STDERR, "D31_INDEX_FAIL: {$description}\n" ); exit( 1 ); }
    $GLOBALS['checks']++;
}
$GLOBALS['checks'] = 0;
require dirname( __DIR__, 2 ) . '/inc/pvdak-legal-index-pagination.php';
for ( $i = 1; $i <= 71; $i++ ) {
    $p = new WP_Post(); $p->ID = $i; $p->post_title = 'Topic ' . $i;
    $GLOBALS['rows'][] = $p;
}
$GLOBALS['rows'][4]->post_password = 'private';
$GLOBALS['rows'][9]->post_status = 'draft';
$first = pvdak_legal_index_window( 7, 1 );
$second = pvdak_legal_index_window( 7, 2 );
$third = pvdak_legal_index_window( 7, 3 );
$fourth = pvdak_legal_index_window( 7, 4 );
check_d31( count( $first['pages'] ) === 24 && $first['has_next'], 'first page' );
check_d31( count( $second['pages'] ) === 24 && $second['has_next'], 'second page' );
check_d31( count( $third['pages'] ) === 21 && ! $third['has_next'], 'last page' );
check_d31( count( $fourth['pages'] ) === 0 && ! $fourth['has_next'], 'out-of-range page' );
$all = array_merge( $first['pages'], $second['pages'], $third['pages'] );
check_d31( count( array_unique( array_map( static function ( $p ) { return $p->ID; }, $all ) ) ) === 69, 'no duplicates or missing public Pages' );
check_d31( ! in_array( 5, array_map( static function ( $p ) { return $p->ID; }, $all ), true ), 'password page excluded' );
check_d31( ! in_array( 10, array_map( static function ( $p ) { return $p->ID; }, $all ), true ), 'draft excluded' );
check_d31( max( array_column( $GLOBALS['queries'], 'posts_per_page' ) ) === 25, 'bounded query' );
check_d31( $GLOBALS['queries'][1]['offset'] === 24 && $GLOBALS['queries'][2]['offset'] === 48, 'stable offsets' );
check_d31( array() === pvdak_legal_index_window( 8, 1 )['pages'], 'invalid parent' );
check_d31( array() === pvdak_legal_index_window( 7, 10000 )['pages'], 'high page rejected' );
foreach ( array( '0', '-1', '01', '1junk', '10000', array( '1' ) ) as $bad ) {
    $_GET['pvdak_topics_page'] = $bad;
    check_d31( pvdak_legal_index_page_number() === 1, 'invalid GET rejected' );
}
$_GET['pvdak_topics_page'] = '2';
check_d31( pvdak_legal_index_page_number() === 2, 'valid page' );
check_d31( isset( pvdak_legal_index_robots( array( 'follow' => true ) )['noindex'] ), 'archive query noindex' );
$GLOBALS['index_template'] = false;
check_d31( array( 'follow' => true ) === pvdak_legal_index_robots( array( 'follow' => true ) ), 'other pages indexable' );
unset( $_GET['pvdak_topics_page'] );
$GLOBALS['index_template'] = true;
check_d31( array( 'follow' => true ) === pvdak_legal_index_robots( array( 'follow' => true ) ), 'clean archive indexable' );
// A third-party query filter must not inject a foreign Page, malformed ID or duplicate.
$valid = new WP_Post(); $valid->ID = 77;
$bad_id = clone $valid; $bad_id->ID = '077';
$bad_parent = clone $valid; $bad_parent->post_parent = '7junk';
$duplicate = clone $valid;
$GLOBALS['injected'] = array( $valid, $bad_id, $bad_parent, $duplicate );
$guarded = pvdak_legal_index_window( 7, 1 );
check_d31( 1 === count( $guarded['pages'] ) && 77 === $guarded['pages'][0]->ID,
    'post-query validation excludes malformed IDs, foreign parents and duplicates' );
$GLOBALS['injected'] = false;
check_d31( array() === pvdak_legal_index_window( 7, 1 )['pages'], 'non-array query fails closed' );
unset( $GLOBALS['injected'] );
echo 'PVDAK_D31_INDEX_OK: ' . $GLOBALS['checks'] . '/' . $GLOBALS['checks'] . " original-source PHP cases; WP/HTTP/host NOT_TESTED.\n";
