<?php
/**
 * Batch 091 source-only regression for account, profile, recovery and contact.
 * No WordPress bootstrap, requests, users, emails, or DB writes.
 */
$root = dirname( __DIR__, 2 );
$paths = array(
  'public' => 'inc/pvdak-public-pages.php',
  'tools' => 'inc/pvdak-account-tools.php',
  'profile' => 'page-templates/pvdak-profile.php',
  'contact' => 'page-templates/pvdak-contact.php',
  'recovery' => 'page-templates/pvdak-recovery.php',
  'login' => 'page-templates/pvdak-login.php',
  'portal' => 'page-templates/pvdak-account.php',
  'script' => 'assets/js/pvdak-account.js',
  'functions' => 'functions.php',
  'css' => 'style.css',
  'sms' => 'inc/pvdak-sms-auth.php',
  'register' => 'inc/pvdak-registration.php',

);
$s = array();
foreach ( $paths as $name => $path ) {
  if ( ! is_readable( $root . '/' . $path ) ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: missing $path\n" ); exit( 2 ); }
  $s[ $name ] = file_get_contents( $root . '/' . $path );
}
$checks = array(
  'Four visible PVDak auth paths use existing WordPress handlers' =>
    strpos($s['login'],'data-pvdak-auth-tab="password"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="sms-login"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="register-email"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="sms-register"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_login"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_register"')!==false,
  'Registration retains admin opt-in and native email verification' =>
    strpos($s['register'],"get_option('pvdak_registration_enabled', '0')")!==false &&
    strpos($s['register'],"get_option('users_can_register', false)")!==false &&
    strpos($s['register'],"check_admin_referer('pvdak_register'")!==false &&
    strpos($s['register'],'pvdak_email_verification_issue($user)')!==false,
  'SMS provider must explicitly opt in before OTP form works' =>
    strpos($s['sms'],"apply_filters('pvdak_sms_auth_enabled', false)")!==false &&
    strpos($s['sms'],"has_filter('pvdak_sms_otp_deliver')")!==false &&
    strpos($s['sms'],"true!==$delivered")!==false &&
    strpos($s['login'],'if (!$sms_ready)')!==false,
  'SMS OTP uses expiry, attempt ceiling, nonces and one-time deletion' =>
    strpos($s['sms'],"check_admin_referer('pvdak_sms_send'")!==false &&
    strpos($s['sms'],"check_admin_referer('pvdak_sms_verify'")!==false &&
    strpos($s['sms'],'wp_hash_password($code)')!==false &&
    strpos($s['sms'],'wp_check_password($code,$challenge')!==false &&
    strpos($s['sms'],'$challenge[\'tries\']>=5')!==false &&
    strpos($s['sms'],'delete_transient($key)')!==false,
  'SMS signup never assigns lawyer roles from self-report' =>
    strpos($s['sms'],"'role'=>get_role('pvdak_client')?'pvdak_client':'subscriber'")!==false &&
    strpos($s['sms'],"'_pvdak_self_reported_category'")!==false &&
    strpos($s['sms'],"user_can($user,'manage_options')")!==false,
  'Email account can bind verified phone before using SMS sign-in' =>
    strpos($s['portal'],'name="sms_intent" value="bind"')!==false &&
    strpos($s['sms'],"(int)($challenge['user_id']??0)!==get_current_user_id()")!==false &&
    strpos($s['sms'],"'_pvdak_phone_verified'")!==false,
  'Unicode helper counts all characters' => strpos( $s['public'], "preg_match_all( '/[\\s\\S]/u'" ) !== false,
  'Contact name Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($name)>100' ) !== false,
  'Contact message minimum Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($msg)<10' ) !== false,
  'Contact message maximum Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($msg)>3500' ) !== false,
  'Contact email bounded server-side' => strpos( $s['public'], 'strlen($email)>254' ) !== false,
  'Profile Unicode length bounded server-side' => strpos( $s['tools'], 'pvdak_public_text_length( $value ) > 120' ) !== false,
  'Profile no-op avoids writes' => strpos( $s['tools'], "'status', 'unchanged'" ) !== false,
  'Recovery minimum Unicode length' => strpos( $s['tools'], 'pvdak_public_text_length( $password ) < 12' ) !== false,
  'Legacy profile URL preserves current form state via redirect' =>
    strpos( $s['profile'], "wp_safe_redirect( \$target . '#pvdak-profile', 302 )" ) !== false &&
    strpos( $s['portal'], 'id="pvdak-profile"' ) !== false,
  'Profile status variants' => strpos( $s['portal'], "if('unchanged'===" ) !== false,
  'Profile counter and hint' => strpos( $s['portal'], 'data-pvdak-count-for="account-display-name"' ) !== false,
  'Profile reset control' => strpos( $s['portal'], 'type="reset"' ) !== false,
  'Profile unsaved change status' => strpos( $s['portal'], 'data-pvdak-profile-status' ) !== false,
  'Contact result accessible focus' => strpos( $s['contact'], 'id="pvdak-contact-result" tabindex="-1"' ) !== false,
  'Contact textarea counter' => strpos( $s['contact'], 'data-pvdak-count-for="pvdak-message"' ) !== false,
  'Contact email browser cap' => strpos( $s['contact'], 'maxlength="254"' ) !== false,
  'Recovery confirmation hint' => strpos( $s['recovery'], 'id="pvdak-password-match"' ) !== false,
  'Recovery client validation marker' => strpos( $s['recovery'], 'data-pvdak-recovery-change' ) !== false,
  'Login bounded identity' => strpos( $s['login'], 'maxlength="250"' ) !== false,
  'Login error focus target' => strpos( $s['login'], 'id="pvdak-login-result"' ) !== false,
  'Dashboard fallback avoids activation claim' => strpos( $s['portal'], 'حساب شما وارد شده است' ) !== false &&
    strpos( $s['portal'], 'اطلاعات پنل فعلاً در دسترس نیست' ) !== false &&
    strpos( $s['portal'], "do_shortcode( '[pvdak_account_dashboard]' )" ) !== false,
  'Account panels offer return navigation' => substr_count( $s['portal'], 'pvdak-panel-return' ) === 2,
  'Account scripts available on profile and contact' => strpos( $s['functions'], "'account-profile', 'contact-us'" ) !== false,
  'Active navigation recognizes JS location marker' => strpos( $s['css'], '.pvdak-account-menu a[aria-current]{' ) !== false,
  'No remote JS calls or credential storage' => strpos( $s['script'], 'fetch(' ) === false && strpos( $s['script'], 'localStorage' ) === false,
  'Prevent repeated valid submissions' => strpos( $s['script'], 'form.dataset.pvdakSubmitting' ) !== false,
  'Password match is validated before submission' => strpos( $s['script'], 'function validateRecovery()' ) !== false,
);
foreach ( $checks as $label => $pass ) {
  if ( ! $pass ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: $label\n" ); exit( 1 ); }
}
echo "PVDAK_ACCOUNT_FORM_091_OK: " . count( $checks ) . " source checks; no WordPress/browser tests.\n";
