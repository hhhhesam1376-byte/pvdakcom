<?php
/**
 * Native WordPress PAGES represent legal subjects and SEO landings.
 * A specialty is a reference to a published child page, not a taxonomy or
 * a duplicated ACF title. This module belongs to the durable Core plugin.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * D23: Never coerce forged negative/overflowed IDs into another published
 * legal landing. Use the same strict bounded decimal representation in
 * request-topic matching, saved profile specialties and admin submissions.
 */
function pvdak_core_specialty_page_id( $value ) {
    if ( ! is_string( $value ) && ! is_int( $value ) ) { return 0; }
    $raw = (string) $value;
    if ( ! preg_match( '/^[1-9][0-9]{0,9}$/D', $raw ) ||
         (float) $raw > 2147483647 ) { return 0; }
    return (int) $raw;
}

function pvdak_core_landing_parent_id() {
    // A negative or malformed option must never be remapped by absint() to
    // a real published legal Page. Reuse the strict D23 Page-ID parser.
    $id = pvdak_core_specialty_page_id( get_option( 'pvdak_core_landing_parent_id', 0 ) );
    if ( $id && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
        return $id;
    }
    // If the theme's ACF home editor already chooses a root, reuse it.
    // The Core plugin does not require the theme or ACF to be active.
    if ( function_exists( 'get_field' ) ) {
        $front_id = pvdak_core_specialty_page_id( get_option( 'page_on_front', 0 ) );
        $acf_page = $front_id ? get_field( 'legal_topics_parent_page', $front_id ) : 0;
        $acf_id = pvdak_core_specialty_page_id(
            $acf_page instanceof WP_Post ? $acf_page->ID : $acf_page
        );
        if ( $acf_id && 'page' === get_post_type( $acf_id ) && 'publish' === get_post_status( $acf_id ) ) {
            return $acf_id;
        }
    }
    $default = get_page_by_path( 'legal-topics', OBJECT, 'page' );
    $default_id = $default instanceof WP_Post ? pvdak_core_specialty_page_id( $default->ID ) : 0;
    return $default_id && 'page' === get_post_type( $default_id ) &&
        'publish' === get_post_status( $default_id ) ? $default_id : 0;
}

function pvdak_core_specialty_pages() {
    $parent_id = pvdak_core_landing_parent_id();
    if ( ! $parent_id ) { return array(); }
    // Legal taxonomy is a Page tree: a specialist may serve a grandchild
    // (e.g., public joint-stock company below company law), not only a
    // direct child of the top-level legal-topics landing.
    $pages = get_pages( array(
        'child_of' => $parent_id,
        'post_status' => 'publish',
        'number' => 200,
        'sort_column' => 'menu_order,post_title',
        'hierarchical' => true,
    ) );
    if ( ! is_array( $pages ) ) { return array(); }
    return array_values( array_filter( $pages, function ( $page ) {
        return $page instanceof WP_Post &&
            pvdak_core_valid_specialty( $page->ID );
    } ) );
}

function pvdak_core_valid_specialty( $page_id ) {
    $page_id = pvdak_core_specialty_page_id( $page_id );
    $parent_id = pvdak_core_landing_parent_id();
    if ( ! $parent_id || ! $page_id || $page_id === $parent_id ) { return false; }
    $seen = array();
    // The tree must terminate at the configured published root. Cycles,
    // unpublished intermediate parents and extreme depths fail closed.
    for ( $depth = 0; $depth < 20; $depth++ ) {
        if ( isset( $seen[ $page_id ] ) ||
             'page' !== get_post_type( $page_id ) ||
             'publish' !== get_post_status( $page_id ) ) { return false; }
        $seen[ $page_id ] = true;
        $direct_parent = pvdak_core_specialty_page_id( wp_get_post_parent_id( $page_id ) );
        if ( ! $direct_parent ) { return false; }
        if ( $direct_parent === $parent_id ) { return true; }
        $page_id = $direct_parent;
    }
    return false;
}

/**
 * Resolve a request's legal subject against the CURRENT published Page tree.
 * IDs, not titles or slugs, are the sole matching key. Never expose private
 * request text or the titles of unpublished Pages in a diagnostic.
 */
function pvdak_core_topic_resolution( $page_id ) {
    if ( null === $page_id || 0 === $page_id || '0' === $page_id || '' === $page_id ) {
        return array( 'status' => 'missing', 'message' => 'موضوع حقوقی برای این درخواست ثبت نشده است؛ مدیر باید داده پرونده را بررسی کند.', 'title' => '' );
    }
    $page_id = pvdak_core_specialty_page_id( $page_id );
    if ( ! $page_id ) {
        return array( 'status' => 'invalid', 'message' => 'شناسه موضوع حقوقی نامعتبر است؛ پیش از ارجاع، مدیر باید داده پرونده را اصلاح کند.', 'title' => '' );
    }
    if ( ! pvdak_core_landing_parent_id() ) {
        return array( 'status' => 'parent_missing', 'message' => 'برگه مادر موضوعات حقوقی منتشر یا تنظیم نشده است؛ ابتدا تنظیمات موضوعات را اصلاح کنید.', 'title' => '' );
    }
    if ( ! pvdak_core_valid_specialty( $page_id ) ) {
        return array( 'status' => 'invalid', 'message' => 'برگه موضوع این درخواست حذف، پیش‌نویس یا از زیرمجموعه موضوعات حقوقی خارج شده است؛ نیازمند بررسی دستی است.', 'title' => '' );
    }
    // A malformed non-string title must not become the literal word "Array"\n    // and incorrectly certify a legal topic as ready for internal matching.\n    $raw_title = get_post_field( 'post_title', $page_id );\n    $title = is_string( $raw_title ) ? trim( $raw_title ) : '';
    if ( '' === $title ) {
        return array( 'status' => 'untitled', 'message' => 'برگه موضوع حقوقی عنوان ندارد؛ پیش از ارجاع باید عنوان برگه تکمیل شود.', 'title' => '' );
    }
    return array( 'status' => 'ready', 'message' => '', 'title' => $title );
}

function pvdak_core_specialty_ids( $profile_id ) {
    $ids = get_post_meta( absint( $profile_id ), '_pvdak_specialty_page_ids', true );
    if ( ! is_array( $ids ) ) { return array(); }
    $valid = array();
    foreach ( array_slice( $ids, 0, 200 ) as $raw ) {
        $id = pvdak_core_specialty_page_id( $raw );
        if ( $id && 'ready' === pvdak_core_topic_resolution( $id )['status'] ) { $valid[ $id ] = $id; }
    }
    return array_values( $valid );
}

/**
 * Administrative approval alone is insufficient. A separate manual clearance
 * must be checked after independent review. It is NOT license verification
 * from an official registry and must never be represented as such publicly.
 */
/**
 * A reviewed profile must still belong to a real WordPress account.
 * Imported/orphaned profiles (author 0 or a deleted user) fail closed.
 */
function pvdak_core_profile_has_owner( $profile_id ) {
    $owner_id = absint( get_post_field( 'post_author', absint( $profile_id ) ) );
    if ( ! $owner_id ) { return false; }
    $owner = get_userdata( $owner_id );
    return $owner && isset( $owner->ID ) && (int) $owner->ID === $owner_id;
}

function pvdak_core_profile_referral_eligible( $profile_id, $specialty_id, $request_type = 'lawyer' ) {
    if ( 'pvdak_professional' !== get_post_type( $profile_id ) ||
         'private' !== get_post_status( $profile_id ) ||
         'ready' !== pvdak_core_topic_resolution( $specialty_id )['status'] ||
         ! pvdak_core_profile_has_owner( $profile_id ) ) { return false; }
    if ( 'approved' !== get_post_meta( $profile_id, '_pvdak_member_state', true ) ||
         '1' !== (string) get_post_meta( $profile_id, '_pvdak_referral_cleared', true ) ||
         ! pvdak_core_license_review_current( $profile_id ) ) { return false; }
    // No trainee/student matching without a separate supervision policy.
    if ( 'lawyer' !== get_post_meta( $profile_id, '_pvdak_member_type', true ) ) { return false; }
    return in_array( $request_type, array( 'lawyer', 'consultation', 'question' ), true ) &&
        in_array( pvdak_core_specialty_page_id( $specialty_id ), pvdak_core_specialty_ids( $profile_id ), true );
}

function pvdak_core_specialty_metabox() {
    add_meta_box(
        'pvdak_professional_specialties',
        'حوزه‌های تخصصی و مجوز ارجاع داخلی',
        'pvdak_core_specialty_metabox_html',
        'pvdak_professional',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'pvdak_core_specialty_metabox' );

function pvdak_core_specialty_metabox_html( $post ) {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
    wp_nonce_field( 'pvdak_core_specialties_' . $post->ID, '_pvdak_specialties_nonce' );
    $selected = pvdak_core_specialty_ids( $post->ID );
    echo '<p>موضوعات حقوقی مستقیماً از برگه‌های فرزند «موضوعات حقوقی» خوانده می‌شوند. فقط مدیر می‌تواند حوزه‌های قابل ارجاع را تعیین کند.</p>';
    $pages = array_values( array_filter( pvdak_core_specialty_pages(), function ( $page ) {
        return $page instanceof WP_Post && 'ready' === pvdak_core_topic_resolution( $page->ID )['status'];
    } ) );
    if ( ! $pages ) {
        echo '<p>ابتدا برگه مادر موضوعات حقوقی و لندینگ‌های زیرمجموعه را منتشر کنید و در تنظیمات PVDak Core برگه مادر را انتخاب کنید.</p>';
    } else {
        echo '<fieldset><legend class="screen-reader-text">موضوعات تخصص</legend>';
        foreach ( $pages as $page ) {
            echo '<label style="display:inline-block;margin:7px 0 7px 20px"><input type="checkbox" name="pvdak_specialty_page_ids[]" value="' .
                esc_attr( $page->ID ) . '" ' . checked( in_array( (int) $page->ID, $selected, true ), true, false ) .
                '> ' . esc_html( $page->post_title ) . '</label>';
        }
        echo '</fieldset>';
    }
    $cleared = '1' === (string) get_post_meta( $post->ID, '_pvdak_referral_cleared', true );
    echo '<hr><p><strong>تأیید داخلی امکان ارجاع</strong></p>';
    echo '<label><input type="checkbox" name="pvdak_referral_cleared" value="1" ' .
        checked( $cleared, true, false ) . '> بررسی مستقل شرایط حرفه‌ای توسط مدیر انجام شده و این درخواست فقط برای ارجاع داخلی تأیید است.</label>';
    echo '<p class="description">این علامت به‌تنهایی مجوز وکالت صادر نمی‌کند، جایگزین استعلام پروانه معتبر نیست و مشخصات را عمومی نمی‌کند. فقط برای متقاضی نوع «وکیل» با وضعیت «تأیید اداری» و تخصص مرتبط اثر دارد.</p>';
}

function pvdak_core_save_specialties( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
         ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) ||
         'pvdak_professional' !== get_post_type( $post_id ) ||
         'private' !== get_post_status( $post_id ) || ! pvdak_core_profile_has_owner( $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_specialties_nonce'] ) || ! is_string( $_POST['_pvdak_specialties_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_specialties_nonce'] ) ), 'pvdak_core_specialties_' . $post_id ) ) { return; }
    if ( isset( $_POST['pvdak_specialty_page_ids'] ) && ! is_array( $_POST['pvdak_specialty_page_ids'] ) ) { return; }
    $raw = isset( $_POST['pvdak_specialty_page_ids'] ) ?
        wp_unslash( $_POST['pvdak_specialty_page_ids'] ) : array();
    // A forged/obsolete selection must not silently wipe or remap the
    // previous specialties. An actually empty checkbox set can still clear.
    if ( ! is_array( $raw ) || count( $raw ) > 200 ) { return; }
    $ids = array();
    foreach ( $raw as $raw_id ) {
        $id = pvdak_core_specialty_page_id( $raw_id );
        if ( ! $id || 'ready' !== pvdak_core_topic_resolution( $id )['status'] ) { return; }
        $ids[ $id ] = $id;
    }
    $next_ids = array_values( $ids );
    $old_ids = get_post_meta( $post_id, '_pvdak_specialty_page_ids', true );
    if ( $old_ids !== $next_ids &&
         false === update_post_meta( $post_id, '_pvdak_specialty_page_ids', $next_ids ) ) { return; }
    if ( get_post_meta( $post_id, '_pvdak_specialty_page_ids', true ) !== $next_ids ) { return; }
    $cleared = isset( $_POST['pvdak_referral_cleared'] ) &&
        is_string( $_POST['pvdak_referral_cleared'] ) &&
        '1' === wp_unslash( $_POST['pvdak_referral_cleared'] ) ? '1' : '0';
    $old = (string) get_post_meta( $post_id, '_pvdak_referral_cleared', true );
    if ( $old !== $cleared ) {
        if ( false === update_post_meta( $post_id, '_pvdak_referral_cleared', $cleared ) ||
             $cleared !== (string) get_post_meta( $post_id, '_pvdak_referral_cleared', true ) ) {
            update_post_meta( $post_id, '_pvdak_referral_cleared', '0' );
            return;
        }
        if ( false === add_post_meta( $post_id, '_pvdak_referral_clearance_audit', array(
            'from' => $old,
            'to' => $cleared,
            'by' => get_current_user_id(),
            'at' => current_time( 'mysql', true ),
        ), false ) ) {
            // Missing audit cannot leave a newly granted clearance active.
            update_post_meta( $post_id, '_pvdak_referral_cleared', '0' );
        }
    }
}
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_specialties' );
