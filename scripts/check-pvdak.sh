#!/usr/bin/env bash
# PVDak read-only release checks: does not change WordPress or production data.
set -euo pipefail
THEME="${1:-$HOME/public_html/wp-content/themes/pvdakcom-main}"
WP_ROOT="${2:-$HOME/public_html}"
if [[ ! -f "$THEME/functions.php" || ! -f "$THEME/style.css" ]]; then
  echo "FAIL: theme directory not found: $THEME" >&2
  exit 1
fi
if ! command -v php >/dev/null 2>&1; then
  echo "FAIL: PHP CLI missing." >&2
  exit 1
fi
echo "Checking PHP syntax in: $THEME"
count=0
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
  ((count+=1))
done < <(find "$THEME" -type f -name '*.php' -print0)
echo "PASS: PHP lint ($count files)."
if [[ ! -f "$THEME/tests/pvdak-theme/editor-bootstrap-smoke.php" ]]; then
  echo "FAIL: client editor content smoke tests are missing" >&2
  exit 1
fi
php "$THEME/tests/pvdak-theme/editor-bootstrap-smoke.php"
if [[ ! -f "$THEME/tests/pvdak-theme/legal-topic-search-smoke.php" ]]; then
  echo "FAIL: legal Page search smoke tests are missing" >&2
  exit 1
fi
php "$THEME/tests/pvdak-theme/legal-topic-search-smoke.php"
if [[ ! -s "$THEME/category.php" ]] || [[ ! -s "$THEME/assets/css/pvdak-editorial.css" ]]; then
  echo "FAIL: native editorial category archive or styling is missing" >&2
  exit 1
fi
echo "PASS: editorial Category archive and RTL styles found."
if [[ ! -s "$THEME/rtl.css" ]] || ! grep -Fq "pvdak-rtl" "$THEME/functions.php" ||
   ! grep -Fq '<html lang="fa-IR" dir="rtl">' "$THEME/header.php"; then
  echo "FAIL: Persian/RTL assets or document language are not deployed correctly." >&2
  exit 1
fi
echo "PASS: RTL stylesheet, enqueue and Persian document attributes found."
grep -E '^Version:' "$THEME/style.css" | head -1
if command -v wp >/dev/null 2>&1 && [[ -f "$WP_ROOT/wp-config.php" ]]; then
  echo "Active WordPress theme:"
  wp theme list --status=active --field=name --path="$WP_ROOT"
  echo "ACF plugin availability:"
  wp plugin list --field=name --status=active --path="$WP_ROOT" | grep -E '^(advanced-custom-fields|acf-pro)$' || true
  echo "Homepage configuration:"
  wp option get show_on_front --path="$WP_ROOT"
  wp option get page_on_front --path="$WP_ROOT"
  # Cron deliberately excludes private diagnostics from the public theme.
  # When this checker runs from a private git archive, its sibling script
  # is the correct, non-public WP-CLI entry point.
  PRIVATE_SCRIPTS="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
  PREFLIGHT="$PRIVATE_SCRIPTS/pvdak-wp-preflight.php"
  if [[ ! -f "$PREFLIGHT" ]]; then
    echo "FAIL: required PRIVATE WordPress preflight script is missing at $PREFLIGHT" >&2
    exit 1
  fi
  echo "Read-only WordPress runtime preflight:"
  wp eval-file "$PREFLIGHT" --path="$WP_ROOT"
else
  echo "INFO: WP-CLI or wp-config.php unavailable; WordPress runtime checks skipped."
fi
echo "INFO: PHP syntax is not an HTTP/ACF functional test. Check the admin editor and live page manually."
