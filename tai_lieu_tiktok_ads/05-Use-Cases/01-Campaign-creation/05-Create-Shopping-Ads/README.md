---
title: "Create Shopping Ads"
doc_id: 1741942179356738
path: "Use Cases / Campaign creation / Create Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Shopping Ads

This article walks you through the steps to create Shopping Ads.  

> <span style="color:DarkRed">**Important**</span><br>
> Starting Q3, 2025, [GMV Max](https://business-api.tiktok.com/portal/docs?id=1822009058467842) will be the default and only supported campaign type for TikTok Shop Ads. If you create ads using the Product Sales objective and TikTok Shop as your sales destination, you will no longer be able to use the API to create, edit, or duplicate [LIVE Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554), [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), or [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942291730434). Please migrate to the [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465) as early as possible. If you have questions or need support, please contact your TikTok representative. Learn more about [the detailed changes by ad type and endpoint](https://business-api.tiktok.com/portal/docs?id=1837161048383489).

# Introduction
Shopping Ads is a catalog powered, smarter, and simplified paid advertising solution designed to drive maximum impact for client's business. It is designed to address our retail advertisers' pain points, such as buying complexity, untapped catalog potential, etc. 

Shopping Ads consolidates the following major functions into one simple buying objective — **Product Sales**: 
* **Video Shopping Ads (for Catalog or for TikTok Shop)**: A smart, catalog-driven solution that surfaces shoppable video creative in the For Your Page. See [here](https://ads.tiktok.com/help/article?aid=10012438) for more details. 

> <span style="color: DodgerBlue">**Note**</span><br>If you have created Collection Ads or Dynamic Showcase Ads before Feb.16, 2023, then to ensure a smooth migration journey, we recommend you [migrate to Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1754726939130882&rid=24jiu7kp2km) as early as possible. 

* **Live Shopping Ads**: Drive incremental traffic to organic Live shopping experiences with this ad solution. See [here](https://ads.tiktok.com/help/article/getting-started-live-shopping-ads?lang=en&redirected=2) for more details.

* **Product Shopping Ads (for TikTok Shop)**: Leverage product images and [Shopping Center and Search](https://ads.tiktok.com/help/article/about-onsite-shopping-placements?lang=en) traffic to promote products, catering to clients focused on goods marketing without ad creatives. See [here](https://ads.tiktok.com/help/article/advertise-using-product-images-from-tiktok-shop) for more details.

* **Catalog Listing Ads** (To be deprecated soon): An entirely new solution that pulls product information directly from your catalog and promote your products across TikTok with experiences like “Recommended Products” and “Related Products".  

**You can use Campaign Management API to create Shopping Ads, and this helps streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to Tiktok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- [Video Shopping Ads (for Catalog)](https://business-api.tiktok.com/portal/docs?id=1750361698613249) is currently an allowlist-only feature for Commerce App advertisers. If you are a Commerce App advertiser or a non-Commerce advertiser and would like to access it, please contact your TikTok representative. If you are a Web Commerce advertiser, you automatically get access to the feature.

# Steps
- See [Create Video Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942291730434) to find out how to create Video Shopping Ads with products from catalogs, TikTok Commerce, or Showcase.
- See [Create Live Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1754162402455554) to find out how to create Live Shopping Ads. 
- See [Create Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401) to find out how to create Product Shopping Ads.

# Related docs
- [Campaign Management API](https://ads.tiktok.com/marketing_api/docs?id=1739381823123458)
- [Campaign Management Guide](https://ads.tiktok.com/marketing_api/docs?id=1735713781404673)

