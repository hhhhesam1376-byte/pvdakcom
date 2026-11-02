#!/usr/bin/env python3
"""Read-only D31 CDN/staging cache audit. No logins, writes or redirects.

Run with synthetic staging accounts only. Never paste cookie values into argv.
This audit does not test production latency, purge, SMTP or deployment.
"""
import argparse
import http.client
import os
import re
import ssl
import sys
from urllib.parse import urlsplit

UNSAFE_CACHE_STATUS = re.compile(r"(?<![a-z])(?:hit|stale|updating)(?![a-z])")


def fail(message):
    raise RuntimeError("D31_EDGE_FAIL: " + message)


def path_ok(value):
    return value.startswith("/") and not value.startswith("//") and "#" not in value and "\r" not in value and "\n" not in value


def request(base, path, cookie=None):
    if not path_ok(path):
        fail("unsafe relative path")
    conn_cls = http.client.HTTPSConnection if base.scheme == "https" else http.client.HTTPConnection
    conn = conn_cls(base.hostname, base.port, timeout=15, **({"context": ssl.create_default_context()} if base.scheme == "https" else {}))
    headers = {"User-Agent": "PVDak-D31-ReadOnly-Edge-Audit", "Accept-Encoding": "identity"}
    # No request cache-bypass header; GET never follows redirects.
    if cookie:
        headers["Cookie"] = cookie
    try:
        conn.request("GET", path, headers=headers)
        response = conn.getresponse()
        body = response.read(500001)
        if len(body) > 500000:
            fail("response exceeds 500KB safety limit")
        # Duplicate HTTP headers must not hide an earlier edge HIT or a
        # conflicting cache policy. Keep every value rather than last-wins.
        headers = {}
        for key, value in response.getheaders():
            key = key.lower()
            value = value.lower().strip()
            headers[key] = headers[key] + ", " + value if key in headers else value
        return response.status, headers, body
    finally:
        conn.close()


def cache_value(headers, name):
    return headers.get(name, "").strip()


def is_hit(value):
    return bool(re.search(r"(?<![a-z])hit(?![a-z])", value))


def private_ok(status, headers, cache_header, label, require_noindex=False):
    if status not in (200, 301, 302, 303, 307, 308):
        fail(label + ": unexpected HTTP status " + str(status))
    # A substring such as "x-no-store" is not the no-store directive.
    directives = {item.strip() for item in headers.get("cache-control", "").split(",")}
    if "no-store" not in directives:
        fail(label + ": missing no-store")
    cache = cache_value(headers, cache_header)
    if not cache:
        fail(label + ": CDN cache status header missing")
    if UNSAFE_CACHE_STATUS.search(cache):
        fail(label + ": private response was cached")
    # A positive Age means a shared intermediary served an aged response,
    # even if the vendor-specific cache header misleadingly says BYPASS.
    # RFC Age is a single non-negative decimal integer; malformed or
    # duplicated Age fields fail closed rather than hiding cache reuse.
    age = headers.get("age")
    if age is not None and not re.fullmatch(r"0+", age.strip()):
        fail(label + ": positive or invalid Age header on private response")
    if require_noindex and "noindex" not in headers.get("x-robots-tag", ""):
        fail(label + ": missing X-Robots-Tag noindex")


def audit(args):
    base = urlsplit(args.base_url)
    loopback = base.hostname in ("127.0.0.1", "localhost")
    if (base.username or base.password or base.query or base.fragment or base.path not in ("", "/") or
            not base.hostname or base.scheme not in ("http", "https")):
        fail("base URL must be a bare origin without credentials/query")
    if base.hostname and base.hostname.rstrip(".") in ("pvdak.com", "www.pvdak.com"):
        fail("production hostname is not an authorized staging target")
    ci_only = loopback and args.ci_loopback and os.getenv("GITHUB_ACTIONS") == "true"
    if not ci_only and not args.confirm_staging:
        fail("explicit --confirm-staging required")
    if base.scheme != "https" and not ci_only:
        fail("HTTPS required outside disposable GitHub CI loopback")
    if not all(path_ok(x) for x in (args.public_path, args.login_path, args.account_path)):
        fail("paths must be origin-relative")
    if args.cache_header not in ("cf-cache-status", "x-cache", "x-pvdak-edge-cache", "x-litespeed-cache"):
        fail("unsupported cache status header")

    # Prove the CDN is really caching a public asset. Without this positive
    # control, private MISS checks can pass when the CDN is bypassed entirely.
    public = [request(base, args.public_path) for _ in range(3)]
    if any(status != 200 for status, _, _ in public):
        fail("public control did not return HTTP 200")
    if not any(is_hit(cache_value(headers, args.cache_header)) for _, headers, _ in public):
        fail("public asset did not demonstrate a CDN HIT")
    if not all(body == public[0][2] for _, _, body in public):
        fail("public cached asset changed across requests")
    print("D31_EDGE_PUBLIC_CACHE_OK: verified public HIT and stable body")

    # Synthetic identity markers are checked on anonymous responses too.
    # This detects origin leaks even if the CDN reports BYPASS/no-store.
    a, b = os.getenv("PVDAK_D31_COOKIE_A"), os.getenv("PVDAK_D31_COOKIE_B")
    ma, mb = os.getenv("PVDAK_D31_MARKER_A"), os.getenv("PVDAK_D31_MARKER_B")
    for label, path in (("anonymous-login", args.login_path), ("anonymous-account", args.account_path)):
        for _ in range(2):
            status, headers, body = request(base, path)
            private_ok(status, headers, args.cache_header, label, require_noindex=(label == "anonymous-login"))
            if ma and mb and (ma.encode() in body or mb.encode() in body):
                fail(label + ": anonymous response leaked synthetic identity")
    print("D31_EDGE_ANONYMOUS_OK: login/account no-store, no private HIT or synthetic identity")


    if not all((a, b, ma, mb)):
        print("D31_EDGE_PARTIAL: two synthetic staging cookies and unique markers absent; authenticated isolation NOT_TESTED")
        return 3
    if a == b or ma == mb or len(ma) < 8 or len(mb) < 8:
        fail("two distinct synthetic accounts and unique markers required")
    for label, cookie, own, other in (("account-A", a, ma, mb), ("account-B", b, mb, ma),
                                       ("account-A-repeat", a, ma, mb), ("account-B-repeat", b, mb, ma)):
        status, headers, body = request(base, args.account_path, cookie)
        private_ok(status, headers, args.cache_header, label, require_noindex=True)
        if status != 200 or own.encode() not in body or other.encode() in body:
            fail(label + ": synthetic private identity mismatch or leakage")
    # Re-check anonymous access *after* authenticated requests. A faulty
    # shared cache can leak the last logged-in response to the next visitor.
    for _ in range(2):
        status, headers, body = request(base, args.account_path)
        private_ok(status, headers, args.cache_header, "anonymous-after-auth")
        if ma.encode() in body or mb.encode() in body:
            fail("anonymous-after-auth: anonymous response leaked synthetic identity")
    print("D31_EDGE_AUTHENTICATED_OK: two synthetic sessions isolated across repeated CDN requests")
    print("D31_EDGE_POST_AUTH_ANONYMOUS_OK: no private identity leaked to anonymous visitors")
    print("D31_EDGE_CHECKS_OK: CDN cache policy read-only audit; purge/latency/deploy/owner acceptance NOT_TESTED")
    return 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-url", required=True)
    parser.add_argument("--public-path", required=True)
    parser.add_argument("--login-path", required=True)
    parser.add_argument("--account-path", required=True)
    parser.add_argument("--cache-header", required=True)
    parser.add_argument("--ci-loopback", action="store_true", help="Only valid in GitHub Actions on 127.0.0.1")
    parser.add_argument("--confirm-staging", action="store_true", help="Explicitly authorize read-only staging requests")
    try:
        return audit(parser.parse_args())
    except (RuntimeError, OSError, ssl.SSLError) as exc:
        # Never log cookies, response bodies, user markers or private URLs.
        print(str(exc), file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
