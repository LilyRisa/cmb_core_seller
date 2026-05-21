/*
 * TikTok Shop Partner docs — Deep tab/category inspector
 * Clicks each tab, expands menus, collects all doc URLs per category
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const OUT  = path.join(__dirname, 'tiktok-inspect2-result.json');

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

  // First, let's understand the tab structure
  const tabsInfo = await page.evaluate(() => {
    // Find the tabs header
    const tabHeaders = document.querySelectorAll('[class*="arco-tabs-header"]');
    const results = [];
    tabHeaders.forEach(th => {
      const tabs = th.querySelectorAll('[class*="arco-tabs-tab"]');
      const tabData = [];
      tabs.forEach(t => {
        tabData.push({
          text: (t.textContent || '').trim(),
          cls: (t.className || '').toString().slice(0, 100),
          isActive: t.className.includes('active'),
        });
      });
      results.push({
        headerCls: (th.className || '').toString().slice(0, 100),
        tabs: tabData,
      });
    });
    return results;
  });
  console.log('Tab structure:');
  console.log(JSON.stringify(tabsInfo, null, 2));

  // Now get the full HTML of the left sidebar to understand its structure
  const sidebarHtml = await page.evaluate(() => {
    const selectors = [
      '[class*="side-menu"]',
      '[class*="sidebar"]',
      '[class*="left-nav"]',
      '[class*="left-menu"]',
      'aside',
    ];
    for (const sel of selectors) {
      const el = document.querySelector(sel);
      if (el) return { sel, html: el.outerHTML.slice(0, 8000) };
    }
    return { sel: null, html: '' };
  });
  console.log('\nSidebar HTML (first 5000):');
  console.log(sidebarHtml.sel, sidebarHtml.html.slice(0, 3000));

  // Find all the tab elements and click each one
  const tabTexts = await page.evaluate(() => {
    const tabs = document.querySelectorAll('[class*="arco-tabs-tab"]');
    return [...tabs].map(t => ({
      text: (t.textContent || '').trim(),
      cls: (t.className || '').toString().slice(0, 120),
    }));
  });
  console.log('\nAll tab elements:', JSON.stringify(tabTexts, null, 2));

  // Let's also check what's at the top left - the doc set selector
  const topLeftHtml = await page.evaluate(() => {
    // Try to get area to the top-left of the page
    const layout = document.querySelector('[class*="layout"]') || document.querySelector('[class*="doc-layout"]');
    if (layout) return { found: true, html: layout.outerHTML.slice(0, 5000) };
    return { found: false, html: '' };
  });
  console.log('\nLayout container HTML:');
  console.log(topLeftHtml.html.slice(0, 3000));

  // Collect all links before clicking anything
  const initialLinks = await page.evaluate(() => {
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
  console.log(`\nInitial doc links: ${initialLinks.length}`);

  // Now try clicking each tab to see what links appear
  const categoriesData = [];

  // Get all tab selectors - including parent nav tabs
  const allTabItems = await page.$$('[class*="arco-tabs-tab"]');
  console.log(`\nFound ${allTabItems.length} tab items`);

  for (let i = 0; i < allTabItems.length; i++) {
    try {
      const tab = allTabItems[i];
      const tabText = await tab.evaluate(el => (el.textContent || '').trim());
      console.log(`\nClicking tab ${i}: "${tabText}"`);

      await tab.click({ timeout: 5000 }).catch(e => console.log('  click err:', e.message));
      await page.waitForTimeout(2000);

      // Expand any collapsed items in the menu
      for (let pass = 0; pass < 5; pass++) {
        const collapsed = await page.$$('[class*="arco-menu-item-group"]');
        let clicked = 0;
        for (const c of collapsed) {
          const isVisible = await c.isVisible().catch(() => false);
          if (isVisible) {
            await c.click({ timeout: 1000 }).catch(() => {});
            clicked++;
          }
        }
        // Also try clicking expand arrows
        const arrows = await page.$$('[class*="arco-menu-icon-suffix"]');
        for (const arrow of arrows) {
          const isVisible = await arrow.isVisible().catch(() => false);
          if (isVisible) {
            await arrow.click({ timeout: 1000 }).catch(() => {});
            clicked++;
          }
        }
        await page.waitForTimeout(500);
        if (clicked === 0) break;
      }

      const links = await page.evaluate(() => {
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

      console.log(`  Found ${links.length} doc links in tab "${tabText}"`);
      categoriesData.push({ tab: tabText, links });
    } catch (e) {
      console.error(`Error with tab ${i}:`, e.message);
    }
  }

  const result = {
    tabsInfo,
    sidebarHtml,
    tabTexts,
    initialLinks,
    categoriesData,
  };
  fs.writeFileSync(OUT, JSON.stringify(result, null, 2));
  console.log('\nResults saved to', OUT);

  await browser.close();
})();
