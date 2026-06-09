---
title: "Create a Lead Generation ad with optimization location as phone call"
doc_id: 1774482936048641
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as phone call"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Lead Generation ad with optimization location as phone call

This article introduces how to create a Lead Generation ad with optimization location as phone call. This enables the ad audience to directly reach out to you (the advertiser) via phone by clicking the call-to-action button within the ad, or be directed to view a Custom TikTok Instant Page. 

> <span style="color:DodgerBlue">**Note**</span><br>
> Creating a Lead Generation ad with optimization location as phone call is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Advertising objective | Lead Generation | `objective_type` | `LEAD_GENERATION` |
| Smart+ Campaign| Disabled <br><br>If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) to check. | / | / |
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
|  Optimization location<br> (Promotion type) | Phone call | `promotion_type`<br> `promotion_target_type`| <ul><li> Set `promotion_type` to `LEAD_GEN_CLICK_TO_CALL`,</li><li> AND do not pass `promotion_target_type`.</li></ul>|
| Placement | <strong>Select Placement</strong> with TikTok placement| `placement_type`<br> `placements` | <ul><li> Set `placement_type` to `PLACEMENT_TYPE_NORMAL`</li><li> AND set `placements` to ` ["PLACEMENT_TIKTOK"]`.</li></ul>|
| Age targeting | Audiences younger than 18 years old can't be targeted. | `age_groups` | Do not pass the value `AGE_13_17`. |
| Demographics - Location targeting | The available targeting regions are as follows: <ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul>Note that advertisers registered with the Chinese mainland (`CN`) cannot target locations in Australia and Indonesia. | `location_ids` | Pass the IDs of locations within the following regions: <ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul>Note that advertisers registered with the Chinese mainland (`CN`) cannot target locations in Australia and Indonesia.<br><br>To obtain the available targeting locations, use [/tool/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1761236883355649) or [/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713). |
| Pangle audience packages  | Not supported| `included_pangle_audience_package_ids` <br>`excluded_pangle_audience_package_ids`| Not supported |
| Optimization goal | Click | `optimization_goal` | `CLICK` |
| Billing event | CPC | `billing_event` | `CPC` |
| Search ads| Not supported| `search_result_enabled`| Can only set it to `false`, or do not pass this field.|
| Automated Creative Optimization (ACO) | Not supported| `creative_material_mode` |`CUSTOM` or not passed|
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
    "promotion_type": "LEAD_GEN_CLICK_TO_CALL",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "video_download_disabled": false,
    "location_ids": ["{{location_id}}"],
    "gender": "GENDER_UNLIMITED",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget":{{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_end_time}}",
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
        "conversion_window": null,
        "frequency": null,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "optimization_event": null,
        "operation_status": "ENABLE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "app_id": null,
        "pixel_id": null,
        "comment_disabled": false,
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "create_time": "{{create_time}}",
        "campaign_name": "{{campaign_name}}",
        "ios14_quota_type": "UNOCCUPIED",
        "device_price_ranges": null,
        "advertiser_id": "{{advertiser_id}}",
        "video_download_disabled": false,
        "scheduled_budget": 0,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "auto_targeting_enabled": false,
        "conversion_bid_price": 0,
        "rf_purchased_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "next_day_retention": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "billing_event": "CPC",
        "isp_ids": [],
        "gender": "GENDER_UNLIMITED",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_infos": null,
        "actions": [],
        "modify_time": "{{modify_time}}",
        "interest_keyword_ids": [],
        "budget": {{budget}},
        "operating_systems": [],
        "is_new_structure": true,
        "adgroup_app_profile_page_state": null,
        "category_exclusion_ids": [],
        "purchased_reach": null,
        "app_download_url": null,
        "creative_material_mode": "CUSTOM",
        "delivery_mode": null,
        "frequency_schedule": null,
        "is_hfss": false,
        "schedule_type": "SCHEDULE_START_END",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "bid_type": "BID_TYPE_NO_BID",
        "secondary_optimization_event": null,
        "rf_estimated_cpr": null,
        "deep_cpa_bid": 0,
        "category_id": "0",
        "feed_type": null,
        "deep_bid_type": null,
        "search_result_enabled": false,
        "audience_ids": [],
        "brand_safety_partner": null,
        "optimization_goal": "CLICK",
        "adgroup_name": "{{adgroup_name}}",
        "share_disabled": false,
        "excluded_audience_ids": [],
        "network_types": [],
        "inventory_filter_enabled": false,
        "app_type": null,
        "bid_price": 0,
        "languages": [],
        "keywords": null,
        "statistic_type": null,
        "promotion_type": "LEAD_GEN_CLICK_TO_CALL",
        "location_ids": [
            "{{location_id}}"
        ],
        "interest_category_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "purchased_impression": null,
        "rf_estimated_frequency": null,
        "skip_learning_phase": false,
        "campaign_id": "{{campaign_id}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111"
    }
}
(/code)
```

## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {20%} | Parameter {22%} | How to configure the parameter {38%} |
|---|---|---|---|
| Ad format | Any of the following types: <ul><li>Single video</li><li> Carousel images</li></ul> | `ad_format` | Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`CAROUSEL_ADS`<ul><li> To learn about how to create Standard Carousel Ads, see [Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).</li></ul></li></ul>|
| Standard call-to-action | Specified.<br>The recommended call-to-action is “Call now”. | `call_to_action` | Pass a valid value.<br>The recommended value is `CALL_NOW`. |
| Dynamic call-to-action | Not supported | `call_to_action_id` | Not supported |
| Interactive add-on | Not supported | `card_id` | Not supported |
| Destination | Any of the following options: <ul><li> Phone number</li><li> TikTok Instant Page </li></ul>| `phone_region_code`<br>`phone_region_calling_code` <br>`phone_number` <br> `page_id`  |<ul><li> To specify Destination as phone number, you need to pass in `phone_region_code`, `phone_region_calling_code`, and `phone_number`. <br><p><span style="color:darkred"><b>Note</b></span>: To obtain the region code (`phone_region_code`) and region calling code (`phone_region_calling_code`) for the region of a specific phone number, use [/tool/phone_region_code/](https://business-api.tiktok.com/portal/docs?id=1774488637039618).</li><li>To specify Destination as TikTok Instant Page, set `page_id` to the ID of a Custom TikTok Instant Page. </li></ul>|
| Smart Creative | Not supported | `is_smart_creative`<br>(in [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626)) | Not supported |
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
        "call_to_action": "CALL_NOW",
        "phone_region_code":"US",
        "phone_region_calling_code":"+1",
        "phone_number":"{{phone_number}}"
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
                "deeplink_type": "NORMAL",
                "card_id": null,
                "ad_name": "{{ad_name}}",
                "create_time": "{{create_time}}",
                "phone_number": "{{phone_number}}",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_id": "{{ad_id}}",
                "landing_page_urls": null,
                "phone_region_calling_code": "+1",
                "music_id": null,
                "viewability_postbid_partner": "UNSET",
                "playable_url": "",
                "call_to_action_id": null,
                "is_new_structure": true,
                "brand_safety_postbid_partner": "UNSET",
                "adgroup_name": "{{adgroup_name}}",
                "viewability_vast_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "modify_time": "{{modify_time}}",
                "page_id": null,
                "profile_image_url": "{{profile_image_url}}",
                "video_id": "{{video_id}}",
                "tracking_pixel_id": 0,
                "advertiser_id": "{{advertiser_id}}",
                "ad_format": "SINGLE_VIDEO",
                "identity_id": "{{identity_id}}",
                "impression_tracking_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "brand_safety_vast_url": null,
                "ad_texts": null,
                "is_aco": false,
                "optimization_event": "PHONE_CONFIRM",
                "app_name": "",
                "image_ids": [
                    "{{image_id}}"
                ],
                "landing_page_url": "",
                "creative_authorized": false,
                "deeplink": "",
                "creative_type": null,
                "click_tracking_url": null,
                "campaign_name": "{{campaign_name}}",
                "call_to_action": "CALL_NOW",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "campaign_id": "{{campaign_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "operation_status": "ENABLE",
                "ad_text": "{{ad_text}}",
                "phone_region_code": "US"
            }
        ]
    }
}
(/code)
```
