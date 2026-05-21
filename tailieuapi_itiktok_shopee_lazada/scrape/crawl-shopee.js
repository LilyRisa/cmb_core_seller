/*
 * Shopee Developer Guide crawler.
 *
 * The Shopee guide sidebar is NOT made of <a> tags — each entry is a
 * <div class="sidebar-item ... selectable" data-ts-content_id="N" data-ts-content_name="...">.
 * The page URL for an entry is /developer-guide/{content_id}. We expand every
 * top-level category, harvest all data-ts-content_id values, then visit each
 * /developer-guide/{id} page and extract .developer-guide-body__main-content.
 *
 * Usage: node crawl-shopee.js [--limit N]
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const args = process.argv.slice(2);
const LIMIT = args.indexOf('--limit') >= 0 ? parseInt(args[args.indexOf('--limit') + 1], 10) : undefined;

const BASE = 'https://open.shopee.com';
const SEED = 'https://open.shopee.com/developer-guide/20';
const OUT = path.resolve(__dirname, '../shopee');
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
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await page.waitForTimeout(1000);
  }

  console.log('Loading seed and expanding sidebar...');
  await page.goto(SEED, { waitUntil: 'domcontentloaded' });
  await settle();

  // Expand every category (multiple passes for nested levels).
  for (let pass = 0; pass < 8; pass++) {
    const toggles = await page.$$('.sidebar-item__level--1 .sidebar-item__icon--expand, .sidebar-item .sidebar-item__icon--expand');
    let clicked = 0;
    for (const t of toggles) {
      try { if (await t.isVisible()) { await t.click({ timeout: 1200 }).catch(() => {}); clicked++; } } catch {}
    }
    await page.waitForTimeout(500);
    if (!clicked) break;
  }

  // Harvest all guide entries.
  const entries = await page.evaluate(() => {
    const out = [];
    const seen = new Set();
    document.querySelectorAll('[data-ts-content_id]').forEach((el) => {
      const id = el.getAttribute('data-ts-content_id');
      const name = el.getAttribute('data-ts-content_name') || (el.textContent || '').trim().replace(/\s+/g, ' ');
      // capture parent category for grouping
      let cat = '';
      let p = el.closest('.sidebar-item__sub-menu');
      while (p) {
        const head = p.parentElement && p.parentElement.querySelector(':scope > .sidebar-item__name');
        if (head) { cat = head.textContent.trim().replace(/\s+/g, ' '); break; }
        p = p.parentElement ? p.parentElement.closest('.sidebar-item__sub-menu') : null;
      }
      if (id && !seen.has(id)) { seen.add(id); out.push({ id, name, cat }); }
    });
    // also include top-level primary categories that are themselves pages
    document.querySelectorAll('[data-ts-primary_category_id]').forEach((el) => {
      const id = el.getAttribute('data-ts-primary_category_id');
      const name = el.getAttribute('data-ts-primary_category') || '';
      if (id && !seen.has('p' + id)) { /* primary categories are containers, skip as pages */ }
    });
    return out;
  });
  console.log('Found', entries.length, 'guide entries');
  fs.writeFileSync(path.join(OUT, '_entries.json'), JSON.stringify(entries, null, 2));

  const list = LIMIT ? entries.slice(0, LIMIT) : entries;
  const done = [];
  let i = 0;
  for (const e of list) {
    i++;
    const url = `${BASE}/developer-guide/${e.id}`;
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      await page.waitForSelector('.developer-guide-body__main-content, .editor-preview', { timeout: 15000 }).catch(() => {});
      await settle();
      const data = await page.evaluate(() => {
        const node = document.querySelector('.developer-guide-body__main-content') ||
                     document.querySelector('.editor-preview') ||
                     document.querySelector('.developer-guide-body__main');
        const title = (document.querySelector('.developer-guide-header-box h1, .developer-guide-header__title, h1')?.textContent || document.title || '').trim();
        if (!node) return { title, html: '', len: 0 };
        const clone = node.cloneNode(true);
        clone.querySelectorAll('script,style,noscript,svg').forEach((x) => x.remove());
        clone.querySelectorAll('*').forEach((el) => [...el.attributes].forEach((a) => {
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name);
        }));
        return { title, html: clone.innerHTML, len: (node.textContent || '').trim().length };
      });
      let md = td.turndown(data.html || '').replace(/\n{3,}/g, '\n\n').trim();
      const heading = data.title || e.name || ('Guide ' + e.id);
      const file = `${e.id}-${slug(e.name || data.title)}.md`;
      const body = `# ${heading}\n\n> Source: ${url}\n> Category: ${e.cat || ''}\n> Scraped: ${new Date().toISOString()}\n\n---\n\n${md}\n`;
      fs.writeFileSync(path.join(OUT, file), body);
      done.push({ ...e, url, file, len: data.len });
      console.log(`[${i}/${list.length}] id=${e.id} ${data.len} chars  ${e.name} -> ${file}`);
    } catch (err) {
      console.error('ERR', url, err.message);
      done.push({ ...e, url, file: null, error: err.message });
    }
  }

  // Build index grouped by category
  const byCat = {};
  for (const d of done) (byCat[d.cat || 'General'] ||= []).push(d);
  const lines = [`# Shopee Open Platform — Developer Guide (Scraped)`, '', `Total pages: ${done.filter((d) => d.file).length}`, `Generated: ${new Date().toISOString()}`, ''];
  for (const cat of Object.keys(byCat)) {
    lines.push(`## ${cat}`, '');
    for (const d of byCat[cat]) {
      if (d.file) lines.push(`- [${d.name}](./${d.file}) — ${d.url}`);
      else lines.push(`- (FAILED) ${d.name} — ${d.url}`);
    }
    lines.push('');
  }
  fs.writeFileSync(path.join(OUT, 'INDEX.md'), lines.join('\n'));
  fs.writeFileSync(path.join(OUT, '_manifest.json'), JSON.stringify({ name: 'Shopee Developer Guide', count: done.length, pages: done }, null, 2));
  console.log(`\nDONE. ${done.filter((d) => d.file).length} pages -> ${OUT}`);
  await browser.close();
})();
