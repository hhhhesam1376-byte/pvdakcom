<?php
/**
 * Branded registration uses the independent manager setting (default ON),
 * a published dedicated registration Page and maintenance-OFF as its gates.
 * The native WordPress registration endpoint is blocked from bypassing verification.
 * Applicant-selected profession is a self-report; NEVER grants lawyer rights.
 */
if (!defined('ABSPATH')) { exit; }
function pvdak_registration_open() {
    // Native public registration is not needed: PVDak has a nonce-protected,
    // rate-limited wp_insert_user handler which always assigns a client role.
    return ! ( function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled() ) &&
        '1' === (string) get_option('pvdak_registration_enabled', '1') &&
        function_exists('pvdak_public_template_page_url') &&
        (bool) pvdak_public_template_page_url('account-register','page-templates/pvdak-register.php');
}
/**
 * Public signup is deliberately opt-in. Expose its existing gate in the native
 * General Settings screen, alongside WordPress's "Anyone can register" switch.
 */
function pvdak_registration_setting_field() {
    $checked = '1' === (string)get_option('pvdak_registration_enabled','1');
    echo '<input type="hidden" name="pvdak_registration_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_registration_enabled" value="1" ' .
        checked($checked,true,false) . '> فعال‌سازی ثبت‌نام عمومی PVDak (ایمیل و پیامک)</label>';
    echo '<p class="description">ثبت‌نام PVDak از فرم اختصاصی امن انجام می‌شود؛ گزینه نام‌نویسی عمومی وردپرس لازم نیست. برای غیرفعال کردن ثبت‌نام این گزینه را خاموش کنید.</p>';
}
function pvdak_registration_register_general_setting() {
    register_setting('general','pvdak_registration_enabled',array(
        'type'=>'string','default'=>'1',
        'sanitize_callback'=>static function($value) {
            return is_scalar($value) && '1'===(string)$value ? '1' : '0';
        }
    ));
    add_settings_field('pvdak_registration_enabled','ثبت‌نام PVDak',
        'pvdak_registration_setting_field','general','default');
}
add_action('admin_init','pvdak_registration_register_general_setting');

/**
 * The native WordPress register endpoint must not bypass PVDak's own email
 * verification and unprivileged-role policy. Redirect it and fail closed.
 * Manager-created WordPress accounts and our wp_insert_user path are unaffected.
 */
function pvdak_native_register_redirect() {
    if (is_user_logged_in()) { return; }
    wp_safe_redirect(pvdak_registration_result_url());
    exit;
}
add_action('login_form_register','pvdak_native_register_redirect');
function pvdak_native_registration_guard($errors,$login,$email) {
    if (is_wp_error($errors)) {
        $errors->add('pvdak_branded_signup_required','برای ثبت‌نام از فرم تأیید ایمیل PVDak استفاده کنید.');
    }
    return $errors;
}
add_filter('registration_errors','pvdak_native_registration_guard',10,3);

function pvdak_registration_result_url() {
    return pvdak_public_template_page_url('account-register','page-templates/pvdak-register.php')
        ?: pvdak_auth_login_url();
}

/**
 * D14: count every submission from the originating server-side IP, including
 * invalid fields and honeypot hits. The transient key stores only a salted
 * HMAC, never the IP or an email address.
 */
function pvdak_registration_rate_key($scope) {
    $remote = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    return 'pvdak_reg_' . substr(hash_hmac('sha256', $scope . '|' . $remote, wp_salt('nonce')), 0, 40);
}
function pvdak_registration_rate_exceeded($scope, $limit, $seconds) {
    $key = pvdak_registration_rate_key($scope);
    $count = max(0, (int) get_transient($key));
    if ($count >= $limit) { return true; }
    set_transient($key, min($limit, $count + 1), $seconds);
    return false;
}
function pvdak_register_submit() {
    if (!pvdak_registration_open()) {
        wp_safe_redirect(add_query_arg('state','unavailable',pvdak_registration_result_url()));
        exit;
    }
    if ('POST' !== ($_SERVER['REQUEST_METHOD']??'')) { wp_die('Method not allowed','',array('response'=>405)); }
    check_admin_referer('pvdak_register','_pvdak_register_nonce');
    $url=pvdak_registration_result_url();
    // Count before validation: rotating emails or malformed requests cannot evade the IP ceiling.
    if (pvdak_registration_rate_exceeded('signup', 12, 15 * MINUTE_IN_SECONDS)) {
        wp_safe_redirect(add_query_arg('state','slow',$url));exit;
    }
    if (!empty($_POST['register_site'])) { wp_safe_redirect(add_query_arg('state','received',$url));exit; }
    $values=array();
    foreach(array('register_username','register_email','register_password','register_role') as $field){
        $raw=wp_unslash($_POST[$field]??'');
        $values[$field]=is_string($raw)?$raw:'';
    }
    $username=sanitize_user($values['register_username'],true);
    $email=sanitize_email($values['register_email']);
    $password=$values['register_password'];
    $claim=sanitize_key($values['register_role']);
    $allowed=array('client','lawyer','trainee','student');
    $rate='pvdak_signup_'.substr(hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'').'|'.$email,wp_salt('nonce')),0,30);
    if (get_transient($rate)) { wp_safe_redirect(add_query_arg('state','slow',$url));exit; }
    if (!$username || strlen($username)<3 || strlen($username)>50 ||
        !validate_username($username) || !is_email($email) || strlen($email)>254 ||
        pvdak_public_text_length($password)<12 || pvdak_public_text_length($password)>256 ||
        strlen($password)>1024 || !in_array($claim,$allowed,true) ||
        !in_array($_POST['register_consent']??null,array('on','1'),true) ||
        username_exists($username) || email_exists($email)) {
        wp_safe_redirect(add_query_arg('state','invalid',$url));exit;
    }
    set_transient($rate,1,180);
    // A professional claim does not create a verified professional account.
    $role=get_role('pvdak_client')?'pvdak_client':'subscriber';
    $user=wp_insert_user(array('user_login'=>$username,'user_email'=>$email,
        'user_pass'=>$password,'role'=>$role,'display_name'=>$username));
    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('state','invalid',$url));exit;
    }
    update_user_meta($user,'_pvdak_self_reported_category',$claim);
    update_user_meta($user,'_pvdak_email_verification_pending','1');
    // Do not tell the applicant to look for an email if wp_mail failed.
    // Keep the pending account and offer the existing rate-limited resend form.
    $mailed=pvdak_email_verification_issue($user);
    wp_safe_redirect(add_query_arg('state',$mailed?'received':'email_issue',$url));exit;
}
add_action('admin_post_nopriv_pvdak_register','pvdak_register_submit');
add_action('admin_post_pvdak_register','pvdak_register_submit');
