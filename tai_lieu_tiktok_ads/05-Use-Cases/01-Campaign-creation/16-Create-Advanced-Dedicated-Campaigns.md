---
title: "Create Advanced Dedicated Campaigns"
doc_id: 1797011827608577
path: "Use Cases / Campaign creation / Create Advanced Dedicated Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Advanced Dedicated Campaigns

This article walks you through the steps to create Advanced Dedicated Campaigns.

# Introduction
Advanced Dedicated Campaign is a new iOS campaign type that leverages advanced delivery models benefiting from real-time signals to achieve improved campaign performance on iOS.

**You can use Campaign Management API to create Advanced Dedicated Campaigns, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details. 
  - To create Advanced Dedicated Campaigns, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.  


# Steps
Based on your preference for enabling Smart+ Campaign for your Advanced Dedicated Campaigns, you can create a Manual Advanced Dedicated Campaign or a Smart+ Advanced Dedicated Campaign. For detailed instructions on creating each type, refer to their respective sections: "**Steps for creating Manual Advanced Dedicated Campaigns**" and "**Steps for creating  Smart+ Advanced Dedicated Campaigns**".

## Steps for creating Manual Advanced Dedicated Campaigns
### 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
    <th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>App promotion</td>
    <td><code>objective_type</code></td>
    <td><code>APP_PROMOTION</code></td>
  </tr>
  <tr>
    <td>App promotion type</td>
    <td>App install</td>
    <td><code>app_promotion_type</code></td>
    <td><code>APP_INSTALL</code></td>
  </tr>
  <tr>
    <td> Smart+ Campaign</td>
    <td>Disabled.<br>Campaigns created via <code>/campaign/create/</code> are all standard (non-Smart+) campaigns.<br><br>If you want to create  Smart+ Advanced Dedicated Campaigns, see <a href="#item-link-Steps for creating Smart+ Advanced Dedicated Campaigns">Steps for creating Smart+ Advanced Dedicated Campaigns</a>.</td>
    <td>/</td>
    <td>/<br></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Enabled</td>
    <td><code>campaign_type</code></td>
    <td><code>IOS14_CAMPAIGN</code></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Pass the ID of an iOS app that is eligible for Advanced Dedicated Campaigns.<br><br>To find eligible apps, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786"  target="_blank">/app/list/</a>. The eligible apps should meet the following requirements:<ul><li><code>platform</code> is <code>IOS</code></li><li><code>advanced_dedicated_campaign_allowed</code> is <code>true</code></li></ul><p><span style="color:darkred"><b>Note</b></span>: Getting access to Advanced Dedicated Campaign requires the app to meet certain criteria. If your app is ineligible for Advanced Dedicated Campaign, reach out to your TikTok representative. Your TikTok representative will be able to provide troubleshooting support to help your app get access to Advanced Dedicated Campaign. </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td><code>ON</code>, or <code>OFF</code> (or not passed)</td>
  </tr>
  <tr>
    <td>Use advanced dedicated campaign</td>
    <td>Enabled</td>
    <td><code>is_advanced_dedicated_campaign</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>SKAN attribution</td>
    <td>Enabled or disabled</td>
    <td><code>disable_skan_campaign</code></td>
    <td>Any of the following values: <ul><li><code>true</code>: To disable SKAN attribution. The campaign will not be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610"  target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics"  target="_blank">Self Attribution Network (SAN) metrics</a> for the campaign. However, you cannot retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics"  target="_blank">SKAN reporting metrics</a> for the campaign. Learn more about <a href="https://ads.tiktok.com/help/article/about-self-attribution-transition"  target="_blank">SAN integration</a></li><li><code>false</code>: To enable SKAN attribution. The campaign will be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610"  target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics"  target="_blank">SKAN metrics</a> for the campaign.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Disabling SKAN attribution for Dedicated Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </td>
  </tr>
</tbody>
</table>

#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_INSTALL",
    "campaign_type": "IOS14_CAMPAIGN",
    "app_id": "{{app_id}}",
    "is_advanced_dedicated_campaign": true,
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget_mode": "BUDGET_MODE_TOTAL",
        "app_id": "{{app_id}}",
        "create_time": "{{create_time}}",
        "campaign_type": "IOS14_CAMPAIGN",
        "app_promotion_type": "APP_INSTALL",
        "budget": {{budget}},
        "is_search_campaign": false,
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "campaign_app_profile_page_state": "OFF",
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "advertiser_id": "{{advertiser_id}}",
        "objective_type": "APP_PROMOTION",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "deep_bid_type": null,
        "campaign_name": "{{campaign_name}}",
        "is_advanced_dedicated_campaign": true,
        "is_smart_performance_campaign": false,
        "objective": "APP",
        "roas_bid": 0
    }
}
(/code)
```

### 2. Create an ad group

Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Optimization location<br> (Promotion type) | iOS App | `promotion_type` | `APP_IOS` |
| Placement | Any of the following types: <ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both </li></ul> | `placement_type`<br>`placements`   | Any of the following configurations:<ul><li> Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`</li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and only include `PLACEMENT_TIKTOK` or `PLACEMENT_PANGLE` or both in the value of `placements` </li></ul> |
|  Device - OS versions| iOS 14.5 or later versions | `operating_systems`<br> `min_ios_version`<br> `ios14_targeting` | <ul><li>Set `operating_systems` to `["IOS"]` </li><li> Set `ios14_targeting` to `IOS14_PLUS`</li><li>Set `min_ios_version` to `14.5` or a later version</li></ul> |
| Optimization goal | Any of the following types:<ul><li>Install   </li><li>In-app event </li><li> Value </li></ul>  | `optimization_goal` | Any of the following values: <ul><li>`INSTALL`<ul><li>To use `INSTALL`, you need to ensure third-party tracking has been set up for the selected App. You can use [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297) to check if third-party tracking (`tracking_url`) has been configured for an App.</li><li> To add third-party tracking for an existing App, use [/app/update/](https://business-api.tiktok.com/portal/docs?id=1740859300069378). To learn about how to obtain tracking links from a third-party partner, see [Mobile Measurement Partner (MMP) Tracking](https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking?lang=en).</li></ul></li><li>`IN_APP_EVENT` (with `optimization_event` specified) <ul><li>To use `IN_APP_EVENT`, you need to ensure the specified event (`optimization_event`) is available for the selected App. You can use [/app/optimization_event/](https://business-api.tiktok.com/portal/docs?id=1740859338750977) to obtain the available optimization events  (`optimization_event`)  for the App.</li></ul></li><li>`VALUE` (with `optimization_event` specified) <ul><li> To check whether certain settings are eligible for the Value optimization goal, use [/tool/vbo_status/](https://business-api.tiktok.com/portal/docs?id=1770016073586753). </li></ul></li></ul> |
| Bid Strategy | <ul><li>If optimization goal is Install or In-app event, the strategy can be Maximum Delivery or Cost Cap.</li><li> If optimization goal is Value, the strategy can be Highest value (to spend your budget fully and maximize the value of results) or Minimum ROAS (to keep your average ROAS around or higher than the target ROAS value).</li></ul> | `bid_type`<br>`conversion_bid_price` <br>`deep_bid_type`<br>`roas_bid`   |<ul><li>If `optimization_goal` is `INSTALL` or `IN_APP_EVENT`: <ul><li>Set `bid_type` to `BID_TYPE_CUSTOM` or `BID_TYPE_NO_BID`. Do not pass `deep_bid_type` and `roas_bid`. <ul><li>If `bid_type` is `BID_TYPE_CUSTOM`, pass `conversion_bid_price` at the same time. </li></ul></li></ul></li><li> If `optimization_goal` is `VALUE`:<ul><li>Set `deep_bid_type` to `VO_MIN_ROAS` or `VO_HIGHEST_VALUE`, and set `bid_type` to `BID_TYPE_NO_BID`. Do not pass `conversion_bid_price`.<ul><li>If you set `deep_bid_type` to `VO_MIN_ROAS`, pass in `roas_bid` at the same time.</li></ul></li></ul></li></ul> |
| Select value | When optimization goal is Value and Self Attributing Network (SAN) is used, use any of the following types:<ul><li>Purchase value (also known as VBO IAP, which is short for Value-Based Optimization for in-app purchases) </li><li>Ad revenue value (also known as VBO IAA, which is short for Value-Based Optimization for in-app advertising)  <br><p><span style="color:darkred"><b>Note</b></span>: To use Ad revenue value, ensure that the placement is TikTok only or Pangle only.</li></ul> | `optimization_event`<br>`secondary_optimization_event` | When `optimization_goal` is `VALUE` and at the campaign level `bid_align_type` is `SAN`: <ul><li>If you want to select Purchase value, set `optimization_event` to `ACTIVE_PAY` and set `secondary_optimization_event` to `PURCHASE_ROI`.</li><li>If you want to select Ad revenue value, set `optimization_event` to `AD_REVENUE_VALUE` and do not pass `secondary_optimization_event`.<br><p><span style="color:darkred"><b>Note</b></span>: To use Ad revenue value, ensure that `placement_type` is `PLACEMENT_TYPE_NORMAL` and `placements` is `&#91;"PLACEMENT_TIKTOK"&#93;` or `&#91;"PLACEMENT_PANGLE"&#93;`.</ul><br> To retrieve the `bid_align_type` of a campaign, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986).|
| Time window of the bid strategy | When optimization goal is Value and Self Attributing Network (SAN) is used, use any of the following types: <ul><li>Day 7 ROAS or Highest Value </li><li>Day 0 ROAS or Highest Value  </li></ul>| `vbo_window` | When `optimization_goal` is `VALUE` and at the campaign level `bid_align_type` is `SAN`, use any of the following values: <ul><li>`SEVEN_DAYS`</li><li>`ZERO_DAY` </li></ul><br>For example, if you set `deep_bid_type` to `VO_MIN_ROAS`, pass in `roas_bid`, and set `vbo_window` to `ZERO_DAY`, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value. <br><br> To retrieve the `bid_align_type` of a campaign, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Day 0 or day 7 bidding (`vbo_window` is `ZERO_DAY` or `SEVEN_DAYS`) for <b>VBO IAP</b> in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li><li>Day 0 or day 7 bidding (`vbo_window` is `ZERO_DAY` or `SEVEN_DAYS`) for <b>VBO IAA</b> in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li></ul>|
| Billing event| oCPM|`billing_event`| `OCPM`|
| Delivery Type | <ul><li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li>If Bid Strategy is set to Maximum Delivery, Highest Value or Minimum ROAS, you can only set Delivery Type to Standard. </li></ul> | `pacing` | <ul><li>If `bid_type` is `BID_TYPE_CUSTOM`, you can set `pacing` to `PACING_MODE_SMOOTH` or `PACING_MODE_FAST`.</li><li>If `bid_type` is `BID_TYPE_NO_BID`, you can only set `pacing` to `PACING_MODE_SMOOTH`.</li></ul> |
```


#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_nam}}",
    "promotion_type": "APP_IOS",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "operating_systems":["IOS"],
    "ios14_targeting": "IOS14_PLUS",
    "min_ios_version": "14.5",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "INSTALL",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_name": "{{adgroup_name}}",
        "adgroup_app_profile_page_state": "OFF",
        "is_new_structure": true,
        "video_download_disabled": false,
        "deep_bid_type": "DEFAULT",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "optimization_event": "ACTIVE",
        "conversion_bid_price": 0,
        "share_disabled": false,
        "schedule_type": "SCHEDULE_START_END",
        "interest_category_ids": [],
        "isp_ids": [],
        "keywords": null,
        "bid_display_mode": "CPMV",
        "creative_material_mode": "CUSTOM",
        "comment_disabled": false,
        "delivery_mode": null,
        "is_hfss": false,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "audience_ids": [],
        "optimization_goal": "INSTALL",
        "category_id": "0",
        "auto_targeting_enabled": false,
        "promotion_type": "APP_IOS",
        "click_attribution_window": "SEVEN_DAYS",
        "skip_learning_phase": true,
        "ios14_targeting": "IOS14_PLUS",
        "adgroup_app_profile_page_type": "OFF",
        "gender": "GENDER_UNLIMITED",
        "bid_type": "BID_TYPE_NO_BID",
        "purchased_reach": null,
        "network_types": [],
        "frequency": null,
        "attribution_event_count": "ONCE",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "create_time": "{{create_time}}",
        "inventory_filter_enabled": false,
        "ios14_quota_type": "OCCUPIED",
        "app_type": "APP_IOS",
        "excluded_audience_ids": [],
        "category_exclusion_ids": [],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "smart_interest_behavior_enabled": null,
        "statistic_type": "NONE",
        "pixel_id": null,
        "advertiser_id": "{{advertiser_id}}",
        "operation_status": "ENABLE",
        "billing_event": "OCPM",
        "modify_time": "{{modify_time}}",
        "package": "{{package}}",
        "view_attribution_window": "ONE_DAY",
        "search_result_enabled": true,
        "deep_cpa_bid": 0,
        "campaign_id": "{{campaign_id}}",
        "rf_estimated_frequency": null,
        "next_day_retention": null,
        "age_groups": null,
        "actions": [],
        "scheduled_budget": 0,
        "adgroup_id": "{{adgroup_id}}",
        "location_ids": [
            "6252001"
        ],
        "schedule_infos": null,
        "conversion_window": null,
        "app_download_url": "{{app_download_url}}",
        "feed_type": null,
        "languages": [],
        "device_price_ranges": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "min_ios_version": "14.5",
        "rf_estimated_cpr": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "budget": {{budget}},
        "interest_keyword_ids": [],
        "operating_systems": [
            "IOS"
        ],
        "brand_safety_partner": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "frequency_schedule": null,
        "purchased_impression": null,
        "is_smart_performance_campaign": false,
        "rf_purchased_type": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_name": "{{campaign_name}}",
        "app_id": "{{app_id}}",
        "pacing": "PACING_MODE_SMOOTH",
        "bid_price": 0,
        "secondary_optimization_event": null,
        "smart_audience_enabled": null
    }
}
(/code)
```
### 3. Create an ad
Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). 

#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "75960823018",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "landing_page_url":"{{landing_page_url}}"
    }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "music_id": null,
                "image_ids": [
                    "{{image_id}}"
                ],
                "carousel_image_labels": null,
                "call_to_action": "SHOP_NOW",
                "ad_name": "{{ad_name}}",
                "impression_tracking_url": null,
                "tracking_app_id": "{{tracking_app_id}}",
                "landing_page_urls": null,
                "landing_page_url": "",
                "ad_text": "{{ad_text}}",
                "ad_texts": null,
                "click_tracking_url": "{{click_tracking_url}}",
                "brand_safety_vast_url": null,
                "creative_authorized": false,
                "page_id": null,
                "modify_time": "{{modify_time}}",
                "deeplink_type": "NORMAL",
                "tracking_pixel_id": 0,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "profile_image_url": "{{profile_image_url}}",
                "identity_id": "{{identity_id}}",
                "creative_type": null,
                "viewability_vast_url": null,
                "card_id": null,
                "viewability_postbid_partner": "UNSET",
                "is_aco": false,
                "display_name": "",
                "identity_type": "CUSTOMIZED_USER",
                "campaign_name": "{{campaign_name}}",
                "optimization_event": "ACTIVE",
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "ad_format": "SINGLE_VIDEO",
                "adgroup_name": "{{adgroup_name}}",
                "playable_url": "",
                "deeplink": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "video_id": "{{video_id}}",
                "app_name": "{{app_name}}",
                "operation_status": "ENABLE",
                "advertiser_id": "{{advertiser_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "create_time": "{{create_time}}",
                "ad_id": "{{ad_id}}",
                "vast_moat_enabled": false,
                "call_to_action_id": null
            }
        ]
    }
}
(/code)
```
## Steps for creating Smart+ Advanced Dedicated Campaigns
Create a Smart+ Campaign using [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
    <th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>App promotion</td>
    <td><code>objective_type</code></td>
    <td><code>APP_PROMOTION</code></td>
  </tr>
  <tr>
    <td>App promotion type</td>
    <td>App install</td>
    <td><code>app_promotion_type</code></td>
    <td><code>APP_INSTALL</code></td>
  </tr>
  <tr>
    <td> Smart+ Campaign</td>
    <td>Enabled.<br>Campaigns created via <code>/campaign/spc/create/</code> are all Smart+ Campaigns.</td>
    <td>/</td>
    <td>/<br></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Enabled</td>
    <td><code>campaign_type</code></td>
    <td><code>IOS14_CAMPAIGN</code></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Pass the ID of an iOS app that is eligible for Advanced Dedicated Campaigns.<br><br>To find eligible apps, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786"  target="_blank">/app/list/</a>. The eligible apps should meet the following requirements:<ul><li><code>platform</code> is <code>IOS</code></li><li><code>advanced_dedicated_campaign_allowed</code> is <code>true</code></li></ul><p><span style="color:darkred"><b>Note</b></span>: Getting access to Advanced Dedicated Campaign requires the app to meet certain criteria. If your app is ineligible for Advanced Dedicated Campaign, reach out to your TikTok representative. Your TikTok representative will be able to provide troubleshooting support to help your app get access to Advanced Dedicated Campaign. </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td><code>ON</code>, or <code>OFF</code> (or not passed)</td>
  </tr>
  <tr>
    <td>Use advanced dedicated campaign</td>
    <td>Enabled</td>
    <td><code>is_advanced_dedicated_campaign</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>SKAN attribution</td>
    <td>Enabled or disabled</td>
    <td><code>disable_skan_campaign</code></td>
    <td>Any of the following values: <ul><li><code>true</code>: To disable SKAN attribution. The campaign will not be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610"  target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics"  target="_blank">Self Attribution Network (SAN) metrics</a> for the campaign. However, you cannot retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics"  target="_blank">SKAN reporting metrics</a> for the campaign. Learn more about <a href="https://ads.tiktok.com/help/article/about-self-attribution-transition"  target="_blank">SAN integration</a></li><li><code>false</code>: To enable SKAN attribution. The campaign will be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610"  target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics"  target="_blank">SKAN metrics</a> for the campaign.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Disabling SKAN attribution for Dedicated Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </td>
  </tr>
  <tr>
    <td>Optimization location<br>(Promotion type)</td>
    <td>iOS App</td>
    <td><code>promotion_type</code></td>
    <td><code>APP_IOS</code></td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both </li></ul></td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Any of the following configurations:<ul><li> Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code></li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and only include <code>PLACEMENT_TIKTOK</code> or <code>PLACEMENT_PANGLE</code> or both in the value of <code>placements</code></li></ul></td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following types: <ul><li> Install </li><li> In-app event </li><li> Value </li></ul></td>
    <td><code>optimization_goal</code></td>
	<td>Any of the following values:<ul><li><code>INSTALL</code><ul><li> To use <code>INSTALL</code>, you need to ensure third-party tracking has been set up for the selected App. You can use <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297" target="_blank" >/app/info/</a> to check if third-party tracking (<code>tracking_url</code>) has been configured for an App. </li><li> To add third-party tracking for an existing App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859300069378" target="_blank" >/app/update/</a>. To learn about how to obtain tracking links from a third-party partner, see <a href="https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking?lang=en" target="_blank" >Mobile Measurement Partner (MMP) Tracking</a>.</li></ul></li><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified) <ul><li> To use <code>IN_APP_EVENT</code>, you need to ensure the specified event (<code>optimization_event</code>) is available for the selected App. You can use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank" >/app/optimization_event/</a> to obtain the available optimization events  (<code>optimization_event</code>)  for the App.</li></ul></li><li><code>VALUE</code>  (with <code>optimization_event</code> set to <code>ACTIVE_PAY</code>  or <code>AD_REVENUE_VALUE</code>)<ul><li> To check whether certain settings are eligible for the Value optimization goal, use <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td>
      <ul>
        <li>If optimization goal is Install or In-app event, the strategy can be Maximum Delivery or Cost Cap.</li>
        <li>If optimization goal is Value, the strategy can be Highest value (to spend your budget fully and maximize the value of results) or Minimum ROAS (to keep your average ROAS around or higher than the target ROAS value).</li>
      </ul>
    </td>
    <td><code>bid_type</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
    <td>
      <ul>
        <li>If <code>optimization_goal</code> is <code>INSTALL</code> or <code>IN_APP_EVENT</code>:
          <ul>
            <li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code> and <code>roas_bid</code>.
              <ul>
                <li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li>
              </ul>
            </li>
          </ul>
        </li>
        <li>If <code>optimization_goal</code> is <code>VALUE</code>:
          <ul>
            <li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code>.
              <ul>
                <li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time.</li>
              </ul>
            </li>
          </ul>
        </li>
      </ul>
    </td>
  </tr>
  <tr>
    <td>Select value</td>
    <td>
      <ul>
        <li>When optimization goal is Value and Self Attributing Network (SAN) is used, use any of the following types:
          <ul>
			<li><b>Purchase value</b> (also known as VBO IAP, which is short for Value-Based Optimization for in-app purchases)</li>
			<li><b>Ad revenue value</b>  (also known as VBO IAA, which is short for Value-Based Optimization for in-app advertising)<ul><li> Note: To use Ad revenue value, ensure that the placement is TikTok only or TikTok and Pangle.</li></ul></li>
          </ul>
        </li>
      </ul>
    </td>
    <td><code>optimization_event</code></td>
    <td>When <code>optimization_goal</code> is <code>VALUE</code> and <code>bid_align_type</code> is <code>SAN</code>:
          <ul>
            <li>If you want to select Purchase value, set <code>optimization_event</code> to <code>ACTIVE_PAY</code>.</li>
			<li>If you want to select Ad revenue value, set <code>optimization_event</code> to <code>AD_REVENUE_VALUE</code>.
              <br><p><span style="color:darkred"><b>Note</b></span>: To use Ad revenue value, ensure that <code>placement_type</code> is <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> is <code>["PLACEMENT_TIKTOK"]</code> or <code>["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]</code>.</li></li>
          </ul>
        <br>To retrieve the <code>bid_align_type</code> of a campaign, use <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986"   target="_blank">/campaign/get/</a>.
    </td>
  </tr>
  <tr>
    <td>Time window of the bid strategy</td>
    <td>
      <ul>
        <li>When optimization goal is Value and Self Attributing Network (SAN) is used, use any of the following types:
          <ul>
            <li>Day 7 ROAS or Highest Value</li>
            <li>Day 0 ROAS or Highest Value</li>
          </ul>
        </li>
      </ul>
    </td>
    <td><code>vbo_window</code></td>
    <td>
      <ul>
        <li>When <code>optimization_goal</code> is <code>VALUE</code> and <code>bid_align_type</code> is <code>SAN</code>, use any of the following values:
          <ul>
            <li><code>SEVEN_DAYS</code></li>
            <li><code>ZERO_DAY</code></li>
          </ul>
          <br>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.
          <br><br>To retrieve the <code>bid_align_type</code> of a campaign, use <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986"   target="_blank">/campaign/get/</a>.
          <br><br><p><span style="color:darkred"><b>Note</b></span>:
            <ul>
              <li>Day 0 or day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for <b>VBO IAP</b> in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li>
			  <li>Day 0 or day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for <b>VBO IAA</b> in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li>
            </ul>
          </p>
        </li>
      </ul>
    </td>
  </tr>
  <tr>
     <td>Billing event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td>
      <ul>
        <li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li>
        <li>If Bid Strategy is set to Maximum Delivery, Highest Value, or Minimum ROAS, you can only set Delivery Type to Standard.</li>
      </ul>
    </td>
    <td><code>pacing</code></td>
    <td>
      <ul>
        <li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li>
        <li>If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li>
      </ul>
    </td>
  </tr>
</tbody>
</table>


#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_INSTALL",
    "campaign_type": "IOS14_CAMPAIGN",
    "app_id": "{{app_id}}",
    "is_advanced_dedicated_campaign": true,
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "APP_IOS",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "INSTALL",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "identity_type": "CUSTOMIZED_USER",
    "identity_id": "{{identity_id}}",
    "media_info_list": [
        {
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}",
                    "file_name": "{{file_name}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ]
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "modify_time": "{{modify_time}}",
        "click_attribution_window": "SEVEN_DAYS",
        "pixel_id": null,
        "campaign_type": "IOS14_CAMPAIGN",
        "page_list": null,
        "is_advanced_dedicated_campaign": true,
        "deep_bid_type": "DEFAULT",
        "call_to_action_id": "{{call_to_action_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "budget": {{budget}},
        "scheduled_budget": 0,
        "app_id": "{{app_id}}",
        "advertiser_id": "{{advertiser_i}}",
        "conversion_bid_price": null,
        "campaign_id": "{{campaign_id}}",
        "deeplink_type": null,
        "app_promotion_type": "APP_INSTALL",
        "landing_page_urls": null,
        "optimization_goal": "INSTALL",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "billing_event": "OCPM",
        "bid_type": "BID_TYPE_NO_BID",
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "click_tracking_url": "{{click_tracking_url}}",
        "view_attribution_window": "ONE_DAY",
        "deeplink": "",
        "bid_price": null,
        "excluded_audience_ids": null,
        "optimization_event": "ACTIVE",
        "schedule_start_time": "{{schedule_start_time}}",
        "identity_id": "{{identity_id}}",
        "video_download_disabled": false,
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "create_time": "{{create_time}}",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "schedule_end_time": "{{schedule_end_time}}",
        "app_name": "{{app_name}}",
        "operation_status": "ENABLE",
        "gender": "GENDER_UNLIMITED",
        "schedule_type": "SCHEDULE_FROM_NOW",
        "call_to_action_list": [],
        "comment_disabled": false,
        "share_disabled": false,
        "media_info_list": [
            {
                "media_info": {
                    "tiktok_item_id": null,
                    "identity_id": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": "{{file_name}}"
                    },
                    "identity_type": null
                }
            }
        ],
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "blocked_pangle_app_ids": null,
        "identity_type": "CUSTOMIZED_USER",
        "app_type": "APP_IOS",
        "card_list": null,
        "special_industries": null,
        "is_smart_performance_campaign": true,
        "campaign_app_profile_page_state": "OFF",
        "skip_learning_phase": true,
        "exclude_age_under_eighteen": true,
        "spc_type": "UNSET",
        "location_ids": [
            "6252001"
        ],
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "languages": [],
        "impression_tracking_url": null,
        "campaign_name": "{{campaign_name}}",
        "attribution_event_count": "ONCE",
        "promotion_website_type": null,
        "promotion_type": "APP_IOS",
        "app_download_url": "{{app_download_url}}",
        "objective_type": "APP_PROMOTION"
    }
}
(/code)
```


## Related docs
- [Create a campaign](https://business-api.tiktok.com/portal/docs?id=1737719523561474) 
- [Dedicated Campaign](https://business-api.tiktok.com/portal/docs?id=1740029173531649) 
- [Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1767959274087425)
