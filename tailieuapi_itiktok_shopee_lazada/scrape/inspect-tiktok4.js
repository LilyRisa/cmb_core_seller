/*
 * TikTok Shop Partner docs — API Reference deep inspection
 * Click API Reference tab, expand ALL menu groups, collect all links
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const OUT  = path.join(__dirname, 'tiktok-inspect4-result.json');

(async () => {
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'],
  });
  const ctx = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 900 },
    locale: 'en-US',
    extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' },
  });
  await ctx.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
  });

  const page = await ctx.newPage();
  console.log('Loading page...');
  await page.goto(BASE, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Click API Reference tab
  const apiTabEl = await page.$('#arco-tabs-0-tab-2');
  if (!apiTabEl) {
    console.log('API Reference tab not found!');
    await browser.close();
    return;
  }
  console.log('Clicking API Reference tab...');
  await apiTabEl.click({ timeout: 5000 });
  await page.waitForTimeout(3000);

  // Get the sidebar HTML before expanding
  const sidebarBefore = await page.evaluate(() => {
    const el = document.querySelector('#doc_left_menu');
    return el ? el.outerHTML : '';
  });
  console.log('Sidebar HTML before expanding (first 5000 chars):');
  console.log(sidebarBefore.slice(0, 5000));

  // Count links before expanding
  const linksBefore = await page.evaluate(() => {
    const links = [];
    const seen = new Set();
    document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
      const href = a.getAttribute('href');
      if (!seen.has(href)) {
        seen.add(href);
        links.push({ href, text: (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 80) });
      }
    });
    return links;
  });
  console.log(`\nLinks BEFORE expanding: ${linksBefore.length}`);

  // Try to expand dir items - click all collapsed groups
  console.log('\nExpanding menu groups...');
  for (let pass = 0; pass < 15; pass++) {
    const dirItems = await page.$$('[class*="side-menu-dir"]');
    let clicked = 0;
    for (const el of dirItems) {
      try {
        const isVisible = await el.isVisible();
        if (isVisible) {
          // Check if it has a "closed" state arrow (pointing down = closed)
          const cls = await el.evaluate(e => e.className || '');
          await el.click({ timeout: 2000 }).catch(() => {});
          clicked++;
        }
      } catch {}
    }
    await page.waitForTimeout(800);
    const linksNow = await page.evaluate(() => document.querySelectorAll('a[href*="/docv2/page/"]').length);
    console.log(`  Pass ${pass}: clicked ${clicked} dir items, links: ${linksNow}`);
    if (clicked === 0) break;
  }

  // Get links after expanding
  const linksAfter = await page.evaluate(() => {
    const links = [];
    const seen = new Set();
    document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
      const href = a.getAttribute('href');
      if (!seen.has(href)) {
        seen.add(href);
        links.push({ href, text: (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 80) });
      }
    });
    return links;
  });
  console.log(`\nLinks AFTER expanding: ${linksAfter.length}`);

  // Get full sidebar HTML after expanding
  const sidebarAfter = await page.evaluate(() => {
    const el = document.querySelector('#doc_left_menu');
    return el ? el.outerHTML : '';
  });

  // Extract all text from sidebar to see what categories are there
  const sidebarText = await page.evaluate(() => {
    const el = document.querySelector('#doc_left_menu');
    return el ? (el.textContent || '').replace(/\s+/g, ' ').trim() : '';
  });
  console.log('\nSidebar text content:');
  console.log(sidebarText.slice(0, 3000));

  const result = {
    linksBefore,
    linksAfter,
    sidebarBeforePreview: sidebarBefore.slice(0, 5000),
    sidebarAfterPreview: sidebarAfter.slice(0, 30000),
    sidebarText: sidebarText.slice(0, 5000),
  };
  fs.writeFileSync(OUT, JSON.stringify(result, null, 2));
  console.log('\nResults saved to', OUT);

  await browser.close();
})();
