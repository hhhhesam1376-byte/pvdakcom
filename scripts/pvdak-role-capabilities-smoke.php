<?php
/**
 * PVDak read-only WordPress permission audit for the five non-admin account roles.
 * Executes inside WP-CLI; reads installed role capabilities and private CPT flags.
 * Does not create users, alter role definitions, access case data or send requests.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "This audit requires WP-CLI eval-file.\n" );
}
$GLOBALS['pvdak_role_audit_count'] = 0;
$GLOBALS['pvdak_role_audit_failures'] = 0;
function pvdak_role_audit_check( $label, $ok ) {
    $GLOBALS['pvdak_role_audit_count']++;
    if ( ! $ok ) { $GLOBALS['pvdak_role_audit_failures']++; }
    WP_CLI::line( ( $ok ? 'PASS ' : 'FAIL ' ) . $label );
}
pvdak_role_audit_check( 'Core loaded', function_exists( 'pvdak_core_register_post_types' ) );
pvdak_role_audit_check( 'Public maintenance enabled',
    function_exists( 'pvdak_maintenance_enabled' ) && pvdak_maintenance_enabled() );
pvdak_role_audit_check( 'Legal intake disabled',
    function_exists( 'pvdak_core_intake_open' ) && ! pvdak_core_intake_open() );
pvdak_role_audit_check( 'Member intake disabled',
    function_exists( 'pvdak_core_member_intake_open' ) && ! pvdak_core_member_intake_open() );

foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $slug ) {
    $type = get_post_type_object( $slug );
    pvdak_role_audit_check( 'Private record type: ' . $slug,
        $type && ! $type->public && ! $type->publicly_queryable &&
        ! $type->show_in_rest && $type->exclude_from_search );
}
$admin = get_role( 'administrator' );
pvdak_role_audit_check( 'Administrator can review legal requests',
    $admin && $admin->has_cap( 'edit_pvdak_requests' ) );
pvdak_role_audit_check( 'Administrator can review professional applications',
    $admin && $admin->has_cap( 'edit_pvdak_professionals' ) );

$roles = array( 'subscriber', 'pvdak_client', 'pvdak_lawyer', 'pvdak_trainee', 'pvdak_student' );
$restricted = array(
    'manage_options',
    'edit_posts',
    'edit_pvdak_requests',
    'edit_others_pvdak_requests',
    'read_private_pvdak_requests',
    'delete_pvdak_requests',
    'edit_pvdak_professionals',
    'edit_others_pvdak_professionals',
    'read_private_pvdak_professionals',
    'delete_pvdak_professionals',
);
foreach ( $roles as $slug ) {
    $role = get_role( $slug );
    pvdak_role_audit_check( 'Account role registered: ' . $slug, (bool) $role );
    pvdak_role_audit_check( 'Account role can log in: ' . $slug, $role && $role->has_cap( 'read' ) );
    foreach ( $restricted as $cap ) {
        pvdak_role_audit_check( 'Role ' . $slug . ' cannot use ' . $cap,
            $role && ! $role->has_cap( $cap ) );
    }
}
$checks = $GLOBALS['pvdak_role_audit_count'];
$failures = $GLOBALS['pvdak_role_audit_failures'];
$expected = 68; // 4 switches + 2 private types + 2 admin + 5 * (role + read + 10 restricted).
WP_CLI::line( sprintf(
    'PVDAK_ROLE_CAPABILITIES: checks=%d failures=%d expected=%d (read-only role registry)',
    $checks, $failures, $expected
) );
if ( $checks !== $expected || $failures ) {
    WP_CLI::error( 'Role capability audit did not pass. Keep intake disabled.' );
}
WP_CLI::success( 'Role permissions baseline verified; multi-account access tests remain pending.' );
