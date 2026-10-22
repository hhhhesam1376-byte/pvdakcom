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
    ) );
    $total = 0;
    foreach ( $ids as $request_id ) {
        // Another manager might have completed a case in a separate request.
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( absint( $request_id ), 'post_meta' );
        }
        $state = get_post_meta( absint( $request_id ), '_pvdak_request_state', true );
        if ( in_array( $state, array( 'matched', 'answered' ), true ) ) { $total++; }
    }
    return $total;
}

function pvdak_core_capacity_available( $profile_id ) {
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
