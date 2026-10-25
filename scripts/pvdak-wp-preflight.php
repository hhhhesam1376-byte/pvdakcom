<?php
/**
 * Read-only acceptance checks inside a real WordPress install.
 *
 * Run:
 * wp eval-file /path/to/theme/scripts/pvdak-wp-preflight.php --path=/path/to/site
 *
 * No user details, case content or personal identifiers are printed.
 * Works when Core is inactive; that state is reported as NOT TESTED.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( "This file must run through WP-CLI eval-file.\n" );
}

// WP-CLI eval-file includes this PHP file from a command callback scope.
// Store counts explicitly in $GLOBALS: local initializers + "global" inside
// pvdak_preflight_check used separate variables and falsely reported 0/0.
$GLOBALS['pvdak_preflight_checks'] = 0;
$GLOBALS['pvdak_preflight_failures'] = 0;
$GLOBALS['pvdak_preflight_warnings'] = 0;
function pvdak_preflight_check( $label, $condition, $mandatory = true ) {
    $GLOBALS['pvdak_preflight_checks']++;
    $status = $condition ? 'PASS' : ( $mandatory ? 'FAIL' : 'WARN' );
    if ( ! $condition ) {
        if ( $mandatory ) { $GLOBALS['pvdak_preflight_failures']++; }
        else { $GLOBALS['pvdak_preflight_warnings']++; }
    }
    WP_CLI::line( sprintf( '%-5s %s', $status, $label ) );
}

$theme = wp_get_theme();
pvdak_preflight_check( 'Theme is pvdakcom-main', 'pvdakcom-main' === $theme->get_stylesheet() );
pvdak_preflight_check( 'Theme version is 0.8.8 or newer', version_compare( (string) $theme->get( 'Version' ), '0.8.8', '>=' ) );
pvdak_preflight_check( 'Required Persian RTL stylesheet exists', is_readable( get_template_directory() . '/rtl.css' ) );
pvdak_preflight_check( 'WordPress site language is Persian (fa_IR)', 'fa_IR' === (string) get_option( 'WPLANG', '' ), false );
pvdak_preflight_check( 'ACF is loaded', function_exists( 'acf_add_local_field_group' ), false );
$front = (int) get_option( 'page_on_front' );
pvdak_preflight_check( 'Published static homepage configured',
    'page' === get_option( 'show_on_front' ) && $front && 'page' === get_post_type( $front ) &&
    'publish' === get_post_status( $front ), false );
pvdak_preflight_check( 'Legal landing helpers loaded', function_exists( 'pvdak_legal_topic_pages' ) );
pvdak_preflight_check( 'Server-side topic search available',
    function_exists( 'pvdak_legal_filter_topic_pages' ) &&
    function_exists( 'pvdak_legal_topic_search_term' ) );
pvdak_preflight_check( 'Native editorial Category archive available',
    is_readable( get_template_directory() . '/category.php' ) &&
    is_readable( get_template_directory() . '/assets/css/pvdak-editorial.css' ) );

if ( function_exists( 'pvdak_legal_topic_pages' ) ) {
    $parent = function_exists( 'pvdak_legal_topics_root_id' ) ? pvdak_legal_topics_root_id() : 0;
    pvdak_preflight_check( 'Legal topics parent configured', $parent > 0, false );
    if ( $parent ) {
        $pages = pvdak_legal_topic_pages( $parent );
        pvdak_preflight_check( 'At least one published legal landing exists', count( $pages ) > 0, false );
    }
}

// Read-only launch gates and conditional feature checks (no user IDs or case data).
pvdak_preflight_check( 'Branded registration uses verification and native signup guard',
    function_exists('pvdak_registration_open') &&
    function_exists('pvdak_email_verification_auth_gate') &&
    function_exists('pvdak_native_registration_guard') );
pvdak_preflight_check( 'New-account verification hooks loaded',
    function_exists('pvdak_email_verification_issue') &&
    function_exists('pvdak_email_verification_auth_gate') );
pvdak_preflight_check( 'Private support thread handler present',
    function_exists('pvdak_support_followup') && function_exists('pvdak_support_append_message') );
pvdak_preflight_check( 'No public WordPress self-registration enabled yet',
    ! (bool) get_option('users_can_register', false), false );
pvdak_preflight_check( 'Maintenance switch available in WordPress General Settings',
    function_exists('pvdak_maintenance_enabled') );
WP_CLI::line( 'MAINTENANCE_MODE_RUNTIME: ' .
    ( function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled() ? 'ON' : 'OFF' ) );
// A missing Page needs a real administrator dashboard visit; WP-CLI does
// not trigger admin_init. Trashed/draft Pages are preserved intentionally.
WP_CLI::line('PAGE_BOOTSTRAP_MARKER: ' . (string)get_option('_pvdak_public_pages_bootstrap','NONE'));
$expected = function_exists('pvdak_public_page_seeds')
    ? array_keys(pvdak_public_page_seeds()) :
    array('account-login','account-recovery','account-profile','my-pvdak-account',
          'contact-us','privacy-policy','terms-of-use');
foreach ($expected as $slug) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    $state = $page ? $page->post_status : 'missing';
    pvdak_preflight_check('Published Page '.$slug.' ('.$state.')',
        $page && 'publish' === $page->post_status, false);
}
WP_CLI::line('PAGE_REPAIR_ACTION: Sign in as administrator at /wp-admin/ once;'
    . ' this safely creates only missing Pages. Re-run read-only preflight.');
WP_CLI::line('THEME_VERSION: '.(string) $theme->get('Version'));
WP_CLI::line('CORE_SOURCE_BUNDLED: '.(
    is_readable(get_template_directory().'/packages/pvdak-core/pvdak-core.php')?'PRESENT':'MISSING'));
WP_CLI::line('CORE_INSTALLED_VERSION: '.(defined('PVDAK_CORE_VERSION') ? PVDAK_CORE_VERSION : 'NOT_ACTIVE'));

$core = function_exists( 'pvdak_core_register_post_types' );
WP_CLI::line( 'CORE: ' . ( $core ? 'ACTIVE - inspect below' : 'INACTIVE - Core tests SKIPPED' ) );
if ( $core ) {
    pvdak_preflight_check( 'Manual subscription gate remains disabled',
        ! function_exists('pvdak_core_subscription_gate_on') || ! pvdak_core_subscription_gate_on() );
    pvdak_preflight_check( 'Core default legal intake disabled until approved',
        ! function_exists( 'pvdak_core_intake_open' ) || ! pvdak_core_intake_open() );
    pvdak_preflight_check( 'Core default professional intake disabled until approved',
        ! function_exists( 'pvdak_core_member_intake_open' ) || ! pvdak_core_member_intake_open() );
    foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $slug ) {
        $type = get_post_type_object( $slug );
        pvdak_preflight_check( 'Private and REST-hidden content model ' . $slug,
            $type && ! $type->public && ! $type->publicly_queryable &&
            ! $type->show_in_rest && ! $type->has_archive && $type->exclude_from_search );
    }
    foreach ( array( 'subscriber', 'pvdak_client', 'pvdak_lawyer', 'pvdak_trainee', 'pvdak_student', 'author', 'editor' ) as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) { continue; }
        $unsafe = false;
        foreach ( array( 'edit_pvdak_requests', 'delete_pvdak_requests', 'edit_pvdak_professionals', 'delete_pvdak_professionals' ) as $cap ) {
            if ( $role->has_cap( $cap ) ) { $unsafe = true; }
        }
        pvdak_preflight_check( 'No case admin capability: ' . $role_name, ! $unsafe );
    }
    $admin = get_role( 'administrator' );
    pvdak_preflight_check( 'Administrator can triage private requests',
        $admin && $admin->has_cap( 'edit_pvdak_requests' ) );
    pvdak_preflight_check( 'Advisory lock module is loaded',
        function_exists( 'pvdak_core_with_assignment_locks' ) );
}

$checks = $GLOBALS['pvdak_preflight_checks'];
$fail = $GLOBALS['pvdak_preflight_failures'];
$warn = $GLOBALS['pvdak_preflight_warnings'];
WP_CLI::line( sprintf( 'PREFLIGHT_SUMMARY: checks=%d fails=%d warnings=%d (read-only)', $checks, $fail, $warn ) );
if ( $checks < 9 || $fail ) {
    WP_CLI::error( 'Read-only preflight failed; leave real data intake disabled.' );
}
WP_CLI::success( 'Read-only preflight completed. Human/staging E2E checks still required.' );
