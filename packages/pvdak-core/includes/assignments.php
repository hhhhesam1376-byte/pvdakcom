<?php
/**
 * Manual, manager-only matching. No applicant receives private case content,
 * no email or notification is sent, and no automatic assignment occurs.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * An internal referral may never point back to its requester.
 * Zero/legacy authors are not sufficient evidence of distinct parties.
 */
function pvdak_core_assignment_parties_distinct( $request_id, $profile_id ) {
    $requester = absint( get_post_field( 'post_author', absint( $request_id ) ) );
    $professional = absint( get_post_field( 'post_author', absint( $profile_id ) ) );
    if ( ! $requester || ! $professional || $requester === $professional ) { return false; }
    $requester_user = get_userdata( $requester );
    $professional_user = get_userdata( $professional );
    return $requester_user && isset( $requester_user->ID ) &&
        (int) $requester_user->ID === $requester &&
        $professional_user && isset( $professional_user->ID ) &&
        (int) $professional_user->ID === $professional;
}

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
    $eligible = array_values( array_filter( $profiles, function ( $profile ) use ( $landing_id, $request_type ) {
        return pvdak_core_profile_referral_eligible( $profile->ID, $landing_id, $request_type ) &&
            pvdak_core_capacity_available( $profile->ID );
    } ) );
    // Fair ordering for the manager only, NOT automatic assignment:
    // least active internal load first; stable tie break by profile ID.
    $load = array();
    foreach ( $eligible as $profile ) {
        $load[(int)$profile->ID] = pvdak_core_active_assignment_count($profile->ID);
    }
    // Keep this request's read-only workload snapshot for the admin dropdown;
    // final eligibility is always rechecked INSIDE the DB advisory locks.
    $GLOBALS['pvdak_assignment_preview_load'] = $load;
    usort( $eligible, function( $a, $b ) use ( $load ) {
        $aa = $load[(int)$a->ID];
        $bb = $load[(int)$b->ID];
        return $aa === $bb ? ((int)$a->ID <=> (int)$b->ID) : ($aa <=> $bb);
    } );
    return $eligible;
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
    if ( 'pvdak_request' !== get_post_type( $request_id ) ||
         'private' !== get_post_status( $request_id ) || ! current_user_can( 'edit_post', $request_id ) ) {
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
    $candidates = array_values( array_filter( $candidates, function ( $p ) use ( $request_id ) {
        return pvdak_core_assignment_parties_distinct( $request_id, $p->ID );
    } ) );
    if ( ! $candidates ) {
        echo '<div class="notice notice-info inline"><p>متخصصی با تخصص مرتبط، تأیید داخلی و ظرفیت پذیرش آزاد پیدا نشد. وضعیت عضویت، تخصص و ظرفیت پرونده‌های فعال را در مدیریت پروفایل‌ها بررسی کنید.</p></div></div>';
        return;
    }
    if ( isset( $_GET['pvdak_assignment_error'] ) ) {
        $error = is_string( $_GET['pvdak_assignment_error'] ) ? sanitize_key( wp_unslash( $_GET['pvdak_assignment_error'] ) ) : '';
        if ( 'lock_unavailable' === $error ) {
            echo '<div class="notice notice-warning inline"><p>درخواست دیگری در حال بررسی یا ارجاع همین پرونده/متخصص است. هیچ انتساب جدیدی ثبت نشد؛ پس از بررسی وضعیت مجدداً اقدام کنید.</p></div>';
        } else {
            echo '<div class="notice notice-error inline"><p>ارجاع ثبت نشد؛ وضعیت پرونده، ظرفیت یا صلاحیت منتخب تغییر کرده است.</p></div>';
        }
    }
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'pvdak_assign_' . $request_id );
    echo '<input type="hidden" name="action" value="pvdak_core_assign">';
    echo '<input type="hidden" name="request_id" value="' . esc_attr( $request_id ) . '">';
    echo '<table class="form-table"><tr><th><label for="pvdak_profile_id">متخصص بررسی‌شده</label></th><td>';
    echo '<select id="pvdak_profile_id" name="profile_id" required><option value="">انتخاب کنید</option>';
    foreach ( $candidates as $profile ) {
        echo '<option value="' . esc_attr( $profile->ID ) . '">' . esc_html( $profile->post_title ) .
            ' — پرونده #' . esc_html( $profile->ID ) . ' | فعال: '.esc_html($GLOBALS['pvdak_assignment_preview_load'][(int)$profile->ID]??0).' از '.esc_html(pvdak_core_capacity_limit($profile->ID)).'</option>';
    }
    echo '</select></td></tr></table>';
    echo '<p>قبل از ادامه، اطلاعات صلاحیت و امکان قبول پرونده باید خارج از این نسخه به‌صورت مستقل بررسی شود. این عمل فقط شناسه پروفایل را به درخواست متصل می‌کند. ظرفیت هر متخصص هنگام ارسال دوباره بررسی می‌شود.</p>';
    submit_button( 'ثبت انتساب داخلی (بدون اطلاع‌رسانی)' );
    echo '</form></div>';
}

/**
 * A single atomic decision is protected by DB advisory locks on the request
 * AND the professional. Every validation is repeated *after* taking the locks.
 */
function pvdak_core_assign_request_decision( $request_id, $profile_id ) {
    pvdak_core_refresh_assignment_meta( $request_id, $profile_id );
    $landing_id = absint( get_post_meta( $request_id, '_pvdak_legal_landing_id', true ) );
    $type = get_post_meta( $request_id, '_pvdak_request_type', true );
    if ( 'pvdak_request' !== get_post_type( $request_id ) ||
         'private' !== get_post_status( $request_id ) ||
         'review' !== get_post_meta( $request_id, '_pvdak_request_state', true ) ||
         get_post_meta( $request_id, '_pvdak_assigned_profile_id', true ) ||
         ! pvdak_core_profile_referral_eligible( $profile_id, $landing_id, $type ) ||
         ! pvdak_core_assignment_parties_distinct( $request_id, $profile_id ) ||
         ! pvdak_core_capacity_available( $profile_id ) ) {
        return 'invalid';
    }
    // A unique key by itself is not a database unique constraint. The
    // advisory request lock is what serializes competing managers here.
    if ( ! add_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id, true ) ) {
        return 'duplicate';
    }
    // Compare-and-set: another admin can change state outside these locks.
    if ( false === update_post_meta( $request_id, '_pvdak_request_state', 'matched', 'review' ) ) {
        delete_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id );
        return 'write_failed';
    }
    $assignment_audit = array(
        'profile_id' => $profile_id, 'landing_id' => $landing_id,
        'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
        'action' => 'assigned', 'scope' => 'internal_only',
    );
    // The quota relies on this event. Fail closed if its write fails.
    // Rollback is best-effort: postmeta writes are not a DB transaction.
    if ( ! add_post_meta( $request_id, '_pvdak_assignment_audit', $assignment_audit, false ) ) {
        update_post_meta( $request_id, '_pvdak_request_state', 'review', 'matched' );
        delete_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id );
        return 'write_failed';
    }
    if ( ! add_post_meta( $request_id, '_pvdak_state_audit', array(
        'from' => 'review', 'to' => 'matched', 'by' => get_current_user_id(),
        'at' => current_time( 'mysql', true ),
    ), false ) ) {
        delete_post_meta( $request_id, '_pvdak_assignment_audit', $assignment_audit );
        update_post_meta( $request_id, '_pvdak_request_state', 'review', 'matched' );
        delete_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id );
        return 'write_failed';
    }
    return 'ok';
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
    $return = admin_url( 'admin.php?page=pvdak-assign-request&request_id=' . $request_id );
    $result = pvdak_core_with_assignment_locks( $request_id, $profile_id, function () use ( $request_id, $profile_id ) {
        return pvdak_core_assign_request_decision( $request_id, $profile_id );
    } );
    if ( 'ok' !== $result ) {
        $result = in_array( $result, array( 'invalid', 'duplicate', 'lock_unavailable', 'write_failed' ), true ) ?
            $result : 'write_failed';
        wp_safe_redirect( add_query_arg( 'pvdak_assignment_error', $result, $return ) );
        exit;
    }
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

/**
 * When admin retracts the internal clearance, changes the approved role, or
 * removes a specialty, previously internally matched cases return to review.
 * Case contents remain private; historical assignment evidence is retained.
 */
function pvdak_core_recheck_assigned_professional( $profile_id ) {
    if ( ! current_user_can( 'edit_post', $profile_id ) ||
         'pvdak_professional' !== get_post_type( $profile_id ) ) { return; }
    $requests = get_posts( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'posts_per_page' => -1,
        'meta_key' => '_pvdak_assigned_profile_id',
        'meta_value' => (int) $profile_id,
    ) );
    foreach ( $requests as $request ) {
        $request_id = (int) $request->ID;
        $result = pvdak_core_with_assignment_locks( $request_id, $profile_id, function () use ( $request_id, $profile_id ) {
            pvdak_core_refresh_assignment_meta( $request_id, $profile_id );
            if ( (int) get_post_meta( $request_id, '_pvdak_assigned_profile_id', true ) !== (int) $profile_id ) {
                return 'already_removed';
            }
            $topic = absint( get_post_meta( $request_id, '_pvdak_legal_landing_id', true ) );
            $type = get_post_meta( $request_id, '_pvdak_request_type', true );
            if ( pvdak_core_profile_referral_eligible( $profile_id, $topic, $type ) &&
                 pvdak_core_assignment_parties_distinct( $request_id, $profile_id ) ) {
                return 'eligible';
            }
            $previous = get_post_meta( $request_id, '_pvdak_request_state', true );
            delete_post_meta( $request_id, '_pvdak_assigned_profile_id', $profile_id );
            if ( 'matched' === $previous ) {
                update_post_meta( $request_id, '_pvdak_request_state', 'review' );
                add_post_meta( $request_id, '_pvdak_state_audit', array(
                    'from' => 'matched', 'to' => 'review',
                    'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
                    'reason' => 'eligibility_revoked',
                ), false );
            }
            add_post_meta( $request_id, '_pvdak_assignment_audit', array(
                'profile_id' => (int) $profile_id,
                'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
                'action' => 'eligibility_revoked', 'scope' => 'internal_only',
            ), false );
            return 'revoked';
        } );
        if ( 'lock_unavailable' === $result ) {
            // Fail visibly: staff must re-run review after a timeout.
            add_post_meta( $profile_id, '_pvdak_referral_review_warning', array(
                'request_id' => $request_id, 'at' => current_time( 'mysql', true ),
                'reason' => 'assignment_lock_timeout',
            ), false );
        }
    }
}
// Run AFTER the separate administrative status and specialty save handlers.
add_action( 'save_post_pvdak_professional', 'pvdak_core_recheck_assigned_professional', 30 );
