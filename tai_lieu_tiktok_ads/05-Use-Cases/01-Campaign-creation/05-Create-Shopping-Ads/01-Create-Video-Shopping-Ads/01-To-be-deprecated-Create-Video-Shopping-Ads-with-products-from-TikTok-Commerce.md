---
title: "(To-be-deprecated) Create Video Shopping Ads with products from TikTok Commerce"
doc_id: 1750361719059457
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / (To-be-deprecated) Create Video Shopping Ads with products from TikTok Commerce"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To-be-deprecated) Create Video Shopping Ads with products from TikTok Commerce

TikTok Commerce (formerly known as TikTok Shopping) is a suite of solutions, features, and advertising tools that empowers brands and merchants to meaningfully engage with their customers. TikTok Commerce supports a variety of eCommerce content that lets you sell your products in TikTok videos and on your profile page's product showcase tab.

> <span style="color:DarkRed">**Important**</span><br>
> Starting Q3, 2025, [GMV Max](https://business-api.tiktok.com/portal/docs?id=1822009058467842) will be the default and only supported campaign type for TikTok Shop Ads. If you create ads using the Product Sales objective and TikTok Shop as your sales destination, you will no longer be able to use the API to create, edit, or duplicate [LIVE Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554), [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), or [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942291730434). Please migrate to the [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465) as early as possible. If you have questions or need support, please contact your TikTok representative.  Learn more about [the detailed changes by ad type and endpoint](https://business-api.tiktok.com/portal/docs?id=1837161048383489).


This article introduces how to create Video Shopping Ads with products from TikTok Commerce.
> <span style="color:DodgerBlue">**Note**</span><br>
> The flags of `Required`/ `Conditional` in the following parameter tables are only applicable for Video Shopping Ads with products from TikTok Commerce. 

# 1. Create a campaign

Create a campaign using `/campaign/create/`. The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating campaigns, refer to [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602).

> <span style="color:DodgerBlue">**Note**</span><br>
> Currently, Campaign Budget Optimization is not supported for Shopping Ads with products from TikTok Commerce.

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID |
|objective_type {Required}|string|Advertising objective. Use `PRODUCT_SALES` for Shopping Ads. |
|budget_mode {+Conditional}|string| Budget mode. How you'd like to set your budget, and for example you can set daily budget. Requied when `objective_type` is <strong>NOT</strong> `RF_REACH`. When `objective_type` is `RF_REACH`, `budget_mode` only supports `BUDGET_MODE_INFINITE`.<br>To learn about how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
|budget {+Conditional}|float| Campaign budget, required when `budget_mode` is `BUDGET_MODE_DAY` or `BUDGET_MODE_DYNAMIC_DAILY_BUDGET` or `BUDGET_MODE_TOTAL`. To learn about the minimum budget and how to set budget types, see [Budget settings](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
|campaign_name {Required}|string|Campaign name. It can contain up to 512 characters. Emoji is not supported. <br data-tomark-pass>Note: Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.|
|app_id {+Conditional}|string|App Id, required for IOS 14 campaign|
|campaign_type|string|Enum values: `REGULAR_CAMPAIGN`,` IOS14_CAMPAIGN`. |
| campaign_product_source{Required}  | string | Product source of the Video Shopping campaign. <br>Set this field to `STORE` (TikTok Shop, or TikTok Showcase).   |
```

## Example
### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{access-token}}' \
--header 'Content-Type: application/json' \
--data-raw '
{
    "advertiser_id": "{{advertiser_id}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "objective_type": "PRODUCT_SALES",
	"campaign_product_source": "STORE",
    "campaign_name": "{{campaign_name}}",
    "budget": 5000
}'
(/code)
```
### Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_app_profile_page_state": "UNSET",
		"campaign_product_source": "STORE",
        "objective_type": "PRODUCT_SALES",
        "is_new_structure": true,
        "budget": 5000.0,
        "objective": "LANDING_PAGE",
        "roas_bid": 0.0,
        "create_time": "2022-11-01 08:35:45",
        "advertiser_id": "{{advertiser_id}}",
        "is_smart_performance_campaign": false,
        "campaign_name": "{{campaign_name}}",
        "operation_status": "ENABLE",
        "deep_bid_type": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "campaign_id": "{{campaign_id}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "modify_time": "2022-11-01 08:35:45"
    }
}
(/code)
```


# 2. Create and manage TikTok Shops

TikTok Commerce offers TikTok Shops for brands and sellers who want to sell their products directly on their TikTok account. 

You can set up TikTok Shop by creating TikTok Shops on TikTok Seller Center. For detailed instructions on how to create a TikTok Shop, see [Set Up TikTok Shop Using TikTok Seller Center](https://ads.tiktok.com/help/article?aid=10005127).

After you have created a TikTok Shop, follow the procedures below:
1. Add the TikTok Shop to the Business Center. To learn about how to request TikTok Shop access from a Business Center, see [here](https://ads.tiktok.com/help/article?aid=10012166). 
2. Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). You can also choose `advertiser_role` that you want to assign to the members invited.
3. Share a TikTok Shop with members and grant the TikTok Shop management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). Make sure to specify `TIKTOK_SHOP` in the `asset_type` field and `AD_PROMOTION` in the `store_role` field.



# 3. Create an ad group
Create an ad group using `/adgroup/create/`.  The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating ad groups, refer to [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114).

> <span style="color:DodgerBlue">**Note**</span><br>
> Automated Creative Optimization is not supported for Shopping Ads. 

```xtable
|Field{35%}|Data Type{10%}|Description{55%}|
|---|---|---|
|promotion_type{+Conditional} |string|Promotion type and you can decide where you'd like to promote your products using this field. You need to specify the field when advertising objective (`objective_type`) for your campaign is NOT set as `REACH`, `VIDEO_VIEWS`, or `ENGAGEMENT`.<br>For enum values, see [Enumeration - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br>Use `VIDEO_SHOPPING` for Video Shopping Ads.|
|placement_type |string|The placement strategy that decides where your ads will be shown. <br><br>Enum values: <ul><li>`PLACEMENT_TYPE_AUTOMATIC`: Automatic placement. TikTok's ad system will use smart calculations to give you the best combination of ad placements across all apps.</li><li>`PLACEMENT_TYPE_NORMAL`: Select placement. By selecting your placement manually, you'll be able to choose the apps to deliver your ads on. </li></ul><br>Default value: `PLACEMENT_TYPE_NORMAL`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Pangle placement (`PLACEMENT_PANGLE`) for Video Shopping Ads for Shop (`product_source` as `STORE` or `SHOWCASE`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. Once you are allowlisted for this feature, you can also use automatic placement (`PLACEMENT_TYPE_AUTOMATIC`) for Video Shopping Ads for Shop. |
|placements {+Conditional}|string[]|<ul><li>When `placement_type` is `PLACEMENT_TYPE_NORMAL`, this field is required.</li><li>When `placement_type` is `PLACEMENT_TYPE_AUTOMATIC`, this field is ignored and overwritten to the combination of ad placements that the TikTok ad system selects for your ad.</li></ul><br>The apps where you want to deliver your ads.  <br><br>Enum values: `PLACEMENT_TIKTOK`, `PLACEMENT_PANGLE`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Pangle placement (`PLACEMENT_PANGLE`) for Video Shopping Ads for Shop is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
|product_source{Required}|string|Product source where you want to get products for promotion.<br>Set this field to `STORE` (TikTok Shop).<br><p><span style="color:darkred"><b>Note</b></span>: If `campaign_product_source` is not set at the campaign level, and you have at least one historical ad group with `product_source` set as `CATALOG` and one with `product_source` set as `STORE` or `SHOWCASE` under the same campaign at the same time, you cannot create new ad groups under the campaign. |
|shopping_ads_type{Required} |string|Shopping ads type. Enum: `VIDEO`(Video Shopping Ads),`LIVE`(Live Shopping Ads), `CATALOG_LISTING_ADS`(Catalog Listing Ads) . Use `VIDEO` for Video Shopping Ads. |
|catalog_id{Required}|string| Catalog ID. <br>You can use [/store/list/](https://ads.tiktok.com/marketing_api/docs?id=1752267762718722) to get the `catalog_id`, `store_id` and `store_authorized_bc_id` for available stores under an ad account.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass `catalog_id` and `catalog_authorized_bc_id` because these two fields will be ignored.|
|catalog_authorized_bc_id |string| Business Center ID that has permission for this catalog. For catalogs in Business Center, you must specify the ID of the Business Center that a catalog belongs to.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass `catalog_id` and `catalog_authorized_bc_id` because these two fields will be ignored.|
|store_id{+Conditional}|string| ID of the TikTok Shop. Required when `product_source` is set as `STORE`. <br><p><span style="color:darkred"><b>Note</b></span>: To get the TikTok Shop ID, you can use [/bc/asset/get/](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401): <ul><li> When in the response `asset_type` is `TIKTOK_SHOP`, the returned `asset_id` is the TikTok Shop ID.</li></ul> |
|store_authorized_bc_id{+Conditional}|string| ID of the Business Center that is authorized to access the store (`store_id`). Required when `product_source` is set as `STORE`. |
| bid_type {Required} | string | Bidding strategy that determines how the system manages your cost per result, spends your budget, and how it delivers ads. <br><br>Enum values: <ul><li>`BID_TYPE_CUSTOM`: Keep the average cost per result around the stated amount. This bidding strategy is called <b>Target CPA</b> or <b>Cost Cap</b> in TikTok Ads Manager. </li><li>`BID_TYPE_NO_BID`: Aim to spend your entire budget to get the most results. This bidding strategy is called <b>Maximum Delivery</b> in TikTok Ads Manager.</li></ul> |
| deep_bid_type{+Conditional} | string | Required when `optimization_goal` is `VALUE`.<br><br> Bidding strategy for [Value-Based Optimization](https://business-api.tiktok.com/portal/docs?id=1739381743067137) that determines how the system manages your cost per result, spends your budget, and how it delivers ads. <br><br>Enum values: <ul><li>`VO_MIN_ROAS`: Keep the average ROAS around the stated amount. This bidding strategy is called <b>Target ROAS</b> in TikTok Ads Manager. </li><li>`VO_HIGHEST_VALUE`: Aim to spend your entire budget to get the most gross revenue. This bidding strategy is called <b>Highest Gross Revenue</b> in TikTok Ads Manager. </li></ul>|
```
## Example -Ad group with products from TikTok Shop
### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{access-token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "adgroup_name": "{{adgroup_name}}",
    "advertiser_id": "{{advertiser_id}}",
    "automated_targeting": "OFF",
    "bid_price": 0.0,
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",
    "budget": 400.0,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "campaign_id": "{{campaign_id}}",
    "conversion_bid_price": 0.0,
    "deep_cpa_bid": 0.0,
    "end_time_stamp": 1643523943,
    "gender": "GENDER_UNLIMITED",
    "ios14_quota_type": "UNOCCUPIED",
    "is_new_structure": true,
    "operation_status": "ENABLE",
    "optimization_goal": "CLICK",
    "pacing": "PACING_MODE_SMOOTH",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "schedule_end_time": "2038-01-01 00:00:00",
    "schedule_start_time": "2037-12-12 23:23:23",
    "schedule_type": "SCHEDULE_START_END",
    "shopping_ads_retargeting_type": "OFF",
    "start_time_stamp": 1640845543,
    "targeting_expansion": {
        "enable_expansion": false
    },
    "video_download_disabled": true,
    "promotion_type": "VIDEO_SHOPPING",
    "product_source": "SHOP",
    "store_id": "{{store_id}}",
    "store_authorized_bc_id": "{{store_authorized_bc_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "location_ids": [
        "1643084"
    ]
}'
(/code)
```
### Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_type": null,
        "optimization_event": null,
        "bid_price": 0.0,
        "pacing": "PACING_MODE_SMOOTH",
        "operation_status": "ENABLE",
        "deep_cpa_bid": 0.0,
        "conversion_bid_price": 0.0,
        "promotion_type": "VIDEO_SHOPPING",
        "adgroup_app_profile_page_state": null,
        "bid_display_mode": "CPMV",
        "scheduled_budget": 0.0,
        "operating_systems": [],
        "frequency": null,
        "deep_bid_type": null,
        "network_types": [],
        "rf_estimated_frequency": null,
        "is_new_structure": true,
        "auto_targeting_enabled": false,
        "adgroup_name": "{{adgroup_name}}",
        "inventory_filter_enabled": false,
        "secondary_optimization_event": null,
        "schedule_type": "SCHEDULE_START_END",
        "pixel_id": null,
        "video_download_disabled": true,
        "interest_category_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "gender": "GENDER_UNLIMITED",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "bid_type": "BID_TYPE_NO_BID",
        "delivery_mode": null,
        "campaign_id": "{{campaign_id}}",
        "share_disabled": false,
        "optimization_goal": "CLICK",
        "frequency_schedule": null,
        "budget": 400.0,
        "schedule_infos": null,
        "creative_material_mode": "CUSTOM",
        "statistic_type": null,
        "modify_time": "2022-11-10 07:21:04",
        "audience_ids": [],
        "purchased_impression": null,
        "languages": [],
        "billing_event": "CPC",
        "store_id": "{{store_id}}",
        "campaign_name": "{{campaign_name}}",
        "brand_safety_partner": null,
        "category_id": "0",
        "device_price_ranges": null,
        "app_download_url": null,
        "product_source": "SHOP",
        "advertiser_id": "{{advertiser_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_estimated_cpr": null,
        "conversion_window": null,
        "rf_purchased_type": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "feed_type": null,
        "is_hfss": false,
        "create_time": "2022-11-10 07:21:04",
        "excluded_audience_ids": [],
        "schedule_end_time": "2038-01-01 00:00:00",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "ios14_quota_type": "UNOCCUPIED",
        "schedule_start_time": "2037-12-12 23:23:23",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "search_result_enabled": false,
        "skip_learning_phase": false,
        "comment_disabled": false,
        "keywords": null,
        "catalog_id": "{{catalog_id}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "age_groups": null,
        "location_ids": [
            "1643084"
        ],
        "next_day_retention": null,
        "app_id": null,
        "actions": [],
        "purchased_reach": null,
        "interest_keyword_ids": [],
        "shopping_ads_type": "VIDEO"
    }
}

(/code)
```


# 4. Create an ad
Create an ad using `/ad/create/`. The following table only lists the parameters related to Shopping Ads. To find out the other parameters required for creating ads, refer to [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354).

><span style="color:DodgerBlue">**Note**</span><br><ul><li> Do not pass in `page_id` or `card_id` if `product_source` has been set as `STORE` at the ad group level.</li><li> When you do not pass in fields related to cards (`card_id`) to specify card as an interactive add-on, the default interactive add-on for video shopping ads with products from TikTok Shop will be anchor links.</li></ul> 

```xtable
|Field|Data Type|Description|
|---|---|---|
|identity_id {Required}|string|Identity ID.|
|identity_type {Required}|string| Identity type. Enum values: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097). <br><p><span style="color:darkred"><b>Note</b></span>: If `product_source` is set as `STORE` at the ad group level, `identity_id` and `identity_type` can only be specified at the ad level, and `identity_type` can only be `AUTH_CODE`, `TT_USER`, or `BC_AUTH_TT` (for this identity of TikTok Account User in Business Center, you need to have the permission of <strong>ad delivery with existing posts from the TikTok account</strong>).|
|catalog_id|string| Catalog ID. <br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass this field because it will be ignored.|
|product_specific_type|string|Different dimensions to choose products. Enum values: `ALL`,` PRODUCT_SET`, `CUSTOMIZED_PRODUCTS`. <br>If you choose `ALL`, you don't need to input `sku_ids` or `item_group_ids` or `product_set_id`. `item_group_ids` or `product_set_id` are required if `product_specific_type`=`PRODUCT_SET`. `sku_ids` are required if `product_specific_type`=`CUSTOMIZED_PRODUCTS`. <br>Ignored when `product_source` is set as `STORE` at the ad group level.|
|item_group_ids {+Conditional}|string[]|Product SPU IDs. Max size=20.<br><br>To retrieve the SPU IDs of products within a TikTok Shop and determine whether they can be used in ads, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130). If the returned `status` for a product is `AVAILABLE`, you can use the product in ads.|
|dynamic_format|string|Dynamic format. <br><br>Enum values: <ul><li>`UNSET`: Unset.</li><li>`DYNAMIC_CREATIVE`: smart creative. To display different videos, product cards and destinations to each person based on purchase intent, maximizing conversions.</li></ul><br>`dynamic_format` cannot be enabled for retargeting ads (i.e. when `shopping_ads_retargeting_type` in the ad group is not `OFF`). When `dynamic_format` = `DYNAMIC_CREATIVE`,we will automatically create cards. This means that you won't need to pass all card related fields(`card_type`, `card_image_id`, `card_tags`, `card_show_price`).<br><br>When `product_source` is set as `STORE` at the ad group level, you cannot set `dynamic_format `as `DYNAMIC_CREATIVE`. You can choose not to pass in the field or set it as `UNSET`. |
|vertical_video_strategy |string|Enum values: `UNSET`, `SINGLE_VIDEO`, `CATALOG_VIDEOS`. It must be `UNSET` if `dynamic_format` = `DYNAMIC_CREATIVE`.<br><br>When `product_source` is set as `STORE` at the ad group level, you can choose not to pass in the field or set it as `SINGLE_VIDEO`. |
|dynamic_destination|string|Enum values: `UNSET`, `DLP`(Dynamic Landing Page). <br><br>You can only set this field to `UNSET`.|
|landing_page_url|string|Landing page URL. <br><br>Do not pass in this field.|
| ad_format{Required} | string | The ad format. Enum values: `SINGLE_IMAGE`, `SINGLE_VIDEO`, `CAROUSEL`, `LIVE_CONTENT`. <br>When `product_source` is set as `STORE` at the ad group level, this field is required and should be set as `SINGLE_VIDEO` . |
| video_id {+Conditional} | string | The ID of the video used to create an ad. For Spark Ads, use `tiktok_item_id` to specify the TikTok post ID. You can find out video IDs by using the [/file/video/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740050472224769) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: When `product_source` is set as `STORE` at the ad group level, you need to pass in `video_id` or `tiktok_item_id`. |
| tiktok_item_id {+Conditional} | string | The ID of the TikTok post to be used as an ad (Spark Ad). Pass in the `item_id` you get from the response of the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) or [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: When `product_source` is set as `STORE` at the ad group level, you need to pass in `video_id` or `tiktok_item_id`. <br><br><span style="color:#fd0000">By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes. </span> |
```


## Example
### Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{access-token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "adgroup_id": "{{adgroup_id}}",
    "advertiser_id": "{{advertiser_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "dynamic_format": "UNSET",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "ad_text": "test",
            "dynamic_destination": "UNSET",
            "call_to_action": "SHOP_NOW",
            "ad_format": "SINGLE_VIDEO",
            "image_ids": [
                "{{image_ids}}"
            ],
            "catalog_id": "{{catalog_id}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "item_group_ids": [
                "{{item_group_ids}}"
            ]
        }
    ]
}'
(/code)
```
### Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
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
                "shopping_ads_video_package_id": "",
                "avatar_icon_web_uri": "",
                "click_tracking_url": null,
                "playable_url": "",
                "is_aco": false,
                "page_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "creative_type": "SHOPPING_SHOP_PAGE",
                "campaign_name": "{{campaign_name}}",
                "ad_text": "test",
                "call_to_action_id": null,
                "optimization_event": null,
                "vast_moat_enabled": false,
                "ad_id": "ad_id",
                "ad_format": "SINGLE_VIDEO",
                "impression_tracking_url": null,
                "operation_status": "ENABLE",
                "profile_image_url": "",
                "ad_name": "{{ad_name}}",
                "advertiser_id": "{{advertiser_id}}",
                "creative_authorized": false,
                "video_id": "{{video_id}}",
                "campaign_id": "{{campaign_id}}",
                "identity_id": "{{identity_id}}",
                "landing_page_url": "",
                "catalog_id": "{{catalog_id}}",
                "display_name": "Pinkeye",
                "dynamic_destination": "UNSET",
                "landing_page_urls": null,
                "deeplink": null,
                "adgroup_id": "{{adgroup_id}}",
                "dynamic_format": "UNSET",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "dark_post_status": "OFF",
                "branded_content_disabled": false,
                "app_name": "Pinkeye",
                "item_group_ids": [
                    "{{item_group_ids}}"
                ],
                "identity_type": "TT_USER",
                "modify_time": "2022-11-10 08:20:25",
                "is_new_structure": true,
                "card_id": null,
                "image_ids": [
                    "{{image_ids}}"
                ],
                "create_time": "2022-11-10 08:20:25",
                "product_specific_type": "UNSET",
                "ad_texts": null
            }
        ]
    }
}
(/code)
```


 # 5. Preview an ad
After the ad is created, preview your ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). 
<br>We offer preview types of `AD` ,`PAGE`, `CARD`, and `ADS_CREATION` to enable you to preview existing ads, instant page, product cards and ads (without creating one).


