<?php
/**
 * D13 signup lifecycle fixture: disposable GitHub Actions WordPress/MariaDB only.
 * Exercises the real public registration handler, not wp_insert_user setup.
 * No production settings, SMTP credentials, or real accounts are used.
 */
if (!defined('WP_CLI') || !WP_CLI ||
    getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('PVDAK_CI_D13_SIGNUP_ISOLATED') !== '1' ||
    !defined('DB_NAME') || DB_NAME !== 'pvdak_ci' ||
    !defined('DB_HOST') || DB_HOST !== '127.0.0.1' ||
    !getenv('RUNNER_TEMP') ||
    realpath(ABSPATH) !== realpath(getenv('RUNNER_TEMP') . '/pvdak-wp') ||
    wp_parse_url(site_url(), PHP_URL_HOST) !== '127.0.0.1') {
    fwrite(STDERR, "D13_SIGNUP_ABORT: disposable GitHub Actions WordPress only\n");
    exit(2);
}
$mode = getenv('PVDAK_CI_D13_SIGNUP_MODE');
if (!in_array($mode, array('setup', 'issue', 'assert', 'cleanup'), true) ||
    !function_exists('pvdak_registration_open') ||
    !function_exists('pvdak_email_verification_issue')) {
    WP_CLI::error('D13_SIGNUP_ABORT: invalid mode or missing theme functions.');
}
$login = 'ci_d13_signup_lawyer';
$email = 'ci_d13_signup_lawyer@example.invalid';
$file = getenv('RUNNER_TEMP') . '/pvdak-d13-signup-url';
$user = get_user_by('login', $login);
if ($mode === 'cleanup') {
    if ($user) {
        if (strtolower($user->user_email) !== $email ||
            !in_array('pvdak_client', (array)$user->roles, true)) {
            WP_CLI::error('D13_SIGNUP_CLEANUP_ABORT: unexpected identity or role.');
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        if (!wp_delete_user($user->ID) || get_user_by('id', $user->ID)) {
            WP_CLI::error('D13_SIGNUP_CLEANUP_FAIL: user not deleted.');
        }
    }
    if (file_exists($file) && !unlink($file)) {
        WP_CLI::error('D13_SIGNUP_CLEANUP_FAIL: temporary URL not deleted.');
    }
    update_option('pvdak_registration_enabled', '0');
    if (pvdak_registration_open()) {
        WP_CLI::error('D13_SIGNUP_CLEANUP_FAIL: registration still open.');
    }
    WP_CLI::line('PVDAK_D13_SIGNUP_CLEANUP_OK: synthetic account removed, registration disabled');
    return;
}
if ($mode === 'setup') {
    if ($user || file_exists($file) ||
        '1' === (string)get_option('pvdak_registration_enabled', '0') ||
        !pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php') ||
        (function_exists('pvdak_maintenance_enabled') && pvdak_maintenance_enabled())) {
        WP_CLI::error('D13_SIGNUP_SETUP_ABORT: fixture already exists or site gate invalid.');
    }
    update_option('pvdak_registration_enabled', '1');
    if (!pvdak_registration_open()) {
        update_option('pvdak_registration_enabled', '0');
        WP_CLI::error('D13_SIGNUP_SETUP_FAIL: registration did not open.');
    }
    WP_CLI::line('PVDAK_D13_SIGNUP_SETUP_OK: disposable registration gate enabled');
    return;
}
if (!$user || strtolower($user->user_email) !== $email ||
    !in_array('pvdak_client', (array)$user->roles, true) ||
    get_user_meta($user->ID, '_pvdak_self_reported_category', true) !== 'lawyer' ||
    user_can($user, 'manage_options') || user_can($user, 'edit_others_posts')) {
    WP_CLI::error('D13_SIGNUP_FAIL: HTTP signup identity, self-report or privilege incorrect.');
}
if ($mode === 'assert') {
    $at = get_user_meta($user->ID, '_pvdak_email_verified_at', true);
    if (get_user_meta($user->ID, '_pvdak_email_verification_pending', true) ||
        get_user_meta($user->ID, '_pvdak_email_verification_token_hash', true) ||
        get_user_meta($user->ID, '_pvdak_email_verification_issued_to', true) ||
        get_user_meta($user->ID, '_pvdak_email_verified_address', true) !== $email ||
        !is_numeric($at) || (int)$at < time() - 300 ||
        (int)$at > time() + 10) {
        WP_CLI::error('D13_SIGNUP_ASSERT_FAIL: verified mailbox or one-use state incorrect.');
    }
    WP_CLI::line('PVDAK_D13_SIGNUP_DB_OK: verified mailbox, no token, self-reported lawyer remains client');
    return;
}
if (get_user_meta($user->ID, '_pvdak_email_verification_pending', true) !== '1' ||
    get_user_meta($user->ID, '_pvdak_email_verified_address', true)) {
    WP_CLI::error('D13_SIGNUP_ISSUE_FAIL: new HTTP account not pending.');
}
$captured = array();
$intercept = static function($return, $atts) use (&$captured) {
    $captured[] = $atts;
    return true; // CI-only mail interception; never contacts SMTP.
};
add_filter('pre_wp_mail', $intercept, 10, 2);
try {
    $sent = pvdak_email_verification_issue($user->ID);
} finally {
    remove_filter('pre_wp_mail', $intercept, 10);
}
if (!$sent || count($captured) !== 1 ||
    ($captured[0]['to'] ?? null) !== $email ||
    !is_string($captured[0]['message'] ?? null)) {
    WP_CLI::error('D13_SIGNUP_ISSUE_FAIL: verification mail not captured.');
}
preg_match_all('~href="([^"]+)"~', $captured[0]['message'], $links);
$url = '';
foreach ($links[1] as $candidate) {
    $candidate = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $query = wp_parse_url($candidate, PHP_URL_QUERY);
    parse_str(is_string($query) ? $query : '', $args);
    if (($args['pvdak_verify'] ?? '') === '1') {
        if ((string)($args['uid'] ?? '') !== (string)$user->ID ||
            !preg_match('/^[A-Za-z0-9]{48}$/D', (string)($args['token'] ?? '')) ||
            wp_parse_url($candidate, PHP_URL_HOST) !== '127.0.0.1') {
            WP_CLI::error('D13_SIGNUP_ISSUE_FAIL: unsafe token URL.');
        }
        $url = $candidate;
        break;
    }
}
if (!$url ||
    !wp_check_password($args['token'], get_user_meta($user->ID, '_pvdak_email_verification_token_hash', true), $user->ID) ||
    (int)get_user_meta($user->ID, '_pvdak_email_verification_expires', true) < time() + 86000 ||
    file_put_contents($file, $url, LOCK_EX) !== strlen($url) ||
    !chmod($file, 0600)) {
    WP_CLI::error('D13_SIGNUP_ISSUE_FAIL: token persistence or file permissions.');
}
WP_CLI::line('PVDAK_D13_SIGNUP_ISSUE_OK: real HTTP-created pending account; intercepted token only');
