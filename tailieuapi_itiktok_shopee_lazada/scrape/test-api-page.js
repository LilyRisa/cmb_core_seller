/*
 * Test: load API Reference endpoint page and examine content structure
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

  // Check what containers exist and their text length
  const containers = await page.evaluate(() => {
    const sels = [
      '[class*="markdown-container"]',
      '#doc_scroll_container',
      '#scrollIntersectionCenter',
      'main',
      'article',
      '[class*="doc-content"]',
      '[class*="api-"]',
      '[class*="content"]',
    ];
    return sels.map(sel => {
      const el = document.querySelector(sel);
      return {
        sel,
        found: !!el,
        len: el ? (el.textContent || '').trim().length : 0,
        previewHtml: el ? el.innerHTML.slice(0, 500) : '',
      };
    });
  });

  containers.forEach(c => {
    if (c.found) {
      console.log(`${c.sel}: found, len=${c.len}`);
    }
  });

  // Get the longest content area
  const textLen = await page.evaluate(() => {
    let maxLen = 0, maxSel = '', maxText = '';
    document.querySelectorAll('div, section, article, main').forEach(el => {
      const len = (el.textContent || '').trim().length;
      if (len > maxLen) {
        maxLen = len;
        maxSel = el.tagName + '.' + (el.className||'').toString().slice(0, 50);
        maxText = (el.textContent||'').trim().slice(0, 300);
      }
    });
    return { maxLen, maxSel, maxText };
  });
  console.log('\nLongest content div:', textLen.maxSel, 'len:', textLen.maxLen);
  console.log('Preview:', textLen.maxText.slice(0, 300));

  // Check the actual page body text
  const bodyText = await page.evaluate(() => (document.body.textContent || '').trim().slice(0, 2000));
  console.log('\nBody text preview (2000 chars):');
  console.log(bodyText);

  await browser.close();
})();
