<?php
/**
 * PVDak editorial content starter: native Pages and Post Categories only.
 * It does not install plugins, publish articles, change users or expose
 * legal request forms. Admin must explicitly click the nonce-protected form.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_editor_child_path( $parent_id, $slug ) {
    $parent_id = absint( $parent_id );
    if ( ! $parent_id ) { return $slug; }
    // WordPress Pages can be nested beyond one level. Resolve the full
    // hierarchy so the idempotent seed never duplicates an existing child.
    $uri = function_exists( 'get_page_uri' ) ? get_page_uri( $parent_id ) : '';
    $base = is_string( $uri ) && '' !== trim( $uri, '/' ) ?
        trim( $uri, '/' ) : get_post_field( 'post_name', $parent_id );
    return $base . '/' . $slug;
}

function pvdak_editor_find_or_create_page( $slug, $title, $status = 'publish', $parent = 0, $template = '' ) {
    $path = pvdak_editor_child_path( $parent, $slug );
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

/**
 * Preserve editor customizations while renaming the two original WordPress
 * category labels created by the old PVDak starter. No slug or post changes.
 */
function pvdak_editor_relabel_legacy_brand_categories() {
    if ( ! is_admin() || ! current_user_can( 'manage_categories' ) ) { return; }
    $legacy = array(
        'pvdak-notices' => array( 'اطلاعیه‌های پویش', 'اطلاعیه‌های PVDak' ),
        'pvdak-news' => array( 'اخبار پویش', 'اخبار PVDak' ),
    );
    foreach ( $legacy as $slug => $names ) {
        $term = get_term_by( 'slug', $slug, 'category' );
        if ( $term instanceof WP_Term && $term->name === $names[0] ) {
            wp_update_term( $term->term_id, 'category', array( 'name' => $names[1] ) );
        }
    }
}
add_action( 'admin_init', 'pvdak_editor_relabel_legacy_brand_categories', 30 );

/** Don't overwrite selections already made by the site's editor. */
function pvdak_editor_prefill_acf( $home_id, $field, $value ) {
    if ( ! $home_id || ! $value || ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
        return false;
    }
    $existing = get_field( $field, $home_id );
    if ( ! empty( $existing ) ) { return false; }
    return (bool) update_field( 'field_pvdak_home_' . $field, (int) $value, $home_id );
}

/**
 * Exact first-round topic labels requested for the site's legal-landing index.
 * They are Pages below /legal-topics/, NOT WordPress Post categories.
 * Keep related labels distinct until editors deliberately consolidate them.
 */
function pvdak_editor_example_legal_topics() {
    return array(
        'civil-law' => 'حقوقی',
        'criminal-cases' => 'کیفری',
        'family' => 'خانواده',
        'registrations' => 'ثبتی',
        'property-cases' => 'ملکی',
        'children-and-adolescents-rights' => 'حقوق اطفال و نوجوانان',
        'land-law' => 'حقوق اراضی',
        'tax-law' => 'حقوق مالیات',
        'human-rights' => 'حقوق بشر',
        'company-law' => 'حقوق شرکت ها',
        'intellectual-property-law' => 'حقوق مالکیت معنوی',
        'registration-law' => 'حقوق ثبت',
        'family-rights' => 'حقوق خانواده',
        'criminal-law-rights' => 'حقوق کیفری',
        'insurance-law' => 'حقوق بیمه',
        'sports-law' => 'حقوق ورزشی',
        'labor-law' => 'حقوق کار',
    );
}

/**
 * Publish EMPTY illustrative landing Pages so they participate in the theme's
 * existing search, cards and Core specialty selectors while maintenance is ON.
 * Existing Pages are never edited, republished, relabelled or overwritten.
 * Only newly created samples receive a marker (for noindex while empty).
 */
function pvdak_editor_seed_example_legal_topics( $parent_id ) {
    $result = array( 'created' => 0, 'existing' => 0, 'unpublished' => 0, 'failed' => 0 );
    $parent_id = absint( $parent_id );
    if ( ! $parent_id || 'publish' !== get_post_status( $parent_id ) ) {
        $result['failed'] = count( pvdak_editor_example_legal_topics() );
        return $result;
    }
    foreach ( pvdak_editor_example_legal_topics() as $slug => $title ) {
        $path = pvdak_editor_child_path( $parent_id, $slug );
        $existing = get_page_by_path( $path, OBJECT, 'page' );
        if ( $existing && 'trash' !== $existing->post_status ) {
            $result['existing']++;
            if ( 'publish' !== $existing->post_status ) {
                $result['unpublished']++;
            }
            continue;
        }
        $id = pvdak_editor_find_or_create_page(
            $slug, $title, 'publish', $parent_id,
            'page-templates/legal-topic-landing.php'
        );
        if ( $id && 'publish' === get_post_status( $id ) ) {
            update_post_meta( $id, '_pvdak_sample_landing', '1' );
            $result['created']++;
        } else {
            $result['failed']++;
        }
    }
    return $result;
}

/** Noindex only empty sample landing Pages; edited substantive Pages become indexable. */
function pvdak_editor_sample_landing_robots( $robots ) {
    if ( ! is_singular( 'page' ) ) { return $robots; }
    $post = get_queried_object();
    if ( $post instanceof WP_Post &&
         '1' === (string) get_post_meta( $post->ID, '_pvdak_sample_landing', true ) &&
         '' === trim( (string) $post->post_content ) ) {
        $robots['noindex'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_editor_sample_landing_robots' );

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
    echo '<p><small>ابزار بالا همچنان فقط چهار پیش‌نویس آموزشی ایجاد می‌کند و محتوای موجود را تغییر نمی‌دهد.</small></p>';
    echo '<hr><h3>۱۷ لندینگ نمونه برای شروع توسعه</h3>';
    echo '<p>این دکمه ۱۷ عنوان دقیقاً مطابق فهرست اولیه، در قالب <strong>برگه منتشرشده اما بدون متن تخصصی</strong> زیر «موضوعات حقوقی» ایجاد می‌کند تا کارت‌ها، جست‌وجو و انتخاب تخصص قابل آزمایش باشند. صفحه‌های خالی نمونه تا آماده‌شدن متن واقعی noindex هستند. صفحه‌های قبلی بدون تغییر باقی می‌مانند.</p>';
    if ( isset( $_GET['pvdak_samples'] ) ) {
        $result = sanitize_key( wp_unslash( $_GET['pvdak_samples'] ) );
        if ( '1' === $result ) {
            echo '<div class="notice notice-success inline"><p>لندینگ‌های نمونه بررسی و موارد گمشده ایجاد شدند. فهرست برگه‌ها را بازبینی کنید.</p></div>';
        } elseif ( 'error' === $result ) {
            echo '<div class="notice notice-error inline"><p>برخی لندینگ‌ها هنوز قابل نمایش نیستند. ممکن است برگه‌ای از قبل پیش‌نویس باشد؛ انتشار یا ویرایش آن فقط با تصمیم مدیر انجام می‌شود.</p></div>';
        }
    }
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="pvdak_editor_publish_sample_topics">';
    wp_nonce_field( 'pvdak_editor_publish_sample_topics' );
    submit_button( 'ایجاد ۱۷ لندینگ نمونه قابل نمایش', 'secondary' );
    echo '</form>';
    echo '<p><small>هیچ متن حقوقی ساختگی منتشر نمی‌شود. صفحه تعمیرات، پذیرش پرونده‌ها و تنظیمات افزونه Core تغییر نمی‌کنند.</small></p>';
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
        'notice_category' => array( 'pvdak-notices', 'اطلاعیه‌های PVDak' ),
        'news_category' => array( 'pvdak-news', 'اخبار PVDak' ),
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

/**
 * One explicit authorized admin action only. Cron and normal theme page views
 * never create/update WordPress content.
 */
function pvdak_editor_publish_sample_topics() {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'اجازه مدیریت برگه‌های نمونه را ندارید.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_editor_publish_sample_topics' );
    // Samples are published as empty navigation Pages. Require the public
    // maintenance gate and closed forms before enabling these seed pages.
    if ( ! function_exists( 'pvdak_maintenance_enabled' ) || ! pvdak_maintenance_enabled() ||
         ( function_exists( 'pvdak_core_intake_open' ) && pvdak_core_intake_open() ) ||
         ( function_exists( 'pvdak_core_member_intake_open' ) && pvdak_core_member_intake_open() ) ) {
        wp_die( 'برای ساخت نمونه‌ها ابتدا صفحه تعمیرات را روشن و پذیرش اطلاعات را غیرفعال نگه دارید.', '', array( 'response' => 403 ) );
    }

    // Reuse an editorially selected and published legal topic parent first.
    $parent = function_exists( 'pvdak_legal_topics_root_id' ) ? pvdak_legal_topics_root_id() : 0;
    if ( ! $parent ) {
        $parent = pvdak_editor_find_or_create_page(
            'legal-topics', 'موضوعات حقوقی', 'publish', 0,
            'page-templates/legal-topics-index.php'
        );
    }
    $result = pvdak_editor_seed_example_legal_topics( $parent );
    $home_id = function_exists( 'pvdak_home_page_id' ) ? pvdak_home_page_id() : 0;
    if ( $home_id && $parent && 'publish' === get_post_status( $parent ) ) {
        pvdak_editor_prefill_acf( $home_id, 'legal_topics_parent_page', $parent );
    }
    $okay = 0 === $result['failed'] &&
        0 === $result['unpublished'] &&
        count( pvdak_editor_example_legal_topics() ) === $result['created'] + $result['existing'];
    wp_safe_redirect( admin_url( 'admin.php?page=pvdak-home-content&pvdak_samples=' . ( $okay ? '1' : 'error' ) ) );
    exit;
}
add_action( 'admin_post_pvdak_editor_publish_sample_topics', 'pvdak_editor_publish_sample_topics' );
