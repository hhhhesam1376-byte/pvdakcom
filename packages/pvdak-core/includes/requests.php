<?php
/** Private, authenticated PVDak request intake. Off by default. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_request_types() {
    return array(
        'lawyer' => 'به وکیل نیاز دارم',
        'consultation' => 'به مشاوره نیاز دارم',
        'question' => 'پرسش حقوقی دارم',
    );
}

function pvdak_core_request_statuses() {
    return array(
        'submitted' => 'ثبت‌شده',
        'review'    => 'در حال بررسی',
        'matched'   => 'ارجاع‌شده',
        'answered'  => 'پاسخ داده‌شده',
        'closed'    => 'بسته‌شده',
    );
}

/** Kill switch: even when the plugin is active, intake requires explicit opt-in. */
function pvdak_core_intake_open() {
    if ( defined( 'PVDAK_CORE_DISABLE_INTAKE' ) && PVDAK_CORE_DISABLE_INTAKE ) {
        return false;
    }
    return '1' === (string) get_option( 'pvdak_core_intake_enabled', '0' );
}

function pvdak_core_request_form( $atts ) {
    $atts = shortcode_atts( array( 'type' => 'lawyer' ), $atts, 'pvdak_request_form' );
    $type = sanitize_key( $atts['type'] );
    $types = pvdak_core_request_types();
    if ( ! isset( $types[ $type ] ) ) {
        return '';
    }
    if ( ! pvdak_core_intake_open() ) {
        return current_user_can( 'manage_options' )
            ? '<p class="pvdak-core-notice">پذیرش درخواست غیرفعال است. پیش از راه‌اندازی عمومی، تست دسترسی و حفظ حریم خصوصی انجام شود.</p>'
            : '<p class="pvdak-core-notice">امکان ثبت این نوع درخواست هنوز فعال نشده است.</p>';
    }
    if ( ! is_user_logged_in() ) {
        return '<p class="pvdak-core-notice">برای ثبت و پیگیری درخواست باید وارد حساب خود شوید. <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">ورود به حساب</a></p>';
    }
    $html = '<section class="pvdak-core-panel"><h2>' . esc_html( $types[ $type ] ) . '</h2>';
    if ( isset( $_GET['pvdak_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['pvdak_sent'] ) ) ) {
        $html .= '<p class="pvdak-core-success" role="status">درخواست ثبت شد و در صف بررسی قرار گرفت.</p>';
    }
    if ( isset( $_GET['pvdak_error'] ) ) {
        $html .= '<p class="pvdak-core-error" role="alert">اطلاعات ارسالی قابل ثبت نبود. فیلدها را بررسی کنید یا کمی بعد دوباره تلاش کنید.</p>';
    }
    $html .= '<p>توضیحات لازم را کوتاه و بدون اطلاعات محرمانه اشخاص ثالث ثبت کنید. فعلاً امکان پیوست مدارک وجود ندارد.</p>';
    $html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pvdak-core-form">';
    $html .= wp_nonce_field( 'pvdak_core_submit_request', '_pvdak_nonce', true, false );
    $html .= '<input type="hidden" name="action" value="pvdak_core_submit_request">';
    $html .= '<input type="hidden" name="pvdak_type" value="' . esc_attr( $type ) . '">';
    $html .= '<label>عنوان کوتاه درخواست <input type="text" name="pvdak_title" required maxlength="140" autocomplete="off"></label>';
    $html .= '<label>شرح مختصر <textarea name="pvdak_description" maxlength="3000" rows="6" required></textarea></label>';
    $html .= '<button type="submit">ثبت درخواست برای بررسی</button></form></section>';
    return $html;
}
add_shortcode( 'pvdak_request_form', 'pvdak_core_request_form' );

function pvdak_core_handle_request() {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! pvdak_core_intake_open() ) {
        wp_die( 'ثبت درخواست در دسترس نیست.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_submit_request', '_pvdak_nonce' );
    $type = isset( $_POST['pvdak_type'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_type'] ) ) : '';
    $types = pvdak_core_request_types();
    $title = isset( $_POST['pvdak_title'] ) && is_string( $_POST['pvdak_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_title'] ) ) : '';
    $description = isset( $_POST['pvdak_description'] ) && is_string( $_POST['pvdak_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pvdak_description'] ) ) : '';
    $source = wp_get_referer();
    $destination = $source ? wp_validate_redirect( $source, home_url( '/' ) ) : home_url( '/' );
    $destination = remove_query_arg( array( 'pvdak_sent', 'pvdak_error' ), $destination );
    if ( ! isset( $types[ $type ] ) || '' === trim( $title ) || '' === trim( $description ) || strlen( $title ) > 600 || strlen( $description ) > 12000 ) {
        wp_safe_redirect( add_query_arg( 'pvdak_error', 'invalid', $destination ) );
        exit;
    }
    $user_id = get_current_user_id();
    $rate_key = 'pvdak_intake_' . $user_id;
    if ( get_transient( $rate_key ) ) {
        wp_safe_redirect( add_query_arg( 'pvdak_error', 'limit', $destination ) );
        exit;
    }
    set_transient( $rate_key, 1, 90 );
    $id = wp_insert_post( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'post_author' => $user_id,
        'post_title' => $title,
        'post_content' => $description,
        'meta_input' => array(
            '_pvdak_request_type' => $type,
            '_pvdak_request_state' => 'submitted',
        ),
    ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        delete_transient( $rate_key );
        wp_safe_redirect( add_query_arg( 'pvdak_error', 'save', $destination ) );
        exit;
    }
    wp_safe_redirect( add_query_arg( 'pvdak_sent', '1', $destination ) );
    exit;
}
add_action( 'admin_post_pvdak_core_submit_request', 'pvdak_core_handle_request' );

function pvdak_core_my_requests() {
    if ( ! is_user_logged_in() ) {
        return '<p>برای دیدن درخواست‌های خود <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">وارد شوید</a>.</p>';
    }
    $items = get_posts( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'author' => get_current_user_id(),
        'posts_per_page' => 30,
        'orderby' => 'date',
        'order' => 'DESC',
    ) );
    if ( ! $items ) {
        return '<p>هنوز درخواستی ثبت نکرده‌اید.</p>';
    }
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    $html = '<div class="pvdak-core-panel"><h2>درخواست‌های من</h2><ul class="pvdak-core-requests">';
    foreach ( $items as $item ) {
        if ( (int) $item->post_author !== get_current_user_id() ) { continue; }
        $type = get_post_meta( $item->ID, '_pvdak_request_type', true );
        $state = get_post_meta( $item->ID, '_pvdak_request_state', true );
        $html .= '<li><strong>' . esc_html( get_the_title( $item->ID ) ) . '</strong><span>' .
            esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'درخواست حقوقی' ) . ' · ' .
            esc_html( isset( $statuses[ $state ] ) ? $statuses[ $state ] : 'ثبت‌شده' ) . '</span></li>';
    }
    return $html . '</ul></div>';
}
add_shortcode( 'pvdak_my_requests', 'pvdak_core_my_requests' );

/** Only privileged managers can change a request's lifecycle state. */
function pvdak_core_request_metabox() {
    add_meta_box( 'pvdak_request_state', 'وضعیت رسیدگی', 'pvdak_core_request_metabox_html', 'pvdak_request', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'pvdak_core_request_metabox' );

function pvdak_core_request_metabox_html( $post ) {
    wp_nonce_field( 'pvdak_core_state_' . $post->ID, '_pvdak_state_nonce' );
    $type = get_post_meta( $post->ID, '_pvdak_request_type', true );
    $types = pvdak_core_request_types();
    echo '<p><strong>نوع:</strong> ' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    $current = get_post_meta( $post->ID, '_pvdak_request_state', true );
    echo '<label for="pvdak_state">وضعیت درخواست</label><select name="pvdak_state" id="pvdak_state" class="widefat">';
    foreach ( pvdak_core_request_statuses() as $status => $label ) {
        echo '<option value="' . esc_attr( $status ) . '" ' . selected( $current, $status, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function pvdak_core_save_request_state( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_state_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_state_nonce'] ) ), 'pvdak_core_state_' . $post_id ) ) { return; }
    if ( empty( $_POST['pvdak_state'] ) || ! is_string( $_POST['pvdak_state'] ) ) { return; }
    $next = sanitize_key( wp_unslash( $_POST['pvdak_state'] ) );
    if ( ! array_key_exists( $next, pvdak_core_request_statuses() ) ) { return; }
    $previous = get_post_meta( $post_id, '_pvdak_request_state', true );
    if ( $previous === $next ) { return; }
    update_post_meta( $post_id, '_pvdak_request_state', $next );
    add_post_meta( $post_id, '_pvdak_state_audit', array(
        'from' => $previous,
        'to' => $next,
        'at' => current_time( 'mysql', true ),
        'by' => get_current_user_id(),
    ), false );
}
add_action( 'save_post_pvdak_request', 'pvdak_core_save_request_state', 10, 2 );
