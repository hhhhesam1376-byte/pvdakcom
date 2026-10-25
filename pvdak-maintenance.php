<?php
/**
 * PVDak temporary launch screen.
 *
 * The ordinary-user preview remains the default (maintenance OFF). Site
 * managers may switch maintenance on/off under Settings > General. An explicit
 * true PVDAK_MAINTENANCE_MODE constant is an emergency host override;
 * false (possibly left in wp-config from the previous release) does not
 * prevent using the General Settings checkbox.
 *
 * This only controls visibility, NOT registration, intake or subscriptions.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function pvdak_maintenance_enabled() {
    if ( defined( 'PVDAK_MAINTENANCE_MODE' ) && PVDAK_MAINTENANCE_MODE ) {
        return true;
    }
    return '1' === (string) get_option( 'pvdak_maintenance_enabled', '0' );
}

/** Settings > General uses WordPress's existing options.php capability/nonce. */
function pvdak_maintenance_sanitize_setting( $value ) {
    return is_scalar( $value ) && '1' === (string) $value ? '1' : '0';
}
function pvdak_maintenance_setting_field() {
    $enabled = '1' === (string) get_option( 'pvdak_maintenance_enabled', '0' );
    echo '<input type="hidden" name="pvdak_maintenance_enabled" value="0">';
    echo '<label for="pvdak-maintenance-enabled"><input type="checkbox" id="pvdak-maintenance-enabled" name="pvdak_maintenance_enabled" value="1" ' .
        checked( $enabled, true, false ) . '> فعال کردن صفحه تعمیرات برای بازدیدکنندگان عادی</label>';
    echo '<p class="description">با فعال‌کردن این گزینه، بازدیدکنندگان صفحه در حال توسعه را می‌بینند؛ مدیران واردشده همچنان سایت واقعی را مشاهده می‌کنند. این گزینه ثبت‌نام و پذیرش پرونده را فعال نمی‌کند.</p>';
    if ( defined( 'PVDAK_MAINTENANCE_MODE' ) && PVDAK_MAINTENANCE_MODE ) {
        echo '<p class="description" role="alert"><strong>توجه:</strong> حالت تعمیرات در تنظیمات سرور اجباری شده است. تا زمانی که مقدار PVDAK_MAINTENANCE_MODE فعال باشد، خاموش کردن این گزینه در پیشخوان اثری نخواهد داشت.</p>';
    }
}
function pvdak_maintenance_register_general_setting() {
    register_setting( 'general', 'pvdak_maintenance_enabled', array(
        'type' => 'string',
        'sanitize_callback' => 'pvdak_maintenance_sanitize_setting',
        'default' => '0',
    ) );
    add_settings_field(
        'pvdak_maintenance_enabled',
        'حالت تعمیرات PVDak',
        'pvdak_maintenance_setting_field',
        'general',
        'default',
        array( 'label_for' => 'pvdak-maintenance-enabled' )
    );
}
add_action( 'admin_init', 'pvdak_maintenance_register_general_setting' );

/**
 * The employer preview is publicly viewable but is NOT the final launch.
 * Keep preview pages out of search results until the owner explicitly
 * authorizes indexing using PVDAK_PUBLIC_LAUNCH_APPROVED in wp-config.php.
 * Do not change the site's existing WordPress search-visibility option.
 */
function pvdak_preview_robots( $robots ) {
    if ( ! defined( 'PVDAK_PUBLIC_LAUNCH_APPROVED' ) || ! PVDAK_PUBLIC_LAUNCH_APPROVED ) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'pvdak_preview_robots', 100 );
function pvdak_maintenance_can_preview_site() {
    return is_user_logged_in() && current_user_can( 'manage_options' );
}
function pvdak_maintenance_protect_rest( $result ) {
    if ( ! pvdak_maintenance_enabled() || pvdak_maintenance_can_preview_site() ) {
        return $result;
    }
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    return new WP_Error(
        'pvdak_site_in_development',
        'The site is temporarily unavailable while it is being developed.',
        array( 'status' => 503 )
    );
}
add_filter( 'rest_authentication_errors', 'pvdak_maintenance_protect_rest', 110 );
function pvdak_maintenance_render() {
    if ( ! pvdak_maintenance_enabled() || pvdak_maintenance_can_preview_site() ) {
        return;
    }
    // Our cPanel deploy health check expects HTTP 200 from the site homepage.
    // Keep a 200 placeholder response until the deploy checker can recognize maintenance 503.
    status_header( 200 );
    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
    header( 'Content-Type: text/html; charset=UTF-8', true );
    // Keep the artwork in uploads so deployment of the theme cannot delete it.
    // A theme-bundled version remains supported as a fallback.
    $uploads = wp_upload_dir( null, false );
    $upload_path = trailingslashit( $uploads['basedir'] ) . 'pvdak-maintenance/pvdak-coming-soon.png';
    $upload_url  = trailingslashit( $uploads['baseurl'] ) . 'pvdak-maintenance/pvdak-coming-soon.png';
    $theme_path  = get_template_directory() . '/assets/maintenance/pvdak-coming-soon.png';
    $theme_url   = get_template_directory_uri() . '/assets/maintenance/pvdak-coming-soon.png';
    if ( is_readable( $upload_path ) ) {
        $image_path = $upload_path;
        $image_url  = $upload_url;
    } else {
        $image_path = $theme_path;
        $image_url  = $theme_url;
    }
    $image_ready = is_readable( $image_path );
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/pvdak-pv.svg' ); ?>" type="image/svg+xml" sizes="any">
    <title>در حال توسعه | PVDak</title>
    <style>
        :root { color-scheme: dark; }
        *, *::before, *::after { box-sizing: border-box; }
        html { min-height: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px;
            background:
                radial-gradient(ellipse at 50% 0%, #324f61 0%, transparent 58%),
                radial-gradient(circle at 7% 88%, #1b4650 0%, transparent 38%),
                #101923;
            color: #f8fafc;
            font-family: Tahoma, 'Segoe UI', Arial, sans-serif;
            text-align: center;
            line-height: 1.9;
            overflow-x: hidden;
        }
        .panel {
            position: relative;
            width: min(100%, 760px);
            overflow: hidden;
            padding: clamp(26px, 5vw, 54px) clamp(18px, 5vw, 55px);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 30px;
            background: linear-gradient(145deg, rgba(255,255,255,.11), rgba(255,255,255,.025));
            box-shadow: 0 30px 90px rgba(0,0,0,.3);
        }
        .panel::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, transparent, #ffd66d, #73e4e9, transparent);
        }
        .eyebrow {
            display: inline-block;
            padding: 5px 17px;
            border: 1px solid rgba(255,214,109,.45);
            border-radius: 50px;
            background: rgba(255,214,109,.1);
            color: #ffda7a;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            direction: ltr;
        }
        .scene { max-width: 510px; margin: 20px auto 10px; }
        .scene svg { display: block; width: 100%; height: auto; overflow: visible; }
        .lightning { transform-origin: 50% 45%; animation: flash .85s infinite alternate; }
        .caveman { transform-origin: 50% 80%; animation: jitter .14s infinite alternate; }
        .glow { animation: glow 1.1s ease-in-out infinite alternate; }
        @keyframes flash { from { opacity: .5; transform: scale(.89); } to { opacity: 1; transform: scale(1.08); } }
        @keyframes jitter { from { transform: translate(-2px, 1px) rotate(-1deg); } to { transform: translate(2px, -1px) rotate(1deg); } }
        @keyframes glow { from { opacity: .4; } to { opacity: .9; } }
        h1 { margin: 7px 0 8px; color: #ffdc84; font-size: clamp(26px, 5vw, 42px); line-height: 1.55; }
        .lead { margin: 0; font-size: clamp(15px, 2.7vw, 21px); font-weight: bold; }
        .description { max-width: 510px; margin: 10px auto 0; color: #c9d6dc; font-size: 15px; }
        .footnote { margin: 22px 0 0; color: #95b2bd; font-size: 13px; }
        @media (prefers-reduced-motion: reduce) { .lightning, .caveman, .glow { animation: none; } }
        /* Full-screen cinematic artwork. The complete poster stays visible at any ratio. */
        body.pvdak-image-mode {
            display: block;
            padding: 0;
            overflow: hidden;
            background: #03192b;
        }
        .pvdak-fullscreen {
            position: relative;
            isolation: isolate;
            width: 100%;
            height: 100vh;
            height: 100dvh;
            min-height: 0;
            overflow: hidden;
            background: #03192b;
        }
        .pvdak-fullscreen::before {
            content: '';
            position: absolute;
            inset: -32px;
            z-index: -1;
            background-image: var(--pvdak-scene);
            background-position: center;
            background-size: cover;
            filter: blur(27px) brightness(.38);
            transform: scale(1.06);
        }
        .pvdak-fullscreen__image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center center;
        }
    </style>
</head>
<body<?php echo $image_ready ? ' class="pvdak-image-mode"' : ''; ?>>
    <?php if ( $image_ready ) : ?>
        <main class="pvdak-fullscreen" style="--pvdak-scene: url('<?php echo esc_url( $image_url ); ?>')">
            <img class="pvdak-fullscreen__image" src="<?php echo esc_url( $image_url ); ?>" alt="سایت در حال توسعه است؛ به‌زودی با تجربه‌ای بهتر برمی‌گردیم" decoding="async" fetchpriority="high">
        </main>
    <?php else : ?>
    <main class="panel">
        <span class="eyebrow">P V D A K · COMING SOON</span>
        <div class="scene">
            <svg viewBox="0 0 540 345" role="img" aria-label="انسان غارنشین بامزه‌ای که دو سیم برق را به هم نزدیک کرده و از جرقه جا خورده است" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="light"><stop stop-color="#ffe88d" stop-opacity=".65"/><stop offset="1" stop-color="#ffe88d" stop-opacity="0"/></radialGradient>
                    <linearGradient id="skin" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#f5bd84"/><stop offset="1" stop-color="#bf774b"/></linearGradient>
                </defs>
                <ellipse cx="270" cy="322" rx="155" ry="13" fill="#040b0f" opacity=".35"/>
                <circle class="glow" cx="270" cy="158" r="103" fill="url(#light)"/>
                <g class="caveman">
                    <path d="M230 194 Q208 205 207 249 L216 290 326 290 337 244 Q339 206 307 191Z" fill="#8b593d"/>
                    <path d="M238 201 Q276 177 308 202 L323 280 Q271 297 220 275Z" fill="url(#skin)"/>
                    <path d="M228 219 L288 194 330 247 303 292 257 275 229 292 216 262Z" fill="#bd865a" stroke="#925d3c" stroke-width="5"/>
                    <path d="M238 224 L248 234 256 216 270 232 282 211 295 231 313 216 324 248 310 279 290 271 274 292 257 274 231 287 217 258Z" fill="#e0a471"/>
                    <path d="M252 282 L245 316" stroke="#c68c65" stroke-width="21" stroke-linecap="round"/>
                    <path d="M298 282 L309 315" stroke="#c68c65" stroke-width="21" stroke-linecap="round"/>
                    <ellipse cx="235" cy="320" rx="25" ry="11" fill="#5e4337"/>
                    <ellipse cx="317" cy="320" rx="25" ry="11" fill="#5e4337"/>
                    <ellipse cx="270" cy="144" rx="55" ry="62" fill="url(#skin)"/>
                    <path d="M216 145 Q200 112 221 96 L231 76 245 87 258 65 277 82 298 66 317 88 338 107 321 144 314 114 297 121 278 109 250 119 231 109Z" fill="#4b3028"/>
                    <circle cx="248" cy="146" r="10" fill="white"/><circle cx="292" cy="146" r="10" fill="white"/>
                    <circle cx="250" cy="147" r="4" fill="#222"/><circle cx="294" cy="147" r="4" fill="#222"/>
                    <ellipse cx="272" cy="176" rx="13" ry="17" fill="#64392d"/>
                    <ellipse cx="272" cy="184" rx="7" ry="4" fill="#f29594"/>
                    <path d="M231 202 Q202 190 184 165 L218 143" stroke="#d99b6b" stroke-width="20" fill="none" stroke-linecap="round"/>
                    <path d="M307 203 Q340 190 357 165 L322 143" stroke="#d99b6b" stroke-width="20" fill="none" stroke-linecap="round"/>
                    <circle cx="218" cy="143" r="13" fill="#f0b580"/>
                    <circle cx="322" cy="143" r="13" fill="#f0b580"/>
                </g>
                <path d="M-15 136 Q68 120 133 139 T217 146" fill="none" stroke="#ff6e73" stroke-width="13" stroke-linecap="round"/>
                <path d="M555 136 Q474 121 414 139 T323 146" fill="none" stroke="#59dbee" stroke-width="13" stroke-linecap="round"/>
                <path d="M212 139 L232 146" stroke="#e7b987" stroke-width="7" stroke-linecap="round"/>
                <path d="M328 139 L308 146" stroke="#e7b987" stroke-width="7" stroke-linecap="round"/>
                <g class="lightning" fill="#ffe36a" stroke="#ffb933" stroke-width="2">
                    <path d="M270 99 256 132 273 132 251 181 293 141 275 141 298 99Z"/>
                    <path d="M237 119 221 139 238 140 224 161 253 136 239 136Z"/>
                    <path d="M302 120 291 140 305 141 295 160 322 135 307 135Z"/>
                </g>
                <g fill="#fff4bd">
                    <circle cx="199" cy="109" r="4"/><circle cx="340" cy="110" r="4"/>
                    <circle cx="191" cy="192" r="3"/><circle cx="356" cy="192" r="3"/>
                </g>
            </svg>
        </div>
        <h1>سایت در حال توسعه است ⚡</h1>
        <p class="lead">داریم سیم‌کشی تمدن دیجیتال رو انجام می‌دیم! 😄</p>
        <p class="description">یه اتصال کوچولو مونده تا همه‌چیز روشن بشه. به‌زودی با کلی امکانات جذاب برمی‌گردیم.</p>
        <p class="footnote">PVDak · به‌زودی می‌بینیمتون!</p>
    </main>
    <?php endif; ?>
</body>
</html>
    <?php
    exit;
}
add_action( 'template_redirect', 'pvdak_maintenance_render', 0 );
