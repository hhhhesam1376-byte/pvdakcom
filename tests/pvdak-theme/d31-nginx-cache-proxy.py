#!/usr/bin/env python3
"""D31 shared-cache regression against disposable WordPress through real nginx.

No production hostname, credentials, database or CDN is accessed. This proves
only localhost nginx behavior; it is NOT acceptance of the production CDN.
"""
import os
import socket
import subprocess
import sys
import tempfile
import time
from http.cookiejar import CookieJar
from pathlib import Path
from urllib.parse import urlencode
from urllib.request import (
    HTTPCookieProcessor, HTTPRedirectHandler, ProxyHandler, Request, build_opener,
)

UPSTREAM = "http://127.0.0.1:8080"
EDGE = "http://127.0.0.1:8081"
ACCOUNT = os.environ.get("PVDAK_CI_D31_ACCOUNT_ID", "")
LOGIN = os.environ.get("PVDAK_CI_D31_LOGIN_ID", "")
if (os.environ.get("GITHUB_ACTIONS") != "true"
        or os.environ.get("PVDAK_CI_D31_ISOLATED") != "1"
        or not ACCOUNT.isdecimal() or not LOGIN.isdecimal()
        or not os.environ.get("RUNNER_TEMP")):
    sys.exit("D31_NGINX_ABORT: disposable CI fixture required")


class LocalRedirects(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        from urllib.parse import urlsplit
        parsed = urlsplit(newurl)
        if parsed.scheme != "http" or parsed.netloc not in (
                "127.0.0.1:8080", "127.0.0.1:8081"):
            raise AssertionError("D31_NGINX_ABORT: off-loopback redirect")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def client():
    cookies = CookieJar()
    return build_opener(ProxyHandler({}), LocalRedirects(),
                        HTTPCookieProcessor(cookies)), cookies


def authenticate(opener, cookies, username, password):
    with opener.open(UPSTREAM + "/wp-login.php", timeout=15) as response:
        response.read()
    body = urlencode({
        "log": username, "pwd": password, "wp-submit": "Log In",
        "redirect_to": UPSTREAM + "/?page_id=" + ACCOUNT, "testcookie": "1",
    }).encode()
    with opener.open(Request(UPSTREAM + "/wp-login.php", data=body, headers={
            "Content-Type": "application/x-www-form-urlencoded"}), timeout=15) as response:
        response.read()
    if not any(c.name.startswith("wordpress_logged_in_") for c in cookies):
        raise AssertionError("D31_NGINX_FAIL: synthetic WordPress login failed")


def read(opener, url):
    with opener.open(Request(url, headers={"User-Agent": "PVDak-D31-Nginx-CI"}),
                     timeout=15) as response:
        if response.status != 200:
            raise AssertionError("D31_NGINX_FAIL: HTTP 200 required")
        return (response.read(2_000_000).decode("utf-8"),
                {k.lower(): v.lower() for k, v in response.headers.items()})


checks = 0


def check(ok, label):
    global checks
    if not ok:
        raise AssertionError("D31_NGINX_FAIL: " + label)
    checks += 1


def check_private(opener, url, own=None, other=None):
    body, headers = read(opener, url)
    check("no-store" in headers.get("cache-control", ""),
          "origin no-store survives nginx")
    check(headers.get("x-pvdak-edge-cache", "") not in ("hit", "stale", "updating"),
          "shared nginx cache never serves private response")
    check("noindex" in headers.get("x-robots-tag", ""),
          "private robots header survives nginx")
    if own:
        check(own in body and other not in body,
              "private identity remains isolated behind shared cache")


def main():
    root = Path(tempfile.mkdtemp(prefix="pvdak-nginx-", dir=os.environ["RUNNER_TEMP"]))
    (root / "cache").mkdir()
    config = root / "nginx.conf"
    config.write_text("""
worker_processes 1;
pid %s;
error_log %s warn;
events { worker_connections 128; }
http {
  access_log %s;
  proxy_cache_path %s levels=1:2 keys_zone=pvdak_ci_cache:1m inactive=5m max_size=10m;
  server {
    listen 127.0.0.1:8081;
    location / {
      proxy_pass http://127.0.0.1:8080;
      proxy_set_header Host 127.0.0.1:8080;
      proxy_cache pvdak_ci_cache;
      proxy_cache_valid 200 5m;
      add_header X-PVDak-Edge-Cache $upstream_cache_status always;
    }
  }
}
""" % (root / "nginx.pid", root / "nginx.error.log", root / "nginx.access.log", root / "cache"))
    # This deliberately uses nginx's ordinary cache rules. It does not
    # ignore upstream Cache-Control or Set-Cookie, nor bypass auth by fiat.
    syntax = subprocess.run(["nginx", "-t", "-c", str(config)],
                            capture_output=True, text=True)
    if syntax.returncode:
        raise AssertionError("D31_NGINX_CONFIG_FAIL: " + syntax.stderr.strip())
    process = subprocess.Popen(["nginx", "-c", str(config), "-g", "daemon off;"],
                               stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    try:
        for _ in range(40):
            if process.poll() is not None:
                detail = process.stderr.read().decode("utf-8", errors="replace")
                log = (root / "nginx.error.log").read_text(errors="replace") if (root / "nginx.error.log").exists() else ""
                raise AssertionError("D31_NGINX_FAIL: nginx exited during startup: " + detail + log)
            try:
                with socket.create_connection(("127.0.0.1", 8081), timeout=1):
                    break
            except OSError:
                time.sleep(0.25)
        else:
            raise AssertionError("D31_NGINX_FAIL: nginx did not listen")

        anon, _ = client()
        # Positive cache control: a harmless public static stylesheet MUST
        # actually reach MISS then HIT. Without this, a disabled/misconfigured
        # proxy cache could make every private no-HIT assertion vacuously pass.
        # The query is fixed and local; no production/CDN URL is contacted.
        stylesheet = EDGE + "/wp-content/themes/pvdakcom-main/style.css?d31_cache_probe=1"
        css_first, first_headers = read(anon, stylesheet)
        css_second, second_headers = read(anon, stylesheet)
        check("Theme Name: PVDak" in css_first,
              "public stylesheet served by disposable WordPress origin")
        check(first_headers.get("x-pvdak-edge-cache") == "miss",
              "shared nginx cache reports a genuine public MISS")
        check(second_headers.get("x-pvdak-edge-cache") == "hit",
              "shared nginx cache reports a genuine public HIT")
        check(css_first == css_second,
              "public cached response body is stable across MISS/HIT")
        check_private(anon, EDGE + "/?page_id=" + LOGIN)
        subscriber, sc = client()
        editor, ec = client()
        authenticate(subscriber, sc, "ci_d31_subscriber",
                     "pvdak_ci_subscriber_disposable_2026")
        authenticate(editor, ec, "ci_d31_editor",
                     "pvdak_ci_editor_disposable_2026")
        check(sc is not ec, "independent cookie jars")
        account = EDGE + "/?page_id=" + ACCOUNT
        check_private(subscriber, account, "D31_CI_SUBSCRIBER_PRIVATE",
                      "D31_CI_EDITOR_PRIVATE")
        check_private(editor, account, "D31_CI_EDITOR_PRIVATE",
                      "D31_CI_SUBSCRIBER_PRIVATE")
        check_private(subscriber, account, "D31_CI_SUBSCRIBER_PRIVATE",
                      "D31_CI_EDITOR_PRIVATE")
        check_private(editor, account, "D31_CI_EDITOR_PRIVATE",
                      "D31_CI_SUBSCRIBER_PRIVATE")
        print(f"PVDAK_D31_NGINX_CACHE_OK: {checks}/{checks} localhost nginx "
              "shared-cache checks; real CDN/host/deploy NOT_TESTED.")
    except Exception:
        # Cache writes exercise nginx worker filesystem access that a
        # no-store-only regression never exercised. Surface the real worker
        # error, rather than an opaque RemoteDisconnected in hosted CI.
        for name in ("nginx.error.log", "nginx.access.log"):
            file = root / name
            if file.exists():
                print("PVDAK_D31_NGINX_DIAG " + name + ": " +
                      "\\n".join(file.read_text(errors="replace").splitlines()[-25:]),
                      file=sys.stderr)
        print("PVDAK_D31_NGINX_DIAG process_rc=" + str(process.poll()) +
              " cache_mode=" + oct((root / "cache").stat().st_mode & 0o777),
              file=sys.stderr)
        raise
    finally:
        process.terminate()
        try:
            process.communicate(timeout=5)
        except subprocess.TimeoutExpired:
            process.kill()
            process.communicate(timeout=5)


if __name__ == "__main__":
    main()
