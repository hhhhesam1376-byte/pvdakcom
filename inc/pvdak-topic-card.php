<?php
/** Custom inline vector artwork for WordPress legal-topic Page cards. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Informative but not substantive legal advice; editorial titles remain in WP. */
function pvdak_topic_card_caption( $page ) {
    if ( ! $page instanceof WP_Post ) { return 'آشنایی با موضوع'; }
    $captions = array(
        'civil-law' => 'دعاوی و اختلافات حقوقی',
        'criminal-cases' => 'پرونده‌ها و موضوعات کیفری',
        'family' => 'امور خانواده و روابط',
        'registrations' => 'اسناد و امور ثبتی',
        'property-cases' => 'ملک و قراردادهای مرتبط',
        'children-and-adolescents-rights' => 'حقوق کودک و نوجوان',
        'land-law' => 'اراضی و مسائل زمین',
        'tax-law' => 'موضوعات مالیاتی',
        'human-rights' => 'حقوق بنیادین اشخاص',
        'company-law' => 'شرکت‌ها و فعالیت تجاری',
        'intellectual-property-law' => 'آثار و مالکیت فکری',
        'registration-law' => 'ثبت و اسناد رسمی',
        'family-rights' => 'قوانین و حقوق خانواده',
        'criminal-law-rights' => 'قوانین و حقوق کیفری',
        'insurance-law' => 'بیمه و تعهدات',
        'sports-law' => 'مسائل و قراردادهای ورزشی',
        'labor-law' => 'روابط کار و کارفرما',
    );
    if ( isset( $captions[ $page->post_name ] ) ) { return $captions[ $page->post_name ]; }
    return $page->post_excerpt ? wp_trim_words( wp_strip_all_tags( $page->post_excerpt ), 6, '…' ) : 'آشنایی با موضوع';
}

/** Each of the original 17 landing-page slugs has its own hand-authored SVG. */
function pvdak_topic_card_svg( $page ) {
    $icons = array(
        'civil-law' => '<path d="M24 7v32M15 40h18M13 13h22M12 13 6 26h12l-6-13Zm24 0-6 13h12l-6-13Z"/><path d="M20 7h8m-7 33h6"/>',
        'criminal-cases' => '<path d="m11 11 12 12m-7-17 17 17M6 17l11-11 17 17-11 11L6 23Zm15 8L8 38m-3 4h17"/><circle cx="34" cy="35" r="4"/>',
        'family' => '<circle cx="16" cy="15" r="5"/><circle cx="33" cy="15" r="5"/><path d="M6 39v-5a10 10 0 0 1 17-7m19 12v-5a10 10 0 0 0-17-7"/><path d="M24 33c-7-5-10 2 0 9 10-7 7-14 0-9Z"/>',
        'registrations' => '<path d="M11 6h21l6 6v29H11zM32 6v8h6M16 21h17M16 27h15M16 33h9"/><circle cx="34" cy="34" r="6"/><path d="m30 39-2 5 6-2"/>',
        'property-cases' => '<path d="m5 23 19-16 19 16M10 21v21h28V21M21 42V29h9v13"/><path d="M16 22h4m12 0h4M17 12h14"/>',
        'children-and-adolescents-rights' => '<path d="M24 7c8 5 14 4 17 4v11c0 11-7 17-17 22C14 39 7 33 7 22V11c4 0 10 1 17-4Z"/><circle cx="24" cy="20" r="5"/><path d="M15 35c1-10 17-10 18 0"/>',
        'land-law' => '<path d="m5 15 12-7 14 7 12-7v29l-12 7-14-7-12 7V15Zm12-7v29m14-22v29"/><path d="m20 20 4 5 7-9"/>',
        'tax-law' => '<path d="M11 6h26v36H11zM17 16h14M17 23h7M17 35h14"/><circle cx="29" cy="28" r="5"/><path d="m26 31 6-6"/>',
        'human-rights' => '<circle cx="24" cy="13" r="6"/><path d="M13 42V28c-5-3-8-8-8-14m30 28V28c5-3 8-8 8-14M16 25l8 6 8-6M16 42V32m16 10V32"/>',
        'company-law' => '<path d="M8 42V12h19v30M27 21h13v21M5 42h38M13 19h7m-7 7h7m-7 7h7m13-5h7m-7 7h7"/><path d="M20 42v-6h7"/>',
        'intellectual-property-law' => '<path d="M19 34v-5c-11-10-5-22 5-22s16 12 5 22v5H19Zm2 5h6m-5 4h4"/><path d="M24 1v3M10 7l3 3M38 7l-3 3M8 23h4m24 0h4"/>',
        'registration-law' => '<path d="M9 7h27v35H9V7Zm7 9h14m-14 7h14m-14 7h8"/><circle cx="33" cy="34" r="7"/><path d="m30 34 3 3 5-7"/>',
        'family-rights' => '<path d="M24 7v34M13 42h22M12 14h24M11 14 5 27h12l-6-13Zm26 0-6 13h12l-6-13Z"/><path d="M20 5c2-3 6-3 8 0"/>',
        'criminal-law-rights' => '<path d="M24 5c8 5 14 5 18 5v13c0 10-7 16-18 21C13 39 6 33 6 23V10c4 0 10 0 18-5Z"/><path d="m17 24 5 5 10-11"/>',
        'insurance-law' => '<path d="M24 5c7 5 13 5 17 5v13c0 11-7 18-17 21C14 41 7 34 7 23V10c4 0 10 0 17-5Z"/><path d="M17 25a7 7 0 0 1 14 0v9H17zM20 33h8"/>',
        'sports-law' => '<circle cx="24" cy="20" r="13"/><path d="m24 7 6 5-2 9h-8l-2-9 6-5Zm-14 9 8-4m12 0 8 4m-20 5-5 9m17-9 5 9M17 35l-3 8h20l-3-8"/>',
        'labor-law' => '<rect x="6" y="15" width="36" height="27" rx="4"/><path d="M17 15V9h14v6M6 26h36M21 24v7h6v-7"/><path d="M13 35h8m6 0h8"/>',
    );
    $slug = $page instanceof WP_Post ? (string) $page->post_name : '';
    if ( ! isset( $icons[ $slug ] ) ) {
        $fallback = $page instanceof WP_Post ? pvdak_legal_topic_icon( $page->ID ) : 'contract';
        return pvdak_legal_topic_svg( $fallback );
    }
    return '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        $icons[ $slug ] . '</svg>';
}
