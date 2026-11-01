<?php
/**
 * D31 real WordPress/MariaDB integration. ONLY on the disposable CI database.
 * Invoked with WP-CLI after a fresh WordPress install; never on a live site.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ||
     getenv( 'PVDAK_CI_D31_ISOLATED' ) !== '1' ||
     ! defined( 'DB_NAME' ) || DB_NAME !== 'pvdak_ci' ||
     ! defined( 'DB_HOST' ) || DB_HOST !== '127.0.0.1' ) {
    fwrite( STDERR, "D31_WP_ABORT: disposable local CI WordPress required\n" );
    exit( 2 );
}
global $wpdb;
if ( ! $wpdb instanceof wpdb ||
     ! function_exists( 'pvdak_legal_index_window' ) ||
     ! function_exists( 'pvdak_legal_topic_search_batch' ) ) {
    throw new RuntimeException( 'D31 WordPress/theme bootstrap missing' );
}
$checks = 0;
$check = static function ( $ok, $label ) use ( &$checks ) {
    if ( ! $ok ) { throw new RuntimeException( 'D31_WP_FAIL: ' . $label ); }
    ++$checks;
};
$make_page = static function ( $title, $parent = 0, $status = 'publish', $password = '', $slug = '' ) {
    $id = wp_insert_post( array(
        'post_type' => 'page', 'post_status' => $status,
        'post_parent' => $parent, 'post_title' => $title,
        'post_password' => $password, 'post_content' => 'CI-only topic',
        'post_name' => $slug,
    ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        throw new RuntimeException( 'D31_WP_FAIL: fixture insert' );
    }
    return (int) $id;
};
$parent = $make_page( 'CI D31 topics root', 0, 'publish', '', 'legal-topics' );
$foreign = $make_page( 'CI D31 other root' );
$ids = array();
for ( $i = 1; $i <= 60; ++$i ) {
    $ids[] = $make_page( 'Topic ' . str_pad( (string) $i, 3, '0', STR_PAD_LEFT ), $parent );
}
$make_page( 'Topic password-protected', $parent, 'publish', 'ci-secret' );
$make_page( 'Topic draft', $parent, 'draft' );
$make_page( 'Topic foreign parent', $foreign );
$first = pvdak_legal_index_window( $parent );
$check( 24 === count( $first['pages'] ) && '' !== $first['next_cursor'] &&
    '' === $first['prev_cursor'], 'first 24 public index Pages' );
$second = pvdak_legal_index_window( $parent, $first['next_cursor'] );
$third = pvdak_legal_index_window( $parent, $second['next_cursor'] );
$check( 24 === count( $second['pages'] ) && 12 === count( $third['pages'] ) &&
    '' === $third['next_cursor'], 'forward pagination to final 12' );
$all = array_merge( $first['pages'], $second['pages'], $third['pages'] );
$check( $ids === array_map( static function ( $p ) { return (int) $p->ID; }, $all ),
    'exact order and no draft, password or foreign Page leakage' );
$back = pvdak_legal_index_window( $parent, $second['prev_cursor'], 'before' );
$check( array_column( $back['pages'], 'ID' ) === array_column( $first['pages'], 'ID' ),
    'reverse keyset returns exact previous window' );
$check( null === pvdak_legal_seek_decode( $first['next_cursor'], 'index:' . $foreign ),
    'signed index cursor cannot cross parent' );
$search1 = pvdak_legal_topic_search_batch( $parent, 'Topic' );
$search2 = pvdak_legal_topic_search_batch( $parent, 'Topic', $search1['next_cursor'] );
$check( 48 === count( $search1['pages'] ) && 12 === count( $search2['pages'] ) &&
    null === $search2['next_cursor'], 'real SQL search windows 48 + 12' );
$check( $ids === array_merge(
    array_column( $search1['pages'], 'ID' ),
    array_column( $search2['pages'], 'ID' )
), 'search preserves ordering and private-page exclusion' );
$check( null === pvdak_legal_seek_decode( $search1['next_cursor'],
    'search:' . $foreign . ':' . hash( 'sha256', 'topic' ) ),
    'signed search cursor cannot cross parent' );
$queries = array();
$capture = static function ( $sql, $query ) use ( &$queries ) {
    if ( $query->get( 'pvdak_legal_seek_scope' ) ) { $queries[] = $sql; }
    return $sql;
};
add_filter( 'posts_request', $capture, 10, 2 );
pvdak_legal_seek_rows( $parent, 25, pvdak_legal_seek_decode( $first['next_cursor'], 'index:' . $parent ) );
remove_filter( 'posts_request', $capture, 10 );
$sql = end( $queries );
$check( is_string( $sql ) && false !== strpos( $sql, 'menu_order >' ) &&
    false === stripos( $sql, ' OFFSET ' ) && false !== stripos( $sql, 'LIMIT' ),
    'real WordPress SQL contains bounded keyset predicate, no OFFSET' );
$explain = $wpdb->get_results( 'EXPLAIN ' . $sql, ARRAY_A );
$check( is_array( $explain ) && count( $explain ) > 0 && '' === $wpdb->last_error,
    'MariaDB EXPLAIN succeeds on generated keyset query' );
$admin_id = get_current_user_id();
wp_set_current_user( 0 );
$check( array() === pvdak_legal_seek_rows( $parent, 10, null, 'after', true ),
    'unauthenticated staff-only FAQ audit denied' );
wp_set_current_user( $admin_id );
$staff = pvdak_legal_seek_rows( $parent, 81, null, 'after', true );
$check( 61 === count( $staff ), 'authorized staff audit includes password Page, excludes draft' );
// Real WordPress roles: subscriber may navigate public Pages, but cannot
// invoke the staff-only password-protected FAQ audit. Editor may audit.
$subscriber_id = wp_create_user( 'ci_d31_subscriber', 'pvdak_ci_subscriber_disposable_2026', 'ci-d31-subscriber@example.test' );
$editor_id = wp_create_user( 'ci_d31_editor', 'pvdak_ci_editor_disposable_2026', 'ci-d31-editor@example.test' );
if ( is_wp_error( $subscriber_id ) || is_wp_error( $editor_id ) ) {
    throw new RuntimeException( 'D31_WP_FAIL: create isolated test roles' );
}
( new WP_User( $subscriber_id ) )->set_role( 'subscriber' );
( new WP_User( $editor_id ) )->set_role( 'editor' );
$named_subscriber = wp_update_user( array( 'ID' => $subscriber_id, 'display_name' => 'D31_CI_SUBSCRIBER_PRIVATE' ) );
$named_editor = wp_update_user( array( 'ID' => $editor_id, 'display_name' => 'D31_CI_EDITOR_PRIVATE' ) );
$check( ! is_wp_error( $named_subscriber ) && ! is_wp_error( $named_editor ),
    'two isolated HTTP identities have distinct private display names' );
wp_set_current_user( $subscriber_id );
$check( array() === pvdak_legal_seek_rows( $parent, 10, null, 'after', true ),
    'real subscriber cannot perform staff-only FAQ audit' );
$check( 24 === count( pvdak_legal_index_window( $parent )['pages'] ),
    'real subscriber can read public index without private Pages' );
wp_set_current_user( $editor_id );
$check( 61 === count( pvdak_legal_seek_rows( $parent, 81, null, 'after', true ) ),
    'real editor can perform authorized staff audit' );
$check( 48 === count( pvdak_legal_topic_search_batch( $parent, 'Topic' )['pages'] ),
    'real editor search still excludes private Pages' );
wp_set_current_user( $admin_id );

// Configure a disposable HTTP fixture for the same WordPress/MariaDB instance.
// This never runs outside the strictly guarded pvdak_ci database.
update_post_meta( $parent, '_wp_page_template', 'page-templates/legal-topics-index.php' );
$front_id = $make_page( 'CI D31 homepage' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $front_id );
$check( pvdak_legal_topics_root_id() === $parent,
    'homepage resolves the published canonical legal-topics root' );
// Exercise real HTTP cache/robots/referrer headers on a private account route.
// This Page exists only inside the guarded disposable CI WordPress database.
$login_page = $make_page( 'CI D31 login', 0, 'publish', '', 'account-login' );
update_post_meta( $login_page, '_wp_page_template', 'page-templates/pvdak-login.php' );
$check( 'page-templates/pvdak-login.php' === get_page_template_slug( $login_page ),
    'isolated private login Page uses the account template' );
$account_page = $make_page( 'CI D31 account', 0, 'publish', '', 'my-pvdak-account' );
update_post_meta( $account_page, '_wp_page_template', 'page-templates/pvdak-account.php' );
$check( 'page-templates/pvdak-account.php' === get_page_template_slug( $account_page ),
    'isolated account Page uses private per-user dashboard template' );
// Medium-scale SQL regression on the same disposable WordPress/MariaDB DB.
// This is NOT a production-scale latency benchmark or a host acceptance test.
$load_parent = $make_page( 'CI D31 SQL load root', 0, 'publish', '', 'ci-d31-sql-load' );
$load_ids = array();
$load_fixture_start = microtime( true );
for ( $i = 1; $i <= 1200; ++$i ) {
    $load_ids[] = $make_page( 'Load ' . str_pad( (string) $i, 5, '0', STR_PAD_LEFT ), $load_parent );
}
$load_fixture_ms = round( ( microtime( true ) - $load_fixture_start ) * 1000 );
$check( 1200 === count( $load_ids ), '1200 disposable published SQL-load Pages inserted' );
$load_first = pvdak_legal_index_window( $load_parent );
$check( 24 === count( $load_first['pages'] ) && '' !== $load_first['next_cursor'] &&
    (int) $load_first['pages'][0]->ID === $load_ids[0],
    '1200-Page index starts at first 24 with a signed next cursor' );
$load_boundary = get_post( $load_ids[999] );
$load_cursor = pvdak_legal_seek_encode( $load_boundary, 'index:' . $load_parent );
$load_tuple = pvdak_legal_seek_decode( $load_cursor, 'index:' . $load_parent );
$check( '' !== $load_cursor && is_array( $load_tuple ),
    'deep 1000th-row keyset boundary is signed and decodes' );
$load_queries = array();
$load_capture = static function ( $sql, $query ) use ( &$load_queries ) {
    if ( $query->get( 'pvdak_legal_seek_scope' ) ) { $load_queries[] = $sql; }
    return $sql;
};
add_filter( 'posts_request', $load_capture, 10, 2 );
$load_query_start = microtime( true );
try {
    $load_rows = pvdak_legal_seek_rows( $load_parent, 25, $load_tuple );
} finally {
    remove_filter( 'posts_request', $load_capture, 10 );
}
$load_query_ms = round( ( microtime( true ) - $load_query_start ) * 1000, 2 );
$check( $load_ids && array_slice( $load_ids, 1000, 25 ) === array_map(
    static function ( $post ) { return (int) $post->ID; }, $load_rows ),
    'deep keyset query returns exact next 25 IDs without repetition' );
$load_sql = end( $load_queries );
$check( is_string( $load_sql ) &&
    false === stripos( $load_sql, ' OFFSET ' ) &&
    1 === preg_match( '/\\bLIMIT\\s+(?:0\\s*,\\s*)?25\\b/i', $load_sql ) &&
    false !== strpos( $load_sql, 'menu_order >' ),
    'deep generated SQL uses bounded keyset predicate and LIMIT 25, no OFFSET' );
$load_explain = $wpdb->get_results( 'EXPLAIN ' . $load_sql, ARRAY_A );
$check( is_array( $load_explain ) && count( $load_explain ) > 0 &&
    '' === $wpdb->last_error, 'MariaDB EXPLAIN succeeds for deep 1200-Page keyset query' );
$load_back = pvdak_legal_index_window( $load_parent, $load_cursor, 'before' );
$check( array_slice( $load_ids, 975, 24 ) === array_map(
    static function ( $post ) { return (int) $post->ID; }, $load_back['pages'] ),
    'deep reverse keyset returns exact preceding 24 IDs' );
$load_plan = implode( ',', array_map( static function ( $row ) {
    return isset( $row['type'] ) ? (string) $row['type'] : '?';
}, $load_explain ) );
echo 'PVDAK_D31_SQL_LOAD_DIAGNOSTIC: fixture_rows=1200 boundary=1000 fixture_ms=' .
    $load_fixture_ms . ' deep_query_ms=' . $load_query_ms .
    ' explain_types=' . $load_plan .
    " (isolated CI only; no production latency SLA)\n";

echo 'PVDAK_D31_HTTP_ACCOUNT_ID=' . $account_page . "\n";
echo 'PVDAK_D31_HTTP_LOGIN_ID=' . $login_page . "\n";
echo 'PVDAK_D31_HTTP_PARENT_ID=' . $parent . "\n";
echo 'PVDAK_D31_WP_MARIADB_OK: ' . $checks . '/' . $checks .
    " real WordPress/MariaDB checks on disposable CI DB; HTTP/browser/deploy NOT_TESTED.\n";
