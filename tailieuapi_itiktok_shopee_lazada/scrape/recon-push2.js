/*
 * Detailed recon for Push Mechanism page structure
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

  // Save full HTML for analysis
  const html = await page.content();
  fs.writeFileSync(path.join(OUT, '_push-page.html'), html);
  console.log('Saved full HTML, length:', html.length);

  const info = await page.evaluate(() => {
    // Look at nav-left-wrapper
    const navLeft = document.querySelector('.nav-left-wrapper');
    const navLeftHtml = navLeft ? navLeft.innerHTML.slice(0, 5000) : 'not found';

    // Look at nav-bar
    const navBar = document.querySelector('.nav-bar');
    const navBarHtml = navBar ? navBar.innerHTML.slice(0, 5000) : 'not found';

    // Get all nav-bar-items
    const navItems = [];
    document.querySelectorAll('.nav-bar-item, [class*="nav-bar-item"]').forEach(el => {
      navItems.push({
        className: el.className,
        text: el.textContent.trim().slice(0, 100),
        href: el.querySelector('a')?.getAttribute('href') || '',
        onclick: el.getAttribute('onclick') || '',
        id: el.id || ''
      });
    });

    // Get breadcrumb items (may indicate page structure)
    const breadcrumbs = [];
    document.querySelectorAll('.push-detail__breadcrumb-item, [class*="breadcrumb"]').forEach(el => {
      breadcrumbs.push(el.textContent.trim().slice(0, 100));
    });

    // Get push content details
    const pushDetails = document.querySelector('.push-mechainsm-detail, .push-mechanism-detail');
    const pushHtml = pushDetails ? pushDetails.innerHTML.slice(0, 3000) : 'not found';

    // Look at collapsible content
    const collapsible = document.querySelector('.collapsible-content-container');
    const collapsibleHtml = collapsible ? collapsible.innerHTML.slice(0, 3000) : 'not found';

    // Get page title/name
    const title = document.querySelector('.push-detail__title, .push-detail__name, h1')?.textContent?.trim() || document.title;

    return {
      title,
      navLeftHtml,
      navBarHtml,
      navItems,
      breadcrumbs,
      pushHtml,
      collapsibleHtml
    };
  });

  console.log('Title:', info.title);
  console.log('NavBar items:', info.navItems.length);
  info.navItems.forEach(item => console.log('  -', item.text, '|', item.href));
  console.log('\nBreadcrumbs:', info.breadcrumbs);
  console.log('\nNav Left HTML:', info.navLeftHtml.slice(0, 500));
  console.log('\nNav Bar HTML:', info.navBarHtml.slice(0, 500));
  console.log('\nCollapsible:', info.collapsibleHtml.slice(0, 500));

  fs.writeFileSync(path.join(OUT, '_push-recon2.json'), JSON.stringify(info, null, 2));
  await browser.close();
})();
