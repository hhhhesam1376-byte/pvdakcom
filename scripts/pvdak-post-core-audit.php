<?php
/** Read-only PVDak Core 0.8.10 post-install acceptance. wp eval-file only. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    exit("Run using wp eval-file in the installed WordPress site.\n");
}
$GLOBALS['pvdak_audit'] = array('pass'=>0,'fail'=>0,'warn'=>0);
function pvdak_audit_check($label,$result,$required=true) {
    $state=$result?'pass':($required?'fail':'warn');
    $GLOBALS['pvdak_audit'][$state]++;
    WP_CLI::line(strtoupper($state).' | '.$label);
}
$theme=wp_get_theme();
pvdak_audit_check('Active PVDak theme at least 0.8.38',
    'pvdakcom-main'===$theme->get_stylesheet() &&
    version_compare($theme->get('Version'),'0.8.38','>='));
pvdak_audit_check('Active Core version 0.8.10',
    defined('PVDAK_CORE_VERSION') && PVDAK_CORE_VERSION==='0.8.10' &&
    in_array('pvdak-core/pvdak-core.php',(array)get_option('active_plugins',array()),true));
pvdak_audit_check('Maintenance ON',function_exists('pvdak_maintenance_enabled') &&
    pvdak_maintenance_enabled());
pvdak_audit_check('WordPress registration OFF',!(bool)get_option('users_can_register',false));
pvdak_audit_check('PVDak registration OFF',function_exists('pvdak_registration_open') &&
    !pvdak_registration_open());
pvdak_audit_check('Legal case intake OFF',function_exists('pvdak_core_intake_open') &&
    !pvdak_core_intake_open());
pvdak_audit_check('Professional intake OFF',function_exists('pvdak_core_member_intake_open') &&
    !pvdak_core_member_intake_open());
pvdak_audit_check('Subscription gate OFF',function_exists('pvdak_core_subscription_gate_on') &&
    !pvdak_core_subscription_gate_on());
foreach(array('pvdak_request','pvdak_professional','pvdak_support_ticket') as $slug){
    $type=get_post_type_object($slug);
    pvdak_audit_check('Private REST-hidden model '.$slug, $type &&
        !$type->public && !$type->publicly_queryable && !$type->show_in_rest &&
        !$type->has_archive && $type->exclude_from_search);
}
foreach(array('subscriber','pvdak_client','pvdak_lawyer','pvdak_trainee',
              'pvdak_student','author','editor') as $slug){
    $role=get_role($slug);
    if(!$role){pvdak_audit_check('Optional role exists '.$slug,false,false);continue;}
    $unsafe=false;
    foreach(array('edit_pvdak_requests','delete_pvdak_requests','edit_pvdak_professionals',
                  'delete_pvdak_professionals') as $cap){
        if($role->has_cap($cap))$unsafe=true;
    }
    pvdak_audit_check('No private case edit permission '.$slug,!$unsafe);
}
$admin=get_role('administrator');
pvdak_audit_check('Administrator can triage requests',
    $admin && $admin->has_cap('edit_pvdak_requests'));
pvdak_audit_check('Account auth, password reset and email verification loaded',
    function_exists('pvdak_login_handle') && function_exists('pvdak_recovery_request') &&
    function_exists('pvdak_email_verification_auth_gate'));
pvdak_audit_check('Private support thread loaded',
    function_exists('pvdak_support_followup') && function_exists('pvdak_support_append_message'));
pvdak_audit_check('Assignment locks/capacity/quota loaded',
    function_exists('pvdak_core_with_assignment_locks') &&
    function_exists('pvdak_core_capacity_available') &&
    function_exists('pvdak_core_subscription_allows_new_assignment'));
pvdak_audit_check('Database read available',
    isset($GLOBALS['wpdb']) && '1'===(string)$GLOBALS['wpdb']->get_var('SELECT 1'));
WP_CLI::line('PAGE_BOOTSTRAP_MARKER: ' . (string)get_option('_pvdak_public_pages_bootstrap','NONE'));
$expected = function_exists('pvdak_public_page_seeds') ? array_keys(pvdak_public_page_seeds()) :
    array('about-us','contact-us','legal-faq','privacy-policy','terms-of-use',
          'account-login','account-recovery','account-profile','my-pvdak-account');
foreach ($expected as $slug) {
    $page=get_page_by_path($slug,OBJECT,'page');
    $state=$page?$page->post_status:'missing';
    pvdak_audit_check('Page published '.$slug.' ('.$state.')',
        $page && $page->post_status==='publish',false);
}
$count=$GLOBALS['pvdak_audit'];
WP_CLI::line(sprintf('PVD_POST_CORE_AUDIT_SUMMARY pass=%d fail=%d warn=%d read_only=yes',
    $count['pass'],$count['fail'],$count['warn']));
if($count['fail'])WP_CLI::error('Acceptance failed; keep public intake disabled.');
WP_CLI::success('Configuration checks passed. Mail/browser/concurrency tests are separate.');
