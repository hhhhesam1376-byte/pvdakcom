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
