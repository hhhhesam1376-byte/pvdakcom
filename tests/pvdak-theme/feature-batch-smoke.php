<?php

/** Compare installed theme or Core metadata without pinning a moving patch version. */
function pvdak_test_release_at_least( $source, $minimum ) {
    return preg_match( '/^\\s*\\*?\\s*Version:[ \\t]*([0-9]+(?:[.][0-9]+)*)/mi', $source, $found ) === 1 &&
        version_compare( $found[1], $minimum, '>=' );
}
/**
 * Read-only static acceptance assertions for feature batch 0.8.28.
 * Does not connect to WordPress or create/delete real users, cases or tickets.
 */
$root = dirname(__DIR__, 2);
$files = array(
    'front' => 'front-page.php',
    'css' => 'style.css',
    'login' => 'inc/pvdak-auth.php',
    'tools' => 'inc/pvdak-account-tools.php',
    'register' => 'inc/pvdak-registration.php',
    'tickets' => 'inc/pvdak-support-tickets.php',
    'portal' => 'page-templates/pvdak-account.php',
    'profile' => 'page-templates/pvdak-profile.php',
    'recovery' => 'page-templates/pvdak-recovery.php',
    'registration_page' => 'page-templates/pvdak-register.php',
    'page_bootstrap' => 'inc/pvdak-public-pages.php',
    'core' => 'packages/pvdak-core/pvdak-core.php',
    'core_capacity' => 'packages/pvdak-core/includes/capacity.php',
    'core_subscription' => 'packages/pvdak-core/includes/subscriptions.php',
    'core_match' => 'packages/pvdak-core/includes/assignments.php',
    'core_prof' => 'packages/pvdak-core/includes/professional-portal.php',
);
$data = array();
foreach($files as $name => $path) {
    $full=$root.'/'.$path;
    if(!is_readable($full)) {fwrite(STDERR,"BATCH_FAIL: missing ".$path."\n");exit(1);}
    $data[$name]=file_get_contents($full);
}
$checks = array(
    'Simplified homepage entry is 3 explicit links' =>
        false !== strpos($data['front'],'می‌خواهید چه کاری انجام دهید؟') &&
        false !== strpos($data['front'],'pvdak-start-here-grid') &&
        false !== strpos($data['front'],'pvdak_home_start_copy(') &&
        false !== strpos($data['front'],'pvdak_auth_login_url()') &&
        false === strpos($data['front'],'از کدام مسیر وارد می‌شوید؟'),
    'Auth is based on WordPress credentials, no parallel login database' =>
        false !== strpos($data['login'],'wp_signon(') && false !== strpos($data['login'],'check_admin_referer('),
    'Recovery validates expiring WordPress token and never emails the password' =>
        false !== strpos($data['tools'],'retrieve_password(') &&
        false !== strpos($data['tools'],'check_password_reset_key(') &&
        false !== strpos($data['tools'],'reset_password(') &&
        false !== strpos($data['tools'],'get_transient('),
    'Profile edit uses logged-in ID and nonce' =>
        false !== strpos($data['tools'],'wp_update_user(') &&
        false !== strpos($data['tools'],"get_current_user_id()") &&
        false !== strpos($data['portal'],"pvdak_profile_save"),
    'Regular requester dashboard delegates to private Core portal' =>
        false !== strpos($data['portal'],"pvdak_account_dashboard"),
    'Professional dashboard only shown for own application' =>
        false !== strpos($data['portal'],"pvdak_core_existing_application(") &&
        false !== strpos($data['core_prof'],"_pvdak_member_state"),
    'Registration hard OFF during maintenance and without admin opt in' =>
        false !== strpos($data['register'],"pvdak_maintenance_enabled()") &&
        false !== strpos($data['register'],"get_option('pvdak_registration_enabled', '0')") &&
        false !== strpos($data['register'],"get_option('users_can_register', false)") &&
        false !== strpos($data['register'],"'role'=>$role") &&
        false !== strpos($data['register'],"'_pvdak_email_verification_pending'"),
    'Tickets private; user sees only own submitted tickets' =>
        false !== strpos($data['tickets'],"'public'=>false") &&
        false !== strpos($data['tickets'],"'author'=>$user") &&
        false !== strpos($data['tickets'],"current_user_can('manage_options')") &&
        false !== strpos($data['tickets'],"check_admin_referer("),
    'Manual matching protects assignment with locks and confirmed capacity' =>
        false !== strpos($data['core_match'],'pvdak_core_with_assignment_locks') &&
        false !== strpos($data['core_match'],'pvdak_core_profile_referral_eligible') &&
        false !== strpos($data['core_match'],'pvdak_core_capacity_available') &&
        false !== strpos($data['core_match'],'usort( $eligible'),
    'Plan/quota enforce only when explicit manager gate is enabled' =>
        false !== strpos($data['core_subscription'],"get_option( 'pvdak_core_subscription_gate_enabled', '0' )") &&
        false !== strpos($data['core_capacity'],"pvdak_core_subscription_allows_new_assignment") &&
        false !== strpos($data['core_subscription'],"'active' !== get_post_meta") &&
        false !== strpos($data['core_subscription'],"pvdak_core_subscription_month_assignments"),
    'Theme and Core versioned in one proposed review batch' =>
        pvdak_test_release_at_least($data['css'], '0.8.30') &&
        pvdak_test_release_at_least($data['core'], '0.8.2') &&
        false !== strpos($data['core'],"includes/subscriptions.php"),
    'Private account screens never cached/indexed' =>
        false !== strpos($data['tools'],'nocache_headers()') &&
        false !== strpos($data['tools'],'X-Robots-Tag: noindex, nofollow'),
);
$count=0;
foreach ($checks as $message=>$passes) {
    if (!$passes) {fwrite(STDERR,"BATCH_FAIL: ".$message."\n");exit(1);}
    $count++;
}
echo "PVDAK_BATCH_SOURCE_OK: ".$count." static checks. WordPress and production are NOT tested.\n";
