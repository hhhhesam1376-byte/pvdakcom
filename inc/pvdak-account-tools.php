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
    $page = 'account-recovery' === $slug
        ? pvdak_public_template_page_url( $slug, 'page-templates/pvdak-recovery.php' )
        : ( 'account-register' === $slug
            ? pvdak_public_template_page_url( $slug, 'page-templates/pvdak-register.php' )
            : pvdak_public_page_url( $slug ) );
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
        nocache_headers();
        header( 'X-Robots-Tag: noindex, nofollow', true );
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

/** Send one generic response for existent and non-existent identities. */
function pvdak_recovery_email_message( $message, $key, $login, $user ) {
    $custom = pvdak_public_template_page_url( 'account-recovery', 'page-templates/pvdak-recovery.php' );
    $url = $custom
        ? add_query_arg( array(
            'reset' => '1', 'key' => $key, 'login' => rawurlencode( $login )
        ), $custom )
        : add_query_arg( array(
            'action' => 'rp', 'key' => $key, 'login' => rawurlencode( $login )
        ), site_url( 'wp-login.php', 'login' ) );
    return "برای انتخاب رمز جدید حساب PVDak به پیوند زیر مراجعه کنید:\n\n" . esc_url_raw( $url ) .
        "\n\nاگر شما این درخواست را ارسال نکرده‌اید، این پیام را نادیده بگیرید.";
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
        add_filter( 'retrieve_password_message', 'pvdak_recovery_email_message', 10, 4 );
        retrieve_password( $identity );
        remove_filter( 'retrieve_password_message', 'pvdak_recovery_email_message', 10 );
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
    if ( is_wp_error( $user ) || pvdak_public_text_length( $password ) < 12 ||
         pvdak_public_text_length( $password ) > 256 || strlen( $password ) > 1024 ||
         $password !== $confirm ) {
        wp_safe_redirect( add_query_arg( 'status', 'invalid', $url ) ); exit;
    }
    reset_password( $user, $password );
    wp_safe_redirect( add_query_arg( 'status', 'reset', pvdak_auth_login_url() ) ); exit;
}
add_action( 'admin_post_nopriv_pvdak_recovery_change', 'pvdak_recovery_change' );
add_action( 'admin_post_pvdak_recovery_change', 'pvdak_recovery_change' );
