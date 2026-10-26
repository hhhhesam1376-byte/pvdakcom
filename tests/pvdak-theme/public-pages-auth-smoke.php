<?php

/** Compare installed theme or Core metadata without pinning a moving patch version. */
function pvdak_test_release_at_least( $source, $minimum ) {
    return preg_match( '/^\\s*\\*?\\s*Version:[ \\t]*([0-9]+(?:[.][0-9]+)*)/mi', $source, $found ) === 1 &&
        version_compare( $found[1], $minimum, '>=' );
}
/**
 * Static, read-only integrity checks for PVDak public pages and user login.
 * WordPress itself is never bootstrapped by this test.
 */
$root = dirname( __DIR__, 2 );
function pvdak_public_smoke_require( $okay, $message ) {
    if ( ! $okay ) { fwrite( STDERR, "PVD_PUBLIC_FAIL: " . $message . "\n" ); exit( 1 ); }
}
$files = array(
 'header' => 'header.php', 'footer' => 'footer.php', 'functions' => 'functions.php',
 'pages' => 'inc/pvdak-public-pages.php', 'auth' => 'inc/pvdak-auth.php',
 'contact' => 'page-templates/pvdak-contact.php', 'login' => 'page-templates/pvdak-login.php',
 'account' => 'page-templates/pvdak-account.php',
 'info' => 'page-templates/pvdak-information.php', 'theme' => 'style.css',
);
$code = array();
foreach ( $files as $type => $filename ) {
    $source = $root . '/' . $filename;
    pvdak_public_smoke_require( is_readable( $source ), 'Missing ' . $filename );
    $code[$type] = file_get_contents( $source );
}
pvdak_public_smoke_require( false !== strpos( $code['header'], 'pvdak_auth_login_url()' ) &&
 false !== strpos( $code['header'], 'pvdak_auth_account_url()' ) &&
 false === strpos( $code['header'], 'wp_login_url(' ) &&
 false === strpos( $code['header'], 'is_user_logged_in() ? admin_url()' ), 'Header no longer links to WP login/admin' );
pvdak_public_smoke_require( false !== strpos( $code['auth'], 'wp_signon(' ) &&
 false !== strpos( $code['auth'], 'check_admin_referer(' ) &&
 false !== strpos( $code['auth'], "add_filter('login_url'" ) &&
 false !== strpos( $code['auth'], 'is_admin()' ), 'Native authenticated login preserves admin access' );
// D05/D07: no guessed 404 routes if admin has not published account Pages.
$tools_source = file_get_contents( $root . '/inc/pvdak-account-tools.php' );
pvdak_public_smoke_require(
 false !== strpos( $code['auth'], "site_url('wp-login.php', 'login')" ) &&
 false !== strpos( $code['auth'], "admin_url('profile.php')" ) &&
 false === strpos( $code['auth'], "home_url('/account-login/')" ) &&
 false === strpos( $code['auth'], "home_url('/my-pvdak-account/')" ) &&
 false !== strpos( $tools_source, "site_url( 'wp-login.php?action=lostpassword', 'login' )" ) &&
 false !== strpos( $tools_source, "'action' => 'rp'" ),
 'D05/D07: missing branded login, dashboard and recovery Pages use valid native WordPress routes' );

// D05/D07: Pages that are published with the wrong native template are NOT
// valid destinations for login, dashboard or password recovery.
pvdak_public_smoke_require(
 false !== strpos( $code['pages'], 'function pvdak_public_template_page_url(' ) &&
 false !== strpos( $code['pages'], 'get_page_template_slug( $page->ID )' ) &&
 false !== strpos( $code['auth'], "pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php')" ) &&
 false !== strpos( $code['auth'], "pvdak_public_template_page_url('my-pvdak-account', 'page-templates/pvdak-account.php')" ) &&
 false !== strpos( $tools_source, "pvdak_public_template_page_url( 'account-recovery', 'page-templates/pvdak-recovery.php' )" ) &&
 false !== strpos( $code['login'], 'data-pvdak-auth-go="register-email"' ) &&
 false !== strpos( $code['login'], 'name="action" value="pvdak_register"' ) &&
 false !== strpos( $code['login'], 'ثبت‌نام در PVDak' ),
 'D05/D07: signup reuses the published login Page'
);
// D07: failed password attempts must surface the existing generic server error,
// and GET filters must retain the support panel after reloading the dashboard.
$support_source = file_get_contents( $root . '/inc/pvdak-support-tickets.php' );
pvdak_public_smoke_require(
 false !== strpos( $code['login'], 'id="pvdak-login-result"' ) &&
 false !== strpos( $code['login'], 'اطلاعات ورود تأیید نشد' ) &&
 false !== strpos( $code['auth'], "add_query_arg('error','1',pvdak_auth_login_url())" ),
 'D07 password failure must return a generic visible error on the branded login page' );
pvdak_public_smoke_require(
 false !== strpos( $support_source, "pvdak_auth_account_url() . '#pvdak-support'" ) &&
 false !== strpos( $support_source, 'name="ticket_status"' ) &&
 false !== strpos( $support_source, 'name="ticket_order"' ) &&
 false !== strpos( $support_source, 'name="ticket_search"' ),
 'D07 support filters must keep the support panel selected after GET submission' );

foreach ( array( 'about-us', 'contact-us', 'legal-faq', 'privacy-policy', 'terms-of-use', 'account-login', 'my-pvdak-account' ) as $slug ) {
    pvdak_public_smoke_require( false !== strpos( $code['pages'], "'" . $slug . "'" ), 'Seed includes ' . $slug );
}
pvdak_public_smoke_require(
 false === strpos( $code['pages'], "|| '0.8.26' === get_option('_pvdak_public_pages_bootstrap')" ) &&
 false !== strpos( $code['pages'], "update_option('_pvdak_public_pages_bootstrap','0.8.29'" ) &&
 false !== strpos( $code['pages'], "'publish' !== \$existing->post_status" ),
 'Page bootstrap must recover missing slugs even after legacy completion marker without overwriting existing Pages' );
pvdak_public_smoke_require( false !== strpos( $code['pages'], "add_action('admin_init'" ) &&
 false !== strpos( $code['pages'], 'get_page_by_path(' ) &&
 false !== strpos( $code['pages'], "current_user_can('manage_options')" ) &&
 false !== strpos( $code['pages'], "'_pvdak_provisional_policy'" ), 'Admin-only idempotent page seed and provisional legal content' );
pvdak_public_smoke_require( false !== strpos( $code['pages'], 'pvdak_maintenance_can_preview_site()' ) &&
 false !== strpos( $code['pages'], 'check_admin_referer(' ) &&
 false !== strpos( $code['pages'], 'wp_mail(' ) &&
 false !== strpos( $code['pages'], 'get_transient(' ), 'Contact handler includes maintenance, nonce, mail and throttle' );
pvdak_public_smoke_require( false !== strpos( $code['contact'], 'pvdak_contact_allowed()' ) &&
 false !== strpos( $code['contact'], 'contact_consent' ) &&
 false !== strpos( $code['login'], 'autocomplete="current-password"' ) &&
 false !== strpos( $code['account'], "'pvdak_account_dashboard'" ), 'Page templates have expected form and Core dashboard' );
pvdak_public_smoke_require( false !== strpos( $code['login'], '$admin_preview' ) &&
 false !== strpos( $code['login'], 'current_user_can(' ) &&
 false !== strpos( $code['footer'], "'legal-faq'=>'پرسش‌های متداول'" ),
 'Site owner can safely preview login UI and footer includes FAQ' );
pvdak_public_smoke_require( false !== strpos( $code['footer'], 'pvdak-footer-public' ) &&
 pvdak_test_release_at_least( $code['theme'], '0.8.29' ), 'Footer links and theme cache version' );

/* Batch 096: ten employer-visible, real-data UI changes without fake endpoints. */
$ui_paths = array(
 'recovery' => 'page-templates/pvdak-recovery.php',
 'register' => 'page-templates/pvdak-register.php',
 'search' => 'search.php',
 'archive' => 'archive.php',
 'journal' => 'index.php',
 'single' => 'template-parts/content-single.php',
 'none' => 'template-parts/content-none.php',
 'editorial' => 'assets/css/pvdak-editorial.css',
);
$ui = array();
foreach ( $ui_paths as $key => $file ) {
    pvdak_public_smoke_require( is_readable( $root . '/' . $file ), 'Missing UI source: ' . $file );
    $ui[ $key ] = file_get_contents( $root . '/' . $file );
}
pvdak_public_smoke_require(
 strpos( $code['login'], 'pvdak-auth-visual' ) !== false &&
 strpos( $code['login'], 'data-pvdak-single-submit' ) !== false &&
 strpos( $code['login'], "wp_nonce_field('pvdak_login'" ) !== false,
 '096-01 Branded login retains native nonce/action' );
pvdak_public_smoke_require(
  strpos( $code['login'], 'data-pvdak-auth-tab="register-email"' ) !== false &&
  strpos( $code['login'], 'data-pvdak-auth-tab="sms-register"' ) !== false &&
  strpos( $code['login'], "wp_nonce_field('pvdak_register'" ) !== false &&
  strpos( $code['login'], "wp_nonce_field('pvdak_sms_send'" ) !== false &&
  strpos( $ui['register'], 'pvdak_registration_result_url()' ) !== false &&
  strpos( $ui['register'], 'wp_safe_redirect(' ) !== false,
  '096-02 Embedded signup modes preserve nonce and legacy redirects' );
pvdak_public_smoke_require(
 strpos( $ui['recovery'], 'pvdak-recovery-steps' ) !== false &&
 strpos( $ui['recovery'], 'check_password_reset_key(' ) !== false &&
 strpos( $ui['recovery'], 'pvdak_recovery_change' ) !== false,
 '096-03 Recovery UI retains token check and handlers' );
pvdak_public_smoke_require(
 strpos( $code['contact'], 'pvdak-contact-guidance' ) !== false &&
 strpos( $code['contact'], 'pvdak_contact_allowed()' ) !== false &&
 strpos( $code['contact'], "wp_nonce_field('pvdak_contact_form'" ) !== false,
 '096-04 Contact confidentiality guide keeps guard and nonce' );
pvdak_public_smoke_require(
 strpos( $code['info'], 'pvdak-info-sidebar' ) !== false &&
 strpos( $code['info'], 'pvdak_public_page_url( $slug )' ) !== false &&
 strpos( $code['info'], 'the_content();' ) !== false,
 '096-05 Informational pages link only published Pages' );
pvdak_public_smoke_require(
 strpos( $ui['search'], 'pvdak-discovery-hero' ) !== false &&
 strpos( $ui['search'], 'esc_html( $term )' ) !== false &&
 strpos( $ui['search'], "get_template_part( 'template-parts/loop' )" ) !== false,
 '096-06 Search result count and query are displayed safely' );
pvdak_public_smoke_require(
 strpos( $ui['archive'], 'pvdak-discovery-categories' ) !== false &&
 strpos( $ui['archive'], 'get_categories(' ) !== false &&
 strpos( $ui['archive'], 'get_category_link(' ) !== false,
 '096-07 Archive filters come from actual WordPress categories' );
pvdak_public_smoke_require(
 strpos( $ui['journal'], 'pvdak-journal-page' ) !== false &&
 strpos( $ui['journal'], 'get_search_form();' ) !== false &&
 strpos( $ui['journal'], "get_template_part( 'template-parts/loop' )" ) !== false,
 '096-08 Journal page uses published content and native search' );
pvdak_public_smoke_require(
 strpos( $ui['single'], 'pvdak-article-jumpnav' ) !== false &&
 strpos( $ui['single'], 'id="pvdak-article-text"' ) !== false &&
 strpos( $ui['editorial'], '.pvdak-article-service-note' ) !== false &&
 strpos( $ui['single'], "apply_filters( 'the_content', get_the_content() )" ) !== false &&
 strpos( $ui['single'], 'pvdak-article-toc' ) !== false,
 '096-09 Article navigation and disclaimer preserve authored content' );
pvdak_public_smoke_require(
 strpos( $ui['none'], 'pvdak-empty-state' ) !== false &&
 strpos( $ui['none'], 'get_categories(' ) !== false &&
 strpos( $ui['none'], 'get_search_form();' ) !== false &&
 strpos( $code['theme'], '.pvdak-discovery-page .post-card' ) !== false,
 '096-10 Responsive empty results offer real published categories' );

// Branding regression: user-facing labels are PVDak across the branded
// templates, while legacy stored defaults and installed Core are normalized
// without touching editor-authored content or the Core release itself.
$brand_acf = file_get_contents( $root . '/inc/pvdak-home-acf.php' );
$brand_editor = file_get_contents( $root . '/inc/pvdak-editor-bootstrap.php' );
pvdak_public_smoke_require(
 false !== strpos( $code['login'], 'حساب کاربری PVDak' ) &&
 false === strpos( $code['login'], 'حساب کاربری پویش' ) &&
 false !== strpos( $code['header'], '<span class="site-description">PVDak</span>' ) &&
 false !== strpos( $code['header'], "str_replace( array( 'پویش وکلای دادگستری کشور', 'پویش' )" ) &&
 false !== strpos( $code['footer'], 'تماس با PVDak' ) &&
 false !== strpos( $code['account'], 'امکانات PVDak' ) &&
 false !== strpos( $brand_acf, "'hero_label' => 'PVDak'" ) &&
 false !== strpos( $brand_acf, 'function pvdak_home_brand_copy(' ) &&
 false !== strpos( $brand_editor, 'function pvdak_editor_relabel_legacy_brand_categories(' ) &&
 false !== strpos( $code['pages'], 'function pvdak_public_relabel_legacy_brand_pages(' ) &&
 false !== strpos( $code['pages'], "update_option( 'blogname', 'PVDak' )" ) &&
 false !== strpos( $code['pages'], "get_option( 'blogdescription', '' )" ) &&
 false !== strpos( $code['functions'], "add_filter( 'do_shortcode_tag', 'pvdak_core_shortcode_brand_labels', 10, 2 );" ),
 'PVDak branding replaces the legacy name on frontend, existing ACF values and installed Core shortcode labels' );


// D28/D31: read-only source regressions; no live comments or WP options changed.
pvdak_public_smoke_require(
 strpos( $code['pages'], "add_filter('comments_open', 'pvdak_forum_disable_native_comments'" ) !== false &&
 strpos( $code['pages'], "add_filter('pings_open', 'pvdak_forum_disable_native_comments'" ) !== false &&
 strpos( $code['pages'], "add_filter('preprocess_comment', 'pvdak_forum_reject_native_submission'" ) !== false,
 'D28 native comments, pingbacks and REST comment submission cannot bypass forum moderation' );
pvdak_public_smoke_require(
 strpos( $code['pages'], "name=\"forum_public_consent\" value=\"1\" required" ) !== false &&
 strpos( $code['pages'], "is_string(\$consent) && '1' === \$consent" ) !== false,
 'D28 requires explicit public visibility acknowledgement on client and server' );
pvdak_public_smoke_require(
 strpos( $code['pages'], 'function pvdak_forum_raw_text_valid(' ) !== false &&
 strpos( $code['pages'], 'pvdak_forum_raw_text_valid($subject_raw, 480)' ) !== false &&
 strpos( $code['pages'], 'pvdak_forum_raw_text_valid($message_raw, 6000)' ) !== false &&
 strpos( $code['pages'], 'pvdak_forum_contains_link_or_email($subject' ) !== false,
 'D28 validates raw UTF-8, byte bounds, controls and direct links before persistence' );
pvdak_public_smoke_require(
 strpos( $code['pages'], '(int) $stored->comment_post_ID === $page_id' ) !== false &&
 strpos( $code['pages'], '0 === (int) $stored->user_id' ) !== false &&
 strpos( $code['pages'], '$payload === (string) $stored->comment_content' ) !== false &&
 strpos( $code['pages'], "'0' === (string) \$stored->comment_approved" ) !== false,
 'D28 checks persisted page, anonymous identity, exact payload and moderation state' );
pvdak_public_smoke_require(
 strpos( $code['pages'], '(int) get_transient($hour_key) === $count + 1' ) !== false &&
 strpos( $code['pages'], "'1' === (string) get_transient(\$cool_key)" ) !== false &&
 strpos( $code['pages'], '0 !== (int) $entry->comment_parent' ) !== false &&
 strpos( $code['pages'], "'_pvdak_forum_submitter'" ) !== false,
 'D28 validates quota persistence and filters malformed approved entries' );
pvdak_public_smoke_require(
 strpos( $code['pages'], "add_action('wp', 'pvdak_forum_send_headers', 0)" ) !== false &&
 strpos( $code['pages'], "define('DONOTCACHEPAGE', true)" ) !== false &&
 strpos( $code['pages'], 'X-Robots-Tag: noindex, noarchive, nosnippet' ) !== false &&
 strpos( $code['theme'], '.pvdak-forum-consent' ) !== false,
 'D31 forum nonce page is non-cacheable, noindex and RTL consent is visible' );

// D28: moderation must preserve an auditable consent acknowledgement and
// recheck privacy after moderator edits, before any comment becomes public.
pvdak_public_smoke_require(
 strpos( $code['pages'], "add_comment_meta(\$comment_id, '_pvdak_forum_consent_at'" ) !== false &&
 strpos( $code['pages'], "get_comment_meta(\$comment_id, '_pvdak_forum_consent_at'" ) !== false &&
 strpos( $code['pages'], 'function pvdak_forum_consent_valid(' ) !== false &&
 strpos( $code['pages'], 'pvdak_forum_consent_valid($entry->comment_ID)' ) !== false &&
 strpos( $code['pages'], 'pvdak_forum_raw_text_valid($entry->comment_content, 6200)' ) !== false &&
 strpos( $code['pages'], "delete_comment_meta(\$comment_id, '_pvdak_forum_consent_at')" ) !== false,
 'D28 private per-message publication acknowledgement is persisted, required and cleaned on failure' );
pvdak_public_smoke_require(
 strpos( $code['pages'], "'number' => 60" ) !== false &&
 strpos( $code['pages'], "if (count(\$items) >= 10) break;" ) !== false &&
 strpos( $code['pages'], "pvdak_forum_contains_link_or_email(\$entry->comment_content)" ) !== false &&
 strpos( $code['pages'], "'عضو جامعه' !== (string) \$entry->comment_author" ) !== false &&
 strpos( $code['pages'], "'' !== (string) \$entry->comment_author_IP" ) !== false,
 'D28 public rendering skips malformed, reidentified and unsafe moderated comments' );


// D07: email verification must be readable, branded and linked only to the
// short-lived existing hashed token. These are source checks, not mailbox tests.
$registration_source = file_get_contents($root . '/inc/pvdak-registration.php');
$verification_source = file_get_contents($root . '/inc/pvdak-email-verification.php');
$signup_page_source = file_get_contents($root . '/page-templates/pvdak-login.php');
pvdak_public_smoke_require(
    strpos($registration_source, 'username_exists($username)') !== false &&
    strpos($registration_source, 'email_exists($email)') !== false &&
    strpos($registration_source, "get_option('admin_email'") !== false &&
    strpos($registration_source, "'state','duplicate'") !== false &&
    strpos($registration_source, "'existing_user_email'") !== false &&
    strpos($registration_source, "'existing_user_login'") !== false &&
    strpos($signup_page_source, "'duplicate'=>'") !== false,
    'D07 duplicate login/email/admin address is rejected and clearly explained'
);
pvdak_public_smoke_require(
    strpos($verification_source, 'function pvdak_email_verification_html(') !== false &&
    strpos($verification_source, 'dir="rtl"') !== false &&
    strpos($verification_source, 'تأیید آدرس ایمیل و فعال‌سازی حساب') !== false &&
    strpos($verification_source, '{{VERIFY_URL}}') !== false &&
    strpos($verification_source, 'esc_html($display_name)') !== false &&
    strpos($verification_source, 'esc_url($url)') !== false &&
    strpos($verification_source, 'Content-Type: text/html; charset=UTF-8') !== false &&
    strpos($verification_source, 'AltBody') !== false,
    'D07 RTL branded HTML verification mail and text alternative are prepared'
);
pvdak_public_smoke_require(
    strpos($verification_source, 'wp_hash_password($token)') !== false &&
    strpos($verification_source, 'DAY_IN_SECONDS') !== false &&
    strpos($verification_source, 'wp_check_password($token, $hash, $user_id)') !== false &&
    strpos($verification_source, "delete_user_meta($user_id, '_pvdak_email_verification_token_hash')") !== false &&
    strpos($verification_source, 'remove_action(') !== false,
    'D07 verification token remains hashed, time-bound and one-use'
);

echo "PVDAK_PUBLIC_PAGES_AUTH_OK: source checks passed; live WP/HTTP still unverified.\n";
