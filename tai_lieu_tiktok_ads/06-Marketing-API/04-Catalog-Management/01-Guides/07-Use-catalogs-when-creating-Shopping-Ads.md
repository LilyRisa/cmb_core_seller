---
title: "Use catalogs when creating Shopping Ads"
doc_id: 1754263154550786
path: "Marketing API / Catalog Management / Guides / Use catalogs when creating Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Use catalogs when creating Shopping Ads

Shopping Ads is a catalog powered, smarter, and simplified paid advertising solution designed to drive maximum impact for clients' business. It consolidates Video Shopping Ads, Catalog Listing Ads, and Live Shopping Ads into one simple buying objective — Product Sales. Follow the following steps to create Shopping Ads.  


<strong>For the  specific description of API endpoints and code examples of each step, please see [Shopping Ads](https://ads.tiktok.com/marketing_api/docs?id=1741942179356738&rid=6pdx02ym97f). </strong>

1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).
2. Create and manage catalogs using Catalog API:
  	1. Create a catalog using [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961).
  	2. Upload products to the catalog using [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) or [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153).
  	3. Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). You can also choose the `advertiser_role` that you want to assign to the members invited.
  4. Share a catalog with members and grant the catalog management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.
3. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).
4. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).
5. After the ad is created, preview your ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426).
