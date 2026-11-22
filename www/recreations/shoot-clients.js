// Screenshots the Firefox add-on and J2ME MIDlet mock-ups in this directory.
// Usage: node shoot-clients.js   (Chromium via the pre-installed playwright)
const { chromium } = require('playwright');
const path = require('path');

const here = __dirname;
const out = path.resolve(here, '..', 'img', 'screenshots');
const pages = [
  'ff-statusbar-menu', 'ff-context-menu', 'ff-options', 'ff-upload-progress', 'ff-title-prompt', 'ff-about',
  'j2me-login', 'j2me-mainmenu', 'j2me-uploads', 'j2me-details', 'j2me-prefs', 'j2me-alert',
];

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1000, height: 800 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  for (const name of pages) {
    await page.goto('file://' + path.join(here, name + '.html'));
    await page.waitForTimeout(150);
    const el = await page.$('#shot');
    const file = path.join(out, name + '.png');
    await el.screenshot({ path: file });
    const box = await el.boundingBox();
    console.log(name + '.png', Math.round(box.width) + 'x' + Math.round(box.height), '(css px; file is 2x)');
  }
  await browser.close();
})();
