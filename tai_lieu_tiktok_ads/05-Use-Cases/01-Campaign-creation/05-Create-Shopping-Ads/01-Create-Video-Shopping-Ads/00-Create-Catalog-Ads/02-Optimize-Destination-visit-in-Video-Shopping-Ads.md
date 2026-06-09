---
title: "Optimize Destination visit in Video Shopping Ads"
doc_id: 1782087855154177
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / Create Catalog Ads / Optimize Destination visit in Video Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Optimize Destination visit in Video Shopping Ads

This article walks you through the steps to create Video Shopping Ads with optimization goal as Destination Visit, sending users to your in-app page or fallback to the website.

# Prerequisite
- The optimization goal Destination Visit is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


# Steps
## 1. Create a campaign
Create a campaign by using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-1. Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-1.%20Create%20a%20campaign), the following requirements must be met at the same time.


<table>
<thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th style="width:30%">Requirement</th>
    <th style="width:25%">Parameter</th>
    <th style="width:25%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>If you are not sure about whether the campaign is Smart+ Campaign, you can use the <code>is_smart_performance_campaign</code> field returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1739315828649986"  target="_blank">/campaign/get/</a> to check.</td>
    <td>/<br></td>
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
    <td>Not supported </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not supported</td>
  </tr>
</tbody>
</table>


### Example

Request

```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-token: {{Access-token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "PRODUCT_SALES",
    "campaign_product_source": "CATALOG",
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
        "modify_time": "{{modify_time}}",
        "is_new_structure": true,
        "operation_status": "ENABLE",
        "create_time": "{{create_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "objective": "LANDING_PAGE",
        "campaign_app_profile_page_state": "UNSET",
        "campaign_type": "REGULAR_CAMPAIGN",
        "deep_bid_type": null,
        "objective_type": "PRODUCT_SALES",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "roas_bid": 0.0,
        "is_search_campaign": false,
        "campaign_product_source": "CATALOG",
        "is_smart_performance_campaign": false,
        "budget": {{budget}},
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "budget_mode": "BUDGET_MODE_TOTAL"
    }
}
(/code)
```

## 2. Create and manage catalogs

Follow the steps outlined in the section [Create Video Shopping Ads with products from catalogs-2. Create and manage catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-2.%20Create%20and%20manage%20catalogs).

## 3. Create an ad group
Create an ad group by using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-3. Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-3.%20Create%20an%20ad%20group), the following requirements must be met at the same time.

``` xtable
| Setting {19%} | Requirement {22%} | Parameter {30%} | How to configure the parameter {28%} |
|---|---|---|
| Optimization location<br>(Promotion type)   | Android App or iOS App | `promotion_type` | `APP_ANDROID` or `APP_IOS` |
| Optimization event | Unset. <br> The default setting is Destination Visit. | `optimization_event` | `DESTINATION_VISIT` or not passed (recommended) |
| Placements | <strong>Select Placement</strong> with only TikTok placement |  <ul><li>`placement_type`</li><li> `placements` </li></ul>| Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to ` ["PLACEMENT_TIKTOK"]` |
| Retarget audience | Disabled |<ul><li>`shopping_ads_retargeting_type`</li><li>`shopping_ads_retargeting_actions_days`</li><li> `included_custom_actions`</li><li> `excluded_custom_actions`</li></ul>  | <ul><li> Set `shopping_ads_retargeting_type` to `OFF`.</li><li> Do not pass `shopping_ads_retargeting_actions_days`, `included_custom_actions`, and `excluded_custom_actions`.</li></ul> |
| Optimization goal | Destination Visit | `optimization_goal` | `DESTINATION_VISIT` |
| Attribution settings | <ul><li> Attribution window: Unset. <br>  The default setting is 7-day click & 1-day view </li><li> Event count: Unset.<br> The default setting is Once.</li></ul> | <ul><li>`click_attribution_window`</li><li>`view_attribution_window`</li><li>`attribution_event_count` </li></ul>| Not passed. <br> <br> <ul><li>`click_attribution_window` will default to `SEVEN_DAYS`. </li><li>`view_attribution_window` will default to `ONE_DAY`.</li><li>`attribution_event_count` will default to `ONCE`.</li></ul> |
| Billing event | oCPM | `billing_event` | `OCPM` |
| Automated Creative Optimization (ACO) <br>& Smart Creative | Disabled | `creative_material_mode` | Not passed |
| Split test | Disabled <br> <br>Traffic ad groups with optimization goal as Destination Visit cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests. | / | / |
```

### Example
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
        "shopping_ads_type": "VIDEO",
        "product_source": "CATALOG",
        "catalog_id": "{{catalog_id}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "promotion_type": "APP_ANDROID",
        "app_id": "{{app_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": ["PLACEMENT_TIKTOK"],
        "shopping_ads_retargeting_type": "OFF",
        "location_ids": ["{{location_id}}", "{{location_id}}"],
        "gender": "GENDER_UNLIMITED",
        "operating_systems": ["ANDROID"],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "budget": {{budget}},
        "schedule_type": "SCHEDULE_START_END",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "optimization_goal": "DESTINATION_VISIT",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "pacing": "PACING_MODE_SMOOTH",
        "operation_status": "ENABLE"
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
        "attribution_event_count": "ONCE",
        "shopping_ads_type": "VIDEO",
        "creative_material_mode": "CATALOG_SALES",
        "statistic_type": null,
        "adgroup_app_profile_page_state": null,
        "auto_targeting_enabled": false,
        "excluded_audience_ids": [],
        "rf_estimated_cpr": null,
        "next_day_retention": null,
        "rf_purchased_type": null,
        "scheduled_budget": 0.0,
        "frequency": null,
        "click_attribution_window": "SEVEN_DAYS",
        "gender": "GENDER_UNLIMITED",
        "deep_bid_type": null,
        "isp_ids": [],
        "deep_cpa_bid": 0.0,
        "interest_keyword_ids": [],
        "shopping_ads_retargeting_type": "OFF",
        "schedule_start_time": "{{schedule_start_time}}",
        "package": "{{package}}",
        "app_id": "{{app_id}}",
        "inventory_filter_enabled": false,
        "location_ids": [
            "{{location_id}}",
            "{{location_id}}"
        ],
        "budget": {{budget}},
        "billing_event": "OCPM",
        "optimization_goal": "DESTINATION_VISIT",
        "ios14_quota_type": "UNOCCUPIED",
        "keywords": null,
        "campaign_id": "{{campaign_id}}",
        "search_result_enabled": false,
        "campaign_name": "{{campaign_name}}",
        "app_type": "APP_ANDROID",
        "purchased_impression": null,
        "secondary_optimization_event": null,
        "purchased_reach": null,
        "languages": [],
        "comment_disabled": false,
        "is_hfss": false,
        "frequency_schedule": null,
        "schedule_infos": null,
        "actions": [],
        "adgroup_id": "{{adgroup_id}}",
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "device_price_ranges": null,
        "age_groups": null,
        "operating_systems": [
            "ANDROID"
        ],
        "category_id": "0",
        "conversion_bid_price": 0.0,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "network_types": [],
        "conversion_window": null,
        "is_new_structure": true,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "catalog_id": "{{catalog_id}}",
        "bid_price": 0.0,
        "brand_safety_partner": null,
        "view_attribution_window": "ONE_DAY",
        "advertiser_id": "{{advertiser_id}}",
        "schedule_type": "SCHEDULE_START_END",
        "skip_learning_phase": true,
        "bid_type": "BID_TYPE_NO_BID",
        "interest_category_ids": [],
        "create_time": "{{create_time}}",
        "product_source": "CATALOG",
        "pixel_id": null,
        "adgroup_name": "{{adgroup_name}}",
        "operation_status": "ENABLE",
        "category_exclusion_ids": [],
        "delivery_mode": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "app_download_url": "{{app_download_url}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "optimization_event": "DESTINATION_VISIT",
        "audience_ids": [],
        "feed_type": null,
        "promotion_type": "APP_ANDROID",
        "is_smart_performance_campaign": false,
        "rf_estimated_frequency": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "modify_time": "{{modify_time}}",
        "video_download_disabled": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false
    }
}
(/code)
```

## 4. Create an ad
Create an ad by using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-4. Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-4.%20Create%20an%20ad), the following requirements must be met.

```xtable
| Setting {20%} | Requirement {20%} | Parameter {23%} | How to configure the parameter {37%} |
|---|---|---|---|
| Destination - Deeplink | Any of the following options: <ul><li> Product deeplink</li><li> Custom deeplink </li></ul>| <ul><li>`deeplink_format_type`</li><li>`deeplink`</li><li>`shopping_ads_deeplink_type` </li><li> `fallback_type` </li><li> `shopping_ads_fallback_type` </li><li>`landing_page_url`</li></ul>| <ul><li> Do not pass `deeplink_format_type`.</li><li> Set `shopping_ads_deeplink_type` to `SHOPPING_ADS` or `NORMAL`. <ul><li> If `shopping_ads_deeplink_type` is set to `NORMAL`, pass `deeplink`. <br><br><p><span style="color:darkred"><b>Note</b></span>: `shopping_ads_deeplink_type` cannot be set to `SHOPPING_ADS` in any of the following scenarios: <ul><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO`. </li><li>`ad_format` is set to `CATALOG_CAROUSEL`.</li></ul></li></ul></li><li> Set `fallback_type` to `UNSET`.</li><li> To specify the fallback type, pass `shopping_ads_fallback_type`.<ul><li> If you want to specify fallback type as App store, set `shopping_ads_fallback_type` to `DEFAULT`, and do not pass `landing_page_url`.</li><li> If you want to specify fallback type as Product link, set `shopping_ads_fallback_type` to `SHOPPING_ADS`, and do not pass `landing_page_url`. <br><br><p><span style="color:darkred"><b>Note</b></span>: `shopping_ads_fallback_type` cannot be set to `SHOPPING_ADS` in any of the following scenarios: <ul><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO`. </li><li>`ad_format` is set to `CATALOG_CAROUSEL`. </li></ul></li><li> If you want to specify fallback type as Custom link, set `shopping_ads_fallback_type` to `CUSTOM`, and pass `landing_page_url`. </li></ul></li></ul>|
| Destination - Deeplink type | Non-deferred deeplinks | `deeplink_type` | `NORMAL` or not passed |
| TikTok Instant Page | Disabled | `page_id` | Not passed |
```
### Example

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "dynamic_format": "UNSET",
            "video_id": "{{video_id}}",
            "image_ids": [
                "{{image_id}}"
            ],
            "ad_text": "{{ad_text}}",
            "call_to_action": "SHOP_NOW",
            "landing_page_url": "{{landing_page_url}}",
            "deeplink": "{{deeplink}}",
            "deeplink_type": "NORMAL",
            "shopping_ads_deeplink_type": "CUSTOM",
            "shopping_ads_fallback_type": "CUSTOM",
            "dynamic_destination": "UNSET",
            "instant_product_page_used": false
        }
    ]
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
                "ad_name": "{{ad_name}}",
                "card_id": null,
                "call_to_action_id": null,
                "profile_image_url": "{{profile_image_url}}",
                "fallback_type": "UNSET",
                "creative_authorized": false,
                "vast_moat_enabled": false,
                "image_ids": [
                    "{{image_id}}"
                ],
                "is_aco": false,
                "campaign_id": "{{campaign_id}}",
                "video_id": "{{video_id}}",
                "click_tracking_url": null,
                "display_name": "",
                "campaign_name": "{{campaign_name}}",
                "app_name": "{{app_name}}",
                "dynamic_destination": "UNSET",
                "ad_texts": null,
                "tracking_pixel_id": 0,
                "call_to_action": "SHOP_NOW",
                "product_specific_type": "ALL",
                "shopping_ads_fallback_type": "CUSTOM",
                "ad_id": "{{ad_id}}",
                "shopping_ads_deeplink_type": "CUSTOM",
                "tracking_app_id": "{{tracking_app_id}}",
                "is_new_structure": true,
                "creative_type": null,
                "catalog_id": "{{catalog_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "playable_url": "",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "impression_tracking_url": null,
                "advertiser_id": "{{advertiser_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "landing_page_urls": null,
                "dynamic_format": "UNSET",
                "create_time": "{{create_time}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "identity_id": "{{identity_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "viewability_vast_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "viewability_postbid_partner": "UNSET",
                "shopping_ads_video_package_id": "",
                "music_id": null,
                "brand_safety_vast_url": null,
                "optimization_event": "DESTINATION_VISIT",
                "deeplink": "{{deeplink}}",
                "deeplink_type": "NORMAL",
                "page_id": null,
                "brand_safety_postbid_partner": "UNSET",
                "landing_page_url": "{{landing_page_url}}",
                "modify_time": "{{modify_time}}",
                "ad_text": "{{ad_text}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}"
            }
        ]
    }
}
(/code)
```
