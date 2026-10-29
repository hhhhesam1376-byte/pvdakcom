<?php
/** D31 index: actual source, signed keyset forward/back, no WP/DB/network. */
require __DIR__ . '/d31-seek-stub.php';
$GLOBALS['d31_index_template'] = true;
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-index-pagination.php';
for ( $i = 1; $i <= 71; $i++ ) {
    $GLOBALS['d31_rows'][] = d31_page( $i, 'Topic ' . str_pad( $i, 3, '0', STR_PAD_LEFT ) );
}
$GLOBALS['d31_rows'][4]->post_password = 'private';
$GLOBALS['d31_rows'][9]->post_status = 'draft';
$first = pvdak_legal_index_window( 7 );
$second = pvdak_legal_index_window( 7, $first['next_cursor'] );
$third = pvdak_legal_index_window( 7, $second['next_cursor'] );
d31_check( 24 === count( $first['pages'] ) && '' === $first['prev_cursor'] &&
    '' !== $first['next_cursor'], 'first window' );
d31_check( 24 === count( $second['pages'] ) && '' !== $second['prev_cursor'] &&
    '' !== $second['next_cursor'], 'second window' );
d31_check( 21 === count( $third['pages'] ) && '' === $third['next_cursor'], 'last window' );
$all = array_merge( $first['pages'], $second['pages'], $third['pages'] );
d31_check( 69 === count( array_unique( array_map( static function ( $p ) { return $p->ID; }, $all ) ) ),
    'all public pages without duplication' );
d31_check( ! in_array( 5, array_column( $all, 'ID' ), true ) &&
    ! in_array( 10, array_column( $all, 'ID' ), true ), 'password and draft excluded' );
$back = pvdak_legal_index_window( 7, $third['prev_cursor'], 'before' );
d31_check( array_column( $back['pages'], 'ID' ) === array_column( $second['pages'], 'ID' ),
    'previous navigation restores identical second window' );
$back_first = pvdak_legal_index_window( 7, $back['prev_cursor'], 'before' );
d31_check( array_column( $back_first['pages'], 'ID' ) === array_column( $first['pages'], 'ID' ) &&
    '' === $back_first['prev_cursor'], 'previous navigation restores first window' );
d31_check( null === pvdak_legal_seek_decode( $first['next_cursor'], 'index:8' ),
    'parent-bound token cannot cross archives' );
$forged = substr_replace( $first['next_cursor'], '0', 0, 1 );
d31_check( null === pvdak_legal_seek_decode( $forged, 'index:7' ), 'tampered token rejected' );
d31_check( array_column( pvdak_legal_index_window( 7, $forged )['pages'], 'ID' ) ===
    array_column( $first['pages'], 'ID' ), 'invalid cursor safely restarts' );
d31_check( array() === pvdak_legal_index_window( 8 )['pages'], 'foreign parent rejected' );
foreach ( array( array( 'pvdak_topics_after' => array( 'x' ) ),
                 array( 'pvdak_topics_after' => 'a', 'pvdak_topics_before' => 'b' ) ) as $bad ) {
    $_GET = $bad;
    d31_check( '' === pvdak_legal_index_request()['token'], 'ambiguous GET rejected' );
}
$_GET = array( 'pvdak_topics_before' => $third['prev_cursor'] );
d31_check( 'before' === pvdak_legal_index_request()['direction'], 'backward GET parsed' );
d31_check( isset( pvdak_legal_index_robots( array() )['noindex'] ), 'cursor archive noindex' );
$_GET = array( 'pvdak_topics_page' => '9999' );
d31_check( isset( pvdak_legal_index_robots( array() )['noindex'] ), 'legacy OFFSET URL noindex' );
$_GET = array(); $GLOBALS['d31_index_template'] = false;
d31_check( array() === pvdak_legal_index_robots( array() ), 'clean unrelated page indexable' );
d31_check( count( $GLOBALS['d31_filters']['posts_where'] ?? array() ) === 0,
    'seek WHERE filter removed after every query' );
d31_check( max( array_column( $GLOBALS['d31_queries'], 'posts_per_page' ) ) === 25 &&
    ! array_filter( $GLOBALS['d31_queries'], static function ( $q ) { return isset( $q['offset'] ); } ),
    'bounded queries without SQL OFFSET' );
d31_check( false !== strpos( implode( ' ', $GLOBALS['d31_where'] ), 'wp_posts.post_title' ),
    'prepared compound WHERE applied' );
$GLOBALS['d31_parent'] = d31_page( 7, 'Protected parent', 'publish', 'secret' );
d31_check( array() === pvdak_legal_index_window( 7 )['pages'],
    'password-protected root blocks public index' );
unset( $GLOBALS['d31_parent'] );
echo 'PVDAK_D31_INDEX_OK: ' . $GLOBALS['d31_checks'] . '/' . $GLOBALS['d31_checks'] .
    " original-source seek cases; WordPress/DB/HTTP NOT_TESTED.\n";
