---
title: "(To be deprecated) Create Legacy Smart+ App Campaigns"
doc_id: 1814122492598274
path: "Use Cases / Campaign creation / (To be deprecated) Create a Legacy Smart+ Campaign / (To be deprecated) Create Legacy Smart+ App Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Create Legacy Smart+ App Campaigns

This article introduces how to create Smart+ App Campaigns.

## Steps

Call [/campaign/spc/create/](https://ads.tiktok.com/marketing_api/docs?id=1767334204047362) to create Smart+ App Campaigns (`objective_type` = `APP_PROMOTION`). 

> <span style="color:DodgerBlue">**Note**</span><br>
> Smart Performance App Campaigns have been automatically upgraded to Smart+ App Campaigns. 

The following table lists only some related parameters. To find a complete list of parameters, refer to the [/campaign/spc/create/](https://ads.tiktok.com/marketing_api/docs?id=1767334204047362) endpoint article and the following code example.  

```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Advertising objective | App promotion | `objective_type` | `APP_PROMOTION` |
| App promotion type | App install | `app_promotion_type` | `APP_INSTALL` |
| Optimization location<br> (Promotion type) | One of the following types: <ul><li>Android App</li><li> iOS App </li></ul>| `promotion_type`<br>`app_id` | <ul><li>To specify optimization location as Android App: <ul><li>Set `promotion_type` to `APP_ANDROID`.</li><li>Specify an Android App via `app_id`.</li></ul></li><li>To specify optimization location as iOS App:<ul><li>Set `promotion_type` to `APP_IOS`.</li><li>Specify an iOS App via `app_id`. </li></ul></li></ul>To obtain the list of Apps under your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786). <ul><li>For Android Apps, the returned `platform` will be `ANDROID`.</li><li>For iOS Apps, the returned `platform` will be `IOS`.</li></ul>|
| Placement | <ul><li>For Android Apps, use <b>Automatic Placement</b>.</li><li>For iOS Apps, use <b>Automatic Placement</b>.</li></ul> | `placement_type`<br> `placements` | <ul><li>If `promotion_type` is `APP_ANDROID`, and do not pass `placement_type` and `placements`. <ul><li>The system will automatically configure placements for your Smart+ Android App Campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately. </li><li>To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).</li></ul></li><li>If `promotion_type` is `APP_IOS`, do not pass `placement_type` and `placements`.<ul><li>Starting June 30th, 2025, the system will automatically configure placements for your Smart+ iOS App Campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided after June 30th, 2025, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately. </li><li>To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).</li></ul></li></ul>|
```


### Example
**Request**
``` xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "operation_status": "ENABLE",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_INSTALL",
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "APP_ANDROID",
    "app_id": "{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "INSTALL",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "identity_type": "CUSTOMIZED_USER",
    "identity_id": "{{identity_id}}",
    "media_info_list": [
        {
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}",
                    "file_name": "{{file_name}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ]
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}"
}'
(/code)
```

**Response**
``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "pixel_id": null,
        "carousel_image_index": null,
        "deeplink": "",
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "gender": "GENDER_UNLIMITED",
        "special_industries": null,
        "location_ids": [
            "6252001"
        ],
        "schedule_end_time": "{{schedule_end_time}}",
        "excluded_audience_ids": null,
        "shopping_ads_fallback_type": null,
        "promotion_type": "APP_ANDROID",
        "product_ids": null,
        "automatic_add_on_enabled": null,
        "budget": {{budget}},
        "skip_learning_phase": true,
        "impression_tracking_url": null,
        "share_disabled": false,
        "deep_bid_type": "DEFAULT",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "conversion_bid_price": null,
        "is_advanced_dedicated_campaign": false,
        "promotion_website_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "attribution_event_count": "ONCE",
        "campaign_app_profile_page_state": "UNSET",
        "product_set_id": null,
        "roas_bid": null,
        "call_to_action_id": "{{call_to_action_id}}",
        "app_promotion_type": "APP_INSTALL",
        "languages": [],
        "is_smart_performance_campaign": true,
        "call_to_action_list": [],
        "click_tracking_url": null,
        "modify_time": "{{modify_time}}",
        "bid_price": null,
        "media_info_list": [
            {
                "media_info": {
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": "{{file_name}}"
                    },
                    "identity_type": null,
                    "aigc_disclosure_type": null,
                    "music_info": null,
                    "tiktok_item_id": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "identity_id": null
                }
            }
        ],
        "catalog_creative_toggle": null,
        "advertiser_id": "{{advertiser_id}}",
        "app_id": "{{app_id}}",
        "identity_type": "CUSTOMIZED_USER",
        "card_list": null,
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "page_list": null,
        "scheduled_budget": 0,
        "bid_type": "BID_TYPE_NO_BID",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "app_download_url": "{{app_download_url}}",
        "view_attribution_window": "ONE_DAY",
        "create_time": "{{create_time}",
        "dayparting": 
"111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "product_specific_type": null,
        "catalog_authorized_bc_id": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "comment_disabled": false,
        "campaign_type": "REGULAR_CAMPAIGN",
        "deeplink_type": null,
        "campaign_id": "{{campaign_id}}",
        "product_source": null,
        "blocked_pangle_app_ids": null,
        "carousel_image_labels": null,
        "billing_event": "OCPM",
        "campaign_name": "{{campaign_name}}",
        "app_name": "{{app_name}}",
        "optimization_goal": "INSTALL",
        "video_download_disabled": false,
        "operation_status": "ENABLE",
        "click_attribution_window": "SEVEN_DAYS",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "landing_page_urls": null,
        "spc_audience_age": "18+",
        "app_type": "APP_ANDROID",
        "spc_type": "UNSET",
        "web_all_in_one_catalog_status": null,
        "optimization_event": "ACTIVE",
        "objective_type": "APP_PROMOTION",
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "exclude_age_under_eighteen": true,
        "identity_id": "{{identity_id}}",
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "catalog_id": null
    }
}
(/code)
```
