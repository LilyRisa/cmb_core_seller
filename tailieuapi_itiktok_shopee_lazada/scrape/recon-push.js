/*
 * Recon script for Push Mechanism page structure
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SEED = 'https://open.shopee.com/push-mechanism/5';
const OUT = path.resolve(__dirname, '../shopee');
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

(async () => {
  const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
  const ctx = await browser.newContext({ userAgent: UA, locale: 'en-US', viewport: { width: 1440, height: 900 } });
  await ctx.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () => undefined }));
  const page = await ctx.newPage();
  page.setDefaultNavigationTimeout(45000);

  console.log('Loading push mechanism page...');
  await page.goto(SEED, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(3000);

  // Expand toggles
  for (let pass = 0; pass < 5; pass++) {
    const toggles = await page.$$('.sidebar-item__icon--expand, [class*="expand"]');
    let clicked = 0;
    for (const t of toggles) {
      try { if (await t.isVisible()) { await t.click({ timeout: 1200 }).catch(() => {}); clicked++; } } catch {}
    }
    await page.waitForTimeout(800);
    if (!clicked) break;
  }

  const info = await page.evaluate(() => {
    // Get all classes in the DOM
    const allClasses = new Set();
    document.querySelectorAll('[class]').forEach(el => {
      el.className.split(/\s+/).forEach(c => c && allClasses.add(c));
    });

    // Look for any content_id attributes
    const contentIds = [];
    document.querySelectorAll('[data-ts-content_id]').forEach(el => {
      contentIds.push({
        id: el.getAttribute('data-ts-content_id'),
        name: el.getAttribute('data-ts-content_name') || el.textContent.trim().slice(0, 50)
      });
    });

    // Get all data-* attributes
    const dataAttrs = new Set();
    document.querySelectorAll('*').forEach(el => {
      Array.from(el.attributes).forEach(a => {
        if (a.name.startsWith('data-')) dataAttrs.add(a.name);
      });
    });

    // Get sidebar structure
    const sidebar = document.querySelector('.sidebar, [class*="sidebar"], [class*="menu"], nav');
    const sidebarInfo = sidebar ? {
      className: sidebar.className,
      childCount: sidebar.children.length,
      html: sidebar.innerHTML.slice(0, 2000)
    } : null;

    // Find all links
    const links = [];
    document.querySelectorAll('a[href*="push-mechanism"]').forEach(el => {
      links.push({ href: el.getAttribute('href'), text: el.textContent.trim().slice(0, 50) });
    });

    // Find all clickable sidebar items
    const sidebarItems = [];
    document.querySelectorAll('.sidebar-item, [class*="sidebar-item"]').forEach(el => {
      const attrs = {};
      Array.from(el.attributes).forEach(a => attrs[a.name] = a.value);
      sidebarItems.push({
        className: el.className.slice(0, 100),
        attrs,
        text: el.textContent.trim().slice(0, 50)
      });
    });

    return {
      title: document.title,
      url: window.location.href,
      contentIds,
      dataAttrs: Array.from(dataAttrs).sort(),
      sidebarInfo,
      links,
      sidebarItems: sidebarItems.slice(0, 20),
      relevantClasses: Array.from(allClasses).filter(c =>
        c.includes('sidebar') || c.includes('menu') || c.includes('nav') ||
        c.includes('content') || c.includes('guide') || c.includes('push')
      ).sort()
    };
  });

  fs.writeFileSync(path.join(OUT, '_push-recon.json'), JSON.stringify(info, null, 2));
  console.log('Title:', info.title);
  console.log('URL:', info.url);
  console.log('ContentIDs found:', info.contentIds.length);
  console.log('Data attributes:', info.dataAttrs.join(', '));
  console.log('Relevant classes:', info.relevantClasses.join(', '));
  console.log('Links found:', info.links.length);
  console.log('Sidebar items:', info.sidebarItems.length);

  if (info.sidebarItems.length > 0) {
    console.log('\nSidebar items sample:');
    info.sidebarItems.slice(0, 5).forEach(item => {
      console.log(' -', item.className, JSON.stringify(item.attrs), item.text);
    });
  }

  if (info.links.length > 0) {
    console.log('\nPush links:');
    info.links.forEach(l => console.log(' -', l.href, ':', l.text));
  }

  await browser.close();
})();
