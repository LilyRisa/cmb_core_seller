/*
 * Debug FAQs and API Testing Tool tabs specifically
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

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

  // Click on each remaining tab
  for (const tabIdx of [5, 6, 7]) {
    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    const ok = await page.evaluate((id) => {
      const el = document.getElementById(`arco-tabs-0-tab-${id}`);
      if (!el) return false;
      el.click();
      return true;
    }, tabIdx);

    if (!ok) { console.log(`Tab ${tabIdx} not found`); continue; }
    await page.waitForTimeout(3000);

    // Check links and dir items before ANY expansion
    const before = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
    const dirInfo = await page.evaluate(() => {
      return [...document.querySelectorAll('[class*="side-menu-dir"]')].map(dir => {
        const d = (dir.querySelector('svg path')?.getAttribute('d') || '');
        return {
          text: (dir.textContent||'').trim().replace(/\s+/g,' ').slice(0,30),
          state: d.includes('10.3536') ? 'CLOSED' : d.includes('6.14645') ? 'OPEN' : 'UNKNOWN',
        };
      });
    });

    // Get the sidebar HTML
    const sidebarHtml = await page.evaluate(() => {
      const el = document.querySelector('#doc_left_menu');
      return el ? el.outerHTML.slice(0, 5000) : 'NOT FOUND';
    });

    console.log(`\n=== Tab ${tabIdx} ===`);
    console.log(`Links before: ${before}`);
    console.log(`Dir items: ${dirInfo.length}`);
    dirInfo.forEach(d => console.log(`  [${d.state}] ${d.text}`));
    console.log('Sidebar HTML preview:');
    console.log(sidebarHtml.slice(0, 2000));

    // Now try clicking ALL dir items (not just closed)
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
        try { dir.click(); n++; } catch {}
      });
      return n;
    });
    await page.waitForTimeout(2000);
    const after = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
    console.log(`After clicking ${n} dirs: ${after} links`);

    // Check if links are non-/docv2/ format
    const allLinks = await page.evaluate(() => {
      return [...document.querySelectorAll('a[href]')].map(a => ({
        href: a.getAttribute('href'),
        text: (a.textContent||'').trim().slice(0,50),
      })).filter(l => l.href && !l.href.startsWith('#')).slice(0, 20);
    });
    console.log('All links (sample):');
    allLinks.forEach(l => console.log(`  ${l.href} -> ${l.text}`));
  }

  await browser.close();
})();
