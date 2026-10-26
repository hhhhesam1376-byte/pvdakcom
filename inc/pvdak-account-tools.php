<?php
/**
 * Private PVDak account tools: basic profile editing and WordPress password reset.
 * Passwords/tokens never stored as theme options or sent to administrators.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function pvdak_account_tool_url( $slug ) {
    // The legacy account-profile Page remains compatible, but never offers a second UI.
    if ( 'account-profile' === $slug ) {
        return pvdak_auth_account_url() . '#pvdak-profile';
    }
    // Recovery is a third, step-by-step state of the canonical login screen.
    // The legacy Page only redirects previously issued reset links.
    if ( 'account-recovery' === $slug ) {
        $login = pvdak_public_template_page_url( 'account-login', 'page-templates/pvdak-login.php' );
        return $login ? add_query_arg( 'auth', 'recover', $login )
            : site_url( 'wp-login.php?action=lostpassword', 'login' );
    }
    $page = 'account-register' === $slug
        ? pvdak_public_template_page_url( $slug, 'page-templates/pvdak-register.php' )
        : pvdak_public_page_url( $slug );
    if ( $page ) { return $page; }
    // The branded recovery Page might be missing before admin page bootstrap.
    // Use the native WordPress recovery route instead of sending reset requests to 404.
    if ( 'account-recovery' === $slug ) {
        return site_url( 'wp-login.php?action=lostpassword', 'login' );
    }
    return home_url( '/' . $slug . '/' );
}
function pvdak_account_noindex( $robots ) {
    if ( is_page( array( 'account-login', 'account-profile', 'account-recovery', 'account-register', 'my-pvdak-account' ) ) ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_account_noindex' );
function pvdak_account_cache_protection() {
    if ( is_page( array( 'account-login', 'account-profile', 'account-recovery', 'account-register', 'my-pvdak-account' ) ) ) {
        // Recovery links carry WordPress reset keys: never cache or forward the
        // URL in a Referer header to analytics, CDNs or third-party assets.
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );
        if ( is_page( 'account-recovery' ) ) {
            header( 'Referrer-Policy: no-referrer', true );
        }
    }
}
add_action( 'template_redirect', 'pvdak_account_cache_protection', 2 );
function pvdak_profile_save() {
    if ( ! is_user_logged_in() ) { wp_die( 'ابتدا وارد حساب شوید.', '', array( 'response' => 403 ) ); }
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Method not allowed', '', array( 'response' => 405 ) ); }
    check_admin_referer( 'pvdak_profile_save', '_pvdak_profile_nonce' );
    $raw = wp_unslash( $_POST['display_name'] ?? '' );
    $value = is_string( $raw ) ? sanitize_text_field( $raw ) : '';
    $url = pvdak_auth_account_url();
    $value = trim( $value );
    // Only non-privileged profile fields are editable here. Do not take
    // user ID, email, role, capability or verification status from POST.
    $profile = array();
    foreach ( array( 'first_name', 'last_name' ) as $field ) {
        // Existing browser tabs with the older form omit these fields.
        // Preserve stored names instead of silently clearing them.
        if ( ! array_key_exists( $field, $_POST ) ) {
            $profile[ $field ] = (string) get_user_meta( get_current_user_id(), $field, true );
            continue;
        }
        $raw_field = wp_unslash( $_POST[ $field ] );
        $profile[ $field ] = is_string( $raw_field )
            ? trim( sanitize_text_field( $raw_field ) ) : '';
    }
    if ( '' === $value || pvdak_public_text_length( $value ) > 120 ||
         pvdak_public_text_length( $profile['first_name'] ) > 80 ||
         pvdak_public_text_length( $profile['last_name'] ) > 80 ) {
        wp_safe_redirect( add_query_arg( 'status', 'invalid', $url ) . '#pvdak-profile' ); exit;
    }
    $id = get_current_user_id();
    if ( $value === (string) wp_get_current_user()->display_name &&
         $profile['first_name'] === (string) get_user_meta( $id, 'first_name', true ) &&
         $profile['last_name'] === (string) get_user_meta( $id, 'last_name', true ) ) {
        wp_safe_redirect( add_query_arg( 'status', 'unchanged', $url ) . '#pvdak-profile' ); exit;
    }
    $result = wp_update_user( array( 'ID' => $id, 'display_name' => $value,
        'first_name' => $profile['first_name'], 'last_name' => $profile['last_name'] ) );
    wp_safe_redirect( add_query_arg( 'status', is_wp_error( $result ) ? 'failed' : 'saved', $url ) . '#pvdak-profile' ); exit;
}
add_action( 'admin_post_pvdak_profile_save', 'pvdak_profile_save' );

/**
 * Recent status history for the CURRENT request owner only. Read-only.
 * Core 0.8.14 stores transitions in repeated _pvdak_state_audit entries.
 * Never expose request descriptions, internal notes, professional identities,
 * assignment IDs or audit actor IDs to the applicant.
 */
function pvdak_account_request_timelines() {
    if ( ! is_user_logged_in() ||
         ! function_exists( 'pvdak_core_request_statuses' ) ) { return ''; }
    $owner = get_current_user_id();
    $statuses = pvdak_core_request_statuses();
    $query = get_posts( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'author' => $owner,
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => false,
        'no_found_rows' => true,
    ) );
    if ( ! is_array( $query ) || ! $query ) { return ''; }
    $html = '<section class="pvdak-request-progress" aria-labelledby="pvdak-request-progress-title">';
    $html .= '<h3 id="pvdak-request-progress-title">آخرین تغییرات درخواست‌های من</h3>';
    $html .= '<p>این تاریخچه فقط وضعیت رسیدگی را نشان می‌دهد؛ اطلاعات محرمانه پرونده و هویت متخصص در آن منتشر نمی‌شود.</p>';
    $count = 0;
    foreach ( $query as $request ) {
        if ( ! ( $request instanceof WP_Post ) ||
             'pvdak_request' !== $request->post_type ||
             'private' !== $request->post_status ||
             (int) $request->post_author !== $owner ) { continue; }
        $state = get_post_meta( $request->ID, '_pvdak_request_state', true );
        if ( ! is_string( $state ) || ! isset( $statuses[$state] ) ) {
            $state = 'submitted';
        }
        $html .= '<details class="pvdak-request-progress-item">';
        $html .= '<summary><span>' . esc_html( get_the_title( $request->ID ) ) .
            ' <small>شماره پیگیری: ' . esc_html( number_format_i18n( $request->ID ) ) .
            '</small></span><strong>' . esc_html( $statuses[$state] ) . '</strong></summary>';
        $events = get_post_meta( $request->ID, '_pvdak_state_audit', false );
        $public_events = array( array(
            'state' => 'submitted',
            'at' => get_post_time( 'U', true, $request->ID ),
        ) );
        if ( is_array( $events ) ) {
            foreach ( $events as $event ) {
                if ( ! is_array( $event ) || ! isset( $event['to'] ) ||
                     ! is_string( $event['to'] ) || ! isset( $statuses[$event['to']] ) ) {
                    continue;
                }
                $event_date = isset( $event['at'] ) && is_string( $event['at'] )
                    ? strtotime( $event['at'] . ' UTC' ) : false;
                if ( ! $event_date ) { continue; }
                $public_events[] = array( 'state' => $event['to'], 'at' => $event_date );
            }
        }
        // Bound rendering without changing historical data. Latest transitions
        // are most useful; creation remains visible when fewer than 8 exist.
        $public_events = array_slice( $public_events, -8 );
        $html .= '<ol class="pvdak-request-progress-events">';
        foreach ( $public_events as $event ) {
            $html .= '<li><span>' . esc_html( $statuses[$event['state']] ) . '</span>';
            if ( ! empty( $event['at'] ) ) {
                $html .= '<time datetime="' . esc_attr( gmdate( 'c', (int) $event['at'] ) ) . '">' .
                    esc_html( wp_date( 'j F Y، H:i', (int) $event['at'] ) ) . '</time>';
            }
            $html .= '</li>';
        }
        $html .= '</ol>';
        if ( 'matched' === $state ) {
            $html .= '<p class="pvdak-request-progress-note">این مرحله صرفاً انتساب داخلی سامانه است؛ به‌معنای قبول پرونده توسط وکیل یا ارسال اطلاعات به متخصص نیست.</p>';
        }
        $html .= '</details>';
        $count++;
    }
    return $count ? $html . '</section>' : '';
}

/**
 * All reset links now open the canonical login Page at its second step.
 * Retain native WordPress reset as a fail-safe if the Page is unavailable.
 */
function pvdak_recovery_link( $key, $login ) {
    $branded = pvdak_public_template_page_url( 'account-login', 'page-templates/pvdak-login.php' );
    return $branded
        ? add_query_arg( array(
            'auth' => 'recover-change', 'reset' => '1',
            'key' => $key, 'login' => $login,
        ), $branded )
        : add_query_arg( array(
            'action' => 'rp', 'key' => $key, 'login' => $login,
        ), site_url( 'wp-login.php', 'login' ) );
}

/** Branded HTML message with the same colors, structure and RTL as signup. */
function pvdak_recovery_email_message( $message, $key, $login, $user ) {
    $url = pvdak_recovery_link( $key, $login );
    $name = $user instanceof WP_User
        ? ( trim( (string) $user->display_name ) ?: (string) $user->user_login )
        : 'کاربر PVDak';
    // A dedicated template: the sign-up email must never inherit reset wording.
    $template = <<<'RECOVERY_EMAIL'
<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>بازیابی رمز عبور PVDak</title></head>
<body dir="rtl" style="margin:0;padding:0;background:#f2f5f8;color:#172335;font-family:Tahoma,Arial,sans-serif;direction:rtl;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f2f5f8;border-collapse:collapse;">
<tr><td align="center" style="padding:32px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#fff;border-collapse:separate;border-radius:16px;overflow:hidden;">
<tr><td align="right" dir="rtl" style="padding:26px 32px;background:#123d54;border-bottom:4px solid #e0a33b;">
<p dir="ltr" style="margin:0;color:#fff;font-size:24px;font-weight:bold;letter-spacing:1px;">PVDak</p>
<p style="margin:8px 0 0;color:#e6edf0;font-size:13px;line-height:1.8;">سامانه خدمات و ارتباطات حقوقی</p></td></tr>
<tr><td align="right" dir="rtl" style="padding:34px 32px 14px;">
<p style="margin:0 0 12px;color:#ac7731;font-size:13px;font-weight:bold;">امنیت حساب شما</p>
<h1 style="margin:0 0 18px;color:#123d54;font-size:23px;line-height:1.7;">رمز عبورتان را بازیابی کنید</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:2;color:#263b4c;">سلام <strong>{{NAME}}</strong>،</p>
<p style="margin:0 0 20px;font-size:15px;line-height:2;color:#34495a;">درخواستی برای بازیابی رمز عبور حساب PVDak ثبت شده است. برای ادامه، روی دکمه زیر بزنید و در همان صفحه ورود، رمز جدید خود را تعیین کنید.</p></td></tr>
<tr><td align="center" style="padding:10px 32px 24px;">
<a href="{{RESET_URL}}" style="display:inline-block;background:#123d54;color:#fff;text-decoration:none;padding:15px 35px;border-radius:9px;font-size:16px;font-weight:bold;line-height:1.6;" target="_blank" rel="noopener noreferrer">انتخاب رمز عبور جدید</a>
<p style="margin:18px 0 0;color:#6b7886;font-size:13px;line-height:1.9;">این لینک زمان‌دار و یک‌بارمصرف است؛ برای امنیت حساب، آن را با دیگران به اشتراک نگذارید.</p></td></tr>
<tr><td align="right" dir="rtl" style="padding:18px 32px 26px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f5f8fa;border-right:3px solid #e0a33b;"><tr><td style="padding:16px 18px;">
<p style="margin:0 0 10px;color:#233b4c;font-size:13px;line-height:1.9;"><strong>دکمه باز نمی‌شود؟</strong> لینک زیر را در مرورگرتان وارد کنید:</p>
<p dir="ltr" style="margin:0;text-align:left;overflow-wrap:anywhere;word-break:break-all;font-family:Arial,sans-serif;font-size:12px;line-height:1.9;"><a href="{{RESET_URL}}" style="color:#155d7c;text-decoration:underline;">{{PLAIN_URL}}</a></p>
</td></tr></table>
<p style="margin:20px 0 0;color:#657382;font-size:13px;line-height:1.9;">اگر شما درخواست بازیابی نداده‌اید، نیاز به اقدام نیست؛ این پیام را نادیده بگیرید و رمز فعلی‌تان را در اختیار کسی قرار ندهید.</p></td></tr>
<tr><td align="center" dir="rtl" style="padding:20px 32px 25px;background:#f8fafc;border-top:1px solid #e5eaee;">
<p style="margin:0 0 7px;color:#123d54;font-weight:bold;font-size:14px;">PVDak</p>
<p style="margin:0;color:#74818e;font-size:12px;line-height:1.9;">این پیام خودکار فقط برای بازیابی امن رمز حساب شماست.</p>
<p style="margin:8px 0 0;font-size:12px;"><a href="{{SITE_URL}}" style="color:#155d7c;text-decoration:none;">وب‌سایت PVDak</a></p>
</td></tr></table></td></tr></table></body></html>
RECOVERY_EMAIL;
    return strtr( $template, array(
        '{{NAME}}' => esc_html( $name ),
        '{{RESET_URL}}' => esc_url( $url ),
        '{{PLAIN_URL}}' => esc_html( esc_url_raw( $url ) ),
        '{{SITE_URL}}' => esc_url( home_url( '/' ) ),
    ) );
}
function pvdak_recovery_email_plain_text( $key, $login ) {
    return "برای انتخاب رمز جدید حساب PVDak روی لینک زیر بزنید:\n\n" .
        esc_url_raw( pvdak_recovery_link( $key, $login ) ) .
        "\n\nاین لینک زمان‌دار و یک‌بارمصرف است. اگر درخواست نداده‌اید، پیام را نادیده بگیرید.\n";
}

function pvdak_recovery_request() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Method not allowed', '', array( 'response' => 405 ) ); }
    check_admin_referer( 'pvdak_recovery_request', '_pvdak_recovery_nonce' );
    $raw = wp_unslash( $_POST['identity'] ?? '' );
    $identity = is_string( $raw ) ? trim( sanitize_text_field( $raw ) ) : '';
    $url = pvdak_account_tool_url( 'account-recovery' );
    // Rotating identities must not evade the IP-wide recovery ceiling.
    if ( pvdak_registration_rate_exceeded( 'recovery-request', 12, HOUR_IN_SECONDS ) ) {
        wp_safe_redirect( add_query_arg( 'status', 'sent', $url ) ); exit;
    }
    if ( '' === $identity || strlen( $identity ) > 250 ) {
        wp_safe_redirect( add_query_arg( 'status', 'sent', $url ) ); exit;
    }
    $token = 'pvdak_recovery_' . substr( hash_hmac( 'sha256',
        ( $_SERVER['REMOTE_ADDR'] ?? '' ) . '|' . strtolower( $identity ), wp_salt( 'nonce' ) ), 0, 35 );
    if ( ! get_transient( $token ) ) {
        set_transient( $token, 1, 90 );
        // Scope HTML/AltBody and the subject to this one reset email only.
        // Even mail errors must not affect unrelated WordPress messages.
        $alternative = '';
        $message_filter = static function( $message, $key, $login, $user ) use ( &$alternative ) {
            $alternative = pvdak_recovery_email_plain_text( $key, $login );
            return pvdak_recovery_email_message( $message, $key, $login, $user );
        };
        $type_filter = static function( $type ) { return 'text/html'; };
        $title_filter = static function( $title ) { return 'PVDak | بازیابی رمز عبور'; };
        $mail_alt = static function( $mailer ) use ( &$alternative ) {
            $mailer->AltBody = $alternative;
        };
        add_filter( 'retrieve_password_message', $message_filter, 10, 4 );
        add_filter( 'retrieve_password_title', $title_filter, 10, 1 );
        add_filter( 'wp_mail_content_type', $type_filter, 10, 1 );
        add_action( 'phpmailer_init', $mail_alt, 30 );
        try {
            retrieve_password( $identity );
        } finally {
            remove_filter( 'retrieve_password_message', $message_filter, 10 );
            remove_filter( 'retrieve_password_title', $title_filter, 10 );
            remove_filter( 'wp_mail_content_type', $type_filter, 10 );
            remove_action( 'phpmailer_init', $mail_alt, 30 );
        }
    }
    wp_safe_redirect( add_query_arg( 'status', 'sent', $url ) ); exit;
}
add_action( 'admin_post_nopriv_pvdak_recovery_request', 'pvdak_recovery_request' );
add_action( 'admin_post_pvdak_recovery_request', 'pvdak_recovery_request' );

function pvdak_recovery_change() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Method not allowed', '', array( 'response' => 405 ) ); }
    check_admin_referer( 'pvdak_recovery_change', '_pvdak_reset_nonce' );
    $user_raw = wp_unslash( $_POST['login'] ?? '' );
    $key_raw = wp_unslash( $_POST['key'] ?? '' );
    $pass_raw = wp_unslash( $_POST['new_password'] ?? '' );
    $confirm_raw = wp_unslash( $_POST['confirm_password'] ?? '' );
    $login = is_string( $user_raw ) ? $user_raw : '';
    $key = is_string( $key_raw ) ? $key_raw : '';
    $password = is_string( $pass_raw ) ? $pass_raw : '';
    $confirm = is_string( $confirm_raw ) ? $confirm_raw : '';
    $url = pvdak_account_tool_url( 'account-recovery' );
    if ( '' === $login || strlen( $login ) > 250 || '' === $key || strlen( $key ) > 64 ) {
        wp_safe_redirect( add_query_arg( 'status', 'invalid', $url ) ); exit;
    }
    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ||
         pvdak_public_text_length( $password ) < 12 ||
         pvdak_public_text_length( $password ) > 256 || strlen( $password ) > 1024 ||
         '' === trim( $password ) || $password !== $confirm ) {
        wp_safe_redirect( add_query_arg( 'status', 'invalid', $url ) ); exit;
    }
    reset_password( $user, $password );
    // WordPress reset_password() does not return a success flag. Check the
    // freshly stored hash and the one-use key before claiming the reset worked.
    // A database failure must never display a false "password changed" state.
    $fresh = get_userdata( $user->ID );
    $saved = $fresh instanceof WP_User &&
        wp_check_password( $password, $fresh->user_pass, $fresh->ID ) &&
        is_wp_error( check_password_reset_key( $key, $login ) );
    if ( ! $saved ) {
        wp_safe_redirect( add_query_arg( 'status', 'failed', $url ) ); exit;
    }
    wp_safe_redirect( add_query_arg( array( 'auth' => 'password', 'status' => 'reset' ), pvdak_auth_login_url() ) ); exit;
}
add_action( 'admin_post_nopriv_pvdak_recovery_change', 'pvdak_recovery_change' );
add_action( 'admin_post_pvdak_recovery_change', 'pvdak_recovery_change' );
