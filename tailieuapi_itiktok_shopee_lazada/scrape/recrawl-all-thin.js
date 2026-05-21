/*
 * Re-crawl ALL thin pages (not just API Reference) with fixed selectors
 * Threshold: < 200 chars textLen
 */
const fs   = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const OUT_DIR  = path.resolve(__dirname, '../tiktok');
const MANIFEST = path.join(OUT_DIR, '_manifest.json');

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const WAIT_MS = 2000;
const NAV_TIMEOUT = 50000;

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-', emDelimiter: '*' });
td.use(gfm.gfm);
td.addRule('preserveLineBreaks', { filter: ['br'], replacement: () => '  \n' });

function slugify(u) {
  try {
    const url = new URL(u);
    let s = (url.pathname + url.search)
      .replace(/^\/+/, '').replace(/[?#=&%]/g, '_').replace(/\/+/g, '__')
      .replace(/[^a-zA-Z0-9_.-]/g, '-').replace(/_+/g, '_').replace(/-+/g, '-');
    s = s.replace(/^[-_.]+|[-_.]+$/g, '');
    if (!s) s = 'index';
    if (s.length > 120) s = s.slice(0, 120);
    return s;
  } catch { return 'page'; }
}

async function goto(page, url) {
  try { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT }); }
  catch { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT }).catch(() => {}); }
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(WAIT_MS);
}

async function extractContent(page, linkText) {
  const CONTENT_SELS = [
    "[class*='markdown-container']",
    '#doc_scroll_container',
    '#scrollIntersectionCenter',
    "[class*='scroll-intersection-center']",
    'main',
  ];
  const REMOVE_SELS = [
    'script', 'style', 'noscript', 'svg',
    "[class*='feedback-btn']",
    "[class*='feedback-action']",
    "[class*='page-navigator']",
    "[class*='breadcrumb']",
    "[class*='side-menu']",
    "[class*='float-toolbar']",
    "header", "footer", "nav", "aside",
  ];

  return await page.evaluate(
    ({ contentSels, removeSels, fallback }) => {
      let node = null;
      for (const s of contentSels) {
        const el = document.querySelector(s);
        if (el && (el.textContent || '').trim().length > 50) { node = el; break; }
      }
      if (!node) node = document.body;

      const clone = node.cloneNode(true);
      removeSels.forEach(s => clone.querySelectorAll(s).forEach(e => e.remove()));
      clone.querySelectorAll('img[src^="data:"]').forEach(e => e.remove());
      clone.querySelectorAll('*').forEach(e => {
        [...e.attributes].forEach(a => {
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name))
            e.removeAttribute(a.name);
        });
      });

      const rawTitle = (document.title || '').trim();
      const h1Text = (document.querySelector('h1')?.textContent || '').trim();
      const GENERIC = ['DocumentsNew', 'Documents', 'TikTok Shop Partner', ''];
      let bestTitle = h1Text || fallback || rawTitle;
      if (GENERIC.includes(bestTitle)) bestTitle = fallback || h1Text || rawTitle;

      return {
        h1: h1Text,
        html: clone.innerHTML,
        textLen: (node.textContent || '').trim().length,
        bestTitle,
      };
    },
    { contentSels: CONTENT_SELS, removeSels: REMOVE_SELS, fallback: linkText || '' }
  );
}

(async () => {
  const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
  // Only re-crawl pages NOT in API Reference (those were already done)
  const thinPages = manifest.pages.filter(p =>
    p.file &&
    p.textLen < 300 &&
    p.category !== 'API Reference'
  );

  console.log(`Found ${thinPages.length} thin non-API pages to re-crawl`);
  if (thinPages.length === 0) {
    console.log('Nothing to do!');
    return;
  }

  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
  });
  await ctx.route('**/*', route => {
    const t = route.request().resourceType();
    if (t === 'image' || t === 'media' || t === 'font') return route.abort();
    return route.continue();
  });

  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(NAV_TIMEOUT);

  let fixed = 0, failed = 0;

  for (let i = 0; i < thinPages.length; i++) {
    const p = thinPages[i];
    try {
      await goto(page, p.url);
      const content = await extractContent(page, p.title);

      let title = content.bestTitle || p.title || p.url.split('/').pop();
      if (!title || /^DocumentsNew$/i.test(title)) title = p.title || p.url.split('/').pop();

      let md = td.turndown(content.html || '');
      md = md.replace(/!\[[^\]]*\]\(data:[^)]+\)/g, '');
      md = md.replace(/\n{3,}/g, '\n\n').trim();

      const file = slugify(p.url) + '.md';
      const full = `# ${title}\n\n> Source: ${p.url}\n> Section: ${p.category}\n> Scraped: ${new Date().toISOString()}\n\n---\n\n${md}\n`;

      fs.writeFileSync(path.join(OUT_DIR, file), full);

      const mEntry = manifest.pages.find(mp => mp.url === p.url);
      if (mEntry) { mEntry.textLen = content.textLen; mEntry.title = title; mEntry.file = file; }

      fixed++;
      console.log(`[${i+1}/${thinPages.length}] FIXED ${content.textLen}ch [${p.category}] ${p.url.split('/').pop()}`);
    } catch (e) {
      failed++;
      console.error(`ERR ${p.url}: ${e.message.slice(0, 60)}`);
    }
  }

  manifest.generated = new Date().toISOString();
  fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2));

  console.log(`\nDone. Fixed: ${fixed}, Failed: ${failed}`);
  await browser.close();
})();
