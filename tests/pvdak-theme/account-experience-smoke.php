<?php
/** Source-only account UX regression; no WordPress bootstrap, users or DB. */
$root = dirname( __DIR__, 2 );
$paths = array(
  'portal'=>'page-templates/pvdak-account.php',
  'front'=>'front-page.php',
  'login'=>'page-templates/pvdak-login.php',
  'profile'=>'page-templates/pvdak-profile.php',
  'recovery'=>'page-templates/pvdak-recovery.php',
  'register'=>'page-templates/pvdak-register.php',
  'contact'=>'page-templates/pvdak-contact.php',
  'header'=>'header.php',
  'footer'=>'footer.php',
  'nav'=>'inc/pvdak-navigation-links.php',
  'assets'=>'functions.php',
  'javascript'=>'assets/js/pvdak-account.js',
  'css'=>'style.css',
);
$v = array();
foreach ( $paths as $name => $file ) {
  $path = $root . '/' . $file;
  if ( ! is_readable( $path ) ) { fwrite( STDERR, "ACCOUNT_UX_FAIL: missing $file\n" ); exit(2); }
  $v[$name] = file_get_contents( $path );
}
$checks = array(
  '01 Global branding with scales icon' =>
    strpos($v['header'],'pvdak-profile-brand-scale')!==false &&
    strpos($v['css'],'.site-header .site-branding .pvdak-profile-brand-scale')!==false,
  '02 Sitewide native WordPress content search' =>
    strpos($v['header'],'class="pvdak-site-search pvdak-profile-header-search"')!==false &&
    strpos($v['header'],'method="get"')!==false &&
    strpos($v['header'],'name="s"')!==false &&
    strpos($v['css'],'.site-header .pvdak-site-search')!==false,
  '03 Accessible native mobile navigation and active fallback links' =>
    strpos($v['header'],'<details class="pvdak-mobile-menu">')!==false &&
    strpos($v['header'],'aria-current="page"')!==false &&
    strpos($v['css'],'.site-header .pvdak-mobile-menu[open]>nav')!==false,
  '04 Footer uses branded legal marks and real destinations' =>
    strpos($v['footer'],'pvdak-footer-heading')!==false &&
    strpos($v['footer'],'pvdak-footer-contact-link')!==false &&
    strpos($v['footer'],'pvdak-footer-tagline')!==false,
  '05 Homepage hero gets vivid branded search styling' =>
    strpos($v['front'],'class="pvdak-home-hero"')!==false &&
    strpos($v['css'],'.pvdak-home-hero::after')!==false &&
    strpos($v['css'],'.pvdak-home-search:focus-within')!==false,
  '06 Four shortcuts remain selected and styled, without fabricated links' =>
    strpos($v['front'],'pvdak_home_quick_pages()')!==false &&
    strpos($v['css'],'.pvdak-home-quick-actions>a')!==false,
  '07 Swiper topic cards retain controls and better contrast' =>
    strpos($v['front'],'data-pvdak-topic-prev')!==false &&
    strpos($v['front'],'swiper-wrapper')!==false &&
    strpos($v['css'],'.pvdak-topics-section .pvdak-home-topic:hover')!==false,
  '08 Editorial cards and timeline share a visual system' =>
    strpos($v['css'],'.pvdak-magazine-feature,.pvdak-magazine-aside-card,.pvdak-notices-panel')!==false &&
    strpos($v['css'],'.pvdak-home-process{background:linear-gradient')!==false,
  '09 Public information/contact retain native forms and improved cards' =>
    strpos($v['contact'],'pvdak_contact_allowed()')!==false &&
    strpos($v['css'],'.page-entry{background:#fff')!==false &&
    strpos($v['css'],'.pvdak-contact-page .pvdak-forms button[type=submit]')!==false,
  '10 Account dashboard adds live gated quick links and styling' =>
    strpos($v['portal'],'pvdak-dashboard-quicklinks')!==false &&
    strpos($v['portal'],'if ( $has_support )')!==false &&
    strpos($v['portal'],'if ( $profile_url )')!==false &&
    strpos($v['css'],'.pvdak-dashboard-quicklinks{')!==false,
  'Account sidebar and personal shortcuts' =>
    strpos($v['portal'],'pvdak-account-sidebar')!==false &&
    strpos($v['portal'],'id="pvdak-profile"')!==false &&
    strpos($v['portal'],'pvdak-account-signout')!==false,
  'Dashboard and private support anchor are connected' =>
    strpos($v['portal'],'id="pvdak-dashboard"')!==false &&
    strpos($v['portal'],'href="#pvdak-support"')!==false &&
    strpos($v['portal'],"do_shortcode( '[pvdak_support_tickets]' )")!==false,
  'Professional panel remains conditional' =>
    strpos($v['portal'],'$has_professional')!==false &&
    strpos($v['portal'],'pvdak_core_existing_application(')!==false &&
    strpos($v['portal'],'id="pvdak-professional"')!==false,
  'Portal reports real intake state' =>
    strpos($v['portal'],'pvdak_core_intake_open()')!==false &&
    strpos($v['portal'],'ثبت درخواست حقوقی جدید فعلاً غیرفعال')!==false,
  'Login retains real WordPress handler, nonce and recovery' =>
    strpos($v['login'],'value="pvdak_login"')!==false &&
    strpos($v['login'],"wp_nonce_field('pvdak_login'")!==false &&
    strpos($v['login'],'pvdak_account_tool_url(\'account-recovery\')')!==false,
  'Password toggle works without backend changes' =>
    strpos($v['login'],'data-pvdak-password-toggle')!==false &&
    strpos($v['recovery'],'data-pvdak-password-toggle')!==false &&
    strpos($v['register'],'data-pvdak-password-toggle')!==false &&
    strpos($v['javascript'],"field.type = visible ? 'text' : 'password'")!==false,
  'Premium profile has RTL hero, live editable form and proper navigation' =>
    strpos($v['portal'], 'pvdak-profile-redesign') !== false &&
    strpos($v['portal'], 'pvdak-account-hero') !== false &&
    strpos($v['portal'], 'pvdak-profile-editor') !== false &&
    strpos($v['portal'], 'pvdak-account-menu') !== false &&
    strpos($v['portal'], 'wp_logout_url(') !== false &&
    strpos($v['portal'], 'data-pvdak-profile-form') !== false &&
    strpos($v['portal'], 'data-pvdak-profile-save') !== false,
  'Profile styling is scoped and responsive with no remote font dependency' =>
    strpos($v['css'], 'body.pvdak-profile-screen') !== false &&
    strpos($v['css'], '.pvdak-profile-workspace{') !== false &&
    strpos($v['css'], '.pvdak-profile-rail{') !== false &&
    strpos($v['css'], '@media(max-width:540px)') !== false,
  'Profile hero artwork is local and references the legal image' =>
    is_file($root . '/assets/icons/pvdak-profile-hero.svg') &&
    strpos($v['portal'], 'pvdak-account-hero')!==false &&
    strpos($v['css'], "url('assets/icons/pvdak-profile-hero.svg')")!==false,
  'Profile-specific header has a working native WordPress search form' =>
    strpos($v['header'], 'pvdak-profile-header-search')!==false &&
    strpos($v['header'], 'name="s"')!==false &&
    strpos($v['header'], "is_page_template( 'page-templates/pvdak-profile.php' )")!==false,
  'Profile-specific footer includes a real WordPress search form' =>
    strpos($v['footer'], 'pvdak-profile-footer-search')!==false &&
    strpos($v['footer'], 'name="s"')!==false &&
    strpos($v['footer'], "is_page_template( 'page-templates/pvdak-profile.php' )")!==false,
  'Reference proportions and trusted form interactions remain' =>
    strpos($v['css'], '.pvdak-profile-hero-photo{')!==false &&
    strpos($v['css'], '.pvdak-profile-bottom{')!==false &&
    strpos($v['portal'], 'pvdak-profile-shield-art')!==false &&
    strpos($v['portal'], 'data-pvdak-profile-save')!==false &&
    strpos($v['portal'], 'wp_nonce_field(')!==false,
  'Profile keeps nonce and provides account return path' =>
    strpos($v['portal'],'pvdak_profile_save')!==false &&
    strpos($v['portal'],'pvdak_auth_account_url()')!==false &&
    strpos($v['portal'],'pvdak-profile-details')!==false,
  'Legacy profile transparently redirects to the unified panel' =>
    strpos($v['profile'], "wp_safe_redirect( $target . '#pvdak-profile', 302 )")!==false &&
    strpos($v['profile'], 'pvdak_auth_account_url()')!==false &&
    strpos($v['profile'], 'get_header()')===false,
  'One dashboard includes accessible tabs for all current features' =>
    strpos($v['portal'], 'data-pvdak-workspace')!==false &&
    strpos($v['portal'], 'data-pvdak-panel')!==false &&
    strpos($v['portal'], 'id="pvdak-dashboard"')!==false &&
    strpos($v['portal'], 'id="pvdak-profile"')!==false &&
    strpos($v['portal'], 'id="pvdak-security"')!==false &&
    strpos($v['portal'], "do_shortcode( '[pvdak_my_requests]' )")!==false &&
    strpos($v['portal'], "do_shortcode( '[pvdak_support_tickets]' )")!==false,
  'Consent links only go to published policies' =>
    strpos($v['register'],"pvdak_public_page_url( 'terms-of-use' )")!==false &&
    strpos($v['register'],"pvdak_public_page_url( 'privacy-policy' )")!==false &&
    strpos($v['contact'],"pvdak_public_page_url( 'privacy-policy' )")!==false,
  'Primary and footer links come from published Pages' =>
    substr_count($v['header'],'pvdak_default_navigation_links()')===2 &&
    strpos($v['footer'],'pvdak_default_navigation_links()')!==false &&
    strpos($v['footer'],"pvdak_public_page_url( 'account-login' )")!==false &&
    strpos($v['nav'],'pvdak_public_page_url( $slug )')!==false &&
    strpos($v['nav'],'get_term_link(')!==false,
  'Interaction script is locally scoped and layouts responsive' =>
    strpos($v['assets'],"'pvdak-account-interactions'")!==false &&
    strpos($v['assets'],'is_page( array( \'account-login\'')!==false &&
    strpos($v['css'],'@media(max-width:720px)')!==false &&
    strpos($v['javascript'],'fetch(')===false,
);
$n = 0;
foreach ( $checks as $label => $pass ) {
  if ( ! $pass ) { fwrite(STDERR,"ACCOUNT_UX_FAIL: $label\n"); exit(1); }
  $n++;
}
echo "PVDAK_ACCOUNT_EXPERIENCE_OK: $n source checks (no WP/browser tests)\n";
