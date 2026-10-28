<?php
/** Static source-only regression for the private legal request history UI. */
$root = dirname(__DIR__, 2);
$requests = file_get_contents($root.'/packages/pvdak-core/includes/requests.php');
$css = file_get_contents($root.'/packages/pvdak-core/assets/forms.css');
$portal = file_get_contents($root.'/packages/pvdak-core/includes/portal.php');
$members = file_get_contents($root.'/packages/pvdak-core/includes/professionals.php');
if (false === $requests || false === $css || false === $portal || false === $members) { fwrite(STDERR, "REQUEST_HISTORY_FAIL: missing source\n"); exit(2); }
$start = strpos($requests, 'function pvdak_core_my_requests()');
$end = strpos($requests, 'Triage-only workflow', $start);
$src = substr($requests, $start, $end - $start);
$checks = array(
 'login required' => strpos($src, 'if ( ! is_user_logged_in() )') !== false,
 'author scoped query' => strpos($src, "'author' => \$user_id") !== false,
 'private post status' => strpos($src, "'post_status' => 'private'") !== false,
 'defense in depth owner' => strpos($src, 'pvdak_core_request_positive_id( $item->post_author ) === $user_id') !== false,
 'defense in depth rejects malformed row IDs' =>
    strpos($src, 'pvdak_core_request_positive_id( $item->ID ) > 0') !== false,
  'saved request owner is strictly parsed' =>
    strpos($requests, 'pvdak_core_request_positive_id( get_post_field( \'post_author\', $id ) ) !== $user_id') !== false,
  'defense in depth private' => strpos($src, "'private' === \$item->post_status") !== false,
 'ten per page' => strpos($src, "'posts_per_page' => 10") !== false,
 'bounded page' => strpos($src, 'min( 1000, (int) $raw_page )') !== false,
 'status allowlist' => strpos($src, 'isset( $statuses[$raw_status] )') !== false,
 'type allowlist' => strpos($src, 'isset( $types[$raw_type] )') !== false,
 'search sanitized' => strpos($src, 'sanitize_text_field( $search_input )') !== false,
 'unicode search bound' => strpos($src, "preg_match_all( '/./us'") !== false,
 'sort allowlist' => strpos($src, "'asc' === \$raw_order ? 'ASC' : 'DESC'") !== false,
 'status filter' => strpos($src, "'_pvdak_request_state'") !== false,
 'type filter' => strpos($src, "'_pvdak_request_type'") !== false,
 'plain permalink route' => strpos($src, 'name="page_id"') !== false,
 'filter reset' => strpos($src, 'pvdak-core-request-reset') !== false,
 'filtered pagination' => strpos($src, "'add_fragment' => '#pvdak-requests'") !== false,
 'out of range recovery' => strpos($src, 'رفتن به آخرین صفحه موجود') !== false,
 'result count' => strpos($src, 'number_format_i18n( $total )') !== false,
 'empty filter result' => strpos($src, 'درخواستی با این فیلترها پیدا نشد') !== false,
 'request reference number' => strpos($src, 'شماره پیگیری') !== false,
 'status badge' => strpos($src, 'pvdak-core-request-state--') !== false,
 'registration date' => strpos($src, "get_the_date( 'Y-m-d'") !== false,
 'published topic only' => strpos($src, "'publish' === get_post_status( \$topic_id )") !== false,
 'no case descriptions in output' => strpos($src, 'get_post_field(') === false && strpos($src, 'post_content') === false,
 'no internal notes in output' => strpos($src, 'get_post_meta( $item->ID, \'_pvdak_internal_notes\'') === false,
 'responsive filter' => strpos($css, '@media(max-width:650px){.pvdak-core-request-filters') !== false,
 'keyboard focus' => strpos($css, '.pvdak-core-request-filters :is(input,select,button,a):focus-visible') !== false,
 'privacy notice' => strpos($src, 'pvdak-core-request-privacy') !== false,
 'safe labels' => strpos($src, 'esc_html( get_the_title( $item->ID ) )') !== false,
 'request query object guard' => strpos($src, '$item instanceof WP_Post') !== false,
 'intake requires a positive decimal topic id rather than absint of forged input' =>
    strpos($requests, 'pvdak_core_request_positive_id( $raw_landing )') !== false &&
    strpos($requests, 'strlen( $raw_landing ) <= 10') !== false,
 'intake errors are scoped to their submitted service' =>
    strpos($requests, 'function pvdak_core_request_feedback_for_type(') !== false &&
    strpos($requests, "'pvdak_error_type' =>") !== false &&
    strpos($requests, 'pvdak_core_request_feedback_for_type( $feedback_type, $type )') !== false,
 'intake form enforces the same read permission as submit' =>
    strpos($requests, "if ( ! current_user_can( 'read' ) )") !== false,
 'request shortcode rejects malformed non-string type' =>
    strpos($requests, "if ( ! is_string( \$atts['type'] ) ) { return ''; }") !== false,
 'intake errors show safe reason-specific feedback' =>
    strpos($requests, 'function pvdak_core_request_error_message(') !== false &&
    strpos($requests, "'limit' =>") !== false &&
    strpos($requests, "'closed' =>") !== false &&
    strpos($requests, 'esc_html( pvdak_core_request_error_message( $error_code ) )') !== false &&
    strpos($requests, "is_string( \$_GET['pvdak_error'] )") !== false,
 'private history and admin topic never remap malformed IDs' =>
    strpos($requests, "pvdak_core_request_positive_id( get_post_meta( \$item->ID, '_pvdak_legal_landing_id', true ) )") !== false &&
    strpos($requests, "pvdak_core_request_positive_id( get_post_meta( \$post->ID, '_pvdak_legal_landing_id', true ) )") !== false,
 'receipt and saved topic use strict positive ID validation' =>
    strpos($requests, 'pvdak_core_request_positive_id( $receipt[\'id\'] )') !== false &&
    strpos($requests, 'pvdak_core_request_positive_id( $owner_id )') !== false &&
    strpos($requests, 'pvdak_core_request_positive_id( get_post_meta( $id, \'_pvdak_legal_landing_id\', true ) )') !== false,
 'intake option cannot trigger array-to-string conversion' =>
    strpos($requests, "in_array( get_option( 'pvdak_core_intake_enabled', '0' ), array( '1', 1, true ), true )") !== false,
 'history rejects malformed page numbers and oversized search inputs' =>
    strpos($requests, 'ctype_digit( (string) $raw_page )') !== false &&
    strpos($requests, 'strlen( $search_input ) <= 500') !== false,
 'history verifies requested type and status against each returned private row' =>
    strpos($src, '$type_filter === get_post_meta( $item->ID,') !== false &&
    strpos($src, '$status_filter === get_post_meta( $item->ID,') !== false,
 'history fails closed on malformed query rows or impossible totals' =>
    strpos($src, '! is_array( $query->posts )') !== false &&
    strpos($src, '! is_numeric( $query->found_posts )') !== false,
 'history rejects impossible page totals and more than ten filtered rows' =>
    strpos($src, 'ctype_digit( (string) $query->found_posts )') !== false &&
    strpos($src, 'ctype_digit( (string) $query->max_num_pages )') !== false &&
    strpos($src, 'count( $items ) > 10') !== false &&
    strpos($src, '(int) ceil( $reported_total / 10 )') !== false &&
    strpos($src, '( $page - 1 ) * 10 + count( $items ) > $reported_total') !== false,
 'history fails closed when filters return foreign or invalid case rows' =>
    strpos($requests, '$unexpected_rows = true;') !== false &&
    strpos($requests, 'if ( $unexpected_rows )') !== false &&
    strpos($requests, "'unknown'") !== false,
 'dashboard active case count excludes unknown or corrupted states' =>
    strpos($portal, "array( 'submitted', 'review', 'matched', 'answered' )") !== false,
 'private portal uses current PVDak branding' =>
    strpos($portal, 'پنل کاربری PVDak') !== false &&
    strpos($portal, 'همکاری با PVDak') !== false,
 'dashboard private-only guard' => strpos($portal, "'private' === \$item->post_status") !== false,
 'member fallback verifies owner' => strpos($members, "(int) get_post_field( 'post_author', \$id ) !== \$user_id") !== false,
 'member create verifies pending meta' => strpos($members, "'pending' !== get_post_meta( \$id, '_pvdak_member_state', true )") !== false
);
foreach($checks as $name=>$ok) { if(!$ok){fwrite(STDERR,"REQUEST_HISTORY_FAIL: $name\n");exit(1);} }
echo 'PVDAK_REQUEST_HISTORY_OK: '.count($checks)." source checks (not WP/browser tests)\n";
