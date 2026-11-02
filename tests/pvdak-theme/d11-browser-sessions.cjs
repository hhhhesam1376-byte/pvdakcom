#!/usr/bin/env node
'use strict';
/**
 * D11 two independent real Chrome sessions on disposable CI WordPress only.
 * Uses fixed, synthetic fixture credentials. Never navigates outside loopback.
 */
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const HOST = 'http://127.0.0.1:8080';
const account = process.env.PVDAK_CI_D31_ACCOUNT_ID || '';
if (process.env.PVDAK_CI_D11_ISOLATED !== '1' ||
    process.env.GITHUB_ACTIONS !== 'true' ||
    !/^[1-9][0-9]*$/.test(account)) {
  process.stderr.write('D11_BROWSER_ABORT: isolated account fixture required\n');
  process.exit(2);
}
const identities = [
  { key: 'client', login: 'ci_d11_browser_client',
    password: 'pvdak_ci_d11_client_browser_only_2026' },
  { key: 'lawyer', login: 'ci_d11_browser_lawyer',
    password: 'pvdak_ci_d11_lawyer_browser_only_2026' }
];
let checks = 0;
function check(value, label) {
  assert.ok(value, 'D11_BROWSER_FAIL: ' + label);
  checks++;
}
async function safeGoto(page, path) {
  const url = new URL(path, HOST);
  if (url.origin !== HOST) throw new Error('D11_BROWSER_ABORT: non-loopback URL');
  const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 30000 });
  check(response && response.status() === 200, 'local HTTP 200 for ' + path.split('?')[0]);
  check(new URL(page.url()).origin === HOST, 'redirect remains on loopback');
  return response;
}
async function main() {
  const browser = await chromium.launch({
    channel: 'chrome', headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const contexts = [];
  try {
    const anon = await browser.newContext({ locale: 'fa-IR' });
    contexts.push(anon);
    const anonPage = await anon.newPage();
    await safeGoto(anonPage, '/?page_id=' + account);
    const anonHTML = await anonPage.content();
    check(!anonHTML.includes('D11_CASE_CLIENT_ONLY') &&
      !anonHTML.includes('D11_CASE_LAWYER_ONLY') &&
      !anonHTML.includes('D11_CITY_CLIENT_ONLY') &&
      !anonHTML.includes('D11_CITY_LAWYER_ONLY'),
      'anonymous browser cannot read either private case or professional city');

    for (const identity of identities) {
      const context = await browser.newContext({ locale: 'fa-IR' });
      contexts.push(context);
      const page = await context.newPage();
      await safeGoto(page, '/wp-login.php');
      await page.locator('#user_login').fill(identity.login);
      await page.locator('#user_pass').fill(identity.password);
      await page.locator('#wp-submit').click();
      await page.waitForLoadState('domcontentloaded');
      check((await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_')),
        identity.key + ' has a real WordPress authentication cookie');
      const response = await safeGoto(page, '/?page_id=' + account);
      check(await page.locator('main.pvdak-account-page').count() === 1,
        identity.key + ' sees actual private account template');
      const html = await page.content();
      const own = identity.key.toUpperCase();
      const other = identity.key === 'client' ? 'LAWYER' : 'CLIENT';
      check(html.includes('D11_BROWSER_' + own),
        identity.key + ' sees own private identity');
      check(html.includes('D11_CASE_' + own + '_ONLY'),
        identity.key + ' sees own private Core request');
      check(html.includes('D11_CITY_' + own + '_ONLY'),
        identity.key + ' sees own private professional application');
      check(!html.includes('D11_CASE_' + other + '_ONLY') &&
        !html.includes('D11_CITY_' + other + '_ONLY') &&
        !html.includes('D11_BROWSER_' + other),
        identity.key + ' cannot see other account identity/case/profile');
      check((response.headers()['cache-control'] || '').includes('no-store'),
        identity.key + ' private response is no-store');
      check(!(await page.locator('a[href*="edit.php?post_type=pvdak_request"]').count()),
        identity.key + ' has no private request admin edit link');
    }
    check(contexts[1] !== contexts[2],
      'client and lawyer have distinct browser contexts');
    const clientPage = contexts[1].pages()[0];
    await safeGoto(clientPage, '/?page_id=' + account);
    const again = await clientPage.content();
    check(again.includes('D11_CASE_CLIENT_ONLY') &&
      !again.includes('D11_CASE_LAWYER_ONLY'),
      'client remains isolated after lawyer session activity');
  } finally {
    for (const context of contexts) await context.close();
    await browser.close();
  }
  process.stdout.write('PVDAK_D11_BROWSER_OK: ' + checks + '/' + checks +
    ' real two-session Chrome checks; disposable CI only; staging/production NOT_TESTED.\n');
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
