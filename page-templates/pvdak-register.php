<?php
/**
 * Template Name: ثبت‌نام PVDak (مسیر قدیمی)
 * Template Post Type: page
 *
 * Public registration now lives inside account-login. Retain the published
 * legacy Page for old links and 24-hour email verification URLs only.
 */
if (!defined('ABSPATH')) { exit; }
if (is_user_logged_in()) {
    wp_safe_redirect(pvdak_auth_account_url(), 302);
    exit;
}
// Email token links are processed by pvdak_email_verification_confirm() at
// template_redirect priority 5, before WordPress loads this template.
// Never copy tokens into the new URL.
$target = pvdak_registration_result_url();
$fields = array();
if (isset($_GET['state']) && is_string($_GET['state'])) {
    $state = sanitize_key(wp_unslash($_GET['state']));
    if (in_array($state, array('received','email_issue','verified','resent','invalid_token',
            'invalid','duplicate','slow','unavailable'), true)) {
        $fields['state'] = $state;
    }
}
$requested_sms = isset($_GET['auth']) && is_string($_GET['auth']) &&
    'sms-register' === sanitize_key(wp_unslash($_GET['auth']));
if (isset($_GET['sms_status']) && is_string($_GET['sms_status'])) {
    $sms = sanitize_key(wp_unslash($_GET['sms_status']));
    if (in_array($sms, array('sent','invalid','slow','unavailable','closed'), true)) {
        $fields['sms_status'] = $sms;
        $requested_sms = true;
    }
}
if ($requested_sms) $fields['auth'] = 'sms-register';
wp_safe_redirect(add_query_arg($fields, $target), 302);
exit;
