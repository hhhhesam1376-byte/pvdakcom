<?php
/** Private staff queue for professional applications. Read-only list enhancements. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** The same capability used by WordPress for editing this private post type. */
function pvdak_core_prof_admin_allowed() {
    $type = get_post_type_object( 'pvdak_professional' );
    return $type && current_user_can( $type->cap->edit_posts );
}

/** Never trust list-table GET values as query keys or SQL fragments. */
function pvdak_core_prof_admin_filters() {
    $allowed = array(
        'pvdak_member_filter' => array_keys( pvdak_core_member_types() ),
        'pvdak_state_filter' => array_keys( pvdak_core_member_states() ),
        'pvdak_method_filter' => array_keys( pvdak_core_review_methods() ),
        'pvdak_expiry_filter' => array( 'expired', 'next30', 'future', 'missing' ),
    );
    $filters = array( '_invalid' => false );
    foreach ( $allowed as $key => $choices ) {
        if ( ! array_key_exists( $key, $_GET ) || '' === $_GET[ $key ] ) {
            $filters[ $key ] = '';
            continue;
        }
        if ( ! is_string( $_GET[ $key ] ) ) {
            $filters[ $key ] = '';
            $filters['_invalid'] = true;
            continue;
        }
        $raw = wp_unslash( $_GET[ $key ] );
        $clean = sanitize_key( $raw );
        if ( $clean !== $raw || ! in_array( $clean, $choices, true ) ) {
            $filters[ $key ] = '';
            $filters['_invalid'] = true;
            continue;
        }
        $filters[ $key ] = $clean;
    }
    if ( array_key_exists( 'pvdak_city_filter', $_GET ) && ! is_string( $_GET['pvdak_city_filter'] ) ) {
        $filters['_invalid'] = true;
    }
    $city_raw = isset( $_GET['pvdak_city_filter'] ) && is_string( $_GET['pvdak_city_filter'] ) ?
        wp_unslash( $_GET['pvdak_city_filter'] ) : '';
    $city = sanitize_text_field( $city_raw );
    $bounded = wp_html_excerpt( $city, 70, '' );
    if ( $city !== $city_raw || $bounded !== $city ) { $filters['_invalid'] = true; }
    $filters['pvdak_city_filter'] = $filters['_invalid'] ? '' : $bounded;
    return $filters;
}

function pvdak_core_prof_admin_dropdown( $name, $label, $options, $selected ) {
    echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
    echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" aria-label="' . esc_attr( $label ) . '">';
    echo '<option value="">' . esc_html( $label ) . ': همه</option>';
    foreach ( $options as $key => $title ) {
        echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . '>' . esc_html( $title ) . '</option>';
    }
    echo '</select>';
}

/** Controls appear only on the private professional list, never on public pages. */
function pvdak_core_prof_admin_controls( $post_type ) {
    if ( 'pvdak_professional' !== $post_type || ! pvdak_core_prof_admin_allowed() ) { return; }
    $f = pvdak_core_prof_admin_filters();
    pvdak_core_prof_admin_dropdown( 'pvdak_member_filter', 'نوع عضویت', pvdak_core_member_types(), $f['pvdak_member_filter'] );
    pvdak_core_prof_admin_dropdown( 'pvdak_state_filter', 'وضعیت اداری', pvdak_core_member_states(), $f['pvdak_state_filter'] );
    pvdak_core_prof_admin_dropdown( 'pvdak_method_filter', 'روش بررسی صلاحیت', pvdak_core_review_methods(), $f['pvdak_method_filter'] );
    pvdak_core_prof_admin_dropdown( 'pvdak_expiry_filter', 'تاریخ اعتبار', array(
        'expired' => 'منقضی‌شده',
        'next30' => 'انقضا در ۳۰ روز آینده',
        'future' => 'دارای تاریخ آینده',
        'missing' => 'بدون تاریخ اعتبار',
    ), $f['pvdak_expiry_filter'] );
    echo '<label class="screen-reader-text" for="pvdak_city_filter">شهر فعالیت</label>';
    echo '<input type="search" id="pvdak_city_filter" name="pvdak_city_filter" maxlength="70" placeholder="شهر فعالیت" value="' . esc_attr( $f['pvdak_city_filter'] ) . '">';
    echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=pvdak_professional' ) ) . '">پاک‌کردن فیلترها</a>';
}
add_action( 'restrict_manage_posts', 'pvdak_core_prof_admin_controls' );

/** Warn only on the professional queue; never echo untrusted filter values. */
function pvdak_core_prof_admin_invalid_notice() {
    if ( ! pvdak_core_prof_admin_allowed() ) { return; }
    $screen = get_current_screen();
    if ( ! $screen || 'edit' !== $screen->base || 'pvdak_professional' !== $screen->post_type ||
         ! pvdak_core_prof_admin_filters()['_invalid'] ) { return; }
    echo '<div class="notice notice-warning"><p>یکی از فیلترهای پرونده‌های عضویت نامعتبر است؛ برای جلوگیری از نمایش فهرست گسترده، نتیجه‌ای نمایش داده نمی‌شود. فیلترها را پاک کنید.</p></div>';
}
add_action( 'admin_notices', 'pvdak_core_prof_admin_invalid_notice' );

/** Preserve native WordPress filters, paging and search; never alter another query. */
function pvdak_core_prof_admin_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ||
         'pvdak_professional' !== $query->get( 'post_type' ) ||
         ! pvdak_core_prof_admin_allowed() ) { return; }
    $f = pvdak_core_prof_admin_filters();
    if ( $f['_invalid'] ) {
        $query->set( 'post__in', array( 0 ) );
        return;
    }
    $conditions = array();
    foreach ( array(
        'pvdak_member_filter' => '_pvdak_member_type',
        'pvdak_state_filter' => '_pvdak_member_state',
        'pvdak_method_filter' => '_pvdak_license_review_method',
    ) as $field => $meta_key ) {
        if ( '' !== $f[ $field ] ) {
            $conditions[] = array( 'key' => $meta_key, 'value' => $f[ $field ], 'compare' => '=' );
        }
    }
    if ( '' !== $f['pvdak_city_filter'] ) {
        $conditions[] = array( 'key' => '_pvdak_member_city', 'value' => $f['pvdak_city_filter'], 'compare' => 'LIKE' );
    }
    $today = current_time( 'Y-m-d' );
    if ( 'expired' === $f['pvdak_expiry_filter'] ) {
        $conditions[] = array( 'key' => '_pvdak_license_review_expires', 'value' => $today, 'compare' => '<', 'type' => 'CHAR' );
        $conditions[] = array( 'key' => '_pvdak_license_review_expires', 'value' => '', 'compare' => '!=' );
    } elseif ( 'future' === $f['pvdak_expiry_filter'] ) {
        $conditions[] = array( 'key' => '_pvdak_license_review_expires', 'value' => $today, 'compare' => '>=', 'type' => 'CHAR' );
    } elseif ( 'next30' === $f['pvdak_expiry_filter'] ) {
        $conditions[] = array( 'key' => '_pvdak_license_review_expires', 'value' => array( $today, current_datetime()->modify( '+30 days' )->format( 'Y-m-d' ) ), 'compare' => 'BETWEEN', 'type' => 'CHAR' );
    } elseif ( 'missing' === $f['pvdak_expiry_filter'] ) {
        $conditions[] = array( 'relation' => 'OR',
            array( 'key' => '_pvdak_license_review_expires', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_pvdak_license_review_expires', 'value' => '', 'compare' => '=' ),
        );
    }
    if ( $conditions ) {
        $previous = $query->get( 'meta_query' );
        $combined = array( 'relation' => 'AND' );
        if ( is_array( $previous ) && $previous ) { $combined[] = $previous; }
        foreach ( $conditions as $condition ) { $combined[] = $condition; }
        $query->set( 'meta_query', $combined );
    }
    if ( 'pvdak_expiry' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', '_pvdak_license_review_expires' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC' );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_prof_admin_query' );

function pvdak_core_prof_admin_columns( $columns ) {
    if ( ! pvdak_core_prof_admin_allowed() ) { return $columns; }
    $columns['pvdak_tracking'] = 'پیگیری';
    $columns['pvdak_license'] = 'بررسی داخلی صلاحیت';
    $columns['pvdak_license_expiry'] = 'پایان اعتبار';
    $columns['pvdak_reviewer'] = 'ثبت‌کننده بررسی';
    return $columns;
}
add_filter( 'manage_pvdak_professional_posts_columns', 'pvdak_core_prof_admin_columns', 30 );

function pvdak_core_prof_admin_column_content( $column, $post_id ) {
    if ( ! pvdak_core_prof_admin_allowed() || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( 'pvdak_tracking' === $column ) {
        echo '<code>#' . esc_html( absint( $post_id ) ) . '</code>';
    } elseif ( 'pvdak_license' === $column ) {
        $status = (string) get_post_meta( $post_id, '_pvdak_license_review_status', true );
        $method = (string) get_post_meta( $post_id, '_pvdak_license_review_method', true );
        $expiry = (string) get_post_meta( $post_id, '_pvdak_license_review_expires', true );
        if ( pvdak_core_license_review_current( $post_id ) ) {
            echo '<strong>بررسی داخلی معتبر</strong>';
        } elseif ( 'checked' === $status && $expiry && $expiry < current_time( 'Y-m-d' ) ) {
            echo '<strong>منقضی‌شده</strong>';
        } elseif ( 'document_review' === $method ) {
            echo 'بررسی اولیه مدارک؛ فاقد اعتبار ارجاع';
        } else {
            echo 'نیازمند بررسی مستقل';
        }
    } elseif ( 'pvdak_license_expiry' === $column ) {
        $expiry = (string) get_post_meta( $post_id, '_pvdak_license_review_expires', true );
        echo preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $expiry ) ? '<time datetime="' . esc_attr( $expiry ) . '">' . esc_html( $expiry ) . '</time>' : '—';
    } elseif ( 'pvdak_reviewer' === $column ) {
        $reviewer_id = absint( get_post_meta( $post_id, '_pvdak_license_review_by', true ) );
        $reviewer = $reviewer_id ? get_user_by( 'id', $reviewer_id ) : false;
        echo $reviewer ? esc_html( $reviewer->display_name ) : '—';
    }
}
add_action( 'manage_pvdak_professional_posts_custom_column', 'pvdak_core_prof_admin_column_content', 30, 2 );

function pvdak_core_prof_admin_sortable( $columns ) {
    if ( pvdak_core_prof_admin_allowed() ) { $columns['pvdak_license_expiry'] = 'pvdak_expiry'; }
    return $columns;
}
add_filter( 'manage_edit-pvdak_professional_sortable_columns', 'pvdak_core_prof_admin_sortable' );

/** Audits remain private and read-only. Never echo free-text review notes. */
function pvdak_core_prof_admin_audit_box( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    echo '<p>این سوابق صرفاً داخلی‌اند؛ تأیید اداری معادل احراز رسمی پروانه نیست.</p>';
    foreach ( array(
        '_pvdak_member_audit' => array( 'title' => 'تغییرات عضویت', 'states' => pvdak_core_member_states() ),
        '_pvdak_license_review_audit' => array( 'title' => 'بررسی صلاحیت', 'states' => array( 'checked' => 'بررسی ثبت‌شده', 'pending' => 'نیازمند بررسی' ) ),
    ) as $meta_key => $config ) {
        $events = get_post_meta( $post->ID, $meta_key, false );
        echo '<h4>' . esc_html( $config['title'] ) . '</h4>';
        if ( ! $events ) { echo '<p>هنوز سابقه‌ای ثبت نشده است.</p>'; continue; }
        echo '<ol>';
        foreach ( array_reverse( array_slice( $events, -10 ) ) as $event ) {
            if ( ! is_array( $event ) ) { continue; }
            $from = isset( $config['states'][ $event['from'] ?? '' ] ) ? $config['states'][ $event['from'] ] : 'نامشخص';
            $to = isset( $config['states'][ $event['to'] ?? '' ] ) ? $config['states'][ $event['to'] ] : 'نامشخص';
            $by = absint( $event['by'] ?? 0 );
            $reviewer = $by ? get_user_by( 'id', $by ) : false;
            $at = isset( $event['at'] ) && is_string( $event['at'] ) ? $event['at'] : '';
            echo '<li>' . esc_html( $from . ' ← ' . $to ) . '<br><small>' . esc_html( $at ) . ' — ' .
                esc_html( $reviewer ? $reviewer->display_name : 'ثبت‌کننده نامشخص' ) . '</small></li>';
        }
        echo '</ol>';
    }
}
function pvdak_core_prof_admin_audit_metabox() {
    if ( ! pvdak_core_prof_admin_allowed() ) { return; }
    add_meta_box( 'pvdak_prof_admin_audits', 'تاریخچه داخلی بررسی‌ها', 'pvdak_core_prof_admin_audit_box', 'pvdak_professional', 'normal', 'default' );
}
add_action( 'add_meta_boxes_pvdak_professional', 'pvdak_core_prof_admin_audit_metabox' );
