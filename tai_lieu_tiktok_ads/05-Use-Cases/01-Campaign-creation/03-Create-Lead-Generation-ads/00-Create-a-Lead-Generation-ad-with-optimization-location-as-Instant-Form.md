---
title: "Create a Lead Generation ad with optimization location as Instant Form"
doc_id: 1774482920012801
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as Instant Form"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Lead Generation ad with optimization location as Instant Form

This article introduces how to create a Lead Generation ad with optimization location as Instant Form, driving lead-related actions on the Instant Form that loads natively within TikTok.

# Prerequisite
1. Create an Instant Form.

To create an Instant Form, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881) and set `businessType` to `1`.

2. Call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) to get the page ID via `page_id`.

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Advertising objective | Lead Generation | `objective_type` | `LEAD_GENERATION` |
| Smart+ Campaign | Disabled <br><br>To learn about how to create a Smart+ Lead Generation Campaign with optimization location as Instant Form, see [Create Smart+ Lead Generation Campaigns](https://business-api.tiktok.com/portal/docs?id=1827645480080385#item-link-Create%20a%20Smart+%20Lead%20Generation%20Campaign%20with%20optimization%20location%20as%20Instant%20Form). | / | / |
```
### Example

Request

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
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
        "campaign_name": "{{campaign_name}}",
        "modify_time": "{{modify_time}}",
        "objective": "LANDING_PAGE",
        "roas_bid": 0,
        "budget": {{budget}},
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": false,
        "create_time": "{{create_time}}",
        "deep_bid_type": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "objective_type": "LEAD_GENERATION",
        "advertiser_id": "{{advertiser_id}}",
        "operation_status": "ENABLE"
    }
}
(/code)
```

## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

``` xtable
| Setting {20%} | Requirement {22%} | Parameter {28%} | How to configure the parameter {30%} |
|---|---|---|
|  Optimization location<br>(Promotion type) | Instant Form | `promotion_type`<br> `promotion_target_type`| <ul><li> Set `promotion_type` to `LEAD_GENERATION`</li><li> AND set `promotion_target_type` to `INSTANT_PAGE` or do not pass this field </li></ul>|
| Deep funnel optimization<br>(Optional) |Enabled or disabled<br> <br> With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.| `deep_funnel_optimization_status` <br>`deep_funnel_event_source`  <br> `deep_funnel_event_source_id` <br> `deep_funnel_optimization_event`  | <ul><li>To enable deep funnel optimization, set `deep_funnel_optimization_status` to `ON` and pass `deep_funnel_event_source`, `deep_funnel_event_source_id`, and `deep_funnel_optimization_event`.</li><li>To disable deep funnel optimization, set `deep_funnel_optimization_status` to `OFF` or leave `deep_funnel_optimization_status` unspecified. Do not pass `deep_funnel_event_source`,`deep_funnel_event_source_id`, and `deep_funnel_optimization_event`.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Deep funnel optimization with Pixel or Offline events is generally available. </li></ul> |
| Placement |Any of the following options:<ul><li> <strong>Automatic Placement</strong> </li><li> <strong>Select Placement</strong> with TikTok placement, or Pangle placement, or TikTok and Pangle placement </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Pangle placement for Lead Generation ads with optimization location as Instant Form is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.| `placement_type`<br> `placements` | Any of the following configurations:<ul><li> Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`</li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to ` ["PLACEMENT_TIKTOK"]`, or `["PLACEMENT_PANGLE"]`, or ` ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]`.</li></ul>|
| Location targeting | Specify locations supported for the Lead Generation objective and placement setting | `location_ids` | Call [/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713) first. Set `objective_type` to `LEAD_GENERATION` and pass `placements`. Then select the location IDs you want to target from the supported location IDs returned through the `location_id` parameter. |
| Age targeting | Audiences younger than 18 years old can't be targeted. | `age_groups` | Do not pass the value `AGE_13_17`. |
| Pangle audience packages <br>(Optional) | Supported | `included_pangle_audience_package_ids` <br>`excluded_pangle_audience_package_ids`| Supported. <br><p><span style="color:darkred"><b>Note</b></span>: Using Pangle audience packages in this scenario is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Targeting expansion | Cannot expand to target audiences younger than 18 years ago | `expansion_enabled`<br>`expansion_types`<br>{-To be deprecated} | If you set `expansion_enabled` to `true` and include `AGE` in the values of `expansion_types`, the call won't fail but you still can't target audiences younger than 18 years. |
| Optimization goal |   Form submission  | `optimization_goal` | `LEAD_GENERATION` |
| Billing event | oCPM | `billing_event` | `OCPM` |
| Bid strategy & Delivery Type | <ul><li> If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li> If Bid Strategy is set to Maximum Delivery, you can only set Delivery Type to Standard.</li></ul>| `bid_type`<br> `pacing` |<ul><li> If you specify `bid_type` as `BID_TYPE_CUSTOM`, you can set `pacing` to `PACING_MODE_SMOOTH` or `PACING_MODE_FAST`. </li><li> If you specify `bid_type` as `BID_TYPE_NO_BID`, you can only set `pacing` to `PACING_MODE_SMOOTH`.</li></ul>|
```

### Example
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
    "promotion_type": "LEAD_GENERATION",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "video_download_disabled": false,
    "location_ids": ["{{location_id}}"],
    "operating_systems": ["ANDROID"],
    "gender": "GENDER_UNLIMITED",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "LEAD_GENERATION",
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
        "conversion_bid_price": 0,
        "actions": [],
        "promotion_type": "LEAD_GENERATION",
        "app_type": null,
        "pixel_id": null,
        "adgroup_app_profile_page_state": null,
        "adgroup_id": "{{adgroup_id}}",
        "feed_type": null,
        "auto_targeting_enabled": false,
        "frequency": null,
        "location_ids": [
            "{{location_id}}"
        ],
        "optimization_goal": "LEAD_GENERATION",
        "delivery_mode": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "campaign_id": "{{campaign_id}}",
        "rf_purchased_type": null,
        "creative_material_mode": "CUSTOM",
        "next_day_retention": null,
        "search_result_enabled": true,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "scheduled_budget": 0,
        "statistic_type": null,
        "ios14_quota_type": "UNOCCUPIED",
        "excluded_audience_ids": [],
        "campaign_name": "{{campaign_name}}",
        "pacing": "PACING_MODE_SMOOTH",
        "advertiser_id": "{{advertiser_id}}",
        "interest_keyword_ids": [],
        "purchased_impression": null,
        "device_price_ranges": null,
        "purchased_reach": null,
        "rf_estimated_cpr": null,
        "schedule_type": "SCHEDULE_START_END",
        "create_time": "{{create_time}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "schedule_infos": null,
        "brand_safety_partner": null,
        "comment_disabled": false,
        "category_exclusion_ids": [],
        "is_new_structure": true,
        "category_id": "0",
        "adgroup_name": "{{adgroup_name}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": null,
        "app_id": null,
        "languages": [],
        "optimization_event": "FORM",
        "operating_systems": [
            "ANDROID"
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "video_download_disabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "keywords": null,
        "deep_cpa_bid": 0,
        "is_hfss": false,
        "bid_display_mode": "CPMV",
        "frequency_schedule": null,
        "bid_price": 0,
        "conversion_window": null,
        "billing_event": "OCPM",
        "audience_ids": [],
        "budget": {{budget}},
        "deep_bid_type": null,
        "isp_ids": [],
        "interest_category_ids": [],
        "skip_learning_phase": true,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "modify_time": "{{modify_time}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "rf_estimated_frequency": null,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "inventory_filter_enabled": false,
        "bid_type": "BID_TYPE_NO_BID",
        "operation_status": "ENABLE",
        "gender": "GENDER_UNLIMITED",
        "secondary_optimization_event": null,
        "share_disabled": false,
        "network_types": []
    }
}
(/code)
```

## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Ad format | Any of the following types: <ul><li>Single video</li><li> Carousel images</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To set ad format to Carousel images, you need to specify the placement as <b>Automatic Placement</b> or <b>Select Placement</b> with TikTok placement included at the ad group level. | `ad_format` | Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`CAROUSEL_ADS`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To set `ad_format` to `CAROUSEL_ADS`, either specify `placement_type` as `PLACEMENT_TYPE_AUTOMATIC` or specify `placement_type` as `PLACEMENT_TYPE_NORMAL` and include `PLACEMENT_TIKTOK` in the value of `placements` at the ad group level first. <ul><li> To learn about how to create Standard Carousel Ads, see [Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).</li></ul>|
| Interactive add-on <br>(Optional for single video ads) | <ul><li>When ad format is single video and the placement includes TikTok, the only supported type is Display Card.</li><li>When ad format is Carousel images, interactive add-ons are not supported.</li></ul> | `card_id` | <ul><li>When `ad_format` is `SINGLE_VIDEO` and at the ad group level `placement_type` is `PLACEMENT_TYPE_AUTOMATIC` or `placement_type`is `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in the value of `placements`, you can specify the Display Card ID. <ul><li>To learn about how to get the Display Card ID, refer to [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058). </li></ul></li><li>When `ad_format` is `CAROUSEL_ADS`, `card_id` is not supported.</li></ul>|
| Destination | Instant Form | `page_id` | Specify the ID of an Instant Form. <br><br><ul><li>To obtain the ID of an Instant Form, set `business_type` to `LEAD_GEN` in [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586).</li><li>To create an Instant Form, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881) and set `businessType` to `1`.</li></ul> |
```

### Example

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
        "call_to_action": "{{call_to_action}}",
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
                "click_tracking_url": null,
                "playable_url": "",
                "display_name": "{{display_name}}",
                "identity_type": "CUSTOMIZED_USER",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "adgroup_id": "{{adgroup_id}}",
                "deeplink": null,
                "video_id": "{{video_id}}",
                "ad_name": "{{ad_name}}",
                "campaign_name": "{{campaign_name}}",
                "advertiser_id": "{{advertiser_id}}",
                "ad_id": "{{ad_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "call_to_action_id": null,
                "campaign_id": "{{campaign_id}}",
                "is_new_structure": true,
                "music_id": null,
                "viewability_postbid_partner": "UNSET",
                "app_name": "",
                "is_aco": false,
                "landing_page_urls": null,
                "ad_texts": null,
                "call_to_action": "{{call_to_action}}",
                "create_time": "{{create_time}}",
                "adgroup_name": "{{adgroup_name}}",
                "vast_moat_enabled": false,
                "image_ids": [
                    "{{image_id}}"
                ],
                "modify_time": "{{modify_time}}",
                "profile_image_url": "{{profile_image_url}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "brand_safety_vast_url": null,
                "impression_tracking_url": null,
                "optimization_event": "FORM",
                "ad_text": "{{ad_text}}",
                "tracking_pixel_id": 0,
                "landing_page_url": null,
                "card_id": null,
                "identity_id": "{{identity_id}}",
                "brand_safety_postbid_partner": "UNSET",
                "viewability_vast_url": null,
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "creative_type": "LEAD_ADS",
                "creative_authorized": false,
                "page_id": {{page_id}}
            }
        ]
    }
}
(/code)
```
