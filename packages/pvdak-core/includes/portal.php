<?php
/**
 * Account portal for PVDak.
 * A WordPress shortcode displays only the current user's own metadata.
 * No public profiles, confidential descriptions or unverified credentials.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_published_page_link( $slug, $shortcode ) {
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    if ( ! $page || 'publish' !== $page->post_status || ! has_shortcode( $page->post_content, $shortcode ) ) {
        return '';
    }
    return get_permalink( $page->ID );
}

function pvdak_core_account_url() {
    return pvdak_core_published_page_link( 'my-pvdak-account', 'pvdak_account_dashboard' );
}

function pvdak_core_dashboard( $atts = array() ) {
    if ( ! is_user_logged_in() ) {
        return '<section class="pvdak-core-panel"><h2>پنل کاربری پویش</h2><p>برای مشاهده درخواست‌ها و وضعیت همکاری ابتدا <a href="' .
            esc_url( wp_login_url( get_permalink() ) ) . '">وارد حساب خود شوید</a>.</p></section>';
    }

    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    $items = get_posts( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'author' => $user_id,
        'posts_per_page' => 30,
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => false,
    ) );
    $mine = array();
    foreach ( $items as $item ) {
        if ( 'pvdak_request' === $item->post_type && (int) $item->post_author === $user_id ) {
            $mine[] = $item;
        }
    }

    $application_id = pvdak_core_existing_application( $user_id );
    $member_states = pvdak_core_member_states();
    $member_types = pvdak_core_member_types();
    $member_state = $application_id ? get_post_meta( $application_id, '_pvdak_member_state', true ) : '';
    $member_type = $application_id ? get_post_meta( $application_id, '_pvdak_member_type', true ) : '';

    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    $html = '<section class="pvdak-core-panel pvdak-core-dashboard" aria-labelledby="pvdak-account-title">';
    $html .= '<p class="pvdak-core-eyebrow">پویش وکلای دادگستری کشور</p>';
    $html .= '<h2 id="pvdak-account-title">پنل من</h2>';
    $html .= '<p>خوش آمدید، ' . esc_html( $user->display_name ) . '. وضعیت درخواست‌ها و عضویت شما در این صفحه نمایش داده می‌شود.</p>';

    $html .= '<div class="pvdak-core-stats" aria-label="خلاصه حساب">';
    $html .= '<div><strong>' . esc_html( count( $mine ) ) . '</strong><span>درخواست‌های اخیر</span></div>';
    $html .= '<div><strong>' . esc_html( count( array_filter( $mine, function( $post ) {
        return 'closed' !== get_post_meta( $post->ID, '_pvdak_request_state', true );
    } ) ) ) . '</strong><span>درخواست‌های در جریان</span></div>';
    $html .= '<div><strong>' . ( $application_id ? '۱' : '۰' ) . '</strong><span>درخواست همکاری</span></div>';
    $html .= '</div>';

    $html .= '<h3>میان‌برهای خدمات</h3><div class="pvdak-core-dashboard-actions">';
    $services = array(
        array( 'request-lawyer', 'pvdak_request_form', 'درخواست وکیل' ),
        array( 'request-consultation', 'pvdak_request_form', 'مشاوره حقوقی' ),
        array( 'request-question', 'pvdak_request_form', 'پرسش حقوقی' ),
        array( 'my-legal-requests', 'pvdak_my_requests', 'همه درخواست‌های من' ),
        array( 'join-legal-community', 'pvdak_professional_application', 'همکاری با پویش' ),
        array( 'my-professional-account', 'pvdak_professional_dashboard', 'پنل همکاری من' ),
    );
    foreach ( $services as $service ) {
        if ( in_array( $service[0], array( 'request-lawyer', 'request-consultation', 'request-question' ), true ) && ! pvdak_core_intake_open() ) { continue; }
        if ( 'join-legal-community' === $service[0] && ! pvdak_core_member_intake_open() ) { continue; }
        $url = pvdak_core_published_page_link( $service[0], $service[1] );
        if ( $url ) {
            $html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $service[2] ) . '</a>';
        }
    }
    $html .= '</div>';

    $html .= '<h3>آخرین درخواست‌های من</h3>';
    if ( ! $mine ) {
        $html .= '<p class="pvdak-core-empty">هنوز درخواستی ثبت نشده است.</p>';
    } else {
        $html .= '<ul class="pvdak-core-requests">';
        foreach ( array_slice( $mine, 0, 5 ) as $item ) {
            $type = get_post_meta( $item->ID, '_pvdak_request_type', true );
            $status = get_post_meta( $item->ID, '_pvdak_request_state', true );
            $html .= '<li><strong>' . esc_html( get_the_title( $item->ID ) ) . '</strong>';
            $html .= '<span>' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'درخواست حقوقی' );
            $html .= ' · ' . esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : 'ثبت‌شده' ) . '</span></li>';
        }
        $html .= '</ul>';
    }

    $html .= '<h3>وضعیت همکاری حقوقی</h3>';
    if ( $application_id ) {
        $html .= '<p class="pvdak-core-notice">' .
            esc_html( isset( $member_types[ $member_type ] ) ? $member_types[ $member_type ] : 'درخواست عضویت' ) .
            ' — ' . esc_html( isset( $member_states[ $member_state ] ) ? $member_states[ $member_state ] : $member_states['pending'] ) .
            '. این وضعیت به‌معنای تأیید پروانه یا صلاحیت حرفه‌ای نیست.</p>';
    } else {
        $html .= '<p class="pvdak-core-empty">درخواستی برای همکاری ثبت نشده است.</p>';
    }

    $html .= '</section>';
    return $html;
}
add_shortcode( 'pvdak_account_dashboard', 'pvdak_core_dashboard' );
