---
title: "Create Video Shopping Ads with products from Showcase"
doc_id: 1759232259360770
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / Create Video Shopping Ads with products from Showcase"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Video Shopping Ads with products from Showcase

Showcase is a feature on TikTok that allows customers to shop directly from a seller’s or a creator’s account. You can find the Showcase tab when tapping the shopping bag icon on the profile page of a TikTok account. Showcase displays products from a single TikTok Shop or multiple TikTok Shops. You can learn more about how to use Showcase [here](https://ads.tiktok.com/help/article?aid=10013245&redirected=1).


This article introduces how to create Video Shopping Ads with products from Showcase.
> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li> The flags of <code>Required</code>/ <code>Conditional</code> in the following parameter tables are only applicable for Video Shopping Ads with products from Showcase.</li><li> Creating Video Shopping Ads with products from Showcase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li></ul>

## 1.Create a campaign
Create a campaign using `/campaign/create/`. The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating campaigns, refer to [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).

> <span style="color:DodgerBlue">**Note**</span><br>
> Currently, Campaign Budget Optimization is not supported for Shopping Ads with products from Showcase.

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| advertiser_id{Required} | string | Advertiser ID |
| objective_type{Required} | string | Advertising objective. <br>Use `PRODUCT_SALES` for Shopping Ads. |
| budget_mode{+Conditional} | string | How you'd like to set your budget, and for example you can set daily budget. Requied when `objective_type` is <strong>NOT</strong> `RF_REACH`. When `objective_type` is `RF_REACH`, `budget_mode` only supports `BUDGET_MODE_INFINITE`.<br>To learn about how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
| budget{+Conditional} | float | Campaign budget. Required when `budget_mode` is `BUDGET_MODE_DAY` or `BUDGET_MODE_DYNAMIC_DAILY_BUDGET` or `BUDGET_MODE_TOTAL`.  <br>To learn about the minimum budget and how to set budget types, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
| campaign_name{Required} | string | Campaign name. It can contain up to 512 characters. Emoji is not supported. <br><p><span style="color:darkred"><b>Note</b></span>: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character. |
| campaign_product_source {Required} | string | Product source of the Video Shopping campaign. <br>Set this field to `STORE` (TikTok Shop, or TikTok Showcase).   |
```

### Example
#### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "objective_type": "PRODUCT_SALES",
	"campaign_product_source": "STORE",
    "campaign_name": "{{campaign_name}}",
    "budget": 5000
}'
(/code)
```
#### Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget": 5000.0,
        "campaign_type": "REGULAR_CAMPAIGN",
		"campaign_product_source": "STORE",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_app_profile_page_state": "UNSET",
        "roas_bid": 0.0,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "objective": "LANDING_PAGE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "create_time": "{{create_time}}",
        "deep_bid_type": null,
        "is_new_structure": true,
        "operation_status": "ENABLE",
        "modify_time": "{{modify_time}}",
        "objective_type": "PRODUCT_SALES",
        "is_smart_performance_campaign": false
    }
}
(/code)
```
## 2.Create a valid Showcase identity and get necessary permissions
You will use the identity of the Showcase to create a Video Shopping Ad Group, and need to ensure that the following prerequisites are all met: 
- Bind the Showcase that you want to use for creating the Video Shopping Ad Group to a <strong>TikTok User</strong> or <strong>TikTok Account User in Business Center</strong> identity. <br>When the <strong>TikTok User</strong> or <strong>TikTok Account User in Business Center</strong> identity is created, the Showcase of the TikTok account is bound to that identity. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to learn more about different identity types, and see [Create Spark Ads](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298) to find out how you can create the <strong>TikTok User</strong> or <strong>TikTok Account User in Business Center</strong> identity and use the identity to create Spark Ads. Note that Video Shopping Ads created with products from Showcases are all Spark Ads.
- Your ad account has obtained the permission of the Showcase. <br>See [here](https://ads.tiktok.com/help/article/how-to-get-showcase-permission?lang=en) to learn about how to get Showcase permissions.
- Your ad account has been allowlisted to create Video Shopping Ads with products from Showcase. <br>If you would like to access the allowlist-only feature, please contact your TikTok representative.

## 3.Get details of the Showcase
Note that the details of the Showcase will be used during ad group creation and ad creation.

i. Call [/showcase/identity/get/](https://ads.tiktok.com/marketing_api/docs?id=1759233549899778) to get the identity of the Showcase.<br>
ii. Call [/showcase/region/get/](https://ads.tiktok.com/marketing_api/docs?id=1759233561597954) to get the available country or region that the Showcase can target.<br>
iii. Call [/showcase/product/get/](https://ads.tiktok.com/marketing_api/docs?id=1759233576199169) to get the Showcase products that can be used for the country or region you want to target.

## 4.Create an ad group

Create an ad group using `/adgroup/create/`.  The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating ad groups, refer to [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).

<br>Set the fields `identity_id`, `identity_type`, `location_ids` and optionally `identity_authorized_bc_id` according to the results from Step 3.i-ii.
><span style="color:DodgerBlue">**Note**</span><br>
><ul><li>Automated Creative Optimization is not supported for Shopping Ads.</li><li>The following parameters will be ignored if passed in:</li><ul><li><code>catalog_id</code> </li><li><code>product_set_id</code></li><li><code>catalog_authorized_bc_id</code></li><li><code>store_id</code></li><li><code>store_authorized_bc_id</code></li></ul></ul>

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| promotion_type{Required} | string | Promotion type and you can decide where you'd like to promote your products using this field. <br>Use `VIDEO_SHOPPING` for Video Shopping Ads. |
|placement_type |string|The placement strategy that decides where your ads will be shown. <br><br>Enum values: <ul><li>`PLACEMENT_TYPE_AUTOMATIC`: Automatic placement. TikTok's ad system will use smart calculations to give you the best combination of ad placements across all apps.</li><li>`PLACEMENT_TYPE_NORMAL`: Select placement. By selecting your placement manually, you'll be able to choose the apps to deliver your ads on. </li></ul><br>Default value: `PLACEMENT_TYPE_NORMAL`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Pangle placement (`PLACEMENT_PANGLE`) for Video Shopping Ads for Shop (`product_source` as `STORE` or `SHOWCASE`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. Once you are allowlisted for this feature, you can also use automatic placement (`PLACEMENT_TYPE_AUTOMATIC`) for Video Shopping Ads for Shop. |
|placements {+Conditional}|string[]|<ul><li>When `placement_type` is `PLACEMENT_TYPE_NORMAL`, this field is required.</li><li>When `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`, this field is ignored and overwritten to the combination of ad placements that the TikTok ad system selects for your ad.</li></ul><br>The apps where you want to deliver your ads.  <br><br>Enum values: `PLACEMENT_TIKTOK`, `PLACEMENT_PANGLE`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Pangle placement (`PLACEMENT_PANGLE`) for Video Shopping Ads for Shop is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| product_source {Required} | string | Product source where you want to get products for promotion. <br> Enum values: `UNSET`, `CATALOG` (Catalog), `STORE` (TikTok Shop), `SHOWCASE` (TikTok Showcase). <br>You need to specify this field as `SHOWCASE`. <br><p><span style="color:darkred"><b>Note</b></span>: If `campaign_product_source` is not set at the campaign level, and you have at least one historical ad group with `product_source` set as `CATALOG` and one with `product_source` set as `STORE` or `SHOWCASE` under the same campaign at the same time, you cannot create new ad groups under the campaign.|
| shopping_ads_type{Required}  | string | Shopping ads type. Enum values: `VIDEO` (Video Shopping Ads), `LIVE` (Live Shopping Ads). <br>Use `VIDEO` for Video Shopping Ads. |
| identity_id {Required} | string | The ID of the identity that the Showcase that you want to use for your ad is bound to. <br>You can use [/showcase/identity/get/](https://ads.tiktok.com/marketing_api/docs?id=1759233549899778) to get the identity of a Showcase. |
| identity_type {Required} | string | Identity type. <br>The allowed enum values: `TT_USER` (TikTok User), `BC_AUTH_TT` (TikTok Account User in Business Center). See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. |
| identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
| location_ids {Required} | string[] | IDs of the locations that you want to target. To get the list of location IDs, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689). <br>The targeted locations should belong to the country or region that the Showcase can target. <br> To get the available country or region that the Showcase can target, use the [/showcase/region/get/](https://ads.tiktok.com/marketing_api/docs?id=1759233561597954)endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> If you add US as your target location, then you can not remove US after ad group creation.</li></ul> |
| optimization_goal{Required} | string | The measurable results you'd like to drive with your ads. <br> The allowed enum values: `CLICK` (Click), `CONVERT` (Conversion), `VALUE` (Value). |
| optimization_event{+Conditional} | string | Required when `optimization_goal` = `CONVERT`. <br>Conversion event for the ad group. The allowed enum values: `INITIATE_ORDER` (Initiate Checkout), `SHOPPING` (Complete Payment). <br><p><span style="color:darkred"><b>Note</b></span>: When `optimization_goal` =`CLICK` or `VALUE` , you don't need to set this field. |
| billing_event{Required} | string | Events that you want to pay for. The allowed enum values: `CPC`, `OCPM`. <ul><li> When `optimization_goal` is `CLICK`, set this field to `CPC`. </li><li> When `optimization_goal` is `CONVERT` or `VALUE`, set this field to `OCPM`. </li></ul> |
| bid_type {Required} | string | Bidding strategy that determines how the system manages your cost per result, spends your budget, and how it delivers ads. <br><br>Enum values: <ul><li>`BID_TYPE_CUSTOM`: Keep the average cost per result around the stated amount. This bidding strategy is called <b>Target CPA</b> or <b>Cost Cap</b> in TikTok Ads Manager. </li><li>`BID_TYPE_NO_BID`: Aim to spend your entire budget to get the most results. This bidding strategy is called <b>Maximum Delivery</b> in TikTok Ads Manager.</li></ul> |
| deep_bid_type{+Conditional} | string | Required when `optimization_goal` is `VALUE`.<br><br> Bidding strategy for [Value-Based Optimization](https://business-api.tiktok.com/portal/docs?id=1739381743067137) that determines how the system manages your cost per result, spends your budget, and how it delivers ads. <br><br>Enum values: <ul><li>`VO_MIN_ROAS`: Keep the average ROAS around the stated amount. This bidding strategy is called <b>Target ROAS</b> in TikTok Ads Manager. </li><li>`VO_HIGHEST_VALUE`: Aim to spend your entire budget to get the most gross revenue. This bidding strategy is called <b>Highest Gross Revenue</b> in TikTok Ads Manager. </li></ul>|
| roas_bid{+Conditional} | float | Required when `deep_bid_type` is `VO_MIN_ROAS`. <br> ROAS goal for Value Optimization. <br>Value range: 0.01-1000. |
```

### Example
#### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "adgroup_name": "{{adgroup_name}}",
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "product_source": "SHOWCASE",
    "promotion_type": "VIDEO_SHOPPING",
    "shopping_ads_type":"VIDEO",
    "identity_type": "BC_AUTH_TT", 
    "identity_id": "{{identity_id}}",
    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "{{location_ids}}"
    ],
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",
    "schedule_end_time": "{{schedule_end_time}}",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_type": "SCHEDULE_START_END",
    "shopping_ads_retargeting_type": "OFF",
    "targeting_expansion": {
        "enable_expansion": false
    },
    "video_download_disabled": true,
    "automated_targeting": "OFF",
    "budget": 400.0,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "gender": "GENDER_UNLIMITED",
    "ios14_quota_type": "UNOCCUPIED",
    "operation_status": "ENABLE",
    "pacing": "PACING_MODE_SMOOTH",
}'
(/code)
```
#### Response
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "gender": "GENDER_UNLIMITED",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "app_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "actions": [],
        "promotion_type": "VIDEO_SHOPPING",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "comment_disabled": false,
        "billing_event": "CPC",
        "age_groups": null,
        "rf_estimated_cpr": null,
        "frequency": null,
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "rf_purchased_type": null,
        "keywords": null,
        "creative_material_mode": "CUSTOM",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "location_ids": [
            "{{location_ids}}"
        ],
        "identity_id": "{{identity_id}}",
        "skip_learning_phase": false,
        "audience_ids": [],
        "search_result_enabled": false,
        "adgroup_name": "{{adgroup_name}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "adgroup_id": "{{adgroup_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "deep_cpa_bid": 0.0,
        "operation_status": "ENABLE",
        "campaign_id": "{{campaign_id}}",
        "optimization_event": null,
        "shopping_ads_type": "VIDEO",
        "bid_price": 0.0,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_hfss": false,
        "deep_bid_type": null,
        "conversion_bid_price": 0.0,
        "frequency_schedule": null,
        "operating_systems": [],
        "category_id": "0",
        "delivery_mode": null,
        "create_time": "{{create_time}}",
        "statistic_type": null,
        "interest_category_ids": [],
        "video_download_disabled": true,
        "product_source": "SHOWCASE",
        "auto_targeting_enabled": false,
        "is_new_structure": true,
        "modify_time": "{{modify_time}}",
        "purchased_reach": null,
        "feed_type": null,
        "purchased_impression": null,
        "adgroup_app_profile_page_state": null,
        "conversion_window": null,
        "bid_type": "BID_TYPE_NO_BID",
        "languages": [],
        "optimization_goal": "CLICK",
        "schedule_infos": null,
        "budget": 400.0,
        "app_download_url": null,
        "device_price_ranges": null,
        "share_disabled": false,
        "secondary_optimization_event": null,
        "next_day_retention": null,
        "brand_safety_partner": null,
        "inventory_filter_enabled": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "pacing": "PACING_MODE_SMOOTH",
        "schedule_start_time": "{{schedule_start_time}}",
        "identity_type": "BC_AUTH_TT",
        "ios14_quota_type": "UNOCCUPIED",
        "app_id": null,
        "excluded_audience_ids": [],
        "network_types": [],
        "interest_keyword_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "scheduled_budget": 0.0,
        "rf_estimated_frequency": null,
        "bid_display_mode": "CPMV",
        "pixel_id": null,
        "campaign_name": "{{}}"
    }
}
(/code)
```
## 5.Create an ad
Create an ad using `/ad/create/`. The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating ads, refer to [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).

<br>Set the fields `showcase_products`, `item_group_id`, `store_id`, `catalog_id` according to the results from Step 3.iii.
><span style="color:DodgerBlue">**Note**</span><br>
><ul><li>Do not pass in <code>page_id</code> when creating a Video Shopping Ad with products from Showcase.</li><li>The following parameters will be ignored if passed in:</li><ul><li><code>catalog_id</code> (the one not in <code>showcase_products</code>)</li><li><code>store_id</code>(the one not in <code>showcase_products</code>)</li><li><code> store_authorized_bc_id</code></li><li><code> product_specific_type</code></li><li><code>dynamic_format</code></li><li><code>dynamic_destination</code></ul></ul>

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
| showcase_products {Required} | object[] | The list of Showcase products that you want to use in your ad. Max size: 20. <br><br>To retrieve the products within a Showcase and determine whether they can be used in ads, use [/showcase/product/get/](https://business-api.tiktok.com/portal/docs?id=1759233576199169). If the returned `status` for a product is `AVAILABLE`, you can use the product in ads.|
#| item_group_id | string | SPU ID of the product. |
#| store_id | string | The ID of the store that the product (`item_group_id`) belongs to. <br>Note that the only supported store type is TikTok Shop. |
#| catalog_id | string | The ID of the catalog that the product (`item_group_id`) belongs to. <br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass this field because it will be ignored.|
| identity_id{Required} | string | Identity ID. <br>Set this field to the same value that you specify at the ad group level. |
| identity_type{Required} | string | Identity type. <br> The allowed enum values: `TT_USER` (TikTok User), `BC_AUTH_TT` (TikTok Account User in Business Center). See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. <br> Set this field to the same value that you specify at the ad group level. |
| identity_authorized_bc_id{+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br> ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
| video_id{+Conditional} | string | Required when you want to create a Spark Ad by pushing a video ad to linked Business Account. See [Create Spark Ads-Push a video ad to linked Business Account](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298) to learn more. <br>You need to pass in either `video_id` or `tiktok_item_id`. <br>Video ID. |
| tiktok_item_id{+Conditional} | string | Required when you want to create a Spark Ad by pulling organic videos. See [Create Spark Ads](https://ads.tiktok.com/marketing_api/docs?id=1739470744631298) to learn more. <br>You need to pass in either `video_id` or `tiktok_item_id`. <br>The ID of the TikTok post to be used as an ad (Spark Ad). <br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes. |
| vertical_video_strategy{Required} | string | The video type that you use for Product Sales scenarios. <br>The allowed enum value: `SINGLE_VIDEO` (single video). |
| card_id | string | Use this field to pass in the ID of an existing creative portfolio to include a Countdown Sticker or Product Card in your ads. <br>You need to first call [/creative/portfolio/create/](https://ads.tiktok.com/marketing_api/docs?id=1739091950439426) to create a Countdown Sticker portfolio, or Product Card portfolio, and get the portfolio ID via the returned `creative_portfolio_id`. |
| call_to_action | string | Call-to-action text. For enum values, see [Enumeration - Call-to-Action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br>You can specify this field when: <ul><li> You pass in `card_id`.</li></ul> Otherwise, this field will be ignored and defaults to `SHOP_NOW`. |
```

### Example
#### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "adgroup_id": "{{adgroup_id}}",
    "advertiser_id": "{{advertiser_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "dynamic_format": "UNSET",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "ad_text": "{{ad_text}}",
            "identity_type": "BC_AUTH_TT",
            "identity_id": "{{identity_id}}",
            "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
            "ad_format": "SINGLE_VIDEO",
            "tiktok_item_id": "{{tiktok_item_id}}",
            "image_ids": [
                "{{image_ids}}"
            ],
            "showcase_products": [
                {
                    "catalog_id": "{{catalog_id}}",
                    "item_group_id": "{{item_group_id}}",
                    "store_id": "{{store_id}}"
                },
                {
                    "catalog_id": "{{catalog_id}}",
                    "item_group_id": "{{item_group_id}}",
                    "store_id": "{{store_id}}"
                }
            ]
        }
    ]
}'
(/code)
```
#### Response
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "ad_text": "",
                "image_ids": [
                    ""
                ],
                "ad_format": "SINGLE_VIDEO",
                "shopping_ads_video_package_id": "",
                "modify_time": "{{modify_time}}",
                "page_id": null,
                "create_time": "{{create_time}}",
                "optimization_event": null,
                "deeplink": null,
                "campaign_id": "{{ampaign_id}}",
                "is_new_structure": true,
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "creative_authorized": false,
                "display_name": "{{display_name}}",
                "is_aco": false,
                "promotional_music_disabled": true,
                "vast_moat_enabled": false,
                "identity_type": "BC_AUTH_TT",
                "product_specific_type": "UNSET",
                "call_to_action": "SHOP_NOW",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "impression_tracking_url": null,
                "operation_status": "ENABLE",
                "adgroup_name": "{{adgroup_name}}",
                "campaign_name": "{{campaign_name}}",
                "landing_page_urls": null,
                "video_id": null,
                "avatar_icon_web_uri": "",
                "landing_page_url": "",
                "ad_name": "{{ad_name}}",
                "card_id": null,
                "brand_safety_postbid_partner": "UNSET",
                "dynamic_destination": "UNSET",
                "catalog_id": "0",
                "profile_image_url": "",
                "showcase_products": [
                    {
                        "catalog_id": "{{catalog_id}}",
                        "item_group_id": "{{item_group_id}}",
                        "store_id": "{{store_id}}"
                    },
                    {
                        "catalog_id": "{{catalog_id}}",
                        "item_group_id": "{{item_group_id}}",
                        "store_id": "{{store_id}}"
                    }
                ],
                "tiktok_item_id": "{{tiktok_item_id}}",
                "ad_texts": null,
                "advertiser_id": "{{advertiser_id}}",
                "creative_type": "SHOPPING_SHOP_PAGE",
                "app_name": "{{app_name}}",
                "click_tracking_url": null,
                "identity_id": "{{identity_id}}",
                "playable_url": "",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "dynamic_format": "UNSET",
                "call_to_action_id": null
            }
        ]
    }
}
(/code)
```
## 6. Preview an ad
After the ad is created,  preview your ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). 
<br>We offer preview types of `ADS_CREATION`, `AD` , `CARD`, and `PAGE` to enable you to preview ads that you plan to create, existing ads, Product Cards, and Instant Pages.
