---
title: "Custom Product Pages"
doc_id: 1798658439859202
path: "Marketing API / Campaign Management / Guides / Ad / Custom Product Pages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Custom Product Pages

Custom Product Pages (CPPs) are customized product page versions where you can tailor the screenshots, promotional text, and app previews to your advertising strategies. With CPPs, you can highlight specific content or features of your promoted app, reach specific audiences, or showcase seasonal events. 

# How to use Custom Product Pages in ads

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To use Custom Product Pages in ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- Using Custom Product Pages in ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
- To learn about how to create Custom Product Pages in the Apple App Store and obtain the corresponding CPP URLs, see [Custom product pages on the App Store](https://developer.apple.com/app-store/custom-product-pages/). 

## Steps

### 1. Create a campaign

Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {17%} | Requirement {23%} | Parameter {25%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective | App promotion | `objective_type` | `APP_PROMOTION` |
| App promotion type | Any of the following types: <ul><li>App install </li><li>App retargeting </li><li> App pre-registration  </li></ul>| `app_promotion_type` | Any of the following values:<ul><li>`APP_INSTALL`</li><li>`APP_RETARGETING` </li><li>`APP_PREREGISTRATION`</li></ul> |
| iOS 14 Dedicated Campaign | <ul><li>If App promotion type is App install: <ul><li>Enabled </li></ul></li><li>If App promotion type is App retargeting or App pre-registration:  <ul><li> Disabled </li></ul></li></ul>| `campaign_type` <br>`app_id` <br>`campaign_app_profile_page_state`| <ul><li>If `app_promotion_type` is `APP_INSTALL`: <ul><li> Set `campaign_type` to `IOS14_CAMPAIGN`. </li><li> Set `app_id` to the ID of an iOS App. To obtain iOS App IDs within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). The `platform` of an iOS App should be `IOS`.</li><li>Set `campaign_app_profile_page_state` to `ON` or `OFF` (or do not pass the field).</li></ul></li><li>If `app_promotion_type` is `APP_RETARGETING` or `APP_PREREGISTRATION`:<ul><li>Set `campaign_type` to `REGULAR_CAMPAIGN`, or do not pass the field. </li><li>Do not pass `app_id`.</li><li>Set `campaign_app_profile_page_state` to `OFF`, or do not pass the field.</li></ul></li></ul>|
| Smart+ Campaign | Disabled | / | / |
```


#### Example

**Create an App Install campaign without App Profile Page**

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
        "budget": {{budget}},
        "objective_type": "APP_PROMOTION",
        "campaign_type": "IOS14_CAMPAIGN",
        "campaign_app_profile_page_state": "OFF",
        "is_new_structure": true,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "app_id": "{{app_id}}",
        "operation_status": "ENABLE",
        "roas_bid": 0,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_search_campaign": false,
        "is_advanced_dedicated_campaign": false,
        "campaign_name": "{{campaign_name}}",
        "is_smart_performance_campaign": false,
        "create_time": "{{create_time}}",
        "campaign_id": "{{campaign_id}}",
        "deep_bid_type": null,
        "modify_time": "{{modify_time}}",
        "objective": "APP",
        "app_promotion_type": "APP_INSTALL",
        "advertiser_id": "{{advertiser_id}}"
    }
}
(/code)
```

**Create an App Install campaign with App Profile Page**

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
    "campaign_app_profile_page_state":"ON",
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
        "is_search_campaign": false,
        "is_new_structure": true,
        "app_promotion_type": "APP_INSTALL",
        "campaign_name": "{{campaign_name}}",
        "app_id": "{{app_id}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "objective_type": "APP_PROMOTION",
        "create_time": "{{create_time}}",
        "campaign_type": "IOS14_CAMPAIGN",
        "roas_bid": 0,
        "is_smart_performance_campaign": false,
        "budget": {{budget}},
        "operation_status": "ENABLE",
        "objective": "APP",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "modify_time": "{{modify_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_id": "{{campaign_id}}",
        "deep_bid_type": null,
        "campaign_app_profile_page_state": "ON"
    }
}
(/code)
```

**Create an App Retargeting campaign**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_RETARGETING",
    "campaign_type": "REGULAR_CAMPAIGN",
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
        "is_search_campaign": false,
        "is_new_structure": true,
        "app_promotion_type": "APP_RETARGETING",
        "campaign_name": "{{campaign_name}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "objective_type": "APP_PROMOTION",
        "create_time": "{{create_time}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "roas_bid": 0,
        "is_smart_performance_campaign": false,
        "budget": {{budget}},
        "operation_status": "ENABLE",
        "objective": "APP",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "modify_time": "{{modify_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_id": "{{campaign_id}}",
        "deep_bid_type": null,
        "campaign_app_profile_page_state": "UNSET"
    }
}
(/code)
```

**Create an App Pre-Registration campaign**

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
    "campaign_type": "REGULAR_CAMPAIGN",
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
        "is_advanced_dedicated_campaign": false,
        "operation_status": "ENABLE",
        "deep_bid_type": null,
        "modify_time": "{{modify_time}}",
        "is_search_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "budget": {{budget}},
        "campaign_id": "{{campaign_id}}",
        "is_smart_performance_campaign": false,
        "roas_bid": 0,
        "app_promotion_type": "APP_PREREGISTRATION",
        "campaign_app_profile_page_state": "UNSET",
        "objective_type": "APP_PROMOTION",
        "objective": "LANDING_PAGE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "is_new_structure": true,
        "create_time": "{{create_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_name": "{{campaign_name}}"
    }
}
(/code)
```
### 2. Create an ad group

Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Promotion type <br>(Optimization location) | <ul><li>If App promotion type is App install or App retargeting: <ul><li>App</li></ul></li><li>If App promotion type is App pre-registration: <ul><li>TikTok Instant Page</li></ul></li></ul>| `promotion_type` <br>`promotion_website_type` | <ul><li>If `app_promotion_type` is `APP_INSTALL` or `APP_RETARGETING`: <ul><li>Set `promotion_type` to `APP_IOS`.</li><li>Set `promotion_website_type` to `UNSET` or do not pass the field. </li></ul></li><li>If `app_promotion_type` is `APP_PREREGISTRATION`:<ul><li>Set `promotion_type` to `WEBSITE`.</li><li>Set `promotion_website_type` to `TIKTOK_NATIVE_PAGE`.</li></ul></li></ul>|
| Promoted App |<ul><li>If App promotion type is App install:<ul><li>Do not specify the App because it has been specified at the campaign level </li></ul></li><li>If App promotion type is App retargeting or App pre-registration:<ul><li>Specify an iOS App </li></ul></li></ul>  | `app_id` | <ul><li>If `app_promotion_type` is `APP_INSTALL`: <ul><li>Do not pass this field.</li></ul></li><li> If `app_promotion_type` is `APP_RETARGETING` or `APP_PREREGISTRATION`: <ul><li>Set this field to the ID of an iOS App. To obtain iOS App IDs within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). The `platform` of an iOS App should be `IOS`.</li></ul></li></ul>|
| Placement | Any of the following options:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both </li></ul> | `placement_type`<br>  `placements` | Any of the following settings: <ul><li>Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`. </li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and `placements` to `["PLACEMENT_TIKTOK"]`, `["PLACEMENT_PANGLE"]`, or `["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]`.</li></ul>|
| Audience targeting <br>&#183; Device - OS versions| <ul><li>If App promotion type is App install or App retargeting: <ul><li>iOS 15.0 or later versions because CPPs can only be opened on devices running iOS 15 or later versions.</li></ul></li><li> If App promotion type is App pre-registration: <ul><li>all versions, including iOS 15.0 and later versions.<br><p><span style="color:darkred"><b>Note</b></span>: Targeting exclusively iOS 15.0 and later versions in App pre-registration ad groups is not supported.</li></ul></li></ul>| `operating_systems`<br> `min_ios_version`<br> `ios14_targeting` <br>`audience_type` |<ul><li> If `app_promotion_type` is `APP_INSTALL`: <ul><li>Set `operating_systems` to `["IOS"]`.</li><li>Set `ios14_targeting` to `IOS14_PLUS`.</li><li>Set `min_ios_version` to `15.0` or a later version.</li><li>Do not pass `audience_type`. </li></ul></li><li>If `app_promotion_type` is `APP_RETARGETING`:<ul><li>Set `operating_systems` to `["IOS"]`.</li><li>Set `ios14_targeting` to `ALL`.</li><li> Set `min_ios_version` to `15.0` or a later version. </li><li> Set `audience_type` to `NEW_CUSTOM_AUDIENCE`. </li></ul></li><li>If `app_promotion_type` is `APP_PREREGISTRATION`:<ul><li>Set `operating_systems` to `["IOS"]`.</li><li>Do not pass `ios14_targeting`, `min_ios_version` and `audience_type`.</li></ul></li></ul>|
```


#### Example

**Create an App Install ad group without App Profile Page**

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "operating_systems":["IOS"],
    "ios14_targeting": "IOS14_PLUS",
    "min_ios_version": "15.0",
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
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_download_url": "{{app_download_url}}",
        "keywords": null,
        "pacing": "PACING_MODE_SMOOTH",
        "auto_targeting_enabled": false,
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "category_exclusion_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "network_types": [],
        "create_time": "{{create_time}}",
        "skip_learning_phase": true,
        "operating_systems": [
            "IOS"
        ],
        "age_groups": null,
        "campaign_id": "{{campaign_id}}",
        "view_attribution_window": "ONE_DAY",
        "smart_interest_behavior_enabled": null,
        "gender": "GENDER_UNLIMITED",
        "modify_time": "{{modify_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "excluded_audience_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "app_type": "APP_IOS",
        "adgroup_id": "{{adgroup_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "creative_material_mode": "CUSTOM",
        "optimization_event": "ACTIVE",
        "is_new_structure": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "delivery_mode": null,
        "adgroup_app_profile_page_type": "OFF",
        "conversion_window": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "bid_display_mode": "CPMV",
        "category_id": "0",
        "app_id": "{{app_id}}",
        "bid_type": "BID_TYPE_NO_BID",
        "isp_ids": [],
        "purchased_reach": null,
        "promotion_type": "APP_IOS",
        "pixel_id": null,
        "rf_estimated_frequency": null,
        "interest_category_ids": [],
        "smart_audience_enabled": null,
        "conversion_bid_price": 0,
        "bid_price": 0,
        "purchased_impression": null,
        "frequency": null,
        "adgroup_name": "{{adgroup_name}}",
        "scheduled_budget": 0,
        "click_attribution_window": "SEVEN_DAYS",
        "is_smart_performance_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "statistic_type": "NONE",
        "feed_type": null,
        "next_day_retention": null,
        "budget": {{budget}},
        "adgroup_app_profile_page_state": "OFF",
        "ios14_quota_type": "OCCUPIED",
        "rf_purchased_type": null,
        "search_result_enabled": true,
        "deep_cpa_bid": 0,
        "billing_event": "OCPM",
        "frequency_schedule": null,
        "ios14_targeting": "IOS14_PLUS",
        "rf_estimated_cpr": null,
        "inventory_filter_enabled": false,
        "location_ids": [
            "6252001"
        ],
        "optimization_goal": "INSTALL",
        "interest_keyword_ids": [],
        "operation_status": "ENABLE",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "is_hfss": false,
        "languages": [],
        "secondary_optimization_event": null,
        "package": "{{package}}",
        "schedule_infos": null,
        "attribution_event_count": "ONCE",
        "comment_disabled": false,
        "actions": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_name": "{{campaign_name}}",
        "audience_ids": [],
        "min_ios_version": "15.0",
        "brand_safety_partner": null,
        "device_price_ranges": null,
        "deep_bid_type": "DEFAULT",
        "video_download_disabled": false
    }
}
(/code)
```

**Create an App Install ad group with App Profile Page**

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
    "location_ids": ["6252001"],
    "operating_systems":["IOS"],
    "ios14_targeting": "IOS14_PLUS",
    "min_ios_version": "15.0",
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
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_download_url": "{{app_download_url}}",
        "keywords": null,
        "pacing": "PACING_MODE_SMOOTH",
        "auto_targeting_enabled": false,
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "category_exclusion_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "network_types": [],
        "create_time": "{{create_time}}",
        "skip_learning_phase": true,
        "operating_systems": [
            "IOS"
        ],
        "age_groups": null,
        "campaign_id": "{{campaign_id}}",
        "view_attribution_window": "ONE_DAY",
        "smart_interest_behavior_enabled": null,
        "gender": "GENDER_UNLIMITED",
        "modify_time": "{{modify_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "excluded_audience_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "app_type": "APP_IOS",
        "adgroup_id": "{{adgroup_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "creative_material_mode": "CUSTOM",
        "optimization_event": "ACTIVE",
        "is_new_structure": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "delivery_mode": null,
        "adgroup_app_profile_page_type": "ON",
        "conversion_window": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "bid_display_mode": "CPMV",
        "category_id": "0",
        "app_id": "{{app_id}}",
        "bid_type": "BID_TYPE_NO_BID",
        "isp_ids": [],
        "purchased_reach": null,
        "promotion_type": "APP_IOS",
        "pixel_id": null,
        "rf_estimated_frequency": null,
        "interest_category_ids": [],
        "smart_audience_enabled": null,
        "conversion_bid_price": 0,
        "bid_price": 0,
        "purchased_impression": null,
        "frequency": null,
        "adgroup_name": "{{adgroup_name}}",
        "scheduled_budget": 0,
        "click_attribution_window": "SEVEN_DAYS",
        "is_smart_performance_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "statistic_type": "NONE",
        "feed_type": null,
        "next_day_retention": null,
        "budget": {{budget}},
        "adgroup_app_profile_page_state": "ON",
        "ios14_quota_type": "OCCUPIED",
        "rf_purchased_type": null,
        "search_result_enabled": true,
        "deep_cpa_bid": 0,
        "billing_event": "OCPM",
        "frequency_schedule": null,
        "ios14_targeting": "IOS14_PLUS",
        "rf_estimated_cpr": null,
        "inventory_filter_enabled": false,
        "location_ids": [
            "6252001"
        ],
        "optimization_goal": "INSTALL",
        "interest_keyword_ids": [],
        "operation_status": "ENABLE",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "is_hfss": false,
        "languages": [],
        "secondary_optimization_event": null,
        "package": "{{package}}",
        "schedule_infos": null,
        "attribution_event_count": "ONCE",
        "comment_disabled": false,
        "actions": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_name": "{{campaign_name}}",
        "audience_ids": [],
        "min_ios_version": "15.0",
        "brand_safety_partner": null,
        "device_price_ranges": null,
        "deep_bid_type": "DEFAULT",
        "video_download_disabled": false
    }
}
(/code)
```
**Create an App Retargeting ad group**

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
    "promotion_type": "APP_IOS",
    "app_id": "{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "operating_systems": ["IOS"],
    "ios14_targeting": "ALL",
    "min_ios_version": "15.0",
    "audience_type": "NEW_CUSTOM_AUDIENCE",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "IN_APP_EVENT",
    "optimization_event": "LAUNCH_APP",
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
        "app_download_url": "{{app_download_url}}",
        "keywords": null,
        "pacing": "PACING_MODE_SMOOTH",
        "auto_targeting_enabled": false,
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "category_exclusion_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "network_types": [],
        "create_time": "{{create_time}}",
        "skip_learning_phase": true,
        "operating_systems": [
            "IOS"
        ],
        "age_groups": null,
        "campaign_id": "{{campaign_id}}",
        "view_attribution_window": "ONE_DAY",
        "smart_interest_behavior_enabled": null,
        "gender": "GENDER_UNLIMITED",
        "modify_time": "{{modify_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "excluded_audience_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "app_type": "APP_IOS",
        "adgroup_id": "{{adgroup_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "creative_material_mode": "CUSTOM",
        "optimization_event": "LAUNCH_APP",
        "is_new_structure": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "delivery_mode": null,
        "adgroup_app_profile_page_type": "UNSET",
        "conversion_window": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "bid_display_mode": "CPMV",
        "category_id": "0",
        "audience_type": "NEW_CUSTOM_AUDIENCE",
        "app_id": "{{app_id}}",
        "bid_type": "BID_TYPE_NO_BID",
        "isp_ids": [],
        "purchased_reach": null,
        "promotion_type": "APP_IOS",
        "pixel_id": null,
        "rf_estimated_frequency": null,
        "interest_category_ids": [],
        "smart_audience_enabled": null,
        "conversion_bid_price": 0,
        "bid_price": 0,
        "purchased_impression": null,
        "frequency": null,
        "adgroup_name": "{{adgroup_name}}",
        "scheduled_budget": 0,
        "click_attribution_window": "SEVEN_DAYS",
        "is_smart_performance_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "statistic_type": "NONE",
        "feed_type": null,
        "next_day_retention": null,
        "budget": {{budget}},
        "adgroup_app_profile_page_state": "UNSET",
        "ios14_quota_type": "UNOCCUPIED",
        "rf_purchased_type": null,
        "search_result_enabled": true,
        "deep_cpa_bid": 0,
        "billing_event": "OCPM",
        "frequency_schedule": null,
        "ios14_targeting": "ALL",
        "rf_estimated_cpr": null,
        "inventory_filter_enabled": false,
        "location_ids": [
            "6252001"
        ],
        "optimization_goal": "IN_APP_EVENT",
        "interest_keyword_ids": [],
        "operation_status": "ENABLE",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "is_hfss": false,
        "languages": [],
        "secondary_optimization_event": null,
        "package": "{{package}}",
        "schedule_infos": null,
        "attribution_event_count": "ONCE",
        "comment_disabled": false,
        "actions": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_name": "{{campaign_name}}",
        "audience_ids": [],
        "min_ios_version": "15.0",
        "brand_safety_partner": null,
        "device_price_ranges": null,
        "deep_bid_type": "AEO",
        "video_download_disabled": false
    }
}
(/code)
```
**Create an App Pre-Registration ad group**

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
    "promotion_type": "WEBSITE",
    "promotion_website_type": "TIKTOK_NATIVE_PAGE",
    "app_id": "{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "operating_systems": ["IOS"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "optimization_event": "BUTTON",
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
        "smart_audience_enabled": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "category_exclusion_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "bid_display_mode": "CPMV",
        "inventory_filter_enabled": false,
        "attribution_event_count": "ONCE",
        "budget": {{budget}},
        "network_types": [],
        "campaign_name": "{{campaign_name}}",
        "purchased_impression": null,
        "delivery_mode": null,
        "conversion_bid_price": 0,
        "pixel_id": null,
        "location_ids": [
            "6252001"
        ],
        "adgroup_app_profile_page_state": null,
        "search_result_enabled": true,
        "is_new_structure": true,
        "optimization_event": "BUTTON",
        "actions": [],
        "schedule_type": "SCHEDULE_START_END",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "adgroup_name": "{{adgroup_name}}",
        "operation_status": "ENABLE",
        "age_groups": null,
        "gender": "GENDER_UNLIMITED",
        "smart_interest_behavior_enabled": null,
        "deep_bid_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "feed_type": null,
        "video_download_disabled": false,
        "creative_material_mode": "CUSTOM",
        "ios14_quota_type": "UNOCCUPIED",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "secondary_optimization_event": null,
        "purchased_reach": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "audience_ids": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": "{{app_download_url}}",
        "app_id": "{{app_id}}",
        "promotion_website_type": "TIKTOK_NATIVE_PAGE",
        "view_attribution_window": "ONE_DAY",
        "adgroup_id": "{{adgroup_id}}",
        "billing_event": "OCPM",
        "bid_price": 0,
        "skip_learning_phase": true,
        "is_hfss": false,
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "frequency": null,
        "statistic_type": null,
        "interest_category_ids": [],
        "optimization_goal": "CONVERT",
        "device_price_ranges": null,
        "comment_disabled": false,
        "schedule_infos": null,
        "keywords": null,
        "ios14_targeting": "UNSET",
        "category_id": "0",
        "deep_cpa_bid": 0,
        "app_type": "APP_IOS",
        "rf_estimated_frequency": null,
        "campaign_id": "{{campaign_id}}",
        "rf_estimated_cpr": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "brand_safety_partner": null,
        "bid_type": "BID_TYPE_NO_BID",
        "modify_time": "{{modify_time}}",
        "package": "{{package}}",
        "isp_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "languages": [],
        "next_day_retention": null,
        "promotion_type": "WEBSITE",
        "interest_keyword_ids": [],
        "conversion_window": null,
        "frequency_schedule": null,
        "excluded_audience_ids": [],
        "is_smart_performance_campaign": false,
        "auto_targeting_enabled": false,
        "rf_purchased_type": null,
        "operating_systems": [
            "IOS"
        ],
        "scheduled_budget": 0,
        "share_disabled": false,
        "click_attribution_window": "SEVEN_DAYS"
    }
}
(/code)
```
### 3. Create an ad

Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.


```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Destination | <ul><li>If App promotion type is App install:<ul><li>When App Profile Page is disabled at the campaign level, the destination is App Store with specific custom product page.</li><li>When App Profile Page is enabled at the campaign level, the destination is App Profile Page with specific custom product page. </li></ul></li><li>If App promotion type is App retargeting, the destination is App Store with specific custom product page.</li><li>If App promotion type is App pre-registration, the destination is TikTok Instant Page that contains a Custom Product Page URL.</li></ul> | `page_id`<br>`cpp_url` |  <ul><li>If `app_promotion_type` is `APP_INSTALL`: <ul><li>When `campaign_app_profile_page_state` at the campaign level is set to `OFF` or not passed: <ul><li>Do not pass `page_id`.</li><li>Specify a valid value for `cpp_url`.</li></ul></li><li>When `campaign_app_profile_page_state` at the campaign level is set to `ON`:<ul><li>Set `page_id` to the ID of an App Profile Page.<ul><li>Ensure that the App in the App Profile Page is the same as the promoted App. Otherwise, an error will occur. To obtain the IDs of App Profile Pages within your ad account, call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `APP_PROFILE_PAGE`. You can obtain the App specified in an App Profile Page via `app_id` returned from `/page/get/`.</li><li>Currently, you can only create App Profile Pages in [TikTok Ads Manager](https://ads.tiktok.com/i18n/home).</li></ul></li><li> Specify a valid value for `cpp_url`. </li></ul></li></ul></li><li> If `app_promotion_type` is `APP_RETARGETING`:<ul><li>Do not pass `page_id`.</li><li>Specify a valid value for `cpp_url`.</li></ul></li><li>If `app_promotion_type` is `APP_PREREGISTRATION`:<ul><li>Set `page_id` to the ID of a TikTok Instant Page that contains a Custom Product Page URL.<ul><li>To obtain the ID of TikTok Instant Pages within your ad account, call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) and set `business_type` to `TIKTOK_INSTANT_PAGE`. For TikTok Instant Pages that contain a Custom Product Page URL, the `has_cpp` field returned from `/page/get/` will be `true`.</li><li>Currently, you can only create TikTok Instant Pages that contain a Custom Product Page URL in [TikTok Ads Manager](https://ads.tiktok.com/i18n/home).</li></ul></li></ul></li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The Custom Product Page URL (`cpp_url`) must be consistent with the promoted app (`app_id`).</li><li>To learn about how to create Custom Product Pages in the Apple Store and obtain the corresponding `CPP` URLs, see [Custom product pages on the App Store](https://developer.apple.com/app-store/custom-product-pages/).</li></ul> |
```


#### Example
**Create an App Install ad without App Profile Page**

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
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "LEARN_MORE",
        "cpp_url":"{{cpp_url}}"
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
                "impression_tracking_url": null,
                "page_id": null,
                "create_time": "{{create_time}}",
                "ad_text": "{{ad_text}}",
                "cpp_url": "{{cpp_url}}",
                "campaign_id": "{{campaign_id}}",
                "video_id": "{{video_id}}",
                "modify_time": "{{modify_time}}",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "ad_id": "{{ad_id}}",
                "deeplink_type": "NORMAL",
                "optimization_event": "ACTIVE",
                "is_new_structure": true,
                "click_tracking_url": null,
                "call_to_action": "LEARN_MORE",
                "image_ids": [
                    "{{image_id}}"
                ],
                "card_id": null,
                "deeplink": "",
                "tracking_app_id": "{{tracking_app_id}}",
                "creative_type": null,
                "vast_moat_enabled": false,
                "brand_safety_vast_url": null,
                "viewability_vast_url": null,
                "display_name": "",
                "adgroup_name": "{{adgroup_name}}",
                "ad_texts": null,
                "landing_page_url": "",
                "app_name": "{{app_name}}",
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "playable_url": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "carousel_image_labels": null,
                "tracking_pixel_id": 0,
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "campaign_name": "{{campaign_name}}",
                "music_id": null,
                "creative_authorized": false,
                "profile_image_url": "{{profile_image_url}}",
                "identity_id": "{{identity_id}}",
                "is_aco": false
            }
        ]
    }
}
(/code)
```

**Create an App Install ad with App Profile Page**

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
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "LEARN_MORE",
        "page_id": {{page_id}},
        "cpp_url":"{{cpp_url}}"
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
                "impression_tracking_url": null,
                "page_id": {{page_id}},
                "create_time": "{{create_time}}",
                "ad_text": "{{ad_text}}",
                "cpp_url": "{{cpp_url}}",
                "campaign_id": "{{campaign_id}}",
                "video_id": "{{video_id}}",
                "modify_time": "{{modify_time}}",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "ad_id": "{{ad_id}}",
                "deeplink_type": "NORMAL",
                "optimization_event": "ACTIVE",
                "is_new_structure": true,
                "click_tracking_url": null,
                "call_to_action": "LEARN_MORE",
                "image_ids": [
                    "{{image_id}}"
                ],
                "card_id": null,
                "deeplink": "",
                "tracking_app_id": "{{tracking_app_id}}",
                "creative_type": "APP_PROMOTION",
                "vast_moat_enabled": false,
                "brand_safety_vast_url": null,
                "viewability_vast_url": null,
                "display_name": "",
                "adgroup_name": "{{adgroup_name}}",
                "ad_texts": null,
                "landing_page_url": "",
                "app_name": "{{app_name}}",
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "playable_url": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "carousel_image_labels": null,
                "tracking_pixel_id": 0,
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "campaign_name": "{{campaign_name}}",
                "music_id": null,
                "creative_authorized": false,
                "profile_image_url": "{{profile_image_url}}",
                "identity_id": "{{identity_id}}",
                "is_aco": false
            }
        ]
    }
}
(/code)
```

**Create an App Retargeting ad**

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
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "LEARN_MORE",
        "cpp_url":"{{cpp_url}}"
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
                "impression_tracking_url": null,
                "page_id": null,
                "create_time": "{{create_time}}",
                "ad_text": "{{ad_text}}",
                "cpp_url": "{{cpp_url}}",
                "campaign_id": "{{campaign_id}}",
                "video_id": "{{video_id}}",
                "modify_time": "{{modify_time}}",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "ad_id": "{{ad_id}}",
                "deeplink_type": "NORMAL",
                "optimization_event": "LAUNCH_APP",
                "is_new_structure": true,
                "click_tracking_url": "{{click_tracking_url}}",
                "call_to_action": "LEARN_MORE",
                "image_ids": [
                    "{{image_id}}"
                ],
                "card_id": null,
                "deeplink": "",
                "tracking_app_id": "{{tracking_app_id}}",
                "creative_type": null,
                "vast_moat_enabled": false,
                "brand_safety_vast_url": null,
                "viewability_vast_url": null,
                "display_name": "",
                "adgroup_name": "{{adgroup_name}}",
                "ad_texts": null,
                "landing_page_url": "",
                "app_name": "{{app_name}}",
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "playable_url": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "carousel_image_labels": null,
                "tracking_pixel_id": 0,
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "campaign_name": "{{campaign_name}}",
                "music_id": null,
                "creative_authorized": false,
                "profile_image_url": "{{profile_image_url}}",
                "identity_id": "{{identity_id}}",
                "is_aco": false
            }
        ]
    }
}
(/code)
```

**Create an App Pre-Registration ad**

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
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "LEARN_MORE",
        "page_id": {{page_id}}
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
                "click_tracking_url": "{{click_tracking_url}}",
                "carousel_image_labels": null,
                "advertiser_id": "{{advertiser_id}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "campaign_name": "{{campaign_name}}",
                "card_id": null,
                "creative_authorized": false,
                "viewability_vast_url": null,
                "page_id": {{page_id}},
                "landing_page_urls": null,
                "is_new_structure": true,
                "optimization_event": "BUTTON",
                "display_name": "{{display_name}}",
                "operation_status": "ENABLE",
                "ad_text": "{{ad_text}}",
                "adgroup_id": "{{adgroup_id}}",
                "vast_moat_enabled": false,
                "call_to_action": "LEARN_MORE",
                "impression_tracking_url": null,
                "landing_page_url": "",
                "identity_type": "CUSTOMIZED_USER",
                "viewability_postbid_partner": "UNSET",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "ad_id": "{{ad_id}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "profile_image_url": "{{profile_image_url}}",
                "is_aco": false,
                "ad_texts": null,
                "create_time": "{{create_time}}",
                "call_to_action_id": null,
                "tracking_pixel_id": 0,
                "ad_format": "SINGLE_VIDEO",
                "playable_url": "",
                "campaign_id": "{{campaign_id}}",
                "brand_safety_vast_url": null,
                "creative_type": "CUSTOM_INSTANT_PAGE",
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "deeplink": null,
                "identity_id": "{{identity_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "app_name": "",
                "tracking_app_id": "{{tracking_app_id}}",
                "video_id": "{{video_id}}",
                "music_id": null
            }
        ]
    }
}
(/code)
```

