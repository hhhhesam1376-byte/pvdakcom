#!/usr/bin/env bash
# PVDak host status reporter. READ-ONLY.
# Usage: bash scripts/pvdak-status.sh [--test]
# Reports versions without exposing passwords, tokens, account data or case data.
set -u
MODE="${1:-}"
if [[ -n "$MODE" && "$MODE" != "--test" ]] || [[ $# -gt 1 ]]; then
  echo "USAGE: bash pvdak-status.sh [--test]" >&2
  exit 2
fi

WP_ROOT="${PVDAK_WP_ROOT:-$HOME/public_html}"
THEME="${PVDAK_THEME_PATH:-$WP_ROOT/wp-content/themes/pvdakcom-main}"
CORE_SOURCE="$THEME/packages/pvdak-core"
CORE_INSTALLED="$WP_ROOT/wp-content/plugins/pvdak-core"
NOW="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
SCRIPTS="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"

safe_version() {
  local filepath="$1" expression="$2" result
  if [[ ! -f "$filepath" ]]; then
    echo "MISSING"
    return
  fi
  result="$(sed -n -E "$expression" "$filepath" | head -n 1)"
  if [[ "$result" =~ ^[0-9]+([.][0-9]+){1,3}$ ]]; then
    echo "$result"
  else
    echo "UNKNOWN"
  fi
}
wp_read() {
  if [[ "$WP_AVAILABLE" != "YES" ]]; then echo "UNAVAILABLE"; return; fi
  local result
  result="$(wp "$@" --path="$WP_ROOT" --skip-plugins --skip-themes 2>/dev/null)"
  if [[ $? -eq 0 && -n "$result" ]]; then
    echo "$result"
  else
    echo "UNKNOWN"
  fi
}
value() { printf '%-29s %s\n' "$1:" "$2"; }
echo "PVDAK_DIAGNOSTIC_BEGIN $NOW"
echo "MODE: $([[ "$MODE" == "--test" ]] && echo 'READ_ONLY_WITH_TESTS' || echo 'READ_ONLY_VERSIONS')"
value "Theme source version" "$(safe_version "$THEME/style.css" 's/^[[:space:]]*Version:[[:space:]]*([0-9.]+).*/\1/p')"
value "Core bundled version" "$(safe_version "$CORE_SOURCE/pvdak-core.php" 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9.]+).*/\1/p')"
value "Core installed version" "$(safe_version "$CORE_INSTALLED/pvdak-core.php" 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([0-9.]+).*/\1/p')"

WP_AVAILABLE="NO"
if command -v wp >/dev/null 2>&1 && [[ -f "$WP_ROOT/wp-config.php" ]]; then
  if wp core is-installed --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    WP_AVAILABLE="YES"
  fi
fi
value "WordPress detected" "$WP_AVAILABLE"
value "WordPress version" "$(wp_read core version)"
value "WordPress site language" "$(wp_read eval 'echo get_locale();')"
value "WordPress WPLANG option" "$(wp_read option get WPLANG)"
value "WordPress active theme" "$(wp_read theme list --status=active --field=name)"

if [[ "$WP_AVAILABLE" == "YES" ]]; then
  if wp plugin is-installed pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    value "Core plugin installed" "YES"
  else
    value "Core plugin installed" "NO"
  fi
  if wp plugin is-active pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    value "Core plugin active" "YES"
  else
    value "Core plugin active" "NO"
  fi
  value "Core legal intake option" "$(wp_read option get pvdak_core_intake_enabled)"
  value "Core member intake option" "$(wp_read option get pvdak_core_member_intake_enabled)"
else
  value "Core plugin installed" "NOT_CHECKED"
  value "Core plugin active" "NOT_CHECKED"
  value "Core legal intake option" "NOT_CHECKED"
  value "Core member intake option" "NOT_CHECKED"
fi

if [[ -f "$CORE_SOURCE/pvdak-core.php" && -f "$CORE_INSTALLED/pvdak-core.php" ]]; then
  if diff -qr "$CORE_SOURCE" "$CORE_INSTALLED" >/dev/null 2>&1; then
    value "Bundled vs installed Core" "MATCH"
  else
    value "Bundled vs installed Core" "DIFFERENT"
  fi
else
  value "Bundled vs installed Core" "NOT_COMPARABLE"
fi

if [[ -f "$THEME/rtl.css" ]]; then
  value "RTL stylesheet" "PRESENT"
else
  value "RTL stylesheet" "MISSING"
fi
if command -v php >/dev/null 2>&1; then
  value "PHP CLI version" "$(php -r 'echo PHP_VERSION;' 2>/dev/null)"
else
  value "PHP CLI version" "UNAVAILABLE"
fi
if command -v git >/dev/null 2>&1 && [[ -d "$HOME/repositories/pvdakcom/.git" ]]; then
  # This is the local CHECKOUT, not proof that the site is deployed at this SHA.
  sha="$(git -C "$HOME/repositories/pvdakcom" rev-parse --short=12 HEAD 2>/dev/null)"
  value "Repository checkout SHA" "${sha:-UNKNOWN} (not deployment proof)"
else
  value "Repository checkout SHA" "NOT_AVAILABLE"
fi

failure=0
if [[ "$MODE" == "--test" ]]; then
  echo "--- CHECKS (READ ONLY) ---"
  run_test() {
    local title="$1" script="$2" log rc
    shift 2
    if [[ ! -f "$script" ]]; then
      value "$title" "SKIPPED: SCRIPT_MISSING"
      failure=1
      return
    fi
    log="$(mktemp)"
    if bash "$script" "$@" >"$log" 2>&1; then
      rc=0
    else
      rc=$?
    fi
    if [[ $rc -eq 0 ]]; then
      value "$title" "PASS"
      grep -E '(PHP_LINT_OK|CORE_POLICY_SMOKE_OK|PVDAK_EDITOR_BOOTSTRAP_OK|PVDAK_LEGAL_SEARCH_SMOKE_OK|PREFLIGHT_SUMMARY|CORE_WORDPRESS_ACCEPTANCE_OK)' "$log" | tail -n 8 || true
    else
      value "$title" "FAIL (exit $rc)"
      tail -n 15 "$log"
      failure=1
    fi
    rm -f "$log"
  }
  run_test "Theme release tests" "$SCRIPTS/check-pvdak.sh" "$THEME" "$WP_ROOT"
  run_test "Core source tests" "$SCRIPTS/check-pvdak-core.sh" "$CORE_SOURCE" "$WP_ROOT"
  # Separate, anonymous in-memory WordPress test. Never creates real users
  # and never reads or prints private cases.
  if [[ "$WP_AVAILABLE" == "YES" ]] && wp plugin is-active pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    LIVE_AUDIT="$SCRIPTS/pvdak-anonymous-live-smoke.php"
    if [[ ! -f "$LIVE_AUDIT" ]]; then
      value "Anonymous live WP checks" "FAIL: private audit file missing"
      failure=1
    else
      AUDIT_LOG="$(mktemp)"
      if wp eval-file "$LIVE_AUDIT" --path="$WP_ROOT" >"$AUDIT_LOG" 2>&1; then
        value "Anonymous live WP checks" "PASS"
        grep -E 'PVDAK_ANON_LIVE_SMOKE:' "$AUDIT_LOG" | tail -n 1 || true
      else
        value "Anonymous live WP checks" "FAIL"
        tail -n 18 "$AUDIT_LOG"
        failure=1
      fi
      rm -f "$AUDIT_LOG"
    fi
  else
    value "Anonymous live WP checks" "SKIPPED: Core not active"
  fi
fi
echo "PVDAK_DIAGNOSTIC_END status=$([[ $failure -eq 0 ]] && echo OK || echo NEEDS_REVIEW)"
exit "$failure"
