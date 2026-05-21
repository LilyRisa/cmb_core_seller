# Shopee Open Platform — Developer Documentation

## What is Shopee Open Platform?

Shopee Open Platform is Shopee's developer ecosystem that provides Open APIs and services for developers building tools to serve Shopee sellers. It offers:

- **Powerful APIs**: A wide range of Open APIs covering orders, products, marketing, logistics, payments, and more
- **Push Notifications**: Real-time push mechanism for order status changes, product updates, marketing events, and more
- **Developer Services**: End-to-end developer support including onboarding, app development, sandbox testing, and maintenance
- **Multi-Market Support**: Single API set works across all Shopee markets (Southeast Asia, Brazil, etc.)

## What Was Captured

This documentation was scraped from https://open.shopee.com on **2026-05-21** using a Playwright headless browser crawler (required because the site blocks plain HTTP/AI fetch requests).

### Sections Captured

| Section | Pages | Description |
|---------|-------|-------------|
| Developer Guide | 48 pages | Core developer documentation covering authentication, API calls, product management, order management, integration guides, and platform policies |
| Push Mechanism | 34 pages | Complete push notification reference — all 34 push event types with parameters, JSON samples, and update logs |
| API Reference Module Index | 1 page | Index of 28 API modules with 406 individual API methods listed |

**Total: 83 documentation pages** (plus INDEX.md and README.md)

### Developer Guide Topics

The 48 Developer Guide pages cover:
- Authorization & Authentication (OAuth flow, token management)
- Developer account registration and app management
- API call structure, parameters, and error codes
- Sandbox testing environment
- Product management (creation, variants, global products, stock/price)
- Order management and fulfillment
- Return and refund management
- Logistics and first mile binding
- Advertising and marketing APIs
- Platform-specific integration guides (Instant Mart, Livestream, AMS, Video)
- Regional guides (Brazil: logistics, NF-e, Fulfilled by Shopee)
- Partner programs and policies (Terms of Service, Data Protection, Platform Partner Rules)

### Push Mechanism Topics

The 34 Push Mechanism pages document all real-time webhook push events:
- **Product Push** (6): stock changes, video uploads, brand registration, violations, price updates
- **Order Push** (9): order status, tracking numbers, shipping documents, booking, package info
- **Return Push** (1): return/refund updates
- **Marketing Push** (2): item promotions, promotion updates
- **Shopee Push** (6): platform updates, authorization expiry, shop authorization, shop penalties
- **Webchat Push** (1): chat notifications
- **Consignment Service Push** (4): inbound status, supplier products, purchase orders
- **Fulfillment by Shopee Push** (5): FBS stock, Brazil-specific invoice/block events

### API Reference

The API Reference module index (`API-REFERENCE-MODULES.md`) lists all 28 API modules with their individual methods (406 total). The full API Reference is a large system with hundreds of detailed method pages available at https://open.shopee.com/documents/v2/ — only the module index was captured, not individual method pages.

## How Documentation Was Generated

1. A Node.js crawler using Playwright (headless Chromium) loaded each page
2. The Developer Guide sidebar was expanded through multiple passes to reveal all 48 entries identified by `[data-ts-content_id]` attributes
3. The Push Mechanism uses a single-page app structure; the crawler clicked each of 34 nav items and extracted content from the detail panel
4. The API Reference module list was extracted by expanding the left sidebar categories
5. HTML content was converted to Markdown using TurndownService with GFM (GitHub Flavored Markdown) tables
6. All pages saved to this directory with descriptive filenames

**Crawler scripts**: `scrape/crawl-shopee.js` (Developer Guide), `scrape/crawl-shopee-push.js` (Push Mechanism), `scrape/crawl-shopee-api-modules.js` (API Reference modules)

## Table of Contents

- [INDEX.md](./INDEX.md) — Complete index of all scraped pages, organized by section and category

### Developer Guide (quick links)
- [Introduction](./4-introduction.md)
- [Authorization and Authentication](./20-authorization-and-authentication.md)
- [API calls](./16-api-calls.md)
- [Push Mechanism notifications](./18-push-mechanism-notifications.md)
- [Sandbox Testing V2](./644-sandbox-testing-v2.md)
- [Order Management](./229-order-management.md)

### Push Mechanism (quick links)
- [order_status_push](./push-007-order-status-push.md)
- [shop_authorization_push](./push-021-shop-authorization-push.md)
- [webchat_push](./push-025-webchat-push.md)

### API Reference
- [API-REFERENCE-MODULES.md](./API-REFERENCE-MODULES.md) — 28 modules, 406 methods indexed
