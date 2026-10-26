<?php

/** Compare installed theme or Core metadata without pinning a moving patch version. */
function pvdak_test_release_at_least( $source, $minimum ) {
    return preg_match( '/^\\s*\\*?\\s*Version:[ \\t]*([0-9]+(?:[.][0-9]+)*)/mi', $source, $found ) === 1 &&
        version_compare( $found[1], $minimum, '>=' );
}
/** Ten read-only source assertions: no WordPress/database/runtime side effects. */
$root = dirname(__DIR__, 2);
$paths = array(
  'auth'=>'inc/pvdak-auth.php',
  'verify'=>'inc/pvdak-email-verification.php',
  'client'=>'packages/pvdak-core/includes/portal.php',
  'professional'=>'packages/pvdak-core/includes/professional-portal.php',
  'tickets'=>'inc/pvdak-support-tickets.php',
  'assignment'=>'packages/pvdak-core/includes/assignments.php',
  'quota'=>'packages/pvdak-core/includes/subscriptions.php',
  'info'=>'page-templates/pvdak-information.php',
  'admin'=>'inc/pvdak-readiness-admin.php',
  'theme'=>'style.css',
  'core'=>'packages/pvdak-core/pvdak-core.php',
  'registration'=>'inc/pvdak-registration.php',
);
$s = array();
foreach ($paths as $key=>$path) {
    $filename = $root.'/'.$path;
    if (!is_file($filename)) {
        fwrite(STDERR, "PVD_TEN_FAIL: missing {$path}\n");
        exit(1);
    }
    $s[$key] = file_get_contents($filename);
}
$checks = array(
  'Login attempt throttling' =>
    strpos($s['auth'],'pvdak_auth_attempt_key(')!==false &&
    strpos($s['auth'],'get_transient($key)>=6')!==false &&
    strpos($s['auth'],'delete_transient($key)')!==false,
  'Verification and staff review' =>
    strpos($s['verify'],'wp_hash_password($token)')!==false &&
    strpos($s['verify'],"add_filter('wp_authenticate_user'")!==false &&
    strpos($s['verify'],'manage_users_custom_column')!==false,
  'Own client requests and dates' =>
    strpos($s['client'],"'author' => ")!==false &&
    strpos($s['client'],'get_the_date(')!==false &&
    strpos($s['client'],'! pvdak_core_intake_open()')!==false,
  'Pending professional restricted' =>
    strpos($s['professional'],"'approved' !== " )!==false &&
    strpos($s['professional'],"'lawyer' === ")!==false,
  'Private support and closed status' =>
    strpos($s['tickets'],'pvdak_support_followup()')!==false &&
    strpos($s['tickets'],"'closed'===get_post_meta")!==false &&
    strpos($s['tickets'],"'private'!==")!==false,
  'Admin ticket columns and state' =>
    strpos($s['tickets'],'manage_pvdak_support_ticket_posts_columns')!==false &&
    strpos($s['tickets'],'pvdak_support_state')!==false,
  'Manager-only locked case matching' =>
    strpos($s['assignment'],'pvdak_core_with_assignment_locks(')!==false &&
    strpos($s['assignment'],'pvdak_assignment_preview_load')!==false,
  'Manual quota audit and pagination' =>
    strpos($s['quota'],"get_option( 'pvdak_core_subscription_gate_enabled', '0' )")!==false &&
    strpos($s['quota'],'_pvdak_subscription_audit')!==false &&
    preg_match('/while\s*\(\s*count\s*\(\s*\$ids\s*\)\s*===\s*\$batch_size\s*\)/', $s['quota'])===1,
  'Provisional policy content notice' =>
    strpos($s['info'],'_pvdak_provisional_policy')!==false &&
    strpos($s['info'],'the_content();')!==false,
  'Read-only readiness and safe versions' =>
    strpos($s['admin'],"current_user_can('manage_options')")!==false &&
    strpos($s['admin'],'get_page_by_path(')!==false &&
    pvdak_test_release_at_least($s['theme'],'0.8.29') &&
    pvdak_test_release_at_least($s['core'],'0.8.2') &&
    strpos($s['registration'],"get_option('pvdak_registration_enabled', '0')")!==false &&
    strpos($s['registration'],"function pvdak_native_registration_guard(")!==false &&
    strpos($s['registration'],"pvdak_public_template_page_url('account-login','page-templates/pvdak-login.php')")!==false,
);
$count = 0;
foreach ($checks as $label=>$passes) {
    if (!$passes) { fwrite(STDERR, "PVD_TEN_FAIL: ".$label."\n"); exit(1); }
    $count++;
}
echo "PVDAK_NEXT_TEN_SOURCE_OK: {$count}/10 static assertions (runtime not tested).\n";
