/*
 * TikTok Shop Partner docs — DOM inspector
 * Finds category switcher, enumerates all menu sections, collects all doc URLs
 * Usage: node inspect-tiktok.js
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'https://partner.tiktokshop.com/docv2/page/tts-developer-types';
const OUT  = path.join(__dirname, 'tiktok-inspect-result.json');

(async () => {
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-blink-features=AutomationControlled'],
  });
  const ctx = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 900 },
    locale: 'en-US',
    extraHTTPHeaders: { 'Accept-Language': 'en-US,en;q=0.9' },
  });
  await ctx.addInitScript(() => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
  });

  const page = await ctx.newPage();
  console.log('Loading page...');
  await page.goto(BASE, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Dump top-level DOM structure to find category switcher
  const domDump = await page.evaluate(() => {
    // Find all elements with 'select', 'dropdown', 'cascader', 'menu' in their class or id
    const candidates = [];
    document.querySelectorAll('*').forEach(el => {
      const cls = (el.className || '').toString();
      const id = el.id || '';
      if (/(select|dropdown|cascader|switcher|nav|category|docset)/i.test(cls + id)) {
        const text = (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 200);
        candidates.push({
          tag: el.tagName,
          id,
          cls: cls.slice(0, 120),
          text: text.slice(0, 100),
          childCount: el.children.length,
        });
      }
    });
    return candidates.slice(0, 40);
  });
  console.log('DOM candidates with select/dropdown/nav keywords:');
  console.log(JSON.stringify(domDump, null, 2));

  // Look specifically for top-left navigation / header area
  const headerDump = await page.evaluate(() => {
    // Try to find header / top-bar
    const selectors = [
      'header',
      '.header',
      '[class*="header"]',
      '[class*="top-bar"]',
      '[class*="topbar"]',
      '[class*="nav-bar"]',
      '[class*="navbar"]',
      '[class*="doc-header"]',
      '[class*="page-header"]',
    ];
    const results = [];
    for (const sel of selectors) {
      const el = document.querySelector(sel);
      if (el) {
        results.push({
          selector: sel,
          html: el.outerHTML.slice(0, 3000),
        });
      }
    }
    return results;
  });

  // Find all select/dropdown elements
  const dropdowns = await page.evaluate(() => {
    const results = [];
    // Look for arco components
    const arcoSels = ['[class*="arco-select"]', '[class*="arco-menu"]', '[class*="arco-cascader"]',
                      '[class*="arco-dropdown"]', '[class*="doc-select"]', '[class*="category-select"]'];
    for (const sel of arcoSels) {
      const els = document.querySelectorAll(sel);
      if (els.length > 0) {
        results.push({
          selector: sel,
          count: els.length,
          sample: els[0] ? els[0].outerHTML.slice(0, 500) : '',
        });
      }
    }
    return results;
  });
  console.log('\nArco dropdowns/selects found:');
  console.log(JSON.stringify(dropdowns, null, 2));

  // Dump ALL links on the page
  const allLinks = await page.evaluate(() => {
    const links = [];
    const seen = new Set();
    document.querySelectorAll('a[href]').forEach(a => {
      const href = a.getAttribute('href');
      if (!seen.has(href)) {
        seen.add(href);
        links.push({ href, text: (a.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 80) });
      }
    });
    return links;
  });
  console.log(`\nTotal unique links: ${allLinks.length}`);
  const docLinks = allLinks.filter(l => l.href && l.href.includes('/docv2/'));
  console.log(`Doc links (/docv2/): ${docLinks.length}`);
  console.log(JSON.stringify(docLinks, null, 2));

  // Full page HTML dump (first 20000 chars)
  const bodyHtml = await page.evaluate(() => document.body.innerHTML.slice(0, 30000));

  // Save results
  const result = {
    domDump,
    headerDump,
    dropdowns,
    allLinks,
    docLinks,
    bodyHtmlPreview: bodyHtml.slice(0, 5000),
  };
  fs.writeFileSync(OUT, JSON.stringify(result, null, 2));
  console.log('\nResults saved to', OUT);

  await browser.close();
})();
