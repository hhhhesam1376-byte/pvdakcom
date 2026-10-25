#!/usr/bin/env bash
# PVDak Core source installer — default dry-run; never activates a plugin.
# Use --install for inactive Core, or --upgrade for guarded replacement of active Core.
set -euo pipefail
# Existing installer also handles guarded upgrades; no separate deploy workflow.
if [[ "${1:-}" == "--upgrade" ]]; then
set -Eeuo pipefail
umask 077
MODE="${1:-}"
if [[ "$MODE" != "--upgrade" ]]; then
  echo "USAGE: bash $0 --upgrade" >&2; exit 2
fi
[[ "$(id -un)" == "amirhoseainsabet" ]] || { echo "STOP: wrong cPanel user" >&2; exit 1; }
for tool in wp php flock cp mv diff find mktemp; do
  command -v "$tool" >/dev/null || { echo "STOP: missing $tool" >&2; exit 1; }
done
WP="$HOME/public_html"
STATE="$HOME/.pvdak-deploy"
THEME="$WP/wp-content/themes/pvdakcom-main"
SOURCE="$THEME/packages/pvdak-core"
DEST="$WP/wp-content/plugins/pvdak-core"
PRIVATE="$(cd -- "$(dirname -- "$0")" && pwd)"
ACCEPT="$PRIVATE/pvdak-core-acceptance.php"
[[ -d "$STATE/backups" && ! -L "$STATE" && ! -L "$STATE/backups" ]] || { echo "STOP: unsafe state folder" >&2; exit 1; }
exec 9>"$STATE/lock"
flock -w 120 9 || { echo "STOP: deployment lock busy" >&2; exit 1; }
[[ -f "$WP/wp-load.php" && -f "$SOURCE/pvdak-core.php" && -f "$DEST/pvdak-core.php" && -f "$ACCEPT" ]] || {
  echo "STOP: WordPress, plugin or private audit is missing" >&2; exit 1;
}
[[ ! -L "$SOURCE" && ! -L "$DEST" && -z "$(find "$SOURCE" -type l -print -quit)" ]] || {
  echo "STOP: symlinked plugin source/destination is not allowed" >&2; exit 1;
}
SITE="$(wp option get siteurl --path="$WP" --skip-plugins --skip-themes)"
ACTIVE_THEME="$(wp option get stylesheet --path="$WP" --skip-plugins --skip-themes)"
[[ "$SITE" == "https://pvdak.com" || "$SITE" == "https://pvdak.com/" ]] || { echo "STOP: wrong site" >&2; exit 1; }
[[ "$ACTIVE_THEME" == "pvdakcom-main" ]] || { echo "STOP: wrong active theme" >&2; exit 1; }
wp plugin is-active pvdak-core --path="$WP" --skip-plugins --skip-themes || { echo "STOP: Core inactive" >&2; exit 1; }
wp eval '
  if (!function_exists("pvdak_maintenance_enabled") || !pvdak_maintenance_enabled()) WP_CLI::error("Maintenance OFF");
  if (get_option("users_can_register") || !function_exists("pvdak_registration_open") || pvdak_registration_open()) WP_CLI::error("Registration ON");
  if (!function_exists("pvdak_core_intake_open") || pvdak_core_intake_open()) WP_CLI::error("Legal intake ON");
  if (!function_exists("pvdak_core_member_intake_open") || pvdak_core_member_intake_open()) WP_CLI::error("Professional intake ON");
  if (!function_exists("pvdak_core_subscription_gate_on") || pvdak_core_subscription_gate_on()) WP_CLI::error("Subscription gate ON");
  WP_CLI::line("PVDAK_GATES_CLOSED");
' --path="$WP"
NEW="$(sed -n 's/^ \* Version: //p' "$SOURCE/pvdak-core.php" | head -1)"
OLD="$(sed -n 's/^ \* Version: //p' "$DEST/pvdak-core.php" | head -1)"
[[ "$NEW" == "0.8.14" && -n "$OLD" ]] || { echo "STOP: unexpected Core versions" >&2; exit 1; }
if diff -qr "$SOURCE" "$DEST" >/dev/null; then
  echo "CORE_ALREADY_MATCHES_BUNDLE version=$NEW"; exit 0
fi
php -r 'exit(version_compare($argv[1],$argv[2],">")?0:1);' "$NEW" "$OLD" || {
  echo "STOP: bundled Core must be newer than installed Core" >&2; exit 1;
}
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || { echo "STOP: invalid PHP: $f" >&2; exit 1; }
done < <(find "$SOURCE" -type f -name '*.php' -print0)
php -l "$ACCEPT" >/dev/null
[[ -f "$PRIVATE/../tests/pvdak-core/policy-smoke.php" ]] || { echo "STOP: policy test missing" >&2; exit 1; }
PVDAK_CORE_SOURCE="$SOURCE/pvdak-core.php" php "$PRIVATE/../tests/pvdak-core/policy-smoke.php"
echo "CORE_UPGRADE_PREFLIGHT_OK installed=$OLD new=$NEW"
[[ "$MODE" == "--upgrade" ]] || exit 0
DB="$STATE/backups/pre-core-$(date -u +%Y%m%dT%H%M%SZ)-${BASHPID}.sql"
wp db export "$DB" --path="$WP" --quiet
[[ -s "$DB" ]] || { echo "STOP: DB backup failed" >&2; exit 1; }
chmod 600 "$DB"
STAGE="$(mktemp -d "$WP/wp-content/plugins/.pvdak-new.XXXXXXXX")"
cp -a "$SOURCE/." "$STAGE/"
find "$STAGE" -type d -exec chmod 755 {} +
find "$STAGE" -type f -exec chmod 644 {} +
diff -qr "$SOURCE" "$STAGE" >/dev/null || { echo "STOP: staged copy mismatch" >&2; rm -rf "$STAGE"; exit 1; }
BACKUP="$(mktemp -d "$STATE/backups/old-core.XXXXXXXX")"
rmdir "$BACKUP"
MOVED=0
finish() {
  code=$?
  trap - EXIT
  if (( code != 0 && MOVED == 1 )); then
    echo "CORE_RELEASE_FAILED: restoring previous plugin" >&2
    if [[ -d "$DEST" ]]; then mv "$DEST" "$STATE/backups/failed-core-$(date -u +%Y%m%dT%H%M%SZ)-${BASHPID}" || true; fi
    mv "$BACKUP" "$DEST" && echo "CORE_ROLLBACK_OK" || echo "CORE_ROLLBACK_FAILED" >&2
  fi
  if [[ -n "$STAGE" && -d "$STAGE" ]]; then rm -rf "$STAGE"; fi
  exit "$code"
}
trap finish EXIT
mv "$DEST" "$BACKUP"
MOVED=1
mv "$STAGE" "$DEST"
STAGE=""
diff -qr "$SOURCE" "$DEST" >/dev/null || { echo "STOP: installed copy mismatch" >&2; exit 1; }
wp plugin is-active pvdak-core --path="$WP" --skip-plugins --skip-themes
wp eval-file "$ACCEPT" --path="$WP"
wp eval '
  if (!defined("PVDAK_CORE_VERSION") || PVDAK_CORE_VERSION !== "0.8.14") WP_CLI::error("Version mismatch");
  if (!pvdak_maintenance_enabled() || pvdak_core_intake_open() || pvdak_core_member_intake_open() || pvdak_core_subscription_gate_on()) WP_CLI::error("Safety gate changed");
  WP_CLI::line("PVDAK_POST_RELEASE_GATES_CLOSED");
' --path="$WP"
echo "CORE_UPGRADE_OK version=$NEW backup=$BACKUP db_backup=$DB"

  exit 0
fi
ACTION="${1:-}"
SRC="${PVDAK_CORE_SOURCE:-$HOME/public_html/wp-content/themes/pvdakcom-main/packages/pvdak-core}"
WP_ROOT="${PVDAK_WP_ROOT:-$HOME/public_html}"
PLUGINS="$WP_ROOT/wp-content/plugins"
DEST="$PLUGINS/pvdak-core"
if [[ "$ACTION" != "" && "$ACTION" != "--install" ]]; then
    echo "USAGE: bash $0 [--install]" >&2
    exit 2
fi
if [[ ! -f "$SRC/pvdak-core.php" || ! -f "$SRC/includes/requests.php" || ! -f "$SRC/includes/professionals.php" ]]; then
    echo "ERROR: source incomplete: $SRC" >&2
    exit 1
fi
command -v php >/dev/null || { echo "ERROR: PHP CLI unavailable" >&2; exit 1; }
while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
done < <(find "$SRC" -type f -name '*.php' -print0)
echo "SOURCE_LINT_OK"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SMOKE="$SCRIPT_DIR/../tests/pvdak-core/policy-smoke.php"
if [[ ! -f "$SMOKE" ]]; then
    echo "STOP: required Core policy test is absent" >&2
    exit 1
fi
php -l "$SMOKE" >/dev/null
PVDAK_CORE_SOURCE="$SRC/pvdak-core.php" php -d display_errors=stderr -d log_errors=1 -d error_log=/dev/stderr "$SMOKE" >/dev/null || {
    echo "STOP: Core policy checks failed. No plugin files were changed." >&2
    exit 1
}
echo "SOURCE_POLICY_SMOKE_OK"
echo "SOURCE=$SRC"
echo "DEST=$DEST"
if [[ "$ACTION" != "--install" ]]; then
    echo "DRY_RUN: no changes made. Use --install to copy source WITHOUT activation."
    exit 0
fi
if ! command -v wp >/dev/null 2>&1 || [[ ! -f "$WP_ROOT/wp-config.php" ]] ||
   ! wp core is-installed --path="$WP_ROOT" >/dev/null 2>&1; then
    echo "STOP: WordPress and WP-CLI must be available before installing a plugin" >&2
    exit 1
fi
if command -v wp >/dev/null 2>&1 && [[ -f "$WP_ROOT/wp-config.php" ]]; then
    if wp plugin is-active pvdak-core --path="$WP_ROOT" 2>/dev/null; then
        echo "STOP: PVDak Core is already active. Do not replace files in use." >&2
        exit 1
    fi
fi
mkdir -p "$PLUGINS"
STAGE="$(mktemp -d "$PLUGINS/.pvdak-core-stage.XXXXXX")"
cleanup() { [[ ! -d "$STAGE" ]] || rm -rf -- "$STAGE"; }
trap cleanup EXIT
cp -a "$SRC/." "$STAGE/"
BACKUP=""
if [[ -e "$DEST" ]]; then
    mkdir -p "$HOME/.pvdak-deploy/backups"
    BACKUP="$HOME/.pvdak-deploy/backups/core-$(date -u +%Y%m%dT%H%M%SZ)"
    mv -- "$DEST" "$BACKUP"
    echo "BACKUP=$BACKUP"
fi
if ! mv -- "$STAGE" "$DEST"; then
    if [[ -n "$BACKUP" && -d "$BACKUP" && ! -e "$DEST" ]]; then
        mv -- "$BACKUP" "$DEST"
    fi
    echo "ERROR: install failed; previous plugin restored when possible." >&2
    exit 1
fi
if ! wp plugin is-installed pvdak-core --path="$WP_ROOT"; then
    echo "ERROR: WordPress did not recognize the copied Core plugin; attempting rollback." >&2
    if [[ -d "$DEST" ]]; then
        FAILED_DIR="$(mktemp -d "$PLUGINS/.pvdak-core-invalid.XXXXXX")"
        mv -- "$DEST" "$FAILED_DIR/failed-plugin"
        echo "Failed source retained for inspection: $FAILED_DIR"
    fi
    if [[ -n "$BACKUP" && -d "$BACKUP" ]]; then
        mv -- "$BACKUP" "$DEST"
        echo "ROLLBACK_OK: previous inactive Core restored"
    fi
    exit 1
fi
echo "INSTALLED_INACTIVE_SOURCE: $DEST"
echo "No plugin was activated. Complete staging tests before enabling intake."
