<?php
/**
 * PVDak management hub — visual dashboard only.
 * Never edits users, content, permissions, options, or Core service switches.
 * Authoritative route/capability checks remain in pvdak-readiness-admin.php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_management_v2_assets( $hook ) {
    if ( 'toplevel_page_pvdak-management' !== $hook || ! current_user_can( 'edit_pages' ) ) {
        return;
    }
    wp_enqueue_style(
        'pvdak-management-dashboard',
        get_template_directory_uri() . '/assets/css/pvdak-management.css',
        array( 'dashicons' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'admin_enqueue_scripts', 'pvdak_management_v2_assets' );

/** Build a single permission-checked source of truth for every dashboard link. */
function pvdak_management_v2_available() {
    $available = array();
    foreach ( pvdak_management_links() as $link ) {
        if ( pvdak_management_link_available( $link ) ) {
            $available[ $link[3] ] = $link;
        }
    }
    return $available;
}

function pvdak_management_v2_icon( $route ) {
    $fixed = array(
        'admin.php?page=pvdak-home-content' => 'dashicons-admin-home',
        'edit.php?post_type=page' => 'dashicons-admin-page',
        'edit.php' => 'dashicons-edit',
        'upload.php' => 'dashicons-format-gallery',
        'nav-menus.php' => 'dashicons-menu',
        'users.php' => 'dashicons-groups',
        'edit-comments.php' => 'dashicons-admin-comments',
        'options-general.php' => 'dashicons-admin-settings',
        'options-general.php?page=pvdak-core' => 'dashicons-admin-tools',
        'tools.php?page=pvdak-readiness' => 'dashicons-yes-alt',
    );
    if ( isset( $fixed[ $route ] ) ) { return $fixed[ $route ]; }
    if ( false !== strpos( $route, 'pvdak_support_ticket' ) ) { return 'dashicons-sos'; }
    if ( false !== strpos( $route, 'pvdak_professional' ) ) { return 'dashicons-id-alt'; }
    if ( false !== strpos( $route, 'pvdak_request' ) ) { return 'dashicons-clipboard'; }
    if ( false !== strpos( $route, 'comment_status=' ) ) { return 'dashicons-admin-comments'; }
    if ( false !== strpos( $route, 'post_type=page' ) ) { return 'dashicons-admin-page'; }
    if ( false !== strpos( $route, 'post_status=' ) ) { return 'dashicons-edit'; }
    return 'dashicons-arrow-left-alt2';
}

/** Descriptions come from the existing fixed route registry, not user content. */
function pvdak_management_v2_card( $link ) {
    $label = $link[0];
    $route = $link[3];
    if ( 'admin.php?page=pvdak-home-content' === $route &&
         function_exists( 'pvdak_home_page_id' ) && ! pvdak_home_page_id() ) {
        $label = 'راه‌اندازی صفحه اصلی';
    }
    if ( 'edit-comments.php?comment_status=moderated' === $route ) {
        $pending = pvdak_management_pending_comments();
        if ( $pending > 0 ) {
            $label .= ' (' . number_format_i18n( $pending ) . ')';
        }
    }
    $icon = pvdak_management_v2_icon( $route );
    return '<a class="pvdak-card" href="' . esc_url( admin_url( $route ) ) . '">' .
        '<span class="pvdak-card-icon" aria-hidden="true"><span class="dashicons ' . esc_attr( $icon ) . '"></span></span>' .
        '<span class="pvdak-card-content"><span class="pvdak-card-title">' . esc_html( $label ) .
        '</span><span class="pvdak-card-desc">' . esc_html( $link[1] ) . '</span></span>' .
        '<span class="pvdak-card-arrow" aria-hidden="true">←</span></a>';
}

function pvdak_management_v2_cards( $routes, $available ) {
    $html = '';
    foreach ( array_unique( $routes ) as $route ) {
        if ( isset( $available[ $route ] ) ) {
            $html .= pvdak_management_v2_card( $available[ $route ] );
        }
    }
    return $html;
}

function pvdak_management_v2_section( $heading, $subtitle, $icon, $routes, $available ) {
    $cards = pvdak_management_v2_cards( $routes, $available );
    if ( '' === $cards ) { return; }
    echo '<section class="pvdak-section" aria-label="' . esc_attr( $heading ) . '">';
    echo '<div class="pvdak-section-head"><div><h2 class="pvdak-section-title">' .
        '<span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>' .
        esc_html( $heading ) . '</h2><p class="pvdak-section-desc">' .
        esc_html( $subtitle ) . '</p></div></div>';
    echo '<div class="pvdak-card-grid">' . $cards . '</div></section>'; // Fixed, escaped routes.
}

function pvdak_management_v2_details( $heading, $routes, $available ) {
    $cards = pvdak_management_v2_cards( $routes, $available );
    if ( '' === $cards ) { return; }
    echo '<details class="pvdak-details"><summary>' . esc_html( $heading ) .
        '</summary><div class="pvdak-card-grid">' . $cards . '</div></details>';
}

/** Real aggregate counters from WordPress; never fetch private record contents. */
function pvdak_management_v2_metrics( $available ) {
    $page_counts = current_user_can( 'edit_pages' ) ? wp_count_posts( 'page' ) : null;
    $post_counts = current_user_can( 'edit_posts' ) ? wp_count_posts( 'post' ) : null;
    $definitions = array(
        array( 'edit.php?post_type=page&post_status=draft', 'برگه‌های پیش‌نویس', $page_counts, 'draft' ),
        array( 'edit.php?post_status=draft', 'مقالات پیش‌نویس', $post_counts, 'draft' ),
        array( 'edit.php?post_status=pending', 'مقالات منتظر بازبینی', $post_counts, 'pending' ),
    );
    $cards = '';
    foreach ( $definitions as $item ) {
        list( $route, $label, $counts, $state ) = $item;
        if ( ! isset( $available[ $route ] ) || ! is_object( $counts ) ||
             ! isset( $counts->$state ) || ! is_numeric( $counts->$state ) ) { continue; }
        $count = max( 0, (int) $counts->$state );
        $cards .= pvdak_management_v2_metric( $route, $label, $count );
    }
    $comments_route = 'edit-comments.php?comment_status=moderated';
    if ( isset( $available[ $comments_route ] ) ) {
        $cards .= pvdak_management_v2_metric(
            $comments_route, 'دیدگاه‌های منتظر تأیید', pvdak_management_pending_comments()
        );
    }
    if ( '' !== $cards ) {
        echo '<section class="pvdak-metrics" aria-label="خلاصه کارهای در انتظار">' . $cards . '</section>';
    }
}

function pvdak_management_v2_metric( $route, $label, $count ) {
    $icon = pvdak_management_v2_icon( $route );
    return '<a class="pvdak-metric" href="' . esc_url( admin_url( $route ) ) . '">' .
        '<span class="pvdak-metric-icon" aria-hidden="true"><span class="dashicons ' .
        esc_attr( $icon ) . '"></span></span><span>' .
        '<strong class="pvdak-metric-number">' . esc_html( number_format_i18n( $count ) ) .
        '</strong><span class="pvdak-metric-label">' . esc_html( $label ) .
        '</span></span></a>';
}

function pvdak_management_v2_status_item( $name, $value, $tone = '' ) {
    $tone_class = in_array( $tone, array( 'attention', 'muted' ), true ) ?
        ' pvdak-status-value--' . $tone : '';
    echo '<div class="pvdak-status"><span class="pvdak-status-label">' .
        esc_html( $name ) . '</span><strong class="pvdak-status-value' . esc_attr( $tone_class ) .
        '">' . esc_html( $value ) . '</strong></div>';
}

/** Read-only service status — the Core send-time and permissions gates still apply. */
function pvdak_management_v2_status() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $core = pvdak_management_core_available();
    $maintenance = function_exists( 'pvdak_maintenance_enabled' ) && pvdak_maintenance_enabled();
    $registration = function_exists( 'pvdak_registration_open' ) && pvdak_registration_open();
    $requests = $core && pvdak_core_intake_open();
    $members = $core && function_exists( 'pvdak_core_member_intake_open' ) && pvdak_core_member_intake_open();
    $directory = $core && function_exists( 'pvdak_core_directory_enabled' ) && pvdak_core_directory_enabled();
    $notifications = $core && pvdak_management_switch_enabled(
        get_option( 'pvdak_core_email_notifications_enabled', '0' )
    );
    echo '<section class="pvdak-section" aria-label="وضعیت خدمات سایت">';
    echo '<div class="pvdak-section-head"><div><h2 class="pvdak-section-title"><span class="dashicons dashicons-shield" aria-hidden="true"></span>وضعیت خدمات سایت</h2>' .
        '<p class="pvdak-section-desc">نمایش وضعیت فعلی؛ هیچ تنظیمی از این صفحه تغییر نمی‌کند.</p></div></div>';
    echo '<div class="pvdak-status-grid">';
    pvdak_management_v2_status_item( 'حالت تعمیرات', $maintenance ? 'فعال' : 'غیرفعال', $maintenance ? 'attention' : 'muted' );
    pvdak_management_v2_status_item( 'ثبت‌نام عمومی', $registration ? 'فعال' : 'بسته', $registration ? '' : 'muted' );
    pvdak_management_v2_status_item( 'پذیرش درخواست حقوقی', ! $core ? 'Core در دسترس نیست' : ( $requests ? 'فعال' : 'بسته' ), $requests ? '' : 'muted' );
    pvdak_management_v2_status_item( 'پذیرش همکاری متخصصان', ! $core ? 'Core در دسترس نیست' : ( $members ? 'فعال' : 'بسته' ), $members ? '' : 'muted' );
    pvdak_management_v2_status_item( 'فهرست عمومی متخصصان', ! $core ? 'Core در دسترس نیست' : ( $directory ? 'کلید فعال؛ رضایت فردی لازم است' : 'بسته' ), $directory ? '' : 'muted' );
    pvdak_management_v2_status_item( 'اعلان ایمیلی', ! $core ? 'Core در دسترس نیست' : ( $notifications ? 'کلید فعال؛ رضایت فردی لازم است' : 'خاموش' ), $notifications ? '' : 'muted' );
    echo '</div>';
    echo '<p class="pvdak-readonly">پیش از فعال‌سازی خدمات عمومی، مجوزها، سیاست حریم خصوصی و تأییدهای حقوقی بررسی شوند.</p>';
    $front = pvdak_readiness_front_page_snapshot();
    if ( 'ready' !== $front ) {
        echo '<p class="pvdak-alert"><strong>تنظیم صفحه اصلی:</strong> ' .
            esc_html( pvdak_readiness_front_page_status_label( $front ) ) . ' — <a href="' .
            esc_url( admin_url( 'options-reading.php' ) ) . '">بررسی تنظیمات خواندن</a></p>';
    }
    echo '</section>';
}

/** Render the actual full-width PVDak management home. */
function pvdak_management_v2_render() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    $available = pvdak_management_v2_available();
    echo '<div class="wrap pvdak-management-app">';
    echo '<header class="pvdak-hero"><div><span class="pvdak-eyebrow">داشبورد مدیریتی PVDak</span>' .
        '<h1>مدیریت سایت PVDak</h1>' .
        '<p>کارهای روزمره سایت، درخواست‌ها، انتشار محتوا و وضعیت خدمات را از یک صفحه پیگیری کنید.</p></div>';
    echo '<div class="pvdak-hero-actions">';
    echo '<a class="pvdak-button" href="' . esc_url( home_url( '/' ) ) .
        '" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external" aria-hidden="true"></span>مشاهده سایت</a>';
    $home = 'admin.php?page=pvdak-home-content';
    if ( isset( $available[ $home ] ) ) {
        echo '<a class="pvdak-button pvdak-button--primary" href="' .
            esc_url( admin_url( $home ) ) . '"><span class="dashicons dashicons-edit" aria-hidden="true"></span>ویرایش صفحه اصلی</a>';
    }
    echo '</div></header>';

    pvdak_management_v2_metrics( $available );

    $priority = pvdak_management_primary_queue_routes( $available );
    $moderation = 'edit-comments.php?comment_status=moderated';
    if ( isset( $available[ $moderation ] ) ) { $priority[] = $moderation; }
    pvdak_management_v2_section(
        'درخواست‌ها و پیگیری‌ها',
        'صف‌های رسیدگی که مدیر سایت و تیم پشتیبانی به آن‌ها بیشتر مراجعه می‌کنند.',
        'dashicons-list-view', $priority, $available
    );
    $additional = pvdak_management_secondary_queue_routes( $available );
    if ( $additional ) {
        pvdak_management_v2_details(
            'نمایش مسیرهای تکمیلی درخواست‌ها و عضویت‌ها', $additional, $available
        );
    }

    pvdak_management_v2_section(
        'محتوا و ظاهر سایت',
        'ویرایش محتوای اصلی، موضوعات حقوقی، مجله، تصاویر و فهرست‌ها.',
        'dashicons-admin-appearance',
        array(
            'admin.php?page=pvdak-home-content',
            'edit.php?post_type=page',
            'edit.php',
            'upload.php',
            'nav-menus.php',
        ), $available
    );

    pvdak_management_v2_section(
        'انتشار و بازبینی',
        'محتوای آماده‌نشده را پیش از انتشار بررسی و تکمیل کنید.',
        'dashicons-welcome-write-blog',
        array(
            'edit.php?post_type=page&post_status=draft',
            'edit.php?post_status=draft',
            'edit.php?post_type=page&post_status=pending',
            'edit.php?post_status=pending',
            'edit-comments.php',
        ), $available
    );

    pvdak_management_v2_section(
        'حساب‌ها و تنظیمات',
        'مدیریت کاربران، تنظیمات پایه و بررسی آمادگی راه‌اندازی.',
        'dashicons-admin-generic',
        array(
            'users.php',
            'options-general.php',
            'options-general.php?page=pvdak-core',
            'tools.php?page=pvdak-readiness',
        ), $available
    );
    if ( ! pvdak_management_core_available() ) {
        echo '<p class="pvdak-alert">ابزارهای خدمات حقوقی و عضویت متخصصان در این نصب در دسترس نیستند. ';
        if ( current_user_can( 'manage_options' ) ) {
            echo '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">بررسی افزونه‌های نصب‌شده</a>';
        } else {
            echo 'برای بررسی وضعیت نصب، با مدیر سایت هماهنگ کنید.';
        }
        echo '</p>';
    }

    pvdak_management_v2_status();
    echo '<details class="pvdak-guide"><summary>راهنمای کارهای روزمره مدیر سایت</summary>';
    // Reuse existing capability-aware routine rather than duplicating routes.
    pvdak_management_daily_steps();
    echo '</details>';
    echo '<p class="pvdak-footnote">این داشبورد فقط اطلاعات و پیوندهای مجاز را نمایش می‌دهد؛ تغییر تنظیمات و انتشار محتوا در ابزارهای اصلی وردپرس انجام می‌شود.</p>';
    echo '</div>';
}
