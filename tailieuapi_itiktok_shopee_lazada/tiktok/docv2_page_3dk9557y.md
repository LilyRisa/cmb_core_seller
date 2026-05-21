# Bestsellers Analytics Open API Launch

> Source: https://partner.tiktokshop.com/docv2/page/3dk9557y
> Section: Changelog
> Scraped: 2026-05-21T00:48:41.091Z

---

## Overview

This release introduces the **Bestsellers Analytics OpenAPI**, a set of four new APIs that provide programmatic access to TikTok Shop's Bestsellers rankings from Data Compass. Developers can now retrieve the top 100 products, creators, videos, and LIVE sessions by GMV in the seller's authorized market.  
Previously, Bestsellers data was only available through the Seller Center web portal with no API access. Third-party data service providers — one of the most influential app categories in the TikTok Shop ecosystem — had no compliant way to access this data. This release enables ISVs across multiple app categories (including **Analytics & Reporting**, **ERP**, **Connector**, **Creator collaborations** and **TikTok Shop Seller** apps) to build data-driven features such as trending product discovery, top creator analysis, viral video insights, and competitive benchmarking.

## What's New

### Affected APIs

| Name | Path | Type | Version | Description |
| --- | --- | --- | --- | --- |
| 
[Get Bestselling Products](https://partner.tiktokshop.com/docv2/page/get-bestselling-products-202511)

 | 

\[GET\]/analytics/202511/products/bestselling

 | 

RESTful API

 | 

202511

 | 

Get the top 100 performing products of the target date range.

 |
| 

[Get Bestselling Creators](https://partner.tiktokshop.com/docv2/page/get-bestselling-creators-202511)

 | 

\[GET\]/analytics/202511/creators/bestselling

 | 

RESTful API

 | 

202511

 | 

Get the top 100 performing creators of the target date range.

 |
| 

[Get Bestselling Videos](https://partner.tiktokshop.com/docv2/page/get-bestselling-videos-202511)

 | 

\[GET\]/analytics/202511/videos/bestselling

 | 

RESTful API

 | 

202511

 | 

Get the top 100 performing videos of the target date range.

 |
| 

[Get Bestselling LIVEs](https://partner.tiktokshop.com/docv2/page/get-bestselling-lives-202511)

 | 

\[GET\]/analytics/202511/lives/bestselling

 | 

RESTful API

 | 

202511

 | 

Get the top 100 performing lives of the target date range.

 |

### Important Notes

-   **Authorization required:** All four APIs require a valid seller OAuth access token (x-tts-access-token). The data returned is scoped to the seller's registered market.
-   **GMV desensitization:** All GMV values are returned as ranges with a random offset applied for privacy protection. Exact GMV figures are not available.
-   **Top 100 limit:** Each API returns a maximum of 100 records per request.
-   **Time granularity:** The minimum granularity is daily (1D). Supported time slots are 1D, 7D, and 30D.
-   **Currency options:** Responses support USD (US dollars) or LOCAL (local currency) via the currency query parameter.
-   **API version:** All four APIs share the version 202511.
-   **Access level:** Public — available to all ISVs and sellers who request the Bestsellers scope.
