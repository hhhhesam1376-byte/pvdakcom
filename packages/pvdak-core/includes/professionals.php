<?php
/** Private onboarding applications for legal-community members. No public directory. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
    $linked = absint( get_user_meta( $user_id, '_pvdak_core_application', true ) );
    if ( $linked && 'pvdak_professional' === get_post_type( $linked ) && (int) get_post_field( 'post_author', $linked ) === (int) $user_id ) {
        return $linked;
    }
    $matches = get_posts( array(
        'post_type' => 'pvdak_professional',
        'post_status' => array( 'private', 'draft' ),
        'author' => (int) $user_id,
        'posts_per_page' => 1,
        'fields' => 'ids',
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
    if ( ! pvdak_core_intake_open() ) {
        return '<p class="pvdak-core-notice">ثبت درخواست همکاری در حال آماده‌سازی است.</p>';
    }
    $existing_id = pvdak_core_existing_application( get_current_user_id() );
    if ( $existing_id ) {
        $current = get_post_meta( $existing_id, '_pvdak_member_state', true );
        $states = pvdak_core_member_states();
        return '<p class="pvdak-core-notice">درخواست عضویت شما: ' . esc_html( isset( $states[ $current ] ) ? $states[ $current ] : $states['pending'] ) . '. برای اصلاح مشخصات با پشتیبانی تماس بگیرید.</p>';
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

function pvdak_core_submit_application() {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! pvdak_core_intake_open() ) {
        wp_die( 'امکان ثبت درخواست وجود ندارد.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_member_apply', '_pvdak_member_nonce' );
    $types = pvdak_core_member_types();
    $type = isset( $_POST['pvdak_member_type'] ) && is_string( $_POST['pvdak_member_type'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_member_type'] ) ) : '';
    $name = isset( $_POST['pvdak_member_name'] ) && is_string( $_POST['pvdak_member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_member_name'] ) ) : '';
    $city = isset( $_POST['pvdak_member_city'] ) && is_string( $_POST['pvdak_member_city'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_member_city'] ) ) : '';
    $about = isset( $_POST['pvdak_member_about'] ) && is_string( $_POST['pvdak_member_about'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pvdak_member_about'] ) ) : '';
    $consent = isset( $_POST['pvdak_consent'] ) && '1' === (string) wp_unslash( $_POST['pvdak_consent'] );
    if ( ! $consent || ! isset( $types[ $type ] ) || ! $name || ! $city || ! $about || strlen( $name ) > 450 || strlen( $city ) > 360 || strlen( $about ) > 6400 ) {
        wp_safe_redirect( add_query_arg( 'pvdak_application', 'error', wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) ) ) );
        exit;
    }
    $user_id = get_current_user_id();
    if ( pvdak_core_existing_application( $user_id ) ) {
        wp_safe_redirect( wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) ) );
        exit;
    }
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
    if ( is_wp_error( $id ) || ! $id ) {
        wp_safe_redirect( add_query_arg( 'pvdak_application', 'error', wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) ) ) );
        exit;
    }
    update_user_meta( $user_id, '_pvdak_core_application', (int) $id );
    wp_safe_redirect( wp_validate_redirect( wp_get_referer() ?: home_url( '/' ), home_url( '/' ) ) );
    exit;
}
add_action( 'admin_post_pvdak_core_member_apply', 'pvdak_core_submit_application' );

function pvdak_core_member_meta_box() {
    add_meta_box( 'pvdak_member_review', 'وضعیت بررسی عضویت', 'pvdak_core_member_meta_box_html', 'pvdak_professional', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'pvdak_core_member_meta_box' );

function pvdak_core_member_meta_box_html( $post ) {
    wp_nonce_field( 'pvdak_core_member_review_' . $post->ID, '_pvdak_member_review_nonce' );
    $type = get_post_meta( $post->ID, '_pvdak_member_type', true );
    $types = pvdak_core_member_types();
    echo '<p><strong>نوع درخواست:</strong> ' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    echo '<p><strong>شهر:</strong> ' . esc_html( get_post_meta( $post->ID, '_pvdak_member_city', true ) ) . '</p>';
    echo '<p>تغییر وضعیت صرفاً اداری است؛ تأیید رسمی مدارک و انتشار پروفایل در این نسخه انجام نمی‌شود.</p>';
    $state = get_post_meta( $post->ID, '_pvdak_member_state', true );
    echo '<label for="pvdak_member_state">وضعیت</label><select id="pvdak_member_state" name="pvdak_member_state" class="widefat">';
    foreach ( pvdak_core_member_states() as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $state, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function pvdak_core_save_member_review( $id ) {
    if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_member_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_member_review_nonce'] ) ), 'pvdak_core_member_review_' . $id ) ) { return; }
    $new = isset( $_POST['pvdak_member_state'] ) && is_string( $_POST['pvdak_member_state'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_member_state'] ) ) : '';
    if ( ! array_key_exists( $new, pvdak_core_member_states() ) ) { return; }
    $old = get_post_meta( $id, '_pvdak_member_state', true );
    if ( $old === $new ) { return; }
    update_post_meta( $id, '_pvdak_member_state', $new );
    add_post_meta( $id, '_pvdak_member_audit', array( 'from' => $old, 'to' => $new, 'by' => get_current_user_id(), 'at' => current_time( 'mysql', true ) ), false );
}
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_member_review' );
