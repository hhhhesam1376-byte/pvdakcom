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
function get_option( $name, $default = false ) {
    return 'pvdak_core_email_notifications_enabled' === $name ?
        ( $GLOBALS['pvdak_mock_notify_enabled'] ?? '0' ) : $default;
}

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
$GLOBALS['pvdak_mock_topic_draft'] = false;
$GLOBALS['pvdak_mock_topic_title'] = 'حقوق خانواده';
function get_post_status( $id ) {
    if ( 11 === (int) $id && $GLOBALS['pvdak_mock_topic_draft'] ) { return 'draft'; }
    return in_array( (int) $id, array( 501, 502 ), true ) ? 'private' : 'publish';
}
function wp_get_post_parent_id( $id ) { return in_array( (int) $id, array( 11, 12 ), true ) ? 10 : 0; }
$GLOBALS['pvdak_mock_profile_author'] = 21;
$GLOBALS['pvdak_mock_profile_name'] = 'وکیل نمونه';
$GLOBALS['pvdak_mock_profile_city'] = 'تهران';
$GLOBALS['pvdak_mock_reviewer_exists'] = true;
function get_post_field( $field, $id ) {
    if ( 'post_title' === $field && 501 === (int) $id ) { return $GLOBALS['pvdak_mock_profile_name']; }
    if ( 'post_title' === $field && 11 === (int) $id ) { return $GLOBALS['pvdak_mock_topic_title']; }
    if ( 'post_title' === $field && 12 === (int) $id ) { return 'حقوق تجارت'; }
    if ( 'post_author' !== $field ) { return ''; }
    if ( 501 === (int) $id ) { return $GLOBALS['pvdak_mock_profile_author']; }
    if ( 502 === (int) $id ) { return 22; }
    if ( 701 === (int) $id ) { return 23; }
    if ( 704 === (int) $id ) { return 88; }
    if ( 702 === (int) $id ) { return 21; }
    return 0;
}
function get_userdata( $id ) {
    if ( 10 === (int) $id && $GLOBALS['pvdak_mock_reviewer_exists'] ) { return (object) array( 'ID' => 10 ); }
    return in_array( (int) $id, array( 21, 22, 23 ), true ) ?
        (object) array( 'ID' => (int) $id, 'user_email' => 'owner@example.test' ) : false;
}
function get_post( $id ) {
    if ( 901 === (int) $id ) {
        return (object) array( 'ID' => 901, 'post_type' => 'pvdak_request',
            'post_status' => $GLOBALS['pvdak_mock_notify_status'] ?? 'private', 'post_author' => 21 );
    }
    return null;
}
function get_user_meta( $id, $key, $single = true ) {
    return '_pvdak_email_verification_pending' === $key ? ( $GLOBALS['pvdak_mock_notify_pending'] ?? '0' ) : '';
}
function is_email( $value ) { return (bool) filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function wp_mail( $email, $subject, $body ) {
    $GLOBALS['pvdak_mock_sent_mail'][] = array( $email, $subject, $body );
    return true;
}
$GLOBALS['pvdak_mock_cleared'] = '1';
$GLOBALS['pvdak_mock_state'] = 'approved';
$GLOBALS['pvdak_mock_license_status'] = 'checked';
$GLOBALS['pvdak_mock_license_method'] = 'official_source';
$GLOBALS['pvdak_mock_license_expiry'] = '2099-12-31';
$GLOBALS['pvdak_mock_license_reviewer'] = 10;
$GLOBALS['pvdak_mock_capacity_limit'] = '2';
$GLOBALS['pvdak_mock_accepting'] = '1';
$GLOBALS['pvdak_mock_active_count'] = 1;
$GLOBALS['pvdak_mock_assignment_audits'] = array();
$GLOBALS['pvdak_mock_audit_request_ids'] = array();
function get_post_meta( $id, $key, $single = true ) {
    if ( '_pvdak_specialty_page_ids' === $key ) { return array( 11 ); }
    if ( '_pvdak_referral_cleared' === $key ) { return $GLOBALS['pvdak_mock_cleared']; }
    if ( '_pvdak_member_state' === $key ) { return $GLOBALS['pvdak_mock_state']; }
    if ( '_pvdak_member_type' === $key ) { return (int) $id === 501 ? 'lawyer' : 'student'; }
    if ( '_pvdak_member_city' === $key ) { return $GLOBALS['pvdak_mock_profile_city']; }
    if ( '_pvdak_license_review_identity' === $key ) { return $GLOBALS['pvdak_mock_license_signature'] ?? ''; }
    if ( '_pvdak_license_review_status' === $key ) { return $GLOBALS['pvdak_mock_license_status']; }
    if ( '_pvdak_license_review_method' === $key ) { return $GLOBALS['pvdak_mock_license_method']; }
    if ( '_pvdak_license_review_expires' === $key ) { return $GLOBALS['pvdak_mock_license_expiry']; }
    if ( '_pvdak_license_review_by' === $key ) { return $GLOBALS['pvdak_mock_license_reviewer']; }
    if ( '_pvdak_capacity_limit' === $key ) { return $GLOBALS['pvdak_mock_capacity_limit']; }
    if ( '_pvdak_accepting_assignments' === $key ) { return $GLOBALS['pvdak_mock_accepting']; }
    if ( '_pvdak_request_state' === $key ) { return (int) $id === 701 ? 'matched' : 'closed'; }
    if ( '_pvdak_assignment_audit' === $key ) { return $GLOBALS['pvdak_mock_assignment_audits'][ (int) $id ] ?? array(); }
    return '';
}
function current_time( $format ) { return '2026-10-22'; }
function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_UNICODE ); }
function wp_checkdate( $month, $day, $year, $source_date ) {
    return checkdate( $month, $day, $year );
}
function get_posts( $query ) {
    if ( isset( $query['post_type'] ) && 'pvdak_request' === $query['post_type'] ) {
        if ( isset( $query['meta_query'] ) ) { return $GLOBALS['pvdak_mock_audit_request_ids']; }
        return $GLOBALS['pvdak_mock_active_count'] ? array_fill( 0, $GLOBALS['pvdak_mock_active_count'], 701 ) : array();
    }
    return array( new WP_Post( 501, 'private', 21 ), new WP_Post( 502, 'private', 22 ) );
}

/**
 * Fake advisory locks for deterministic policy tests. No DB connection.
 * This tests lock orchestration, not actual simultaneous MariaDB processes.
 */
class PVDak_Test_Lock_DB {
    public $locks = array();
    public $deny = false;
    public $last_error = '';
    public function prepare( $sql, ...$args ) {
        $escaped = array_map( function( $arg ) {
            return is_string( $arg ) ? "'" . addslashes( $arg ) . "'" : (string) $arg;
        }, $args );
        $sql = str_replace( '%s', '%s', $sql );
        return vsprintf( $sql, $escaped );
    }
    public function get_var( $sql ) {
        if ( preg_match( "/GET_LOCK\\('([^']+)'/", $sql, $matches ) ) {
            if ( $this->deny || isset( $this->locks[ $matches[1] ] ) ) { return 0; }
            $this->locks[ $matches[1] ] = true;
            return 1;
        }
        if ( preg_match( "/RELEASE_LOCK\\('([^']+)'/", $sql, $matches ) ) {
            unset( $this->locks[ $matches[1] ] );
            return 1;
        }
        return null;
    }
}
$GLOBALS['wpdb'] = new PVDak_Test_Lock_DB();

// Supports both a full repository checkout and the current cPanel deployment:
// production keeps Core under the theme but strips private scripts from web.
$core_entry = getenv( 'PVDAK_CORE_SOURCE' );
if ( ! is_string( $core_entry ) || '' === $core_entry ) {
    $core_entry = dirname( __DIR__, 2 ) . '/packages/pvdak-core/pvdak-core.php';
}
if ( ! is_readable( $core_entry ) ) {
    fwrite( STDERR, 'POLICY_SOURCE_MISSING: ' . $core_entry . PHP_EOL );
    exit( 2 );
}
require $core_entry;
$GLOBALS['pvdak_mock_license_signature'] = pvdak_core_license_review_identity_signature( 501 );

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

pvdak_expect( pvdak_core_request_input_valid( str_repeat( 'آ', 140 ), str_repeat( 'ب', 3000 ) ),
    'Intake accepts 140/3000 Persian characters within byte limits' );
pvdak_expect( ! pvdak_core_request_input_valid( str_repeat( 'آ', 141 ), 'شرح' ),
    'Intake rejects an overlong Persian title even below its byte cap' );
pvdak_expect( ! pvdak_core_request_input_valid( 'عنوان', str_repeat( 'ب', 3001 ) ),
    'Intake rejects an overlong Persian description even below its byte cap' );
pvdak_expect( ! pvdak_core_request_input_valid( '   ', 'شرح' ),
    'Intake rejects blank titles after trimming' );
pvdak_expect( 902 === pvdak_core_request_receipt_id( array( 'type' => 'lawyer', 'id' => 902 ), 'lawyer' ),
    'Valid owner-scoped receipt retains its tracking number' );
pvdak_expect( 0 === pvdak_core_request_receipt_id( array( 'type' => 'lawyer', 'id' => 902 ), 'question' ),
    'A different request form cannot consume a lawyer receipt' );
pvdak_expect( 0 === pvdak_core_request_receipt_id( array( 'type' => 'lawyer', 'id' => 0 ), 'lawyer' ),
    'An invalid receipt cannot be shown as a successful submission' );

$pvdak_note_token = 'a5b1c1d2-e3f4-4a5b-8c6d-123456789abc';
pvdak_expect( pvdak_core_request_note_valid( str_repeat( 'آ', 1000 ) ),
    'Staff note accepts 1000 Persian characters' );
pvdak_expect( ! pvdak_core_request_note_valid( str_repeat( 'آ', 1001 ) ),
    'Staff note rejects 1001 Persian characters' );
pvdak_expect( ! pvdak_core_request_note_valid( "   " ),
    'Blank staff note is not stored' );
pvdak_expect( ! pvdak_core_request_note_valid( "\xC3\x28" ),
    'Malformed UTF-8 staff note is rejected' );
pvdak_expect( ! pvdak_core_request_note_token_seen( array(), $pvdak_note_token ),
    'New staff note submission token is accepted' );
pvdak_expect( pvdak_core_request_note_token_seen(
    array( array( 'text' => 'private', 'token' => $pvdak_note_token ) ), $pvdak_note_token ),
    'Replayed staff note token is detected' );
pvdak_expect( pvdak_core_request_note_token_seen( array(), 'invalid' ),
    'Missing or malformed note token fails closed' );

pvdak_expect( count( pvdak_core_member_types() ) === 3, 'Three professional application categories' );
pvdak_expect( pvdak_core_member_application_input_valid( 'lawyer', str_repeat( 'آ', 110 ), 'تهران', 'حقوق خانواده', true ),
    'Professional intake accepts 110 Persian name characters' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'lawyer', str_repeat( 'آ', 111 ), 'تهران', 'حقوق خانواده', true ),
    'Professional intake rejects 111 Persian name characters' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'trainee', 'نام', str_repeat( 'ب', 91 ), 'حقوق', true ),
    'Professional intake rejects overlong Persian city' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'student', 'نام', 'تهران', str_repeat( 'آ', 1601 ), true ),
    'Professional intake rejects overlong Persian interests' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'lawyer', 'نام', 'تهران', "\xC3\x28", true ),
    'Professional intake rejects malformed UTF-8' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'lawyer', 'نام', 'تهران', '   ', true ),
    'Professional intake rejects blank details' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'lawyer', 'نام', 'تهران', 'حقوق', false ),
    'Professional intake requires explicit consent' );
pvdak_expect( ! pvdak_core_member_application_input_valid( 'unverified', 'نام', 'تهران', 'حقوق', true ),
    'Professional intake rejects unknown category' );
pvdak_expect( pvdak_core_member_allowed_transitions( 'pending' ) === array( 'review', 'rejected' ),
    'Professional application cannot be approved directly from pending' );
pvdak_expect( in_array( 'approved', pvdak_core_member_allowed_transitions( 'review' ), true ),
    'Reviewed application can receive administrative approval' );
pvdak_expect( pvdak_core_member_allowed_transitions( 'approved' ) === array( 'review' ),
    'Administrative approval can only reopen to review' );
pvdak_expect( pvdak_core_member_allowed_transitions( 'rejected' ) === array( 'review' ),
    'Rejected application requires review to reopen' );
pvdak_expect( pvdak_core_member_allowed_transitions( 'unexpected' ) === array(),
    'Unknown membership state fails closed' );

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
pvdak_expect( 'ready' === pvdak_core_topic_resolution( 11 )['status'], 'Published child topic maps by Page ID' );
pvdak_expect( 'missing' === pvdak_core_topic_resolution( 0 )['status'], 'Missing topic has explicit repair state' );
pvdak_expect( 'invalid' === pvdak_core_topic_resolution( 999 )['status'], 'Unknown topic cannot be guessed from its title' );
$GLOBALS['pvdak_mock_topic_title'] = '  ';
pvdak_expect( 'untitled' === pvdak_core_topic_resolution( 11 )['status'], 'Untitled topic is flagged for repair' );
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Untitled topic cannot silently match a lawyer' );
pvdak_expect( pvdak_core_specialty_ids( 501 ) === array(), 'Untitled topic is omitted from stored specialist mappings' );
$GLOBALS['pvdak_mock_topic_title'] = 'حقوق خانواده';
$GLOBALS['pvdak_mock_topic_draft'] = true;
pvdak_expect( 'invalid' === pvdak_core_topic_resolution( 11 )['status'], 'Unpublished topic loses referral eligibility' );
pvdak_expect( count( pvdak_core_assignable_profiles( 11, 'lawyer' ) ) === 0, 'Unpublished topic has no candidate list' );
$GLOBALS['pvdak_mock_topic_draft'] = false;
pvdak_expect( pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Internally cleared lawyer matches related landing' );
pvdak_expect( pvdak_core_assignment_parties_distinct( 701, 501 ), 'Distinct real requester and lawyer owners permit internal referral' );
pvdak_expect( ! pvdak_core_assignment_parties_distinct( 702, 501 ), 'Self-referral is blocked in preview, decision and recheck' );
pvdak_expect( ! pvdak_core_assignment_parties_distinct( 703, 501 ), 'Legacy request without an owner cannot be assigned' );
pvdak_expect( ! pvdak_core_assignment_parties_distinct( 704, 501 ), 'Deleted requester account cannot receive a referral' );
$GLOBALS['pvdak_mock_profile_author'] = 0;
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Orphan professional without owner cannot be matched' );
$GLOBALS['pvdak_mock_profile_author'] = 88;
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Deleted professional account cannot be matched' );
$GLOBALS['pvdak_mock_profile_author'] = 21;
pvdak_expect( pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Valid account restores normal eligibility' );
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
$pvdak_this_month = gmdate( 'Y-m-15 12:00:00' );
$pvdak_last_month = gmdate( 'Y-m-15 12:00:00', strtotime( '-1 month' ) );
$GLOBALS['pvdak_mock_audit_request_ids'] = array( 701, 702 );
$GLOBALS['pvdak_mock_assignment_audits'] = array(
    701 => array(
        array( 'profile_id' => 501, 'at' => $pvdak_this_month, 'scope' => 'internal_only' ),
        array( 'profile_id' => 501, 'at' => $pvdak_this_month, 'scope' => 'internal_only', 'action' => 'assigned' ),
        array( 'profile_id' => 501, 'at' => $pvdak_this_month, 'scope' => 'internal_only', 'action' => 'eligibility_revoked' ),
        array( 'profile_id' => 501, 'at' => $pvdak_last_month, 'scope' => 'internal_only' ),
        array( 'profile_id' => 501, 'at' => $pvdak_this_month, 'scope' => 'external' ),
        array( 'profile_id' => 502, 'at' => $pvdak_this_month, 'scope' => 'internal_only' ),
        array( 'profile_id' => 501, 'at' => 'invalid-date', 'scope' => 'internal_only' ),
    ),
    702 => array(
        array( 'profile_id' => 501, 'at' => $pvdak_this_month, 'scope' => 'internal_only', 'action' => 'assigned' ),
    ),
);
pvdak_expect( 3 === pvdak_core_subscription_month_assignments( 501 ),
    'Quota counts successful events but not revocations, old events, foreign scope or malformed dates' );
pvdak_expect( 1 === pvdak_core_subscription_month_assignments( 502 ),
    'Monthly quota counts only the selected professional' );
$GLOBALS['wpdb']->last_error = 'synthetic query failure';
pvdak_expect( PHP_INT_MAX === pvdak_core_subscription_month_assignments( 501 ),
    'Failed audit query cannot silently reset quota usage' );
$GLOBALS['wpdb']->last_error = '';
$GLOBALS['pvdak_mock_assignment_audits'] = array();
$GLOBALS['pvdak_mock_audit_request_ids'] = array();
pvdak_expect(
    pvdak_core_assignment_lock_names( 701, 501 ) === array( 'pvdak:pro:501', 'pvdak:req:701' ),
    'Request and lawyer locks have deterministic sorted names'
);
$inside_locks = false;
$locked_result = pvdak_core_with_assignment_locks( 701, 501, function () use ( &$inside_locks ) {
    $inside_locks = count( $GLOBALS['wpdb']->locks ) === 2;
    return 'ok';
} );
pvdak_expect( 'ok' === $locked_result && $inside_locks, 'Both database locks held across decision callback' );
pvdak_expect( count( $GLOBALS['wpdb']->locks ) === 0, 'Both advisory locks released after normal decision' );
$GLOBALS['wpdb']->deny = true;
$callback_invoked = false;
$denied = pvdak_core_with_assignment_locks( 702, 501, function() use ( &$callback_invoked ) {
    $callback_invoked = true;
    return 'unsafe';
} );
pvdak_expect( 'lock_unavailable' === $denied && ! $callback_invoked, 'Lock timeout fails closed without writes' );
$GLOBALS['wpdb']->deny = false;
pvdak_expect( count( $GLOBALS['wpdb']->locks ) === 0, 'Denied acquisition does not leak advisory locks' );
pvdak_expect( 'lock_unavailable' === pvdak_core_with_assignment_locks( 0, 501, function() {} ), 'Zero request IDs cannot acquire locks' );
pvdak_expect( pvdak_core_license_review_current( 501 ), 'Time-limited staff review is valid with source, reviewer and expiry' );
$GLOBALS['pvdak_mock_profile_name'] = 'نام تغییرکرده';
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Changed professional identity invalidates an earlier review' );
$GLOBALS['pvdak_mock_profile_name'] = 'وکیل نمونه';
$GLOBALS['pvdak_mock_profile_city'] = 'شیراز';
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Changed city invalidates an earlier review' );
$GLOBALS['pvdak_mock_profile_city'] = 'تهران';
$GLOBALS['pvdak_mock_license_signature'] = '';
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Legacy review without identity signature fails closed' );
$GLOBALS['pvdak_mock_license_signature'] = pvdak_core_license_review_identity_signature( 501 );
$GLOBALS['pvdak_mock_reviewer_exists'] = false;
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Deleted reviewer invalidates an unaccountable review' );
$GLOBALS['pvdak_mock_reviewer_exists'] = true;
$GLOBALS['pvdak_mock_state'] = 'pending';
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Administrative approval withdrawal invalidates review' );
$GLOBALS['pvdak_mock_state'] = 'approved';
$GLOBALS['pvdak_mock_license_status'] = 'pending';
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Pending license review blocks internal matching' );
$GLOBALS['pvdak_mock_license_status'] = 'checked';
$GLOBALS['pvdak_mock_license_method'] = 'document_review';
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Initial document review is insufficient for referral' );
$GLOBALS['pvdak_mock_license_method'] = 'official_source';
$GLOBALS['pvdak_mock_license_expiry'] = '2020-01-01';
pvdak_expect( ! pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Expired independent review blocks referral' );
$GLOBALS['pvdak_mock_license_expiry'] = '2099-12-31';
$GLOBALS['pvdak_mock_license_reviewer'] = 0;
pvdak_expect( ! pvdak_core_license_review_current( 501 ), 'Review without accountable manager is invalid' );
$GLOBALS['pvdak_mock_license_reviewer'] = 10;
pvdak_expect( pvdak_core_profile_referral_eligible( 501, 11, 'lawyer' ), 'Eligible lawyer can be matched only after independent current review' );

pvdak_expect( pvdak_core_professional_portal_access( 501, 'lawyer', 'approved' )['ready'],
    'D21 portal readiness requires current lawyer review and available internal capacity' );
$GLOBALS['pvdak_mock_cleared'] = '0';
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'lawyer', 'approved' )['ready'],
    'D21 portal blocks missing staff clearance' );
$GLOBALS['pvdak_mock_cleared'] = '1';
$GLOBALS['pvdak_mock_license_expiry'] = '2020-01-01';
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'lawyer', 'approved' )['ready'],
    'D21 portal blocks expired independent review' );
$GLOBALS['pvdak_mock_license_expiry'] = '2099-12-31';
$GLOBALS['pvdak_mock_accepting'] = '0';
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'lawyer', 'approved' )['ready'],
    'D21 portal reflects staff-paused internal assignments' );
$GLOBALS['pvdak_mock_accepting'] = '1';
$GLOBALS['pvdak_mock_active_count'] = 2;
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'lawyer', 'approved' )['ready'],
    'D21 portal reflects exhausted capacity' );
$GLOBALS['pvdak_mock_active_count'] = 1;
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'student', 'approved' )['ready'],
    'D21 portal does not grant lawyer access to students' );
pvdak_expect( ! pvdak_core_professional_portal_access( 501, 'lawyer', 'pending' )['ready'],
    'D21 portal does not grant lawyer access before approval' );
$GLOBALS['pvdak_mock_sent_mail'] = array();
pvdak_expect( ! pvdak_core_notify_owner( 901, 'pvdak_request' ) && ! $GLOBALS['pvdak_mock_sent_mail'],
    'D24 email is off by default' );
$GLOBALS['pvdak_mock_notify_enabled'] = '1';
pvdak_expect( ! pvdak_core_notify_owner( 901, 'unknown' ), 'D24 rejects unknown record types' );
$GLOBALS['pvdak_mock_notify_status'] = 'draft';
pvdak_expect( ! pvdak_core_notify_owner( 901, 'pvdak_request' ), 'D24 rejects draft legal requests' );
$GLOBALS['pvdak_mock_notify_status'] = 'private';
$GLOBALS['pvdak_mock_notify_pending'] = '1';
pvdak_expect( ! pvdak_core_notify_owner( 901, 'pvdak_request' ), 'D24 excludes unverified email' );
$GLOBALS['pvdak_mock_notify_pending'] = '0';
pvdak_expect( pvdak_core_notify_owner( 901, 'pvdak_request' ) && count( $GLOBALS['pvdak_mock_sent_mail'] ) === 1,
    'D24 sends a generic email to the current private record owner after opt-in' );
$mail = $GLOBALS['pvdak_mock_sent_mail'][0];
pvdak_expect( false === strpos( $mail[2], '901' ) && false === strpos( $mail[2], 'حقوق خانواده' ) &&
    false === strpos( $mail[2], 'owner@example.test' ), 'D24 body excludes case ID, topic and email' );
echo "CORE_POLICY_SMOKE_OK: {$checks} checks.\n";
