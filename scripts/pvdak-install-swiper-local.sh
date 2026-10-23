#!/usr/bin/env bash
# Install pinned official Swiper distribution ON THE WORDPRESS HOST, not via browser CDN.
# Reuses existing PVDak cPanel paths. A persistent uploads asset dir survives theme Cron releases.
# No WordPress DB/permissions/intake/options, no GitHub Actions, and no theme-source writes.
set -euo pipefail
W="${PVDAK_WP_ROOT:-$HOME/public_html}"
ASSETS="$W/wp-content/uploads/pvdak-static/swiper-14.3.0"
JS="swiper-bundle.min.js"
CSS="swiper-bundle.min.css"
LICENSE="LICENSE"
if [[ ! -f "$W/wp-config.php" || ! -d "$W/wp-content" ]]; then
  echo 'STOP: expected WordPress installation missing' >&2
  exit 1
fi
if [[ -L "$ASSETS" ]]; then
  echo 'STOP: asset destination must not be a symlink' >&2
  exit 1
fi
if [[ -s "$ASSETS/$JS" && -s "$ASSETS/$CSS" && -s "$ASSETS/$LICENSE" ]] &&
   [[ $(wc -c < "$ASSETS/$JS") -ge 120000 ]] &&
   [[ $(wc -c < "$ASSETS/$CSS") -ge 10000 ]] &&
   grep -aq "Swiper" "$ASSETS/$JS" &&
   grep -aq "\.swiper" "$ASSETS/$CSS"; then
  echo 'SWIPER_LOCAL_ASSETS_OK: existing assets verified; no changes'
  exit 0
fi
TMP="$(mktemp -d "$HOME/.pvdak-deploy/pvdak-swiper.XXXXXX")"
trap 'rm -rf -- "$TMP"' EXIT

download_verified() {
  local name="$1" min="$2" url status=1
  for base in \
    'https://cdnjs.cloudflare.com/ajax/libs/Swiper/14.3.0' \
    'https://cdn.jsdelivr.net/npm/swiper@14.3.0' \
    'https://unpkg.com/swiper@14.3.0'
  do
    # CDNJS does not serve LICENSE in its public bundle listing.
    if [[ "$name" == LICENSE && "$base" == *cloudflare.com* ]]; then continue; fi
    url="$base/$name"
    echo "Downloading pinned Swiper 14.3.0 $name from $base ..." >&2
    if curl -4 -fsSL --retry 2 --connect-timeout 12 --max-time 90 \
      -A 'PVDak-Swiper-Vendor/1.0' -o "$TMP/$name" "$url"; then
      local bytes
      bytes=$(wc -c < "$TMP/$name")
      if [[ "$bytes" -ge "$min" ]]; then
        case "$name" in
          "$JS") grep -aq 'Swiper' "$TMP/$name" || continue ;;
          "$CSS") grep -aq '\.swiper' "$TMP/$name" || continue ;;
          LICENSE) grep -aq 'MIT' "$TMP/$name" || continue ;;
        esac
        status=0
        break
      fi
    fi
  done
  if [[ "$status" -ne 0 ]]; then
    echo "STOP: official Swiper $name could not be downloaded/verified; existing assets left unchanged" >&2
    return 1
  fi
}
download_verified "$JS" 120000
download_verified "$CSS" 10000
download_verified LICENSE 500

mkdir -p "$ASSETS"
if [[ -L "$ASSETS" ]]; then echo 'STOP: symlink destination detected' >&2; exit 1; fi
# Deploy as complete verified files: browser loads only this local path.
install -m 0644 "$TMP/$JS" "$ASSETS/$JS"
install -m 0644 "$TMP/$CSS" "$ASSETS/$CSS"
install -m 0644 "$TMP/$LICENSE" "$ASSETS/$LICENSE"
sha256sum "$ASSETS/$JS" "$ASSETS/$CSS" "$ASSETS/$LICENSE" > "$ASSETS/SHA256SUMS"
echo 'SWIPER_LOCAL_ASSETS_OK: Swiper 14.3.0 official JS/CSS/LICENSE installed in persistent local WordPress assets'
ls -lh "$ASSETS/$JS" "$ASSETS/$CSS"
