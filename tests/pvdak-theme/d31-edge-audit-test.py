#!/usr/bin/env python3
"""D31 read-only edge-audit contract tests with a synthetic local HTTP edge."""
import http.server
import os
from pathlib import Path
import subprocess
import sys
import threading
import unittest

SCRIPT = Path(__file__).resolve().parents[2] / "scripts" / "pvdak-d31-edge-audit.py"


class Edge(http.server.BaseHTTPRequestHandler):
    leak = False
    private_hit = False
    fake_no_store = False
    duplicate_hit = False
    hit_later = False
    age_value = None
    duplicate_age = False

    def log_message(self, *_):
        pass

    def do_GET(self):
        if self.path == "/public.css":
            self.send_response(200)
            self.send_header("X-PVDak-Edge-Cache", "HIT")
            self.end_headers()
            self.wfile.write(b"/* public */")
            return
        cookie = self.headers.get("Cookie", "")
        self.send_response(200 if cookie or self.path == "/login" else 302)
        self.send_header("Cache-Control", "private, x-no-store" if self.fake_no_store else "private, no-store")
        self.send_header("X-Robots-Tag", "noindex")
        if self.duplicate_hit:
            self.send_header("X-PVDak-Edge-Cache", "HIT")
        self.send_header("X-PVDak-Edge-Cache", "HIT" if self.private_hit else
                         ("BYPASS, HIT" if self.hit_later else "BYPASS"))
        if self.age_value is not None:
            self.send_header("Age", self.age_value)
        if self.duplicate_age:
            self.send_header("Age", "0")
        self.end_headers()
        if cookie == "session=A":
            self.wfile.write(b"STAGING_A_123456 " + (b"STAGING_B_123456" if self.leak else b""))
        elif cookie == "session=B":
            self.wfile.write(b"STAGING_B_123456")
        else:
            self.wfile.write(b"anonymous")


class EdgeAuditTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), Edge)
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True)
        cls.thread.start()

    @classmethod
    def tearDownClass(cls):
        cls.server.shutdown()
        cls.server.server_close()

    def setUp(self):
        Edge.leak = False
        Edge.private_hit = False
        Edge.fake_no_store = False
        Edge.duplicate_hit = False
        Edge.hit_later = False
        Edge.age_value = None
        Edge.duplicate_age = False

    def run_audit(self, authenticated=True):
        env = {**os.environ, "GITHUB_ACTIONS": "true"}
        if authenticated:
            env.update(PVDAK_D31_COOKIE_A="session=A", PVDAK_D31_COOKIE_B="session=B",
                       PVDAK_D31_MARKER_A="STAGING_A_123456", PVDAK_D31_MARKER_B="STAGING_B_123456")
        else:
            for k in ("PVDAK_D31_COOKIE_A", "PVDAK_D31_COOKIE_B", "PVDAK_D31_MARKER_A", "PVDAK_D31_MARKER_B"):
                env.pop(k, None)
        return subprocess.run([sys.executable, str(SCRIPT), "--base-url",
                               f"http://127.0.0.1:{self.server.server_port}",
                               "--public-path", "/public.css", "--login-path", "/login",
                               "--account-path", "/account", "--cache-header", "x-pvdak-edge-cache",
                               "--ci-loopback"], capture_output=True, text=True, env=env)

    def test_complete_isolated_sessions(self):
        r = self.run_audit()
        self.assertEqual(r.returncode, 0, r.stdout + r.stderr)
        self.assertIn("D31_EDGE_CHECKS_OK", r.stdout)

    def test_anonymous_only_is_partial(self):
        r = self.run_audit(authenticated=False)
        self.assertEqual(r.returncode, 3, r.stdout + r.stderr)
        self.assertIn("NOT_TESTED", r.stdout)

    def test_cross_session_leak_fails(self):
        Edge.leak = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1)
        self.assertIn("identity mismatch", r.stderr)

    def test_private_cache_hit_fails(self):
        Edge.private_hit = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1)
        self.assertIn("private response was cached", r.stderr)

    def test_fake_no_store_directive_fails(self):
        Edge.fake_no_store = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("missing no-store", r.stderr)

    def test_duplicate_cache_status_cannot_hide_hit(self):
        Edge.duplicate_hit = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("private response was cached", r.stderr)

    def test_hit_after_bypass_in_same_header_fails(self):
        Edge.hit_later = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("private response was cached", r.stderr)

    def test_zero_age_on_private_response_allowed(self):
        Edge.age_value = "0"
        r = self.run_audit()
        self.assertEqual(r.returncode, 0, r.stdout + r.stderr)

    def test_positive_age_on_private_response_rejected(self):
        Edge.age_value = "42"
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("positive or invalid Age", r.stderr)

    def test_malformed_age_on_private_response_rejected(self):
        Edge.age_value = "not-an-age"
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("positive or invalid Age", r.stderr)

    def test_duplicate_age_headers_rejected(self):
        Edge.age_value = "0"
        Edge.duplicate_age = True
        r = self.run_audit()
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("positive or invalid Age", r.stderr)

    def test_production_hostname_with_trailing_dot_rejected(self):
        env = {**os.environ, "GITHUB_ACTIONS": "true"}
        r = subprocess.run([sys.executable, str(SCRIPT), "--base-url", "https://pvdak.com.",
                            "--public-path", "/public.css", "--login-path", "/login",
                            "--account-path", "/account", "--cache-header", "cf-cache-status",
                            "--confirm-staging"], capture_output=True, text=True, env=env)
        self.assertEqual(r.returncode, 1, r.stdout + r.stderr)
        self.assertIn("production hostname", r.stderr)

    def test_external_http_is_rejected(self):
        env = {**os.environ, "GITHUB_ACTIONS": "true"}
        r = subprocess.run([sys.executable, str(SCRIPT), "--base-url", "http://example.test",
                            "--public-path", "/public.css", "--login-path", "/login",
                            "--account-path", "/account", "--cache-header", "cf-cache-status",
                            "--confirm-staging"], capture_output=True, text=True, env=env)
        self.assertEqual(r.returncode, 1)
        self.assertIn("HTTPS required", r.stderr)


if __name__ == "__main__":
    unittest.main(verbosity=2)
