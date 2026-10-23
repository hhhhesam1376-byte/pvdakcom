# PVDak cPanel deployment

Deployment is being installed; successful host execution and cron scheduling must be confirmed from host output.

- Site: https://pvdak.com
- Account home: /home/amirhoseainsabet
- WordPress: /home/amirhoseainsabet/public_html
- Active theme: wp-content/themes/pvdakcom-main
- Repository checkout: /home/amirhoseainsabet/repositories/pvdakcom
- Production branch: main
- Private runner: /home/amirhoseainsabet/.pvdak-deploy/deploy.sh
- Existing cron: every fifteen minutes, with output appended to the private deploy.log.

The checkout is old and contains manual files. Do NOT reset, clean, or checkout over those files. The runner fetches into refs/remotes/pvdak-deploy/main and exports an immutable commit with git archive without altering the checkout.

The runner checks site identity, active theme, classic theme files and PHP syntax, backs up the installed theme, synchronizes only that theme, and checks HTTP 200 before and after publication. A failed publication restores the backup and records a failed commit. HTTP 200 is a basic availability check, not full functional testing. Theme sync is not a fully atomic release switch. PHP CLI is /usr/local/bin/php; ensure its version remains compatible with the site's web PHP.

Private state: deployed, failed, last-check, lock and backups/. Backups are retained; monitor disk use and remove old backups deliberately. deploy.log records DEPLOY_OK, UP_TO_DATE and errors. Remove the failed marker only after investigating a failure, or push a corrected commit.

Runner changes are not automatically executed from the repository: explicitly reinstall the reviewed script in the private runner path when updating deployment infrastructure. Normal theme commits deploy automatically once cron is installed. Do not add secrets to the repository. scripts/, docs/, .github/ and root Git/deployment metadata are excluded from theme publication.

No GitHub Actions minutes, new SSH keys, or WordPress API passwords are required for this public repository.

## Theme-bundled Swiper and file permissions (2026-10-23)
- Official Swiper 14.3.0 JS/CSS/LICENSE are version-controlled in `assets/vendor/swiper-14.3.0/` and copied with every normal theme release. No frontend CDN or one-time uploads installation needed. User-uploaded media remains in ordinary WordPress uploads.
- The private runner keeps `$HOME/.pvdak-deploy/deploy.sh` and the existing 15-minute cron. Install an updated reviewed script explicitly; pushing `scripts/deploy-cpanel.sh` does not overwrite the private runner.
- Previous `umask 077` combined with `rsync -rlt` made new theme files potentially unreadable (HTTP 403). The updated runner uses `rsync -rltp --chmod=D755,F644` to set directories 0755 and files 0644, even when contents match. Backup restores have safe explicit chmod as well.
- Verify browser HTTP 200 for `rtl.css`, `assets/js/pvdak-home.js`, and bundled Swiper JS/CSS, not just the homepage. Never disable security controls to mask unexplained 403 errors.
