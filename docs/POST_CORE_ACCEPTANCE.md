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


## Operator updates 2026-10-23

- Live preflight passed: 37 checks, 0 failures, 0 warnings.
- The two-account synthetic privacy smoke passed independently: `PVDAK_TWO_ACCOUNT_PRIVACY_OK: checks=23 expected=23` followed by `PRIVACY_TEST_COMPLETED`.
- The main source runner was blocked first by two outdated test expectations and then ended without the consolidated success sentinel; each subsequent individual component was inspected/tested separately. Avoid implying the entire unmodified runner succeeded.
- Before opening site: review Privacy Policy page 3 text; keep maintenance/registration/intakes/quota restrictions.
- Next safe host action: anonymous HTTP headers for home and account URL, plus REST anonymous result. Do not log session cookies, user names, private ticket content or legal case metadata.
