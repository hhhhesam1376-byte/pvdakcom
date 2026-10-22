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
    $receipt_key = 'pvdak_receipt_' . get_current_user_id();
    if ( get_transient( $receipt_key ) ) {
        delete_transient( $receipt_key );
        $html .= '<p class="pvdak-core-success" role="status">درخواست شما ثبت شد و در صف بررسی قرار گرفت.</p>';
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
    $html .= '<label class="pvdak-core-consent"><input type="checkbox" name="pvdak_consent" value="1" required> موافقم اطلاعات واردشده برای بررسی این درخواست در سامانه ثبت و توسط مدیران مجاز بررسی شود.</label>';
    $html .= '<button type="submit">ثبت درخواست برای بررسی</button></form></section>';
    return $html;
}
add_shortcode( 'pvdak_request_form', 'pvdak_core_request_form' );

function pvdak_core_handle_request() {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! pvdak_core_intake_open() ) {
        wp_die( 'ثبت درخواست در دسترس نیست.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_submit_request', '_pvdak_nonce' );
    $type = isset( $_POST['pvdak_type'] ) && is_string( $_POST['pvdak_type'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_type'] ) ) : '';
    $types = pvdak_core_request_types();
    $title = isset( $_POST['pvdak_title'] ) && is_string( $_POST['pvdak_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_title'] ) ) : '';
    $description = isset( $_POST['pvdak_description'] ) && is_string( $_POST['pvdak_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pvdak_description'] ) ) : '';
    $source = wp_get_referer();
    $destination = $source ? wp_validate_redirect( $source, home_url( '/' ) ) : home_url( '/' );
    $destination = remove_query_arg( array( 'pvdak_sent', 'pvdak_error' ), $destination );
    $consent = isset( $_POST['pvdak_consent'] ) && is_string( $_POST['pvdak_consent'] ) && '1' === wp_unslash( $_POST['pvdak_consent'] );
    if ( ! $consent || ! isset( $types[ $type ] ) || '' === trim( $title ) || '' === trim( $description ) || strlen( $title ) > 600 || strlen( $description ) > 12000 ) {
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
    set_transient( 'pvdak_receipt_' . $user_id, 1, 300 );
    wp_safe_redirect( $destination );
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

/**
 * Triage-only workflow for the first release.
 * Do not allow "matched" until a verified professional-assignment module exists.
 */
function pvdak_core_allowed_request_transitions( $from ) {
    $transitions = array(
        'submitted' => array( 'review', 'closed' ),
        'review'    => array( 'answered', 'closed' ),
        'matched'   => array( 'answered', 'closed' ), // Legacy records only.
        'answered'  => array( 'closed' ),
        'closed'    => array(),
    );
    return isset( $transitions[ $from ] ) ? $transitions[ $from ] : array();
}

function pvdak_core_request_metabox() {
    add_meta_box( 'pvdak_request_state', 'رسیدگی به درخواست', 'pvdak_core_request_metabox_html', 'pvdak_request', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'pvdak_core_request_metabox' );

function pvdak_core_request_metabox_html( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_core_state_' . $post->ID, '_pvdak_state_nonce' );
    $type = get_post_meta( $post->ID, '_pvdak_request_type', true );
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    $current = get_post_meta( $post->ID, '_pvdak_request_state', true );
    if ( ! isset( $statuses[ $current ] ) ) { $current = 'submitted'; }
    echo '<p><strong>نوع:</strong> ' . esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    echo '<p><strong>وضعیت کنونی:</strong> ' . esc_html( $statuses[ $current ] ) . '</p>';
    echo '<label for="pvdak_state">تغییر وضعیت رسیدگی</label><select name="pvdak_state" id="pvdak_state" class="widefat">';
    echo '<option value="' . esc_attr( $current ) . '">بدون تغییر (' . esc_html( $statuses[ $current ] ) . ')</option>';
    foreach ( pvdak_core_allowed_request_transitions( $current ) as $next ) {
        echo '<option value="' . esc_attr( $next ) . '">' . esc_html( $statuses[ $next ] ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">«ارجاع به وکیل» تا زمان احراز متخصص و راه‌اندازی موتور ارجاع در دسترس نیست.</p>';
    echo '<label for="pvdak_internal_note"><strong>یادداشت داخلی مدیر</strong></label>';
    echo '<textarea id="pvdak_internal_note" name="pvdak_internal_note" class="widefat" maxlength="1000" rows="4" placeholder="فقط برای مدیران مجاز"></textarea>';
    echo '<p class="description">این یادداشت در پنل مراجعه‌کننده نمایش داده نمی‌شود.</p>';
    $notes = get_post_meta( $post->ID, '_pvdak_internal_notes', false );
    if ( $notes ) {
        echo '<div style="max-height:220px;overflow:auto"><strong>آخرین یادداشت‌ها</strong><ul>';
        foreach ( array_reverse( array_slice( $notes, -5 ) ) as $note ) {
            if ( ! is_array( $note ) ) { continue; }
            echo '<li style="border-bottom:1px solid #ddd;padding:8px 0"><small>' .
                esc_html( isset( $note['at'] ) ? $note['at'] : '' ) . '</small><p>' .
                esc_html( isset( $note['text'] ) ? $note['text'] : '' ) . '</p></li>';
        }
        echo '</ul></div>';
    }
    $audit = get_post_meta( $post->ID, '_pvdak_state_audit', false );
    if ( $audit ) {
        echo '<details><summary>تاریخچه تغییر وضعیت</summary><ol>';
        foreach ( array_reverse( array_slice( $audit, -10 ) ) as $event ) {
            if ( ! is_array( $event ) ) { continue; }
            $from = isset( $event['from'] ) ? $event['from'] : '';
            $to = isset( $event['to'] ) ? $event['to'] : '';
            echo '<li>' . esc_html( isset( $statuses[ $from ] ) ? $statuses[ $from ] : $from ) .
                ' ← ' . esc_html( isset( $statuses[ $to ] ) ? $statuses[ $to ] : $to ) .
                ' <small>' . esc_html( isset( $event['at'] ) ? $event['at'] : '' ) . '</small></li>';
        }
        echo '</ol></details>';
    }
}

function pvdak_core_save_request_state( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_state_nonce'] ) || ! is_string( $_POST['_pvdak_state_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_state_nonce'] ) ), 'pvdak_core_state_' . $post_id ) ) { return; }

    $statuses = pvdak_core_request_statuses();
    $previous = get_post_meta( $post_id, '_pvdak_request_state', true );
    if ( ! isset( $statuses[ $previous ] ) ) { $previous = 'submitted'; }
    $next = isset( $_POST['pvdak_state'] ) && is_string( $_POST['pvdak_state'] ) ?
        sanitize_key( wp_unslash( $_POST['pvdak_state'] ) ) : $previous;

    if ( $next !== $previous && in_array( $next, pvdak_core_allowed_request_transitions( $previous ), true ) ) {
        update_post_meta( $post_id, '_pvdak_request_state', $next );
        add_post_meta( $post_id, '_pvdak_state_audit', array(
            'from' => $previous,
            'to' => $next,
            'at' => current_time( 'mysql', true ),
            'by' => get_current_user_id(),
        ), false );
    }
    if ( isset( $_POST['pvdak_internal_note'] ) && is_string( $_POST['pvdak_internal_note'] ) ) {
        $note = sanitize_textarea_field( wp_unslash( $_POST['pvdak_internal_note'] ) );
        if ( '' !== trim( $note ) && strlen( $note ) <= 4000 ) {
            add_post_meta( $post_id, '_pvdak_internal_notes', array(
                'text' => $note,
                'at' => current_time( 'mysql', true ),
                'by' => get_current_user_id(),
            ), false );
        }
    }
}
add_action( 'save_post_pvdak_request', 'pvdak_core_save_request_state', 10, 2 );
