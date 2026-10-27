<?php
/** Static source-only professional portal safeguards; NOT a WP integration test. */
$root = dirname(__DIR__, 2);
$src = file_get_contents($root . '/packages/pvdak-core/includes/professional-portal.php');
$css = file_get_contents($root . '/packages/pvdak-core/assets/forms.css');
if (false === $src || false === $css) { fwrite(STDERR, "PROF_PORTAL_FAIL: missing source\n"); exit(2); }
$checks = array(
 'login required' => strpos($src, 'if ( ! is_user_logged_in() )') !== false,
 'owner check' => strpos($src, "get_post_field( 'post_author', \$profile_id )") !== false,
 'private or draft only' => strpos($src, "array( 'private', 'draft' )") !== false,
 'application type check' => strpos($src, "'pvdak_professional' === get_post_type") !== false,
 'published join link' => strpos($src, "pvdak_core_published_page_link( 'join-legal-community'") !== false,
 'intake kill switch' => strpos($src, 'pvdak_core_member_intake_open()') !== false,
 'account destination' => strpos($src, 'pvdak_core_account_url()') !== false,
 'published contact only' => strpos($src, "'publish' === \$contact->post_status") !== false,
 'tracking reference' => strpos($src, 'شماره پیگیری') !== false,
 'machine-readable date' => strpos($src, '<time datetime=') !== false,
 'city fallback' => strpos($src, 'ثبت نشده') !== false,
 'status allowlist' => strpos($src, 'pvdak_core_professional_visible_state(') !== false &&
     strpos($src, 'isset( $statuses[ $state ] )') !== false,
 'unknown status does not impersonate pending' => strpos($src, "return 'unknown';") !== false &&
     strpos($src, "'unknown' === $status ? 'نیازمند بررسی مدیر'") !== false,
 'unknown membership category fails closed' => strpos($src, "isset( \$types[ \$type ] )") !== false &&
     strpos($src, 'نوع همکاری نامعتبر؛ نیازمند بررسی مدیر') !== false,
 'draft approval is not presented as accepted' => strpos($src, "'draft' === \$visibility && 'approved' === \$state") !== false,
 'readiness rechecks saved membership state' => strpos($src, "'approved' !== get_post_meta( \$id, '_pvdak_member_state', true )") !== false,
 'readiness rechecks saved member type' => strpos($src, "'lawyer' !== get_post_meta( \$id, '_pvdak_member_type', true )") !== false,
 'capacity error blocks readiness' => strpos($src, 'PHP_INT_MAX === $active') !== false,
 'unknown state hides normal progress steps' => strpos($src, 'elseif ( \'unknown\' === $status )') !== false &&
     strpos($src, 'مراحل بررسی تا اصلاح توسط مدیر نمایش داده نمی‌شوند') !== false,
 'unknown state never unlocks professional access' => strpos($src, '\'unknown\' === $status ?') !== false &&
     strpos($src, "array( 'ready' => false, 'reason' =>") !== false,
 'status steps' => strpos($src, 'aria-current="step"') !== false,
 'rejection distinct' => strpos($src, "'rejected' === \$status") !== false,
 'guidance all states' => strpos($src, "'pending' =>") !== false && strpos($src, "'review' =>") !== false && strpos($src, "'approved' =>") !== false,
 'lawyer approval gate' => strpos($src, "'lawyer' === \$member_type && 'approved' === \$status") !== false,
 'valid published specialties' => strpos($src, "pvdak_core_valid_specialty( \$landing_id )") !== false && strpos($src, "'publish' === get_post_status( \$landing_id )") !== false,
 'no duplicate specialties' => strpos($src, '! in_array( $landing_id, $valid_ids, true )') !== false,
 'no confidential request text' => strpos($src, 'post_content') === false && strpos($src, '_pvdak_internal_notes') === false,
 'bounded capacity remainder' => strpos($src, 'max( 0, $limit - $active )') !== false,
 'capacity meter' => strpos($src, 'pvdak-core-prof-meter') !== false,
 'paused capacity reason' => strpos($src, 'if ( ! $accepting )') !== false,
 'subscription plan allowlist' => strpos($src, 'isset( $plans[ $plan ] )') !== false,
 'subscription state allowlist' => strpos($src, 'isset( $states[ $raw_sub_state ] )') !== false,
 'subscription gate off' => strpos($src, 'if ( $gate )') !== false,
 'bounded quota remainder' => strpos($src, 'max( 0, $quota - $used )') !== false,
 'no payment promise' => strpos($src, 'خرید و پرداخت آنلاین در این نسخه وجود ندارد') !== false,
 'privacy guidance' => strpos($src, 'مدارک اشخاص را در فرم تماس عمومی وارد نکنید') !== false,
 'responsive portal' => strpos($css, '@media(max-width:650px){.pvdak-core-prof-facts') !== false,
 'visible keyboard focus' => strpos($css, '.pvdak-core-prof-links a:focus-visible') !== false
);
foreach ($checks as $name => $ok) {
    if (!$ok) { fwrite(STDERR, "PROF_PORTAL_FAIL: $name\n"); exit(1); }
}
echo 'PVDAK_PROF_PORTAL_SOURCE_OK: ' . count($checks) . " static checks (not WordPress/browser tests)\n";
