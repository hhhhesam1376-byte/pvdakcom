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
    $user = check_password_reset_key( $key, $login );
    if ( is_wp_error( $user ) || pvdak_public_text_length( $password ) < 12 || $password !== $confirm ) {
        wp_safe_redirect( add_query_arg( 'status', 'invalid', $url ) ); exit;
    }
    reset_password( $user, $password );
    wp_safe_redirect( add_query_arg( 'status', 'reset', pvdak_auth_login_url() ) ); exit;
}
add_action( 'admin_post_nopriv_pvdak_recovery_change', 'pvdak_recovery_change' );
add_action( 'admin_post_pvdak_recovery_change', 'pvdak_recovery_change' );
