/* Quick DOM inspector: node inspect.js <url> <selector> [clickSelector] */
const { chromium } = require('playwright');
(async () => {
  const url = process.argv[2];
  const selector = process.argv[3];
  const clickSelector = process.argv[4];
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 900 },
  });
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(2000);

  const before = await page.evaluate(() => document.querySelectorAll('a[href]').length);
  console.log('anchors before:', before);

  if (clickSelector) {
    for (let i = 0; i < 6; i++) {
      const els = await page.$$(clickSelector);
      let n = 0;
      for (const el of els) { try { if (await el.isVisible()) { await el.click({ timeout: 1000 }).catch(()=>{}); n++; } } catch {} }
      await page.waitForTimeout(600);
      console.log(`click pass ${i}: clicked ${n} (${clickSelector})`);
      if (n === 0) break;
    }
    const after = await page.evaluate(() => document.querySelectorAll('a[href]').length);
    console.log('anchors after expand:', after);
  }

  const dump = await page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return { found: false };
    return { found: true, outer: el.outerHTML.slice(0, 6000) };
  }, selector);
  console.log('SELECTOR', selector, 'found:', dump.found);
  if (dump.found) console.log(dump.outer);
  await browser.close();
})();
