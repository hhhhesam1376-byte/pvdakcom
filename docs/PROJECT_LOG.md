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

