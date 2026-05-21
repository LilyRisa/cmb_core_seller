/*
 * Deep recon for Push Mechanism - find all IDs/entries from the left navigation
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

  // Expand all collapsible sections
  console.log('Expanding collapsible sections...');
  for (let pass = 0; pass < 10; pass++) {
    const expandCount = await page.evaluate(() => {
      let clicked = 0;
      // Try clicking expand buttons/arrows in the left navigation
      document.querySelectorAll(
        '.collapsible-content-container .expand-btn, ' +
        '[class*="expand"], [class*="arrow"], [class*="toggle"], ' +
        '.push-mechanism-detail__category, ' +
        '[class*="category"] > [class*="icon"], [class*="category"] > svg'
      ).forEach(el => {
        if (el.offsetParent !== null) {  // visible check
          try { el.click(); clicked++; } catch(e) {}
        }
      });
      return clicked;
    });
    await page.waitForTimeout(800);
    if (!expandCount) break;
  }

  const info = await page.evaluate(() => {
    // Look at the full left navigation area
    const leftArea = document.querySelector('.push-mechanism-detail__wrapper, .push-mechanism-detail--flex');
    const leftHtml = leftArea ? leftArea.innerHTML.slice(0, 10000) : 'not found';

    // Find all IDs from window.__NUXT__ or page state
    let nuxtData = null;
    try {
      const nuxt = window.__NUXT__;
      if (nuxt && nuxt.data) {
        nuxtData = JSON.stringify(nuxt.data).slice(0, 5000);
      }
    } catch(e) {}

    // Get Vue data if available
    let vueData = null;
    try {
      const vueRoot = document.querySelector('#__nuxt')?.__vue__;
      if (vueRoot) {
        vueData = JSON.stringify(vueRoot.$data || {}).slice(0, 3000);
      }
    } catch(e) {}

    // Find category items in left nav
    const categories = [];
    document.querySelectorAll('.push-mechanism-detail__category, [class*="category"]').forEach(el => {
      const text = el.textContent.trim().slice(0, 100);
      const children = el.querySelectorAll('a, [class*="item"]');
      if (text && text.length < 80) categories.push(text);
    });

    // Find all push-related links and buttons
    const pushLinks = [];
    document.querySelectorAll('a, button, [onclick], [class*="item"][class*="click"], [class*="clickable"]').forEach(el => {
      const href = el.getAttribute('href') || '';
      const text = el.textContent.trim().slice(0, 80);
      if ((href.includes('push') || text.length < 60) && text) {
        pushLinks.push({ href, text, tag: el.tagName });
      }
    });

    // Look for numeric IDs in the page source
    const scripts = Array.from(document.querySelectorAll('script')).map(s => s.textContent);
    const idMatches = [];
    scripts.forEach(s => {
      const matches = s.match(/"id"\s*:\s*\d+/g) || [];
      idMatches.push(...matches.slice(0, 20));
    });

    return {
      leftHtml,
      nuxtData,
      vueData,
      categories,
      pushLinks: pushLinks.slice(0, 30),
      idMatches: [...new Set(idMatches)].slice(0, 30)
    };
  });

  console.log('\n=== LEFT AREA HTML (first 2000 chars) ===');
  console.log(info.leftHtml.slice(0, 2000));
  console.log('\n=== CATEGORIES ===');
  info.categories.forEach(c => console.log(' -', c));
  console.log('\n=== ID MATCHES ===');
  info.idMatches.forEach(m => console.log(' -', m));
  console.log('\n=== PUSH LINKS (first 20) ===');
  info.pushLinks.slice(0, 20).forEach(l => console.log(' -', l.tag, l.href, ':', l.text));

  fs.writeFileSync(path.join(OUT, '_push-recon3.json'), JSON.stringify(info, null, 2));
  await browser.close();
})();
