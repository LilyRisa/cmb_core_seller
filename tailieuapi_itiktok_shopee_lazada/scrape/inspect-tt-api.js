/* Inspect TikTok API-reference endpoint page structure */
const { chromium } = require('playwright');
(async () => {
  const url = process.argv[2] || 'https://partner.tiktokshop.com/docv2/page/ship-package-202309';
  const b = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await b.newContext({ userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36', viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3500);
  const info = await page.evaluate(() => {
    const cands = [];
    document.querySelectorAll('div,main,section,table').forEach((el) => {
      const len = (el.textContent || '').trim().length;
      if (len > 200) cands.push({ tag: el.tagName, id: el.id || '', cls: (el.className || '').toString().slice(0, 70), len });
    });
    cands.sort((a, b) => b.len - a.len);
    const mc = document.querySelector('[class*="markdown-container"]');
    return {
      title: document.title,
      mcLen: mc ? (mc.textContent || '').trim().length : -1,
      tableCount: document.querySelectorAll('table').length,
      arcoTableCount: document.querySelectorAll('[class*="arco-table"]').length,
      bodyLen: (document.body.textContent || '').trim().length,
      cands: cands.slice(0, 18),
    };
  });
  console.log(JSON.stringify(info, null, 2));
  await b.close();
})();
