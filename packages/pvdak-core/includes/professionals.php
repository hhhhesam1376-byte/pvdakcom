<?php
/** Private onboarding applications for legal-community members. No public directory. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_member_intake_open() {
    if ( defined( 'PVDAK_CORE_DISABLE_INTAKE' ) && PVDAK_CORE_DISABLE_INTAKE ) { return false; }
    return '1' === (string) get_option( 'pvdak_core_member_intake_enabled', '0' );
}

function pvdak_core_member_types() {
    return array(
        'lawyer' => 'متقاضی عضویت به‌عنوان وکیل',
        'trainee' => 'متقاضی عضویت به‌عنوان کارآموز',
        'student' => 'متقاضی عضویت به‌عنوان دانشجوی حقوق',
    );
}

function pvdak_core_member_states() {
    return array(
        'pending' => 'ثبت‌شده / در انتظار بررسی',
        'review' => 'در حال بررسی',
        'approved' => 'تأیید اداری (بدون انتشار عمومی)',
        'rejected' => 'ردشده',
    );
}

function pvdak_core_existing_application( $user_id ) {
    $user_id = absint( $user_id );
    if ( ! $user_id ) { return 0; }
    $linked_raw = get_user_meta( $user_id, '_pvdak_core_application', true );
    $linked = is_scalar( $linked_raw ) && ctype_digit( (string) $linked_raw ) ? absint( $linked_raw ) : 0;
    if ( $linked && 'pvdak_professional' === get_post_type( $linked ) &&
         in_array( get_post_status( $linked ), array( 'private', 'draft' ), true ) &&
         (int) get_post_field( 'post_author', $linked ) === $user_id ) {
        return $linked;
    }
    $matches = get_posts( array(
        'post_type' => 'pvdak_professional',
        'post_status' => array( 'private', 'draft' ),
        'author' => (int) $user_id,
        'posts_per_page' => 20,
        'fields' => 'ids',
        'no_found_rows' => true,
        'cache_results' => false,
        'suppress_filters' => false,
    ) );
    if ( ! is_array( $matches ) ) { return 0; }
    foreach ( $matches as $candidate ) {
        if ( ! is_scalar( $candidate ) || ! ctype_digit( (string) $candidate ) ) { continue; }
        $id = absint( $candidate );
        // A third-party query filter may return another account's application.
        if ( ! $id || 'pvdak_professional' !== get_post_type( $id ) ||
             ! in_array( get_post_status( $id ), array( 'private', 'draft' ), true ) ||
             (int) get_post_field( 'post_author', $id ) !== $user_id ) { continue; }
        update_user_meta( $user_id, '_pvdak_core_application', $id );
        return $id;
    }
    return 0;
}

function pvdak_core_member_application_form() {
    if ( ! is_user_logged_in() ) {
        return '<p class="pvdak-core-notice">برای درخواست عضویت در جامعه حقوقی ابتدا <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">وارد حساب شوید</a>.</p>';
    }
    // Existing applicants must see their status even while new intake is closed.
    $existing_id = pvdak_core_existing_application( get_current_user_id() );
    if ( $existing_id ) {
        $current = get_post_meta( $existing_id, '_pvdak_member_state', true );
        $states = pvdak_core_member_states();
        $html = '<p class="pvdak-core-notice" role="status">درخواست عضویت شما با شماره پیگیری #' . esc_html( $existing_id ) . ': ' .
            esc_html( is_string( $current ) && isset( $states[ $current ] ) ? $states[ $current ] : 'نیازمند بررسی' ) . '. برای اصلاح مشخصات با پشتیبانی تماس بگیرید.</p>';
        $dashboard = pvdak_core_published_page_link( 'my-professional-account', 'pvdak_professional_dashboard' );
        if ( $dashboard ) { $html .= '<p><a href="' . esc_url( $dashboard ) . '">پیگیری در پنل همکاری</a></p>'; }
        return $html;
    }
    if ( ! pvdak_core_member_intake_open() ) {
        return '<p class="pvdak-core-notice">ثبت درخواست همکاری در حال آماده‌سازی است.</p>';
    }
    $html = '<section class="pvdak-core-panel"><h2>اعلام آمادگی برای همکاری</h2>';
    $html .= '<p>این فرم صرفاً درخواست بررسی عضویت است و به‌معنای احراز صلاحیت حرفه‌ای یا اعطای دسترسی وکالت نیست. از وارد کردن شماره مدارک هویتی یا ارسال اسناد حساس خودداری کنید.</p>';
    if ( isset( $_GET['pvdak_application'] ) && is_string( $_GET['pvdak_application'] ) &&
         'error' === sanitize_key( wp_unslash( $_GET['pvdak_application'] ) ) ) {
        $html .= '<p class="pvdak-core-error" role="alert">ثبت درخواست انجام نشد. اطلاعات را بررسی کنید.</p>';
    }
    $html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pvdak-core-form">';
    $html .= wp_nonce_field( 'pvdak_core_member_apply', '_pvdak_member_nonce', true, false );
    $html .= '<input type="hidden" name="action" value="pvdak_core_member_apply">';
    $html .= '<label>زمینه همکاری<select name="pvdak_member_type" required><option value="">انتخاب کنید</option>';
    foreach ( pvdak_core_member_types() as $key => $label ) {
        $html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
    }
    $html .= '</select></label>';
    $html .= '<label>نام نمایشی <input type="text" name="pvdak_member_name" maxlength="110" required></label>';
    $html .= '<label>شهر فعالیت <input type="text" name="pvdak_member_city" maxlength="90" required></label>';
    $html .= '<label>حوزه‌های مورد علاقه/تخصص <textarea name="pvdak_member_about" maxlength="1600" rows="4" required></textarea></label>';
    $html .= '<label class="pvdak-core-consent"><input type="checkbox" name="pvdak_consent" value="1" required> موافقم اطلاعات این درخواست همکاری برای بررسی اداری ذخیره و توسط مدیران مجاز بررسی شود.</label>';
    $html .= '<button type="submit">ارسال برای بررسی</button></form></section>';
    return $html;
}
add_shortcode( 'pvdak_professional_application', 'pvdak_core_member_application_form' );

/**
 * Server-side limits use Unicode code points AND bytes; HTML maxlength is not
 * a security boundary. Reject invalid UTF-8 and blank sanitized content.
 */
function pvdak_core_member_application_input_valid( $type, $name, $city, $about, $consent ) {
    if ( ! $consent || ! array_key_exists( $type, pvdak_core_member_types() ) ) { return false; }
    foreach ( array( array( $name, 110, 450 ), array( $city, 90, 360 ), array( $about, 1600, 6400 ) ) as $field ) {
        if ( ! is_string( $field[0] ) || '' === trim( $field[0] ) ||
             strlen( $field[0] ) > $field[2] || 1 !== preg_match( '//u', $field[0] ) ||
             preg_match_all( '/./us', $field[0] ) > $field[1] ) { return false; }
    }
    return true;
}

/**
 * Serialize submissions for one user across PHP workers using the existing
 * MariaDB connection. If advisory locks are unavailable, fail closed.
 */
function pvdak_core_with_member_application_lock( $user_id, $callback ) {
    global $wpdb;
    if ( ! absint( $user_id ) || ! is_callable( $callback ) || ! isset( $wpdb ) ||
         ! method_exists( $wpdb, 'get_var' ) || ! method_exists( $wpdb, 'prepare' ) ) {
        return 'lock_unavailable';
    }
    $name = 'pvdak:member:' . absint( $user_id );
    if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, 3 ) ) ) {
        return 'lock_unavailable';
    }
    try {
        return call_user_func( $callback );
    } finally {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
    }
}

function pvdak_core_submit_application() {
    if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
        wp_die( 'روش درخواست نامعتبر است.', '', array( 'response' => 405 ) );
    }
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! pvdak_core_member_intake_open() ) {
        wp_die( 'امکان ثبت درخواست وجود ندارد.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_member_apply', '_pvdak_member_nonce' );
    $type = isset( $_POST['pvdak_member_type'] ) && is_string( $_POST['pvdak_member_type'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_member_type'] ) ) : '';
    $name = isset( $_POST['pvdak_member_name'] ) && is_string( $_POST['pvdak_member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_member_name'] ) ) : '';
    $city = isset( $_POST['pvdak_member_city'] ) && is_string( $_POST['pvdak_member_city'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_member_city'] ) ) : '';
    $about = isset( $_POST['pvdak_member_about'] ) && is_string( $_POST['pvdak_member_about'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pvdak_member_about'] ) ) : '';
    $consent = isset( $_POST['pvdak_consent'] ) && is_string( $_POST['pvdak_consent'] ) && '1' === wp_unslash( $_POST['pvdak_consent'] );
    $back = wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) );
    if ( ! pvdak_core_member_application_input_valid( $type, $name, $city, $about, $consent ) ) {
        wp_safe_redirect( add_query_arg( 'pvdak_application', 'error', $back ) );
        exit;
    }
    $user_id = get_current_user_id();
    $result = pvdak_core_with_member_application_lock( $user_id, function() use ( $user_id, $type, $name, $city, $about ) {
        // Bypass stale post caches before checking inside the database lock.
        if ( function_exists( 'wp_cache_delete' ) ) { wp_cache_delete( $user_id, 'user_meta' ); }
        if ( pvdak_core_existing_application( $user_id ) ) { return 'existing'; }
        if ( ! pvdak_core_member_intake_open() ) { return 'closed'; }
        $id = wp_insert_post( array(
            'post_type' => 'pvdak_professional',
            'post_status' => 'private',
            'post_author' => $user_id,
            'post_title' => $name,
            'post_content' => $about,
            'meta_input' => array(
                '_pvdak_member_type' => $type,
                '_pvdak_member_city' => $city,
                '_pvdak_member_state' => 'pending',
            ),
        ), true );
        if ( is_wp_error( $id ) || ! $id ) { return 'write_failed'; }
        $id = absint( $id );
        // WP may return a post ID even if individual meta_input writes failed.
        if ( 'pvdak_professional' !== get_post_type( $id ) ||
             'private' !== get_post_status( $id ) ||
             (int) get_post_field( 'post_author', $id ) !== $user_id ||
             $type !== get_post_meta( $id, '_pvdak_member_type', true ) ||
             $city !== get_post_meta( $id, '_pvdak_member_city', true ) ||
             'pending' !== get_post_meta( $id, '_pvdak_member_state', true ) ) {
            wp_delete_post( $id, true ); // Only this newly created incomplete record.
            return 'write_failed';
        }
        if ( ! update_user_meta( $user_id, '_pvdak_core_application', $id ) &&
             $id !== absint( get_user_meta( $user_id, '_pvdak_core_application', true ) ) ) {
            wp_delete_post( $id, true );
            return 'write_failed';
        }
        return 'created';
    } );
    wp_safe_redirect( add_query_arg( 'pvdak_application',
        in_array( $result, array( 'created', 'existing' ), true ) ? 'received' : 'error', $back ) );
    exit;
}
add_action( 'admin_post_pvdak_core_member_apply', 'pvdak_core_submit_application' );

function pvdak_core_member_meta_box() {
    add_meta_box( 'pvdak_member_review', 'وضعیت بررسی عضویت', 'pvdak_core_member_meta_box_html', 'pvdak_professional', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'pvdak_core_member_meta_box' );

/** Explicit review flow: approval cannot jump straight from pending. */
function pvdak_core_member_allowed_transitions( $state ) {
    $allowed = array(
        'pending' => array( 'review', 'rejected' ),
        'review' => array( 'pending', 'approved', 'rejected' ),
        'approved' => array( 'review' ),
        'rejected' => array( 'review' ),
    );
    return isset( $allowed[ $state ] ) ? $allowed[ $state ] : array();
}

function pvdak_core_member_meta_box_html( $post ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_core_member_review_' . $post->ID, '_pvdak_member_review_nonce' );
    $type = get_post_meta( $post->ID, '_pvdak_member_type', true );
    $types = pvdak_core_member_types();
    echo '<p><strong>نوع درخواست:</strong> ' . esc_html( is_string( $type ) && isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    $city = get_post_meta( $post->ID, '_pvdak_member_city', true );
    echo '<p><strong>شهر:</strong> ' . esc_html( is_string( $city ) ? $city : 'نامشخص' ) . '</p>';
    echo '<p>تغییر وضعیت صرفاً اداری است؛ تأیید رسمی مدارک و انتشار پروفایل در این نسخه انجام نمی‌شود.</p>';
    $state = get_post_meta( $post->ID, '_pvdak_member_state', true );
    $states = pvdak_core_member_states();
    if ( ! is_string( $state ) || ! isset( $states[ $state ] ) ) {
        echo '<p class="notice notice-warning">وضعیت اولیه نامعتبر یا خالی است؛ تغییر خودکار مسدود شد.</p>';
        return;
    }
    echo '<input type="hidden" name="pvdak_member_previous_state" value="' . esc_attr( $state ) . '">';
    echo '<label for="pvdak_member_state">وضعیت</label><select id="pvdak_member_state" name="pvdak_member_state" class="widefat">';
    foreach ( array_merge( array( $state ), pvdak_core_member_allowed_transitions( $state ) ) as $key ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $state, $key, false ) . '>' . esc_html( $states[ $key ] ) . '</option>';
    }
    echo '</select>';
}

function pvdak_core_save_member_review( $id ) {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! current_user_can( 'manage_options' ) ) { return; }
    if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
         ! current_user_can( 'edit_post', $id ) || 'pvdak_professional' !== get_post_type( $id ) ||
         ! in_array( get_post_status( $id ), array( 'private', 'draft' ), true ) ) { return; }
    $owner = absint( get_post_field( 'post_author', $id ) );
    if ( ! $owner || ! get_userdata( $owner ) ) { return; }
    if ( ! isset( $_POST['_pvdak_member_review_nonce'] ) ||
         ! is_string( $_POST['_pvdak_member_review_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_member_review_nonce'] ) ),
             'pvdak_core_member_review_' . $id ) ) { return; }
    $new = isset( $_POST['pvdak_member_state'] ) && is_string( $_POST['pvdak_member_state'] ) ?
        sanitize_key( wp_unslash( $_POST['pvdak_member_state'] ) ) : '';
    $expected = isset( $_POST['pvdak_member_previous_state'] ) && is_string( $_POST['pvdak_member_previous_state'] ) ?
        sanitize_key( wp_unslash( $_POST['pvdak_member_previous_state'] ) ) : '';
    $old = get_post_meta( $id, '_pvdak_member_state', true );
    if ( ! is_string( $old ) || ! isset( pvdak_core_member_states()[ $old ] ) ||
         $expected !== $old || $new === $old ||
         ! in_array( $new, pvdak_core_member_allowed_transitions( $old ), true ) ) { return; }
    // An unpublished draft is never an approved member, even for staff.
    if ( 'approved' === $new && 'private' !== get_post_status( $id ) ) { return; }
    // Compare-and-set blocks stale editor tabs; audit failures attempt rollback.
    if ( ! update_post_meta( $id, '_pvdak_member_state', $new, $old ) ) { return; }
    if ( $new !== get_post_meta( $id, '_pvdak_member_state', true ) ) {
        update_post_meta( $id, '_pvdak_member_state', $old, $new ); // Best-effort rollback.
        return;
    }
    if ( ! add_post_meta( $id, '_pvdak_member_audit', array(
        'from' => $old, 'to' => $new, 'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
    ), false ) ) {
        update_post_meta( $id, '_pvdak_member_state', $old, $new );
        return;
    }
    pvdak_core_notify_owner( $id, 'pvdak_professional' );
}
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_member_review' );

/** The editorial review queue should show type and state at a glance. */
function pvdak_core_member_admin_columns( $cols ) {
    $cols['pvdak_member_type'] = 'نوع عضویت';
    $cols['pvdak_member_state'] = 'وضعیت بررسی';
    $cols['pvdak_member_city'] = 'شهر';
    return $cols;
}
add_filter( 'manage_pvdak_professional_posts_columns', 'pvdak_core_member_admin_columns' );
function pvdak_core_member_admin_column( $column, $id ) {
    if ( ! current_user_can( 'edit_post', $id ) ) { return; }
    if ( 'pvdak_member_type' === $column ) {
        $types = pvdak_core_member_types();
        $type = get_post_meta( $id, '_pvdak_member_type', true );
        echo esc_html( is_string( $type ) && isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' );
    } elseif ( 'pvdak_member_state' === $column ) {
        $states = pvdak_core_member_states();
        $state = get_post_meta( $id, '_pvdak_member_state', true );
        echo esc_html( is_string( $state ) && isset( $states[ $state ] ) ? $states[ $state ] : 'نیازمند بررسی' );
    } elseif ( 'pvdak_member_city' === $column ) {
        $city = get_post_meta( $id, '_pvdak_member_city', true );
        echo esc_html( is_string( $city ) ? $city : 'نامشخص' );
    }
}
add_action( 'manage_pvdak_professional_posts_custom_column', 'pvdak_core_member_admin_column', 10, 2 );


/**
 * D27: explicit owner consent for a minimal public directory. This is NOT
 * a public post type or a license certificate. Every read rechecks current
 * administrative eligibility; changing profile fields invalidates consent.
 */
function pvdak_core_directory_enabled() {
    return !( defined( 'PVDAK_CORE_DISABLE_PUBLIC_DIRECTORY' ) && PVDAK_CORE_DISABLE_PUBLIC_DIRECTORY ) &&
        '1' === (string) get_option( 'pvdak_core_directory_enabled', '0' );
}

function pvdak_core_directory_consent_fingerprint( $profile_id ) {
    $id = absint( $profile_id );
    if ( ! $id || 'pvdak_professional' !== get_post_type( $id ) ||
         'private' !== get_post_status( $id ) || ! pvdak_core_profile_has_owner( $id ) ||
         'lawyer' !== get_post_meta( $id, '_pvdak_member_type', true ) ||
         'approved' !== get_post_meta( $id, '_pvdak_member_state', true ) ||
         '1' !== (string) get_post_meta( $id, '_pvdak_referral_cleared', true ) ||
         ! pvdak_core_license_review_current( $id ) ) { return ''; }
    $owner = absint( get_post_field( 'post_author', $id ) );
    if ( '1' === (string) get_user_meta( $owner, '_pvdak_email_verification_pending', true ) ) { return ''; }
    $name = trim( (string) get_post_field( 'post_title', $id ) );
    $city = trim( (string) get_post_meta( $id, '_pvdak_member_city', true ) );
    $specialties = pvdak_core_specialty_ids( $id );
    if ( '' === $name || '' === $city || ! $specialties ) { return ''; }
    sort( $specialties, SORT_NUMERIC );
    return hash( 'sha256', wp_json_encode( array(
        pvdak_core_license_review_identity_signature( $id ),
        (string) get_post_field( 'post_modified_gmt', $id ),
        $specialties,
    ) ) );
}

function pvdak_core_directory_consent_valid( $profile_id ) {
    $fingerprint = pvdak_core_directory_consent_fingerprint( $profile_id );
    $consent = get_post_meta( absint( $profile_id ), '_pvdak_directory_consent', true );
    return '' !== $fingerprint && is_array( $consent ) &&
        isset( $consent['fingerprint'], $consent['at'] ) &&
        is_string( $consent['fingerprint'] ) && is_string( $consent['at'] ) &&
        '' !== trim( $consent['at'] ) &&
        hash_equals( $fingerprint, $consent['fingerprint'] );
}

function pvdak_core_directory_eligible( $profile_id ) {
    return pvdak_core_directory_enabled() && pvdak_core_directory_consent_valid( $profile_id );
}

/** The applicant may revoke consent even if clearance has since expired. */
function pvdak_core_directory_consent_panel( $profile_id ) {
    $id = absint( $profile_id );
    if ( ! is_user_logged_in() || ! $id ||
         (int) get_post_field( 'post_author', $id ) !== get_current_user_id() ||
         'pvdak_professional' !== get_post_type( $id ) ||
         ! in_array( get_post_status( $id ), array( 'private', 'draft' ), true ) ) { return ''; }
    $valid = pvdak_core_directory_consent_valid( $id );
    $possible = '' !== pvdak_core_directory_consent_fingerprint( $id );
    $saved = get_post_meta( $id, '_pvdak_directory_consent', true );
    $html = '<section class="pvdak-core-prof-section" aria-labelledby="pvdak-directory-consent"><h3 id="pvdak-directory-consent">رضایت نمایش در فهرست عمومی</h3>';
    $html .= '<p>فقط نام نمایشی درخواست، شهر فعالیت و عنوان حوزه‌های حقوقی منتشرشده نمایش داده می‌شوند؛ هیچ تلفن، ایمیل، مدارک، شناسه پروفایل، شرح درخواست یا اطلاعات پرونده منتشر نمی‌شود. این فهرست گواهی رسمی پروانه نیست.</p>';
    if ( $valid ) {
        $html .= '<p role="status">' . ( pvdak_core_directory_enabled() ?
            'رضایت معتبر است و پروفایل امکان نمایش عمومی دارد.' :
            'رضایت ثبت شده، اما انتشار فهرست هنوز توسط مدیر غیرفعال است.' ) . '</p>';
    } elseif ( $saved ) {
        $html .= '<p role="status">رضایت قبلی دیگر معتبر نیست؛ برای نمایش دوباره باید شرایط و اطلاعات فعلی تأیید و رضایت جدید ثبت شود.</p>';
    } else {
        $html .= '<p>هیچ رضایتی برای نمایش عمومی ثبت نشده است.</p>';
    }
    $html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    $html .= wp_nonce_field( 'pvdak_directory_consent_' . $id, '_pvdak_directory_nonce', true, false );
    $html .= '<input type="hidden" name="action" value="pvdak_core_directory_consent">';
    $html .= '<input type="hidden" name="pvdak_profile_id" value="' . esc_attr( $id ) . '">';
    if ( $possible && ! $valid ) {
        $html .= '<button type="submit" name="pvdak_directory_choice" value="yes">با نمایش این سه مورد در فهرست عمومی موافقم</button> ';
    } elseif ( ! $possible ) {
        $html .= '<p>تا تأیید اداری، بررسی معتبر صلاحیت و ثبت تخصص، امکان ثبت رضایت انتشار وجود ندارد.</p>';
    }
    if ( $saved ) {
        $html .= '<button type="submit" name="pvdak_directory_choice" value="no">لغو رضایت نمایش عمومی</button>';
    }
    return $html . '</form></section>';
}

function pvdak_core_directory_consent_submit() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! is_user_logged_in() ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    $id = isset( $_POST['pvdak_profile_id'] ) && is_scalar( $_POST['pvdak_profile_id'] ) ?
        absint( wp_unslash( $_POST['pvdak_profile_id'] ) ) : 0;
    if ( ! $id || $id !== pvdak_core_existing_application( get_current_user_id() ) ||
         (int) get_post_field( 'post_author', $id ) !== get_current_user_id() ||
         'pvdak_professional' !== get_post_type( $id ) ) {
        wp_die( 'مالکیت پروفایل تأیید نشد.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_directory_consent_' . $id, '_pvdak_directory_nonce' );
    $choice = isset( $_POST['pvdak_directory_choice'] ) && is_string( $_POST['pvdak_directory_choice'] ) ?
        sanitize_key( wp_unslash( $_POST['pvdak_directory_choice'] ) ) : '';
    if ( 'no' === $choice ) {
        delete_post_meta( $id, '_pvdak_directory_consent' );
    } elseif ( 'yes' === $choice ) {
        $fingerprint = pvdak_core_directory_consent_fingerprint( $id );
        if ( '' === $fingerprint ) {
            wp_die( 'شرایط انتشار معتبر نیست.', '', array( 'response' => 403 ) );
        }
        update_post_meta( $id, '_pvdak_directory_consent', array(
            'fingerprint' => $fingerprint, 'at' => current_time( 'mysql', true ),
        ) );
    } else {
        wp_die( 'انتخاب نامعتبر است.', '', array( 'response' => 400 ) );
    }
    wp_safe_redirect( wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) ) );
    exit;
}
add_action( 'admin_post_pvdak_core_directory_consent', 'pvdak_core_directory_consent_submit' );

/**
 * Public directory exposes only three consented fields. Private CPT status,
 * notes, member ID, contact details and profile permalinks are never output.
 * No automatic Page creation or public REST route is introduced.
 */
function pvdak_core_professional_directory() {
    if ( ! pvdak_core_directory_enabled() ) {
        return '<p class="pvdak-core-notice">فهرست متخصصان در حال آماده‌سازی است.</p>';
    }
    $raw_page = isset( $_GET['pvdak_dir_page'] ) && is_scalar( $_GET['pvdak_dir_page'] ) ?
        absint( wp_unslash( $_GET['pvdak_dir_page'] ) ) : 1;
    $page = max( 1, min( 50, $raw_page ) );
    $ids = get_posts( array(
        'post_type' => 'pvdak_professional',
        'post_status' => 'private',
        'fields' => 'ids',
        'posts_per_page' => 1000,
        'orderby' => 'ID',
        'order' => 'DESC',
        'no_found_rows' => true,
        'cache_results' => false,
        'update_post_meta_cache' => false,
        'meta_query' => array( array( 'key' => '_pvdak_directory_consent', 'compare' => 'EXISTS' ) ),
    ) );
    $visible = array();
    foreach ( $ids as $id ) {
        if ( pvdak_core_directory_eligible( absint( $id ) ) ) { $visible[] = absint( $id ); }
    }
    $per_page = 12;
    $total = count( $visible );
    $html = '<section class="pvdak-core-panel pvdak-core-directory"><h2>فهرست اعضای حرفه‌ای دارای رضایت انتشار</h2>';
    $html .= '<p>نمایش در این فهرست به معنی تضمین خدمات یا استعلام برخط پروانه نیست. هیچ اطلاعات تماس یا پرونده‌ای در این بخش منتشر نمی‌شود.</p>';
    $page_ids = array_slice( $visible, ( $page - 1 ) * $per_page, $per_page );
    if ( ! $page_ids ) { return $html . '<p>موردی برای نمایش وجود ندارد.</p></section>'; }
    $html .= '<ul class="pvdak-core-directory-list">';
    foreach ( $page_ids as $id ) {
        // Revalidate immediately before rendering, including consent revocation.
        if ( ! pvdak_core_directory_eligible( $id ) ) { continue; }
        $name = trim( (string) get_post_field( 'post_title', $id ) );
        $city = trim( (string) get_post_meta( $id, '_pvdak_member_city', true ) );
        $topics = array();
        foreach ( pvdak_core_specialty_ids( $id ) as $topic_id ) {
            $topic = pvdak_core_topic_resolution( $topic_id );
            if ( 'ready' === $topic['status'] ) { $topics[] = $topic['title']; }
        }
        if ( ! $topics || '' === $name || '' === $city ) { continue; }
        $html .= '<li><strong>' . esc_html( $name ) . '</strong><span> — ' .
            esc_html( $city ) . '</span><p>' . esc_html( implode( '، ', $topics ) ) . '</p></li>';
    }
    $html .= '</ul>';
    $base = get_permalink( get_queried_object_id() );
    if ( $page > 1 ) {
        $html .= '<a href="' . esc_url( add_query_arg( 'pvdak_dir_page', $page - 1, $base ) ) . '">صفحه قبل</a> ';
    }
    if ( $page * $per_page < $total ) {
        $html .= '<a href="' . esc_url( add_query_arg( 'pvdak_dir_page', $page + 1, $base ) ) . '">صفحه بعد</a>';
    }
    return $html . '</section>';
}
add_shortcode( 'pvdak_professional_directory', 'pvdak_core_professional_directory' );
