<?php
/**
 * PVDak email verification for future opt-in registrations.
 * Uses a hashed single-use 24h token; never upgrades a self-declared
 * professional role. Public registration is governed by the independent
 * PVDak manager setting, published Page and maintenance gate.
 */
if (!defined('ABSPATH')) { exit; }

/**
 * Standalone RTL HTML email: no external images, scripts or font downloads.
 * All dynamic values are escaped before placement in HTML/attributes.
 */
function pvdak_email_verification_html($display_name, $url) {
    $template = <<<'EMAIL_HTML'
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>تأیید ایمیل حساب PVDak</title>
</head>
<body dir="rtl" style="margin:0;padding:0;background-color:#f2f5f8;color:#172335;font-family:Tahoma,Arial,sans-serif;direction:rtl;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f2f5f8;border-collapse:collapse;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-collapse:separate;border-radius:16px;overflow:hidden;">
<tr><td align="right" dir="rtl" style="padding:26px 32px;background-color:#123d54;border-bottom:4px solid #e0a33b;">
  <p style="margin:0;color:#ffffff;font-size:24px;font-weight:bold;letter-spacing:1px;" dir="ltr">PVDak</p>
  <p style="margin:8px 0 0;color:#e6edf0;font-size:13px;line-height:1.8;">سامانه خدمات و ارتباطات حقوقی</p>
</td></tr>
<tr><td dir="rtl" align="right" style="padding:34px 32px 14px;">
  <p style="margin:0 0 12px;color:#ac7731;font-size:13px;font-weight:bold;">به جمع کاربران PVDak خوش آمدید</p>
  <h1 style="margin:0 0 18px;color:#123d54;font-size:23px;line-height:1.7;font-weight:bold;">حساب کاربری خود را تأیید کنید</h1>
  <p style="margin:0 0 16px;font-size:15px;line-height:2;color:#263b4c;">سلام <strong>{{NAME}}</strong>،</p>
  <p style="margin:0 0 20px;font-size:15px;line-height:2;color:#34495a;">از ثبت‌نام شما در PVDak سپاسگزاریم. برای اطمینان از اینکه این آدرس ایمیل متعلق به شماست و برای فعال‌کردن ورود به حساب، لطفاً روی دکمه زیر کلیک کنید.</p>
</td></tr>
<tr><td align="center" style="padding:10px 32px 24px;">
  <a href="{{VERIFY_URL}}" style="display:inline-block;background-color:#123d54;color:#ffffff;text-decoration:none;padding:15px 35px;border-radius:9px;font-size:16px;font-weight:bold;line-height:1.5;" target="_blank" rel="noopener">تأیید آدرس ایمیل و فعال‌سازی حساب</a>
  <p style="margin:18px 0 0;color:#6b7886;font-size:13px;line-height:1.9;">این لینک فقط یک‌بار قابل استفاده است و تا <strong>۲۴ ساعت</strong> اعتبار دارد.</p>
</td></tr>
<tr><td align="right" dir="rtl" style="padding:18px 32px 26px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f8fa;border-right:3px solid #e0a33b;border-collapse:separate;">
  <tr><td style="padding:16px 18px;">
    <p style="margin:0 0 10px;color:#233b4c;font-size:13px;line-height:1.9;"><strong>دکمه کار نمی‌کند؟</strong> لینک زیر را کپی کنید و مستقیماً در مرورگر باز کنید:</p>
    <p dir="ltr" style="margin:0;direction:ltr;text-align:left;overflow-wrap:anywhere;word-break:break-all;font-family:Arial,sans-serif;font-size:12px;line-height:1.9;"><a href="{{VERIFY_URL}}" style="color:#155d7c;text-decoration:underline;">{{PLAIN_URL}}</a></p>
  </td></tr></table>
  <p style="margin:20px 0 0;color:#657382;font-size:13px;line-height:1.9;">اگر شما در PVDak ثبت‌نام نکرده‌اید، نیازی به انجام هیچ کاری نیست؛ این پیام را نادیده بگیرید. برای امنیت حساب، این لینک را در اختیار دیگران قرار ندهید.</p>
</td></tr>
<tr><td align="center" dir="rtl" style="padding:20px 32px 25px;background-color:#f8fafc;border-top:1px solid #e5eaee;">
  <p style="margin:0 0 7px;color:#123d54;font-weight:bold;font-size:14px;">PVDak</p>
  <p style="margin:0;color:#74818e;font-size:12px;line-height:1.9;">این پیام به‌صورت خودکار و صرفاً برای تأیید آدرس ایمیل شما ارسال شده است.</p>
  <p style="margin:8px 0 0;font-size:12px;"><a href="{{SITE_URL}}" style="color:#155d7c;text-decoration:none;">ورود به وب‌سایت PVDak</a></p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
EMAIL_HTML;
    return strtr($template, array(
        '{{NAME}}' => esc_html($display_name),
        '{{VERIFY_URL}}' => esc_url($url),
        '{{PLAIN_URL}}' => esc_html(esc_url_raw($url)),
        '{{SITE_URL}}' => esc_url(home_url('/')),
    ));
}

/** Match HTML mail with a readable text-only version in clients that need it. */
function pvdak_email_verification_plain_text($display_name, $url) {
    return "سلام " . wp_strip_all_tags($display_name) . "\n\n" .
        "به PVDak خوش آمدید.\n" .
        "برای تأیید آدرس ایمیل و فعال‌سازی حساب روی این لینک کلیک کنید:\n" .
        esc_url_raw($url) . "\n\n" .
        "این لینک یک‌بار مصرف است و تا ۲۴ ساعت اعتبار دارد.\n" .
        "اگر شما ثبت‌نام نکرده‌اید، این پیام را نادیده بگیرید.\n";
}

function pvdak_email_verification_issue($user_id) {
    $user = get_userdata(absint($user_id));
    if (!$user || !is_email($user->user_email) || '1' !== (string)get_user_meta($user->ID, '_pvdak_email_verification_pending', true)) {
        return false;
    }
    $token = wp_generate_password(48, false, false);
    // Do not email a broken link or a token that could not be persisted.
    $base = pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php');
    if (!$base) { return false; }
    if (!update_user_meta($user->ID, '_pvdak_email_verification_token_hash', wp_hash_password($token))) {
        return false;
    }
    // Bind the issued token to the exact mailbox; a later address change must invalidate it.
    $issued_to = strtolower(trim($user->user_email));
    if (!update_user_meta($user->ID, '_pvdak_email_verification_issued_to', $issued_to) &&
        $issued_to !== get_user_meta($user->ID, '_pvdak_email_verification_issued_to', true)) {
        delete_user_meta($user->ID, '_pvdak_email_verification_token_hash');
        return false;
    }
    $expires = time() + DAY_IN_SECONDS;
    if (!update_user_meta($user->ID, '_pvdak_email_verification_expires', $expires) &&
        (int)get_user_meta($user->ID, '_pvdak_email_verification_expires', true) !== $expires) {
        delete_user_meta($user->ID, '_pvdak_email_verification_token_hash');
        delete_user_meta($user->ID, '_pvdak_email_verification_issued_to');
        return false;
    }
    $url = add_query_arg(array('pvdak_verify'=>'1', 'uid'=>$user->ID, 'token'=>$token), $base);
    $name = trim((string)$user->display_name) ?: (string)$user->user_login;
    $body = pvdak_email_verification_html($name, $url);
    $alt_body = pvdak_email_verification_plain_text($name, $url);
    // Only this message is HTML; leave other WordPress messages unchanged.
    // PHPMailer's text fallback is installed only during this synchronous send.
    $with_alternative = static function($mailer) use ($alt_body) {
        $mailer->AltBody = $alt_body;
    };
    add_action('phpmailer_init', $with_alternative, 30);
    try {
        return wp_mail(
            $user->user_email,
            'به PVDak خوش آمدید | تأیید آدرس ایمیل',
            $body,
            array('Content-Type: text/html; charset=UTF-8')
        );
    } finally {
        remove_action('phpmailer_init', $with_alternative, 30);
    }
}

/** Verify a token without showing its value in rendered pages or analytics links. */
function pvdak_email_verification_confirm() {
    // New links verify directly from the unified login Page. Old links from
    // the former register Page remain valid until their 24-hour expiry.
    $unified = is_page('account-login') && is_page_template('page-templates/pvdak-login.php');
    $legacy = is_page('account-register') && is_page_template('page-templates/pvdak-register.php');
    if ((!$unified && !$legacy) || !isset($_GET['pvdak_verify'])) { return; }
    nocache_headers();
    $uid_raw = wp_unslash($_GET['uid'] ?? '');
    $token_raw = wp_unslash($_GET['token'] ?? '');
    $user_id = is_string($uid_raw) && preg_match('/^[1-9][0-9]{0,18}$/D', $uid_raw) ? absint($uid_raw) : 0;
    $token = is_string($token_raw) ? $token_raw : '';
    $valid = false;
    if ($user_id && preg_match('/^[A-Za-z0-9]{48}$/D', $token)) {
        $hash = get_user_meta($user_id, '_pvdak_email_verification_token_hash', true);
        $expiry = absint(get_user_meta($user_id, '_pvdak_email_verification_expires', true));
        $account = get_userdata($user_id);
        $issued_to = get_user_meta($user_id, '_pvdak_email_verification_issued_to', true);
        $valid = '1' === (string)get_user_meta($user_id, '_pvdak_email_verification_pending', true) &&
            $account && is_email($account->user_email) && is_string($issued_to) && '' !== $issued_to &&
            hash_equals($issued_to, strtolower(trim($account->user_email))) &&
            is_string($hash) && $hash !== '' && $expiry >= time() &&
            (bool)wp_check_password($token, $hash, $user_id);
    }
    if ($valid) {
        // Store the verified mailbox BEFORE lifting the login gate; fail closed on any write error.
        $marked_address = update_user_meta($user_id, '_pvdak_email_verified_address', $issued_to);
        $valid = (bool)$marked_address || $issued_to === get_user_meta($user_id, '_pvdak_email_verified_address', true);
        if ($valid) {
            $verified_at = time();
            $marked = update_user_meta($user_id, '_pvdak_email_verified_at', $verified_at);
            $valid = (bool)$marked || (int)get_user_meta($user_id, '_pvdak_email_verified_at', true) === $verified_at;
        }
        if ($valid) {
            $valid = (bool)delete_user_meta($user_id, '_pvdak_email_verification_pending');
            if ($valid) {
                delete_user_meta($user_id, '_pvdak_email_verification_token_hash');
                delete_user_meta($user_id, '_pvdak_email_verification_expires');
                delete_user_meta($user_id, '_pvdak_email_verification_issued_to');
            }
        }
        if (!$valid) {
            // Do not retain a positive verification marker after a failed finalization.
            delete_user_meta($user_id, '_pvdak_email_verified_at');
            delete_user_meta($user_id, '_pvdak_email_verified_address');
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
            (int)get_user_meta($user_id,'_pvdak_email_verified_at',true)>0)return 'ایمیل تأییدشده';
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
