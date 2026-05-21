/*
 * Debug: examine SVG paths in the API Reference side menu items
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

  // Examine all dir items
  const dirInfo = await page.evaluate(() => {
    const dirs = document.querySelectorAll('[class*="side-menu-dir"]');
    return [...dirs].map(dir => {
      const svgPaths = dir.querySelectorAll('svg path');
      const paths = [...svgPaths].map(p => p.getAttribute('d') || '').slice(0, 3);
      const text = (dir.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 50);
      const cls = (dir.className || '').toString().slice(0, 80);
      return { text, cls, paths };
    });
  });
  console.log('Dir items:', JSON.stringify(dirInfo, null, 2));

  // Also check sidebar structure
  const sidebarText = await page.evaluate(() => {
    const el = document.querySelector('#doc_left_menu');
    return el ? (el.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 500) : '';
  });
  console.log('\nSidebar text:', sidebarText);

  // Try clicking ALL dir items and see what happens
  console.log('\nClicking ALL dir items...');
  const n = await page.evaluate(() => {
    let n = 0;
    document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
      dir.click();
      n++;
    });
    return n;
  });
  await page.waitForTimeout(2000);
  const after1 = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
  console.log(`Clicked ${n} items, links now: ${after1}`);

  // Click again
  const n2 = await page.evaluate(() => {
    let n = 0;
    document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
      dir.click();
      n++;
    });
    return n;
  });
  await page.waitForTimeout(2000);
  const after2 = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
  console.log(`Clicked ${n2} items again, links now: ${after2}`);

  await browser.close();
})();
