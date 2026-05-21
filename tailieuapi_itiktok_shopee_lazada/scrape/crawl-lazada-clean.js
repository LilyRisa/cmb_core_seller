/*
 * crawl-lazada-clean.js
 *
 * Authoritative Lazada API-reference crawler producing CLEAN markdown.
 * - Expands the full left category tree, harvests every ?path= endpoint and
 *   its category (from the tree node ancestry).
 * - For each endpoint: renders .api-detail, FLATTENS every table cell to inline
 *   content (keeping <a>/<code>) so GFM tables render on a single line, then
 *   converts to Markdown.
 * - Retries pages that fail/empty up to 2 extra passes.
 * - Writes one <slug>.md per endpoint plus a category-grouped INDEX.md and
 *   _manifest.json. Overwrites existing files (force) to fix earlier messy ones.
 *
 * Usage: node crawl-lazada-clean.js [--limit N]
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const args = process.argv.slice(2);
const LIMIT = args.indexOf('--limit') >= 0 ? parseInt(args[args.indexOf('--limit') + 1], 10) : undefined;

const SEED = 'https://open.lazada.com/apps/doc/api?path=%2Fauth%2Ftoken%2Fcreate';
const OUT = path.resolve(__dirname, '../lazada');
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const INCLUDE = /open\.lazada\.com\/apps\/doc\/api\?path=/;

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-', emDelimiter: '*' });
td.use(gfm.gfm);
td.addRule('br', { filter: ['br'], replacement: () => ' ' });

function slugify(u) {
  const url = new URL(u);
  let s = (url.pathname + url.search)
    .replace(/^\/+/, '').replace(/[?#=&%]/g, '_').replace(/\/+/g, '__')
    .replace(/[^a-zA-Z0-9_.-]/g, '-').replace(/_+/g, '_').replace(/-+/g, '-')
    .replace(/^[-_.]+|[-_.]+$/g, '');
  if (!s) s = 'index';
  if (s.length > 120) s = s.slice(0, 120);
  return s;
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 }, extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' } });
  await ctx.addInitScript(() => { Object.defineProperty(navigator, 'webdriver', { get: () => undefined }); });
  await ctx.route('**/*', (r) => {
    const t = r.request().resourceType();
    if (t === 'image' || t === 'media' || t === 'font') return r.abort();
    return r.continue();
  });
  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(45000);

  async function settle(sel) {
    if (sel) await page.waitForSelector(sel, { timeout: 15000 }).catch(() => {});
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await page.waitForTimeout(900);
  }

  // ---- 1. expand tree + harvest all endpoints with category ----
  console.log('Loading seed + expanding category tree...');
  await page.goto(SEED, { waitUntil: 'domcontentloaded' });
  await settle('.ui-dir-tree');
  for (let pass = 0; pass < 14; pass++) {
    const collapsed = await page.$$('.ui-dir-tree [role="listitem"][aria-expanded="false"]');
    let clicked = 0;
    for (const el of collapsed) {
      try { if (await el.isVisible()) { await el.click({ timeout: 1500 }).catch(() => {}); clicked++; } } catch {}
    }
    await page.waitForTimeout(500);
    if (!clicked) break;
  }

  const harvested = await page.evaluate(() => {
    const out = [];
    const seen = new Set();
    document.querySelectorAll('.ui-dir-tree a[href*="path="]').forEach((a) => {
      const href = a.getAttribute('href');
      const text = (a.textContent || '').trim().replace(/\s+/g, ' ');
      // walk up collecting li[title] ancestors; outermost = top category
      const cats = [];
      let p = a.closest('li[title]');
      while (p) { if (p.getAttribute('title')) cats.push(p.getAttribute('title')); p = p.parentElement ? p.parentElement.closest('li[title]') : null; }
      const topCat = cats.length ? cats[cats.length - 1] : 'Other';
      const subCat = cats.length ? cats[0] : '';
      if (href && !seen.has(href)) { seen.add(href); out.push({ href, text, topCat, subCat }); }
    });
    return out;
  });

  const endpoints = [];
  const seen = new Set();
  for (const h of harvested) {
    const url = new URL(h.href, page.url()).href.split('#')[0];
    if (INCLUDE.test(url) && !seen.has(url)) { seen.add(url); endpoints.push({ url, ...h }); }
  }
  console.log('Harvested', endpoints.length, 'endpoints across', new Set(endpoints.map(e => e.topCat)).size, 'top categories');
  fs.writeFileSync(path.join(OUT, '_paths.json'), JSON.stringify(endpoints, null, 2));

  // ---- 2. fetch + extract clean ----
  const list = LIMIT ? endpoints.slice(0, LIMIT) : endpoints;
  const results = new Map();   // url -> {file, title, len, topCat, subCat}
  const failed = [];

  async function fetchOne(e, idx, total, passLabel) {
    const url = e.url;
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      await settle('.api-detail');
      const data = await page.evaluate(() => {
        const node = document.querySelector('.api-detail');
        if (!node) return { title: '', html: '', len: 0 };
        const clone = node.cloneNode(true);
        clone.querySelectorAll('script,style,noscript,svg').forEach((x) => x.remove());
        // flatten table cells -> inline (keep a/code), collapse whitespace
        clone.querySelectorAll('td,th').forEach((cell) => {
          cell.querySelectorAll('br').forEach((b) => b.replaceWith(' '));
          cell.querySelectorAll('*:not(a):not(code)').forEach((el) => el.replaceWith(...el.childNodes));
          const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
          const tn = []; while (walker.nextNode()) tn.push(walker.currentNode);
          tn.forEach((t) => { t.nodeValue = t.nodeValue.replace(/\s+/g, ' '); });
        });
        // strip noise attributes
        clone.querySelectorAll('*').forEach((el) => [...el.attributes].forEach((a) => {
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name);
        }));
        const title = (node.querySelector('.api-name, h1, h2')?.textContent || document.title || '').trim().replace(/\s+/g, ' ');
        return { title, html: clone.innerHTML, len: (node.textContent || '').trim().length };
      });
      if (!data.len) throw new Error('empty .api-detail');
      let md = td.turndown(data.html || '');
      // strip repetitive UI noise
      md = md.replace(/\n?\s*Did this chapter help you\?\s*\n+\s*YesNo\s*/g, '\n');
      md = md.replace(/\n\s*Please rate this article[\s\S]*$/i, '\n');
      md = md.replace(/\n\s*Popular Articles[\s\S]*$/i, '\n');
      md = md.replace(/\n{3,}/g, '\n\n').trim();
      const apiPath = decodeURIComponent((url.match(/path=([^&]+)/) || [])[1] || '');
      const heading = e.text || data.title || apiPath || url;
      const file = slugify(url) + '.md';
      const body =
        `# ${heading}\n\n` +
        `> Source: ${url}\n` +
        `> API path: ${apiPath}\n` +
        `> Category: ${e.topCat}${e.subCat && e.subCat !== e.topCat ? ' / ' + e.subCat : ''}\n` +
        `> Scraped: ${new Date().toISOString()}\n\n---\n\n` + md + '\n';
      fs.writeFileSync(path.join(OUT, file), body);
      results.set(url, { file, title: heading, len: data.len, topCat: e.topCat, subCat: e.subCat, url });
      console.log(`${passLabel}[${idx}/${total}] ${data.len}c  ${e.topCat}  ${apiPath} -> ${file}`);
    } catch (err) {
      console.error(`${passLabel}ERR ${url} : ${err.message}`);
      return false;
    }
    return true;
  }

  let i = 0;
  for (const e of list) { i++; const ok = await fetchOne(e, i, list.length, ''); if (!ok) failed.push(e); }

  // retry passes
  for (let pass = 1; pass <= 2 && failed.length; pass++) {
    const retry = failed.splice(0);
    console.log(`\n--- retry pass ${pass}: ${retry.length} pages ---`);
    let j = 0;
    for (const e of retry) { j++; const ok = await fetchOne(e, j, retry.length, `R${pass} `); if (!ok) failed.push(e); }
  }

  await browser.close();

  // ---- 3. index grouped by category ----
  const byTop = {};
  for (const e of list) {
    const r = results.get(e.url);
    (byTop[e.topCat] ||= []).push(r ? { ...r, ok: true } : { url: e.url, title: e.text, file: null, ok: false, topCat: e.topCat, subCat: e.subCat });
  }
  const okCount = [...results.values()].length;
  const lines = [
    `# Lazada Open Platform — API Reference (Scraped)`, '',
    `Total endpoints: ${okCount} / ${list.length} harvested`,
    `Top-level categories: ${Object.keys(byTop).length}`,
    `Generated: ${new Date().toISOString()}`, '',
  ];
  for (const cat of Object.keys(byTop).sort()) {
    lines.push(`## ${cat} (${byTop[cat].filter(p => p.ok).length})`, '');
    for (const p of byTop[cat]) {
      if (p.ok) lines.push(`- [${p.title}](./${p.file})`);
      else lines.push(`- (FAILED) ${p.title} — ${p.url}`);
    }
    lines.push('');
  }
  fs.writeFileSync(path.join(OUT, 'INDEX.md'), lines.join('\n'));
  fs.writeFileSync(path.join(OUT, '_manifest.json'), JSON.stringify({ name: 'Lazada Open Platform API Reference', harvested: list.length, scraped: okCount, failed: failed.map(f => f.url), pages: [...results.values()] }, null, 2));
  console.log(`\nDONE. scraped ${okCount}/${list.length}, failed ${failed.length} -> ${OUT}`);
})();
