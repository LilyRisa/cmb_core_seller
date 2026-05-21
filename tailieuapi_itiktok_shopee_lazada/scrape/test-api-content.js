/*
 * Examine what's in the API page content containers more carefully
 */
const { chromium } = require('playwright');
const TurndownService = require('turndown');
const gfm = require('turndown-plugin-gfm');

const URL = 'https://partner.tiktokshop.com/docv2/page/get-order-detail-202507';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

const td = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced', bulletListMarker: '-' });
td.use(gfm.gfm);

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'] });
  const ctx = await browser.newContext({ userAgent: UA, viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => { Object.defineProperty(navigator, 'webdriver', { get: () => undefined }); });
  const page = await ctx.newPage();

  await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Check the structure of #doc_scroll_container
  const docScrollInfo = await page.evaluate(() => {
    const el = document.querySelector('#doc_scroll_container');
    if (!el) return { found: false };
    return {
      found: true,
      textLen: (el.textContent || '').length,
      childrenInfo: [...el.children].map(c => ({
        tag: c.tagName,
        cls: (c.className || '').toString().slice(0, 80),
        textLen: (c.textContent || '').length,
      })),
      innerHTML: el.innerHTML.slice(0, 5000),
    };
  });

  console.log('doc_scroll_container textLen:', docScrollInfo.textLen);
  console.log('children:', JSON.stringify(docScrollInfo.childrenInfo, null, 2));

  // Now get the full HTML with proper content
  const html = await page.evaluate(() => {
    const el = document.querySelector('#doc_scroll_container') ||
               document.querySelector('#scrollIntersectionCenter');
    if (!el) return '';
    const clone = el.cloneNode(true);
    // Remove script, style, svg
    clone.querySelectorAll('script, style, noscript').forEach(e => e.remove());
    // Remove img with base64 src
    clone.querySelectorAll('img[src^="data:"]').forEach(e => e.remove());
    // Remove the feedback widget
    clone.querySelectorAll('[class*="scribe-feedback"]').forEach(e => e.remove());
    // Remove the sidebar
    clone.querySelectorAll('[class*="side-menu"]').forEach(e => e.remove());
    // Remove attributes except key ones
    clone.querySelectorAll('*').forEach(e => {
      [...e.attributes].forEach(a => {
        if (!['href', 'src', 'colspan', 'rowspan', 'alt', 'title'].includes(a.name))
          e.removeAttribute(a.name);
      });
    });
    return clone.innerHTML;
  });

  console.log('\nHTML preview (first 3000):');
  console.log(html.slice(0, 3000));

  // Try turning this into markdown
  const md = td.turndown(html);
  console.log('\nMarkdown preview (first 2000):');
  console.log(md.slice(0, 2000));

  await browser.close();
})();
