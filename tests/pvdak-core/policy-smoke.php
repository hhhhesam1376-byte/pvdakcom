<?php
/**
 * PHP-only safety policy smoke tests for PVDak Core.
 * Does NOT bootstrap WordPress, access a database or change production data.
 *
 * Run: php tests/pvdak-core/policy-smoke.php
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function get_option( $name, $default = false ) { return $default; }

// Synthetic records — no connection to a real WordPress installation.
class WP_Post {
    public $ID;
    public $post_status;
    public $post_author;
    public function __construct( $id, $status = 'publish', $author = 1 ) {
        $this->ID = $id;
        $this->post_status = $status;
        $this->post_author = $author;
    }
}
function absint( $value ) { return abs( (int) $value ); }
function get_page_by_path( $slug, $output = null, $type = null ) {
    return 'legal-topics' === $slug ? new WP_Post( 10 ) : null;
}
function get_post_type( $id ) {
    return in_array( (int) $id, array( 10, 11, 12 ), true ) ? 'page' :
        ( in_array( (int) $id, array( 501, 502 ), true ) ? 'pvdak_professional' : '' );
}
function get_post_status( $id ) { return in_array( (int) $id, array( 501, 502 ), true ) ? 'private' : 'publish'; }
function wp_get_post_parent_id( $id ) { return in_array( (int) $id, array( 11, 12 ), true ) ? 10 : 0; }
$GLOBALS['pvdak_mock_cleared'] = '1';
$GLOBALS['pvdak_mock_state'] = 'approved';
$GLOBALS['pvdak_mock_capacity_limit'] = '2';
$GLOBALS['pvdak_mock_accepting'] = '1';
$GLOBALS['pvdak_mock_active_count'] = 1;
function get_post_meta( $id, $key, $single = true ) {
    if ( '_pvdak_specialty_page_ids' === $key ) { return array( 11 ); }
    if ( '_pvdak_referral_cleared' === $key ) { return $GLOBALS['pvdak_mock_cleared']; }
    if ( '_pvdak_member_state' === $key ) { return $GLOBALS['pvdak_mock_state']; }
    if ( '_pvdak_member_type' === $key ) { return (int) $id === 501 ? 'lawyer' : 'student'; }
    if ( '_pvdak_capacity_limit' === $key ) { return $GLOBALS['pvdak_mock_capacity_limit']; }
    if ( '_pvdak_accepting_assignments' === $key ) { return $GLOBALS['pvdak_mock_accepting']; }
    if ( '_pvdak_request_state' === $key ) { return (int) $id === 701 ? 'matched' : 'closed'; }
    return '';
}
function get_posts( $query ) {
    if ( isset( $query['post_type'] ) && 'pvdak_request' === $query['post_type'] ) {
        return $GLOBALS['pvdak_mock_active_count'] ? array_fill( 0, $GLOBALS['pvdak_mock_active_count'], 701 ) : array();
    }
    return array( new WP_Post( 501, 'private', 21 ), new WP_Post( 502, 'private', 22 ) );
}

require dirname( __DIR__, 2 ) . '/packages/pvdak-core/pvdak-core.php';

$checks = 0;
function pvdak_expect( $condition, $description ) {
    global $checks;
    $checks++;
    if ( ! $condition ) {
        fwrite( STDERR, "FAIL: {$description}\n" );
        exit( 1 );
    }
    echo "PASS: {$description}\n";
}

pvdak_expect( count( pvdak_core_request_types() ) === 3, 'Three distinct legal request types' );
pvdak_expect( count( pvdak_core_member_types() ) === 3, 'Three professional application categories' );
pvdak_expect( ! pvdak_core_intake_open(), 'Legal request intake starts OFF' );
pvdak_expect( ! pvdak_core_member_intake_open(), 'Professional onboarding starts OFF' );
pvdak_expect(
    pvdak_core_allowed_request_transitions( 'submitted' ) === array( 'review', 'closed' ),
    'New requests can only move to review/closed'
);
pvdak_expect(
    ! in_array( 'matched', pvdak_core_allowed_request_transitions( 'review' ), true ),
    'Unverified professional matching cannot be selected'
);
pvdak_expect(
    pvdak_core_allowed_request_transitions( 'closed' ) === array(),
    'Closed requests do not have an implicit reopen transition'
);
pvdak_expect(
    pvdak_core_enforce_private_status( array( 'post_type' => 'pvdak_request', 'post_status' => 'publish' ), array() )['post_status'] === 'private',
    'Legal case cannot be published by WordPress'
);
pvdak_expect(
    pvdak_core_enforce_private_status( array( 'post_type' => 'pvdak_professional', 'post_status' => 'future' ), array() )['post_status'] === 'private',
    'Professional application cannot be scheduled publicly'
);
pvdak_expect(
    pvdak_core_enforce_private_status( array( 'post_type' => 'page', 'post_status' => 'publish' ), array() )['post_status'] === 'publish',
    'Normal published pages remain unaffected'
);
pvdak_expect(
    pvdak_core_enforce_private_status( array( 'post_type' => 'pvdak_request', 'post_status' => 'draft' ), array() )['post_status'] === 'draft',
    'Private cases can remain drafts while administered'
);
pvdak_expect( pvdak_core_landing_parent_id() === 10, 'Real published landing Page is a valid parent' );
pvdak_expect( pvdak_core_valid_specialty( 11 ), 'Published child landing Page is a valid specialty' );
pvdak_expect( ! pvdak_core_valid_specialty( 999 ), 'Unrelated nonexistent Page cannot be selected' );
pvdak_expect( pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Internally cleared lawyer matches related landing' );
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 12, 'lawyer' ), 'Different legal landing is not considered a match' );
pvdak_expect( ! pvdak_core_profile_referral_eligible( 502, 11, 'lawyer' ), 'Student role cannot be matched as lawyer' );
pvdak_expect( count( pvdak_core_assignable_profiles( 11, 'lawyer' ) ) === 1, 'Only eligible reviewed lawyer enters candidate set' );
$GLOBALS['pvdak_mock_cleared'] = '0';
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Removing manual referral clearance blocks assignment' );
$GLOBALS['pvdak_mock_cleared'] = '1';
$GLOBALS['pvdak_mock_state'] = 'pending';
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Pending membership cannot be internally matched' );
$GLOBALS['pvdak_mock_state'] = 'approved';
pvdak_expect( pvdak_core_capacity_limit( 501 ) === 2, 'Lawyer capacity cap stored as bounded integer' );
pvdak_expect( pvdak_core_active_assignment_count( 501 ) === 1, 'Only active internally assigned case records count toward capacity' );
pvdak_expect( pvdak_core_capacity_available( 501 ), 'Eligible capacity leaves one new slot' );
$GLOBALS['pvdak_mock_active_count'] = 2;
pvdak_expect( ! pvdak_core_capacity_available( 501 ), 'Full capacity excludes candidate for new assignments' );
pvdak_expect( count( pvdak_core_assignable_profiles( 11, 'lawyer' ) ) === 0, 'Full lawyer is absent from candidate dropdown' );
$GLOBALS['pvdak_mock_active_count'] = 1;
$GLOBALS['pvdak_mock_accepting'] = '0';
pvdak_expect( ! pvdak_core_capacity_available( 501 ), 'Manually paused accepting status blocks matching' );
$GLOBALS['pvdak_mock_accepting'] = '1';
$GLOBALS['pvdak_mock_capacity_limit'] = '0';
pvdak_expect( ! pvdak_core_capacity_available( 501 ), 'Unconfigured capacity defaults to closed instead of unlimited' );
$GLOBALS['pvdak_mock_capacity_limit'] = '2';
pvdak_expect( pvdak_core_capacity_available( 501 ), 'Capacity restores only when both limit and accepting switch are valid' );
echo "CORE_POLICY_SMOKE_OK: {$checks} checks.\n";
