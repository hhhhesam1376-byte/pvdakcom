<?php
/** Static source-only checks; no WordPress, database or browser is invoked. */
$root = dirname( __DIR__, 2 );
$src = file_get_contents( $root . '/packages/pvdak-core/includes/request-admin.php' );
$boot = file_get_contents( $root . '/packages/pvdak-core/pvdak-core.php' );
$setup = file_get_contents( $root . '/packages/pvdak-core/includes/admin.php' );
if ( false === $src || false === $boot || false === $setup ) { fwrite( STDERR, "PVDAK_CASE_ADMIN_SOURCE_MISSING\n" ); exit( 2 ); }
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
    'operational filters' => strpos( $src, 'function pvdak_core_case_ops_filters()' ) !== false,
    'assignment validity' => strpos( $src, '^[1-9][0-9]*$' ) !== false,
    'consistency checks' => strpos( $src, "'matched_missing'" ) !== false && strpos( $src, "'assigned_pending'" ) !== false,
    'modified date column' => strpos( $src, "'post_modified'" ) !== false,
    'explicit status preservation' => strpos( $src, 'if ( ! $query->get( \'post_status\' )' ) !== false,
    'per record capability' => strpos( $src, 'current_user_can( \'edit_post\', $post_id )' ) !== false,
    'future age flag' => strpos( $src, 'تاریخ ثبت آینده</strong>' ) !== false,
    'assignment review link' => strpos( $src, "'post_row_actions'" ) !== false,
    'private default for custom filters' => strpos( $src, "'post_status', 'private'" ) !== false,
    'precise age cutoffs' => strpos( $src, 'pvdak_core_triage_cutoff( 7, $now )' ) !== false,
    'future registration filter' => strpos( $src, '\'future\' === $age' ) !== false,
    'deduplicated admin columns' => strpos( $src, 'unset( $columns[\'pvdak_kind\']' ) !== false,
    'invalid professional warning' => strpos( $src, 'پرونده حرفه‌ای نامعتبر' ) !== false,
    'malformed stored assignment IDs cannot be silently coerced' =>
        strpos( $src, "preg_match( '/^[1-9][0-9]{0,9}$/D', (string) \$raw_profile_id )" ) !== false &&
        strpos( $src, 'ارجاع ناقص یا نامعتبر؛ بررسی شود' ) !== false,
    'professional list checks nonpublic profile status' =>
        strpos( $src, "get_post_status( \$profile_id ), array( 'private', 'draft' )" ) !== false,
    'client-facing Core settings keep controls outside technical guide' =>
        strpos( $setup, '<details><summary>اطلاعات تکمیلی امکانات' ) !== false &&
        strpos( $setup, "echo '</tbody></table></details>';" ) !== false,
    'new service page slug verified after insert' =>
        strpos( $setup, "\$slug !== get_post_field( 'post_name', \$id )" ) !== false,
    'draft generator checks actual insert result and retains existing pages' =>
        strpos( $setup, "get_page_by_path( \$slug, OBJECT, 'page' )" ) !== false &&
        strpos( $setup, 'wp_insert_post( array(' ) !== false &&
        strpos( $setup, 'is_wp_error( $id )' ) !== false &&
        strpos( $setup, "'draft' !== get_post_status( \$id )" ) !== false,
    'draft generator distinguishes partial failure from successful creation' =>
        strpos( $setup, "'partial' : 'failed'" ) !== false &&
        strpos( $setup, "'created' : 'existing'" ) !== false &&
        strpos( $setup, "'pvdak_pages'" ) !== false,
    'future service page titles use PVDak branding' =>
        strpos( $setup, 'پنل کاربری PVDak' ) !== false &&
        strpos( $setup, 'همکاری با متخصصان PVDak' ) !== false,
    'malformed supplied numeric filters fail closed rather than widening private queue' =>
        strpos( $src, 'function pvdak_core_case_admin_positive_filter( $name )' ) !== false &&
        strpos( $src, 'array_key_exists( $name, $_GET )' ) !== false &&
        strpos( $src, '(float) $raw > 2147483647' ) !== false &&
        strpos( $src, "return -1;" ) !== false &&
        strpos( $src, "'post__in', array( 0 )" ) !== false,
    'stale or invalid topic is rejected and warning is visible' =>
        strpos( $src, '! pvdak_core_valid_specialty( $topic ) ? -1' ) !== false &&
        strpos( $src, 'شناسه یا موضوع فیلتر نامعتبر است' ) !== false &&
        strpos( $src, '$f[ $name ] > 0 ? $f[ $name ]' ) !== false,
    'no data mutations' => strpos( $src, 'update_post_meta(' ) === false && strpos( $src, 'wp_insert_post(' ) === false,
);
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "PVDAK_CASE_ADMIN_FAIL: $label\n" ); exit( 1 ); }
}
echo 'PVDAK_CASE_ADMIN_SOURCE_OK: ' . count( $checks ) . " static checks; WordPress runtime not tested\n";
