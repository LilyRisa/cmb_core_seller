/*
 * crawl-lazada-resume.js
 *
 * Resumable variant of crawl.js specifically for the Lazada config.
 * Reuses the same logic (config-driven, Playwright + turndown), but SKIPS
 * any URL whose output .md file already exists. This lets the crawl finish
 * the remaining pages within a single short run, since the full 383-page
 * crawl exceeds the background-task lifetime.
 *
 * It does NOT modify the shared crawl.js. It reads the same config:
 *   configs/lazada.json
 *
 * Usage:
 *   node crawl-lazada-resume.js                 # resume (skip existing)
 *   node crawl-lazada-resume.js --force         # re-scrape everything
 *   node crawl-lazada-resume.js --config <f>    # alternate config
 *
 * On completion writes/refreshes INDEX.md and _manifest.json covering ALL
 * .md files present in outDir (not just ones scraped this run), and prints
 * a final "DONE." line like crawl.js.
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const args = process.argv.slice(2);
function argVal(flag) {
  const i = args.indexOf(flag);
  return i >= 0 ? args[i + 1] : undefined;
}
const FORCE = args.includes('--force');
const configPath = argVal('--config') || path.join(__dirname, 'configs', 'lazada.json');
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

const td = new TurndownService({
  headingStyle: 'atx',
  codeBlockStyle: 'fenced',
  bulletListMarker: '-',
  emDelimiter: '*',
});
td.use(gfm.gfm);
td.addRule('preserveLineBreaks', { filter: ['br'], replacement: () => '  \n' });

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
  return map;
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

(async () => {
  const outDir = path.resolve(path.dirname(configPath), C.outDir);
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
      await page.goto(u, { waitUntil: 'domcontentloaded' }).catch(() => {});
    }
    if (C.waitForSelector) {
      await page.waitForSelector(C.waitForSelector, { timeout: 15000 }).catch(() => {});
    }
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    await page.waitForTimeout(C.waitMs);
  }

  // ---- discover all URLs from the seed (expandOnce semantics) ----
  const queue = [...C.startUrls];
  const queued = new Set(queue);
  const visited = new Set();
  const linkTextByUrl = new Map();
  let isFirst = true;
  let scrapedThisRun = 0;
  let skipped = 0;

  const cap = C.maxPages;

  while (queue.length && visited.size < cap) {
    const url = queue.shift();
    if (visited.has(url)) continue;
    visited.add(url);

    const file = slugify(url) + '.md';
    const filePath = path.join(outDir, file);

    // On the seed page we must still navigate + expand to discover all links.
    const mustVisitForDiscovery = isFirst && (!C.expandOnce || isFirst);

    const fileExists = fs.existsSync(filePath);

    if (!FORCE && fileExists && !mustVisitForDiscovery) {
      // Already scraped — skip the network round-trip.
      skipped++;
      // record link text if known later; title resolved during index build
      continue;
    }

    try {
      await goto(url);
      if (!C.expandOnce || isFirst) await expandAll(page);

      const links = await collectLinks(page);
      if (!C.expandOnce || isFirst) {
        for (const [lu, ltext] of links) {
          if (!linkTextByUrl.has(lu)) linkTextByUrl.set(lu, ltext);
          if (!queued.has(lu) && !visited.has(lu)) {
            queued.add(lu);
            queue.push(lu);
          }
        }
      }
      isFirst = false;

      // If the seed file already exists and not forcing, we discovered links
      // but should not re-write it (idempotent skip after discovery).
      if (!FORCE && fileExists) {
        skipped++;
        continue;
      }

      const content = await extractContent(page);
      const linkText = links.get(url) || linkTextByUrl.get(url);
      const heading = content.h1 || linkText || content.title || url;
      let md = td.turndown(content.html || '');
      md = md.replace(/\n{3,}/g, '\n\n').trim();

      const full =
        `# ${heading}\n\n` +
        `> Source: ${url}\n` +
        `> Scraped: ${new Date().toISOString()}\n\n` +
        `---\n\n` +
        md +
        '\n';
      fs.writeFileSync(filePath, full);
      scrapedThisRun++;
      console.log(`[${scrapedThisRun}] (q=${queued.size}) ${content.textLen} chars  ${url} -> ${file}`);
    } catch (e) {
      console.error('ERR', url, e.message);
    }
  }

  await browser.close();

  // ---- rebuild INDEX.md + _manifest.json from ALL .md files on disk ----
  // Map every queued URL to its file, plus any orphan .md files present.
  const allMdFiles = fs
    .readdirSync(outDir)
    .filter((f) => f.endsWith('.md') && f !== 'INDEX.md' && f !== 'README.md' && !f.startsWith('_'));

  const urlByFile = new Map();
  for (const u of queued) urlByFile.set(slugify(u) + '.md', u);

  function titleFromFile(filePath) {
    try {
      const txt = fs.readFileSync(filePath, 'utf8');
      const m = txt.match(/^#\s+(.+)$/m);
      return m ? m[1].trim() : null;
    } catch {
      return null;
    }
  }

  const pages = [];
  for (const f of allMdFiles.sort()) {
    const fp = path.join(outDir, f);
    const url = urlByFile.get(f) || '';
    const title = titleFromFile(fp) || linkTextByUrl.get(url) || f.replace(/\.md$/, '');
    pages.push({ url, title, file: f });
  }

  const indexLines = [
    `# ${C.name} — Scraped Documentation Index`,
    '',
    `Total pages: ${pages.length}`,
    `Generated: ${new Date().toISOString()}`,
    '',
  ];
  for (const p of pages) {
    indexLines.push(`- [${p.title}](./${p.file})${p.url ? ' — ' + p.url : ''}`);
  }
  fs.writeFileSync(path.join(outDir, 'INDEX.md'), indexLines.join('\n') + '\n');
  fs.writeFileSync(
    path.join(outDir, '_manifest.json'),
    JSON.stringify({ name: C.name, count: pages.length, pages }, null, 2)
  );

  console.log(
    `\nDONE. scraped ${scrapedThisRun} this run, skipped ${skipped} existing, ${pages.length} total pages -> ${outDir}`
  );
})();
