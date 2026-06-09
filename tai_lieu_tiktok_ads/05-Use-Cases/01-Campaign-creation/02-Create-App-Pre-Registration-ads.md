---
title: "Create App Pre-Registration ads"
doc_id: 1772549012687873
path: "Use Cases / Campaign creation / Create App Pre-Registration ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create App Pre-Registration ads

This article walks you through the steps to create App Pre-Registration ads.
# Introduction
App Pre-Registration is a type of app promotion that encourages users to register ahead of a game's launch, allowing for automatic downloads of the app on launch day. By leveraging App Pre-Registration, you can build a user base for the game ahead of its launch, generate publicity and awareness, and build momentum to create a vibrant community on the TikTok platform.

**You can use Campaign Management API to create App Pre-Registration ads, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create App Pre-Registration Ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- App Pre-Registration is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

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
    <td>App promotion</td>
    <td><code>objective_type</code></td>
    <td><code>APP_PROMOTION</code></td>
  </tr>
  <tr>
    <td>App promotion type</td>
    <td>App pre-registration</td>
    <td><code>app_promotion_type</code></td>
    <td><code>APP_PREREGISTRATION</code></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td>Set to <code>REGULAR_CAMPAIGN</code> or not passed.</td>
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
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>If you are not sure about whether the campaign is Smart+ Campaign, you can use the <code>is_smart_performance_campaign</code> field returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1739315828649986"  target="_blank">/campaign/get/</a> to check.</td>
    <td>/<br></td>
    <td>/</td>
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
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_PREREGISTRATION",
    "campaign_name": "{{campaign_name}}",
    "campaign_type": "REGULAR_CAMPAIGN",
    "budget_mode":"BUDGET_MODE_TOTAL",
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
        "campaign_id": "{{campaign_id}}",
        "app_promotion_type": "APP_PREREGISTRATION",
        "is_new_structure": true,
        "deep_bid_type": null,
        "modify_time": "2023-07-24 02:55:32",
        "advertiser_id": "{{advertiser_id}}",
        "create_time": "2023-07-24 02:55:32",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "roas_bid": 0,
        "budget": {{budget}},
        "campaign_app_profile_page_state": "UNSET",
        "objective_type": "APP_PROMOTION",
        "operation_status": "ENABLE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "objective": "LANDING_PAGE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "is_smart_performance_campaign": false,
        "campaign_name": "{{campaign_name}}"
    }
}
(/code)
```
## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

``` xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Promotion type<br>  (Optimization location) | One of the following types: <ul><li>Website</li><li>TikTok Instant Page</li></ul>| `promotion_type`<br>`promotion_website_type`   |<ul><li>To specify promotion type as Website:<ul><li> Set `promotion_type` to `WEBSITE`</li><li>Set `promotion_website_type` to `UNSET` or do not pass this field</li></ul></li><li>To specify promotion type as TikTok Instant Page: <ul><li>Set `promotion_type` to `WEBSITE`</li><li> Set `promotion_website_type` to `TIKTOK_NATIVE_PAGE`</li></ul></li></ul> |
| App | Specify an app that users can pre-register for. | `app_id` | Pass a valid value |
| Optimization event | Specified. <br><p><span style="color:darkred"><b>Note</b></span>: When promotion type is TikTok Instant Page, the only supported optimization event is Click Button.   | `optimization_event` | Specified. <br><p><span style="color:darkred"><b>Note</b></span>: When `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`, this field can only be set to `BUTTON`.|
| Pixel | <ul><li>Required when Promotion type is Website. </li><li>Not supported when Promotion type is TikTok Instant Page. </li></ul>| `pixel_id` |<ul><li>Required when `promotion_website_type` is set to `UNSET` or not passed. </li><li>Not supported when `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`.</li></ul> |
| Placement | When promotion type is TikTok Instant Page, the placement should at least include TikTok or Pangle | `placement_type`<br> `placements` | When `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`, you can either:<ul><li>set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`</li><li>OR set `placement_type` to `PLACEMENT_TYPE_NORMAL` and include at least `PLACEMENT_TIKTOK` or `PLACEMENT_PANGLE` in the value of `placements`</li></ul> |
| Optimization goal | Only Conversion is supported. | `optimization_goal` | `CONVERT` |
| Billing event | Only oCPM is supported. | `billing_event` | `OCPM` |
| Pre-bid brand safety filtering <br> (Inventory filter)    | Disabled | `brand_safety_type` | Not passed or set to `NO_BRAND_SAFETY` |
| Pre-bid brand safety filtering     | Disabled | `brand_safety_partner` | Not passed|
| Split test | Disabled<br><br>Therefore, App Pre-Registration ad groups cannot be used in [/split_test/create/](https://ads.tiktok.com/marketing_api/docs?id=1742666471475201) to create split tests. | / | / |
```

  
  
### Example - Promotion type as Website

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
    "app_id": "{{app_id}}",
    "promotion_type": "WEBSITE",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "ON_WEB_DETAIL",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "location_ids": ["6252001"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget":{{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time":"{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
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
        "billing_event": "OCPM",
        "device_price_ranges": null,
        "excluded_audience_ids": [],
        "share_disabled": false,
        "package": "{{package}}",
        "schedule_type": "SCHEDULE_START_END",
        "age_groups": null,
        "conversion_window": null,
        "pixel_id": "{{pixel_id}}",
        "rf_estimated_frequency": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "create_time": "2023-07-24 03:03:27",
        "adgroup_app_profile_page_state": null,
        "interest_category_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "deep_bid_type": null,
        "advertiser_id": "{{advertiser_id}}",
        "ios14_targeting": "UNSET",
        "adgroup_name": "{{adgroup_name}}",
        "interest_keyword_ids": [],
        "frequency": null,
        "is_new_structure": true,
        "secondary_optimization_event": null,
        "app_download_url": "{{app_download_url}}",
        "statistic_type": null,
        "operation_status": "ENABLE",
        "category_id": "0",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "creative_material_mode": "CUSTOM",
        "brand_safety_partner": null,
        "bid_price": 0,
        "frequency_schedule": null,
        "gender": "GENDER_UNLIMITED",
        "modify_time": "2023-07-24 03:03:27",
        "ios14_quota_type": "UNOCCUPIED",
        "app_id": "{{app_id}}",
        "rf_purchased_type": null,
        "deep_cpa_bid": 0,
        "actions": [],
        "audience_ids": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "search_result_enabled": true,
        "purchased_reach": null,
        "app_type": "APP_IOS",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "comment_disabled": false,
        "feed_type": null,
        "isp_ids": [],
        "schedule_infos": null,
        "campaign_name": "{{campaign_name}}",
        "pacing": "PACING_MODE_SMOOTH",
        "scheduled_budget": 0,
        "location_ids": [
            "6252001"
        ],
        "delivery_mode": null,
        "video_download_disabled": true,
        "inventory_filter_enabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "network_types": [],
        "category_exclusion_ids": [],
        "optimization_goal": "CONVERT",
        "skip_learning_phase": true,
        "purchased_impression": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "bid_display_mode": "CPMV",
        "promotion_type": "WEBSITE",
        "next_day_retention": null,
        "languages": [],
        "conversion_bid_price": 1,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "rf_estimated_cpr": null,
        "optimization_event": "ON_WEB_DETAIL",
        "operating_systems": [],
        "auto_targeting_enabled": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "campaign_id": "{{campaign_id}}",
        "is_hfss": false,
        "promotion_website_type": "UNSET",
        "adgroup_id": "{{adgroup_id}}",
        "keywords": null,
        "budget": {{budget}}
    }
}
(/code)
```

### Example - Promotion type as TikTok Instant Page

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
    "app_id": "{{app_id}}",
    "promotion_type": "WEBSITE",
    "promotion_website_type":"TIKTOK_NATIVE_PAGE",
    "optimization_event": "BUTTON",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "location_ids": ["6252001"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget":{{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time":"{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "bid_type": "BID_TYPE_NO_BID",
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
        "optimization_event": "BUTTON",
        "auto_targeting_enabled": false,
        "keywords": null,
        "secondary_optimization_event": null,
        "age_groups": null,
        "category_exclusion_ids": [],
        "next_day_retention": null,
        "promotion_website_type": "TIKTOK_NATIVE_PAGE",
        "schedule_start_time": "{{schedule_start_time}}",
        "create_time": "2023-07-24 03:23:17",
        "deep_cpa_bid": 0,
        "location_ids": [
            "6252001"
        ],
        "device_price_ranges": null,
        "app_download_url": "{{app_download_url}}",
        "audience_ids": [],
        "search_result_enabled": true,
        "scheduled_budget": 0,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "share_disabled": false,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "promotion_type": "WEBSITE",
        "frequency_schedule": null,
        "purchased_reach": null,
        "campaign_id": "{{campaign_id}}",
        "interest_keyword_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "isp_ids": [],
        "is_new_structure": true,
        "pixel_id": null,
        "feed_type": null,
        "campaign_name": "{{campaign_name}}",
        "bid_type": "BID_TYPE_NO_BID",
        "languages": [],
        "modify_time": "2023-07-24 03:23:17",
        "optimization_goal": "CONVERT",
        "package": "{{package}}",
        "deep_bid_type": null,
        "ios14_quota_type": "UNOCCUPIED",
        "skip_learning_phase": true,
        "excluded_audience_ids": [],
        "category_id": "0",
        "conversion_window": null,
        "pacing": "PACING_MODE_SMOOTH",
        "operating_systems": [],
        "frequency": null,
        "gender": "GENDER_UNLIMITED",
        "bid_price": 0,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "schedule_infos": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "creative_material_mode": "CUSTOM",
        "actions": [],
        "delivery_mode": null,
        "rf_estimated_cpr": null,
        "app_id": "{{app_id}}",
        "is_hfss": false,
        "adgroup_app_profile_page_state": null,
        "comment_disabled": false,
        "bid_display_mode": "CPMV",
        "operation_status": "ENABLE",
        "inventory_filter_enabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_estimated_frequency": null,
        "network_types": [],
        "billing_event": "OCPM",
        "conversion_bid_price": 0,
        "schedule_type": "SCHEDULE_START_END",
        "statistic_type": null,
        "brand_safety_partner": null,
        "video_download_disabled": true,
        "interest_category_ids": [],
        "budget": 40,
        "app_type": "APP_IOS",
        "advertiser_id": "{{advertiser_id}}",
        "adgroup_id": "{{adgroup_id}}",
        "purchased_impression": null,
        "ios14_targeting": "UNSET",
        "rf_purchased_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "adgroup_name": "{{adgroup_name}}"
    }
}
(/code)
```

## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

``` xtable
| Setting{15%} | Requirement{25%} | Parameter{18%} | How to configure the parameter{42%} |
|---|---|---|
| Destination |<ul><li>If promotion type is specified as Website at the ad group level, a landing page URL must be specified. </li><li>If promotion type is specified as TikTok Instant Page at the ad group level, a TikTok Instant Page must be specified.</li></ul> | `landing_page_url` <br>`page_id`<br> `creative_type` |<ul><li>If `promotion_website_type` is set to `UNSET` or not passed at the ad group level, you need to pass in `landing_page_url`.</li><li> If `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE` at the ad group level, you need to specify a TikTok Instant Page through `page_id`. <ul><li> At the same time, you can omit `creative_type`, then the field will default to `CUSTOM_INSTANT_PAGE`. Or you can manually set `creative_type` to `CUSTOM_INSTANT_PAGE`.</li></ul></li></ul>  |
| Deeplink | Disabled | `deeplink`<br> `deeplink_type` | Not passed |
```

### Example - Promotion type as Website

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
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SIGN_UP",
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
                "call_to_action": "SIGN_UP",
                "vast_moat_enabled": false,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "playable_url": "",
                "is_aco": false,
                "app_name": "",
                "identity_type": "CUSTOMIZED_USER",
                "profile_image_url": "{{profile_image_url}}",
                "creative_authorized": false,
                "advertiser_id": "{{advertiser_id}}",
                "display_name": "{{display_name}}",
                "deeplink": "",
                "operation_status": "ENABLE",
                "page_id": null,
                "ad_format": "SINGLE_VIDEO",
                "brand_safety_postbid_partner": "UNSET",
                "call_to_action_id": null,
                "impression_tracking_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "campaign_id": "{{campaign_id}}",
                "create_time": "2023-07-24 03:07:21",
                "identity_id": "{{identity_id}}",
                "creative_type": null,
                "landing_page_urls": null,
                "is_new_structure": true,
                "click_tracking_url": "{{click_tracking_url}}",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "ad_texts": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "video_id": "{{video_id}}",
                "viewability_postbid_partner": "UNSET",
                "modify_time": "2023-07-24 03:07:21",
                "ad_id": "{{ad_id}}",
                "ad_text": "{{ad_text}}",
                "adgroup_name": "{{adgroup_name}}",
                "campaign_name": "{{campaign_name}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "optimization_event": "ON_WEB_DETAIL",
                "music_id": null,
                "deeplink_type": "NORMAL",
                "viewability_vast_url": null,
                "card_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "brand_safety_vast_url": null,
                "tracking_app_id": "{{tracking_app_id}}",
                "image_ids": [
                    "{{image_ids}}"
                ]
            }
        ]
    }
}
(/code)
```

### Example - Promotion type as TikTok Instant Page

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
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SIGN_UP",
        "page_id": "{{page_id}}"
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
                "brand_safety_vast_url": null,
                "viewability_postbid_partner": "UNSET",
                "tracking_app_id": "{{tracking_app_id}}",
                "landing_page_urls": null,
                "creative_type": "CUSTOM_INSTANT_PAGE",
                "tracking_pixel_id": 0,
                "playable_url": "",
                "video_id": "{{video_id}}",
                "is_aco": false,
                "brand_safety_postbid_partner": "UNSET",
                "page_id": {{page_id}},
                "ad_name": "{{ad_name}}",
                "click_tracking_url": "{{click_tracking_url}}",
                "creative_authorized": false,
                "ad_format": "SINGLE_VIDEO",
                "landing_page_url": "",
                "viewability_vast_url": null,
                "app_name": "",
                "optimization_event": "BUTTON",
                "music_id": null,
                "ad_text": "{{ad_text}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "identity_id": "{{identity_id}}",
                "modify_time": "2023-07-24 03:25:53",
                "identity_type": "CUSTOMIZED_USER",
                "call_to_action_id": null,
                "impression_tracking_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "call_to_action": "SIGN_UP",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "image_ids": [
                    "{{image_id}}"
                ],
                "deeplink": null,
                "campaign_id": "{{campaign_id}}",
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "profile_image_url": "{{profile_image_url}}",
                "vast_moat_enabled": false,
                "create_time": "2023-07-24 03:25:53",
                "is_new_structure": true,
                "ad_texts": null,
                "advertiser_id": "{{advertiser_id}}",
                "campaign_name": "{{campaign_name}}",
                "operation_status": "ENABLE",
                "card_id": null,
                "display_name": "{{display_name}}"
            }
        ]
    }
}
(/code)
```

# Related docs
[Campaign Management API](https://ads.tiktok.com/marketing_api/docs?id=1739381823123458&rid=24jiu7kp2km)

[Campaign Management Guide](https://ads.tiktok.com/marketing_api/docs?id=1735713781404673&rid=24jiu7kp2km)

[App promotion](https://business-api.tiktok.com/portal/docs?id=1747553433902082)
