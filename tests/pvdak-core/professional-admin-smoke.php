<?php
/** Static checks only; not a WordPress/permissions/database integration test. */
$root = dirname( __DIR__, 2 );
$src = file_get_contents( $root . '/packages/pvdak-core/includes/professional-admin.php' );
$boot = file_get_contents( $root . '/packages/pvdak-core/pvdak-core.php' );
if ( false === $src || false === $boot ) { fwrite( STDERR, "PROF_ADMIN_SOURCE_MISSING\n" ); exit( 2 ); }
$checks = array(
    'capability scoped' => strpos( $src, 'current_user_can( $type->cap->edit_posts )' ) !== false,
    'main query only' => strpos( $src, '$query->is_main_query()' ) !== false,
    'admin only' => strpos( $src, '! is_admin()' ) !== false,
    'private post type' => strpos( $src, '\'pvdak_professional\' !== $query->get( \'post_type\' )' ) !== false,
    'type allowlist' => strpos( $src, 'array_keys( pvdak_core_member_types() )' ) !== false,
    'state allowlist' => strpos( $src, 'array_keys( pvdak_core_member_states() )' ) !== false,
    'method allowlist' => strpos( $src, 'array_keys( pvdak_core_review_methods() )' ) !== false,
    'expiry allowlist' => strpos( $src, "array( 'expired', 'next30', 'future', 'missing' )" ) !== false,
    'sanitized GET' => strpos( $src, 'sanitize_key( $raw )' ) !== false,
    'malformed filters fail closed' => strpos( $src, '$filters[\'_invalid\'] = true' ) !== false &&
        strpos( $src, '$query->set( \'post__in\', array( 0 ) )' ) !== false,
    'invalid input never echoed in warning' => strpos( $src, 'pvdak_core_prof_admin_invalid_notice' ) !== false &&
        strpos( $src, 'فیلترها را پاک کنید') !== false,
    'city rejects overlong or altered text' => strpos( $src, '$bounded !== $city' ) !== false,
    'bounded city' => strpos( $src, 'wp_html_excerpt( $city, 70' ) !== false,
    'city search' => strpos( $src, "'compare' => 'LIKE'" ) !== false,
    'preserve other meta filters' => strpos( $src, '$query->get( \'meta_query\' )' ) !== false,
    'expired filter' => strpos( $src, '\'expired\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'future filter' => strpos( $src, '\'future\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'upcoming filter' => strpos( $src, '\'next30\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'missing filter' => strpos( $src, "'compare' => 'NOT EXISTS'" ) !== false,
    'reset control' => strpos( $src, 'پاک‌کردن فیلترها' ) !== false,
    'accessible filters' => strpos( $src, 'screen-reader-text' ) !== false,
    'review column' => strpos( $src, '\'pvdak_license\' === $column' ) !== false,
    'expiry column' => strpos( $src, '\'pvdak_license_expiry\' === $column' ) !== false,
    'reviewer column' => strpos( $src, '\'pvdak_reviewer\' === $column' ) !== false,
    'tracking column' => strpos( $src, '\'pvdak_tracking\' === $column' ) !== false,
    'expiry sorting' => strpos( $src, '\'pvdak_expiry\' === $query->get( \'orderby\' )' ) !== false,
    'audit is read only' => strpos( $src, 'get_post_meta( $post->ID, $meta_key, false )' ) !== false && strpos( $src, 'update_post_meta(' ) === false,
    'bounded audit history' => strpos( $src, 'array_slice( $events, -10 )' ) !== false,
    'bootstrapped' => strpos( $boot, "'/includes/professional-admin.php'" ) !== false,
);
foreach ( $checks as $name => $passed ) {
    if ( ! $passed ) { fwrite( STDERR, "PROF_ADMIN_FAIL: $name\n" ); exit( 1 ); }
}
echo 'PVDAK_PROF_ADMIN_SOURCE_OK: ' . count( $checks ) . " static checks; not WP runtime\n";

// Execute the actual filter/query functions with minimal CLI WordPress stubs.
// This is a unit test of repository code, NOT a database or WP integration test.
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', $root . '/' ); }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {}
function pvdak_core_member_types() { return array( 'lawyer' => 'وکیل', 'student' => 'دانشجو' ); }
function pvdak_core_member_states() { return array( 'pending' => 'ثبت‌شده', 'approved' => 'تأیید' ); }
function pvdak_core_review_methods() { return array( 'manual' => 'دستی' ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function wp_html_excerpt( $value, $length, $more = '' ) { return substr( $value, 0, $length ) . $more; }
function get_post_type_object( $type ) { return (object) array( 'cap' => (object) array( 'edit_posts' => 'edit_pvdak_professionals' ) ); }
function current_user_can( $capability ) { return 'edit_pvdak_professionals' === $capability; }
function is_admin() { return true; }
function current_time( $format ) { return '2026-10-27'; }
require_once $root . '/packages/pvdak-core/includes/professional-admin.php';
class Pvdak_Prof_Admin_Test_Query {
    private $vars;
    public function __construct() {
        $this->vars = array( 'post_type' => 'pvdak_professional', 'meta_query' => array(
            array( 'key' => '_test_existing_scope', 'value' => '1' ),
        ) );
    }
    public function is_main_query() { return true; }
    public function get( $name ) { return $this->vars[ $name ] ?? null; }
    public function set( $name, $value ) { $this->vars[ $name ] = $value; }
}
$cases = array(
    array( array(), false ),
    array( array( 'pvdak_member_filter' => 'lawyer' ), false ),
    array( array( 'pvdak_state_filter' => 'approved' ), false ),
    array( array( 'pvdak_city_filter' => 'Tehran' ), false ),
    array( array( 'pvdak_member_filter' => 'unknown' ), true ),
    array( array( 'pvdak_member_filter' => array( 'lawyer' ) ), true ),
    array( array( 'pvdak_expiry_filter' => '-1' ), true ),
    array( array( 'pvdak_city_filter' => array( 'Tehran' ) ), true ),
    array( array( 'pvdak_city_filter' => str_repeat( 'x', 71 ) ), true ),
    array( array( 'pvdak_member_filter' => 'lawyer ' ), true ),
);
foreach ( $cases as $index => $case ) {
    $_GET = $case[0];
    $filters = pvdak_core_prof_admin_filters();
    $query = new Pvdak_Prof_Admin_Test_Query();
    pvdak_core_prof_admin_query( $query );
    if ( $filters['_invalid'] !== $case[1] ||
         ( $case[1] && array( 0 ) !== $query->get( 'post__in' ) ) ||
         ( ! $case[1] && null !== $query->get( 'post__in' ) ) ||
         ! is_array( $query->get( 'meta_query' ) ) ) {
        fwrite( STDERR, "PROF_ADMIN_LOGIC_FAIL: case $index\n" );
        exit( 1 );
    }
}
echo 'PVDAK_PROF_ADMIN_LOGIC_OK: ' . count( $cases ) . " CLI cases against included source; no WP/DB\n";
