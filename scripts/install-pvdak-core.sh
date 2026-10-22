#!/usr/bin/env bash
# PVDak Core source installer — default dry-run; never activates a plugin.
# Use --install only after verifying the PHP release and taking a host backup.
set -euo pipefail
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
echo "SOURCE=$SRC"
echo "DEST=$DEST"
if [[ "$ACTION" != "--install" ]]; then
    echo "DRY_RUN: no changes made. Use --install to copy source WITHOUT activation."
    exit 0
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
echo "INSTALLED_INACTIVE_SOURCE: $DEST"
echo "No plugin was activated. Complete staging tests before enabling intake."
