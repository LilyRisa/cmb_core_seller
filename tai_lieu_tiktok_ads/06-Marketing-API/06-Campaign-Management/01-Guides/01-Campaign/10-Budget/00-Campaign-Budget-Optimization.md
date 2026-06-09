---
title: "Campaign Budget Optimization"
doc_id: 1739381757818881
path: "Marketing API / Campaign Management / Guides / Campaign / Budget / Campaign Budget Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Campaign Budget Optimization


# Overview

Campaign Budget Optimization (CBO) is a feature that enables advertisers to maximize campaign results by dynamically controlling the campaign budget. With CBO, you only need to set the budget at the campaign level and the system will dynamically distribute the budget to the best performing ad groups to guarantee maximum conversions at the campaign level. For more details about CBO, see [this article](https://ads.tiktok.com/help/article/campaign-budget-optimization?redirected=1&lang=en) at the TikTok Business Help Center.

# Supported advertising objectives

CBO supports the following advertising objectives (`objective_type`):

- Reach (`REACH`)
- Traffic (`TRAFFIC`)
- Website conversions (`WEB_CONVERSIONS`)
- App promotion (`APP_PROMOTION`)
- Lead generation (`LEAD_GENERATION`)
- Video views (`VIDEO_VIEWS`)
- Community interaction (`ENGAGEMENT`)
- Product sales (`PRODUCT_SALES`) 
> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>CBO is supported for the Product Sales objective only when the Shopping Ads type is Video Shopping Ads and the product source is catalog.</li><li>CBO for Video Shopping Ads with product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>


# Supported optimization goals
When CBO is enabled, you need to set an `optimization_goal` at the **ad group level**. Only certain optimization goals are supported for an advertising objective (`objective_type`). Refer to the table below for more details.

 ```xtable
|<code>objective_type</code> at the campaign level {40%}|<code>optimization_goal</code> at the ad group level {60%}|
|---|---|
|`REACH`|`REACH`|
|`VIDEO_VIEWS`|<ul><li>`ENGAGED_VIEW`</li><li>`ENGAGED_VIEW_FIFTEEN` (allowlisted) </li></ul>|
|`TRAFFIC`|<ul><li>`CLICK`</li><li>`TRAFFIC_LANDING_PAGE_VIEW` </li></ul>|
|`WEB_CONVERSIONS`|<ul><li>`CONVERT`</li><li>`VALUE` (allowlisted)</li><li>`AUTOMATIC_VALUE_OPTIMIZATION` (allowlisted)</li></ul><br>To learn about how to use the optimization goal Automatic Value Optimization for the Web Conversions objective, see [Enable VBO for Web](https://business-api.tiktok.com/portal/docs?id=1770019181843458#item-link-Enable%20VBO%20for%20Web).<br><br><p><span style="color:darkred"><b>Note</b></span>: Automatic Value Optimization and enabling Automatic Value Optimization and Campaign Budget Optimization at the same time are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.|
|`APP_PROMOTION`|<ul><li> `INSTALL`</li><li>`IN_APP_EVENT`</li><li>`VALUE`</li></ul>|
|`LEAD_GENERATION`|`LEAD_GENERATION`|
|`ENGAGEMENT`| <ul><li>`FOLLOWERS`</li><li>`PAGE_VISIT`</li></ul>|
| `PRODUCT_SALES` |<ul><li>`INSTALL` (when `promotion_type` = `APP_ANDRIOD` or `APP_IOS`)</li><li>`IN_APP_EVENT` (when `promotion_type` = `APP_ANDRIOD` or `APP_IOS`)</li><li>`CONVERT`(when `promotion_type` = `WEBSITE`)</li></ul>  |
 ```



# Enable Campaign Budget Optimizaton

You cannot enable CBO on an existing standard campaign. You need to create a new campaign.

1. Use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create a campaign and configure the following settings. 
  - Set `objective_type` to one of the supported advertising objectives.
  - Set `budget_optimize_on` to `TRUE` to enable CBO for this campaign.
  - Set the `budget_mode` and specify a `budget` at the campaign level. To learn about the minimum budget and how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114).
  Note that for a CBO campaign, the value of `budget_optimize_on` and `budget_mode` cannot be modified once specified. 
	
2. Use [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) to create ad groups in this campaign. 
- You can configure the following settings at the ad group level:
  - Use `optimization_goal` to set the optimization goal. This field is required when CBO is on. For enum values, see the above "Supported optimization goals" section.
  - Use `bid_type` to set the bidding strategy. This field is required when CBO is on. Enum values:  `BID_TYPE_NO_BID`, `BID_TYPE_CUSTOM`. For details, see [Enumeration - Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). 
  - Use `deep_bid_type` to set the bidding strategy for in-app events. This field is required when CBO is enabled and `optimization_goal` is `VALUE`. Enum values: `VO_MIN_ROAS` (allowlisted), `VO_HIGHEST_VALUE` (allowlisted). For details, see [Enumerations - Deep Event Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). 
  - Use `bid_price` to set the bid price. To specify `bid_price`, you need to set `bid_type` as `BID_TYPE_CUSTOM`. When CBO is on, we suggest that you set the same bid price for all ad groups in the campaign.
  - Use `roas_bid` to set ROAS goal for Value-based Optimization. This field is required when `deep_bid_type` is `VO_MIN_ROAS`. Value range: 0.01-10.
- You DO NOT need to configure the following settings:
  - You don't need to specify `budget` or `budget_mode` at the ad group level as settings at the campaign level will be used. 
  - You don't need to specify pacing as `PACING_MODE_SMOOTH` will be used by default.
- If you want to create new ad groups under one CBO campaign, then the value of the following fields should be the same as those specified in the first ad group, and these fields **CANNOT** be updated once specified:
  - `optimization_goal`
  - `bid_type`
  - `optimization_event`
3. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create ads. There are no special settings at the ad level.
