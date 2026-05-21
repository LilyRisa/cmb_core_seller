/*
 * Shopee API Reference — module list harvester.
 *
 * Opens the API Reference page and extracts the left-side module/category list.
 * We navigate to the table of contents page and expand all modules to get
 * a complete listing.
 *
 * Output: shopee/API-REFERENCE-MODULES.md
 *
 * Usage: node crawl-shopee-api-modules.js
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

// Use the ToC page which should list all modules
const SEEDS = [
  'https://open.shopee.com/documents/v2/v2.ams.get_open_campaign_added_product?module=127&type=1',
  'https://open.shopee.com/documents?module=87&type=2&id=58&version=2',
];
const OUT = path.resolve(__dirname, '../shopee');
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-' });
td.use(gfm.gfm);

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => undefined }));
  await ctx.route('**/*', (r) => {
    const t = r.request().resourceType();
    if (t === 'image' || t === 'media' || t === 'font') return r.abort();
    return r.continue();
  });
  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(60000);

  // Try the first seed
  console.log('Loading API Reference page...');
  await page.goto(SEEDS[0], { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Expand all categories in the left sidebar
  console.log('Expanding sidebar categories...');
  for (let pass = 0; pass < 8; pass++) {
    const clicked = await page.evaluate(() => {
      let n = 0;
      // Click collapsed items
      document.querySelectorAll(
        '.category-folded-icon, [class*="folded-icon"], [class*="expand-icon"], ' +
        '[class*="collapsed"], .category-item-container:not(.expanded) .category-folded-icon'
      ).forEach(el => {
        if (el.offsetParent !== null) {
          try { el.click(); n++; } catch(e) {}
        }
      });
      return n;
    });
    await page.waitForTimeout(1000);
    console.log(`Pass ${pass}: expanded ${clicked} items`);
    if (!clicked) break;
  }

  // Extract all module/API items from the left nav
  const allModules = await page.evaluate(() => {
    const modules = [];
    const seen = new Set();

    // Category items (module headers)
    document.querySelectorAll('.category-item-container, [class*="category-item"]').forEach(el => {
      const nameEl = el.querySelector('.category-item-name-container, [class*="name-container"]');
      if (!nameEl) return;
      const name = nameEl.textContent.trim();
      if (!name || seen.has(name)) return;
      seen.add(name);

      const apis = [];
      el.querySelectorAll('.api-reference-item, [class*="api-reference-item"]').forEach(apiEl => {
        const apiName = (apiEl.querySelector('.api-reference-item-name-container, [class*="item-name"]')?.textContent || apiEl.textContent).trim();
        const apiLink = apiEl.querySelector('a');
        const href = apiLink?.getAttribute('href') || '';
        if (apiName && !seen.has(apiName)) {
          seen.add(apiName);
          apis.push({ name: apiName, href });
        }
      });

      modules.push({ name, apis });
    });

    return modules;
  });

  console.log(`Found ${allModules.length} modules`);
  allModules.forEach(m => console.log(` - ${m.name}: ${m.apis.length} APIs`));

  // Also try to get a flat list of all API items
  const allApiItems = await page.evaluate(() => {
    const items = [];
    const seen = new Set();
    document.querySelectorAll('.api-reference-item').forEach(el => {
      const name = (el.querySelector('.api-reference-item-name-container')?.textContent || el.textContent).trim();
      const href = el.querySelector('a')?.getAttribute('href') || '';
      if (name && !seen.has(name)) {
        seen.add(name);
        items.push({ name, href });
      }
    });
    return items;
  });

  // Get V2.0 Table of Contents page if we can find it
  const tocLink = await page.evaluate(() => {
    const links = Array.from(document.querySelectorAll('a'));
    for (const link of links) {
      const href = link.getAttribute('href') || '';
      const text = link.textContent.trim();
      if (text.includes('Tables of Contents') || text.includes('Table of Content') || href.includes('type=2')) {
        return href;
      }
    }
    return null;
  });

  let tocContent = '';
  if (tocLink) {
    console.log('Found ToC link:', tocLink);
    const tocUrl = tocLink.startsWith('http') ? tocLink : `https://open.shopee.com${tocLink}`;
    await page.goto(tocUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    await page.waitForTimeout(2000);

    const tocData = await page.evaluate(() => {
      // Get the main content area
      const main = document.querySelector('.api-reference-detail-container, .developer-guide-body__main-content, main, [class*="content"]');
      if (main) {
        const clone = main.cloneNode(true);
        clone.querySelectorAll('script,style,noscript').forEach(x => x.remove());
        clone.querySelectorAll('*').forEach(el => {
          Array.from(el.attributes).forEach(a => {
            if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name);
          });
        });
        return { html: clone.innerHTML, len: (main.textContent || '').trim().length };
      }
      return { html: '', len: 0 };
    });

    if (tocData.html) {
      tocContent = td.turndown(tocData.html).replace(/\n{3,}/g, '\n\n').trim();
    }
  }

  // Build markdown content
  const lines = [
    '# Shopee Open Platform — API Reference Module Index',
    '',
    '> Source: https://open.shopee.com/documents/v2/',
    `> Scraped: ${new Date().toISOString()}`,
    '',
    '---',
    '',
    'The Shopee Open Platform API Reference (v2) provides hundreds of API methods organized into modules.',
    'Full documentation is available at: https://open.shopee.com/documents/v2/',
    '',
    `Total modules found: ${allModules.length}`,
    `Total API methods visible: ${allApiItems.length}`,
    '',
  ];

  if (allModules.length > 0) {
    lines.push('## Modules', '');
    for (const mod of allModules) {
      lines.push(`### ${mod.name}`, '');
      for (const api of mod.apis) {
        const href = api.href.startsWith('http') ? api.href : `https://open.shopee.com${api.href}`;
        if (api.href) {
          lines.push(`- [${api.name}](${href})`);
        } else {
          lines.push(`- ${api.name}`);
        }
      }
      lines.push('');
    }
  } else {
    // Fall back to flat list
    lines.push('## API Methods', '');
    for (const item of allApiItems.slice(0, 200)) {
      const href = item.href.startsWith('http') ? item.href : `https://open.shopee.com${item.href}`;
      if (item.href) {
        lines.push(`- [${item.name}](${href})`);
      } else {
        lines.push(`- ${item.name}`);
      }
    }
    if (allApiItems.length > 200) lines.push(`\n... and ${allApiItems.length - 200} more`);
    lines.push('');
  }

  if (tocContent) {
    lines.push('## Table of Contents', '', tocContent, '');
  }

  lines.push('---', '', '_Note: This is a module index only. Individual API method pages are not fully crawled due to the large volume of methods (hundreds across dozens of modules)._');

  fs.writeFileSync(path.join(OUT, 'API-REFERENCE-MODULES.md'), lines.join('\n'));
  console.log(`\nDONE-API-MODULES. Saved API-REFERENCE-MODULES.md with ${allModules.length} modules, ${allApiItems.length} API items`);
  await browser.close();
})();
