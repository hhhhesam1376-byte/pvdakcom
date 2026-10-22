<?php
/**
 * Manual, manager-only matching. No applicant receives private case content,
 * no email or notification is sent, and no automatic assignment occurs.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_assignable_profiles( $landing_id, $request_type ) {
    if ( ! pvdak_core_valid_specialty( $landing_id ) ) { return array(); }
    $profiles = get_posts( array(
        'post_type' => 'pvdak_professional',
        'post_status' => 'private',
        'posts_per_page' => 200,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'AND',
            array( 'key' => '_pvdak_member_type', 'value' => 'lawyer' ),
            array( 'key' => '_pvdak_member_state', 'value' => 'approved' ),
            array( 'key' => '_pvdak_referral_cleared', 'value' => '1' ),
        ),
    ) );
    return array_values( array_filter( $profiles, function ( $profile ) use ( $landing_id, $request_type ) {
        return pvdak_core_profile_referral_eligible( $profile->ID, $landing_id, $request_type );
    } ) );
}

function pvdak_core_assignment_admin_page_register() {
    add_submenu_page(
        'edit.php?post_type=pvdak_request',
        'ارجاع داخلی درخواست',
        'ارجاع کنترل‌شده',
        'edit_pvdak_requests',
        'pvdak-assign-request',
        'pvdak_core_assignment_admin_page'
    );
}
add_action( 'admin_menu', 'pvdak_core_assignment_admin_page_register' );

function pvdak_core_assignment_admin_page() {
    if ( ! current_user_can( 'edit_pvdak_requests' ) ) { wp_die( 'مجوز کافی نیست.', '', array( 'response' => 403 ) ); }
    $request_id = isset( $_GET['request_id'] ) && is_scalar( $_GET['request_id'] ) ?
        absint( wp_unslash( $_GET['request_id'] ) ) : 0;
    echo '<div class="wrap"><h1>ارجاع داخلی و کنترل‌شده درخواست</h1>';
    echo '<div class="notice notice-warning inline"><p>این ماژول یک انتساب داخلی برای مدیر ثبت می‌کند. احراز رسمی، توافق وکیل با موکل، ارتباطات و ارسال پرونده به وکیل هنوز پیاده‌سازی نشده و هیچ اطلاعات خصوصی برای متخصص ارسال نمی‌شود.</p></div>';
    if ( ! $request_id ) {
        echo '<p>ابتدا از فهرست درخواست‌ها، پرونده موردنظر را باز کنید و از پیوند ارجاع داخلی در صفحه ویرایش استفاده کنید.</p></div>';
        return;
    }
    if ( 'pvdak_request' !== get_post_type( $request_id ) || ! current_user_can( 'edit_post', $request_id ) ) {
        wp_die( 'درخواست معتبر نیست.', '', array( 'response' => 403 ) );
    }
    $state = get_post_meta( $request_id, '_pvdak_request_state', true );
    $request_type = get_post_meta( $request_id, '_pvdak_request_type', true );
    $landing_id = absint( get_post_meta( $request_id, '_pvdak_legal_landing_id', true ) );
    $assigned = absint( get_post_meta( $request_id, '_pvdak_assigned_profile_id', true ) );
    echo '<p><strong>درخواست:</strong> ' . esc_html( get_the_title( $request_id ) ) . '</p>';
    echo '<p><strong>موضوع لندینگ:</strong> ' . ( pvdak_core_valid_specialty( $landing_id ) ? esc_html( get_the_title( $landing_id ) ) : 'تنظیم نشده' ) . '</p>';
    if ( $assigned ) {
        echo '<div class="notice notice-info inline"><p>این درخواست قبلاً به پرونده حرفه‌ای شماره ' . esc_html( $assigned ) . ' در سیستم داخلی اختصاص داده شده است.</p></div>';
    }
    if ( 'review' !== $state || $assigned ) {
        echo '<p>ارجاع جدید فقط برای درخواستِ «در حال بررسی» که قبلاً اختصاص داده نشده، قابل ثبت است.</p></div>';
        return;
    }
    $candidates = pvdak_core_assignable_profiles( $landing_id, $request_type );
    $requester = (int) get_post_field( 'post_author', $request_id );
    $candidates = array_values( array_filter( $candidates, function ( $p ) use ( $requester ) {
        return (int) $p->post_author !== $requester;
    } ) );
    if ( ! $candidates ) {
        echo '<div class="notice notice-info inline"><p>هیچ پروفایل حرفه‌ای واجد شرایط داخلی برای این موضوع یافت نشد. وضعیت بررسی، تعیین حوزه تخصصی و بررسی مستقل صلاحیت را در پروفایل متقاضیان کنترل کنید.</p></div></div>';
        return;
    }
    if ( isset( $_GET['pvdak_assignment_error'] ) ) {
        echo '<div class="notice notice-error inline"><p>ارجاع ثبت نشد؛ وضعیت پرونده یا صلاحیت منتخب تغییر کرده است.</p></div>';
    }
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'pvdak_assign_' . $request_id );
    echo '<input type="hidden" name="action" value="pvdak_core_assign">';
    echo '<input type="hidden" name="request_id" value="' . esc_attr( $request_id ) . '">';
    echo '<table class="form-table"><tr><th><label for="pvdak_profile_id">متخصص بررسی‌شده</label></th><td>';
    echo '<select id="pvdak_profile_id" name="profile_id" required><option value="">انتخاب کنید</option>';
    foreach ( $candidates as $profile ) {
        echo '<option value="' . esc_attr( $profile->ID ) . '">' . esc_html( $profile->post_title ) .
            ' — پرونده #' . esc_html( $profile->ID ) . '</option>';
    }
    echo '</select></td></tr></table>';
    echo '<p>قبل از ادامه، اطلاعات صلاحیت و امکان قبول پرونده باید خارج از این نسخه به‌صورت مستقل بررسی شود. این عمل فقط شناسه پروفایل را به درخواست متصل می‌کند.</p>';
    submit_button( 'ثبت انتساب داخلی (بدون اطلاع‌رسانی)' );
    echo '</form></div>';
}

function pvdak_core_assign_request() {
    if ( ! current_user_can( 'edit_pvdak_requests' ) ) {
        wp_die( 'مجوز کافی نیست.', '', array( 'response' => 403 ) );
    }
    $request_id = isset( $_POST['request_id'] ) && is_scalar( $_POST['request_id'] ) ?
        absint( wp_unslash( $_POST['request_id'] ) ) : 0;
    $profile_id = isset( $_POST['profile_id'] ) && is_scalar( $_POST['profile_id'] ) ?
        absint( wp_unslash( $_POST['profile_id'] ) ) : 0;
    if ( ! $request_id || ! $profile_id || 'pvdak_request' !== get_post_type( $request_id ) ||
         ! current_user_can( 'edit_post', $request_id ) ) {
        wp_die( 'ورودی نامعتبر است.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_assign_' . $request_id );
    $landing_id = absint( get_post_meta( $request_id, '_pvdak_legal_landing_id', true ) );
    $type = get_post_meta( $request_id, '_pvdak_request_type', true );
    $return = admin_url( 'admin.php?page=pvdak-assign-request&request_id=' . $request_id );
    if ( 'private' !== get_post_status( $request_id ) ||
         'review' !== get_post_meta( $request_id, '_pvdak_request_state', true ) ||
         get_post_meta( $request_id, '_pvdak_assigned_profile_id', true ) ||
         ! pvdak_core_profile_referral_eligible( $profile_id, $landing_id, $type ) ||
         (int) get_post_field( 'post_author', $request_id ) === (int) get_post_field( 'post_author', $profile_id ) ) {
        wp_safe_redirect( add_query_arg( 'pvdak_assignment_error', 'invalid', $return ) );
        exit;
    }
    // A unique meta key guards against a second assignment by another manager.
    if ( ! add_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id, true ) ) {
        wp_safe_redirect( add_query_arg( 'pvdak_assignment_error', 'duplicate', $return ) );
        exit;
    }
    update_post_meta( $request_id, '_pvdak_request_state', 'matched' );
    add_post_meta( $request_id, '_pvdak_state_audit', array(
        'from' => 'review', 'to' => 'matched', 'by' => get_current_user_id(),
        'at' => current_time( 'mysql', true ),
    ), false );
    add_post_meta( $request_id, '_pvdak_assignment_audit', array(
        'profile_id' => $profile_id, 'landing_id' => $landing_id,
        'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
        'scope' => 'internal_only',
    ), false );
    wp_safe_redirect( $return );
    exit;
}
add_action( 'admin_post_pvdak_core_assign', 'pvdak_core_assign_request' );

function pvdak_core_assignment_link_metabox() {
    add_meta_box(
        'pvdak_assignment_summary',
        'انتساب داخلی متخصص',
        'pvdak_core_assignment_link_html',
        'pvdak_request', 'side', 'default'
    );
}
add_action( 'add_meta_boxes', 'pvdak_core_assignment_link_metabox' );

function pvdak_core_assignment_link_html( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    $profile_id = absint( get_post_meta( $post->ID, '_pvdak_assigned_profile_id', true ) );
    if ( $profile_id ) {
        echo '<p>پروفایل اختصاص‌داده‌شده: <strong>#' . esc_html( $profile_id ) . '</strong></p>';
    }
    $landing = absint( get_post_meta( $post->ID, '_pvdak_legal_landing_id', true ) );
    echo '<p>موضوع حقوقی: ' . ( pvdak_core_valid_specialty( $landing ) ?
        esc_html( get_the_title( $landing ) ) : 'نامشخص/لندینگ قدیمی' ) . '</p>';
    $url = admin_url( 'admin.php?page=pvdak-assign-request&request_id=' . $post->ID );
    echo '<p><a class="button button-secondary" href="' . esc_url( $url ) . '">مدیریت ارجاع داخلی</a></p>';
}
