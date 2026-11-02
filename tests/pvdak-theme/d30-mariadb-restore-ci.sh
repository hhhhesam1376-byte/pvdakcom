#!/usr/bin/env bash
# D30: test the real restore drill against a disposable WordPress SQL dump.
# Run only inside GitHub Actions after the isolated pvdak_ci WP/MariaDB bootstrap.
set -Eeuo pipefail
umask 077
[[ "${GITHUB_ACTIONS:-}" == "true" && "${PVDAK_CI_D30_ISOLATED:-}" == "1" &&
   -n "${RUNNER_TEMP:-}" && -n "${GITHUB_WORKSPACE:-}" ]] || {
  echo 'D30_CI_ABORT: disposable GitHub Actions fixture required' >&2
  exit 2
}
[[ "$(id -u)" -ne 0 ]] || { echo 'D30_CI_ABORT: root not permitted' >&2; exit 2; }
for cmd in mariadb-dump mariadbd mariadb-install-db mariadb; do
  command -v "$cmd" >/dev/null || { echo "D30_CI_ABORT: missing $cmd" >&2; exit 2; }
done
WP_ROOT="$RUNNER_TEMP/pvdak-wp"
[[ -f "$WP_ROOT/wp-config.php" && -d "$WP_ROOT/wp-content/themes/pvdakcom-main" &&
   -d "$WP_ROOT/wp-content/plugins/pvdak-core" ]] || {
  echo 'D30_CI_ABORT: isolated WordPress fixture missing' >&2; exit 2;
}
FIXTURE="$(mktemp -d "$RUNNER_TEMP/pvdak-d30-ci.XXXXXXXX")"
trap 'rm -rf -- "$FIXTURE"' EXIT
BACKUP="$FIXTURE/backups"
mkdir -p "$BACKUP/theme" "$BACKUP/core"
cp -a -- "$WP_ROOT/wp-content/themes/pvdakcom-main/." "$BACKUP/theme/"
cp -a -- "$WP_ROOT/wp-content/plugins/pvdak-core/." "$BACKUP/core/"
export PVDAK_D30_BACKUP_ROOT="$BACKUP"
export PVDAK_WP_ROOT="$WP_ROOT"
# This DB is created by the same workflow with disposable users/credentials;
# never use wp-config credentials or access any external database.
MYSQL_PWD=pvdak_ci_local_only mariadb-dump \
  --host=127.0.0.1 --port=3306 --user=pvdak_ci \
  --single-transaction --skip-comments --skip-dump-date pvdak_ci > "$BACKUP/site.sql"
[[ -s "$BACKUP/site.sql" ]] || { echo 'D30_CI_FAIL: empty disposable SQL dump' >&2; exit 1; }
bash "$GITHUB_WORKSPACE/scripts/pvdak-restore-drill.sh" \
  --theme "$BACKUP/theme" --core "$BACKUP/core" --files-only \
  > "$FIXTURE/files.log" 2>&1
grep -F 'D30_FILES_RESTORE_OK' "$FIXTURE/files.log" >/dev/null
grep -F 'D30_SQL_RESTORE_NOT_TESTED' "$FIXTURE/files.log" >/dev/null
echo 'PVDAK_D30_CI_FILES_ONLY_OK: isolated theme and Core restore'
bash "$GITHUB_WORKSPACE/scripts/pvdak-restore-drill.sh" \
  --theme "$BACKUP/theme" --core "$BACKUP/core" --sql "$BACKUP/site.sql" --ci-wp-boot \
  > "$FIXTURE/full.log" 2>&1 || {
    cat "$FIXTURE/full.log" >&2
    exit 1
  }
grep -F 'D30_FILES_RESTORE_OK' "$FIXTURE/full.log" >/dev/null
grep -F 'D30_SQL_RESTORE_OK' "$FIXTURE/full.log" >/dev/null
grep -F 'D30_CI_WP_BOOT_OK' "$FIXTURE/full.log" >/dev/null
grep -F 'D30_CI_WP_CONTENT_OK' "$FIXTURE/full.log" >/dev/null
grep -F 'D30_CI_ROLLBACK_OK' "$FIXTURE/full.log" >/dev/null
echo 'PVDAK_D30_CI_ROLLBACK_OK: disposable release mutation rolled back to original SQL snapshot'
echo 'PVDAK_D30_CI_SQL_RESTORE_OK: disposable WordPress SQL imported into fresh socket-only MariaDB'
echo 'PVDAK_D30_CI_WP_BOOT_OK: restored WordPress and theme/Core loaded against restored socket-only DB'
# A syntactically valid but unrelated SQL dump must NOT count as WordPress recovery.
printf 'CREATE TABLE unrelated_fixture (id INT);\n' > "$BACKUP/unrelated.sql"
if bash "$GITHUB_WORKSPACE/scripts/pvdak-restore-drill.sh" \
  --theme "$BACKUP/theme" --core "$BACKUP/core" --sql "$BACKUP/unrelated.sql" \
  > "$FIXTURE/negative.log" 2>&1; then
  echo 'D30_CI_FAIL: unrelated SQL was accepted' >&2; exit 1
fi
grep -F 'D30_SQL_RESTORE_FAILED: incomplete WordPress table family' \
  "$FIXTURE/negative.log" >/dev/null || {
    cat "$FIXTURE/negative.log" >&2
    echo 'D30_CI_FAIL: unrelated SQL rejected for unexpected reason' >&2
    exit 1
  }
echo 'PVDAK_D30_CI_NEGATIVE_OK: unrelated SQL rejected by real MariaDB schema guard'
# Keep the WordPress table family intact but remove its required URL options
# from a COPY of the disposable dump; this must be rejected after SQL import.
cp -- "$BACKUP/site.sql" "$BACKUP/missing-site-options.sql"
printf "\\nDELETE FROM \`wp_options\` WHERE option_name IN ('siteurl','home');\\n" >> "$BACKUP/missing-site-options.sql"
if bash "$GITHUB_WORKSPACE/scripts/pvdak-restore-drill.sh" \
  --theme "$BACKUP/theme" --core "$BACKUP/core" --sql "$BACKUP/missing-site-options.sql" \
  > "$FIXTURE/missing-options.log" 2>&1; then
  echo 'D30_CI_FAIL: dump missing site options was accepted' >&2; exit 1
fi
grep -F 'D30_SQL_RESTORE_FAILED: missing required WordPress site options' \
  "$FIXTURE/missing-options.log" >/dev/null || {
    cat "$FIXTURE/missing-options.log" >&2
    echo 'D30_CI_FAIL: missing options rejected for unexpected reason' >&2
    exit 1
  }
echo 'PVDAK_D30_CI_OPTIONS_NEGATIVE_OK: missing siteurl/home rejected after MariaDB import'
# A partial dump may retain users/posts/options while losing taxonomy data.
# Reject it after a real import into the disposable socket-only MariaDB.
cp -- "$BACKUP/site.sql" "$BACKUP/missing-taxonomy.sql"
printf '\nDROP TABLE IF EXISTS `wp_term_relationships`;\n' >> "$BACKUP/missing-taxonomy.sql"
if bash "$GITHUB_WORKSPACE/scripts/pvdak-restore-drill.sh" \
  --theme "$BACKUP/theme" --core "$BACKUP/core" --sql "$BACKUP/missing-taxonomy.sql" \
  > "$FIXTURE/missing-taxonomy.log" 2>&1; then
  echo 'D30_CI_FAIL: dump missing WordPress taxonomy table was accepted' >&2; exit 1
fi
grep -F 'D30_SQL_RESTORE_FAILED: incomplete WordPress table family' \
  "$FIXTURE/missing-taxonomy.log" >/dev/null || {
    cat "$FIXTURE/missing-taxonomy.log" >&2
    echo 'D30_CI_FAIL: partial WordPress dump rejected for unexpected reason' >&2
    exit 1
  }
echo 'PVDAK_D30_CI_SCHEMA_NEGATIVE_OK: missing taxonomy table rejected after MariaDB import'
echo 'PVDAK_D30_CI_OK: 7 isolated checks; real private backup and live host rollback NOT_TESTED'
