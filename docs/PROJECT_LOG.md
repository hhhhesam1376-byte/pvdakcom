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
