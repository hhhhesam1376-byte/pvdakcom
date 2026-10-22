<?php
/** Administrator-only legal request overview and queue filters. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_triage_can_manage() {
    return current_user_can( 'edit_pvdak_requests' );
}

function pvdak_core_triage_menu() {
    add_submenu_page(
        'edit.php?post_type=pvdak_request',
        'صف بررسی درخواست‌ها',
        'صف بررسی',
        'edit_pvdak_requests',
        'pvdak-request-triage',
        'pvdak_core_triage_page'
    );
}
add_action( 'admin_menu', 'pvdak_core_triage_menu' );

function pvdak_core_triage_counts() {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.meta_value AS state, COUNT(*) AS total
         FROM {$wpdb->posts} AS p
         LEFT JOIN {$wpdb->postmeta} AS pm
           ON pm.post_id = p.ID AND pm.meta_key = %s
         WHERE p.post_type = %s AND p.post_status = %s
         GROUP BY pm.meta_value",
        '_pvdak_request_state', 'pvdak_request', 'private'
    ), ARRAY_A );
    $counts = array_fill_keys( array_keys( pvdak_core_request_statuses() ), 0 );
    foreach ( (array) $rows as $row ) {
        $key = isset( $row['state'] ) ? (string) $row['state'] : '';
        if ( isset( $counts[ $key ] ) ) { $counts[ $key ] = (int) $row['total']; }
        else { $counts['submitted'] += (int) $row['total']; }
    }
    return $counts;
}

function pvdak_core_triage_page() {
    if ( ! pvdak_core_triage_can_manage() ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    $counts = pvdak_core_triage_counts();
    $statuses = pvdak_core_request_statuses();
    $base = admin_url( 'edit.php?post_type=pvdak_request' );
    echo '<div class="wrap"><h1>صف بررسی درخواست‌های حقوقی</h1>';
    echo '<p>این صفحه صرفاً مخصوص مدیران مجاز است. اطلاعات درخواست‌ها خصوصی‌اند؛ ارجاع به متخصص تا زمان راه‌اندازی اعتبارسنجی حرفه‌ای غیرفعال است.</p>';
    echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:24px 0">';
    foreach ( $counts as $key => $count ) {
        $url = add_query_arg( 'pvdak_filter_state', $key, $base );
        echo '<a href="' . esc_url( $url ) . '" style="display:block;min-width:145px;padding:20px;background:#fff;border:1px solid #cfd9d8;border-radius:12px;text-decoration:none">';
        echo '<strong style="font-size:29px;display:block">' . esc_html( $count ) . '</strong>';
        echo '<span>' . esc_html( $statuses[ $key ] ) . '</span></a>';
    }
    echo '</div>';
    echo '<p><a class="button button-primary" href="' . esc_url( $base ) . '">مشاهده همه درخواست‌ها</a></p>';
    echo '<h2>روند بررسی</h2>';
    echo '<p>ثبت‌شده ← در حال بررسی ← پاسخ داده‌شده / بسته‌شده. تا پیش از آماده‌شدن موتور ارجاع، ثبت وضعیت «ارجاع‌شده» برای درخواست‌های جدید مجاز نیست.</p>';
    echo '</div>';
}

/** Filter the actual WordPress admin list, never front-end queries. */
function pvdak_core_triage_list_filters( $post_type ) {
    if ( 'pvdak_request' !== $post_type || ! pvdak_core_triage_can_manage() ) { return; }
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    $selected_type = isset( $_GET['pvdak_filter_type'] ) && is_string( $_GET['pvdak_filter_type'] ) ?
        sanitize_key( wp_unslash( $_GET['pvdak_filter_type'] ) ) : '';
    $selected_state = isset( $_GET['pvdak_filter_state'] ) && is_string( $_GET['pvdak_filter_state'] ) ?
        sanitize_key( wp_unslash( $_GET['pvdak_filter_state'] ) ) : '';
    echo '<select name="pvdak_filter_type" aria-label="فیلتر نوع درخواست"><option value="">همه انواع درخواست</option>';
    foreach ( $types as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $selected_type, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select><select name="pvdak_filter_state" aria-label="فیلتر وضعیت"><option value="">همه وضعیت‌ها</option>';
    foreach ( $statuses as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $selected_state, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'pvdak_core_triage_list_filters' );

function pvdak_core_triage_filter_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || ! pvdak_core_triage_can_manage() ||
         'pvdak_request' !== $query->get( 'post_type' ) ) { return; }
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    $selected_type = isset( $_GET['pvdak_filter_type'] ) && is_string( $_GET['pvdak_filter_type'] ) ?
        sanitize_key( wp_unslash( $_GET['pvdak_filter_type'] ) ) : '';
    $selected_state = isset( $_GET['pvdak_filter_state'] ) && is_string( $_GET['pvdak_filter_state'] ) ?
        sanitize_key( wp_unslash( $_GET['pvdak_filter_state'] ) ) : '';
    $filters = array();
    if ( isset( $types[ $selected_type ] ) ) {
        $filters[] = array( 'key' => '_pvdak_request_type', 'value' => $selected_type, 'compare' => '=' );
    }
    if ( isset( $statuses[ $selected_state ] ) ) {
        $filters[] = array( 'key' => '_pvdak_request_state', 'value' => $selected_state, 'compare' => '=' );
    }
    if ( $filters ) {
        $existing = (array) $query->get( 'meta_query', array() );
        $query->set( 'meta_query', array_merge( array( 'relation' => 'AND' ), $existing, $filters ) );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_triage_filter_query' );
