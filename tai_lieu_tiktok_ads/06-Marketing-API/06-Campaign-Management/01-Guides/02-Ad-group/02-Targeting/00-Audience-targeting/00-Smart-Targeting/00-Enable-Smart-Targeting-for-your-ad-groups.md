---
title: "Enable Smart Targeting for your ad groups"
doc_id: 1783164826830849
path: "Marketing API / Campaign Management / Guides / Ad group / Targeting / Audience targeting / Smart Targeting / Enable Smart Targeting for your ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable Smart Targeting for your ad groups

This article walks you through the steps to enable Smart Targeting for your ad groups.

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create ad groups, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

``` xtable
| Setting{20%} | Requirement{25%} | Parameter{25%} | How to configure the parameter{30%} |
|---|---|---|
| Advertising objective | Any of the following objectives: <ul><li> Traffic </li><li> App promotion </li><li> Lead generation </li><li> Website conversions </li></ul> | `objective_type` | Any of the following values: <ul><li>`TRAFFIC`</li><li>`APP_PROMOTION` </li><li>`LEAD_GENERATION`</li><li>`WEB_CONVERSIONS`</li></ul>|
| App promotion type for App promotion objective | Any of the following types: <ul><li> App install </li><li> App pre-registration </li></ul> | `app_promotion_type` <br>(when `objective_type` is `APP_PROMOTION`) | Any of the following values: <ul><li>`APP_INSTALL` </li><li>`APP_PREREGISTRATION`</li></ul>  |
|Smart+ Campaign| Disabled<br><br> If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) to check. | / | / |
| Special ad categories | Disabled | `special_industries` | Not passed |
``` 

## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.
``` xtable
| Setting{20%} | Requirement{25%} | Parameter{25%} | How to configure the parameter{30%} |
|---|---|---|
| Placements | <b>Select Placement</b> with only TikTok placement | <ul><li>`placement_type`</li><li> `placements`</li></ul> | Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to `["PLACEMENT_TIKTOK"]` |
| Audience targeting<br>• Saved audience  | Disabled | `saved_audience_id` | Not passed |
| Audience targeting<br>• Automatic targeting | Disabled | `auto_targeting_enabled` | Not passed |
| Audience targeting<br>• Targeting expansion| Disabled | `targeting_expansion` | Not passed |
``` 


### Requirements for Smart audience
If you want to enable Smart audience, the following requirements must be met at the same time.
``` xtable
| Setting{25%} | Requirement{25%} | Parameter{25%} | How to configure the parameter{25%} |
|---|---|---|
| Audience targeting <br>• Audience <br>&nbsp;&nbsp;&nbsp;&nbsp;○ Include | At least one included audience is specified | `audience_ids` | Pass a valid value |
| Audience targeting <br>• Audience<br>&nbsp;&nbsp;&nbsp;&nbsp;○ Smart audience  | Enabled | `smart_audience_enabled`  | `true`  |
```
### Requirements for Smart interests & behaviors
If you want to enable Smart interests & behaviors, the following requirements must be met at the same time.
``` xtable
| Setting{25%} | Requirement{25%} | Parameter{25%} | How to configure the parameter{25%} |
|---|---|---|
| Audience targeting  <br>• Interests & Behaviors<br>&nbsp;&nbsp;&nbsp;&nbsp;○ Interests <br>&nbsp;&nbsp;&nbsp;&nbsp;○ Purchase intention<br>&nbsp;&nbsp;&nbsp;&nbsp;○ Video interactions & Creator interactions & Hashtag interactions   | At least one interest, purchase intention or action category is specified  | Any of the following parameters:<ul><li>`interest_category_ids`</li><li>`interest_keyword_ids`</li><li>`purchase_intention_keyword_ids`</li><li>`actions`</li></ul>  | Pass a valid value |
| Audience targeting <br>• Interests & Behaviors<br>&nbsp;&nbsp;&nbsp;&nbsp;○ Smart interests & behaviors| Enabled  | `smart_interest_behavior_enabled` | `true`  |
```
# Next steps
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). 

# Related docs

- [Audience targeting](https://business-api.tiktok.com/portal/docs?id=1739381236849665)
- [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586)
- [Create an ad group](https://business-api.tiktok.com/portal/docs?id=1739499616346114)
