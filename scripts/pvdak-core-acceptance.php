<?php
/**
 * Read-only acceptance gate for an ACTIVE PVDak Core installation.
 *
 * Usage: wp eval-file /path/to/theme/scripts/pvdak-core-acceptance.php --path=/path/to/wordpress
 * Does not create users, roles, pages, posts, notices or modify any option.
 * It intentionally prints no user, request, legal case, or license information.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "PVDak Core acceptance requires WP-CLI eval-file.\n" );
}

$failures = 0;
function pvdak_core_acceptance_check( $label, $result ) {
    global $failures;
    if ( ! $result ) { $failures++; }
    WP_CLI::line( ( $result ? 'PASS ' : 'FAIL ' ) . $label );
}

$loaded = defined( 'PVDAK_CORE_VERSION' ) &&
    function_exists( 'pvdak_core_register_post_types' );
pvdak_core_acceptance_check( 'PVDak Core plugin loaded', $loaded );
if ( ! $loaded ) {
    WP_CLI::error( 'Core is not active. Only an inactive source copy or theme package may be present.' );
}
pvdak_core_acceptance_check( 'Core version at least 0.8.0',
    version_compare( (string) PVDAK_CORE_VERSION, '0.8.0', '>=' ) );

$expected = WP_PLUGIN_DIR . '/pvdak-core';
$theme_copy = get_template_directory() . '/packages/pvdak-core';
$files = array(
    'pvdak-core.php',
    'includes/requests.php',
    'includes/professionals.php',
    'includes/admin.php',
    'includes/portal.php',
    'includes/triage.php',
    'includes/specialties.php',
    'includes/license-review.php',
    'includes/capacity.php',
    'includes/professional-portal.php',
    'includes/assignment-locks.php',
    'includes/assignments.php',
    'assets/forms.css',
);
$source_ready = is_dir( $theme_copy );
pvdak_core_acceptance_check( 'Core release source bundled with current theme', $source_ready );
pvdak_core_acceptance_check( 'Standalone Core installation directory is present', is_dir( $expected ) );
if ( $source_ready && is_dir( $expected ) ) {
    $matching = true;
    foreach ( $files as $name ) {
        $a = $theme_copy . '/' . $name;
        $b = $expected . '/' . $name;
        if ( ! is_file( $a ) || ! is_file( $b ) || hash_file( 'sha256', $a ) !== hash_file( 'sha256', $b ) ) {
            $matching = false;
            break;
        }
    }
    pvdak_core_acceptance_check( 'Installed Core files match theme-bundled source', $matching );
}

pvdak_core_acceptance_check( 'Legal case intake remains disabled',
    function_exists( 'pvdak_core_intake_open' ) && ! pvdak_core_intake_open() );
pvdak_core_acceptance_check( 'Professional onboarding remains disabled',
    function_exists( 'pvdak_core_member_intake_open' ) && ! pvdak_core_member_intake_open() );

$required_shortcodes = array(
    'pvdak_request_form', 'pvdak_my_requests', 'pvdak_account_dashboard',
    'pvdak_professional_application', 'pvdak_professional_dashboard',
);
foreach ( $required_shortcodes as $shortcode ) {
    pvdak_core_acceptance_check( 'Shortcode registered: ' . $shortcode, shortcode_exists( $shortcode ) );
}

foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type_name ) {
    $type = get_post_type_object( $type_name );
    pvdak_core_acceptance_check( 'Private, REST-hidden data model: ' . $type_name,
        $type && ! $type->public && ! $type->publicly_queryable &&
        ! $type->show_in_rest && ! $type->has_archive && $type->exclude_from_search );
}

$privileged_caps = array(
    'edit_pvdak_requests',
    'edit_others_pvdak_requests',
    'read_private_pvdak_requests',
    'delete_pvdak_requests',
    'edit_pvdak_professionals',
    'edit_others_pvdak_professionals',
    'read_private_pvdak_professionals',
    'delete_pvdak_professionals',
);
$admin = get_role( 'administrator' );
pvdak_core_acceptance_check( 'Administrator has explicit case management permissions',
    $admin && $admin->has_cap( 'edit_pvdak_requests' ) &&
    $admin->has_cap( 'edit_pvdak_professionals' ) );
foreach ( array( 'subscriber', 'pvdak_client', 'pvdak_lawyer', 'pvdak_trainee', 'pvdak_student', 'editor', 'author', 'contributor' ) as $role_name ) {
    $role = get_role( $role_name );
    if ( ! $role ) { continue; }
    $unsafe = false;
    foreach ( $privileged_caps as $cap ) {
        if ( $role->has_cap( $cap ) ) { $unsafe = true; break; }
    }
    pvdak_core_acceptance_check( 'Role cannot administer private records: ' . $role_name, ! $unsafe );
}

pvdak_core_acceptance_check( 'Referral locks and capacity functions loaded',
    function_exists( 'pvdak_core_with_assignment_locks' ) &&
    function_exists( 'pvdak_core_capacity_available' ) );
pvdak_core_acceptance_check( 'Professional license review gate loaded',
    function_exists( 'pvdak_core_license_review_current' ) );
pvdak_core_acceptance_check( 'Referral UI action is registered',
    has_action( 'admin_post_pvdak_core_assign', 'pvdak_core_assign_request' ) !== false );

WP_CLI::line( 'PVDAK_CORE_ACCEPTANCE: failures=' . $failures . ' (read-only; no real case data displayed)' );
if ( $failures ) {
    WP_CLI::error( 'Core acceptance checks failed. Keep both intake switches disabled.' );
}
WP_CLI::success( 'Core baseline accepted in WordPress. End-to-end role and concurrent assignment tests still required.' );
