/*
 * Generic Playwright doc crawler.
 *
 * Usage:
 *   node crawl.js --config <config.json>            # full crawl -> markdown files
 *   node crawl.js --config <config.json> --recon    # dump DOM structure for tuning
 *   node crawl.js --config <config.json> --limit 5  # cap pages (debug)
 *
 * Config (JSON) fields:
 *   name            : site label
 *   startUrls       : [ "https://..." ]  seed pages
 *   outDir          : output directory (relative to cwd or absolute)
 *   includePattern  : regex (string) an absolute URL must match to be crawled
 *   excludePattern  : regex (string) URLs to skip (optional)
 *   scopeSelector   : restrict link discovery to inside this element (optional)
 *   linkSelector    : selector for link elements (default "a[href]")
 *   hrefAttr        : attribute to read link target from (default "href")
 *   contentSelector : selector(s) for main content; first found wins. string | string[]
 *   removeSelectors : selectors removed from content before markdown conversion
 *   expandSelectors : selectors clicked repeatedly to expand collapsed menus
 *   waitForSelector : wait for this selector after each navigation (optional)
 *   waitMs          : extra settle delay per page (default 800)
 *   navTimeoutMs    : navigation timeout (default 45000)
 *   maxPages        : safety cap (default 2000)
 *   headless        : bool (default true)
 *   userAgent       : override UA
 *   blockResources  : bool, abort images/fonts/media for speed (default true)
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

// ---------- arg parsing ----------
const args = process.argv.slice(2);
function argVal(flag) {
  const i = args.indexOf(flag);
  return i >= 0 ? args[i + 1] : undefined;
}
const RECON = args.includes('--recon');
const LIMIT = argVal('--limit') ? parseInt(argVal('--limit'), 10) : undefined;
const configPath = argVal('--config');
if (!configPath) {
  console.error('Missing --config <file>');
  process.exit(1);
}
const cfg = JSON.parse(fs.readFileSync(configPath, 'utf8'));

const DEFAULTS = {
  linkSelector: 'a[href]',
  hrefAttr: 'href',
  contentSelector: ['main', 'article', '#content', '.content'],
  removeSelectors: ['script', 'style', 'noscript', 'svg', 'nav', 'aside', 'header', 'footer'],
  expandSelectors: [],
  waitMs: 800,
  navTimeoutMs: 45000,
  maxPages: 2000,
  headless: true,
  blockResources: true,
  userAgent:
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
};
const C = { ...DEFAULTS, ...cfg };
const includeRe = C.includePattern ? new RegExp(C.includePattern) : null;
const excludeRe = C.excludePattern ? new RegExp(C.excludePattern) : null;
const contentSelectors = Array.isArray(C.contentSelector) ? C.contentSelector : [C.contentSelector];

// ---------- turndown ----------
const td = new TurndownService({
  headingStyle: 'atx',
  codeBlockStyle: 'fenced',
  bulletListMarker: '-',
  emDelimiter: '*',
});
td.use(gfm.gfm);
// keep <br> readable
td.addRule('preserveLineBreaks', {
  filter: ['br'],
  replacement: () => '  \n',
});

// ---------- helpers ----------
function slugify(u) {
  try {
    const url = new URL(u);
    let s = (url.pathname + url.search)
      .replace(/^\/+/, '')
      .replace(/[?#=&%]/g, '_')
      .replace(/\/+/g, '__')
      .replace(/[^a-zA-Z0-9_.-]/g, '-')
      .replace(/_+/g, '_')
      .replace(/-+/g, '-');
    s = s.replace(/^[-_.]+|[-_.]+$/g, '');
    if (!s) s = 'index';
    if (s.length > 120) s = s.slice(0, 120);
    return s;
  } catch {
    return 'page';
  }
}

function abs(href, base) {
  try {
    return new URL(href, base).href.split('#')[0];
  } catch {
    return null;
  }
}

function wanted(u) {
  if (!u) return false;
  if (includeRe && !includeRe.test(u)) return false;
  if (excludeRe && excludeRe.test(u)) return false;
  return true;
}

async function expandAll(page) {
  if (!C.expandSelectors.length) return;
  for (let pass = 0; pass < 8; pass++) {
    let clicked = 0;
    for (const sel of C.expandSelectors) {
      const els = await page.$$(sel);
      for (const el of els) {
        try {
          if (await el.isVisible()) {
            await el.click({ timeout: 1500 }).catch(() => {});
            clicked++;
          }
        } catch {}
      }
    }
    await page.waitForTimeout(400);
    if (clicked === 0) break;
  }
}

async function collectLinks(page) {
  const sel = C.linkSelector;
  const scope = C.scopeSelector || null;
  const attr = C.hrefAttr;
  const raw = await page.evaluate(
    ({ sel, scope, attr }) => {
      const root = scope ? document.querySelector(scope) : document;
      if (!root) return [];
      const out = [];
      root.querySelectorAll(sel).forEach((el) => {
        const v = attr === 'href' ? el.getAttribute('href') : el.getAttribute(attr);
        const text = (el.textContent || '').trim().replace(/\s+/g, ' ');
        if (v) out.push({ href: v, text });
      });
      return out;
    },
    { sel, scope, attr }
  );
  const map = new Map();
  for (const r of raw) {
    const a = abs(r.href, page.url());
    if (a && wanted(a) && !map.has(a)) map.set(a, r.text);
  }
  return map; // url -> link text
}

async function extractContent(page) {
  return await page.evaluate(
    ({ contentSelectors, removeSelectors }) => {
      let node = null;
      for (const s of contentSelectors) {
        const el = document.querySelector(s);
        if (el && el.textContent && el.textContent.trim().length > 40) {
          node = el;
          break;
        }
      }
      if (!node) node = document.body;
      const clone = node.cloneNode(true);
      removeSelectors.forEach((s) => clone.querySelectorAll(s).forEach((e) => e.remove()));
      // strip data-heavy attrs that bloat turndown
      clone.querySelectorAll('*').forEach((e) => {
        [...e.attributes].forEach((a) => {
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) {
            e.removeAttribute(a.name);
          }
        });
      });
      return {
        title: (document.title || '').trim(),
        h1: (document.querySelector('h1')?.textContent || '').trim(),
        html: clone.innerHTML,
        textLen: (node.textContent || '').trim().length,
      };
    },
    { contentSelectors, removeSelectors: C.removeSelectors }
  );
}

async function reconDump(page) {
  return await page.evaluate(() => {
    const anchorSample = [];
    const seen = new Set();
    document.querySelectorAll('a[href]').forEach((a) => {
      const h = a.getAttribute('href');
      if (h && !seen.has(h)) {
        seen.add(h);
        if (anchorSample.length < 60)
          anchorSample.push({ href: h, text: (a.textContent || '').trim().slice(0, 60) });
      }
    });
    // candidate content containers by text length
    const cands = [];
    document.querySelectorAll('main, article, section, div').forEach((el) => {
      const len = (el.textContent || '').trim().length;
      if (len > 300) {
        const cls = (el.className || '').toString().slice(0, 80);
        const id = el.id || '';
        cands.push({ tag: el.tagName, id, cls, len });
      }
    });
    cands.sort((a, b) => b.len - a.len);
    return {
      title: document.title,
      url: location.href,
      anchorCount: seen.size,
      anchorSample,
      contentCandidates: cands.slice(0, 25),
    };
  });
}

// ---------- main ----------
(async () => {
  const outDir = path.resolve(C.outDir);
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await chromium.launch({
    headless: C.headless,
    args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'],
  });
  const context = await browser.newContext({
    userAgent: C.userAgent,
    locale: 'en-US',
    viewport: { width: 1440, height: 900 },
    extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' },
  });
  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
  });
  if (C.blockResources) {
    await context.route('**/*', (route) => {
      const t = route.request().resourceType();
      if (t === 'image' || t === 'media' || t === 'font') return route.abort();
      return route.continue();
    });
  }

  const page = await context.newPage();
  page.setDefaultNavigationTimeout(C.navTimeoutMs);

  async function goto(u) {
    try {
      await page.goto(u, { waitUntil: 'domcontentloaded' });
    } catch (e) {
      // retry once
      await page.goto(u, { waitUntil: 'domcontentloaded' }).catch(() => {});
    }
    if (C.waitForSelector) {
      await page.waitForSelector(C.waitForSelector, { timeout: 15000 }).catch(() => {});
    }
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await page.waitForTimeout(C.waitMs);
  }

  if (RECON) {
    const results = [];
    for (const u of C.startUrls) {
      await goto(u);
      await expandAll(page);
      const dump = await reconDump(page);
      const links = await collectLinks(page);
      dump.matchedLinkCount = links.size;
      dump.matchedLinkSample = [...links.entries()].slice(0, 40).map(([k, v]) => ({ url: k, text: v }));
      results.push(dump);
    }
    const reconPath = path.join(outDir, '_recon.json');
    fs.writeFileSync(reconPath, JSON.stringify(results, null, 2));
    console.log('RECON written to', reconPath);
    await browser.close();
    return;
  }

  // ---- BFS crawl ----
  const queue = [...C.startUrls];
  const queued = new Set(queue);
  const visited = new Set();
  const pages = []; // {url, title, file}
  const cap = LIMIT || C.maxPages;
  let isFirst = true;

  while (queue.length && visited.size < cap) {
    const url = queue.shift();
    if (visited.has(url)) continue;
    visited.add(url);
    try {
      await goto(url);
      if (!C.expandOnce || isFirst) await expandAll(page);

      // discover more links. When expandOnce, only the first (fully expanded)
      // page is used for discovery; later pages reset the menu so re-collecting
      // would miss links already queued from the seed.
      const links = await collectLinks(page);
      if (!C.expandOnce || isFirst) {
        for (const [lu] of links) {
          if (!queued.has(lu) && !visited.has(lu)) {
            queued.add(lu);
            queue.push(lu);
          }
        }
      }
      isFirst = false;

      const content = await extractContent(page);
      const linkText = [...links.entries()].find(([k]) => k === url)?.[1];
      const heading = content.h1 || linkText || content.title || url;
      let md = td.turndown(content.html || '');
      md = md.replace(/\n{3,}/g, '\n\n').trim();

      const file = slugify(url) + '.md';
      const full =
        `# ${heading}\n\n` +
        `> Source: ${url}\n` +
        `> Scraped: ${new Date().toISOString()}\n\n` +
        `---\n\n` +
        md +
        '\n';
      fs.writeFileSync(path.join(outDir, file), full);
      pages.push({ url, title: heading, file, textLen: content.textLen });
      console.log(
        `[${visited.size}/${queued.size}] ${content.textLen} chars  ${url} -> ${file}`
      );
    } catch (e) {
      console.error('ERR', url, e.message);
      pages.push({ url, title: '(error)', file: null, error: e.message });
    }
  }

  // index
  const indexLines = [
    `# ${C.name} — Scraped Documentation Index`,
    '',
    `Total pages: ${pages.filter((p) => p.file).length}`,
    `Generated: ${new Date().toISOString()}`,
    '',
  ];
  for (const p of pages) {
    if (p.file) indexLines.push(`- [${p.title}](./${p.file}) — ${p.url}`);
    else indexLines.push(`- (FAILED) ${p.url} — ${p.error || ''}`);
  }
  fs.writeFileSync(path.join(outDir, 'INDEX.md'), indexLines.join('\n') + '\n');
  fs.writeFileSync(
    path.join(outDir, '_manifest.json'),
    JSON.stringify({ name: C.name, count: pages.length, pages }, null, 2)
  );
  console.log(`\nDONE. ${pages.filter((p) => p.file).length} pages -> ${outDir}`);
  await browser.close();
})();
