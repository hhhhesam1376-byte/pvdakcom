<?php
/**
 * PHP-only safety policy smoke tests for PVDak Core.
 * Does NOT bootstrap WordPress, access a database or change production data.
 *
 * Run: php tests/pvdak-core/policy-smoke.php
 */
define( 'ABSPATH', __DIR__ . '/' );
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function get_option( $name, $default = false ) { return $default; }

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
echo "CORE_POLICY_SMOKE_OK: {$checks} checks.\n";
