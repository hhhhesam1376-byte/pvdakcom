<?php
/**
 * Private request administration: read-only queue discovery.
 * Never change intake, state transitions, assignment, or stored case data.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_case_admin_allowed() {
    $type = get_post_type_object( 'pvdak_request' );
    return $type && current_user_can( $type->cap->edit_posts );
}

/**
 * An empty admin filter means "all"; a supplied malformed identifier means
 * "no results", never an accidental unrestricted case list.
 */
function pvdak_core_case_admin_positive_filter( $name ) {
    if ( ! array_key_exists( $name, $_GET ) ) { return 0; }
    if ( ! is_string( $_GET[ $name ] ) ) { return -1; }
    $raw = trim( wp_unslash( $_GET[ $name ] ) );
    if ( '' === $raw ) { return 0; }
    if ( ! preg_match( '/^[1-9][0-9]{0,9}$/D', $raw ) ||
         (float) $raw > 2147483647 ) { return -1; }
    return (int) $raw;
}

/** Preserve the distinction between an empty filter and a malformed supplied value.
 * Silently ignoring invalid selectors could widen the private case list.
 */
function pvdak_core_case_admin_choice_filter( $name, $allowed ) {
    if ( ! array_key_exists( $name, $_GET ) ) { return ''; }
    if ( ! is_string( $_GET[ $name ] ) ) { return '-1'; }
    $raw = trim( wp_unslash( $_GET[ $name ] ) );
    if ( '' === $raw ) { return ''; }
    return in_array( $raw, $allowed, true ) ? $raw : '-1';
}

/** Validate all URL inputs before they can affect WP_Query. */
function pvdak_core_case_admin_filters() {
    $choices = array(
        'pvdak_case_age' => array( 'today', 'last7', 'older7', 'older30', 'future' ),
        'pvdak_case_notes' => array( 'yes', 'no' ),
        'pvdak_case_audit' => array( 'yes', 'no' ),
        'pvdak_case_order' => array( 'newest', 'oldest' ),
    );
    $result = array();
    foreach ( $choices as $name => $allowed ) {
        $result[ $name ] = pvdak_core_case_admin_choice_filter( $name, $allowed );
    }
    foreach ( array( 'pvdak_case_id', 'pvdak_case_owner' ) as $name ) {
        $result[ $name ] = pvdak_core_case_admin_positive_filter( $name );
    }
    $raw_topic = isset( $_GET['pvdak_case_topic'] ) && is_string( $_GET['pvdak_case_topic'] )
        ? trim( wp_unslash( $_GET['pvdak_case_topic'] ) ) : '';
    $topic = pvdak_core_case_admin_positive_filter( 'pvdak_case_topic' );
    $result['pvdak_case_topic'] = 'missing' === $raw_topic ? 'missing'
        : ( $topic > 0 && ! pvdak_core_valid_specialty( $topic ) ? -1 : $topic );
    return $result;
}

function pvdak_core_case_admin_select( $name, $label, $options, $current ) {
    echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
    echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" aria-label="' . esc_attr( $label ) . '">';
    echo '<option value="">' . esc_html( $label ) . ': همه</option>';
    foreach ( $options as $value => $title ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( (string) $current, (string) $value, false ) . '>' . esc_html( $title ) . '</option>';
    }
    echo '</select>';
}

/** Additional controls complement the existing type/state controls in triage.php. */
function pvdak_core_case_admin_controls( $post_type ) {
    if ( 'pvdak_request' !== $post_type || ! pvdak_core_case_admin_allowed() ) { return; }
    $f = pvdak_core_case_admin_filters();
    $topics = array( 'missing' => 'موضوع ثبت‌نشده / قدیمی' );
    foreach ( pvdak_core_specialty_pages() as $page ) {
        $topics[ (int) $page->ID ] = $page->post_title;
    }
    pvdak_core_case_admin_select( 'pvdak_case_topic', 'موضوع حقوقی', $topics, $f['pvdak_case_topic'] );
    pvdak_core_case_admin_select( 'pvdak_case_age', 'قدمت درخواست', array(
        'today' => 'ثبت امروز',
        'last7' => 'هفت روز اخیر',
        'older7' => 'قدیمی‌تر از هفت روز',
        'older30' => 'قدیمی‌تر از سی روز',
        'future' => 'تاریخ ثبت آینده / غیرعادی',
    ), $f['pvdak_case_age'] );
    pvdak_core_case_admin_select( 'pvdak_case_notes', 'یادداشت داخلی', array(
        'yes' => 'دارای یادداشت', 'no' => 'بدون یادداشت',
    ), $f['pvdak_case_notes'] );
    pvdak_core_case_admin_select( 'pvdak_case_audit', 'تاریخچه وضعیت', array(
        'yes' => 'دارای تاریخچه', 'no' => 'بدون تاریخچه',
    ), $f['pvdak_case_audit'] );
    pvdak_core_case_admin_select( 'pvdak_case_order', 'ترتیب ثبت', array(
        'newest' => 'جدیدترین ابتدا', 'oldest' => 'قدیمی‌ترین ابتدا',
    ), $f['pvdak_case_order'] );
    foreach ( array(
        'pvdak_case_id' => 'شماره پیگیری دقیق',
        'pvdak_case_owner' => 'شناسه کاربر ثبت‌کننده',
    ) as $name => $label ) {
        echo '<label class="screen-reader-text" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
        echo '<input type="number" min="1" max="2147483647" step="1" id="' . esc_attr( $name ) .
            '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $f[ $name ] > 0 ? $f[ $name ] : '' ) .
            '" placeholder="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '">';
    }
    echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=pvdak_request' ) ) . '">پاک‌کردن فیلترهای صف</a>';
    if ( in_array( '-1', $f, true ) ||
         in_array( -1, array( $f['pvdak_case_id'], $f['pvdak_case_owner'], $f['pvdak_case_topic'] ), true ) ) {
        echo '<span class="pvdak-case-active-filters" role="status">یکی از فیلترهای صف نامعتبر است؛ هیچ پرونده‌ای نمایش داده نمی‌شود.</span>';
    }
}
add_action( 'restrict_manage_posts', 'pvdak_core_case_admin_controls', 20 );

/** Combine with native WP and existing type/state filters rather than replacing them. */
function pvdak_core_case_admin_query( $query ) {
    global $pagenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ||
        'pvdak_request' !== $query->get( 'post_type' ) || ! pvdak_core_case_admin_allowed() ) { return; }
    $f = pvdak_core_case_admin_filters();
    if ( in_array( '-1', $f, true ) ||
         in_array( -1, array( $f['pvdak_case_id'], $f['pvdak_case_owner'], $f['pvdak_case_topic'] ), true ) ) {
        $query->set( 'post__in', array( 0 ) );
        return; // Invalid supplied filters must never expand the private queue.
    }
    if ( array_filter( $f ) && ! $query->get( 'post_status' ) &&
        ( ! isset( $_GET['post_status'] ) || '' === $_GET['post_status'] ) ) {
        $query->set( 'post_status', 'private' );
    }
    $conditions = array();
    if ( is_int( $f['pvdak_case_topic'] ) && $f['pvdak_case_topic'] ) {
        $conditions[] = array( 'key' => '_pvdak_legal_landing_id', 'value' => $f['pvdak_case_topic'], 'compare' => '=' );
    } elseif ( 'missing' === $f['pvdak_case_topic'] ) {
        $conditions[] = array( 'relation' => 'OR',
            array( 'key' => '_pvdak_legal_landing_id', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_pvdak_legal_landing_id', 'value' => '', 'compare' => '=' ),
            array( 'key' => '_pvdak_legal_landing_id', 'value' => '0', 'compare' => '=' ),
        );
    }
    foreach ( array(
        'pvdak_case_notes' => '_pvdak_internal_notes',
        'pvdak_case_audit' => '_pvdak_state_audit',
    ) as $field => $meta_key ) {
        if ( '' !== $f[ $field ] ) {
            $conditions[] = array( 'key' => $meta_key, 'compare' => 'yes' === $f[ $field ] ? 'EXISTS' : 'NOT EXISTS' );
        }
    }
    if ( $conditions ) {
        $existing = $query->get( 'meta_query' );
        $combined = array( 'relation' => 'AND' );
        if ( is_array( $existing ) && $existing ) { $combined[] = $existing; }
        foreach ( $conditions as $condition ) { $combined[] = $condition; }
        $query->set( 'meta_query', $combined );
    }
    if ( $f['pvdak_case_id'] ) {
        $existing_ids = $query->get( 'post__in' );
        $ids = is_array( $existing_ids ) && $existing_ids
            ? array_intersect( array_map( 'absint', $existing_ids ), array( $f['pvdak_case_id'] ) )
            : array( $f['pvdak_case_id'] );
        $query->set( 'post__in', $ids ? array_values( $ids ) : array( 0 ) );
    }
    if ( $f['pvdak_case_owner'] ) {
        $existing_author = absint( $query->get( 'author' ) );
        if ( $existing_author && $existing_author !== $f['pvdak_case_owner'] ) {
            $query->set( 'post__in', array( 0 ) ); // Never broaden an existing author restriction.
        } else {
            $query->set( 'author', $f['pvdak_case_owner'] );
        }
    }
    $now = current_datetime();
    $age = $f['pvdak_case_age'];
    $date = array();
    if ( 'today' === $age ) {
        $date = array( 'after' => $now->format( 'Y-m-d 00:00:00' ),
            'before' => $now->format( 'Y-m-d H:i:s' ), 'inclusive' => true );
    } elseif ( 'last7' === $age ) {
        $date = array( 'after' => pvdak_core_triage_cutoff( 7, $now ),
            'before' => $now->format( 'Y-m-d H:i:s' ), 'inclusive' => true );
    } elseif ( 'older7' === $age || 'older30' === $age ) {
        $date = array( 'before' => pvdak_core_triage_cutoff( 'older7' === $age ? 7 : 30, $now ),
            'inclusive' => false );
    } elseif ( 'future' === $age ) {
        $date = array( 'after' => $now->format( 'Y-m-d H:i:s' ), 'inclusive' => false );
    }
    if ( $date ) {
        $previous = $query->get( 'date_query' );
        $combined_dates = array( 'relation' => 'AND' );
        if ( is_array( $previous ) && $previous ) { $combined_dates[] = $previous; }
        $combined_dates[] = array_merge( $date, array( 'column' => 'post_date' ) );
        $query->set( 'date_query', $combined_dates );
    }
    if ( '' !== $f['pvdak_case_order'] ) {
        $query->set( 'orderby', 'date' );
        $query->set( 'order', 'oldest' === $f['pvdak_case_order'] ? 'ASC' : 'DESC' );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_case_admin_query', 30 );

/** List shows metadata summaries only, never private descriptions or notes. */
function pvdak_core_case_admin_columns( $columns ) {
    if ( ! pvdak_core_case_admin_allowed() ) { return $columns; }
    $columns['pvdak_case_ref'] = 'پیگیری';
    $columns['pvdak_case_topic_column'] = 'موضوع حقوقی';
    $columns['pvdak_case_age_column'] = 'قدمت';
    $columns['pvdak_case_activity'] = 'سوابق داخلی';
    $columns['pvdak_case_modified'] = 'آخرین ویرایش';
    return $columns;
}
add_filter( 'manage_pvdak_request_posts_columns', 'pvdak_core_case_admin_columns', 30 );

function pvdak_core_case_admin_column_content( $column, $post_id ) {
    if ( ! pvdak_core_case_admin_allowed() || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( 'pvdak_case_ref' === $column ) {
        $label = '#' . absint( $post_id );
        $edit_url = get_edit_post_link( $post_id );
        echo $edit_url ? '<a href="' . esc_url( $edit_url ) . '"><code>' . esc_html( $label ) . '</code></a>'
            : '<code>' . esc_html( $label ) . '</code>';
    } elseif ( 'pvdak_case_topic_column' === $column ) {
        $id = pvdak_core_assignment_positive_id( get_post_meta( $post_id, '_pvdak_legal_landing_id', true ) );
        echo $id && pvdak_core_valid_specialty( $id ) ? esc_html( get_the_title( $id ) ) : 'ثبت‌نشده / قدیمی';
    } elseif ( 'pvdak_case_age_column' === $column ) {
        $timestamp = get_post_time( 'U', true, $post_id );
        $now = current_time( 'timestamp', true );
        if ( ! $timestamp ) { echo '—'; }
        elseif ( $timestamp > $now ) { echo '<strong class="pvdak-case-alert">تاریخ ثبت آینده</strong>'; }
        else { echo esc_html( human_time_diff( $timestamp, $now ) . ' پیش' ); }
    } elseif ( 'pvdak_case_activity' === $column ) {
        $labels = array();
        if ( metadata_exists( 'post', $post_id, '_pvdak_internal_notes' ) ) { $labels[] = 'یادداشت داخلی'; }
        if ( metadata_exists( 'post', $post_id, '_pvdak_state_audit' ) ) { $labels[] = 'تغییر وضعیت'; }
        echo esc_html( $labels ? implode( '، ', $labels ) : 'بدون سابقه' );
    } elseif ( 'pvdak_case_modified' === $column ) {
        $modified = get_post_modified_time( 'Y-m-d H:i', false, $post_id );
        echo $modified ? '<time datetime="' . esc_attr( get_post_modified_time( 'c', false, $post_id ) ) .
            '">' . esc_html( $modified ) . '</time>' : '—';
        $modified_utc = get_post_modified_time( 'U', true, $post_id );
        if ( $modified_utc && $modified_utc > current_time( 'timestamp', true ) ) {
            echo ' <strong class="pvdak-case-alert">زمان آینده</strong>';
        }
    }
}
add_action( 'manage_pvdak_request_posts_custom_column', 'pvdak_core_case_admin_column_content', 30, 2 );


/**
 * Operational list enhancements. These are read-only views, not state changes.
 * Never show request bodies, private notes, or client contact details.
 */
function pvdak_core_case_ops_filters() {
    $choices = array(
        'pvdak_case_assignment' => array( 'assigned', 'unassigned' ),
        'pvdak_case_consistency' => array( 'matched_missing', 'assigned_pending' ),
        'pvdak_case_modified' => array( 'last1', 'last7', 'older7', 'older30', 'future' ),
    );
    $result = array();
    foreach ( $choices as $key => $allowed ) {
        $result[ $key ] = pvdak_core_case_admin_choice_filter( $key, $allowed );
    }
    return $result;
}

/** A numeric profile ID is required; legacy blank/zero values are unassigned. */
function pvdak_core_case_ops_assigned_condition( $assigned ) {
    $valid = array(
        'key' => '_pvdak_assigned_profile_id',
        'value' => '^[1-9][0-9]*$',
        'compare' => 'REGEXP',
    );
    if ( $assigned ) { return $valid; }
    return array( 'relation' => 'OR',
        array( 'key' => '_pvdak_assigned_profile_id', 'compare' => 'NOT EXISTS' ),
        array( 'key' => '_pvdak_assigned_profile_id', 'value' => '^[1-9][0-9]*$', 'compare' => 'NOT REGEXP' ),
    );
}

function pvdak_core_case_ops_controls( $post_type ) {
    if ( 'pvdak_request' !== $post_type || ! pvdak_core_case_admin_allowed() ) { return; }
    $f = pvdak_core_case_ops_filters();
    pvdak_core_case_admin_select( 'pvdak_case_assignment', 'ارجاع داخلی', array(
        'assigned' => 'دارای ارجاع داخلی', 'unassigned' => 'بدون ارجاع داخلی',
    ), $f['pvdak_case_assignment'] );
    pvdak_core_case_admin_select( 'pvdak_case_consistency', 'بررسی ناسازگاری', array(
        'matched_missing' => 'ارجاع‌شده بدون پرونده حرفه‌ای',
        'assigned_pending' => 'در انتظار با ارجاع ثبت‌شده',
    ), $f['pvdak_case_consistency'] );
    pvdak_core_case_admin_select( 'pvdak_case_modified', 'آخرین فعالیت', array(
        'last1' => 'ویرایش در ۲۴ ساعت اخیر',
        'last7' => 'ویرایش در ۷ روز اخیر',
        'older7' => 'بدون ویرایش در بیش از ۷ روز',
        'older30' => 'بدون ویرایش در بیش از ۳۰ روز',
        'future' => 'زمان ویرایش در آینده',
    ), $f['pvdak_case_modified'] );
    echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=pvdak_request&page=pvdak-request-triage' ) ) .
        '">نمای صف بررسی</a>';
    if ( in_array( '-1', $f, true ) ) {
        echo '<span class="pvdak-case-active-filters" role="status">فیلتر عملیاتی نامعتبر است؛ هیچ پرونده‌ای نمایش داده نمی‌شود.</span>';
    } elseif ( array_filter( $f ) ) {
        echo '<span class="pvdak-case-active-filters" role="status">فیلتر عملیاتی فعال است</span>';
    }
}
add_action( 'restrict_manage_posts', 'pvdak_core_case_ops_controls', 35 );

/** Extend the existing WP list query; never replace other plugins' conditions. */
function pvdak_core_case_ops_query( $query ) {
    global $pagenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ||
        'pvdak_request' !== $query->get( 'post_type' ) || ! pvdak_core_case_admin_allowed() ) { return; }
    $f = pvdak_core_case_ops_filters();
    if ( in_array( '-1', $f, true ) ) {
        $query->set( 'post__in', array( 0 ) );
        return; // Malformed operational selectors must never widen the private list.
    }
    if ( ! array_filter( $f ) ) { return; }
    // When a custom operational filter is selected, default to private cases;
    // preserve explicit native WordPress status tabs (draft/trash/etc.).
    if ( ! $query->get( 'post_status' ) &&
        ( ! isset( $_GET['post_status'] ) || '' === $_GET['post_status'] ) ) {
        $query->set( 'post_status', 'private' );
    }
    $conditions = array();
    if ( $f['pvdak_case_assignment'] ) {
        $conditions[] = pvdak_core_case_ops_assigned_condition( 'assigned' === $f['pvdak_case_assignment'] );
    }
    if ( 'matched_missing' === $f['pvdak_case_consistency'] ) {
        $conditions[] = array( 'key' => '_pvdak_request_state', 'value' => 'matched', 'compare' => '=' );
        $conditions[] = pvdak_core_case_ops_assigned_condition( false );
    } elseif ( 'assigned_pending' === $f['pvdak_case_consistency'] ) {
        $conditions[] = array( 'key' => '_pvdak_request_state',
            'value' => array( 'submitted', 'review' ), 'compare' => 'IN' );
        $conditions[] = pvdak_core_case_ops_assigned_condition( true );
    }
    if ( $conditions ) {
        $existing = $query->get( 'meta_query' );
        $combined = array( 'relation' => 'AND' );
        if ( is_array( $existing ) && $existing ) { $combined[] = $existing; }
        foreach ( $conditions as $condition ) { $combined[] = $condition; }
        $query->set( 'meta_query', $combined );
    }
    $modified = $f['pvdak_case_modified'];
    if ( $modified ) {
        $now = current_datetime();
        $date = array( 'column' => 'post_modified', 'inclusive' => false );
        if ( 'future' === $modified ) {
            $date['after'] = $now->format( 'Y-m-d H:i:s' );
        } else {
            $days = in_array( $modified, array( 'last1', 'last7', 'older7' ), true ) ?
                ( 'last1' === $modified ? 1 : 7 ) : 30;
            $cutoff = pvdak_core_triage_cutoff( $days, $now );
            if ( 0 === strpos( $modified, 'last' ) ) {
                $date['after'] = $cutoff;
                $date['inclusive'] = true;
            } else {
                $date['before'] = $cutoff;
            }
        }
        $existing = $query->get( 'date_query' );
        $combined = array( 'relation' => 'AND' );
        if ( is_array( $existing ) && $existing ) { $combined[] = $existing; }
        $combined[] = $date;
        $query->set( 'date_query', $combined );
    }
}
add_action( 'pre_get_posts', 'pvdak_core_case_ops_query', 40 );

/** Compact summaries only; per-record capability checks prevent disclosure. */
function pvdak_core_case_ops_columns( $columns ) {
    if ( ! pvdak_core_case_admin_allowed() ) { return $columns; }
    // The original Core list already supplies these fields; avoid three duplicate columns.
    unset( $columns['pvdak_kind'], $columns['pvdak_state'], $columns['pvdak_owner'] );
    $columns['pvdak_case_kind'] = 'نوع خدمت';
    $columns['pvdak_case_state'] = 'وضعیت رسیدگی';
    $columns['pvdak_case_assignee'] = 'ارجاع داخلی';
    $columns['pvdak_case_author'] = 'شناسه ثبت‌کننده';
    return $columns;
}
add_filter( 'manage_pvdak_request_posts_columns', 'pvdak_core_case_ops_columns', 40 );

function pvdak_core_case_ops_column_content( $column, $post_id ) {
    if ( ! pvdak_core_case_admin_allowed() || ! current_user_can( 'edit_post', $post_id ) ||
        'pvdak_request' !== get_post_type( $post_id ) ) { return; }
    if ( 'pvdak_case_kind' === $column ) {
        $types = pvdak_core_request_types();
        $type = get_post_meta( $post_id, '_pvdak_request_type', true );
        echo esc_html( is_string( $type ) && isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص / قدیمی' );
    } elseif ( 'pvdak_case_state' === $column ) {
        $statuses = pvdak_core_request_statuses();
        $state = get_post_meta( $post_id, '_pvdak_request_state', true );
        echo esc_html( is_string( $state ) && isset( $statuses[ $state ] ) ? $statuses[ $state ] : 'نامعتبر / نیازمند بررسی' );
    } elseif ( 'pvdak_case_assignee' === $column ) {
        $raw_profile_id = get_post_meta( $post_id, '_pvdak_assigned_profile_id', true );
        $profile_id = pvdak_core_assignment_positive_id( $raw_profile_id );
        $invalid_assignment = ! in_array( $raw_profile_id, array( '', '0', 0, false, null ), true ) && ! $profile_id;
        $state = get_post_meta( $post_id, '_pvdak_request_state', true );
        if ( ! $profile_id ) {
            echo 'matched' === $state || $invalid_assignment ?
                '<strong class="pvdak-case-alert">ارجاع ناقص یا نامعتبر؛ بررسی شود</strong>' : '—';
        } else {
            echo '<code>#' . esc_html( $profile_id ) . '</code>';
            if ( 'pvdak_professional' !== get_post_type( $profile_id ) ||
                 ! in_array( get_post_status( $profile_id ), array( 'private', 'draft' ), true ) ) {
                echo ' <strong class="pvdak-case-alert">پرونده حرفه‌ای نامعتبر</strong>';
            } elseif ( current_user_can( 'edit_post', $profile_id ) ) {
                $link = get_edit_post_link( $profile_id );
                if ( $link ) { echo ' <a href="' . esc_url( $link ) . '">پرونده حرفه‌ای</a>'; }
            }
            if ( in_array( $state, array( 'submitted', 'review' ), true ) ) {
                echo ' <strong class="pvdak-case-alert">وضعیت ناسازگار</strong>';
            }
        }
    } elseif ( 'pvdak_case_author' === $column ) {
        $owner = absint( get_post_field( 'post_author', $post_id ) );
        echo $owner ? '<code>#' . esc_html( $owner ) . '</code>'
            : '<strong class="pvdak-case-alert">ثبت‌کننده نامشخص</strong>';
    }
}
add_action( 'manage_pvdak_request_posts_custom_column', 'pvdak_core_case_ops_column_content', 40, 2 );

/** Reuse the already-existing assignment review screen; no new write route. */
function pvdak_core_case_ops_row_actions( $actions, $post ) {
    if ( ! pvdak_core_case_admin_allowed() || ! isset( $post->ID, $post->post_type ) ||
        'pvdak_request' !== $post->post_type || 'private' !== $post->post_status ||
        ! current_user_can( 'edit_post', $post->ID ) ) { return $actions; }
    $actions['pvdak_review_assignment'] = '<a href="' .
        esc_url( admin_url( 'admin.php?page=pvdak-assign-request&request_id=' . absint( $post->ID ) ) ) .
        '">بررسی ارجاع</a>';
    return $actions;
}
add_filter( 'post_row_actions', 'pvdak_core_case_ops_row_actions', 30, 2 );

function pvdak_core_case_ops_admin_css() {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'edit-pvdak_request' !== $screen->id || ! pvdak_core_case_admin_allowed() ) { return; }
    echo '<style>.pvdak-case-alert{color:#a43c15}.pvdak-case-active-filters{display:inline-block;padding:5px 8px;color:#1d3550;font-weight:600}';
    echo '@media(max-width:782px){.pvdak-case-active-filters{display:block;margin:6px 0}}</style>';
}
add_action( 'admin_head-edit.php', 'pvdak_core_case_ops_admin_css' );
