<?php
/**
 * Template Name: پروفایل کاربری PVDak
 * Template Post Type: page
 * Legacy link: profile now belongs to the single private account dashboard.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( pvdak_auth_login_url(), 302 );
    exit;
}
$status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ?
    sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
$target = pvdak_auth_account_url();
if ( in_array( $status, array( 'saved', 'unchanged', 'invalid', 'failed' ), true ) ) {
    $target = add_query_arg( 'status', $status, $target );
}
wp_safe_redirect( $target . '#pvdak-profile', 302 );
exit;
