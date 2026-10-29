<?php
/** D31 search: original-source signed seek cursor regression without WP/DB/network. */
require __DIR__ . '/d31-seek-stub.php';
require_once dirname( __DIR__, 2 ) . '/inc/pvdak-legal-landings.php';
// D31: seek is a shared boundary. Reject malformed internal inputs before
// WP_Query can coerce a different parent, cursor, direction or query limit.
$bad_seek_cases = array(
    'lossy parent' => array( '7junk', 41 ),
    'zero parent' => array( 0, 41 ),
    'lossy limit' => array( 7, '41' ),
    'zero limit' => array( 7, 0 ),
    'unknown direction' => array( 7, 41, null, 'sideways' ),
    'sparse tuple' => array( 7, 41, array( 0 => 0, 2 => 9 ) ),
    'string order' => array( 7, 41, array( '0', 'A', 9 ) ),
    'oversized title' => array( 7, 41, array( 0, str_repeat( 'a', 1025 ), 9 ) ),
    'invalid UTF-8' => array( 7, 41, array( 0, "\xFF", 9 ) ),
    'zero tuple ID' => array( 7, 41, array( 0, 'A', 0 ) ),
    'oversized menu order' => array( 7, 41, array( 10000000000, 'A', 9 ) ),
);
foreach ( $bad_seek_cases as $label => $args ) {
    $query_count = count( $GLOBALS['d31_queries'] );
    d31_check( array() === pvdak_legal_seek_rows( ...$args ) &&
        $query_count === count( $GLOBALS['d31_queries'] ),
        'invalid seek rejected without SQL: ' . $label );
}
$GLOBALS['d31_rows'] = array( d31_page( 1, 'A' ), d31_page( 2, 'B' ), d31_page( 3, 'C' ) );
$seek_first = pvdak_legal_seek_rows( 7, 2 );
d31_check( array( 1, 2 ) === array_column( $seek_first, 'ID' ),
    'valid bounded seek still returns first two Pages' );
// D31: generated cursors must satisfy the same tuple constraints as decoding.
$long_title = d31_page( 88, str_repeat( 'x', 1025 ) );
$bad_utf8_title = d31_page( 89, "\xFF" );
d31_check( null === pvdak_legal_seek_tuple( $long_title ) &&
    '' === pvdak_legal_seek_encode( $long_title, 'seek-contract:7' ),
    'oversized post title never creates an unusable signed cursor' );
d31_check( null === pvdak_legal_seek_tuple( $bad_utf8_title ) &&
    '' === pvdak_legal_seek_encode( $bad_utf8_title, 'seek-contract:7' ),
    'invalid UTF-8 title cannot become a cursor' );
$seek_token = pvdak_legal_seek_encode( $seek_first[1], 'seek-contract:7' );
$seek_tuple = pvdak_legal_seek_decode( $seek_token, 'seek-contract:7' );
d31_check( is_array( $seek_tuple ) && 2 === $seek_tuple[2] &&
    null === pvdak_legal_seek_decode( $seek_token, 'other-parent' ),
    'signed tuple remains usable and scoped' );
d31_check( array( 3 ) === array_column( pvdak_legal_seek_rows( 7, 2, $seek_tuple ), 'ID' ),
    'forward seek preserved' );
d31_check( array( 1 ) === array_column( pvdak_legal_seek_rows( 7, 2, $seek_tuple, 'before' ), 'ID' ),
    'reverse seek preserved' );
d31_check( empty( $GLOBALS['d31_filters']['posts_where'] ),
    'seek SQL filter always detached' );
$GLOBALS['d31_rows'] = array();
$GLOBALS['d31_queries'] = array();
for ( $i = 0; $i < 600; ++$i ) {
    $GLOBALS['d31_rows'][] = d31_page( $i + 1, $i < 120 ? 'حقوق شرکت' : 'كسب و كار' );
}
$first = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت' );
d31_check( 48 === count( $first['pages'] ) && is_string( $first['next_cursor'] ),
    'first search window with signed token' );
$second = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $first['next_cursor'] );
d31_check( 48 === count( $second['pages'] ), 'second window' );
$third = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $second['next_cursor'] );
d31_check( 24 === count( $third['pages'] ) && null !== $third['next_cursor'],
    'partial third window' );
$fourth = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $third['next_cursor'] );
d31_check( array() === $fourth['pages'] && null !== $fourth['next_cursor'],
    'empty search window continues' );
$last = pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', $fourth['next_cursor'] );
d31_check( array() === $last['pages'] && null === $last['next_cursor'], 'search exhausted' );
$all = array_merge( $first['pages'], $second['pages'], $third['pages'] );
d31_check( 120 === count( array_unique( array_column( $all, 'ID' ) ) ),
    'all matches returned without duplicates' );
d31_check( 48 === count( pvdak_legal_topic_search_batch( 7, 'کسب و کار' )['pages'] ),
    'Persian and Arabic text normalization' );
d31_check( null === pvdak_legal_seek_decode( $first['next_cursor'],
    'search:7:' . hash( 'sha256', pvdak_legal_normalize_topic_text( 'کسب و کار' ) ) ),
    'search token bound to normalized term' );
d31_check( 48 === count( pvdak_legal_topic_search_batch( 7, 'حقوق شرکت', 'invalid' )['pages'] ),
    'invalid token restarts safely' );
$GLOBALS['d31_rows'] = array();
for ( $i = 0; $i < 351; ++$i ) {
    $GLOBALS['d31_rows'][] = d31_page( $i + 1, $i >= 290 ? 'پاسخ حقوقی' : 'نمونه' );
}
$empty = pvdak_legal_topic_search_batch( 7, 'پاسخ حقوقی' );
d31_check( array() === $empty['pages'] && is_string( $empty['next_cursor'] ),
    'sparse first window' );
$continued = pvdak_legal_topic_search_batch( 7, 'پاسخ حقوقی', $empty['next_cursor'] );
d31_check( 48 === count( $continued['pages'] ), 'late matches are reachable' );
$GLOBALS['d31_rows'] = array( d31_page( 1, 'خصوصی', 'publish', 'secret' ),
    d31_page( 2, 'خصوصی', 'draft' ), d31_page( 3, 'خصوصی' ) );
$public = pvdak_legal_topic_search_batch( 7, 'خصوصی' );
d31_check( 1 === count( $public['pages'] ) && 3 === $public['pages'][0]->ID,
    'draft and password pages excluded' );
$foreign = d31_page( 91, 'بیگانه' ); $foreign->post_parent = 99;
$wrong_type = d31_page( 92, 'نوشته' ); $wrong_type->post_type = 'post';
$invalid_id = d31_page( 93, 'شناسه' ); $invalid_id->ID = -3;
$GLOBALS['d31_injected'] = array( d31_page( 1, 'منتشر' ), d31_page( 2, 'پیش‌نویس', 'draft' ),
    d31_page( 3, 'رمزدار', 'publish', 'secret' ), $foreign, $wrong_type, $invalid_id );
$preview = pvdak_legal_topic_pages( 7 );
d31_check( 1 === count( $preview ) && 1 === $preview[0]->ID,
    'preview rejects injected unsafe rows' );
d31_check( 2 === count( pvdak_legal_filter_topic_pages( $GLOBALS['d31_injected'], '' ) ),
    'generic filter excludes unsafe rows without parent context' );
$bad_id = d31_page( 97, 'منتشر' ); $bad_id->ID = '097';
$bad_parent = d31_page( 98, 'منتشر' ); $bad_parent->post_parent = '7junk';
$valid = d31_page( 99, 'منتشر' );
$GLOBALS['d31_injected'] = array( $bad_id, $bad_parent, $valid, $valid );
$guarded = pvdak_legal_topic_search_batch( 7, 'منتشر' );
d31_check( 1 === count( $guarded['pages'] ) && 99 === $guarded['pages'][0]->ID,
    'search rejects malformed and duplicate filtered rows' );
// The unfiltered homepage preview and generic filter must not display
// duplicate Page cards when a WordPress posts filter replays a row.
$GLOBALS['d31_injected'] = array( $valid, $valid );
d31_check( array( 99 ) === array_column( pvdak_legal_topic_pages( 7 ), 'ID' ),
    'homepage topic preview deduplicates injected Page IDs' );
d31_check( array( 99 ) === array_column( pvdak_legal_filter_topic_pages( array( $valid, $valid ), '' ), 'ID' ),
    'generic topic filter deduplicates even with empty search' );
unset( $GLOBALS['d31_injected'] );
// A third-party posts filter may ignore the cursor and replay the same
// 41-row window. Stop after detecting the repeated boundary, not after
// exhausting six identical SQL calls or emitting an endless next link.
$replayed = array( d31_page( 901, 'منتشر' ) );
for ( $i = 0; $i < 40; ++$i ) {
    $replayed[] = d31_page( 1000 + $i, 'پیش‌نویس', 'draft' );
}
$GLOBALS['d31_injected'] = $replayed;
$GLOBALS['d31_queries'] = array();
$stalled = pvdak_legal_topic_search_batch( 7, 'منتشر' );
d31_check( 1 === count( $stalled['pages'] ) && null === $stalled['next_cursor'] &&
    2 === count( $GLOBALS['d31_queries'] ), 'replayed search window terminates without a looping cursor' );
unset( $GLOBALS['d31_injected'] );
$_GET = array( 'pvdak_topic_after' => $first['next_cursor'] );
d31_check( $first['next_cursor'] === pvdak_legal_topic_search_cursor(),
    'GET token retained as opaque string' );
d31_check( isset( pvdak_legal_search_robots( array() )['noindex'] ), 'seek URL noindex' );
$_GET = array( 'pvdak_topic_offset' => '9999999' );
d31_check( isset( pvdak_legal_search_robots( array() )['noindex'] ), 'legacy offset URL noindex' );
$_GET = array();
d31_check( array() === pvdak_legal_search_robots( array() ), 'clean homepage indexable' );
d31_check( ! array_filter( $GLOBALS['d31_queries'], static function ( $q ) {
    return isset( $q['offset'] ) || $q['posts_per_page'] > 49;
} ), 'all search and preview queries bounded with no OFFSET' );
d31_check( count( $GLOBALS['d31_filters']['posts_where'] ?? array() ) === 0,
    'no leaked SQL filter after searches' );
$GLOBALS['d31_parent'] = d31_page( 7, 'Protected parent', 'publish', 'secret' );
d31_check( array() === pvdak_legal_topic_search_batch( 7, 'منتشر' )['pages'],
    'password-protected root blocks public search' );
unset( $GLOBALS['d31_parent'] );
echo 'PVDAK_D31_SEARCH_OK: ' . $GLOBALS['d31_checks'] . '/' . $GLOBALS['d31_checks'] .
    " original-source seek cases; WordPress/DB/HTTP NOT_TESTED.\n";
