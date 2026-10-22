<?php
/** Admin-controlled availability switch, default OFF. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_register_settings() {
    register_setting( 'pvdak_core_settings', 'pvdak_core_intake_enabled', array(
        'type' => 'string',
        'default' => '0',
        'sanitize_callback' => function( $value ) {
            return '1' === (string) $value ? '1' : '0';
        },
    ) );
}
add_action( 'admin_init', 'pvdak_core_register_settings' );

function pvdak_core_admin_menu() {
    add_options_page(
        'تنظیمات PVDak Core',
        'PVDak Core',
        'manage_options',
        'pvdak-core',
        'pvdak_core_settings_page'
    );
}
add_action( 'admin_menu', 'pvdak_core_admin_menu' );

function pvdak_core_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    echo '<div class="wrap"><h1>PVDak Core — تنظیمات موتور حقوقی</h1>';
    echo '<div class="notice notice-warning inline"><p><strong>نسخه آزمایشی:</strong> این افزونه شامل فرم خصوصیِ ثبت درخواست است، اما اعتبارسنجی وکلا، ارجاع خودکار، پرداخت، اشتراک، نظارت و حریم خصوصی نهایی هنوز پیاده‌سازی/تأیید نشده‌اند. روی سایت واقعی تا تکمیل تست‌ها پذیرش درخواست را روشن نکنید.</p></div>';
    echo '<h2>وضعیت امکانات</h2><table class="widefat striped" style="max-width:840px"><tbody>';
    $items = array(
        'نقش‌های مراجعه‌کننده، وکیل، کارآموز و دانشجو' => 'ایجاد نقش؛ تأیید حرفه‌ای هنوز ندارد',
        'پروفایل حرفه‌ای' => 'نوع داده خصوصی؛ ثبت عمومی غیرفعال',
        'ثبت سه نوع درخواست' => 'فرم پایه فقط برای کاربران واردشده، پشت کلید فعال‌سازی',
        'مدیریت درخواست‌ها' => 'فقط مدیر، ثبت وضعیت و تاریخچه تغییر وضعیت',
        'درخواست‌های من' => 'فقط درخواست‌های کاربر واردشده',
        'مدارک، ارجاع، سهمیه، پرداخت و انجمن' => 'هنوز پیاده‌سازی نشده',
    );
    foreach( $items as $name => $status ) {
        echo '<tr><th scope="row">' . esc_html( $name ) . '</th><td>' . esc_html( $status ) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<h2>کنترل پذیرش درخواست</h2>';
    echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
    settings_fields( 'pvdak_core_settings' );
    echo '<input type="hidden" name="pvdak_core_intake_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_intake_enabled" value="1" ' . checked( pvdak_core_intake_open(), true, false ) . '> پذیرش فرم درخواست را فعال کن (فقط بعد از تست کامل)</label>';
    if ( defined( 'PVDAK_CORE_DISABLE_INTAKE' ) && PVDAK_CORE_DISABLE_INTAKE ) {
        echo '<p><strong>قفل اضطراری فعال:</strong> تعریف PVDAK_CORE_DISABLE_INTAKE در wp-config.php اجازه ثبت درخواست نمی‌دهد.</p>';
    }
    submit_button( 'ذخیره تنظیمات' );
    echo '</form>';
    echo '<h2>برگه‌های موردنیاز</h2><p>پس از تست، برگه‌های جدا برای هر خدمت ایجاد کنید و یکی از کدهای کوتاه زیر را قرار دهید:</p>';
    echo '<p><code>[pvdak_request_form type="lawyer"]</code>، <code>[pvdak_request_form type="consultation"]</code>، <code>[pvdak_request_form type="question"]</code></p>';
    echo '<p>پنل پیگیری درخواست‌های شخصی: <code>[pvdak_my_requests]</code></p>';
    echo '<p>وقتی برگه‌ها تست شدند، لینک‌های سه خدمت را در منوی «محتوای صفحه اصلی» قالب روی همان برگه‌ها قرار دهید.</p>';
    echo '</div>';
}

/** Admin list supports triage without opening each request. */
function pvdak_core_request_columns( $columns ) {
    $columns['pvdak_kind'] = 'نوع درخواست';
    $columns['pvdak_state'] = 'وضعیت رسیدگی';
    $columns['pvdak_owner'] = 'ثبت‌کننده';
    return $columns;
}
add_filter( 'manage_pvdak_request_posts_columns', 'pvdak_core_request_columns' );

function pvdak_core_request_column_content( $column, $post_id ) {
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
    if ( 'pvdak_kind' === $column ) {
        $types = pvdak_core_request_types();
        $type = get_post_meta( $post_id, '_pvdak_request_type', true );
        echo esc_html( isset( $types[ $type ] ) ? $types[ $type ] : 'نامشخص' );
    } elseif ( 'pvdak_state' === $column ) {
        $statuses = pvdak_core_request_statuses();
        $status = get_post_meta( $post_id, '_pvdak_request_state', true );
        echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : 'ثبت‌شده' );
    } elseif ( 'pvdak_owner' === $column ) {
        $user = get_user_by( 'id', get_post_field( 'post_author', $post_id ) );
        echo $user ? esc_html( $user->display_name ) : 'نامشخص';
    }
}
add_action( 'manage_pvdak_request_posts_custom_column', 'pvdak_core_request_column_content', 10, 2 );

function pvdak_core_assets() {
    if ( ! is_singular() ) { return; }
    global $post;
    if ( $post instanceof WP_Post && (
        has_shortcode( $post->post_content, 'pvdak_request_form' ) ||
        has_shortcode( $post->post_content, 'pvdak_my_requests' )
    ) ) {
        wp_enqueue_style( 'pvdak-core', plugins_url( 'assets/forms.css', dirname( __FILE__ ) . '/../pvdak-core.php' ), array(), PVDAK_CORE_VERSION );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_core_assets' );
