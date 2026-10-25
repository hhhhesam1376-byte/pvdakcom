<?php
/**
 * Internal referral capacity. This manages eligibility for FUTURE assignments,
 * not subscriptions or client access. All settings require staff permissions.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Zero is deliberately "not configured", never unlimited. */
function pvdak_core_capacity_limit( $profile_id ) {
    $saved = get_post_meta( absint( $profile_id ), '_pvdak_capacity_limit', true );
    if ( ! is_scalar( $saved ) || ! ctype_digit( (string) $saved ) ) { return 0; }
    return min( 25, absint( $saved ) );
}

function pvdak_core_capacity_accepting( $profile_id ) {
    return '1' === (string) get_post_meta( absint( $profile_id ), '_pvdak_accepting_assignments', true );
}

/** Count only internally assigned cases that have not been closed. */
function pvdak_core_active_assignment_count( $profile_id ) {
    global $wpdb;
    $profile_id = absint( $profile_id );
    if ( ! $profile_id ) { return 0; }
    $ids = get_posts( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_pvdak_assigned_profile_id',
        'meta_value' => (string) $profile_id,
        'no_found_rows' => true,
        'cache_results' => false,
        'update_post_meta_cache' => false,
        'suppress_filters' => true, // Capacity decisions must not inherit unrelated query filters.
    ) );
    // A failed query must never be mistaken for free capacity.
    if ( ! is_array( $ids ) || ( isset( $wpdb->last_error ) && '' !== (string) $wpdb->last_error ) ) {
        return PHP_INT_MAX;
    }
    $total = 0;
    foreach ( $ids as $request_id ) {
        // Corrupt query results cannot establish spare capacity.
        if ( ! is_scalar( $request_id ) || ! ctype_digit( (string) $request_id ) ||
             ! absint( $request_id ) ) { return PHP_INT_MAX; }
        $request_id = absint( $request_id );
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $request_id, 'post_meta' );
        }
        // Revalidate the private model and assignee despite SQL/query hooks.
        if ( 'pvdak_request' !== get_post_type( $request_id ) ||
             'private' !== get_post_status( $request_id ) ||
             (string) $profile_id !== (string) get_post_meta( $request_id, '_pvdak_assigned_profile_id', true ) ) {
            return PHP_INT_MAX;
        }
        $state = get_post_meta( $request_id, '_pvdak_request_state', true );
        // Only explicitly completed states release capacity. Unknown legacy
        // states must never be treated as free slots.
        if ( 'closed' !== $state ) { $total++; }
    }
    return $total;
}

function pvdak_core_capacity_available( $profile_id ) {
    // Subscription enforcement is an explicit default-OFF manager switch.
    if ( function_exists( 'pvdak_core_subscription_allows_new_assignment' ) &&
         ! pvdak_core_subscription_allows_new_assignment( $profile_id ) ) { return false; }
    if ( ! pvdak_core_capacity_accepting( $profile_id ) ) { return false; }
    $limit = pvdak_core_capacity_limit( $profile_id );
    return $limit > 0 && pvdak_core_active_assignment_count( $profile_id ) < $limit;
}

function pvdak_core_capacity_metabox() {
    add_meta_box(
        'pvdak_professional_capacity',
        'ظرفیت ارجاع داخلی',
        'pvdak_core_capacity_metabox_html',
        'pvdak_professional',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'pvdak_core_capacity_metabox' );

function pvdak_core_capacity_metabox_html( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_capacity_' . $post->ID, '_pvdak_capacity_nonce' );
    $limit = pvdak_core_capacity_limit( $post->ID );
    $active = pvdak_core_active_assignment_count( $post->ID );
    $accepting = pvdak_core_capacity_accepting( $post->ID );
    echo '<p>این تنظیمات فقط برای ارجاع داخلی استفاده می‌شوند؛ تأیید هویت یا سهمیه اشتراک محسوب نمی‌شوند.</p>';
    echo '<p><label for="pvdak_capacity_limit">حداکثر پرونده فعال (۱ تا ۲۵)</label>';
    echo '<input id="pvdak_capacity_limit" class="small-text" type="number" min="0" max="25" step="1" name="pvdak_capacity_limit" value="' .
        esc_attr( $limit ) . '"> <small>صفر: غیرفعال</small></p>';
    echo '<p><strong>پرونده‌های فعال ثبت‌شده:</strong> ' . esc_html( $active ) . '</p>';
    echo '<label><input type="checkbox" name="pvdak_accepting_assignments" value="1" ' .
        checked( $accepting, true, false ) . '> پذیرش انتساب داخلی جدید</label>';
    if ( $limit > 0 && $active >= $limit ) {
        echo '<p><strong>ظرفیت تکمیل است.</strong> تا بسته‌شدن پرونده‌های موجود، ارجاع جدید ممکن نیست.</p>';
    }
}

function pvdak_core_save_capacity( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
         ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_capacity_nonce'] ) || ! is_string( $_POST['_pvdak_capacity_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_capacity_nonce'] ) ), 'pvdak_capacity_' . $post_id ) ) { return; }
    if ( ! isset( $_POST['pvdak_capacity_limit'] ) || ! is_scalar( $_POST['pvdak_capacity_limit'] ) ) { return; }
    $raw = wp_unslash( (string) $_POST['pvdak_capacity_limit'] );
    // Invalid or manipulated values never become an unlimited capacity.
    if ( ! ctype_digit( $raw ) || (int) $raw > 25 ) { return; }
    $limit = absint( $raw );
    $enabled = isset( $_POST['pvdak_accepting_assignments'] ) &&
        is_string( $_POST['pvdak_accepting_assignments'] ) &&
        '1' === wp_unslash( $_POST['pvdak_accepting_assignments'] ) ? '1' : '0';

    $old_limit = pvdak_core_capacity_limit( $post_id );
    $old_enabled = pvdak_core_capacity_accepting( $post_id ) ? '1' : '0';
    if ( $old_limit === $limit && $old_enabled === $enabled ) { return; }
    update_post_meta( $post_id, '_pvdak_capacity_limit', $limit );
    update_post_meta( $post_id, '_pvdak_accepting_assignments', $enabled );
    add_post_meta( $post_id, '_pvdak_capacity_audit', array(
        'old_limit' => $old_limit,
        'new_limit' => $limit,
        'old_accepting' => $old_enabled,
        'new_accepting' => $enabled,
        'by' => get_current_user_id(),
        'at' => current_time( 'mysql', true ),
    ), false );
}
// Status and specialties are saved at 10; capacity at 20; revocation review at 30.
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_capacity', 20 );
