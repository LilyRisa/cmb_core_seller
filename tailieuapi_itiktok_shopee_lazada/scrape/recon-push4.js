/*
 * Find all push mechanism page IDs by clicking through the left nav
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SEED = 'https://open.shopee.com/push-mechanism/5';
const OUT = path.resolve(__dirname, '../shopee');
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  const ctx = await browser.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => undefined }));
  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(45000);

  console.log('Loading push mechanism page...');
  await page.goto(SEED, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Expand ALL categories first
  for (let pass = 0; pass < 5; pass++) {
    const expandCount = await page.evaluate(() => {
      let clicked = 0;
      document.querySelectorAll('.arrow-icon, .category-item--nested > .arrow-icon, [class*="arrow-icon"]').forEach(el => {
        if (el.offsetParent !== null) {
          try { el.click(); clicked++; } catch(e) {}
        }
      });
      return clicked;
    });
    console.log(`Pass ${pass}: clicked ${expandCount} arrows`);
    await page.waitForTimeout(1000);
    if (!expandCount) break;
  }

  // Now collect all clickable items in the category list
  const navItems = await page.evaluate(() => {
    const items = [];
    const seen = new Set();

    // Get all leaf items (not category headers) from the nav
    document.querySelectorAll('.category-item__name, [class*="category-item"] span, [class*="leaf"]').forEach(el => {
      const text = el.textContent.trim().slice(0, 100);
      if (text && !seen.has(text) && text.length < 80) {
        seen.add(text);
        // Try to find parent clickable element
        const clickable = el.closest('[class*="item"]');
        items.push({
          text,
          className: clickable?.className?.slice(0, 100) || '',
        });
      }
    });

    return items;
  });

  console.log('\nNav items found:', navItems.length);
  navItems.forEach(item => console.log(' -', item.text, '|', item.className.slice(0, 50)));

  // Intercept navigation to capture URL changes
  const urls = new Set();
  urls.add(page.url());

  page.on('framenavigated', (frame) => {
    if (frame === page.mainFrame()) {
      urls.add(page.url());
      console.log('Navigated to:', page.url());
    }
  });

  // Also track API requests to find IDs
  const apiRequests = [];
  page.on('request', req => {
    const url = req.url();
    if (url.includes('push') && url.includes('api')) {
      apiRequests.push(url);
    }
  });

  // Click each nav item and capture the URL
  const discoveredPages = [];
  const categoryItems = await page.$$('.category-container__list [class*="leaf"], .category-container__list [class*="item--leaf"], .category-container__list [class*="selectable"]');
  console.log('\nLeaf/selectable items:', categoryItems.length);

  // Try clicking the first few items to see URL changes
  const allClickable = await page.$$('.category-item__name, .category-container__list span:not(.arrow-icon span)');
  console.log('Clickable name spans:', allClickable.length);

  for (const item of allClickable.slice(0, 5)) {
    try {
      const text = await item.textContent();
      if (text && text.trim().length < 80) {
        const prevUrl = page.url();
        await item.click({ timeout: 3000 }).catch(() => {});
        await page.waitForTimeout(1500);
        const newUrl = page.url();
        console.log(`Clicked "${text.trim()}", URL: ${newUrl}`);
        if (newUrl !== prevUrl) {
          discoveredPages.push({ text: text.trim(), url: newUrl });
        }
      }
    } catch(e) { /* skip */ }
  }

  console.log('\nDiscovered pages:', discoveredPages);
  console.log('\nAll visited URLs:', Array.from(urls));

  await browser.close();
})();
