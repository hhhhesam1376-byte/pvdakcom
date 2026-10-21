#!/bin/bash
# PVDak production theme deployer. Run as the cPanel account, never root.
set -Eeuo pipefail
export PATH="/usr/local/cpanel/3rdparty/lib/path-bin:/usr/local/bin:/usr/bin:/bin"
export GIT_TERMINAL_PROMPT=0
umask 077
ACCOUNT="/home/amirhoseainsabet"
REPO="$ACCOUNT/repositories/pvdakcom"
WP="$ACCOUNT/public_html"
TARGET="$WP/wp-content/themes/pvdakcom-main"
STATE="$ACCOUNT/.pvdak-deploy"
REMOTE="https://github.com/hhhhesam1376-byte/pvdakcom.git"
PHP="/usr/local/bin/php"
WPC="/usr/local/bin/wp"
mkdir -p "$STATE/backups"
chmod 700 "$STATE" "$STATE/backups"
exec 9>"$STATE/lock"
flock -n 9 || exit 0
log() { printf '[%s] %s\n' "$(date -u +%FT%TZ)" "$*"; }
die() { log "ERROR: $*"; exit 1; }
[[ "$(id -un)" == amirhoseainsabet ]] || die "Wrong account"
for tool in git rsync flock tar curl realpath timeout; do
  command -v "$tool" >/dev/null || die "Missing tool: $tool"
done
[[ -x "$PHP" && -x "$WPC" ]] || die "PHP or WP-CLI missing"
[[ -f "$WP/wp-load.php" && -d "$TARGET" && ! -L "$TARGET" ]] || die "WordPress/theme missing or symlinked"
[[ "$(realpath "$WP")" == "$WP" && "$(realpath "$TARGET")" == "$TARGET" ]] || die "Unexpected real path"
[[ "$(realpath "$STATE")" == "$STATE" ]] || die "State must not be symlinked"
SITE="$("$WPC" option get siteurl --path="$WP" --skip-plugins --skip-themes)"
[[ "${SITE%/}" == https://pvdak.com ]] || die "Wrong WordPress site"
ACTIVE="$("$WPC" option get stylesheet --path="$WP" --skip-plugins --skip-themes)"
[[ "$ACTIVE" == pvdakcom-main ]] || die "Active theme changed; deployment stopped"
# Fetch objects only: preserve the old checkout and all its manual files.
timeout 120 git -C "$REPO" fetch --quiet --no-tags "$REMOTE" "+refs/heads/main:refs/remotes/pvdak-deploy/main"
SHA="$(git -C "$REPO" rev-parse refs/remotes/pvdak-deploy/main)"
if [[ -f "$STATE/deployed" && "$(cat "$STATE/deployed")" == "$SHA" ]]; then
  date -u +%FT%TZ > "$STATE/last-check"
  log "UP_TO_DATE $SHA"
  exit 0
fi
[[ ! -f "$STATE/failed" || "$(cat "$STATE/failed")" != "$SHA" ]] || die "This commit previously failed; push a fix or remove the private failed marker to retry"
[[ -z "$(git -C "$REPO" ls-tree -r "$SHA" | awk '$1 == "120000" || $1 == "160000" { print $1 }')" ]] || die "Symlinks/submodules are not allowed in a theme release"
STAGE="$(mktemp -d "$STATE/stage.XXXXXXXX")"
BACKUP=""
PUBLISHING=0
cleanup() {
  rc=$?
  trap - EXIT
  if (( PUBLISHING == 1 && rc != 0 )); then
    log "Deployment failed; restoring $BACKUP"
    if rsync -a --delete "$BACKUP/" "$TARGET/"; then
      log "ROLLBACK_OK"
    else
      log "ROLLBACK_FAILED: restore the private backup manually"
    fi
    printf '%s\n' "$SHA" > "$STATE/failed"
  fi
  rm -rf -- "$STAGE"
  exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
git -C "$REPO" archive "$SHA" | tar -x -C "$STAGE"
for required in style.css index.php functions.php header.php footer.php; do
  [[ -s "$STAGE/$required" ]] || die "Missing required theme file: $required"
done
grep -Eq '^Theme Name:[[:space:]]*PVDak[[:space:]]*$' "$STAGE/style.css" || die "Unexpected theme"
[[ ! -e "$STAGE/templates/index.html" ]] || die "Block theme detected; expected classic PHP theme"
while IFS= read -r -d '' file; do
  "$PHP" -l "$file" >/dev/null || die "PHP syntax error: ${file#"$STAGE/"}"
done < <(find "$STAGE" -type f -name '*.php' -print0)
# Check the existing site before changing files. Check it again after deployment.
health() {
  code="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' --connect-timeout 10 --max-time 40 "https://pvdak.com/?pvdak_deploy_check=$SHA-$(date +%s)")" || return 1
  [[ "$code" == 200 ]]
}
health || die "Site health check failed before deployment; no theme files changed"
BACKUP="$STATE/backups/$(date -u +%Y%m%dT%H%M%SZ)-${SHA:0:12}"
mkdir "$BACKUP"
cp -a "$TARGET/." "$BACKUP/"
# Never copy deployment scripts, Git metadata or documentation into the public theme.
rm -rf "$STAGE/.github" "$STAGE/scripts" "$STAGE/docs"
rm -f "$STAGE/.gitignore" "$STAGE/.cpanel.yml" "$STAGE/README.md" "$STAGE/CHANGELOG.md"
PUBLISHING=1
rsync -rlt --delete-delay --delay-updates --chmod=D755,F644 "$STAGE/" "$TARGET/"
health || die "Site health check failed after deployment"
printf '%s\n' "$SHA" > "$STATE/deployed.tmp"
mv -f "$STATE/deployed.tmp" "$STATE/deployed"
PUBLISHING=0
rm -f "$STATE/failed"
date -u +%FT%TZ > "$STATE/last-check"
log "DEPLOY_OK $SHA; backup=$BACKUP"
