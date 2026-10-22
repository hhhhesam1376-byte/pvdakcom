#!/usr/bin/env bash
# Controlled first-time PVDak Core activation for existing maintenance site.
# Run from a PRIVATE git archive. Default is read-only preflight.
# --activate creates a private DB backup, runs WP-CLI acceptance and rolls back plugin activation on failure.
set -Eeuo pipefail
umask 077
MODE="$1"
if [[ "$MODE" != "--check" && "$MODE" != "--activate" ]]; then
  echo "USAGE: bash pvdak-core-activate-guarded.sh --check|--activate" >&2
  exit 2
fi
W="$HOME/public_html"
SOURCE="$W/wp-content/themes/pvdakcom-main/packages/pvdak-core"
DEST="$W/wp-content/plugins/pvdak-core"
SELF_DIR="$(cd -- "$(dirname -- "$0")" && pwd)"
ACCEPTANCE="$SELF_DIR/pvdak-core-acceptance.php"
STATE="$HOME/.pvdak-deploy"
fail() { echo "STOP: $*" >&2; exit 1; }
say() { printf 'CORE_GUARD: %s\n' "$*"; }

[[ "$(id -u)" -ne 0 ]] || fail "Run as the non-root cPanel site owner."
command -v wp >/dev/null 2>&1 || fail "WP-CLI unavailable."
[[ -f "$W/wp-config.php" && -f "$W/wp-load.php" ]] || fail "WordPress path missing."
wp core is-installed --path="$W" --skip-plugins --skip-themes >/dev/null 2>&1 ||
  fail "WordPress is not installed at expected path."
site="$(wp option get siteurl --path="$W" --skip-plugins --skip-themes)"
site="$(printf '%s' "$site" | sed 's@/$@@')"
[[ "$site" == "https://pvdak.com" ]] || fail "Wrong WordPress site URL."
theme="$(wp option get stylesheet --path="$W" --skip-plugins --skip-themes)"
[[ "$theme" == "pvdakcom-main" ]] || fail "Unexpected active WordPress theme."
[[ -f "$SOURCE/pvdak-core.php" && -f "$DEST/pvdak-core.php" ]] ||
  fail "Bundled and installed Core must both exist before activation."
[[ -r "$ACCEPTANCE" ]] || fail "Private Core acceptance script is missing."
diff -qr "$SOURCE" "$DEST" >/dev/null ||
  fail "Installed Core differs from bundled source. Do not activate out-of-sync code."
say "Bundled and installed Core source MATCH."
legal="$(wp option get pvdak_core_intake_enabled --path="$W" --skip-plugins --skip-themes 2>/dev/null || true)"
member="$(wp option get pvdak_core_member_intake_enabled --path="$W" --skip-plugins --skip-themes 2>/dev/null || true)"
[[ "$legal" != "1" && "$member" != "1" ]] ||
  fail "At least one intake option is enabled. Turn it OFF before activation."
say "Both intake settings are disabled/unset."
maintenance="$(wp eval 'echo function_exists("pvdak_maintenance_enabled") && pvdak_maintenance_enabled() ? "ON" : "OFF";' --path="$W" 2>/dev/null)"
[[ "$maintenance" == "ON" ]] ||
  fail "Maintenance mode must remain enabled for this controlled activation."
say "Public maintenance mode ON."
if wp plugin is-active pvdak-core --path="$W" --skip-plugins --skip-themes >/dev/null 2>&1; then
  say "Plugin already ACTIVE: running read-only acceptance."
  wp eval-file "$ACCEPTANCE" --path="$W"
  echo "CORE_ALREADY_ACTIVE_ACCEPTANCE_OK"
  exit 0
fi
wp plugin is-installed pvdak-core --path="$W" --skip-plugins --skip-themes >/dev/null 2>&1 ||
  fail "WordPress does not recognize pvdak-core installation."
say "Plugin currently INACTIVE, ready for controlled first activation."

if [[ "$MODE" == "--check" ]]; then
  echo "CORE_ACTIVATION_PREFLIGHT_OK (READ_ONLY)"
  exit 0
fi

mkdir -p "$STATE/backups"
[[ -d "$STATE/backups" && ! -L "$STATE/backups" ]] ||
  fail "Private backups folder invalid."
backup="$STATE/backups/pvdak-before-core-$(date -u +%Y%m%dT%H%M%SZ)-$$.sql"
say "Saving private WordPress database backup."
wp db export "$backup" --path="$W" --quiet ||
  fail "Database export failed; plugin not activated."
[[ -s "$backup" ]] || fail "Database backup is empty; plugin not activated."
chmod 600 "$backup"
say "DB_BACKUP_OK: $backup"
attempted=0
finish() {
  local status=$?
  trap - EXIT
  if (( status != 0 && attempted == 1 )); then
    echo "CORE_GUARD: Acceptance failed; attempting plugin DEACTIVATION." >&2
    wp plugin deactivate pvdak-core --path="$W" --skip-plugins --skip-themes >&2 ||
      echo "CORE_GUARD: ROLLBACK_FAILED -- manual review required." >&2
    echo "CORE_GUARD: Pre-activation database backup retained privately; no automatic DB restore." >&2
  fi
}
trap finish EXIT
attempted=1
say "Activating PVDak Core with both intake settings disabled."
wp plugin activate pvdak-core --path="$W"
say "Running active WordPress role/private CPT/lock/source acceptance."
wp eval-file "$ACCEPTANCE" --path="$W"
say "Rechecking intake after activation."
wp eval 'if (pvdak_core_intake_open() || pvdak_core_member_intake_open()) { WP_CLI::error("Core intake unexpectedly open."); } WP_CLI::success("Both intake paths remain closed.");' --path="$W"
say "Rechecking maintenance after activation."
wp eval 'if (!function_exists("pvdak_maintenance_enabled") || !pvdak_maintenance_enabled()) { WP_CLI::error("Maintenance mode is off."); } WP_CLI::success("Maintenance mode remains enabled.");' --path="$W"
echo "CORE_ACTIVATION_ACCEPTANCE_OK (INTAKE_OFF / MAINTENANCE_ON)"
