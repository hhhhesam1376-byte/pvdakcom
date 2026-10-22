# PVDak — Development Log

## 2026-10-21 — HOME-ACF-001
- Branch: `main`
- Theme version: `0.3.0`
- Implemented ACF Free-compatible homepage content fields in `inc/pvdak-home-acf.php`.
- Registered 8 editor tabs: hero, services, audiences, topics, process, magazine/forum, membership, FAQs.
- 77 text, textarea, URL, and image fields. All use stable keys to preserve client-entered values across theme updates.
- Added `محتوای صفحه اصلی` menu in WordPress admin. It links to the actual front page editor.
- If the WordPress static front page is not configured, the menu offers a permission/nonce-protected one-click setup.
- `front-page.php` now reads editable content, with layout/style in `assets/css/pvdak-home.css`.
- Hero, magazine, forum, and member call-to-action images can be changed in WordPress Media Library.
- Action destination links remain optional until their workflows are implemented; no fictitious request forms are linked.
- Kept public maintenance screen (`pvdak-maintenance.php`) untouched.

### Deployment and validation
- PHP syntax checked on local working-copy templates before commit.
- GitHub writes successful; runtime installation and ACF activation **not verified**.
- Site has a 15-minute cron deploy according to prior server logs, but new release and WordPress admin should be inspected directly.
- Dependency: install/activate Advanced Custom Fields (free or PRO). No PRO-only Repeater or Options Page feature required.
- After deploy: open WordPress admin, click `محتوای صفحه اصلی`; if necessary configure static homepage using the one-click setup; edit fields and verify in an admin-signed-in browser while maintenance is active.

### Pending
1. Verify ACF plugin present, group visible, text and photo changes saved correctly.
2. Match all desktop/mobile visual details of mockup v1.01.05.
3. Implement real request submission workflow and link destinations.
4. Add structured taxonomy and dynamic content for legal subjects, magazine, and forum.
5. Perform release smoke testing and mobile layout review.

## 2026-10-21 — HOME-UI-002 (v0.4.0)
- Shipped improved header and footer aligned with the supplied desktop/mobile design `1.01.05`.
- The standard WordPress menu editor controls both desktop and mobile navigation when a menu is assigned; a sensible anchor-link fallback is provided otherwise.
- Added login entry linked to the standard WordPress login URL; role-based registration and specialized dashboards are **not implemented** yet.
- Homepage hero now supports separate desktop/mobile image fields through ACF; image sources use Media Library IDs.
- Added the three service shortcuts inside the hero, additional cross-page quick links, and live topic filtering with a Persian-normalized client-side script. Search falls back to WordPress site search when JavaScript is off.
- Updated responsive CSS, cards, topics grid, and mobile appearance with the mockup as layout reference.
- Version bumped to `0.4.0`; deployed assets are expected to be served from the theme.
- All editable content remains linked to the existing 8-tab ACF field group. New field: `hero_image_mobile` (78 content fields total).
- No plugin purchase required; Advanced Custom Fields Free is the intended dependency.
- Public maintenance view and its separate uploaded image directory remain unchanged.

### Validation / deployment
- GitHub Push successful. Repository files were re-fetched for consistency.
- **Server-side PHP lint and end-to-end WordPress/ACF testing have not yet been completed.**
- Live homepage is behind the maintenance screen; an admin login is needed to preview it.
- To test on the target server, confirm version 0.4.0 has deployed, ACF is active, and set a static front page using the `محتوای صفحه اصلی` entry; then change hero image and copy and reload the homepage as an administrator.
- Customer request forms, lawyer verification, case matching, subscription billing and real service routes remain future work.

## 2026-10-21 — HOME-DESIGN-003 (v0.5.0)
- The customer supplied the actual desktop screenshot of the approved PVDak version `1.01.05`. Corrected earlier generic implementation to reflect its **actual composition**.
- Preserved photo-led legal-consultation hero and dark overlay, 3 white service action cards, and a raised white four-shortcut strip (not a four-link table).
- Followed intended RTL order: rightmost audience card navy; eight teal line-icon topic cards; four-step navy/teal timeline.
- Rebuilt **magazine** as photo feature on the right plus two stacked pastel editorial cards on the left.
- Added entirely separate **news/announcements** 3-column region: white notices panel on the right, featured photo article in the center and dark forum card on the left.
- Reworked green membership banner and FAQ accordions; retained native WordPress header/footer and accessible semantic content.
- Extended the existing ACF Free editor with quick links, the 2 magazine side cards, and the announcements/news panel. Editor now has 11 tabs and 117 fields (including optional mobile hero image).
- All buttons without a configured destination are deliberately not clickable while application services are unimplemented.
- Original photos were extracted from the supplied design as a separate bundle for use in WordPress Media Library. They are not yet present in the repository or live media library; the photo slots therefore show gradient placeholders until ACF images are configured.
- Theme version is `0.5.0`; public maintenance screen is unchanged.

### Verification still required
- Confirm the updated theme actually deploys with the existing cPanel cron.
- PHP lint, visual desktop/mobile screenshot comparison and ACF admin save/reload tests against the live WordPress installation are **not yet complete**.
- Upload the supplied design photographs into WordPress media library and assign **بنر اصلی > عکس پس‌زمینه**, **مجله > تصویر مجله**, **اطلاعیه‌ها > تصویر مطلب شاخص**, **دعوت به عضویت > تصویر**.
- Real lawyer-request workflows, member identity verification and paid subscriptions remain future milestones.

## 2026-10-21 — HOME-STYLE-HOTFIX-004 (v0.5.1)
- User screenshot after activation shows that base site stylesheet is loaded, but homepage-specific styles are absent: the hero appears unstyled and inline icons render at natural oversized sizes.
- Root cause cannot be confirmed without live HTTP asset details or cPanel deploy logs. A possible asset URL/caching/deployment issue is suspected.
- Bundled homepage CSS into theme root `style.css` and removed conditional extra CSS file enqueue, so all homepage layout and SVG styles are served from the stylesheet already loaded by the site.
- Kept source `assets/css/pvdak-home.css` as an editable copy; root stylesheet now embeds a matching snapshot for reliable deploy.
- Set theme version to `0.5.1` to bust asset caches. Left maintenance mode intact.
- GitHub committed; cPanel deploy and browser hard-refresh still required to confirm fix.

## 2026-10-21 — ACF-EDITOR-005 (v0.5.2)
- User reported that the expected homepage ACF section / edit option was not visible after installing ACF.
- Earlier menu sent users into native WordPress post editor, dependent on the static front-page setting and ACF's metabox placement.
- Changed the `محتوای صفحه اصلی` top-level menu into a dedicated ACF Free editor with all existing field group sections directly rendered.
- Implemented `acf_form_head()` in the menu's WordPress load hook so edited fields can be saved before header output.
- If the homepage is not assigned to a static page, a clear one-click, nonce-protected setup button appears; no automatic page replacement is performed by merely visiting the menu.
- Admin form writes ACF values to the existing static front-page post ID, so front-end `get_field(..., $page_id)` remains compatible.
- Conserved all existing ACF 11 tabs/117 fields and public maintenance screen.
- Bumped theme to `0.5.2` and pushed to `main`.
- Live admin validation pending: verify the editor form appears, one title can be changed and saved, and the value reflects on the homepage (admin signed in). PHP lint on actual deployment pending.

## 2026-10-21 — LEGAL-PAGES-006 (v0.6.0)
- Corrected the user-identified content-model error: **legal topics are ordinary hierarchical WordPress Pages serving as standalone SEO landing pages**, not categories or 8 repeated ACF title/url fields.
- Homepage now uses the **published child pages** of one selected root page. Each card links to the actual page permalink, uses the WordPress page title, and optionally uses an ACF-selected icon.
- Added a single ACF `legal_topics_parent_page` selector to the homepage editor (tab 4) and removed 16 unused fixed fields from code registration without deleting any WordPress database data.
- Added a legal-topic landing template using the normal page editor, optional featured image and small ACF landing intro/icon fields.
- Added an archive/index template for the landing root to show its published child pages. No topic terms or custom post type were registered.
- Existing `topic_1..8` postmeta remains untouched; legacy defaults removed from code because they no longer drive display.
- Home topics are no longer capped at eight; sorting respects native WordPress page order and titles.
- Theme version bumped to `0.6.0`; maintenance page remains enabled.
- Updated client editor guide with setup steps.
- GitHub changes pushed; live cPanel deploy and actual WP/ACF runtime checks still pending.

### Next operator steps
1. Deploy via existing automatic cron or `bash "$HOME/.pvdak-deploy/deploy.sh"`.
2. Create the published parent Page "موضوعات حقوقی" and assign template "فهرست لندینگ‌های حقوقی".
3. In the "محتوای صفحه اصلی" editor, choose it in tab 4.
4. Add published child Pages such as "حقوق خانواده"; select template "لندینگ موضوع حقوقی", set content and optional icon.
5. Refresh homepage as an authenticated admin to verify title/link/icon updates. If not working, collect cPanel PHP lint and WordPress logs for precise diagnosis.

## 2026-10-22 — CONTENT-ARCHITECTURE-007 (v0.7.0)

### Reason for revision
The owner correctly identified that the earlier site was a visually dynamic theme but not a client-operable legal platform. ACF had been used as a replacement for WordPress' real content types, including repeated magazine articles, announcements, and FAQ items.

### Implemented in GitHub
- Audited the architecture against official WP theme/plugin handbooks and the data-model patterns of MyListing and Listeo. Confirmed: core content in WP, theme for presentation, durable service workflows in a standalone plugin.
- Created `docs/PRODUCT_ARCHITECTURE.md` with entities, WordPress role boundaries, permissions, the three request types, state model, data privacy safeguards, roadmap and handover acceptance tests.
- Created `inc/pvdak-home-collections.php`, which queries published Posts in selected categories and published child Pages for FAQ. It intentionally returns no unrelated results if no category is configured.
- Replaced fixed magazine, news, notices and three FAQ repetitions in `front-page.php` with read-only dynamic queries; highlights come from published posts and their images/excerpts/permalinks. FAQ can grow without new ACF fields.
- Refactored the ACF editor: `102 -> 74` content controls, `11 -> 10` tabs. Category/post/Page selectors replace repeated per-item fields. Existing WordPress DB postmeta was NOT deleted.
- Added client-facing shortcuts to Pages, Posts, Categories, Media, and Menus under `محتوای صفحه اصلی`; rewrote `docs/HOMEPAGE_EDITOR_GUIDE.md` around actual content editing.
- Added `packages/pvdak-core/pvdak-core.php`, an independent, **not-installed** plugin skeleton containing read-only community roles and private request/professional record types (no public REST, no public submission). It must pass a staged review and receive a proper plugin deployment path before use.
- Added read-only PHP lint / basic configuration script `scripts/check-pvdak.sh`, corrected README maintenance HTTP semantics and bumped theme to `0.7.0`.

### Status / limitations
- GitHub commits succeeded; new content queries and plugin behavior have NOT been functionally tested on the actual server.
- Real lawyer verification, membership, case intake, sensitive document processing, quotas, service routing and discussion forum are not live features.
- Public maintenance page preserved. Do not disable it until product testing and readiness review.
- The current cPanel cron deploy targets the **theme**; it does not install or update the standalone `packages/pvdak-core` plugin.
- Media and existing unported ACF fields are deliberately preserved in WordPress storage; only display logic and registration changed.

### Follow-up release validation
1. Run normal deploy; ensure no automatic rollback.
2. Run `bash "$HOME/public_html/wp-content/themes/pvdakcom-main/scripts/check-pvdak.sh"` and review PHP lint / theme version `0.7.0`.
3. In WP admin, select Magazine and Notices categories and create at least one published post in each; test that only those posts appear on homepage.
4. Make a published child Page under the FAQ parent and verify its accordion entry; test draft visibility.
5. Recheck main legal landing pages, maintenance mode, mobile screenshot and ACF editor save/reload.
6. Plan independent plugin deployment and least-privilege request form testing before collecting real user data.

## 2026-10-22 — PRODUCT-FLOW-008 (Theme 0.8.0 / Core 0.3.0)
- Continued feature-first development in existing `main` branch; avoided workflow duplication and preserved maintenance screen.
- Implemented new `packages/pvdak-core/includes/requests.php`: user-authenticated, nonce-protected **three request types**; private `pvdak_request` CPT storage, input length checks, consent checkbox and per-user rate limiting. Disabled by default via WordPress option, with emergency code kill switch.
- Implemented `[pvdak_request_form type="lawyer|consultation|question"]` and `[pvdak_my_requests]` shortcode foundation. Users are limited to a list of their own request titles/types/states; no public case REST/archives. Request review status is admin-only with a per-change audit record.
- Implemented `packages/pvdak-core/includes/professionals.php`: private application to join as lawyer/trainee/student, one-application-per-user reference, admin review statuses and status audit; neither applicant claims nor manual review status grant public verification or lawyer capabilities.
- Added `packages/pvdak-core/includes/admin.php` with explicit opt-in intake setting, operations matrix, WordPress admin columns and a one-click action that creates five **draft** pages without overwriting existing pages.
- Added reusable RTL form styles; removed revisions/media support from private record CPTs to reduce unnecessary copies and exposure.
- Added `inc/pvdak-home-readiness.php` with an eight-item client management status overview, linking to pages, categories, menus, imagery and plugins.
- Updated homepage service-card logic: if optional manual link absent, automatically link to the appropriate **published** WP Page generated by Core only when Core is active and intake enabled; no bogus links to draft pages.
- Enforced that selected featured articles belong to their selected WP categories.
- New script `scripts/check-pvdak-core.sh`: read-only PHP lint/plugin status checker; added updated README for Core 0.3.
- Theme version bumped to 0.8.0. Plugin source not installed; no public intake live.
- **Not yet verified:** real PHP lint from deployed release, WordPress Core plugin activation, proper owner-only CPT retrieval, CSRF rejection, private permission isolation and browser/mobile acceptance. Do not enable intake in production until these pass.
