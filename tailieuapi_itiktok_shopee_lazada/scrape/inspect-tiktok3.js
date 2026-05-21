/*
 * TikTok Shop Partner docs — Click each tab, expand menus, collect all URLs
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const OUT  = path.join(__dirname, 'tiktok-inspect3-result.json');

async function expandMenus(page) {
  // Click all collapsed group headers and expand arrows in the left sidebar
  for (let pass = 0; pass < 10; pass++) {
    let clicked = 0;
    // Click directory/folder items in side menu (items without href are collapsible groups)
    const dirItems = await page.$$('[class*="side-menu-dir"]');
    for (const el of dirItems) {
      try {
        if (await el.isVisible()) {
          await el.click({ timeout: 1000 }).catch(() => {});
          clicked++;
        }
      } catch {}
    }
    // Also click any expand icons
    const arrowIcons = await page.$$('[class*="side-menu-icon"]');
    for (const el of arrowIcons) {
      try {
        if (await el.isVisible()) {
          const parent = await el.$('..');
          if (parent) {
            const cls = await parent.evaluate(e => e.className || '');
            if (cls.includes('dir')) {
              await el.click({ timeout: 1000 }).catch(() => {});
              clicked++;
            }
          }
        }
      } catch {}
    }
    await page.waitForTimeout(500);
    if (clicked === 0) break;
  }
}

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

  // Get all tab title divs
  const tabTitles = await page.evaluate(() => {
    const titles = document.querySelectorAll('[class*="arco-tabs-header-title"]');
    return [...titles].map((t, i) => ({
      index: i,
      text: (t.textContent || '').trim().replace(/\s+/g, ' '),
      cls: (t.className || '').toString().slice(0, 100),
      id: t.id || '',
      role: t.getAttribute('role') || '',
    })).filter(t => t.role === 'tab');  // Only actual tab items
  });
  console.log('Tab items with role="tab":');
  console.log(JSON.stringify(tabTitles, null, 2));

  const allCategoriesData = [];

  // Click each tab by its ID
  for (const tab of tabTitles) {
    console.log(`\n=== Processing tab: "${tab.text}" (id: ${tab.id}) ===`);

    try {
      // Click the tab
      const tabEl = await page.$(`#${tab.id}`);
      if (!tabEl) {
        console.log(`  Tab element not found by id`);
        continue;
      }
      await tabEl.click({ timeout: 5000 });
      await page.waitForTimeout(2500);

      // Expand all collapsed menus
      await expandMenus(page);
      await page.waitForTimeout(1000);

      // Collect all doc links
      const links = await page.evaluate(() => {
        const links = [];
        const seen = new Set();
        document.querySelectorAll('a[href*="/docv2/page/"]').forEach(a => {
          const href = a.getAttribute('href');
          if (!seen.has(href)) {
            seen.add(href);
            links.push({
              href,
              text: (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 100),
            });
          }
        });
        return links;
      });

      console.log(`  Found ${links.length} doc links`);
      links.slice(0, 10).forEach(l => console.log(`    ${l.href} -> ${l.text}`));

      // Also capture sidebar HTML for this tab
      const sidebarHtml = await page.evaluate(() => {
        const el = document.querySelector('#doc_left_menu') || document.querySelector('[class*="side-menu-container"]');
        return el ? el.outerHTML.slice(0, 10000) : '';
      });

      allCategoriesData.push({
        tab: tab.text,
        tabId: tab.id,
        links,
        sidebarHtmlPreview: sidebarHtml.slice(0, 2000),
      });

    } catch (e) {
      console.error(`Error processing tab "${tab.text}":`, e.message);
      allCategoriesData.push({ tab: tab.text, tabId: tab.id, links: [], error: e.message });
    }
  }

  // Build unique URL set
  const allUrls = new Set();
  for (const cat of allCategoriesData) {
    for (const link of cat.links || []) {
      allUrls.add(link.href);
    }
  }
  console.log(`\n=== TOTAL UNIQUE DOC LINKS: ${allUrls.size} ===`);

  const result = { tabTitles, categoriesData: allCategoriesData, totalUniqueUrls: allUrls.size };
  fs.writeFileSync(OUT, JSON.stringify(result, null, 2));
  console.log('Results saved to', OUT);

  await browser.close();
})();
