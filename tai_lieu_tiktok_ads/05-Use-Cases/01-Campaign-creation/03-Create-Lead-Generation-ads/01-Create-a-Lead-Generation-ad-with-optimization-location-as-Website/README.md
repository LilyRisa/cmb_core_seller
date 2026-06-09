---
title: "Create a Lead Generation ad with optimization location as Website"
doc_id: 1774482976097281
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as Website"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Lead Generation ad with optimization location as Website

This article introduces how to create a Lead Generation ad with optimization location as Website, driving lead-related actions on your website.


Based on your preference for using a catalog to promote car inventory or models, you have the flexibility to create three types of Lead Generation ads:
- Standard Lead Generation ads: This type of Lead Generation ad doesn't use a catalog to promote car inventory. For detailed instructions on creating such ads, see <a href="#item-link-Create a Standard Lead Generation ad with optimization location as Website">Create a Standard Lead Generation ad with optimization location as Website</a>.
- Automotive Ads for Inventory: This type of Lead Generation ad uses a catalog to promote car inventory. For detailed instructions on creating such ads, see [Create Automotive Ads for Inventory](https://business-api.tiktok.com/portal/docs?id=1827752507854850).
- Automotive Ads for Models: This type of Lead Generation ad uses a catalog to promote car models. For detailed instructions on creating such ads, see [Create Automotive Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994).

# Create a Standard Lead Generation ad with optimization location as Website

## Steps
### 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Advertising objective | Lead Generation | `objective_type` | `LEAD_GENERATION` |
| Smart+ Campaign | Disabled <br><br>To learn about how to create a Smart+ Lead Generation Campaign with optimization location as Website, see [Create Smart+ Lead Generation Campaigns](https://business-api.tiktok.com/portal/docs?id=1827645480080385#item-link-Create%20a%20Smart+%20Lead%20Generation%20Campaign%20with%20optimization%20location%20as%20Website). | / | / |
|Use catalog| Disabled | `catalog_enabled` | `false` or not passed|
```
#### Example

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

### 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

``` xtable
| Setting {20%} | Requirement {22%} | Parameter {26%} | How to configure the parameter {32%} |
|---|---|---|
| Optimization location<br>(Promotion type)  | Website | `promotion_type` <br>`promotion_target_type`<br> `pixel_id`<br>`optimization_event` | <ul><li> Set `promotion_type` to `LEAD_GENERATION`,</li><li> Set `promotion_target_type` to `EXTERNAL_WEBSITE`,</li><li>If `optimization_goal` is `CONVERT`, pass in `pixel_id` and `optimization_event` at the same time.</li><li>If `optimization_goal` is `CLICK`, do not pass in `pixel_id` and `optimization_event` at the same time.</li></ul> To obtain pixels with their associated optimization events, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978).|
| Deep funnel optimization<br>(Optional)  | Enabled or disabled<br> <br> With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.| `deep_funnel_optimization_status` <br>`deep_funnel_event_source`  <br> `deep_funnel_event_source_id` <br> `deep_funnel_optimization_event`  | <ul><li>To enable deep funnel optimization, set `deep_funnel_optimization_status` to `ON` and pass `deep_funnel_event_source`, `deep_funnel_event_source_id`, and `deep_funnel_optimization_event`.</li><li>To disable deep funnel optimization, set `deep_funnel_optimization_status` to `OFF` or leave `deep_funnel_optimization_status` unspecified. Do not pass `deep_funnel_event_source`,`deep_funnel_event_source_id`, and `deep_funnel_optimization_event`.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Deep funnel optimization with Pixel or Offline events is generally available. </li></ul>|
| Placement |Any of the following options:<ul><li> <strong>Automatic Placement</strong> </li><li> <strong>Select Placement</strong> with TikTok placement, or Pangle placement, or TikTok and Pangle placement </li></ul>| `placement_type`<br> `placements` | Any of the following configurations:<ul><li> Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`</li><li>Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to ` ["PLACEMENT_TIKTOK"]`, or `["PLACEMENT_PANGLE"]`, or ` ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]`.</li></ul>|
| Optimization goal | Any of the following options: <ul><li>Conversions</li><li>Click</li></ul> | `optimization_goal` | Any value below: <ul><li>`CONVERT` </li><li>`CLICK`</li></ul>|
| Billing event | <ul><li> If optimization goal is set to Conversions, set billing event to oCPM.</li><li> If optimization goal is set to Click, set billing event to CPC. </li></ul>| `billing_event` |<ul><li> If `optimization_goal` is set to `CLICK`, specify `billing_event` as `CPC`. </li><li> If `optimization_goal` is set to `CONVERT`, specify `billing_event` as `OCPM`.  </li></ul>|
| Bid strategy & Delivery Type | <ul><li> If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li> If Bid Strategy is set to Maximum Delivery, you can only set Delivery Type to Standard.</li></ul>| `bid_type`<br> `pacing` |<ul><li> If you specify `bid_type` as `BID_TYPE_CUSTOM`, you can set `pacing` to `PACING_MODE_SMOOTH` or `PACING_MODE_FAST`. </li><li> If you specify `bid_type` as `BID_TYPE_NO_BID`, you can only set `pacing` to `PACING_MODE_SMOOTH`.</li></ul>|
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
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type":"EXTERNAL_WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "video_download_disabled": false,
    "location_ids": ["{{location_id}}"],
    "gender": "GENDER_UNLIMITED",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
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
        "scheduled_budget": 0,
        "secondary_optimization_event": null,
        "frequency_schedule": null,
        "schedule_infos": null,
        "billing_event": "CPC",
        "app_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "modify_time": "{{modify_time}}",
        "actions": [],
        "advertiser_id": "{{advertiser_id}}",
        "delivery_mode": null,
        "gender": "GENDER_UNLIMITED",
        "optimization_goal": "CLICK",
        "keywords": null,
        "optimization_event": null,
        "pixel_id": null,
        "bid_type": "BID_TYPE_NO_BID",
        "rf_estimated_frequency": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "location_ids": [
            "{{location_id}}"
        ],
        "promotion_type": "LEAD_GENERATION",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "operating_systems": [],
        "purchased_impression": null,
        "share_disabled": false,
        "brand_safety_partner": null,
        "category_id": "0",
        "bid_price": 0,
        "operation_status": "ENABLE",
        "budget": {{budget}},
        "excluded_audience_ids": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_id": "{{campaign_id}}",
        "network_types": [],
        "app_id": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "conversion_window": null,
        "languages": [],
        "schedule_type": "SCHEDULE_START_END",
        "category_exclusion_ids": [],
        "ios14_quota_type": "UNOCCUPIED",
        "purchased_reach": null,
        "auto_targeting_enabled": false,
        "isp_ids": [],
        "conversion_bid_price": 0,
        "campaign_name": "{{campaign_name}}",
        "comment_disabled": false,
        "age_groups": null,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "search_result_enabled": true,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "interest_category_ids": [],
        "frequency": null,
        "adgroup_app_profile_page_state": null,
        "feed_type": null,
        "audience_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "deep_bid_type": null,
        "is_hfss": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "device_price_ranges": null,
        "video_download_disabled": false,
        "next_day_retention": null,
        "rf_purchased_type": null,
        "interest_keyword_ids": [],
        "deep_cpa_bid": 0,
        "inventory_filter_enabled": false,
        "create_time": "{{create_time}}",
        "adgroup_id": "{{adgroup_id}}",
        "statistic_type": null,
        "skip_learning_phase": false,
        "is_new_structure": true,
        "app_download_url": null,
        "creative_material_mode": "CUSTOM",
        "adgroup_name": "{{adgroup_name}}",
        "rf_estimated_cpr": null
    }
}
(/code)
```

### 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Ad format | Any of the following options: <ul><li> Single video </li><li> Single image </li><li>Carousel images</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To set ad format to single image, you need to specify the placement as <b>Select Placement</b> with Pangle only at the ad group level.</li><li>To set ad format to Carousel images, you need to specify the placement as <b>Automatic Placement</b> or <b>Select Placement</b> with TikTok placement included at the ad group level. </li></ul>| `ad_format` | Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`SINGLE_IMAGE`</li><li>`CAROUSEL_ADS`</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To set `ad_format` to `SINGLE_IMAGE`, specify `placement_type` as `PLACEMENT_TYPE_NORMAL` and `placements` as `&#91;"PLACEMENT_PANGLE"&#93;` at the ad group level first.<ul><li>To learn about how to create single image ads, see [Create single image ads](https://business-api.tiktok.com/portal/docs?id=1777633230937090).</li></ul></li><li>To set `ad_format` to `CAROUSEL_ADS`, either specify `placement_type` as `PLACEMENT_TYPE_AUTOMATIC` or specify `placement_type` as `PLACEMENT_TYPE_NORMAL` and include `PLACEMENT_TIKTOK` in the value of `placements` at the ad group level first. <ul><li> To learn about how to create Standard Carousel Ads, see [Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).</li></ul></li></ul>|
| Interactive add-on<br> (Optional for single video ads)  |<ul><li>When ad format is single video and the placement includes TikTok, the only supported types are Display Card, Gift Code Sticker, and Countdown Sticker.</li><li>When ad format is single image or Carousel images, interactive add-ons are not supported.</li></ul> | `card_id` | <ul><li>When `ad_format` is `SINGLE_VIDEO` and at the ad group level `placement_type` is `PLACEMENT_TYPE_AUTOMATIC` or `placement_type` is `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in the value of `placements`, you can specify the Display Card ID or ID of a Countdown Sticker or Gift Code Sticker portfolio. <ul><li>To learn about how to get the Display Card ID, see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058). </li><li>To find out how to create a Countdown Sticker or Gift Code Sticker portfolio, see [Stickers](https://business-api.tiktok.com/portal/docs?id=1749019667506177).</li></ul></li><li>When `ad_format` is `SINGLE_IMAGE` or `CAROUSEL_ADS`, `card_id` is not supported. </li></ul> |
| Destination | Any of the following options: <ul><li> URL </li><li> Custom Page </li></ul>| `landing_page_url`<br> `page_id` | <ul><li> To specify Destination as URL, specify the landing page URL of the website that has the Website Form via `landing_page_url`.</li><li> To specify Destination as Custom Page, specify the TikTok Instant Page that redirects to the website that has the Website Form via `page_id`.<ul><li>To create a TikTok Instant Page, you can use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881) and set `businessType` to `6`.</li><li>After you create a TikTok Instant Page, call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) to get the page ID.</li></ul></li></ul>|
```

#### Example

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
                "landing_page_urls": null,
                "ad_texts": null,
                "optimization_event": "FORM",
                "ad_format": "SINGLE_VIDEO",
                "page_id": null,
                "campaign_id": "{{campaign_id}}",
                "identity_id": "{{identity_id}}",
                "display_name": "{{display_name}}",
                "call_to_action_id": null,
                "brand_safety_postbid_partner": "UNSET",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_vast_url": null,
                "creative_type": null,
                "is_aco": false,
                "create_time": "{{create_time}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "app_name": "",
                "adgroup_name": "{{adgroup_name}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "deeplink": null,
                "operation_status": "ENABLE",
                "vast_moat_enabled": false,
                "advertiser_id": "{{advertiser_id}}",
                "ad_text": "{{ad_text}}",
                "ad_name": "{{ad_name}}",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "playable_url": "",
                "viewability_postbid_partner": "UNSET",
                "identity_type": "CUSTOMIZED_USER",
                "campaign_name": "{{campaign_name}}",
                "profile_image_url": "{{profile_image_url}}",
                "viewability_vast_url": null,
                "is_new_structure": true,
                "music_id": null,
                "impression_tracking_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "ad_id": "{{ad_id}}",
                "call_to_action": "{{call_to_action}}",
                "landing_page_url": "{{landing_page_url}}",
                "click_tracking_url": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "video_id": "{{video_id}}",
                "modify_time": "{{modify_time}}",
                "creative_authorized": false,
                "card_id": null
            }
        ]
    }
}
(/code)
```

# Create Automotive Ads for Inventory
To learn about the steps, see [Create Automotive Ads for Inventory](https://business-api.tiktok.com/portal/docs?id=1827752507854850).

# Create Automotive Ads for Models
To learn about the steps, see [Create Automotive Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994).

