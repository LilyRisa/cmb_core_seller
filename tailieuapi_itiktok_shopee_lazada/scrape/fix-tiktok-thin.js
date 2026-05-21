/*
 * fix-tiktok-thin.js
 * Re-extracts TikTok doc pages whose .md body came out empty/thin.
 * Scans ../tiktok for *.md files smaller than THRESHOLD bytes, re-fetches the
 * Source URL, extracts content from the API-doc / markdown containers (with a
 * content-ready wait loop), and rewrites the file preserving its header block.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const DIR = path.resolve(__dirname, '../tiktok');
const THRESHOLD = 400;
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-' });
td.use(gfm.gfm);
td.addRule('br', { filter: ['br'], replacement: () => '  \n' });

const CONTENT = ['[class*="markdown-container"]', '[id^="api-doc-"]', '.editor-canvas.render-container', '#scrollIntersectionCenter'];

(async () => {
  const files = fs.readdirSync(DIR)
    .filter((f) => f.endsWith('.md') && !['INDEX.md', 'README.md'].includes(f) && !f.startsWith('_'))
    .map((f) => ({ f, full: path.join(DIR, f) }))
    .filter((o) => fs.statSync(o.full).size < THRESHOLD);

  console.log('Thin files to fix:', files.length);

  const b = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await b.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => undefined }));
  await ctx.route('**/*', (r) => { const t = r.request().resourceType(); if (t === 'image' || t === 'media' || t === 'font') return r.abort(); return r.continue(); });
  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(45000);

  let fixed = 0, still = 0;
  for (const o of files) {
    const head = fs.readFileSync(o.full, 'utf8').split('\n').slice(0, 7);
    const titleLine = head.find((l) => l.startsWith('# ')) || '# (untitled)';
    const srcLine = head.find((l) => l.startsWith('> Source:')) || '';
    const secLine = head.find((l) => l.startsWith('> Section:')) || '';
    const url = srcLine.replace('> Source:', '').trim();
    if (!url) { console.log('no url for', o.f); continue; }
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded' });
      await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
      // content-ready poll: wait until a container has real text
      let data = null;
      for (let t = 0; t < 12; t++) {
        await page.waitForTimeout(1000);
        data = await page.evaluate((CONTENT) => {
          let node = null;
          for (const s of CONTENT) { const el = document.querySelector(s); if (el && (el.textContent || '').trim().length > 200) { node = el; break; } }
          if (!node) return { len: 0 };
          const clone = node.cloneNode(true);
          clone.querySelectorAll('script,style,noscript,svg,[class*="scribe-feedback"],[class*="feedback"]').forEach((e) => e.remove());
          clone.querySelectorAll('*').forEach((el) => [...el.attributes].forEach((a) => { if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name)) el.removeAttribute(a.name); }));
          return { len: (node.textContent || '').trim().length, html: clone.innerHTML };
        }, CONTENT);
        if (data.len > 200) break;
      }
      if (!data || data.len <= 200) { console.log('STILL EMPTY', url); still++; continue; }
      let md = td.turndown(data.html || '').replace(/\n{3,}/g, '\n\n').trim();
      const body = `${titleLine}\n\n${srcLine}\n${secLine}\n> Scraped: ${new Date().toISOString()}\n\n---\n\n${md}\n`;
      fs.writeFileSync(o.full, body);
      fixed++;
      console.log(`FIXED ${data.len}c  ${url} -> ${o.f}`);
    } catch (e) {
      console.error('ERR', url, e.message);
      still++;
    }
  }
  await b.close();
  console.log(`\nDONE. fixed ${fixed}, still-empty ${still}`);
})();
