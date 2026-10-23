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

    // Pending/rejected professional applicants must not see capacity or plan
    // UI that could be mistaken for permission to receive real case details.
    if ( 'approved' !== $status ) {
        $html .= '<p class="pvdak-core-notice">پرونده همکاری هنوز در وضعیت تأییدشده قرار ندارد؛ ظرفیت و ارجاع تخصصی تا بررسی مدیر قابل استفاده نیست.</p>';
    }
    if ( 'lawyer' === $member_type && 'approved' === $status ) {
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
        if ( function_exists( 'pvdak_core_subscription_plan' ) ) {
            $plans = array('none'=>'بدون طرح','basic'=>'پایه','standard'=>'استاندارد','advanced'=>'پیشرفته');
            $sub_plan = pvdak_core_subscription_plan($profile_id);
            $sub_state = get_post_meta($profile_id,'_pvdak_subscription_state',true);
            $sub_expiry = pvdak_core_subscription_expiry($profile_id);
            $sub_quota = pvdak_core_subscription_monthly_quota($profile_id);
            $sub_used = pvdak_core_subscription_gate_on()?pvdak_core_subscription_month_assignments($profile_id):0;
            $html .= '<h3>طرح و سهمیه ثبت‌شده</h3>';
            $html .= '<p>طرح: <strong>'.esc_html($plans[$sub_plan]).'</strong> · وضعیت: <strong>'.esc_html($sub_state?:'غیرفعال').'</strong></p>';
            $html .= '<p>انقضا: '.esc_html($sub_expiry?:'تنظیم نشده').' · سقف ماهانه: '.esc_html($sub_quota).'</p>';
            $html .= '<p><small>'.(pvdak_core_subscription_gate_on() ?
                'انتساب‌های ثبت‌شده این ماه: '.esc_html($sub_used).'؛ مدیریت طرح فقط توسط مدیر.' :
                'کنترل اشتراک فعلاً غیرفعال است؛ این اطلاعات نمایشی، پرداخت یا دسترسی تأییدشده نیست.').'</small></p>';
        }
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
