<?php
/**
 * PVDak email verification for future opt-in registrations.
 * Uses a hashed single-use 24h token; never upgrades a self-declared
 * professional role. Public registration is governed by the independent
 * PVDak manager setting, published Page and maintenance gate.
 */
if (!defined('ABSPATH')) { exit; }

function pvdak_email_verification_issue($user_id) {
    $user = get_userdata(absint($user_id));
    if (!$user || '1' !== (string)get_user_meta($user->ID, '_pvdak_email_verification_pending', true)) {
        return false;
    }
    $token = wp_generate_password(48, false, false);
    // Do not email a broken link or a token that could not be persisted.
    $base = pvdak_public_template_page_url('account-register', 'page-templates/pvdak-register.php');
    if (!$base) { return false; }
    if (!update_user_meta($user->ID, '_pvdak_email_verification_token_hash', wp_hash_password($token))) {
        return false;
    }
    $expires = time() + DAY_IN_SECONDS;
    if (!update_user_meta($user->ID, '_pvdak_email_verification_expires', $expires) &&
        (int)get_user_meta($user->ID, '_pvdak_email_verification_expires', true) !== $expires) {
        return false;
    }
    $url = add_query_arg(array('pvdak_verify'=>'1', 'uid'=>$user->ID, 'token'=>$token), $base);
    $body = "برای تأیید ایمیل حساب PVDak، پیوند زیر را باز کنید:\n\n" .
        esc_url_raw($url) . "\n\nاین پیوند تا ۲۴ ساعت معتبر است. اگر ثبت‌نام نکرده‌اید، پیام را نادیده بگیرید.";
    return wp_mail($user->user_email, 'تأیید ایمیل حساب PVDak', $body);
}

/** Verify a token without showing its value in rendered pages or analytics links. */
function pvdak_email_verification_confirm() {
    if (!is_page('account-register') || !is_page_template('page-templates/pvdak-register.php') ||
        !isset($_GET['pvdak_verify'])) { return; }
    nocache_headers();
    $uid_raw = wp_unslash($_GET['uid'] ?? '');
    $token_raw = wp_unslash($_GET['token'] ?? '');
    $user_id = is_scalar($uid_raw) ? absint($uid_raw) : 0;
    $token = is_string($token_raw) ? $token_raw : '';
    $valid = false;
    if ($user_id && preg_match('/^[A-Za-z0-9]{48}$/D', $token)) {
        $hash = get_user_meta($user_id, '_pvdak_email_verification_token_hash', true);
        $expiry = absint(get_user_meta($user_id, '_pvdak_email_verification_expires', true));
        $valid = '1' === (string)get_user_meta($user_id, '_pvdak_email_verification_pending', true) &&
            is_string($hash) && $hash !== '' && $expiry >= time() &&
            (bool)wp_check_password($token, $hash, $user_id);
    }
    if ($valid) {
        // A failed DB update must not claim successful verification.
        $valid = (bool)delete_user_meta($user_id, '_pvdak_email_verification_pending');
        if ($valid) {
            delete_user_meta($user_id, '_pvdak_email_verification_token_hash');
            delete_user_meta($user_id, '_pvdak_email_verification_expires');
        }
    }
    $url = pvdak_registration_result_url();
    wp_safe_redirect(add_query_arg('state', $valid ? 'verified' : 'invalid_token', $url));
    exit;
}
add_action('template_redirect', 'pvdak_email_verification_confirm', 5);

/** Block login through both the PVDak form and standard WordPress login. */
function pvdak_email_verification_auth_gate($user, $password) {
    if ($user instanceof WP_User &&
        '1' === (string)get_user_meta($user->ID, '_pvdak_email_verification_pending', true)) {
        return new WP_Error('pvdak_unverified_email', 'ایمیل این حساب هنوز تأیید نشده است.');
    }
    return $user;
}
add_filter('wp_authenticate_user', 'pvdak_email_verification_auth_gate', 20, 2);

/** Anonymous resend endpoint has a generic reply to prevent email enumeration. */
function pvdak_email_verification_resend() {
    // Previously created pending accounts must be able to receive a fresh
    // verification link even after new registrations were temporarily closed.
    if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
        wp_die('Method not allowed', '', array('response'=>405));
    }
    check_admin_referer('pvdak_verify_resend', '_pvdak_verify_nonce');
    $raw = wp_unslash($_POST['verify_email'] ?? '');
    $email = is_string($raw) ? sanitize_email($raw) : '';
    $url = pvdak_registration_result_url();
    // D14: a rotating list of emails must not bypass the existing per-email cooldown.
    // Always return the same generic response, including on an IP-wide throttle.
    if (pvdak_registration_rate_exceeded('verify-resend', 12, HOUR_IN_SECONDS)) {
        wp_safe_redirect(add_query_arg('state', 'resent', $url));
        exit;
    }
    if (is_email($email)) {
        $rate = 'pvdak_verify_resend_' . substr(hash_hmac('sha256', strtolower($email), wp_salt('nonce')), 0, 32);
        if (!get_transient($rate)) {
            set_transient($rate, '1', 300);
            $user = get_user_by('email', $email);
            if ($user && '1' === (string)get_user_meta($user->ID, '_pvdak_email_verification_pending', true)) {
                pvdak_email_verification_issue($user->ID);
            }
        }
    }
    wp_safe_redirect(add_query_arg('state', 'resent', $url));
    exit;
}
add_action('admin_post_nopriv_pvdak_verify_resend', 'pvdak_email_verification_resend');
add_action('admin_post_pvdak_verify_resend', 'pvdak_email_verification_resend');

/** Admin-only list indicators. These are *not* legal credential approvals. */
function pvdak_verification_users_column($columns) {
    if (!current_user_can('list_users'))return $columns;
    $columns['pvdak_email_status']='تأیید ایمیل PVDak';
    $columns['pvdak_requested_role']='نوع همکاری اعلامی';
    return $columns;
}
add_filter('manage_users_columns','pvdak_verification_users_column');
function pvdak_verification_users_value($value,$column,$user_id) {
    if (!current_user_can('list_users'))return '';
    if('pvdak_email_status'===$column) {
        if('1'===(string)get_user_meta($user_id,'_pvdak_email_verification_pending',true))return 'در انتظار تأیید';
        $person=get_userdata(absint($user_id));
        if($person instanceof WP_User && ''===trim((string)$person->user_email) &&
            get_user_meta($user_id,'_pvdak_phone_verified',true)) {
            return 'شماره موبایل تأییدشده (ایمیل ثبت نشده)';
        }
        if($person instanceof WP_User && is_email($person->user_email) &&
            get_user_meta($user_id,'_pvdak_self_reported_category',true))return 'ایمیل تأییدشده';
        return 'کاربر موجود / نامشخص';
    }
    if('pvdak_requested_role'===$column){
        $raw=get_user_meta($user_id,'_pvdak_self_reported_category',true);
        $labels=array('client'=>'مراجعه‌کننده','lawyer'=>'متقاضی وکیل','trainee'=>'متقاضی کارآموز','student'=>'دانشجوی حقوق');
        return is_string($raw) && isset($labels[$raw]) ? $labels[$raw] : '—';
    }
    return $value;
}
add_filter('manage_users_custom_column','pvdak_verification_users_value',10,3);
