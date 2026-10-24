<?php
/** Source-only account UX regression; no WordPress bootstrap, users or DB. */
$root = dirname( __DIR__, 2 );
$paths = array(
  'portal'=>'page-templates/pvdak-account.php',
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
  'Account sidebar and personal shortcuts' =>
    strpos($v['portal'],'pvdak-account-sidebar')!==false &&
    strpos($v['portal'],'pvdak_public_page_url( \'account-profile\' )')!==false &&
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
    strpos($v['profile'], 'pvdak-profile-redesign') !== false &&
    strpos($v['profile'], 'pvdak-profile-hero') !== false &&
    strpos($v['profile'], 'pvdak-profile-editor') !== false &&
    strpos($v['profile'], 'pvdak-profile-nav') !== false &&
    strpos($v['profile'], 'wp_logout_url(') !== false &&
    strpos($v['profile'], 'data-pvdak-profile-form') !== false &&
    strpos($v['profile'], 'data-pvdak-profile-save') !== false,
  'Profile styling is scoped and responsive with no remote font dependency' =>
    strpos($v['css'], 'body.pvdak-profile-screen') !== false &&
    strpos($v['css'], '.pvdak-profile-workspace{') !== false &&
    strpos($v['css'], '.pvdak-profile-rail{') !== false &&
    strpos($v['css'], '@media(max-width:540px)') !== false,
  'Profile hero artwork is local and references the legal image' =>
    is_file($root . '/assets/icons/pvdak-profile-hero.svg') &&
    strpos($v['profile'], 'pvdak-profile-hero-photo')!==false &&
    strpos($v['profile'], '/assets/icons/pvdak-profile-hero.svg')!==false,
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
    strpos($v['profile'], 'pvdak-profile-shield-art')!==false &&
    strpos($v['profile'], 'data-pvdak-profile-save')!==false &&
    strpos($v['profile'], 'wp_nonce_field(')!==false,
  'Profile keeps nonce and provides account return path' =>
    strpos($v['profile'],'pvdak_profile_save')!==false &&
    strpos($v['profile'],'pvdak_auth_account_url()')!==false &&
    strpos($v['profile'],'pvdak-profile-details')!==false,
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
