---
title: "Create a Saved Audience"
doc_id: 1780156510696449
path: "Marketing API / Audience Management / Guides / Create an audience / Create a Saved Audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Saved Audience

This article introduces how to create a Saved Audience. 

## Saved Audience

You can use Saved Audience to store your targeting settings and reuse them to create future ad groups. By removing the repetitive steps you typically take to build your target audiences, Saved Audience will save you time and improve your experience of running campaigns.

Currently, the settings you can specify within your Saved Audience include demographics, audience, interests, behaviors, and devices. For detailed options, refer to the [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754) API endpoint article. However, options relating to household income(`household_income`), spending power(`spending_power`), special industries(`special_industries`), and Pangle audience packages (`included_pangle_audience_package_ids` and `excluded_pangle_audience_package_ids`) are not supported.

> <span style="color:DarkRed">**Notes**</span><ul><li> Before using Saved Audience in your ad group, please ensure your campaign and ad group settings meet the following conditions:</li><ul><li>The category of Housing, Employment, or Credit (`specical_industries`) is NOT specified in your campaign.</li><li>**AND** the advertising objective (`objective_type`) is NOT set to Product Sales (`PRODUCT_SALES`) in your campaign. </li><li>**AND** TikTok placement is selected in your ad group (i.e., `placement_type` is set as `PLACEMENT_TYPE_AUTOMATIC` <b>or</b> `placement_type` is set as `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TIKTOK` is included in placements). </li><li>**AND** automatic targeting is NOT used in your ad group(i.e., `auto_targeting_enabled` is NOT set to `true`).
  </li></ul></li><li> Additionally, the Smart+ Campaign and the Reach & Frequency ads cannot use the Saved Audience. </li></ul>

## Create a Saved Audience
Use [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754) to create a Saved Audience. If your call is successful, the response will return a  `saved_audience_id`, which can be used to create or update an ad group.

If you want to get details of your Saved Audiences, use [/dmp/saved_audience/list/](https://business-api.tiktok.com/portal/docs?id=1780154619404290).  If you want to delete existing Saved Audiences, use [/dmp/saved_audience/delete/](https://business-api.tiktok.com/portal/docs?id=1780154666312706).
> <span style="color:DarkRed">**Note**</span><br> Currently, we don't allow you to update a Saved Audience. 

## Apply your Saved Audience to an ad group
1. Create a campaign via [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). 
2. Create an ad group via  [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114).

You can apply your Saved Audience to an ad group by inputting the `saved_audience_id` in the endpoint. The advertiser ID associated with your Saved Audience should be the same as the advertiser ID in your ad group. 
> <span style="color:DarkRed">**Note**</span><br> When creating a Saved Audience via [/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754), you can specify different targeting options such as `gender`. However, if you are using the Saved Audience to create an ad group via  [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114), you must avoid concurrently setting both the `saved_audience_id` and targeting options (like `gender`) already within your Saved Audience. Otherwise, an error will occur. 

In the response, we will return both the `saved_audience_id` and the targeting options that are included within your Saved Audience.

> <span style="color:DarkRed">**Note**</span><br>If your API request throws an error that is associated with a conflict between your Saved Audience configurations and ad group configurations, the current error message will indicate that the issue pertains to your audience targeting option. To resolve this, we recommend using [/dmp/saved_audience/list/](https://business-api.tiktok.com/portal/docs?id=1780154619404290) to check the details of the corresponding targeting option to identify the source of the problem and then create a new Saved Audience if necessary.

**Request example**
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/dmp/saved_audience/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": " {{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
    "app_id": "{{app_id}}",
    "app_name": "{{app_name}}",
    "optimization_goal": "INSTALL",
    "optimization_event": "ACTIVE",
    "billing_event": "OCPM",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": 1,
    "conversion_bid_price": 1.0,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": 50,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "2024-01-01 00:00:00",
    "schedule_end_time": "2024-01-02 23:59:59",
    "saved_audience_id": "{{saved_audience_id}}"
}'
(/code)
```

**Response example**
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_id": "{{app_id}}",
        "comment_disabled": false,
        "saved_audience_id": "{{saved_audience_id}}",
        "inventory_filter_enabled": false,
        "statistic_type": "NONE",
        "next_day_retention": null,
        "pixel_id": null,
        "carrier_ids": [
            "{{carrier_ids}}"
        ],
        "category_id": "0",
        "interest_category_ids": [
            "14",
            "16"
        ],
        "campaign_name": "{{campaign_name}}",
        "adgroup_name": "{{adgroup_name}}",
        "is_hfss": false,
        "package": "{{package}}",
        "campaign_id": "{{campaign_id}}",
        "rf_purchased_type": null,
        "app_type": "APP_ANDROID",
        "scheduled_budget": 0.0,
        "age_groups": [
            "AGE_18_24"
        ],
        "promotion_type": "APP_ANDROID",
        "video_download_disabled": false,
        "purchased_impression": null,
        "deep_cpa_bid": 0.0,
        "rf_estimated_cpr": null,
        "keywords": null,
        "conversion_bid_price": 1.0,
        "schedule_type": "SCHEDULE_START_END",
        "is_new_structure": true,
        "audience_ids": [
            "{{audience_id}}",
            "{{audience_id}}"
        ],
        "operation_status": "ENABLE",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "device_price_ranges": [
            0,
            50,
            100,
            150,
            200,
            250,
            300,
            350,
            400,
            450,
            500,
            550,
            600,
            650,
            700,
            750,
            800,
            850,
            900,
            950,
            1000
        ],
        "min_android_version": "10.0",
        "schedule_infos": null,
        "secondary_optimization_event": null,
        "feed_type": null,
        "operating_systems": [
            "ANDROID"
        ],
        "gender": "GENDER_UNLIMITED",
        "adgroup_id": "{{adgroup_id}}",
        "rf_estimated_frequency": null,
        "creative_material_mode": "CUSTOM",
        "bid_price": 0.0,
        "network_types": [
            "WIFI"
        ],
        "optimization_event": "ACTIVE",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "conversion_window": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "adgroup_app_profile_page_state": null,
        "deep_bid_type": "DEFAULT",
        "click_attribution_window": "SEVEN_DAYS",
        "frequency_schedule": null,
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_GLOBAL_APP_BUNDLE",
            "PLACEMENT_PANGLE"
        ],
        "ios14_quota_type": "UNOCCUPIED",
        "languages": [
            "en"
        ],
        "isp_ids": [],
        "schedule_start_time": "2024-01-01 00:00:00",
        "schedule_end_time": "2024-01-02 23:59:59",
        "modify_time": "2023-10-17 03:39:26",
        "actions": [
            {
                "action_period": 0,
                "action_category_ids": [
                    "2"
                ],
                "video_user_actions": [
                    "FOLLOWING"
                ],
                "action_scene": "CREATOR_RELATED"
            }
        ],
        "skip_learning_phase": true,
        "bid_type": "BID_TYPE_CUSTOM",
        "interest_keyword_ids": [
            "{{interest_keyword_ids}}"
        ],
        "bid_display_mode": "CPMV",
        "create_time": "2023-10-17 03:39:26",
        "delivery_mode": null,
        "billing_event": "OCPM",
        "excluded_audience_ids": [
            "{{excluded_audience_ids}}",
            "{{excluded_audience_ids}}"
        ],
        "location_ids": [
            "{{location_ids}}",
            "{{location_ids}}"
        ],
        "attribution_event_count": "ONCE",
        "pacing": "PACING_MODE_FAST",
        "budget": 50.0,
        "purchased_reach": null,
        "view_attribution_window": "ONE_DAY",
        "frequency": null,
        "share_disabled": false,
        "auto_targeting_enabled": false,
        "search_result_enabled": true,
        "app_download_url": "{{app_download_url}}",
        "category_exclusion_ids": [],
        "brand_safety_partner": null,
        "optimization_goal": "INSTALL",
        "advertiser_id": "{{advertiser_id}}"
    }
}
(/code)
```

3. Create an ad via [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354).



