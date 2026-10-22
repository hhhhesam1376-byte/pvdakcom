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

## 2026-10-22 — PRODUCT-OPERATIONS-009 (Theme 0.8.1 / Core 0.3.0)
- Added `[pvdak_professional_application]` private role-claim onboarding for lawyers, trainees and students; applications do not grant verified rights or public listings.
- Added independent toggles for legal-case intake and professional-member intake, both initially OFF; emergency `PVDAK_CORE_DISABLE_INTAKE` is a shared kill switch.
- Added explicit consent requirement and scalar input validation on both forms. Prevented automatic file uploads and unnecessary private revisions.
- Added administrator status-review meta boxes, audit history for requests and applications and list columns for review queues.
- Added five safe draft service pages via admin control, without altering pages that already exist.
- Added `inc/pvdak-home-readiness.php`: client-facing homepage readiness and direct admin next actions (eight checks).
- Changed three legal service CTAs and professional join CTA to resolve published workflow pages automatically when the respective Core intake feature is enabled. Manually configured ACF URL still wins.
- Changed the four shortcut cards below Hero to a **native WordPress navigation menu location**, retaining a noneditable legacy fallback until the client assigns a menu; removed seven repetitive ACF input fields.
- Added `scripts/install-pvdak-core.sh` (default dry-run, explicit install only, never activates plugin) and `scripts/check-pvdak-core.sh` for PHP lint/installation status.
- Added `docs/ACCEPTANCE_TESTS.md` with independent security/privacy, content, UX and role-access criteria.
- README for PVDak Core documents explicit opt-in, draft pages, current limitations and required staging work.

### Verification gate
- GitHub source writes and reference consistency checks succeeded.
- **No live php -l, WordPress form submission, role isolation test or Core activation completed**. Core is source code only until installed and enabled by an authorized operator. Production intake should remain OFF. The maintenance screen remains configured.
- Next step: deploy theme 0.8.1 via existing `deploy.sh` and perform `scripts/check-pvdak.sh`; run Core read-only lint and staged tests independently before enabling.

## 2026-10-22 — CLIENT-PORTAL-010 (Theme 0.8.2 / Core 0.4.0)

### Completed in GitHub
- Added `packages/pvdak-core/includes/portal.php`: private `[pvdak_account_dashboard]` shortcode showing current user's **own** recent requests, status counts, membership application status and links to published workflows. Disabled intake actions are omitted; visitor must log in.
- Updated `header.php` to link signed-in users to the published `my-pvdak-account` page, retaining normal WordPress fallback if the page/Core are unavailable.
- Added sixth **draft** page to the one-click Core setup, without automatic publication.
- Rebuilt request-manager metabox with allowed transition graph, a private staff-only note field, latest note log and status audit history. `matched` intentionally unavailable for newly reviewed cases because real professional verification/assignment is not yet implemented.
- Added `packages/pvdak-core/includes/triage.php`: administrator-only triage overview with counts by case state, plus type/status filters in the WordPress request list.
- Added a `wp_insert_post_data` defense-in-depth guard to block unintended publication/scheduling of `pvdak_request` and `pvdak_professional` records.
- Added 20-item pagination for the user's own case-history list and disabled false success confirmations coming only from URL parameters.
- Personalized forms/dashboard pages send no-cache headers; private account and personal request pages also send noindex.
- Changed submission success feedback to a short-lived one-time per-user receipt created **only after a successful insert**, not a URL query parameter.
- Added standalone source policy checks in `tests/pvdak-core/policy-smoke.php`, run via `scripts/check-pvdak-core.sh` when available; they test defaults, private status behavior and allowed state transitions without database writes.
- Added RTL responsive dashboard CSS, updated Core README and expanded acceptance checklist.

### Verification and rollout boundaries
- GitHub commits and source reference checks completed; code is pushed to main.
- **No PHP interpreter or live WordPress runtime tests on the production server have been executed in this turn.** Source policy tests have been authored but their pass results are not yet confirmed. The user still needs to run read-only lint and staged acceptance tests after the existing Cron deploy.
- Core remains uninstalled/unactivated on production; both intake settings are OFF by default. Maintenance mode is unchanged.
- No automated case matching, verified professional directory, payment or sensitive file upload currently exists. Do not accept real confidential information until privacy/security tests pass.

## 2026-10-22 — LEGAL-REFERRAL-011 (Core 0.5.0; Theme remains 0.8.2)

### Core implementation
- Added `includes/specialties.php`: sources specialties from published child **WordPress Pages** under the selected legal-landing parent. No custom taxonomy or duplicate content model was introduced.
- Core resolves landing parent from its own optional setting, then from homepage ACF selection, then from default published `legal-topics` page. The plugin remains functional without the theme or ACF (using the normal Pages fallback).
- Professional managers can select specialties directly from Page objects and record a separate, audited internal referral-clearance flag. Clearance is not a statement of official license verification; it is never applicant-editable in the public form.
- Client intake now requires a published legal-topic landing Page ID. The request stores `_pvdak_legal_landing_id`, not a free-form ACF title or duplicated category.
- Added `includes/assignments.php`: manager-only matching screen and nonce-protected POST action for reviewed cases, filtering professional profiles to approved, manually-cleared **lawyers** with the exact specialty and excluding self-assignment.
- Successful action writes a unique internal profile reference, updates state to `matched`, and appends audit records. **No case contents, contact details, notifications or file attachments are transmitted to a professional**; this is only internal case assignment.
- Standard request state editor still cannot select `matched` directly; that state is only reached through guarded matching.
- Changing a professional's clearance, status or specialties causes any now-ineligible internally matched requests to revert to `review`, deleting their active assignment reference but keeping historical audits.
- Renamed the client-visible `matched` label to "انتساب داخلی ثبت‌شده" to avoid implying actual lawyer contact or representation.
- Added a mock WordPress security-policy test suite extension (20 assertions total), checked into `tests/pvdak-core/policy-smoke.php`; these assertions are **not yet executed on host**.
- Updated docs for client-side review, explicit limitations, and stage acceptance.

### Rollout precautions
- Source pushed to existing `main` branch. **No new GitHub Actions workflow, Secrets, deploy route or Cron was created.**
- Existing site cron is known to update the **theme files**. It can bring the `packages/pvdak-core` source alongside the theme, but that does NOT mean PVDak Core is installed/activated as a WordPress plugin. Its independent rollout must wait for PHP lint and staging checks.
- Request and professional application intake both remain **disabled by default**; public maintenance is unchanged.
- Live PHP lint, WordPress registration/permissions and end-to-end multi-user testing are not yet confirmed.

## 2026-10-22 — CAPACITY-PORTAL-012 (Core 0.6.0 / Theme unchanged 0.8.2)

### GitHub updates
- Created `packages/pvdak-core/includes/capacity.php`, loaded from Core bootstrap.
- Administrators can set per-professional limits 0–25 and independently switch **accepting new internal assignments** on/off. Both are OFF by default; zero does not mean unlimited. Audit entries record capacity changes.
- Active workload is based on internally matched/answered private requests only, excluding closed items. Lowering the limit pauses future referrals without deleting existing assignments.
- Candidate lists and final assignment POST both re-check capacity, specialty, internal professional clearance and request state. **This is not a transaction-safe capacity guarantee under concurrent managers**; race-condition testing/locking remains mandatory before live use.
- Created `packages/pvdak-core/includes/professional-portal.php`: private `[pvdak_professional_dashboard]` displays the signed-in user's own application review, public landing-topic labels and **aggregate** capacity counts, but no case texts, names, files or claimant contacts.
- Core admin can create a seventh service page `my-professional-account` as DRAFT; page also appears in signed-in account shortcut panel when published. Personal dashboard uses existing nocache/noindex helpers.
- Fixed a previous settings coupling bug: the legal landing parent option now uses a separate Settings API group from the intake toggles, preventing accidental reset of unrelated options.
- Extended `tests/pvdak-core/policy-smoke.php` from 20 to 28 synthetic policy checks including "full capacity", "paused accepting", zero default and candidate filtering.
- Updated Core README, administrator capability list, acceptance test checklist, version metadata and source comments.

### Deployment and remaining testing
- Code changes committed directly to the existing `main` branch, without new GitHub Actions, secrets or Cron changes.
- Existing Cron deploy handles theme deployment; it **does not by itself activate the plugin** from `packages/pvdak-core`.
- Core 0.6.0 is not confirmed installed/active in WordPress. On-host PHP lint, policy suite execution, real WordPress role isolation, form handling, no-cache behavior and referral concurrency tests remain pending.
- Production intake remains disabled by default, and the maintenance page remains unchanged.

## 2026-10-22 — PHASE1-CORE-SAFETY-013 (Core 0.8.0; Theme 0.8.2)

### Executed on GitHub main
- Added an explicit, separate **MariaDB/MySQL named-lock** module `packages/pvdak-core/includes/assignment-locks.php`. A referral now locks both request and professional in deterministic order, repeats eligibility and capacity checks inside the locks, fails closed on unavailable lock, and releases acquired locks via `finally`.
- Capacity query now disables WP_Query result caching and refreshes request meta-cache for each case before computing current workload.
- Professional clearance/specification revocation review also uses the matching request/professional locks. If a lock cannot be acquired, an internal private warning record is left for staff follow-up.
- Added manager-facing distinctions for lock contention versus invalid/missing eligibility.
- Added `includes/license-review.php`: a strictly administrative and expiring professional review record (source method, reviewer, expiry, audit); **no automatic authoritative verification or public certificate is claimed**.
- Internal assignment eligibility now requires three independent controls: approved membership, explicit internal referral clearance, and a current official-source review recorded by a manager. A missing/expired review fails closed.
- Added WP-CLI live preflight `scripts/pvdak-wp-preflight.php`: read-only checks of site theme version, ACF, front-page configuration, private CPTs and role permissions. Wired into existing `scripts/check-pvdak.sh`.
- Strengthened `scripts/check-pvdak-core.sh` so the required policy test cannot be silently skipped. Expanded `tests/pvdak-core/policy-smoke.php` to **40 synthetic assertions**, including lock order, contention failure, expiry, validation method, staff reviewer and capacity.
- Version changed to `Core 0.8.0`; theme remains `0.8.2`. No changes to the existing GitHub Actions, cPanel Cron, server settings, maintenance mode or live intake toggles.

### Verification status
- GitHub source writes and connector re-reads have succeeded. A lightweight PHP bracket/string-structure scan found no mismatched delimiters in inspected modules.
- **Not yet done:** `php -l`, execution of 40 synthetic tests with real PHP interpreter on server, live WordPress staging permission/API checks, concurrency simulation using two MariaDB sessions, backup restore rehearsal, and browser testing. No claim of deployed/activated Core.
- Current site Cron delivers the theme source, but it does **not** install/activate the Core plugin in WordPress. It has not been modified.
- Real legal data intake must stay disabled until independent privacy, role and staging tests pass.

### Next implementation priorities
1. Run read-only host checks and staging tests; ensure lock behavior is as expected with two real database connections.
2. Complete formal professional verification workflow: staff review evidence policy, expiry/re-review, revocation reporting and user-safe status labels.
3. Build document management with strictly controlled private storage and retention/deletion rules.
4. Complete authenticated inter-party messaging and role-specific workflow without exposing claimant details before authorized release.
5. Integrate role-specific content, editorial landing Pages, subscriptions and handover acceptance checks as separate milestones.

## 2026-10-22 — PERSIAN-RTL-014 (Theme 0.8.3; Core remains 0.8.0)

### User-reported defects
1. WordPress site administration had been left in English after installation.
2. The theme was not rendered consistently right-to-left.

### GitHub changes
- New, explicitly loaded `rtl.css`: global RTL direction and scoped layouts for header/navigation, homepage, legal landing pages, forms, magazine/news/FAQ/footer and mobile. Kept code and Latin/URL fields readable in LTR.
- `header.php` now emits `<html lang="fa-IR" dir="rtl">` consistently, regardless of the previously English WordPress installation.
- `functions.php` explicitly enqueues `rtl.css` after `style.css` even when WordPress locale remains `en_US`; theme bumped to `0.8.3`.
- New `scripts/set-persian-wordpress.sh` checks current locale without writes by default; with `--apply` installs the official `fa_IR` core pack as needed and switches the site locale via WP-CLI. Optional `--admins` changes only administrator user profiles explicitly pinned to `en_US` (preserving other personal preferences).
- Existing `scripts/check-pvdak.sh` now fails if the new RTL stylesheet/enqueue or Persian HTML attributes are missing, and WP-CLI preflight checks/theme version are updated; locale mismatch is reported as a warning until the dedicated migration is applied.
- Added `docs/PERSIAN_RTL_SETUP.md` with one-time host operation, read-only checks and mobile/desktop visual acceptance steps. Updated README.
- Left existing Cron, GitHub Actions, maintenance artwork/page and Core integration unchanged.

### Verification boundary
- GitHub writes succeeded. Source integrity to be re-read after the change.
- **Live WordPress site language has NOT been changed from this connector:** the language migration must be executed once on the cPanel server because it updates the existing WordPress installation and potentially downloads the official translation pack.
- Browser/mobile and WP admin after-migration RTL checks have NOT been performed; old site cache may still present older assets.
- No new password, secret, workflow, deployment path or WordPress database reinstall is required.

## 2026-10-22 — CONTENT-ONBOARDING-015 (Theme 0.8.4; Core unchanged 0.8.0)

### Implemented in existing main branch
- Added an explicit administrator-only, nonce-protected **editorial content starter** in `inc/pvdak-editor-bootstrap.php`, linked to the already-working `محتوای صفحه اصلی` dashboard.
- The editor can choose to create the published parent Pages `legal-topics` and `legal-faq`, native WordPress post Categories `legal-magazine`, `pvdak-notices` and `pvdak-news`, plus four sample child legal-topic Pages that are **drafts only** (family, property, criminal and contracts).
- Starter is idempotent: existing Pages, terms, titles, slugs, content, statuses and templates are preserved. It preselects the created sources in corresponding ACF Free fields **only if those selections were previously empty**.
- No invented legal article content is published. The admin must review titles, editor text, permalink, featured images and publication status.
- Added WordPress language (`fa_IR`) and RTL CSS checks to the client's existing readiness panel, taking its status rows from 8 to 10.
- Added 14 isolated PHP assertions for no duplicate pages, preservation of drafts, duplicate categories, empty-only ACF source selections and authorization assumptions in `tests/pvdak-theme/editor-bootstrap-smoke.php`.
- Existing read-only `scripts/check-pvdak.sh` now requires that smoke test to be present and executes it, prior to WP-CLI runtime checks.
- Updated `docs/HOMEPAGE_EDITOR_GUIDE.md`, `docs/ACCEPTANCE_TESTS.md`, `README.md`, and theme version to `0.8.4`.

### Validation boundary
- All GitHub file writes succeeded and re-fetched source passed a **basic structural delimiter scan**.
- No on-host PHP interpreter tests, WordPress mutation tests or browser visual checks have been performed as part of this turn. The new smoke tests are authored, not reported as passed.
- WordPress's `fa_IR` setting remains unconfirmed on the server (requires one-time WP-CLI `scripts/set-persian-wordpress.sh --apply --admins` if not already applied).
- Existing Cron and maintenance mode are unchanged. **PVDak Core is still not confirmed installed/activated on the site**.
- This phase advances real client operability, but a live acceptance test is needed before the site is treated as ready.

## 2026-10-22 — EDITORIAL-SEARCH-016 (Theme 0.8.5; Core still 0.8.0)
- Replaced the homepage `name=s` query with a dedicated `topic` query scoped to real published legal-topic child **Pages**, preventing accidental fallback to unrelated WordPress article search.
- Added `pvdak_legal_topic_search_term`, `pvdak_legal_normalize_topic_text`, and `pvdak_legal_filter_topic_pages`; normalization covers common Arabic/Persian kaf/yeh variants, non-breaking half-spaces, whitespace and diacritic marks. Client-side topic filtering remains progressive enhancement; server-side results and an empty-state are available without JavaScript.
- Implemented native `category.php` for WordPress **post** Categories only (magazine, news, announcements). Legal-topic landings remain WordPress hierarchical **Pages**. Archives use the category title, term description and the existing posts/pagination loop without duplicated ACF items or invented content.
- Added `assets/css/pvdak-editorial.css` for RTL responsive editorial cards, category navigation and article typography; loaded only on post/category/archive contexts.
- Added `tests/pvdak-theme/legal-topic-search-smoke.php` with 11 synthetic assertions, hooked into the read-only `scripts/check-pvdak.sh` alongside the 14 editorial-bootstrap assertions. Checks fail when real editorial archive files are missing.
- Updated `scripts/pvdak-wp-preflight.php` to require theme 0.8.5, topic search helpers and category template/styles.
- All changes pushed directly to `main`; no GitHub Actions, secrets, cron or maintenance-page changes.

### Unverified
- Code/connector reference checks only: **PHP CLI execution, browser/mobile layout, WordPress admin input/save, on-host Cron deployment and cache invalidation are not confirmed**. Existing Core must still be staged and activated separately from theme release.

## 2026-10-22 — PERSIAN-SEARCH-POLISH-017 (Theme 0.8.6)
- Moved the legal-topic query parameter from generic `topic` to namespaced `pvdak_topic` to avoid future clashes with forum or other WP integrations. Updated PHP, homepage input, JavaScript, regression tests and acceptance checklist consistently.
- Made non-mbstring PHP search truncation Unicode-safe via `preg_split('//u')` and normalized Arabic diacritic marks in JavaScript consistently with the PHP renderer.
- Added explicit `.pvdak-home-topic[hidden]` CSS so author-level display rules cannot accidentally override client-side filtering.
- Bumped theme to `0.8.6` to invalidate cached JS/CSS assets and aligned read-only WP-CLI preflight with this release.
- Connector re-read verified search parameter consistency, template wiring, expected assets and 11 **authored** search assertions (plus the existing 14 content bootstrap assertions).
- **Still pending:** actual on-host PHP interpreter execution, Cron deployment confirmation, WordPress browser/mobile tests, and separate Core 0.8.0 plugin staging/activation. Existing Cron/Actions, maintenance mode and disabled private intake are unchanged.

## 2026-10-22 — CORE-RELEASE-GATE-018 (Theme 0.8.7 / Core source 0.8.0)
- Refocused the previous development roadmap on **staging readiness** instead of creating another untested product feature.
- Added `scripts/pvdak-core-acceptance.php`: strictly read-only WP-CLI acceptance gate, requiring an active Core installation and checking Core version, plugin/main theme source SHA-256 file matching, both intake switches OFF, registered shortcodes, private CPTs and REST isolation, administrator-only capabilities, assignment locks and time-valid professional review handler. No real personal data are printed.
- Integrated this gate into the existing `scripts/check-pvdak-core.sh` **only if Core is already active**. For an inactive Core it still reports inactive and runs standalone lint/policy tests.
- Hardened the existing `scripts/install-pvdak-core.sh`: Core policy suite is required and must pass before copying source into WordPress plugins, a valid WP-CLI/WordPress installation is required, active plugin replacement is refused, and the previous inactive release is restored when WP-CLI does not recognize the new copy.
- Expanded the client `محتوای صفحه اصلی` readiness panel to distinguish **files in themes**, **plugin installed**, **plugin source release matching**, **plugin active** and **both intake channels disabled**; corrected vague “ready” text to neutral “present/needs review.”
- Bumped theme to 0.8.7, left independent Core source at 0.8.0. Added `docs/CORE_STAGING_HANDOFF.md` with the exact no-new-Cron dry-run/install/acceptance sequence.
- **No change** to GitHub Actions, server Cron, deployment script, maintenance page or production intake settings. The project has not yet run Core installation, PHP lint, WordPress role/E2E or database concurrency tests on the cPanel server in this turn.
- Still pending: on-host verification, optional inactive plugin installation and stage-only activation, real access isolation checks with multiple test accounts, MariaDB double-session locking tests, privacy/retention and case workflow completion.

## 2026-10-22 — HOST-DIAGNOSTIC-019 (Theme 0.8.8 / Core 0.8.0)
- Added `scripts/pvdak-status.sh`, a read-only Bash diagnostic. Without options, it reports WordPress/PHP/site locale/active theme, deployed theme version, bundled Core version, installed Core version, plugin install/activation status, intake option states, installed-vs-bundled Core file comparison and optional local repo SHA (explicitly **not** deployment proof).
- With `--test`, the same command invokes the existing read-only `check-pvdak.sh` and `check-pvdak-core.sh` tools, prints compact PASS/FAIL summaries and the last relevant failure lines. It does not install or activate plugins, change languages, or enable intake.
- Published `docs/HOST_STATUS_REPORT.md` with one copy/paste host command using the **existing** `deploy.sh`, plus instructions to send only the diagnostic block back to chat.
- Bumped theme to `0.8.8` and updated WP-CLI preflight version expectation. Core code remains `0.8.0`.
- No changes to Cron cadence, GitHub Actions, secrets or maintenance screen. The diagnostic source is committed, but **no host execution/results have been received**; real installed versions, RTL status, test suite outcomes and staging readiness remain unverified until a cPanel-host report is supplied.

## 2026-10-22 — HOST-TEST-PATH-FIX-020 (Theme 0.8.8 / bundled Core 0.8.0)
- User ran private diagnostic at 2026-10-22T15:25:11Z; confirmed WordPress 7.1.3, PHP CLI 8.1.34, ACF 6.8.10 active, site locale en_US, active theme pvdakcom-main 0.8.8, bundled Core 0.8.0, **Core not installed or activated**, and rtl.css present.
- Theme lint and prior editorial/search assertions reached successful checkpoints, but `check-pvdak.sh` failed because it sought `pvdak-wp-preflight.php` under the **public theme scripts directory**. Existing publisher deliberately removes scripts and docs before rsync; deployment is not broken.
- Core source check linted all 12 PHP files but failed at the policy suite (exit 255). The private temporary git archive contained scripts/tests, while the policy suite unconditionally required the Core entry from that same extraction root, where packages were absent. Policy results were therefore *unknown*, not a proven regression in installed Core.
- Fixed theme check to load WP-CLI preflight from the executing script's **private temporary directory**.
- Fixed standalone Core policy suite to use optional `PVDAK_CORE_SOURCE` (defaults to full repo layout), modified `check-pvdak-core.sh` to pass the actual deployed bundled plugin entry path, and modified `install-pvdak-core.sh` similarly. Both paths now expose PHP runtime errors rather than silently returning 255.
- Updated the host report guide with the verified findings and correct one-command rerun. No Cron, GitHub Actions, server files, production intake or maintenance-page settings were changed. **Awaiting rerun to confirm real WP-CLI preflight and policy-suite results; do not enable Core/real intake before then.**

## 2026-10-22 — HOST-TEST-PASS-021 (verified host run 16:16:05 UTC)
- Host returned `UP_TO_DATE 80457b695175bf9924dce4711fdc6841ca09f042`. Existing Cron workflow delivered theme **0.8.8** to WordPress; bundled Core source is **0.8.0**.
- Read-only diagnostic showed WordPress **7.1.3**, PHP CLI **8.1.34**, active theme `pvdakcom-main`, site runtime locale `en_US`, and RTL stylesheet PRESENT. Earlier host output identified active ACF **6.8.10**.
- **Theme release tests PASS**: editor bootstrap **14** synthetic assertions, Persian legal search **11** assertions, native editorial/RTL checks, WP-CLI `PREFLIGHT_SUMMARY: fails=0 warnings=0`.
- **Core source tests PASS**: `PHP_LINT_OK: 12 files`, `CORE_POLICY_SMOKE_OK: 40 checks`. The Core policy tests are synthetic; passing does NOT establish role/data privacy, concurrency or E2E behavior with real WordPress users.
- **Core plugin installed NO / active NO**, installed version MISSING; intake option values UNKNOWN because options have not been configured. Never claim Core is active or an actual legal workflow is production ready.
- Noted a locale reporting discrepancy: diagnostic's `get_locale()` returned `en_US` while the preflight emitted zero warnings for its `WPLANG` option check. Investigate the option versus runtime locale before claiming the dashboard is Persian.
- Next stage is a controlled **inactive plugin install** with backup/rollback checks; only after this, evaluate isolated staging activation and real-user permission tests. No new Cron, Actions or secrets.

## 2026-10-22 — CORE-INACTIVE-INSTALL-022 (user host CLI confirmation)
- The cPanel user ran the existing private-script installer against `public_html/wp-content/themes/pvdakcom-main/packages/pvdak-core`. Both **SOURCE_LINT_OK** and **SOURCE_POLICY_SMOKE_OK** passed during the first `DRY_RUN` and again during `--install`.
- Host reported `INSTALLED_INACTIVE_SOURCE: /home/amirhoseainsabet/public_html/wp-content/plugins/pvdak-core`, followed by `wp plugin status pvdak-core` showing **Status: Inactive, Version: 0.8.0**.
- Consequently, the next phase no longer needs to troubleshoot Core source transfer or repeat installation. **Do not execute the installer again against an active plugin.**
- Remains unverified: actual plugin activation and resulting registered admin roles/post types, WP-CLI active Core acceptance, live front-end/role security checks, independent legal data privacy and MariaDB concurrent assignment behavior.
- Both real client and professional intake should remain disabled; public maintenance should remain enabled. No new Cron/Actions/secrets requested.

## 2026-10-22 — CORE-ACTIVATION-GUARD-023 (preflight tool; not yet run on host)
- Confirmed successful separate **inactive** PVDak Core 0.8.0 plugin installation via user's WP-CLI log (see CORE-INACTIVE-INSTALL-022).
- Added private-only `scripts/pvdak-core-activate-guarded.sh` with explicit `--check` (read-only) and `--activate` modes. The latter refuses the wrong site/theme, installation/source mismatches, open intake settings, non-maintenance mode and root execution; creates a private DB backup and checks the active Core role/CPT/source policy. If acceptance fails, it attempts plugin deactivation without claiming database rollback.
- Expanded `docs/CORE_STAGING_HANDOFF.md` with the existing `deploy.sh` + private `git archive` procedure, Bash syntax check, dry-run and activation command.
- Source-only commit; **no activation, on-host command execution or live E2E assessment has been performed by the assistant**. Cron, GitHub Actions, production frontend maintenance and intake options are untouched. Await user CLI output.

## 2026-10-22 — CORE-ACTIVE-ANON-GATE-024 (verified activation; next test pending)
- Actual cPanel output confirmed deployment `454012fe0e5247ac0cf80ec4b94ff3cdab5e7675` and first-time PVDak Core **0.8.0 Active** after guarded dry run and private database backup `pvdak-before-core-20261022T163606Z-3646343.sql` (do not copy backup into public webroot).
- Guard reported bundled and installed Core files MATCH, public maintenance mode ON, both legal and professional intake OFF/unset. `PVDAK_CORE_ACCEPTANCE: failures=0`; 26 labeled PASS checks appeared in WP-CLI output, including the private CPTs/shortcodes, administrator permissions, lack of private management roles for 8 non-administrator roles, and loadable referral safety code. No real legal data were exposed by this audit.
- WordPress activation created the intended roles and management capabilities. **Do not mistake these baseline checks for true multi-account E2E, official credential verification, MariaDB concurrency or privacy/legal completion.**
- Added `scripts/pvdak-anonymous-live-smoke.php` as a strictly read-only WP-CLI check: anonymous caller cannot manage sensitive post types; request/application/account/dashboard shortcodes do not render editable forms or case lists while intake is closed; no direct sensitive REST routes; maintenance remains on. Runs with `wp_set_current_user(0)` in memory, without creating user data. Integrated into `scripts/pvdak-status.sh --test` automatically when Core is active.
- Improved diagnostics to show both `get_locale()` runtime value and the `WPLANG` option independently for the observed en_US/fa_IR discrepancy.
- **New anonymous-access test has NOT yet been run on the user's actual WordPress**; keep intake closed and maintenance active. No plugin PHP modifications, plugin upgrade, extra Cron/Actions/secrets or production access changes performed.

## 2026-10-22 — ANON-COUNTER-FIX-025 (test verification bug; no plugin changes)
- User's 16:54 UTC read-only host run showed `UP_TO_DATE affb6190cafc5ce1ddd3627a569619ddd0ff91fe`, theme **0.8.8 PASS**, bundled and installed Core **0.8.0 MATCH / ACTIVE**, PHP lint, 14 content and 11 search checks PASS, Core 40 synthetic policy checks PASS and active Core baseline loaded.
- **Important false-positive bug identified:** `PVDAK_ANON_LIVE_SMOKE: checks=0 failures=0` despite `Anonymous live WP checks: PASS`. Also WP preflight had `fails=0 warnings=0` despite `get_locale()=en_US` and a Persian-language warning condition. Root cause: WP-CLI `eval-file` can include test files from a function scope; script-local zero counters were distinct from `global` variables mutated by test helper functions. An actual failed assertion could therefore have been displayed while the final summary reported zero failures and returned success.
- Corrected **all three** WP-CLI scripts `scripts/pvdak-anonymous-live-smoke.php`, `scripts/pvdak-wp-preflight.php`, `scripts/pvdak-core-acceptance.php` to store counters explicitly under dedicated `$GLOBALS` keys. Anonymous smoke requires exactly **19** assertions; Core active acceptance requires exactly **26**; WordPress preflight requires at least **9** baseline checks. Any failure now propagates as nonzero exit status. The public theme and installed plugin PHP code are unchanged.
- Added `scripts/pvdak-anonymous-counter-selftest.php`, an offline read-only positive/negative control that executes the *real* anonymous script under a nested include (the WP-CLI scope). Negative control deliberately switches maintenance OFF in memory and expects one counted failure; positive control expects zero failures. `scripts/pvdak-status.sh --test` runs both controls.
- Site's `WPLANG` status was `UNKNOWN` in the CLI report; its runtime locale is `en_US`. A Persian-language warning may now be correctly visible after rerun. No language setting has been changed. **Host rerun is still required**: no claim of complete live anonymous isolation is warranted from the false-positive 0/0 report.
- Existing 15-min Git fetch/archive/rsync deployment remains unchanged, as do public maintenance and both legal/professional intake OFF. No changes to the installed Core plugin or to real WordPress accounts/data.

## 2026-10-22 — DIAGNOSTIC-OPTION-REPORT-026 (after CLI scope fix)
- Private host reporter now differentiates unset/empty WordPress `WPLANG` and unset legal/member intake options from a WP-CLI query that failed; Core code defaults both intakes to OFF, and WordPress runtime language is still independently reported.
- The success log for active Core acceptance now prints `PVDAK_CORE_ACCEPTANCE: checks=26 failures=0 expected=26` after the new WP-CLI global-counter logic is exercised correctly; anonymous audit prints `checks=19 failures=0 expected=19`. The two-mode offline counter self-test is included in the private one-command reporter.
- All assertions and operational reporting fixes have been pushed to GitHub, but **no post-fix host run has been received yet**. Do not take a previous `checks=0 failures=0` summary as a verified passing privacy audit.

## 2026-10-22 — VERIFIED-HOST-AUDIT-027 (read-only host test at 17:06 UTC)
- User supplied `DEPLOY_OK 04c23f9cc09e45316c767fce8bd249f9d64a5647` and a complete `PVDAK_DIAGNOSTIC_END status=OK` from cPanel.
- `pvdakcom-main 0.8.8` active, `PVDak Core 0.8.0` installed/active and exact source MATCH, WordPress 7.1.3, PHP CLI 8.1.34, RTL stylesheet present. `WPLANG` EMPTY (English default); runtime language en_US.
- Legal and professional intake settings both UNSET (default OFF), public maintenance previously confirmed ON and checked inside the 19-pass anonymous live test.
- Test evidence: editor-bootstrap 14/14 (synthetic), legal topic search 11/11 (synthetic), Core 40/40 policy (synthetic), active WP Core acceptance **26 checks / 0 failures**, anonymous live WP **19 checks / 0 failures**, live WP preflight **23 checks / 0 failures / 2 warnings**. Both fail-closed controls (19/0 and deliberately 19/1) passed.
- **Two preflight warnings** remain, one confirmed from en_US vs fa_IR. The other is not shown in the compact host log; do not guess its cause. Enhanced `pvdak-status.sh --test` to print `WARN` labels next to the summary on future host runs.
- Real multi-account client/lawyer isolation, private data leakage tests with distinct records and simultaneous MariaDB assignment remain outstanding. Baseline success does not authorize opening intake or disabling maintenance.

## 2026-10-22 — WARNINGS-IDENTIFIED-028 (verified cPanel run 17:23:30Z)
- Host: `UP_TO_DATE e413fc09f3f1f16ee40b5c46724b4d7134e4259f`; theme 0.8.8; WordPress 7.1.3; PHP CLI 8.1.34; Core installed/active 0.8.0 and file source MATCH; legal and professional intake UNSET (OFF by default); RTL present.
- Verified reports: theme tests PASS, editor 14/14, search 11/11, preflight **23 checks, 0 fails, 2 WARN**, active Core acceptance 26/26, synthetic Core policy 40/40, fail-closed control both modes PASS, live anonymous 19/19.
- Exact WARN labels are `WordPress site language is Persian (fa_IR)` and `Legal topics parent configured`. No remaining unclassified warnings from that run.
- Source review: `inc/pvdak-legal-landings.php` resolves published legal root via ACF `legal_topics_parent_page` or published `legal-topics` Page. `inc/pvdak-editor-bootstrap.php` offers an administrator-only, nonce-protected **one-click** idempotent content skeleton initializer in `wp-admin/admin.php?page=pvdak-home-content`. It creates a published empty legal-topics parent, published FAQ parent and three real Post categories; four sample landing child Pages are **drafts**, not published; it preserves existing pages/content. Keep public maintenance ON.
- The site language may be set with existing private `scripts/set-persian-wordpress.sh --apply` (without `--admins`, which would change admin profile language overrides). After creating the legal parent, the preflight may show **a new warning** (`At least one published legal landing exists`) until an editor legitimately publishes a completed child landing; do not publish placeholder legal articles simply to silence diagnostics.
- Remaining work: real client/lawyer account isolation with dedicated safe test accounts on an isolated stage, MariaDB concurrent assignment, privacy/retention E2E. Do not enable either intake or disable maintenance merely due to passing read-only checks.

## 2026-10-22 — FA_IR_INSTALL_NETWORK_TIMEOUT-029 (user-reported host)
- WordPress Persian language installation `wp language core install fa_IR --path="$HOME/public_html"` failed with `cURL error 28: Operation timed out after 3000 milliseconds with 0 bytes received` while contacting WordPress.org.
- A subsequent guarded retry with temporary HTTP timeout filter returned no output; explicit status probes showed `wp language core is-installed fa_IR` exit **1**, `WPLANG` value **empty** with exit 0, and runtime `get_locale()` still **en_US**. **Persian translation is NOT installed; do not claim otherwise.** The previous `wp site switch-language` stage did not complete.
- Next: read-only remote network checks on `api.wordpress.org` and `downloads.wordpress.org` (and an explicit WordPress HTTP GET with longer timeout) to isolate outgoing network vs WP PHP transport. Do not reset WordPress, modify resolver/firewall, download unverified translations or create alternate deployment flows.
- All other previously validated PVDak Core 0.8.0 PASS results stand; maintenance ON and intake default OFF. Legal topic parent remains unconfigured pending separate editorial initializer action.

## 2026-10-22 — FA_IR_HTTP_REACHABLE-030 (host-only network diagnosis)
- User's read-only host networking probe: `api.wordpress.org` DNS 66.6.42.251 and HTTPS `HTTP=302` in 5.17 seconds; `downloads.wordpress.org` DNS 66.6.42.250 and HTTPS `HTTP=302` in 6.19 seconds. Those 302 results were for root URLs and confirm HTTPS connectivity, not translation package validity.
- Actual WordPress PHP HTTP client `wp_remote_get('https://api.wordpress.org/translations/core/1.0/?version=...')` produced `WP_HTTP_STATUS=200`. Therefore no total DNS/network isolation of WordPress.org. WordPress `translations_api()` uses a 3-second default HTTP timeout, which can plausibly explain earlier cURL 28 on this slow connection.
- Patched existing private `scripts/set-persian-wordpress.sh` so first core language pack installation runs with an **ephemeral WP-CLI `--exec` registering the `http_request_args` filter via `WP_CLI::add_wp_hook`**, setting a per-request minimum 30-second timeout for only official `api.wordpress.org` and `downloads.wordpress.org`. No global networking settings, mu-plugins, or persistent HTTP filters added. Confirms fa_IR package installed before switching `WPLANG`.
- Existing site uses `get_locale()=en_US`, `WPLANG` empty and fa_IR not installed, as of user's last actual locale probe. **Revised command is committed, but no subsequent host execution yet.** Safe next action: run existing private helper from `git archive refs/remotes/pvdak-deploy/main scripts` after the already-established deploy.sh, using `--apply` *without* `--admins`. Maintain public maintenance and both intake forms off.

## 2026-10-22 — PERSIAN-LANGUAGE-HOST-PASS-031 (verified at 17:53 UTC)
- User executed the existing private `scripts/set-persian-wordpress.sh --apply` via `git archive refs/remotes/pvdak-deploy/main scripts` after `DEPLOY_OK f92a12ae7e52240f210faf4bf0d3d60e8a834cd2`.
- Host output: `Downloading translation from https://downloads.wordpress.org/translation/core/7.1.3/fa_IR.zip`, `Language 'fa_IR' installed`, `Success: Language activated`, `PERSIAN_SITE_OK: locale=fa_IR`.
- Official Persian language package for WordPress 7.1.3 is now **installed**, site default language switched to **fa_IR** and runtime locale verified **fa_IR**. Earlier language warning has been resolved by an actual host write operation. Existing administrator-specific profile locales were intentionally not changed (`--admins` was not used).
- No WordPress reinstall, DB reset, PHP/SSH/DNS rewrite, Cron modification, Core plugin upgrade or public intake toggle performed. Core 0.8.0 remains previously verified active; intake both OFF and public maintenance ON from last preflight (not re-tested after locale switch).
- Remaining configuration warning from the 17:23 audit: `Legal topics parent configured` (no published legal-topics parent selected/found). Existing `pvdak_editor_prepare_structure` is opt-in, administrator-only, idempotent and nonce-protected via the `pvdak-home-content` dashboard. It creates published empty parent Page and FAQ, three content categories, and four draft topic child Pages, preserving existing content. Parent creation is not yet confirmed on host.
- Further multi-account access-isolation, privacy and assignment-concurrency E2E tests remain pending; do not enable intake or disable public maintenance based solely on successful language setup.

