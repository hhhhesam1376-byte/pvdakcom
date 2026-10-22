#!/usr/bin/env bash
# Read-only syntax and integration check. This does not activate PVDak Core.
set -euo pipefail
ROOT="${1:-$HOME/public_html/wp-content/themes/pvdakcom-main/packages/pvdak-core}"
WORDPRESS="${2:-$HOME/public_html}"
if [[ ! -f "$ROOT/pvdak-core.php" ]]; then
  echo "ERROR: PVDak Core source not found: $ROOT" >&2
  exit 1
fi
command -v php >/dev/null || { echo "ERROR: PHP CLI unavailable" >&2; exit 1; }
count=0
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null || { echo "PHP_SYNTAX_FAILED: $file" >&2; exit 1; }
  count=$((count+1))
done < <(find "$ROOT" -type f -name '*.php' -print0)
echo "PHP_LINT_OK: $count files"
grep -E '^ \* Version:' "$ROOT/pvdak-core.php" || true
if command -v wp >/dev/null 2>&1 && [[ -f "$WORDPRESS/wp-config.php" ]]; then
  echo "WordPress plugin installation:"
  wp plugin is-installed pvdak-core --path="$WORDPRESS" && echo "INSTALLED" || echo "NOT_INSTALLED"
  if wp plugin is-active pvdak-core --path="$WORDPRESS"; then
    echo "PLUGIN_ACTIVE"
    printf 'Intake setting: '
    wp option get pvdak_core_intake_enabled --path="$WORDPRESS" 2>/dev/null || echo "0 (default)"
  else
    echo "PLUGIN_INACTIVE"
  fi
fi
POLICY_TEST="$(dirname "$(dirname "$ROOT")")/tests/pvdak-core/policy-smoke.php"
if [[ -f "$POLICY_TEST" ]]; then
  php "$POLICY_TEST"
else
  echo "INFO: policy smoke test not found beside source: $POLICY_TEST"
fi
echo "NOTE: lint and configuration checks do not substitute for role isolation, privacy or end-to-end tests."
