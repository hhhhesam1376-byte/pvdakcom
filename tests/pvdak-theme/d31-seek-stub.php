<?php
/** D31 seek query fixture: executes actual theme helpers with a WP_Query-like stub. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ ); }
class WP_Post {
    public $ID, $post_type = 'page', $post_status = 'publish', $post_parent = 7;
    public $post_password = '', $post_title = '', $post_excerpt = '', $menu_order = 0;
}
class D31_Query {
    private $args;
    public function __construct( $args ) { $this->args = $args; }
    public function get( $key ) { return isset( $this->args[ $key ] ) ? $this->args[ $key ] : null; }
}
class D31_DB {
    public $posts = 'wp_posts';
    public function prepare( $sql, ...$values ) {
        $GLOBALS['d31_seek'] = array(
            'op' => strpos( $sql, 'menu_order < %d' ) !== false ? '<' : '>',
            'tuple' => array( $values[0], $values[2], $values[4] ),
        );
        $escaped = array_map( static function ( $value ) {
            return is_int( $value ) ? $value : "'" . str_replace( "'", "''", $value ) . "'";
        }, $values );
        return vsprintf( str_replace( '%s', '%s', $sql ), $escaped );
    }
}
$GLOBALS['wpdb'] = new D31_DB();
$GLOBALS['d31_filters'] = array();
$GLOBALS['d31_queries'] = array();
$GLOBALS['d31_rows'] = array();
function add_filter( $name, $callback, $priority = 10, $accepted = 1 ) {
    $GLOBALS['d31_filters'][ $name ][ $priority ] = $callback;
}
function remove_filter( $name, $callback, $priority = 10 ) {
    if ( isset( $GLOBALS['d31_filters'][ $name ][ $priority ] ) &&
         $GLOBALS['d31_filters'][ $name ][ $priority ] === $callback ) {
        unset( $GLOBALS['d31_filters'][ $name ][ $priority ] );
    }
}
function add_action() {}
function wp_salt() { return 'isolated-d31-test-key'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function pvdak_home_configured_page_id( $id ) { return is_int( $id ) && $id > 0 ? $id : 0; }
function get_post_type( $id ) { return 7 === $id ? 'page' : false; }
function get_post_status( $id ) { return 7 === $id ? 'publish' : false; }
function is_page_template( $slug ) { return ! empty( $GLOBALS['d31_index_template'] ) && 'page-templates/legal-topics-index.php' === $slug; }
function is_front_page() { return true; }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
function d31_cmp( $a, $b ) {
    $m = $a[0] <=> $b[0];
    if ( $m ) { return $m; }
    $t = strcmp( $a[1], $b[1] );
    return $t ?: ( $a[2] <=> $b[2] );
}
function get_posts( $args ) {
    $GLOBALS['d31_queries'][] = $args;
    if ( ! empty( $args['offset'] ) || $args['posts_per_page'] > 41 ||
         ! empty( $args['suppress_filters'] ) ) { throw new RuntimeException( 'Unbounded or unfiltered query' ); }
    $GLOBALS['d31_seek'] = null;
    $where = '';
    foreach ( $GLOBALS['d31_filters']['posts_where'] ?? array() as $callback ) {
        $where = $callback( $where, new D31_Query( $args ) );
    }
    $GLOBALS['d31_where'][] = $where;
    if ( array_key_exists( 'd31_injected', $GLOBALS ) ) { return $GLOBALS['d31_injected']; }
    $rows = array_values( array_filter( $GLOBALS['d31_rows'], static function ( $p ) use ( $args ) {
        if ( 'page' !== $p->post_type || 'publish' !== $p->post_status ||
             $p->post_parent !== $args['post_parent'] || '' !== $p->post_password ) { return false; }
        if ( null === $GLOBALS['d31_seek'] ) { return true; }
        $cmp = d31_cmp( array( $p->menu_order, $p->post_title, $p->ID ), $GLOBALS['d31_seek']['tuple'] );
        return '<' === $GLOBALS['d31_seek']['op'] ? $cmp < 0 : $cmp > 0;
    } ) );
    usort( $rows, static function ( $a, $b ) use ( $args ) {
        $cmp = d31_cmp( array( $a->menu_order, $a->post_title, $a->ID ),
            array( $b->menu_order, $b->post_title, $b->ID ) );
        return 'DESC' === $args['orderby']['ID'] ? -$cmp : $cmp;
    } );
    return array_slice( $rows, 0, $args['posts_per_page'] );
}
function d31_page( $id, $title, $status = 'publish', $password = '' ) {
    $p = new WP_Post(); $p->ID = $id; $p->post_title = $title;
    $p->post_status = $status; $p->post_password = $password;
    return $p;
}
function d31_check( $condition, $message ) {
    if ( ! $condition ) { fwrite( STDERR, 'D31_SEEK_FAIL: ' . $message . "\n" ); exit( 1 ); }
    $GLOBALS['d31_checks']++;
}
$GLOBALS['d31_checks'] = 0;
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-seek.php';
