<?php
/** Admin-controlled availability switch, default OFF. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_core_register_settings() {
    register_setting( 'pvdak_core_pages_settings', 'pvdak_core_landing_parent_id', array(
        'type' => 'integer',
        'default' => 0,
        'sanitize_callback' => function( $value ) {
            $page_id = absint( $value );
            return ( $page_id && 'page' === get_post_type( $page_id ) ) ? $page_id : 0;
        },
    ) );
    register_setting( 'pvdak_core_settings', 'pvdak_core_intake_enabled', array(
        'type' => 'string',
        'default' => '0',
        'sanitize_callback' => function( $value ) {
            return '1' === (string) $value ? '1' : '0';
        },
    ) );
    register_setting( 'pvdak_core_settings', 'pvdak_core_subscription_gate_enabled', array(
        'type'=>'string', 'default'=>'0',
        'sanitize_callback'=>function($v){return '1'===(string)$v?'1':'0';}
    ) );
    register_setting( 'pvdak_core_settings', 'pvdak_core_directory_enabled', array(
        'type' => 'string', 'default' => '0',
        'sanitize_callback' => function( $v ) { return '1' === (string) $v ? '1' : '0'; },
    ) );
    register_setting( 'pvdak_core_settings', 'pvdak_core_email_notifications_enabled', array(
        'type' => 'string', 'default' => '0',
        'sanitize_callback' => function( $v ) { return '1' === (string) $v ? '1' : '0'; },
    ) );
    register_setting( 'pvdak_core_settings', 'pvdak_core_member_intake_enabled', array(
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
        'خدمات حقوقی PVDak',
        'manage_options',
        'pvdak-core',
        'pvdak_core_settings_page'
    );
}
add_action( 'admin_menu', 'pvdak_core_admin_menu' );

function pvdak_core_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    echo '<div class="wrap"><h1>تنظیمات خدمات حقوقی PVDak</h1>';
    echo '<p>از این صفحه می‌توانید برگه مادر حقوقی و کلیدهای پذیرش خدمات را مدیریت کنید. هیچ خدمتی با مشاهده این صفحه فعال نمی‌شود.</p>';
    echo '<div class="notice notice-warning inline"><p><strong>نسخه آزمایشی:</strong> این افزونه شامل فرم خصوصیِ ثبت درخواست است، اما اعتبارسنجی وکلا، ارجاع خودکار، پرداخت، اشتراک، نظارت و حریم خصوصی نهایی هنوز پیاده‌سازی/تأیید نشده‌اند. روی سایت واقعی تا تکمیل تست‌ها پذیرش درخواست را روشن نکنید.</p></div>';
    echo '<details><summary>اطلاعات تکمیلی امکانات (فقط راهنما)</summary>';
    echo '<table class="widefat striped" style="max-width:840px"><tbody>';
    $items = array(
        'نقش‌های مراجعه‌کننده، وکیل، کارآموز و دانشجو' => 'ایجاد نقش؛ تأیید حرفه‌ای هنوز ندارد',
        'پروفایل حرفه‌ای' => 'نوع داده خصوصی؛ ثبت عمومی غیرفعال',
        'ثبت سه نوع درخواست' => 'فرم پایه فقط برای کاربران واردشده، پشت کلید فعال‌سازی',
        'مدیریت درخواست‌ها' => 'فقط مدیر، ثبت وضعیت و تاریخچه تغییر وضعیت',
        'درخواست‌های من' => 'فقط درخواست‌های کاربر واردشده',
        'تخصص‌های حقوقی' => 'انتخاب برگه لندینگ واقعی برای هر متخصص',
        'صلاحیت ارجاع داخلی' => 'تأیید دستی مجزا از درخواست عضویت و تأیید اداری',
        'ارجاع کنترل‌شده' => 'فقط به وکیل دارای تأیید داخلی، تخصص مرتبط و ظرفیت آزاد؛ بدون ارسال محتوای پرونده',
        'کنترل ظرفیت و پنل همکاری' => 'کنترل داخلی و آمار تجمیعی؛ فاقد دسترسی به محتوای پرونده',
        'اشتراک و سهمیه دستی' => 'نمونه اولیه با کنترل اختیاریِ پیش‌فرض خاموش؛ بدون پرداخت یا تمدید خودکار',
        'استعلام رسمی، ارجاع خودکار و پرداخت آنلاین' => 'هنوز آماده بهره‌برداری نیست',
    );
    foreach( $items as $name => $status ) {
        echo '<tr><th scope="row">' . esc_html( $name ) . '</th><td>' . esc_html( $status ) . '</td></tr>';
    }
    echo '</tbody></table></details>';
    echo '<h2>موضوعات حقوقی (برگه‌های لندینگ)</h2>';
    echo '<p>همان برگه مادر صفحه اصلی را انتخاب کنید؛ حوزه‌های تخصصی، موضوع درخواست‌ها و تطبیق داخلی همگی به برگه‌های فرزند منتشرشده این صفحه متصل می‌شوند.</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
    settings_fields( 'pvdak_core_pages_settings' );
    echo wp_dropdown_pages( array(
        'name' => 'pvdak_core_landing_parent_id',
        'id' => 'pvdak_core_landing_parent_id',
        'selected' => absint( get_option( 'pvdak_core_landing_parent_id', 0 ) ),
        'show_option_none' => 'انتخاب برگه مادر',
        'option_none_value' => 0,
        'echo' => 0,
    ) );
    echo '<p class="description">اگر خالی باشد، برگه با نشانی legal-topics استفاده می‌شود.</p>';
    submit_button( 'ذخیره برگه مادر حقوقی' );
    echo '</form>';
    echo '<h2>کنترل پذیرش درخواست</h2>';
    echo '<form method="post" action="' . esc_url( admin_url( 'options.php' ) ) . '">';
    settings_fields( 'pvdak_core_settings' );
    echo '<input type="hidden" name="pvdak_core_intake_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_intake_enabled" value="1" ' . checked( pvdak_core_intake_open(), true, false ) . '> پذیرش فرم درخواست را فعال کن (فقط بعد از تست کامل)</label>';
    echo '<p>پذیرش درخواست همکاری جامعه حقوقی جداگانه کنترل می‌شود:</p>';
    echo '<input type="hidden" name="pvdak_core_member_intake_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_member_intake_enabled" value="1" ' . checked( pvdak_core_member_intake_open(), true, false ) . '> پذیرش درخواست همکاری حرفه‌ای (فقط پس از تست)</label>';
    if ( defined( 'PVDAK_CORE_DISABLE_INTAKE' ) && PVDAK_CORE_DISABLE_INTAKE ) {
        echo '<p><strong>قفل اضطراری فعال:</strong> تعریف PVDAK_CORE_DISABLE_INTAKE در wp-config.php اجازه ثبت درخواست نمی‌دهد.</p>';
    }
    echo '<p>کنترل آزمایشی سهمیه اشتراک فقط پس از تست کامل:</p>';
    echo '<input type="hidden" name="pvdak_core_subscription_gate_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_subscription_gate_enabled" value="1" ' .
         checked( pvdak_core_subscription_gate_on(), true, false ) .
         '> فعال‌کردن محدودیت طرح/انقضا/سهمیه در ارجاع داخلی (پیش‌فرض خاموش)</label>';
    echo '<p class="description">فعال‌سازی بدون تعیین دستی طرح معتبر و تاریخ انقضا، همه ارجاع‌های جدید را متوقف می‌کند. پرداخت آنلاین وجود ندارد.</p>';
    echo '<p>اعلان ایمیلی عمومی تغییر وضعیت (بدون اطلاعات پرونده یا متن گفتگو):</p>';
    echo '<input type="hidden" name="pvdak_core_email_notifications_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_email_notifications_enabled" value="1" ' .
         checked( '1' === (string) get_option( 'pvdak_core_email_notifications_enabled', '0' ), true, false ) .
         '> فعال‌سازی پس از بررسی تنظیمات ایمیل و حریم خصوصی (پیش‌فرض خاموش)</label>';
    echo '<p class="description">فقط ایمیل مالک فعلی رکورد خصوصی، به‌جز حساب در انتظار تأیید ایمیل؛ wp_mail تحویل واقعی را تضمین نمی‌کند.</p>';
    echo '<p>فهرست عمومی اعضای حرفه‌ای، فقط با رضایت معتبر هر شخص و بررسی صلاحیت جاری:</p>';
    echo '<input type="hidden" name="pvdak_core_directory_enabled" value="0">';
    echo '<label><input type="checkbox" name="pvdak_core_directory_enabled" value="1" ' .
         checked( pvdak_core_directory_enabled(), true, false ) .
         '> فعال‌کردن فهرست عمومی (پیش‌فرض خاموش؛ فقط پس از تأیید سیاست حریم خصوصی و آزمون)</label>';
    echo '<p class="description">تنها نام نمایشی، شهر و عنوان تخصص منتشر می‌شود. رضایت هر فرد در پنل همکاری ثبت یا لغو می‌شود. کش عمومی این صفحه باید غیرفعال باشد.</p>';
    submit_button( 'ذخیره تنظیمات' );
    echo '</form>';
    echo '<h2>ساخت برگه‌های خدمات (بدون انتشار عمومی)</h2>';
    echo '<p>با این دکمه هفت برگه پیش‌نویس برای درخواست وکیل، مشاوره، پرسش حقوقی، پنل‌های کاربری و همکاری ساخته می‌شود. می‌توانید محتوا و طراحی هر برگه را ویرایش و بعد از آزمون منتشر کنید؛ برگه موجود دوباره ساخته نمی‌شود.</p>';
    echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
    echo '<input type="hidden" name="action" value="pvdak_core_create_pages">';
    wp_nonce_field( 'pvdak_core_create_pages' );
    submit_button( 'ساخت برگه‌های آزمایشی به‌صورت پیش‌نویس', 'secondary' );
    echo '</form>';
    $page_result = isset( $_GET['pvdak_pages'] ) && is_string( $_GET['pvdak_pages'] ) ?
        sanitize_key( wp_unslash( $_GET['pvdak_pages'] ) ) : '';
    $page_messages = array(
        'created' => array( 'success', 'برگه‌های جدید به‌صورت پیش‌نویس ساخته شدند؛ برگه‌های موجود بدون تغییر باقی ماندند.' ),
        'existing' => array( 'info', 'همه برگه‌های موردنیاز از قبل وجود دارند؛ هیچ برگه‌ای تغییر نکرد.' ),
        'partial' => array( 'warning', 'برخی برگه‌ها ساخته شدند اما ساخت همه برگه‌ها موفق نبود؛ پیش از تکرار، فهرست برگه‌ها را بررسی کنید.' ),
        'failed' => array( 'error', 'ساخت برگه‌های جدید موفق نبود؛ هیچ موفقیت تأییدنشده‌ای ثبت نشد.' ),
    );
    if ( isset( $page_messages[ $page_result ] ) ) {
        $message = $page_messages[ $page_result ];
        echo '<div class="notice notice-' . esc_attr( $message[0] ) . ' inline"><p>' . esc_html( $message[1] ) . '</p></div>';
    }
    echo '<h2>برگه‌های موردنیاز</h2><p>هر برگه از یکی از کدهای کوتاه زیر استفاده می‌کند:</p>';
    echo '<p><code>[pvdak_request_form type="lawyer"]</code>، <code>[pvdak_request_form type="consultation"]</code>، <code>[pvdak_request_form type="question"]</code></p>';
    echo '<p>پنل پیگیری درخواست‌های شخصی: <code>[pvdak_my_requests]</code></p>';
    echo '<p>فرم عضویت جامعه حقوقی: <code>[pvdak_professional_application]</code></p>';
    echo '<p>داشبورد شخصی کاربران: <code>[pvdak_account_dashboard]</code></p>';
    echo '<p>پنل همکاری حرفه‌ای (بدون اطلاعات پرونده): <code>[pvdak_professional_dashboard]</code></p>';
    echo '<p>فهرست عمومی رضایت‌محور (برگه فقط با تصمیم مدیر منتشر شود): <code>[pvdak_professional_directory]</code></p>';
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
        has_shortcode( $post->post_content, 'pvdak_my_requests' ) ||
        has_shortcode( $post->post_content, 'pvdak_professional_application' ) ||
        has_shortcode( $post->post_content, 'pvdak_account_dashboard' ) ||
        has_shortcode( $post->post_content, 'pvdak_professional_dashboard' ) ||
        has_shortcode( $post->post_content, 'pvdak_professional_directory' )
    ) ) {
        wp_enqueue_style( 'pvdak-core', plugins_url( 'assets/forms.css', PVDAK_CORE_PLUGIN_FILE ), array(), PVDAK_CORE_VERSION );
    }
}
add_action( 'wp_enqueue_scripts', 'pvdak_core_assets' );


/** One-click draft service pages: safe to repeat, never overwrite content. */
function pvdak_core_default_service_pages() {
    return array(
        'request-lawyer' => array( 'درخواست وکیل', '[pvdak_request_form type="lawyer"]' ),
        'request-consultation' => array( 'درخواست مشاوره حقوقی', '[pvdak_request_form type="consultation"]' ),
        'request-question' => array( 'پرسش حقوقی', '[pvdak_request_form type="question"]' ),
        'my-legal-requests' => array( 'درخواست‌های من', '[pvdak_my_requests]' ),
        'join-legal-community' => array( 'همکاری با متخصصان PVDak', '[pvdak_professional_application]' ),
        'my-pvdak-account' => array( 'پنل کاربری PVDak', '[pvdak_account_dashboard]' ),
        'my-professional-account' => array( 'پنل همکاری حقوقی', '[pvdak_professional_dashboard]' ),
    );
}
function pvdak_core_create_service_pages() {
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_pages' ) ) {
        wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'pvdak_core_create_pages' );
    $created = 0;
    $failed = 0;
    foreach ( pvdak_core_default_service_pages() as $slug => $item ) {
        // Never overwrite, rename or republish an existing page, even a draft.
        if ( get_page_by_path( $slug, OBJECT, 'page' ) ) { continue; }
        $id = wp_insert_post( array(
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => $item[0],
            'post_name' => $slug,
            'post_content' => $item[1],
            'post_author' => get_current_user_id(),
        ), true );
        if ( is_wp_error( $id ) || ! $id || 'page' !== get_post_type( $id ) ||
             $slug !== get_post_field( 'post_name', $id ) ) {
            ++$failed;
            continue;
        }
        // A third-party save hook may alter status. Only a verified draft
        // counts as created; attempt to restore this newly inserted page.
        if ( 'draft' !== get_post_status( $id ) ) {
            $restored = wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ), true );
            if ( is_wp_error( $restored ) || 'draft' !== get_post_status( $id ) ) {
                ++$failed;
                continue;
            }
        }
        ++$created;
    }
    $result = $failed ? ( $created ? 'partial' : 'failed' ) : ( $created ? 'created' : 'existing' );
    wp_safe_redirect( admin_url( 'options-general.php?page=pvdak-core&pvdak_pages=' . $result ) );
    exit;
}
add_action( 'admin_post_pvdak_core_create_pages', 'pvdak_core_create_service_pages' );

/**
 * Avoid accidentally caching personalized legal account/request pages.
 * Does not modify static public article or landing page caching.
 */
function pvdak_core_private_page_headers() {
    if ( ! is_singular( 'page' ) ) { return; }
    $post = get_queried_object();
    if ( ! $post instanceof WP_Post ) { return; }
    // A revoked directory consent must not linger in WordPress page caches.
    if ( has_shortcode( $post->post_content, 'pvdak_professional_directory' ) ) {
        nocache_headers();
        return;
    }
    $shortcodes = array( 'pvdak_account_dashboard', 'pvdak_my_requests', 'pvdak_request_form', 'pvdak_professional_application', 'pvdak_professional_dashboard' );
    foreach ( $shortcodes as $code ) {
        if ( has_shortcode( $post->post_content, $code ) ) {
            nocache_headers();
            if ( in_array( $code, array( 'pvdak_account_dashboard', 'pvdak_my_requests', 'pvdak_professional_dashboard' ), true ) ) {
                header( 'X-Robots-Tag: noindex, nofollow', true );
            }
            return;
        }
    }
}
add_action( 'template_redirect', 'pvdak_core_private_page_headers', 1 );
