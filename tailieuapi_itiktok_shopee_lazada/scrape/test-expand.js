/*
 * Quick test: click API Reference tab, expand ONLY closed items
 */
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 900 },
  });
  await ctx.addInitScript(() => { Object.defineProperty(navigator, 'webdriver', { get: () => undefined }); });
  const page = await ctx.newPage();
  await page.goto('https://partner.tiktokshop.com/docv2/page/tts-developer-types', { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Dismiss any modal
  await page.evaluate(() => {
    document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { el.style.display = 'none'; });
  });

  // Click API Reference tab via JS
  await page.evaluate(() => { document.getElementById('arco-tabs-0-tab-2').click(); });
  await page.waitForTimeout(3000);

  const before = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
  console.log('Before expand:', before);

  // Expand ONLY closed items (arrow pointing UP = closed)
  for (let pass = 0; pass < 25; pass++) {
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
        const svgPath = dir.querySelector('svg path');
        if (!svgPath) return;
        const d = svgPath.getAttribute('d') || '';
        // Up-pointing chevron (closed): "M3.64645 10.3536"
        // Down-pointing chevron (open): "M3.64645 6.14645"
        if (d.includes('10.3536')) {
          dir.click();
          n++;
        }
      });
      return n;
    });
    await page.waitForTimeout(700);
    const now = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
    console.log('Pass', pass, ': clicked', n, 'closed items, links now:', now);
    if (n === 0) break;
  }

  const after = await page.evaluate(() => {
    const links = [];
    document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
      const href = a.getAttribute('href');
      if (href) links.push({ href, text: (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 60) });
    });
    return links;
  });
  console.log('After expand:', after.length, 'unique links');

  await browser.close();
})();
