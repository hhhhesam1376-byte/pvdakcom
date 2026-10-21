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
        'audiences_title' => 'از کدام مسیر وارد می‌شوید؟',
        'audiences_intro' => 'خدمات متناسب با نقش شما',
        'audience_1_title' => 'مردم و درخواست‌کنندگان',
        'audience_1_description' => 'ثبت نیاز حقوقی و پیگیری درخواست‌ها',
        'audience_2_title' => 'وکلا و کارآموزان',
        'audience_2_description' => 'تخصص، اشتراک و درخواست‌های مرتبط',
        'audience_3_title' => 'دانشجویان حقوق',
        'audience_3_description' => 'عضویت و همراهی با جامعه حقوقی',
        'topics_title' => 'برای چه موضوعی به کمک نیاز دارید؟',
        'topic_1_title' => 'خانواده',
        'topic_2_title' => 'ملکی و املاک',
        'topic_3_title' => 'کیفری و جرایم',
        'topic_4_title' => 'قراردادها',
        'topic_5_title' => 'کار و بیمه',
        'topic_6_title' => 'چک و مطالبات',
        'topic_7_title' => 'ارث و وصیت',
        'topic_8_title' => 'کسب‌وکار',
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
    $fields[] = array( 'key' => 'field_pvdak_tab_audiences', 'label' => '۳. گروه‌های کاربران', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_audiences_title', 'label' => 'تیتر بخش', 'name' => 'audiences_title', 'type' => 'text', 'default_value' => isset($defaults['audiences_title']) ? $defaults['audiences_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audiences_intro', 'label' => 'توضیح کوتاه', 'name' => 'audiences_intro', 'type' => 'text', 'default_value' => isset($defaults['audiences_intro']) ? $defaults['audiences_intro'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_1_title', 'label' => 'گروه 1 — عنوان', 'name' => 'audience_1_title', 'type' => 'text', 'default_value' => isset($defaults['audience_1_title']) ? $defaults['audience_1_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_1_description', 'label' => 'گروه 1 — توضیح', 'name' => 'audience_1_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_1_description']) ? $defaults['audience_1_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_1_url', 'label' => 'گروه 1 — لینک', 'name' => 'audience_1_url', 'type' => 'url', 'default_value' => isset($defaults['audience_1_url']) ? $defaults['audience_1_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_2_title', 'label' => 'گروه 2 — عنوان', 'name' => 'audience_2_title', 'type' => 'text', 'default_value' => isset($defaults['audience_2_title']) ? $defaults['audience_2_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_2_description', 'label' => 'گروه 2 — توضیح', 'name' => 'audience_2_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_2_description']) ? $defaults['audience_2_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_2_url', 'label' => 'گروه 2 — لینک', 'name' => 'audience_2_url', 'type' => 'url', 'default_value' => isset($defaults['audience_2_url']) ? $defaults['audience_2_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_3_title', 'label' => 'گروه 3 — عنوان', 'name' => 'audience_3_title', 'type' => 'text', 'default_value' => isset($defaults['audience_3_title']) ? $defaults['audience_3_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_3_description', 'label' => 'گروه 3 — توضیح', 'name' => 'audience_3_description', 'type' => 'textarea', 'default_value' => isset($defaults['audience_3_description']) ? $defaults['audience_3_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_audience_3_url', 'label' => 'گروه 3 — لینک', 'name' => 'audience_3_url', 'type' => 'url', 'default_value' => isset($defaults['audience_3_url']) ? $defaults['audience_3_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_topics', 'label' => '۴. موضوعات حقوقی', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_topics_title', 'label' => 'عنوان موضوعات', 'name' => 'topics_title', 'type' => 'text', 'default_value' => isset($defaults['topics_title']) ? $defaults['topics_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_1_title', 'label' => 'موضوع 1 — عنوان', 'name' => 'topic_1_title', 'type' => 'text', 'default_value' => isset($defaults['topic_1_title']) ? $defaults['topic_1_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_1_url', 'label' => 'موضوع 1 — لینک', 'name' => 'topic_1_url', 'type' => 'url', 'default_value' => isset($defaults['topic_1_url']) ? $defaults['topic_1_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_2_title', 'label' => 'موضوع 2 — عنوان', 'name' => 'topic_2_title', 'type' => 'text', 'default_value' => isset($defaults['topic_2_title']) ? $defaults['topic_2_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_2_url', 'label' => 'موضوع 2 — لینک', 'name' => 'topic_2_url', 'type' => 'url', 'default_value' => isset($defaults['topic_2_url']) ? $defaults['topic_2_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_3_title', 'label' => 'موضوع 3 — عنوان', 'name' => 'topic_3_title', 'type' => 'text', 'default_value' => isset($defaults['topic_3_title']) ? $defaults['topic_3_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_3_url', 'label' => 'موضوع 3 — لینک', 'name' => 'topic_3_url', 'type' => 'url', 'default_value' => isset($defaults['topic_3_url']) ? $defaults['topic_3_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_4_title', 'label' => 'موضوع 4 — عنوان', 'name' => 'topic_4_title', 'type' => 'text', 'default_value' => isset($defaults['topic_4_title']) ? $defaults['topic_4_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_4_url', 'label' => 'موضوع 4 — لینک', 'name' => 'topic_4_url', 'type' => 'url', 'default_value' => isset($defaults['topic_4_url']) ? $defaults['topic_4_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_5_title', 'label' => 'موضوع 5 — عنوان', 'name' => 'topic_5_title', 'type' => 'text', 'default_value' => isset($defaults['topic_5_title']) ? $defaults['topic_5_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_5_url', 'label' => 'موضوع 5 — لینک', 'name' => 'topic_5_url', 'type' => 'url', 'default_value' => isset($defaults['topic_5_url']) ? $defaults['topic_5_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_6_title', 'label' => 'موضوع 6 — عنوان', 'name' => 'topic_6_title', 'type' => 'text', 'default_value' => isset($defaults['topic_6_title']) ? $defaults['topic_6_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_6_url', 'label' => 'موضوع 6 — لینک', 'name' => 'topic_6_url', 'type' => 'url', 'default_value' => isset($defaults['topic_6_url']) ? $defaults['topic_6_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_7_title', 'label' => 'موضوع 7 — عنوان', 'name' => 'topic_7_title', 'type' => 'text', 'default_value' => isset($defaults['topic_7_title']) ? $defaults['topic_7_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_7_url', 'label' => 'موضوع 7 — لینک', 'name' => 'topic_7_url', 'type' => 'url', 'default_value' => isset($defaults['topic_7_url']) ? $defaults['topic_7_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_8_title', 'label' => 'موضوع 8 — عنوان', 'name' => 'topic_8_title', 'type' => 'text', 'default_value' => isset($defaults['topic_8_title']) ? $defaults['topic_8_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_topic_8_url', 'label' => 'موضوع 8 — لینک', 'name' => 'topic_8_url', 'type' => 'url', 'default_value' => isset($defaults['topic_8_url']) ? $defaults['topic_8_url'] : '' );
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
    $fields[] = array( 'key' => 'field_pvdak_tab_editorial', 'label' => '۶. مجله و انجمن', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_title', 'label' => 'عنوان بخش مجله', 'name' => 'magazine_title', 'type' => 'text', 'default_value' => isset($defaults['magazine_title']) ? $defaults['magazine_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_heading', 'label' => 'تیتر اصلی مجله', 'name' => 'magazine_heading', 'type' => 'text', 'default_value' => isset($defaults['magazine_heading']) ? $defaults['magazine_heading'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_description', 'label' => 'توضیحات مجله', 'name' => 'magazine_description', 'type' => 'textarea', 'default_value' => isset($defaults['magazine_description']) ? $defaults['magazine_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_button', 'label' => 'متن دکمه مجله', 'name' => 'magazine_button', 'type' => 'text', 'default_value' => isset($defaults['magazine_button']) ? $defaults['magazine_button'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_image', 'label' => 'تصویر مجله', 'name' => 'magazine_image', 'type' => 'image', 'default_value' => isset($defaults['magazine_image']) ? $defaults['magazine_image'] : '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_url', 'label' => 'آدرس مجله', 'name' => 'magazine_url', 'type' => 'url', 'default_value' => isset($defaults['magazine_url']) ? $defaults['magazine_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_title', 'label' => 'عنوان انجمن', 'name' => 'forum_title', 'type' => 'text', 'default_value' => isset($defaults['forum_title']) ? $defaults['forum_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_description', 'label' => 'توضیحات انجمن', 'name' => 'forum_description', 'type' => 'textarea', 'default_value' => isset($defaults['forum_description']) ? $defaults['forum_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_button', 'label' => 'متن دکمه انجمن', 'name' => 'forum_button', 'type' => 'text', 'default_value' => isset($defaults['forum_button']) ? $defaults['forum_button'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_image', 'label' => 'تصویر انجمن', 'name' => 'forum_image', 'type' => 'image', 'default_value' => isset($defaults['forum_image']) ? $defaults['forum_image'] : '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_url', 'label' => 'آدرس انجمن', 'name' => 'forum_url', 'type' => 'url', 'default_value' => isset($defaults['forum_url']) ? $defaults['forum_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_community', 'label' => '۷. دعوت به عضویت', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_title', 'label' => 'عنوان دعوت به عضویت', 'name' => 'community_title', 'type' => 'text', 'default_value' => isset($defaults['community_title']) ? $defaults['community_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_description', 'label' => 'توضیح اول', 'name' => 'community_description', 'type' => 'textarea', 'default_value' => isset($defaults['community_description']) ? $defaults['community_description'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_community_note', 'label' => 'توضیح دوم', 'name' => 'community_note', 'type' => 'textarea', 'default_value' => isset($defaults['community_note']) ? $defaults['community_note'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_community_button', 'label' => 'متن دکمه عضویت', 'name' => 'community_button', 'type' => 'text', 'default_value' => isset($defaults['community_button']) ? $defaults['community_button'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_image', 'label' => 'تصویر دعوت به عضویت', 'name' => 'community_image', 'type' => 'image', 'default_value' => isset($defaults['community_image']) ? $defaults['community_image'] : '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_community_url', 'label' => 'لینک ثبت‌نام', 'name' => 'community_url', 'type' => 'url', 'default_value' => isset($defaults['community_url']) ? $defaults['community_url'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_faq', 'label' => '۸. پرسش‌های متداول', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_title', 'label' => 'عنوان پرسش‌های متداول', 'name' => 'faq_title', 'type' => 'text', 'default_value' => isset($defaults['faq_title']) ? $defaults['faq_title'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_1_question', 'label' => 'پرسش 1', 'name' => 'faq_1_question', 'type' => 'text', 'default_value' => isset($defaults['faq_1_question']) ? $defaults['faq_1_question'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_1_answer', 'label' => 'پاسخ 1', 'name' => 'faq_1_answer', 'type' => 'textarea', 'default_value' => isset($defaults['faq_1_answer']) ? $defaults['faq_1_answer'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_2_question', 'label' => 'پرسش 2', 'name' => 'faq_2_question', 'type' => 'text', 'default_value' => isset($defaults['faq_2_question']) ? $defaults['faq_2_question'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_2_answer', 'label' => 'پاسخ 2', 'name' => 'faq_2_answer', 'type' => 'textarea', 'default_value' => isset($defaults['faq_2_answer']) ? $defaults['faq_2_answer'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_3_question', 'label' => 'پرسش 3', 'name' => 'faq_3_question', 'type' => 'text', 'default_value' => isset($defaults['faq_3_question']) ? $defaults['faq_3_question'] : '' );
    $fields[] = array( 'key' => 'field_pvdak_home_faq_3_answer', 'label' => 'پاسخ 3', 'name' => 'faq_3_answer', 'type' => 'textarea', 'default_value' => isset($defaults['faq_3_answer']) ? $defaults['faq_3_answer'] : '', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_tab_quick', 'label' => '۹. نوار دسترسی سریع', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_1_title', 'label' => 'دسترسی ۱ — عنوان', 'name' => 'quick_1_title', 'type' => 'text', 'default_value' => 'پیگیری درخواست' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_1_url', 'label' => 'دسترسی ۱ — لینک', 'name' => 'quick_1_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_2_title', 'label' => 'دسترسی ۲ — عنوان', 'name' => 'quick_2_title', 'type' => 'text', 'default_value' => 'راهنمای خدمات' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_2_url', 'label' => 'دسترسی ۲ — لینک', 'name' => 'quick_2_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_3_title', 'label' => 'دسترسی ۳ — عنوان', 'name' => 'quick_3_title', 'type' => 'text', 'default_value' => 'پنل جامعه حقوقی' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_3_url', 'label' => 'دسترسی ۳ — لینک', 'name' => 'quick_3_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_4_title', 'label' => 'دسترسی ۴ — عنوان', 'name' => 'quick_4_title', 'type' => 'text', 'default_value' => 'پشتیبانی' );
    $fields[] = array( 'key' => 'field_pvdak_home_quick_4_url', 'label' => 'دسترسی ۴ — لینک', 'name' => 'quick_4_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_magazinesupp', 'label' => '۱۰. کارت‌های مکمل مجله', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_1_title', 'label' => 'کارت مجله ۱ — عنوان', 'name' => 'magazine_side_1_title', 'type' => 'text', 'default_value' => 'قراردادها را با آگاهی بخوانید' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_1_description', 'label' => 'کارت مجله ۱ — خلاصه', 'name' => 'magazine_side_1_description', 'type' => 'textarea', 'default_value' => 'راهنمای شناخت تعهدات و تصمیم‌گیری بهتر', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_1_button', 'label' => 'کارت مجله ۱ — متن لینک', 'name' => 'magazine_side_1_button', 'type' => 'text', 'default_value' => 'موضوع پیشنهادی مجله' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_1_url', 'label' => 'کارت مجله ۱ — لینک', 'name' => 'magazine_side_1_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_2_title', 'label' => 'کارت مجله ۲ — عنوان', 'name' => 'magazine_side_2_title', 'type' => 'text', 'default_value' => 'جایی برای گفت‌وگوی تخصصی' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_2_description', 'label' => 'کارت مجله ۲ — خلاصه', 'name' => 'magazine_side_2_description', 'type' => 'textarea', 'default_value' => 'تبادل تجربه و طرح مسائل در انجمن وکلا', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_2_button', 'label' => 'کارت مجله ۲ — متن لینک', 'name' => 'magazine_side_2_button', 'type' => 'text', 'default_value' => 'ورود به انجمن' );
    $fields[] = array( 'key' => 'field_pvdak_home_magazine_side_2_url', 'label' => 'کارت مجله ۲ — لینک', 'name' => 'magazine_side_2_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_tab_notices', 'label' => '۱۱. اطلاعیه‌ها و تازه‌ها', 'name' => '', 'type' => 'tab', 'placement' => 'top' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_title', 'label' => 'عنوان بخش تازه‌ها', 'name' => 'news_title', 'type' => 'text', 'default_value' => 'تازه‌های پویش و اطلاعیه‌ها' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_archive_label', 'label' => 'متن لینک آرشیو', 'name' => 'news_archive_label', 'type' => 'text', 'default_value' => 'آرشیو اطلاعیه‌ها' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_archive_url', 'label' => 'آدرس آرشیو اطلاعیه‌ها', 'name' => 'news_archive_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_heading', 'label' => 'عنوان تابلوی اطلاعیه‌ها', 'name' => 'notice_heading', 'type' => 'text', 'default_value' => 'تابلوی اطلاعیه‌های پویش' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_badge', 'label' => 'برچسب تابلوی اطلاعیه‌ها', 'name' => 'notice_badge', 'type' => 'text', 'default_value' => 'اطلاع‌رسانی' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_1_title', 'label' => 'اطلاعیه ۱ — عنوان', 'name' => 'notice_1_title', 'type' => 'text', 'default_value' => 'راهنمای عضویت جامعه حقوقی' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_1_description', 'label' => 'اطلاعیه ۱ — خلاصه', 'name' => 'notice_1_description', 'type' => 'textarea', 'default_value' => 'مراحل معرفی تخصص و تکمیل حساب', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_1_url', 'label' => 'اطلاعیه ۱ — لینک', 'name' => 'notice_1_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_2_title', 'label' => 'اطلاعیه ۲ — عنوان', 'name' => 'notice_2_title', 'type' => 'text', 'default_value' => 'آشنایی با اشتراک و سهمیه' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_2_description', 'label' => 'اطلاعیه ۲ — خلاصه', 'name' => 'notice_2_description', 'type' => 'textarea', 'default_value' => 'راهنمای اعلام آمادگی روی درخواست‌ها', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_2_url', 'label' => 'اطلاعیه ۲ — لینک', 'name' => 'notice_2_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_3_title', 'label' => 'اطلاعیه ۳ — عنوان', 'name' => 'notice_3_title', 'type' => 'text', 'default_value' => 'اصول ارجاع و پیگیری' );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_3_description', 'label' => 'اطلاعیه ۳ — خلاصه', 'name' => 'notice_3_description', 'type' => 'textarea', 'default_value' => 'آشنایی با مراحل رسیدگی به درخواست', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_notice_3_url', 'label' => 'اطلاعیه ۳ — لینک', 'name' => 'notice_3_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_article_title', 'label' => 'مطلب شاخص — عنوان', 'name' => 'news_article_title', 'type' => 'text', 'default_value' => 'دانش حقوقی کاربردی' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_article_description', 'label' => 'مطلب شاخص — خلاصه', 'name' => 'news_article_description', 'type' => 'textarea', 'default_value' => 'مطالب آموزشی و راهنمای خدمات', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_news_article_image', 'label' => 'مطلب شاخص — تصویر', 'name' => 'news_article_image', 'type' => 'image', 'default_value' => '', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_article_button', 'label' => 'مطلب شاخص — متن لینک', 'name' => 'news_article_button', 'type' => 'text', 'default_value' => 'مطالعه در مجله' );
    $fields[] = array( 'key' => 'field_pvdak_home_news_article_url', 'label' => 'مطلب شاخص — لینک', 'name' => 'news_article_url', 'type' => 'url', 'default_value' => '' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_title', 'label' => 'انجمن وکلا — تیتر اول', 'name' => 'forum_feature_title', 'type' => 'text', 'default_value' => 'یک جامعه' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_subtitle', 'label' => 'انجمن وکلا — تیتر دوم', 'name' => 'forum_feature_subtitle', 'type' => 'text', 'default_value' => 'چندین تجربه' );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_description', 'label' => 'انجمن وکلا — توضیح', 'name' => 'forum_feature_description', 'type' => 'textarea', 'default_value' => 'گفت‌وگو درباره مسائل تخصصی با اعضای جامعه حقوقی', 'rows' => 3 );
    $fields[] = array( 'key' => 'field_pvdak_home_forum_feature_button', 'label' => 'انجمن وکلا — متن دکمه', 'name' => 'forum_feature_button', 'type' => 'text', 'default_value' => 'ورود به انجمن وکلا' );
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

function pvdak_home_add_admin_menu() {
    add_menu_page('محتوای صفحه اصلی','محتوای صفحه اصلی','edit_pages','pvdak-home-content','pvdak_home_admin_landing','dashicons-admin-home',22);
}
add_action( 'admin_menu', 'pvdak_home_add_admin_menu' );

function pvdak_home_admin_redirect() {
    if ( empty($_GET['page']) || 'pvdak-home-content' !== sanitize_key(wp_unslash($_GET['page'])) ) { return; }
    $page_id = (int) get_option('page_on_front');
    if ( $page_id && 'page' === get_option('show_on_front') && 'page' === get_post_type($page_id) && current_user_can('edit_post',$page_id) ) {
        wp_safe_redirect( admin_url('post.php?post='.$page_id.'&action=edit') );
        exit;
    }
}
add_action( 'admin_init', 'pvdak_home_admin_redirect' );

function pvdak_home_admin_landing() {
    if ( ! current_user_can('edit_pages') ) { return; }
    echo '<div class="wrap"><h1>محتوای صفحه اصلی</h1>';
    if ( ! function_exists('acf_add_local_field_group') ) {
        echo '<div class="notice notice-warning"><p>برای ویرایش این فیلدها، افزونه رایگان Advanced Custom Fields (ACF) را نصب و فعال کنید.</p></div>';
    }
    echo '<p>ابتدا یک برگه را به عنوان صفحه اصلی در تنظیمات خواندن مشخص کنید، یا با دکمه زیر آن را بسازید.</p>';
    if ( current_user_can('manage_options') ) {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="pvdak_prepare_home_page">';
        wp_nonce_field('pvdak_prepare_home_page');
        submit_button('ایجاد و تنظیم برگه صفحه اصلی');
        echo '</form>';
    }
    echo '<p><a href="'.esc_url(admin_url('options-reading.php')).'">تنظیمات خواندن وردپرس</a></p></div>';
}
function pvdak_home_prepare_page() {
    if ( ! current_user_can('manage_options') ) { wp_die('دسترسی مجاز نیست.'); }
    check_admin_referer('pvdak_prepare_home_page');
    $page_id = (int) get_option('page_on_front');
    if ( ! $page_id || 'page' !== get_post_type($page_id) ) {
        $page_id = wp_insert_post( array('post_type'=>'page','post_status'=>'publish','post_title'=>'صفحه اصلی','post_name'=>'pvdak-home','post_content'=>'') , true );
        if ( is_wp_error($page_id) ) { wp_die( esc_html($page_id->get_error_message()) ); }
    }
    update_option('show_on_front','page');
    update_option('page_on_front',(int) $page_id);
    wp_safe_redirect( admin_url('post.php?post='.(int)$page_id.'&action=edit') );
    exit;
}
add_action('admin_post_pvdak_prepare_home_page','pvdak_home_prepare_page');
