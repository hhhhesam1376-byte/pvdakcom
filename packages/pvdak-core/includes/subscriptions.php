<?php
/**
 * Manually-administered prototype subscription quota. No billing, money,
 * checkout, credential verification or automatically paid claims.
 * Entitlement gate defaults OFF, preserving existing Core matching until
 * a manager explicitly enables it after acceptance testing.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function pvdak_core_subscription_gate_on() {
    return '1' === (string) get_option( 'pvdak_core_subscription_gate_enabled', '0' );
}
function pvdak_core_subscription_plan( $profile_id ) {
    $raw = get_post_meta( absint( $profile_id ), '_pvdak_subscription_plan', true );
    return in_array( $raw, array( 'basic','standard','advanced' ), true ) ? $raw : 'none';
}
function pvdak_core_subscription_monthly_quota( $profile_id ) {
    $raw = get_post_meta( absint( $profile_id ), '_pvdak_subscription_monthly_quota', true );
    return is_scalar( $raw ) && ctype_digit( (string ) $raw ) ?
        min( 100, absint( $raw ) ) : 0;
}
function pvdak_core_subscription_expiry( $profile_id ) {
    $date = get_post_meta( absint( $profile_id ), '_pvdak_subscription_expires', true );
    if ( ! is_string( $date ) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) ) { return ''; }
    $parts = explode( '-', $date );
    return checkdate( (int)$parts[1], (int)$parts[2], (int)$parts[0] ) ? $date : '';
}
/** Audited monthly assignment count; never a count of money or service use. */
function pvdak_core_subscription_month_assignments( $profile_id ) {
    $profile_id = absint( $profile_id );
    if ( ! $profile_id ) { return 0; }
    // Audits are immutable history: count even if a case was later unassigned.
    $ids = get_posts( array( 'post_type'=>'pvdak_request', 'post_status'=>'private',
        'posts_per_page'=>-1, 'fields'=>'ids','no_found_rows'=>true,
        'meta_query'=>array(
            array('key'=>'_pvdak_assignment_audit','compare'=>'EXISTS')
        ),
        'suppress_filters'=>false ) );
    $first = gmdate( 'Y-m-01 00:00:00' );
    $next = gmdate( 'Y-m-01 00:00:00', strtotime('+1 month', strtotime($first.' UTC')) );
    $used = 0;
    foreach ( $ids as $id ) {
        $entries = get_post_meta( absint( $id ), '_pvdak_assignment_audit', false );
        foreach ( $entries as $a ) {
            if ( is_array( $a ) && (int)($a['profile_id']??0)===$profile_id &&
                 isset($a['at']) && is_string($a['at']) &&
                 $a['at'] >= $first && $a['at'] < $next ) {
                $used++;
                break;
            }
        }
    }
    return $used;
}
function pvdak_core_subscription_allows_new_assignment( $profile_id ) {
    if ( ! pvdak_core_subscription_gate_on() ) { return true; }
    if ( 'active' !== get_post_meta( absint( $profile_id ), '_pvdak_subscription_state', true ) ) { return false; }
    $expires = pvdak_core_subscription_expiry( $profile_id );
    if ( ! $expires || $expires < current_time( 'Y-m-d' ) ) { return false; }
    $quota = pvdak_core_subscription_monthly_quota( $profile_id );
    return $quota > 0 && pvdak_core_subscription_month_assignments( $profile_id ) < $quota;
}
function pvdak_core_subscription_metabox() {
    add_meta_box('pvdak_subscription_admin','اشتراک آزمایشی و سهمیه ارجاع','pvdak_core_subscription_metabox_html','pvdak_professional','side','default');
}
add_action('add_meta_boxes','pvdak_core_subscription_metabox');
function pvdak_core_subscription_metabox_html( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_sub_'.$post->ID, '_pvdak_subscription_nonce' );
    $state = get_post_meta( $post->ID, '_pvdak_subscription_state', true );
    $plan = pvdak_core_subscription_plan( $post->ID );
    $quota = pvdak_core_subscription_monthly_quota( $post->ID );
    $expiry = pvdak_core_subscription_expiry( $post->ID );
    echo '<p><strong>این فقط ثبت دستی است.</strong> پرداخت یا خرید آنلاین هنوز وجود ندارد. کنترل سهمیه به‌صورت پیش‌فرض خاموش است.</p>';
    echo '<p><label for="pvdak_sub_state">وضعیت</label> <select id="pvdak_sub_state" name="pvdak_sub_state">';
    foreach(array('inactive'=>'غیرفعال','active'=>'فعال دستی','paused'=>'تعلیق') as $k=>$label){
        echo '<option value="'.esc_attr($k).'" '.selected($state,$k,false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p><p><label for="pvdak_sub_plan">طرح</label><select id="pvdak_sub_plan" name="pvdak_sub_plan">';
    foreach(array('none'=>'بدون طرح','basic'=>'پایه','standard'=>'استاندارد','advanced'=>'پیشرفته') as $k=>$label){
        echo '<option value="'.esc_attr($k).'" '.selected($plan,$k,false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p>';
    echo '<p><label for="pvdak_sub_limit">سهمیه ارجاع ماهانه (۰ تا ۱۰۰)</label><input id="pvdak_sub_limit" type="number" min="0" max="100" name="pvdak_sub_limit" value="'.esc_attr($quota).'" class="small-text"></p>';
    echo '<p><label for="pvdak_sub_expires">تاریخ انقضا (YYYY-MM-DD)</label><input id="pvdak_sub_expires" type="date" name="pvdak_sub_expires" value="'.esc_attr($expiry).'"></p>';
    echo '<p>استفاده از سهمیه در ماه جاری: '.esc_html(pvdak_core_subscription_month_assignments($post->ID)).'</p>';
    echo '<p>اجرای محدودیت: '.(pvdak_core_subscription_gate_on()?'فعال (با مجوز مدیر)':'خاموش').'</p>';
}
function pvdak_core_subscription_save( $post_id ) {
    if ( ! current_user_can( 'edit_post', $post_id ) || ! isset($_POST['_pvdak_subscription_nonce']) ||
         ! is_string($_POST['_pvdak_subscription_nonce']) ||
         ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_pvdak_subscription_nonce'])),'pvdak_sub_'.$post_id) ) { return; }
    $raw_state = wp_unslash($_POST['pvdak_sub_state']??'');
    $raw_plan = wp_unslash($_POST['pvdak_sub_plan']??'');
    $raw_limit = wp_unslash($_POST['pvdak_sub_limit']??'');
    $raw_expiry = wp_unslash($_POST['pvdak_sub_expires']??'');
    if (!is_string($raw_state)||!is_string($raw_plan)||!is_string($raw_limit)||!is_string($raw_expiry))return;
    $state=sanitize_key($raw_state);
    $plan=sanitize_key($raw_plan);
    $limit=trim($raw_limit);
    $date=trim($raw_expiry);
    if(!in_array($state,array('active','paused','inactive'),true)||
       !in_array($plan,array('none','basic','standard','advanced'),true)||
       !ctype_digit($limit)||(int)$limit>100||
       ($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))))return;
    update_post_meta($post_id,'_pvdak_subscription_state',$state);
    update_post_meta($post_id,'_pvdak_subscription_plan',$plan);
    update_post_meta($post_id,'_pvdak_subscription_monthly_quota',(int)$limit);
    update_post_meta($post_id,'_pvdak_subscription_expires',$date);
}
add_action('save_post_pvdak_professional','pvdak_core_subscription_save',24);
