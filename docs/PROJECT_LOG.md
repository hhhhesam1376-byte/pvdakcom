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
