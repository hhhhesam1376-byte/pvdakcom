#!/usr/bin/env bash
# Private read-only batch preflight. Must run on source archive, not production.
# Does not install, migrate, create users, change options or touch wp-config.
set -Eeuo pipefail
ROOT="${1:-$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)}"
[[ -s "$ROOT/functions.php" && -s "$ROOT/packages/pvdak-core/pvdak-core.php" ]] || { echo "FAIL: SOURCE_NOT_FOUND" >&2; exit 2; }
command -v php >/dev/null || { echo "FAIL: PHP_REQUIRED" >&2; exit 2; }
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || { echo "FAIL: PHP_PARSE $f" >&2; exit 1; }
done < <(find "$ROOT" -type f -name '*.php' -print0)
php "$ROOT/tests/pvdak-theme/feature-batch-smoke.php"
php "$ROOT/tests/pvdak-theme/next-phase-smoke.php"
php "$ROOT/tests/pvdak-theme/next-ten-smoke.php"
php "$ROOT/tests/pvdak-theme/post-core-audit-smoke.php"
php "$ROOT/tests/pvdak-theme/public-pages-auth-smoke.php"
PVDAK_THEME_SOURCE="$ROOT" php "$ROOT/tests/pvdak-theme/navigation-smoke.php"
PVDAK_PRIVATE_SCRIPTS="$ROOT/scripts" php "$ROOT/tests/pvdak-theme/homepage-layout-smoke.php"
php "$ROOT/tests/pvdak-theme/editor-bootstrap-smoke.php"
php "$ROOT/tests/pvdak-core/policy-smoke.php"
PVDAK_CORE_SOURCE="$ROOT/packages/pvdak-core/pvdak-core.php" php "$ROOT/tests/pvdak-core/two-account-privacy-smoke.php"
echo "PVDAK_BATCH_PREFLIGHT_OK: code checks only. No database/browser/mail tests performed."
