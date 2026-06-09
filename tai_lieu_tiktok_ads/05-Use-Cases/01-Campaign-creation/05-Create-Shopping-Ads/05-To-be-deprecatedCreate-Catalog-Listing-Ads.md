---
title: "(To be deprecated)Create Catalog Listing Ads"
doc_id: 1742003478618114
path: "Use Cases / Campaign creation / Create Shopping Ads / (To be deprecated)Create Catalog Listing Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated)Create Catalog Listing Ads

This article introduces how to create Catalog Listing Ads.
> <span style="color: DodgerBlue">**Note**</span><br>We are ending our Catalog Listing Ads (CLA) soon and closing out all active CLA campaigns. In the coming months, we will be consolidating CLA with Video Shopping Ads into a unified Shopping Ads solution. We will have more to share on integration opportunities for this new solution in the near future and look forward to introducing new catalog-powered placements and formats to deliver more immediate value for our advertisers.

>  <span style="color:DodgerBlue">**Note**</span><br>
>  The flags of `Required`/ `Conditional` in the following parameter tables are only applicable for Catalog Listing Ads.

# 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). The following table lists the related parameters. 

>  <span style="color:DodgerBlue">**Note**</span><br>
>  Currently, Campaign Budget Optimization is not supported for Shopping Ads.

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID |
|objective_type {Required}|string| Advertising objective. Use `PRODUCT_SALES` for Shopping Ads. |
|budget_mode {+Conditional}|string|How you'd like to set your budget. For example, you can set a daily budget. To learn about how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114).|
|budget {+Conditional}|float| Campaign budget. Required when `budget_mode` is `BUDGET_MODE_DAY` or `BUDGET_MODE_TOTAL`. To learn about the minimum budget and how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
|campaign_name {Required}|string|Campaign name. It can contain up to 512 characters. Emoji is not supported. <br data-tomark-pass>Note: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.|
|campaign_type|string|Enums values: `REGULAR_CAMPAIGN`,` IOS14_CAMPAIGN`.<br> Note: `IOS14_CAMPAIGN` is not supported for Catalog Listing Ads.|
```
# 2. Create and manage catalogs
Create and manage catalogs using Catalog APIs or TikTok Business Center. 
* Catalog APIs:  For all catalog APIs, see [here](https://ads.tiktok.com/marketing_api/docs?id=1739578477445121). 
  1.  Create a catalog using [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961). 
  2.  Upload products to the catalog using [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) or [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153). 
  3. Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). You can also choose `advertiser_role` that you want to assign to the members invited.
  4. Share a catalog with members and grant the catalog management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field. 
* TikTok Business Center
  1. Create a catalog on TikTok Business Center. For detailed instructions, see [Manage Catalog in Business Center](https://ads.tiktok.com/help/article?aid=10012889)
  2. Upload your products to your catalog.
  3. Invite a member to your Business Center and grant the admin permission. For detailed instructions, see [Add Users to TikTok Business Center](https://ads.tiktok.com/help/article?aid=12790). 
  4. Choose the advertiser account to be shared, add members to the account and grant them the admin access. 
  5. Share a catalog with members and grant the catalog management access. For detailed instructions, see [Manage Catalog in Business Center - Share Catalog with a Member](https://ads.tiktok.com/help/article?aid=10012889).

# 3.  Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).  The following table lists the related parameters. 

>  <span style="color:DodgerBlue">**Note**</span><br>
> Automated Creative Optimization is not supported for Shopping Ads. <br>  Catalog Listing Ads is an image-based ads, so all video related fields are not supported.

```xtable
|Field|Data Type|Description|
|---|---|---|
|promotion_type{+Conditional} |string|Promotion type and you can decide where you'd like to promote your products using this field. You need to specify the field when advertising objective (`objective_type`) for your campaign is NOT set as `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`.<br>For enum values, see [Enumeration - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
|product_source{Required}|string|Enum values: `UNSET` ,`CATALOG`(Catalog) . Use `CATALOG` for Shopping Ads.  |
|shopping_ads_type{Required}|string|Shopping ads type. Enum: `UNSET`, `VIDEO`(Video Shopping Ads),`LIVE`(Live Shopping Ads), `CATALOG_LISTING_ADS`(Catalog Listing Ads) . Use `CATALOG_LISTING_ADS` here.|
|pixel_id |string|Pixel ID. Only applicable for landing pages.  It needs to be passed in with the `optimization_event` field. Use [/pixel/list/](https://ads.tiktok.com/marketing_api/docs?id=1740858697598978) endpoint to get all Pixel IDs. <br> If `optimization_goal` is `CONVERT`,  `pixel_id` cannot be empty.|
|optimization_event|string|Conversion event for the ad group. It is required when the promoted object is an App with tracking urls, or when `pixel_id` is specified. For the supported app and pixel events, see [Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889). You can use the [/app/optimization_event/](https://ads.tiktok.com/marketing_api/docs?id=1740859338750977) endpoint to find out the supported events for your app. <br><span style="color: darkred"><b>Note:</b> Not supported when `pixel_id` is used in Reach, Video View, and Traffic objectives. </span>|
|placement_type |string|The placement strategy that decides where your ads will be shown. Use `PLACEMENT_TYPE_NORMAL` for Catalog Listing Ads<br>Default value: `PLACEMENT_TYPE_NORMAL`.|
|placements {Required}|string[]|The apps where you want to deliver your ads. Required when `placement_type` is `PLACEMENT_TYPE_NORMAL`, and optional when `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`. Use `PLACEMENT_TIKTOK` for Catalog Listing Ads. |
|auto_targeting_enabled|boolean|Whether to enable automated targeting. Choose `True`.|
|schedule_type {Required} |string|The schedule type can be either `SCHEDULE_START_END` or `SCHEDULE_FROM_NOW`. If you choose `SCHEDULE_START_END`, you need to specify a start time and an end time. If you choose `SCHEDULE_FROM_NOW`, you only need to specify a start time. If `budget_mode` is `BUDGET_MODE_TOTAL`, this field must be set to `SCHEDULE_START_END`.|
|catalog_id{Required}|string| Catalog ID. |
|catalog_authorized_bc_id {Required}|string| Business Center ID that has permission for this catalog. For catalogs in Business Center, you must specify the ID of the Business Center that a catalog belongs to.|
|optimization_goal{Required}|string|The measurable results you'd like to drive with your ads. Use `CONVERT` or `CLICK` for Catalog Listing Ads. |
|billing_event {Required}|string| Events that you want to pay for. Support `OCPC`  and `CPC` for Catalog Listing Ads. If `optimization_goal`= `CLICK`, you have to choose `CPC` for billing event.|
|bid_type {Required} |string|Bidding strategy that determines how the system manages your cost per result, spends your budget, and how it delivers ads. Only support `BID_TYPE_CUSTOM` for Catalog Listing Ads. |
|conversion_bid_price{+Conditional} |float| Where you can set a target cost per conversion for oCPM(Optimized Cost per Mille); Required when `billing_event` = `OCPM` and `bid_type` = `BID_TYPE_CUSTOM`.|
|pacing |string| You can choose between `PACING_MODE_SMOOTH` and `PACING_MODE_FAST`. For `PACING_MODE_SMOOTH`, the budget is allocated evenly within the scheduled time. `PACING_MODE_FAST` would consume budget and produce results as soon as possible. When Campaign Budget Optimization (`budget_optimize_on`) is on, your setting will be ignored and it will be set as `PACING_MODE_SMOOTH`. Otherwise, this field is required. |
```
# 4. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). The following table includes related parameters.

>  <span style="color:DodgerBlue">**Note**</span><br>
> Catalog Listing Ads is an imaged-based ads, so all video related fields are not supported.

```xtable
|Field|Data Type|Description|
|---|---|---|
|identity_id {Required}|string|Identity ID.|
|identity_type {Required}|string| Identity type. Enum: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. Use `CUSTOMIZED_USER` for Shopping Ads.  <br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). <br> Note: When `identity_type` = `CUSTOMIZED_USER`, you shouldn't pass any `identity_authorized_bc_id`. |
|ad_name {Required}|string| Ad name. |
|catalog_id{Required}|string| Catalog ID. |
|product_specific_type{Required}|string|Different dimensions to choose products. Enum values: `ALL`,` PRODUCT_SET`, `CUSTOMIZED_PRODUCTS`. <br>If you choose `ALL`, you don't need to input `sku_ids` or  `item_group_ids`  or `product_set_id`.  `item_group_ids`  or `product_set_id` are required if `product_specific_type`=`PRODUCT_SET`. `sku_ids` are required if `product_specific_type`=`CUSTOMIZED_PRODUCTS`|
|item_group_ids {+Conditional}|string[]|Product SPU ID. Max size=20.|
|product_set_id {+Conditional}|string|The ID of the product set.|
|sku_ids {+Conditional}|string|IDs of the SKUs. Max size=20.|
```
 # 5. Preview an ad
After the ad is created,  preview your ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). <br>
  We offer preview types of `AD` and `ADS_CREATION` to enable you to preview existing ads and the ads that you haven't created. 





