# PVDak 0.8.30 / Core 0.8.3 — controlled publication

STATUS: release candidate in feature branch; NOT deployed. No new cron, Action, secret, server or database setup.

## Step 1. Read-only source verification, run as amirhoseainsabet on existing cPanel SSH

~~~bash
(
  set -Eeuo pipefail
  R="$HOME/repositories/pvdakcom"
  D="$(mktemp -d "$HOME/.pvdak-deploy/pvdak-release-check.XXXXXXXX")"
  trap 'rm -rf -- "$D"' EXIT
  git -C "$R" fetch --quiet --no-tags https://github.com/hhhhesam1376-byte/pvdakcom.git refs/heads/feature/pvdak-account-experience-080
  git -C "$R" archive FETCH_HEAD | tar -xf - -C "$D"
  bash -n "$D/scripts/pvdak-core-upgrade-guarded.sh"
  bash "$D/scripts/check-batch-readonly.sh" "$D"
)
~~~

Require exit 0 and the final sentinel PVDAK_BATCH_PREFLIGHT_OK. If any test fails, DO NOT merge to main or publish; fix the feature branch. This test does not modify the live WordPress site and is not a real browser/multi-account acceptance.

## Step 2. Promote tested branch to main and publish theme through existing runner

After Step 1 has passed and the tested SHA is unchanged, merge to main. The existing 15-minute cPanel cron pulls main; optionally trigger it immediately:

~~~bash
bash "$HOME/.pvdak-deploy/deploy.sh"
~~~

Require DEPLOY_OK or matching UP_TO_DATE, and version 0.8.30. The existing runner validates and backs up theme files; it deliberately does NOT update the installed Core.

## Step 3. Controlled upgrade of already-active Core 0.8.2 to 0.8.3

Run only after theme publication and the updated main SHA have been confirmed. Extract private scripts from the existing repository, not the publicly deployed theme.

~~~bash
(
  set -Eeuo pipefail
  R="$HOME/repositories/pvdakcom"
  D="$(mktemp -d "$HOME/.pvdak-deploy/pvdak-core-release.XXXXXXXX")"
  trap 'rm -rf -- "$D"' EXIT
  git -C "$R" archive refs/remotes/pvdak-deploy/main scripts | tar -xf - -C "$D"
  bash -n "$D/scripts/pvdak-core-upgrade-guarded.sh"
  bash "$D/scripts/pvdak-core-upgrade-guarded.sh" --check
  bash "$D/scripts/pvdak-core-upgrade-guarded.sh" --upgrade
)
~~~

The guarded updater uses the existing deployment lock, verifies site/maintenance/disabled registration and intakes, lint and policy smoke, backs up database and old plugin privately, installs source files and runs active WP-CLI acceptance. On failure, it attempts plugin file rollback; database is never automatically restored.

## Step 4. Verify real installed versions

~~~bash
wp theme get pvdakcom-main --field=version --path="$HOME/public_html"
wp plugin get pvdak-core --field=version --path="$HOME/public_html"
tail -n 5 "$HOME/.pvdak-deploy/deploy.log"
~~~

Expect 0.8.30 / 0.8.3 and a matching deployed main SHA. Keep maintenance enabled and registration, both intake gates and subscription gate disabled. Actual WordPress permission behavior, real two-account browser checks, PHP logs, email and SQL performance are separate tests. The earlier Plain permalink friendly-URL 404 requires a separate, backed-up configuration fix; do not silently alter rewrite rules during this release.
