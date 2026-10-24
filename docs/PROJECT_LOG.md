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

## 2026-10-22 — ROLE-READINESS-032 (theme 0.8.9, new host test not yet executed)
- User requested remaining-work list followed immediately by further development. Captured a 10-item prioritized project backlog in `docs/REMAINING_WORK.md` with acceptance criteria and clear distinction between confirmed installed versions and unverified future work.
- Added **`scripts/pvdak-role-capabilities-smoke.php`**, a 68-assertion **read-only** WP-CLI check: Core/maintenance/intake state, privacy configuration for both legal request and professional models, admin rights and account capabilities for WordPress subscriber plus four PVDak custom roles. It operates on the WordPress role registry, **not on actual user accounts**, and does not create any records or print case data. It does not replace real multi-account privacy tests.
- Integrated role audit into existing private `scripts/pvdak-status.sh --test` using error-reporting and fail-closed behavior; no new deployment or GitHub Actions flow.
- Improved admin-only `inc/pvdak-home-readiness.php`: separate check for legal-topics parent (with direct link to existing non-destructive content initializer), base user roles and enabled maintenance screen; no new public endpoint or intake flows.
- Bumped only public theme version to **0.8.9**; bundled/installed PVDak Core code **unchanged at 0.8.0**, avoiding plugin source mismatch on theme-only Cron deployment.
- Updated `README.md` to remove stale claims that Core is not installed, WP language is English, and no host tests were run. Confirmed last *host* output had Core 0.8.0 active and WordPress `fa_IR`. Do not claim theme 0.8.9 or the new 68-check role audit passed on host until user supplies the next output.
- **Pending on-host:** check new theme PHP lint and 68 role assertions, and confirm whether editorial administrator clicked the content initialization button. Keep maintenance ON and both intakes OFF. Real client/lawyer E2E, concurrent assignment test, verified license review and all public launch gates remain pending.

## 2026-10-22 — TWO-ACCOUNT-SYNTHETIC-033 (next phase after verified 0.8.9 deployment)
- User provided host output `DEPLOY_OK 5a8b7517d49279a70a9ed6558555674232f49809`; theme **0.8.9** confirmed active. WordPress 7.1.3 is **fa_IR**, bundled/installed Core **0.8.0 active and MATCH**, both public intakes **UNSET (defaults OFF)**.
- Theme tests PASS: editor bootstrap 14, legal search 11, WordPress preflight **23 checks, 0 failures, 1 warning**, exact remaining WARN `Legal topics parent configured`. Other results: Core policy 40, WP Core acceptance 26, role capability audit **68 checks / 0 failures**, anonymous access **19 checks / 0 failures**, both negative/positive diagnostic controls PASS.
- Implemented `tests/pvdak-core/two-account-privacy-smoke.php` exercising the **actual bundled Core PHP entry** in an isolated PHP-only WordPress mock. Two synthetic accounts own distinct legal requests and distinct professional profiles. Tests check anonymous login gates, correct own-content display in `pvdak_core_my_requests`, `pvdak_core_dashboard`, `pvdak_core_professional_dashboard`, refusal of forged link to another user's profile, and defensive author filtering even when a mock DB query returns a foreign request. Exactly 23 assertions, including a count guard; any failure exits nonzero.
- Integrated this fixture into the existing `scripts/check-pvdak-core.sh` and output into `scripts/pvdak-status.sh --test`. Uses `PVDAK_CORE_SOURCE` injected from actual theme-bundled Core; avoids relying on the private Git checkout HEAD. No actual user, case, DB, WordPress option or access change occurs.
- IMPORTANT: The new **two-account synthetic test has NOT yet executed on the host** and should be verified before accepting its result. It is not a replacement for live two-account browser tests in an isolated staging WordPress, real owner-by-owner privacy checks or MariaDB concurrent assignment verification.
- No modifications to active PVDak Core PHP or its plugin installation; no new Cron/Actions/secrets and no change to maintenance or intakes. Legal topics parent remains a one-time editorial setup action available from the existing admin readiness screen.

## 2026-10-22 — MULTIACCOUNT-HOST-PASS-034 (verified host deployment at 18:18 UTC)
- User provided `DEPLOY_OK 57fc1cb080dd3db21fbe92d7f10eebeedfefd0dd`. Verified active theme **0.8.9**, WordPress 7.1.3 `fa_IR`, Core standalone **0.8.0 active** (bundled/installed copies MATCH), both intakes UNSET/default OFF.
- Real host test results: `PVDAK_TWO_ACCOUNT_PRIVACY_OK: checks=23 expected=23` (synthetic, no accounts or data writes); `PVDAK_ROLE_CAPABILITIES: checks=68 failures=0 expected=68` (read-only role registry); `PVDAK_ANON_LIVE_SMOKE: checks=19 failures=0 expected=19`; `PVDAK_CORE_ACCEPTANCE: checks=26 failures=0 expected=26`; `CORE_POLICY_SMOKE_OK: 40 checks`. Preflight **23 checks / 0 failures / 1 warning** (`Legal topics parent configured`). All status checks `PASS`; `PVDAK_DIAGNOSTIC_END status=OK`.
- Added private `scripts/pvdak-staging-multiuser-e2e.php`, database-backed WordPress test with **strict production blocking**: requires `PVDAK_E2E_ISOLATED_STAGING===true`, non-production URL, non-`$HOME/public_html` ABSPATH, active Core, public maintenance ON and both intakes OFF. If staging is correctly configured, creates **two disposable WordPress users and four strictly private synthetic records**, verifies own/foreign data visibility from real queries and forged professional profile link, then deletes only created fixtures in `finally`. Output criterion: `checks=20 failures=0 expected=20 cleanup=OK`. Prints no passwords, email addresses or private record contents.
- **Do not run this test on production**, and do not include it in the ordinary production `pvdak-status.sh --test`: wired only `php -l` of the private staging fixture into `scripts/check-pvdak-core.sh`; compact host reporter includes `STAGING_MULTIUSER_SCRIPT_LINT_OK (NOT RUN: isolated staging required)`. Described required DB separation, staging backups and manual deployment in `docs/STAGING_MULTIUSER_TEST.md`.
- The staging database-backed test was committed but **has not been executed**; no actual staging WordPress setup or live browser-sessions were created by the assistant. No production users/cases/options modified. Continue to keep maintenance ON, legal intake OFF and professional onboarding OFF. Remaining single site configuration warning is legal-topics parent.

## 2026-10-23 — LEGAL-LANDING-SAMPLES-035 (code ready; user-click activation pending)
- User provided **17 exact legal topic names** for sample PVDak landing categories: حقوقی، کیفری، خانواده، ثبتی، ملکی، حقوق اطفال و نوجوانان، حقوق اراضی، حقوق مالیات، حقوق بشر، حقوق شرکت ها، حقوق مالکیت معنوی، حقوق ثبت، حقوق خانواده، حقوق کیفری، حقوق بیمه، حقوق ورزشی، حقوق کار. Names, order and all broad/qualified near-duplicates were preserved as **distinct Page topics** for now.
- Confirmed current theme architecture: `inc/pvdak-legal-landings.php` and Core `includes/specialties.php` select published **WordPress child Pages**, not `category` taxonomies; search/home cards and referral specialization share the selected `legal-topics` parent.
- Added `pvdak_editor_example_legal_topics()` and `pvdak_editor_seed_example_legal_topics()` in `inc/pvdak-editor-bootstrap.php` plus a **separate** administrator/nonce-protected action/button `ایجاد ۱۷ لندینگ نمونه قابل نمایش` on `/wp-admin/admin.php?page=pvdak-home-content`. The admin action reuses a valid selected parent or creates the default `legal-topics` root, then creates only missing child Pages using unique SEO-readable slugs; it never rewrites/publishes existing Pages, including the prior four training drafts.
- Newly created examples are **published with empty content** to exercise page cards, search and topic selection without publishing made-up legal material. Each gets `_pvdak_sample_landing=1`, a visible provisional message on the Page template and a `wp_robots` `noindex` directive while its substantive content is empty. Once the editor adds substantive content, this sample-specific noindex no longer applies automatically.
- Hard gates for insertion: administrator `manage_options` and `edit_pages`, a valid WordPress action nonce, **public maintenance enabled**, and **both Core intake settings OFF**. Cron/theme deployment merely updates code; it NEVER inserts content by itself. ACF root field filled only when empty.
- Expanded synthetic `tests/pvdak-theme/editor-bootstrap-smoke.php` from **14 to 27 assertions**: exact requested strings/order, unique slugs, published but empty sample Pages, explicit noindex behavior, re-run idempotence, existing draft/content preservation and blocked publishing with nonpublished parent. Mapped the new provisional state in `page-templates/legal-topic-landing.php` and stylesheet, theme version bumped `0.8.9 → 0.8.10`.
- Bundled and installed Core **still 0.8.0 unchanged**; no new Actions, Cron, secrets, records, users, intake changes, or maintenance toggle were performed by the assistant. New code has been committed but **the user has NOT yet shown a `DEPLOY_OK`, test run or admin click for version 0.8.10**. Do not claim the 17 child Pages exist in the live WordPress database yet.
- Remaining work: deploy/check the new theme, click the explicit sample button in wp-admin once and verify published child Page counts. The earlier one-warning preflight `Legal topics parent configured` should disappear when a valid published root exists; after sample creation the published child check should pass. Continue independently with isolated staging E2E and privacy testing before opening legal or professional intake.

## 2026-10-23 — LEGAL-SAMPLE-READY-036 (finalized but on-host rollout not yet verified)
- Follow-up reliability fixes to 0.8.10 sample seed: a preexisting draft now increments `unpublished` and does **not** yield a false admin success, while preserving the draft's content and state. The parent resolution uses `pvdak_legal_topics_root_id()` when a published custom root is selected in ACF, otherwise creates/reuses the default `legal-topics`. `pvdak_editor_child_path()` now resolves nested Page URIs correctly, preventing duplicate Page slugs under nested legal-parent hierarchies.
- Added an explicit readiness row `۱۷ لندینگ نمونه موضوعی` in `inc/pvdak-home-readiness.php`. Shows **N of 17 published** by actual WordPress child Pages, separate from the legal root and total published children. Links to the single admin action that fills missing samples. When the admin seeds all entries correctly, both landing preflight checks should become green.
- Synthetic editor test was expanded to 27 checks; includes preexisting draft not published and reported as such, the 17 exact labels in user order, and an empty Page's noindex behavior.
- Version **0.8.10** is committed to `main` and will be deployed by the existing 15-minute cron or one manual `deploy.sh` run. Until a new terminal output and admin button execution is observed, the last confirmed host theme remains **0.8.9**, the 17 database records remain **NOT CONFIRMED**, and Core **0.8.0** continues active/unchanged with intake disabled and maintenance enabled.

## 2026-10-23 — LANDING-CODE-HOST-DEPLOY-037 (verified deployment at 08:10:18 UTC)
- User reported `DEPLOY_OK a42e72dc65ba1ac49bbd9ec7d960a38e1afa4421` from existing `$HOME/.pvdak-deploy/deploy.sh`; automatic versioned rollout and rollback backup `/home/amirhoseainsabet/.pvdak-deploy/backups/20261023T081018Z-a42e72dc65ba` confirmed by host output.
- The pushed theme is code version **0.8.10** with the administrator-only, nonce-protected `pvdak_editor_publish_sample_topics` action and 17 exact legal topic names. Deployment alone does NOT create database Pages or prove the 27-check updated editor test passes.
- **Outstanding:** an authenticated administrator must use `wp-admin/admin.php?page=pvdak-home-content` → `ایجاد ۱۷ لندینگ نمونه قابل نمایش` while maintenance ON and both intake modes OFF. Then confirm readiness **17 of 17** and the theme test summary. No report of user clicking this action yet; published child Page count remains unverified.
- Installed PVDak Core is last reported **0.8.0** and unchanged by theme-only deployment. No new Cron, Actions, secrets or database writes were made by this assistant.

## 2026-10-23 — DYNAMIC-MEGA-NAV-038 (0.8.11 source change, browser/host deployment pending)
- User confirmed the WordPress `primary` menu location uses a dynamic menu (called `pvdak-main`) and asked for a two-column dropdown **on hover**, based on their provided RTL navigation screenshot, recolored for PVDak.
- Current code `header.php` already calls WordPress `wp_nav_menu( theme_location='primary' )` separately for desktop and mobile and only uses static fallback if no menu is assigned. **No hard-coded menu categories, nav links, or content were added.** WP items must be nested as child entries in Appearance → Menus to appear in dropdown; merely publishing Page records does not auto-add them to a WP menu.
- New versioned `assets/css/pvdak-navigation.css` is loaded after `rtl.css` via `functions.php`. Desktop `>1030px`: top-level WordPress `menu-item-has-children` opens centered RTL **two-column scrollable mega panel** with navy/teal gradient, rounded light panels, gold dots, PVDak accent colors, accessible `:hover` and `:focus-within` triggers, no pointer gap. Third-level children remain under their parent item.
- Mobile `<=1030px`: reuses the existing `<details>` disclosure for tap and displays nested `pvdak-mobile-links` as inline scrollable submenu lists; supports keyboard focus and prefers-reduced-motion. No new JavaScript library or JS state to interfere with links.
- Added `tests/pvdak-theme/navigation-smoke.php` (13 static read-only checks for enqueuing, two WordPress menu uses, CSS responsive rules, opening behaviors, mobile markup, RTL and theme colors); integrated into existing `scripts/check-pvdak.sh`; status displays `PVDAK_MEGA_NAV_OK`. A cross-file source check confirmed all 13 string predicates and balanced CSS brace counts. **No actual host PHP, live mouse/touch/browser or visual acceptance has been performed in this turn**.
- Theme version bumped `0.8.10 → 0.8.11` (Core `0.8.0` remains unchanged). No real WordPress data, menu assignment, 17 Page records, WP settings, private requests, users, intakes, or maintenance mode changed by assistant. Existing 15-minute Cron and `deploy.sh` workflow preserved.
- Next: user deploys/checks new theme with existing publisher and private status script; confirm `PVDAK_MEGA_NAV_OK: checks=13`, no pre-existing test regressions; verify primary menu `pvdak-main` has actual child items, hover reveals 2-column dropdown and keyboard/mobile are usable. Do not open intake or disable maintenance.

## 2026-10-23 — DYNAMIC-MEGA-NAV-HOST-039 (host confirmation at 09:13:25Z)
- User host shell reported `DEPLOY_OK 10d76bf14f8f7efadf36d7fe5f6ce71148b0b1f0`; existing deployment script took backup `/home/amirhoseainsabet/.pvdak-deploy/backups/20261023T091325Z-10d76bf14f8f`.
- Theme **0.8.11** source with WordPress `primary` two-column RTL CSS mega dropdown is now deployed. Deployment command returned to shell; user entered `^C` afterward, not evidence of a deploy failure.
- **Not yet verified:** live browser hover/focus/touch rendering, actual `pvdak-main` assigned location, child menu items, or `PVDAK_MEGA_NAV_OK: checks=13` from the current host diagnostic. Do not represent them as tested.
- Production Core **0.8.0** and intake options remain last-known off, not directly re-tested after this deployment; public maintenance kept unchanged by theme update.
- To verify quickly after deploy: browser refresh and mouse hover on parent with assigned WP child items; optionally run existing private `pvdak-status.sh --test` workflow for 13 static assertions and all previous health checks.

## 2026-10-23 — MEGA-NAV-MAIN-CSS-HOTFIX-040 (0.8.12 committed, live visual recheck pending)
- User sent a screenshot of the deployed **0.8.11** WordPress `primary` dropdown: despite parent "موضوعات حقوقی" having 17 real subitems, it was still displayed as the **old narrow, vertical white submenu centered over the homepage hero**, rather than the requested dark RTL two-column mega panel. This confirms actual 0.8.11 visual output did not apply the new mega styling; screenshot alone cannot prove whether this was due to asset loading, CSS cache or a third-party optimization plugin.
- A direct remote attempt to read `https://pvdak.com/wp-content/themes/pvdakcom-main/assets/css/pvdak-navigation.css` was inaccessible from external browsing, so no definitive HTTP asset diagnosis was possible. Avoid claiming a specific network/403 cause.
- Applied robust stylesheet fallback: **moved the complete menu CSS into the existing always-enqueued theme `style.css`**, after original theme styles. The old extra enqueued `assets/css/pvdak-navigation.css` was removed from `functions.php`, and the redundant file deleted from repository. Navigation now depends only on the normal `pvdak-style` URL, already rendering other homepage styling, with normal version cache-busting.
- No WordPress menu record, submenu label, URL, parent/child relationship or Core data was changed. Responsive/touch menu, hover + keyboard focus behavior, PVDak navy/teal/gold palette and viewport-bound RTL two-column layout preserved. Theme bumped **0.8.11 → 0.8.12**. Core code untouched at 0.8.0, both intake forms remain in last-verified OFF configuration and maintenance setting was not touched.
- Updated `tests/pvdak-theme/navigation-smoke.php` (13 static source assertions) and `scripts/check-pvdak.sh` to check main CSS instead of removed external asset; compact `pvdak-status.sh --test` still reports `PVDAK_MEGA_NAV_OK: checks=13`. Confirmed **13/13 JS predicate matches against fetched repository files** and CSS brace counts balanced (364 open, 364 close), not a substitute for browser or PHP host verification.
- **Next host acceptance**: deploy current main using existing `$HOME/.pvdak-deploy/deploy.sh`, confirm theme **0.8.12** and hover the WordPress-managed "موضوعات حقوقی" menu. Request an updated screenshot only if still visually incorrect. The 13 checks and actual hover/mobile appearance are **not yet host verified**.

## 2026-10-23 — HOMEPAGE-FOUR-SLOTS-SWIPER-041 (0.8.13 code committed, deployment pending)
- User provided two screenshots of live homepage. Requested (1) remove three hero CTA boxes for lawyer/consultation/question, (2) show **only four manager-selected published Pages** in white quick-access strip instead of all WordPress menu items, and (3) turn "برای چه موضوعی به کمک نیاز دارید؟" into an **RTL one-row, seven-slide desktop carousel**, autoplay and infinite loop.
- Root cause of overlong quick strip was `front-page.php` iterating the entire `wp_get_nav_menu_items( location='pvdak_quick' )` collection without limiting to four; topics was a CSS 8-column grid without carousel JS.
- Changed `front-page.php` to remove the whole `pvdak-home-hero-actions` block but preserve form, heading and hint. Quick ribbon now uses `pvdak_home_quick_pages()` from new `inc/pvdak-home-quick-links.php`: accepts exactly four explicit ACF Page selectors from homepage editor and returns **at most four published distinct WordPress Pages** with live title+permalink; when all four explicit fields are empty, temporarily falls back to at most four published Page items from the original `pvdak_quick` WordPress menu. After any ACF selection, it never mixes in menu leftovers. The editor needs to select all four for exactly four visible cards.
- Added the four `quick_page_1..4` ACF Free `post_object` fields in tab "۹. چهار دسترسی سریع زیر بنر" on current homepage. The main/header `pvdak-main` WordPress menu location remains unchanged; no user menu entries modified.
- Transformed `#topics` to `pvdak-topics-swiper` with a single flex-track. CSS card width uses static `calc((100% - 72px)/7)` for desktop (7 visible), `calc((100% - 36px)/4)` for tablet, `calc((100% - 9px)/2)` for phone. Local `assets/js/pvdak-home.js` continues Persian search plus a dependency-free swiper-style infinite carousel: 3.4-second autoplay, 470ms slide animation, DOM recycling instead of clones, two manual controls, swipe gestures, keyboard focus/hover/hidden-tab pause, reduced-motion support and scrollable no-JS fallback. Cards remain linked to the same real published legal-topic child Pages, with no changes to their records.
- Added **18 read-only smoke assertions** in `tests/pvdak-theme/homepage-layout-smoke.php` and connected them to existing `scripts/check-pvdak.sh` and `scripts/pvdak-status.sh`. Inspected new sources via GitHub and programmatically checked 18/18 source invariants, CSS braces balanced, and compiled the JS source with V8 `Function` constructor: **syntax PASS**; these are local code-only checks, NOT on-host PHP/runtime/visual acceptance.
- Theme bumped from **0.8.12 to 0.8.13**, Core unchanged at 0.8.0. No direct WordPress admin/DB edit, real pages/users/intake change, Cron change, new workflow, external CDN or asset fetching. Changes are pushed to GitHub main but **the actual 0.8.13 deploy and PHP smoke execution remain unverified on host**.
- Next: `bash "$HOME/.pvdak-deploy/deploy.sh"`, then the previously established private archive/report command; expected `PVDAK_HOMEPAGE_LAYOUT_OK: 18 checks`, theme `0.8.13`. In wp-admin "محتوای صفحه اصلی", pick four published Pages in the new ACF tab and save. Check single-row seven-card carousel, 3.4-sec loop, mobile and Persian search visually after refresh.

## 2026-10-23 — REAL-SWIPER-042 (v0.8.14 committed; no host deployment yet)
- User correctly challenged the 0.8.13 homepage legal topics "swiper": it was a home-grown DOM-reordering/scroll-fallback implementation, **NOT the actual Swiper.js library**. The user explicitly wants Swiper with 7 visible slides, true infinite loop and autoplay; do not claim the earlier version fulfilled that.
- Verified official Swiper documentation and npm release info (2026): stable **Swiper 14.3.0**, official browser bundle `swiper-bundle.min.js` and CSS. Replaced the imitation with a real `new window.Swiper(container, options)` initialization. In `functions.php` enqueue pinned Swiper bundle JS/CSS from jsDelivr only on WordPress front page and make `pvdak-home-search` depend on the `pvdak-swiper` script. If main script fails, local JS attempts pinned bundle and style from unpkg. **An external CDN dependency remains; if both fail, cards are readable as a single scrolling row, with no false claim that Swiper loaded.**
- Updated `front-page.php` with `swiper`, `swiper-wrapper` and `swiper-slide` native class names on the WordPress-derived published child Page links. Swiper config: desktop **7** slides, tablet **4**, mobile **2**; `loop:true`, `slidesPerGroup:1`, `autoplay.delay:3400`, touch drag, next/previous controls, RTL from page direction, keyboard and A11y, reduced-motion and hidden-tab/focus pause. CSS cleanup makes the actual Swiper inline slide widths and transitions override prior fixed-width pseudo-slider CSS, keeps no-CDN fallback accessible.
- Persian text search retained; destroy native Swiper while search filters Page links and reinitialize when cleared to avoid mixing `loop` and `hidden` slides. No stored records, four ACF-selected quick Pages, WP menu, or Core code were changed. Theme version **0.8.13 → 0.8.14**; Core remains **0.8.0**.
- Updated `tests/pvdak-theme/homepage-layout-smoke.php` from 18 to **20 source + synthetic assertions**, now requiring actual enqueued Swiper 14.3.0 CSS/JS, dependency ordering, native Swiper DOM, `new Swiper`, loop, autoplay, breakpoints, CDN retry and search behavior. Ran private source inspection: **13/13 integrity probes passed**, CSS curly braces balanced (391 each), JavaScript compiled in V8 and a **mock browser Swiper constructor successfully received `slidesPerView=7`, `loop:true`, autoplay 3400ms and 2/4/7 breakpoints**. These are **NOT tests of actual browser CDN or production host runtime**.
- **Host deployment and live browser carousel remain unverified**. Next action is the EXISTING `bash "$HOME/.pvdak-deploy/deploy.sh"`, then existing private `scripts/pvdak-status.sh --test` via `git archive refs/remotes/pvdak-deploy/main scripts tests`; expected `PVDAK_HOMEPAGE_LAYOUT_OK: 20 checks` and theme 0.8.14. Confirm actual auto transitions in browser; if only scroll, inspect whether the CDN bundle loaded (`typeof window.Swiper === 'function'`), not CSS alone. No Actions/Cron/secrets/activation changes.

## 2026-10-23 — LOCAL-SWIPER-INSTALL-043 (v0.8.15 committed, host install pending)
- User reported that even after 0.8.14 the browser only showed a scrolling topics row and specifically asked whether Swiper JS/CSS were bundled and enqueued. Honest diagnosis: 0.8.14 enqueued **external jsDelivr files only**, with unpkg fallback; NO Swiper library files were in the GitHub repo or PVDak theme, so a CDN-blocked page could not initialize real Swiper.
- Confirmed official Swiper v14.3.0 distribution is available from cdnjs/npm repositories; minified JS ~150 KB, CSS ~14 KB, MIT license. Due to inaccessible direct binary download from current tool runtime, **did NOT pretend these vendor bytes were pushed to GitHub**.
- Added private `scripts/pvdak-install-swiper-local.sh`: a reproducible, idempotent and fail-closed **host-side installer**. Attempts official Swiper 14.3.0 minified JS, CSS and LICENSE from Cloudflare cdnjs / jsDelivr / unpkg, validates minimum size and identifying contents, then installs into a versioned **persistent same-origin WordPress path** `$HOME/public_html/wp-content/uploads/pvdak-static/swiper-14.3.0/` with SHA256SUMS and mode 0644. It never overwrites WordPress DB/users/Core and does not require npm, SSH secrets or new Cron.
- In `functions.php`, frontend enqueues vendor CSS/JS **ONLY** when three installed files exist on the host; dependent `pvdak-home.js` loads afterward. All browser-side external CDN URLs and CDN fallback script injection have been removed. If not yet installed, it explicitly warns in browser console and leaves Page links in a readable horizontal row.
- Added `Swiper local assets` and `PVDAK_SWIPER_LOCAL_OK` **read-only diagnostic checks** to `scripts/pvdak-status.sh --test`, which fail when missing, so successful theme code deployment cannot be mistaken for real library installation.
- `tests/pvdak-theme/homepage-layout-smoke.php` now 21 assertions for own-domain enqueues and private official-vendor installer with no CDN references; version bumped `0.8.14 → 0.8.15`. Cross-file review confirms new expected paths and no CDN links in frontend JS/PHP, but full host PHP/network/browser behavior is **UNVERIFIED**.
- Next: user runs existing `deploy.sh` and archives `scripts` to execute one-time host vendor installer; **expected** `SWIPER_LOCAL_ASSETS_OK`, `Local Swiper library: PASS` and `PVDAK_SWIPER_LOCAL_OK`. If CDN origins are unreachable from the server too, the installer stops and the next diagnostic will correctly fail; a verified alternate acquisition path would be required. Do not say Swiper is locally installed until user provides that output.
- Existing 4 editor-selected Pages, WordPress primary menu, legal topic Pages, Core 0.8.0, maintenance and disabled intakes unchanged. Existing 15-minute deploy route preserved, installed files persist outside theme release folder.



## 2026-10-23 — SWIPER-ADMIN-LOCAL-PROVISION-044 (v0.8.16, host verification pending)
- Kept existing main branch, cPanel theme Cron, 15-minute deployment, maintenance, Core 0.8.0, WordPress content and the persistent uploads/pvdak-static/swiper-14.3.0 destination unchanged.
- Added inc/pvdak-swiper-local.php: admin-only, transient-throttled one-time acquisition of official Swiper 14.3.0 files over HTTPS (cdnjs/jsDelivr/unpkg), checking minimum size, expected content and required MIT license. Stages files before placing JS/CSS/LICENSE and writes SHA256SUMS completion marker last. No browser-side CDN, new cron, keys, WP users, cases, intake or membership effects.
- pvdak_home_assets() only enqueues vendor assets after shared file-size/marker validation, followed by existing local pvdak-home.js; former private scripts/pvdak-install-swiper-local.sh is still available and unchanged.
- Source-only regression checks strengthened for one-time admin acquisition and no frontend CDN dependency; theme version 0.8.16. This DOES NOT prove successful installation: only a logged-in admin visit to WordPress after deployment can trigger PHP-side acquisition; if host outbound CDNs fail, the same-origin files remain absent. Do not claim carousel works until host PVDAK_SWIPER_LOCAL_OK and visual autoplay/loop checks.


## 2026-10-23 — SWIPER-THEME-BUNDLE-045 (v0.8.17, cPanel verification pending)
- User reported 403 responses for the theme's own `rtl.css` and `assets/js/pvdak-home.js` and requested structural dependencies be versioned inside the theme. Pinned Swiper 14.3.0 JS/CSS and MIT LICENSE are now committed under `assets/vendor/swiper-14.3.0/`, shipped with every existing theme release.
- `functions.php` enqueues Swiper from the active theme path ahead of `pvdak-home.js`. Removed admin downloader inclusion and obsolete module. Existing uploaded Swiper files are no longer used, and no WordPress uploads/media are removed.
- Existing private deploy runner uses `umask 077` and `rsync -rlt --chmod=D755,F644` without `-p`; new files can become mode 0600. Repository copy updated to `rsync -rltp --chmod=D755,F644` and safe rollback mode handling. **Updating the GitHub copy does not automatically replace the existing private runner.** Operator must review/install it at the same private path and run normal deploy. No new workflow/cron/access keys.
- Updated read-only checks for bundled library. No change to PVDak Core, maintenance mode, intake flags, menu contents, database or registered users. Push alone does not certify live HTTP assets or actual autoplay.


## 2026-10-23 — SWIPER-AUTOPLAY-046 (v0.8.18, deployment pending)
- User confirmed production deploy `26db39d4c787`: DEPLOY_OK and HTTP 200 for RTL, homepage JS and both bundled Swiper assets.
- Reduced Swiper autoplay interval from `3400` to exactly `3000` milliseconds in `assets/js/pvdak-home.js`; slide animation speed and responsive behavior are unchanged. Existing hover/focus/background/reduced-motion pauses still apply.
- Theme bumped to `0.8.18` to invalidate cached local JS; updated homepage source assertion to require `delay: 3000`. No changes to Core, deployment script/cron, WordPress content, maintenance, requests, menus, or media. Run existing `$HOME/.pvdak-deploy/deploy.sh` for publication.


## 2026-10-23 — PREMIUM-TOPICS-AND-DEMO-EDITORIAL-047 (v0.8.19; WordPress seeding pending)
- User approved rework of real legal topic Swiper into 6 visible cards on desktop, 3 on tablets, 2 on mobile, keeping 3000ms autoplay and infinite loop, with premium cards, six muted colorways, captions, decorative bands and 17 hand-authored SVGs matching legal-topic Page slugs. Existing published Page titles/links/search and ACF remain authoritative; no duplicate static cards.
- Swiper/CSS/JS all stay bundled inside the same theme release and same GitHub/main/cPanel 15-minute Cron, no new workflow or secrets. Style source in style.css updated; assets/css/pvdak-home.css companion snapshot updated.
- Prepared an *explicit*, one-time private WP-CLI `scripts/pvdak-seed-demo-blog.php` to publish exactly five marked Lorem ipsum blog posts plus five original GD-generated JPEG featured images into the active magazine category and prefill ACF only if empty. Uses existing static homepage and selected category. Re-run is idempotent by fixed post slugs and _pvdak_demo_blog marker; existing non-demo content is never overwritten. Sample posts have noindex,nofollow and clearly state they are placeholders.
- This script is **not run by normal theme deploy or the public website**. It must be invoked explicitly using `wp eval-file` as the existing cPanel account, only while the maintenance gate is on and legal/member intakes are off, with GD/ACF available. No WordPress post/attachment changes are made by merely pushing the files.
- Theme 0.8.19; source assertions updated; no confirmation of production WordPress sample publication or live carousel yet.


## 2026-10-23 — DESIGN-BATCH-048 (v0.8.20, DRAFT — not deployed)
- User requested batched fixes and explicit review BEFORE any public deployment. Keep `main` untouched until the user approves; established private cPanel deploy cron reads only `main`.
- [1] Swiper: moved visible custom SVG prev/next buttons into section heading with bold gradient styling, hover/focus states, RTL-aware chevrons, keyboard labels; original true Swiper navigation handles, 6/3/2 counts and 3000ms autoplay remain.
- [2] Legal-topic SVG card badges, headings, captions and links now center aligned. No changes to original editable WordPress Page data.
- [3] Homepage magazine: 1 visually featured plus 4 side articles from the selected magazine category (real WordPress Posts), all full-card links and all image-backed; responsive 2x2 grid and mobile stacked list; no hardcoded demo IDs or data overrides.
- [4] Single article: breadcrumb, excerpt, publication date, reading time, high-quality featured image, long-form text layout, taxonomy tags, related posts from same category excluding current and limited to published posts; dedicated WordPress-native comment template and responsive forms. Existing per-post comment settings are respected; old demo posts remain comments CLOSED until the site editor enables them deliberately. Site maintenance and legal/member intake remain unchanged.
- Theme version 0.8.20; added source regression checks. **This is a draft commit object, NOT a main-branch update, release, or live test.** Site still runs 0.8.19 until separate approval and deployment.

- Draft follow-up: corrected magazine query to fetch up to 5 posts when no featured override exists (otherwise 1 featured + only 3 side cards would appear). Also avoided nesting a nav element inside WordPress comments pagination. Added explicit approval checklist docs/PENDING_APPROVAL.md.


## 2026-10-23 — DRAFT-PV-FAVICON-049 (pending review, no deployment)
- Added theme-contained `assets/icons/pvdak-pv.svg` temporary two-tone vector favicon (white P, gold V, dark emerald/navy background) with small-size legibility. No external assets or CDN.
- The classic theme emits the icon in wp_head, login_head and admin_head, suppressing duplicate native WP favicon tags without changing WordPress site_icon option. The standalone maintenance page also emits the icon explicitly because it bypasses wp_head.
- Added G to docs/PENDING_APPROVAL.md; 0.8.20 draft already contains A–F. Existing real production main and website remain unchanged until the user requests review/publish.


## 2026-10-23 — DRAFT-PREMIUM-PROCESS-050 (review batch v0.8.20, not deployed)
- Added item H: visually upgraded the four-step process section in `front-page.php` to four editorial-quality translucent cards against the existing dark navy/teal gradient; four unique stroke SVGs, refined step number pills, consistent centered text, ambient background accents and a connecting desktop progress line.
- Used semantic `ol/li` rather than an interactive/phony slider: the steps explain sequence and should not swipe or navigate. Preserved all original editable ACF title/description fields and four-step sequence. Responsive two-column grid up to tablet/mobile, one column at tiny widths, accessible reduced-motion styling. Changed `style.css` and mirrored `assets/css/pvdak-home.css`.
- Updated source smoke checks and `docs/PENDING_APPROVAL.md`. All A–H are still draft only; `main` and its existing production deploy path remain unchanged. No WordPress content/options, maintenance, Core or user intake state changed. User will authorize publication as a separate step after review.

- Small compatibility fix: clears legacy direct-child step span margin for the new visual progress decoration to keep equal card heights on mobile.


## 2026-10-23 — RELEASE-APPROVAL-051 (v0.8.20)
- User explicitly approved publishing all eight pending UI/editorial changes A–H together. Scheduled fast-forward from the completed draft chain to the existing `main` branch; existing 15-minute private cPanel cron/deploy path is unchanged.
- Theme v0.8.20 includes custom Swiper arrows, centered topic card art, five real WordPress post cards with fully clickable images, single article/related posts/native comments layout, PV favicon (including maintenance page), and premium four-step timeline. The seeded five WP posts remain unchanged and comment settings are preserved.
- Note: GitHub main update is NOT equivalent to successful host deployment; verify `DEPLOY_OK` for release SHA and check article pages and new styles after cPanel fetch. The theme runner includes PHP syntax checks and rollback.


## 2026-10-23 — DRAFT-PROCESS-CONNECTOR-052 (v0.8.21, pending approval)
- User screenshot shows an unexpected horizontal line crossing all four translucent process cards and icons. Root cause: `.pvdak-process-timeline .pvdak-process-grid::before` line at top:66px. Removed the pseudo-element from versioned `style.css` and helper CSS file, as well as its now-unused responsive override. Per-card accent bars, titles, steps, SVGs, accessibility and responsive layout remain unchanged.
- Theme version raised to 0.8.21 for cache invalidation; regression assertion now enforces that the connector line is absent. Added `docs/PENDING_APPROVAL_NEXT.md` with one pending change.
- DO NOT publish to main until the user explicitly instructs to do so, because the established 15-minute cPanel cron auto-deploys main.


## 2026-10-23 — DRAFT-EDITORIAL-COMMENTS-053 (v0.8.22, pending approval)
- User reported absent comment form on demo article, unclear related-posts eyebrow "ادامه مطالعه", and oversized, unappealing masthead. Root cause is confirmed in demo post seeder: all five sample posts were deliberately created with `comment_status=closed`. WordPress suppresses its form accordingly.
- Added `inc/pvdak-demo-comment-preview.php` with the official WordPress `comments_open` two-argument filter to allow native comment submission for admin only, during active maintenance, only on published `_pvdak_demo_blog=1` posts. No DB or discussion-option mutation. The exception automatically disappears when maintenance is turned off; real posts continue to follow WordPress discussion settings.
- `single.php` puts comments immediately after article; `comments.php` surfaces the form before the existing comments, with a contextual editor-only hint and safe privacy text. `content-single.php` provides a #respond link where commenting is available. Related-post eyebrow "ادامه مطالعه" removed; plain "مقالات مرتبط" remains.
- Restrained editorial title scale to 24–33px desktop / 21–25px mobile, subtle header surface, lighter margins, 395px maximum cover, 755px reading column, and more compact comment/related cards. Updated `style.css` and editorial stylesheet; theme v0.8.22, with new regression checks.
- Pending approval items 2–5 added to docs/PENDING_APPROVAL_NEXT.md together with earlier line-removal item 1. NO change to main, cron, deployment path, public maintenance, intake or real WordPress post data. PHP host/browser checks remain pending.


## 2026-10-23 — DRAFT-PUBLIC-PAGES-AUTH-054 (0.8.23; HOLD)
- Added WP-native about/contact/provisional privacy/provisional terms Pages, branded /account-login/ and /my-pvdak-account/ templates, and footer information links. Missing Pages will be provisioned once on the next administrator wp-admin visit after actual deployment, without changing existing Pages, menu selections or WP users. Provisional policies are noindex; no invented contact details.
- Header login now uses site Page, not wp-login.php, while logged-in account goes to PVDak Core dashboard or a protected fallback Page. Frontend Core wp_login_url links are filtered but official wp-admin login stays available for site owners. Authentication uses wp_signon/cookies and nonce, no separate credentials, SMS or registration.
- Contact form uses nonce, server validation, hidden trap, transient throttle, wp_mail to WordPress admin email; maintenance mode restricts access to admin preview. No legal/membership intake enabled or altered.
- Theme 0.8.23 is a standalone draft commit, NOT on main; existing production code and auto-deploy stay unchanged until explicit publish approval. Added checklist items 6-9 alongside previous 5.

- Post-build refinement: footer grid now accommodates its fourth information-links column on desktop and stays usable on tablets/mobile, with a clear heading for the links.

- Input hardening: reject array-shaped login/contact POST values before passing them to WordPress string sanitizers, avoiding malformed-request PHP warnings or errors.

- Added FAQ native root Page to the missing-page list (preserves any previously created legal-faq Page), conditional FAQ footer link, and a safe admin-only ?preview=1 UI mode for the branded login so the manager can inspect the design while remaining signed in during maintenance. Real credential testing still requires the appropriate independent acceptance environment.


## 2026-10-23 — DRAFT-CLEAR-HOMEPAGE-ENTRY-055 (0.8.24, NOT DEPLOYED)
- Replaced role-oriented 'از کدام مسیر وارد می‌شوید' section with three accessible full-card action links to legal topic index, magazine, and signed-in account/login. No fictitious route choices, disabled form or unfinished registration. Premium SVG cards, mobile layout, reduced-motion styling. Old ACF inputs remain stored but homepage no longer displays them, as the new destinations are functional site navigation.
- Legacy ACF defaults title updated for future admin copy; theme version 0.8.24. Main remains untouched and maintenance ON.


## 2026-10-23 — DRAFT-ACCOUNT-TOOLS-056 (0.8.25, NOT DEPLOYED)
- Added a WordPress-native branded password recovery request/reset page using retrieve_password/get_password_reset_key core flow, check_password_reset_key and reset_password, generic non-enumerating message, 90-second throttling, nonce-protected POST, minimum 12-character new password, native email transport, no external credentials.
- Added a private account profile editor for logged-in user display_name only; username/role/verified email are not user-modifiable. Account shortcut links and login recovery link, no-cache and noindex on auth/profile/account routes. New Pages provisioned only from admin_init after future release, no WordPress data changes yet.
- Recovery is subject to configured WP outgoing mail. Real delivery/reset and rate-limiting must be tested before site launch.


## 2026-10-23 — DRAFT-REGISTRATION-SUPPORT-057 (0.8.26; not deployed)
- Added self-service registration Page scaffold, hard-gated behind BOTH the existing maintenance OFF condition AND WordPress users_can_register AND new explicit pvdak_registration_enabled option (default 0). All accounts use lowest-privileged client/subscriber role, never self-grant professional status; claimed professional type only saved as unverified user metadata. Approval/email verification is still missing, so NEVER switch on until those are completed. Generic validations, nonce, throttle, honeypot.
- Added private non-public support ticket CPT handled in the theme, user-restricted ticket list, 90s rate limit, nonce, admin-only reply metabox and support section under account; ticket body remains accessible to administrators only through backend. Ticket replies only visible to original owner. No public REST endpoints, no legal case details, no notifications.
- Professional dashboard section only appears if PVDak Core is active and current user has their own existing application. Underlying Core manual assignment/verification/capacity untouched; no client case leaks. Theme version 0.8.26. No automatic public feature enablement and no main update.


## 2026-10-23 — DRAFT-CORE-QUOTA-058 (Core 0.8.1 / theme 0.8.26, NOT INSTALLED)
- Reused existing manager-only locked matching workflow; candidate dropdown now sorted by fewest active internally assigned cases (tie by stable profile ID) and shows occupied/limit counts. Assignment stays MANUAL, no notification or confidential case information goes to professionals.
- Added manual subscription records for each professional: plan, state, expiry, monthly referral ceiling; a manager-only metabox saves bounded values. Separate global switch `pvdak_core_subscription_gate_enabled` is default 0, so existing matching eligibility does NOT change until specifically enabled after staging testing. This is NOT a payment/billing/renewal implementation and has NO new purchase route.
- In optional gate mode, a matching attempt fails closed for missing/expired/inactive plan or monthly quota exhaustion. Counts come from existing UTC audit meta. Existing assignment advisory locks remain, no auto-assignment. Read-only professional dashboard shows the manual plan status without exposing case contents.
- Core source raised to 0.8.1. It resides in `packages/pvdak-core` and is NOT deployed by the theme-only cPanel cron; do not claim this Core version is live until an explicit safe Core upgrade after backup/lint/tests. Main remains unchanged.


## 2026-10-23 — DRAFT-RELEASE-PREFLIGHT-059 (0.8.26 / Core 0.8.1, NOT DEPLOYED)
- Added read-only source preflight script `scripts/check-batch-readonly.sh`: PHP lint on all repo PHP, existing theme/navigation/editor tests and privacy/Core policy regression, plus new feature-batch smoke.
- Expanded source checks to assert new 3-action homepage section. Updated public pages/auth smoke expected theme version. Added explicit 10-task review checklist distinguishing implemented code, closed defaults, missing runtime tests and Core installation limitation.
- This only prepares the batch for administrator review. It does NOT update GitHub main, cron, site, users, passwords, Core plugin version on host, intake settings, registrations, or public maintenance.

- Corrected optional monthly quota accounting: queries immutable assignment-audit entries independently of the current assigned-profile meta. Revoked/reassigned cases still consume the originating professional's recorded month quota; default-OFF gate unchanged.

- Polished homepage starter integration with WP ACF: card titles and descriptions remain editable; legacy saved role defaults are auto-normalized on read without overwriting DB. Removed misleading unused URL inputs from ACF UI and renamed the admin tab. Destination URLs remain safe site routes.


## 2026-10-23 — RELEASE-INSTRUCTION-060 (Theme 0.8.26)
- User directed work to proceed ('برو توکارش') after the proposed controlled review/release. Prepare fast-forward on existing main only, with established 15-minute cPanel theme cron. No new workflow, keys, credentials, or new branch.
- Explicit release scope: theme + bundled Core source, NOT installed Core plugin. Core 0.8.1 active upgrade remains pending; intake/registration/payment gates remain OFF and maintenance ON. Runtime PHP, browser, mail and user-role acceptance tests remain unverified until cPanel release is confirmed.


## 2026-10-23 — NEXT-BATCH-061 (v0.8.27 draft, email verification)
- Added hashed, one-time 24-hour email verification tokens and generic throttled resend on the opt-in registration page. New accounts are marked pending and prevented from authenticating via wp_authenticate_user until verification. Verification state does NOT imply lawyer/professional credentials. No existing users are flagged. The registration feature remains CLOSED by default and while maintenance is on.
- Added registration UI messages and no plaintext token storage; requires working outgoing WordPress mail and future runtime acceptance before opening registration. Change is a standalone draft commit; no main or live deployment.


## 2026-10-23 — NEXT-BATCH-062 (v0.8.27 draft, support conversations)
- Upgraded native private support tickets to staff/customer back-and-forth thread: only owning logged-in author may POST follow-ups (nonce + ownership/type/private checks + cooldown), and only administrators may add staff replies. Every message is add_post_meta append-only, avoiding lost-message races. Maximum size capped, last 30 messages displayed, legacy single staff replies preserved.
- Ticket mail/case intake remain separate; no open public API, no public CPT archive and no cross-account thread display. Site runtime two-account privacy tests still required before release.


## 2026-10-23 — NEXT-BATCH-063 (0.8.27 draft, read-only acceptance)
- Expanded PHP WordPress preflight with checks for signup gate, email verification, ticket handler, key Pages, maintenance state and bundled-vs-active Core version (no sensitive records shown).
- Added synthetic PHP test for pending-user login rejection, hashed/expiring verification tokens, fake outbound mail, and ticket ownership/source controls; no real accounts or email sent. Wired into private read-only batch script.
- Updated 10-item priority checklist with precise code-vs-runtime status. Draft remains off main. Actual PHP interpreter, WordPress runtime, Cron, SMTP and browser tests NOT executed during authoring.


## 2026-10-23 — NEXT-BATCH-064 (v0.8.28 draft, identity and dashboards)
- Added branded-login 6-failure/15-minute transient throttle keyed with HMAC rather than raw identity or IP; native WordPress admin login remains untouched, and successful sign-in clears only the associated limit.
- User list columns now display PVDak email verification and self-declared user type to administrators without granting any legal/professional capability. Email confirmation still does not mean lawyer verification.
- Client dashboard now shows a non-intake notice and dates for only the authenticated user's own requests. The fallback correctly distinguishes login success from the separately deployed Core plugin.
- Professional portal hides sensitive-looking capacity/subscription panels until the member's own application status is approved; even approved pages contain aggregates, not case descriptions.
- Source-only checks pending; no main branch update, server deployment, account-data change or public intake activation.


## 2026-10-23 — NEXT-BATCH-065 (v0.8.28 draft, staff tickets and entitlement)
- Added staff-only ticket admin list columns, clear open/answered/closed statuses, a close/reopen selector, and prevented follow-ups on closed threads with both UI and server-side owner/status checks. Full private thread remains author-only.
- Manual subscription update now rejects incoherent active plans (expired/missing date, zero quota, no tier) and appends an audit record for every actual change; gate still defaults OFF.
- Historical monthly assignment audit iteration is paginated in 100-ID blocks instead of allocating an unbounded list; existing one-case-one-month consumption semantics unchanged.
- The internal manager matching dropdown reuses its per-request load snapshot, while final assignment is revalidated inside the existing advisory DB lock. No automated assignment, notices to lawyers, or public case data exposure.
- Code prepared on draft only. Core installation, PHP syntax and DB concurrency tests still pending.


## 2026-10-23 — NEXT-BATCH-066 (v0.8.28 / Core source 0.8.2, staff-only launch report)
- Added Tools > آمادگی PVDak as a staff-only read-only report: version, maintenance/intake/registration/quota switches, relevant Page publication states, editor links and policy-draft flags. No user identities, protected legal requests, or private case text are read or displayed.
- Preliminary privacy/terms Pages now show a visible editorial notice until the site owner finalizes text; existing content is not overwritten. No invented addresses, official claims or compliance promises.
- Source Core version incremented to 0.8.2 for matching/subscription/admin changes; still not installed by the theme-only Cron, and no runtime or external mail tests have been executed.


## 2026-10-23 — NEXT-BATCH-067 (source regressions)
- Added 10 source-only regression checks for login, email, client/professional dashboards, private support, staff controls, locked matching, subscription audit, provisional Pages and readiness report.
- Updated previous feature/public-page/mock test version expectations to Theme 0.8.28 and Core source 0.8.2; guarded non-string user type metadata on the administrative Users table.
- Linked new test to existing private batch preflight. This is NOT PHP lint, browser or WordPress integration testing and main is unchanged.


## 2026-10-23 — CORE-UPGRADE-ACCEPTANCE-068 (draft)
- User supplied proof of active Core 0.8.2 with rollback backup core-upgrade-hk6xg2a1. Theme 0.8.28 was verified deployed at release SHA 63e836be.
- Prepared read-only WordPress post-upgrade audit for versions, safety gates, private content types, role capabilities, account functions, assignment lock and nine Pages. No real case/user content printed.
- Prepared static test and E2E checklist. Neither WP-CLI audit nor real mail, multi-account or DB concurrency tests were executed by ChatGPT. Draft changes not on main.


## 2026-10-23 — PAGE-BOOTSTRAP-RECOVERY-069 (draft theme 0.8.29)
- Host read-only preflight shows 37 checks, zero failures, seven warnings: expected native Pages for login, recovery, profile, account, contact, privacy and terms are not published. Core 0.8.2 and theme 0.8.28 are active, with maintenance and all intake gates OFF.
- Identified a stale-version-marker failure mode in `inc/pvdak-public-pages.php`: the `_pvdak_public_pages_bootstrap=0.8.26` option short-circuited future reconciliation, even when Pages were missing. Removed the early exit. Every normal authorized admin visit now checks only known slugs; missing Pages are seeded, existing published/draft/trashed Pages are never edited.
- The read-only preflight and private post-Core acceptance report now include all seeded Pages, their published/missing status and bootstrap marker, with instructions for one wp-admin visit. PHP/WordPress runtime verification of this patch and seven Page creations is pending; this draft is NOT published.
- Updated theme version/source regression expectations to 0.8.29. Core remains 0.8.2. No new pipeline, keys, SMTP changes, role changes or public intake activation.


## 2026-10-23 — PAGE-BOOTSTRAP-REGRESSION-070 (draft only)
- Added eight synthetic assertions demonstrating that a stale 0.8.26 completion marker no longer blocks creation of the seven missing Pages.
- Demonstrates that draft and trashed Pages are preserved, the new 0.8.29 marker is written only when complete, and a later missing Page is reconciled even with a current marker.
- Wired the regression into the pre-existing read-only source test script. No production Page content was created, modified or deleted by ChatGPT.


## 2026-10-23 — POST-CORE-PREFLIGHT-071 (37 / 0 / 7)
- Host operator executed original WP-CLI preflight: 37 checks, 0 fails, 7 Page publication warnings; 0.8.28 theme, 0.8.2 Core loaded, registration/member intake/case intake/quota OFF; admin case permissions and advisory lock present.
- Draft v0.8.29 Page reconciliation fix/source smoke added without touching live `main`. Latest pending-approval table now distinguishes confirmed host results from missing browser/email/privacy/concurrency tests.
- Next action on current live release is a real authorized admin dashboard visit for the existing admin_init seeder, then rerun read-only page state check. If the option marker prematurely blocks seeding, draft fix should be reviewed and released separately. No user Pages have been modified by the assistant.


## 2026-10-23 — HOST-PREFLIGHT-CLEARED-072 (verified by operator)
- Host operator confirmed WordPress page ID 3 (`privacy-policy`, originally `draft`) was published manually after review step. This was NOT created or modified by ChatGPT.
- Operator ran the read-only live preflight again: **37 checks, 0 failures, 0 warnings**. Active theme 0.8.28 / installed Core 0.8.2; maintenance ON, WordPress and PVDak registration OFF, both intake switches and subscription gate OFF; restricted roles have no case administration permissions.
- Do not mistake 37/0/0 for end-to-end or legal acceptance: real browser login, password recovery email, verification mail, two-account ticket/request privacy, live locking, manual subscription history, and privacy-policy copy review remain unverified.
- Theme 0.8.29 draft is now optional, not needed to clear current published Page warnings. Keep main untouched pending explicit user approval for future feature release.


## 2026-10-23 — NAV-SMOKE-VERSION-FIX-073 (unreleased draft; no runtime execution yet)
- Operator ran the source-only batch on production main 63e836be: early source/mocked checks passed, but navigation-smoke stopped on an obsolete exact string assertion `Version: 0.8.12` despite active theme 0.8.28.
- Source inspection confirms the normal theme `pvdak-style` uses `get_stylesheet_uri()`, bundled mega-navigation CSS remains in `style.css`, and there is no separate navigation stylesheet enqueue.
- Fixed navigation smoke to read the actual CSS header and require minimum theme 0.8.12 via semantic `version_compare`, allowing 0.8.28+; added lightweight regression and wired into existing script.
- Operator also confirmed WP-CLI live `PREFLIGHT_SUMMARY: checks=37 fails=0 warnings=0` after publishing their existing privacy-policy Page. No theme/Core deployed, website state changed, or real mail/database/browser test run by assistant.


## 2026-10-23 — HOMEPAGE-ICON-SMOKE-074 (draft only)
- Operator reran source tests with NAV's obsolete 0.8.12 literal patched only in an ephemeral archive. NAV 13 checks passed, then the next layout test failed at an obsolete exact string `stroke="#134a51"`.
- Verified all four inline process stage icons are present in `front-page.php` and the step's icon SCSS selector exists in `style.css`. Stage four's badge intentionally uses `fill="#134a51"` with inherited `stroke="currentColor"`.
- Updated only the source regression to accept either valid fill or stroke accent, while still demanding both stage-specific paths and scoped CSS. No homepage graphic, live theme or Core plugin change; next full PHP source-suite execution remains necessary.


## 2026-10-23 — TWO-ACCOUNT-FIXTURE-075 (draft only)
- Operator confirmed initial source suite and all subsequent layout/editor/Core policy checks passed (12,15,10,13,41,27,40 respectively); no final `PVDAK_TWO_ACCOUNT_PRIVACY_OK` nor `PVDAK_BATCH_PREFLIGHT_OK` appeared.
- Source analysis found the synthetic privacy test had not registered `esc_attr()` or `get_the_date()` WordPress stand-ins even though Core 0.8.28-era dashboards now render request dates. This can cause PHP fatal at the final mocked two-account stage. The exact exit/fatal stderr is not yet supplied.
- Added deterministic no-data fixtures for both function calls, one mock date assertion, and corrected the synthetic assertion-count expectation (24 including final count guard). Core permission logic is not loosened.
- Draft change only; no production site, plugins, cases or database modified. Isolated test on host remains required; don't claim passing until `PVDAK_BATCH_PREFLIGHT_OK` prints.


## 2026-10-23 — SYNTHETIC-PRIVACY-PASS-076 (host output; draft log only)
- Operator completed isolated two-account synthetic privacy regression on an ephemeral copy of deployed main with deterministic WordPress stubs for `esc_attr` and `get_the_date`: `PVDAK_TWO_ACCOUNT_PRIVACY_OK: checks=23 expected=23` and `PRIVACY_TEST_COMPLETED`. Test exited successfully.
- Earlier individual suites also reported 12 initial static, 15 mock email/token, 10 next-phase, 13 navigation, 41 homepage, 27 editor bootstrap, 40 Core policy checks. The first two stale source assertions (0.8.12 CSS and stage icon fill) were patched only inside the ephemeral test copy.
- The consolidated test runner has **not** printed `PVDAK_BATCH_PREFLIGHT_OK`; only individual components have proved passing. Do not describe this as a real two-account browser/privacy acceptance.
- Previously verified live WordPress preflight remained 37 checks, 0 fails, 0 warnings (theme 0.8.28, Core 0.8.2); public maintenance remains enabled; registrations, both intakes and subscription enforcement remain OFF.
- Next: anonymous HTTP routing/cache/REST check from host, then controlled E2E with two synthetic logins and ticket ownership on a separate staging WP install. Keep all real client/legal data private. No new workflows, secrets or deployments.


## 2026-10-23 — ANONYMOUS-HTTP-TRIAGE-077 (read-only operator observation)
- The live anonymous `curl` check from cPanel returned `/` HTTP/2 200 with `cache-control: no-cache, must-revalidate, max-age=0, no-store, private` and `x-robots-tag: noindex, nofollow, noarchive`. Maintenance placeholder is functioning on homepage.
- `/account-login/`, `/my-pvdak-account/`, and `/wp-json/` each returned HTTP/2 404. The public Pages are published according to earlier 37/0/0 WP-CLI preflight; these 404s should NOT be called passing accessibility or REST restrictions.
- Theme template redirects through `pvdak-maintenance.php` at `template_redirect`, and REST maintenance guard returns 503 on anonymous WP REST access; the observed common 404 may indicate that friendly-path requests never reach WordPress (permalink/rewrite/.htaccess/vhost route), but this is UNCONFIRMED. Do not guess root cause or run rewrite flush/htaccess replacement blindly.
- Next safe diagnosis: inspect WP-CLI `permalink_structure`, real `get_permalink()` values, presence of rewrite directives, then compare request status of `/?pagename=account-login` and `/?rest_route=/` with pretty paths. Avoid logging cookies, user details or private content.
- No server config or WP settings changed; public maintenance and all intake/registration/quota gates remain OFF.


## 2026-10-23 — PERMALINK-ROOT-CAUSE-078 (operator-confirmed; no server change by assistant)
- Operator's read-only cPanel diagnosis: WordPress `permalink_structure` option is empty (Plain). Live, published Pages are `account-login` ID 67 and `my-pvdak-account` ID 68; `get_permalink()` returns `https://pvdak.com/?page_id=67` and `...?page_id=68` respectively.
- WordPress-managed `.htaccess` block has BEGIN/END markers but no RewriteEngine/RewriteRule lines, which is consistent with Plain URLs. Anonymous pretty paths returned 404, while query-string routes `/?pagename=account-login` and `/?pagename=my-pvdak-account` returned HTTP 200. `/?rest_route=/` returned HTTP 503, demonstrating the live REST maintenance gate actually executes through WordPress.
- Root cause for current pretty-URL 404 is WordPress's Plain permalink configuration, not a proven theme/Core defect. Safe remediation should back up existing `.htaccess` and previous permalink setting, change single-site structure to `/%postname%/`, generate Apache/LiteSpeed rewrite rules and verify friendly routes plus maintenance/noindex before calling fixed.
- WP-CLI's `--hard` flush may require `apache_modules: [mod_rewrite]` in its CLI config to update .htaccess; do not assume command success proves web routing. Do not disable maintenance, intake, signup, subscription gates, rewrite webserver config broadly or modify unrelated directives.
- Code and production unchanged by assistant; next operator action is guarded permalink repair with backup and read-only verification.


## 2026-10-23 — TICKET-EXPERIENCE-081 (draft only)
- 20 account/ticket improvements implemented on feature branch; full list in `docs/WORK_BATCH_081.md`.
- Private per-author ticket pagination, native disclosure cards, status/date/history, safe notices, Unicode length checks, privacy hints, counters, admin-only status filtering and responsive scoped styling.
- Added 20 source-only assertions to existing read-only test runner; JavaScript syntax checked in generation context. PHP lint, WP integration and browser tests are pending; no claim of runtime passing.
- Main/live theme, Core, maintenance, intake, registration, database, server settings and deployment unchanged.


## 2026-10-23 — TICKET-DISCOVERY-082 (feature branch only)
- Improved owner-scoped ticket discovery with status, search, oldest/newest sort, retained filters during pagination, reset link, context-aware empty state and page result count.
- Added authorized ticket deep links, preserving current filter/page/search after a follow-up; redirects are built only from allowlisted parameters, not supplied URLs.
- Staff composer now starts empty to prevent accidental duplicate replies; staff can inspect last 30 conversation messages and a private status-change audit history. A new staff reply marks a waiting ticket answered unless closed.
- Added ticket reply privacy reminder, dynamic character limits, near-limit warning, accessible focus on submission result, responsive filter controls and 21 new static regression assertions.
- Source-only modifications. PHP lint, read-only smoke suite, real WordPress two-account behavior and browser checks are pending. No changes to main, live site, Core, registrations, intakes, maintenance, cron, database or server.
- Follow-up corrections: authorized focused ticket display across filters, user follow-up status audit, and same-request admin save idempotency. These are still source-only changes.


## 2026-10-24 — PRIVATE-REQUEST-HISTORY-083 (feature branch only)
- Rebuilt `pvdak_my_requests` into owner-scoped, private request history with status/type/search/order filters, safe 10-item pagination, Plain permalink GET routing, result counts, reset and out-of-range recovery.
- Cards show only escaped title, reference ID, state, type, date and published topic title. No case descriptions, internal notes, or other owners' requests are rendered; no intake switch changed.
- Added responsive RTL CSS and 30 source-only regression checks to existing read-only runner. PHP lint, actual WordPress/two-account, permalink, and mobile checks still **pending**.
- Core plugin source modified on feature branch only; a future Core release requires separate controlled deployment and acceptance. Main/live, maintenance, registration, intake, data, server and cron unchanged.


## 2026-10-24 — PROFESSIONAL-PORTAL-084 (feature branch only)
- Implemented 20 applicant-only professional dashboard UX, accessibility, state and capacity/subscription clarity improvements. See `docs/WORK_BATCH_084.md`.
- Rechecks application owner/type/private-or-draft before rendering; no client identity, case details, assignment IDs, verification claims or billing functionality exposed.
- Added 30 static source checks to the existing read-only batch runner; PHP lint, actual WordPress/two-account, browser and capacity/quota tests still pending.
- No changes to main, live site, maintenance, signup, intake, stored records, server, cron or payment settings.


## 2026-10-24 — PROFESSIONAL-ADMIN-085 (feature branch only)
- Added 20 read-only staff queue enhancements for private professional applications; details in `docs/WORK_BATCH_085.md`.
- Added scoped admin filters (membership, state, review method, expiry windows, city), protected existing query clauses, reset/accessibility, four review columns, expiry sorting and private bounded audit metabox.
- Added 26 source-only assertions to the existing batch preflight runner. Local PHP lint of the new source and smoke test passed; full checkout runner, actual WP permissions, browser and two-account tests remain pending.
- No production deployment, main update, live settings, intake, maintenance, subscription, private records, server or cron changes.


## 2026-10-24 — REQUEST-ADMIN-086 (feature branch only)
- Implemented 20 read-only request queue discovery and metadata-summary improvements. See `docs/WORK_BATCH_086.md`.
- Added scoped filters for exact tracking/owner/topic/age/notes/audit, newest/oldest sorting, reset and accessible labels; list shows safe reference, topic, age, activity-presence and last-modified columns.
- Preserved existing WordPress and triage filters; no case description, internal note or audit body shown; no data mutations or activation of intake.
- Added 26 static source assertions to existing batch runner. PHP lint and actual WordPress/admin permissions/combined filters/browser tests remain pending.
- No change to main, live site, maintenance, intake, user data, server, cron, staging or secrets.

## 2026-10-24 — REQUEST-TRIAGE-087 (feature branch only)
- Implemented 20 read-only management queue improvements (status/type totals, legacy metadata, pending/7-day/30-day backlog, oldest pending date, direct queue links, allowlisted filters, privacy-safe dashboard and responsive/accessibility controls). See `docs/WORK_BATCH_087.md`.
- Admin aggregate query only counts private requests, requires request-management capability and does not read or print private case bodies or internal notes. Dashboard links use native WordPress admin filters and preserve existing query clauses.
- Added synthetic non-mutating regression `tests/pvdak-core/triage-overview-smoke.php` and wired it into `scripts/check-batch-readonly.sh`.
- A source delimiter-balance heuristic passed for both new/modified PHP files. **PHP lint, synthetic regression execution, real WordPress/multiuser, combined filters, SQL performance and browser/mobile acceptance remain unverified.** Do not claim runtime success.
- Main/live, maintenance, registration, request/professional intake, assignments, user records, DB, server, Cron, staging and secrets unchanged. Core source requires separate controlled deployment when explicitly requested.

## 2026-10-24 — REQUEST-TRIAGE-088 (feature branch only)
- Completed 20 read-only triage improvements: canonical first postmeta values to avoid duplicate-row overcounts, explicit private links, exact site-local age boundaries, 1/3/14/60-day queues, future-date anomaly, oldest-first navigation, safe SQL failure notice, backlog ratio and responsive metadata-only UI. See `docs/WORK_BATCH_088.md`.
- Updated the existing synthetic triage test with precise cutoffs, additional queues, query composition, canonical SQL checks and a simulated database failure; existing read-only runner remains the single test entry point.
- GitHub read-back confirmed modified files and static delimiter balance; code guard assertions passed. **PHP lint, actual PHP synthetic execution, WordPress integration, two-account permissions, duplicate-meta database behavior, SQL performance and browser/mobile testing remain unverified.**
- Source changes were committed only to `feature/pvdak-account-experience-080`. No production deployment, main update, intake/registration/maintenance change, data mutation, staging, secrets or cron changes.

## 2026-10-24 — WORK-BATCH-089 (development branch only)
- Added 20 read-only admin request queue enhancements: assignment presence, inconsistent state detection, modified-age filters, private scope, status preservation, metadata-only columns, future-date warnings and guarded assignment links.
- New synthetic operational queue test added to existing readonly runner; source checks extended. GitHub source changes committed only; PHP lint and real WordPress tests remain pending.
- No production deployment, main branch, intake, maintenance, roles, database or stored records changed. See `docs/WORK_BATCH_089.md`.


## 2026-10-24 — WORK-BATCH-090 (development branch only)
- Repaired malformed PHP source regression `tests/pvdak-core/request-admin-smoke.php` from batch 089; added focused date/filter/permission regression assertions.
- Implemented 20 operational fixes and UX improvements (see `docs/WORK_BATCH_090.md`): exact local age windows, future-date diagnostics, private scope, native status preservation, safe author intersection, deduplicated columns, guarded links and metadata anomaly warnings.
- Added read-only bounded eight-item oldest-pending triage table: ID-only WP_Query, per-record capability/type/privacy/state/time checks, no request text, owner, notes or audit content.
- PHP lint, full synthetic suite and real WordPress/browser/performance tests remain pending; no runtime claim. No production/main, maintenance, intake, registration, data, server or cron changes.
