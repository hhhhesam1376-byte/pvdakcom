#!/usr/bin/env node
'use strict';
/**
 * D13 intercepted-email verification through real localhost HTTP and Chrome.
 * A synthetic one-use link lives only in RUNNER_TEMP; never printed.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const ORIGIN = 'http://127.0.0.1:8080';
const LOGIN = 'ci_d13_mail_pending';
const PASSWORD = 'pvdak_ci_d13_mail_password_only_2026';
if (process.env.GITHUB_ACTIONS !== 'true' ||
    process.env.PVDAK_CI_D13_MAIL_ISOLATED !== '1' ||
    !process.env.RUNNER_TEMP) {
  process.stderr.write('D13_MAIL_HTTP_ABORT: isolated CI required\n');
  process.exit(2);
}
const file = path.join(process.env.RUNNER_TEMP, 'pvdak-d13-mail-url');
const url = new URL(fs.readFileSync(file, 'utf8').trim());
if (url.origin !== ORIGIN || url.searchParams.get('pvdak_verify') !== '1' ||
    !/^[A-Za-z0-9]{48}$/.test(url.searchParams.get('token') || '')) {
  throw new Error('D13_MAIL_HTTP_ABORT: invalid loopback verification link');
}
let checks = 0;
function check(value, label) {
  assert.ok(value, 'D13_MAIL_HTTP_FAIL: ' + label);
  checks++;
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
async function verify(context, link) {
  const response = await context.request.get(link, { maxRedirects: 0 });
  check(new URL(response.url()).origin === ORIGIN, 'verification request stays loopback');
  check([301, 302, 303].includes(response.status()), 'verification redirects instead of exposing token');
  const location = response.headers().location || '';
  const redirect = new URL(location, ORIGIN);
  check(redirect.origin === ORIGIN && !redirect.searchParams.has('token') &&
    !redirect.searchParams.has('uid'), 'redirect strips token and uid');
  return { response, redirect };
}
async function main() {
  const browser = await chromium.launch({
    channel: 'chrome', headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  const context = await browser.newContext();
  try {
    check(!(await login(browser)), 'pending account denied WordPress login');
    const tampered = new URL(url.href);
    tampered.searchParams.set('token', 'A'.repeat(48));
    const bad = await verify(context, tampered.href);
    check(bad.redirect.searchParams.get('state') === 'invalid_token',
      'wrong token rejected without activating account');
    const good = await verify(context, url.href);
    check(good.redirect.searchParams.get('state') === 'verified',
      'issued link activates only matching pending mailbox');
    check(/no-store/i.test(good.response.headers()['cache-control'] || ''),
      'verification HTTP response is no-store');
    const replay = await verify(context, url.href);
    check(replay.redirect.searchParams.get('state') === 'invalid_token',
      'one-use verification link replay rejected');
    check(await login(browser), 'verified client can log in with original password');
    process.stdout.write('PVDAK_D13_MAIL_HTTP_OK: ' + checks + '/' + checks +
      ' Chrome/HTTP checks; intercepted CI mail only; real SMTP/staging NOT_TESTED.\n');
  } finally {
    await context.close();
    await browser.close();
  }
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
