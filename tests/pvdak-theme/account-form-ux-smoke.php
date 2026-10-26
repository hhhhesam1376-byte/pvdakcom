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
  'admin-management' => 'inc/pvdak-readiness-admin.php',
  'home-editor' => 'inc/pvdak-home-acf.php',

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
    strpos($s['login'],"'email_issue'=>'")!==false &&
    strpos($s['login'],'<details class="pvdak-register-resend">')!==false &&
    strpos($s['verification'],"if (!pvdak_registration_open())")===false,
  'Homepage readiness checks live preview and complete account routes' =>
    strpos($s['readiness'],"'account-register' => 'page-templates/pvdak-register.php'")===false &&
    strpos($s['readiness'],"'account-recovery' => 'page-templates/pvdak-recovery.php'")!==false &&
    strpos($s['readiness'],"'دسترسی نمایشی بازدیدکننده بدون تعمیرات', ! $maintenance_on")!==false,
  'Login screen owns both sign-in and sign-up with email and SMS modes' =>
    strpos($s['login'],'name="action" value="pvdak_login"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="password"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="sms-login"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="register-email"')!==false &&
    strpos($s['login'],'data-pvdak-auth-tab="sms-register"')!==false &&
    substr_count($s['login'],'data-pvdak-auth-tab=')===4 &&
    strpos($s['login'],'data-pvdak-auth-group="register"')!==false &&
    strpos($s['login'],'data-pvdak-auth-group="login"')!==false &&
    strpos($s['login'],'data-pvdak-auth-pane="register-email"')!==false &&
    strpos($s['login'],'data-pvdak-auth-pane="sms-register"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_register"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_sms_send"')!==false &&
    strpos($s['login'],'name="action" value="pvdak_sms_verify"')!==false &&
    strpos($s['login'],'ثبت‌نام در PVDak')!==false &&
    strpos($s['login'],'data-pvdak-auth-go="register-email"')!==false &&
    strpos($s['script'],"link.getAttribute('data-pvdak-auth-go')")!==false &&
    strpos($s['register-ui'],'pvdak_registration_result_url()')!==false &&
    strpos($s['register-ui'],'wp_safe_redirect(add_query_arg($fields, $target), 302)')!==false &&
    strpos($s['script'],"var defaultMode = modes.indexOf('password') !== -1 ? 'password' : modes[0];")!==false,
  'Registration uses branded controlled email verification, without native WP signup' =>
    strpos($s['register'],"get_option('pvdak_registration_enabled', '0')")!==false &&
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
    strpos($s['login'],'if($sms_ready&&$registration_open)')!==false,
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
  'Profile has a single clear save action without confusing reset' =>
    strpos( $s['portal'], 'data-pvdak-profile-save' ) !== false &&
    strpos( $s['portal'], 'type="reset"' ) === false,
  'Profile unsaved change status' => strpos( $s['portal'], 'data-pvdak-profile-status' ) !== false,
  'Contact result accessible focus' => strpos( $s['contact'], 'id="pvdak-contact-result" tabindex="-1"' ) !== false,
  'Contact textarea counter' => strpos( $s['contact'], 'data-pvdak-count-for="pvdak-message"' ) !== false,
  'Contact email browser cap' => strpos( $s['contact'], 'maxlength="254"' ) !== false,
  'Recovery confirmation hint' => strpos( $s['recovery'], 'id="pvdak-password-match"' ) !== false,
  'Recovery client validation marker' => strpos( $s['recovery'], 'data-pvdak-recovery-change' ) !== false,
  'Login bounded identity' => strpos( $s['login'], 'maxlength="250"' ) !== false,
  'Login error focus target' =>
    strpos( $s['login'], 'id="pvdak-login-result"' ) !== false &&
    strpos( $s['login'], 'pvdak-login-error-notice' ) !== false &&
    strpos( file_get_contents($root.'/inc/pvdak-auth.php'), "'pvdak_login_error'=>'invalid'" ) !== false &&
    strpos( $s['script'], "params.has('pvdak_login_error')" ) !== false,
  'Dashboard fallback avoids activation claim' => strpos( $s['portal'], 'حساب شما وارد شده است' ) !== false &&
    strpos( $s['portal'], 'اطلاعات پنل فعلاً در دسترس نیست' ) !== false &&
    strpos( $s['portal'], "do_shortcode( '[pvdak_account_dashboard]' )" ) !== false,
  'Account panels offer return navigation' => substr_count( $s['portal'], 'pvdak-panel-return' ) === 2,
  'Account scripts available on profile and contact' => strpos( $s['functions'], "'account-profile', 'contact-us'" ) !== false,
  'Active navigation recognizes JS location marker' => strpos( $s['css'], '.pvdak-account-menu a[aria-current]{' ) !== false,
  'SMS registration redirect is visible without JavaScript' =>
    strpos( $s['login'], "'sms-register'===$auth_value" ) !== false &&
    strpos( $s['login'], 'data-pvdak-auth-pane="sms-register" <?php echo ($register_selected' ) !== false,
  'Both signup methods link to the published legal policies' =>
    substr_count( $s['login'], 'esc_url($terms)' ) === 2 &&
    substr_count( $s['login'], 'esc_url($privacy)' ) === 2,
  'Server feedback is focusable and distinguishes SMS from email errors' =>
    strpos( $s['login'], 'id="pvdak-register-email-result" tabindex="-1"' ) !== false &&
    strpos( $s['login'], 'id="pvdak-register-sms-result" tabindex="-1"' ) !== false &&
    strpos( $s['login'], 'id="pvdak-sms-login-result" tabindex="-1"' ) !== false &&
    strpos( $s['recovery'], 'id="pvdak-recovery-result" tabindex="-1"' ) !== false &&
    strpos( $s['script'], "params.has('sms_status')" ) !== false,
  'D07 SMS feedback stays on correct pane even without JavaScript' =>
    strpos( $s['login'], "'sms-login'===\$auth_value" ) !== false &&
    strpos( $s['login'], "'sms-register'===\$auth_value" ) !== false &&
    strpos( $s['script'], "query.has('sms_status') && smsMode" ) !== false,
  'D07 hidden feedback never steals keyboard focus' =>
    strpos( $s['script'], "!notice.closest('[hidden]')" ) !== false,
  'D13 newly created signup accounts are rolled back if verification metadata is missing' =>
    strpos($s['register'],'pvdak_registration_rollback_user($user)')!==false &&
    strpos($s['register'],"'_pvdak_email_verification_pending',true")!==false,
  'D13 verified email requires a persisted positive marker' =>
    strpos($s['verification'],"'_pvdak_email_verified_at'")!==false &&
    strpos($s['verification'],"delete_user_meta($user_id, '_pvdak_email_verification_pending')")!==false,
  'D13 concurrent verification cannot erase a successfully confirmed mailbox' =>
    strpos($s['verification'], "if (!\$valid && '1' === (string)get_user_meta(\$user_id, '_pvdak_email_verification_pending', true))") !== false &&
    strpos($s['verification'], "delete_user_meta(\$user_id, '_pvdak_email_verified_at')") !== false &&
    strpos($s['verification'], "delete_user_meta(\$user_id, '_pvdak_email_verified_address')") !== false,
  'D14 SMS cannot verify with a disabled provider or claim ambiguous phone' =>
    strpos($s['sms'],"if (!pvdak_sms_auth_ready())")!==false &&
    strpos($s['sms'],'pvdak_sms_phone_claimed($phone)')!==false &&
    strpos($s['sms'],'pvdak_registration_rollback_user($created)')!==false,
  'D07 request filters remain on the existing Core account panel' =>
    strpos($s['functions'], "class=\"pvdak-core-request-filters\"")!==false &&
    strpos($s['functions'], "pvdak_auth_account_url() . '#pvdak-requests'")!==false &&
    strpos($s['script'], "form.pvdak-core-request-filters")!==false &&
    strpos($s['script'], "url.hash = 'pvdak-requests'")!==false,
  'D07 support overview does not mix with legal requests' =>
    strpos($s['portal'], "function_exists( 'pvdak_support_recent_overview' )")!==false &&
    strpos($s['portal'], "pvdak_support_recent_overview()")!==false,
  'D07 password login rejects huge submissions and bad result types' =>
    strpos(file_get_contents($root.'/inc/pvdak-auth.php'),'strlen($pass)>1024')!==false &&
    strpos(file_get_contents($root.'/inc/pvdak-auth.php'),'!($user instanceof WP_User)')!==false,
  'No remote JS calls or credential storage' => strpos( $s['script'], 'fetch(' ) === false && strpos( $s['script'], 'localStorage' ) === false,
  'Prevent repeated valid submissions' => strpos( $s['script'], 'form.dataset.pvdakSubmitting' ) !== false,
  'D07 support deep link resolves to the unified panel' => strpos( $s['portal'], 'id="pvdak-support" class="pvdak-account-panel' ) !== false,
  'D07 provider-disabled buttons remain disabled on pageshow' => strpos( $s['script'], 'button.dataset.pvdakDisabledBySubmit' ) !== false && strpos( $s['script'], "button.dataset.pvdakDisabledBySubmit === '1'" ) !== false,
  'D07 auth tabs follow restored URL on BFCache' => strpos( $s['script'], "if (!event.persisted) return;" ) !== false && strpos( $s['script'], 'showAuthMode(mode, false, false)' ) !== false,
  'Password match is validated before submission' => strpos( $s['script'], 'function validateRecovery()' ) !== false,

  'D13 unused reset links expire after at most two hours without custom token storage' =>
    strpos($s['tools'], "function pvdak_recovery_reset_key_lifetime( \$seconds )") !== false &&
    strpos($s['tools'], "min( (int) \$seconds, 2 * HOUR_IN_SECONDS )") !== false &&
    strpos($s['tools'], "add_filter( 'password_reset_expiration', 'pvdak_recovery_reset_key_lifetime', 20 )") !== false &&
    strpos($s['tools'], 'reset_password( $user, $password );') !== false &&
    strpos($s['tools'], 'is_wp_error( check_password_reset_key( $key, $login ) )') !== false,
  'D13 reset request is a state of the canonical login Page' =>
    strpos($s['tools'], "if ( 'account-recovery' === \$slug )")!==false &&
    strpos($s['tools'], "add_query_arg( 'auth', 'recover', \$login )")!==false &&
    strpos($s['login'], 'data-pvdak-auth-pane="recover"')!==false &&
    strpos($s['login'], 'data-pvdak-auth-pane="recover-change"')!==false &&
    strpos($s['login'], 'name="action" value="pvdak_recovery_request"')!==false &&
    strpos($s['login'], 'name="action" value="pvdak_recovery_change"')!==false &&
    strpos($s['login'], 'check_password_reset_key($reset_key, $reset_login)')!==false,
  'D13 reset links now land inside login without breaking old emailed URLs' =>
    strpos($s['tools'], "'auth' => 'recover-change', 'reset' => '1'")!==false &&
    strpos($s['recovery'], 'wp_safe_redirect(add_query_arg($args, $canonical), 302)')!==false &&
    strpos($s['tools'], "'auth' => 'password', 'status' => 'reset'")!==false,
  'D13 branded RTL HTML email retains text alternative and mail hook cleanup' =>
    strpos($s['tools'], 'lang="fa" dir="rtl"')!==false &&
    strpos($s['tools'], 'بازیابی رمز عبور PVDak')!==false &&
    strpos($s['tools'], 'pvdak_recovery_email_plain_text')!==false &&
    strpos($s['tools'], "'wp_mail_content_type', \$type_filter")!==false &&
    strpos($s['tools'], "remove_action( 'phpmailer_init', \$mail_alt, 30 )")!==false,
  'D13 recovery JavaScript keeps 2 login methods and strips reset secrets' =>
    strpos($s['script'], "modes.push('recover')")!==false &&
    strpos($s['script'], "modes.push('recover-change')")!==false &&
    strpos($s['script'], "link.searchParams.delete('status')")!==false &&
    strpos($s['script'], "'reset', 'key', 'login'")!==false &&
    strpos($s['script'], 'isRecovery ?')!==false,
  'D13 recovery progress and positive completion message stay inside login' =>
    strpos($s['login'], '۱. دریافت لینک')!==false &&
    strpos($s['login'], '۲. رمز تازه')!==false &&
    strpos($s['login'], '۳. ورود دوباره')!==false &&
    strpos($s['login'], 'رمز عبور با موفقیت تغییر کرد')!==false &&
    strpos($s['login'], 'مرحله اول انجام شد!')!==false,
  'D13 recovery URLs are not cached or sent as third-party referrers' =>
    strpos($s['tools'], "if ( ! defined( 'DONOTCACHEPAGE' ) )") !== false &&
    strpos($s['tools'], "header( 'Referrer-Policy: no-referrer', true );") !== false &&
    strpos($s['tools'], "if ( is_page( array( 'account-login', 'account-recovery' ) ) )") !== false,
  'D13 reset cannot falsely claim success without persisted hash and spent key' =>
    strpos($s['tools'],'reset_password( $user, $password );')!==false &&
    strpos($s['tools'],'$fresh = get_userdata( $user->ID );')!==false &&
    strpos($s['tools'],'wp_check_password( $password, $fresh->user_pass, $fresh->ID )')!==false &&
    strpos($s['tools'],'is_wp_error( check_password_reset_key( $key, $login ) )')!==false &&
    strpos($s['tools'],"'status', 'failed'")!==false &&
    strpos($s['recovery'],"if($status==='failed')")!==false,
  'D13 reset refuses all-whitespace passwords' =>
    strpos($s['tools'],"'' === trim( $password )")!==false,

  'D07 recovery token and password bounded on GET, POST and in the form' =>
    strpos($s['recovery'],'strlen($key)<=64')!==false &&
    strpos($s['recovery'],'name="new_password" minlength="12" maxlength="256"')!==false &&
    strpos($s['tools'],'strlen( $key ) > 64')!==false &&
    strpos($s['tools'],'pvdak_public_text_length( $password ) > 256')!==false,
  'D13 verification requires a published template and real persistence' =>
    strpos($s['verification'],"pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php')")!==false &&
    strpos($s['verification'],"is_page_template('page-templates/pvdak-login.php')")!==false &&
    strpos($s['verification'],'(bool)delete_user_meta($user_id')!==false &&
    strpos($s['verification'],'pvdak_registration_result_url()')!==false,
  'D14 signup/contact explicit consent and Unicode password length' =>
    strpos($s['register'],'pvdak_public_text_length($password)<12')!==false &&
    strpos($s['register'],"!in_array(\$_POST['register_consent']??null")!==false &&
    strpos($s['public'],"!in_array(\$_POST['contact_consent']??null")!==false,
  'D31 employer admin hub preserves editor capabilities and existing readiness URL' =>
    strpos($s['admin-management'],"add_menu_page(")!==false &&
    strpos($s['admin-management'],"'pvdak-management'")!==false &&
    strpos($s['admin-management'],"'edit_pages'")!==false &&
    strpos($s['admin-management'],"add_management_page('آمادگی راه‌اندازی PVDak'")!==false &&
    strpos($s['admin-management'],"current_user_can('manage_options')")!==false,
  'D31 comments are hidden from primary menu but still available for moderators' =>
    strpos($s['admin-management'],"remove_menu_page('edit-comments.php')")!==false &&
    strpos($s['admin-management'],"'moderate_comments', 'edit-comments.php'")!==false &&
    strpos($s['admin-management'],"current_user_can('moderate_comments')")!==false &&
    strpos($s['admin-management'],"remove_meta_box('dashboard_activity'")!==false,
  'D31 homepage editor is grouped and technical setup remains administrator-only' =>
    strpos($s['home-editor'],"add_submenu_page(")!==false &&
    strpos($s['home-editor'],"'pvdak-management'")!==false &&
    strpos($s['home-editor'],"current_user_can( 'manage_options' )")!==false &&
    strpos($s['home-editor'],"<details style=")!==false &&
    strpos($s['home-editor'],"pvdak_editor_bootstrap_card()")!==false,
  'D31 client hub groups real workflows without bypassing Core or moderation permissions' =>
    strpos($s['admin-management'], "function pvdak_management_link_available( \$link )") !== false &&
    strpos($s['admin-management'], "post_type_exists( \$type )") !== false &&
    strpos($s['admin-management'], "current_user_can( \$object->cap->edit_posts )") !== false &&
    strpos($s['admin-management'], "'edit.php?post_type=pvdak_professional'") !== false &&
    strpos($s['admin-management'], "'moderate_comments', 'edit-comments.php?comment_status=moderated'") !== false &&
    strpos($s['admin-management'], "'محتوا و ظاهر سایت'") !== false &&
    strpos($s['admin-management'], "'درخواست‌ها و گفت‌وگوها'") !== false &&
    strpos($s['admin-management'], "esc_url( home_url( '/' ) )") !== false,
  'D13/D14 recovery/contact IP ceilings and published contact route' =>
    strpos($s['tools'],"pvdak_registration_rate_exceeded( 'recovery-request', 12")!==false &&
    strpos($s['public'],"pvdak_registration_rate_exceeded('contact',12")!==false &&
    strpos($s['public'],"pvdak_public_template_page_url('contact-us','page-templates/pvdak-contact.php')")!==false,

);
foreach ( $checks as $label => $pass ) {
  if ( ! $pass ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: $label\n" ); exit( 1 ); }
}
echo "PVDAK_ACCOUNT_FORM_091_OK: " . count( $checks ) . " source checks; no WordPress/browser tests.\n";
