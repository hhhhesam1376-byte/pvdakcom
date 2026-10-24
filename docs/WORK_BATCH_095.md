# PVDak — batch 095 — unified account workspace (2026-10-24)

## Delivered in existing theme
- One canonical private account URL: `my-pvdak-account`, rendered by the existing `page-templates/pvdak-account.php`.
- One premium header, one sidebar and one responsive dashboard shell. Same sidebar tabs: dashboard, editable profile, private legal requests (only when shortcode is registered), professional membership panel (only for eligible users), support tickets (only when shortcode is registered) and account security.
- Existing display-name profile editor copied into the single dashboard intact: same `pvdak_profile_save` action, `_pvdak_profile_nonce`, validation, 120-character limit, reset, counter, server feedback and immutable account identity.
- Old `account-profile` page template performs a safe 302 to the dashboard `#pvdak-profile` panel, carrying only recognized public feedback status values. It no longer renders a competing dashboard.
- `pvdak_profile_save` redirects to the canonical dashboard and retains the status and fragment.
- Existing `assets/js/pvdak-account.js` now highlights and shows the selected hash panel, works with nested support-ticket anchors and preserves all sections as plain HTML when JavaScript is off. Invalid URL hash does not break navigation.
- Existing WordPress/Core private request/support/professional shortcodes remain unmodified; no change to access rights, database, password recovery, registration or legal intake.
- Header account-specific message button and footer search now target the actual unified dashboard, not the legacy page.
- Style release: theme **0.8.34**, Core remains **0.8.3**; updates only to existing files, no duplicate deployment paths, new Cron, Actions, secrets, plugin or remote JS.
- Existing source tests updated to assert new canonical form/URL and backwards compatibility.

## Validation status
- GitHub source checks are expected to validate markup, role gates, section anchors, preserving nonce/save hooks, and non-duplication.
- Production WordPress rendering, PHP lint, end-to-end multiuser privacy, mobile viewport checks and host deployment are **not verified here**. The existing private host deploy runner performs lint / backups before making theme live.
- Keep maintenance ON and registration/intakes OFF. Preserve the original Core installed copy.
