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
    strpos($s['admin'],'pvdak_filter_queue=pending&pvdak_case_order=oldest')!==false &&
    strpos($s['admin'],'pvdak_state_filter=pending')!==false &&
    strpos($s['admin'],"echo '</tbody></table>';")!==false &&
    strpos($s['admin'],'pvdak_readiness_page_classify(')!==false &&
    strpos($s['admin'],'pvdak_public_page_seeds()')!==false &&
    strpos($s['admin'],'get_page_template_slug( $post->ID )')!==false &&
    strpos($s['admin'],"'wrong_template' => 0")!==false &&
    strpos($s['admin'],"'template_unavailable' => 0")!==false &&
    strpos($s['admin'],'locate_template( $expected_template, false, false )')!==false &&
    strpos($s['admin'],"get_option( 'wp_page_for_privacy_policy', 0 )")!==false &&
    strpos($s['admin'],"admin_url( 'options-privacy.php' )")!==false &&
    strpos($s['admin'],"admin_url( 'options-reading.php' )")!==false &&
    strpos($s['admin'],'pvdak_readiness_front_page_snapshot()')!==false &&
    strpos($s['admin'],'pvdak_readiness_front_page_status_label( $front_state )')!==false &&
    strpos($s['admin'],"get_option( 'page_for_posts', 0 )")!==false &&
    strpos($s['admin'],"'posts_conflict' =>")!==false &&
    strpos($s['admin'],"'invalid_mode' =>")!==false &&
    strpos($s['admin'],"explode( '&', substr( \$link[3]")!==false &&
    strpos($s['admin'],"function_exists( 'pvdak_core_triage_filter_query' )")!==false &&
    strpos($s['admin'],"function_exists( 'pvdak_core_prof_admin_query' )")!==false &&
    pvdak_test_release_at_least($s['theme'],'0.8.94') &&
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

// Execute the real, read-only readiness helpers in an isolated PHP fixture.
// Hooks are stubbed; this does not bootstrap WordPress or read production data.
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', $root . '/' ); }
if ( ! function_exists( 'add_action' ) ) { function add_action() {} }
require_once $root . '/inc/pvdak-readiness-admin.php';
$readiness_cases = array(
    array( false, '', '', 'missing' ),
    array( true, 'draft', '', 'unpublished' ),
    array( true, 'private', '', 'unpublished' ),
    array( true, 'trash', '', 'unpublished' ),
    array( true, array(), '', 'unpublished' ),
    array( true, 'publish', '1', 'provisional' ),
    array( true, 'publish', array( '1' ), 'provisional' ),
    array( true, 'publish', 'invalid', 'provisional' ),
    array( true, 'publish', '', 'published' ),
    array( true, 'publish', '0', 'published' ),
    array( true, 'publish', false, 'published' ),
    array( true, 'publish', '', 'wrong_template', false ),
    array( true, 'publish', '1', 'wrong_template', false ),
    array( true, 'draft', '', 'unpublished', false ),
    array( true, 'publish', '', 'published', true ),
    array( false, '', '', 'missing', false ),
    array( true, 'publish', '', 'template_unavailable', true, false ),
    array( true, 'publish', '1', 'template_unavailable', true, false ),
    array( true, 'publish', '', 'template_unavailable', false, false ),
    array( true, 'draft', '', 'unpublished', true, false ),
    array( false, '', '', 'missing', true, false ),
);
foreach ( $readiness_cases as $case ) {
    $template_matches = array_key_exists( 4, $case ) ? $case[4] : true;
    $template_available = array_key_exists( 5, $case ) ? $case[5] : true;
    if ( pvdak_readiness_page_classify( $case[0], $case[1], $case[2], $template_matches, $template_available ) !== $case[3] ) {
        fwrite( STDERR, "PVD_READINESS_FAIL: " . $case[3] . "\n" );
        exit( 1 );
    }
}
if ( 'منتشرنشده — پیش‌نویس' !== pvdak_readiness_page_status_label( 'unpublished', 'draft' ) ||
     'منتشرنشده — وضعیت نامعتبر یا نامشخص' !== pvdak_readiness_page_status_label( 'unpublished', array() ) ||
     'منتشرشده — قالب صفحه نادرست است؛ مسیر ممکن است کار نکند' !==
         pvdak_readiness_page_status_label( 'wrong_template', 'publish' ) ||
     'منتشرشده — فایل یا تعریف قالب لازم در نصب فعلی موجود نیست؛ نیازمند بررسی فنی' !==
         pvdak_readiness_page_status_label( 'template_unavailable', 'publish' ) ) {
    fwrite( STDERR, "PVD_READINESS_FAIL: status labels\n" );
    exit( 1 );
}
echo "PVDAK_READINESS_HELPERS_OK: 25/25 isolated PHP cases (not WordPress E2E).\n";
$privacy_cases = array(
    array( 0, false, 0, 'missing' ),
    array( 12, false, 12, 'missing' ),
    array( 12, true, 0, 'unset' ),
    array( 12, true, '0', 'unset' ),
    array( 12, true, '', 'unset' ),
    array( 12, true, 13, 'mismatch' ),
    array( 12, true, array( 12 ), 'mismatch' ),
    array( 12, true, 12, 'matched' ),
    array( 12, true, '12', 'matched' ),
);
foreach ( $privacy_cases as $case ) {
    if ( pvdak_readiness_privacy_setting_state( $case[0], $case[1], $case[2] ) !== $case[3] ) {
        fwrite( STDERR, "PVD_READINESS_FAIL: WordPress privacy Page selection\n" );
        exit( 1 );
    }
}
echo "PVDAK_READINESS_PRIVACY_OK: 9/9 isolated PHP cases (not WordPress E2E).\n";

$front_cases = array(
    array( 'page', 12, 'page', 'publish', 'ready' ),
    array( 'page', '12', 'page', 'publish', 'ready' ),
    array( 'page', 0, false, false, 'missing' ),
    array( 'page', '0', false, false, 'missing' ),
    array( 'page', '', false, false, 'missing' ),
    array( 'page', array( -1 ), false, false, 'missing' ),
    array( 'page', -12, false, false, 'missing' ),
    array( 'page', '12abc', false, false, 'missing' ),
    array( 'page', '999999999999999999999999999', false, false, 'missing' ),
    array( 'page', '012', 'page', 'publish', 'missing' ),
    array( 'page', 12, 'post', 'publish', 'invalid' ),
    array( 'page', 12, false, false, 'invalid' ),
    array( 'page', 12, 'page', 'draft', 'unpublished' ),
    array( 'page', 12, 'page', 'private', 'unpublished' ),
    array( 'posts', 12, 'page', 'publish', 'posts_mode' ),
    array( 'invalid', 12, 'page', 'publish', 'invalid_mode' ),
    array( '', 12, 'page', 'publish', 'invalid_mode' ),
    array( array( 'page' ), 12, 'page', 'publish', 'invalid_mode' ),
    array( 'page', 12, 'page', 'publish', 'posts_conflict', 12 ),
    array( 'page', '12', 'page', 'publish', 'posts_conflict', '12' ),
    array( 'page', 12, 'page', 'publish', 'ready', '13' ),
    array( 'page', 12, 'page', 'draft', 'unpublished', 12 ),
    array( 'page', 12, 'post', 'publish', 'invalid', 12 ),
    array( 'page', 12, 'page', 'publish', 'ready', array( 12 ) ),
);
foreach ( $front_cases as $case ) {
    if ( pvdak_readiness_front_page_state( $case[0], $case[1], $case[2], $case[3],
        array_key_exists( 5, $case ) ? $case[5] : 0 ) !== $case[4] ) {
        fwrite( STDERR, "PVD_READINESS_FAIL: WordPress static front Page state\n" );
        exit( 1 );
    }
}
foreach ( array( 'ready', 'posts_mode', 'invalid_mode', 'posts_conflict', 'missing', 'invalid', 'unpublished' ) as $state ) {
    if ( '' === pvdak_readiness_front_page_status_label( $state ) ||
         'وضعیت تنظیم صفحه اصلی نامشخص است' === pvdak_readiness_front_page_status_label( $state ) ) {
        fwrite( STDERR, "PVD_READINESS_FAIL: front Page status label\n" );
        exit( 1 );
    }
}
echo "PVDAK_READINESS_FRONT_PAGE_OK: 32/32 isolated PHP cases (not WordPress E2E).\n";
