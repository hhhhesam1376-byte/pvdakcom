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
/** Count successful internal referral events in the current UTC calendar month.
 * Revocations, malformed records and unrelated audit actions are never usage.
 * This runs under the professional's advisory lock for final decisions.
 */
function pvdak_core_subscription_month_assignments( $profile_id ) {
    global $wpdb;
    $profile_id = absint( $profile_id );
    if ( ! $profile_id ) { return 0; }
    $first = gmdate( 'Y-m-01 00:00:00' );
    $next = gmdate( 'Y-m-01 00:00:00', strtotime( '+1 month', strtotime( $first . ' UTC' ) ) );
    $page = 1;
    $batch_size = 100;
    $used = 0;
    do {
        $ids = get_posts( array(
            'post_type' => 'pvdak_request',
            'post_status' => 'private',
            'posts_per_page' => $batch_size,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'cache_results' => false,
            'update_post_meta_cache' => false,
            'suppress_filters' => true,
            'meta_query' => array(
                array( 'key' => '_pvdak_assignment_audit', 'compare' => 'EXISTS' ),
            ),
        ) );
        // Never grant a fresh quota when its audit query fails.
        if ( ! is_array( $ids ) || ( isset( $wpdb->last_error ) && '' !== (string) $wpdb->last_error ) ) {
            return PHP_INT_MAX;
        }
        foreach ( $ids as $id ) {
            $entries = get_post_meta( absint( $id ), '_pvdak_assignment_audit', false );
            if ( ! is_array( $entries ) ) { continue; }
            foreach ( $entries as $entry ) {
                if ( ! is_array( $entry ) ||
                     (int) ( $entry['profile_id'] ?? 0 ) !== $profile_id ||
                     ( $entry['scope'] ?? '' ) !== 'internal_only' ||
                     ( isset( $entry['action'] ) && 'assigned' !== $entry['action'] ) ||
                     ! isset( $entry['at'] ) || ! is_string( $entry['at'] ) ||
                     ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $entry['at'] ) ||
                     $entry['at'] < $first || $entry['at'] >= $next ) {
                    continue;
                }
                // Reassignment after revocation consumes another quota event.
                $used++;
            }
        }
        $page++;
    } while ( count( $ids ) === $batch_size );
    return $used;
}

/** A disabled gate preserves the existing internal workflow; enabled means fail closed. */
function pvdak_core_subscription_allows_new_assignment( $profile_id ) {
    if ( ! pvdak_core_subscription_gate_on() ) { return true; }
    $profile_id = absint( $profile_id );
    if ( ! $profile_id || 'pvdak_professional' !== get_post_type( $profile_id ) ||
         'private' !== get_post_status( $profile_id ) ||
         ! pvdak_core_profile_has_owner( $profile_id ) ||
         'active' !== get_post_meta( $profile_id, '_pvdak_subscription_state', true ) ||
         'none' === pvdak_core_subscription_plan( $profile_id ) ) {
        return false;
    }
    $expires = pvdak_core_subscription_expiry( $profile_id );
    $quota = pvdak_core_subscription_monthly_quota( $profile_id );
    if ( ! $expires || $expires < current_time( 'Y-m-d' ) || $quota < 1 ) { return false; }
    return pvdak_core_subscription_month_assignments( $profile_id ) < $quota;
}

/** Canonical readback used for stale-form detection; no payment claims. */
function pvdak_core_subscription_snapshot( $profile_id ) {
    $profile_id = absint( $profile_id );
    return array(
        'state' => (string) get_post_meta( $profile_id, '_pvdak_subscription_state', true ),
        'plan' => pvdak_core_subscription_plan( $profile_id ),
        'quota' => pvdak_core_subscription_monthly_quota( $profile_id ),
        'expires' => pvdak_core_subscription_expiry( $profile_id ),
    );
}
function pvdak_core_subscription_revision( $snapshot ) {
    return hash_hmac( 'sha256', wp_json_encode( $snapshot ), wp_salt( 'auth' ) );
}
function pvdak_core_subscription_metabox() {
    add_meta_box('pvdak_subscription_admin','اشتراک آزمایشی و سهمیه ارجاع','pvdak_core_subscription_metabox_html','pvdak_professional','side','default');
}
add_action('add_meta_boxes','pvdak_core_subscription_metabox');
function pvdak_core_subscription_metabox_html( $post ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_sub_'.$post->ID, '_pvdak_subscription_nonce' );
    $snapshot = pvdak_core_subscription_snapshot( $post->ID );
    echo '<input type="hidden" name="_pvdak_subscription_revision" value="' .
        esc_attr( pvdak_core_subscription_revision( $snapshot ) ) . '">';
    $state = $snapshot['state'];
    $plan = $snapshot['plan'];
    $quota = $snapshot['quota'];
    $expiry = $snapshot['expires'];
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
/**
 * Manual admin-only updates. Serialize concurrent staff saves and reject
 * stale forms before any write; meta writes still are not DB transactions.
 */
function pvdak_core_subscription_save( $post_id ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ||
         ! isset( $_POST['_pvdak_subscription_nonce'], $_POST['_pvdak_subscription_revision'] ) ||
         ! is_string( $_POST['_pvdak_subscription_nonce'] ) ||
         ! is_string( $_POST['_pvdak_subscription_revision'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_subscription_nonce'] ) ), 'pvdak_sub_' . $post_id ) ) {
        return;
    }
    $revision = wp_unslash( $_POST['_pvdak_subscription_revision'] );
    if ( ! preg_match( '/^[a-f0-9]{64}$/D', $revision ) ) { return; }
    $raw_state = wp_unslash( $_POST['pvdak_sub_state'] ?? '' );
    $raw_plan = wp_unslash( $_POST['pvdak_sub_plan'] ?? '' );
    $raw_limit = wp_unslash( $_POST['pvdak_sub_limit'] ?? '' );
    $raw_expiry = wp_unslash( $_POST['pvdak_sub_expires'] ?? '' );
    if ( ! is_string( $raw_state ) || ! is_string( $raw_plan ) ||
         ! is_string( $raw_limit ) || ! is_string( $raw_expiry ) ) { return; }
    $state = sanitize_key( $raw_state );
    $plan = sanitize_key( $raw_plan );
    $limit = trim( $raw_limit );
    $date = trim( $raw_expiry );
    if ( ! in_array( $state, array( 'active', 'paused', 'inactive' ), true ) ||
         ! in_array( $plan, array( 'none', 'basic', 'standard', 'advanced' ), true ) ||
         ! ctype_digit( $limit ) || (int) $limit > 100 ||
         ( '' !== $date && ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/D', $date ) ||
           ! checkdate( (int) substr( $date, 5, 2 ), (int) substr( $date, 8, 2 ), (int) substr( $date, 0, 4 ) ) ) ) ) {
        return;
    }
    if ( 'active' === $state &&
         ( 'none' === $plan || 0 === (int) $limit || '' === $date || $date < current_time( 'Y-m-d' ) ) ) {
        return;
    }
    global $wpdb;
    if ( ! is_object( $wpdb ) ) { return; }
    $lock = 'pvdak:sub:' . absint( $post_id );
    if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock, 5 ) ) ) {
        return; // Never save without the per-profile lock.
    }
    try {
        $old = pvdak_core_subscription_snapshot( $post_id );
        if ( ! hash_equals( pvdak_core_subscription_revision( $old ), $revision ) ) {
            return; // Another administrator changed the subscription since form load.
        }
        $next = array( 'state' => $state, 'plan' => $plan, 'quota' => (int) $limit, 'expires' => $date );
        if ( $old === $next ) { return; }
        $changes = array(
            '_pvdak_subscription_state' => $state,
            '_pvdak_subscription_plan' => $plan,
            '_pvdak_subscription_monthly_quota' => (int) $limit,
            '_pvdak_subscription_expires' => $date,
        );
        $previous = array();
        $written = array();
        foreach ( $changes as $key => $value ) {
            $had = metadata_exists( 'post', $post_id, $key );
            $before = get_post_meta( $post_id, $key, true );
            if ( $had && (string) $before === (string) $value ) { continue; }
            $previous[ $key ] = array( 'had' => $had, 'value' => $before );
            if ( false === update_post_meta( $post_id, $key, $value ) ) {
                pvdak_core_subscription_restore( $post_id, $written, $previous );
                return;
            }
            $written[] = $key;
        }
        if ( false === add_post_meta( $post_id, '_pvdak_subscription_audit', array(
            'previous' => $old, 'next' => $next, 'by' => get_current_user_id(),
            'at' => current_time( 'mysql', true ),
        ), false ) ) {
            pvdak_core_subscription_restore( $post_id, $written, $previous );
        }
    } finally {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
    }
}

/** Best-effort restoration on a failed write; WP postmeta is not atomic. */
function pvdak_core_subscription_restore( $post_id, $written, $previous ) {
    foreach ( array_reverse( $written ) as $key ) {
        if ( $previous[ $key ]['had'] ) {
            update_post_meta( $post_id, $key, $previous[ $key ]['value'] );
        } else {
            delete_post_meta( $post_id, $key );
        }
    }
}
add_action('save_post_pvdak_professional','pvdak_core_subscription_save',24);
