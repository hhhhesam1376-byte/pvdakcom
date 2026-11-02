#!/usr/bin/env node
'use strict';
/**
 * D12 real HTTP REST cookie/nonce authorization on disposable CI WordPress.
 * Reuses the D11 marked accounts, but never writes to WordPress or leaves loopback.
 * This does not test production CDN, staging or live credentials.
 */
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const HOST = 'http://127.0.0.1:8080';
if (process.env.GITHUB_ACTIONS !== 'true' ||
    process.env.PVDAK_CI_D11_ISOLATED !== '1' ||
    process.env.PVDAK_CI_D12_ISOLATED !== '1' ||
    !/^[1-9][0-9]*$/.test(process.env.PVDAK_CI_D31_ACCOUNT_ID || '')) {
  process.stderr.write('D12_HTTP_ABORT: disposable CI fixture required\n');
  process.exit(2);
}

const actors = [
  { key: 'client', login: 'ci_d11_browser_client',
    password: 'pvdak_ci_d11_client_browser_only_2026' },
  { key: 'lawyer', login: 'ci_d11_browser_lawyer',
    password: 'pvdak_ci_d11_lawyer_browser_only_2026' }
];
const privateMarkers = [
  'D11_CASE_CLIENT_ONLY', 'D11_CASE_LAWYER_ONLY',
  'D11_PROFILE_CLIENT_ONLY', 'D11_PROFILE_LAWYER_ONLY',
  'D11_CITY_CLIENT_ONLY', 'D11_CITY_LAWYER_ONLY'
];
let checks = 0;
function check(condition, label) {
  assert.ok(condition, 'D12_HTTP_FAIL: ' + label);
  ++checks;
}
function localUrl(path) {
  const url = new URL(path, HOST);
  if (url.origin !== HOST) throw new Error('D12_HTTP_ABORT: external URL');
  return url.href;
}
async function rest(context, route, nonce, params = {}) {
  const url = new URL('/', HOST);
  url.searchParams.set('rest_route', route);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);
  const headers = nonce ? { 'X-WP-Nonce': nonce } : {};
  const response = await context.request.get(url.href, {
    headers, maxRedirects: 0, timeout: 30000
  });
  check(new URL(response.url()).origin === HOST, 'REST response stays on loopback');
  return response;
}
function containsPrivate(text) {
  return privateMarkers.some(marker => text.includes(marker));
}
async function login(context, actor) {
  const page = await context.newPage();
  await page.goto(localUrl('/wp-login.php'), { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(actor.login);
  await page.locator('#user_pass').fill(actor.password);
  await page.locator('#wp-submit').click();
  await page.waitForLoadState('domcontentloaded');
  check((await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_')),
    actor.key + ' logged in with real WordPress cookie');
  check(new URL(page.url()).origin === HOST, actor.key + ' login stays on loopback');
  const nonceResponse = await context.request.get(
    localUrl('/wp-admin/admin-ajax.php?action=rest-nonce'),
    { maxRedirects: 0, timeout: 30000 }
  );
  const nonce = (await nonceResponse.text()).trim();
  check(nonceResponse.status() === 200 && /^[a-zA-Z0-9]{10}$/.test(nonce),
    actor.key + ' obtained session-bound REST nonce via authenticated AJAX');
  return nonce;
}
async function main() {
  const browser = await chromium.launch({
    channel: 'chrome', headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const contexts = [];
  try {
    const anon = await browser.newContext();
    contexts.push(anon);
    const anonMe = await rest(anon, '/wp/v2/users/me');
    check([401, 403].includes(anonMe.status()), 'anonymous users/me denied');
    const anonUsers = await rest(anon, '/wp/v2/users', null, { context: 'edit' });
    check([401, 403].includes(anonUsers.status()), 'anonymous user edit denied');
    for (const type of ['pvdak_request', 'pvdak_professional']) {
      const r = await rest(anon, '/wp/v2/' + type);
      check(r.status() === 404, 'anonymous private CPT route absent: ' + type);
    }

    const active = [];
    for (const actor of actors) {
      const context = await browser.newContext({ locale: 'fa-IR' });
      contexts.push(context);
      const nonce = await login(context, actor);
      const withoutNonce = await rest(context, '/wp/v2/users/me');
      check([401, 403].includes(withoutNonce.status()),
        actor.key + ' cookie without REST nonce cannot authenticate');
      const me = await rest(context, '/wp/v2/users/me', nonce);
      const meBody = await me.json();
      check(me.status() === 200 && Number.isSafeInteger(meBody.id) && meBody.id > 0,
        actor.key + ' cookie plus nonce authenticates REST user');
      check(meBody.slug === actor.login, actor.key + ' REST identity matches login');
      const users = await rest(context, '/wp/v2/users', nonce, { context: 'edit' });
      check([401, 403].includes(users.status()),
        actor.key + ' cannot enumerate user administration');
      for (const type of ['pvdak_request', 'pvdak_professional']) {
        const r = await rest(context, '/wp/v2/' + type, nonce);
        check(r.status() === 404, actor.key + ' private CPT route absent: ' + type);
      }
      for (const marker of ['D11_CASE_CLIENT_ONLY', 'D11_CASE_LAWYER_ONLY']) {
        const r = await rest(context, '/wp/v2/search', nonce,
          { search: marker, per_page: '20' });
        check(r.status() === 200 && !containsPrivate(await r.text()),
          actor.key + ' REST search hides private case ' + marker);
      }
      const posts = await rest(context, '/wp/v2/posts', nonce, { per_page: '20' });
      check(posts.status() === 200 && !containsPrivate(await posts.text()),
        actor.key + ' public posts REST excludes private fixture');
      active.push({ context, nonce, id: meBody.id, key: actor.key });
    }
    check(active[0].id !== active[1].id, 'REST sessions resolve to distinct users');
    for (let i = 0; i < 2; i++) {
      const own = active[i];
      const other = active[1 - i];
      const wrong = await rest(own.context, '/wp/v2/users/me', other.nonce);
      check(wrong.status() === 403, own.key + ' rejects other session REST nonce');
      const again = await rest(own.context, '/wp/v2/users/me', own.nonce);
      const data = await again.json();
      check(again.status() === 200 && data.id === own.id,
        own.key + ' remains authenticated with own nonce after other login');
    }
  } finally {
    for (const context of contexts) await context.close();
    await browser.close();
  }
  process.stdout.write('PVDAK_D12_HTTP_OK: ' + checks + '/' + checks +
    ' real cookie/nonce REST HTTP checks; disposable CI only; staging/production NOT_TESTED.\n');
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
