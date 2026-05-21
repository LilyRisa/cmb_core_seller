/*
 * TikTok Shop Partner docs — RESUME crawl
 * Skips URLs already saved in tiktok/*.md (by matching slug)
 * Continues from where crawl-tiktok.js left off
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
  'Partner Guide', 'Developer Guide', 'API Reference', 'Webhooks',
  'Terms and Policies', 'Changelog', 'FAQs', 'API Testing Tool',
];
const TAB_EXPAND = ['none','none','all','none','none','none','none','none'];
const TAB_LINK_SEL = [
  'a[href*="/docv2/page/"]', 'a[href*="/docv2/page/"]', 'a[href*="/docv2/page/"]',
  'a[href*="/docv2/page/"]', 'a[href*="/docv2/page/"]', 'a[href*="/docv2/page/"]',
  'a[href*="/docv2/faqs/"]', 'a[href*="/docv2/faqs/"]',
];

// ---------- turndown ----------
const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-', emDelimiter: '*' });
td.use(gfm.gfm);
td.addRule('preserveLineBreaks', { filter: ['br'], replacement: () => '  \n' });

// ---------- helpers ----------
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

function isDocUrl(u) {
  return u && /partner\.tiktokshop\.com\/docv2\/(page|faqs)\//.test(u);
}

async function expandMenu(page, strategy) {
  if (strategy === 'none') return;
  if (strategy === 'all') {
    const n = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('[class*="side-menu-dir"]').forEach(dir => { try { dir.click(); n++; } catch {} });
      return n;
    });
    await page.waitForTimeout(1500);
    console.log(`  Expand 'all': clicked ${n} dir items`);
  }
}

async function collectLinksBySel(page, linkSel) {
  return await page.evaluate((sel) => {
    const map = {};
    document.querySelectorAll(sel).forEach(a => {
      const href = a.getAttribute('href');
      if (!href) return;
      try {
        const u = new URL(href, location.href); u.hash = '';
        const full = u.href;
        if (!map[full]) map[full] = (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 100);
      } catch {}
    });
    return map;
  }, linkSel);
}

async function collectPageLinks(page) {
  return await collectLinksBySel(page, 'a[href*="/docv2/page/"], a[href*="/docv2/faqs/"]');
}

async function extractContent(page, linkText) {
  const CONTENT_SELS = ["[class*='markdown-container']", '#doc_scroll_container', '#scrollIntersectionCenter', "[class*='faq-content']", "[class*='faq']", 'main', 'article'];
  const REMOVE_SELS = ['script', 'style', 'noscript', 'svg', "[class*='feedback-btn']", "[class*='feedback-action']", "[class*='page-navigator']", "[class*='breadcrumb']", "[class*='side-menu']", "[class*='float-toolbar']", "header", "footer", "nav", "aside"];
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
          if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) e.removeAttribute(a.name);
        });
      });
      const rawTitle = (document.title || '').trim();
      const h1Text = (document.querySelector('h1')?.textContent || '').trim();
      const GENERIC = ['DocumentsNew', 'Documents', 'TikTok Shop Partner', ''];
      let bestTitle = h1Text || fallback || rawTitle;
      if (GENERIC.includes(bestTitle)) bestTitle = fallback || h1Text || rawTitle;
      return { title: rawTitle, h1: h1Text, html: clone.innerHTML, textLen: (node.textContent || '').trim().length, bestTitle };
    },
    { contentSels: CONTENT_SELS, removeSels: REMOVE_SELS, fallback: linkText || '' }
  );
}

async function goto(page, url) {
  try { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT }); }
  catch { await page.goto(url, { waitUntil: 'domcontentloaded', timeout: NAV_TIMEOUT }).catch(() => {}); }
  await page.waitForSelector("[class*='markdown-container']", { timeout: 12000 }).catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(WAIT_MS);
}

async function dismissModals(page) {
  await page.evaluate(() => {
    document.querySelectorAll('[class*="arco-modal-wrapper"]').forEach(el => { try { el.style.display = 'none'; } catch {} });
  });
}

(async () => {
  fs.mkdirSync(OUT_DIR, { recursive: true });

  // Find already-done slugs
  const existingFiles = new Set(
    fs.readdirSync(OUT_DIR).filter(f => f.endsWith('.md') && f !== 'INDEX.md' && f !== 'README.md')
  );
  console.log(`Already saved: ${existingFiles.size} files`);

  // Load existing manifest if present
  let existingPages = [];
  if (fs.existsSync(MANIFEST)) {
    try {
      const m = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
      existingPages = m.pages || [];
      console.log(`Loaded ${existingPages.length} pages from manifest`);
    } catch {}
  }
  const existingUrls = new Set(existingPages.map(p => p.url));

  const browser = await chromium.launch({ headless: true, args: ['--disable-blink-features=AutomationControlled', '--no-sandbox'] });
  const context = await browser.newContext({
    userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 },
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

  // ===== PHASE 1: Collect all URLs =====
  console.log('\n=== PHASE 1: Collecting URLs ===\n');
  const urlMap = {};

  console.log('Loading base page...');
  await goto(navPage, BASE_URL);

  for (let tabIdx = 0; tabIdx < TAB_NAMES.length; tabIdx++) {
    const tabName  = TAB_NAMES[tabIdx];
    const tabId    = `arco-tabs-0-tab-${tabIdx}`;
    const strategy = TAB_EXPAND[tabIdx];
    const linkSel  = TAB_LINK_SEL[tabIdx];

    console.log(`\n--- Tab ${tabIdx}: "${tabName}" ---`);
    await dismissModals(navPage);

    const clicked = await navPage.evaluate((id) => {
      const el = document.getElementById(id);
      if (!el) return false;
      el.click();
      return true;
    }, tabId);

    if (!clicked) { console.log(`  Tab not found`); continue; }
    await navPage.waitForTimeout(2500);
    await dismissModals(navPage);

    await expandMenu(navPage, strategy);
    await navPage.waitForTimeout(500);

    const links = await collectLinksBySel(navPage, linkSel);
    console.log(`  Links: ${Object.keys(links).length}`);

    for (const [url, text] of Object.entries(links)) {
      if (!urlMap[url]) urlMap[url] = { text, category: tabName };
    }
  }

  console.log(`\nTotal URLs: ${Object.keys(urlMap).length}`);

  // ===== PHASE 2: Crawl remaining URLs =====
  console.log('\n=== PHASE 2: Crawling new pages ===\n');

  const pages = [...existingPages]; // start with existing
  const visited = new Set(existingUrls);
  const bfsQueue = [...Object.keys(urlMap)];
  const bfsQueued = new Set(bfsQueue);

  // Also add existing URLs to queued set so we don't re-add them
  for (const url of existingUrls) bfsQueued.add(url);

  let idx = 0;
  let newCount = 0;

  while (idx < bfsQueue.length && (visited.size < MAX_PAGES)) {
    const url = bfsQueue[idx++];
    if (visited.has(url)) continue;

    // Check if file already exists (by slug)
    const slug = slugify(url) + '.md';
    if (existingFiles.has(slug)) {
      visited.add(url);
      // Still need to add to pages if not in manifest
      if (!existingUrls.has(url)) {
        const meta = urlMap[url] || { text: '', category: 'Unknown' };
        // Read title from existing file
        try {
          const content = fs.readFileSync(path.join(OUT_DIR, slug), 'utf8');
          const titleMatch = content.match(/^# (.+)$/m);
          const title = titleMatch ? titleMatch[1] : meta.text || url.split('/').pop();
          pages.push({ url, title, file: slug, category: meta.category, textLen: 0 });
        } catch {}
      }
      continue;
    }

    visited.add(url);
    const meta = urlMap[url] || { text: '', category: 'Unknown' };

    try {
      await goto(navPage, url);

      // BFS discovery
      const extra = await collectPageLinks(navPage);
      for (const [lu, lt] of Object.entries(extra)) {
        if (isDocUrl(lu) && !bfsQueued.has(lu)) {
          bfsQueued.add(lu);
          bfsQueue.push(lu);
          if (!urlMap[lu]) urlMap[lu] = { text: lt, category: meta.category };
        }
      }

      const content = await extractContent(navPage, meta.text);
      let title = content.bestTitle || meta.text || url.split('/').pop();
      if (!title || /^DocumentsNew$/i.test(title) || /^Documents$/i.test(title)) {
        title = meta.text || url.split('/').pop();
      }

      let md = td.turndown(content.html || '');
      md = md.replace(/\n{3,}/g, '\n\n').trim();

      const file = slugify(url) + '.md';
      const full = `# ${title}\n\n> Source: ${url}\n> Section: ${meta.category}\n> Scraped: ${new Date().toISOString()}\n\n---\n\n${md}\n`;

      fs.writeFileSync(path.join(OUT_DIR, file), full);
      pages.push({ url, title, file, category: meta.category, textLen: content.textLen });
      newCount++;

      console.log(`[${visited.size}/${bfsQueue.length}] NEW ${content.textLen}ch [${meta.category}] ${url.split('/').pop()}`);
    } catch (e) {
      console.error(`ERR [${meta.category}] ${url}: ${e.message.slice(0, 80)}`);
      pages.push({ url, title: meta.text || '(error)', file: null, category: meta.category, error: e.message });
    }
  }

  console.log(`\nNew pages crawled: ${newCount}`);

  // ===== PHASE 3: Write output =====
  console.log('\n=== PHASE 3: Writing INDEX and manifest ===');

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
      if (p.file) indexLines.push(`- [${p.title}](./${p.file}) — ${p.url}`);
      else indexLines.push(`- (FAILED) ${p.url}`);
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

  console.log(`\nDONE. ${successCount} pages total -> ${OUT_DIR}`);
  for (const [cat, catPages] of Object.entries(byCategory)) {
    console.log(`  ${cat}: ${catPages.filter(p => p.file).length} pages`);
  }

  await browser.close();
})();
