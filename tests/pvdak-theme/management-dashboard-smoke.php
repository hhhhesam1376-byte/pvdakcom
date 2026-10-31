<?php
/**
 * Source-only regression for the isolated PVDak management hub.
 * Does not load WordPress or access user records, posts, or the database.
 */
$root = dirname( __DIR__, 2 );
$dashboard = file_get_contents( $root . '/inc/pvdak-management-dashboard.php' );
$entry = file_get_contents( $root . '/inc/pvdak-readiness-admin.php' );
$css = file_get_contents( $root . '/assets/css/pvdak-management.css' );
$checks = array(
    'UI module loaded' => strpos( $entry, "require_once get_template_directory() . '/inc/pvdak-management-dashboard.php';" ) !== false,
    'existing menu delegates to new UI' => strpos( $entry, 'pvdak_management_v2_render();' ) !== false,
    'old allowlist retained' => strpos( $entry, 'function pvdak_management_link_available(' ) !== false,
    'links still permission checked' => strpos( $dashboard, 'pvdak_management_link_available( $link )' ) !== false,
    'editor access verified' => strpos( $dashboard, "current_user_can( 'edit_pages' )" ) !== false,
    'admin assets scoped' => strpos( $dashboard, 'toplevel_page_pvdak-management' ) !== false,
    'full width available' => strpos( $css, 'max-width:none' ) !== false,
    'RTL presentation' => strpos( $css, 'direction:rtl' ) !== false,
    'mobile layout' => strpos( $css, '@media(max-width:600px)' ) !== false,
    'actionable queues' => strpos( $dashboard, 'pvdak_management_primary_queue_routes( $available )' ) !== false,
    'supplementary paths' => strpos( $dashboard, 'pvdak_management_secondary_queue_routes( $available )' ) !== false,
    'comments count permission routine reused' => strpos( $dashboard, 'pvdak_management_pending_comments()' ) !== false,
    'status is read-only' => strpos( $dashboard, 'نمایش وضعیت فعلی؛ هیچ تنظیمی از این صفحه تغییر نمی‌کند.' ) !== false,
    'no mutation APIs in dashboard' => preg_match( '/\\b(?:update_option|wp_insert_post|wp_update_post|wp_delete_post|delete_option|wp_create_user)\\s*\\(/', $dashboard ) === 0,
);
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) {
        fwrite( STDERR, "PVDAK_ADMIN_UI_FAIL: $name\n" );
        exit( 1 );
    }
}
echo 'PVDAK_ADMIN_UI_SOURCE_OK: ' . count( $checks ) . "/14 checks (PHP syntax and browser tests separate).\n";
