<?php
/**
 * Read-only professional membership portal.
 *
 * The applicant may see only their own non-public application metadata.
 * Administrative approval is NOT legal credential verification, and no
 * client/case identity, content or assignment identifier is disclosed.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Read-only account guidance. This does not grant permissions, reveal cases,
 * or replace the locked assignment-time checks in the matching engine.
 */
function pvdak_core_professional_portal_access( $profile_id, $member_type, $member_state ) {
    $id = absint( $profile_id );
    $blocked = function( $reason ) {
        return array( 'ready' => false, 'reason' => $reason );
    };
    if ( ! $id || 'pvdak_professional' !== get_post_type( $id ) ||
         'private' !== get_post_status( $id ) || ! pvdak_core_profile_has_owner( $id ) ) {
        return $blocked( 'پروفایل خصوصی معتبر و حساب مالک برای ارجاع داخلی لازم است.' );
    }
    // Do not trust a caller's cached type/state. Recheck the persisted record:
    // a stale UI or another integration must not show referral readiness after
    // approval is withdrawn or the membership category changes.
    if ( 'lawyer' !== $member_type || 'approved' !== $member_state ||
         'lawyer' !== get_post_meta( $id, '_pvdak_member_type', true ) ||
         'approved' !== get_post_meta( $id, '_pvdak_member_state', true ) ) {
        return $blocked( 'نوع همکاری یا تأیید اداری فعلی پرونده برای ارجاع داخلی معتبر نیست.' );
    }
    if ( '1' !== (string) get_post_meta( $id, '_pvdak_referral_cleared', true ) ) {
        return $blocked( 'مجوز ارجاع داخلی توسط مدیر ثبت نشده است.' );
    }
    if ( ! pvdak_core_license_review_current( $id ) ) {
        return $blocked( 'بررسی مستقل صلاحیت معتبر نیست یا تاریخ اعتبار آن گذشته است.' );
    }
    if ( ! pvdak_core_specialty_ids( $id ) ) {
        return $blocked( 'هنوز حوزه تخصصی معتبر و منتشرشده‌ای برای ارجاع تعیین نشده است.' );
    }
    if ( ! pvdak_core_capacity_accepting( $id ) ) {
        return $blocked( 'پذیرش انتساب داخلی توسط مدیر متوقف شده است.' );
    }
    $limit = pvdak_core_capacity_limit( $id );
    if ( $limit < 1 ) {
        return $blocked( 'سقف ظرفیت انتساب داخلی هنوز توسط مدیر تنظیم نشده است.' );
    }
    $active = pvdak_core_active_assignment_count( $id );
    if ( PHP_INT_MAX === $active ) {
        return $blocked( 'محاسبه ظرفیت قابل اتکا نیست؛ ارجاع جدید تا بررسی مدیر متوقف است.' );
    }
    if ( $active >= $limit ) {
        return $blocked( 'ظرفیت انتساب داخلی تکمیل است.' );
    }
    if ( function_exists( 'pvdak_core_subscription_allows_new_assignment' ) &&
         ! pvdak_core_subscription_allows_new_assignment( $id ) ) {
        return $blocked( 'اشتراک یا تاریخ انقضا یا سهمیه ماهانه، انتساب تازه را مجاز نمی‌کند.' );
    }
    return array(
        'ready' => true,
        'reason' => 'شرایط اولیه ارجاع داخلی فراهم است؛ تصمیم نهایی فقط با بررسی مجدد مدیر و کنترل‌های مستقل هر پرونده انجام می‌شود.',
    );
}

/** Invalid stored categories and impossible draft approvals need review, not
 * an apparent successful administrative workflow in the applicant UI.
 */
function pvdak_core_professional_visible_state( $type, $state, $visibility ) {
    $types = pvdak_core_member_types();
    $statuses = pvdak_core_member_states();
    if ( ! is_string( $type ) || ! isset( $types[ $type ] ) ||
         ! is_string( $state ) || ! isset( $statuses[ $state ] ) ||
         ( 'draft' === $visibility && 'approved' === $state ) ) {
        return 'unknown';
    }
    return $state;
}

function pvdak_core_professional_dashboard() {
    if ( ! is_user_logged_in() ) {
        return '<section class="pvdak-core-panel"><h2>پنل همکاری حقوقی</h2><p>برای مشاهده وضعیت درخواست همکاری، <a href="' .
            esc_url( wp_login_url( get_permalink() ) ) . '">وارد حساب خود شوید</a>.</p></section>';
    }

    $user_id = get_current_user_id();
    $profile_id = absint( pvdak_core_existing_application( $user_id ) );
    $account_url = pvdak_core_account_url();
    $contact = get_page_by_path( 'contact-us', OBJECT, 'page' );
    $contact_url = $contact && 'publish' === $contact->post_status ? get_permalink( $contact->ID ) : '';

    $html = '<section class="pvdak-core-panel pvdak-core-dashboard pvdak-core-professional" aria-labelledby="pvdak-prof-title">';
    $html .= '<p class="pvdak-core-eyebrow">پویش وکلای دادگستری کشور</p>';
    $html .= '<h2 id="pvdak-prof-title">پنل همکاری من</h2>';

    // Never trust the cached application ID without checking the object again.
    $owned = $profile_id &&
        'pvdak_professional' === get_post_type( $profile_id ) &&
        $user_id === (int) get_post_field( 'post_author', $profile_id ) &&
        in_array( get_post_status( $profile_id ), array( 'private', 'draft' ), true );
    if ( ! $owned ) {
        $html .= '<div class="pvdak-core-empty"><h3>درخواست همکاری پیدا نشد</h3>';
        $html .= '<p>هنوز درخواست همکاری خصوصی قابل مشاهده‌ای برای این حساب ثبت نشده است.</p></div>';
        $join = pvdak_core_published_page_link( 'join-legal-community', 'pvdak_professional_application' );
        if ( $join && pvdak_core_member_intake_open() ) {
            $html .= '<p><a class="pvdak-core-link" href="' . esc_url( $join ) . '">رفتن به فرم درخواست همکاری</a></p>';
        } else {
            $html .= '<p class="pvdak-core-notice">ثبت درخواست همکاری جدید در حال حاضر غیرفعال است.</p>';
        }
        if ( $account_url ) {
            $html .= '<p><a class="pvdak-core-link" href="' . esc_url( $account_url ) . '">بازگشت به پنل کاربری</a></p>';
        }
        return $html . '</section>';
    }

    $types = pvdak_core_member_types();
    $statuses = pvdak_core_member_states();
    $member_type = get_post_meta( $profile_id, '_pvdak_member_type', true );
    $raw_status = get_post_meta( $profile_id, '_pvdak_member_state', true );
    // A damaged membership category or an impossible approved draft cannot
    // be shown as an accepted application, even if state meta says approved.
    $status = pvdak_core_professional_visible_state(
        $member_type, $raw_status, get_post_status( $profile_id )
    );
    $status_label = 'unknown' === $status ? 'نیازمند بررسی مدیر' : $statuses[ $status ];
    $type_label = is_string( $member_type ) && isset( $types[ $member_type ] )
        ? $types[ $member_type ] : 'نوع همکاری نامعتبر؛ نیازمند بررسی مدیر';
    $city = get_post_meta( $profile_id, '_pvdak_member_city', true );
    $city_label = is_string( $city ) && '' !== trim( $city ) ? $city : 'ثبت نشده';
    $created_iso = get_the_date( 'Y-m-d', $profile_id );
    $created_label = get_the_date( 'Y/m/d', $profile_id );
    $steps = array( 'pending' => 'ثبت درخواست', 'review' => 'بررسی اداری', 'approved' => 'تأیید اداری' );
    $step_number = 'approved' === $status ? 3 : ( 'review' === $status ? 2 : 1 );

    $html .= '<div class="pvdak-core-prof-status pvdak-core-prof-status--' . esc_attr( $status ) . '">';
    $html .= '<span class="pvdak-core-prof-status-label">وضعیت درخواست</span>';
    $html .= '<strong>' . esc_html( $status_label ) . '</strong></div>';

    $html .= '<dl class="pvdak-core-prof-facts">';
    $facts = array( 'شماره پیگیری' => '#' . $profile_id, 'نوع همکاری' => $type_label, 'شهر اعلام‌شده' => $city_label );
    foreach ( $facts as $label => $value ) {
        $html .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
    }
    $html .= '<div><dt>تاریخ ثبت</dt><dd><time datetime="' . esc_attr( $created_iso ) . '">' .
        esc_html( $created_label ) . '</time></dd></div></dl>';

    $html .= '<h3>مراحل بررسی درخواست</h3>';
    if ( 'rejected' === $status ) {
        $html .= '<p class="pvdak-core-notice" role="status">بررسی اداری این درخواست با وضعیت ردشده پایان یافته است. برای پرسش عمومی از مسیر تماس با پشتیبانی استفاده کنید؛ ثبت دوباره خودکار فعال نیست.</p>';
    } elseif ( 'unknown' === $status ) {
        $html .= '<p class="pvdak-core-notice" role="status">نوع همکاری یا وضعیت ذخیره‌شده درخواست نیازمند اصلاح مدیر است؛ مراحل بررسی تا اصلاح توسط مدیر نمایش داده نمی‌شوند.</p>';
    } else {
        $html .= '<ol class="pvdak-core-prof-steps" aria-label="مراحل بررسی اداری">';
        $position = 0;
        foreach ( $steps as $key => $label ) {
            $position++;
            $class = $position < $step_number ? 'is-done' : ( $position === $step_number ? 'is-current' : 'is-next' );
            $html .= '<li class="' . esc_attr( $class ) . '"' .
                ( $position === $step_number ? ' aria-current="step"' : '' ) . '><span>' .
                esc_html( $label ) . '</span></li>';
        }
        $html .= '</ol>';
    }

    $guidance = array(
        'pending' => 'درخواست شما ثبت شده و هنوز وارد مرحله بررسی اداری نشده است. برای تکمیل یا اصلاح اطلاعات از مسیر پشتیبانی اقدام کنید.',
        'review' => 'درخواست شما در حال بررسی اداری است. این مرحله به معنی تأیید مدارک یا صلاحیت حرفه‌ای نیست.',
        'approved' => 'بررسی اداری درخواست تکمیل شده است. این وضعیت به معنی احراز رسمی پروانه یا اجازه دسترسی به پرونده مراجعان نیست.',
        'rejected' => 'این درخواست در وضعیت ردشده است و هیچ دسترسی حرفه‌ای ایجاد نمی‌کند.',
        'unknown' => 'وضعیت درخواست نیازمند بازبینی مدیر است؛ هیچ دسترسی حرفه‌ای بر اساس این مقدار فعال نمی‌شود.',
    );
    $html .= '<p class="pvdak-core-prof-guidance">' . esc_html( $guidance[ $status ] ) . '</p>';
    $access = 'unknown' === $status ?
        array( 'ready' => false, 'reason' => 'وضعیت درخواست نامعتبر است؛ بررسی مدیر لازم است.' ) :
        pvdak_core_professional_portal_access( $profile_id, $member_type, $status );
    $html .= '<div class="pvdak-core-notice" role="status"><strong>وضعیت امکان ارجاع داخلی: ' .
        ( $access['ready'] ? 'دارای شرایط اولیه' : 'غیرفعال' ) . '</strong><p>' .
        esc_html( $access['reason'] ) . '</p></div>';
    $html .= '<p class="pvdak-core-notice">این پنل فقط اطلاعات خودتان را نمایش می‌دهد. جزئیات پرونده مراجعان، مشخصات اشخاص ثالث و مدارک حساس در این بخش نمایش داده نمی‌شوند.</p>';
    $html .= pvdak_core_directory_consent_panel( $profile_id );

    // Neither a role alone nor a rejected/pending application unlocks capacity.
    // Read-only operational figures are not a substitute for clearance.
    // Keep them hidden for draft, revoked or expired professional reviews.
    $review_current = 'private' === get_post_status( $profile_id ) &&
        '1' === (string) get_post_meta( $profile_id, '_pvdak_referral_cleared', true ) &&
        pvdak_core_license_review_current( $profile_id );
    if ( 'lawyer' === $member_type && 'approved' === $status && $review_current ) {
        $html .= '<section class="pvdak-core-prof-section" aria-labelledby="pvdak-prof-specialties"><h3 id="pvdak-prof-specialties">تخصص‌های ثبت‌شده توسط مدیر</h3>';
        $ids = pvdak_core_specialty_ids( $profile_id );
        $valid_ids = array();
        foreach ( $ids as $landing_id ) {
            $landing_id = absint( $landing_id );
            if ( $landing_id && pvdak_core_valid_specialty( $landing_id ) &&
                'publish' === get_post_status( $landing_id ) && ! in_array( $landing_id, $valid_ids, true ) ) {
                $valid_ids[] = $landing_id;
            }
        }
        if ( $valid_ids ) {
            $html .= '<ul class="pvdak-core-topic-list">';
            foreach ( $valid_ids as $landing_id ) {
                $html .= '<li>' . esc_html( get_the_title( $landing_id ) ) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="pvdak-core-empty">هنوز تخصص منتشرشده و معتبری توسط مدیر ثبت نشده است.</p>';
        }
        $html .= '</section>';

        $limit = pvdak_core_capacity_limit( $profile_id );
        $active = pvdak_core_active_assignment_count( $profile_id );
        $capacity_error = PHP_INT_MAX === $active;
        $remaining = $capacity_error ? 0 : max( 0, $limit - $active );
        $accepting = pvdak_core_capacity_accepting( $profile_id );
        $html .= '<section class="pvdak-core-prof-section" aria-labelledby="pvdak-prof-capacity"><h3 id="pvdak-prof-capacity">ظرفیت انتساب داخلی</h3>';
        if ( $capacity_error ) {
            $html .= '<p class="pvdak-core-notice" role="status">شمارش ظرفیت فعلاً قابل اتکا نیست؛ ارجاع جدید تا بررسی مدیر متوقف می‌ماند.</p>';
        } else {
            $html .= '<div class="pvdak-core-prof-numbers"><div><strong>' . esc_html( $limit ) . '</strong><span>سقف هم‌زمان</span></div>';
            $html .= '<div><strong>' . esc_html( $active ) . '</strong><span>انتساب فعال</span></div>';
            $html .= '<div><strong>' . esc_html( $remaining ) . '</strong><span>ظرفیت باقی‌مانده</span></div></div>';
        }
        if ( ! $capacity_error && $limit > 0 ) {
            $html .= '<label class="pvdak-core-prof-meter">استفاده از ظرفیت <progress max="' . esc_attr( $limit ) .
                '" value="' . esc_attr( min( $active, $limit ) ) . '">' .
                esc_html( min( $active, $limit ) ) . '</progress></label>';
        }
        if ( $capacity_error ) {
            $capacity_message = 'برای بررسی خطای شمارش ظرفیت با پشتیبانی تماس بگیرید.';
        } elseif ( ! $accepting ) {
            $capacity_message = 'انتساب داخلی جدید توسط مدیر متوقف شده است.';
        } elseif ( 0 === $limit ) {
            $capacity_message = 'سقف ظرفیت هنوز توسط مدیر تنظیم نشده است.';
        } elseif ( 0 === $remaining ) {
            $capacity_message = 'ظرفیت داخلی فعلاً تکمیل است.';
        } else {
            $capacity_message = 'از نظر تنظیم ظرفیت داخلی، امکان بررسی انتساب جدید وجود دارد؛ سایر کنترل‌ها مستقل‌اند.';
        }
        $html .= '<p class="pvdak-core-prof-hint">' . esc_html( $capacity_message ) . '</p>';
        $html .= '<p><small>این اعداد فقط شمارش داخلی هستند و مجوز دسترسی به پرونده‌ها یا پذیرش پرونده جدید محسوب نمی‌شوند.</small></p></section>';

        if ( function_exists( 'pvdak_core_subscription_plan' ) ) {
            $plans = array( 'none' => 'بدون طرح', 'basic' => 'پایه', 'standard' => 'استاندارد', 'advanced' => 'پیشرفته' );
            $states = array( 'inactive' => 'غیرفعال', 'active' => 'فعال دستی', 'paused' => 'تعلیق' );
            $plan = pvdak_core_subscription_plan( $profile_id );
            $plan_label = isset( $plans[ $plan ] ) ? $plans[ $plan ] : $plans['none'];
            $raw_sub_state = get_post_meta( $profile_id, '_pvdak_subscription_state', true );
            $sub_state = is_string( $raw_sub_state ) && isset( $states[ $raw_sub_state ] ) ? $raw_sub_state : 'inactive';
            $expiry = pvdak_core_subscription_expiry( $profile_id );
            $quota = pvdak_core_subscription_monthly_quota( $profile_id );
            $gate = pvdak_core_subscription_gate_on();
            $used = $gate ? pvdak_core_subscription_month_assignments( $profile_id ) : 0;
            $quota_error = PHP_INT_MAX === $used;
            $left = $quota_error ? 0 : max( 0, $quota - $used );

            $html .= '<section class="pvdak-core-prof-section" aria-labelledby="pvdak-prof-plan"><h3 id="pvdak-prof-plan">طرح و سهمیه ثبت‌شده</h3>';
            $html .= '<dl class="pvdak-core-prof-facts"><div><dt>طرح</dt><dd>' . esc_html( $plan_label ) . '</dd></div>';
            $html .= '<div><dt>وضعیت ثبت دستی</dt><dd>' . esc_html( $states[ $sub_state ] ) . '</dd></div>';
            $html .= '<div><dt>سقف ماهانه</dt><dd>' . esc_html( $quota ) . '</dd></div>';
            $html .= '<div><dt>تاریخ انقضا</dt><dd>' .
                ( $expiry ? '<time datetime="' . esc_attr( $expiry ) . '">' . esc_html( $expiry ) . '</time>' : 'تنظیم نشده' ) .
                '</dd></div></dl>';
            if ( $gate ) {
                if ( $quota_error ) {
                    $html .= '<p class="pvdak-core-notice" role="status">سهمیه ماهانه فعلاً قابل محاسبه نیست؛ انتساب تازه تا رفع خطا مجاز نیست.</p>';
                } else {
                    $html .= '<p>انتساب‌های ثبت‌شده در ماه جاری: <strong>' . esc_html( $used ) .
                        '</strong> · سهمیه باقی‌مانده: <strong>' . esc_html( $left ) . '</strong></p>';
                }
                if ( ! $quota_error && $quota > 0 ) {
                    $html .= '<label class="pvdak-core-prof-meter">مصرف سهمیه ماهانه <progress max="' .
                        esc_attr( $quota ) . '" value="' . esc_attr( min( $used, $quota ) ) . '">' .
                        esc_html( min( $used, $quota ) ) . '</progress></label>';
                }
                if ( 'active' !== $sub_state || ! $expiry || $expiry < current_time( 'Y-m-d' ) || 0 === $left ) {
                    $html .= '<p class="pvdak-core-prof-hint">شرایط سهمیه یا تاریخ اعتبار برای انتساب جدید کامل نیست. تصمیم نهایی به کنترل‌های داخلی بستگی دارد.</p>';
                }
            } else {
                $html .= '<p class="pvdak-core-notice">کنترل سهمیه اشتراک فعلاً غیرفعال است. مصرف سهمیه محاسبه نمی‌شود و اطلاعات فوق به معنی پرداخت، خرید یا دسترسی تأییدشده نیست.</p>';
            }
            $html .= '<p><small>طرح‌ها صرفاً توسط مدیر ثبت می‌شوند؛ خرید و پرداخت آنلاین در این نسخه وجود ندارد.</small></p></section>';
        }
    } elseif ( 'approved' === $status && 'lawyer' === $member_type ) {
        $html .= '<p class="pvdak-core-empty">تا ثبت مجوز داخلی و بررسی مستقل معتبر، جزئیات ظرفیت و طرح حرفه‌ای در پنل فعال نمی‌شود.</p>';
    } elseif ( 'approved' === $status ) {
        $html .= '<p class="pvdak-core-empty">ظرفیت انتساب وکالت فقط برای درخواست تأیید اداری‌شده از نوع وکیل قابل نمایش است. برای کارآموز و دانشجو فعال نیست.</p>';
    } else {
        $html .= '<p class="pvdak-core-empty">تا تکمیل بررسی اداری، بخش تخصص و ظرفیت انتساب داخلی نمایش داده نمی‌شود.</p>';
    }

    $html .= '<nav class="pvdak-core-prof-links" aria-label="مسیرهای مرتبط با حساب">';
    if ( $account_url ) {
        $html .= '<a href="' . esc_url( $account_url ) . '">بازگشت به پنل کاربری</a>';
    }
    if ( $contact_url ) {
        $html .= '<a href="' . esc_url( $contact_url ) . '">تماس عمومی با پشتیبانی</a>';
    }
    $html .= '</nav><p><small>برای پیگیری وضعیت، اطلاعات محرمانه یا مدارک اشخاص را در فرم تماس عمومی وارد نکنید.</small></p>';
    return $html . '</section>';
}
add_shortcode( 'pvdak_professional_dashboard', 'pvdak_core_professional_dashboard' );
