---
title: "Create Community Interaction ads"
doc_id: 1808164002093058
path: "Use Cases / Campaign creation / Create Community Interaction ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Community Interaction ads

This article walks you through the steps to create Community Interaction ads.

# Introduction
With the Community Interaction objective, you can build and nurture your connections with one of the most valuable communities that exists today - the TikTok community. This objective allows you to drive follower growth or increase traffic to different landing pages on TikTok.

**You can use Campaign Management API to create Community Interaction ads, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create Community Interaction ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  


# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:27%">Parameter</th>
    <th style="width:28%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>Community Interaction</td>
    <td><code>objective_type</code></td>
    <td><code>ENGAGEMENT</code></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362"  target="_blank">/campaign/spc/create/</a> to create a Smart+ Community Interaction campaign.</td>
    <td>/<br></td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
</tbody>
</table>


### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "ENGAGEMENT",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget_mode": "BUDGET_MODE_TOTAL",
        "objective": "LANDING_PAGE",
        "budget": {{budget}},
        "deep_bid_type": null,
        "is_smart_performance_campaign": false,
        "is_advanced_dedicated_campaign": false,
        "is_search_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "modify_time": "{{modify_time}}",
        "roas_bid": 0,
        "rta_id": null,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "objective_type": "ENGAGEMENT",
        "operation_status": "ENABLE",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "rta_product_selection_enabled": false
    }
}
(/code)
```
## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Interaction type | TikTok account <br><br><p><span style="color:darkred"><b>Note</b></span>: Creating Community Interaction ad groups with interaction type as Live is not supported via API. | / | / <br><br><p><span style="color:darkred"><b>Note</b></span>: All Community Interaction ad groups created through the API use "TikTok account" as the default interaction type. |
| Placements | <b>Select Placement</b> with only TikTok placement <br><br>Optionally, you can specify the following TikTik subplacements: <ul><li>In-feed. Ads will be placed in the For You feed and might also be placed in Profile Page and Following feeds.</li><li>Search feed (supported with allowlist)</li><li>TikTok Lite (supported with allowlist), a streamlined version of TikTok that features a smaller app size and faster video loading speed. The TikTok Lite subplacement is currently available for Japan or South Korea as targeting locations.</li></ul>| `placement_type` <br>`placements`<br>`tiktok_subplacements` | Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to `["PLACEMENT_TIKTOK"]` <br><br>Optionally, you can specify the following TikTik subplacements via `tiktok_subplacements`: <ul><li>`IN_FEED`</li><li>`SEARCH_FEED`</li><li>`TIKTOK_LITE`<span style="color:Red">to-be-deprecated</span></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Starting July 19th, 2025, you will no longer be able to create or copy ad groups using the TikTok Lite subplacement (`TIKTOK_LITE`). To ensure a smooth and uninterrupted ad creation experience, we recommend that you avoid creating new ad groups with this subplacement or copying campaigns that contain such ad groups.</li><li>Using the Search feed subplacement (`SEARCH_FEED`) in your ad group is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Using the TikTok Lite subplacement (`TIKTOK_LITE`) in your ad group is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>If you pass `tiktok_subplacements`, you need to at least include `IN_FEED` in the `tiktok_subplacements` value. </li><li>To include `TIKTOK_LITE` in the `tiktok_subplacements` value, you need to include locations in Japan or South Korea in your targeted locations (`location_ids`). Otherwise, an error will occur.</li><li>If you include `SEARCH_FEED` or `TIKTOK_LITE` in the `tiktok_subplacements` value, you cannot set `brand_safety_type` to `THRID_PARTY`. </li></ul>|
| Optimization goal | Any of the following optimization goals: <ul><li>Follow</li><li>TikTok page visits</li></ul>| `optimization_goal` | Any of the following values:<ul><li>`FOLLOWERS`</li><li>`PAGE_VISIT`</li></ul> |
| Bid Strategy | Any of the following types: <ul><li>Maximum Delivery</li><li>Cost Cap</li></ul>| `bid_type`<br> `bid_price`<br> `conversion_bid_price`  | Any of the following values for `bid_type`: <ul><li>`BID_TYPE_NO_BID`</li><li>`BID_TYPE_CUSTOM`<ul><li>If `bid_type` is `BID_TYPE_CUSTOM` and `optimization_goal` is `FOLLOWERS`, pass `conversion_bid_price` at the same time.</li><li>If `bid_type` is `BID_TYPE_CUSTOM` and `optimization_goal` is `PAGE_VISIT`, pass `bid_price` at the same time. </li></ul></li></ul>|
| Billing event | <ul><li>If optimization goal is TikTok page visits, the billing event should be CPC.</li><li>If optimization goal is Follow, the billing event should be oCPM. </li></ul> | `billing_event` |<ul><li> If `optimization_goal` is `PAGE_VISIT`, set this field to `CPC`. </li><li>If `optimization_goal` is `FOLLOWERS`, set this field to `OCPM`.</li></ul>|
| Delivery Type |<ul><li>If Bid Strategy is Maximum Delivery, you can only set Delivery Type to Standard. </li><li>If Bid Strategy is Cost Cap, you can set Delivery Type to Standard or Accelerated.</li></ul> | `pacing` | <ul><li>If `bid_type` is `BID_TYPE_NO_BID`, you can only set `pacing` to `PACING_MODE_SMOOTH`. </li><li> If `bid_type` is `BID_TYPE_CUSTOM`, you can set `pacing` to `PACING_MODE_SMOOTH` or `PACING_MODE_FAST`.</li></ul>|
| Split test | Supported <br><br>Community Interaction ad groups can be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests.| / | / |
```

  
### Example
#### Optimization goal as Follow
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6251999"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "FOLLOWERS",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "deep_cpa_bid": 0,
        "statistic_type": null,
        "frequency": null,
        "device_price_ranges": null,
        "inventory_filter_enabled": false,
        "schedule_infos": null,
        "create_time": "{{create_time}}",
        "location_ids": [
            "6251999"
        ],
        "billing_event": "OCPM",
        "promotion_type": "WEBSITE_OR_DISPLAY",
        "smart_interest_behavior_enabled": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "brand_safety_partner": null,
        "campaign_id": "{{campaign_id}}",
        "auto_targeting_enabled": false,
        "isp_ids": [],
        "scheduled_budget": 0,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "modify_time": "{{modify_time}}",
        "pacing": "PACING_MODE_SMOOTH",
        "gender": "GENDER_UNLIMITED",
        "optimization_event": null,
        "bid_price": 0,
        "feed_type": null,
        "adgroup_app_profile_page_state": null,
        "attribution_event_count": "ONCE",
        "creative_material_mode": "CUSTOM",
        "campaign_name": "{{campaign_name}}",
        "age_groups": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "rf_purchased_type": null,
        "languages": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "category_exclusion_ids": [],
        "view_attribution_window": "ONE_DAY",
        "actions": [],
        "is_smart_performance_campaign": false,
        "adgroup_name": "{{adgroup_name}}",
        "adgroup_id": "{{adgroup_id}}",
        "video_download_disabled": false,
        "purchased_impression": null,
        "ios14_quota_type": "UNOCCUPIED",
        "schedule_end_time": "{{schedule_end_time}}",
        "comment_disabled": false,
        "next_day_retention": null,
        "app_download_url": null,
        "interest_keyword_ids": [],
        "operating_systems": [],
        "conversion_window": null,
        "is_new_structure": true,
        "share_disabled": false,
        "purchased_reach": null,
        "frequency_schedule": null,
        "click_attribution_window": "SEVEN_DAYS",
        "smart_audience_enabled": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "advertiser_id": "{{advertiser_id}}",
        "category_id": "0",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "conversion_bid_price": {{conversion_bid_price}},
        "is_hfss": false,
        "secondary_optimization_event": null,
        "search_result_enabled": false,
        "rf_estimated_cpr": null,
        "pixel_id": null,
        "schedule_type": "SCHEDULE_START_END",
        "rf_estimated_frequency": null,
        "vbo_window": null,
        "optimization_goal": "FOLLOWERS",
        "audience_ids": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "interest_category_ids": [],
        "operation_status": "ENABLE",
        "app_type": null,
        "deep_bid_type": null,
        "network_types": [],
        "skip_learning_phase": true,
        "keywords": null,
        "bid_display_mode": "CPMV",
        "budget": {{budget}},
        "app_id": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "excluded_audience_ids": [],
        "delivery_mode": null
    }
}
(/code)
```

#### Optimization goal as TikTok page visits
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6251999"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "PAGE_VISIT",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "actions": [],
        "category_exclusion_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "deep_cpa_bid": 0,
        "smart_audience_enabled": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "skip_learning_phase": false,
        "bid_price": {{bid_price}},
        "excluded_audience_ids": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "conversion_window": null,
        "next_day_retention": null,
        "gender": "GENDER_UNLIMITED",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "frequency": null,
        "interest_category_ids": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "promotion_type": "WEBSITE_OR_DISPLAY",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "vbo_window": null,
        "frequency_schedule": null,
        "billing_event": "CPC",
        "ios14_quota_type": "UNOCCUPIED",
        "rf_purchased_type": null,
        "interest_keyword_ids": [],
        "operation_status": "ENABLE",
        "inventory_filter_enabled": false,
        "campaign_id": "{{campaign_id}}",
        "app_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "budget": {{budget}},
        "secondary_optimization_event": null,
        "share_disabled": false,
        "audience_ids": [],
        "rf_estimated_cpr": null,
        "device_price_ranges": null,
        "adgroup_app_profile_page_state": null,
        "purchased_impression": null,
        "brand_safety_partner": null,
        "create_time": "{{create_time}}",
        "auto_targeting_enabled": false,
        "network_types": [],
        "video_download_disabled": false,
        "age_groups": null,
        "statistic_type": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "languages": [],
        "smart_interest_behavior_enabled": null,
        "bid_display_mode": "CPMV",
        "campaign_name": "{{campaign_name}}",
        "keywords": null,
        "creative_material_mode": "CUSTOM",
        "adgroup_name": "{{adgroup_name}}",
        "delivery_mode": null,
        "purchased_reach": null,
        "app_id": null,
        "optimization_goal": "PAGE_VISIT",
        "scheduled_budget": 0,
        "operating_systems": [],
        "bid_type": "BID_TYPE_CUSTOM",
        "pacing": "PACING_MODE_SMOOTH",
        "schedule_infos": null,
        "pixel_id": null,
        "modify_time": "{{modify_time}}",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "rf_estimated_frequency": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "app_download_url": null,
        "deep_bid_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_new_structure": true,
        "feed_type": null,
        "is_hfss": false,
        "search_result_enabled": false,
        "optimization_event": "PAGE_VISIT",
        "is_smart_performance_campaign": false,
        "isp_ids": [],
        "category_id": "0",
        "location_ids": [
            "6251999"
        ]
    }
}
(/code)
```

## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

<table>
  <thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:25%">Parameter</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626"  target="_blank">/ad/aco/create/</a> to create Smart Creative Community Interaction ads.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td>Ad format</td>
    <td>Single Video</td>
    <td><code>ad_format</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Any of the following options:<ul><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
    <td><code>identity_type</code></td>
    <td>Any of the following values:<ul><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br>To learn about how to create Spark Ads, refer to <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298"  target="_blank">Create Spark Ads</a>.</td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value.<br> <br> To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057"  target="_blank">/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
  </tr>
  <tr>
    <td>Destination</td>
    <td><ul><li>If optimization goal is specified as Follow at the ad group level, the destination is the TikTok account profile page.</li><li>If optimization goal is specified as TikTok page visits at the ad group level, you can choose any of the following types:<ul><li>Account profile<ul><li>You don't need to provide a TikTok page URL or a TikTok Instant Page.</li></ul></li><li>Other TikTok page<ul><li>You need to provide a TikTok page URL.</li></ul></li><li>TikTok Instant Page<ul><li>You need to provide a Custom TikTok Instant Page. </li></ul></li></ul></li></ul></td>
    <td><code>tiktok_page_category</code><br><code>landing_page_url</code><br><code>page_id</code></td>
    <td><ul><li>If <code>optimization_goal</code> is set to <code>FOLLOWERS</code> at the ad group level, you don't need to pass <code>tiktok_page_category</code>, <code>landing_page_url</code> and <code>page_id</code>.</li><li>If <code>optimization_goal</code> is set to <code>PAGE_VISIT</code> at the ad group level, you need to pass <code>tiktok_page_category</code> to specify the exact destination:<ul><li>If you want to set the Destination to Account profile, set <code>tiktok_page_category</code> to <code>PROFILE_PAGE</code> and don't pass <code>landing_page_url</code> and <code>page_id</code>.</li><li>If you want to set the Destination to Other TikTok page, set <code>tiktok_page_category</code> to <code>OTHER_TIKTOK_PAGE</code> and pass the TikTok page URL of a Playlist Page, Hashtag Page, Music Page, or Branded Effect Page via <code>landing_page_url</code>. Do not pass <code>page_id</code>. <ul><li>To obtain a TikTok page URL, tap on the top right corner of the TikTok page you want to promote within your TikTok app, and click to copy the URL.</li></ul></li><li>If you want to set the Destination to TikTok Instant Page, set <code>tiktok_page_category</code> to <code>TIKTOK_INSTANT_PAGE</code> and pass the ID of a TikTok Instant Page via <code>page_id</code>. Do not pass <code>landing_page_url</code>.<ul><li>To obtain Custom TikTok Instant Pages within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586"  target="_blank">/page/get/</a> and set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li></ul></li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Call-to-action</td>
    <td><ul><li>If optimization goal is specified as Follow at the ad group level: Disabled.</li><li>If optimization goal is specified as TikTok page visits at the ad group level: Specify a standard call-to-action or a dynamic call-to-action.</li></ul></td>
    <td><code>call_to_action</code><br><code>call_to_action_id</code></td>
    <td><ul><li>If <code>optimization_goal</code> is set to <code>FOLLOWERS</code> at the ad group level, do not pass <code>call_to_action</code> and <code>call_to_action_id</code>.</li><li>If <code>optimization_goal</code> is set to <code>PAGE_VISIT</code> at the ad group level, specify a standard call-to-action via <code>call_to_action</code> or a dynamic call-to-action via <code>call_to_action_id</code>. <ul><li>To learn about the supported values for <code>call_to_action</code>, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action"  target="_blank">Enumeration - Call-to-action</a>.</li><li>To learn about how to obtain a <code>call_to_action_id</code>, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs"  target="_blank">Dynamic CTAs</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Interactive add-on (Optional)</td>
    <td>Disabled or enabled with the following type:<ul><li>Reminder Countdown Sticker</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you specify the TikTok subplacement Search feed at the ad group level, you cannot use Reminder Countdown Sticker.</td>
    <td><code>card_id</code></td>
    <td>Not passed or specify the ID of the following creative portfolio type:<ul><li>Reminder Countdown Sticker (<code>sticker_type</code> as <code>REMINDER_COUNTDOWN</code> or <code>LIVE_REMINDER_COUNTDOWN</code>)</li></ul><br> To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426"  target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Countdown Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177"  target="_blank">Stickers</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you include <code>SEARCH_FEED</code> in the <code>tiktok_subplacements</code> value at the ad group level, you cannot use Reminder Countdown Sticker via <code>card_id</code> at the ad level. </td>
  </tr>
</tbody>
</table>

### Example 
#### Destination as Account profile with Optimization goal as Follow at the ad group level

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"AUTH_CODE",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "tiktok_item_id":"{{tiktok_item_id}}"
    }]
}'
(/code)
```
Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "creative_type": null,
                "modify_time": "{{modify_time}}",
                "call_to_action_id": null,
                "deeplink": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}",
                    "{{tracking_offline_event_set_ids}}"
                ],
                "promotional_music_disabled": true,
                "landing_page_urls": null,
                "adgroup_name": "{{adgroup_name}}",
                "ad_id": "{{ad_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_id": "{{identity_id}}",
                "tracking_pixel_id": 0,
                "video_id": null,
                "display_name": "{{display_name}}",
                "optimization_event": null,
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "create_time": "{{create_time}}",
                "card_id": null,
                "ad_texts": null,
                "campaign_id": "{{campaign_id}}",
                "vast_moat_enabled": false,
                "viewability_vast_url": null,
                "avatar_icon_web_uri": "",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "is_aco": false,
                "landing_page_url": null,
                "image_ids": [],
                "profile_image_url": "",
                "operation_status": "ENABLE",
                "ad_text": "{{ad_text}}",
                "ad_name": "{{ad_name}}",
                "impression_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "identity_type": "AUTH_CODE",
                "playable_url": "",
                "music_id": null,
                "page_id": null,
                "creative_authorized": false,
                "is_new_structure": true,
                "campaign_name": "{{campaign_name}}",
                "click_tracking_url": null,
                "app_name": "{{app_name}}",
                "carousel_image_labels": null
            }
        ]
    }
}
(/code)
```

#### Destination as Account profile with Optimization goal as TikTok page visits at the ad group level

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"TT_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "tiktok_item_id":"{{tiktok_item_id}}",
        "tiktok_page_category":"PROFILE_PAGE",
        "call_to_action":"VIEW_PROFILE"
    }]
}'
(/code)
```
Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "call_to_action_id": null,
                "app_name": "{{app_name}}",
                "tracking_pixel_id": 0,
                "ad_format": "SINGLE_VIDEO",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}",
                    "{{tracking_offline_event_set_ids}}"
                ],
                "call_to_action": "VIEW_PROFILE",
                "music_id": null,
                "avatar_icon_web_uri": "",
                "ad_name": "{{ad_name}}",
                "creative_type": null,
                "campaign_name": "{{campaign_name}}",
                "page_id": null,
                "is_aco": false,
                "vast_moat_enabled": false,
                "advertiser_id": "{{advertiser_id}}",
                "optimization_event": "PAGE_VISIT",
                "tiktok_page_category": "PROFILE_PAGE",
                "adgroup_name": "{{adgroup_name}}",
                "campaign_id": "{{campaign_id}}",
                "create_time": "{{create_time}}",
                "deeplink": "{{deeplink}}",
                "card_id": null,
                "brand_safety_vast_url": null,
                "operation_status": "ENABLE",
                "video_id": null,
                "deeplink_type": "NORMAL",
                "ad_text": "{{ad_text}}",
                "landing_page_url": "{{landing_page_url}}",
                "viewability_vast_url": null,
                "carousel_image_labels": null,
                "is_new_structure": true,
                "adgroup_id": "{{adgroup_id}}",
                "playable_url": "",
                "promotional_music_disabled": true,
                "landing_page_urls": null,
                "display_name": "{{display_name}}",
                "creative_authorized": false,
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_type": "TT_USER",
                "profile_image_url": "",
                "viewability_postbid_partner": "UNSET",
                "click_tracking_url": null,
                "impression_tracking_url": null,
                "image_ids": [],
                "ad_texts": null
            }
        ]
    }
}
(/code)
```
#### Destination as Other TikTok page with Optimization goal as TikTok page visits at the ad group level

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"TT_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "tiktok_item_id":"{{tiktok_item_id}}",
        "tiktok_page_category":"OTHER_TIKTOK_PAGE",
        "call_to_action":"VIEW_PROFILE",
        "landing_page_url":"{{landing_page_url}}"
    }]
}'
(/code)
```
Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "ad_name": "{{ad_name}}",
                "is_new_structure": true,
                "creative_authorized": false,
                "music_id": null,
                "playable_url": "",
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "avatar_icon_web_uri": "",
                "campaign_name": "{{campaign_name}}",
                "profile_image_url": "",
                "click_tracking_url": null,
                "viewability_vast_url": null,
                "identity_type": "TT_USER",
                "create_time": "{{create_time}}",
                "landing_page_urls": null,
                "ad_texts": null,
                "landing_page_url": "{{landing_page_url}}",
                "deeplink": "{{deeplink}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id_1}}",
                    "{{tracking_offline_event_set_id_2}}"
                ],
                "image_ids": [],
                "promotional_music_disabled": true,
                "tiktok_page_category": "OTHER_TIKTOK_PAGE",
                "is_aco": false,
                "adgroup_name": "{{adgroup_name}}",
                "tracking_pixel_id": 0,
                "operation_status": "ENABLE",
                "ad_text": "{{ad_text}}",
                "impression_tracking_url": null,
                "vast_moat_enabled": false,
                "deeplink_type": "NORMAL",
                "modify_time": "{{modify_time}}",
                "viewability_postbid_partner": "UNSET",
                "adgroup_id": "{{adgroup_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "call_to_action": "VIEW_PROFILE",
                "display_name": "{{display_name}}",
                "call_to_action_id": null,
                "app_name": "{{app_name}}",
                "page_id": null,
                "video_id": null,
                "ad_format": "SINGLE_VIDEO",
                "carousel_image_labels": null,
                "optimization_event": "PAGE_VISIT",
                "brand_safety_vast_url": null,
                "card_id": null,
                "campaign_id": "{{campaign_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "creative_type": null
            }
        ]
    }
}
(/code)
```
#### Destination as TikTok Instant Page with Optimization goal as TikTok page visits at the ad group level

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"TT_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "tiktok_item_id":"{{tiktok_item_id}}",
        "tiktok_page_category":"TIKTOK_INSTANT_PAGE",
        "call_to_action":"VIEW_PROFILE",
        "page_id":"{{page_id}}"
    }]
}'
(/code)
```
Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "display_name": "{{display_name}}",
                "music_id": null,
                "tiktok_item_id": "{{tiktok_item_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "campaign_name": "{{campaign_name}}",
                "avatar_icon_web_uri": "",
                "profile_image_url": "",
                "modify_time": "{{modify_time}}",
                "vast_moat_enabled": false,
                "carousel_image_labels": null,
                "ad_format": "SINGLE_VIDEO",
                "optimization_event": "PAGE_VISIT",
                "advertiser_id": "{{advertiser_id}}",
                "app_name": "{{app_name}}",
                "landing_page_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id_1}}",
                    "{{tracking_offline_event_set_id_2}}"
                ],
                "create_time": "{{create_time}}",
                "video_id": null,
                "ad_texts": null,
                "impression_tracking_url": null,
                "campaign_id": "{{campaign_id}}",
                "call_to_action": "VIEW_PROFILE",
                "ad_name": "{{ad_name}}",
                "promotional_music_disabled": true,
                "creative_authorized": false,
                "image_ids": [],
                "identity_type": "TT_USER",
                "call_to_action_id": null,
                "card_id": null,
                "viewability_postbid_partner": "UNSET",
                "tiktok_page_category": "TIKTOK_INSTANT_PAGE",
                "is_aco": false,
                "playable_url": "",
                "viewability_vast_url": null,
                "ad_text": "{{ad_text}}",
                "landing_page_urls": null,
                "operation_status": "ENABLE",
                "click_tracking_url": null,
                "ad_id": "{{ad_id}}",
                "identity_id": "{{identity_id}}",
                "deeplink": null,
                "is_new_structure": true,
                "brand_safety_vast_url": null,
                "creative_type": "CUSTOM_INSTANT_PAGE",
                "page_id": {{page_id}},
                "tracking_pixel_id": 0
            }
        ]
    }
}
(/code)
```

# Related docs
- [Campaign Management API](https://ads.tiktok.com/marketing_api/docs?id=1739381823123458&rid=24jiu7kp2km)
- [Campaign Management Guide](https://ads.tiktok.com/marketing_api/docs?id=1735713781404673&rid=24jiu7kp2km)
- [About the Community Interaction objective](https://ads.tiktok.com/help/article/community-interaction-objective)
