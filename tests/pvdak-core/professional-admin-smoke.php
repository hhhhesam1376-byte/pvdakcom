<?php
/** Static checks only; not a WordPress/permissions/database integration test. */
$root = dirname( __DIR__, 2 );
$src = file_get_contents( $root . '/packages/pvdak-core/includes/professional-admin.php' );
$boot = file_get_contents( $root . '/packages/pvdak-core/pvdak-core.php' );
if ( false === $src || false === $boot ) { fwrite( STDERR, "PROF_ADMIN_SOURCE_MISSING\n" ); exit( 2 ); }
$checks = array(
    'capability scoped' => strpos( $src, 'current_user_can( $type->cap->edit_posts )' ) !== false,
    'main query only' => strpos( $src, '$query->is_main_query()' ) !== false,
    'admin only' => strpos( $src, '! is_admin()' ) !== false,
    'private post type' => strpos( $src, '\'pvdak_professional\' !== $query->get( \'post_type\' )' ) !== false,
    'type allowlist' => strpos( $src, 'array_keys( pvdak_core_member_types() )' ) !== false,
    'state allowlist' => strpos( $src, 'array_keys( pvdak_core_member_states() )' ) !== false,
    'method allowlist' => strpos( $src, 'array_keys( pvdak_core_review_methods() )' ) !== false,
    'expiry allowlist' => strpos( $src, "array( 'expired', 'next30', 'future', 'missing' )" ) !== false,
    'sanitized GET' => strpos( $src, 'sanitize_key( wp_unslash(' ) !== false,
    'bounded city' => strpos( $src, 'wp_html_excerpt( $city, 70' ) !== false,
    'city search' => strpos( $src, "'compare' => 'LIKE'" ) !== false,
    'preserve other meta filters' => strpos( $src, '$query->get( \'meta_query\' )' ) !== false,
    'expired filter' => strpos( $src, '\'expired\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'future filter' => strpos( $src, '\'future\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'upcoming filter' => strpos( $src, '\'next30\' === $f[\'pvdak_expiry_filter\']' ) !== false,
    'missing filter' => strpos( $src, "'compare' => 'NOT EXISTS'" ) !== false,
    'reset control' => strpos( $src, 'پاک‌کردن فیلترها' ) !== false,
    'accessible filters' => strpos( $src, 'screen-reader-text' ) !== false,
    'review column' => strpos( $src, '\'pvdak_license\' === $column' ) !== false,
    'expiry column' => strpos( $src, '\'pvdak_license_expiry\' === $column' ) !== false,
    'reviewer column' => strpos( $src, '\'pvdak_reviewer\' === $column' ) !== false,
    'tracking column' => strpos( $src, '\'pvdak_tracking\' === $column' ) !== false,
    'expiry sorting' => strpos( $src, '\'pvdak_expiry\' === $query->get( \'orderby\' )' ) !== false,
    'audit is read only' => strpos( $src, 'get_post_meta( $post->ID, $meta_key, false )' ) !== false && strpos( $src, 'update_post_meta(' ) === false,
    'bounded audit history' => strpos( $src, 'array_slice( $events, -10 )' ) !== false,
    'bootstrapped' => strpos( $boot, "'/includes/professional-admin.php'" ) !== false,
);
foreach ( $checks as $name => $passed ) {
    if ( ! $passed ) { fwrite( STDERR, "PROF_ADMIN_FAIL: $name\n" ); exit( 1 ); }
}
echo 'PVDAK_PROF_ADMIN_SOURCE_OK: ' . count( $checks ) . " static checks; not WP runtime\n";
