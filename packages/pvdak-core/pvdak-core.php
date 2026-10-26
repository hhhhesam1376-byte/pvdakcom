<?php
/**
 * Plugin Name: PVDak Core
 * Description: Private legal requests and independent professional data foundations; client intake disabled by default.
 * Version: 0.8.19
 * Requires PHP: 7.4
 * Requires at least: 6.6
 * Author: PVDak
 * Text Domain: pvdak-core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PVDAK_CORE_VERSION', '0.8.19' );

/**
 * D24: owner-only generic status email, explicitly OFF until approved.
 * Never include a legal case title, tracking number, status, notes or links.
 */
function pvdak_core_notify_owner( $post_id, $kind ) {
    if ( '1' !== (string) get_option( 'pvdak_core_email_notifications_enabled', '0' ) ) { return false; }
    $allowed = array( 'pvdak_request', 'pvdak_professional', 'pvdak_support_ticket' );
    if ( ! in_array( $kind, $allowed, true ) ) { return false; }
    $post = get_post( absint( $post_id ) );
    if ( ! $post || $post->post_type !== $kind ||
         ! in_array( $post->post_status, 'pvdak_professional' === $kind ? array( 'private', 'draft' ) : array( 'private' ), true ) ) { return false; }
    $owner = absint( $post->post_author );
    $user = $owner ? get_userdata( $owner ) : false;
    if ( ! $user || ! isset( $user->user_email ) || ! is_email( $user->user_email ) ||
         '1' === (string) get_user_meta( $owner, '_pvdak_email_verification_pending', true ) ||
         (int) get_user_meta( $owner, '_pvdak_email_verified_at', true ) < 1 ) { return false; }
    // The positive verification must belong to the CURRENT mailbox, not a prior address.
    $recipient = strtolower( trim( $user->user_email ) );
    $verified = get_user_meta( $owner, '_pvdak_email_verified_address', true );
    if ( ! is_string( $verified ) || '' === $verified || ! hash_equals( $verified, $recipient ) ) { return false; }
    $subject = 'اعلان تغییر در حساب PVDak';
    $body = "یک تغییر در بخش خصوصی حساب شما ثبت شده است.\n" .
        "برای مشاهده وضعیت، مستقیماً وارد وب‌سایت PVDak و حساب کاربری خود شوید.\n" .
        "این ایمیل شامل جزئیات پرونده، عضویت یا گفتگو نیست.\n" .
        "اگر این پیام را انتظار نداشتید، از طریق سایت با پشتیبانی تماس بگیرید.";
    return (bool) wp_mail( $recipient, $subject, $body );
}

define( 'PVDAK_CORE_PLUGIN_FILE', __FILE__ );
require_once __DIR__ . '/includes/requests.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/professionals.php';
require_once __DIR__ . '/includes/portal.php';
require_once __DIR__ . '/includes/triage.php';
require_once __DIR__ . '/includes/request-admin.php';
require_once __DIR__ . '/includes/specialties.php';
require_once __DIR__ . '/includes/license-review.php';
require_once __DIR__ . '/includes/professional-admin.php';
require_once __DIR__ . '/includes/capacity.php';
require_once __DIR__ . '/includes/subscriptions.php';
require_once __DIR__ . '/includes/professional-portal.php';
require_once __DIR__ . '/includes/assignment-locks.php';
require_once __DIR__ . '/includes/assignments.php';

/**
 * Product roles: never confer professional verification or post-editing rights
 * by role assignment alone. Verification and real request intake are future modules.
 */
function pvdak_core_register_roles() {
    $roles = array(
        'pvdak_client' => 'مراجعه‌کننده حقوقی',
        'pvdak_lawyer' => 'وکیل (نیازمند احراز)',
        'pvdak_trainee' => 'کارآموز حقوق (نیازمند احراز)',
        'pvdak_student' => 'دانشجوی حقوق',
    );
    foreach ( $roles as $key => $label ) {
        add_role( $key, $label, array( 'read' => true ) );
    }
}

/**
 * Data models remain accessible to administrators for design/testing.
 * Neither type exposes private request information through a public route.
 * No public profile is published until verification workflow is implemented.
 */
function pvdak_core_register_post_types() {
    register_post_type( 'pvdak_request', array(
        'labels' => array(
            'name' => 'درخواست‌های حقوقی',
            'singular_name' => 'درخواست حقوقی',
            'add_new_item' => 'ثبت دستی درخواست (مدیر)',
            'edit_item' => 'بررسی درخواست',
            'menu_name' => 'درخواست‌های حقوقی',
        ),
        'description' => 'Private request records for future lawyer, consultation, and legal-question workflows.',
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'rewrite' => false,
        'query_var' => false,
        'supports' => array( 'title', 'editor', 'author' ),
        'capability_type' => array( 'pvdak_request', 'pvdak_requests' ),
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-clipboard',
        'menu_position' => 24,
        'has_archive' => false,
    ) );
    register_post_type( 'pvdak_professional', array(
        'labels' => array(
            'name' => 'پروفایل‌های جامعه حقوقی',
            'singular_name' => 'پروفایل حرفه‌ای',
            'add_new_item' => 'افزودن پروفایل بررسی‌نشده',
            'edit_item' => 'بررسی پروفایل',
            'menu_name' => 'جامعه حقوقی',
        ),
        'description' => 'Private unpublished professional profile records; public verification is not yet implemented.',
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'rewrite' => false,
        'query_var' => false,
        'supports' => array( 'title', 'editor', 'author' ),
        'capability_type' => array( 'pvdak_professional', 'pvdak_professionals' ),
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 25,
        'has_archive' => false,
    ) );
}
add_action( 'init', 'pvdak_core_register_post_types' );

/** Only admin receives management capabilities; subscriber-like roles never do. */
function pvdak_core_install() {
    pvdak_core_register_roles();
    pvdak_core_register_post_types();
    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( array( 'pvdak_request', 'pvdak_professional' ) as $type ) {
            $object = get_post_type_object( $type );
            if ( $object && isset( $object->cap ) ) {
                foreach ( (array) $object->cap as $capability ) {
                    if ( is_string( $capability ) && '' !== $capability ) {
                        $admin->add_cap( $capability );
                    }
                }
            }
        }
    }
    flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'pvdak_core_install' );

function pvdak_core_deactivate() {
    // Never delete records, roles, or capabilities automatically.
    flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'pvdak_core_deactivate' );

/** Admin information states explicitly which workflows do NOT exist yet. */
function pvdak_core_admin_message() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ! in_array( $screen->post_type, array( 'pvdak_request', 'pvdak_professional' ), true ) ) {
        return;
    }
    echo '<div class="notice notice-info"><p><strong>PVDak Core:</strong> ثبت درخواست فقط برای کاربران واردشده و پس از فعال‌سازی دستی مدیر امکان‌پذیر است. احراز صلاحیت وکلا، مدیریت مدارک حساس و ارجاع خودکار هنوز پیاده‌سازی نشده‌اند. قبل از تأیید امنیت، داده حساس واقعی وارد نکنید.</p></div>';
}
add_action( 'admin_notices', 'pvdak_core_admin_message' );

/**
 * Defense in depth: neither case files nor professional applications may
 * become publicly published through WordPress admin or other integrations.
 * Draft, auto-draft and trash are left to core behavior.
 */
function pvdak_core_enforce_private_status( $data, $postarr ) {
    if ( ! isset( $data['post_type'], $data['post_status'] ) ) { return $data; }
    if ( in_array( $data['post_type'], array( 'pvdak_request', 'pvdak_professional' ), true ) &&
         in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
        $data['post_status'] = 'private';
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'pvdak_core_enforce_private_status', 20, 2 );
