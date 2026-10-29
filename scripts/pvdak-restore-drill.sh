#!/usr/bin/env bash
# D30: rehearse restoring private theme/Core backups in disposable storage.
# SQL is imported ONLY into a new, socket-only MariaDB instance in that storage.
# Never reads wp-config.php, connects to the production DB or changes the site.
set -Eeuo pipefail
umask 077
# Never launch the isolated SQL server with superuser privileges.
[[ "$(id -u)" -ne 0 ]] || { echo "D30_STOP: run as an unprivileged account" >&2; exit 1; }

usage() {
  echo 'USAGE: bash scripts/pvdak-restore-drill.sh --theme DIR --core DIR --sql FILE [--files-only]' >&2
  exit 2
}
THEME='' CORE='' SQL='' FILES_ONLY=0
while (( $# )); do
  case "$1" in
    --theme|--core|--sql)
      (( $# >= 2 )) || usage
      case "$1" in
        --theme) THEME="$2" ;;
        --core) CORE="$2" ;;
        --sql) SQL="$2" ;;
      esac
      shift 2 ;;
    --files-only) FILES_ONLY=1; shift ;;
    *) usage ;;
  esac
done
[[ -n "$THEME" && -n "$CORE" && -n "$SQL" ]] || usage
for cmd in realpath mktemp cp diff find rm; do
  command -v "$cmd" >/dev/null || { echo "D30_STOP: required command unavailable" >&2; exit 1; }
done

BACKUP_ROOT="${PVDAK_D30_BACKUP_ROOT:-$HOME/.pvdak-deploy/backups}"
[[ -d "$BACKUP_ROOT" && ! -L "$BACKUP_ROOT" ]] || { echo 'D30_STOP: private backup root missing/unsafe' >&2; exit 1; }
BACKUP_ROOT="$(realpath -e -- "$BACKUP_ROOT")"
# Refuse a backup root within the web tree, even when overridden for a drill.
WEB_ROOT="$(realpath -m -- "${PVDAK_WP_ROOT:-$HOME/public_html}")"
[[ "$BACKUP_ROOT" != "$WEB_ROOT" && "$BACKUP_ROOT" != "$WEB_ROOT/"* ]] || {
  echo 'D30_STOP: backup root is inside public web tree' >&2; exit 1;
}

check_candidate() {
  local input="$1" kind="$2" resolved
  [[ ! -L "$input" ]] || { echo "D30_STOP: $kind symlink" >&2; exit 1; }
  resolved="$(realpath -e -- "$input" 2>/dev/null)" || { echo "D30_STOP: $kind missing" >&2; exit 1; }
  [[ "$resolved" == "$BACKUP_ROOT/"* ]] || { echo "D30_STOP: $kind outside backup root" >&2; exit 1; }
  if [[ "$kind" == sql ]]; then
    [[ -f "$resolved" && -r "$resolved" && -s "$resolved" && ! -L "$resolved" ]] || {
      echo 'D30_STOP: SQL backup not readable regular nonempty file' >&2; exit 1;
    }
  else
    [[ -d "$resolved" && -r "$resolved" && ! -L "$resolved" ]] || {
      echo "D30_STOP: $kind backup invalid" >&2; exit 1;
    }
    [[ -z "$(find "$resolved" -type l -print -quit)" ]] || {
      echo "D30_STOP: $kind contains symlink" >&2; exit 1;
    }
  fi
  printf '%s' "$resolved"
}
THEME="$(check_candidate "$THEME" theme)"
CORE="$(check_candidate "$CORE" core)"
SQL="$(check_candidate "$SQL" sql)"
for file in style.css functions.php index.php header.php footer.php; do
  [[ -s "$THEME/$file" && -f "$THEME/$file" ]] || { echo "D30_STOP: incomplete theme ($file)" >&2; exit 1; }
done
for file in pvdak-core.php includes/requests.php includes/professionals.php; do
  [[ -s "$CORE/$file" && -f "$CORE/$file" ]] || { echo "D30_STOP: incomplete Core ($file)" >&2; exit 1; }
done

SCRATCH="$(mktemp -d "${TMPDIR:-/tmp}/pvdak-d30.XXXXXXXX")"
DB_PID=''
cleanup() {
  local rc=$?
  trap - EXIT
  if [[ -n "$DB_PID" ]]; then
    kill "$DB_PID" 2>/dev/null || true
    wait "$DB_PID" 2>/dev/null || true
  fi
  [[ -z "$SCRATCH" ]] || rm -rf -- "$SCRATCH"
  exit "$rc"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# Simulate a changed release, then restore the ORIGINAL private snapshots.
# The only directories removed below are known children of mktemp's SCRATCH.
mkdir -p "$SCRATCH/theme" "$SCRATCH/core"
cp -a -- "$THEME/." "$SCRATCH/theme/"
cp -a -- "$CORE/." "$SCRATCH/core/"
diff -qr -- "$THEME" "$SCRATCH/theme" >/dev/null || { echo 'D30_FAIL: theme copy mismatch' >&2; exit 1; }
diff -qr -- "$CORE" "$SCRATCH/core" >/dev/null || { echo 'D30_FAIL: Core copy mismatch' >&2; exit 1; }
printf '\nD30_TEST_RELEASE\n' >> "$SCRATCH/theme/style.css"
printf '\nD30_TEST_RELEASE\n' >> "$SCRATCH/core/pvdak-core.php"
rm -rf -- "$SCRATCH/theme" "$SCRATCH/core"
mkdir -p "$SCRATCH/theme" "$SCRATCH/core"
cp -a -- "$THEME/." "$SCRATCH/theme/"
cp -a -- "$CORE/." "$SCRATCH/core/"
diff -qr -- "$THEME" "$SCRATCH/theme" >/dev/null || { echo 'D30_FAIL: theme restore mismatch' >&2; exit 1; }
diff -qr -- "$CORE" "$SCRATCH/core" >/dev/null || { echo 'D30_FAIL: Core restore mismatch' >&2; exit 1; }
echo 'D30_FILES_RESTORE_OK: theme and Core restored byte-for-byte in disposable storage'

if (( FILES_ONLY )); then
  echo 'D30_SQL_RESTORE_NOT_TESTED: --files-only; no database used'
  exit 0
fi
for cmd in mariadb-install-db mariadbd mariadb; do
  command -v "$cmd" >/dev/null || { echo 'D30_SQL_RESTORE_NOT_TESTED: isolated MariaDB binaries unavailable' >&2; exit 3; }
done
mkdir -p "$SCRATCH/db" "$SCRATCH/no-plugins"
SOCKET="$SCRATCH/db.sock"
PIDFILE="$SCRATCH/mariadb.pid"
# No defaults, TCP networking or external database credentials.
mariadb-install-db --no-defaults --datadir="$SCRATCH/db" \
  --auth-root-authentication-method=normal --skip-test-db --user="$(id -un)" \
  >"$SCRATCH/init.log" 2>&1 || { echo 'D30_SQL_RESTORE_FAILED: isolated database initialization' >&2; exit 1; }
mariadbd --no-defaults --datadir="$SCRATCH/db" --socket="$SOCKET" \
  --pid-file="$PIDFILE" --log-error="$SCRATCH/db.log" --skip-networking \
  --user="$(id -un)" --secure-file-priv="$SCRATCH" --local-infile=0 \
  --plugin-dir="$SCRATCH/no-plugins" &
DB_PID=$!
ready=0
for (( attempt=0; attempt<50; attempt++ )); do
  if mariadb --no-defaults --protocol=socket --socket="$SOCKET" --user=root \
    --batch --skip-column-names -e 'SELECT 1' >/dev/null 2>&1; then
    ready=1; break
  fi
  kill -0 "$DB_PID" 2>/dev/null || break
  sleep 0.2
done
(( ready )) || { echo 'D30_SQL_RESTORE_FAILED: isolated database not ready' >&2; exit 1; }
DB='pvdak_restore_drill'
mariadb --no-defaults --protocol=socket --socket="$SOCKET" --user=root \
  -e "CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" >/dev/null || {
  echo 'D30_SQL_RESTORE_FAILED: cannot create isolated database' >&2; exit 1;
}
mariadb --no-defaults --binary-mode --local-infile=0 --protocol=socket --socket="$SOCKET" --user=root "$DB" \
  < "$SQL" >"$SCRATCH/import.log" 2>&1 || { echo 'D30_SQL_RESTORE_FAILED: SQL import into isolated database' >&2; exit 1; }
TABLES="$(mariadb --no-defaults --protocol=socket --socket="$SOCKET" --user=root \
  --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB' AND table_type='BASE TABLE'")"
[[ "$TABLES" =~ ^[1-9][0-9]*$ ]] || { echo 'D30_SQL_RESTORE_FAILED: no restored tables' >&2; exit 1; }
echo "D30_SQL_RESTORE_OK: isolated socket-only MariaDB, tables=$TABLES; WordPress boot NOT_TESTED"
