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
foreach ( array( 'about-us', 'contact-us', 'legal-faq', 'privacy-policy', 'terms-of-use', 'account-login', 'my-pvdak-account' ) as $slug ) {
    pvdak_public_smoke_require( false !== strpos( $code['pages'], "'" . $slug . "'" ), 'Seed includes ' . $slug );
}
pvdak_public_smoke_require(
 false === strpos( $code['pages'], "|| '0.8.26' === get_option('_pvdak_public_pages_bootstrap')" ) &&
 false !== strpos( $code['pages'], "update_option('_pvdak_public_pages_bootstrap','0.8.29'" ) &&
 false !== strpos( $code['pages'], "'publish' !== $existing->post_status" ),
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
 strpos( $ui['register'], 'pvdak-register-steps' ) !== false &&
 strpos( $ui['register'], 'if(!pvdak_registration_open())' ) !== false &&
 strpos( $ui['register'], "wp_nonce_field('pvdak_register'" ) !== false,
 '096-02 Registration closed-state is honest and gated' );
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

echo "PVDAK_PUBLIC_PAGES_AUTH_OK: source checks passed; live WP/HTTP still unverified.\n";
