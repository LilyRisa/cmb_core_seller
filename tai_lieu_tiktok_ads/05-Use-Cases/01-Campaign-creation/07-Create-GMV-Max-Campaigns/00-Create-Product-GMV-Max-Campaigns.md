---
title: "Create Product GMV Max Campaigns"
doc_id: 1822009220448257
path: "Use Cases / Campaign creation / Create GMV Max Campaigns / Create Product GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Product GMV Max Campaigns

This article walks you through the steps to create Product GMV Max Campaigns.

# Introduction
Product GMV Max Campaigns automatically choose the products, creative assets and placement options that are most likely to encourage people to buy your products.

**You can use GMV Max API to create Product GMV Max Campaigns, and this helps streamline your campaign creation experience, and elevate operational efficiency and scalability.**

# Supported market
The following table lists the countries or regions where Product GMV Max Campaigns are available.
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
  - To create Product GMV Max Campaigns, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
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
## 1. Select a TikTok Shop and an ad account that are available for Product GMV Max Campaigns
**i. Obtain a TikTok Shop that is available for GMV Max Campaigns and ensure the ad account has GMV Max authorization for TikTok Shop.**

To do this, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true` and the returned `advertiser_id` within `exclusive_authorized_advertiser_info` is the same as the ad account that you want to use to create GMV Max Campaigns.

If the returned `advertiser_id` is not the same as the ad account that you want to use to create GMV Max Campaigns, use [/gmv_max/exclusive_authorization/create/](https://business-api.tiktok.com/portal/docs?id=1822001200356354) to grant the ad account exclusive GMV Max authorization for the TikTok Shop.

> <span style="color:DarkRed">**Important**</span><br>
> <ul><li>For each TikTok Shop, only one ad account can be authorized to create GMV Max Campaigns using the TikTok Shop for optimized revenue and sales campaigns.</li><li>If you grant an ad account exclusive GMV Max authorization for the TikTok Shop, the previous ad account loses access to GMV Max Campaigns that use the same TikTok Shop, and all existing GMV Max Campaigns that use the same TikTok Shop in the previous ad account are automatically paused.</li></ul>

**ii. Confirm that the products are not occupied by an enabled Product GMV Max Campaign.**

Each product within a TikTok Shop can only be used in one enabled Product GMV Max Campaign at a time.
- For Product GMV Max Campaigns that promote all products within a TikTok Shop, ensure that the TikTok Shop is only used in one enabled Product GMV Max Campaign.
  - To confirm that no enabled Product GMV Max Campaigns use all products within a TikTok Shop, call [/gmv_max/store/shop_ad_usage_check/](https://business-api.tiktok.com/portal/docs?id=1822001084174338) and check the returned `promote_all_products_allowed`.
  - If returned `promote_all_products_allowed` is `false`, you need to disable enabled Product GMV Max Campaigns using the same TikTok Shop. You can find these campaigns by using [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177). Set `gmv_max_promotion_types` to `["PRODUCT_GMV_MAX"]`, specify the ID of the TikTok Shop via `store_ids` , and set `primary_status` to `STATUS_DELIVERY_OK`. If you want to disable these campaigns, use [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178) and set `operation_status` to `DISABLE`.
- For Product GMV Max Campaigns that promote specific products within a TikTok Shop, ensure that each product is only used in one enabled Product GMV Max Campaign. You can create more than one enabled Product GMV Max Campaign for the same TikTok Shop, as long as these campaigns promote different products.
  - To confirm that no enabled Product GMV Max Campaigns use specific products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130). Set `ad_creation_eligible` to `GMV_MAX` and check whether the returned `gmv_max_ads_status values` for the `item_group_id` of the products that you want to promote are `UNOCCUPIED`.

**iii. (Optional) Confirm that the TikTok Shop and the products within the TikTok Shop are not occupied by enabled [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1750361719059457) or [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401).**

- Promoting all products from a TikTok Shop in a Product GMV Max Campaign will automatically pause or impact any existing enabled Video Shopping Ad Groups or Product Shopping Ad Groups using the same shop, regardless of which ad account the Video Shopping Ad Groups or Product Shopping Ad Groups were created in. 
<br>If you want to promote all products from a TikTok Shop in a Product GMV Max Campaign, you can call [/gmv_max/store/shop_ad_usage_check/](https://business-api.tiktok.com/portal/docs?id=1822001084174338) to confirm whether the TikTok Shop has existing enabled Video Shopping Ad Groups or Product Shopping Ad Groups. 

- Promoting specific products from a TikTok Shop in a Product GMV Max Campaign will automatically pause or impact any existing enabled Video Shopping Ads or Product Shopping Ads using the same products, regardless of which ad account the Video Shopping Ads or Product Shopping Ads were created in. 
<br>If you want to promote specific products from a TikTok Shop in a Product GMV Max Campaign, call [/gmv_max/store/shop_ad_usage_check/](https://business-api.tiktok.com/portal/docs?id=1822001084174338) to confirm whether the products have been occupied by existing enabled Video Shopping Ads or Product Shopping Ads. 
  - If the returned `is_running_custom_shop_ads` is `true`, you can call [/gmv_max/occupied_custom_shop_ads/list/](https://business-api.tiktok.com/portal/docs?id=1822001136924674) and set `occupied_asset_type` to `SPU` to find out the specific enabled Video Shopping Ads or Product Shopping Ads that are using these products. 

To learn more about the mutual exclusion rules for GMV Max Campaigns, see [Mutual exclusion rules for GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009058467842#item-link-Mutual%20exclusion%20rules%20for%20GMV%20Max%20Campaigns).

## 2. Create a Product GMV Max Campaign
Create a Product GMV Max Campaign by using [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089). Note that the following requirements must be met.

```xtable
| Setting{20%} | Requirement{25%} | Parameter{23%} | How to configure the parameter{32%} |
|---|---|---|---|
| TikTok Shop | Specify a TikTok Shop that is available for GMV Max Campaigns | `store_id`<br> `store_authorized_bc_id`| Specify a TikTok Shop that is available for Product GMV Max Campaigns as obtained from <a href="#item-link-1. Select a TikTok Shop and an ad account that are available for Product GMV Max Campaigns">Step 1</a>. |
| Ad account | Select the only ad account that is authorized to create GMV Max Campaigns for the TikTok Shop | `advertiser_id` | Specify the ad account that is exclusively authorized to create GMV Max Campaigns for the TikTok Shop as obtained from <a href="#item-link-1. Select a TikTok Shop and an ad account that are available for Product GMV Max Campaigns">Step 1</a>. |
| Promote products | Enabled | `shopping_ads_type` | `PRODUCT` (Product GMV Max Campaign) |
| Products | Any of the following options: <ul><li>(Recommended) Promote all products in TikTok Shop </li><li>Promote specific products in TikTok Shop </li></ul>| `product_specific_type`<br>`item_group_ids`  |<ul><li>To promote all products in the TikTok Shop, set `product_specific_type` to `ALL` and do not pass `item_group_ids`. <ul><li>To confirm whether you can promote all products in the TikTok Shop, call [/gmv_max/store/shop_ad_usage_check/](https://business-api.tiktok.com/portal/docs?id=1822001084174338) and check the returned `promote_all_products_allowed`. </li></ul></li><li>To promote specific products in the TikTok Shop, set `product_specific_type` to `CUSTOMIZED_PRODUCTS` and specify SPU IDs of the products via `item_group_ids`.<ul><li>To obtain the list of SPU IDs for products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130). Set `ad_creation_eligible` to `GMV_MAX` and select `item_group_id` values where `status` is `AVAILABLE` and `gmv_max_ads_status` is `UNOCCUPIED`.</li></ul></li></ul> |
| Targeting <br>&nbsp;&nbsp;&#183;&nbsp;Placements | Automatic placement | N/A | N/A <br><br>GMV Max Campaigns use automated placement selection. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. To retrieve the exact placement setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `placements`. |
| Targeting <br>&#183;&nbsp;Location |The country or region that you registered for your TikTok Shop.| N/A |N/A <br><br> By default, GMV Max Campaigns will be delivered to the registered country or region of the TikTok Shop. To retrieve the exact location targeting setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `location_ids`. |
| Targeting <br>&#183;&nbsp;Audience  | Users under 18 excluded |N/A |N/A <br><br>By default, GMV Max Campaigns will be delivered to all 18+ age groups. To retrieve the exact age group targeting setting of a GMV Max Campaign, use [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) and check the returned `age_groups`. |
| Optimization goal | Gross revenue | `optimization_goal` | `VALUE` |
| Product ROI target | Specify a valid ROI target | `roas_bid` | Pass a valid value. <br><br>To obtain the recommended ROI target, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `roas_bid`. |
| Target ROI budget | Specify a valid budget | `budget` | Pass a valid value.<br><br>To obtain the recommended budget, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `budget`. |
| Schedule type | Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul> | `schedule_type`<br>`schedule_start_time`<br>`schedule_end_time` | <ul><li>To run the campaign continuously after the scheduled start time, set `schedule_type` to `SCHEDULE_FROM_NOW` and specify `schedule_start_time`. Do not pass `schedule_end_time`.</li><li>To run the campaign between the scheduled start time and end time, set `schedule_type` to `SCHEDULE_START_END` and specify both `schedule_start_time` and `schedule_end_time`.</li></ul> |
| Creative mode | Any of the following types:<ul><li>Autoselect videos from up to three video sources (authorized posts, affiliate posts, and customized posts) and up to 20 TikTok accounts</li><li> Manually select up to 50 authorized posts, videos to be used in customized posts, or a combination of authorized posts and videos to be used in customized posts</li></ul>| `product_video_specific_type`<br>`identity_list`<br>`affiliate_posts_enabled`<br> `item_list` | <ul><li>To autoselect from three video sources (authorized posts, affiliate posts, and customized posts) and up to 20 TikTok accounts:<ul><li>Set `product_video_specific_type` to `AUTO_SELECTION` to autoselect from authorized posts and customized posts.</li><li>Specify up to 20 identities associated with TikTok accounts via `identity_list`. <ul><li>To obtain a list of identities available for a Product GMV Max Campaign using the same TikTok Shop, use [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882) and select identities with `product_gmv_max_available` as `true`.</li></ul></li><li>To autoselect from customized posts, your configuration depends on the customized post type:<ul><li>If you are using <b>campaign-level customized posts</b> (posts linked to a product during the Product GMV Max Campaign creation process), use `custom_anchor_video_list` to specify up to 200 videos for autoselection.</li><li>If you are using <b>shop-level customized posts</b> (posts directly linked to a product at the TikTok Shop level), leave `custom_anchor_video_list` unspecified. When the linked product is part of an active campaign, the video is automatically added to the campaign.</li><li><p><span style="color:DarkRed"><b>Important</b></span>: The campaign-level customized posts feature will be deprecated in the upcoming API version. To ensure a seamless integration, create shop-level customized posts using <a href="https://business-api.tiktok.com/portal/docs/create-shop-level-customized-tiktok-posts/v1.3" target="_blank">/gmv_max/creation/custom_anchor_video_list/create/</a> and retrieve the details of videos used in customized posts using <a href="https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3" target="_blank">/gmv_max/creation/custom_anchor_video_list/get/</a> and <a href="https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3" target="_blank">/gmv_max/creation/shop_video/video_anchors/</a>.</li></ul></li><li>Do not pass `item_list`. </li><li>`affiliate_posts_enabled` will default to `true` to enable affiliate posts for your Product GMV Max Campaign.</li></ul><li>To manually select up to 50 authorized posts, customized posts, or a combination of authorized posts and customized posts: <ul><li> Set `product_video_specific_type` to `CUSTOM_SELECTION` and specify up to 50 authorized posts, videos to be used in customized posts, or a combination of authorized posts and videos to be used in customized posts via `item_list`.</li><ul><li>If `item_list` includes videos intended for campaign-level customized posts, simultaneously specify these videos in `custom_anchor_video_list`.</li><li>If `item_list` includes videos intended for shop-level customized posts, you do not need to specify them in `custom_anchor_video_list`.</li><li>To obtain a list of TikTok posts available for a Product GMV Max Campaign using the same TikTok Shop, use [/gmv_max/video/get/](https://business-api.tiktok.com/portal/docs?id=1822001168512129).<br><p><span style="color:darkred"><b>Note</b></span>: If `/gmv_max/video/get/` returns an empty list, follow these steps to authorize posts with product links to the ad account:<br>1. Authorize posts to the ad account using [/tt_video/authorize/](https://business-api.tiktok.com/portal/docs?id=1738376435339265).<br>2. Check whether the posts contain product information (`spu_id`, `spu_name`, and `store_id`) using [/tt_video/info/](https://business-api.tiktok.com/portal/docs?id=1738376324021250) or [/tt_video/list/](https://business-api.tiktok.com/portal/docs?id=1738376465972226).</li></ul><li> Do not pass `identity_list`.</li></ul> |
| Campaign name | Specify a valid name | `campaign_name` | Pass a valid value |
```

### Example
#### Promote all products in TikTok Shop & autoselect videos from authorized posts, affiliate posts, and customized posts

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
    "shopping_ads_type": "PRODUCT",
    //promote all products using the "ALL" value
    "product_specific_type": "ALL",
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": "{{roas_bid}}",
    "budget": "{{budget}}",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "AUTO_SELECTION",
    "identity_list":[ 
        {
            "identity_id": "{{identity_id}}",    
            "identity_type": "TT_USER"
        },
        {
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        },
        {    
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        }
    ],
    //specify the videos to be auto-selected for customized posts
    //authorized posts will be automatically pulled
    //"affiliate_posts_enabled" will default to "true" to automatically pull affiliate posts 
    "custom_anchor_video_list": [
        {
            "identity_info": {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            "item_id": "{{item_id}}",
            "spu_id_list": [
                "{{spu_id}}"
            ]
        }
    ],
    "campaign_name":"{{campaign_name}}"
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
        "affiliate_posts_enabled": true,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "budget": "{{budget}}",
        "campaign_custom_anchor_video_id": "{{campaign_custom_anchor_video_id}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "custom_anchor_video_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ]
            }
        ],
        "deep_bid_type": "VO_MIN_ROAS",
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "item_list": [],
        "location_ids": [
            "6252001"
        ],
        "operation_status": "ENABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_specific_type": "ALL",
        "product_video_specific_type": "AUTO_SELECTION",
        "roas_bid": "{{roas_bid}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```

#### Promote all products in TikTok Shop & autoselect videos from authorized posts and affiliate posts

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
    "shopping_ads_type": "PRODUCT",
	//promote all products using the "ALL" value
    "product_specific_type": "ALL",
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "AUTO_SELECTION",
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TTS_TT",
                "store_id": "{{store_id}}"
            },
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
	//authorized posts will be automatically pulled
    //"affiliate_posts_enabled" will default to "true" to automatically pull affiliate posts 
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
		"affiliate_posts_enabled": true,
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
                "identity_id": "{{identity_id}}",
                "identity_type": "TTS_TT",
                "store_id": "{{store_id}}"
            },
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
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
        "product_specific_type": "ALL",
        "product_video_specific_type": "AUTO_SELECTION",
        "roas_bid": {{roas_bid}},
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```
#### Promote specific products in TikTok Shop & autoselect videos from authorized posts, affiliate posts, and customized posts

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
    "shopping_ads_type": "PRODUCT",
   //promote specific products using the "CUSTOMIZED_PRODUCTS" value and the parameter"item_group_ids" 
    "product_specific_type": "CUSTOMIZED_PRODUCTS",
    "item_group_ids":[ "{{item_group_id}}", "{{item_group_id}}"],
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": "{{roas_bid}}",
    "budget": "{{budget}}",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "AUTO_SELECTION",
    "identity_list":[
        {
            "identity_id": "{{identity_id}}",    
            "identity_type": "TT_USER"
        },
        {
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        },
        {    
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        }
    ],
    //specify the videos to be auto-selected for customized posts
    //authorized posts will be automatically pulled
    //"affiliate_posts_enabled" will default to "true" to automatically pull affiliate posts 
    "custom_anchor_video_list": [
        {
            "identity_info": {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            "item_id": "{{item_id}}",
            "spu_id_list": [
                "{{spu_id}}"
            ]
        }
    ],
    "campaign_name":"{{campaign_name}}"
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
        "affiliate_posts_enabled": true,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "budget": {{budget}},
        "campaign_custom_anchor_video_id": "{{campaign_custom_anchor_video_id}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "custom_anchor_video_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ]
            }
        ],
        "deep_bid_type": "VO_MIN_ROAS",
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "item_group_ids": [
            "{{item_group_id}}",
            "{{item_group_id}}"
        ],
        "item_list": [],
        "location_ids": [
            "6252001"
        ],
        "operation_status": "ENABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_specific_type": "CUSTOMIZED_PRODUCTS",
        "product_video_specific_type": "AUTO_SELECTION",
        "roas_bid": "{{roas_bid}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```

#### Promote specific products in TikTok Shop & autoselect videos from authorized posts and affiliate posts

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
    "shopping_ads_type": "PRODUCT",
	//promote specific products using the "CUSTOMIZED_PRODUCTS" value and the parameter"item_group_ids" 
    "product_specific_type": "CUSTOMIZED_PRODUCTS",
    "item_group_ids":["{{item_group_id}}","{{item_group_id}}"],
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "AUTO_SELECTION",
        "identity_list": [
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TTS_TT",
                "store_id": "{{store_id}}"
            },
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
	//authorized posts will be automatically pulled
    //"affiliate_posts_enabled" will default to "true" to automatically pull affiliate posts 
    "campaign_name":"{{campaign_name}}"
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
		"affiliate_posts_enabled": true,
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
                "identity_id": "{{identity_id}}",
                "identity_type": "TTS_TT",
                "store_id": "{{store_id}}"
            },
            {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "item_group_ids": [
            "{{item_group_id}}",
            "{{item_group_id}}"
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
        "product_specific_type": "CUSTOMIZED_PRODUCTS",
        "product_video_specific_type": "AUTO_SELECTION",
        "roas_bid": {{roas_bid}},
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{chedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```
#### Promote all products in TikTok Shop & manually select videos from authorized posts and customized posts

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
    "shopping_ads_type": "PRODUCT",
    //promote all products using the "ALL" value
    "product_specific_type": "ALL",
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": "{{roas_bid}}",
    "budget": "{{budget}}",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "CUSTOM_SELECTION",
    "identity_list":[
        {
            "identity_id": "{{identity_id}}",    
            "identity_type": "TT_USER"
        },
        {
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        },
        {    
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "BC_AUTH_TT"
        }
    ],
    //specify the manually selected authorized posts and videos to be used in customized posts using "item_list"
    // also repeat the videos to be used in customized posts using "custom_anchor_video_list"
    // For example, if you want to use one authorized post and one video to be used in a customized post, specify the two videos in "item_list" and repeat the one video for customized post in "custom_anchor_video_list"
    "item_list": [
        {
            "identity_info": {
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            },
            "item_id": "{{item_id}}",
            "spu_id_list": [
                "{{spu_id}}"
            ],
            "video_info": {
                "video_id": "{{video_id}}"
            }
        },
        {
            "identity_info": {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            "item_id": "{{item_id}}",
            "spu_id_list": [
                "{{spu_id}}"
            ],
            "video_info": {
                "video_id": "{{video_id}}"
            }
        }
    ],
    // specify the videos to be used in customized posts, which are repeated in "item_list", using "custom_anchor_video_list".
    "custom_anchor_video_list": [
        {
            "identity_info": {
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER"
            },
            "item_id": "{{item_id}}",
            "spu_id_list": [
                "{{spu_id}}"
            ],
            "video_info": {
                "video_id": "{{video_id}}"
            }
        }
    ],
    "campaign_name":"{{campaign_name}}"
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
        "budget": "{{budget}}",
        "campaign_custom_anchor_video_id": "{{campaign_custom_anchor_video_id}}",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "custom_anchor_video_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ]
            }
        ],
        "deep_bid_type": "VO_MIN_ROAS",
        "item_list": [
            {
                "identity_info": {
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "profile_image": "{{profile_image}}",
                    "user_name": "{{user_name}}"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 3240646,
                    "definition": "1080p",
                    "duration": 3.367,
                    "format": "mp4",
                    "fps": 30,
                    "height": 1920,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 1363907,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 1080
                }
            },
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "profile_image": "{{profile_image}}",
                    "user_name": "{{user_name}}"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 824497,
                    "definition": "540p",
                    "duration": 10.444,
                    "format": "mp4",
                    "fps": 30,
                    "height": 960,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 1076382,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 540
                }
            }
        ],
        "location_ids": [
            "6252001"
        ],
        "operation_status": "ENABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_specific_type": "ALL",
        "product_video_specific_type": "CUSTOM_SELECTION",
        "roas_bid": "{{roas_bid}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "store_id": "{{store_id}}"
    }
}
(/code)
```

#### Promote all products in TikTok Shop & manually select videos from authorized posts

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
    "shopping_ads_type": "PRODUCT",
    "product_specific_type": "ALL",
	//promote all products using the "ALL" value
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "product_video_specific_type": "CUSTOM_SELECTION",
	//specify the manually selected authorized posts using "item_list"
    "item_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TTS_TT",
                    "store_id": "{{store_id}}"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                }
            },
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TTS_TT",
                    "store_id": "{{store_id}}"
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                }
            }
        ],
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
        "item_list": [
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TTS_TT",
                    "profile_image": "{{profile_image}}",
                    "store_id": "{{store_id}}",
                    "user_name": ""
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "text": "{{text}}",
                "video_info": {
                    "bit_rate": 22697262,
                    "definition": "1080p",
                    "duration": 4.621,
                    "format": "mp4",
                    "fps": 30,
                    "height": 1080,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 13110506,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 1920
                }
            },
            {
                "identity_info": {
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TTS_TT",
                    "profile_image": "{{profile_image}}",
                    "store_id": "{{store_id}}",
                    "user_name": ""
                },
                "item_id": "{{item_id}}",
                "spu_id_list": [
                    "{{spu_id}}"
                ],
                "text": "",
                "video_info": {
                    "bit_rate": 9696015,
                    "definition": "1080p",
                    "duration": 2.114,
                    "format": "mp4",
                    "fps": 60,
                    "height": 1080,
                    "preview_url": "{{preview_url}}",
                    "signature": "{{signature}}",
                    "size": 2562172,
                    "video_cover_url": "{{video_cover_url}}",
                    "video_id": "{{video_id}}",
                    "width": 1920
                }
            }
        ],
        "location_ids": [
            "1562822"
        ],
        "operation_status": "ENABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_specific_type": "ALL",
        "product_video_specific_type": "CUSTOM_SELECTION",
        "roas_bid": 2,
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "PRODUCT",
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
- [About Product GMV Max](https://ads.tiktok.com/help/article/about-product-gmv-max)
- [GMV Max Guidelines](https://ads.tiktok.com/help/article/gmv-max-guidelines)
- [Troubleshoot account settings for GMV Max](https://ads.tiktok.com/help/article/troubleshoot-account-settings-for-gmv-max-in-tiktok-ads-manager)
- [Tips to measure product and video quality in your Product GMV Max Campaign](https://ads.tiktok.com/help/article/tips-to-measure-product-and-video-quality-in-your-product-gmv-max-campaign)
- [About attribution for GMV Max](https://ads.tiktok.com/help/article/about-attribution-for-gmv-max)

