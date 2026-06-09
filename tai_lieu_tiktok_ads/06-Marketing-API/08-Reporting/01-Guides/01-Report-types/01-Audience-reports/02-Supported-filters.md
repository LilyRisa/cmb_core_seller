---
title: "Supported filters"
doc_id: 1751454172429314
path: "Marketing API / Reporting / Guides / Report types / Audience reports / Supported filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported filters

You can use the following filters to narrow down the scope of reporting results. 

Other than [the same filters that are supported in basic reports](https://ads.tiktok.com/marketing_api/docs?id=1751443975608321), audience reports also support the following filters.

> <span style="color:DodgerBlue">**Note**</span><br>
> - In asynchronous audience reports, the only supported filter fields are `campaign_ids`, `adgroup_ids`, and `ad_ids`.
> - Audience reports now include data for Auction ads, Reach & Frequency ads, and TopView ads. However, you can not filter data for a specific buying type in audience reports. <br>To filter data for a specific buying type, use [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) to run a basic report. Within the filtering object, specify the `buying_type` field and enter the values of either `AUCTION`, `RESERVATION_TOP_VIEW`, or `RESERVATION_RF`.
> -  For TopView ads, cost-related metrics at ad level and cost-related metrics for audience breakdown will only include data for ads delivered after January 18th, 2024.
> -  The reporting data for [Product GMV max ads](https://ads.tiktok.com/help/article/how-to-create-product-gmv-max-ads-in-seller-center) and LIVE GMV max ads created from TikTok Shop is excluded from synchronous Auction basic reports, audience reports, playable ad reports, and DSA reports. Shopping Ads created via API or TikTok Ads Manager are not affected.

```xtable
|Filter{17%}|Supported Filter Types{15%}| Supported Data Level{15%}|Notes{53%}|
|---|---|---|---|
| campaign_system_origins | IN | Campaign/Ad group/Ad | The campaign origins (campaign sources). <br> <br> Enum values: <ul><li>`PROMOTE`: The campaign is a Promote campaign created through the TikTok mobile App.</li><li>`TT_ADS_PLATFORM`: The campaign is a non-Promote campaign created through the API or in TikTok Ads Manager. </li></ul> Default value: `"&#91;\"TT_ADS_PLATFORM\"&#93;"`.<br><br> To learn more about the supported dimensions, metrics and filters for Promote ads, see [Promote campaigns](https://business-api.tiktok.com/portal/docs?id=1785880454546433). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> This filter is only supported in synchronous audience reports. </li><li> In asynchronous audience reports, the results include the data for Promote ads by default. </li></ul> |
|dma_ids|IN|Campaign/Ad group/Ad|Filter by DMA IDs. Valid only when the `dma_id` dimension is used. For enum values, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689).|
|region_ids|IN|Campaign/Ad group/Ad|Filter by region IDs on the state or provincial level. Valid only when the `province_id` dimension is used.|
|interest_category|IN|Campaign/Ad group/Ad| Filter by tier-1 interest categories. Valid only when the `interest_category` dimension is used. For available values, see [Get interest categories](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961).|
|interest_category_tier2|IN|Campaign/Ad group/Ad|Filter by tier-2 interest categories. Valid only when the `interest_category_tier2` dimension is used. For available values, see [Get interest categories](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961).|
|interest_category_tier3|IN|Campaign/Ad group/Ad| Filter by tier-3 interest categories. Valid only when the `interest_category_tier3` dimension is used. For available values, see [Get interest categories](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961).|
|interest_category_tier4|IN|Campaign/Ad group/Ad| Filter by tier-4 interest categories. Valid only when the `interest_category_tier4` dimension is used. For available values, see [Get interest categories](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961).|
| destination | IN | Ad | Destination page type. <br>Enum values: <ul><li> `APP`: The download page for the promoted App in Google Play or Apple Store. The `promotion_type` for the corresponding ad group is set as `APP_ANDROID` or `APP_IOS`. </li><li>`TIKTOK_INSTANT_PAGE`: TikTok Instant Pages, including Custom Page, Instant Form, App Profile Page, and Instant Product Page. The page ID (`page_id`) of a TikTok Instant Page is specified for the ad. </li><li>`WEBSITE`: Website page. A landing page URL (`landing_page_url`) is specified for the ad.</li><li>`SOCIAL_MEDIA_APP`: Social media URL. To learn about the ads where the destination can be set as social media URL, see [Create a Lead Generation ad with promotion type as instant messaging apps](https://business-api.tiktok.com/portal/docs?id=1774482999036930).</li><li>`PHONE_CALL`: Phone number. To learn about the ads where the destination can be set as phone number, see [Create a Lead Generation ad with promotion type as phone call](https://business-api.tiktok.com/portal/docs?id=1774482936048641).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: This filter is only supported in synchronous audience reports. |
|creative_material_mode|IN|Ad group/Ad|The strategy that your creatives will be delivered.  Enum values: `CUSTOM`(custom), `DYNAMIC`(automated), `SMART_CREATIVE`(Smart Creative). |
|bid_strategy|IN|Ad group|Bidding strategy.  Enum values: `BID_STRATEGY_COST_CAP`, `BID_STRATEGY_BID_CAP`, `BID_STRATEGY_MAX_CONVERSION` and `BID_STRATEGY_LOWEST_COST`. |
```

