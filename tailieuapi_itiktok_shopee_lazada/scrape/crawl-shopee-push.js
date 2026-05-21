/*
 * Shopee Push Mechanism documentation crawler.
 *
 * The Push Mechanism page is a Single Page App - all content loads at
 * /push-mechanism/5. The left sidebar shows categories and leaf items;
 * clicking each leaf item updates the displayed content (no URL change).
 *
 * Strategy:
 * 1. Load the seed page, expand all categories
 * 2. Collect all leaf nav items (category-container__item puppeteer-link)
 * 3. Click each item, wait for content to update, extract and save
 *
 * Output: shopee/push-{index}-{slug}.md
 *
 * Usage: node crawl-shopee-push.js [--limit N]
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const args = process.argv.slice(2);
const LIMIT = args.indexOf('--limit') >= 0 ? parseInt(args[args.indexOf('--limit') + 1], 10) : undefined;

const SEED = 'https://open.shopee.com/push-mechanism/5';
const OUT = path.resolve(__dirname, '../shopee');
const PREFIX = 'push-';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-' });
td.use(gfm.gfm);
td.addRule('br', { filter: ['br'], replacement: () => '  \n' });

function slug(s) {
  return s.toString().replace(/[^a-zA-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80).toLowerCase() || 'page';
}

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
  page.setDefaultNavigationTimeout(45000);

  async function settle() {
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    await page.waitForTimeout(800);
  }

  console.log('Loading Push Mechanism page...');
  await page.goto(SEED, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Expand all categories by clicking the nested header items
  console.log('Expanding all categories...');
  for (let pass = 0; pass < 6; pass++) {
    const clicked = await page.evaluate(() => {
      let n = 0;
      // Click collapsed category headers (those with arrow icons not rotated)
      document.querySelectorAll('.category-item--nested').forEach(el => {
        // Check if sub-list is collapsed (height 0 or not expanded)
        const subList = el.parentElement?.querySelector('.category-item__sub-list');
        const isExpanded = el.parentElement?.classList.contains('category-item--expended');
        if (!isExpanded) {
          try { el.click(); n++; } catch(e) {}
        }
      });
      return n;
    });
    await page.waitForTimeout(800);
    if (!clicked) break;
  }

  // Collect all leaf items from sidebar
  const navEntries = await page.evaluate(() => {
    const entries = [];
    const seen = new Set();
    // Get all leaf items (category-container__item)
    document.querySelectorAll('.category-container__item').forEach((el, idx) => {
      const text = el.textContent.trim().replace(/\s+/g, ' ');
      if (text && !seen.has(text)) {
        seen.add(text);
        // Find parent category
        let cat = '';
        let parent = el.closest('.category-item--expended') || el.parentElement;
        while (parent) {
          const nameEl = parent.querySelector(':scope > .category-item--nested .category-item__name--nested');
          if (nameEl) { cat = nameEl.textContent.trim(); break; }
          parent = parent.parentElement;
        }
        entries.push({ text, cat, idx });
      }
    });
    return entries;
  });

  console.log('Found', navEntries.length, 'push mechanism entries');
  fs.writeFileSync(path.join(OUT, '_push-entries.json'), JSON.stringify(navEntries, null, 2));

  const list = LIMIT ? navEntries.slice(0, LIMIT) : navEntries;
  const done = [];
  let i = 0;

  for (const entry of list) {
    i++;
    try {
      // Click the item by its text content
      const clicked = await page.evaluate((text) => {
        const items = document.querySelectorAll('.category-container__item');
        for (const item of items) {
          if (item.textContent.trim().replace(/\s+/g, ' ') === text) {
            item.click();
            return true;
          }
        }
        return false;
      }, entry.text);

      if (!clicked) {
        console.warn(`[${i}/${list.length}] Could not click: ${entry.text}`);
        done.push({ ...entry, file: null, error: 'Could not click nav item' });
        continue;
      }

      await settle();

      // Extract content from the detail panel
      const data = await page.evaluate(() => {
        // Main content in the detail area
        const detailEl = document.querySelector('.push-mechanism-detail__content, .push-mechainsm-detail__wrapper, .push-detail-content');
        const nameEl = document.querySelector('.push-detail__name, .api-reference-name, h1');
        const title = (nameEl?.textContent || '').trim().replace(/\s+/g, ' ');

        if (!detailEl) {
          // Fallback - get anything in the main content area
          const anyContent = document.querySelector('.push-mechainsm-detail, .api-reference-detail-container');
          if (anyContent) {
            const clone = anyContent.cloneNode(true);
            clone.querySelectorAll('script,style,noscript,svg,button').forEach(x => x.remove());
            clone.querySelectorAll('*').forEach(el => {
              Array.from(el.attributes).forEach(a => {
                if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name);
              });
            });
            return { title, html: clone.innerHTML, len: (anyContent.textContent || '').trim().length };
          }
          return { title, html: '', len: 0 };
        }

        const clone = detailEl.cloneNode(true);
        clone.querySelectorAll('script,style,noscript,svg,button').forEach(x => x.remove());
        clone.querySelectorAll('*').forEach(el => {
          Array.from(el.attributes).forEach(a => {
            if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name);
          });
        });
        return { title, html: clone.innerHTML, len: (detailEl.textContent || '').trim().length };
      });

      const titleText = data.title || entry.text;
      let md = td.turndown(data.html || '').replace(/\n{3,}/g, '\n\n').trim();
      const file = `${PREFIX}${String(i).padStart(3, '0')}-${slug(titleText)}.md`;
      const body = `# ${titleText}\n\n> Source: ${SEED}\n> Category: ${entry.cat || ''}\n> Scraped: ${new Date().toISOString()}\n\n---\n\n${md}\n`;
      fs.writeFileSync(path.join(OUT, file), body);
      done.push({ ...entry, file, len: data.len });
      console.log(`[${i}/${list.length}] ${data.len} chars  ${entry.text} (${entry.cat}) -> ${file}`);
    } catch (err) {
      console.error('ERR', entry.text, err.message);
      done.push({ ...entry, file: null, error: err.message });
    }
  }

  // Write push-specific manifest
  fs.writeFileSync(path.join(OUT, '_push-manifest.json'), JSON.stringify({
    name: 'Shopee Push Mechanism',
    count: done.length,
    pages: done
  }, null, 2));
  console.log(`\nDONE-PUSH. ${done.filter(d => d.file).length} pages -> ${OUT}`);
  await browser.close();
})();
