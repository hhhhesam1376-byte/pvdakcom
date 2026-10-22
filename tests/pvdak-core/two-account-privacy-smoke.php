<?php
/**
 * Synthetic two-account privacy regression, using the REAL bundled Core source.
 *
 * No WordPress bootstrap, network/database connection or actual users/records.
 * Fake cases and profiles represent two distinct logged-in accounts.
 * Host must provide PVDAK_CORE_SOURCE pointing to bundled Core entry.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function get_option( $name, $default = false ) { return $default; }
function absint( $v ) { return abs( (int) $v ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $v ) { return esc_html( $v ); }
function get_permalink( $id = 0 ) { return 'https://example.invalid/account'; }
function wp_login_url( $url = '' ) { return 'https://example.invalid/login'; }
function remove_query_arg( $key, $url = '' ) { return $url; }
function add_query_arg( $key, $value, $url = '' ) { return $url; }
function paginate_links( $args ) { return ''; }
function get_page_by_path( $slug, $output = null, $type = null ) { return null; }
function get_post_type( $id ) {
    return isset( $GLOBALS['case_posts'][$id] ) ? 'pvdak_request' :
        ( isset( $GLOBALS['member_posts'][$id] ) ? 'pvdak_professional' : '' );
}
function get_post_status( $id ) { return 'private'; }
function get_post_field( $field, $id ) {
    $post = isset( $GLOBALS['member_posts'][$id] ) ? $GLOBALS['member_posts'][$id] :
        ( isset( $GLOBALS['case_posts'][$id] ) ? $GLOBALS['case_posts'][$id] : null );
    return $post && isset( $post->$field ) ? $post->$field : '';
}
function get_the_title( $id ) {
    return isset( $GLOBALS['case_posts'][$id] ) ? $GLOBALS['case_posts'][$id]->post_title : '';
}
function get_post_meta( $id, $key, $single = true ) {
    return isset( $GLOBALS['mock_meta'][$id][$key] ) ? $GLOBALS['mock_meta'][$id][$key] : '';
}
function get_user_meta( $id, $key, $single = true ) {
    return isset( $GLOBALS['mock_user_meta'][$id][$key] ) ? $GLOBALS['mock_user_meta'][$id][$key] : '';
}
function update_user_meta( $id, $key, $value ) {
    // Detect any unexpected persistent write path, even though this is mocked.
    $GLOBALS['write_attempts']++;
    return false;
}
function is_user_logged_in() { return $GLOBALS['viewer_id'] > 0; }
function get_current_user_id() { return $GLOBALS['viewer_id']; }
function wp_get_current_user() {
    return (object) array( 'ID' => $GLOBALS['viewer_id'], 'display_name' => 'Synthetic viewer' );
}
function current_user_can( $cap ) { return false; }
function wp_unslash( $value ) { return $value; }

class WP_Post {
    public $ID;
    public $post_author;
    public $post_type;
    public $post_status;
    public $post_title;
    public function __construct( $id, $owner, $kind, $title ) {
        $this->ID = $id;
        $this->post_author = $owner;
        $this->post_type = $kind;
        $this->post_status = 'private';
        $this->post_title = $title;
    }
}
$GLOBALS['viewer_id'] = 0;
$GLOBALS['write_attempts'] = 0;
$GLOBALS['poison_query'] = false;
$GLOBALS['case_posts'] = array(
    1101 => new WP_Post( 1101, 11, 'pvdak_request', 'ALPHA_CASE_ONLY' ),
    2202 => new WP_Post( 2202, 22, 'pvdak_request', 'BETA_CASE_ONLY' ),
);
$GLOBALS['member_posts'] = array(
    3301 => new WP_Post( 3301, 11, 'pvdak_professional', 'ALPHA_MEMBER_ONLY' ),
    4402 => new WP_Post( 4402, 22, 'pvdak_professional', 'BETA_MEMBER_ONLY' ),
);
$GLOBALS['mock_meta'] = array(
    1101 => array( '_pvdak_request_type' => 'lawyer', '_pvdak_request_state' => 'submitted' ),
    2202 => array( '_pvdak_request_type' => 'question', '_pvdak_request_state' => 'closed' ),
    3301 => array( '_pvdak_member_state' => 'pending', '_pvdak_member_type' => 'trainee', '_pvdak_member_city' => 'ALPHA_CITY_ONLY' ),
    4402 => array( '_pvdak_member_state' => 'review', '_pvdak_member_type' => 'student', '_pvdak_member_city' => 'BETA_CITY_ONLY' ),
);
$GLOBALS['mock_user_meta'] = array(
    11 => array( '_pvdak_core_application' => 3301 ),
    22 => array( '_pvdak_core_application' => 4402 ),
    33 => array( '_pvdak_core_application' => 4402 ), // Deliberately forged link to another account.
);
function get_posts( $args ) {
    if ( ! isset( $args['post_type'] ) ) { return array(); }
    $type = $args['post_type'];
    // The source never needs actual assignments in this fixture.
    if ( isset( $args['meta_key'] ) && '_pvdak_assigned_profile_id' === $args['meta_key'] ) { return array(); }
    $source = 'pvdak_request' === $type ? $GLOBALS['case_posts'] :
        ( 'pvdak_professional' === $type ? $GLOBALS['member_posts'] : array() );
    $author = isset( $args['author'] ) ? (int) $args['author'] : 0;
    $owned = array_values( array_filter( $source, function ( $post ) use ( $author ) {
        return $post->post_author === $author;
    } ) );
    if ( ! empty( $args['fields'] ) && 'ids' === $args['fields'] ) {
        return array_map( function ( $post ) { return $post->ID; }, $owned );
    }
    // Misbehaving query provider returns a foreign case as well as own records.
    // Privacy code must perform its own final ownership check.
    if ( $GLOBALS['poison_query'] && 'pvdak_request' === $type ) {
        foreach ( $source as $item ) {
            if ( $item->post_author !== $author ) { $owned[] = $item; }
        }
    }
    return $owned;
}
class WP_Query {
    public $posts;
    public $max_num_pages = 1;
    public function __construct( $args ) { $this->posts = get_posts( $args ); }
}

$core = getenv( 'PVDAK_CORE_SOURCE' );
if ( ! is_string( $core ) || ! is_file( $core ) ) {
    fwrite( STDERR, "STOP: PVDAK_CORE_SOURCE must be an existing bundled Core PHP entry.\n" );
    exit( 2 );
}
require $core;

$checks = 0;
function pvdak_privacy_assert( $condition, $label ) {
    global $checks;
    $checks++;
    if ( ! $condition ) {
        fwrite( STDERR, "PRIVACY_FAIL: " . $label . "\n" );
        exit( 1 );
    }
}
pvdak_privacy_assert( ! pvdak_core_intake_open() && ! pvdak_core_member_intake_open(), 'Both intakes remain closed' );
$GLOBALS['viewer_id'] = 0;
pvdak_privacy_assert(
    false === strpos( pvdak_core_my_requests(), 'ALPHA_CASE_ONLY' ) &&
    false === strpos( pvdak_core_my_requests(), 'BETA_CASE_ONLY' ),
    'Anonymous request list contains no private cases'
);
pvdak_privacy_assert(
    false === strpos( pvdak_core_dashboard(), 'ALPHA_CASE_ONLY' ) &&
    false === strpos( pvdak_core_dashboard(), 'BETA_CASE_ONLY' ),
    'Anonymous dashboard contains no private cases'
);
pvdak_privacy_assert(
    false === strpos( pvdak_core_professional_dashboard(), 'ALPHA_CITY_ONLY' ) &&
    false === strpos( pvdak_core_professional_dashboard(), 'BETA_CITY_ONLY' ),
    'Anonymous professional dashboard contains no private profile'
);
$GLOBALS['poison_query'] = true;
foreach ( array( 11, 22 ) as $id ) {
    $GLOBALS['viewer_id'] = $id;
    $self = 11 === $id ? 'ALPHA_CASE_ONLY' : 'BETA_CASE_ONLY';
    $other = 11 === $id ? 'BETA_CASE_ONLY' : 'ALPHA_CASE_ONLY';
    $self_city = 11 === $id ? 'ALPHA_CITY_ONLY' : 'BETA_CITY_ONLY';
    $other_city = 11 === $id ? 'BETA_CITY_ONLY' : 'ALPHA_CITY_ONLY';
    $list = pvdak_core_my_requests();
    pvdak_privacy_assert( false !== strpos( $list, $self ), 'Own request visible in case list' );
    pvdak_privacy_assert( false === strpos( $list, $other ), 'Foreign request absent from case list despite poisoned query' );
    $dashboard = pvdak_core_dashboard();
    pvdak_privacy_assert( false !== strpos( $dashboard, $self ), 'Own request visible in account dashboard' );
    pvdak_privacy_assert( false === strpos( $dashboard, $other ), 'Foreign request absent from account dashboard despite poisoned query' );
    pvdak_privacy_assert( false === strpos( $dashboard, $other_city ), 'Foreign membership metadata absent from account dashboard' );
    $professional = pvdak_core_professional_dashboard();
    pvdak_privacy_assert( false !== strpos( $professional, $self_city ), 'Own professional status visible' );
    pvdak_privacy_assert( false === strpos( $professional, $other_city ), 'Foreign professional metadata hidden' );
    pvdak_privacy_assert( false === strpos( $professional, $other ), 'Professional dashboard exposes no client case titles' );
}
$GLOBALS['viewer_id'] = 33;
$forged = pvdak_core_professional_dashboard();
pvdak_privacy_assert( false === strpos( $forged, 'BETA_CITY_ONLY' ), 'Forged cross-owner professional link rejected' );
pvdak_privacy_assert( $GLOBALS['write_attempts'] === 0, 'Read-only simulated views do not attempt writes' );
$expected = 22; // 4 anonymous/option checks + 8 checks each for 2 users + 2 forged/write checks.
pvdak_privacy_assert( $checks === $expected, 'Exact synthetic assertion count executed' );
echo "PVDAK_TWO_ACCOUNT_PRIVACY_OK: checks=" . $checks . " (synthetic; no real accounts, data or writes)\n";
