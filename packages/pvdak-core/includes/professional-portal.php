<?php
/**
 * Read-only personal professional portal. This is NOT a public directory,
 * license verification or a way to access confidential legal cases.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_professional_dashboard() {
    if ( ! is_user_logged_in() ) {
        return '<section class="pvdak-core-panel"><h2>پنل همکاری حقوقی</h2><p>برای مشاهده وضعیت همکاری، <a href="' .
            esc_url( wp_login_url( get_permalink() ) ) . '">وارد حساب خود شوید</a>.</p></section>';
    }
    $user_id = get_current_user_id();
    $profile_id = pvdak_core_existing_application( $user_id );
    $html = '<section class="pvdak-core-panel pvdak-core-dashboard" aria-labelledby="pvdak-prof-title">';
    $html .= '<p class="pvdak-core-eyebrow">پویش وکلای دادگستری کشور</p>';
    $html .= '<h2 id="pvdak-prof-title">پنل همکاری من</h2>';
    if ( ! $profile_id || 'pvdak_professional' !== get_post_type( $profile_id ) ||
         $user_id !== (int) get_post_field( 'post_author', $profile_id ) ) {
        $html .= '<p>هنوز درخواست همکاری ثبت نکرده‌اید.</p>';
        $join = pvdak_core_published_page_link( 'join-legal-community', 'pvdak_professional_application' );
        if ( $join && pvdak_core_member_intake_open() ) {
            $html .= '<p><a class="pvdak-core-link" href="' . esc_url( $join ) . '">ثبت درخواست همکاری</a></p>';
        }
        return $html . '</section>';
    }
    $member_type = get_post_meta( $profile_id, '_pvdak_member_type', true );
    $status = get_post_meta( $profile_id, '_pvdak_member_state', true );
    $types = pvdak_core_member_types();
    $statuses = pvdak_core_member_states();
    $type_label = isset( $types[ $member_type ] ) ? $types[ $member_type ] : 'متقاضی همکاری';
    $status_label = isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'];

    $html .= '<div class="pvdak-core-summary"><p><strong>نوع همکاری:</strong> ' .
        esc_html( $type_label ) . '</p><p><strong>وضعیت بررسی:</strong> ' . esc_html( $status_label ) .
        '</p><p><strong>شهر اعلام‌شده:</strong> ' .
        esc_html( get_post_meta( $profile_id, '_pvdak_member_city', true ) ) . '</p></div>';
    $html .= '<p class="pvdak-core-notice">ثبت یا تأیید اداری این درخواست به معنی احراز رسمی پروانه وکالت نیست. تا تکمیل سامانه، جزئیات پرونده مراجعان در این پنل نمایش داده نمی‌شود.</p>';

    if ( 'lawyer' === $member_type ) {
        $ids = pvdak_core_specialty_ids( $profile_id );
        $html .= '<h3>موضوعات تخصص ثبت‌شده توسط مدیر</h3>';
        if ( $ids ) {
            $html .= '<ul class="pvdak-core-topic-list">';
            foreach ( $ids as $landing_id ) {
                if ( ! pvdak_core_valid_specialty( $landing_id ) ) { continue; }
                $html .= '<li>' . esc_html( get_the_title( $landing_id ) ) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>هنوز تخصصی توسط مدیر برای این حساب تعیین نشده است.</p>';
        }
        // Aggregate count only: no request IDs, claimant names or case details.
        $limit = pvdak_core_capacity_limit( $profile_id );
        $active = pvdak_core_active_assignment_count( $profile_id );
        $accepting = pvdak_core_capacity_accepting( $profile_id );
        $html .= '<h3>وضعیت ظرفیت داخلی</h3>';
        $html .= '<p>حداکثر پرونده هم‌زمان: <strong>' . esc_html( $limit ) . '</strong> — پرونده‌های منتسب و فعال: <strong>' .
            esc_html( $active ) . '</strong></p>';
        $html .= '<p>' . ( $accepting && $limit > $active ? 'پذیرش انتساب داخلی باز است.' : 'پذیرش انتساب داخلی جدید متوقف است.' ) . '</p>';
        $html .= '<p><small>تنظیم ظرفیت و موضوعات فقط توسط مدیر انجام می‌شود. این آمار به معنی دریافت اطلاعات محرمانه پرونده‌ها نیست.</small></p>';
    } else {
        $html .= '<p>دسترسی به پرونده‌های وکالت برای کارآموزان و دانشجویان در این نسخه فعال نیست.</p>';
    }
    return $html . '</section>';
}
add_shortcode( 'pvdak_professional_dashboard', 'pvdak_core_professional_dashboard' );
