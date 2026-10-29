#!/usr/bin/env bash
# Private read-only batch preflight. Must run on source archive, not production.
# Does not install, migrate, create users, change options or touch wp-config.
set -Eeuo pipefail
# Always identify a failing check, even when the host suppresses PHP fatal
# diagnostics (PHP CLI failures otherwise show only exit 255 in status).
trap 'rc=$?; printf "PVDAK_BATCH_FAILED: exit=%s line=%s command=%s\n" "$rc" "$LINENO" "$BASH_COMMAND" >&2' ERR
ROOT="${1:-$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)}"
[[ -s "$ROOT/functions.php" && -s "$ROOT/packages/pvdak-core/pvdak-core.php" ]] || { echo "FAIL: SOURCE_NOT_FOUND" >&2; exit 2; }
command -v php >/dev/null || { echo "FAIL: PHP_REQUIRED" >&2; exit 2; }
linted=0
while IFS= read -r -d '' f; do
  php -l "$f" >/dev/null || { echo "FAIL: PHP_PARSE $f" >&2; exit 1; }
  linted=$((linted+1))
done < <(find "$ROOT" -type f -name '*.php' -print0)
if [[ "$linted" -lt 40 ]]; then
  echo "FAIL: PHP_LINT_SCOPE_INCOMPLETE ($linted PHP files; expected full project tree)" >&2
  exit 1
fi
echo "PVDAK_BATCH_PHP_LINT_OK: files=$linted"
php "$ROOT/tests/pvdak-theme/feature-batch-smoke.php"
php "$ROOT/tests/pvdak-theme/account-experience-smoke.php"
php "$ROOT/tests/pvdak-theme/account-form-ux-smoke.php"
php "$ROOT/tests/pvdak-theme/ticket-experience-smoke.php"
php "$ROOT/tests/pvdak-theme/next-phase-smoke.php"
php "$ROOT/tests/pvdak-theme/next-ten-smoke.php"
php "$ROOT/tests/pvdak-theme/post-core-audit-smoke.php"
php "$ROOT/tests/pvdak-theme/public-pages-auth-smoke.php"
php "$ROOT/tests/pvdak-theme/page-bootstrap-regression.php"
PVDAK_THEME_SOURCE="$ROOT" php "$ROOT/tests/pvdak-theme/navigation-smoke.php"
php "$ROOT/tests/pvdak-theme/navigation-version-regression.php"
PVDAK_PRIVATE_SCRIPTS="$ROOT/scripts" php "$ROOT/tests/pvdak-theme/homepage-layout-smoke.php"
php "$ROOT/tests/pvdak-theme/editor-bootstrap-smoke.php"
# D31 guards are part of the canonical read-only release gate, not orphan tests.
php "$ROOT/tests/pvdak-theme/d31-homepage-preview-smoke.php"
php "$ROOT/tests/pvdak-theme/d31-topic-search-smoke.php"
php "$ROOT/tests/pvdak-theme/d31-legal-index-pagination-smoke.php"
php "$ROOT/tests/pvdak-theme/d31-account-privacy-smoke.php"
php "$ROOT/tests/pvdak-core/private-requests-history-smoke.php"
php "$ROOT/tests/pvdak-core/professional-portal-smoke.php"
php "$ROOT/tests/pvdak-core/professional-admin-smoke.php"
php "$ROOT/tests/pvdak-core/request-admin-smoke.php"
php -d display_errors=1 "$ROOT/tests/pvdak-core/request-ops-smoke.php"
php "$ROOT/tests/pvdak-core/triage-overview-smoke.php"
php "$ROOT/tests/pvdak-core/policy-smoke.php"
PVDAK_CORE_SOURCE="$ROOT/packages/pvdak-core/pvdak-core.php" php "$ROOT/tests/pvdak-core/two-account-privacy-smoke.php"
echo "PVDAK_BATCH_PREFLIGHT_OK: code checks only. No database/browser/mail tests performed."
