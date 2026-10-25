<?php
/**
 * Native WordPress PAGES represent legal subjects and SEO landings.
 * A specialty is a reference to a published child page, not a taxonomy or
 * a duplicated ACF title. This module belongs to the durable Core plugin.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_landing_parent_id() {
    $id = absint( get_option( 'pvdak_core_landing_parent_id', 0 ) );
    if ( $id && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
        return $id;
    }
    // If the theme's ACF home editor already chooses a root, reuse it.
    // The Core plugin does not require the theme or ACF to be active.
    if ( function_exists( 'get_field' ) ) {
        $front_id = absint( get_option( 'page_on_front' ) );
        $acf_page = $front_id ? get_field( 'legal_topics_parent_page', $front_id ) : 0;
        $acf_id = $acf_page instanceof WP_Post ? (int) $acf_page->ID : absint( $acf_page );
        if ( $acf_id && 'page' === get_post_type( $acf_id ) && 'publish' === get_post_status( $acf_id ) ) {
            return $acf_id;
        }
    }
    $default = get_page_by_path( 'legal-topics', OBJECT, 'page' );
    return $default && 'publish' === $default->post_status ? (int) $default->ID : 0;
}

function pvdak_core_specialty_pages() {
    $parent_id = pvdak_core_landing_parent_id();
    if ( ! $parent_id ) { return array(); }
    return get_posts( array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_parent' => $parent_id,
        'posts_per_page' => 200,
        'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
        'order' => 'ASC',
    ) );
}

function pvdak_core_valid_specialty( $page_id ) {
    $page_id = absint( $page_id );
    $parent_id = pvdak_core_landing_parent_id();
    return $parent_id && $page_id && 'page' === get_post_type( $page_id ) &&
        'publish' === get_post_status( $page_id ) &&
        $parent_id === (int) wp_get_post_parent_id( $page_id );
}

/**
 * Resolve a request's legal subject against the CURRENT published Page tree.
 * IDs, not titles or slugs, are the sole matching key. Never expose private
 * request text or the titles of unpublished Pages in a diagnostic.
 */
function pvdak_core_topic_resolution( $page_id ) {
    $page_id = absint( $page_id );
    if ( ! $page_id ) {
        return array( 'status' => 'missing', 'message' => 'موضوع حقوقی برای این درخواست ثبت نشده است؛ مدیر باید داده پرونده را بررسی کند.', 'title' => '' );
    }
    if ( ! pvdak_core_landing_parent_id() ) {
        return array( 'status' => 'parent_missing', 'message' => 'برگه مادر موضوعات حقوقی منتشر یا تنظیم نشده است؛ ابتدا تنظیمات موضوعات را اصلاح کنید.', 'title' => '' );
    }
    if ( ! pvdak_core_valid_specialty( $page_id ) ) {
        return array( 'status' => 'invalid', 'message' => 'برگه موضوع این درخواست حذف، پیش‌نویس یا از زیرمجموعه موضوعات حقوقی خارج شده است؛ نیازمند بررسی دستی است.', 'title' => '' );
    }
    $title = trim( (string) get_post_field( 'post_title', $page_id ) );
    if ( '' === $title ) {
        return array( 'status' => 'untitled', 'message' => 'برگه موضوع حقوقی عنوان ندارد؛ پیش از ارجاع باید عنوان برگه تکمیل شود.', 'title' => '' );
    }
    return array( 'status' => 'ready', 'message' => '', 'title' => $title );
}

function pvdak_core_specialty_ids( $profile_id ) {
    $ids = get_post_meta( (int) $profile_id, '_pvdak_specialty_page_ids', true );
    if ( ! is_array( $ids ) ) { return array(); }
    return array_values( array_unique( array_filter( array_map( 'absint', $ids ), function ( $id ) {
        return 'ready' === pvdak_core_topic_resolution( $id )['status'];
    } ) ) );
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
        in_array( absint( $specialty_id ), pvdak_core_specialty_ids( $profile_id ), true );
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
    if ( ! current_user_can( 'edit_post', $post->ID ) ) { return; }
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
         ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( ! isset( $_POST['_pvdak_specialties_nonce'] ) || ! is_string( $_POST['_pvdak_specialties_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_pvdak_specialties_nonce'] ) ), 'pvdak_core_specialties_' . $post_id ) ) { return; }
    $raw = isset( $_POST['pvdak_specialty_page_ids'] ) && is_array( $_POST['pvdak_specialty_page_ids'] ) ?
        wp_unslash( $_POST['pvdak_specialty_page_ids'] ) : array();
    $ids = array();
    foreach ( array_slice( $raw, 0, 200 ) as $raw_id ) {
        if ( ! is_scalar( $raw_id ) ) { continue; }
        $id = absint( $raw_id );
        if ( 'ready' === pvdak_core_topic_resolution( $id )['status'] ) { $ids[ $id ] = $id; }
    }
    update_post_meta( $post_id, '_pvdak_specialty_page_ids', array_values( $ids ) );
    $cleared = isset( $_POST['pvdak_referral_cleared'] ) &&
        is_string( $_POST['pvdak_referral_cleared'] ) &&
        '1' === wp_unslash( $_POST['pvdak_referral_cleared'] ) ? '1' : '0';
    $old = (string) get_post_meta( $post_id, '_pvdak_referral_cleared', true );
    if ( $old !== $cleared ) {
        update_post_meta( $post_id, '_pvdak_referral_cleared', $cleared );
        add_post_meta( $post_id, '_pvdak_referral_clearance_audit', array(
            'from' => $old,
            'to' => $cleared,
            'by' => get_current_user_id(),
            'at' => current_time( 'mysql', true ),
        ), false );
    }
}
add_action( 'save_post_pvdak_professional', 'pvdak_core_save_specialties' );
