<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_setup() {
	load_theme_textdomain( 'pvdak', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	register_nav_menus( array( 'primary' => __( 'منوی اصلی', 'pvdak' ), 'footer' => __( 'منوی فوتر', 'pvdak' ), 'pvdak_quick' => __( 'دسترسی‌های سریع صفحه اصلی', 'pvdak' ) ) );
}
add_action( 'after_setup_theme', 'pvdak_setup' );

function pvdak_assets() {
	wp_enqueue_style( 'pvdak-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	// Always load the Persian RTL layer, even before WordPress is switched from en_US.
	wp_enqueue_style( 'pvdak-rtl', get_template_directory_uri() . '/rtl.css', array( 'pvdak-style' ), wp_get_theme()->get( 'Version' ) );
	// Primary mega-navigation is bundled directly into style.css (v0.8.12+).
	// Avoid an extra stylesheet request that can be skipped by caching/minifiers.
	if ( is_category() || is_archive() || is_home() || is_single() ) {
		// Native WordPress Posts/Categories, separate from legal landing Pages.
		wp_enqueue_style( 'pvdak-editorial', get_template_directory_uri() . '/assets/css/pvdak-editorial.css', array( 'pvdak-rtl' ), wp_get_theme()->get( 'Version' ) );
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'pvdak_assets' );

// Keep unfinished pages hidden from public visitors while development is in progress.
require_once get_template_directory() . '/pvdak-maintenance.php';

/* Homepage content and editor (ACF Free-compatible). */
require_once get_template_directory() . '/inc/pvdak-home-acf.php';
require_once get_template_directory() . '/inc/pvdak-home-quick-links.php';
require_once get_template_directory() . '/inc/pvdak-legal-landings.php';
require_once get_template_directory() . '/inc/pvdak-topic-card.php';
require_once get_template_directory() . '/inc/pvdak-demo-blog-robots.php';
require_once get_template_directory() . '/inc/pvdak-demo-comment-preview.php';
require_once get_template_directory() . '/inc/pvdak-public-pages.php';
require_once get_template_directory() . '/inc/pvdak-auth.php';
require_once get_template_directory() . '/inc/pvdak-account-tools.php';
require_once get_template_directory() . '/inc/pvdak-registration.php';
require_once get_template_directory() . '/inc/pvdak-email-verification.php';
require_once get_template_directory() . '/inc/pvdak-support-tickets.php';
require_once get_template_directory() . '/inc/pvdak-home-collections.php';
require_once get_template_directory() . '/inc/pvdak-navigation-links.php';
require_once get_template_directory() . '/inc/pvdak-home-readiness.php';
require_once get_template_directory() . '/inc/pvdak-readiness-admin.php';
/**
 * Display-only compatibility for wording embedded in the already installed
 * PVDak Core 0.8.14 shortcodes. Keep the plugin's byte-for-byte release
 * parity intact; do not touch case/user content or other shortcode outputs.
 */
function pvdak_core_shortcode_brand_labels( $output, $tag ) {
    if ( ! is_string( $output ) ||
        ! in_array( $tag, array( 'pvdak_account_dashboard', 'pvdak_professional_dashboard' ), true ) ) {
        return $output;
    }
    return str_replace(
        array(
            '<h2>پنل کاربری پویش</h2>',
            '<p class="pvdak-core-eyebrow">پویش وکلای دادگستری کشور</p>',
            '>همکاری با پویش</a>',
        ),
        array(
            '<h2>پنل کاربری PVDak</h2>',
            '<p class="pvdak-core-eyebrow">PVDak</p>',
            '>همکاری با PVDak</a>',
        ),
        $output
    );
}
add_filter( 'do_shortcode_tag', 'pvdak_core_shortcode_brand_labels', 10, 2 );

// Optional admin starter: native Page and Post Category content structure.
require_once get_template_directory() . '/inc/pvdak-editor-bootstrap.php';

function pvdak_home_assets() {
    if ( is_front_page() ) {
        // All structural Swiper assets ship inside this versioned WordPress theme.
        $vendor_rel = '/assets/vendor/swiper-14.3.0';
        $vendor_dir = get_template_directory() . $vendor_rel;
        $vendor_url = get_template_directory_uri() . $vendor_rel;
        $have_swiper = is_readable( $vendor_dir . '/swiper-bundle.min.js' ) &&
            is_readable( $vendor_dir . '/swiper-bundle.min.css' ) &&
            is_readable( $vendor_dir . '/LICENSE' );
        if ( $have_swiper ) {
            wp_enqueue_style(
                'pvdak-swiper',
                $vendor_url . '/swiper-bundle.min.css',
                array( 'pvdak-rtl' ),
                '14.3.0'
            );
            wp_enqueue_script(
                'pvdak-swiper',
                $vendor_url . '/swiper-bundle.min.js',
                array(),
                '14.3.0',
                true
            );
        }
        wp_enqueue_script(
            'pvdak-home-search',
            get_template_directory_uri() . '/assets/js/pvdak-home.js',
            $have_swiper ? array( 'pvdak-swiper' ) : array(),
            wp_get_theme()->get( 'Version' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_home_assets' );

/** Tiny progressive enhancement on account credential screens only. */
function pvdak_account_form_assets() {
    if ( is_page( array( 'account-login', 'account-recovery', 'account-register', 'my-pvdak-account', 'account-profile', 'contact-us' ) ) ||
         is_page_template( 'page-templates/pvdak-account.php' ) ) {
        wp_enqueue_script(
            'pvdak-account-interactions',
            get_template_directory_uri() . '/assets/js/pvdak-account.js',
            array(),
            wp_get_theme()->get( 'Version' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_account_form_assets' );

/** Load private ticket UI styling only on the personal account screen. */
function pvdak_support_ticket_assets() {
    if ( is_page( 'my-pvdak-account' ) ||
         is_page_template( 'page-templates/pvdak-account.php' ) ) {
        // D07: Core checks post_content for shortcodes, but this account
        // renders the Core dashboard from its template. Enqueue the same
        // installed plugin CSS once; do NOT duplicate it in the theme.
        $core_file = WP_PLUGIN_DIR . '/pvdak-core/pvdak-core.php';
        $core_css  = WP_PLUGIN_DIR . '/pvdak-core/assets/forms.css';
        if ( shortcode_exists( 'pvdak_account_dashboard' ) &&
             is_readable( $core_file ) && is_readable( $core_css ) &&
             ! wp_style_is( 'pvdak-core', 'enqueued' ) ) {
            wp_enqueue_style(
                'pvdak-core',
                plugins_url( 'assets/forms.css', $core_file ),
                array( 'pvdak-rtl' ),
                defined( 'PVDAK_CORE_VERSION' ) ? PVDAK_CORE_VERSION : wp_get_theme()->get( 'Version' )
            );
        }
        wp_enqueue_style(
            'pvdak-support-tickets',
            get_template_directory_uri() . '/assets/css/pvdak-support.css',
            array( 'pvdak-rtl' ),
            wp_get_theme()->get( 'Version' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_support_ticket_assets' );


// Temporary PV favicon bundled in the theme, including login/admin tabs.
// Preserve the WordPress site_icon option untouched for an eventual real brand.
function pvdak_pv_favicon_url() {
    return get_template_directory_uri() . '/assets/icons/pvdak-pv.svg';
}
function pvdak_pv_favicon_markup() {
    printf(
        '<link rel="icon" href="%s" type="image/svg+xml" sizes="any">' . "\n",
        esc_url( pvdak_pv_favicon_url() )
    );
}
// Prevent conflicting old favicon links from WordPress while this temporary
// theme icon is in use; no database mutations, plugins, CDN or separate assets.
function pvdak_pv_disable_core_icon_markup() {
    remove_action( 'wp_head', 'wp_site_icon', 99 );
    remove_action( 'login_head', 'wp_site_icon', 99 );
    remove_action( 'admin_head', 'wp_site_icon', 99 );
}
add_action( 'after_setup_theme', 'pvdak_pv_disable_core_icon_markup', 20 );
add_action( 'wp_head', 'pvdak_pv_favicon_markup', 99 );
add_action( 'login_head', 'pvdak_pv_favicon_markup', 99 );
add_action( 'admin_head', 'pvdak_pv_favicon_markup', 99 );
