---
title: "Create a Lead Generation ad with optimization location as TikTok direct messages"
doc_id: 1774482992153601
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as TikTok direct messages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Lead Generation ad with optimization location as TikTok direct messages

This article introduces how to create a Lead Generation ad with optimization location as TikTok direct messages. This enables the ad audience to directly message your authorized TikTok account in the TikTok app by clicking the "Send message" call-to-action button within the ad.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Creating a Lead Generation ad with TikTok direct messages as the optimization location is generally available for advertisers registered in the following regions: <ul><li>APAC: Australia, Bangladesh, Cambodia, Indonesia, Japan, Malaysia, New Zealand, Pakistan, the Philippines, Singapore, South Korea, Sri Lanka, Taiwan, Thailand, and Vietnam.</li><li>LATAM: Argentina, Bolivia, Brazil, Chile, Colombia, Costa Rica, the Dominican Republic, Ecuador, Guatemala, Mexico, Panama, Paraguay, Peru, Puerto Rico, and Uruguay.</li></ul></li><li>For advertisers registered in other regions, this feature is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>You can only create <strong>Spark Ads</strong> as Lead Generation ads with optimization location as TikTok direct messages. </li></ul>


# Prerequisite
The owner of the authorized TikTok account has enabled the "Everyone" permission for Direct Messages on the TikTok account, allowing anyone to send direct messages to the TikTok account. To learn how to configure this setting, refer to [How to manage who can send you direct messages](https://support.tiktok.com/en/using-tiktok/messaging-and-notifications/direct-message-settings#7).

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Advertising objective | Lead Generation | `objective_type` | `LEAD_GENERATION` |
| Smart+ Campaign | Disabled <br><br>If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) to check. | / | / |
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
| Setting {20%} | Requirement {22%} | Parameter {30%} | How to configure the parameter {28%} |
|---|---|---|
| Optimization location<br>(Promotion type) | TikTok direct message | `promotion_type`<br> `promotion_target_type`| <ul><li> Set `promotion_type` to `LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`,</li><li> AND do not pass `promotion_target_type`.</li></ul>|
| Placement | <strong>Select Placement</strong> with TikTok placement| `placement_type`<br> `placements` | <ul><li> Set `placement_type` to `PLACEMENT_TYPE_NORMAL`</li><li> AND set `placements` to ` ["PLACEMENT_TIKTOK"]`.</li></ul>|
| Age targeting | Audiences younger than 18 years old can't be targeted. | `age_groups` | Do not pass the value `AGE_13_17`. |
| Demographics - Location targeting | The available targeting regions are as follows: <ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul>Note that advertisers registered with the Chinese mainland (`CN`) cannot target locations in Australia and Indonesia. | `location_ids` | Pass the IDs of locations within the following regions: <ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul>Note that advertisers registered with the Chinese mainland (`CN`) cannot target locations in Australia and Indonesia.<br><br>To obtain the available targeting locations, use [/tool/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1761236883355649) or [/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713). |
| Pangle audience packages  | Not supported| `included_pangle_audience_package_ids` <br>`excluded_pangle_audience_package_ids`| Not supported |
| Optimization goal | Any of the following types: <ul><li> Click </li><li> Conversations (supported with allowlist) </li></ul>| `optimization_goal`<ul><li>`optimization_event` </li></ul> | Any of the following values: <ul><li>`CLICK` </li><li>`CONVERSATION`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> The optimization goal Conversation (`CONVERSATION`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li> If `optimization_goal` is `CLICK`, do not pass `optimization_event`. </li><li> If `optimization_goal` is `CONVERSATION`, omit `optimization_event` or set `optimization_event` to `MESSAGE`. </li></ul>|
| Bid Strategy | Any of the following types: <ul><li> Maximum Delivery </li><li> Cost Cap </li></ul>| <ul><li>`bid_type`</li><li>`bid_price`</li><li>`conversion_bid_price` </li></ul> | Any of the following values for `bid_type`: <ul><li>`BID_TYPE_NO_BID` </li><li>`BID_TYPE_CUSTOM`<ul><li> If `bid_type` is `BID_TYPE_CUSTOM` and `optimization_goal` is `CONVERSATION`, pass `conversion_bid_price` at the same time.</li><li> If `bid_type` is `BID_TYPE_CUSTOM` and `optimization_goal` is `CLICK`, pass `bid_price` at the same time.</li></ul> |
| Attribution settings | <ul><li> Attribution window: Unset.<br> When Optimization goal is Conversations, the default setting is 7-day click & 1-day view. </li><li> Event count: Unset. <br> When Optimization goal is Conversations, the default setting is Once.  </li></ul> | <ul><li>`click_attribution_window`</li><li>`view_attribution_window`</li><li>`attribution_event_count` </li></ul>  | Not passed. <br><ul><li> When `optimization_goal` is `CLICK`, these fields are not supported.</li><li> When `optimization_goal` is `CONVERSATION`, these fields can be omitted, and the default settings will be used: <ul><li>`click_attribution_window` will default to `SEVEN_DAYS`. </li><li> `view_attribution_window` will default to `ONE_DAY`. </li><li> `attribution_event_count` will default to `ONCE`.</li></ul></li></ul>|
| Billing event | <ul><li> If optimization goal is Click, the billing event should be CPC.</li><li> If optimization goal is Conversations, the billing event should be oCPM.</li></ul> | `billing_event` | <ul><li> If `optimization_goal` is `CLICK`, set this field to `CPC`. </li><li> If `optimization_goal` is `CONVERSATION`, set this field to `OCPM`.</li></ul>|
| Delivery Type | <ul><li> If Bid Strategy is Maximum Delivery, you can only set Delivery Type to Standard.</li><li> If Bid Strategy is Cost Cap, you can set Delivery Type to Standard or Accelerated. </li></ul>| `pacing` | <ul><li> If `bid_type` is `BID_TYPE_NO_BID`, you can only set `pacing` to `PACING_MODE_SMOOTH`. </li><li> If `bid_type` is `BID_TYPE_CUSTOM`, you can set `pacing` to `PACING_MODE_SMOOTH` or `PACING_MODE_FAST`. </li></ul>|
| Search ads| Not supported| `search_result_enabled`| Can only set it to `false`, or do not pass this field.|
| Automated Creative Optimization (ACO) | Not supported| `creative_material_mode` |`CUSTOM` or not passed|
```

### Example
#### Optimization goal as Conversations 
Request
```xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "video_download_disabled": true,
    "location_ids": [
        "5332921"
    ],
    "age_groups": [
        "AGE_18_24"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERSATION",
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
        "rf_estimated_frequency": null,
        "languages": [],
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "delivery_mode": null,
        "frequency": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "excluded_audience_ids": [],
        "secondary_optimization_event": null,
        "brand_safety_partner": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "operating_systems": [],
        "operation_status": "ENABLE",
        "creative_material_mode": "CUSTOM",
        "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
        "deep_cpa_bid": 0,
        "bid_display_mode": "CPMV",
        "purchased_impression": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "view_attribution_window": "ONE_DAY",
        "schedule_infos": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "age_groups": [
            "AGE_18_24"
        ],
        "adgroup_app_profile_page_state": null,
        "app_type": null,
        "next_day_retention": null,
        "rf_estimated_cpr": null,
        "comment_disabled": false,
        "is_smart_performance_campaign": false,
        "keywords": null,
        "network_types": [],
        "schedule_type": "SCHEDULE_START_END",
        "bid_price": 0,
        "click_attribution_window": "SEVEN_DAYS",
        "budget": {{budget}},
        "location_ids": [
            "5332921"
        ],
        "billing_event": "OCPM",
        "is_new_structure": true,
        "inventory_filter_enabled": false,
        "attribution_event_count": "ONCE",
        "category_exclusion_ids": [],
        "smart_audience_enabled": null,
        "frequency_schedule": null,
        "isp_ids": [],
        "optimization_event": "MESSAGE",
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_id": "{{campaign_id}}",
        "modify_time": "{{modify_time}}",
        "pacing": "PACING_MODE_SMOOTH",
        "app_id": null,
        "scheduled_budget": 0,
        "share_disabled": false,
        "device_price_ranges": null,
        "statistic_type": null,
        "ios14_quota_type": "UNOCCUPIED",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "skip_learning_phase": true,
        "adgroup_id": "{{adgroup_id}}",
        "create_time": "{{create_time}}",
        "interest_keyword_ids": [],
        "interest_category_ids": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "actions": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "gender": "GENDER_UNLIMITED",
        "pixel_id": null,
        "auto_targeting_enabled": false,
        "category_id": "0",
        "search_result_enabled": false,
        "is_hfss": false,
        "smart_interest_behavior_enabled": null,
        "deep_bid_type": null,
        "purchased_reach": null,
        "audience_ids": [],
        "conversion_bid_price": {{conversion_bid_price}},
        "app_download_url": null,
        "rf_purchased_type": null,
        "optimization_goal": "CONVERSATION",
        "video_download_disabled": true,
        "feed_type": null,
        "conversion_window": null,
        "campaign_name": "{{campaign_name}}"
    }
}
(/code)
```

#### Optimization goal as Click
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
    "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "video_download_disabled": true,
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
        "adgroup_name": "{{adgroup_name}}",
        "deep_bid_type": null,
        "inventory_filter_enabled": false,
        "billing_event": "CPC",
        "video_download_disabled": true,
        "actions": [],
        "operation_status": "ENABLE",
        "deep_cpa_bid": 0,
        "network_types": [],
        "optimization_event": null,
        "brand_safety_partner": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "scheduled_budget": 0,
        "conversion_bid_price": 0,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "adgroup_id": "{{adgroup_id}}",
        "statistic_type": null,
        "comment_disabled": false,
        "location_ids": [
            "{{location_id}}"
        ],
        "languages": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "category_id": "0",
        "pacing": "PACING_MODE_SMOOTH",
        "conversion_window": null,
        "purchased_reach": null,
        "rf_purchased_type": null,
        "gender": "GENDER_UNLIMITED",
        "optimization_goal": "CLICK",
        "isp_ids": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "frequency_schedule": null,
        "pixel_id": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_name": "{{campaign_name}}",
        "secondary_optimization_event": null,
        "advertiser_id": "{{advertiser_id}}",
        "interest_keyword_ids": [],
        "delivery_mode": null,
        "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
        "purchased_impression": null,
        "rf_estimated_frequency": null,
        "auto_targeting_enabled": false,
        "next_day_retention": null,
        "keywords": null,
        "frequency": null,
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "rf_estimated_cpr": null,
        "feed_type": null,
        "app_type": null,
        "app_download_url": null,
        "budget": {{budget}},
        "create_time": "{{create_time}}",
        "creative_material_mode": "CUSTOM",
        "bid_price": 0,
        "adgroup_app_profile_page_state": null,
        "interest_category_ids": [],
        "operating_systems": [],
        "is_hfss": false,
        "category_exclusion_ids": [],
        "campaign_id": "{{campaign_id}}",
        "skip_learning_phase": false,
        "device_price_ranges": null,
        "schedule_infos": null,
        "bid_type": "BID_TYPE_NO_BID",
        "app_id": null,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "is_new_structure": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "modify_time": "{{modify_time}}",
        "audience_ids": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "bid_display_mode": "CPMV",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "ios14_quota_type": "UNOCCUPIED",
        "excluded_audience_ids": [],
        "share_disabled": false
    }
}
(/code)
```

## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {23%} | Requirement {20%} | Parameter {17%} | How to configure the parameter {40%} |
|---|---|---|---|
| Ad format | Any of the following types: <ul><li>Single video</li><li> Carousel images</li></ul> | `ad_format` | Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`CAROUSEL_ADS`<ul><li> To learn about how to create Standard Carousel Ads, see [Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).</li></ul></li></ul>|
| Spark ads | Enabled | `identity_type` | <ul><li> If `optimization_goal` is `CLICK`, set this field to `TT_USER` or `BC_AUTH_TT`. </li><li> If `optimization_goal` is `CONVERSATION`, set this field to `TT_USER`.</li></ul><br>To learn about how to create Spark Ads, refer to [Create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).<br><br>If you want to use an identity in TikTok Direct Messaging Ads, you need to have direct message permission for the identity. To confirm this, call [/identity/get/](https://business-api.tiktok.com/portal/docs?id=1740218420781057) or [/identity/info/](https://business-api.tiktok.com/portal/docs?id=1740218453385217) and check whether the returned `can_manage_message` is `true`.<ul><li>If the returned `can_manage_message` is `false` and `identity_type` is `TT_USER`, you can navigate to [the ad account user setting page](https://ads.tiktok.com/ac/page/settings). In the <b>Profile</b> section of the <b>Personal Info</b> tab, click <b>Edit access</b> to edit your permissions for the associated TikTok account. Ensure that you have <b>"Read and manage messages in your inbox"</b> permission for the TikTok account. </li><li>If the returned `can_manage_message` is `false` and `identity_type` is `BC_AUTH_TT`, you can ask the Business Center admin to grant you direct message permission for the TikTok account associated with the identity by using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121). Set `asset_type` to `TT_ACCOUNT` and ensure that the `tt_account_roles` value includes at least `POST` and `DIRECT_MESSAGE`. </li></ul> |
| Standard call-to-action | Send message | `call_to_action` | `SEND_MESSAGE` |
| Dynamic call-to-action | Not supported | `call_to_action_id` | Not supported |
| Interactive add-on | Not supported | `card_id` | Not supported |
| Destination | TikTok direct message | `landing_page_url`<br>`page_id`<br>`deeplink` <br> `deeplink_type`<br>`fallback_type` | Not supported |
| Destination<br>&nbsp;&nbsp;&#183;&nbsp;Welcome message | Enabled | `auto_message_id` | Pass a valid value.<br><br><ul><li> To obtain a list of welcome messages within your ad account, use [/creative/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1822106498804738).</li><li>To create a welcome message within your ad account, use [/creative/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1822106113771521).</li></ul> |
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
        "identity_type": "TT_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "tiktok_item_id":"{{tiktok_item_id}}",
        "call_to_action": "SEND_MESSAGE"
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
                "page_id": null,
                "creative_authorized": false,
                "display_name": "{{display_name}}",
                "identity_type": "TT_USER",
                "advertiser_id": "{{advertiser_id}}",
                "playable_url": "",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE",
                "viewability_postbid_partner": "UNSET",
                "adgroup_id": "{{adgroup_id}}",
                "ad_texts": null,
                "app_name": "{{app_name}}",
                "call_to_action": "SEND_MESSAGE",
                "click_tracking_url": null,
                "image_ids": [
                    ""
                ],
                "is_aco": false,
                "brand_safety_vast_url": null,
                "deeplink": "",
                "identity_id": "{{identity_id}}",
                "promotional_music_disabled": true,
                "creative_type": null,
                "music_id": null,
                "card_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "is_new_structure": true,
                "ad_name": "{{ad_name}}",
                "impression_tracking_url": null,
                "profile_image_url": "",
                "video_id": null,
                "brand_safety_postbid_partner": "UNSET",
                "optimization_event": "MESSAGE",
                "campaign_id": "{{campaign_id}}",
                "modify_time": "{{modify_time}}",
                "deeplink_type": "NORMAL",
                "viewability_vast_url": null,
                "tracking_pixel_id": 0,
                "avatar_icon_web_uri": "",
                "call_to_action_id": null,
                "ad_id": "{{ad_id}}",
                "create_time": "{{create_time}}",
                "landing_page_url": "",
                "vast_moat_enabled": false,
                "tiktok_item_id": "{{tiktok_item_id}}",
                "ad_text": "{{ad_text}}",
                "landing_page_urls": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "campaign_name": "{{campaign_name}}"
            }
        ]
    }
}
(/code)
```
