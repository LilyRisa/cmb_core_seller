/*
 * Test: click each tab WITHOUT any expansion, check link count
 * Then try with expansion to see what works
 */
const { chromium } = require('playwright');

const BASE_URL = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const TAB_NAMES = ['Partner Guide','Developer Guide','API Reference','Webhooks','Terms and Policies','Changelog','FAQs','API Testing Tool'];

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

  for (let tabIdx = 0; tabIdx < TAB_NAMES.length; tabIdx++) {
    const tabName = TAB_NAMES[tabIdx];
    const tabId = `arco-tabs-0-tab-${tabIdx}`;

    // Dismiss modals
    await page.evaluate(() => {
      document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
    });

    // Click tab
    const ok = await page.evaluate((id) => {
      const el = document.getElementById(id);
      if (!el) return false;
      el.click();
      return true;
    }, tabId);

    if (!ok) { console.log(`Tab ${tabIdx} not found`); continue; }
    await page.waitForTimeout(2500);

    // Count BEFORE any expansion
    const before = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);

    // Check dir items
    const dirInfo = await page.evaluate(() => {
      const dirs = document.querySelectorAll('[class*="side-menu-dir"]');
      return [...dirs].map(dir => {
        const svgPaths = [...dir.querySelectorAll('svg path')].map(p => {
          const d = p.getAttribute('d') || '';
          // 10.3536 = up/closed, 6.14645 = down/open
          return d.includes('10.3536') ? 'CLOSED' : d.includes('6.14645') ? 'OPEN' : 'UNKNOWN';
        });
        return {
          text: (dir.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 30),
          state: svgPaths[0] || 'UNKNOWN',
        };
      });
    });

    const closedCount = dirInfo.filter(d => d.state === 'CLOSED').length;
    const openCount = dirInfo.filter(d => d.state === 'OPEN').length;
    console.log(`Tab ${tabIdx} "${tabName}": ${before} links, dirs: ${dirInfo.length} (${closedCount} closed, ${openCount} open)`);
    if (dirInfo.length > 0) {
      dirInfo.slice(0, 5).forEach(d => console.log(`    [${d.state}] ${d.text}`));
    }
  }

  await browser.close();
})();
