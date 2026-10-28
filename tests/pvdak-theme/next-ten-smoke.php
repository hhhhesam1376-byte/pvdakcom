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
  'public'=>'inc/pvdak-public-pages.php',
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
    pvdak_test_release_at_least($s['theme'],'0.8.95') &&
    pvdak_test_release_at_least($s['core'],'0.8.2') &&
    strpos($s['registration'],"get_option('pvdak_registration_enabled', '0')")!==false &&
    strpos($s['registration'],"function pvdak_native_registration_guard(")!==false &&
    strpos($s['registration'],"pvdak_public_template_page_url('account-login','page-templates/pvdak-login.php')")!==false &&
    strpos($s['public'],'! locate_template( $template, false, false )')!==false &&
    strpos($s['admin'],"'account-register'=>'ثبت‌نام (برگه قدیمی)'")!==false &&
    strpos($s['admin'],'pvdak_readiness_required_totals( $audits )')!==false &&
    strpos($s['admin'],"'optional' => isset( $optional_pages[ $slug ] )")!==false &&
    strpos($s['admin'],"$"+"optional_pages = array(")!==false &&
    strpos($s['admin'],"remove_menu_page('edit-comments.php')")===false &&
    strpos($s['admin'],'pvdak-readiness-technical')!==false &&
    strpos($s['admin'],'بازگشت به مدیریت روزمره')!==false &&
    strpos($s['admin'],'$technical_facts = array(')!==false,
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
// Count-only moderation mocks: never load comment authors, bodies or private cases.
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $cap ) {
        return ( 'moderate_comments' === $cap && ! empty( $GLOBALS['pvdak_mock_moderate'] ) ) ||
            ( 'edit_pages' === $cap && ! empty( $GLOBALS['pvdak_mock_edit_pages'] ) );
    }
}
if ( ! function_exists( 'wp_count_comments' ) ) {
    function wp_count_comments() { return $GLOBALS['pvdak_mock_comment_counts'] ?? null; }
}
require_once $root . '/inc/pvdak-readiness-admin.php';
$comment_cases = array(
    array( true, (object) array( 'moderated' => 0 ), 0 ),
    array( true, (object) array( 'moderated' => '0' ), 0 ),
    array( true, (object) array( 'moderated' => 12 ), 12 ),
    array( true, (object) array( 'moderated' => '12' ), 12 ),
    array( true, (object) array( 'moderated' => -12 ), 0 ),
    array( true, (object) array( 'moderated' => '-12' ), 0 ),
    array( true, (object) array( 'moderated' => '12junk' ), 0 ),
    array( true, (object) array( 'moderated' => array( 12 ) ), 0 ),
    array( true, (object) array( 'moderated' => 12.2 ), 0 ),
    array( true, (object) array( 'moderated' => '012' ), 0 ),
    array( true, (object) array( 'moderated' => '2147483648' ), 0 ),
    array( true, (object) array(), 0 ),
    array( true, array(), 0 ),
    array( false, (object) array( 'moderated' => 5 ), 0 ),
);
foreach ( $comment_cases as $case ) {
    $GLOBALS['pvdak_mock_moderate'] = $case[0];
    $GLOBALS['pvdak_mock_comment_counts'] = $case[1];
    if ( pvdak_management_pending_comments() !== $case[2] ) {
        fwrite( STDERR, "PVD_MANAGEMENT_FAIL: malformed or unauthorized comment count\n" );
        exit( 1 );
    }
}
unset( $GLOBALS['pvdak_mock_moderate'], $GLOBALS['pvdak_mock_comment_counts'] );
echo "PVDAK_MANAGEMENT_PENDING_COUNT_OK: 14/14 isolated PHP cases (not WordPress E2E).\n";
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
// D31: the legacy registration Page must not inflate the required-page count.
$readiness_totals = pvdak_readiness_required_totals( array(
    array( 'classification' => 'published', 'optional' => false ),
    array( 'classification' => 'missing', 'optional' => true ),
    array( 'classification' => 'unpublished', 'optional' => false ),
    array( 'classification' => 'wrong_template', 'optional' => false ),
    array( 'classification' => 'template_unavailable', 'optional' => false ),
    array( 'classification' => 'provisional', 'optional' => false ),
    array( 'classification' => 'missing', 'optional' => false ),
    array( 'classification' => array( 'published' ), 'optional' => false ),
    array( 'classification' => 'unrecognized', 'optional' => false ),
    'malformed',
) );
foreach ( array( 'published', 'missing', 'unpublished', 'wrong_template', 'template_unavailable', 'provisional' ) as $key ) {
    if ( 1 !== $readiness_totals[ $key ] ) {
        fwrite( STDERR, "PVD_READINESS_FAIL: required-only counts for {$key}\n" );
        exit( 1 );
    }
}
$only_optional = pvdak_readiness_required_totals( array(
    array( 'classification' => 'missing', 'optional' => true ),
    array( 'classification' => 'published', 'optional' => true ),
) );
if ( 0 !== array_sum( $only_optional ) ||
     0 !== array_sum( pvdak_readiness_required_totals( false ) ) ) {
    fwrite( STDERR, "PVD_READINESS_FAIL: optional or malformed totals\n" );
    exit( 1 );
}
echo "PVDAK_READINESS_REQUIRED_PAGES_OK: 8/8 isolated PHP checks (not WordPress E2E).\n";
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
    array( 'page', 12, 'page', 'publish', 'ready', '012' ),
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

// D13/D14: exercise the actual public Page and registration gate functions.
// WordPress is NOT loaded. All Pages, options and templates below are synthetic.
if ( ! function_exists( 'add_filter' ) ) { function add_filter() {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode() {} }
if ( ! class_exists( 'WP_Post' ) ) {
    class WP_Post {
        public $ID, $post_status, $post_type;
        public function __construct( $id, $status = 'publish' ) {
            $this->ID = $id; $this->post_status = $status; $this->post_type = 'page';
        }
    }
}
if ( ! function_exists( 'sanitize_title' ) ) { function sanitize_title( $slug ) { return $slug; } }
if ( ! function_exists( 'get_page_by_path' ) ) {
    function get_page_by_path( $slug, $output = null, $type = null ) {
        return $GLOBALS['pvdak_test_public_page'] ?? null;
    }
}
if ( ! function_exists( 'get_page_template_slug' ) ) {
    function get_page_template_slug( $id ) { return $GLOBALS['pvdak_test_template_slug'] ?? ''; }
}
if ( ! function_exists( 'locate_template' ) ) {
    function locate_template( $template, $load = false, $require_once = true ) {
        return ! empty( $GLOBALS['pvdak_test_template_installed'] ) ? '/mock/theme/' . $template : '';
    }
}
if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( $id ) { return 'https://example.test/account-login/'; }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = false ) {
        return 'pvdak_registration_enabled' === $key ?
            ( $GLOBALS['pvdak_test_signup_setting'] ?? '0' ) : $default;
    }
}
require_once $root . '/inc/pvdak-public-pages.php';
require_once $root . '/inc/pvdak-registration.php';
$GLOBALS['pvdak_test_template_slug'] = 'page-templates/pvdak-login.php';
$GLOBALS['pvdak_test_template_installed'] = true;
$GLOBALS['pvdak_test_signup_setting'] = '1';
$public_cases = array(
    array( new WP_Post( 100, 'publish' ), 'page-templates/pvdak-login.php', true, true ),
    array( new WP_Post( 100, 'publish' ), 'page-templates/pvdak-login.php', false, false ),
    array( new WP_Post( 100, 'draft' ), 'page-templates/pvdak-login.php', true, false ),
    array( new WP_Post( 100, 'private' ), 'page-templates/pvdak-login.php', true, false ),
    array( new WP_Post( 100, 'publish' ), 'page-templates/pvdak-account.php', true, false ),
    array( null, 'page-templates/pvdak-login.php', true, false ),
);
foreach ( $public_cases as $case ) {
    $GLOBALS['pvdak_test_public_page'] = $case[0];
    $GLOBALS['pvdak_test_template_slug'] = $case[1];
    $GLOBALS['pvdak_test_template_installed'] = $case[2];
    $found = pvdak_public_template_page_url( 'account-login', 'page-templates/pvdak-login.php' );
    if ( ( '' !== $found ) !== $case[3] ) {
        fwrite( STDERR, "PVD_PUBLIC_ROUTE_FAIL: published Page/template availability\n" );
        exit( 1 );
    }
}
$GLOBALS['pvdak_test_public_page'] = new WP_Post( 100, 'publish' );
$GLOBALS['pvdak_test_template_slug'] = 'page-templates/pvdak-login.php';
foreach ( array(
    array( '1', true, true ), array( '1', false, false ),
    array( '0', true, false ),
) as $case ) {
    $GLOBALS['pvdak_test_signup_setting'] = $case[0];
    $GLOBALS['pvdak_test_template_installed'] = $case[1];
    if ( pvdak_registration_open() !== $case[2] ) {
        fwrite( STDERR, "PVD_PUBLIC_ROUTE_FAIL: registration must close on missing template\n" );
        exit( 1 );
    }
}
echo "PVDAK_PUBLIC_TEMPLATE_GATE_OK: 9/9 original-source PHP cases (not WordPress E2E).\n";


// D31: exercise the original dashboard setup with stubbed WordPress APIs.
// A reviewer must keep native Activity moderation; no production data is read.
if ( ! function_exists( 'remove_meta_box' ) ) {
    function remove_meta_box( $id, $screen, $context ) {
        $GLOBALS['pvdak_mock_removed_boxes'][] = $id;
    }
}
if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
    function wp_add_dashboard_widget( $id, $title, $callback ) {
        $GLOBALS['pvdak_mock_dashboard_widgets'][] = $id;
    }
}
$dashboard_cases = array(
    array( true, false, true, 3, 1 ),
    array( true, true, false, 2, 1 ),
    array( false, true, false, 0, 0 ),
);
foreach ( $dashboard_cases as $case ) {
    $GLOBALS['pvdak_mock_edit_pages'] = $case[0];
    $GLOBALS['pvdak_mock_moderate'] = $case[1];
    $GLOBALS['pvdak_mock_removed_boxes'] = array();
    $GLOBALS['pvdak_mock_dashboard_widgets'] = array();
    pvdak_management_dashboard_setup();
    if ( in_array( 'dashboard_activity', $GLOBALS['pvdak_mock_removed_boxes'], true ) !== $case[2] ||
         count( $GLOBALS['pvdak_mock_removed_boxes'] ) !== $case[3] ||
         count( $GLOBALS['pvdak_mock_dashboard_widgets'] ) !== $case[4] ) {
        fwrite( STDERR, "PVD_D31_DASHBOARD_FAIL: native Activity or editor capability\n" );
        exit( 1 );
    }
}
echo "PVDAK_D31_DASHBOARD_OK: 3/3 original-source PHP cases (not WordPress E2E).\n";

$notification_switch_cases = array(
    array( '1', true ), array( 1, true ), array( true, true ),
    array( '0', false ), array( 0, false ), array( false, false ),
    array( '', false ), array( '01', false ), array( '1junk', false ),
    array( array( '1' ), false ), array( (object) array( 'value' => '1' ), false ),
    array( null, false ),
);
foreach ( $notification_switch_cases as $case ) {
    if ( pvdak_management_switch_enabled( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_NOTIFICATION_STATUS_FAIL: invalid switch interpretation\n" );
        exit( 1 );
    }
}
echo "PVDAK_D31_NOTIFICATION_STATUS_OK: 12/12 original-source PHP cases (not WordPress E2E).\n";
