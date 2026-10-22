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
    ACCEPTANCE="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/pvdak-core-acceptance.php"
    if [[ ! -f "$ACCEPTANCE" ]]; then
      echo "FAIL: Core WP-CLI acceptance checks file is missing" >&2
      exit 1
    fi
    php -l "$ACCEPTANCE" >/dev/null
    wp eval-file "$ACCEPTANCE" --path="$WORDPRESS"
    echo "CORE_WORDPRESS_ACCEPTANCE_OK"
  else
    echo "PLUGIN_INACTIVE"
  fi
fi
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
POLICY_TEST="$SCRIPT_DIR/../tests/pvdak-core/policy-smoke.php"
if [[ ! -f "$POLICY_TEST" ]]; then
  echo "FAIL: Required policy test file absent: $POLICY_TEST" >&2
  exit 1
fi
php -l "$POLICY_TEST" >/dev/null || { echo "FAIL: policy test has PHP syntax errors" >&2; exit 1; }
# Test the actual bundled Core on the WordPress host rather than assuming
# the checkout has been updated (cron uses git fetch + archive).
# Explicit PHP diagnostics are surfaced even when host php.ini hides warnings.
PVDAK_CORE_SOURCE="$ROOT/pvdak-core.php" php -d display_errors=stderr -d log_errors=1 -d error_log=/dev/stderr "$POLICY_TEST"
echo "NOTE: lint and configuration checks do not substitute for role isolation, privacy or end-to-end tests."
