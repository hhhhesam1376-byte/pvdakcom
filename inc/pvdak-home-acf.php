<?php
/** PVDak homepage: ACF Free-compatible fields and admin entry. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function pvdak_home_defaults() {
    return array(
        'hero_label' => 'پویش وکلای دادگستری کشور',
        'hero_title' => 'حق شما، نقطه شروع ماست',
        'hero_description' => 'یک مسیر روشن برای خدمات حقوقی و همراهی جامعه وکلا',
        'hero_search_label' => 'موضوع حقوقی خود را پیدا کنید؛ خانواده، ملک، قرارداد…',
        'hero_hint' => 'ثبت درخواست • بررسی و تأیید • انتخاب متخصص مرتبط • پیگیری',
        'services_title' => 'خدمت مورد نیازتان را انتخاب کنید',
        'service_1_title' => 'به وکیل نیاز دارم',
        'service_1_description' => 'برای پیگیری و رسیدگی به پرونده',
        'service_2_title' => 'به مشاوره نیاز دارم',
        'service_2_description' => 'برای تصمیم‌گیری آگاهانه',
        'service_3_title' => 'سؤال حقوقی دارم',
        'service_3_description' => 'برای طرح پرسش و دریافت پاسخ',
        'audiences_title' => 'می‌خواهید چه کاری انجام دهید؟',
        'audiences_intro' => 'دسترسی سریع به قسمت‌های اصلی سایت',
        'audience_1_title' => 'موضوع حقوقی‌ام را پیدا کنم',
        'audience_1_description' => 'از میان موضوعات حقوقی، حوزه موردنظر را پیدا کنید.',
        'audience_2_title' => 'مقاله و راهنما بخوانم',
        'audience_2_description' => 'در مجله حقوقی، یادداشت‌ها و مطالب آموزشی را دنبال کنید.',
        'audience_3_title' => 'حساب کاربری‌ام را ببینم',
        'audience_3_description' => 'اگر حساب دارید، از پنل وضعیت و اطلاعات خود را ببینید.',
        'topics_title' => 'برای چه موضوعی به کمک نیاز دارید؟',
        'process_title' => 'از یک درخواست تا یک همراه متخصص',
        'step_1_title' => 'ثبت نیاز شما',
        'step_1_description' => 'موضوع و جزئیات را بنویسید.',
        'step_2_title' => 'بررسی و تأیید',
        'step_2_description' => 'درخواست آماده ارجاع می‌شود.',
        'step_3_title' => 'انتخاب متخصص',
        'step_3_description' => 'از میان افراد مرتبط انتخاب می‌شود.',
        'step_4_title' => 'پیگیری درخواست',
        'step_4_description' => 'وضعیت را در پنل ببینید.',
        'magazine_title' => 'مجله؛ نگاهی تازه به دنیای حقوق',
        'magazine_heading' => 'آگاهی حقوقی، پیش از تصمیم‌های مهم',
        'magazine_description' => 'راهنماها و یادداشت‌های کاربردی برای زندگی و کسب‌وکار',
        'magazine_button' => 'کشف مطالب مجله',
        'forum_title' => 'جایی برای گفت‌وگوی تخصصی',
        'forum_description' => 'تبادل تجربه و طرح مسائل در انجمن وکلا',
        'forum_button' => 'ورود به انجمن',
        'community_title' => 'تخصص شما، آغاز یک همراهی مؤثر',
        'community_description' => 'برای وکلا، کارآموزان و دانشجویان حقوق؛ تخصص خود را معرفی کنید.',
        'community_note' => 'درخواست‌های مرتبط را ببینید و با سهمیه اشتراک اعلام آمادگی کنید.',
        'community_button' => 'پیوستن به جامعه حقوقی',
                'quick_1_title' => 'پیگیری درخواست',
        'quick_2_title' => 'راهنمای خدمات',
        'quick_3_title' => 'پنل جامعه حقوقی',
        'quick_4_title' => 'پشتیبانی',
        'magazine_side_1_title' => 'قراردادها را با آگاهی بخوانید',
        'magazine_side_1_description' => 'راهنمای شناخت تعهدات و تصمیم‌گیری بهتر',
        'magazine_side_1_button' => 'موضوع پیشنهادی مجله',
        'magazine_side_2_title' => 'جایی برای گفت‌وگوی تخصصی',
        'magazine_side_2_description' => 'تبادل تجربه و طرح مسائل در انجمن وکلا',
        'magazine_side_2_button' => 'ورود به انجمن',
        'news_title' => 'تازه‌های پویش و اطلاعیه‌ها',
        'news_archive_label' => 'آرشیو اطلاعیه‌ها',
        'notice_heading' => 'تابلوی اطلاعیه‌های پویش',
        'notice_badge' => 'اطلاع‌رسانی',
        'notice_1_title' => 'راهنمای عضویت جامعه حقوقی',
        'notice_1_description' => 'مراحل معرفی تخصص و تکمیل حساب',
        'notice_2_title' => 'آشنایی با اشتراک و سهمیه',
        'notice_2_description' => 'راهنمای اعلام آمادگی روی درخواست‌ها',
        'notice_3_title' => 'اصول ارجاع و پیگیری',
        'notice_3_description' => 'آشنایی با مراحل رسیدگی به درخواست',
        'news_article_title' => 'دانش حقوقی کاربردی',
        'news_article_description' => 'مطالب آموزشی و راهنمای خدمات',
        'news_article_button' => 'مطالعه در مجله',
        'forum_feature_title' => 'یک جامعه',
        'forum_feature_subtitle' => 'چندین تجربه',
        'forum_feature_description' => 'گفت‌وگو درباره مسائل تخصصی با اعضای جامعه حقوقی',
        'forum_feature_button' => 'ورود به انجمن وکلا',
        'faq_title' => 'پیش از شروع، بیشتر بدانید',
        'faq_1_question' => 'درخواست من چگونه به متخصص ارجاع می‌شود؟',
        'faq_2_question' => 'تفاوت درخواست وکیل، مشاوره و سؤال حقوقی چیست؟',
        'faq_3_question' => 'چطور وضعیت درخواست خود را پیگیری کنم؟',
    );
}
/**
 * Preserve intentional custom editorial copy while hiding obsolete role copy.
 * Destinations are resolved by the theme and never become dead user-configured
 * placeholder URL fields.
 */
function pvdak_home_start_copy( $name, $new_default ) {
    $legacy = array(
        'audiences_title' => 'از کدام مسیر وارد می‌شوید؟',
        'audiences_intro' => 'خدمات متناسب با نقش شما',
        'audience_1_title' => 'مردم و درخواست‌کنندگان',
        'audience_1_description' => 'ثبت نیاز حقوقی و پیگیری درخواست‌ها',
        'audience_2_title' => 'وکلا و کارآموزان',
        'audience_2_description' => 'تخصص، اشتراک و درخواست‌های مرتبط',
        'audience_3_title' => 'دانشجویان حقوق',
        'audience_3_description' => 'عضویت و همراهی با جامعه حقوقی',
    );
    $saved = trim( (string) pvdak_home_value( $name, $new_default ) );
    return '' === $saved || ( isset( $legacy[ $name ] ) && $saved === $legacy[ $name ] ) ?
        $new_default : $saved;
}

function pvdak_home_value( $name, $default = null ) {
    if ( null === $default ) {
        $defaults = pvdak_home_defaults();
        $default = isset( $defaults[$name] ) ? $defaults[$name] : '';
    }
    $page_id = (int) get_option( 'page_on_front' );
    if ( $page_id && function_exists( 'get_field' ) ) {
        $value = get_field( $name, $page_id );
        if ( false !== $value && null !== $value && '' !== $value ) { return $value; }
    }
    // Transitional support for custom shortcut text saved before migration to WP Menus.
    if ( $page_id && preg_match( '/^quick_[1-4]_(title|url)$/', (string) $name ) ) {
        $legacy = get_post_meta( $page_id, $name, true );
        if ( is_string( $legacy ) && '' !== $legacy ) { return $legacy; }
    }
    return $default;
}
function pvdak_home_image_url( $name ) {
    $value = pvdak_home_value( $name, '' );
    if ( is_array( $value ) && isset( $value['ID'] ) ) { $value = (int) $value['ID']; }
    $url = is_numeric( $value ) && $value > 0 ? wp_get_attachment_image_url( (int) $value, 'full' ) : '';
    return $url ? $url : '';
}
function pvdak_register_home_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
    $defaults = pvdak_home_defaults();
    $fields = array();
    $fields[] = array( 'key' => 'field_pvdak_tab_hero', 'label' => '۱. بنر اصلی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_label', 'label' => 'متن کوچک بالای تیتر', 'name' => 'hero_label', 'type' => 'text', 'default_value' => isset($defaults['hero_label']) ? $defaults['hero_label'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_title', 'label' => 'عنوان اصلی سایت', 'name' => 'hero_title', 'type' => 'text', 'default_value' => isset($defaults['hero_title']) ? $defaults['hero_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_description', 'label' => 'توضیحات بنر', 'name' => 'hero_description', 'type' => 'textarea', 'default_value' => isset($defaults['hero_description']) ? $defaults['hero_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_image', 'label' => 'عکس پس‌زمینه بنر', 'name' => 'hero_image', 'type' => 'image', 'default_value' => isset($defaults['hero_image']) ? $defaults['hero_image'] : '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_image_mobile', 'label' => 'عکس اختصاصی بنر موبایل (اختیاری)', 'name' => 'hero_image_mobile', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'instructions' => 'اگر خالی باشد، همان عکس دسکتاپ نمایش داده می‌شود.' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_search_label', 'label' => 'متن راهنمای جست‌وجو', 'name' => 'hero_search_label', 'type' => 'text', 'default_value' => isset($defaults['hero_search_label']) ? $defaults['hero_search_label'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_hero_hint', 'label' => 'متن مراحل زیر بنر', 'name' => 'hero_hint', 'type' => 'text', 'default_value' => isset($defaults['hero_hint']) ? $defaults['hero_hint'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_services', 'label' => '۲. سه خدمت اصلی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_services_title', 'label' => 'عنوان خدمات', 'name' => 'services_title', 'type' => 'text', 'default_value' => isset($defaults['services_title']) ? $defaults['services_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_1_title', 'label' => 'خدمت 1 — عنوان', 'name' => 'service_1_title', 'type' => 'text', 'default_value' => isset($defaults['service_1_title']) ? $defaults['service_1_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_1_description', 'label' => 'خدمت 1 — توضیح کوتاه', 'name' => 'service_1_description', 'type' => 'text', 'default_value' => isset($defaults['service_1_description']) ? $defaults['service_1_description'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_1_url', 'label' => 'خدمت 1 — آدرس صفحه مقصد', 'name' => 'service_1_url', 'type' => 'url', 'default_value' => isset($defaults['service_1_url']) ? $defaults['service_1_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_2_title', 'label' => 'خدمت 2 — عنوان', 'name' => 'service_2_title', 'type' => 'text', 'default_value' => isset($defaults['service_2_title']) ? $defaults['service_2_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_2_description', 'label' => 'خدمت 2 — توضیح کوتاه', 'name' => 'service_2_description', 'type' => 'text', 'default_value' => isset($defaults['service_2_description']) ? $defaults['service_2_description'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_2_url', 'label' => 'خدمت 2 — آدرس صفحه مقصد', 'name' => 'service_2_url', 'type' => 'url', 'default_value' => isset($defaults['service_2_url']) ? $defaults['service_2_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_3_title', 'label' => 'خدمت 3 — عنوان', 'name' => 'service_3_title', 'type' => 'text', 'default_value' => isset($defaults['service_3_title']) ? $defaults['service_3_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_3_description', 'label' => 'خدمت 3 — توضیح کوتاه', 'name' => 'service_3_description', 'type' => 'text', 'default_value' => isset($defaults['service_3_description']) ? $defaults['service_3_description'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_service_3_url', 'label' => 'خدمت 3 — آدرس صفحه مقصد', 'name' => 'service_3_url', 'type' => 'url', 'default_value' => isset($defaults['service_3_url']) ? $defaults['service_3_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_audiences', 'label' => '۳. سه دسترسی کاربردی صفحه اصلی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_start_notice', 'label' => 'لینک‌های واقعی', 'name' => '', 'type' => 'message', 'message' => 'دکمه‌های این سه کارت به موضوعات حقوقی، مجله و حساب کاربری سایت متصل‌اند؛ لینک‌های قدیمی انتخاب نقش دیگر استفاده نمی‌شوند.' );
    $fields[] = array( 'key' => 'field_pvdak_home_audiences_title', 'label' => 'تیتر بخش', 'name' => 'audiences_title', 'type' => 'text', 'default_value' => isset($defaults['audiences_title']) ? $defaults['audiences_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audiences_intro', 'label' => 'توضیح کوتاه', 'name' => 'audiences_intro', 'type' => 'text', 'default_value' => isset($defaults['audiences_intro']) ? $defaults['audiences_intro'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_1_title', 'label' => 'کارت 1 — عنوان', 'name' => 'audience_1_title', 'type' => 'text', 'default_value' => isset($defaults['audience_1_title']) ? $defaults['audience_1_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_1_description', 'label' => 'کارت 1 — توضیح', 'name' => 'audience_1_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_1_description']) ? $defaults['audience_1_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_2_title', 'label' => 'کارت 2 — عنوان', 'name' => 'audience_2_title', 'type' => 'text', 'default_value' => isset($defaults['audience_2_title']) ? $defaults['audience_2_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_2_description', 'label' => 'کارت 2 — توضیح', 'name' => 'audience_2_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_2_description']) ? $defaults['audience_2_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_3_title', 'label' => 'کارت 3 — عنوان', 'name' => 'audience_3_title', 'type' => 'text', 'default_value' => isset($defaults['audience_3_title']) ? $defaults['audience_3_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_3_description', 'label' => 'کارت 3 — توضیح', 'name' => 'audience_3_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_3_description']) ? $defaults['audience_3_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_tab_topics', 'label' => '۴. لندینگ‌های حقوقی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_topics_title', 'label' => 'عنوان این قسمت در صفحه اصلی', 'name' => 'topics_title', 'type' => 'text', 'default_value' => isset($defaults['topics_title']) ? $defaults['topics_title'] : '' );
    $fields[] = array(
        'key' => 'field_pvdak_home_legal_topics_parent_page',
        'label' => 'برگه مادر لندینگ‌های حقوقی',
        'name' => 'legal_topics_parent_page',
        'type' => 'post_object',
        'post_type' => array( 'page' ),
        'post_status' => array( 'publish' ),
        'return_format' => 'id',
        'ui' => 1,
        'allow_null' => 1,
        'instructions' => 'از بخش «برگه‌ها» یک برگه مادر (مثل موضوعات حقوقی) بسازید. سپس برای هر لندینگ، برگه فرزند آن را منتشر کنید. کارت‌های صفحه اصلی به‌صورت خودکار از همین برگه‌ها و پیوند یکتای آن‌ها ساخته می‌شوند؛ نیازی به ثبت نام یا لینک جداگانه نیست.',
    );
    $fields[] = array( 'key' => 'field_pvdak_tab_process', 'label' => '۵. مراحل رسیدگی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_process_title', 'label' => 'عنوان مراحل', 'name' => 'process_title', 'type' => 'text', 'default_value' => isset($defaults['process_title']) ? $defaults['process_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_step_1_title', 'label' => 'مرحله 1 — عنوان', 'name' => 'step_1_title', 'type' => 'text', 'default_value' => isset($defaults['step_1_title']) ? $defaults['step_1_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_step_1_description', 'label' => 'مرحله 1 — توضیح', 'name' => 'step_1_description', 'type' => 'textarea', 'default_value' => isset($defaults['step_1_description']) ? $defaults['step_1_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_step_2_title', 'label' => 'مرحله 2 — عنوان', 'name' => 'step_2_title', 'type' => 'text', 'default_value' => isset($defaults['step_2_title']) ? $defaults['step_2_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_step_2_description', 'label' => 'مرحله 2 — توضیح', 'name' => 'step_2_description', 'type' => 'textarea', 'default_value' => isset($defaults['step_2_description']) ? $defaults['step_2_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_step_3_title', 'label' => 'مرحله 3 — عنوان', 'name' => 'step_3_title', 'type' => 'text', 'default_value' => isset($defaults['step_3_title']) ? $defaults['step_3_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_step_3_description', 'label' => 'مرحله 3 — توضیح', 'name' => 'step_3_description', 'type' => 'textarea', 'default_value' => isset($defaults['step_3_description']) ? $defaults['step_3_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_step_4_title', 'label' => 'مرحله 4 — عنوان', 'name' => 'step_4_title', 'type' => 'text', 'default_value' => isset($defaults['step_4_title']) ? $defaults['step_4_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_step_4_description', 'label' => 'مرحله 4 — توضیح', 'name' => 'step_4_description', 'type' => 'textarea', 'default_value' => isset($defaults['step_4_description']) ? $defaults['step_4_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_tab_editorial', 'label' => '۶. مجله حقوقی و انجمن', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_title', 'name' => 'magazine_title', 'label' => 'عنوان بخش مجله', 'type' => 'text', 'default_value' => 'مجله؛ نگاهی تازه به دنیای حقوق' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_category', 'name' => 'magazine_category', 'label' => 'دسته مطالب مجله', 'type' => 'taxonomy', 'taxonomy' => 'category', 'field_type' => 'select', 'return_format' => 'id', 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1, 'instructions' => 'از نوشته‌ها ← دسته‌ها یک دسته بسازید؛ اینجا فقط منبع مطالب را انتخاب کنید.' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_featured_post', 'name' => 'magazine_featured_post', 'label' => 'مقاله شاخص مجله (اختیاری؛ پیش‌فرض آخرین مقاله)', 'type' => 'post_object', 'post_type' => array( 'post' ), 'post_status' => array( 'publish' ), 'return_format' => 'id', 'ui' => 1, 'allow_null' => 1 );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_url', 'name' => 'magazine_url', 'label' => 'پیوند صفحه مجله (اختیاری)', 'type' => 'url' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_url', 'name' => 'forum_url', 'label' => 'پیوند صفحه انجمن (پس از راه‌اندازی)', 'type' => 'url' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_title', 'name' => 'forum_title', 'label' => 'عنوان معرفی انجمن', 'type' => 'text', 'default_value' => 'جایی برای گفت‌وگوی تخصصی' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_description', 'name' => 'forum_description', 'label' => 'توضیح کوتاه انجمن', 'type' => 'textarea', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_tab_community', 'label' => '۷. دعوت به عضویت', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_title', 'label' => 'عنوان دعوت به عضویت', 'name' => 'community_title', 'type' => 'text', 'default_value' => isset($defaults['community_title']) ? $defaults['community_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_description', 'label' => 'توضیح اول', 'name' => 'community_description', 'type' => 'textarea', 'default_value' => isset($defaults['community_description']) ? $defaults['community_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_community_note', 'label' => 'توضیح دوم', 'name' => 'community_note', 'type' => 'textarea', 'default_value' => isset($defaults['community_note']) ? $defaults['community_note'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_community_button', 'label' => 'متن دکمه عضویت', 'name' => 'community_button', 'type' => 'text', 'default_value' => isset($defaults['community_button']) ? $defaults['community_button'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_image', 'label' => 'تصویر دعوت به عضویت', 'name' => 'community_image', 'type' => 'image', 'default_value' => isset($defaults['community_image']) ? $defaults['community_image'] : '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_url', 'label' => 'لینک ثبت‌نام', 'name' => 'community_url', 'type' => 'url', 'default_value' => isset($defaults['community_url']) ? $defaults['community_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_faq', 'label' => '۸. پرسش‌های متداول', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_title', 'name' => 'faq_title', 'label' => 'عنوان بخش پرسش‌های متداول', 'type' => 'text', 'default_value' => 'پیش از شروع، بیشتر بدانید' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_parent_page', 'name' => 'faq_parent_page', 'label' => 'برگه مادر پرسش‌های متداول', 'type' => 'post_object', 'post_type' => array( 'page' ), 'post_status' => array( 'publish' ), 'return_format' => 'id', 'ui' => 1, 'allow_null' => 1, 'instructions' => 'یک برگه مادر بسازید و هر پرسش را یک برگه فرزند آن قرار دهید؛ عنوان برگه سؤال و محتوای برگه پاسخ است.' );
    $fields[] = array( 'key' => 'field_pvdak_tab_quick', 'label' => '۹. چهار دسترسی سریع زیر بنر', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array(
        'key' => 'field_pvdak_home_quick_menu_guide',
        'label' => 'انتخاب دقیق چهار برگه',
        'name' => '',
        'type' => 'message',
        'message' => 'چهار برگه منتشرشده دلخواه را به ترتیب در کادرهای زیر انتخاب کنید. عنوان و لینک هر کارت خودکار از همان برگه می‌آید. موارد تکراری و منتشرنشده نمایش داده نمی‌شوند. تا پیش از اولین انتخاب، حداکثر چهار برگه اول فهرست «دسترسی‌های سریع صفحه اصلی» (در صورت وجود) به‌طور موقت نمایش داده می‌شود؛ دیگر هیچ‌وقت کل فهرست به این نوار وارد نمی‌شود.',
    );
    for ( $quick_slot = 1; $quick_slot <= 4; $quick_slot++ ) {
        $fields[] = array(
            'key' => 'field_pvdak_home_quick_page_' . $quick_slot,
            'label' => 'جایگاه ' . $quick_slot . ' — انتخاب برگه',
            'name' => 'quick_page_' . $quick_slot,
            'type' => 'post_object',
            'post_type' => array( 'page' ),
            'post_status' => array( 'publish' ),
            'return_format' => 'id',
            'ui' => 1,
            'allow_null' => 1,
            'instructions' => 'فقط برگه منتشرشده. برای چهار کارت، هر چهار جایگاه را پر کنید.',
        );
    }
    $fields[] = array( 'key' => 'field_pvdak_tab_notices', 'label' => '۹. اطلاعیه‌ها و تازه‌های پویش', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_title', 'name' => 'news_title', 'label' => 'عنوان بخش تازه‌ها', 'type' => 'text', 'default_value' => 'تازه‌های پویش و اطلاعیه‌ها' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_heading', 'name' => 'notice_heading', 'label' => 'عنوان تابلوی اطلاعیه‌ها', 'type' => 'text', 'default_value' => 'تابلوی اطلاعیه‌های پویش' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_badge', 'name' => 'notice_badge', 'label' => 'برچسب تابلوی اطلاعیه‌ها', 'type' => 'text', 'default_value' => 'اطلاع‌رسانی' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_category', 'name' => 'notice_category', 'label' => 'دسته اطلاعیه‌ها', 'type' => 'taxonomy', 'taxonomy' => 'category', 'field_type' => 'select', 'return_format' => 'id', 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1, 'instructions' => 'از نوشته‌ها ← دسته‌ها یک دسته بسازید؛ اینجا فقط منبع مطالب را انتخاب کنید.' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_category', 'name' => 'news_category', 'label' => 'دسته مطالب ویژه/تازه‌ها', 'type' => 'taxonomy', 'taxonomy' => 'category', 'field_type' => 'select', 'return_format' => 'id', 'add_term' => 0, 'save_terms' => 0, 'load_terms' => 0, 'allow_null' => 1, 'instructions' => 'از نوشته‌ها ← دسته‌ها یک دسته بسازید؛ اینجا فقط منبع مطالب را انتخاب کنید.' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_featured_post', 'name' => 'news_featured_post', 'label' => 'نوشته شاخص تازه‌ها (اختیاری)', 'type' => 'post_object', 'post_type' => array( 'post' ), 'post_status' => array( 'publish' ), 'return_format' => 'id', 'ui' => 1, 'allow_null' => 1 );
    $fields[] = array( 'key' => 'field_pvdak_home_news_archive_label', 'name' => 'news_archive_label', 'label' => 'متن لینک آرشیو', 'type' => 'text', 'default_value' => 'آرشیو اطلاعیه‌ها' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_archive_url', 'name' => 'news_archive_url', 'label' => 'نشانی آرشیو (اختیاری؛ لینک دسته به‌صورت خودکار استفاده می‌شود)', 'type' => 'url' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_title', 'name' => 'forum_feature_title', 'label' => 'عنوان اول کارت انجمن', 'type' => 'text', 'default_value' => 'یک جامعه' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_subtitle', 'name' => 'forum_feature_subtitle', 'label' => 'عنوان دوم کارت انجمن', 'type' => 'text', 'default_value' => 'چندین تجربه' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_description', 'name' => 'forum_feature_description', 'label' => 'توضیحات انجمن', 'type' => 'textarea', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_button', 'name' => 'forum_feature_button', 'label' => 'متن دکمه انجمن', 'type' => 'text', 'default_value' => 'ورود به انجمن وکلا' );
    acf_add_local_field_group( array(
        'key' => 'group_pvdak_home_content_v1',
        'title' => 'محتوای صفحه اصلی PVDak',
        'fields' => $fields,
        'location' => array( array( array('param'=>'page_type','operator'=>'==','value'=>'front_page') ) ),
        'position' => 'normal',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true
    ) );
}
add_action( 'acf/init', 'pvdak_register_home_acf_fields' );

/**
 * Dedicated client editor. Unlike a redirect to Gutenberg this loads
 * all 11 ACF tabs directly in the PVDak menu, even if WordPress changes
 * the normal page-editor presentation.
 */
function pvdak_home_add_admin_menu() {
    $hook = add_menu_page(
        'محتوای صفحه اصلی',
        'محتوای صفحه اصلی',
        'edit_pages',
        'pvdak-home-content',
        'pvdak_home_admin_landing',
        'dashicons-admin-home',
        22
    );
    if ( $hook ) {
        add_action( 'load-' . $hook, 'pvdak_home_editor_load' );
    }
}
add_action( 'admin_menu', 'pvdak_home_add_admin_menu' );

function pvdak_home_page_id() {
    $page_id = (int) get_option( 'page_on_front' );
    if ( 'page' !== get_option( 'show_on_front' ) || ! $page_id || 'page' !== get_post_type( $page_id ) ) {
        return 0;
    }
    return $page_id;
}

function pvdak_home_editor_load() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        wp_die( esc_html__( 'You are not allowed to edit pages.', 'pvdak' ) );
    }
    $page_id = pvdak_home_page_id();
    if ( ! $page_id || ! current_user_can( 'edit_post', $page_id ) ) {
        return;
    }
    if ( function_exists( 'acf_form_head' ) ) {
        // Process & save ACF fields before the admin header is rendered.
        acf_form_head();
    }
}

function pvdak_home_admin_landing() {
    if ( ! current_user_can( 'edit_pages' ) ) {
        return;
    }
    echo '<div class="wrap pvdak-home-editor"><h1>محتوای صفحه اصلی PVDak</h1>';
    echo '<p>این صفحه فقط برای چیدمان و متن‌های ثابت است. مطالب مجله، اطلاعیه‌ها، لندینگ‌ها و پرسش‌ها را در بخش‌های اصلی وردپرس ثبت کنید؛ کارت‌های صفحه اصلی خودکار به‌روز می‌شوند.</p>';
    echo '<div class="pvdak-editor-navigation" style="display:flex;gap:10px;flex-wrap:wrap;margin:16px 0 22px">';
    if ( current_user_can( 'edit_pages' ) ) {
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '">برگه‌ها و لندینگ‌های حقوقی</a>';
    }
    if ( current_user_can( 'edit_posts' ) ) {
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'edit.php' ) ) . '">مقالات مجله و اطلاعیه‌ها</a>';
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=category' ) ) . '">دسته‌بندی نوشته‌ها</a>';
    }
    if ( current_user_can( 'upload_files' ) ) {
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'upload.php' ) ) . '">کتابخانه تصاویر</a>';
    }
    if ( current_user_can( 'edit_theme_options' ) ) {
        echo '<a class="button button-secondary" href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">فهرست‌های سایت</a>';
    }
    echo '</div>';
    echo '<div class="notice notice-info inline"><p><strong>راهنمای مدیریت:</strong> برای «موضوعات حقوقی» برگه مادر و فرزند بسازید؛ برای «مجله» و «اطلاعیه‌ها» نوشته‌ها را در دسته‌های جدا منتشر کنید؛ برای «پرسش‌های متداول» از برگه‌های فرزند یک برگه مادر استفاده کنید. این بخش فقط انتخاب می‌کند که چه محتوایی کجا نمایش داده شود. فرم درخواست حقوقی و احراز صلاحیت حرفه‌ای هنوز منتشر نشده‌اند.</p></div>';

    if ( ! function_exists( 'acf_form' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
        echo '<div class="notice notice-warning"><p>افزونه Advanced Custom Fields (ACF) باید فعال باشد. پس از فعال‌سازی صفحه را دوباره باز کنید.</p></div>';
        echo '</div>';
        return;
    }
    $page_id = pvdak_home_page_id();
    if ( ! $page_id ) {
        echo '<div class="notice notice-info inline"><p>صفحه اصلی وردپرس هنوز به یک برگه ثابت متصل نشده است. برای فعال‌کردن ویرایشگر، یک‌بار دکمه زیر را بزنید.</p></div>';
        if ( current_user_can( 'manage_options' ) ) {
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
            echo '<input type="hidden" name="action" value="pvdak_prepare_home_page">';
            wp_nonce_field( 'pvdak_prepare_home_page' );
            submit_button( 'فعال‌سازی ویرایشگر صفحه اصلی' );
            echo '</form>';
        } else {
            echo '<p>برای راه‌اندازی اولیه، از مدیر سایت کمک بگیرید.</p>';
        }
        echo '<p><a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">تنظیمات خواندن وردپرس</a></p></div>';
        return;
    }
    if ( ! current_user_can( 'edit_post', $page_id ) ) {
        echo '<div class="notice notice-error inline"><p>مجوز ویرایش برگه صفحه اصلی را ندارید.</p></div></div>';
        return;
    }
    if ( isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>تغییرات صفحه اصلی ذخیره شد.</p></div>';
    }
    echo '<p><a href="' . esc_url( get_permalink( $page_id ) ) . '" target="_blank" rel="noopener noreferrer">پیش‌نمایش صفحه اصلی</a> | ';
    echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) ) . '">ویرایش برگه در وردپرس</a></p>';

    if ( function_exists( 'pvdak_home_readiness_panel' ) ) {
        pvdak_home_readiness_panel();
    }

    if ( function_exists( 'pvdak_editor_bootstrap_card' ) ) {
        pvdak_editor_bootstrap_card();
    }

    // ACF Free includes acf_form: no ACF Pro options-page license required.
    acf_form( array(
        'post_id'         => $page_id,
        'field_groups'    => array( 'group_pvdak_home_content_v1' ),
        'submit_value'    => 'ذخیره تغییرات صفحه اصلی',
        'updated_message' => 'تغییرات ذخیره شد.',
        'return'          => admin_url( 'admin.php?page=pvdak-home-content&updated=1' ),
        'form'            => true,
        'form_attributes' => array( 'id' => 'pvdak-home-acf-form' ),
    ) );
    // Output ACF media templates inside the document body for image fields.
    if ( function_exists( 'acf_enqueue_uploader' ) ) {
        acf_enqueue_uploader();
    }
    echo '</div>';
}

function pvdak_home_prepare_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to manage this site.', 'pvdak' ) );
    }
    check_admin_referer( 'pvdak_prepare_home_page' );

    $page_id = (int) get_option( 'page_on_front' );
    if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
        $existing = get_page_by_path( 'pvdak-home', OBJECT, 'page' );
        if ( $existing && 'trash' !== $existing->post_status ) {
            $page_id = (int) $existing->ID;
            if ( 'publish' !== $existing->post_status ) {
                wp_update_post( array( 'ID' => $page_id, 'post_status' => 'publish' ) );
            }
        } else {
            $created = wp_insert_post( array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => 'صفحه اصلی',
                'post_name'    => 'pvdak-home',
                'post_content' => '',
            ), true );
            if ( is_wp_error( $created ) ) {
                wp_die( esc_html( $created->get_error_message() ) );
            }
            $page_id = (int) $created;
        }
    }
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $page_id );
    wp_safe_redirect( admin_url( 'admin.php?page=pvdak-home-content' ) );
    exit;
}
add_action( 'admin_post_pvdak_prepare_home_page', 'pvdak_home_prepare_page' );
