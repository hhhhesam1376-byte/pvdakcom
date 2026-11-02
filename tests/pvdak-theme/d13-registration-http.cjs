#!/usr/bin/env node
'use strict';
/**
 * D13 real public signup POST, pending login and verification on localhost.
 * This is a disposable CI test; credentials and one-use token never leave CI.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const ORIGIN = 'http://127.0.0.1:8080';
const LOGIN = 'ci_d13_signup_lawyer';
const EMAIL = LOGIN + '@example.invalid';
const PASSWORD = 'pvdak_ci_d13_signup_password_only_2026';
const mode = process.env.PVDAK_CI_D13_SIGNUP_MODE;
if (process.env.GITHUB_ACTIONS !== 'true' ||
    process.env.PVDAK_CI_D13_SIGNUP_ISOLATED !== '1' ||
    process.env.PVDAK_CI_D11_ISOLATED !== '1' ||
    !/^[1-9][0-9]*$/.test(process.env.PVDAK_CI_D31_LOGIN_ID || '') ||
    !process.env.RUNNER_TEMP ||
    !['register', 'verify'].includes(mode)) {
  process.stderr.write('D13_SIGNUP_HTTP_ABORT: disposable CI fixture required\n');
  process.exit(2);
}
const loginUrl = ORIGIN + '/?page_id=' + process.env.PVDAK_CI_D31_LOGIN_ID + '&auth=register-email';
let checks = 0;
function check(ok, label) {
  assert.ok(ok, 'D13_SIGNUP_HTTP_FAIL: ' + label);
  ++checks;
}
function redirect(response) {
  check([301, 302, 303].includes(response.status()), 'POST/verification redirects');
  const target = new URL(response.headers().location || '/', ORIGIN);
  check(target.origin === ORIGIN && !target.searchParams.has('token'), 'redirect stays loopback and hides token');
  return target;
}
async function login(browser) {
  const context = await browser.newContext();
  try {
    const page = await context.newPage();
    await page.goto(ORIGIN + '/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill(LOGIN);
    await page.locator('#user_pass').fill(PASSWORD);
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
    if (mode === 'register') {
      const page = await context.newPage();
      const response = await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
      check(response && response.status() === 200, 'signup page returns HTTP 200');
      const form = page.locator('form:has(input[name="action"][value="pvdak_register"])');
      check(await form.count() === 1, 'real branded signup form visible');
      const nonce = await form.locator('input[name="_pvdak_register_nonce"]').inputValue();
      check(/^[A-Za-z0-9]{10}$/.test(nonce), 'real WordPress signup nonce rendered');
      const post = async (fields, withNonce = true) => context.request.post(
        ORIGIN + '/pvdak-wp/wp-admin/admin-post.php', {
          form: { action: 'pvdak_register', ...(withNonce ? { _pvdak_register_nonce: nonce } : {}), ...fields },
          maxRedirects: 0
        });
      const valid = { register_username: LOGIN, register_email: EMAIL,
        register_password: PASSWORD, register_role: 'lawyer', register_consent: 'on' };
      const noNonce = await post(valid, false);
      check(noNonce.status() === 403, 'missing signup nonce rejected');
      const invalid = redirect(await post({ ...valid, register_username: 'ci_d13_signup_bad',
        register_email: 'ci_d13_signup_bad@example.invalid', register_consent: '' }));
      check(invalid.searchParams.get('state') === 'invalid', 'missing consent rejected');
      const trap = redirect(await post({ ...valid, register_username: 'ci_d13_signup_spam',
        register_email: 'ci_d13_signup_spam@example.invalid', register_site: 'bot' }));
      check(trap.searchParams.get('state') === 'received', 'honeypot handled without account creation');
      const created = redirect(await post(valid));
      check(['received', 'email_issue'].includes(created.searchParams.get('state')),
        'valid real HTTP signup creates pending account; SMTP not asserted');
      check(!(await login(browser)), 'new pending signup cannot authenticate');
      const duplicate = redirect(await post({ ...valid, register_email: 'ci_d13_signup_duplicate@example.invalid' }));
      check(duplicate.searchParams.get('state') === 'duplicate', 'existing login rejected');
      process.stdout.write('PVDAK_D13_SIGNUP_HTTP_REGISTER_OK: ' + checks + '/' + checks +
        ' localhost HTTP/Chrome checks; no real mail or staging.\n');
      return;
    }
    const file = path.join(process.env.RUNNER_TEMP, 'pvdak-d13-signup-url');
    const url = new URL(fs.readFileSync(file, 'utf8').trim());
    check(url.origin === ORIGIN && url.searchParams.get('pvdak_verify') === '1' &&
      /^[A-Za-z0-9]{48}$/.test(url.searchParams.get('token') || ''),
      'intercepted verification URL belongs to loopback');
    const bad = new URL(url.href);
    bad.searchParams.set('token', 'A'.repeat(48));
    const invalid = await context.request.get(bad.href, { maxRedirects: 0 });
    check(redirect(invalid).searchParams.get('state') === 'invalid_token',
      'tampered token rejected');
    const confirmed = await context.request.get(url.href, { maxRedirects: 0 });
    check(redirect(confirmed).searchParams.get('state') === 'verified',
      'real HTTP-created account verifies its mailbox');
    check(/no-store/i.test(confirmed.headers()['cache-control'] || ''),
      'token response prevents shared caching');
    const replay = await context.request.get(url.href, { maxRedirects: 0 });
    check(redirect(replay).searchParams.get('state') === 'invalid_token',
      'one-use verification token replay rejected');
    check(await login(browser), 'verified self-declared lawyer logs in as client');
    process.stdout.write('PVDAK_D13_SIGNUP_HTTP_VERIFY_OK: ' + checks + '/' + checks +
      ' localhost HTTP/Chrome checks; SMTP and staging NOT_TESTED.\n');
  } finally {
    await context.close();
    await browser.close();
  }
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
