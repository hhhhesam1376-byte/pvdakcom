# PVDak post-install acceptance — 2026-10-23

Reported by cPanel owner:
- Theme 0.8.28 deployed, `DEPLOY_OK 63e836be6686717e5dc2942018fb7a2fb851dfac`.
- Core 0.8.2 active, `CORE_UPGRADE_OK 0.8.2`.
- Backup: `$HOME/.pvdak-deploy/backups/core-upgrade-hk6xg2a1`.

This proves installation and PHP syntax checks, NOT full integration/security acceptance.

## Run next
`scripts/pvdak-post-core-audit.php` with `wp eval-file` from a private checkout; it performs only configuration/database reads, never writes users or requests.

Separately test, with synthetic accounts only:
1. Branded login and logout, failed-attempt throttling.
2. Email recovery and 24-hour verification including expiration.
3. Two-client separation of requests, support tickets and replies.
4. Unapproved professional must not access case data or capacity UI.
5. Manager-only referral lock with two concurrent staging database connections.
6. Historical subscription quota and expiry using test cases only (gate OFF).
7. Admin first-visit page bootstrap, contact/FAQ/terms drafts.
8. Mobile, accessibility, HTTP/cache headers and server logs.

Maintain maintenance ON, registration/intakes OFF, quota gate OFF and payment disabled. No new workflow, secrets or Cron.
