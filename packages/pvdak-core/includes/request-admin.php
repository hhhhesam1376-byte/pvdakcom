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

/** Validate all URL inputs before they can affect WP_Query. */
function pvdak_core_case_admin_filters() {
    $choices = array(
        'pvdak_case_age' => array( 'today', 'last7', 'older7', 'older30' ),
        'pvdak_case_notes' => array( 'yes', 'no' ),
        'pvdak_case_audit' => array( 'yes', 'no' ),
        'pvdak_case_order' => array( 'newest', 'oldest' ),
    );
    $result = array();
    foreach ( $choices as $name => $allowed ) {
        $raw = isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] )
            ? sanitize_key( wp_unslash( $_GET[ $name ] ) ) : '';
        $result[ $name ] = in_array( $raw, $allowed, true ) ? $raw : '';
    }
    foreach ( array( 'pvdak_case_id', 'pvdak_case_owner' ) as $name ) {
        $raw = isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] )
            ? trim( wp_unslash( $_GET[ $name ] ) ) : '';
        $result[ $name ] = preg_match( '/^[1-9][0-9]{0,9}$/D', $raw ) ? absint( $raw ) : 0;
    }
    $raw_topic = isset( $_GET['pvdak_case_topic'] ) && is_string( $_GET['pvdak_case_topic'] )
        ? trim( wp_unslash( $_GET['pvdak_case_topic'] ) ) : '';
    $topic = preg_match( '/^[1-9][0-9]{0,9}$/D', $raw_topic ) ? absint( $raw_topic ) : 0;
    $result['pvdak_case_topic'] = 'missing' === $raw_topic ? 'missing'
        : ( $topic && pvdak_core_valid_specialty( $topic ) ? $topic : '' );
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
            '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $f[ $name ] ?: '' ) .
            '" placeholder="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '">';
    }
    echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=pvdak_request' ) ) . '">پاک‌کردن فیلترهای صف</a>';
}
add_action( 'restrict_manage_posts', 'pvdak_core_case_admin_controls', 20 );

/** Combine with native WP and existing type/state filters rather than replacing them. */
function pvdak_core_case_admin_query( $query ) {
    global $pagenow;
    if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ||
        'pvdak_request' !== $query->get( 'post_type' ) || ! pvdak_core_case_admin_allowed() ) { return; }
    $f = pvdak_core_case_admin_filters();
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
        $query->set( 'author', $f['pvdak_case_owner'] );
    }
    $now = current_datetime();
    $age = $f['pvdak_case_age'];
    $date = array();
    if ( 'today' === $age ) {
        $date = array( 'after' => $now->format( 'Y-m-d' ), 'inclusive' => true );
    } elseif ( 'last7' === $age ) {
        $date = array( 'after' => $now->modify( '-7 days' )->format( 'Y-m-d' ), 'inclusive' => true );
    } elseif ( 'older7' === $age || 'older30' === $age ) {
        $date = array( 'before' => $now->modify( 'older7' === $age ? '-7 days' : '-30 days' )->format( 'Y-m-d' ), 'inclusive' => false );
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
        echo '<code>#' . esc_html( absint( $post_id ) ) . '</code>';
    } elseif ( 'pvdak_case_topic_column' === $column ) {
        $id = absint( get_post_meta( $post_id, '_pvdak_legal_landing_id', true ) );
        echo $id && pvdak_core_valid_specialty( $id ) ? esc_html( get_the_title( $id ) ) : 'ثبت‌نشده / قدیمی';
    } elseif ( 'pvdak_case_age_column' === $column ) {
        $timestamp = get_post_time( 'U', true, $post_id );
        echo $timestamp ? esc_html( human_time_diff( $timestamp, current_time( 'timestamp', true ) ) . ' پیش' ) : '—';
    } elseif ( 'pvdak_case_activity' === $column ) {
        $labels = array();
        if ( metadata_exists( 'post', $post_id, '_pvdak_internal_notes' ) ) { $labels[] = 'یادداشت داخلی'; }
        if ( metadata_exists( 'post', $post_id, '_pvdak_state_audit' ) ) { $labels[] = 'تغییر وضعیت'; }
        echo esc_html( $labels ? implode( '، ', $labels ) : 'بدون سابقه' );
    } elseif ( 'pvdak_case_modified' === $column ) {
        $modified = get_post_modified_time( 'Y-m-d H:i', false, $post_id );
        echo $modified ? '<time datetime="' . esc_attr( get_post_modified_time( 'c', false, $post_id ) ) .
            '">' . esc_html( $modified ) . '</time>' : '—';
    }
}
add_action( 'manage_pvdak_request_posts_custom_column', 'pvdak_core_case_admin_column_content', 30, 2 );
