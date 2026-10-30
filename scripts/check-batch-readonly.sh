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
# Run every independent read-only smoke even when an earlier one fails.
# Report all failures in one checkout; never treat a partial pass as release PASS.
failed=0
run_smoke() {
  if "$@"; then
    return 0
  else
    local rc=$?
    printf 'PVDAK_SMOKE_FAILED: exit=%s command=' "$rc" >&2
    printf '%q ' "$@" >&2
    printf '\n' >&2
    failed=$((failed+1))
    return 0
  fi
}
run_smoke php "$ROOT/tests/pvdak-theme/feature-batch-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/account-experience-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/account-form-ux-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/ticket-experience-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/next-phase-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/next-ten-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/post-core-audit-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/public-pages-auth-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/page-bootstrap-regression.php"
PVDAK_THEME_SOURCE="$ROOT" run_smoke php "$ROOT/tests/pvdak-theme/navigation-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/navigation-version-regression.php"
PVDAK_PRIVATE_SCRIPTS="$ROOT/scripts" run_smoke php "$ROOT/tests/pvdak-theme/homepage-layout-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/editor-bootstrap-smoke.php"
# D31 guards are part of the canonical read-only release gate, not orphan tests.
run_smoke php "$ROOT/tests/pvdak-theme/d31-homepage-preview-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/d31-public-post-privacy-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/d31-public-navigation-smoke.php"
# Keep the older ACF/parent/robots regression in the same canonical runner.
# It now uses canonical WP_Post fixtures and fails closed for private Pages.
run_smoke php "$ROOT/tests/pvdak-theme/legal-topic-search-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/d31-topic-search-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/d31-legal-index-pagination-smoke.php"
run_smoke php "$ROOT/tests/pvdak-theme/d31-account-privacy-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/private-requests-history-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/professional-portal-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/professional-admin-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/request-admin-smoke.php"
run_smoke php -d display_errors=1 "$ROOT/tests/pvdak-core/request-ops-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/triage-overview-smoke.php"
run_smoke php "$ROOT/tests/pvdak-core/policy-smoke.php"
PVDAK_CORE_SOURCE="$ROOT/packages/pvdak-core/pvdak-core.php" run_smoke php "$ROOT/tests/pvdak-core/two-account-privacy-smoke.php"
if (( failed > 0 )); then
  echo "PVDAK_BATCH_SMOKE_FAILURES: $failed (full runner NOT_PASS)" >&2
  exit 1
fi
echo "PVDAK_BATCH_PREFLIGHT_OK: code checks only. No database/browser/mail tests performed."
