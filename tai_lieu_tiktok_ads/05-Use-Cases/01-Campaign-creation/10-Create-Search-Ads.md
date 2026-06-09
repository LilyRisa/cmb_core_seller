---
title: "Create Search Ads"
doc_id: 1780164603696130
path: "Use Cases / Campaign creation / Create Search Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Search Ads

This article walks you through the steps to create Search Ads.

## Introduction
Search Ads can extend your in-feed advertising to the TikTok search results page by reaching users who are searching for specific terms related to your product or service.  These ads appear as 'Sponsored' content within the TikTok search results page.

**You can use Campaign Management API to create Search Ads, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create Search Ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  


## Steps

1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective| One of the following objectives: <ul><li>App promotion</li><li>Website conversions</li><li>Traffic </li><li>Lead generation </li></ul>| `objective_type` | Any value below: <ul><li>`APP_PROMOTION` </li><li>`WEB_CONVERSIONS` </li><li> `TRAFFIC`</li><li>`LEAD_GENERATION`</li></ul>|
```

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.


```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Placement | <ul><li><strong>Automatic Placement</strong> or</li><li><strong>Select Placement</strong> with TikTok placement included  </li></ul> | <ul><li>`placement_type`</li><li>`placements`</li></ul> | <ul><li>Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`, or</li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and include `PLACEMENT_TIKTOK` in the value of `placements` </li></ul> |
|Include search results| Enabled| `search_result_enabled`|  `true` or not passed|
| Promotion type (Optimization location) <br> when advertising objective is Lead generation | Instant Form or Website | `promotion_type` | When `objective_type` is `LEAD_GENERATION`, set this field to `LEAD_GENERATION`. <br> The values `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`, `LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`, and `LEAD_GEN_CLICK_TO_CALL` are not supported. |
```

When the objective and placement requirements are met, the `search_result_enabled` field will default to `true`. In this case, you can enable or disable Automatic Search Placement:
- To enable Automatic Search Placement for your ad group, either omit `search_result_enabled` or manually set it to `true`.
- To disable Automatic Search Placement for your ad group, manually set `search_result_enabled` to `false`.

3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). 

## Next steps

### Use Search Ads capabilities in Reporting API

Once you have created Search Ads, you can use [Reporting API](https://business-api.tiktok.com/portal/docs?id=1740302665828417) to perform the following actions:

- Retrieve data for both Search Ads and non-Search Ads by using the `ad_type` dimension in [basic reports](https://business-api.tiktok.com/portal/docs?id=1751443956638721). If the ad is a Search Ad, the value of `ad_type` will be `Search`, and any value other than `Search` indicates the ad is not a Search Ad.
- Retrieve data segmented by Search Term using the `search_terms` dimension in [basic reports](https://business-api.tiktok.com/portal/docs?id=1751443956638721).

### Manage negative keywords for Search Ads

Negative keywords are a feature that helps you optimize Search Ads delivery by filtering out irrelevant queries or queries that may affect your brand safety. You can create, update, delete, retrieve and download negative keywords by using the [Negative Keywords API](https://business-api.tiktok.com/portal/docs?id=1775103049049090).

## Related docs
- [Reporting API](https://business-api.tiktok.com/portal/docs?id=1740302665828417) 
- [Negative Keywords API](https://business-api.tiktok.com/portal/docs?id=1775103049049090)
