/*
 * Quick test: run just phase 1 (URL collection) + first 3 pages
 */
const fs   = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const BASE_URL = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const TAB_NAMES = ['Partner Guide','Developer Guide','API Reference','Webhooks','Terms and Policies','Changelog','FAQs','API Testing Tool'];

async function collectLinks(page) {
  return await page.evaluate(() => {
    const map = {};
    document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
      const href = a.getAttribute('href');
      if (!href) return;
      try {
        const u = new URL(href, location.href);
        u.hash = '';
        map[u.href] = (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 80);
      } catch {}
    });
    return map;
  });
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

    console.log(`\nTab ${tabIdx}: "${tabName}"`);

    // Dismiss modals
    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    // Click tab via JS
    const ok = await page.evaluate((id) => {
      const el = document.getElementById(id);
      if (!el) return false;
      el.click();
      return true;
    }, tabId);

    if (!ok) { console.log('  Tab not found'); continue; }
    await page.waitForTimeout(2500);

    // Expand
    if (tabIdx === 2) {
      // API Reference: single pass
      const n = await page.evaluate(() => {
        let n = 0;
        document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => { try { dir.click(); n++; } catch {} });
        return n;
      });
      await page.waitForTimeout(1500);
      console.log(`  Clicked ${n} dir items`);
    } else {
      // Growth-based
      let lastCount = 0;
      for (let pass = 0; pass < 5; pass++) {
        await page.evaluate(() => {
          document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => { try { dir.click(); } catch {} });
        });
        await page.waitForTimeout(700);
        const now = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
        if (now > lastCount) lastCount = now; else break;
      }
    }

    const links = await collectLinks(page);
    const count = Object.keys(links).length;
    console.log(`  Links: ${count}`);

    for (const [url, text] of Object.entries(links)) {
      if (!urlMap[url]) urlMap[url] = { text, category: tabName };
    }
  }

  console.log(`\nTotal unique URLs: ${Object.keys(urlMap).length}`);
  console.log('\nFirst 20 URLs:');
  Object.entries(urlMap).slice(0, 20).forEach(([url, meta]) => {
    console.log(`  [${meta.category}] ${url.split('/').pop()} -> "${meta.text.slice(0, 50)}"`);
  });

  await browser.close();
  console.log('\nPhase 1 test complete!');
})();
