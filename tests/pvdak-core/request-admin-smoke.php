<?php
/** Static source-only checks; no WordPress, database or browser is invoked. */
$root = dirname( __DIR__, 2 );
$src = file_get_contents( $root . '/packages/pvdak-core/includes/request-admin.php' );
$boot = file_get_contents( $root . '/packages/pvdak-core/pvdak-core.php' );
if ( false === $src || false === $boot ) { fwrite( STDERR, "PVDAK_CASE_ADMIN_SOURCE_MISSING\n" ); exit( 2 ); }
$checks = array(
    'private CPT capability' => strpos( $src, 'get_post_type_object( \'pvdak_request\' )' ) !== false,
    'main query' => strpos( $src, '$query->is_main_query()' ) !== false,
    'admin list only' => strpos( $src, '\'edit.php\' !== $pagenow' ) !== false,
    'type guard' => strpos( $src, '\'pvdak_request\' !== $query->get( \'post_type\' )' ) !== false,
    'GET sanitization' => strpos( $src, 'wp_unslash( $_GET[' ) !== false,
    'bounded numeric ids' => strpos( $src, '[1-9][0-9]{0,9}' ) !== false,
    'allowlisted ages' => strpos( $src, '\'today\', \'last7\', \'older7\', \'older30\'' ) !== false,
    'allowlisted flags' => strpos( $src, '\'yes\', \'no\'' ) !== false,
    'valid topic' => strpos( $src, 'pvdak_core_valid_specialty( $topic )' ) !== false,
    'missing topic' => strpos( $src, '\'missing\' === $f[\'pvdak_case_topic\']' ) !== false,
    'owner filter' => strpos( $src, '\'author\', $f[\'pvdak_case_owner\']' ) !== false,
    'tracking exact' => strpos( $src, '\'post__in\', $ids' ) !== false,
    'preserve meta query' => strpos( $src, '$query->get( \'meta_query\' )' ) !== false,
    'preserve date query' => strpos( $src, '$query->get( \'date_query\' )' ) !== false,
    'notes existence only' => strpos( $src, '\'_pvdak_internal_notes\'' ) !== false,
    'audit existence only' => strpos( $src, '\'_pvdak_state_audit\'' ) !== false,
    'age sorting' => strpos( $src, '\'oldest\' === $f[\'pvdak_case_order\']' ) !== false,
    'tracking column' => strpos( $src, '\'pvdak_case_ref\' === $column' ) !== false,
    'topic column' => strpos( $src, '\'pvdak_case_topic_column\' === $column' ) !== false,
    'age column' => strpos( $src, '\'pvdak_case_age_column\' === $column' ) !== false,
    'activity column' => strpos( $src, '\'pvdak_case_activity\' === $column' ) !== false,
    'modified column' => strpos( $src, '\'pvdak_case_modified\' === $column' ) !== false,
    'accessible controls' => strpos( $src, 'screen-reader-text' ) !== false,
    'reset filters' => strpos( $src, 'پاک‌کردن فیلترهای صف' ) !== false,
    'bootstrapped' => strpos( $boot, '\'/includes/request-admin.php\'' ) !== false,
    'no data mutations' => strpos( $src, 'update_post_meta(' ) === false && strpos( $src, 'wp_insert_post(' ) === false,
);
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "PVDAK_CASE_ADMIN_FAIL: $label\n" ); exit( 1 ); }
}
echo 'PVDAK_CASE_ADMIN_SOURCE_OK: ' . count( $checks ) . " static checks; WordPress runtime not tested\n";
