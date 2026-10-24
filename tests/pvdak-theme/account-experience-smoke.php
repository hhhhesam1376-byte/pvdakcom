<?php
/** Source-only account UX regression; no WordPress bootstrap, users or DB. */
$root = dirname( __DIR__, 2 );
$paths = array(
  'portal'=>'page-templates/pvdak-account.php',
  'front'=>'front-page.php',
  'login'=>'page-templates/pvdak-login.php',
  'profile'=>'page-templates/pvdak-profile.php',
  'recovery'=>'page-templates/pvdak-recovery.php',
  'register'=>'page-templates/pvdak-register.php',
  'contact'=>'page-templates/pvdak-contact.php',
  'header'=>'header.php',
  'footer'=>'footer.php',
  'nav'=>'inc/pvdak-navigation-links.php',
  'assets'=>'functions.php',
  'javascript'=>'assets/js/pvdak-account.js',
  'css'=>'style.css',
  'homejs'=>'assets/js/pvdak-home.js',
  'editorialcss'=>'assets/css/pvdak-editorial.css',
  'ticketcss'=>'assets/css/pvdak-support.css',
  'card'=>'template-parts/content-card.php',
  'article'=>'template-parts/content-single.php',
  'homejs'=>'assets/js/pvdak-home.js',
);
$v = array();
foreach ( $paths as $name => $file ) {
  $path = $root . '/' . $file;
  if ( ! is_readable( $path ) ) { fwrite( STDERR, "ACCOUNT_UX_FAIL: missing $file\n" ); exit(2); }
  $v[$name] = file_get_contents( $path );
}
$checks = array(
  '098-01 Hero search always has a real fallback mode' =>
    strpos($v['front'], 'name="<?php echo $all_legal_pages ?')!==false &&
    strpos($v['front'], '$all_legal_pages ? home_url(')!==false,
  '098-02 Search can be cleared without a page reload' =>
    strpos($v['front'],'data-pvdak-clear-search')!==false &&
    strpos($v['homejs'],"clear.addEventListener('click'")!==false,
  '098-03 Swiper narrow cards use a visible peek and matching breakpoints' =>
    strpos($v['homejs'],"(max-width: 400px)")!==false &&
    strpos($v['homejs'],'slidesPerView: 1.15')!==false,
  '098-04 Swiper focus and hidden tabs do not autoplay unnoticed' =>
    strpos($v['homejs'],"document.addEventListener('visibilitychange', updateAutoplay)")!==false &&
    strpos($v['homejs'],'updateAutoplay();')!==false,
  '098-05 Topic count comes from published matching Pages' =>
    strpos($v['front'],'count( $legal_pages )')!==false &&
    strpos($v['front'],'pvdak-home-section-caption')!==false,
  '098-06 Intake-closed process is described as introductory' =>
    strpos($v['front'],"pvdak_core_intake_open() ? 'مسیر همراهی'")!==false &&
    strpos($v['front'],'پس از آغاز رسمی خدمات')!==false,
  '098-07 Empty notices are never rendered in populated news grid' =>
    strpos($v['front'],'<?php if ( $notice_posts ): ?>')!==false &&
    strpos($v['front'],'if ( $notice_posts || $news_feature )')!==false,
  '098-08 Published notices include real dates and destination links' =>
    strpos($v['front'], "get_the_date( 'Y-m-d', " . '$notice_post->ID )')!==false &&
    strpos($v['front'], 'get_permalink( $notice_post->ID )')!==false,
  '098-09 News without thumbnail uses bundled vector instead of blank box' =>
    strpos($v['front'],'pvdak-news-fallback-art')!==false &&
    strpos($v['front'],'has_post_thumbnail( $news_feature->ID )')!==false,
  '098-10 News card includes real publication date' =>
    strpos($v['front'], "get_the_date( 'Y-m-d', " . '$news_feature->ID )')!==false,
  '098-11 Global logged-in support shortcut retains role gate' =>
    strpos($v['header'],"is_user_logged_in() && shortcode_exists( 'pvdak_support_tickets' )")!==false &&
    strpos($v['header'],"pvdak_auth_account_url() . '#pvdak-support'")!==false,
  '098-12 Header search requires a nonempty native WordPress term' =>
    strpos($v['header'],'name="s"')!==false &&
    strpos($v['header'],'maxlength="100" required')!==false,
  '098-13 Mobile details use a described trigger and visible icon' =>
    strpos($v['header'],'pvdak-mobile-menu-icon')!==false &&
    strpos($v['header'],'باز و بسته کردن فهرست سایت')!==false,
  '098-14 Footer has accessible return-to-content link' =>
    strpos($v['footer'],'class="pvdak-back-top" href="#main-content"')!==false &&
    strpos($v['css'],'.site-footer .pvdak-back-top')!==false,
  '098-15 Core-unavailable fallback is honest and links to existing profile' =>
    strpos($v['portal'],'pvdak-unified-empty-dashboard')!==false &&
    strpos($v['portal'],'href="#pvdak-profile"')!==false,
  '098-16 Private support section remains account gated' =>
    strpos($v['portal'],'if ( ! is_user_logged_in() )')!==false &&
    strpos($v['portal'],'if ( $has_support )')!==false &&
    strpos($v['portal'],'پشتیبانی • فقط حساب شما')!==false,
  '098-17 Login error visibly associates with real credentials fields' =>
    strpos($v['login'],'aria-invalid="true" aria-describedby="pvdak-login-result"')!==false &&
    strpos($v['login'],"wp_nonce_field('pvdak_login'")!==false,
  '098-18 Shared 860/680/410 breakpoints prevent overflowing news cards' =>
    strpos($v['css'],'@media(max-width:860px)')!==false &&
    strpos($v['css'],'@media(max-width:680px)')!==false &&
    strpos($v['css'],'@media(max-width:410px)')!==false,
  '098-19 Ticket registration and Core private data stay untouched' =>
    strpos($v['portal'],"do_shortcode( '[pvdak_account_dashboard]' )")!==false &&
    strpos($v['portal'],"do_shortcode( '[pvdak_support_tickets]' )")!==false,
  '098-20 Theme version, local-only UI and safe fallback all present' =>
    strpos($v['css'],'Version: 0.8.38')!==false &&
    strpos($v['css'],'WORK_BATCH_098')!==false &&
    strpos($v['javascript'],'fetch(')===false,

  '097-01 Homepage has no empty topics or technical setup' =>
    strpos($v['front'],'pvdak-topics-help')!==false &&
    strpos($v['front'],'pvdak-legal-topics-setup')===false,
  '097-02 Live dashboard uses real Core shortcode and stats layout' =>
    strpos($v['portal'],"do_shortcode( '[pvdak_account_dashboard]' )")!==false &&
    strpos($v['css'],'.pvdak-core-stats>div')!==false,
  '097-03 RTL mobile layout at 480px' =>
    strpos($v['css'],'@media(max-width:480px)')!==false &&
    strpos($v['css'],'.pvdak-home-quick-actions')!==false,
  '097-04 Hero uses genuine in-page navigation' =>
    strpos($v['front'],'pvdak-home-hero-steps')!==false &&
    strpos($v['front'],'href="#topics"')!==false,
  '097-05 Four configured quick pages retain published links' =>
    strpos($v['front'],'pvdak_home_quick_pages()')!==false &&
    strpos($v['front'],'pvdak-home-quick-icon')!==false &&
    strpos($v['front'],"esc_url( $quick_page['url'] )")!==false,
  '097-06 Swiper controls actually label their track' =>
    strpos($v['front'],'aria-controls="pvdak-topics-track"')!==false &&
    strpos($v['front'],'id="pvdak-topics-track"')!==false &&
    strpos($v['homejs'],'delay: 4700')!==false,
  '097-07 Owner-only requests panel remains gated' =>
    strpos($v['portal'],"if ( $has_requests )")!==false &&
    strpos($v['portal'],"do_shortcode( '[pvdak_my_requests]' )")!==false,
  '097-08 Membership panel remains conditional with status cards' =>
    strpos($v['portal'],'if ( $has_professional )')!==false &&
    strpos($v['css'],'.pvdak-core-dashboard>h3:last-of-type + .pvdak-core-notice')!==false,
  '097-09 Private support keeps shortcode and dedicated CSS' =>
    strpos($v['portal'],"do_shortcode( '[pvdak_support_tickets]' )")!==false &&
    strpos($v['ticketcss'],'.pvdak-ticket-conversation')!==false &&
    strpos($v['ticketcss'],'WORK_BATCH_097')!==false,
  '097-10 One sidebar supports keyboard accessible tabs' =>
    strpos($v['portal'],'data-pvdak-workspace')!==false &&
    strpos($v['javascript'],"event.key !== 'Escape'")!==false &&
    strpos($v['javascript'],"destination.focus({ preventScroll: true })")!==false,
  '097-11 Login retains secure submit with readable trust note' =>
    strpos($v['login'],"wp_nonce_field('pvdak_login'")!==false &&
    strpos($v['login'],'pvdak-login-trust-note')!==false,
  '097-12 Profile retains nonce and readonly account identity' =>
    strpos($v['portal'],"wp_nonce_field( 'pvdak_profile_save'")!==false &&
    strpos($v['portal'],'pvdak-profile-readonly')!==false,
  '097-13 Public pages retain a branded legal content structure' =>
    strpos($v['css'],'.page-entry :where(h2,h3)')!==false &&
    strpos($v['css'],'.page-entry :where(table)')!==false,
  '097-14 Real editorial categories and local missing-cover art' =>
    strpos($v['card'],'get_the_category()')!==false &&
    strpos($v['card'],'pvdak-card-art')!==false &&
    strpos($v['editorialcss'],'.pvdak-editorial-tile')!==false,
  '097-15 Article TOC built from rendered authored headings' =>
    strpos($v['article'],"apply_filters( 'the_content', get_the_content() )")!==false &&
    strpos($v['article'],'pvdak-article-toc')!==false &&
    strpos($v['editorialcss'],'.pvdak-article-toc ol')!==false,
  '097-16 FAQ appears only with published page data' =>
    strpos($v['front'],'if ( $faq_pages )')!==false &&
    strpos($v['front'],'pvdak-faq-item')!==false &&
    strpos($v['front'],'get_the_title( $faq_page->ID )')!==false,
  '097-17 Contact form maintains nonce/consent and status display' =>
    strpos($v['contact'],"wp_nonce_field('pvdak_contact_form'")!==false &&
    strpos($v['contact'],'contact_consent')!==false &&
    strpos($v['contact'],'pvdak-contact-result')!==false,
  '097-18 Footer links remain existing real WordPress pages' =>
    strpos($v['footer'],'pvdak-footer-bottom-mark')!==false &&
    strpos($v['footer'],"pvdak_public_page_url( 'contact-us' )")!==false,
  '097-19 Design tokens and reduced-motion support' =>
    strpos($v['css'],'--pvdak-97-navy')!==false &&
    strpos($v['css'],'@media(prefers-reduced-motion:reduce)')!==false,
  '097-20 No fake data or new frontend request for UI' =>
    strpos($v['javascript'],'fetch(')===false &&
    preg_match('/Version:\s*0[.]8[.](?:3[8-9]|[4-9][0-9])/', $v['css'])===1,

  '01 Global branding with scales icon' =>
    strpos($v['header'],'pvdak-profile-brand-scale')!==false &&
    strpos($v['css'],'.site-header .site-branding .pvdak-profile-brand-scale')!==false,
  '02 Sitewide native WordPress content search' =>
    strpos($v['header'],'class="pvdak-site-search pvdak-profile-header-search"')!==false &&
    strpos($v['header'],'method="get"')!==false &&
    strpos($v['header'],'name="s"')!==false &&
    strpos($v['css'],'.site-header .pvdak-site-search')!==false,
  '03 Accessible native mobile navigation and active fallback links' =>
    strpos($v['header'],'<details class="pvdak-mobile-menu">')!==false &&
    strpos($v['header'],'aria-current="page"')!==false &&
    strpos($v['css'],'.site-header .pvdak-mobile-menu[open]>nav')!==false,
  '04 Footer uses branded legal marks and real destinations' =>
    strpos($v['footer'],'pvdak-footer-heading')!==false &&
    strpos($v['footer'],'pvdak-footer-contact-link')!==false &&
    strpos($v['footer'],'pvdak-footer-tagline')!==false,
  '05 Homepage hero gets vivid branded search styling' =>
    strpos($v['front'],'class="pvdak-home-hero"')!==false &&
    strpos($v['css'],'.pvdak-home-hero::after')!==false &&
    strpos($v['css'],'.pvdak-home-search:focus-within')!==false,
  '06 Four shortcuts remain selected and styled, without fabricated links' =>
    strpos($v['front'],'pvdak_home_quick_pages()')!==false &&
    strpos($v['css'],'.pvdak-home-quick-actions>a')!==false,
  '07 Swiper topic cards retain controls and better contrast' =>
    strpos($v['front'],'data-pvdak-topic-prev')!==false &&
    strpos($v['front'],'swiper-wrapper')!==false &&
    strpos($v['css'],'.pvdak-topics-section .pvdak-home-topic:hover')!==false,
  '08 Editorial cards and timeline share a visual system' =>
    strpos($v['css'],'.pvdak-magazine-feature,.pvdak-magazine-aside-card,.pvdak-notices-panel')!==false &&
    strpos($v['css'],'.pvdak-home-process{background:linear-gradient')!==false,
  '09 Public information/contact retain native forms and improved cards' =>
    strpos($v['contact'],'pvdak_contact_allowed()')!==false &&
    strpos($v['css'],'.page-entry{background:#fff')!==false &&
    strpos($v['css'],'.pvdak-contact-page .pvdak-forms button[type=submit]')!==false,
  '10 Account dashboard adds live gated quick links and styling' =>
    strpos($v['portal'],'pvdak-dashboard-quicklinks')!==false &&
    strpos($v['portal'],'if ( $has_support )')!==false &&
    strpos($v['portal'],'id="pvdak-profile"')!==false &&
    strpos($v['css'],'.pvdak-dashboard-quicklinks{')!==false,
  'Card-based personal dashboard with live Core values' =>
    strpos($v['portal'],"do_shortcode( '[pvdak_account_dashboard]' )")!==false &&
    strpos($v['css'],'.pvdak-unified-account #pvdak-dashboard .pvdak-core-stats>div')!==false &&
    strpos($v['css'],'.pvdak-unified-account #pvdak-dashboard .pvdak-core-requests li')!==false &&
    strpos($v['css'],'.pvdak-unified-account #pvdak-dashboard .pvdak-core-dashboard>.pvdak-core-empty')!==false,
  'Unconfigured service shortcuts are hidden without fake actions' =>
    strpos($v['css'],'h3:has(+ .pvdak-core-dashboard-actions:empty)')!==false &&
    strpos($v['css'],'.pvdak-core-dashboard-actions:empty{display:none}')!==false,
  'Account sidebar and personal shortcuts' =>
    strpos($v['portal'],'pvdak-account-sidebar')!==false &&
    strpos($v['portal'],'id="pvdak-profile"')!==false &&
    strpos($v['portal'],'pvdak-account-signout')!==false,
  'Dashboard and private support anchor are connected' =>
    strpos($v['portal'],'id="pvdak-dashboard"')!==false &&
    strpos($v['portal'],'href="#pvdak-support"')!==false &&
    strpos($v['portal'],"do_shortcode( '[pvdak_support_tickets]' )")!==false,
  'Professional panel remains conditional' =>
    strpos($v['portal'],'$has_professional')!==false &&
    strpos($v['portal'],'pvdak_core_existing_application(')!==false &&
    strpos($v['portal'],'id="pvdak-professional"')!==false,
  'Portal reports real intake state' =>
    strpos($v['portal'],'pvdak_core_intake_open()')!==false &&
    strpos($v['portal'],'ثبت درخواست حقوقی جدید فعلاً غیرفعال')!==false,
  'Login retains real WordPress handler, nonce and recovery' =>
    strpos($v['login'],'value="pvdak_login"')!==false &&
    strpos($v['login'],"wp_nonce_field('pvdak_login'")!==false &&
    strpos($v['login'],'pvdak_account_tool_url(\'account-recovery\')')!==false,
  'Password toggle works without backend changes' =>
    strpos($v['login'],'data-pvdak-password-toggle')!==false &&
    strpos($v['recovery'],'data-pvdak-password-toggle')!==false &&
    strpos($v['register'],'data-pvdak-password-toggle')!==false &&
    strpos($v['javascript'],"field.type = visible ? 'text' : 'password'")!==false,
  'Premium profile has RTL hero, live editable form and proper navigation' =>
    strpos($v['portal'], 'pvdak-profile-redesign') !== false &&
    strpos($v['portal'], 'pvdak-account-hero') !== false &&
    strpos($v['portal'], 'pvdak-profile-editor') !== false &&
    strpos($v['portal'], 'pvdak-account-menu') !== false &&
    strpos($v['portal'], 'wp_logout_url(') !== false &&
    strpos($v['portal'], 'data-pvdak-profile-form') !== false &&
    strpos($v['portal'], 'data-pvdak-profile-save') !== false,
  'Profile styling is scoped and responsive with no remote font dependency' =>
    strpos($v['css'], 'body.pvdak-profile-screen') !== false &&
    strpos($v['css'], '.pvdak-profile-workspace{') !== false &&
    strpos($v['css'], '.pvdak-profile-rail{') !== false &&
    strpos($v['css'], '@media(max-width:540px)') !== false,
  'Profile hero artwork is local and references the legal image' =>
    is_file($root . '/assets/icons/pvdak-profile-hero.svg') &&
    strpos($v['portal'], 'pvdak-account-hero')!==false &&
    strpos($v['css'], "url('assets/icons/pvdak-profile-hero.svg')")!==false,
  'Unified account header has working WordPress search and support actions' =>
    strpos($v['header'], 'pvdak-profile-header-search')!==false &&
    strpos($v['header'], 'name="s"')!==false &&
    strpos($v['header'], "is_page_template( 'page-templates/pvdak-account.php' )")!==false,
  'Unified account footer includes real WordPress search form' =>
    strpos($v['footer'], 'pvdak-profile-footer-search')!==false &&
    strpos($v['footer'], 'name="s"')!==false &&
    strpos($v['footer'], "is_page_template( 'page-templates/pvdak-account.php' )")!==false,
  'Reference proportions and trusted form interactions remain' =>
    strpos($v['css'], '.pvdak-profile-hero-photo{')!==false &&
    strpos($v['css'], '.pvdak-profile-bottom{')!==false &&
    strpos($v['portal'], 'pvdak-profile-shield-art')!==false &&
    strpos($v['portal'], 'data-pvdak-profile-save')!==false &&
    strpos($v['portal'], 'wp_nonce_field(')!==false,
  'Profile keeps nonce and provides account return path' =>
    strpos($v['portal'],'pvdak_profile_save')!==false &&
    strpos($v['portal'],'pvdak_auth_account_url()')!==false &&
    strpos($v['portal'],'pvdak-profile-details')!==false,
  'Unified panel contains exactly one editable profile instead of two UIs' =>
    substr_count( $v['portal'], 'data-pvdak-profile-form' ) === 1 &&
    strpos( $v['profile'], 'data-pvdak-profile-form' ) === false &&
    strpos( $v['portal'], "wp_nonce_field( 'pvdak_profile_save'") !== false,
  'Profile legacy route and save feedback end in same profile tab' =>
    strpos( $v['profile'], "wp_safe_redirect( $target . '#pvdak-profile', 302 )" ) !== false &&
    strpos( file_get_contents( $root . '/inc/pvdak-account-tools.php' ), "add_query_arg( 'status', 'unchanged', $url ) . '#pvdak-profile'" ) !== false,
  'All panels are hash navigated without backend changes' =>
    strpos( $v['javascript'], "target.closest('[data-pvdak-panel]')" ) !== false &&
    strpos( $v['javascript'], 'panels.forEach(' ) !== false &&
    strpos( $v['css'], '[data-pvdak-panel][hidden]{display:none!important}' ) !== false,
  'Support shortcode ID remains unique while wrapper is a panel' =>
    strpos( $v['portal'], 'data-pvdak-panel aria-label="پشتیبانی و تیکت‌ها"' ) !== false &&
    substr_count( $v['portal'], 'id="pvdak-support"' ) === 0 &&
    strpos( $v['portal'], "do_shortcode( '[pvdak_support_tickets]' )" ) !== false,
  'Legacy profile transparently redirects to the unified panel' =>
    strpos($v['profile'], "wp_safe_redirect( $target . '#pvdak-profile', 302 )")!==false &&
    strpos($v['profile'], 'pvdak_auth_account_url()')!==false &&
    strpos($v['profile'], 'get_header()')===false,
  'One dashboard includes accessible tabs for all current features' =>
    strpos($v['portal'], 'data-pvdak-workspace')!==false &&
    strpos($v['portal'], 'data-pvdak-panel')!==false &&
    strpos($v['portal'], 'id="pvdak-dashboard"')!==false &&
    strpos($v['portal'], 'id="pvdak-profile"')!==false &&
    strpos($v['portal'], 'id="pvdak-security"')!==false &&
    strpos($v['portal'], "do_shortcode( '[pvdak_my_requests]' )")!==false &&
    strpos($v['portal'], "do_shortcode( '[pvdak_support_tickets]' )")!==false,
  'Consent links only go to published policies' =>
    strpos($v['register'],"pvdak_public_page_url( 'terms-of-use' )")!==false &&
    strpos($v['register'],"pvdak_public_page_url( 'privacy-policy' )")!==false &&
    strpos($v['contact'],"pvdak_public_page_url( 'privacy-policy' )")!==false,
  'Primary and footer links come from published Pages' =>
    substr_count($v['header'],'pvdak_default_navigation_links()')===2 &&
    strpos($v['footer'],'pvdak_default_navigation_links()')!==false &&
    strpos($v['footer'],"pvdak_public_page_url( 'account-login' )")!==false &&
    strpos($v['nav'],'pvdak_public_page_url( $slug )')!==false &&
    strpos($v['nav'],'get_term_link(')!==false,
  'Interaction script is locally scoped and layouts responsive' =>
    strpos($v['assets'],"'pvdak-account-interactions'")!==false &&
    strpos($v['assets'],'is_page( array( \'account-login\'')!==false &&
    strpos($v['css'],'@media(max-width:720px)')!==false &&
    strpos($v['javascript'],'fetch(')===false,
);
$n = 0;
foreach ( $checks as $label => $pass ) {
  if ( ! $pass ) { fwrite(STDERR,"ACCOUNT_UX_FAIL: $label\n"); exit(1); }
  $n++;
}
echo "PVDAK_ACCOUNT_EXPERIENCE_OK: $n source checks (no WP/browser tests)\n";
