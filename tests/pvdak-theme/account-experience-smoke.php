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
  'Profile keeps nonce and provides account return path' =>
    strpos($v['profile'],'pvdak_profile_save')!==false &&
    strpos($v['profile'],'pvdak_auth_account_url()')!==false &&
    strpos($v['profile'],'pvdak-profile-details')!==false,
  'Consent links only go to published policies' =>
    strpos($v['register'],"pvdak_public_page_url( 'terms-of-use' )")!==false &&
    strpos($v['register'],"pvdak_public_page_url( 'privacy-policy' )")!==false &&
    strpos($v['contact'],"pvdak_public_page_url( 'privacy-policy' )")!==false,
  'Primary and footer links come from published Pages' =>
    strpos($v['header'],'pvdak_public_page_url( $slug )')!==false &&
    strpos($v['footer'],"pvdak_public_page_url( 'account-login' )")!==false,
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
