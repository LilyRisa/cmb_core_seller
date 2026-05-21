/*
 * TikTok Shop Partner docs — Comprehensive crawler
 *
 * DOM inspection findings:
 *
 *   Tab 0: Partner Guide       — 85 /docv2/page/ links, dirs already OPEN
 *   Tab 1: Developer Guide     — 37 /docv2/page/ links, dirs already OPEN
 *   Tab 2: API Reference       — 305 /docv2/page/ links; click ALL dirs ONCE to expand
 *   Tab 3: Webhooks            — 43 /docv2/page/ links, dirs OPEN
 *   Tab 4: Terms and Policies  — 43 /docv2/page/ links, dirs OPEN
 *   Tab 5: Changelog           — 147 /docv2/page/ links (DON'T click dirs, they collapse)
 *   Tab 6: FAQs                — /docv2/faqs/ URLs (different pattern) + click CLOSED dirs
 *   Tab 7: API Testing Tool    — same sidebar as FAQs
 *
 * Run: node crawl-tiktok.js
 */

const fs   = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const BASE_URL   = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const OUT_DIR    = path.resolve(__dirname, '../tiktok');
const MANIFEST   = path.join(OUT_DIR, '_manifest.json');
const INDEX_FILE = path.join(OUT_DIR, 'INDEX.md');

const UA          = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const WAIT_MS     = 1600;
const NAV_TIMEOUT = 50000;
const MAX_PAGES   = 5000;

const TAB_NAMES = [
  'Partner Guide',
  'Developer Guide',
  'API Reference',
  'Webhooks',
  'Terms and Policies',
  'Changelog',
  'FAQs',
  'API Testing Tool',
];

// Expansion strategy per tab:
//   'none'   = DON'T click dir items (links already visible, clicking would collapse)
//   'all'    = Click ALL dir items once (API Reference: opens all 305 endpoint links)
const TAB_EXPAND = [
  'none',  // 0: Partner Guide (85 links visible)
  'none',  // 1: Developer Guide (37 links visible)
  'all',   // 2: API Reference (6 visible; click all dirs once -> 305)
  'none',  // 3: Webhooks (43 links visible)
  'none',  // 4: Terms and Policies (43 links visible)
  'none',  // 5: Changelog (147 links visible)
  'none',  // 6: FAQs (11 faqs links visible — clicking dirs collapses them)
  'none',  // 7: API Testing Tool (11 faqs links visible)
];

// Link selector per tab — FAQs uses /docv2/faqs/ pattern
const TAB_LINK_SEL = [
  'a[href*="/docv2/page/"]',  // 0: Partner Guide
  'a[href*="/docv2/page/"]',  // 1: Developer Guide
  'a[href*="/docv2/page/"]',  // 2: API Reference
  'a[href*="/docv2/page/"]',  // 3: Webhooks
  'a[href*="/docv2/page/"]',  // 4: Terms and Policies
  'a[href*="/docv2/page/"]',  // 5: Changelog
  'a[href*="/docv2/faqs/"]',  // 6: FAQs
  'a[href*="/docv2/faqs/"]',  // 7: API Testing Tool
];

// ---------- turndown ----------
const td = new TurndownService({
  headingStyle:    'atx',
  codeBlockStyle:  'fenced',
  bulletListMarker: '-',
  emDelimiter:     '*',
});
td.use(gfm.gfm);
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
  } catch { return 'page'; }
}

function isDocUrl(u) {
  return u && /partner\.tiktokshop\.com\/docv2\/(page|faqs)\//.test(u);
}

// ---------- expand menu ----------
async function expandMenu(page, strategy) {
  if (strategy === 'none') return;

  if (strategy === 'all') {
    // Click ALL dir items exactly once (API Reference: opens all 305 links)
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => {
        try { dir.click(); n++; } catch {}
      });
      return n;
    });
    await page.waitForTimeout(1500);
    console.log(`  Expand 'all': clicked ${n} dir items`);
    return;
  }
}

// ---------- collect links by selector ----------
async function collectLinksBySel(page, linkSel) {
  return await page.evaluate((sel) => {
    const map = {};
    document.querySelectorAll(sel).forEach(a => {
      const href = a.getAttribute('href');
      if (!href) return;
      try {
        const u = new URL(href, location.href);
        u.hash = '';
        const full = u.href;
        if (!map[full]) {
          map[full] = (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 100);
        }
      } catch {}
    });
    return map;
  }, linkSel);
}

// Collect all /docv2/ content links (page and faqs) for BFS traversal
async function collectPageLinks(page) {
  return await collectLinksBySel(page, 'a[href*="/docv2/page/"], a[href*="/docv2/faqs/"]');
}

// ---------- extract content ----------
async function extractContent(page, linkText) {
  const CONTENT_SELS = [
    "[class*='markdown-container']",
    '#doc_scroll_container',
    '#scrollIntersectionCenter',
    "[class*='faq-content']",
    "[class*='faq']",
    'main',
    'article',
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
        if (el && (el.textContent || '').trim().length > 40) { node = el; break; }
      }
      if (!node) node = document.body;

      const clone = node.cloneNode(true);
      removeSels.forEach(s => clone.querySelectorAll(s).forEach(e => e.remove()));
      clone.querySelectorAll('*').forEach(e => {
        [...e.attributes].forEach(a => {
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name))
            e.removeAttribute(a.name);
        });
      });

      const rawTitle = (document.title || '').trim();
      const h1Text  = (document.querySelector('h1')?.textContent || '').trim();
      const GENERIC = ['DocumentsNew', 'Documents', 'TikTok Shop Partner', ''];
      let bestTitle = h1Text || fallback || rawTitle;
      if (GENERIC.includes(bestTitle)) bestTitle = fallback || h1Text || rawTitle;

      return {
        title:    rawTitle,
        h1:       h1Text,
        html:     clone.innerHTML,
        textLen:  (node.textContent || '').trim().length,
        bestTitle,
      };
    },
    { contentSels: CONTENT_SELS, removeSels: REMOVE_SELS, fallback: linkText || '' }
  );
}

// ---------- navigate ----------
async function goto(page, url) {
  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT });
  } catch {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT }).catch(() => {});
  }
  // Try markdown container first, then any substantial content
  await page.waitForSelector("[class*='markdown-container']", { timeout: 12000 }).catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(WAIT_MS);
}

async function dismissModals(page) {
  await page.evaluate(() => {
    document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => {
      try { el.style.display = 'none'; } catch {}
    });
  });
}

// ============================================================
// MAIN
// ============================================================
(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });

  const browser = await chromium.launch({
    headless: true,
    args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'],
  });
  const context = await browser.newContext({
    userAgent: UA,
    locale: 'en-US',
    viewport: { width: 1440, height: 900 },
    extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' },
  });
  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
    Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
  });
  await context.route('**/*', route => {
    const t = route.request().resourceType();
    if (t === 'image' || t === 'media' || t === 'font') return route.abort();
    return route.continue();
  });

  const navPage = await context.newPage();
  navPage.setDefaultNavigationTimeout(NAV_TIMEOUT);

  // ===== PHASE 1: Collect all URLs per category =====
  console.log('=== PHASE 1: Collecting URLs per category ===\n');

  const urlMap = {}; // url -> { text, category }

  console.log('Loading base page...');
  await goto(navPage, BASE_URL);

  for (let tabIdx = 0; tabIdx < TAB_NAMES.length; tabIdx++) {
    const tabName  = TAB_NAMES[tabIdx];
    const tabId    = `arco-tabs-0-tab-${tabIdx}`;
    const strategy = TAB_EXPAND[tabIdx];
    const linkSel  = TAB_LINK_SEL[tabIdx];

    console.log(`\n--- Tab ${tabIdx}: "${tabName}" (expand: ${strategy}) ---`);
    await dismissModals(navPage);

    // Click tab via JS (bypasses modal overlay issues)
    const clicked = await navPage.evaluate((id) => {
      const el = document.getElementById(id);
      if (!el) return false;
      el.click();
      return true;
    }, tabId);

    if (!clicked) {
      console.log(`  Tab not found, skipping`);
      continue;
    }
    await navPage.waitForTimeout(2500);
    await dismissModals(navPage);

    // Expand menu
    await expandMenu(navPage, strategy);
    await navPage.waitForTimeout(500);

    // Collect links
    const links = await collectLinksBySel(navPage, linkSel);
    const count = Object.keys(links).length;
    console.log(`  Links: ${count}`);

    for (const [url, text] of Object.entries(links)) {
      if (!urlMap[url]) {
        urlMap[url] = { text, category: tabName };
      }
    }
  }

  const totalUrls = Object.keys(urlMap).length;
  console.log(`\n=== Total unique URLs: ${totalUrls} ===`);

  // ===== PHASE 2: Crawl each URL =====
  console.log('\n=== PHASE 2: Crawling pages ===\n');

  const pages = [];
  const visited = new Set();
  const bfsQueue = [...Object.keys(urlMap)];
  const bfsQueued = new Set(bfsQueue);
  let idx = 0;

  while (idx < bfsQueue.length && visited.size < MAX_PAGES) {
    const url = bfsQueue[idx++];
    if (visited.has(url)) continue;
    visited.add(url);

    const meta = urlMap[url] || { text: '', category: 'Unknown' };

    try {
      await goto(navPage, url);

      // BFS: discover new page links
      const extra = await collectPageLinks(navPage);
      for (const [lu, lt] of Object.entries(extra)) {
        if (isDocUrl(lu) && !bfsQueued.has(lu)) {
          bfsQueued.add(lu);
          bfsQueue.push(lu);
          if (!urlMap[lu]) urlMap[lu] = { text: lt, category: meta.category };
        }
      }

      // Extract content
      const content = await extractContent(navPage, meta.text);

      let title = content.bestTitle || meta.text || url.split('/').pop();
      if (!title || /^DocumentsNew$/i.test(title) || /^Documents$/i.test(title)) {
        title = meta.text || url.split('/').pop();
      }

      let md = td.turndown(content.html || '');
      md = md.replace(/\n{3,}/g, '\n\n').trim();

      const file = slugify(url) + '.md';
      const full =
        `# ${title}\n\n` +
        `> Source: ${url}\n` +
        `> Section: ${meta.category}\n` +
        `> Scraped: ${new Date().toISOString()}\n\n` +
        `---\n\n` +
        md + '\n';

      fs.writeFileSync(path.join(OUT_DIR, file), full);
      pages.push({ url, title, file, category: meta.category, textLen: content.textLen });

      console.log(
        `[${visited.size}/${bfsQueue.length}] ${content.textLen}ch [${meta.category}] ${url.split('/').pop()}`
      );
    } catch (e) {
      console.error(`ERR [${meta.category}] ${url}: ${e.message.slice(0, 80)}`);
      pages.push({ url, title: meta.text || '(error)', file: null, category: meta.category, error: e.message });
    }
  }

  // ===== PHASE 3: Write output files =====
  console.log('\n=== PHASE 3: Writing INDEX.md and _manifest.json ===');

  const byCategory = {};
  for (const p of pages) {
    const cat = p.category || 'Unknown';
    if (!byCategory[cat]) byCategory[cat] = [];
    byCategory[cat].push(p);
  }

  const successCount = pages.filter(p => p.file).length;

  const indexLines = [
    '# TikTok Shop Partner — Scraped Documentation Index',
    '',
    `Total pages: ${successCount}`,
    `Generated: ${new Date().toISOString()}`,
    '',
    '## Sections',
    '',
  ];

  for (const [cat, catPages] of Object.entries(byCategory)) {
    const ok = catPages.filter(p => p.file).length;
    indexLines.push(`### ${cat} (${ok} pages)`);
    indexLines.push('');
    for (const p of catPages) {
      if (p.file) {
        indexLines.push(`- [${p.title}](./${p.file}) — ${p.url}`);
      } else {
        indexLines.push(`- (FAILED) ${p.url} — ${(p.error || '').slice(0, 80)}`);
      }
    }
    indexLines.push('');
  }

  fs.writeFileSync(INDEX_FILE, indexLines.join('\n') + '\n');
  fs.writeFileSync(MANIFEST, JSON.stringify({
    name: 'TikTok Shop Partner',
    count: pages.length,
    successCount,
    categories: Object.keys(byCategory).map(cat => ({
      name: cat,
      count: byCategory[cat].filter(p => p.file).length,
    })),
    pages,
    generated: new Date().toISOString(),
  }, null, 2));

  console.log(`\nDONE. ${successCount} pages -> ${OUT_DIR}`);
  console.log('Categories:');
  for (const [cat, catPages] of Object.entries(byCategory)) {
    console.log(`  ${cat}: ${catPages.filter(p => p.file).length} pages`);
  }

  await browser.close();
})();
