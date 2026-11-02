#!/usr/bin/env node
'use strict';
/**
 * D13 real HTTP password-recovery negative and one-use checks on disposable
 * WordPress/MariaDB CI only. The key is generated for a marked D11 fixture
 * by WP-CLI, stored in RUNNER_TEMP, never printed or sent off loopback.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const HOST = 'http://127.0.0.1:8080';
const LOGIN = 'ci_d11_browser_client';
const OLD = 'pvdak_ci_d11_client_browser_only_2026';
const NEW = 'pvdak_ci_d13_reset_new_password_2026';
if (process.env.GITHUB_ACTIONS !== 'true' ||
    process.env.PVDAK_CI_D11_ISOLATED !== '1' ||
    process.env.PVDAK_CI_D13_ISOLATED !== '1' ||
    !/^[1-9][0-9]*$/.test(process.env.PVDAK_CI_D31_LOGIN_ID || '') ||
    !process.env.RUNNER_TEMP) {
  process.stderr.write('D13_HTTP_ABORT: disposable CI fixture required\n');
  process.exit(2);
}
const keyFile = path.join(process.env.RUNNER_TEMP, 'pvdak-d13-reset-key');
const key = fs.readFileSync(keyFile, 'utf8').trim();
if (!/^[a-zA-Z0-9]{10,64}$/.test(key)) throw new Error('D13_HTTP_ABORT: invalid fixture key');
let checks = 0;
function check(value, label) {
  assert.ok(value, 'D13_HTTP_FAIL: ' + label);
  ++checks;
}
function local(pathname) {
  const url = new URL(pathname, HOST);
  if (url.origin !== HOST) throw new Error('D13_HTTP_ABORT: external URL');
  return url.href;
}
const resetPath = '/?page_id=' + process.env.PVDAK_CI_D31_LOGIN_ID +
  '&auth=recover-change&reset=1&login=' + encodeURIComponent(LOGIN) +
  '&key=' + encodeURIComponent(key);
const badPath = resetPath.replace('key=' + encodeURIComponent(key), 'key=invalid-key');
function nonceFrom(html) {
  const match = html.match(/name="_pvdak_reset_nonce"\s+value="([^"]+)"/) ||
    html.match(/value="([^"]+)"\s+name="_pvdak_reset_nonce"/);
  return match ? match[1] : '';
}
async function get(context, url) {
  const response = await context.request.get(local(url), { maxRedirects: 0 });
  check(new URL(response.url()).origin === HOST, 'GET stays on loopback');
  return response;
}
async function post(context, form) {
  const response = await context.request.post(local('/pvdak-wp/wp-admin/admin-post.php'), {
    form: { action: 'pvdak_recovery_change', login: LOGIN, key, ...form },
    maxRedirects: 0
  });
  check(new URL(response.url()).origin === HOST, 'POST stays on loopback');
  return response;
}
async function loginWith(browser, password) {
  const context = await browser.newContext();
  try {
    const page = await context.newPage();
    await page.goto(local('/wp-login.php'), { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill(LOGIN);
    await page.locator('#user_pass').fill(password);
    await page.locator('#wp-submit').click();
    await page.waitForLoadState('domcontentloaded');
    return (await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_'));
  } finally {
    await context.close();
  }
}
async function main() {
  const browser = await chromium.launch({
    channel: 'chrome', headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const context = await browser.newContext();
  try {
    const invalid = await get(context, badPath);
    const invalidHtml = await invalid.text();
    check(invalid.status() === 200, 'invalid key renders recovery screen');
    check(!invalidHtml.includes('name="action" value="pvdak_recovery_change"'),
      'invalid key does not render reset form');
    const valid = await get(context, resetPath);
    const html = await valid.text();
    const nonce = nonceFrom(html);
    check(valid.status() === 200 && html.includes('name="action" value="pvdak_recovery_change"'),
      'valid one-use key renders actual PVDak reset form');
    check(/no-store/i.test(valid.headers()['cache-control'] || ''),
      'reset URL is HTTP no-store');
    check((valid.headers()['referrer-policy'] || '').toLowerCase() === 'no-referrer',
      'reset URL blocks referrer leakage');
    check(/^[a-zA-Z0-9]{10}$/.test(nonce), 'reset form has WordPress nonce');

    const missingNonce = await post(context, {
      new_password: NEW, confirm_password: NEW
    });
    check(missingNonce.status() === 403, 'missing nonce rejected with HTTP 403');
    const mismatch = await post(context, {
      _pvdak_reset_nonce: nonce, new_password: NEW,
      confirm_password: NEW + '_different'
    });
    check([301, 302, 303].includes(mismatch.status()) &&
      /status=invalid/.test(mismatch.headers().location || ''),
      'password confirmation mismatch fails without success redirect');
    const short = await post(context, {
      _pvdak_reset_nonce: nonce, new_password: 'short',
      confirm_password: 'short'
    });
    check([301, 302, 303].includes(short.status()) &&
      /status=invalid/.test(short.headers().location || ''),
      'short password fails without success redirect');
    const stillValid = await get(context, resetPath);
    check((await stillValid.text()).includes('name="action" value="pvdak_recovery_change"'),
      'failed attempts do not consume valid reset key');
    // D11 already proves the fixture's initial password login before this test.
    // Avoid a redundant login here: WordPress may rotate the user's password
    // hash on authentication, invalidating an outstanding reset key.
    const changed = await post(context, {
      _pvdak_reset_nonce: nonce, new_password: NEW, confirm_password: NEW
    });
    const changedLocation = changed.headers().location || '';
    const changedStatus = new URL(changedLocation || '/', HOST).searchParams.get('status');
    if (!([301, 302, 303].includes(changed.status()) && changedStatus === 'reset')) {
      process.stderr.write('D13_HTTP_DIAGNOSTIC: HTTP=' + changed.status() +
        ' redirect_status=' + (changedStatus || 'none') + '\\n');
    }
    check([301, 302, 303].includes(changed.status()) && changedStatus === 'reset',
      'valid reset returns success only after database write');
    const replay = await get(context, resetPath);
    check(!(await replay.text()).includes('name="action" value="pvdak_recovery_change"'),
      'consumed key cannot reopen password-change form');
    const repeated = await post(context, {
      _pvdak_reset_nonce: nonce, new_password: OLD, confirm_password: OLD
    });
    check([301, 302, 303].includes(repeated.status()) &&
      /status=invalid/.test(repeated.headers().location || ''),
      'replay POST cannot reset password again');
    check(!await loginWith(browser, OLD), 'old password rejected after reset');
    check(await loginWith(browser, NEW), 'new password authenticates in fresh Chrome session');
    process.stdout.write('PVDAK_D13_HTTP_OK: ' + checks + '/' + checks +
      ' real HTTP/Chrome recovery checks; disposable CI only; live email and staging NOT_TESTED.\n');
  } finally {
    await context.close();
    await browser.close();
  }
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
