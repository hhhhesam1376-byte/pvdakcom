#!/usr/bin/env python3
"""D31 HTTP checks against disposable WordPress/MariaDB started by CI only.

This test never contacts production, logs in to production, or writes to a live site.
"""
import os
import sys
from html.parser import HTMLParser
from urllib.parse import quote, urljoin
from urllib.request import Request, urlopen

HOST = "http://127.0.0.1:8080"
PARENT = os.environ.get("PVDAK_CI_D31_PARENT_ID", "")
if os.environ.get("PVDAK_CI_D31_ISOLATED") != "1" or not PARENT.isdecimal():
    sys.exit("D31_HTTP_ABORT: isolated CI fixture and parent ID required")


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = {}
        self.topic_links = []
        self.robots = []
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


def fetch(path):
    url = urljoin(HOST, path)
    with urlopen(Request(url, headers={"User-Agent": "PVDak-D31-Isolated-CI"}), timeout=15) as response:
        if response.status != 200 or "text/html" not in response.headers.get("Content-Type", ""):
            raise AssertionError(f"HTTP response not HTML 200: {url}")
        data = response.read(2_000_000)
    page = Page()
    page.feed(data.decode("utf-8", errors="strict"))
    return page


checks = 0


def check(condition, label):
    global checks
    if not condition:
        raise AssertionError("D31_HTTP_FAIL: " + label)
    checks += 1


index_url = "/?page_id=" + quote(PARENT)
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
print(f"PVDAK_D31_HTTP_OK: {checks}/{checks} actual localhost HTTP checks; browser, live cache, production deploy NOT_TESTED.")
