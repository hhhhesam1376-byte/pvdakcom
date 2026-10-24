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

/** Aggregate metadata only; no request descriptions, people or private notes. */
function pvdak_core_triage_snapshot() {
    global $wpdb;
    $statuses = pvdak_core_request_statuses();
    $types = pvdak_core_request_types();
    $result = array(
        'statuses' => array_fill_keys( array_keys( $statuses ), 0 ),
        'types' => array_fill_keys( array_keys( $types ), 0 ),
        'total' => 0, 'legacy_state' => 0, 'legacy_type' => 0,
        'pending' => 0, 'pending7' => 0, 'pending30' => 0, 'oldest_pending' => '',
    );
    $now = current_datetime();
    $cutoff7 = $now->modify( '-7 days' )->format( 'Y-m-d' );
    $cutoff30 = $now->modify( '-30 days' )->format( 'Y-m-d' );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT COALESCE(s.meta_value, '') AS state, COALESCE(t.meta_value, '') AS kind,
                COUNT(DISTINCT p.ID) AS total,
                COUNT(DISTINCT CASE WHEN p.post_date < %s THEN p.ID END) AS older7,
                COUNT(DISTINCT CASE WHEN p.post_date < %s THEN p.ID END) AS older30,
                MIN(p.post_date) AS oldest
         FROM {$wpdb->posts} AS p
         LEFT JOIN {$wpdb->postmeta} AS s ON s.post_id = p.ID AND s.meta_key = %s
         LEFT JOIN {$wpdb->postmeta} AS t ON t.post_id = p.ID AND t.meta_key = %s
         WHERE p.post_type = %s AND p.post_status = %s
         GROUP BY s.meta_value, t.meta_value",
        $cutoff7, $cutoff30, '_pvdak_request_state', '_pvdak_request_type',
        'pvdak_request', 'private'
    ), ARRAY_A );
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
            $result['pending7'] += (int) $row['older7'];
            $result['pending30'] += (int) $row['older30'];
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
        'pvdak_filter_queue' => array( 'pending', 'pending7', 'pending30' ),
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

function pvdak_core_triage_page() {
    if ( ! pvdak_core_triage_can_manage() ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    $snapshot = pvdak_core_triage_snapshot();
    $statuses = pvdak_core_request_statuses();
    $types = pvdak_core_request_types();
    $base = admin_url( 'edit.php?post_type=pvdak_request' );
    echo '<div class="wrap pvdak-triage" dir="rtl"><h1>صف بررسی درخواست‌های حقوقی</h1>';
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
    .pvdak-triage-actions{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0}
    .pvdak-triage .button{white-space:normal;text-align:center}
    @media(max-width:600px){.pvdak-triage-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pvdak-triage-card{padding:12px;min-width:0}}
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
    echo '</div><h2>اولویت بررسی</h2><div class="pvdak-triage-grid">';
    pvdak_core_triage_link_card( 'ثبت‌شده یا در حال بررسی', $snapshot['pending'],
        add_query_arg( 'pvdak_filter_queue', 'pending', $base ) );
    pvdak_core_triage_link_card( 'در انتظار بیش از ۷ روز', $snapshot['pending7'],
        add_query_arg( 'pvdak_filter_queue', 'pending7', $base ) );
    pvdak_core_triage_link_card( 'در انتظار بیش از ۳۰ روز', $snapshot['pending30'],
        add_query_arg( 'pvdak_filter_queue', 'pending30', $base ) );
    echo '</div>';
    if ( $snapshot['oldest_pending'] ) {
        echo '<p class="pvdak-triage-note">قدیمی‌ترین درخواست در انتظار بررسی: <strong><time datetime="' .
            esc_attr( str_replace( ' ', 'T', $snapshot['oldest_pending'] ) ) . '">' .
            esc_html( mysql2date( 'Y/m/d', $snapshot['oldest_pending'] ) ) . '</time></strong>. ' .
            'این شاخص فقط از تاریخ ثبت استفاده می‌کند و به معنی تأخیر قطعی در خدمت‌رسانی نیست.</p>';
    } elseif ( 0 === $snapshot['pending'] ) {
        echo '<p class="pvdak-triage-note">درخواست ثبت‌شده یا در حال بررسی در صف فعلی وجود ندارد.</p>';
    }
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
            'pending7' => 'در انتظار بیش از ۷ روز', 'pending30' => 'در انتظار بیش از ۳۰ روز',
        ),
    );
    foreach ( $choices as $name => $options ) {
        echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' .
            esc_html( 'فیلتر ' . $name ) . '</label>';
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
    if ( in_array( $f['pvdak_filter_queue'], array( 'pending7', 'pending30' ), true ) ) {
        $days = 'pending7' === $f['pvdak_filter_queue'] ? 7 : 30;
        $cutoff = current_datetime()->modify( '-' . $days . ' days' )->format( 'Y-m-d' );
        $existing_dates = $query->get( 'date_query' );
        $dates = array( 'relation' => 'AND' );
        if ( is_array( $existing_dates ) && $existing_dates ) { $dates[] = $existing_dates; }
        $dates[] = array( 'before' => $cutoff, 'inclusive' => false, 'column' => 'post_date' );
        $query->set( 'date_query', $dates );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_triage_filter_query', 20 );
