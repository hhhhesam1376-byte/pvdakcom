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
    if ( 'payment' === (string) get_post_meta( $profile_id, '_pvdak_subscription_source', true ) &&
         ! pvdak_core_payment_receipt_verified( $profile_id, (string) get_post_meta( $profile_id, '_pvdak_subscription_receipt', true ) ) ) {
        return false; // Refund or failed reconciliation immediately blocks paid entitlement.
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
        'source' => 'payment' === get_post_meta( $profile_id, '_pvdak_subscription_source', true ) ? 'payment' : 'manual',
        'receipt' => (string) get_post_meta( $profile_id, '_pvdak_subscription_receipt', true ),
    );
}
function pvdak_core_subscription_revision( $snapshot ) {
    return hash_hmac( 'sha256', wp_json_encode( $snapshot ), wp_salt( 'auth' ) );
}
function pvdak_core_subscription_metabox() {
    add_meta_box('pvdak_subscription_admin','اشتراک آزمایشی و سهمیه ارجاع','pvdak_core_subscription_metabox_html','pvdak_professional','side','default');
    add_meta_box('pvdak_payment_admin','سوابق تطبیق پرداخت (دستی)','pvdak_core_payment_metabox_html','pvdak_professional','normal','default');
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
    echo '<p><label for="pvdak_sub_source">منبع فعال‌سازی</label><select id="pvdak_sub_source" name="pvdak_sub_source">';
    foreach ( array( 'manual' => 'مجوز دستی مستقل از پرداخت', 'payment' => 'پس از تطبیق رسید' ) as $key => $label ) {
        echo '<option value="'.esc_attr($key).'" '.selected($snapshot['source'],$key,false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p><p><label for="pvdak_sub_receipt">شناسه رسید تطبیق‌شده (UUID)</label>' .
        '<input id="pvdak_sub_receipt" name="pvdak_sub_receipt" class="widefat" value="'.esc_attr($snapshot['receipt']).'"></p>';
    echo '<p>ثبت پرداخت به‌تنهایی اشتراک را فعال نمی‌کند؛ فعال‌سازی نیازمند ذخیره جداگانه مدیر است.</p>';
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
    $raw_source = wp_unslash( $_POST['pvdak_sub_source'] ?? '' );
    $raw_receipt = wp_unslash( $_POST['pvdak_sub_receipt'] ?? '' );
    if ( ! is_string( $raw_state ) || ! is_string( $raw_plan ) ||
         ! is_string( $raw_limit ) || ! is_string( $raw_expiry ) ||
         ! is_string( $raw_source ) || ! is_string( $raw_receipt ) ) { return; }
    $state = sanitize_key( $raw_state );
    $plan = sanitize_key( $raw_plan );
    $limit = trim( $raw_limit );
    $date = trim( $raw_expiry );
    $source = sanitize_key( $raw_source );
    $receipt = 'payment' === $source ? trim( $raw_receipt ) : '';
    if ( ! in_array( $source, array( 'manual', 'payment' ), true ) ||
         ( 'payment' === $source && ! pvdak_core_payment_receipt_id_valid( $receipt ) ) ||
         ! in_array( $state, array( 'active', 'paused', 'inactive' ), true ) ||
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
        // An old paid link may be deactivated after refund, but cannot be renewed
        // or changed to a different unverified receipt.
        if ( 'payment' === $source && ! pvdak_core_payment_receipt_verified( $post_id, $receipt ) &&
             ( 'active' === $state || $old['receipt'] !== $receipt || $old['source'] !== $source ) ) { return; }
        $next = array( 'state' => $state, 'plan' => $plan, 'quota' => (int) $limit, 'expires' => $date,
            'source' => $source, 'receipt' => $receipt );
        if ( $old === $next ) { return; }
        $changes = array(
            '_pvdak_subscription_state' => $state,
            '_pvdak_subscription_plan' => $plan,
            '_pvdak_subscription_monthly_quota' => (int) $limit,
            '_pvdak_subscription_expires' => $date,
            '_pvdak_subscription_source' => $source,
            '_pvdak_subscription_receipt' => $receipt,
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

/**
 * D26: immutable manual payment reconciliation events. No checkout endpoint,
 * gateway callback, money movement, or automatic entitlement activation.
 * A verified event means a privileged operator attested to checking the
 * provider dashboard, NOT cryptographic gateway confirmation.
 */
function pvdak_core_payment_receipt_id_valid( $id ) {
    return is_string( $id ) && (bool) preg_match(
        '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $id );
}
function pvdak_core_payment_transition_allowed( $state, $action ) {
    return ( 'pending' === $state && in_array( $action, array( 'verified', 'failed' ), true ) ) ||
        ( 'verified' === $state && 'refunded' === $action );
}
/** Any corrupt/out-of-order event fails the entire read closed. */
function pvdak_core_payment_fold( $events ) {
    if ( ! is_array( $events ) ) { return array(); }
    $receipts = array();
    foreach ( $events as $event ) {
        if ( ! is_array( $event ) || ! isset( $event['receipt_id'], $event['action'], $event['by'], $event['at'] ) ||
             ! pvdak_core_payment_receipt_id_valid( $event['receipt_id'] ) ||
             ! is_string( $event['action'] ) || ! is_int( $event['by'] ) || $event['by'] < 1 ||
             ! is_string( $event['at'] ) ||
             ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $event['at'] ) ) { return array(); }
        $id = $event['receipt_id'];
        $action = $event['action'];
        if ( 'created' === $action ) {
            $ref = $event['reference'] ?? '';
            $amount = $event['amount_irr'] ?? 0;
            if ( isset( $receipts[ $id ] ) || ! is_string( $ref ) ||
                 ! preg_match( '/^[A-Za-z0-9_-]{6,80}$/D', $ref ) ||
                 ! is_int( $amount ) || $amount < 1 || $amount > 1000000000000 ) { return array(); }
            $receipts[ $id ] = array( 'state' => 'pending', 'reference' => $ref,
                'amount_irr' => $amount, 'by' => (int) $event['by'] );
        } else {
            if ( ! isset( $receipts[ $id ] ) ||
                 ! pvdak_core_payment_transition_allowed( $receipts[ $id ]['state'], $action ) ||
                 ( 'verified' === $action && ( $event['evidence'] ?? '' ) !== 'provider_dashboard' ) ) {
                return array();
            }
            $receipts[ $id ]['state'] = $action;
        }
    }
    return $receipts;
}
function pvdak_core_payment_ledger( $profile_id ) {
    return pvdak_core_payment_fold( get_post_meta( absint( $profile_id ), '_pvdak_payment_event', false ) );
}
function pvdak_core_payment_receipt_verified( $profile_id, $receipt_id ) {
    $profile_id = absint( $profile_id );
    if ( ! pvdak_core_payment_receipt_id_valid( $receipt_id ) || ! $profile_id ||
         'pvdak_professional' !== get_post_type( $profile_id ) ||
         'private' !== get_post_status( $profile_id ) ||
         ! pvdak_core_profile_has_owner( $profile_id ) ) { return false; }
    $receipts = pvdak_core_payment_ledger( $profile_id );
    return isset( $receipts[ $receipt_id ] ) && 'verified' === $receipts[ $receipt_id ]['state'];
}
function pvdak_core_payment_metabox_html( $post ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_payment_' . $post->ID, '_pvdak_payment_nonce' );
    $receipts = pvdak_core_payment_ledger( $post->ID );
    echo '<p>این دفتر صرفاً برای <strong>تطبیق دستی</strong> است؛ درگاه، callback و تأیید بانکی خودکار ندارد. شماره کارت، CVV یا اطلاعات حساس وارد نکنید.</p>';
    echo '<p><label>شناسه غیرحساس تراکنش درگاه <input name="pvdak_payment_reference" maxlength="80" class="regular-text"></label> ';
    echo '<label>مبلغ صحیح (ریال) <input name="pvdak_payment_amount" type="number" min="1" max="1000000000000"></label></p>';
    echo '<p><label>رسید موجود <select name="pvdak_payment_receipt"><option value="">انتخاب رسید</option>';
    foreach ( array_reverse( $receipts, true ) as $id => $item ) {
        echo '<option value="' . esc_attr( $id ) . '">' .
            esc_html( $id . ' — ' . $item['reference'] . ' — ' . $item['state'] . ' — ' . $item['amount_irr'] . ' IRR' ) . '</option>';
    }
    echo '</select></label></p>';
    echo '<p><label>اقدام <select name="pvdak_payment_action">';
    foreach ( array( 'none' => 'بدون تغییر', 'created' => 'ثبت رسید در انتظار',
        'verified' => 'تطبیق‌شده با پنل درگاه', 'failed' => 'ناموفق',
        'refunded' => 'وجه بازگشت داده شد' ) as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
    }
    echo '</select></label></p><p><label><input type="checkbox" name="pvdak_payment_confirm" value="1"> ' .
        'برای تأیید یا بازگشت وجه، شواهد را خارج از سایت بررسی کرده‌ام.</label></p>';
    echo '<p>ثبت/تأیید رسید به‌تنهایی هیچ اشتراکی فعال نمی‌کند. برای فعال‌سازی، رسید تأییدشده را در باکس اشتراک پیوند دهید.</p>';
}
function pvdak_core_payment_save( $post_id ) {
    $action = wp_unslash( $_POST['pvdak_payment_action'] ?? '' );
    if ( ! is_string( $action ) || ! in_array( $action, array( 'created', 'verified', 'failed', 'refunded' ), true ) ||
         ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ||
         'pvdak_professional' !== get_post_type( $post_id ) ||
         'private' !== get_post_status( $post_id ) || ! pvdak_core_profile_has_owner( $post_id ) ||
         ! isset( $_POST['_pvdak_payment_nonce'] ) || ! is_string( $_POST['_pvdak_payment_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_payment_nonce'] ) ),
             'pvdak_payment_' . $post_id ) ) { return; }
    $raw_ref = wp_unslash( $_POST['pvdak_payment_reference'] ?? '' );
    $raw_amount = wp_unslash( $_POST['pvdak_payment_amount'] ?? '' );
    $raw_id = wp_unslash( $_POST['pvdak_payment_receipt'] ?? '' );
    if ( ! is_string( $raw_ref ) || ! is_string( $raw_amount ) || ! is_string( $raw_id ) ) { return; }
    $reference = trim( $raw_ref );
    $amount = trim( $raw_amount );
    $receipt_id = trim( $raw_id );
    if ( 'created' === $action &&
         ( ! preg_match( '/^[A-Za-z0-9_-]{6,80}$/D', $reference ) ||
           ! ctype_digit( $amount ) || strlen( $amount ) > 13 ||
           (float) $amount < 1 || (float) $amount > 1000000000000 ) ) { return; }
    if ( 'created' !== $action && ! pvdak_core_payment_receipt_id_valid( $receipt_id ) ) { return; }
    if ( in_array( $action, array( 'verified', 'refunded' ), true ) &&
         ( $_POST['pvdak_payment_confirm'] ?? '' ) !== '1' ) { return; }
    global $wpdb;
    if ( ! is_object( $wpdb ) ) { return; }
    $lock = 'pvdak:sub:' . absint( $post_id ); // Same lock as subscription activation.
    if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $lock, 5 ) ) ) { return; }
    try {
        $receipts = pvdak_core_payment_ledger( $post_id );
        $reservation = '';
        if ( 'created' === $action ) {
            $receipt_id = wp_generate_uuid4();
            $reservation = 'pvdak_payref_' . hash( 'sha256', strtolower( $reference ) );
            // add_option is unique by option_name across all profiles, even under concurrency.
            if ( ! add_option( $reservation, absint( $post_id ), '', false ) ) { return; }
        } elseif ( ! isset( $receipts[ $receipt_id ] ) ||
                   ! pvdak_core_payment_transition_allowed( $receipts[ $receipt_id ]['state'], $action ) ) {
            return;
        }
        $event = array( 'receipt_id' => $receipt_id, 'action' => $action,
            'at' => current_time( 'mysql', true ), 'by' => get_current_user_id() );
        if ( 'created' === $action ) {
            $event['reference'] = $reference;
            $event['amount_irr'] = (int) $amount;
        }
        if ( 'verified' === $action ) { $event['evidence'] = 'provider_dashboard'; }
        if ( false === add_post_meta( $post_id, '_pvdak_payment_event', $event, false ) && $reservation ) {
            delete_option( $reservation ); // Only undo a reservation made in this call.
        }
    } finally {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
    }
}
add_action( 'save_post_pvdak_professional', 'pvdak_core_payment_save', 25 );
