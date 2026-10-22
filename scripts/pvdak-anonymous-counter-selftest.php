<?php
/**
 * Off-site synthetic regression: execute the actual private anonymous smoke
 * under the same function-scoped include semantics as WP-CLI eval-file.
 *
 * Run twice: php scripts/pvdak-anonymous-counter-selftest.php pass
 *            php scripts/pvdak-anonymous-counter-selftest.php fail
 * Never bootstraps WordPress, reads a database, or connects to a network.
 */
$mode = isset( $argv[1] ) ? $argv[1] : '';
if ( ! in_array( $mode, array( 'pass', 'fail' ), true ) ) {
    fwrite( STDERR, "USAGE: php pvdak-anonymous-counter-selftest.php pass|fail\n" );
    exit( 2 );
}
define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_CLI', true );

class WP_CLI {
    public static function line( $message ) { echo $message . "\n"; }
    public static function error( $message ) { throw new RuntimeException( $message ); }
    public static function success( $message ) { echo $message . "\n"; }
}
$GLOBALS['pvdak_test_user'] = 1;
function get_current_user_id() { return $GLOBALS['pvdak_test_user']; }
function wp_set_current_user( $id ) { $GLOBALS['pvdak_test_user'] = (int) $id; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function current_user_can( $cap ) { return false; }
function pvdak_core_request_form() { return '<p>disabled</p>'; }
function pvdak_core_member_application_form() { return '<p>disabled</p>'; }
function pvdak_core_my_requests() { return '<p>login required</p>'; }
function pvdak_core_dashboard() { return '<p>login required</p>'; }
function pvdak_core_professional_dashboard() { return '<p>login required</p>'; }
function pvdak_core_intake_open() { return false; }
function pvdak_core_member_intake_open() { return false; }
function pvdak_maintenance_enabled() { return $GLOBALS['pvdak_test_mode'] !== 'fail'; }
function do_shortcode( $text ) { return '<p>locked</p>'; }
class PVDak_Anonymous_Test_REST {
    public function get_routes() { return array( '/wp/v2/posts' => array() ); }
}
function rest_get_server() { return new PVDak_Anonymous_Test_REST(); }

$GLOBALS['pvdak_test_mode'] = $mode;
$exception = null;
ob_start();
try {
    // WordPress CLI runs eval-file in a nested PHP scope; reproduce it.
    (function () { include __DIR__ . '/pvdak-anonymous-live-smoke.php'; })();
} catch ( RuntimeException $error ) {
    $exception = $error;
}
$output = ob_get_clean();
if ( ! preg_match( '/PVDAK_ANON_LIVE_SMOKE: checks=(\d+) failures=(\d+) expected=(\d+)/', $output, $match ) ) {
    fwrite( STDERR, "COUNTER_SELFTEST_FAIL: summary absent\n" );
    exit( 1 );
}
$checks = (int) $match[1];
$failures = (int) $match[2];
$expected = (int) $match[3];
$correct = $checks === 19 && $expected === 19 &&
    ( 'pass' === $mode
        ? ( 0 === $failures && null === $exception )
        : ( 1 === $failures && $exception instanceof RuntimeException ) ) &&
    get_current_user_id() === 1;
if ( ! $correct ) {
    fwrite( STDERR, "COUNTER_SELFTEST_FAIL: incorrect PASS/FAIL or missing assertions\n" );
    exit( 1 );
}
echo 'COUNTER_SELFTEST_OK: mode=' . $mode . ' checks=' . $checks .
    ' failures=' . $failures . "\n";
