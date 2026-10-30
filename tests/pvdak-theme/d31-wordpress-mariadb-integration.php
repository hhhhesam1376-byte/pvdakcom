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
$make_page = static function ( $title, $parent = 0, $status = 'publish', $password = '' ) {
    $id = wp_insert_post( array(
        'post_type' => 'page', 'post_status' => $status,
        'post_parent' => $parent, 'post_title' => $title,
        'post_password' => $password, 'post_content' => 'CI-only topic',
    ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        throw new RuntimeException( 'D31_WP_FAIL: fixture insert' );
    }
    return (int) $id;
};
$parent = $make_page( 'CI D31 topics root' );
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
$subscriber_id = wp_create_user( 'ci_d31_subscriber', wp_generate_password( 32 ), 'ci-d31-subscriber@example.test' );
$editor_id = wp_create_user( 'ci_d31_editor', wp_generate_password( 32 ), 'ci-d31-editor@example.test' );
if ( is_wp_error( $subscriber_id ) || is_wp_error( $editor_id ) ) {
    throw new RuntimeException( 'D31_WP_FAIL: create isolated test roles' );
}
( new WP_User( $subscriber_id ) )->set_role( 'subscriber' );
( new WP_User( $editor_id ) )->set_role( 'editor' );
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
echo 'PVDAK_D31_HTTP_PARENT_ID=' . $parent . "\n";
echo 'PVDAK_D31_WP_MARIADB_OK: ' . $checks . '/' . $checks .
    " real WordPress/MariaDB checks on disposable CI DB; HTTP/browser/deploy NOT_TESTED.\n";
