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
  'sms'=>'inc/pvdak-sms-auth.php',
  'recovery'=>'inc/pvdak-account-tools.php',
  'core_requests'=>'packages/pvdak-core/includes/requests.php',
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
    strpos($s['auth'],'pvdak_registration_rate_exceeded_key($key,6,15*MINUTE_IN_SECONDS)')!==false &&
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

// D31: a handler without its Page resolver cannot authorize a home shortcut.
if ( ! function_exists( 'pvdak_home_admin_landing' ) ) {
    function pvdak_home_admin_landing() {}
}
$GLOBALS['pvdak_mock_edit_pages'] = true;
if ( pvdak_management_link_available( array(
    'ویرایش صفحه اصلی', '', 'edit_pages', 'admin.php?page=pvdak-home-content'
) ) ) {
    fwrite( STDERR, "PVD_D31_HOME_ROUTE_FAIL: missing resolver must fail closed\n" );
    exit( 1 );
}
unset( $GLOBALS['pvdak_mock_edit_pages'] );
echo "PVDAK_D31_HOME_ROUTE_OK: original-source guard (not WP E2E).\n";

// The helper uses only allowlisted/capability-filtered routes supplied by
// the caller. Run the real function against synthetic maps, without WP/DB.
$triage = 'edit.php?post_type=pvdak_request&page=pvdak-request-triage';
$pending = 'edit.php?post_type=pvdak_request&pvdak_filter_queue=pending&pvdak_case_order=oldest';
$requests = 'edit.php?post_type=pvdak_request';
$prof_pending = 'edit.php?post_type=pvdak_professional&pvdak_state_filter=pending';
$professionals = 'edit.php?post_type=pvdak_professional';
$support = 'edit.php?post_type=pvdak_support_ticket';
$queue_cases = array(
    array( array( $triage => 1, $pending => 1, $requests => 1, $prof_pending => 1, $professionals => 1, $support => 1 ), array( $triage, $prof_pending, $support ) ),
    array( array( $pending => 1, $requests => 1, $professionals => 1, $support => 1 ), array( $pending, $professionals, $support ) ),
    array( array( $requests => 1, $professionals => 1, $support => 1 ), array( $requests, $professionals, $support ) ),
    array( array( $support => 1 ), array( $support ) ),
    array( array( 'untrusted.php' => 1 ), array() ),
    array( array(), array() ),
);
foreach ( $queue_cases as $case ) {
    if ( pvdak_management_primary_queue_routes( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_MANAGEMENT_FAIL: queue fallback/allowlist\n" );
        exit( 1 );
    }
}
echo "PVDAK_MANAGEMENT_QUEUE_ROUTES_OK: 6/6 isolated PHP cases (not WordPress E2E).\n";

$secondary_queue_cases = array(
    array( array( $triage => 1, $pending => 1, $requests => 1, $prof_pending => 1, $professionals => 1, $support => 1 ), array( $pending, $requests, $professionals ) ),
    array( array( $pending => 1, $requests => 1, $professionals => 1, $support => 1 ), array( $requests ) ),
    array( array( $requests => 1, $professionals => 1, $support => 1 ), array() ),
    array( array( $support => 1 ), array() ),
    array( array( 'untrusted.php' => 1 ), array() ),
    array( array(), array() ),
);
foreach ( $secondary_queue_cases as $case ) {
    if ( pvdak_management_secondary_queue_routes( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_MANAGEMENT_FAIL: supplementary queue links must remain capability-filtered\n" );
        exit( 1 );
    }
}
echo "PVDAK_MANAGEMENT_SECONDARY_QUEUE_OK: 6/6 original-source PHP cases (not WordPress E2E).\n";


$draft_page = 'edit.php?post_type=page&post_status=draft';
$draft_post = 'edit.php?post_status=draft';
$draft_cases = array(
    array( array( $draft_page => 1, $draft_post => 1 ), array( $draft_page, $draft_post ) ),
    array( array( $draft_post => 1 ), array( $draft_post ) ),
    array( array( $draft_page => 1 ), array( $draft_page ) ),
    array( array( 'untrusted.php' => 1 ), array() ),
    array( array(), array() ),
);
foreach ( $draft_cases as $case ) {
    if ( pvdak_management_draft_routes( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_MANAGEMENT_FAIL: draft shortcut allowlist\n" );
        exit( 1 );
    }
}
echo "PVDAK_MANAGEMENT_DRAFT_ROUTES_OK: 5/5 isolated PHP cases (not WordPress E2E).\n";

// D31: real original-source helper; only already-authorized editorial routes.
$review_page = 'edit.php?post_type=page&post_status=pending';
$review_post = 'edit.php?post_status=pending';
$review_cases = array(
    array( array( $review_page => 1, $review_post => 1 ), array( $review_page, $review_post ) ),
    array( array( $review_post => 1 ), array( $review_post ) ),
    array( array( $review_page => 1 ), array( $review_page ) ),
    array( array( 'untrusted.php' => 1 ), array() ),
    array( array(), array() ),
);
foreach ( $review_cases as $case ) {
    if ( pvdak_management_secondary_content_routes( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_MANAGEMENT_FAIL: editorial review route allowlist\n" );
        exit( 1 );
    }
}
if ( strpos( $s['admin'], 'محتوای منتظر بازبینی' ) === false ||
     strpos( $s['admin'], "'edit_pages', 'edit.php?post_type=page&post_status=pending'" ) === false ||
     strpos( $s['admin'], "'edit_posts', 'edit.php?post_status=pending'" ) === false ) {
    fwrite( STDERR, "PVD_MANAGEMENT_FAIL: pending editorial links and collapsed group\n" );
    exit( 1 );
}
echo "PVDAK_MANAGEMENT_REVIEW_ROUTES_OK: 5/5 original-source PHP cases and 3 static route checks (not WP E2E).\n";

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
if ( ! function_exists( 'get_posts' ) ) {
    function get_posts( $args ) {
        if ( '_wp_page_template' === ( $args['meta_key'] ?? '' ) ) {
            $GLOBALS['pvdak_test_template_query'] = $args;
            return $GLOBALS['pvdak_test_public_template_candidates'] ?? array();
        }
        $GLOBALS['pvdak_d31_collection_query'] = $args;
        return array();
    }
}
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
        if ( 'page_on_front' === $key ) { return $GLOBALS['pvdak_test_front_id'] ?? 0; }
        if ( 'show_on_front' === $key ) { return $GLOBALS['pvdak_test_front_mode'] ?? 'posts'; }
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
// D31: original-source route resolver with synthetic WordPress Page records.
$GLOBALS['pvdak_test_template_installed'] = true;
$GLOBALS['pvdak_test_template_slug'] = 'page-templates/pvdak-login.php';
$route_cases = array(
    array( null, array(), false ),
    array( null, array( new WP_Post( 201 ) ), true ),
    array( null, array( new WP_Post( 201 ), new WP_Post( 202 ) ), false ),
    array( null, array( new WP_Post( 201, 'draft' ) ), false ),
    array( new WP_Post( 100 ), array( new WP_Post( 201 ) ), true ),
    array( new WP_Post( 100, 'draft' ), array( new WP_Post( 201 ) ), false ),
);
$route_count = 0;
foreach ( $route_cases as $case ) {
    $GLOBALS['pvdak_test_public_page'] = $case[0];
    $GLOBALS['pvdak_test_public_template_candidates'] = $case[1];
    $found = pvdak_public_template_page_url( 'account-login', 'page-templates/pvdak-login.php' );
    if ( ( '' !== $found ) !== $case[2] ) {
        fwrite( STDERR, "PVD_D31_RENAMED_ROUTE_FAIL: unsafe or missing account destination\n" ); exit( 1 );
    }
    $route_count++;
}
$GLOBALS['pvdak_test_public_template_candidates'] = array( new WP_Post( 201, 'draft' ) );
if ( 1 !== count( pvdak_public_account_template_pages( 'page-templates/pvdak-login.php', true ) ) ||
     'page' !== $GLOBALS['pvdak_test_template_query']['post_type'] ||
     2 !== $GLOBALS['pvdak_test_template_query']['posts_per_page'] ||
     pvdak_public_account_template_pages( 'page-templates/pvdak-information.php', true ) ) {
    fwrite( STDERR, "PVD_D31_RENAMED_ROUTE_FAIL: bootstrap candidate or allowlist\n" ); exit( 1 );
}
$route_count++;
$GLOBALS['pvdak_test_public_page'] = new WP_Post( 100 );
$GLOBALS['pvdak_test_public_template_candidates'] = array();
echo "PVDAK_D31_RENAMED_ACCOUNT_ROUTE_OK: {$route_count}/{$route_count} original-source PHP cases (WP/host NOT_TESTED).\n";



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

/** D31 source-only regression: editors see guidance but no plugin-control link. */
$management_source = $s['admin'];
if ( strpos( $management_source, 'function pvdak_management_core_available()' ) === false ||
     strpos( $management_source, "if ( ! pvdak_management_core_available() )") === false ||
     strpos( $management_source, 'برای بررسی وضعیت نصب، با مدیر سایت هماهنگ کنید.' ) === false ||
     strpos( $management_source, "if ( current_user_can( 'manage_options' ) ) {") === false ) {
    fwrite( STDERR, "PVD_D31_CORE_GUIDANCE_FAIL: missing role-aware Core guidance\n" );
    exit( 1 );
}
echo "PVDAK_D31_CORE_GUIDANCE_OK: source-only check (not WordPress E2E).\n";

/**
 * D31: exercise the actual homepage ACF resolver with fake WordPress options.
 * No WordPress database, user records, post updates or real ACF calls.
 */
if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type( $id ) { return $GLOBALS['pvdak_test_front_type'] ?? 'page'; }
}
if ( ! function_exists( 'get_field' ) ) {
    function get_field( $name, $id ) {
        $GLOBALS['pvdak_test_home_field_reads'][] = $id;
        return array_key_exists( 'pvdak_test_home_field_value', $GLOBALS ) ?
            $GLOBALS['pvdak_test_home_field_value'] : 'متن آزمایشی';
    }
}
if ( ! function_exists( 'get_post_status' ) ) {
    function get_post_status( $id ) { return $GLOBALS['pvdak_test_front_status'] ?? 'publish'; }
}
if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
    function wp_get_attachment_image_url( $id, $size ) { return 'https://example.test/image-' . $id . '.jpg'; }
}
require_once $root . '/inc/pvdak-home-acf.php';
$home_id_cases = array(
    array( 21, 21 ), array( '21', 21 ), array( '021', 0 ),
    array( '21junk', 0 ), array( '-21', 0 ), array( -21, 0 ),
    array( '21.0', 0 ), array( array( 21 ), 0 ),
    array( true, 0 ), array( null, 0 ), array( 0, 0 ),
    array( '0', 0 ), array( '999999999999999999999999', 0 ),
);
$home_checks = 0;
foreach ( $home_id_cases as $case ) {
    if ( pvdak_home_configured_page_id( $case[0] ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: noncanonical configured Page ID\n" );
        exit( 1 );
    }
    $home_checks++;
}
$GLOBALS['pvdak_test_front_mode'] = 'page';
$GLOBALS['pvdak_test_front_type'] = 'page';
$GLOBALS['pvdak_test_front_id'] = '21';
if ( 21 !== pvdak_home_page_id() ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: valid static Page\n" ); exit( 1 );
}
$home_checks++;
$GLOBALS['pvdak_test_front_mode'] = 'posts';
if ( 0 !== pvdak_home_page_id() ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: latest-posts mode\n" ); exit( 1 );
}
$home_checks++;
$GLOBALS['pvdak_test_front_mode'] = 'page';
$GLOBALS['pvdak_test_front_type'] = 'post';
if ( 0 !== pvdak_home_page_id() ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: wrong content type\n" ); exit( 1 );
}
$home_checks++;
$GLOBALS['pvdak_test_front_type'] = 'page';
$GLOBALS['pvdak_test_front_id'] = '21junk';
$GLOBALS['pvdak_test_home_field_reads'] = array();
if ( 0 !== pvdak_home_page_id() ||
     'fallback' !== pvdak_home_value( 'hero_title', 'fallback' ) ||
     $GLOBALS['pvdak_test_home_field_reads'] ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: malformed option must not load another Page's ACF\n" );
    exit( 1 );
}
$home_checks++;
$GLOBALS['pvdak_test_front_id'] = '21';
if ( 'متن آزمایشی' !== pvdak_home_value( 'hero_title', 'fallback' ) ||
     $GLOBALS['pvdak_test_home_field_reads'] !== array( 21 ) ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: valid Page ACF read\n" ); exit( 1 );
}
$home_checks++;
$GLOBALS['pvdak_test_front_mode'] = 'posts';
$GLOBALS['pvdak_test_home_field_reads'] = array();
if ( 'fallback' !== pvdak_home_value( 'hero_title', 'fallback' ) ||
     $GLOBALS['pvdak_test_home_field_reads'] ) {
    fwrite( STDERR, "PVD_D31_HOME_ID_FAIL: stale front-page ACF read in posts mode\n" );
    exit( 1 );
}
$home_checks++;
echo "PVDAK_D31_HOME_ID_OK: {$home_checks}/{$home_checks} original-source PHP cases (not WordPress E2E).\n";


// D31: exercise original homepage public-copy and attachment guards against
// synthetic unpublished Pages and malformed ACF IDs; no WordPress DB loaded.
$GLOBALS['pvdak_test_front_mode'] = 'page';
$GLOBALS['pvdak_test_front_type'] = 'page';
$GLOBALS['pvdak_test_front_id'] = '21';
$draft_status_checks = 0;
foreach ( array( 'draft', 'private', 'pending', 'future', 'trash' ) as $status ) {
    $GLOBALS['pvdak_test_front_status'] = $status;
    $GLOBALS['pvdak_test_home_field_reads'] = array();
    if ( 'fallback' !== pvdak_home_value( 'hero_title', 'fallback' ) ||
         $GLOBALS['pvdak_test_home_field_reads'] ) {
        fwrite( STDERR, "PVD_D31_DRAFT_COPY_FAIL: unpublished Page ACF reached public output\n" );
        exit( 1 );
    }
    $draft_status_checks++;
}
$GLOBALS['pvdak_test_front_status'] = 'publish';
$GLOBALS['pvdak_test_home_field_value'] = '21';
$image_cases = array(
    array( 21, 'https://example.test/image-21.jpg' ),
    array( '21', 'https://example.test/image-21.jpg' ),
    array( array( 'ID' => '21' ), 'https://example.test/image-21.jpg' ),
    array( '021', '' ), array( '21junk', '' ), array( '2e1', '' ),
    array( '21.0', '' ), array( true, '' ),
    array( array( 'ID' => '21junk' ), '' ),
    array( array( 'ID' => array( 21 ) ), '' ),
    array( array( 'id' => 21 ), '' ),
);
foreach ( $image_cases as $case ) {
    $GLOBALS['pvdak_test_home_field_value'] = $case[0];
    if ( pvdak_home_image_url( 'hero_image' ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_IMAGE_FAIL: malformed attachment ID\n" );
        exit( 1 );
    }
}
unset( $GLOBALS['pvdak_test_home_field_value'] );
$home_source = file_get_contents( $root . '/inc/pvdak-home-acf.php' );
if ( strpos( $home_source, "wp_safe_redirect( admin_url( 'admin.php?page=pvdak-home-content&setup=review' ) );" ) === false ||
     strpos( $home_source, "wp_update_post( array( 'ID' => \$page_id, 'post_status' => 'publish' ) )" ) !== false ) {
    fwrite( STDERR, "PVD_D31_SETUP_FAIL: draft must not be auto-published\n" );
    exit( 1 );
}
echo "PVDAK_D31_DRAFT_IMAGE_OK: {$draft_status_checks} status + " . count( $image_cases ) .
    " image original-source PHP cases; setup source-only guard (not WP E2E).\n";

/** D31 setup regression: source-only guards; NOT a WordPress setup test. */
if ( ! preg_match( '/\$created\s*=\s*wp_insert_post\(\s*array\((.*?)\)\s*,\s*true\s*\);/s', $home_source, $new_page_match ) ||
     strpos( $new_page_match[1], "'post_status'  => 'draft'" ) === false ||
     strpos( $new_page_match[1], "'post_status'  => 'publish'" ) !== false ||
     strpos( $home_source, "if ( ! is_int( \$created ) || \$created < 1 )" ) === false ||
     strpos( $home_source, "'posts-conflict' === wp_unslash( \$_GET['setup'] )" ) === false ||
     strpos( $home_source, "pvdak_home_configured_page_id( get_option( 'page_for_posts' ) )" ) === false ) {
    fwrite( STDERR, "PVD_D31_SETUP_DRAFT_FAIL: missing draft/invalid-result/posts-index guard\n" );
    exit( 1 );
}
$draft_create_at = strpos( $home_source, "'post_status'  => 'draft'" );
$review_redirect_at = strpos( $home_source, "wp_safe_redirect( admin_url( 'admin.php?page=pvdak-home-content&setup=review' ) );", $draft_create_at );
$posts_conflict_at = strpos( $home_source, "if ( \$posts_page_id && \$posts_page_id === \$page_id )" );
$reading_update_at = strpos( $home_source, "update_option( 'show_on_front', 'page' );" );
if ( false === $review_redirect_at || false === $posts_conflict_at || false === $reading_update_at ||
     ! ( $draft_create_at < $review_redirect_at && $review_redirect_at < $posts_conflict_at &&
         $posts_conflict_at < $reading_update_at ) ) {
    fwrite( STDERR, "PVD_D31_SETUP_ORDER_FAIL: setup can update Reading settings before review\n" );
    exit( 1 );
}
echo "PVDAK_D31_SETUP_DRAFT_SOURCE_OK: source-only guards; WordPress E2E NOT_TESTED.\n";


/**
 * D14 source guard and original-function fixture. This is not a WordPress
 * integration test: a fake DB models lock success/contention and transients.
 */
$d14_guards = array(
    'atomic rate lock' => strpos($s['registration'], 'SELECT GET_LOCK(%s, 2)') !== false &&
        strpos($s['registration'], 'SELECT RELEASE_LOCK(%s)') !== false,
    'registration reservation' => strpos($s['registration'], 'pvdak_registration_rate_exceeded_key($rate, 1, 180)') !== false,
    'sms send and verify' => strpos($s['sms'], 'pvdak_sms_reserve_send($phone)') !== false &&
        strpos($s['sms'], 'pvdak_sms_claim_challenge($key,$code)') !== false,
    'verification resend' => strpos($s['verify'], 'pvdak_registration_rate_exceeded_key($rate, 1, 300)') !== false,
    'recovery cooldown' => strpos($s['recovery'], 'pvdak_registration_rate_exceeded_key( $token, 1, 90 )') !== false,
    'private intake' => strpos($s['core_requests'], 'pvdak_core_intake_reserve( $user_id )') !== false &&
        strpos($s['core_requests'], '$honeypot_filled') !== false,
);
foreach ($d14_guards as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "PVD_D14_SOURCE_FAIL: {$name}\n"); exit(1); }
}
if (!defined('MINUTE_IN_SECONDS')) { define('MINUTE_IN_SECONDS', 60); }
if (!defined('HOUR_IN_SECONDS')) { define('HOUR_IN_SECONDS', 3600); }
if (!function_exists('get_transient')) {
    function get_transient($key) {
        return array_key_exists($key, $GLOBALS['pvdak_d14_transients']) ?
            $GLOBALS['pvdak_d14_transients'][$key] : false;
    }
}
if (!function_exists('set_transient')) {
    function set_transient($key, $value, $ttl) {
        if (!empty($GLOBALS['pvdak_d14_storage_fail'])) { return false; }
        $GLOBALS['pvdak_d14_transients'][$key] = $value;
        return true;
    }
}
if (!function_exists('delete_transient')) {
    function delete_transient($key) {
        if (!array_key_exists($key, $GLOBALS['pvdak_d14_transients'])) { return false; }
        unset($GLOBALS['pvdak_d14_transients'][$key]);
        return true;
    }
}
if (!function_exists('wp_check_password')) {
    function wp_check_password($code, $hash) {
        return hash_equals($hash, 'mock:' . $code);
    }
}
if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth') { return 'isolated-fixture-only'; }
}
class PvdakD14FakeDb {
    public $busy = false;
    public $locks = 0;
    public $releases = 0;
    public function prepare($query, $name) {
        return str_replace('%s', "'" . $name . "'", $query);
    }
    public function get_var($query) {
        if (strpos($query, 'GET_LOCK') !== false) {
            if ($this->busy) { return 0; }
            $this->locks++;
            return 1;
        }
        if (strpos($query, 'RELEASE_LOCK') !== false) {
            $this->releases++;
            return 1;
        }
        return null;
    }
}
$GLOBALS['pvdak_d14_transients'] = array();
$wpdb = new PvdakD14FakeDb();
require_once $root . '/inc/pvdak-registration.php';
require_once $root . '/inc/pvdak-sms-auth.php';
$d14_results = array();
$d14_results['first'] = !pvdak_registration_rate_exceeded_key('test-rate', 2, 60);
$d14_results['second'] = !pvdak_registration_rate_exceeded_key('test-rate', 2, 60);
$d14_results['third denied'] = pvdak_registration_rate_exceeded_key('test-rate', 2, 60);
$wpdb->busy = true;
$d14_results['busy denied'] = pvdak_registration_rate_exceeded_key('busy', 2, 60);
$wpdb->busy = false;
$GLOBALS['pvdak_d14_transients']['bad'] = array('malformed');
$d14_results['malformed denied'] = pvdak_registration_rate_exceeded_key('bad', 2, 60);
$GLOBALS['pvdak_d14_storage_fail'] = true;
$d14_results['storage denied'] = pvdak_registration_rate_exceeded_key('store', 2, 60);
$GLOBALS['pvdak_d14_storage_fail'] = false;
$d14_results['phone reserved'] = pvdak_sms_reserve_send('09120000000');
$d14_results['phone cooldown'] = !pvdak_sms_reserve_send('09120000000');
$GLOBALS['pvdak_d14_transients']['challenge'] = array(
    'hash' => 'mock:123456', 'expires' => time() + 300, 'tries' => 0,
);
$d14_results['OTP claimed'] = is_array(pvdak_sms_claim_challenge('challenge', '123456'));
$d14_results['OTP replay denied'] = false === pvdak_sms_claim_challenge('challenge', '123456');
$d14_results['all locks released'] = $wpdb->locks === $wpdb->releases;
foreach ($d14_results as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "PVD_D14_FIXTURE_FAIL: {$name}\n"); exit(1); }
}
echo 'PVDAK_D14_ORIGINAL_FUNCTIONS_OK: ' . count($d14_results) .
    " isolated cases, " . count($d14_guards) . " source checks; WP/MariaDB E2E NOT_TESTED.\n";


/**
 * D30: read-only source guards for recovery safety. These assertions do NOT
 * restore a database, inspect live backups, or prove a successful rollback.
 */
$d30_installer = file_get_contents( $root . '/scripts/install-pvdak-core.sh' );
$d30_status = file_get_contents( $root . '/scripts/pvdak-status.sh' );
$d30_drill = file_get_contents( $root . '/scripts/pvdak-restore-drill.sh' );
$d30_checks = array(
    'isolated backup restore rehearsal available' => is_string( $d30_drill ) &&
        strpos( $d30_drill, 'D30_FILES_RESTORE_OK' ) !== false &&
        strpos( $d30_drill, 'diff -qr -- "$THEME" "$SCRATCH/theme"' ) !== false &&
        strpos( $d30_drill, 'diff -qr -- "$CORE" "$SCRATCH/core"' ) !== false,
    'private backup path and symlink controls' =>
        strpos( $d30_drill, '"$resolved" == "$BACKUP_ROOT/"*' ) !== false &&
        strpos( $d30_drill, 'contains symlink' ) !== false &&
        strpos( $d30_drill, 'backup root is inside public web tree' ) !== false,
    'no live SQL connection' =>
        strpos( $d30_drill, '--skip-networking' ) !== false &&
        strpos( $d30_drill, '--no-defaults' ) !== false &&
        strpos( $d30_drill, '--binary-mode --local-infile=0' ) !== false &&
        strpos( $d30_drill, '--secure-file-priv=' ) !== false &&
        strpos( $d30_drill, 'wp db import' ) === false,
    'no misleading SQL PASS in files-only mode' =>
        strpos( $d30_drill, 'D30_SQL_RESTORE_NOT_TESTED' ) !== false &&
        strpos( $d30_drill, '--files-only' ) !== false &&
        strpos( $d30_drill, 'run as an unprivileged account' ) !== false,
    'upgrade version is dynamic' => strpos( $d30_installer, 'PVDAK_EXPECTED_CORE_VERSION="$NEW" wp eval' ) !== false &&
        strpos( $d30_installer, 'PVDAK_CORE_VERSION !== "0.8.14"' ) === false &&
        strpos( $d30_installer, '"$NEW" == "0.8.14"' ) === false,
    'strict version header' => strpos( $d30_installer, 'malformed Core version headers' ) !== false,
    'installed symlink denied' => strpos( $d30_installer, 'find "$DEST" -type l -print -quit' ) !== false,
    'database backup required before swap' => strpos( $d30_installer, 'DB backup missing, empty or symlinked; Core unchanged' ) !== false,
    'private backup candidates only' => strpos( $d30_status, 'd30_backup_candidate()' ) !== false &&
        strpos( $d30_status, 'D30 SQL backup candidate' ) !== false,
    'no restore claim' => strpos( $d30_status, 'D30 restore drill' ) !== false &&
        strpos( $d30_status, 'NOT_TESTED (requires isolated database and restore evidence)' ) !== false,
);
foreach ( $d30_checks as $name => $ok ) {
    if ( ! $ok ) {
        fwrite( STDERR, "PVD_D30_SOURCE_FAIL: {$name}\n" );
        exit( 1 );
    }
}
echo 'PVDAK_D30_SOURCE_OK: ' . count( $d30_checks ) .
    " source-only checks; actual restore NOT_TESTED.\n";


/**
 * D31: exercise original homepage collection selectors with synthetic ACF
 * values. This is a PHP fixture against repository functions, not WP E2E.
 */
if ( ! class_exists( 'WP_Term' ) ) {
    class WP_Term {
        public $term_id;
        public function __construct( $id ) { $this->term_id = $id; }
    }
}
if ( ! function_exists( 'get_term' ) ) {
    function get_term( $id, $taxonomy ) { return new WP_Term( $id ); }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $value ) { return false; }
}
if ( ! function_exists( 'get_post' ) ) {
    function get_post( $id ) {
        $post = new WP_Post( $id, 'publish' );
        $post->post_type = 'post';
        return $post;
    }
}
require_once $root . '/inc/pvdak-home-collections.php';
$GLOBALS['pvdak_test_front_id'] = '21';
$GLOBALS['pvdak_test_front_mode'] = 'page';
$GLOBALS['pvdak_test_front_type'] = 'page';
$GLOBALS['pvdak_test_front_status'] = 'publish';
$d31_collection_checks = 0;
foreach ( array(
    array( 42, 42 ), array( '42', 42 ), array( new WP_Term( 42 ), 42 ),
    array( '042', 0 ), array( '42junk', 0 ), array( '-42', 0 ),
    array( true, 0 ), array( array( 'ID' => 42 ), 0 ),
) as $case ) {
    $GLOBALS['pvdak_test_home_field_value'] = $case[0];
    if ( pvdak_home_selected_category( 'magazine_category' ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_COLLECTION_FAIL: category ID must be canonical\n" ); exit( 1 );
    }
    $d31_collection_checks++;
}
foreach ( array(
    array( 17, 17 ), array( '17', 17 ), array( new WP_Post( 17 ), 17 ),
    array( '017', 0 ), array( '17junk', 0 ), array( array( 'ID' => 17 ), 0 ),
) as $case ) {
    $GLOBALS['pvdak_test_home_field_value'] = $case[0];
    $selected = pvdak_home_selected_post( 'featured_override' );
    if ( ( $selected instanceof WP_Post ? $selected->ID : 0 ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_COLLECTION_FAIL: featured Post ID must be canonical\n" ); exit( 1 );
    }
    $d31_collection_checks++;
}
foreach ( array(
    array( '31', 31 ), array( new WP_Post( 31 ), 31 ),
    array( '031', 0 ), array( '31junk', 0 ),
) as $case ) {
    $GLOBALS['pvdak_test_home_field_value'] = $case[0];
    unset( $GLOBALS['pvdak_d31_collection_query'] );
    pvdak_home_faq_pages( true );
    if ( ( $GLOBALS['pvdak_d31_collection_query']['post_parent'] ?? 0 ) !== $case[1] ) {
        fwrite( STDERR, "PVD_D31_COLLECTION_FAIL: FAQ parent must be canonical\n" ); exit( 1 );
    }
    $d31_collection_checks++;
}
foreach ( array( 'draft', 'private', 'trash' ) as $status ) {
    $GLOBALS['pvdak_test_front_status'] = $status;
    $GLOBALS['pvdak_test_home_field_reads'] = array();
    $GLOBALS['pvdak_test_home_field_value'] = '42';
    if ( 0 !== pvdak_home_selected_category( 'magazine_category' ) ||
         null !== pvdak_home_selected_post( 'featured_override' ) ||
         $GLOBALS['pvdak_test_home_field_reads'] ) {
        fwrite( STDERR, "PVD_D31_COLLECTION_FAIL: unpublished front Page read\n" ); exit( 1 );
    }
    $d31_collection_checks++;
}
$GLOBALS['pvdak_test_front_status'] = 'publish';
$GLOBALS['pvdak_test_front_mode'] = 'posts';
$GLOBALS['pvdak_test_home_field_reads'] = array();
if ( 0 !== pvdak_home_selected_category( 'magazine_category' ) ||
     $GLOBALS['pvdak_test_home_field_reads'] ) {
    fwrite( STDERR, "PVD_D31_COLLECTION_FAIL: latest-posts mode ACF read\n" ); exit( 1 );
}
$d31_collection_checks++;
echo 'PVDAK_D31_COLLECTIONS_OK: ' . $d31_collection_checks .
    "/$d31_collection_checks original-source PHP cases (not WordPress E2E).\n";

/**
 * D31: exercise the original private-screen classifier and robots callback.
 * No WordPress bootstrap, user data, cache plugin, or HTTP server is used.
 * The actual response headers still require a WordPress/browser test.
 */
if ( ! function_exists( 'is_page' ) ) {
    function is_page( $pages = null ) {
        $slug = $GLOBALS['pvdak_d31_private_slug'] ?? '';
        return null === $pages ? '' !== $slug : in_array( $slug, (array) $pages, true );
    }
}
if ( ! function_exists( 'is_page_template' ) ) {
    function is_page_template( $templates = '' ) {
        return in_array( $GLOBALS['pvdak_d31_private_template'] ?? '', (array) $templates, true );
    }
}
require_once $root . '/inc/pvdak-account-tools.php';
require_once $root . '/inc/pvdak-auth.php';
$private_cases = array(
    array( 'account-login', '', true ),
    array( 'account-profile', '', true ),
    array( 'account-recovery', '', true ),
    array( 'account-register', '', true ),
    array( 'my-pvdak-account', '', true ),
    array( 'renamed-login', 'page-templates/pvdak-login.php', true ),
    array( 'renamed-profile', 'page-templates/pvdak-profile.php', true ),
    array( 'renamed-recovery', 'page-templates/pvdak-recovery.php', true ),
    array( 'renamed-register', 'page-templates/pvdak-register.php', true ),
    array( 'renamed-dashboard', 'page-templates/pvdak-account.php', true ),
    array( 'ordinary', 'page-templates/pvdak-information.php', false ),
    array( 'ordinary', '', false ),
    array( '', 'page-templates/pvdak-account.php', false ),
);
$private_checks = 0;
foreach ( $private_cases as $case ) {
    $GLOBALS['pvdak_d31_private_slug'] = $case[0];
    $GLOBALS['pvdak_d31_private_template'] = $case[1];
    $actual = pvdak_account_sensitive_screen();
    $robots = pvdak_account_noindex( array( 'max-image-preview' => 'large' ) );
    if ( $actual !== $case[2] ||
         isset( $robots['noindex'] ) !== $case[2] ||
         isset( $robots['nofollow'] ) !== $case[2] ||
         'large' !== $robots['max-image-preview'] ) {
        fwrite( STDERR, "PVD_D31_PRIVATE_TEMPLATE_FAIL: unexpected privacy route or robots result\n" );
        exit( 1 );
    }
    $private_checks++;
}
$GLOBALS['pvdak_d31_private_slug'] = 'renamed-dashboard';
$GLOBALS['pvdak_d31_private_template'] = 'page-templates/pvdak-account.php';
pvdak_auth_disable_public_form_cache();
if ( ! defined( 'DONOTCACHEPAGE' ) || true !== DONOTCACHEPAGE ) {
    fwrite( STDERR, "PVD_D31_PRIVATE_TEMPLATE_FAIL: early cache opt-out missing\n" );
    exit( 1 );
}
pvdak_account_cache_protection(); // No live HTTP headers are asserted here.
echo "PVDAK_D31_PRIVATE_TEMPLATES_OK: {$private_checks}/{$private_checks} original-source PHP route/robots cases; cache opt-out checked; HTTP headers/WP NOT_TESTED.\n";
