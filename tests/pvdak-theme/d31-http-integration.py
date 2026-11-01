#!/usr/bin/env python3
"""D31 HTTP checks against disposable WordPress/MariaDB started by CI only.

This test never contacts production, logs in to production, or writes to a live site.
"""
import os
import sys
from http.cookiejar import CookieJar
from html.parser import HTMLParser
from urllib.parse import quote, urlencode, urljoin, urlsplit
from urllib.request import Request, HTTPRedirectHandler, HTTPCookieProcessor, ProxyHandler, build_opener

HOST = "http://127.0.0.1:8080"
PARENT = os.environ.get("PVDAK_CI_D31_PARENT_ID", "")
LOGIN = os.environ.get("PVDAK_CI_D31_LOGIN_ID", "")
ACCOUNT = os.environ.get("PVDAK_CI_D31_ACCOUNT_ID", "")
if (os.environ.get("PVDAK_CI_D31_ISOLATED") != "1"
        or not PARENT.isdecimal() or not LOGIN.isdecimal() or not ACCOUNT.isdecimal()):
    sys.exit("D31_HTTP_ABORT: isolated CI fixture, parent, login and account IDs required")


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = {}
        self.topic_links = []
        self.robots = []
        self.headers = {}
        self.text = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "a":
            href = a.get("href", "")
            rel = (a.get("rel") or "").split()
            for direction in ("next", "prev"):
                if direction in rel:
                    self.links[direction] = urljoin(HOST, href)
            if "pvdak-home-topic" in (a.get("class") or "").split():
                self.topic_links.append(href)
        if tag == "meta" and (a.get("name") or "").lower() == "robots":
            self.robots.append((a.get("content") or "").lower())

    def handle_data(self, data):
        self.text.append(data)

    @property
    def body_text(self):
        return " ".join(self.text)


def loopback_only(url):
    parsed = urlsplit(url)
    return parsed.scheme == "http" and parsed.netloc == "127.0.0.1:8080"


class LoopbackRedirectHandler(HTTPRedirectHandler):
    """Validate every redirect hop; urllib otherwise follows remote Location URLs."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        if not loopback_only(newurl):
            raise AssertionError("D31_HTTP_ABORT: off-loopback redirect URL")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


# Ignore ambient HTTP_PROXY/HTTPS_PROXY settings on CI runners.
opener = build_opener(ProxyHandler({}), LoopbackRedirectHandler())


def redirect_rejected(target):
    try:
        LoopbackRedirectHandler().redirect_request(
            Request(HOST + "/"), None, 302, "Found", {}, target
        )
    except AssertionError:
        return True
    return False


def fetch(path):
    url = urljoin(HOST, path)
    # Pagination hrefs come from HTML. Never follow a remote or alternate-port
    # URL, even if a permalink/plugin configuration is unexpectedly wrong.
    if not loopback_only(url):
        raise AssertionError("D31_HTTP_ABORT: off-loopback navigation URL")
    with opener.open(Request(url, headers={"User-Agent": "PVDak-D31-Isolated-CI"}), timeout=15) as response:
        if response.status != 200 or "text/html" not in response.headers.get("Content-Type", ""):
            raise AssertionError(f"HTTP response not HTML 200: {url}")
        data = response.read(2_000_000)
        response_headers = {key.lower(): value.lower() for key, value in response.headers.items()}
    page = Page()
    page.headers = response_headers
    page.feed(data.decode("utf-8", errors="strict"))
    return page


checks = 0


def check(condition, label):
    global checks
    if not condition:
        raise AssertionError("D31_HTTP_FAIL: " + label)
    checks += 1


index_url = "/?page_id=" + quote(PARENT)
check(loopback_only(urljoin(HOST, index_url)), "fixture URL is on isolated loopback")
check(not loopback_only(urljoin(HOST, "//example.invalid/path")),
      "protocol-relative external pagination URL rejected")
check(not loopback_only("http://127.0.0.1:8081/"),
      "alternate localhost port rejected")
check(not loopback_only("https://127.0.0.1:8080/"),
      "unexpected HTTPS origin rejected")
check(redirect_rejected("http://example.invalid/"),
      "redirect handler blocks external Location")
check(redirect_rejected("//example.invalid/"),
      "redirect handler blocks protocol-relative external Location")
check(redirect_rejected("http://127.0.0.1:8081/"),
      "redirect handler blocks alternate-port Location")
check(redirect_rejected("https://127.0.0.1:8080/"),
      "redirect handler blocks unexpected HTTPS Location")
check(redirect_rejected("http://user@127.0.0.1:8080/"),
      "redirect handler blocks userinfo origin confusion")
first = fetch(index_url)
check(len(first.topic_links) == 24, "first HTTP index has 24 public topic links")
check("Topic 001" in first.body_text and "Topic 025" not in first.body_text,
      "first HTTP window has expected ordered titles")
check(all(s not in first.body_text for s in ("Topic password-protected", "Topic draft", "Topic foreign parent")),
      "private/draft/foreign Page titles absent from first HTTP window")
check("next" in first.links and "prev" not in first.links, "first HTTP window exposes next only")

second = fetch(first.links["next"])
check(len(second.topic_links) == 24 and "Topic 025" in second.body_text,
      "second HTTP window advances by 24")
check("next" in second.links and "prev" in second.links,
      "second HTTP window exposes forward and reverse navigation")
check(any("noindex" in directive for directive in second.robots),
      "cursor HTTP page emits robots noindex")
third = fetch(second.links["next"])
check(len(third.topic_links) == 12 and "Topic 049" in third.body_text and "next" not in third.links,
      "final HTTP window contains 12 public topics and no next loop")
back = fetch(second.links["prev"])
check("Topic 001" in back.body_text and len(back.topic_links) == 24,
      "reverse HTTP navigation returns first window")
bad = fetch(index_url + "&pvdak_topics_after=invalid-cursor")
check(len(bad.topic_links) == 24 and "Topic 001" in bad.body_text,
      "invalid HTTP cursor safely resets to public first window")
check(any("noindex" in directive for directive in bad.robots),
      "invalid cursor URL remains noindex")
malformed_search = fetch("/?pvdak_topic%5B%5D=Topic")
check(len(malformed_search.topic_links) == 0,
      "array-valued GET search cannot fall back to public topic preview")
check(any("noindex" in directive for directive in malformed_search.robots),
      "malformed GET search remains noindex")
home_search = fetch("/?pvdak_topic=Topic")
check(len(home_search.topic_links) == 48 and "Topic 001" in home_search.body_text,
      "homepage GET search returns the first 48 real published child topics")
check("next" in home_search.links,
      "homepage GET search exposes a signed continuation link")
check(any("noindex" in directive for directive in home_search.robots),
      "homepage GET search emits robots noindex")
check("Topic password-protected" not in home_search.body_text and "Topic draft" not in home_search.body_text,
      "homepage search does not leak protected/draft topic titles")
home_search_next = fetch(home_search.links["next"])
check(len(home_search_next.topic_links) == 12 and "Topic 049" in home_search_next.body_text,
      "homepage search continuation returns the final 12 public topics")
check("next" not in home_search_next.links,
      "homepage search stops at the final window without a cursor loop")
check(any("noindex" in directive for directive in home_search_next.robots),
      "homepage continuation URL remains noindex")
check("Topic password-protected" not in home_search_next.body_text and "Topic draft" not in home_search_next.body_text,
      "homepage continuation never exposes protected or draft topics")
# Validate actual WordPress response headers, not just PHP source/stub policy.
# The login Page is anonymous, but sensitive because of its page template.
login = fetch("/?page_id=" + quote(LOGIN))
check("no-store" in login.headers.get("cache-control", ""),
      "private login HTTP response is marked no-store")
check("noindex" in login.headers.get("x-robots-tag", "") and
      "nofollow" in login.headers.get("x-robots-tag", ""),
      "private login HTTP response has X-Robots-Tag noindex/nofollow")
check(login.headers.get("referrer-policy", "") == "no-referrer",
      "private login HTTP response blocks referrer leakage")
check(any("noindex" in directive for directive in login.robots),
      "private login HTML robots meta is noindex")
check("no-store" not in first.headers.get("cache-control", ""),
      "anonymous public topic index is not accidentally marked private")

# Two real independent WordPress cookie jars exercise private per-user HTML,
# not a simulated wp_set_current_user() call. All accounts/passwords are
# disposable fixtures guarded by the pvdak_ci database check in the PHP setup.
def authenticated_client(username, password):
    cookies = CookieJar()
    client = build_opener(ProxyHandler({}), LoopbackRedirectHandler(),
                          HTTPCookieProcessor(cookies))
    with client.open(Request(HOST + "/wp-login.php"), timeout=15) as response:
        response.read(2_000_000)  # Set WordPress test cookie before POST.
    form = urlencode({
        "log": username, "pwd": password, "wp-submit": "Log In",
        "redirect_to": HOST + "/?page_id=" + ACCOUNT, "testcookie": "1"
    }).encode("utf-8")
    with client.open(Request(HOST + "/wp-login.php", data=form, headers={
        "Content-Type": "application/x-www-form-urlencoded"
    }), timeout=15) as response:
        response.read(2_000_000)
    check(any(cookie.name.startswith("wordpress_logged_in_") for cookie in cookies),
          username + " has an actual WordPress login cookie")
    return client, cookies


def session_fetch(client, path):
    url = urljoin(HOST, path)
    if not loopback_only(url):
        raise AssertionError("D31_HTTP_ABORT: off-loopback session URL")
    with client.open(Request(url), timeout=15) as response:
        if response.status != 200 or "text/html" not in response.headers.get("Content-Type", ""):
            raise AssertionError("D31_HTTP_FAIL: private session HTML response")
        body = response.read(2_000_000).decode("utf-8", errors="strict")
        headers = {key.lower(): value.lower() for key, value in response.headers.items()}
    page = Page()
    page.headers = headers
    page.feed(body)
    return page


account_path = "/?page_id=" + quote(ACCOUNT)
anonymous_account = fetch(account_path)  # Redirect to branded login, never a private account.
check("D31_CI_SUBSCRIBER_PRIVATE" not in anonymous_account.body_text and
      "D31_CI_EDITOR_PRIVATE" not in anonymous_account.body_text,
      "anonymous account request cannot reveal either private identity")
subscriber_client, subscriber_cookies = authenticated_client(
    "ci_d31_subscriber", "pvdak_ci_subscriber_disposable_2026")
subscriber_account = session_fetch(subscriber_client, account_path)
check("D31_CI_SUBSCRIBER_PRIVATE" in subscriber_account.body_text,
      "subscriber session renders its own private display name")
check("D31_CI_EDITOR_PRIVATE" not in subscriber_account.body_text,
      "subscriber session does not render editor identity")
check("no-store" in subscriber_account.headers.get("cache-control", ""),
      "subscriber account HTML is not publicly cacheable")
editor_client, editor_cookies = authenticated_client(
    "ci_d31_editor", "pvdak_ci_editor_disposable_2026")
editor_account = session_fetch(editor_client, account_path)
check("D31_CI_EDITOR_PRIVATE" in editor_account.body_text,
      "editor session renders its own private display name")
check("D31_CI_SUBSCRIBER_PRIVATE" not in editor_account.body_text,
      "editor session does not render subscriber identity")
check("no-store" in editor_account.headers.get("cache-control", ""),
      "editor account HTML is not publicly cacheable")
check(subscriber_cookies is not editor_cookies,
      "subscriber and editor use distinct independent cookie jars")
# Use the permalink path generated by WordPress itself. The isolated WP-CLI
# install can be hosted in a subdirectory, which scopes auth cookies there.
canonical_index = urlsplit(first.links["next"]).path + "?page_id=" + quote(PARENT)
check("no-store" in session_fetch(subscriber_client, canonical_index).headers.get("cache-control", ""),
      "logged-in subscriber public index response is private/no-store")
check("no-store" in session_fetch(editor_client, canonical_index).headers.get("cache-control", ""),
      "logged-in editor public index response is private/no-store")
subscriber_again = session_fetch(subscriber_client, account_path)
check("D31_CI_SUBSCRIBER_PRIVATE" in subscriber_again.body_text and
      "D31_CI_EDITOR_PRIVATE" not in subscriber_again.body_text,
      "subscriber identity remains isolated after editor request")

print(f"PVDAK_D31_HTTP_OK: {checks}/{checks} actual localhost HTTP checks; browser, live cache, production deploy NOT_TESTED.")
