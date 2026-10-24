<?php
/**
 * Read-only management overview for private legal requests.
 * Never change intake, assignment, case state, or any stored user information.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_triage_can_manage() {
    return current_user_can( 'edit_pvdak_requests' );
}

function pvdak_core_triage_menu() {
    add_submenu_page(
        'edit.php?post_type=pvdak_request',
        'صف بررسی درخواست‌ها',
        'صف بررسی',
        'edit_pvdak_requests',
        'pvdak-request-triage',
        'pvdak_core_triage_page'
    );
}
add_action( 'admin_menu', 'pvdak_core_triage_menu' );

/** A single clock is shared by SQL aggregation and WP_Query date filters. */
function pvdak_core_triage_cutoff( $days, $now = null ) {
    if ( null === $now ) { $now = current_datetime(); }
    return $now->modify( '-' . max( 0, (int) $days ) . ' days' )->format( 'Y-m-d H:i:s' );
}

/**
 * Aggregate only private requests. Select the FIRST value for each meta key,
 * matching get_post_meta(..., true). Multiple legacy rows must never multiply
 * the same request across status/type groups.
 */
function pvdak_core_triage_snapshot() {
    global $wpdb;
    $statuses = pvdak_core_request_statuses();
    $types = pvdak_core_request_types();
    $result = array(
        'statuses' => array_fill_keys( array_keys( $statuses ), 0 ),
        'types' => array_fill_keys( array_keys( $types ), 0 ),
        'total' => 0, 'legacy_state' => 0, 'legacy_type' => 0,
        'pending' => 0, 'pending1' => 0, 'pending3' => 0,
        'pending7' => 0, 'pending14' => 0, 'pending30' => 0,
        'pending60' => 0, 'pending_future' => 0,
        'oldest_pending' => '', 'query_ok' => true, 'generated_at' => '',
    );
    if ( ! pvdak_core_triage_can_manage() ) { return $result; }
    $now = current_datetime();
    $result['generated_at'] = $now->format( 'Y-m-d H:i:s' );
    // Each post is represented by one derived-table row, regardless of
    // duplicated/legacy postmeta. Never select case text or owner information.
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT r.state, r.kind, COUNT(*) AS total,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older1,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older3,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older7,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older14,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older30,
                SUM(CASE WHEN r.post_date < %s THEN 1 ELSE 0 END) AS older60,
                SUM(CASE WHEN r.post_date > %s THEN 1 ELSE 0 END) AS future,
                MIN(r.post_date) AS oldest
         FROM (
            SELECT p.ID, p.post_date,
                COALESCE((SELECT m.meta_value FROM {$wpdb->postmeta} AS m
                    WHERE m.post_id = p.ID AND m.meta_key = %s
                    ORDER BY m.meta_id ASC LIMIT 1), '') AS state,
                COALESCE((SELECT m.meta_value FROM {$wpdb->postmeta} AS m
                    WHERE m.post_id = p.ID AND m.meta_key = %s
                    ORDER BY m.meta_id ASC LIMIT 1), '') AS kind
            FROM {$wpdb->posts} AS p
            WHERE p.post_type = %s AND p.post_status = %s
         ) AS r
         GROUP BY r.state, r.kind",
        pvdak_core_triage_cutoff( 1, $now ),
        pvdak_core_triage_cutoff( 3, $now ),
        pvdak_core_triage_cutoff( 7, $now ),
        pvdak_core_triage_cutoff( 14, $now ),
        pvdak_core_triage_cutoff( 30, $now ),
        pvdak_core_triage_cutoff( 60, $now ),
        $result['generated_at'],
        '_pvdak_request_state', '_pvdak_request_type',
        'pvdak_request', 'private'
    ), ARRAY_A );
    if ( null === $rows ) {
        $result['query_ok'] = false;
        return $result;
    }
    foreach ( (array) $rows as $row ) {
        $state = (string) $row['state'];
        $kind = (string) $row['kind'];
        $count = (int) $row['total'];
        $result['total'] += $count;
        if ( isset( $result['statuses'][ $state ] ) ) {
            $result['statuses'][ $state ] += $count;
        } else {
            $result['legacy_state'] += $count;
        }
        if ( isset( $result['types'][ $kind ] ) ) {
            $result['types'][ $kind ] += $count;
        } else {
            $result['legacy_type'] += $count;
        }
        if ( in_array( $state, array( 'submitted', 'review' ), true ) ) {
            $result['pending'] += $count;
            foreach ( array( 1, 3, 7, 14, 30, 60 ) as $days ) {
                $result['pending' . $days] += (int) $row['older' . $days];
            }
            $result['pending_future'] += (int) $row['future'];
            if ( ! empty( $row['oldest'] ) &&
                ( '' === $result['oldest_pending'] || $row['oldest'] < $result['oldest_pending'] ) ) {
                $result['oldest_pending'] = $row['oldest'];
            }
        }
    }
    return $result;
}

/** Backward-compatible counter API used by existing admin integrations. */
function pvdak_core_triage_counts() {
    return pvdak_core_triage_snapshot()['statuses'];
}

/** Whitelist query-string inputs before constructing any administrative filter. */
function pvdak_core_triage_filters() {
    $types = array_keys( pvdak_core_request_types() );
    $states = array_keys( pvdak_core_request_statuses() );
    $choices = array(
        'pvdak_filter_type' => array_merge( $types, array( 'legacy' ) ),
        'pvdak_filter_state' => array_merge( $states, array( 'legacy' ) ),
        'pvdak_filter_queue' => array( 'pending', 'pending1', 'pending3', 'pending7', 'pending14', 'pending30', 'pending60', 'pending_future' ),
    );
    $selected = array();
    foreach ( $choices as $name => $allowed ) {
        $raw = isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] )
            ? sanitize_key( wp_unslash( $_GET[ $name ] ) ) : '';
        $selected[ $name ] = in_array( $raw, $allowed, true ) ? $raw : '';
    }
    return $selected;
}

/** Find missing or invalid legacy metadata without revealing the underlying data. */
function pvdak_core_triage_legacy_condition( $key, $allowed ) {
    return array(
        'relation' => 'OR',
        array( 'key' => $key, 'compare' => 'NOT EXISTS' ),
        array( 'key' => $key, 'value' => $allowed, 'compare' => 'NOT IN' ),
    );
}

function pvdak_core_triage_link_card( $title, $count, $url, $hint = '' ) {
    echo '<a class="pvdak-triage-card" href="' . esc_url( $url ) . '" aria-label="' .
        esc_attr( $title . '، ' . number_format_i18n( $count ) . ' درخواست' ) . '">';
    echo '<span class="pvdak-triage-value">' . esc_html( number_format_i18n( $count ) ) . '</span>';
    echo '<span class="pvdak-triage-label">' . esc_html( $title ) . '</span>';
    if ( $hint ) { echo '<span class="pvdak-triage-hint">' . esc_html( $hint ) . '</span>'; }
    echo '</a>';
}

/**
 * Read-only shortlist of the eight oldest private requests awaiting review.
 * Fetch IDs only, then re-check post type, visibility, state and per-post access.
 */
function pvdak_core_triage_priority_cases() {
    if ( ! pvdak_core_triage_can_manage() ) { return array(); }
    $query = new WP_Query( array(
        'post_type' => 'pvdak_request',
        'post_status' => 'private',
        'posts_per_page' => 8,
        'no_found_rows' => true,
        'fields' => 'ids',
        'orderby' => array( 'date' => 'ASC', 'ID' => 'ASC' ),
        'date_query' => array( array( 'column' => 'post_date',
            'before' => current_datetime()->format( 'Y-m-d H:i:s' ), 'inclusive' => true ) ),
        'meta_query' => array( array( 'key' => '_pvdak_request_state',
            'value' => array( 'submitted', 'review' ), 'compare' => 'IN' ) ),
        'suppress_filters' => false,
    ) );
    $cases = array();
    foreach ( $query->posts as $raw_id ) {
        $id = absint( $raw_id );
        if ( ! $id || ! current_user_can( 'edit_post', $id ) ||
            'pvdak_request' !== get_post_type( $id ) ||
            'private' !== get_post_status( $id ) ) { continue; }
        $state = get_post_meta( $id, '_pvdak_request_state', true );
        if ( ! in_array( $state, array( 'submitted', 'review' ), true ) ) { continue; }
        $timestamp = (int) get_post_time( 'U', true, $id );
        if ( ! $timestamp || $timestamp > current_time( 'timestamp', true ) ) { continue; }
        $cases[] = array( 'id' => $id, 'state' => $state, 'timestamp' => $timestamp,
            'kind' => get_post_meta( $id, '_pvdak_request_type', true ) );
    }
    return $cases;
}

/** Never render case descriptions, owner identities, notes or audit bodies. */
function pvdak_core_triage_priority_table( $cases, $base ) {
    echo '<h2 id="pvdak-priority-heading">قدیمی‌ترین درخواست‌های منتظر بررسی</h2>';
    echo '<p class="description">حداکثر هشت مورد، فقط اطلاعات عملیاتی؛ متن پرونده و اطلاعات مراجعه‌کننده نمایش داده نمی‌شود.</p>';
    if ( ! $cases ) {
        echo '<p class="pvdak-triage-note">درخواست قابل نمایش در فهرست اولویت وجود ندارد.</p>';
        return;
    }
    $types = pvdak_core_request_types();
    $states = pvdak_core_request_statuses();
    echo '<div class="pvdak-triage-table-wrap" role="region" tabindex="0" aria-label="جدول اولویت رسیدگی">';
    echo '<table class="widefat striped pvdak-triage-table"><caption class="screen-reader-text">قدیمی‌ترین درخواست‌های خصوصی در انتظار رسیدگی</caption>';
    echo '<thead><tr><th scope="col">پیگیری</th><th scope="col">نوع خدمت</th><th scope="col">وضعیت</th><th scope="col">زمان انتظار</th><th scope="col">اقدام</th></tr></thead><tbody>';
    foreach ( $cases as $case ) {
        $id = $case['id'];
        $edit = get_edit_post_link( $id );
        if ( ! $edit ) { continue; }
        $age = human_time_diff( $case['timestamp'], current_time( 'timestamp', true ) );
        $kind = isset( $types[ $case['kind'] ] ) ? $types[ $case['kind'] ] : 'نوع نامشخص';
        $state = isset( $states[ $case['state'] ] ) ? $states[ $case['state'] ] : 'نیازمند بررسی';
        echo '<tr><th scope="row"><a href="' . esc_url( $edit ) . '">#' . esc_html( $id ) . '</a></th>';
        echo '<td>' . esc_html( $kind ) . '</td><td>' . esc_html( $state ) . '</td>';
        echo '<td><time datetime="' . esc_attr( gmdate( 'c', $case['timestamp'] ) ) . '">' .
            esc_html( $age . ' پیش' ) . '</time></td>';
        echo '<td><a class="button button-small" href="' . esc_url( $edit ) . '">بررسی درخواست #' .
            esc_html( $id ) . '</a></td></tr>';
    }
    echo '</tbody></table></div>';
    echo '<p><a href="' . esc_url( add_query_arg( array( 'pvdak_filter_queue' => 'pending',
        'pvdak_case_order' => 'oldest' ), $base ) ) . '">مشاهده تمام موارد در انتظار به‌ترتیب قدمت ←</a></p>';
}

function pvdak_core_triage_page() {
    if ( ! pvdak_core_triage_can_manage() ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    $snapshot = pvdak_core_triage_snapshot();
    $statuses = pvdak_core_request_statuses();
    $types = pvdak_core_request_types();
    $base = add_query_arg( 'post_status', 'private', admin_url( 'edit.php?post_type=pvdak_request' ) );
    echo '<div class="wrap pvdak-triage" dir="rtl"><h1>صف بررسی درخواست‌های حقوقی</h1>';
    if ( ! $snapshot['query_ok'] ) {
        echo '<div class="notice notice-error"><p>دریافت آمار صف فعلاً ممکن نیست؛ لطفاً گزارش خطای پایگاه داده را در محیط امن بررسی کنید.</p></div></div>';
        return;
    }
    echo '<p class="description">نمای مدیریتی فقط‌خواندنی برای درخواست‌های خصوصی. متن پرونده، هویت مراجعه‌کننده و یادداشت‌های داخلی در این صفحه نمایش داده نمی‌شوند.</p>';
    echo '<div class="notice notice-info inline"><p>ارجاع خودکار به متخصص و احراز حرفه‌ای هنوز فعال نیست. وضعیت پذیرش درخواست: <strong>' .
        ( pvdak_core_intake_open() ? 'فعال (صرفاً نمایش وضعیت)' : 'غیرفعال' ) . '</strong>.</p></div>';
    echo '<style>
    .pvdak-triage{max-width:1180px}
    .pvdak-triage h2{margin:30px 0 12px}
    .pvdak-triage-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:12px 0 22px}
    .pvdak-triage-card{display:flex;flex-direction:column;gap:6px;min-height:86px;padding:16px;background:#fff;border:1px solid #cbd5dc;border-radius:10px;text-decoration:none;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .pvdak-triage-card:hover,.pvdak-triage-card:focus-visible{border-color:#2271b1;box-shadow:0 0 0 2px #2271b133}
    .pvdak-triage-value{font-size:27px;line-height:1.25;font-weight:700;color:#1d3550}
    .pvdak-triage-label{font-weight:600;color:#233a49}
    .pvdak-triage-hint{color:#50575e;font-size:12px}
    .pvdak-triage-note{padding:14px 18px;background:#fff;border-inline-start:4px solid #2271b1}
    .pvdak-triage-card--urgent{border-inline-start:4px solid #a54519}
    .pvdak-triage-summary{font-weight:600;color:#233a49}
    .pvdak-triage-meta{color:#50575e;font-size:12px}
    .pvdak-triage-actions{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
    .pvdak-triage .button{white-space:normal;text-align:center}
    .pvdak-triage-table-wrap{max-width:100%;overflow-x:auto;margin:12px 0 18px;border-radius:8px}
    .pvdak-triage-table{min-width:580px}
    .pvdak-triage-table th,.pvdak-triage-table td{vertical-align:middle;padding:11px}
    .pvdak-triage-table-wrap:focus-visible{outline:2px solid #2271b1;outline-offset:2px}
    @media(max-width:600px){.pvdak-triage-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pvdak-triage-card{padding:12px;min-width:0}}
    @media(max-width:420px){.pvdak-triage-grid{grid-template-columns:minmax(0,1fr)}}
    </style>';
    echo '<h2>خلاصه درخواست‌ها</h2><div class="pvdak-triage-grid">';
    pvdak_core_triage_link_card( 'همه درخواست‌های خصوصی', $snapshot['total'], $base, 'بدون نمایش اطلاعات محرمانه' );
    foreach ( $statuses as $key => $label ) {
        pvdak_core_triage_link_card( $label, $snapshot['statuses'][ $key ],
            add_query_arg( 'pvdak_filter_state', $key, $base ) );
    }
    if ( $snapshot['legacy_state'] ) {
        pvdak_core_triage_link_card( 'وضعیت قدیمی / نامعتبر', $snapshot['legacy_state'],
            add_query_arg( 'pvdak_filter_state', 'legacy', $base ), 'نیازمند بررسی دستی' );
    }
    echo '</div><h2>اولویت بررسی</h2>';
    echo '<p class="pvdak-triage-summary">' . esc_html( number_format_i18n( $snapshot['pending'] ) ) .
        ' درخواست در صف؛ ' . esc_html( number_format_i18n( $snapshot['total'] ? round( 100 * $snapshot['pending'] / $snapshot['total'] ) : 0 ) ) .
        '٪ از درخواست‌های خصوصی.</p>';
    echo '<p class="pvdak-triage-meta">زمان محاسبه (ساعت سایت): <time datetime="' .
        esc_attr( str_replace( ' ', 'T', $snapshot['generated_at'] ) ) . '">' .
        esc_html( $snapshot['generated_at'] ) . '</time>. معیار قدمت: بیش از تعداد روز کامل از زمان ثبت.</p>';
    echo '<div class="pvdak-triage-grid">';
    $oldest_first = array( 'pvdak_case_order' => 'oldest' );
    pvdak_core_triage_link_card( 'ثبت‌شده یا در حال بررسی', $snapshot['pending'],
        add_query_arg( array_merge( $oldest_first, array( 'pvdak_filter_queue' => 'pending' ) ), $base ),
        'قدیمی‌ترین ابتدا' );
    foreach ( array( 1, 3, 7, 14, 30, 60 ) as $days ) {
        pvdak_core_triage_link_card( 'در انتظار بیش از ' . $days . ' روز',
            $snapshot['pending' . $days],
            add_query_arg( array_merge( $oldest_first, array( 'pvdak_filter_queue' => 'pending' . $days ) ), $base ),
            $days >= 14 ? 'برای بازبینی اولویت' : 'مرتب‌شده از قدیمی‌ترین' );
    }
    if ( $snapshot['pending_future'] ) {
        pvdak_core_triage_link_card( 'تاریخ ثبت آینده / نیازمند بررسی', $snapshot['pending_future'],
            add_query_arg( array_merge( $oldest_first, array( 'pvdak_filter_queue' => 'pending_future' ) ), $base ),
            'اختلاف زمان یا داده قدیمی؛ بدون تغییر خودکار' );
    }
    echo '</div>';
    if ( $snapshot['oldest_pending'] ) {
        echo '<p class="pvdak-triage-note">قدیمی‌ترین درخواست در انتظار بررسی: <strong><time datetime="' .
            esc_attr( str_replace( ' ', 'T', $snapshot['oldest_pending'] ) ) . '">' .
            esc_html( mysql2date( 'Y/m/d', $snapshot['oldest_pending'] ) ) . '</time></strong>. ' .
            'این شاخص فقط از تاریخ ثبت استفاده می‌کند و به معنی تأخیر قطعی در خدمت‌رسانی نیست.</p>';
    } elseif ( 0 === $snapshot['pending'] ) {
        echo '<p class="pvdak-triage-note">درخواست ثبت‌شده یا در حال بررسی در صف فعلی وجود ندارد.</p>';
    }
    pvdak_core_triage_priority_table( pvdak_core_triage_priority_cases(), $base );
    echo '<h2>نوع خدمت درخواستی</h2><div class="pvdak-triage-grid">';
    foreach ( $types as $key => $label ) {
        pvdak_core_triage_link_card( $label, $snapshot['types'][ $key ],
            add_query_arg( 'pvdak_filter_type', $key, $base ) );
    }
    if ( $snapshot['legacy_type'] ) {
        pvdak_core_triage_link_card( 'نوع نامشخص / قدیمی', $snapshot['legacy_type'],
            add_query_arg( 'pvdak_filter_type', 'legacy', $base ), 'نیازمند بررسی دستی' );
    }
    echo '</div><div class="pvdak-triage-actions">';
    echo '<a class="button button-primary" href="' . esc_url( $base ) . '">مشاهده فهرست و فیلترهای پیشرفته</a>';
    echo '<a class="button" href="' . esc_url( add_query_arg( 'pvdak_filter_queue', 'pending', $base ) ) . '">رفتن به صف بررسی</a>';
    echo '</div><h2>راهنمای رسیدگی</h2>';
    echo '<p>گردش داخلی: ثبت‌شده ← در حال بررسی ← پاسخ داده‌شده / بسته‌شده. انتساب داخلی به معنی تأیید صلاحیت حرفه‌ای نیست. تغییر وضعیت و مشاهده جزئیات فقط از مسیر مجاز وردپرس انجام شود.</p>';
    echo '<p class="description">آمار صرفاً از درخواست‌های خصوصی خوانده می‌شود. فیلترهای نوع، وضعیت، قدمت، موضوع و شماره پیگیری در فهرست مدیریت قابل ترکیب‌اند.</p>';
    echo '</div>';
}

/** Native admin-list controls. All selections are allowlisted. */
function pvdak_core_triage_list_filters( $post_type ) {
    if ( 'pvdak_request' !== $post_type || ! pvdak_core_triage_can_manage() ) { return; }
    $f = pvdak_core_triage_filters();
    $choices = array(
        'pvdak_filter_type' => array_merge( array( '' => 'همه انواع درخواست' ),
            pvdak_core_request_types(), array( 'legacy' => 'نوع نامشخص / قدیمی' ) ),
        'pvdak_filter_state' => array_merge( array( '' => 'همه وضعیت‌ها' ),
            pvdak_core_request_statuses(), array( 'legacy' => 'وضعیت نامشخص / قدیمی' ) ),
        'pvdak_filter_queue' => array(
            '' => 'همه صف‌ها', 'pending' => 'ثبت‌شده یا در حال بررسی',
            'pending1' => 'در انتظار بیش از ۱ روز', 'pending3' => 'در انتظار بیش از ۳ روز',
            'pending7' => 'در انتظار بیش از ۷ روز', 'pending14' => 'در انتظار بیش از ۱۴ روز',
            'pending30' => 'در انتظار بیش از ۳۰ روز', 'pending60' => 'در انتظار بیش از ۶۰ روز',
            'pending_future' => 'تاریخ ثبت آینده / بررسی',
        ),
    );
    $labels = array(
        'pvdak_filter_type' => 'فیلتر نوع درخواست',
        'pvdak_filter_state' => 'فیلتر وضعیت درخواست',
        'pvdak_filter_queue' => 'فیلتر اولویت بررسی',
    );
    foreach ( $choices as $name => $options ) {
        echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' .
            esc_html( $labels[ $name ] ) . '</label>';
        echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
        foreach ( $options as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '"' .
                selected( $f[ $name ], (string) $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }
}
add_action( 'restrict_manage_posts', 'pvdak_core_triage_list_filters' );

/** Preserve native and other plugin query clauses rather than replacing them. */
function pvdak_core_triage_filter_query( $query ) {
    global $pagenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ||
         ! pvdak_core_triage_can_manage() || 'pvdak_request' !== $query->get( 'post_type' ) ) { return; }
    $f = pvdak_core_triage_filters();
    // Dashboard counters cover private records only. Match that scope for
    // dashboard-driven links, but preserve an explicit native status tab.
    if ( array_filter( $f ) && ! $query->get( 'post_status' ) &&
        ( ! isset( $_GET['post_status'] ) || '' === $_GET['post_status'] ) ) {
        $query->set( 'post_status', 'private' );
    }
    $filters = array();
    $types = pvdak_core_request_types();
    $statuses = pvdak_core_request_statuses();
    if ( isset( $types[ $f['pvdak_filter_type'] ] ) ) {
        $filters[] = array( 'key' => '_pvdak_request_type',
            'value' => $f['pvdak_filter_type'], 'compare' => '=' );
    } elseif ( 'legacy' === $f['pvdak_filter_type'] ) {
        $filters[] = pvdak_core_triage_legacy_condition( '_pvdak_request_type', array_keys( $types ) );
    }
    if ( isset( $statuses[ $f['pvdak_filter_state'] ] ) ) {
        $filters[] = array( 'key' => '_pvdak_request_state',
            'value' => $f['pvdak_filter_state'], 'compare' => '=' );
    } elseif ( 'legacy' === $f['pvdak_filter_state'] ) {
        $filters[] = pvdak_core_triage_legacy_condition( '_pvdak_request_state', array_keys( $statuses ) );
    }
    if ( $f['pvdak_filter_queue'] ) {
        $filters[] = array( 'key' => '_pvdak_request_state',
            'value' => array( 'submitted', 'review' ), 'compare' => 'IN' );
    }
    if ( $filters ) {
        $existing = $query->get( 'meta_query' );
        $combined = array( 'relation' => 'AND' );
        if ( is_array( $existing ) && $existing ) { $combined[] = $existing; }
        foreach ( $filters as $condition ) { $combined[] = $condition; }
        $query->set( 'meta_query', $combined );
    }
    $queue = $f['pvdak_filter_queue'];
    $age_days = array( 'pending1' => 1, 'pending3' => 3, 'pending7' => 7,
        'pending14' => 14, 'pending30' => 30, 'pending60' => 60 );
    if ( isset( $age_days[ $queue ] ) || 'pending_future' === $queue ) {
        $existing_dates = $query->get( 'date_query' );
        $dates = array( 'relation' => 'AND' );
        if ( is_array( $existing_dates ) && $existing_dates ) { $dates[] = $existing_dates; }
        $dates[] = 'pending_future' === $queue
            ? array( 'after' => current_datetime()->format( 'Y-m-d H:i:s' ), 'inclusive' => false, 'column' => 'post_date' )
            : array( 'before' => pvdak_core_triage_cutoff( $age_days[ $queue ] ), 'inclusive' => false, 'column' => 'post_date' );
        $query->set( 'date_query', $dates );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_triage_filter_query', 20 );
