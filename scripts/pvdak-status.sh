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
value "WordPress WPLANG option" "$(wp_read eval '$v = get_option("WPLANG", null); echo null === $v ? "UNSET (English default)" : ("" === $v ? "EMPTY (English default)" : $v);')"
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
  value "Core legal intake option" "$(wp_read eval '$v = get_option("pvdak_core_intake_enabled", null); echo null === $v ? "UNSET (defaults OFF)" : ("" === $v ? "EMPTY (defaults OFF)" : $v);')"
  value "Core member intake option" "$(wp_read eval '$v = get_option("pvdak_core_member_intake_enabled", null); echo null === $v ? "UNSET (defaults OFF)" : ("" === $v ? "EMPTY (defaults OFF)" : $v);')"
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
SWIPER_LOCAL="$THEME/assets/vendor/swiper-14.3.0"
if [[ -s "$SWIPER_LOCAL/swiper-bundle.min.js" && -s "$SWIPER_LOCAL/swiper-bundle.min.css" && -s "$SWIPER_LOCAL/LICENSE" ]]; then
  value "Swiper 14.3.0 theme files" "PRESENT (bundled in theme)"
else
  value "Swiper 14.3.0 theme files" "MISSING (theme release incomplete)"
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

# D01: Compare the host's actual successful deployment marker to the
# fetched release ref. An unchanged repository checkout HEAD is not evidence.
DEPLOY_MARKER="$HOME/.pvdak-deploy/deployed"
REMOTE_RELEASE=""
DEPLOYED_RELEASE=""
if command -v git >/dev/null 2>&1 &&
   [[ -d "$HOME/repositories/pvdakcom/.git" ]]; then
  REMOTE_RELEASE="$(git -C "$HOME/repositories/pvdakcom" rev-parse --verify refs/remotes/pvdak-deploy/main 2>/dev/null || true)"
fi
if [[ -f "$DEPLOY_MARKER" && ! -L "$DEPLOY_MARKER" ]]; then
  DEPLOYED_RELEASE="$(head -n 1 "$DEPLOY_MARKER")"
fi
if [[ "$REMOTE_RELEASE" =~ ^[a-f0-9]{40}$ && "$DEPLOYED_RELEASE" == "$REMOTE_RELEASE" ]]; then
  value "D01 deployed vs fetched SHA" "MATCH ${DEPLOYED_RELEASE:0:12}"
  D01_READY=YES
else
  value "D01 deployed vs fetched SHA" "NOT_VERIFIED (run existing deploy.sh, check DEPLOY_OK)"
  D01_READY=NO
fi

failure=0
if [[ "$MODE" == "--test" ]]; then
  if [[ "$D01_READY" != YES ]]; then
    value "D01 release gate" "FAIL: deployed SHA not verified against fetched main"
    failure=1
  fi
  # Verify real installed files, not just a mutable success marker.
  # A cron fetch can succeed while a partial theme deployment or manual
  # override leaves the rendered code different from that commit.
  if [[ "$D01_READY" == YES && -d "$THEME" ]] && command -v cmp >/dev/null 2>&1; then
    D01_FILES=0
    for relative in style.css front-page.php functions.php header.php footer.php \
      assets/js/pvdak-home.js page-templates/pvdak-account.php; do
      if [[ -s "$THEME/$relative" ]] &&
         git -C "$HOME/repositories/pvdakcom" cat-file -e "$REMOTE_RELEASE:$relative" 2>/dev/null &&
         git -C "$HOME/repositories/pvdakcom" show "$REMOTE_RELEASE:$relative" 2>/dev/null |
           cmp -s - "$THEME/$relative"; then
        D01_FILES=$((D01_FILES+1))
      else
        value "D01 mismatched installed file" "$relative"
        failure=1
      fi
    done
    value "D01 installed code parity" "$D01_FILES/7 files matched fetched main"
    if [[ $D01_FILES -ne 7 ]]; then failure=1; fi
  else
    value "D01 installed code parity" "FAIL: deployed marker, theme path or cmp missing"
    failure=1
  fi
  # D02: a successful theme release does not update the independently
  # installed Core plugin; require exact file-tree parity and active status.
  if [[ "$WP_AVAILABLE" == YES ]] &&
     [[ -s "$CORE_SOURCE/pvdak-core.php" && -s "$CORE_INSTALLED/pvdak-core.php" ]] &&
     wp plugin is-active pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1 &&
     diff -qr "$CORE_SOURCE" "$CORE_INSTALLED" >/dev/null 2>&1; then
    value "D02 Core parity gate" "PASS: installed and bundled Core match"
  else
    value "D02 Core parity gate" "FAIL: inactive, absent or different Core"
    failure=1
  fi
  echo "--- CHECKS (READ ONLY) ---"
  if [[ -s "$SWIPER_LOCAL/swiper-bundle.min.js" && -s "$SWIPER_LOCAL/swiper-bundle.min.css" &&
        -s "$SWIPER_LOCAL/LICENSE" ]] &&
     [[ $(wc -c < "$SWIPER_LOCAL/swiper-bundle.min.js") -ge 120000 ]] &&
     [[ $(wc -c < "$SWIPER_LOCAL/swiper-bundle.min.css") -ge 10000 ]]; then
    value "Bundled Swiper library" "PASS"
    echo "PVDAK_SWIPER_LOCAL_OK: JS and CSS shipped in active WordPress theme (read-only)"
  else
    value "Bundled Swiper library" "FAIL (Swiper bundle missing from active theme)"
    failure=1
  fi
  # D06: local files can exist yet fail from the browser with HTTP 403
  # (this happened with previous deployment file permissions). Check only
  # static, first-party assets; 200 does NOT prove visual mobile QA.
  ASSET_URL_ROOT="https://pvdak.com/wp-content/themes/pvdakcom-main"
  ASSET_OK=0
  for asset in rtl.css assets/js/pvdak-home.js assets/js/pvdak-account.js \
      assets/vendor/swiper-14.3.0/swiper-bundle.min.js \
      assets/vendor/swiper-14.3.0/swiper-bundle.min.css; do
    http_code="$(curl --silent --show-error --location --output /dev/null \
      --write-out '%{http_code}' --connect-timeout 8 --max-time 25 \
      "$ASSET_URL_ROOT/$asset" 2>/dev/null || true)"
    if [[ "$http_code" == 200 ]]; then
      ASSET_OK=$((ASSET_OK+1))
    else
      value "D06 static asset" "FAIL HTTP ${http_code:-000}: $asset"
      failure=1
    fi
  done
  value "D06 browser asset delivery" "$ASSET_OK/5 HTTP 200 (visual QA still required)"
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
    # D03 must not report PASS for an early-success shell script that never
    # reached the final marker after all 21 PHP source tests.
    if [[ $rc -eq 0 && "$title" == "D03 full source regression" ]] &&
       ! grep -Fq 'PVDAK_BATCH_PREFLIGHT_OK:' "$log"; then
      echo "FAIL: missing end-of-suite marker (some source checks may have been skipped)" >>"$log"
      rc=1
    fi
    if [[ $rc -eq 0 ]]; then
      value "$title" "PASS"
      grep -E '(^WARN[[:space:]]|PHP_LINT_OK|CORE_POLICY_SMOKE_OK|PVDAK_TWO_ACCOUNT_PRIVACY_OK|STAGING_MULTIUSER_SCRIPT_LINT_OK|PVDAK_EDITOR_BOOTSTRAP_OK|PVDAK_LEGAL_SEARCH_SMOKE_OK|PVDAK_HOMEPAGE_LAYOUT_OK|PVDAK_MEGA_NAV_OK|PREFLIGHT_SUMMARY|PVDAK_CORE_ACCEPTANCE:|CORE_WORDPRESS_ACCEPTANCE_OK)' "$log" | tail -n 8 || true
    else
      value "$title" "FAIL (exit $rc)"
      tail -n 15 "$log"
      failure=1
    fi
    rm -f "$log"
  }
  run_test "Theme release tests" "$SCRIPTS/check-pvdak.sh" "$THEME" "$WP_ROOT"
  run_test "Core source tests" "$SCRIPTS/check-pvdak-core.sh" "$CORE_SOURCE" "$WP_ROOT"
  # D03: run the repository's EXISTING full read-only regression suite
  # against the files actually deployed on the host. The private runner
  # lives outside the published theme; create only an ephemeral copy,
  # without writing to WordPress or duplicating release pipelines.
  BATCH_RUNNER="$SCRIPTS/check-batch-readonly.sh"
  if [[ -r "$BATCH_RUNNER" && -d "$THEME" && -d "$SCRIPTS/../tests" ]]; then
    AUDIT_TEMP="$(mktemp -d "$HOME/.pvdak-deploy/pvdak-audit.XXXXXXXX" 2>/dev/null || true)"
    if [[ -n "$AUDIT_TEMP" ]] &&
       cp -a "$THEME/." "$AUDIT_TEMP/" &&
       cp -a "$SCRIPTS" "$AUDIT_TEMP/scripts" &&
       { [[ -d "$AUDIT_TEMP/tests/pvdak-theme" ]] ||
         cp -a "$SCRIPTS/../tests" "$AUDIT_TEMP/tests"; }; then
      run_test "D03 full source regression" "$BATCH_RUNNER" "$AUDIT_TEMP"
    else
      value "D03 full source regression" "FAIL: private audit copy unavailable"
      failure=1
    fi
    if [[ -n "$AUDIT_TEMP" && -d "$AUDIT_TEMP" && ! -L "$AUDIT_TEMP" ]]; then
      rm -rf -- "$AUDIT_TEMP"
    fi
  else
    value "D03 full source regression" "FAIL: private suite or source missing"
    failure=1
  fi
  # Synthetic negative control for the live WP counter. A deliberately failed
  # assertion must yield 19 checks / 1 failure, not a false 0/0 PASS.
  SELFTEST="$SCRIPTS/pvdak-anonymous-counter-selftest.php"
  if [[ -f "$SELFTEST" ]] && command -v php >/dev/null 2>&1; then
    SELFTEST_LOG="$(mktemp)"
    if php "$SELFTEST" pass >"$SELFTEST_LOG" 2>&1 &&
       php "$SELFTEST" fail >>"$SELFTEST_LOG" 2>&1 &&
       grep -Fq 'COUNTER_SELFTEST_OK: mode=pass checks=19 failures=0' "$SELFTEST_LOG" &&
       grep -Fq 'COUNTER_SELFTEST_OK: mode=fail checks=19 failures=1' "$SELFTEST_LOG"; then
      value "Diagnostic fail-closed self-test" "PASS"
      grep -E 'COUNTER_SELFTEST_OK:' "$SELFTEST_LOG" | tail -n 2 || true
    else
      value "Diagnostic fail-closed self-test" "FAIL"
      tail -n 12 "$SELFTEST_LOG"
      failure=1
    fi
    rm -f "$SELFTEST_LOG"
  else
    value "Diagnostic fail-closed self-test" "FAIL: private PHP self-test missing"
    failure=1
  fi
  # Read-only live WordPress account role audit: no users or case records created.
  if [[ "$WP_AVAILABLE" == "YES" ]] && wp plugin is-active pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    ROLE_AUDIT="$SCRIPTS/pvdak-role-capabilities-smoke.php"
    ROLE_LOG="$(mktemp)"
    if [[ -f "$ROLE_AUDIT" ]] &&
       wp eval-file "$ROLE_AUDIT" --path="$WP_ROOT" >"$ROLE_LOG" 2>&1 &&
       grep -Eq 'PVDAK_ROLE_CAPABILITIES: checks=68 failures=0 expected=68' "$ROLE_LOG"; then
      value "Account role permissions" "PASS"
      grep -E 'PVDAK_ROLE_CAPABILITIES:' "$ROLE_LOG" | tail -n 1 || true
    else
      value "Account role permissions" "FAIL"
      tail -n 14 "$ROLE_LOG"
      failure=1
    fi
    rm -f "$ROLE_LOG"
  else
    value "Account role permissions" "FAIL: D04 requires live active Core and 68 successful assertions"
    failure=1
  fi
  # Separate, anonymous in-memory WordPress test. Never creates real users
  # and never reads or prints private cases.
  if [[ "$WP_AVAILABLE" == "YES" ]] && wp plugin is-active pvdak-core --path="$WP_ROOT" --skip-plugins --skip-themes >/dev/null 2>&1; then
    LIVE_AUDIT="$SCRIPTS/pvdak-anonymous-live-smoke.php"
    if [[ ! -f "$LIVE_AUDIT" ]]; then
      value "Anonymous live WP checks" "FAIL: private audit file missing"
      failure=1
    else
      AUDIT_LOG="$(mktemp)"
      if wp eval-file "$LIVE_AUDIT" --path="$WP_ROOT" >"$AUDIT_LOG" 2>&1 &&
         grep -Eq 'PVDAK_ANON_LIVE_SMOKE: checks=19 failures=0 expected=19' "$AUDIT_LOG"; then
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
    value "Anonymous live WP checks" "FAIL: D04 requires live active Core and 19 successful assertions"
    failure=1
  fi
fi
echo "PVDAK_DIAGNOSTIC_END status=$([[ $failure -eq 0 ]] && echo OK || echo NEEDS_REVIEW)"
exit "$failure"
