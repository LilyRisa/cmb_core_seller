# TikTok Shop Partner Documentation

Offline mirror of the [TikTok Shop Partner Center developer documentation](https://partner.tiktokshop.com/docv2/).

**628 pages** captured across 6 sections. See [INDEX.md](./INDEX.md) for the full page listing.

> Note: 6 pages are near-empty placeholders on the live site (the public page body is only nav chrome); their files contain the title + source URL only.

---

## How it was generated

A Playwright (Chromium) headless browser crawl was run on 2026-05-20 / 2026-05-21 against `https://partner.tiktokshop.com/docv2/`. JavaScript rendering was required because the site is a React SPA that blocks plain HTTP fetch and AI user-agents.

The crawler iterated all 8 navigation tabs (Partner Guide, Developer Guide, API Reference, Webhooks, Terms and Policies, Changelog, FAQs, API Testing Tool), expanded the sidebar tree for each section, collected all unique `/docv2/page/` and `/docv2/faqs/` URLs, then visited each page and extracted the main content container. HTML was converted to Markdown using Turndown + turndown-plugin-gfm.

Scripts used (in `../scrape/`):
- `crawl-tiktok.js` — initial full crawl
- `crawl-tiktok-resume.js` — resume after timeout
- `recrawl-api-pages.js` — re-crawl API Reference pages to fix content extraction bug
- `recrawl-all-thin.js` — re-crawl remaining thin pages
- `cleanup-tiktok.js` — post-processing (remove base64 images, feedback widgets)

---

## Sections captured

### Partner Guide (85 pages)

Guides for TikTok Shop Partners (TSPs), Creator Agency Partners (CAPs), Affiliate Partners (TAPs), and Multi-Channel Networks (MCNs). Covers partner registration, service categories, Partner Center Console features, creator management, seller leads, and commission workflows.

### Developer Guide (37 pages)

Technical onboarding for developers: app creation and management, API concepts, OAuth authorization flow (Get Access Token / Refresh Token), request signing, rate limits, API versioning, API SDK (Java, Go, Node.js), Widgets SDK, and the API Testing Tool.

### API Reference (305 pages)

Full REST API reference for all TikTok Shop open APIs, organized by resource domain:

| Domain | Coverage |
| --- | --- |
| Authorization | Get/Refresh Access Token, shop cipher |
| Orders | Search, get detail, confirm, cancel, split, RTS |
| Products | Create, edit, search, categories, attributes, brand, compliance |
| Logistics / Fulfillment | Shipping providers, label generation, tracking, warehouse |
| Returns & Cancellations | Search returns, reject, search cancellations |
| Promotions | Flash sale, discount, voucher, gift-with-purchase, free sample |
| Finance | Statement transactions, seller income, order settlements |
| Customer Service | Conversations, messages, agent assignments |
| Analytics | Shop analytics, product analytics, video/LIVE analytics, bestsellers |
| Seller Shop | Shop info, authorized shops |
| Webhooks | Webhook management APIs |
| LIVE | Live stream data APIs |

Each API page includes: HTTP method and endpoint path, required scopes, request headers and query/body parameters with types and descriptions, example cURL/Go/Node.js/Java requests, response schema and example JSON, and error codes.

### Webhooks (43 pages)

Event-driven webhook reference: webhook configuration guide, and per-event payload schemas covering Order status change, Package update, Product status/information/category change, Cancellation, Recipient address update, Shoppable content posting, Invoice status change, Creator deauthorization, FBT events, IM messages, and more.

### Changelog (147 pages)

Chronological release notes and API change announcements by market (US, SEA, EU, UK, ID, PH, TH, BR, JP, MX) covering new API versions, deprecations, new fields, new endpoints, and breaking changes.

### FAQs (11 pages)

Frequently asked questions organized by topic: Registration, Onboarding Process, Authorization, Order API, Product API, Logistic API, Other API, Error Code, Sandbox, US Sandbox, and Linked Creator.

---

## File format

Each page is a Markdown file (`docv2_page_<slug>.md` or `docv2_faqs_<slug>.md`) with a standard header:

```
# Page Title

> Source: https://partner.tiktokshop.com/docv2/page/<slug>
> Section: <Category>
> Scraped: <ISO timestamp>

---

<page content as Markdown>
```

The manifest (`_manifest.json`) records URL, title, file name, category, and text length for every captured page.
