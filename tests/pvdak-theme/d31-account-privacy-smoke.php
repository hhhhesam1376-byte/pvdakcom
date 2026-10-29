<?php
/** D31 account privacy: source-backed PHP stub, no WordPress, mail or user data. */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['d31_logged_in'] = false;
$GLOBALS['d31_sensitive'] = false;
$GLOBALS['d31_nocache'] = 0;
$GLOBALS['d31_checks'] = 0;
function add_filter() {}
function add_action() {}
function is_page( $slugs = null ) {
    return null === $slugs || $GLOBALS['d31_sensitive'];
}
function is_page_template( $slugs ) { return $GLOBALS['d31_sensitive']; }
function is_user_logged_in() { return $GLOBALS['d31_logged_in']; }
function nocache_headers() { ++$GLOBALS['d31_nocache']; }
function d31_privacy_check( $ok, $name ) {
    if ( ! $ok ) { fwrite( STDERR, 'D31_PRIVACY_FAIL: ' . $name . "\n" ); exit( 1 ); }
    ++$GLOBALS['d31_checks'];
}
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-account-tools.php';
d31_privacy_check( ! pvdak_account_sensitive_screen(), 'ordinary public page' );
d31_privacy_check( ! pvdak_account_private_response_required(), 'anonymous public response' );
d31_privacy_check( array( 'follow' => true ) === pvdak_account_noindex( array( 'follow' => true ) ), 'public robots unchanged' );
d31_privacy_check( false === pvdak_account_private_rest_cache( false, null, null, null ), 'anonymous REST result unchanged' );
d31_privacy_check( 0 === $GLOBALS['d31_nocache'] && ! defined( 'DONOTCACHEPAGE' ), 'anonymous REST cache untouched' );
$GLOBALS['d31_sensitive'] = true;
d31_privacy_check( pvdak_account_sensitive_screen(), 'renamed account template sensitive' );
d31_privacy_check( pvdak_account_private_response_required(), 'anonymous account must not cache' );
$robots = pvdak_account_noindex( array( 'follow' => true ) );
d31_privacy_check( ! empty( $robots['noindex'] ) && ! empty( $robots['nofollow'] ), 'private robots noindex and nofollow' );
$GLOBALS['d31_sensitive'] = false;
$GLOBALS['d31_logged_in'] = true;
d31_privacy_check( pvdak_account_private_response_required(), 'logged-in public HTML private' );
d31_privacy_check( array( 'follow' => true ) === pvdak_account_noindex( array( 'follow' => true ) ), 'logged-in public robots unchanged' );
d31_privacy_check( false === pvdak_account_private_rest_cache( false, null, null, null ), 'authenticated REST result unchanged' );
d31_privacy_check( defined( 'DONOTCACHEPAGE' ) && $GLOBALS['d31_nocache'] > 0, 'authenticated REST no-store and flag' );
d31_privacy_check( 7200 === pvdak_recovery_reset_key_lifetime( 86400 ), 'reset key max two hours' );
d31_privacy_check( 1800 === pvdak_recovery_reset_key_lifetime( 1800 ), 'shorter reset key lifetime preserved' );
echo 'PVDAK_D31_PRIVACY_OK: ' . $GLOBALS['d31_checks'] . '/' . $GLOBALS['d31_checks'] . " source-level PHP cases; WP/HTTP/host NOT_TESTED.\n";
