#!/usr/bin/env node
/*
 * Screenshots the static reconstructions in this directory with Playwright/Chromium.
 * Run: node build-web.js && node shoot-web.js
 * Output: ../img/screenshots/web-*.png (2x device scale)
 */
const path = require('path');
const { chromium } = require('playwright');

const here = (f) => 'file://' + path.join(__dirname, f);
const outDir = path.join(__dirname, '..', 'img', 'screenshots');

const shots = [
  { file: 'web-login.html',        png: 'web-login.png',            vp: { width: 1024, height: 768 }, full: true },
  { file: 'web-uploads.html',      png: 'web-uploads.png',          vp: { width: 1024, height: 900 }, full: true },
  { file: 'web-register.html',     png: 'web-register.png',         vp: { width: 1024, height: 900 }, full: true },
  { file: 'web-welcome.html',      png: 'web-welcome-firstrun.png', vp: { width: 1024, height: 720 }, full: false },
  { file: 'web-get-software.html', png: 'web-get-software.png',     vp: { width: 1024, height: 640 }, full: false },
  { file: 'web-iphone.html',       png: 'web-iphone.png',           vp: { width: 320, height: 480 },  full: false },
  { file: 'web-wap.html',          png: 'web-wap.png',              vp: { width: 240, height: 320 },  full: false },
];

(async () => {
  const browser = await chromium.launch();
  for (const s of shots) {
    const ctx = await browser.newContext({ viewport: s.vp, deviceScaleFactor: 2 });
    const page = await ctx.newPage();
    await page.goto(here(s.file));
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: path.join(outDir, s.png), fullPage: s.full });
    console.log('wrote', s.png);
    await ctx.close();
  }
  await browser.close();
})();
