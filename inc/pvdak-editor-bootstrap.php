<?php
/**
 * PVDak editorial content starter: native Pages and Post Categories only.
 * It does not install plugins, publish articles, change users or expose
 * legal request forms. Admin must explicitly click the nonce-protected form.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_editor_find_or_create_page( $slug, $title, $status = 'publish', $parent = 0, $template = '' ) {
    $path = $parent ? get_post_field( 'post_name', $parent ) . '/' . $slug : $slug;
    $existing = get_page_by_path( $path, OBJECT, 'page' );
    if ( $existing && 'trash' !== $existing->post_status ) {
        return (int) $existing->ID; // Preserve ALL existing content/status/template.
    }
    $args = array(
        'post_type' => 'page',
        'post_title' => $title,
        'post_name' => $slug,
        'post_parent' => absint( $parent ),
        'post_status' => $status,
        'post_content' => '', // Client owns content and SEO text.
        'post_author' => get_current_user_id(),
    );
    if ( $template && is_readable( get_template_directory() . '/' . $template ) ) {
        $args['page_template'] = $template;
    }
    $id = wp_insert_post( $args, true );
    return is_wp_error( $id ) ? 0 : (int) $id;
}

function pvdak_editor_find_or_create_category( $slug, $title ) {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( $term && ! is_wp_error( $term ) ) { return (int) $term->term_id; }
    $inserted = wp_insert_term( $title, 'category', array( 'slug' => $slug ) );
    return is_wp_error( $inserted ) ? 0 : (int) $inserted['term_id'];
}

/** Don't overwrite selections already made by the site's editor. */
function pvdak_editor_prefill_acf( $home_id, $field, $value ) {
    if ( ! $home_id || ! $value || ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
        return false;
    }
    $existing = get_field( $field, $home_id );
    if ( ! empty( $existing ) ) { return false; }
    return (bool) update_field( 'field_pvdak_home_' . $field, (int) $value, $home_id );
}

/** A permanent admin guide with an explicit opt-in starter action. */
function pvdak_editor_bootstrap_card() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $topic = get_page_by_path( 'legal-topics', OBJECT, 'page' );
    $faq = get_page_by_path( 'legal-faq', OBJECT, 'page' );
    echo '<div class="postbox" style="max-width:1100px;margin:18px 0"><div class="inside" style="padding:14px 20px">';
    echo '<h2>راه‌اندازی ساختار محتوایی برای کارفرما</h2>';
    echo '<p>محتوای سایت در خودِ وردپرس مدیریت می‌شود. این ابزار یک‌بار برگه‌های مادر «موضوعات حقوقی» و «پرسش‌های متداول» را همراه دسته‌های مجله، اخبار و اطلاعیه‌ها می‌سازد؛ چهار لندینگ نمونه فقط به شکل <strong>پیش‌نویس</strong> ایجاد می‌شوند.</p>';
    if ( $topic || $faq ) {
        echo '<p>برگه‌های موجود بدون تغییر عنوان، محتوا، وضعیت انتشار یا قالب حفظ می‌شوند. این عملیات چندبار قابل اجراست و مطالب موجود را بازنویسی نمی‌کند.</p>';
    }
    if ( isset( $_GET['pvdak_structure'] ) && '1' === (string) sanitize_text_field( wp_unslash( $_GET['pvdak_structure'] ) ) ) {
        echo '<div class="notice notice-success inline"><p>ساختار محتوایی بررسی شد؛ موارد گمشده ایجاد شدند. اکنون برگه‌ها و دسته‌ها را از میان‌برهای بالا مدیریت کنید.</p></div>';
    }
    if ( isset( $_GET['pvdak_structure'] ) && 'error' === (string) sanitize_text_field( wp_unslash( $_GET['pvdak_structure'] ) ) ) {
        echo '<div class="notice notice-error inline"><p>ایجاد بخشی از ساختار کامل نشد. دسترسی‌ها و گزارش‌های وردپرس را بررسی کنید.</p></div>';
    }
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="pvdak_editor_prepare_structure">';
    wp_nonce_field( 'pvdak_editor_prepare_structure' );
    submit_button( 'آماده‌سازی ساختار محتوایی (بدون انتشار لندینگ‌های نمونه)', 'secondary' );
    echo '</form>';
    echo '<p><small>برگه‌های مادر تازه در وضعیت منتشرشده ساخته می‌شوند تا در ACF قابل انتخاب باشند؛ محتوای حقوقی نمونه منتشر نمی‌شود. این ابزار تنظیمات Cron، پرداخت، نقش‌ها یا دیتابیس هسته خصوصی را تغییر نمی‌دهد.</small></p>';
    echo '</div></div>';
}

function pvdak_editor_prepare_structure() {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) ||
         ! current_user_can( 'manage_categories' ) ) {
        wp_die( 'اجازه مدیریت این بخش را ندارید.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_editor_prepare_structure' );

    $topic = pvdak_editor_find_or_create_page(
        'legal-topics', 'موضوعات حقوقی', 'publish', 0,
        'page-templates/legal-topics-index.php'
    );
    $faq = pvdak_editor_find_or_create_page( 'legal-faq', 'پرسش‌های متداول' );
    $categories = array(
        'magazine_category' => array( 'legal-magazine', 'مجله حقوقی' ),
        'notice_category' => array( 'pvdak-notices', 'اطلاعیه‌های پویش' ),
        'news_category' => array( 'pvdak-news', 'اخبار پویش' ),
    );
    $ids = array();
    foreach ( $categories as $field => $parts ) {
        $ids[ $field ] = pvdak_editor_find_or_create_category( $parts[0], $parts[1] );
    }

    if ( $topic ) {
        // Illustrative Page drafts, never published automatically.
        $samples = array(
            'family-law' => 'حقوق خانواده',
            'property-law' => 'دعاوی ملکی',
            'criminal-law' => 'دعاوی کیفری',
            'contracts-law' => 'قراردادها و تعهدات',
        );
        foreach ( $samples as $slug => $title ) {
            pvdak_editor_find_or_create_page(
                $slug, $title, 'draft', $topic,
                'page-templates/legal-topic-landing.php'
            );
        }
    }
    $home_id = function_exists( 'pvdak_home_page_id' ) ? pvdak_home_page_id() : 0;
    if ( $home_id ) {
        if ( $topic && 'publish' === get_post_status( $topic ) ) {
            pvdak_editor_prefill_acf( $home_id, 'legal_topics_parent_page', $topic );
        }
        if ( $faq && 'publish' === get_post_status( $faq ) ) {
            pvdak_editor_prefill_acf( $home_id, 'faq_parent_page', $faq );
        }
        foreach ( $ids as $field => $term_id ) {
            pvdak_editor_prefill_acf( $home_id, $field, $term_id );
        }
    }
    $ok = $topic && $faq && ! in_array( 0, array_values( $ids ), true );
    wp_safe_redirect( admin_url( 'admin.php?page=pvdak-home-content&pvdak_structure=' . ( $ok ? '1' : 'error' ) ) );
    exit;
}
add_action( 'admin_post_pvdak_editor_prepare_structure', 'pvdak_editor_prepare_structure' );
