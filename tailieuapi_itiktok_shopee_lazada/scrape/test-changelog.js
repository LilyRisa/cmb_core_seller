/*
 * Debug Changelog tab - fresh page load
 */
const { chromium } = require('playwright');

// Test fresh load on changelog page directly
const CHANGELOG_URL = 'https://partner.tiktokshop.com/docv2/changelog';
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

  // First, check what the changelog page looks like
  console.log('Loading changelog...');
  await page.goto(CHANGELOG_URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  const links = await page.evaluate(() => {
    return [...document.querySelectorAll('a[href]')].map(a => ({
      href: a.getAttribute('href'),
      text: (a.textContent||'').trim().slice(0,50),
    })).filter(l => l.href && !l.href.startsWith('#') && (l.href.includes('/docv2/') || l.href.includes('partner.tiktok'))).slice(0, 30);
  });
  console.log('Links on changelog page:', JSON.stringify(links, null, 2));

  const html = await page.evaluate(() => document.body.innerHTML.slice(0, 3000));
  console.log('\nBody HTML preview:', html.slice(0, 2000));

  // Now load the main page and click Changelog tab
  console.log('\n\nLoading main page...');
  await page.goto('https://partner.tiktokshop.com/docv2/page/tts-developer-types', { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Click changelog tab
  await page.evaluate(() => { document.getElementById('arco-tabs-0-tab-5').click(); });
  await page.waitForTimeout(3000);

  // DON'T click anything - just check the sidebar and links
  const before = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/"]').length);
  const allLinks = await page.evaluate(() => {
    return [...document.querySelectorAll('a[href*="/docv2/"]')].map(a => ({
      href: a.getAttribute('href'),
      text: (a.textContent||'').trim().slice(0,50),
    })).slice(0, 20);
  });
  console.log(`\nChangelog tab - total /docv2/ links: ${before}`);
  console.log('Sample links:', JSON.stringify(allLinks, null, 2));

  await browser.close();
})();
