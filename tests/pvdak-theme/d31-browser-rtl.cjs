#!/usr/bin/env node
'use strict';
/**
 * D31 RTL browser regression on disposable localhost WordPress only.
 * No production credentials, requests, screenshots or external navigation.
 */
const assert = require('node:assert/strict');
const { chromium } = require('playwright');

const HOST = 'http://127.0.0.1:8080';
const parent = process.env.PVDAK_CI_D31_PARENT_ID || '';
const login = process.env.PVDAK_CI_D31_LOGIN_ID || '';
if (process.env.PVDAK_CI_D31_ISOLATED !== '1' ||
    !/^[1-9][0-9]*$/.test(parent) || !/^[1-9][0-9]*$/.test(login)) {
  process.stderr.write('D31_BROWSER_ABORT: disposable CI fixture required\n');
  process.exit(2);
}
let checks = 0;
function check(value, description) {
  assert.ok(value, 'D31_BROWSER_FAIL: ' + description);
  checks++;
}
async function main() {
  const browser = await chromium.launch({
    channel: 'chrome', headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage']
  });
  try {
    for (const viewport of [
      { name: 'mobile', width: 390, height: 844 },
      { name: 'desktop', width: 1366, height: 900 }
    ]) {
      const context = await browser.newContext({
        viewport: { width: viewport.width, height: viewport.height },
        locale: 'fa-IR', javaScriptEnabled: true
      });
      const page = await context.newPage();
      try {
        const response = await page.goto(HOST + '/?page_id=' + parent, {
          waitUntil: 'domcontentloaded', timeout: 30000
        });
        check(response && response.status() === 200, viewport.name + ' legal index HTTP 200');
        check(await page.locator('html').getAttribute('dir') === 'rtl',
          viewport.name + ' HTML explicitly RTL');
        check(await page.locator('main#main-content').isVisible(),
          viewport.name + ' real legal index main is visible');
        check(await page.locator('a.pvdak-home-topic').count() === 24,
          viewport.name + ' first window has 24 topic links');
        const geometry = await page.evaluate(() => ({
          viewport: document.documentElement.clientWidth,
          scroll: document.documentElement.scrollWidth,
          bodyDirection: getComputedStyle(document.body).direction
        }));
        check(geometry.bodyDirection === 'rtl', viewport.name + ' computed RTL direction');
        check(geometry.scroll <= geometry.viewport + 2,
          viewport.name + ' no document horizontal overflow (' +
          geometry.scroll + ' > ' + geometry.viewport + ')');
        if (viewport.name === 'mobile') {
          const summary = page.locator('details.pvdak-mobile-menu > summary');
          check(await summary.isVisible(), 'mobile navigation trigger visible');
          await summary.click();
          await page.waitForFunction(() =>
            document.querySelector('details.pvdak-mobile-menu').open);
          check(await page.locator('#pvdak-mobile-drawer').isVisible(),
            'mobile drawer opens with JavaScript enabled');
          await page.keyboard.press('Escape');
          await page.waitForFunction(() =>
            !document.querySelector('details.pvdak-mobile-menu').open);
          check(!(await page.locator('details.pvdak-mobile-menu').evaluate(el => el.open)),
            'Escape closes mobile drawer');
        } else {
          check(await page.locator('nav.primary-navigation').isVisible(),
            'desktop navigation visible');
        }
        await page.locator('a[rel="next"]').click();
        await page.waitForLoadState('domcontentloaded');
        check(await page.locator('a.pvdak-home-topic').count() === 24,
          viewport.name + ' second window still has 24 links');
        check((await page.locator('main#main-content').innerText()).includes('Topic 025'),
          viewport.name + ' browser pagination advanced to Topic 025');
        check((await page.locator('meta[name="robots"]').first().getAttribute('content') || '')
          .includes('noindex'), viewport.name + ' cursor URL is noindex');
        const loginResponse = await page.goto(HOST + '/?page_id=' + login, {
          waitUntil: 'domcontentloaded', timeout: 30000
        });
        check(loginResponse && loginResponse.status() === 200,
          viewport.name + ' private login page HTTP 200');
        check(await page.locator('main.pvdak-login-page').isVisible(),
          viewport.name + ' login template renders in browser');
        check((await page.locator('html').getAttribute('dir')) === 'rtl',
          viewport.name + ' login retains RTL direction');
      } finally {
        await context.close();
      }
    }
  } finally {
    await browser.close();
  }
  process.stdout.write('PVDAK_D31_BROWSER_RTL_OK: ' + checks + '/' + checks +
    ' real headless Chrome checks (mobile + desktop), isolated localhost only; CDN/production NOT_TESTED.\n');
}
main().catch(error => {
  process.stderr.write(String(error.stack || error) + '\n');
  process.exitCode = 1;
});
