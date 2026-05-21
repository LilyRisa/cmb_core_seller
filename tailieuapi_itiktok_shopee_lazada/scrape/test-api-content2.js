/*
 * Find where the actual API params are in the DOM
 */
const { chromium } = require('playwright');

const URL = 'https://partner.tiktokshop.com/docv2/page/get-order-detail-202507';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => { Object.defineProperty(navigator, 'webdriver', { get: () => undefined }); });
  const page = await ctx.newPage();

  await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Get the raw text of the center section to see actual content
  const centerText = await page.evaluate(() => {
    const center = document.querySelector('[class*="scroll-intersection-center"]');
    return center ? (center.textContent || '').trim() : 'NOT FOUND';
  });
  console.log('Center section text (first 3000):');
  console.log(centerText.slice(0, 3000));

  // Raw HTML of the center section (unmodified)
  const centerHtml = await page.evaluate(() => {
    const center = document.querySelector('[class*="scroll-intersection-center"]');
    return center ? center.innerHTML.slice(0, 10000) : 'NOT FOUND';
  });
  console.log('\n\nRaw center HTML (first 8000):');
  console.log(centerHtml.slice(0, 8000));

  await browser.close();
})();
