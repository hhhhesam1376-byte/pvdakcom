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
 'defense in depth owner' => strpos($src, '(int) $item->post_author === $user_id') !== false,
 'defense in depth private' => strpos($src, "'private' === \$item->post_status") !== false,
 'ten per page' => strpos($src, "'posts_per_page' => 10") !== false,
 'bounded page' => strpos($src, 'min( 1000, absint(') !== false,
 'status allowlist' => strpos($src, 'isset( $statuses[$raw_status] )') !== false,
 'type allowlist' => strpos($src, 'isset( $types[$raw_type] )') !== false,
 'search sanitized' => strpos($src, 'sanitize_text_field( wp_unslash(') !== false,
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
 'dashboard private-only guard' => strpos($portal, "'private' === \$item->post_status") !== false,
 'member fallback verifies owner' => strpos($members, "(int) get_post_field( 'post_author', \$id ) !== \$user_id") !== false,
 'member create verifies pending meta' => strpos($members, "'pending' !== get_post_meta( \$id, '_pvdak_member_state', true )") !== false
);
foreach($checks as $name=>$ok) { if(!$ok){fwrite(STDERR,"REQUEST_HISTORY_FAIL: $name\n");exit(1);} }
echo 'PVDAK_REQUEST_HISTORY_OK: '.count($checks)." source checks (not WP/browser tests)\n";
