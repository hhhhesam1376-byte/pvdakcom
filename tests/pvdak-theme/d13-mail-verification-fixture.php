<?php
/**
 * D13 email verification fixture. GitHub Actions disposable WordPress only.
 * No SMTP: pre_wp_mail captures the single synthetic verification URL locally.
 */
if (!defined('WP_CLI') || !WP_CLI ||
    getenv('GITHUB_ACTIONS') !== 'true' ||
    getenv('PVDAK_CI_D13_MAIL_ISOLATED') !== '1' ||
    !defined('DB_NAME') || DB_NAME !== 'pvdak_ci' ||
    !defined('DB_HOST') || DB_HOST !== '127.0.0.1' ||
    !getenv('RUNNER_TEMP') ||
    realpath(ABSPATH) !== realpath(getenv('RUNNER_TEMP') . '/pvdak-wp') ||
    wp_parse_url(site_url(), PHP_URL_HOST) !== '127.0.0.1') {
    fwrite(STDERR, "D13_MAIL_ABORT: disposable GitHub Actions WordPress only\n");
    exit(2);
}
$mode = getenv('PVDAK_CI_D13_MAIL_MODE');
if (!in_array($mode, array('setup', 'verify', 'cleanup'), true) ||
    !function_exists('pvdak_email_verification_issue') ||
    !function_exists('pvdak_email_verification_auth_gate')) {
    WP_CLI::error('D13_MAIL_ABORT: invalid mode or missing theme functions.');
}
$login = 'ci_d13_mail_pending';
$email = 'ci_d13_mail_pending@example.invalid';
$file = getenv('RUNNER_TEMP') . '/pvdak-d13-mail-url';
$user = get_user_by('login', $login);
if ($mode === 'cleanup') {
    if ($user) {
        if (get_user_meta($user->ID, '_pvdak_d13_mail_fixture', true) !== '1' ||
            strtolower($user->user_email) !== $email) {
            WP_CLI::error('D13_MAIL_CLEANUP_ABORT: fixture identity mismatch.');
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        if (!wp_delete_user($user->ID) || get_user_by('id', $user->ID)) {
            WP_CLI::error('D13_MAIL_CLEANUP_FAIL: user not deleted.');
        }
    }
    if (file_exists($file) && !unlink($file)) {
        WP_CLI::error('D13_MAIL_CLEANUP_FAIL: temporary URL not deleted.');
    }
    WP_CLI::line('PVDAK_D13_MAIL_CLEANUP_OK: synthetic user and token file absent');
    return;
}
if ($mode === 'verify') {
    if (!$user || get_user_meta($user->ID, '_pvdak_d13_mail_fixture', true) !== '1') {
        WP_CLI::error('D13_MAIL_VERIFY_FAIL: missing marked user.');
    }
    $at = get_user_meta($user->ID, '_pvdak_email_verified_at', true);
    if (get_user_meta($user->ID, '_pvdak_email_verification_pending', true) ||
        get_user_meta($user->ID, '_pvdak_email_verification_token_hash', true) ||
        get_user_meta($user->ID, '_pvdak_email_verification_expires', true) ||
        get_user_meta($user->ID, '_pvdak_email_verification_issued_to', true) ||
        get_user_meta($user->ID, '_pvdak_email_verified_address', true) !== $email ||
        !is_numeric($at) || (int)$at < time() - 300 || (int)$at > time() + 10 ||
        !in_array('pvdak_client', (array)$user->roles, true) ||
        pvdak_email_verification_issue($user->ID) !== false) {
        WP_CLI::error('D13_MAIL_VERIFY_FAIL: token/role/verified state incorrect.');
    }
    WP_CLI::line('PVDAK_D13_MAIL_DB_OK: verified address, single-use cleanup, client role, no reissue');
    return;
}
if ($user || file_exists($file) || !get_role('pvdak_client') ||
    !pvdak_public_template_page_url('account-login', 'page-templates/pvdak-login.php')) {
    WP_CLI::error('D13_MAIL_SETUP_ABORT: existing fixture or login page missing.');
}
$id = wp_insert_user(array(
    'user_login' => $login,
    'user_pass' => 'pvdak_ci_d13_mail_password_only_2026',
    'user_email' => $email,
    'display_name' => 'D13_MAIL_SYNTHETIC',
    'role' => 'pvdak_client',
));
if (is_wp_error($id) || !$id) {
    WP_CLI::error('D13_MAIL_SETUP_FAIL: insert user.');
}
try {
    if (!update_user_meta($id, '_pvdak_d13_mail_fixture', '1') ||
        !update_user_meta($id, '_pvdak_email_verification_pending', '1')) {
        throw new RuntimeException('marker/pending write');
    }
    $captured = array();
    $intercept = static function($return, $atts) use (&$captured) {
        $captured[] = $atts;
        return true; // Never contact SMTP in isolated CI.
    };
    add_filter('pre_wp_mail', $intercept, 10, 2);
    try {
        $sent = pvdak_email_verification_issue($id);
    } finally {
        remove_filter('pre_wp_mail', $intercept, 10);
    }
    if (!$sent || count($captured) !== 1 ||
        ($captured[0]['to'] ?? null) !== $email ||
        !is_string($captured[0]['message'] ?? null)) {
        throw new RuntimeException('mail interception');
    }
    preg_match_all('~href="([^"]+)"~', $captured[0]['message'], $links);
    $url = '';
    foreach ($links[1] as $candidate) {
        $candidate = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $query = wp_parse_url($candidate, PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $args);
        if (($args['pvdak_verify'] ?? '') === '1') {
            if ((string)($args['uid'] ?? '') !== (string)$id ||
                !preg_match('/^[A-Za-z0-9]{48}$/D', (string)($args['token'] ?? '')) ||
                wp_parse_url($candidate, PHP_URL_HOST) !== '127.0.0.1') {
                throw new RuntimeException('unsafe verification URL');
            }
            $url = $candidate;
            break;
        }
    }
    if (!$url ||
        get_user_meta($id, '_pvdak_email_verification_pending', true) !== '1' ||
        get_user_meta($id, '_pvdak_email_verification_issued_to', true) !== $email ||
        !wp_check_password($args['token'], get_user_meta($id, '_pvdak_email_verification_token_hash', true), $id) ||
        (int)get_user_meta($id, '_pvdak_email_verification_expires', true) < time() + 86000) {
        throw new RuntimeException('token not stored correctly');
    }
    if (file_put_contents($file, $url, LOCK_EX) !== strlen($url) ||
        !chmod($file, 0600)) {
        throw new RuntimeException('temporary URL write');
    }
} catch (Throwable $e) {
    @unlink($file);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($id);
    WP_CLI::error('D13_MAIL_SETUP_FAIL: fixture rolled back.');
}
WP_CLI::line('PVDAK_D13_MAIL_SETUP_OK: synthetic pending user and intercepted mail (no SMTP)');
