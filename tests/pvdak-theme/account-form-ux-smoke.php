<?php
/**
 * Batch 091 source-only regression for account, profile, recovery and contact.
 * No WordPress bootstrap, requests, users, emails, or DB writes.
 */
$root = dirname( __DIR__, 2 );
$paths = array(
  'public' => 'inc/pvdak-public-pages.php',
  'tools' => 'inc/pvdak-account-tools.php',
  'profile' => 'page-templates/pvdak-profile.php',
  'contact' => 'page-templates/pvdak-contact.php',
  'recovery' => 'page-templates/pvdak-recovery.php',
  'login' => 'page-templates/pvdak-login.php',
  'portal' => 'page-templates/pvdak-account.php',
  'script' => 'assets/js/pvdak-account.js',
  'functions' => 'functions.php',
  'css' => 'style.css',
);
$s = array();
foreach ( $paths as $name => $path ) {
  if ( ! is_readable( $root . '/' . $path ) ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: missing $path\n" ); exit( 2 ); }
  $s[ $name ] = file_get_contents( $root . '/' . $path );
}
$checks = array(
  'Unicode helper counts all characters' => strpos( $s['public'], "preg_match_all( '/[\\s\\S]/u'" ) !== false,
  'Contact name Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($name)>100' ) !== false,
  'Contact message minimum Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($msg)<10' ) !== false,
  'Contact message maximum Unicode length' => strpos( $s['public'], 'pvdak_public_text_length($msg)>3500' ) !== false,
  'Contact email bounded server-side' => strpos( $s['public'], 'strlen($email)>254' ) !== false,
  'Profile Unicode length bounded server-side' => strpos( $s['tools'], 'pvdak_public_text_length( $value ) > 120' ) !== false,
  'Profile no-op avoids writes' => strpos( $s['tools'], "'status', 'unchanged'" ) !== false,
  'Recovery minimum Unicode length' => strpos( $s['tools'], 'pvdak_public_text_length( $password ) < 12' ) !== false,
  'Legacy profile URL preserves current form state via redirect' =>
    strpos( $s['profile'], "wp_safe_redirect( $target . '#pvdak-profile', 302 )" ) !== false &&
    strpos( $s['portal'], 'id="pvdak-profile"' ) !== false,
  'Profile status variants' => strpos( $s['portal'], "if('unchanged'===" ) !== false,
  'Profile counter and hint' => strpos( $s['portal'], 'data-pvdak-count-for="account-display-name"' ) !== false,
  'Profile reset control' => strpos( $s['portal'], 'type="reset"' ) !== false,
  'Profile unsaved change status' => strpos( $s['portal'], 'data-pvdak-profile-status' ) !== false,
  'Contact result accessible focus' => strpos( $s['contact'], 'id="pvdak-contact-result" tabindex="-1"' ) !== false,
  'Contact textarea counter' => strpos( $s['contact'], 'data-pvdak-count-for="pvdak-message"' ) !== false,
  'Contact email browser cap' => strpos( $s['contact'], 'maxlength="254"' ) !== false,
  'Recovery confirmation hint' => strpos( $s['recovery'], 'id="pvdak-password-match"' ) !== false,
  'Recovery client validation marker' => strpos( $s['recovery'], 'data-pvdak-recovery-change' ) !== false,
  'Login bounded identity' => strpos( $s['login'], 'maxlength="250"' ) !== false,
  'Login error focus target' => strpos( $s['login'], 'id="pvdak-login-result"' ) !== false,
  'Dashboard fallback avoids activation claim' => strpos( $s['portal'], 'شما وارد حساب خود شده‌اید' ) !== false,
  'Account panels offer return navigation' => substr_count( $s['portal'], 'pvdak-panel-return' ) === 2,
  'Account scripts available on profile and contact' => strpos( $s['functions'], "'account-profile', 'contact-us'" ) !== false,
  'Active navigation recognizes JS location marker' => strpos( $s['css'], '.pvdak-account-menu a[aria-current]{' ) !== false,
  'No remote JS calls or credential storage' => strpos( $s['script'], 'fetch(' ) === false && strpos( $s['script'], 'localStorage' ) === false,
  'Prevent repeated valid submissions' => strpos( $s['script'], 'form.dataset.pvdakSubmitting' ) !== false,
  'Password match is validated before submission' => strpos( $s['script'], 'function validateRecovery()' ) !== false,
);
foreach ( $checks as $label => $pass ) {
  if ( ! $pass ) { fwrite( STDERR, "ACCOUNT_FORM_FAIL: $label\n" ); exit( 1 ); }
}
echo "PVDAK_ACCOUNT_FORM_091_OK: " . count( $checks ) . " source checks; no WordPress/browser tests.\n";
