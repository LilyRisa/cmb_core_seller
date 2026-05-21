/*
 * Test ALL tabs WITHOUT any expansion (just click tab, collect links)
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const TAB_NAMES = ['Partner Guide','Developer Guide','API Reference','Webhooks','Terms and Policies','Changelog','FAQs','API Testing Tool'];
// Different selectors - some tabs use /faqs/ links
const LINK_SELS = [
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]',
  'a[href*="/docv2/faqs/"]',
  'a[href*="/docv2/faqs/"]',
];

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => { Object.defineProperty(navigator, 'webdriver', { get: () => undefined }); });
  await ctx.route('**/*', route => {
    const t = route.request().resourceType();
    if (t === 'image' || t === 'media' || t === 'font') return route.abort();
    return route.continue();
  });
  const page = await ctx.newPage();

  await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  const urlMap = {};

  for (let i = 0; i < TAB_NAMES.length; i++) {
    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    const ok = await page.evaluate((id) => {
      const el = document.getElementById(`arco-tabs-0-tab-${id}`);
      if (!el) return false; el.click(); return true;
    }, i);
    if (!ok) { console.log(`Tab ${i} not found`); continue; }
    await page.waitForTimeout(2500);

    // For API Reference: click ALL dir items once
    if (i === 2) {
      const n = await page.evaluate(() => {
        let n = 0;
        document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => { try { dir.click(); n++; } catch {} });
        return n;
      });
      await page.waitForTimeout(1500);
      console.log(`  [API Reference] Expand all: clicked ${n}`);
    }
    // For all others: NO expansion

    const links = await page.evaluate((sel) => {
      const map = {};
      document.querySelectorAll(sel).forEach(a => {
        const href = a.getAttribute('href');
        if (!href) return;
        try {
          const u = new URL(href, location.href); u.hash = '';
          map[u.href] = (a.textContent||'').trim().replace(/\s+/g,' ').slice(0,80);
        } catch {}
      });
      return map;
    }, LINK_SELS[i]);

    const count = Object.keys(links).length;
    console.log(`Tab ${i} "${TAB_NAMES[i]}": ${count} links`);

    for (const [url, text] of Object.entries(links)) {
      if (!urlMap[url]) urlMap[url] = { text, category: TAB_NAMES[i] };
    }
  }

  console.log(`\nTotal unique URLs: ${Object.keys(urlMap).length}`);
  const byCategory = {};
  for (const [url, meta] of Object.entries(urlMap)) {
    byCategory[meta.category] = (byCategory[meta.category] || 0) + 1;
  }
  for (const [cat, count] of Object.entries(byCategory)) {
    console.log(`  ${cat}: ${count}`);
  }

  await browser.close();
})();
