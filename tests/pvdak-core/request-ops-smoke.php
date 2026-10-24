<?php
/** Read-only operational queue filters: synthetic WP_Query checks only. */
define( 'ABSPATH', __DIR__ );
$GLOBALS['pagenow'] = 'edit.php';
$GLOBALS['case_allowed'] = true;
$GLOBALS['case_admin'] = true;
function add_action() {}
function add_filter() {}
function get_post_type_object( $name ) { return (object) array( 'cap' => (object) array( 'edit_posts' => 'edit_pvdak_requests' ) ); }
function current_user_can( $cap ) { return $GLOBALS['case_allowed']; }
function is_admin() { return $GLOBALS['case_admin']; }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $v ) ); }
function wp_unslash( $v ) { return stripslashes( $v ); }
function current_datetime() { return new DateTimeImmutable( '2026-10-24 12:00:00', new DateTimeZone( 'Asia/Tehran' ) ); }
function pvdak_core_triage_cutoff( $days, $now = null ) {
    if ( null === $now ) { $now = current_datetime(); }
    return $now->modify( '-' . (int) $days . ' days' )->format( 'Y-m-d H:i:s' );
}
class PvdakOpsQuery {
    public $vars;
    public $main = true;
    function __construct( $vars = array() ) { $this->vars = $vars; }
    function is_main_query() { return $this->main; }
    function get( $key ) { return isset( $this->vars[ $key ] ) ? $this->vars[ $key ] : ''; }
    function set( $key, $value ) { $this->vars[ $key ] = $value; }
}
require dirname( __DIR__, 2 ) . '/packages/pvdak-core/includes/request-admin.php';
$count = 0;
function check_ops( $condition, $label ) {
    global $count;
    ++$count;
    if ( ! $condition ) { fwrite( STDERR, "PVDAK_OPS_FAIL: $label\n" ); exit( 1 ); }
}
$_GET = array( 'pvdak_case_assignment' => 'assigned', 'pvdak_case_consistency' => 'matched_missing', 'pvdak_case_modified' => 'older30' );
$f = pvdak_core_case_ops_filters();
check_ops( 'assigned' === $f['pvdak_case_assignment'], 'assigned allowlist' );
check_ops( 'matched_missing' === $f['pvdak_case_consistency'], 'consistency allowlist' );
check_ops( 'older30' === $f['pvdak_case_modified'], 'modified allowlist' );
$_GET = array( 'pvdak_case_assignment' => array( 'assigned' ), 'pvdak_case_consistency' => 'fake', 'pvdak_case_modified' => 'older999' );
$f = pvdak_core_case_ops_filters();
check_ops( '' === $f['pvdak_case_assignment'], 'reject array' );
check_ops( '' === $f['pvdak_case_consistency'], 'reject fake consistency' );
check_ops( '' === $f['pvdak_case_modified'], 'reject fake modified' );
check_ops( 'REGEXP' === pvdak_core_case_ops_assigned_condition( true )['compare'], 'assigned numeric regexp' );
$unassigned = pvdak_core_case_ops_assigned_condition( false );
check_ops( 'OR' === $unassigned['relation'], 'unassigned includes missing' );
check_ops( 'NOT EXISTS' === $unassigned[0]['compare'], 'missing profile' );
check_ops( 'NOT REGEXP' === $unassigned[1]['compare'], 'blank/zero profile' );
$_GET = array( 'pvdak_case_assignment' => 'assigned', 'pvdak_case_modified' => 'last7' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request',
    'meta_query' => array( array( 'key' => 'existing' ) ),
    'date_query' => array( array( 'after' => '2026-01-01' ) ) ) );
pvdak_core_case_ops_query( $q );
check_ops( 'private' === $q->get( 'post_status' ), 'private default for ops' );
$m = $q->get( 'meta_query' );
check_ops( 'AND' === $m['relation'], 'combine meta AND' );
check_ops( 'existing' === $m[0][0]['key'], 'keep third-party meta' );
check_ops( 'REGEXP' === $m[1]['compare'], 'assigned filter' );
$d = $q->get( 'date_query' );
check_ops( 'AND' === $d['relation'], 'combine dates AND' );
check_ops( '2026-01-01' === $d[0][0]['after'], 'keep third-party date' );
check_ops( 'post_modified' === $d[1]['column'], 'modified column' );
check_ops( '2026-10-17 12:00:00' === $d[1]['after'], '7 day cutoff' );
check_ops( true === $d[1]['inclusive'], 'recent inclusive' );
$_GET = array( 'pvdak_case_consistency' => 'matched_missing' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_case_ops_query( $q );
$m = $q->get( 'meta_query' );
check_ops( 'matched' === $m[0]['value'], 'matched state' );
check_ops( 'OR' === $m[1]['relation'], 'matched missing profile' );
$_GET = array( 'pvdak_case_consistency' => 'assigned_pending' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_case_ops_query( $q );
$m = $q->get( 'meta_query' );
check_ops( array( 'submitted', 'review' ) === $m[0]['value'], 'pending states only' );
check_ops( 'REGEXP' === $m[1]['compare'], 'pending assigned profile' );
foreach ( array(
    'last1' => array( 'after', '2026-10-23 12:00:00' ),
    'older7' => array( 'before', '2026-10-17 12:00:00' ),
    'older30' => array( 'before', '2026-09-24 12:00:00' ),
    'future' => array( 'after', '2026-10-24 12:00:00' ),
) as $key => $expected ) {
    $_GET = array( 'pvdak_case_modified' => $key );
    $q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
    pvdak_core_case_ops_query( $q );
    $date = $q->get( 'date_query' )[0];
    check_ops( $expected[1] === $date[$expected[0]], $key . ' cutoff' );
    check_ops( 'post_modified' === $date['column'], $key . ' column' );
}
$_GET = array( 'pvdak_case_assignment' => 'unassigned', 'post_status' => 'draft' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request', 'post_status' => 'draft' ) );
pvdak_core_case_ops_query( $q );
check_ops( 'draft' === $q->get( 'post_status' ), 'explicit native status kept' );
$_GET = array();
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_case_ops_query( $q );
check_ops( '' === $q->get( 'post_status' ), 'no filter no status override' );
$_GET = array( 'pvdak_case_assignment' => 'assigned' );
$q = new PvdakOpsQuery( array( 'post_type' => 'post' ) );
pvdak_core_case_ops_query( $q );
check_ops( '' === $q->get( 'meta_query' ), 'other post type ignored' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
$q->main = false;
pvdak_core_case_ops_query( $q );
check_ops( '' === $q->get( 'meta_query' ), 'secondary query ignored' );
$GLOBALS['case_allowed'] = false;
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
pvdak_core_case_ops_query( $q );
check_ops( '' === $q->get( 'meta_query' ), 'unauthorized ignored' );
check_ops( array( 'original' => 'keep' ) === pvdak_core_case_ops_row_actions( array( 'original' => 'keep' ), (object) array(
    'ID' => 12, 'post_type' => 'pvdak_request', 'post_status' => 'private' ) ), 'unauthorized row action hidden' );

$GLOBALS['case_allowed'] = true;
foreach ( array(
    'today' => array( 'after' => '2026-10-24 00:00:00', 'before' => '2026-10-24 12:00:00' ),
    'last7' => array( 'after' => '2026-10-17 12:00:00', 'before' => '2026-10-24 12:00:00' ),
    'older7' => array( 'before' => '2026-10-17 12:00:00' ),
    'older30' => array( 'before' => '2026-09-24 12:00:00' ),
    'future' => array( 'after' => '2026-10-24 12:00:00' ),
) as $age => $expected ) {
    $_GET = array( 'pvdak_case_age' => $age );
    $q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request' ) );
    pvdak_core_case_admin_query( $q );
    $date = $q->get( 'date_query' )[0];
    foreach ( $expected as $field => $value ) {
        check_ops( $value === $date[ $field ], $age . ' exact ' . $field );
    }
    check_ops( 'post_date' === $date['column'], $age . ' post_date' );
    check_ops( 'private' === $q->get( 'post_status' ), $age . ' private default' );
}
$_GET = array( 'pvdak_case_owner' => '42' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request', 'author' => 41 ) );
pvdak_core_case_admin_query( $q );
check_ops( array( 0 ) === $q->get( 'post__in' ), 'author intersection prevents widening' );
$_GET = array( 'pvdak_case_age' => 'last7', 'post_status' => 'draft' );
$q = new PvdakOpsQuery( array( 'post_type' => 'pvdak_request', 'post_status' => 'draft',
    'date_query' => array( array( 'before' => '2026-11-01' ) ) ) );
pvdak_core_case_admin_query( $q );
check_ops( 'draft' === $q->get( 'post_status' ), 'native status preserved by case filters' );
check_ops( '2026-11-01' === $q->get( 'date_query' )[0][0]['before'], 'existing date query preserved' );
$columns = pvdak_core_case_ops_columns( array( 'pvdak_kind' => 'duplicate type',
    'pvdak_state' => 'duplicate state', 'pvdak_owner' => 'duplicate owner', 'title' => 'Title' ) );
check_ops( ! isset( $columns['pvdak_kind'], $columns['pvdak_state'], $columns['pvdak_owner'] ),
    'duplicate list columns removed' );
check_ops( isset( $columns['pvdak_case_kind'], $columns['pvdak_case_state'], $columns['pvdak_case_author'] ),
    'replacement columns present' );
echo "PVDAK_OPS_SYNTHETIC_OK: $count checks; no WordPress or database involved\n";
