---
title: "Create single image ads"
doc_id: 1777633230937090
path: "Use Cases / Campaign creation / Create single image ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create single image ads

This article walks you through the steps to create single image ads on Pangle or Global App Bundle placement.

## Introduction
Single image is an ad format that enables you to present your product, service, or brand using a single image. It provides a visually appealing way to capture the attention of your audience and encourage conversions.

**You can use Campaign Management API to create single image ads, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create single image ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- Creating single image Pangle ads by using an image of the resolution 640 x 100 pixels, 600 x 500 pixels, or 640 x 200 pixels is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## Steps

### Create single image ads on Pangle placement
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective | One of the following objectives: <ul><li> App promotion </li><li> Website conversions </li><li> Traffic </li><li>Lead generation </li><li>Product sales</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you select Lead generation, you need to set Optimization location as Website at the ad group level. To learn more about how to create single image Lead Generation ads, see [Create a Lead Generation ad with optimization location as Website](https://business-api.tiktok.com/portal/docs?id=1774482976097281).| `objective_type` | Any value below: <ul><li>`APP_PROMOTION`</li><li>`WEB_CONVERSIONS`</li><li>`TRAFFIC`</li><li>`LEAD_GENERATION`</li><li>`PRODUCT_SALES`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set this field to `LEAD_GENERATION`, you need to set `promotion_target_type` to `EXTERNAL_WEBSITE` at the ad group level. To learn more about how to create single image Lead Generation ads, see [Create a Lead Generation ad with optimization location as Website](https://business-api.tiktok.com/portal/docs?id=1774482976097281).|
```
> <span style="color:DodgerBlue">**Note**</span><br>
> The Smart+ Campaign setting should be disabled for the single image campaign. If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) to check.

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "TRAFFIC",
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
        "create_time": "{{create_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "budget": {{budget}},
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "modify_time": "{{modify_time}}",
        "campaign_name": "{{campaign_name}}",
        "operation_status": "ENABLE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "deep_bid_type": null,
        "objective_type": "TRAFFIC",
        "roas_bid": 0,
        "objective": "LANDING_PAGE"
    }
}
(/code)
```
2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th style="width:25%">Requirement</th>
    <th style="width:20%">Parameter</th>
    <th style="width:35%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">Placements</td>
    <td rowspan="2"><strong>Select Placement </strong>with Pangle placement<br></td>
    <td><code>placement_type</code></td>
    <td><code>PLACEMENT_TYPE_NORMAL</code></td>
  </tr>
  <tr>
    <td><code>placements</code></td>
    <td><code>["PLACEMENT_PANGLE"]</code></td>
  </tr>
  <tr>
    <td>Audience targeting - Location</td>
    <td>One or more locations that are available for Pangle placement</td>
    <td><code>location_ids</code></td>
    <td>Set <code>location_ids</code> to one or more locations available for Pangle placement.<br><br>To learn about the locations available for Pangle placement based on the place of registration for your ad account, refer to the Help Center article <a href="https://ads.tiktok.com/help/article/placements-available-locations?lang=en#anchor-1"  target="_blank">Placements and Available Locations</a>.<br><br>To obtain the location IDs that are available for your ad account under Pangle placement, use <a href="https://business-api.tiktok.com/portal/docs?id=1737189539571713"  target="_blank">/tool/region/</a> and set <code>placements</code> to <code>["PLACEMENT_PANGLE"]</code>.</td>
  </tr>
</tbody>
</table>

**Example**

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
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_PANGLE"
    ],
    "video_download_disabled": true,
    "location_ids": ["102358"],
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
        "rf_estimated_frequency": null,
        "promotion_type": "WEBSITE",
        "feed_type": null,
        "is_new_structure": true,
        "optimization_event": null,
        "conversion_bid_price": 0,
        "bid_display_mode": "CPMV",
        "placements": [
            "PLACEMENT_PANGLE"
        ],
        "rf_estimated_cpr": null,
        "ios14_quota_type": "UNOCCUPIED",
        "statistic_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "app_download_url": null,
        "creative_material_mode": "CUSTOM",
        "app_type": null,
        "campaign_id": "{{campaign_id}}",
        "bid_price": 0,
        "campaign_name": "{{campaign_name}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "adgroup_id": "{{adgroup_id}}",
        "category_id": "0",
        "purchased_impression": null,
        "actions": [],
        "schedule_type": "SCHEDULE_START_END",
        "interest_category_ids": [],
        "operation_status": "ENABLE",
        "frequency_schedule": null,
        "video_download_disabled": true,
        "bid_type": "BID_TYPE_NO_BID",
        "audience_ids": [],
        "adgroup_name": "{{adgroup_name}}",
        "schedule_infos": null,
        "skip_learning_phase": false,
        "operating_systems": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "app_id": null,
        "optimization_goal": "CLICK",
        "conversion_window": null,
        "advertiser_id": "{{advertiser_id}}",
        "network_types": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "brand_safety_partner": null,
        "delivery_mode": null,
        "scheduled_budget": 0,
        "comment_disabled": false,
        "search_result_enabled": false,
        "languages": [],
        "share_disabled": false,
        "isp_ids": [],
        "auto_targeting_enabled": false,
        "secondary_optimization_event": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "next_day_retention": null,
        "location_ids": [
            "102358"
        ],
        "pixel_id": null,
        "billing_event": "CPC",
        "rf_purchased_type": null,
        "frequency": null,
        "keywords": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "inventory_filter_enabled": false,
        "purchased_reach": null,
        "deep_bid_type": null,
        "interest_keyword_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "age_groups": null,
        "modify_time": "{{modify_time}}",
        "deep_cpa_bid": 0,
        "budget": {{}},
        "gender": "GENDER_UNLIMITED",
        "create_time": "{{create_time}}",
        "excluded_audience_ids": [],
        "device_price_ranges": null,
        "is_hfss": false,
        "adgroup_app_profile_page_state": null,
        "category_exclusion_ids": []
    }
}
(/code)
```


3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Ad format | Single image | `ad_format` | `SINGE_IMAGE` |
| Images | One image that is supported in the single image Pangle ad | `image_ids` | Pass one ID of the image that meets the specification requirements. <br>The image to be used in the single image Pangle ad should meet the specification requirements below:<ul><li>File Type: JPG, JPEG, or PNG</li><li> Image Resolution: <ul><li>720 x 1280 pixels</li><li>1200 x 628 pixels</li><li>640 x 640 pixels</li><li>640 x 100 pixels</li><li>600 x 500 pixels or</li><li>640 x 200 pixels</li></ul></li><li>File Size: within 100 MB</li></ul><br>To obtain the image IDs, use [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642) and [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506).  |
```

**Example**

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
        "identity_id": "{{identity_id}}",
        "identity_type": "CUSTOMIZED_USER",
        "ad_format": "SINGLE_IMAGE",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",   
        "landing_page_url":"{{landing_page_url}}"
        }
    ]
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
                "tracking_pixel_id": 0,
                "ad_name": "{{ad_name}}",
                "creative_type": null,
                "ad_text": "{{ad_text}}",
                "campaign_id": "{{campaign_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "music_id": null,
                "page_id": null,
                "video_id": null,
                "create_time": "{{create_time}}",
                "is_aco": false,
                "identity_id": "{{identity_id}}",
                "click_tracking_url": null,
                "landing_page_urls": null,
                "adgroup_id": "{{adgroup_id}}",
                "ad_format": "SINGLE_IMAGE",
                "campaign_name": "{{campaign_name}}",
                "vast_moat_enabled": false,
                "deeplink": "",
                "impression_tracking_url": null,
                "is_new_structure": true,
                "app_name": "",
                "display_name": "{{display_name}}",
                "modify_time": "{{modify_time}}",
                "ad_id": "{{ad_id}}",
                "optimization_event": null,
                "playable_url": "",
                "advertiser_id": "{{advertiser_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "fallback_type": "UNSET",
                "brand_safety_postbid_partner": "UNSET",
                "viewability_vast_url": null,
                "operation_status": "ENABLE",
                "call_to_action": "SHOP_NOW",
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_url": "{{landing_page_url}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "brand_safety_vast_url": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_texts": null,
                "profile_image_url": "{{profile_image_url}}",
                "card_id": null,
                "call_to_action_id": null,
                "deeplink_type": "NORMAL",
                "viewability_postbid_partner": "UNSET",
                "creative_authorized": false
            }
        ]
    }
}
(/code)
```

4. Preview an ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). We offer preview types of `AD` and `ADS_CREATION` to enable you to preview single image Pangle ads that you plan to create, and existing single image Pangle ads.

### Create single image ads on Global App Bundle placement
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective | One of the following objectives: <ul><li> App promotion</li><li>Website conversions</li><li>Traffic </li></ul>| `objective_type` | Any value below:<ul><li>`APP_PROMOTION`</li><li>`WEB_CONVERSIONS`</li><li>`TRAFFIC`</li></ul> |
| App promotion type <br>(when Advertising objective = App promotion) | App Install | `app_promotion_type` | `APP_INSTALL` |
| iOS 14 dedicated campaign | Disabled | `campaign_type` | `REGULAR_CAMPAIGN` |
```

> <span style="color:DodgerBlue">**Note**</span><br>
> The Smart+ Campaign setting should be disabled for the single image campaign. If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) to check.

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "TRAFFIC",
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
        "create_time": "{{create_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "budget": {{budget}},
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "modify_time": "{{modify_time}}",
        "campaign_name": "{{campaign_name}}",
        "operation_status": "ENABLE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "deep_bid_type": null,
        "objective_type": "TRAFFIC",
        "roas_bid": 0,
        "objective": "LANDING_PAGE"
    }
}
(/code)
```

2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th style="width:25%">Requirement</th>
    <th style="width:23%">Parameter</th>
    <th style="width:32%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">Placements<br></td>
    <td rowspan="2"><strong>Select Placement</strong> with Global App Bundle placement<br></td>
    <td><code>placement_type</code></td>
    <td><code>PLACEMENT_TYPE_NORMAL</code></td>
  </tr>
  <tr>
    <td><code>placements</code></td>
    <td><code>["PLACEMENT_GLOBAL_APP_BUNDLE"]</code></td>
  </tr>
  <tr>
    <td>Audience targeting - Automatic targeting</td>
    <td>Disabled</td>
    <td><code>auto_targeting_enabled</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting - Location</td>
    <td>One or more locations that are available for Global App Bundle placement</td>
    <td><code>location_ids</code></td>
    <td>Set <code>location_ids</code> to one or more locations available for Global App Bundle placement.<br><br>The available locations you can target with the Global App Bundle placement are: <ul><li><code>BR</code> Brazil</li><li><code>ID</code>: Indonesia</li><li><code>JP</code>: Japan</li><li><code>MY</code>: Malaysia</li><li><code>MX</code>: Mexico</li><li><code>PH</code>: Philippines</li><li><code>SA</code>: Saudi Arabia</li><li><code>TH</code>: Thailand</li><li><code>VN</code>: Vietnam</li></ul><br>To obtain the location IDs that are available for your ad account under Global App Bundle placement, use <a href="https://business-api.tiktok.com/portal/docs?id=1737189539571713"  target="_blank">/tool/region/</a> and set <code>placements</code> to <code>["PLACEMENT_GLOBAL_APP_BUNDLE"]</code>.</td>
  </tr>
  <tr>
    <td>Audience targeting - Spending power</td>
    <td>Disabled</td>
    <td><code>spending_power</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting - Audience - Include and Exclude</td>
    <td><ul><li>The only supported audience types are Engagement, App Activity, and Website Traffic.</li><li>Lookalike audiences are not supported.</li></ul></td>
    <td><code>audience_ids</code><br><code>excluded_audience_ids</code></td>
    <td>Pass IDs of Engagement audiences, App Activity audiences, Website Traffic audiences, or a combination of the three audience types.<br><br>To obtain the ID of these audience types, use <a href="https://business-api.tiktok.com/portal/docs?id=1739940506015746"  target="_blank">/dmp/custom_audience/list/</a> and select audience IDs with </code>audience_type</code> as <code>Engagement Audience</code>, <code>App Activity Audience</code>, or <code>Website Audience</code>.</td>
  </tr>
  <tr>
    <td>Audience targeting - Interest & Behaviors</td>
    <td>Disabled</td>
    <td><code>interest_category_ids</code><br><code> interest_keyword_ids</code><br><code>purchase_intention_keyword_ids</code><br><code>actions </code><br><code> action_period </code><br><code>video_user_actions </code><br><code>action_category_ids  </code><br><code>action_scene</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting - Targeting expansion</td>
    <td>Disabled</td>
    <td><code>targeting_expansion</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td><ul><li>If Advertising objective is App promotion, the only supported optimization goals are Click and Install.</li><li>If Advertising objective is Website conversions, the only supported optimization goal is Click.</li><li>If Advertising objective is Traffic, the only supported optimization goal is Click.</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td><ul><li>If <code>objective_type</code> is set to <code>APP_PROMOTION</code>, set this field to <code>CLICK</code> or <code>INSTALL</code></li><li>If <code>objective_type</code> is set to <code>WEB_CONVERSIONS</code>, set this field to <code>CLICK</code>.</li><li>If <code>objective_type</code> is set to <code>TRAFFIC</code>, set this field to <code>CLICK</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Install, set billing event to oCPM.</li><li>If optimization goal is Click, set billing event to CPC.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is set to <code>INSTALL</code>, set this field to <code>OCPM</code>.</li><li>If <code>optimization_goal</code> is set to <code>CLICK</code>, set this field to <code>CPC</code>.</li></ul></td>
  </tr>
</tbody>
</table>

**Example**

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
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_GLOBAL_APP_BUNDLE"
    ],
    "video_download_disabled": true,
    "location_ids": ["102358"],
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
        "skip_learning_phase": false,
        "adgroup_app_profile_page_state": null,
        "rf_purchased_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "frequency": null,
        "create_time": "{{create_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "placements": [
            "PLACEMENT_GLOBAL_APP_BUNDLE"
        ],
        "budget": {{budget}},
        "schedule_infos": null,
        "keywords": null,
        "purchased_reach": null,
        "promotion_type": "WEBSITE",
        "bid_price": 0.0,
        "app_download_url": null,
        "statistic_type": null,
        "share_disabled": false,
        "conversion_window": null,
        "is_hfss": false,
        "app_id": null,
        "delivery_mode": null,
        "interest_keyword_ids": [],
        "operating_systems": [],
        "adgroup_name": "{{adgroup_name}}",
        "excluded_audience_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": null,
        "secondary_optimization_event": null,
        "pixel_id": null,
        "frequency_schedule": null,
        "ios14_quota_type": "UNOCCUPIED",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_name": "{{campaign_name}}",
        "bid_type": "BID_TYPE_NO_BID",
        "comment_disabled": false,
        "rf_estimated_cpr": null,
        "brand_safety_partner": null,
        "deep_cpa_bid": 0.0,
        "optimization_goal": "CLICK",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "audience_ids": [],
        "category_id": "0",
        "inventory_filter_enabled": false,
        "video_download_disabled": true,
        "schedule_type": "SCHEDULE_START_END",
        "creative_material_mode": "CUSTOM",
        "conversion_bid_price": 0.0,
        "location_ids": [
            "102358"
        ],
        "network_types": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "interest_category_ids": [],
        "device_price_ranges": null,
        "rf_estimated_frequency": null,
        "next_day_retention": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "age_groups": null,
        "pacing": "PACING_MODE_SMOOTH",
        "campaign_id": "{{campaign_id}}",
        "scheduled_budget": 0.0,
        "feed_type": null,
        "modify_time": "{{modify_time}}",
        "languages": [],
        "purchased_impression": null,
        "billing_event": "CPC",
        "gender": "GENDER_UNLIMITED",
        "auto_targeting_enabled": false,
        "app_type": null,
        "isp_ids": [],
        "optimization_event": null,
        "search_result_enabled": false,
        "actions": [],
        "operation_status": "ENABLE",
        "is_new_structure": true,
        "adgroup_id": "{{adgroup_id}}",
        "bid_display_mode": "CPMV",
        "category_exclusion_ids": []
    }
}
(/code)
```
3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Ad format | Single image | `ad_format` | `SINGE_IMAGE` |
| Images | One image that is supported in the single image Global App Bundle ad | `image_ids` | Pass one ID of the image that meets the specification requirements. <br>The image to be used in the single image Global App Bundle ad should meet the specification requirements below:<ul><li>File Type: JPG, JPEG, or PNG</li><li> Image Resolution: <ul><li>720 x 1280 pixels</li><li>1200 x 628 pixels or</li><li>640 x 640 pixels</li></ul></li><li>File Size: within 100 MB</li></ul><br>To obtain the image IDs, use [/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642) and [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506).  |
```

**Example**

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
        "identity_id": "{{identity_id}}",
        "identity_type": "CUSTOMIZED_USER",
        "ad_format": "SINGLE_IMAGE",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",   
        "landing_page_url":"{{landing_page_url}}"
        }
    ]
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
                "is_aco": false,
                "click_tracking_url": null,
                "video_id": null,
                "call_to_action_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "deeplink_type": "NORMAL",
                "create_time": "{{create_time}}",
                "ad_id": "{{ad_id}}",
                "app_name": "",
                "identity_id": "{{identity_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_postbid_partner": "UNSET",
                "viewability_postbid_partner": "UNSET",
                "page_id": null,
                "card_id": null,
                "call_to_action": "SHOP_NOW",
                "brand_safety_vast_url": null,
                "deeplink": "",
                "identity_type": "CUSTOMIZED_USER",
                "creative_authorized": false,
                "creative_type": null,
                "ad_text": "{{ad_text}}",
                "adgroup_name": "{{adgroup_name}}",
                "ad_name": "{{ad_name}}",
                "campaign_name": "{{campaign_name}}",
                "campaign_id": "{{campaign_id}}",
                "display_name": "{{display_name}}",
                "ad_texts": null,
                "fallback_type": "UNSET",
                "image_ids": [
                    "{{image_id}}"
                ],
                "music_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "modify_time": "{{modify_time}}",
                "operation_status": "ENABLE",
                "tracking_pixel_id": 0,
                "playable_url": "",
                "is_new_structure": true,
                "viewability_vast_url": null,
                "ad_format": "SINGLE_IMAGE",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "impression_tracking_url": null,
                "landing_page_urls": null,
                "advertiser_id": "{{advertiser_id}}",
                "profile_image_url": "{{profile_image_url}}",
                "vast_moat_enabled": false,
                "optimization_event": null
            }
        ]
    }
}
(/code)
```


