#!/usr/bin/env bash
# PVDak: configure the *existing* WordPress site for Persian.
# Read-only by default. "--apply" explicitly installs/activates fa_IR.
# "--admins" optionally updates only administrators whose user locale is en_US.
# Does not reinstall WordPress, edit wp-config.php, or touch posts/users/passwords.
set -euo pipefail

WP_ROOT="${PVDAK_WP_ROOT:-$HOME/public_html}"
MODE="${1:---check}"
ADMIN_MODE="${2:-}"

if [[ "$MODE" != "--check" && "$MODE" != "--apply" ]] ||
   [[ -n "$ADMIN_MODE" && "$ADMIN_MODE" != "--admins" ]] ||
   [[ "$ADMIN_MODE" == "--admins" && "$MODE" != "--apply" ]] ||
   [[ $# -gt 2 ]]; then
    echo "Usage: bash $0 [--check | --apply [--admins]]" >&2
    exit 2
fi
if ! command -v wp >/dev/null 2>&1; then
    echo "ERROR: WP-CLI is not available on this host." >&2
    exit 1
fi
if [[ ! -f "$WP_ROOT/wp-config.php" ]]; then
    echo "ERROR: WordPress wp-config.php missing at $WP_ROOT" >&2
    exit 1
fi
if ! wp core is-installed --path="$WP_ROOT" >/dev/null 2>&1; then
    echo "ERROR: WordPress is not installed at $WP_ROOT" >&2
    exit 1
fi

site_language="$(wp option get WPLANG --path="$WP_ROOT" 2>/dev/null || true)"
if [[ -z "$site_language" ]]; then site_language="en_US (default/unspecified)"; fi
echo "WordPress root: $WP_ROOT"
echo "Current WordPress site locale: $site_language"
if wp language core is-installed fa_IR --path="$WP_ROOT" >/dev/null 2>&1; then
    echo "Persian language pack: INSTALLED"
    language_ready=1
else
    echo "Persian language pack: NOT INSTALLED"
    language_ready=0
fi

if [[ "$MODE" == "--check" ]]; then
    echo "CHECK_ONLY: no changes made."
    echo "To apply once: bash $0 --apply --admins"
    exit 0
fi

# Network access to WordPress.org may be required for initial installation.
if (( language_ready == 0 )); then
    echo "Installing the official Persian (fa_IR) core language pack..."
    wp language core install fa_IR --path="$WP_ROOT"
fi
# Use the supported WP-CLI site language command, not direct SQL.
wp site switch-language fa_IR --path="$WP_ROOT"

if [[ "$ADMIN_MODE" == "--admins" ]]; then
    echo "Checking admin profile language overrides (en_US only)..."
    while IFS= read -r user_id; do
        [[ "$user_id" =~ ^[0-9]+$ ]] || continue
        locale="$(wp user meta get "$user_id" locale --path="$WP_ROOT" 2>/dev/null || true)"
        if [[ "$locale" == "en_US" ]]; then
            wp user meta update "$user_id" locale fa_IR --path="$WP_ROOT" >/dev/null
            echo "Admin user ID $user_id: English override changed to Persian."
        elif [[ -z "$locale" || "$locale" == "fa_IR" ]]; then
            echo "Admin user ID $user_id: already uses Persian/site default."
        else
            echo "Admin user ID $user_id: explicit $locale preference preserved."
        fi
    done < <(wp user list --role=administrator --field=ID --path="$WP_ROOT")
fi

actual="$(wp option get WPLANG --path="$WP_ROOT")"
if [[ "$actual" != "fa_IR" ]]; then
    echo "ERROR: WordPress locale was not switched successfully; reported: $actual" >&2
    exit 1
fi
effective_locale="$(wp eval 'echo get_locale();' --path="$WP_ROOT" 2>/dev/null)"
if [[ "$effective_locale" != "fa_IR" ]]; then
    echo "ERROR: WordPress still reports effective locale '$effective_locale'. Check WPLANG overrides in wp-config.php or plugins." >&2
    exit 1
fi
echo "PERSIAN_SITE_OK: locale=fa_IR"
echo "The dashboard uses RTL when its user's profile language is Persian or inherits the site language."
echo "No WordPress reinstall, database reset, theme deployment, or Cron edit performed."
