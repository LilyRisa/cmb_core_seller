---
title: "Create Carousel Ads"
doc_id: 1766217791987713
path: "Use Cases / Campaign creation / Create Carousel Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Carousel Ads

This article walks you through the steps to create Carousel Ads.

## Introduction
Carousel is a new ad format on TikTok that supports images for in-feed ads. Multiple images will be displayed in order, and run as a carousel. Users can swipe the images from left to right or right to left and convert based on their interests. To find Carousel format demos, see [Carousel Ads](https://ads.tiktok.com/help/article/carousel-ads?lang=en#anchor-1).

**You can use Campaign Management API to create Carousel Ads, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create Carousel Ads, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the **"Steps"** section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  


## Steps
Based on your preference for creating Video Shopping Ads (VSA), Automotive Ads for Inventory (AIA), or Automotive Ads for Models (AMA) using the Carousel format, you have the flexibility to create three types of Carousel Ads:
- Standard Carousel Ad: This type of Carousel Ad is not specific to VSA or AIA and can be used for general purposes. For detailed instructions on creating such ads, see <a href="#item-link-Steps for creating Standard Carousel Ads">Steps for creating Standard Carousel Ads</a>.
- VSA Carousel Ad: This Carousel Ad format is designed specifically for Video Shopping Ads. For detailed instructions on creating such ads, see <a href="#item-link-Steps for creating VSA Carousel Ads">Steps for creating VSA Carousel Ads</a>.
- AIA Carousel Ad: This Carousel Ad format is tailored for showcasing the inventory of your Auto-Inventory catalog in Automotive Ads for Inventory. For detailed instructions on creating such ads, see [Steps for creating AIA Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1827752507854850#item-link-Ad%20format%20as%20Catalog%20Carousel)</a>.
- AMA Carousel Ad: This Carousel Ad format is tailored for showcasing the inventory of your Auto-Model catalog in Automotive Ads for Models. For detailed instructions on creating such ads, see [Steps for creating AMA Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1829635758164994#item-link-Ad%20format%20as%20Catalog%20Carousel).


### Steps for creating Standard Carousel Ads

1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Advertising objective | One of the following objectives: <ul><li> App promotion </li><li> Website conversions </li><li> Traffic </li><li>Lead Generation</li><li>Reach</li></ul>| `objective_type` | Any value below: <ul><li>`APP_PROMOTION`</li><li>`WEB_CONVERSIONS`</li><li>`TRAFFIC`</li><li>`LEAD_GENERATION`</li><li>`REACH`</li></ul>|
```
> <span style="color:DodgerBlue">**Note**</span><br>
> The Smart+ Campaign setting should be disabled for the Carousel campaign. If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) to check.

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id":"{{advertiser_id}}",
    "budget_mode":"BUDGET_MODE_TOTAL",
    "objective_type":"APP_PROMOTION",
    "campaign_name": "{{campaign_name}}",
    "app_promotion_type":"APP_INSTALL",
    "budget":{{budget}}
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
        "app_promotion_type": "APP_INSTALL",
        "deep_bid_type": null,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "modify_time": "2023-05-15 05:14:35",
        "budget": {{budget}},
        "objective_type": "APP_PROMOTION",
        "operation_status": "ENABLE",
        "campaign_app_profile_page_state": "UNSET",
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "create_time": "2023-05-15 05:14:35",
        "campaign_type": "REGULAR_CAMPAIGN",
        "campaign_name": "{{campaign_name}}",
        "is_smart_performance_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "objective": "APP",
        "roas_bid": 0
    }
}
(/code)
```
2. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Placement | <ul><li> <strong>Automatic Placement</strong> or </li><li> <strong>Select Placement</strong> with TikTok placement included </li></ul> | `placement_type` & `placements` |<ul><li> Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`, or </li><li> Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and include `PLACEMENT_TIKTOK` in the value of `placements` </li></ul> |
| Automated Creative Optimization (ACO) | Disabled | `creative_material_mode` | `CUSTOM` or not passed |
```
> <span style="color:DodgerBlue">**Note**</span><br>
> Carousel ad groups can be used in [/split_test/create/](https://ads.tiktok.com/marketing_api/docs?id=1742666471475201) to create split test groups.

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
    "app_id": "{{app_id}}",
    "promotion_type": "APP_ANDROID",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"],
    "location_ids": ["1733045"],
    "languages":["en"],
    "gender": "GENDER_UNLIMITED",
    "age_groups":["AGE_13_17","AGE_18_24","AGE_25_34","AGE_35_44","AGE_45_54","AGE_55_100"],
    "operating_systems": ["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
    "bid_price": 0.0,
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
        "schedule_infos": null,
        "languages": [
            "en"
        ],
        "skip_learning_phase": false,
        "inventory_filter_enabled": false,
        "bid_display_mode": "CPMV",
        "schedule_start_time": "{{schedule_start_time}}",
        "actions": [],
        "frequency_schedule": null,
        "schedule_type": "SCHEDULE_START_END",
        "promotion_type": "APP_ANDROID",
        "create_time": "2023-05-15 05:49:33",
        "adgroup_id": "{{adgroup_id}}",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "interest_category_ids": [],
        "network_types": [],
        "auto_targeting_enabled": false,
        "pixel_id": null,
        "video_download_disabled": false,
        "app_download_url": "{{app_download_url}}",
        "keywords": null,
        "bid_type": "BID_TYPE_NO_BID",
        "ios14_quota_type": "UNOCCUPIED",
        "app_type": "APP_ANDROID",
        "statistic_type": null,
        "category_id": "0",
        "secondary_optimization_event": null,
        "age_groups": [
            "AGE_13_17",
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "isp_ids": [],
        "adgroup_app_profile_page_state": null,
        "is_new_structure": true,
        "scheduled_budget": 0,
        "operation_status": "ENABLE",
        "search_result_enabled": true,
        "audience_ids": [],
        "share_disabled": false,
        "app_id": "{{app_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "operating_systems": [
            "ANDROID"
        ],
        "location_ids": [
            "1733045"
        ],
        "is_hfss": false,
        "pacing": "PACING_MODE_SMOOTH",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "brand_safety_partner": null,
        "rf_estimated_cpr": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "rf_estimated_frequency": null,
        "modify_time": "2023-05-15 05:49:33",
        "comment_disabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "advertiser_id": "{{advertiser_id}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "schedule_end_time": "{{schedule_end_time}}",
        "purchased_reach": null,
        "feed_type": null,
        "optimization_goal": "CLICK",
        "optimization_event": null,
        "device_price_ranges": null,
        "delivery_mode": null,
        "billing_event": "CPC",
        "purchased_impression": null,
        "interest_keyword_ids": [],
        "gender": "GENDER_UNLIMITED",
        "excluded_audience_ids": [],
        "conversion_window": null,
        "bid_price": 0,
        "conversion_bid_price": 0,
        "package": "{{package}}",
        "campaign_name": "{{campaign_name}}",
        "rf_purchased_type": null,
        "frequency": null,
        "next_day_retention": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "creative_material_mode": "CUSTOM",
        "budget": 400,
        "deep_cpa_bid": 0,
        "deep_bid_type": null,
        "campaign_id": "{{campaign_id}}"
    }
}
(/code)
```


3. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

**For Standard Carousel Non-Spark Ads or Standard Carousel Spark Ads via Spark Ads Push**
```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
|Identity | Any of the following options:<ul><li>Custom User (custom identity)</li><li>TikTok User</li><li>TikTok Account User in Business Center </li></ul>| `identity_type` | Any of the following values:<ul><li>`CUSTOMIZED_USER`</li><li>`TT_USER`</li><li>`BC_AUTH_TT`</li></ul><br>To learn more about Spark Ads, see [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).  |
| Ad format | Carousel Ads | `ad_format` | `CAROUSEL_ADS` |
|Ad creative - Images  | one to 35 images supported in the Carousel Ad | `image_ids` | Pass one to 35 image IDs. <br>You need to get the image IDs by using [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) and [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506). <br>The image to be used in Carousel Ads should meet the requirements below <strong>at the same time</strong>: <ul><li> Specifications: <ul><li> File Type: JPG, JPEG, or PNG.</li><li> Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li> Aspect Ratio: a maximum of 9:20 or 20:9.</li><li> File Size: below 50 MB. </li></ul></li><li> The value of the parameter `is_carousel_usable` returned from [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506) for the image is `true`. </li></ul>|
| Ad creative - Music | A piece of music supported in the Carousel Ad | `music_id` | Pass one music ID that is valid for use in non-VSA Carousel Ads.<br>To obtain a valid music ID, you can <ul><li>filter the pieces of music for non-VSA Carousel Ads under an ad account by specifying `music_scene` as `CAROUSEL_ADS` in [/file/music/get/](https://ads.tiktok.com/marketing_api/docs?id=1740053909509122).</li><li>OR upload a piece of customized music for non-VSA Carousel Ads to an ad account by: <ul><li>specifying `music_scene` as `CAROUSEL_ADS` and passing `music_file` and `music_signature` in [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650)</li><li>OR specifying `music_scene` as `CAROUSEL_ADS` , specifying `upload_type` as `UPLOAD_BY_FILE_ID`, and passing `file_id` in [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650).</li></ul></li></ul> |
| Interactive Add-on | Disabled | `card_id` | Not passed |
```
**For Standard Carousel Spark Ads via Spark Ads Pull**
```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
|Identity | Any of the following options:<ul><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center </li></ul>| `identity_type` | Any of the following values:<ul><li>`AUTH_CODE`</li><li>`TT_USER`</li><li>`BC_AUTH_TT`</li></ul><br>To learn more about Spark Ads, see [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298). |
| Ad format | Carousel Ads | `ad_format` | `CAROUSEL_ADS` |
| Ad creative - Post| Enabled | `tiktok_item_id` | Specify a valid value. <br> <br>To filter photo posts under an identity, use [/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577) or [/identity/video/info/](https://business-api.tiktok.com/portal/docs?id=1740218522178562) and set `item_type` to `CAROUSEL`. |
| Ad creative - Post - Stitch and Duet| Disabled | <ul><li>`item_duet_status`</li><li>`item_stitch_status`</li></ul>  | Not passed |
|Ad creative- Images & Muisc  | Disabled| <ul><li>`image_ids`</li><li> `music_id`</li></ul>| Not passed|
| Interactive Add-on | Disabled | `card_id` | Not passed |
```

**Example**

Request
```xcodeblock
(code curl http)
{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_format": "CAROUSEL_ADS",
        "ad_name": "{{ad_name}}",
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "image_ids":["{{image_id}}", "{{image_id}}"],
        "music_id":"{{music_id}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "landing_page_url":"{{landing_page_url}}"
    }]
}
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
                "ad_texts": null,
                "vast_moat_enabled": false,
                "tracking_app_id": "{{tracking_app_id}}",
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "call_to_action_id": null,
                "operation_status": "ENABLE",
                "creative_type": null,
                "deeplink_type": "NORMAL",
                "brand_safety_postbid_partner": "UNSET",
                "call_to_action": "SHOP_NOW",
                "campaign_id": "{{campaign_id}}",
                "is_new_structure": true,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "advertiser_id": "{{advertiser_id}}",
                "optimization_event": null,
                "landing_page_urls": null,
                "adgroup_id": "{{adgroup_id}}",
                "ad_name": "{{ad_name}}",
                "campaign_name": "{{campaign_name}}",
                "landing_page_url": "",
                "impression_tracking_url": null,
                "deeplink": "",
                "modify_time": "2023-05-15 06:39:22",
                "music_id": "{{}}",
                "ad_format": "CAROUSEL_ADS",
                "profile_image_url": "{{profile_image_url}}",
                "viewability_postbid_partner": "UNSET",
                "video_id": null,
                "click_tracking_url": null,
                "page_id": null,
                "viewability_vast_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "ad_text": "{{ad_text}}",
                "is_aco": false,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "tracking_pixel_id": 0,
                "playable_url": "",
                "brand_safety_vast_url": null,
                "creative_authorized": false,
                "card_id": null,
                "identity_type": "CUSTOMIZED_USER",
                "app_name": "{{app_name}}",
                "display_name": "",
                "create_time": "2023-05-15 06:39:22",
                "image_ids": [
                    "{{image_id}}",
                    "{{image_id}}"
                ]
            }
        ]
    }
}
(/code)
```

### Steps for creating VSA Carousel Ads
1. Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that in addition to the parameter requirements listed in the section [Create Video Shopping Ads with products from catalogs-1. Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-1.%20Create%20a%20campaign), the following requirements must be met at the same time.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {25%} | How to configure the parameter {30%} |
|---|---|---|---|
| Campaign Budget Optimization (CBO) | Supported with allowlist | `budget_optimize_on` | `false` or `true`<br><br><p><span style="color:darkred"><b>Note</b></span>: Campaign Budget Optimization for Video Shopping Ads with product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Campaign product source | Catalog | `campaign_product_source` | `CATALOG` |
```
> <span style="color:DodgerBlue">**Note**</span><br>
> The Smart+ Campaign setting should be disabled for the Carousel campaign. If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) to check.

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "PRODUCT_SALES",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 5000,
    "campaign_name": "{{campaign_name}}",
    "campaign_product_source":"CATALOG"
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
        "campaign_type": "REGULAR_CAMPAIGN",
        "campaign_product_source": "CATALOG",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_app_profile_page_state": "UNSET",
        "roas_bid": 0,
        "modify_time": "2023-06-07 10:55:33",
        "deep_bid_type": null,
        "is_smart_performance_campaign": false,
        "budget": 5000,
        "campaign_name": "{{campaign_name}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "objective_type": "PRODUCT_SALES",
        "operation_status": "ENABLE",
        "create_time": "2023-06-07 10:55:33",
        "objective": "LANDING_PAGE"
    }
}
(/code)
```

2. Create and manage catalogs. Follow the steps outlined in the section [Create Video Shopping Ads with products from catalogs-2. Create and manage catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-2.%20Create%20and%20manage%20catalogs).

3. Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-3. Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-3.%20Create%20an%20ad%20group), the following requirements must be met at the same time.

```xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|---|
| Placement | <ul><li> <strong>Automatic Placement</strong> or </li><li> <strong>Select Placement</strong> with TikTok placement included </li></ul> | `placement_type` & `placements` |<ul><li> Set `placement_type` to `PLACEMENT_TYPE_AUTOMATIC`, or </li><li> Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and include `PLACEMENT_TIKTOK` in the value of `placements` </li></ul> |
```
> <span style="color:DodgerBlue">**Note**</span><br>
> Carousel ad groups can be used in [/split_test/create/](https://ads.tiktok.com/marketing_api/docs?id=1742666471475201) to create split test groups.

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "campaign_id": "{{campaign_id}}",
    "promotion_type": "APP_ANDROID", 
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],  
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "app_id": "{{app_id}}",
    "shopping_ads_retargeting_type": "OFF",    
    "location_ids": [
        "1861060"
    ], 
    "optimization_goal": "CLICK", 
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}", 
    "video_download_disabled": true, 
    "automated_targeting": "OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "gender": "GENDER_UNLIMITED",
    "operating_systems": [
        "ANDROID"
    ],
    "ios14_quota_type": "UNOCCUPIED",
    "operation_status": "ENABLE",
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
        "billing_event": "CPC",
        "purchased_impression": null,
        "optimization_event": null,
        "schedule_infos": null,
        "bid_price": 0,
        "comment_disabled": false,
        "network_types": [],
        "device_price_ranges": null,
        "actions": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "operating_systems": [
            "ANDROID"
        ],
        "conversion_bid_price": 0,
        "next_day_retention": null,
        "advertiser_id": "{{advertiser_id}}",
        "modify_time": "2023-06-07 11:46:40",
        "shopping_ads_retargeting_type": "OFF",
        "rf_estimated_cpr": null,
        "skip_learning_phase": false,
        "budget": 38000,
        "brand_safety_partner": null,
        "catalog_id": "{{catalog_id}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "product_source": "CATALOG",
        "creative_material_mode": "CATALOG_SALES",
        "is_hfss": false,
        "age_groups": null,
        "adgroup_name": "{{adgroup_name}}",
        "auto_targeting_enabled": false,
        "gender": "GENDER_UNLIMITED",
        "bid_display_mode": "CPMV",
        "delivery_mode": null,
        "feed_type": null,
        "bid_type": "BID_TYPE_NO_BID",
        "rf_estimated_frequency": null,
        "isp_ids": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "purchased_reach": null,
        "promotion_type": "APP_ANDROID",
        "optimization_goal": "CLICK",
        "share_disabled": false,
        "frequency_schedule": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "package": "{{package}}",
        "deep_cpa_bid": 0,
        "pixel_id": null,
        "adgroup_app_profile_page_state": null,
        "excluded_audience_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "campaign_name": "{{campaign_name}}",
        "languages": [],
        "ios14_quota_type": "UNOCCUPIED",
        "secondary_optimization_event": null,
        "pacing": "PACING_MODE_SMOOTH",
        "audience_ids": [],
        "shopping_ads_type": "VIDEO",
        "operation_status": "ENABLE",
        "conversion_window": null,
        "video_download_disabled": true,
        "search_result_enabled": false,
        "inventory_filter_enabled": false,
        "frequency": null,
        "deep_bid_type": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "interest_keyword_ids": [],
        "schedule_type": "SCHEDULE_START_END",
        "scheduled_budget": 0,
        "interest_category_ids": [],
        "keywords": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "app_type": "APP_ANDROID",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "campaign_id": "{{campaign_id}}",
        "rf_purchased_type": null,
        "is_new_structure": true,
        "category_id": "0",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "app_id": "{{}}",
        "create_time": "2023-06-07 11:46:40",
        "location_ids": [
            "1861060"
        ],
        "app_download_url": "{{app_download_url}}",
        "adgroup_id": "{{adgroup_id}}",
        "statistic_type": null
    }
}
(/code)
```
4. Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-4. Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-4.%20Create%20an%20ad), the following requirements must be met at the same time.


```xtable
| Setting {20%} | Requirement {15%} | Parameter {25%} | How to configure the parameter {40%} |
|---|---|---|---|
| Ad format | Catalog Carousel | `ad_format` | `CATALOG_CAROUSEL` |
| Images | At least 2 images supported in the Carousel Ad | `catalog_id` (required)<br>`product_specific_type` (required)<br>`item_group_ids` (conditional) <br>`product_set_id` (conditional)<br>`sku_ids`(conditional) <br> `carousel_image_index` (optional)<br> `image_ids` (unsupported)  | <ul><li> Carousel image selection scope: <br> The product images for products in a catalog (`catalog_id`) will be dynamically chosen as images in the delivering Carousel Ads. You can specify the scope of product images to choose from using the `product_specific_type` parameter: <ul><li> To have the Carousel images dynamically chosen from all products in the catalog, set `product_specific_type` to `ALL`, and do not pass any of `item_group_ids`, `product_set_id`, or `sku_ids`. The catalog (`catalog_id`) needs to include at least two products.</li><li> To have the Carousel images dynamically chosen from products in a product set, set `product_specific_type` to `PRODUCT_SET`, and pass either `item_group_ids` or `product_set_id`. The specified `item_group_ids` or `product_set_id` needs to include at least two products.</li><li> To have the images dynamically chosen from a customized product range consisting of up to 20 products, set `product_specific_type` to `CUSTOMIZED_PRODUCTS`, and pass `sku_ids`. The specified `sku_ids` needs to include at least two products. </li></ul></li><li> Carousel image type: <ul><li> Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images. </li><li> Use the additional images of the products: Optionally, you can specify the position of the product image within the additional image list (`additional_image_urls`) for each selected product by using the `carousel_image_index` parameter to select from the additional images. </li></ul></li></ul> |
| Music | A piece of music supported in the Carousel Ad | `music_id` | Pass one music ID that is valid for use in VSA Carousel Ads.<br>To obtain a valid music ID, you can <ul><li>filter the pieces of music for VSA Carousel Ads under an ad account by specifying `music_scene` as `CATALOG_CAROUSEL` in [/file/music/get/](https://ads.tiktok.com/marketing_api/docs?id=1740053909509122).</li><li>OR upload a piece of customized music for VSA Carousel Ads to an ad account by: <ul><li>specifying `music_scene` as `CATALOG_CAROUSEL` and passing `music_file` and `music_signature` in [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650)</li><li>OR specifying `music_scene` as `CATALOG_CAROUSEL` , specifying `upload_type` as `UPLOAD_BY_FILE_ID`, and passing `file_id` in [/file/music/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740052650395650).</li></ul></li></ul>  |
| Video | Unsupported | `video_id`<br>`vertical_video_strategy` <br>`shopping_ads_video_package_id`| Not passed |
| Interactive Add-on | Disabled | `card_id` | Not passed |
| Spark ads | Disabled | `identity_type` | `CUSTOMIZED_USER` |
| Spark ads | Disabled | `tiktok_item_id` | Not passed |
| Dynamic format | Disabled | `dynamic_format` | `UNSET` or not passed |
| Dynamic Landing Page | Disabled | `dynamic_destination` | `UNSET` or not passed |
| Instant Form, Custom TikTok Instant Page or App Profile Page | Disabled | `page_id` | Not passed |
| Instant Product Page | / | `instant_product_page_used` |Not passed|
| Deeplink type in Shopping Ads scenario | The only supported type is Custom deeplink | `shopping_ads_deeplink_type` | If passed, the field <strong>cannot</strong> be set to `SHOPPING_ADS` (Product deeplink). |
| Fallback type in Shopping Ads retargeting scenario | The only supported type is Custom link | `shopping_ads_fallback_type` | If passed, the field <strong>cannot</strong> be set to `SHOPPING_ADS` (Product link) |
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
                "ad_format": "CATALOG_CAROUSEL",
                "catalog_id": "{{catalog_id}}",      
                "product_specific_type": "ALL",   
                "music_id": "{{music_id}}",   
                "identity_id": "{{identity_id}}",
                "identity_type": "CUSTOMIZED_USER",   
                "dynamic_format": "UNSET",  
                "dynamic_destination": "UNSET",  
                "shopping_ads_deeplink_type": "CUSTOM",             
                "deeplink": "{{deeplink}}",
                "deeplink_type": "DEFERRED_DEEPLINK",              
                "call_to_action": "SHOP_NOW",
                "ad_text": "{{ad_text}}"
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
                "operation_status": "ENABLE",
                "is_aco": false,
                "page_id": null,
                "image_ids": [],
                "brand_safety_vast_url": null,
                "modify_time": "2023-06-07 12:00:09",
                "create_time": "2023-06-07 12:00:09",
                "campaign_id": "{{campaign_id}}",
                "profile_image_url": "{{profile_image_url}}",
                "click_tracking_url": null,
                "viewability_vast_url": null,
                "brand_safety_postbid_partner": "UNSET",
                "landing_page_urls": null,
                "call_to_action_id": null,
                "catalog_id": "{{catalog_id}}",
                "deeplink_type": "DEFERRED_DEEPLINK",
                "viewability_postbid_partner": "UNSET",
                "shopping_ads_deeplink_type": "CUSTOM",
                "dynamic_format": "UNSET",
                "creative_authorized": false,
                "music_id": "{{music_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "identity_type": "CUSTOMIZED_USER",
                "video_id": null,
                "identity_id": "{{identity_id}}",
                "app_name": "{{app_name}}",
                "ad_id": "{{ad_id}}",
                "landing_page_url": "",
                "deeplink": "{{deeplink}}",
                "display_name": "",
                "product_specific_type": "ALL",
                "tracking_pixel_id": 0,
                "campaign_name": "{{campaign_name}}",
                "vast_moat_enabled": false,
                "call_to_action": "SHOP_NOW",
                "ad_format": "CATALOG_CAROUSEL",
                "dynamic_destination": "UNSET",
                "optimization_event": null,
                "shopping_ads_fallback_type": "DEFAULT",
                "card_id": null,
                "tracking_app_id": "{{tracking_app_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "creative_type": null,
                "adgroup_id": "{{adgroup_id}}",
                "ad_text": "{{ad_text}}",
                "impression_tracking_url": null,
                "fallback_type": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "is_new_structure": true,
                "ad_name": "{{ad_name}}",
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "playable_url": ""
            }
        ]
    }
}
(/code)
```
5. Preview an ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). We offer preview types of `AD` and `ADS_CREATION` to enable you to preview VSA Carousel Ads that you plan to create, and existing VSA Carousel Ads.

### Steps for creating AIA Carousel Ads
To learn about the steps, see [Create Automotive Carousel Ads for Inventory](https://business-api.tiktok.com/portal/docs?id=1827752507854850#item-link-Ad%20format%20as%20Catalog%20Carousel).

### Steps for creating AMA Carousel Ads
To learn about the steps, see [Create Automotive Carousel Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994#item-link-Ad%20format%20as%20Catalog%20Carousel).

## Related docs
- [Campaign Management API](https://ads.tiktok.com/marketing_api/docs?id=1739381823123458)
- [Campaign Management Guide](https://ads.tiktok.com/marketing_api/docs?id=1735713781404673)
- [Create Video Shopping Ads with products from catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249)
- [Create Automotive Ads for Inventory](https://business-api.tiktok.com/portal/docs?id=1827752507854850)
- [Create Automotive Ads for Models](https://business-api.tiktok.com/portal/docs?id=1829635758164994)
