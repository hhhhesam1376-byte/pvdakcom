<?php
/** Synthetic, non-mutating triage regression; no WordPress or database needed. */
define( 'ABSPATH', __DIR__ );
define( 'ARRAY_A', 'ARRAY_A' );
$GLOBALS['pvdak_test_allowed'] = true;
$GLOBALS['pvdak_test_admin'] = true;
$GLOBALS['pagenow'] = 'edit.php';
function add_action() {}
function current_user_can( $cap ) { return $GLOBALS['pvdak_test_allowed'] && 'edit_pvdak_requests' === $cap; }
function is_admin() { return $GLOBALS['pvdak_test_admin']; }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $v ) ); }
function wp_unslash( $v ) { return stripslashes( $v ); }
function current_datetime() { return new DateTimeImmutable( '2026-10-24 12:00:00', new DateTimeZone( 'Asia/Tehran' ) ); }
function pvdak_core_request_types() { return array( 'lawyer' => 'وکیل', 'consultation' => 'مشاوره', 'question' => 'سؤال' ); }
function pvdak_core_request_statuses() { return array( 'submitted' => 'ثبت‌شده', 'review' => 'بررسی', 'matched' => 'ارجاع', 'answered' => 'پاسخ', 'closed' => 'بسته' ); }

class PvdakTriageFakeQuery {
    public $vars;
    public $main = true;
    function __construct( $vars = array() ) { $this->vars = $vars; }
    function is_main_query() { return $this->main; }
    function get( $key ) { return isset( $this->vars[ $key ] ) ? $this->vars[ $key ] : ''; }
    function set( $key, $value ) { $this->vars[ $key ] = $value; }
}
class PvdakTriageFakeDb {
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $sql = '';
    public $params = array();
    function prepare( $sql, ...$params ) { $this->sql = $sql; $this->params = $params; return $sql; }
    function get_results( $sql, $format ) {
        return array(
            array( 'state' => 'submitted', 'kind' => 'lawyer', 'total' => '5', 'older7' => '3', 'older30' => '1', 'oldest' => '2026-08-01 10:00:00' ),
            array( 'state' => 'review', 'kind' => 'consultation', 'total' => '2', 'older7' => '1', 'older30' => '0', 'oldest' => '2026-10-01 10:00:00' ),
            array( 'state' => 'closed', 'kind' => 'question', 'total' => '4', 'older7' => '4', 'older30' => '3', 'oldest' => '2026-07-01 10:00:00' ),
            array( 'state' => 'unexpected', 'kind' => 'unknown', 'total' => '1', 'older7' => '1', 'older30' => '1', 'oldest' => '2026-06-01 10:00:00' ),
        );
    }
}
$GLOBALS['wpdb'] = new PvdakTriageFakeDb();
require dirname( __DIR__, 2 ) . '/packages/pvdak-core/includes/triage.php';
$checks = 0;
function check_triage( $condition, $label ) {
    global $checks;
    ++$checks;
    if ( ! $condition ) { fwrite( STDERR, "PVDAK_TRIAGE_FAIL: $label\n" ); exit( 1 ); }
}
$_GET = array( 'pvdak_filter_type' => 'lawyer', 'pvdak_filter_state' => 'review', 'pvdak_filter_queue' => 'pending7' );
$f = pvdak_core_triage_filters();
check_triage( 'lawyer' === $f['pvdak_filter_type'], 'valid type' );
check_triage( 'review' === $f['pvdak_filter_state'], 'valid state' );
check_triage( 'pending7' === $f['pvdak_filter_queue'], 'valid queue' );
$_GET = array( 'pvdak_filter_type' => array( 'lawyer' ), 'pvdak_filter_state' => 'injected', 'pvdak_filter_queue' => 'pending90' );
$f = pvdak_core_triage_filters();
check_triage( '' === $f['pvdak_filter_type'], 'reject array' );
check_triage( '' === $f['pvdak_filter_state'], 'reject unknown state' );
check_triage( '' === $f['pvdak_filter_queue'], 'reject unknown queue' );
$_GET = array( 'pvdak_filter_type' => 'legacy', 'pvdak_filter_state' => 'legacy' );
$f = pvdak_core_triage_filters();
check_triage( 'legacy' === $f['pvdak_filter_type'], 'legacy type allowlisted' );
check_triage( 'legacy' === $f['pvdak_filter_state'], 'legacy state allowlisted' );
$legacy = pvdak_core_triage_legacy_condition( '_pvdak_request_state', array( 'submitted', 'review' ) );
check_triage( 'OR' === $legacy['relation'], 'legacy OR' );
check_triage( 'NOT EXISTS' === $legacy[0]['compare'], 'missing meta' );
check_triage( 'NOT IN' === $legacy[1]['compare'], 'invalid meta' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request', 'meta_query' => array( array( 'key' => 'existing' ) ) ) );
pvdak_core_triage_filter_query( $q );
check_triage( 'private' === $q->get( 'post_status' ), 'dashboard private scope' );
$mq = $q->get( 'meta_query' );
check_triage( 'AND' === $mq['relation'], 'meta conjunction' );
check_triage( 'existing' === $mq[0][0]['key'], 'preserve existing meta' );
check_triage( count( $mq ) === 4, 'legacy conditions combined' );
$_GET = array( 'pvdak_filter_queue' => 'pending7' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request', 'date_query' => array( array( 'after' => '2026-01-01' ) ) ) );
pvdak_core_triage_filter_query( $q );
$mq = $q->get( 'meta_query' );
check_triage( 'IN' === $mq[0]['compare'], 'pending states' );
check_triage( array( 'submitted', 'review' ) === $mq[0]['value'], 'pending keys' );
$dates = $q->get( 'date_query' );
check_triage( 'AND' === $dates['relation'], 'date conjunction' );
check_triage( '2026-01-01' === $dates[0][0]['after'], 'preserve date' );
check_triage( '2026-10-17' === $dates[1]['before'], '7 day cutoff' );
$_GET = array( 'pvdak_filter_queue' => 'pending30' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_triage_filter_query( $q );
check_triage( '2026-09-24' === $q->get( 'date_query' )[0]['before'], '30 day cutoff' );
$_GET = array( 'pvdak_filter_queue' => 'pending' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'post' ) );
pvdak_core_triage_filter_query( $q );
check_triage( ! isset( $q->vars['meta_query'] ), 'other post type untouched' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request' ) );
$q->main = false;
pvdak_core_triage_filter_query( $q );
check_triage( ! isset( $q->vars['meta_query'] ), 'secondary query untouched' );
$GLOBALS['pvdak_test_allowed'] = false;
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_triage_filter_query( $q );
check_triage( ! isset( $q->vars['meta_query'] ), 'unauthorized untouched' );
$GLOBALS['pvdak_test_allowed'] = true;
$_GET = array( 'pvdak_filter_queue' => 'pending' , 'post_status' => 'draft' );
$q = new PvdakTriageFakeQuery( array( 'post_type' => 'pvdak_request', 'post_status' => 'draft' ) );
pvdak_core_triage_filter_query( $q );
check_triage( 'draft' === $q->get( 'post_status' ), 'native status respected' );
$s = pvdak_core_triage_snapshot();
check_triage( 12 === $s['total'], 'total private' );
check_triage( 5 === $s['statuses']['submitted'], 'submitted' );
check_triage( 2 === $s['statuses']['review'], 'review' );
check_triage( 4 === $s['statuses']['closed'], 'closed' );
check_triage( 1 === $s['legacy_state'], 'legacy state count' );
check_triage( 1 === $s['legacy_type'], 'legacy type count' );
check_triage( 5 === $s['types']['lawyer'], 'lawyer count' );
check_triage( 7 === $s['pending'], 'pending count' );
check_triage( 4 === $s['pending7'], 'pending older7' );
check_triage( 1 === $s['pending30'], 'pending older30' );
check_triage( '2026-08-01 10:00:00' === $s['oldest_pending'], 'oldest pending' );
check_triage( strpos( $GLOBALS['wpdb']->sql, 'p.post_status = %s' ) !== false, 'private SQL scope' );
check_triage( 'private' === end( $GLOBALS['wpdb']->params ), 'private SQL parameter' );
$src = file_get_contents( dirname( __DIR__, 2 ) . '/packages/pvdak-core/includes/triage.php' );
check_triage( false === strpos( $src, 'update_post_meta(' ) && false === strpos( $src, 'wp_insert_post(' ), 'read only' );
check_triage( false === strpos( $src, 'post_content' ) && false === strpos( $src, '_pvdak_internal_notes' ), 'no private bodies' );
echo "PVDAK_TRIAGE_SYNTHETIC_OK: $checks checks; no WordPress or DB runtime tested\n";
