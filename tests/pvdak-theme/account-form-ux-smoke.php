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
  'register-ui' => 'page-templates/pvdak-register.php',
  'verification' => 'inc/pvdak-email-verification.php',
  'readiness' => 'inc/pvdak-home-readiness.php',

);
$s = array();
foreach ( $paths as $name => $path ) {
  if ( ! is_readable( $root . '/' . $path ) ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: missing $path\n" ); exit( 2 ); }
  $s[ $name ] = file_get_contents( $root . '/' . $path );
}
$checks = array(
  'Private profile allows name editing but not role, email or owner changes' =>
    strpos($s['portal'],'name="first_name"')!==false &&
    strpos($s['portal'],'name="last_name"')!==false &&
    strpos($s['tools'],"array( 'first_name', 'last_name' )")!==false &&
    strpos($s['tools'],"'ID' => \$id, 'display_name' => \$value")!==false &&
    strpos($s['tools'],"array_key_exists( \$field, \$_POST )")!==false &&
    strpos($s['script'],'#pvdak-first-name, #pvdak-last-name')!==false,
  'Password recovery link avoids mismatched Pages and SMS-only emails' =>
    strpos($s['portal'],"pvdak_account_tool_url( 'account-recovery' )")!==false &&
    strpos($s['portal'],"is_email( (string) \$user->user_email )")!==false,
  'Applicants can reach a real professional application without auto-approval' =>
    strpos($s['portal'],"'_pvdak_self_reported_category'")!==false &&
    strpos($s['portal'],'pvdak_core_member_intake_open()')!==false &&
    strpos($s['portal'],"'join-legal-community','pvdak_professional_application'")!==false &&
    strpos($s['portal'],'انتخاب نوع حساب در ثبت‌نام، به معنی تأیید صلاحیت')!==false,
  'Email send failures and closed-signup resend are handled separately' =>
    strpos($s['register'],'$mailed=pvdak_email_verification_issue($user)')!==false &&
    strpos($s['register-ui'],"'email_issue'=>'")!==false &&
    strpos($s['register-ui'],'<details class="pvdak-register-resend">')!==false &&
    strpos($s['verification'],"if (!pvdak_registration_open())")===false,
  'Homepage readiness checks live preview and complete account routes' =>
    strpos($s['readiness'],"'account-register' => 'page-templates/pvdak-register.php'")!==false &&
    strpos($s['readiness'],"'account-recovery' => 'page-templates/pvdak-recovery.php'")!==false &&
    strpos($s['readiness'],"'دسترسی نمایشی بازدیدکننده بدون تعمیرات', ! $maintenance_on")!==false,
  'Login has only password/SMS tabs; signup keeps its separate email/SMS tabs' =>
    strpos($s['login'],'name="action" value="pvdak_login"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="password"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="sms-login"')!==false &&
    substr_count($s['login'],'data-pvdak-auth-tab=')===2 &&
    strpos($s['login'],'data-pvdak-auth-pane="password"')!==false &&
    strpos($s['login'],'data-pvdak-auth-pane="sms-login"')!==false &&
    strpos($s['login'],'role="tabpanel"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_sms_send"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_sms_verify"')!==false &&
    strpos($s['login'],'ثبت‌نام در PVDak')!==false &&
    strpos($s['login'],'<details class="pvdak-login-sms-panel"')===false &&
    strpos($s['login'],'data-pvdak-auth-tab="register-email"')===false &&
    strpos($s['login'],'چهار مسیر در یک حساب کاربری وردپرس')===false &&
    strpos($s['register-ui'],'data-pvdak-auth-tab="register-email"')!==false &&
    strpos($s['register-ui'],'data-pvdak-auth-tab="sms-register"')!==false &&
    strpos($s['register-ui'],'name="action" value="pvdak_register"')!==false &&
    strpos($s['register-ui'],'name="action" value="pvdak_sms_send"')!==false &&
    strpos($s['script'],"var defaultMode = modes.indexOf('password') !== -1 ? 'password' : modes[0];")!==false,
  'Registration uses branded controlled email verification, without native WP signup' =>
    strpos($s['register'],"get_option('pvdak_registration_enabled', '1')")!==false &&
    strpos($s['register'],'pvdak_public_template_page_url(')!==false &&
    strpos($s['register'],"check_admin_referer('pvdak_register'")!==false &&
    strpos($s['register'],'pvdak_email_verification_issue($user)')!==false,
  'Native WordPress public registration cannot bypass PVDak email verification' =>
    strpos($s['register'],"add_action('login_form_register','pvdak_native_register_redirect')")!==false &&
    strpos($s['register'],"add_filter('registration_errors','pvdak_native_registration_guard'")!==false &&
    strpos($s['register'],"'pvdak_branded_signup_required'")!==false &&
    strpos($s['register'],"pvdak_registration_result_url()")!==false,
  'SMS provider must explicitly opt in before OTP form works' =>
    strpos($s['sms'],"apply_filters('pvdak_sms_auth_enabled', false)")!==false &&
    strpos($s['sms'],"has_filter('pvdak_sms_otp_deliver')")!==false &&
    strpos($s['sms'],'true!==$delivered')!==false &&
    strpos($s['register-ui'],'if($sms_ready&&$registration_open)')!==false,
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
    strpos($s['sms'],"user_can(\$user,'manage_options')")!==false,
  'Email account can bind verified phone before using SMS sign-in' =>
    strpos($s['portal'],'name="sms_intent" value="bind"')!==false &&
    strpos($s['sms'],"(int)(\$challenge['user_id']??0)!==get_current_user_id()")!==false &&
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
