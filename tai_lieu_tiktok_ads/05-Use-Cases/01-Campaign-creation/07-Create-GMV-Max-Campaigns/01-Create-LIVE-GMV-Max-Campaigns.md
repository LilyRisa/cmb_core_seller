---
title: "Create LIVE GMV Max Campaigns"
doc_id: 1822009242546258
path: "Use Cases / Campaign creation / Create GMV Max Campaigns / Create LIVE GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create LIVE GMV Max Campaigns

This article walks you through the steps to create LIVE GMV Max Campaigns.


# Introduction
LIVE GMV Max Campaigns optimize liveroom traffic to get the highest gross revenue for your LIVE event. 

**You can use GMV Max API to create LIVE GMV Max Campaigns, and this helps streamline your campaign creation experience, and elevate operational efficiency and scalability.**

# Supported market
The following table lists the countries or regions where LIVE GMV Max Campaigns are available.
```xtable
| Country or region code {30%}| Market {60%}|
|---|---|
| `ID` | Indonesia |
| `MY` | Malaysia |
| `PH` | the Philippines |
| `SG` | Singapore |
| `TH` | Thailand |
| `US` | the United States |
| `VN` | Vietnam |
```

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create LIVE GMV Max Campaigns, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- Creating a GMV Max Campaign requires setting up a TikTok Shop in Business Center first. 
  1. Add the TikTok Shop to the Business Center. You can either [create a TikTok Shop in Seller Center](https://ads.tiktok.com/help/article/set-up-tiktok-shop-using-tiktok-seller-center) and [add a TikTok Shop to the Business Center as Shop admin](https://ads.tiktok.com/help/article/add-tiktok-shop-from-business-center) or[ request access to the others' TikTok Shop in Business Center](https://ads.tiktok.com/help/article/requesting-tiktok-shop-access-business-center).
  2. Ensure that you have admin permission for the Business Center. To do this, call [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). 
    - If the Business Center is not returned in the response, you are not a member of the Business Center. Ask one admin for the Business Center to invite you to join the Business Center as a member and grant admin access using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505).
    - If the returned `user_role` for the Business Center is `STANDARD`, you are a standard member of the Business Center. Ask one admin for the Business Center to update your permission for the Business Center to admin using [/bc/member/update/](https://business-api.tiktok.com/portal/docs?id=1739696704424961).
  3. Ensure that you have management access to the TikTok Shop that you want to use to create GMV Max Campaigns. To do this, call [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `TIKTOK_SHOP`. 
<br>If the returned `store_role` for the TikTok Shop is not `AD_PROMOTION`, you don't have management access to the TikTok Shop. Ask one admin for the Business Center to share the TikTok Shop with you and grant the TikTok Shop management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). The Business Center admin needs to specify `asset_type` as `TIKTOK_SHOP` and `store_role` as `AD_PROMOTION`.
  4. Ensure that you have operator or admin access to the ad account that you want to use to create GMV Max Campaigns. To do this, call [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `ADVERTISER`. 
<br>If the returned `advertiser_role` for the ad account is not `OPERATOR` or `ADMIN`, you don't have operator or admin access to the ad account. Ask one admin for the Business Center to share the ad account with you and grant the operator or admin access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). The Business Center admin needs to specify `asset_type` as `ADVERTISER` and `advertiser_role` as `ADMIN` or `OPERATOR`.
  
# Steps
## 1. Select a TikTok Shop and an ad account that are available for LIVE GMV Max Campaigns

Obtain a TikTok Shop that is available for GMV Max Campaigns and ensure the ad account has GMV Max authorization for TikTok Shop. 

To do this, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true` and the returned `advertiser_id` within `exclusive_authorized_advertiser_info` is the same as the ad account that you want to use to create GMV Max Campaigns.

If the returned `advertiser_id` is not the same as the ad account that you want to use to create GMV Max Campaigns, use [/gmv_max/exclusive_authorization/create/](https://business-api.tiktok.com/portal/docs?id=1822001200356354) to grant the ad account exclusive GMV Max authorization for the TikTok Shop.

> <span style="color:DarkRed">**Important**</span><br>
> <ul><li>For each TikTok Shop, only one ad account can be authorized to create GMV Max Campaigns using the TikTok Shop for optimized revenue and sales campaigns.</li><li>If you grant an ad account exclusive GMV Max authorization for the TikTok Shop, the previous ad account loses access to GMV Max Campaigns that use the same TikTok Shop, and all existing GMV Max Campaigns that use the same TikTok Shop in the previous ad account are automatically paused.</li></ul>

## 2. Ensure that you have a LIVE source identity is available for LIVE GMV Max Campaigns
To obtain such an identity, call [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882) and check whether the returned `live_gmv_max_available` is `true`.

If the returned `live_gmv_max_available` is `false` and `unavailable_reason` is `OCCUPIED`, the identity is occupied by an enabled Live Shopping Ad Group or Live GMV Max Campaign. You can determine the occupied ad or campaign type based on `is_running_custom_shop_ads`.
- If `is_running_custom_shop_ads` is` false`, the identity is occupied by an enabled Live GMV Max Campaign and you need to disable the campaign. To find the campaign, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177). Set `gmv_max_promotion_types` to `["LIVE_GMV_MAX"]`, specify the ID of the TikTok Shop via `store_ids` , and set `primary_status` to `STATUS_DELIVERY_OK`. To disable the campaign, use [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178) and set `operation_status` to `DISABLE`.
- If `is_running_custom_shop_ads` is `true`, the identity is occupied by an enabled Live Shopping Ad Group. To find the ad group, call [/gmv_max/occupied_custom_shop_ads/list/](https://business-api.tiktok.com/portal/docs?id=1822001136924674) and set `occupied_asset_type` to `IDENTITY_TT_USER`, `IDENTITY_BC_AUTH_TT`, or `IDENTITY_TTS_TT`.

> <span style="color:DarkRed">**Important**</span><br>
>  <ul><li>Using an identity associated with the official TikTok account of a TikTok Shop in a LIVE GMV Max Campaign will automatically pause or impact any existing enabled LIVE Shopping Ad Groups using the same identity, regardless of which ad account the LIVE Shopping Ad Groups were created in. </li><li>If an enabled Live Shopping Ad Group has occupied an identity associated with a TikTok account that is not an official TikTok account of a TikTok Shop, you cannot use the identity in an enabled LIVE GMV Max Campaign. You will need to manually pause the existing enabled LIVE Shopping Ad Group using the same identity first. To disable the ad group, use [/adgroup/status/update/](https://business-api.tiktok.com/portal/docs?id=1739591716326402) and set `operation_status` to `DISABLE`.</li><li>If the shop's official TikTok account is disconnected from the shop, is disconnected from TikTok Business Center, or is disconnected as the TikTok Business Account of the ad account, the shop's LIVE GMV Max Campaigns using the identity associated with this TikTok account may be paused.</li></ul>

## 3. Create a LIVE GMV Max Campaign
Create a LIVE GMV Max Campaign by using [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089). Note that the following requirements must be met.

```xtable
| Setting{20%} | Requirement{25%} | Parameter{23%} | How to configure the parameter{32%} |
|---|---|---|---|
| TikTok Shop | Specify a TikTok Shop that is available for GMV Max Campaigns | `store_id`<br> `store_authorized_bc_id`| Specify a TikTok Shop that is available for Product GMV Max Campaigns as obtained from <a href="#item-link-1. Select a TikTok Shop and an ad account that are available for LIVE GMV Max Campaigns">Step 1</a>. |
| Ad account | Select the only ad account that is authorized to create GMV Max Campaigns for the TikTok Shop | `advertiser_id` | Specify the ad account that is exclusively authorized to create GMV Max Campaigns for the TikTok Shop as obtained from <a href="#item-link-1. Select a TikTok Shop and an ad account that are available for LIVE GMV Max Campaigns">Step 1</a>. |
| Promote LIVE| Enabled | `shopping_ads_type` | `LIVE` (LIVE GMV Max Campaign)|
|LIVE source| Select a TikTok account to get the LIVE you want to promote.|`identity_list`| Specify one identity associated with a TikTok account via `identity_list` as obtained from <a href="#item-link-2. Ensure that you have a LIVE source identity is available for LIVE GMV Max Campaigns">Step 2</a>.|
| Targeting <br>&nbsp;&nbsp;&#183;&nbsp;Placements | Automatic placement | N/A | N/A <br><br>GMV Max Campaigns use automated placement selection. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. To retrieve the exact placement setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `placements`. |
| Targeting <br>&#183;&nbsp;Location |The country or region that you registered for your TikTok Shop.| N/A |N/A <br><br> By default, GMV Max Campaigns will be delivered to the registered country or region of the TikTok Shop. To retrieve the exact location targeting setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `location_ids`. |
| Targeting <br>&#183;&nbsp;Audience  | Users under 18 excluded |N/A |N/A <br><br>By default, GMV Max Campaigns will be delivered to all 18+ age groups. To retrieve the exact age group targeting setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `age_groups`. |
| Optimization goal | Gross revenue | `optimization_goal` | `VALUE` |
| LIVE ROI target | Specify a valid ROI target | `roas_bid` | Pass a valid value. <br><br>To obtain the recommended ROI target, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `roas_bid`. |
| Target ROI budget | Specify a valid budget | `budget` | Pass a valid value.<br><br>To obtain the recommended budget, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `budget`. |
| Schedule type | Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul> | `schedule_type`<br>`schedule_start_time`<br>`schedule_end_time` | <ul><li>To run the campaign continuously after the scheduled start time, set `schedule_type` to `SCHEDULE_FROM_NOW` and specify `schedule_start_time`. Do not pass `schedule_end_time`.</li><li>To run the campaign between the scheduled start time and end time, set `schedule_type` to `SCHEDULE_START_END` and specify both `schedule_start_time` and `schedule_end_time`.</li></ul> |
| Campaign name | Specify a valid name | `campaign_name` | Pass a valid value |
```

### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "request_id": "{{request_id}}",
    "advertiser_id": "{{advertiser_id}}",
    "store_id": "{{store_id}}",
    "store_authorized_bc_id": "{{store_authorized_bc_id}}",
    "shopping_ads_type": "LIVE",
    "identity_list": [
        {
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT",
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
        }
    ],
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
    "budget": {{budget}},
    "schedule_type": "{{schedule_type}}",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "campaign_name": "{{campaign_name}}"
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
        "advertiser_id": "{{advertiser_id}}",
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "budget": {{budget}},
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "custom_anchor_video_list": [],
        "deep_bid_type": "VO_MIN_ROAS",
        "identity_list": [
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "item_list": [],
        "location_ids": [
            "1562822"
        ],
        "operation_status": "ENABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "roas_bid": {{roas_bid}},
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "LIVE",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```

# Next steps
Once you have created GMV Max Campaigns, you can use [/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601) to run GMV Max Campaign reports.


# Related docs
- [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465)
- [About LIVE GMV Max](https://ads.tiktok.com/help/article/about-live-gmv-max)
- [GMV Max Guidelines](https://ads.tiktok.com/help/article/gmv-max-guidelines)
- [Troubleshoot your LIVE GMV Max Campaign](https://ads.tiktok.com/help/article/troubleshoot-your-live-gmv-max-campaign)
- [Best practices for LIVE GMV Max](https://ads.tiktok.com/help/article/best-practices-for-live-gmv-max)
