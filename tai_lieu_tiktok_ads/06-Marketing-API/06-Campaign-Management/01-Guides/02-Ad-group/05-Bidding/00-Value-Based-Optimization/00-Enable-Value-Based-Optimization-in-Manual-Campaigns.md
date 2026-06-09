---
title: "Enable Value-Based Optimization in Manual Campaigns"
doc_id: 1770019181843458
path: "Marketing API / Campaign Management / Guides / Ad group / Bidding / Value-Based Optimization / Enable Value-Based Optimization in Manual Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable Value-Based Optimization in Manual Campaigns

This article walks you through the steps to enable Value-Based Optimization (VBO) in Manual Campaigns.

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To enable VBO, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  
- VBO IAP (Value-Based Optimization for in-app purchases) and VBO IAA (Value-Based Optimization for in-app advertising) in different scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.
- Some VBO IAA and VBO IAP features are available after you meet the unlocking criteria. To find out the unlocking criteria, refer to the <strong>"Unlocking criteria for VBO"</strong> section below. To learn more about VBO IAP and VBO IAA, see [Value-Based Optimization - Value types](https://business-api.tiktok.com/portal/docs?id=1739381743067137#item-link-Value%20types).
- Day 0 or day 7 bidding for VBO IAP in Advanced Dedicated Campaign or Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.
- Day 0 or day 7 bidding for VBO IAA in Advanced Dedicated Campaign or Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.
 
# Unlocking criteria for VBO
VBO will be automatically unlocked for the promoted App (`app_id`) or website (`pixel_id`) when the App or website meets the following unlocking criteria.

```xtable
| Promoted object {15%}| Unlocking criteria {35%}| Parameter {15%}| Requirement {35%}|
|---|---|---|
| App  (VBO IAP)| The criteria depend on the placement setting you want to use: <ul><li> To use Automatic Placement or non-Pangle-only Select Placement, the App needs to have at least <strong>30 unique Purchase events with value attributed to TikTok or Global App Bundle placements over any consecutive 7 days. </strong></li><li> To use Pangle-only Select Placement, you need to first be allowlisted for VBO with Pangle-only placement. Then the App needs to have at least <strong>50 unique Purchase events with value attributed to Pangle placement in its lifetime. </strong></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li> Once unlocked, VBO IAP will remain available to use for the app.</li><li> VBO with Pangle-only placement is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>  | `app_id` | <ul><li> If you don't want to set `placements` to `PLACEMENT_PANGLE`, the App (`app_id`) needs to have at least 30 unique Purchase events (`optimization_event`= `ACTIVE_PAY`) , which are created with `value` specified in the `properties` object and attributed to TikTok or Global App Bundle placements over any consecutive 7 days. </li><li> If you want to set `placements` to `PLACEMENT_PANGLE`, the App (`app_id`) needs to have at least 50 unique Purchase events (`optimization_event`= `ACTIVE_PAY`), which are created with `value` specified in the `properties` object and attributed to Pangle placement in its lifetime. </li></ul> To check whether the App has accumulated enough events and is supported for a certain placement setting, pass `placements` and the App ID to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753). If the App has accumulated enough events and is supported for the specified `placements`, the returned `vo_status` will be `QUALIFIED`. <br>To report App Events for your App, use [/app/track/](https://ads.tiktok.com/marketing_api/docs?id=1740859196043266) or [/app/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740859218541569). |
| App (VBO IAA) | To unlock VBO IAA for Automatic Placement, TikTok-only, or Pangle-only Select Placement, the App must meet the following requirements: <ul><li> The App is an Android App. </li><li> The Mobile Measurement Partner for the App is AppsFlyer or Adjust.</li><li> The Ads impression ROAS for the App is greater than or equal to 1.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Once unlocked, VBO IAA will remain available to use for the app.  | `app_id` | To enable VBO IAA with `PLACEMENT_TYPE_AUTOMATIC`, `PLACEMENT_TIKTOK`, or `PLACEMENT_PANGLE`, the App (`app_id`) must meet the following requirements: <ul><li> The `platform` returned from [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297) for the App is `ANDROID`.</li><li> The `partner_name` returned from [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297)  for the App is `AppsFlyer` or `Adjust`.</li><li> The <strong> Ads impression ROAS</strong> for the App meets or exceeds 1 during any previous time frame. <ul><li> To confirm if the Ads impression ROAS requirement is met, you can use any of the following methods: <ul><li><strong>Method 1 (Recommended) </strong>: Pass the `app_id` directly to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753) to check whether the App is eligible for VBO IAA.</li><li><strong>Method 2</strong>: Run a synchronous basic report by using [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353). Set `query_lifetime` to `true`, and include the In-app event metric `total_ad_impression_roas` and the attribute metric `tt_app_id` in the request parameter `metrics`. The Ads impression ROAS (`total_ad_impression_roas`) for the App (`tt_app_id`) should be equal to or greater than 1. </li></ul></li></ul></li></ul>  |
| Website | The pixel needs to have at least <strong>20 unique Complete Payment events with value and currency attributed to TikTok, Pangle, or Global App Bundle placements over any consecutive 7 days. </strong><ul><li> If the pixel, over any consecutive 7 days, obtained <strong>at least 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>another 20</strong> attributed to Pangle, any placement is valid.  </li><li> If the pixel, over any consecutive 7 days, obtained <strong>at least 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>less than 20</strong> attributed to Pangle, only the TikTok placement is valid. </li><li> If the pixel, over any consecutive 7 days, obtained <strong>less than 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>more than 20 </strong> attributed to Pangle, only the Pangle placement is valid. </li></ul> <p><span style="color:darkred"><b>Note</b></span>: Once unlocked, VBO will remain available to use for the pixel.| `pixel_id` | The pixel (`pixel_id`) needs to have at least 20 unique Complete Payment events (`optimization_event`= `SHOPPING`) , which are created with `value` and `currency` specified in the `properties` object and attributed to TikTok, Pangle, or Global App Bundle placements over any consecutive 7 days. <ul><li> If the pixel, over any consecutive 7 days, obtained at least 20 unique attributed Complete Payment events with value attributed to TikTok, and another 20 attributed to Pangle, any configurations through `placement_type` and `placements` are supported.</li><li> If the pixel, over any consecutive 7 days, obtained at least 20 unique attributed Complete Payment events with value attributed to TikTok, and less than 20 attributed to Pangle, you can only set `placements` to `PLACEMENT_TIKTOK`.</li><li> If the pixel, over any consecutive 7 days, obtained less than 20 unique attributed Complete Payment events with value attributed to TikTok, and more than 20 attributed to Pangle, you can only set `placements` to `PLACEMENT_PANGLE`.</li></ul>   To check whether the Pixel has accumulated enough events and is supported for a certain placement setting, pass `placements` and the Pixel ID to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753). If the Pixel has accumulated enough events and is supported for the specified `placements`, the returned `vo_status` will be `QUALIFIED`. <br>To report Pixel Events for your App, use [/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) or [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225). |
```

# Steps
> <span style="color:DodgerBlue">**Note**</span><br>
> Before enabling VBO, it is recommended to use [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753) to check whether your settings are eligible for VBO.

## Enable VBO for App
### IAP
#### For iOS apps
To learn about how to enable VBO IAP for iOS apps in Advanced Dedicated Campaigns, see [Create Advanced Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1797011827608577).

#### For Android apps
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {20%} | Parameter {25%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective | App promotion | `objective_type` | `APP_PROMOTION` |
|App promotion type | <ul><li> App Install or </li><li> App retargeting </li></ul> | `app_promotion_type` | <ul><li>`APP_INSTALL` or </li><li> `APP_RETARGETING`</li></ul> |
| Campaign Budget Optimization (CBO) | Not supported | `budget_optimize_on` | Not passed or `false` |
```

**Example**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_INSTALL",
    "campaign_name": "{{campaign_name}}",    
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 400
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_type": "REGULAR_CAMPAIGN",
        "is_smart_performance_campaign": false,
        "deep_bid_type": null,
        "modify_time": "2023-06-27 08:01:50",
        "objective": "APP",
        "campaign_app_profile_page_state": "UNSET",
        "app_promotion_type": "APP_INSTALL",
        "operation_status": "ENABLE",
        "budget": 400,
        "objective_type": "APP_PROMOTION",
        "campaign_id": "{{campaign_id}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_name": "{{campaign_name}}",
        "create_time": "2023-06-27 08:01:50",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "roas_bid": 0,
        "advertiser_id": "{{advertiser_id}}",
        "is_new_structure": true
    }
}
(/code)
```

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {20%} | Parameter {25%} | How to configure the parameter {35%} |
|---|---|---|---|
| Promoted App | Set an Android App that is eligible for VBO IAP|<ul><li>`promotion_type`</li><li>`app_id`</li><li>`operating_systems`</li></ul>  |<ul><li>Set `promotion_type` to `APP_ANDROID`</li><li>Set `app_id` to the ID of an Android App that is eligible for VBO IAP. <br>You can get IDs of Android Apps from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786). For Android Apps, the returned `platform` will be `ANDROID`. <br> To check whether an App ID is eligible for VBO IAP, pass the App ID to the parameter `app_id` in [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753).</li><li>Set `operating_systems` to `["ANDROID"]`</li></ul>  |
| Placement | Any of the following types: <ul><li><b>Automatic Placement</b>  </li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both</li></ul>| `placement_type`<br> `placements` | Any of the following configurations:<ul><li>Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC` </li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and only include `PLACEMENT_TIKTOK` or `PLACEMENT_PANGLE` or both in the value of `placements`</li></ul> |
| Optimization Goal | Value | `optimization_goal` | `VALUE` |
| Select value | Purchase value | `optimization_event` | `ACTIVE_PAY` |
| Deep Conversion Event | ROI of the purchase | `secondary_optimization_event` | `PURCHASE_ROI` |
| Bid Strategy | <ul><li> Highest Value (Highest Gross Revenue): To spend your budget fully and maximize the value of results</li><li> Minimum ROAS (Target ROAS): To keep your average ROAS around or higher than the target ROAS value</li></ul> | <ul><li>`deep_bid_type` </li><li>`bid_type`</li></ul>| <ul><li> Set `deep_bid_type` to `VO_MIN_ROAS` or `VO_HIGHEST_VALUE`<br><p><span style="color:darkred"><b>Note</b></span>: If you set `deep_bid_type` to `VO_MIN_ROAS`, pass in `roas_bid` at the same time.</li><li> Set `bid_type` to `BID_TYPE_NO_BID`</li></ul> |
| Time window of the bid strategy | Any of the following types:<ul><li>Day 7 ROAS or Highest Value </li><li>Day 0 ROAS or Highest Value </li></ul> | `vbo_window` | Any of the following values: <ul><li>`SEVEN_DAYS`</li><li>`ZERO_DAY` </li></ul>For example, if you set `deep_bid_type` to `VO_MIN_ROAS`, pass in `roas_bid`, and set `vbo_window` to `ZERO_DAY`, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br> <br><p><span style="color:darkred"><b>Note</b></span>: Day 0 or Day 7 bidding (`vbo_window` is `ZERO_DAY` or `SEVEN_DAYS`) for VBO IAP in Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. |
| Billing event | oCPM | `billing_event` | `OCPM` |
| Delivery type | Standard | `pacing` | `PACING_MODE_SMOOTH` |
```

**Example**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "optimization_event":"ACTIVE_PAY",
    "secondary_optimization_event":"PURCHASE_ROI",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "video_download_disabled": false,
    "automated_targeting": "OFF",
    "location_ids": ["1733045"],
    "gender": "GENDER_UNLIMITED",
    "operating_systems": ["ANDROID"],
    "targeting_expansion": {
        "enable_expansion": false
    },
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 400.0,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "deep_bid_type":"VO_HIGHEST_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_app_profile_page_state": null,
        "app_download_url": "{{app_download_url}}",
        "optimization_goal": "VALUE",
        "scheduled_budget": 0.0,
        "optimization_event": "ACTIVE_PAY",
        "share_disabled": false,
        "rf_purchased_type": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "keywords": null,
        "pacing": "PACING_MODE_SMOOTH",
        "purchased_reach": null,
        "gender": "GENDER_UNLIMITED",
        "pixel_id": null,
        "ios14_quota_type": "UNOCCUPIED",
        "is_hfss": false,
        "campaign_id": "{{campaign_id}}",
        "languages": [],
        "interest_keyword_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "excluded_audience_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "rf_estimated_frequency": null,
        "modify_time": "2023-06-27 08:06:56",
        "comment_disabled": false,
        "skip_learning_phase": true,
        "network_types": [],
        "category_id": "0",
        "audience_ids": [],
        "app_id": "{{app_id}}",
        "statistic_type": null,
        "billing_event": "OCPM",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "auto_targeting_enabled": false,
        "budget": 400.0,
        "brand_safety_partner": null,
        "conversion_bid_price": 0.0,
        "frequency_schedule": null,
        "promotion_type": "APP_ANDROID",
        "app_type": "APP_ANDROID",
        "bid_price": 0.0,
        "package": "{{package}}",
        "feed_type": null,
        "is_new_structure": true,
        "operation_status": "ENABLE",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "schedule_start_time": "{{schedule_start_time}}",
        "purchased_impression": null,
        "schedule_infos": null,
        "deep_cpa_bid": 0.0,
        "device_price_ranges": null,
        "video_download_disabled": false,
        "conversion_window": null,
        "secondary_optimization_event": "PURCHASE_ROI",
        "schedule_end_time": "{{schedule_end_time}}",
        "location_ids": [
            "1733045"
        ],
        "inventory_filter_enabled": false,
        "operating_systems": [
            "ANDROID"
        ],
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "actions": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "rf_estimated_cpr": null,
        "frequency": null,
        "age_groups": null,
        "creative_material_mode": "CUSTOM",
        "search_result_enabled": true,
        "targeting_expansion": {
            "expansion_enabled": false,
            "expansion_types": []
        },
        "delivery_mode": null,
        "adgroup_name": "{{adgroup_name}}",
        "bid_type": "BID_TYPE_NO_BID",
        "bid_display_mode": "CPMV",
        "interest_category_ids": [],
        "campaign_name": "{{campaign_name}}",
        "create_time": "2023-06-27 08:06:56",
        "isp_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "next_day_retention": null
    }
}
(/code)
```

3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). 

**Example**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
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
(code Success-Response http)
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
                "campaign_id": "{{campaign_id}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "brand_safety_postbid_partner": "UNSET",
                "vast_moat_enabled": false,
                "adgroup_id": "{{adgroup_id}}",
                "ad_texts": null,
                "card_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "ad_format": "SINGLE_VIDEO",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "optimization_event": "ACTIVE_PAY",
                "viewability_vast_url": null,
                "is_new_structure": true,
                "deeplink_type": "NORMAL",
                "ad_name": "{{ad_name}}",
                "operation_status": "ENABLE",
                "landing_page_url": "",
                "app_name": "{{app_name}}",
                "page_id": null,
                "call_to_action_id": null,
                "click_tracking_url": "{{click_tracking_url}}",
                "music_id": null,
                "tracking_pixel_id": 0,
                "create_time": "2023-06-27 08:08:49",
                "creative_authorized": false,
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "profile_image_url": "{{profile_image_url}}",
                "impression_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "modify_time": "2023-06-27 08:08:49",
                "is_aco": false,
                "video_id": "{{video_id}}",
                "viewability_postbid_partner": "UNSET",
                "call_to_action": "SHOP_NOW",
                "advertiser_id": "{{advertiser_id}}",
                "creative_type": null,
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "campaign_name": "{{campaign_name}}",
                "playable_url": "",
                "deeplink": "",
                "ad_text": "{{ad_text}}",
                "brand_safety_vast_url": null,
                "tracking_app_id": "{{tracking_app_id}}",
                "display_name": ""
            }
        ]
    }
}
(/code)
```

### IAA
#### For iOS apps
To learn about how to enable VBO IAA for iOS apps in Advanced Dedicated Campaigns, see [Create Advanced Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1797011827608577).

#### For Android apps


1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
	<th style="width:30%">How to configure the parameter</th>
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
    <td>App Install</td>
    <td><code>app_promotion_type</td>
    <td><code>APP_INSTALL</code></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362"  target="_blank">/campaign/spc/create/</a> to create a Smart+ VBO IAA campaign.</td>
    <td>/</td>
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
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>Disabled</td>
    <td><code>budget_optimize_on</code></td>
    <td>Not passed or <code>false</code></td>
  </tr>
</tbody>
</table>


**Example**

Request
```xcodeblock
(code JSON json)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_INSTALL",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "budget": {{budget}},
        "objective": "APP",
        "campaign_name": "{{campaign_name}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_app_profile_page_state": "UNSET",
        "is_search_campaign": false,
        "objective_type": "APP_PROMOTION",
        "advertiser_id": "{{advertiser_id}}",
        "app_promotion_type": "APP_INSTALL",
        "is_smart_performance_campaign": false,
        "campaign_type": "REGULAR_CAMPAIGN",
        "create_time": "{{create_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "roas_bid": 0,
        "operation_status": "ENABLE",
        "deep_bid_type": null,
        "modify_time": "{{modify_time}}"
    }
}
(/code)
```

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
	<th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="3">Promoted App</td>
    <td rowspan="3">Set an Android App that is eligible for VBO IAA</td>
    <td><code>promotion_type</code></td>
    <td><code>APP_ANDROID</code></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Specify the ID of an Android App that is eligible for VBO IAA. <ul><li> You can get IDs of Android Apps from <a href="https://ads.tiktok.com/marketing_api/docs?id=1740859313270786" target="_blank">/app/list/</a>. For Android Apps, the returned <code>platform</code> will be <code>ANDROID</code>.</li><li> To check whether an App ID is eligible for VBO IAA, pass the App ID to the parameter <code>app_id</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>. The returned <code>vo_iaa_min_roas</code> or <code>vo_iaa_highest_value</code> should be <code>QUALIFIED</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>operating_systems</code></td>
    <td><code>["ANDROID"]</code></li></ul></td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following types: <ul><li><b>Automatic Placement</b>  </li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both</li></ul></td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Any of the following configurations:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code> </li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and only include <code>PLACEMENT_TIKTOK</code> or <code>PLACEMENT_PANGLE</code> or both in the value of <code>placements</code></li></ul></td>
  </tr>
  <tr>
    <td>Optimization Goal</td>
    <td>Value</td>
    <td><code>optimization_goal</code></td>
    <td><code>VALUE</code></td>
  </tr>
  <tr>
    <td rowspan="2">Select value</td>
    <td rowspan="2">Ad revenue value</td>
    <td><code>optimization_event</code></td>
    <td><code>AD_REVENUE_VALUE</code></td>
  </tr>
  <tr>
    <td><code>secondary_optimization_event</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td>Any of the following types: <ul><li> Highest Value (Highest Gross Revenue): To spend your budget fully and maximize the value of results</li><li> Minimum ROAS (Target ROAS): To keep your average ROAS around or higher than the target ROAS value </li></ul></td>
    <td><ul><li><code>deep_bid_type</code></li><li><code>bid_type</code></li></ul> </td>
    <td><ul><li> Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>.<br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time. The value range for <code>roas_bid</code> is 0.01-10.</li><li> Set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>.</li></ul> </td>
  </tr>
  <tr>
    <td>Time window of the bid strategy</td>
    <td>Any of the following types:<ul><li> Day 7 ROAS or Highest Value </li><li>Day 0 ROAS or Highest Value </li></ul> </td>
    <td><code>vbo_window</code></td>
    <td><code>SEVEN_DAYS</code> or <code>ZERO_DAY</code><br><br> For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value. <br><br><p><span style="color:darkred"><b>Note</b></span>: Day 0 or Day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for VBO IAA in Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.  </td>
  </tr>  
  <tr>
    <td>Billing event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
  </tr>
  <tr>
    <td>Delivery type</td>
    <td>Standard</td>
    <td><code>pacing</code></td>
    <td><code>PACING_MODE_SMOOTH</code></td>
  </tr>
</tbody>
</table>


**Example**

Request
```xcodeblock
(code JSON json)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_PANGLE"
    ],
    "location_ids": [
        "1643084"
    ],
    "operating_systems": [
        "ANDROID"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "optimization_event": "AD_REVENUE_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "billing_event": "OCPM",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "schedule_type": "SCHEDULE_START_END",
        "adgroup_name": "{{adgroup_name}}",
        "deep_cpa_bid": 0,
        "pixel_id": null,
        "auto_targeting_enabled": false,
        "skip_learning_phase": true,
        "comment_disabled": false,
        "bid_type": "BID_TYPE_NO_BID",
        "keywords": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "frequency": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "frequency_schedule": null,
        "adgroup_app_profile_page_state": null,
        "purchased_reach": null,
        "conversion_bid_price": 0,
        "purchased_impression": null,
        "interest_keyword_ids": [],
        "is_new_structure": true,
        "search_result_enabled": false,
        "smart_interest_behavior_enabled": null,
        "smart_audience_enabled": null,
        "create_time": "{{create_time}}",
        "operating_systems": [
            "ANDROID"
        ],
        "click_attribution_window": "SEVEN_DAYS",
        "is_hfss": false,
        "view_attribution_window": "ONE_DAY",
        "delivery_mode": null,
        "app_id": "{{app_id}}",
        "package": "{{package}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "bid_display_mode": "CPMV",
        "campaign_id": "{{campaign_id}}",
        "video_download_disabled": false,
        "interest_category_ids": [],
        "next_day_retention": null,
        "adgroup_id": "{{adgroup_id}}",
        "audience_ids": [],
        "bid_price": 0,
        "excluded_audience_ids": [],
        "budget": {{budget}},
        "statistic_type": null,
        "creative_material_mode": "CUSTOM",
        "actions": [],
        "is_smart_performance_campaign": false,
        "languages": [],
        "category_exclusion_ids": [],
        "promotion_type": "APP_ANDROID",
        "attribution_event_count": "EVERY",
        "brand_safety_partner": null,
        "roas_bid": 2,
        "pacing": "PACING_MODE_SMOOTH",
        "secondary_optimization_event": null,
        "isp_ids": [],
        "app_type": "APP_ANDROID",
        "deep_bid_type": "VO_MIN_ROAS",
        "gender": "GENDER_UNLIMITED",
        "category_id": "0",
        "app_download_url": "{{app_download_url}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "optimization_event": "AD_REVENUE_VALUE",
        "location_ids": [
            "1643084"
        ],
        "placements": [
            "PLACEMENT_PANGLE"
        ],
        "share_disabled": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "network_types": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "scheduled_budget": 0,
        "advertiser_id": "{{advertiser_id}}",
        "optimization_goal": "VALUE",
        "campaign_name": "{{campaign_name}}",
        "rf_purchased_type": null,
        "inventory_filter_enabled": false,
        "device_price_ranges": null,
        "ios14_quota_type": "UNOCCUPIED",
        "rf_estimated_frequency": null,
        "conversion_window": null,
        "operation_status": "ENABLE",
        "feed_type": null,
        "rf_estimated_cpr": null,
        "schedule_infos": null,
        "modify_time": "{{modify_time}}",
        "age_groups": null,
        "vbo_window": "SEVEN_DAYS"
    }
}
(/code)
```

3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
	<th style="width:30%">How to configure the parameter</th>
</tr>
</thead>
<tbody>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Custom User (custom identity) </td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad format</td>
    <td>Any of the following types: <ul><li> Single video </li><li> Single image </li></ul> </td>
    <td><code>ad_format</code></td>
    <td>Any of the following values: <ul><li><code>SINGE_VIDEO</code></li><li><code>SINGE_IMAGE</code></li></ul></td>
  </tr>
</tbody>
</table>

**Example**

Request
```xcodeblock
(code JSON json)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "LEARN_MORE"
    }]
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
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
                "viewability_vast_url": null,
                "call_to_action": "LEARN_MORE",
                "creative_authorized": false,
                "call_to_action_id": null,
                "brand_safety_vast_url": null,
                "creative_type": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "is_new_structure": true,
                "create_time": "{{create_time}}",
                "playable_url": "",
                "ad_name": "{{ad_name}}",
                "profile_image_url": "{{profile_image_url}}",
                "identity_type": "CUSTOMIZED_USER",
                "music_id": null,
                "ad_texts": null,
                "campaign_id": "{{campaign_id}}",
                "vast_moat_enabled": false,
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "tracking_app_id": "{{tracking_app_id}}",
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "app_name": "{{app_name}}",
                "page_id": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "tracking_pixel_id": 0,
                "impression_tracking_url": "{{impression_tracking_url}}",
                "deeplink": "",
                "click_tracking_url": "{{click_tracking_url}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "optimization_event": "AD_REVENUE_VALUE",
                "ad_format": "SINGLE_VIDEO",
                "card_id": null,
                "landing_page_url": "",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_name": "{{campaign_name}}",
                "display_name": "",
                "video_id": "{{video_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "ad_text": "{{ad_text}}",
                "viewability_postbid_partner": "UNSET",
                "is_aco": false,
                "deeplink_type": "NORMAL",
                "operation_status": "ENABLE",
                "modify_time": "{{modify_time}}"
            }
        ]
    }
}
(/code)
```
## Enable VBO for Web
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirement must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:29%">Parameter</th>
    <th style="width:26%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>Website Conversions</td>
    <td><code>objective_type</code></td>
    <td><code>WEB_CONVERSIONS</code></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>To learn about how to create a Smart+ Web Conversion campaign, see <a href="https://business-api.tiktok.com/portal/docs?id=1768006268820546"  target="_blank">Create a Smart+ Campaign</a>.</td>
    <td>/</td>
    <td>/</td>
  </tr>
    <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed.</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>Enabled or Disabled<br><br>To learn more about how to create a CBO Web Conversion campaign, see <a href="https://business-api.tiktok.com/portal/docs?id=1739381757818881"  target="_blank">Campaign Budget Optimization</a>.</td>
    <td><code>budget_optimize_on</code></td>
    <td><code>true</code>, or <code>false</code> or not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Enabling CBO and VBO for Web at the same time is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </td>
  </tr>
</tbody>
</table>


**Example**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 400
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "deep_bid_type": null,
        "roas_bid": 0,
        "advertiser_id": "{{advertiser_id}}",
        "objective_type": "WEB_CONVERSIONS",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "objective": "LANDING_PAGE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "create_time": "2023-06-27 05:43:14",
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "is_smart_performance_campaign": false,
        "modify_time": "2023-06-27 05:43:14",
        "budget": 400,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "operation_status": "ENABLE"
    }
}
(/code)
```

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.
> <span style="color:DodgerBlue">**Note**</span><br>
> Ad groups with the optimization goal Automatic Value Optimization cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Promoted Website | Set a pixel that is eligible for VBO | `promotion_type` | `WEBSITE` |
| Promoted Website | Set a pixel that is eligible for VBO | `pixel_id` | Specify the ID of a pixel that is eligible for VBO. <br>To check whether a Pixel ID is eligible for VBO, pass the Pixel ID to the parameter `pixel_id` in [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753). <br> To get pixel IDs, you can use [/pixel/list/](https://ads.tiktok.com/marketing_api/docs?id=1740858697598978). |
| Optimization Goal | Any of the following options:<ul><li>Value: Spend your budget fully while maximizing purchase value, or keep your average return on ad spend (ROAS) around or higher than the target ROAS value</li><li>Automatic Value Optimization: Dynamically optimize for the highest value or maximum delivery via machine learning. </li></ul>  | `optimization_goal` |Any of the following values: <ul><li>`VALUE`</li><li>`AUTOMATIC_VALUE_OPTIMIZATION`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Automatic Value Optimization is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Enabling Automatic Value Optimization and Campaign Budget Optimization at the same time is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>  |
| Conversion Event | Complete Payment | `optimization_event` | `SHOPPING` |
| Bid Strategy |<ul><li>If Optimization Goal is set to Value, you can use any of the following options: <ul><li>Highest Value: To spend your budget fully while maximizing purchase value </li><li> Minimum ROAS: To keep your average return on ad spend (ROAS) around or higher than the target ROAS value </li></ul></li><li>If Optimization Goal is set to Automatic Value Optimization, you can only use Highest Value.</li></ul> | <ul><li>`deep_bid_type`</li><li>`bid_type` </li></ul> | <ul><li>If `optimization_goal` is set to `VALUE`, use the following settings:<ul><li>Set `deep_bid_type` to `VO_MIN_ROAS` or `VO_HIGHEST_VALUE`<br><p><span style="color:darkred"><b>Note</b></span>: If you set `deep_bid_type` to `VO_MIN_ROAS`, pass in `roas_bid` at the same time.</li><li> Set `bid_type` to `BID_TYPE_NO_BID`</li></ul></li><li>If `optimization_goal` is `AUTOMATIC_VALUE_OPTIMIZATION`:<ul><li>`roas_bid` is not supported. </li><li>Set `deep_bid_type` to `VO_HIGHEST_VALUE` and `bid_type` to `BID_TYPE_NO_BID`.<ul><li>The fields `deep_bid_type` and `bid_type` can be omitted. If you omit these fields, `deep_bid_type` will default to `VO_HIGHEST_VALUE` and `bid_type` will default to `BID_TYPE_NO_BID`.</li></ul></li></ul></li></ul>|
| Billing event | oCPM | `billing_event` | `OCPM` |
| Delivery type | Standard | `pacing` | `PACING_MODE_SMOOTH` |
```

**Example**

**Optimization Goal as Value**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "location_ids": ["1733045"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 400.0,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "deep_bid_type":"VO_HIGHEST_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "promotion_website_type": "UNSET",
        "promotion_type": "WEBSITE",
        "schedule_end_time": "{{schedule_end_time}}",
        "adgroup_name": "{{adgroup_name}}",
        "skip_learning_phase": true,
        "statistic_type": null,
        "advertiser_id": "{{advertiser_id}}",
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "bid_price": 0,
        "bid_display_mode": "CPMV",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "search_result_enabled": true,
        "adgroup_app_profile_page_state": null,
        "share_disabled": false,
        "comment_disabled": false,
        "purchased_impression": null,
        "bid_type": "BID_TYPE_NO_BID",
        "actions": [],
        "age_groups": null,
        "schedule_infos": null,
        "pacing": "PACING_MODE_SMOOTH",
        "rf_estimated_cpr": null,
        "auto_targeting_enabled": false,
        "ios14_quota_type": "UNOCCUPIED",
        "deep_cpa_bid": 0,
        "interest_keyword_ids": [],
        "location_ids": [
            "1733045"
        ],
        "gender": "GENDER_UNLIMITED",
        "schedule_start_time": "{{schedule_start_time}}",
        "create_time": "2023-06-27 06:02:23",
        "campaign_id": "{{campaign_id}}",
        "scheduled_budget": 0,
        "billing_event": "OCPM",
        "rf_purchased_type": null,
        "optimization_goal": "VALUE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "creative_material_mode": "CUSTOM",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "device_price_ranges": null,
        "operating_systems": [],
        "budget": 400,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "languages": [],
        "frequency": null,
        "operation_status": "ENABLE",
        "network_types": [],
        "modify_time": "2023-06-27 06:02:23",
        "inventory_filter_enabled": false,
        "interest_category_ids": [],
        "app_download_url": null,
        "conversion_window": null,
        "frequency_schedule": null,
        "brand_safety_partner": null,
        "keywords": null,
        "adgroup_id": "{{adgroup_id}}",
        "campaign_name": "{{campaign_name}}",
        "app_id": null,
        "secondary_optimization_event": null,
        "is_new_structure": true,
        "conversion_bid_price": 0,
        "is_hfss": false,
        "next_day_retention": null,
        "schedule_type": "SCHEDULE_START_END",
        "delivery_mode": null,
        "pixel_id": "{{pixel_id}}",
        "isp_ids": [],
        "optimization_event": "SHOPPING",
        "category_id": "0",
        "video_download_disabled": false,
        "audience_ids": [],
        "rf_estimated_frequency": null,
        "feed_type": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "app_type": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "targeting_expansion": {
            "expansion_types": [],
            "expansion_enabled": false
        },
        "excluded_audience_ids": [],
        "purchased_reach": null
    }
}
(/code)
```

**Optimization Goal as Automatic Value Optimization**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "operation_status": "ENABLE",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "location_ids": ["1733045"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 400.0,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",   
    "optimization_goal": "AUTOMATIC_VALUE_OPTIMIZATION",
    "deep_bid_type":"VO_HIGHEST_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "age_groups": null,
        "pacing": "PACING_MODE_SMOOTH",
        "billing_event": "OCPM",
        "view_attribution_window": "ONE_DAY",
        "keywords": null,
        "adgroup_name": "{{adgroup_name}}",
        "is_smart_performance_campaign": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "rf_estimated_frequency": null,
        "frequency_schedule": null,
        "device_price_ranges": null,
        "frequency": null,
        "bid_display_mode": "CPMV",
        "pixel_id": "{{pixel_id}}",
        "optimization_event": "SHOPPING",
        "modify_time": "{{modify_time}}",
        "feed_type": null,
        "gender": "GENDER_UNLIMITED",
        "comment_disabled": false,
        "adgroup_app_profile_page_state": null,
        "click_attribution_window": "SEVEN_DAYS",
        "smart_interest_behavior_enabled": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "delivery_mode": null,
        "campaign_name": "{{campaign_name}}",
        "conversion_bid_price": 0,
        "adgroup_id": "{{adgroup_id}}",
        "auto_targeting_enabled": false,
        "purchased_reach": null,
        "search_result_enabled": true,
        "actions": [],
        "creative_material_mode": "CUSTOM",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "is_new_structure": true,
        "interest_category_ids": [],
        "attribution_event_count": "EVERY",
        "smart_audience_enabled": null,
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "share_disabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "conversion_window": null,
        "is_hfss": false,
        "budget": {{budget}},
        "advertiser_id": "{{advertiser_id}}",
        "audience_ids": [],
        "inventory_filter_enabled": false,
        "video_download_disabled": false,
        "rf_purchased_type": null,
        "brand_safety_partner": null,
        "promotion_type": "WEBSITE",
        "statistic_type": null,
        "bid_price": 0,
        "product_source": "UNSET",
        "ios14_quota_type": "UNOCCUPIED",
        "languages": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "purchased_impression": null,
        "app_id": null,
        "operation_status": "ENABLE",
        "rf_estimated_cpr": null,
        "interest_keyword_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "secondary_optimization_event": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "category_id": "0",
        "optimization_goal": "AUTOMATIC_VALUE_OPTIMIZATION",
        "create_time": "{{create_time}}",
        "schedule_infos": null,
        "scheduled_budget": 0,
        "deep_cpa_bid": 0,
        "network_types": [],
        "excluded_audience_ids": [],
        "isp_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "promotion_website_type": "UNSET",
        "category_exclusion_ids": [],
        "skip_learning_phase": true,
        "operating_systems": [],
        "next_day_retention": null,
        "location_ids": [
            "1733045"
        ],
        "campaign_id": "{{campaign_id}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "app_type": null,
        "app_download_url": null,
        "bid_type": "BID_TYPE_NO_BID"
    }
}
(/code)
```

3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). 

**Example**

Request
```xcodeblock
(code JSON json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
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
(code Success-Response http)
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
                "advertiser_id": "{{advertiser_id}}",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "campaign_id": "{{campaign_id}}",
                "modify_time": "2023-06-27 06:16:12",
                "fallback_type": "UNSET",
                "campaign_name": "{{campaign_name}}",
                "card_id": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_format": "SINGLE_VIDEO",
                "is_aco": false,
                "adgroup_id": "{{adgroup_id}}",
                "landing_page_urls": null,
                "deeplink_type": "NORMAL",
                "optimization_event": "SHOPPING",
                "click_tracking_url": null,
                "viewability_postbid_partner": "UNSET",
                "is_new_structure": true,
                "viewability_vast_url": null,
                "call_to_action_id": null,
                "ad_text": "{{ad_text}}",
                "call_to_action": "SHOP_NOW",
                "identity_id": "{{identity_id}}",
                "profile_image_url": "{{profile_image_url}}",
                "app_name": "",
                "brand_safety_postbid_partner": "UNSET",
                "ad_name": "{{ad_name}}",
                "display_name": "{{display_name}}",
                "creative_authorized": false,
                "identity_type": "CUSTOMIZED_USER",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "page_id": null,
                "impression_tracking_url": null,
                "vast_moat_enabled": false,
                "operation_status": "ENABLE",
                "ad_texts": null,
                "video_id": "{{video_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_vast_url": null,
                "playable_url": "",
                "create_time": "2023-06-27 06:16:12",
                "ad_id": "{{ad_id}}",
                "creative_type": null,
                "adgroup_name": "{{adgroup_name}}",
                "music_id": null,
                "image_ids": [
                    "{{image_id}}"
                ],
                "landing_page_url": "{{landing_page_url}}",
                "deeplink": ""
            }
        ]
    }
}
(/code)
```

## Enable VBO for Product Sales objective
To enable VBO for the Product Sales objective, you can use the [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753) endpoint to check whether your settings are eligible for VBO. You need to specify `objective_type` as `PRODUCT_SALES` in the API request. If your settings are eligible, the returned `vo_status` will be `QUALIFIED`. Note that the specific settings required for enabling VBO for the Product Sales objective may vary depending on your use case.

