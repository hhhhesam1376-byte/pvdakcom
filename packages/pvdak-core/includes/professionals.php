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
    $linked = absint( get_user_meta( $user_id, '_pvdak_core_application', true ) );
    if ( $linked && 'pvdak_professional' === get_post_type( $linked ) &&
         in_array( get_post_status( $linked ), array( 'private', 'draft' ), true ) &&
         (int) get_post_field( 'post_author', $linked ) === $user_id ) {
        return $linked;
    }
    $matches = get_posts( array(
        'post_type' => 'pvdak_professional',
        'post_status' => array( 'private', 'draft' ),
        'author' => (int) $user_id,
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'cache_results' => false,
    ) );
    if ( $matches ) {
        update_user_meta( $user_id, '_pvdak_core_application', (int) $matches[0] );
        return (int) $matches[0];
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
            esc_html( isset( $states[ $current ] ) ? $states[ $current ] : $states['pending'] ) . '. برای اصلاح مشخصات با پشتیبانی تماس بگیرید.</p>';
        $dashboard = pvdak_core_published_page_link( 'my-professional-account', 'pvdak_professional_dashboard' );
        if ( $dashboard ) { $html .= '<p><a href="' . esc_url( $dashboard ) . '">پیگیری در پنل همکاری</a></p>'; }
        return $html;
    }
    if ( ! pvdak_core_member_intake_open() ) {
        return '<p class="pvdak-core-notice">ثبت درخواست همکاری در حال آماده‌سازی است.</p>';
    }
    $html = '<section class="pvdak-core-panel"><h2>اعلام آمادگی برای همکاری</h2>';
    $html .= '<p>این فرم صرفاً درخواست بررسی عضویت است و به‌معنای احراز صلاحیت حرفه‌ای یا اعطای دسترسی وکالت نیست. از وارد کردن شماره مدارک هویتی یا ارسال اسناد حساس خودداری کنید.</p>';
    if ( isset( $_GET['pvdak_application'] ) && 'error' === sanitize_key( wp_unslash( $_GET['pvdak_application'] ) ) ) {
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
        update_user_meta( $user_id, '_pvdak_core_application', (int) $id );
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
    wp_nonce_field( 'pvdak_core_member_review_' . $post->ID, '_pvdak_member_review_nonce' );
    $type = get_post_meta( $post->ID, '_pvdak_member_type', true );
    $types = pvdak_core_member_types();
    echo '<p><strong>نوع درخواست:</strong> ' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    echo '<p><strong>شهر:</strong> ' . esc_html( get_post_meta( $post->ID, '_pvdak_member_city', true ) ) . '</p>';
    echo '<p>تغییر وضعیت صرفاً اداری است؛ تأیید رسمی مدارک و انتشار پروفایل در این نسخه انجام نمی‌شود.</p>';
    $state = get_post_meta( $post->ID, '_pvdak_member_state', true );
    $states = pvdak_core_member_states();
    if ( ! isset( $states[ $state ] ) ) {
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
    if ( ! isset( pvdak_core_member_states()[ $old ] ) || $expected !== $old || $new === $old ||
         ! in_array( $new, pvdak_core_member_allowed_transitions( $old ), true ) ) { return; }
    // Compare-and-set blocks stale editor tabs; audit failures attempt rollback.
    if ( ! update_post_meta( $id, '_pvdak_member_state', $new, $old ) ) { return; }
    if ( ! add_post_meta( $id, '_pvdak_member_audit', array(
        'from' => $old, 'to' => $new, 'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ),
    ), false ) ) {
        update_post_meta( $id, '_pvdak_member_state', $old, $new );
    }
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
        echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' );
    } elseif ( 'pvdak_member_state' === $column ) {
        $states = pvdak_core_member_states();
        $state = get_post_meta( $id, '_pvdak_member_state', true );
        echo esc_html( isset( $states[ $state ] ) ? $states[ $state ] : $states['pending'] );
    } elseif ( 'pvdak_member_city' === $column ) {
        echo esc_html( get_post_meta( $id, '_pvdak_member_city', true ) );
    }
}
add_action( 'manage_pvdak_professional_posts_custom_column', 'pvdak_core_member_admin_column', 10, 2 );
