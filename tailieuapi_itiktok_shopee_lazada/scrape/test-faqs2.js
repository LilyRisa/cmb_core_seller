/*
 * Fresh test: load on FAQs URL, check links
 */
const { chromium } = require('playwright');

const FA_URL = 'https://partner.tiktokshop.com/docv2/faqs/7174708677826370049';
const BASE = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
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

  // Load a FAQ page directly
  console.log('Loading FAQ page directly...');
  await page.goto(FA_URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  const title = await page.evaluate(() => document.title);
  const links = await page.evaluate(() => {
    return [...document.querySelectorAll('a[href*="/docv2/faqs/"]')].map(a => ({
      href: a.getAttribute('href'),
      text: (a.textContent||'').trim().slice(0,60),
    })).slice(0, 20);
  });
  console.log('Title:', title);
  console.log('FAQ links:', JSON.stringify(links, null, 2));

  // Check content
  const content = await page.evaluate(() => {
    const el = document.querySelector('[class*="markdown-container"]') ||
               document.querySelector('main') ||
               document.body;
    return (el.textContent || '').trim().slice(0, 500);
  });
  console.log('\nContent preview:', content);

  // Now load main page and click FAQ tab
  console.log('\n\nLoading main page, clicking FAQ tab...');
  await page.goto(BASE, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Click FAQs tab
  await page.evaluate(() => { document.getElementById('arco-tabs-0-tab-6').click(); });
  await page.waitForTimeout(3000);

  const faqLinksCount = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/faqs/"]').length);
  console.log(`FAQ links in sidebar (before expand): ${faqLinksCount}`);

  // Click all CLOSED dirs
  for (let pass = 0; pass < 5; pass++) {
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
        const d = (dir.querySelector('svg path')?.getAttribute('d') || '');
        if (d.includes('10.3536')) { try { dir.click(); n++; } catch {} }
      });
      return n;
    });
    await page.waitForTimeout(800);
    const nowCount = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/faqs/"]').length);
    console.log(`Pass ${pass}: clicked ${n} closed dirs, links now: ${nowCount}`);
    if (n === 0) break;
  }

  // Get all /docv2/ links
  const allDocLinks = await page.evaluate(() => {
    return [...document.querySelectorAll('a[href*="/docv2/"]')].map(a => ({
      href: a.getAttribute('href'),
      text: (a.textContent||'').trim().slice(0,50),
    })).filter(l => !l.href.includes('/docv2/page/')).slice(0, 20);
  });
  console.log('\nNon-/page/ docv2 links:', JSON.stringify(allDocLinks, null, 2));

  await browser.close();
})();
