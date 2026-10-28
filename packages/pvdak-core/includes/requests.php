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
        'matched'   => 'انتساب داخلی ثبت‌شده',
        'answered'  => 'پاسخ داده‌شده',
        'closed'    => 'بسته‌شده',
    );
}

/** Reject malformed request IDs instead of mapping them to another record. */
function pvdak_core_request_positive_id( $value ) {
    if ( is_int( $value ) ) { return $value > 0 ? $value : 0; }
    if ( ! is_string( $value ) || '' === $value ||
         strlen( $value ) > strlen( (string) PHP_INT_MAX ) ||
         ! preg_match( '/^[1-9][0-9]*$/D', $value ) ||
         ( strlen( $value ) === strlen( (string) PHP_INT_MAX ) &&
           strcmp( $value, (string) PHP_INT_MAX ) > 0 ) ) { return 0; }
    return (int) $value;
}

/** Kill switch: even when the plugin is active, intake requires explicit opt-in. */
function pvdak_core_intake_open() {
    if ( defined( 'PVDAK_CORE_DISABLE_INTAKE' ) && PVDAK_CORE_DISABLE_INTAKE ) {
        return false;
    }
    return in_array( get_option( 'pvdak_core_intake_enabled', '0' ), array( '1', 1, true ), true );
}

/**
 * Enforce the same Unicode character limits on the server as the form does
 * in the browser. The byte caps also bound the stored request size.
 */
function pvdak_core_request_input_valid( $title, $description ) {
    if ( ! is_string( $title ) || ! is_string( $description ) ||
        '' === trim( $title ) || '' === trim( $description ) ||
        strlen( $title ) > 600 || strlen( $description ) > 12000 ) {
        return false;
    }
    $title_length = preg_match_all( '/./us', $title );
    $description_length = preg_match_all( '/./us', $description );
    return false !== $title_length && false !== $description_length &&
        $title_length <= 140 && $description_length <= 3000;
}

/** Fixed, non-sensitive feedback for intake failures; never echo GET input. */
function pvdak_core_request_error_message( $code ) {
    $messages = array(
        'invalid' => 'اطلاعات فرم کامل یا معتبر نیست. عنوان، موضوع حقوقی، شرح و تأیید رضایت را بررسی کنید.',
        'limit' => 'برای جلوگیری از ثبت تکراری، کمی بعد دوباره تلاش کنید.',
        'closed' => 'پذیرش درخواست در حال حاضر بسته است. لطفاً بعداً دوباره بررسی کنید.',
        'save' => 'درخواست ذخیره نشد. لطفاً کمی بعد دوباره تلاش کنید.',
    );
    return is_string( $code ) && isset( $messages[ $code ] ) ?
        $messages[ $code ] : 'درخواست قابل ثبت نبود. لطفاً فرم را بررسی کنید.';
}

/** A failed submission on a page with three forms belongs only to its own form. */
function pvdak_core_request_feedback_for_type( $feedback_type, $form_type ) {
    // Null preserves older links that did not include the service type.
    return null === $feedback_type ||
        ( is_string( $feedback_type ) && ( '' === $feedback_type || $feedback_type === $form_type ) );
}

/** One-time receipt is scoped to the owner AND the submitted service type. */
function pvdak_core_request_receipt_id( $receipt, $type, $owner_id = 0 ) {
    if ( ! is_array( $receipt ) || ! isset( $receipt['type'], $receipt['id'] ) ||
         ! is_string( $receipt['type'] ) || $receipt['type'] !== $type ) { return 0; }
    $id = pvdak_core_request_positive_id( $receipt['id'] );
    if ( ! $id ) { return 0; }
    if ( 0 !== $owner_id ) {
        $owner = pvdak_core_request_positive_id( $owner_id );
        if ( ! $owner || 'pvdak_request' !== get_post_type( $id ) ||
             'private' !== get_post_status( $id ) ||
             $owner !== pvdak_core_request_positive_id( get_post_field( 'post_author', $id ) ) ||
             $type !== get_post_meta( $id, '_pvdak_request_type', true ) ) { return 0; }
    }
    return $id;
}

function pvdak_core_request_form( $atts ) {
    $atts = shortcode_atts( array( 'type' => 'lawyer' ), $atts, 'pvdak_request_form' );
    // Malformed shortcode attributes must not trigger a PHP type error.
    if ( ! is_string( $atts['type'] ) ) { return ''; }
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
    // The submit handler also requires this capability; do not show a form that must fail.
    if ( ! current_user_can( 'read' ) ) {
        return '<p class="pvdak-core-notice">این حساب مجوز ثبت درخواست ندارد.</p>';
    }
    // Do not offer blank-title, moved or unpublished topics to applicants.
    $landing_pages = array_values( array_filter( pvdak_core_specialty_pages(), function ( $page ) {
        return $page instanceof WP_Post &&
            'ready' === pvdak_core_topic_resolution( $page->ID )['status'];
    } ) );
    if ( ! $landing_pages ) {
        return '<p class="pvdak-core-notice">پذیرش این درخواست هنوز آماده نیست؛ موضوعات حقوقی باید توسط مدیر سایت تنظیم شوند.</p>';
    }
    $html = '<section class="pvdak-core-panel"><h2>' . esc_html( $types[ $type ] ) . '</h2>';
    $receipt_key = 'pvdak_receipt_' . get_current_user_id();
    $receipt_id = pvdak_core_request_receipt_id( get_transient( $receipt_key ), $type, get_current_user_id() );
    if ( $receipt_id ) {
        delete_transient( $receipt_key );
        $html .= '<p class="pvdak-core-success" role="status">درخواست شما ثبت شد و در صف بررسی قرار گرفت. شماره پیگیری: <strong>#' .
            esc_html( number_format_i18n( $receipt_id ) ) . '</strong></p>';
        $account_page = get_page_by_path( 'my-pvdak-account', OBJECT, 'page' );
        if ( $account_page && 'publish' === get_post_status( $account_page->ID ) ) {
            $html .= '<p><a href="' . esc_url( get_permalink( $account_page->ID ) . '#pvdak-requests' ) .
                '">پیگیری در حساب کاربری</a></p>';
        }
    }
    $feedback_type = array_key_exists( 'pvdak_error_type', $_GET ) ? $_GET['pvdak_error_type'] : null;
    if ( isset( $_GET['pvdak_error'] ) && pvdak_core_request_feedback_for_type( $feedback_type, $type ) ) {
        $error_code = is_string( $_GET['pvdak_error'] ) ?
            sanitize_key( wp_unslash( $_GET['pvdak_error'] ) ) : '';
        $html .= '<p class="pvdak-core-error" role="alert">' .
            esc_html( pvdak_core_request_error_message( $error_code ) ) . '</p>';
    }
    $html .= '<p>توضیحات لازم را کوتاه و بدون اطلاعات محرمانه اشخاص ثالث ثبت کنید. فعلاً امکان پیوست مدارک وجود ندارد.</p>';
    $html .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pvdak-core-form">';
    $html .= wp_nonce_field( 'pvdak_core_submit_request', '_pvdak_nonce', true, false );
    $html .= '<input type="hidden" name="action" value="pvdak_core_submit_request">';
    $html .= '<input type="hidden" name="pvdak_type" value="' . esc_attr( $type ) . '">';
    $html .= '<label>عنوان کوتاه درخواست <input type="text" name="pvdak_title" required maxlength="140" autocomplete="off"></label>';
    $html .= '<label>موضوع حقوقی <select name="pvdak_landing_id" required><option value="">انتخاب موضوع</option>';
    foreach ( $landing_pages as $page ) {
        $html .= '<option value="' . esc_attr( $page->ID ) . '">' . esc_html( $page->post_title ) . '</option>';
    }
    $html .= '</select></label>';
    $html .= '<label>شرح مختصر <textarea name="pvdak_description" maxlength="3000" rows="6" required></textarea></label>';
    $html .= '<label class="pvdak-core-consent"><input type="checkbox" name="pvdak_consent" value="1" required> موافقم اطلاعات واردشده برای بررسی این درخواست در سامانه ثبت و توسط مدیران مجاز بررسی شود.</label>';
    $html .= '<button type="submit">ثبت درخواست برای بررسی</button></form></section>';
    return $html;
}
add_shortcode( 'pvdak_request_form', 'pvdak_core_request_form' );

function pvdak_core_handle_request() {
    if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        wp_die( 'روش درخواست نامعتبر است.', '', array( 'response' => 405 ) );
    }
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) || ! pvdak_core_intake_open() ) {
        wp_die( 'ثبت درخواست در دسترس نیست.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_submit_request', '_pvdak_nonce' );
    $type = isset( $_POST['pvdak_type'] ) && is_string( $_POST['pvdak_type'] ) ? sanitize_key( wp_unslash( $_POST['pvdak_type'] ) ) : '';
    $types = pvdak_core_request_types();
    $title = isset( $_POST['pvdak_title'] ) && is_string( $_POST['pvdak_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pvdak_title'] ) ) : '';
    $description = isset( $_POST['pvdak_description'] ) && is_string( $_POST['pvdak_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pvdak_description'] ) ) : '';
    // A submitted topic ID must be a positive decimal string. absint() would
    // silently turn a forged negative ID into a different, valid topic.
    $raw_landing = isset( $_POST['pvdak_landing_id'] ) && is_string( $_POST['pvdak_landing_id'] ) ?
        wp_unslash( $_POST['pvdak_landing_id'] ) : '';
    $landing_id = strlen( $raw_landing ) <= 10 ?
        pvdak_core_request_positive_id( $raw_landing ) : 0;
    $source = wp_get_referer();
    $destination = $source ? wp_validate_redirect( $source, home_url( '/' ) ) : home_url( '/' );
    $destination = remove_query_arg( array( 'pvdak_sent', 'pvdak_error', 'pvdak_error_type' ), $destination );
    $consent = isset( $_POST['pvdak_consent'] ) && is_string( $_POST['pvdak_consent'] ) && '1' === wp_unslash( $_POST['pvdak_consent'] );
    if ( ! $consent || ! isset( $types[ $type ] ) || 'ready' !== pvdak_core_topic_resolution( $landing_id )['status'] ||
         ! pvdak_core_request_input_valid( $title, $description ) ) {
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'invalid', 'pvdak_error_type' => isset( $types[ $type ] ) ? $type : '' ), $destination ) );
        exit;
    }
    $user_id = get_current_user_id();
    $rate_key = 'pvdak_intake_' . $user_id;
    if ( get_transient( $rate_key ) ) {
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'limit', 'pvdak_error_type' => $type ), $destination ) );
        exit;
    }
    if ( ! set_transient( $rate_key, 1, 90 ) ) {
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'limit', 'pvdak_error_type' => $type ), $destination ) );
        exit;
    }
    // An administrator may close intake between form rendering and submission.
    if ( ! pvdak_core_intake_open() ) {
        delete_transient( $rate_key );
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'closed', 'pvdak_error_type' => $type ), $destination ) );
        exit;
    }
    $id = wp_insert_post( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'post_author' => $user_id,
        'post_title' => $title,
        'post_content' => $description,
        'meta_input' => array(
            '_pvdak_request_type' => $type,
            '_pvdak_legal_landing_id' => $landing_id,
            '_pvdak_request_state' => 'submitted',
        ),
    ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        delete_transient( $rate_key );
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'save', 'pvdak_error_type' => $type ), $destination ) );
        exit;
    }
    $id = absint( $id );
    if ( 'pvdak_request' !== get_post_type( $id ) ||
         'private' !== get_post_status( $id ) ||
         pvdak_core_request_positive_id( get_post_field( 'post_author', $id ) ) !== $user_id ||
         $type !== get_post_meta( $id, '_pvdak_request_type', true ) ||
         $landing_id !== pvdak_core_request_positive_id( get_post_meta( $id, '_pvdak_legal_landing_id', true ) ) ||
         'submitted' !== get_post_meta( $id, '_pvdak_request_state', true ) ) {
        wp_delete_post( $id, true ); // Newly inserted incomplete request only.
        delete_transient( $rate_key );
        wp_safe_redirect( add_query_arg( array( 'pvdak_error' => 'save', 'pvdak_error_type' => $type ), $destination ) );
        exit;
    }
    set_transient( 'pvdak_receipt_' . $user_id, array( 'type' => $type, 'id' => $id ), 300 );
    wp_safe_redirect( $destination );
    exit;
}
add_action( 'admin_post_pvdak_core_submit_request', 'pvdak_core_handle_request' );

/** Validate exact allowlisted GET choices before querying private history. */
function pvdak_core_request_history_choice_valid( $input, $normalized, $allowed ) {
    return is_string( $input ) && wp_unslash( $input ) === $normalized &&
        ( '' === $normalized || in_array( $normalized, $allowed, true ) );
}

/**
 * Private, owner-scoped request history. Never render case descriptions,
 * attachments, internal notes or unpublished legal landing titles.
 */
function pvdak_core_my_requests() {
    if ( ! is_user_logged_in() ) {
        return '<p>برای دیدن درخواست‌های خود <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">وارد شوید</a>.</p>';
    }
    $user_id = get_current_user_id();
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();

    $raw_page = isset( $_GET['pvdak_page'] ) && is_scalar( $_GET['pvdak_page'] ) ? wp_unslash( $_GET['pvdak_page'] ) : 1;
    $page = is_scalar( $raw_page ) && ctype_digit( (string) $raw_page ) &&
        strlen( (string) $raw_page ) <= 4 && (int) $raw_page > 0 ?
        min( 1000, (int) $raw_page ) : 1;
    $raw_type = isset( $_GET['request_type'] ) && is_string( $_GET['request_type'] ) ? sanitize_key( wp_unslash( $_GET['request_type'] ) ) : '';
    $type_filter = isset( $types[$raw_type] ) ? $raw_type : '';
    $raw_status = isset( $_GET['request_status'] ) && is_string( $_GET['request_status'] ) ? sanitize_key( wp_unslash( $_GET['request_status'] ) ) : '';
    $status_filter = isset( $statuses[$raw_status] ) ? $raw_status : '';
    $raw_order = isset( $_GET['request_order'] ) && is_string( $_GET['request_order'] ) ? sanitize_key( wp_unslash( $_GET['request_order'] ) ) : '';
    $order = 'asc' === $raw_order ? 'ASC' : 'DESC';
    $search_input = isset( $_GET['request_search'] ) && is_string( $_GET['request_search'] ) ?
        wp_unslash( $_GET['request_search'] ) : '';
    // Bound untrusted bytes before sanitization, not only the resulting Unicode length.
    $raw_search = strlen( $search_input ) <= 500 ?
        trim( sanitize_text_field( $search_input ) ) : '';
    $search_length = preg_match_all( '/./us', $raw_search );
    $search = false !== $search_length && $search_length <= 80 ? $raw_search : '';
    // A malformed filter must never silently broaden a private search to all
    // of the owner's records. Keep the reset route available without querying.
    $invalid_filters =
        ( array_key_exists( 'request_type', $_GET ) &&
          ! pvdak_core_request_history_choice_valid( $_GET['request_type'], $raw_type, array_keys( $types ) ) ) ||
        ( array_key_exists( 'request_status', $_GET ) &&
          ! pvdak_core_request_history_choice_valid( $_GET['request_status'], $raw_status, array_keys( $statuses ) ) ) ||
        ( array_key_exists( 'request_order', $_GET ) &&
          ! pvdak_core_request_history_choice_valid( $_GET['request_order'], $raw_order, array( 'asc', 'desc' ) ) ) ||
        ( array_key_exists( 'request_search', $_GET ) &&
          ( ! is_string( $_GET['request_search'] ) || strlen( $search_input ) > 500 ||
            false === $search_length || $search_length > 80 ||
            ( '' !== trim( $search_input ) && '' === $search ) ) ) ||
        ( array_key_exists( 'pvdak_page', $_GET ) &&
          ( ! is_scalar( $_GET['pvdak_page'] ) ||
            ! ctype_digit( (string) $raw_page ) ||
            strlen( (string) $raw_page ) > 4 ||
            (int) $raw_page < 1 || (int) $raw_page > 1000 ) );
    if ( $invalid_filters ) {
        return '<section id="pvdak-requests" class="pvdak-core-panel" aria-label="درخواست‌های من">' .
            '<h2>درخواست‌های من</h2><p class="pvdak-core-notice" role="status">' .
            'فیلتر یا شماره صفحه معتبر نیست. برای مشاهده سوابق، فیلترها را پاک کنید.</p>' .
            '<p><a href="' . esc_url( get_permalink() . '#pvdak-requests' ) .
            '">پاک‌کردن فیلترها</a></p></section>';
    }

    $args = array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'author' => $user_id,
        'posts_per_page' => 10,
        'paged' => $page,
        'orderby' => 'date',
        'order' => $order,
        'ignore_sticky_posts' => true,
        'no_found_rows' => false,
        'suppress_filters' => false,
    );
    $meta = array( 'relation' => 'AND' );
    if ( $type_filter ) { $meta[] = array( 'key' => '_pvdak_request_type', 'value' => $type_filter, 'compare' => '=' ); }
    if ( $status_filter ) { $meta[] = array( 'key' => '_pvdak_request_state', 'value' => $status_filter, 'compare' => '=' ); }
    if ( count( $meta ) > 1 ) { $args['meta_query'] = $meta; }
    if ( '' !== $search ) { $args['s'] = $search; }
    $query = new WP_Query( $args );
    $items = array();
    $unexpected_rows = ! is_array( $query->posts );
    foreach ( is_array( $query->posts ) ? $query->posts : array() as $item ) {
        // Defense in depth against third-party query filters or stale records.
        if ( $item instanceof WP_Post && 'pvdak_request' === $item->post_type && 'private' === $item->post_status &&
            pvdak_core_request_positive_id( $item->ID ) > 0 &&
            pvdak_core_request_positive_id( $item->post_author ) === $user_id &&
            ( ! $type_filter || $type_filter === get_post_meta( $item->ID, '_pvdak_request_type', true ) ) &&
            ( ! $status_filter || $status_filter === get_post_meta( $item->ID, '_pvdak_request_state', true ) ) ) {
            $items[] = $item;
        } else {
            $unexpected_rows = true;
        }
    }
    // Reject impossible pagination supplied by a third-party query filter.
    // An empty out-of-range page is valid, but a nonempty later page must
    // account for all preceding pages in the reported result total.
    $found_valid = is_numeric( $query->found_posts ) &&
        ctype_digit( (string) $query->found_posts );
    $pages_valid = is_numeric( $query->max_num_pages ) &&
        ctype_digit( (string) $query->max_num_pages );
    if ( ! $found_valid || ! $pages_valid || count( $items ) > 10 ||
         ( is_array( $query->posts ) && count( $query->posts ) > 10 ) ) {
        $unexpected_rows = true;
    } else {
        $reported_total = (int) $query->found_posts;
        $expected_pages = (int) ceil( $reported_total / 10 );
        if ( (int) $query->max_num_pages !== $expected_pages ||
             ( $items && ( ( $page - 1 ) * 10 + count( $items ) > $reported_total ) ) ) {
            $unexpected_rows = true;
        }
    }
    // Never show potentially foreign rows or an untrustworthy total when a
    // third-party query filter has changed the owner/private-only result.
    if ( $unexpected_rows ) {
        return '<section id="pvdak-requests" class="pvdak-core-panel"><h2>درخواست‌های من</h2>' .
            '<p class="pvdak-core-notice" role="status">نمایش سوابق موقتاً در دسترس نیست. لطفاً بعداً دوباره تلاش کنید.</p></section>';
    }
    $total = max( 0, (int) $query->found_posts );
    $pages = max( 1, (int) $query->max_num_pages );
    $base = get_permalink();
    $filters = array();
    if ( $type_filter ) { $filters['request_type'] = $type_filter; }
    if ( $status_filter ) { $filters['request_status'] = $status_filter; }
    if ( 'ASC' === $order ) { $filters['request_order'] = 'asc'; }
    if ( '' !== $search ) { $filters['request_search'] = $search; }
    $filtered_url = add_query_arg( $filters, $base );

    $html = '<section id="pvdak-requests" class="pvdak-core-panel pvdak-core-request-history" aria-labelledby="pvdak-requests-heading">';
    $html .= '<h2 id="pvdak-requests-heading">درخواست‌های من</h2>';
    $html .= '<p class="pvdak-core-request-privacy">فقط درخواست‌های متعلق به حساب شما نمایش داده می‌شود. شرح محرمانه، یادداشت داخلی و اطلاعات اشخاص دیگر در این فهرست نمایش داده نمی‌شود.</p>';
    $html .= '<form method="get" action="' . esc_url( $base ) . '" class="pvdak-core-request-filters" role="search" aria-label="فیلتر درخواست‌های شخصی">';
    // Browsers discard query parameters from the action URL of a GET form.
    // Preserve WordPress Plain permalink routing without altering site settings.
    if ( ! get_option( 'permalink_structure' ) && is_page() ) {
        $html .= '<input type="hidden" name="page_id" value="' . esc_attr( get_queried_object_id() ) . '">';
    }
    $html .= '<label for="pvdak-request-search">جستجو در درخواست‌ها<input id="pvdak-request-search" type="search" name="request_search" value="' . esc_attr( $search ) . '" maxlength="80" placeholder="عنوان یا متن درخواست"></label>';
    $html .= '<label for="pvdak-request-type">نوع درخواست<select id="pvdak-request-type" name="request_type"><option value="">همه انواع</option>';
    foreach ( $types as $key => $label ) {
        $html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $type_filter, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    $html .= '</select></label><label for="pvdak-request-status">وضعیت<select id="pvdak-request-status" name="request_status"><option value="">همه وضعیت‌ها</option>';
    foreach ( $statuses as $key => $label ) {
        $html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $status_filter, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    $html .= '</select></label><label for="pvdak-request-order">ترتیب<select id="pvdak-request-order" name="request_order">';
    $html .= '<option value="desc" ' . selected( $order, 'DESC', false ) . '>جدیدترین</option>';
    $html .= '<option value="asc" ' . selected( $order, 'ASC', false ) . '>قدیمی‌ترین</option></select></label>';
    $html .= '<button type="submit">اعمال فیلتر</button>';
    if ( $filters ) { $html .= '<a class="pvdak-core-request-reset" href="' . esc_url( $base . '#pvdak-requests' ) . '">پاک‌کردن فیلترها</a>'; }
    $html .= '</form>';

    $html .= '<p class="pvdak-core-request-count" role="status">' . esc_html( number_format_i18n( count( $items ) ) ) .
        ' درخواست در این صفحه از ' . esc_html( number_format_i18n( $total ) ) . ' نتیجه</p>';
    if ( ! $items ) {
        $html .= '<p class="pvdak-core-empty">' .
            ( $page > $pages && $total > 0 ? 'شماره صفحه خارج از محدوده نتایج است.' :
                ( $filters ? 'درخواستی با این فیلترها پیدا نشد.' : 'هنوز درخواستی ثبت نشده است.' ) ) . '</p>';
        if ( $page > $pages && $total > 0 ) {
            $html .= '<p><a class="pvdak-core-link" href="' .
                esc_url( add_query_arg( 'pvdak_page', $pages, $filtered_url ) . '#pvdak-requests' ) .
                '">رفتن به آخرین صفحه موجود</a></p>';
        }
    } else {
        $html .= '<ul class="pvdak-core-requests pvdak-core-request-cards">';
        foreach ( $items as $item ) {
            $type = get_post_meta( $item->ID, '_pvdak_request_type', true );
            $state = get_post_meta( $item->ID, '_pvdak_request_state', true );
            $state_key = is_string( $state ) && isset( $statuses[$state] ) ? $state : 'unknown';
            $topic_id = pvdak_core_request_positive_id( get_post_meta( $item->ID, '_pvdak_legal_landing_id', true ) );
            $topic = $topic_id && 'page' === get_post_type( $topic_id ) && 'publish' === get_post_status( $topic_id ) ?
                get_the_title( $topic_id ) : '';
            $html .= '<li class="pvdak-core-request-card"><div class="pvdak-core-request-card-head"><strong>' .
                esc_html( get_the_title( $item->ID ) ) . '</strong><span class="pvdak-core-request-state pvdak-core-request-state--' .
                esc_attr( $state_key ) . '">' . esc_html( is_string( $state ) && isset( $statuses[$state] ) ? $statuses[$state] : 'نیازمند بررسی' ) . '</span></div>';
            $html .= '<dl><div><dt>شماره پیگیری</dt><dd>' . esc_html( number_format_i18n( $item->ID ) ) . '</dd></div>';
            $html .= '<div><dt>نوع درخواست</dt><dd>' . esc_html( is_string( $type ) && isset( $types[$type] ) ? $types[$type] : 'درخواست حقوقی' ) . '</dd></div>';
            $html .= '<div><dt>تاریخ ثبت</dt><dd><time datetime="' . esc_attr( get_the_date( 'Y-m-d', $item->ID ) ) .
                '">' . esc_html( get_the_date( 'j F Y', $item->ID ) ) . '</time></dd></div>';
            if ( '' !== $topic ) { $html .= '<div><dt>موضوع حقوقی</dt><dd>' . esc_html( $topic ) . '</dd></div>'; }
            $html .= '</dl></li>';
        }
        $html .= '</ul>';
    }
    if ( $pages > 1 && $page <= $pages ) {
        $links = paginate_links( array(
            'base' => add_query_arg( 'pvdak_page', '%#%', $filtered_url ),
            'current' => $page,
            'total' => $pages,
            'prev_text' => 'صفحه قبل',
            'next_text' => 'صفحه بعد',
            'add_fragment' => '#pvdak-requests',
        ) );
        if ( $links ) { $html .= '<nav class="pvdak-core-pagination" aria-label="صفحه‌های درخواست‌های من">' . $links . '</nav>'; }
    }
    return $html . '</section>';
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

/**
 * Keep notes bounded and reject invalid UTF-8 / oversized forged POST values.
 * WordPress sanitization is performed before this validation.
 */
function pvdak_core_request_note_valid( $note ) {
    if ( ! is_string( $note ) || '' === trim( $note ) || strlen( $note ) > 4000 ) {
        return false;
    }
    $length = preg_match_all( '/./us', $note );
    return false !== $length && $length <= 1000;
}

/** Replaying a saved admin form must not append the same private note twice. */
function pvdak_core_request_note_token_seen( $notes, $token ) {
    if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9-]{36}$/i', $token ) ) {
        return true; // No valid form token: fail closed for note writes.
    }
    foreach ( (array) $notes as $entry ) {
        if ( is_array( $entry ) && isset( $entry['token'] ) && is_string( $entry['token'] ) &&
            hash_equals( $entry['token'], $token ) ) {
            return true;
        }
    }
    return false;
}

/** Only exact server-defined case states may be accepted from admin forms.
 * sanitize_key() could turn a forged value such as "review!" into "review".
 */
function pvdak_core_request_state_choice( $value ) {
    $states = pvdak_core_request_statuses();
    return is_string( $value ) && isset( $states[ $value ] ) ? $value : null;
}

/** A damaged or legacy-unknown service type needs explicit repair first. */
function pvdak_core_request_workflow_type_valid( $type ) {
    $types = pvdak_core_request_types();
    return is_string( $type ) && isset( $types[ $type ] );
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
    if ( ! pvdak_core_request_workflow_type_valid( $type ) ) {
        echo '<p class="notice notice-warning inline">نوع خدمت این پرونده نامعتبر است؛ تغییر وضعیت و یادداشت تا اصلاح داده توسط مدیر مسدود است.</p>';
        return;
    }
    $landing_id = pvdak_core_request_positive_id( get_post_meta( $post->ID, '_pvdak_legal_landing_id', true ) );
    $topic = pvdak_core_topic_resolution( $landing_id );
    echo '<p><strong>موضوع حقوقی:</strong> ' .
        ( 'ready' === $topic['status'] ? esc_html( $topic['title'] ) : esc_html( $topic['message'] ) ) . '</p>';
    $current = get_post_meta( $post->ID, '_pvdak_request_state', true );
    if ( ! is_string( $current ) || ( '' !== $current && ! isset( $statuses[ $current ] ) ) ) {
        echo '<p class="notice notice-warning">وضعیت ذخیره‌شده نامعتبر است؛ ویرایش گردش کار و یادداشت تا بررسی داده توسط مدیر مسدود است.</p>';
        return;
    }
    // Keep the pre-existing legacy empty-state recovery path usable.
    if ( '' === $current ) { $current = 'submitted'; }
    echo '<input type="hidden" name="_pvdak_expected_state" value="' . esc_attr( $current ) . '">';
    echo '<input type="hidden" name="_pvdak_note_token" value="' . esc_attr( wp_generate_uuid4() ) . '">';
    echo '<p><strong>نوع:</strong> ' . esc_html( is_string( $type ) && isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' ) . '</p>';
    echo '<p><strong>وضعیت کنونی:</strong> ' . esc_html( $statuses[ $current ] ) . '</p>';
    echo '<label for="pvdak_state">تغییر وضعیت رسیدگی</label><select name="pvdak_state" id="pvdak_state" class="widefat">';
    echo '<option value="' . esc_attr( $current ) . '">بدون تغییر (' . esc_html( $statuses[ $current ] ) . ')</option>';
    foreach ( pvdak_core_allowed_request_transitions( $current ) as $next ) {
        echo '<option value="' . esc_attr( $next ) . '">' . esc_html( $statuses[ $next ] ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">انتساب داخلی فقط از بخش «مدیریت ارجاع داخلی» و پس از کنترل صلاحیت، تخصص و ظرفیت انجام می‌شود. وضعیت انتساب را از این فرم به‌صورت دستی نمی‌توان انتخاب کرد.</p>';
    echo '<label for="pvdak_internal_note"><strong>یادداشت داخلی مدیر</strong></label>';
    echo '<textarea id="pvdak_internal_note" name="pvdak_internal_note" class="widefat" maxlength="1000" rows="4" placeholder="فقط برای مدیران مجاز"></textarea>';
    echo '<p class="description">این یادداشت در پنل مراجعه‌کننده نمایش داده نمی‌شود.</p>';
    $notes = get_post_meta( $post->ID, '_pvdak_internal_notes', false );
    if ( $notes ) {
        echo '<div style="max-height:220px;overflow:auto"><strong>آخرین یادداشت‌ها</strong><ul>';
        foreach ( array_reverse( array_slice( $notes, -5 ) ) as $note ) {
            if ( ! is_array( $note ) || ! isset( $note['text'] ) || ! is_string( $note['text'] ) ) { continue; }
            echo '<li style="border-bottom:1px solid #ddd;padding:8px 0"><small>' .
                esc_html( isset( $note['at'] ) && is_string( $note['at'] ) ? $note['at'] : '' ) . '</small><p>' .
                esc_html( isset( $note['text'] ) ? $note['text'] : '' ) . '</p></li>';
        }
        echo '</ul></div>';
    }
    $audit = get_post_meta( $post->ID, '_pvdak_state_audit', false );
    if ( $audit ) {
        echo '<details><summary>تاریخچه تغییر وضعیت</summary><ol>';
        foreach ( array_reverse( array_slice( $audit, -10 ) ) as $event ) {
            if ( ! is_array( $event ) ) { continue; }
            $from = isset( $event['from'] ) && is_string( $event['from'] ) ? $event['from'] : '';
            $to = isset( $event['to'] ) && is_string( $event['to'] ) ? $event['to'] : '';
            echo '<li>' . esc_html( isset( $statuses[ $from ] ) ? $statuses[ $from ] : 'وضعیت نامعتبر' ) .
                ' ← ' . esc_html( isset( $statuses[ $to ] ) ? $statuses[ $to ] : 'وضعیت نامعتبر' ) .
                ' <small>' . esc_html( isset( $event['at'] ) && is_string( $event['at'] ) ? $event['at'] : '' ) . '</small></li>';
        }
        echo '</ol></details>';
    }
}

function pvdak_core_save_request_state( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
    // Case workflow only applies to private legal requests, never drafts/trash or another post type.
    if ( ! isset( $post->post_type ) || 'pvdak_request' !== $post->post_type ||
        'private' !== get_post_status( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_state_nonce'] ) || ! is_string( $_POST['_pvdak_state_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_state_nonce'] ) ), 'pvdak_core_state_' . $post_id ) ) { return; }

    // WordPress may call save_post for records with incomplete legacy metadata.
    // Never advance such a record or attach notes to an unknown service type.
    if ( ! pvdak_core_request_workflow_type_valid( get_post_meta( $post_id, '_pvdak_request_type', true ) ) ) {
        return;
    }
    $statuses = pvdak_core_request_statuses();
    $raw_previous = get_post_meta( $post_id, '_pvdak_request_state', true );
    // Missing legacy state is treated as submitted; an unknown nonempty state is never overwritten.
    if ( ! is_string( $raw_previous ) || ( '' !== $raw_previous && ! isset( $statuses[ $raw_previous ] ) ) ) { return; }
    $previous = '' === $raw_previous ? 'submitted' : $raw_previous;
    $expected = pvdak_core_request_state_choice( $_POST['_pvdak_expected_state'] ?? null );
    if ( $expected !== $previous ) { return; } // Stale or malformed editor form.

    // A missing state field can accompany a note-only legacy edit; a
    // malformed supplied state is different and must reject the entire write.
    $next = array_key_exists( 'pvdak_state', $_POST )
        ? pvdak_core_request_state_choice( $_POST['pvdak_state'] ) : $previous;
    if ( null === $next ) { return; }
    if ( $next !== $previous ) {
        if ( ! in_array( $next, pvdak_core_allowed_request_transitions( $previous ), true ) ) { return; }
        // Compare the previous stored value when available to avoid overwriting a concurrent transition.
        if ( ! update_post_meta( $post_id, '_pvdak_request_state', $next, $raw_previous ) ) { return; }
        if ( ! add_post_meta( $post_id, '_pvdak_state_audit', array(
            'from' => $previous,
            'to' => $next,
            'at' => current_time( 'mysql', true ),
            'by' => get_current_user_id(),
        ), false ) ) {
            // A state without its audit trail must not be reported as completed.
            update_post_meta( $post_id, '_pvdak_request_state', $previous, $next );
            return;
        }
        pvdak_core_notify_owner( $post_id, 'pvdak_request' );
    }

    if ( isset( $_POST['pvdak_internal_note'] ) && is_string( $_POST['pvdak_internal_note'] ) ) {
        $note = sanitize_textarea_field( wp_unslash( $_POST['pvdak_internal_note'] ) );
        $token = isset( $_POST['_pvdak_note_token'] ) && is_string( $_POST['_pvdak_note_token'] ) ?
            sanitize_text_field( wp_unslash( $_POST['_pvdak_note_token'] ) ) : '';
        if ( pvdak_core_request_note_valid( $note ) &&
            ! pvdak_core_request_note_token_seen( get_post_meta( $post_id, '_pvdak_internal_notes', false ), $token ) ) {
            add_post_meta( $post_id, '_pvdak_internal_notes', array(
                'text' => $note,
                'at' => current_time( 'mysql', true ),
                'by' => get_current_user_id(),
                'token' => $token,
            ), false );
        }
    }
}
add_action( 'save_post_pvdak_request', 'pvdak_core_save_request_state', 10, 2 );
