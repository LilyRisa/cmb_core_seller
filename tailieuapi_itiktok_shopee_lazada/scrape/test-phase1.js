/*
 * Test phase 1 only with corrected expansion strategy
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const TAB_NAMES = ['Partner Guide','Developer Guide','API Reference','Webhooks','Terms and Policies','Changelog','FAQs','API Testing Tool'];
const TAB_EXPAND = ['none','none','all','none','none','none','closed','closed'];

async function expandMenu(page, strategy) {
  if (strategy === 'none') return;
  if (strategy === 'all') {
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
        try { dir.click(); n++; } catch {}
      });
      return n;
    });
    await page.waitForTimeout(1500);
    return n;
  }
  if (strategy === 'closed') {
    for (let pass = 0; pass < 5; pass++) {
      const n = await page.evaluate(() => {
        let n = 0;
        document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
          const svgPath = dir.querySelector('svg path');
          if (!svgPath) return;
          const d = svgPath.getAttribute('d') || '';
          if (d.includes('10.3536')) { try { dir.click(); n++; } catch {} }
        });
        return n;
      });
      await page.waitForTimeout(700);
      if (n === 0) break;
    }
  }
}

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

  for (let tabIdx = 0; tabIdx < TAB_NAMES.length; tabIdx++) {
    const tabName = TAB_NAMES[tabIdx];
    const tabId = `arco-tabs-0-tab-${tabIdx}`;
    const strategy = TAB_EXPAND[tabIdx];

    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    const ok = await page.evaluate((id) => {
      const el = document.getElementById(id); if (!el) return false; el.click(); return true;
    }, tabId);

    if (!ok) { console.log(`Tab ${tabIdx} not found`); continue; }
    await page.waitForTimeout(2500);

    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    await expandMenu(page, strategy);
    await page.waitForTimeout(500);

    const links = await page.evaluate(() => {
      const map = {};
      document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
        const href = a.getAttribute('href');
        if (!href) return;
        try {
          const u = new URL(href, location.href); u.hash = '';
          map[u.href] = (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 80);
        } catch {}
      });
      return map;
    });
    const count = Object.keys(links).length;
    console.log(`Tab ${tabIdx} "${tabName}" (${strategy}): ${count} links`);

    for (const [url, text] of Object.entries(links)) {
      if (!urlMap[url]) urlMap[url] = { text, category: tabName };
    }
  }

  console.log(`\nTotal unique URLs: ${Object.keys(urlMap).length}`);
  const byCategory = {};
  for (const [url, meta] of Object.entries(urlMap)) {
    if (!byCategory[meta.category]) byCategory[meta.category] = 0;
    byCategory[meta.category]++;
  }
  for (const [cat, count] of Object.entries(byCategory)) {
    console.log(`  ${cat}: ${count}`);
  }

  await browser.close();
})();
