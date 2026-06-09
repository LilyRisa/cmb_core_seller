---
title: "Get the details of a GMV Max Campaign"
doc_id: 1822000968821762
path: "API Reference / GMV Max / Get the details of a GMV Max Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of a GMV Max Campaign

Use this endpoint to retrieve the details of a GMV Max Campaign.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/info/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**
``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| campaign_id {Required} | string | The ID of a GMV Max Campaign.<br><br><ul><li>To create a GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089).</li><li>To filter existing GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177).</li></ul> |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/info/?advertiser_id={{advertiser_id}}&campaign_id={{campaign_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| advertiser_id | string | Advertiser ID. |
#| operation_status| string | The status of the GMV Max Campaign.<br><br>Enum values:<ul><li>`DISABLE`: Disabled.</li><li>`ENABLE`: Enabled. </li></ul> |
#| campaign_id | string | The ID of the GMV Max Campaign. |
#| campaign_name | string | The name of the GMV Max Campaign. |
#| store_id | string | The ID of the TikTok Shop. |
#| store_authorized_bc_id | string | ID of the Business Center that is authorized to access the TikTok Shop (`store_id`). |
#| shopping_ads_type | string | The type of the GMV Max Campaign.<br><br>Enum values:<ul><li>`PRODUCT`: Product GMV Max Campaign.</li><li>`LIVE`: Live GMV Max Campaign. </li></ul> |
#| product_specific_type | string | Different dimensions to choose products. <br><br>Enum values:<ul><li>`ALL`: Allow TikTok to dynamically choose from all products within the TikTok Shop.</li><li>`CUSTOMIZED_PRODUCTS`: Specify a customized number of products within the TikTok Shop.</li><li>`UNSET`: Unset.</li></ul> |
#| item_group_ids | string[] | Returned only when `shopping_ads_type` is `PRODUCT` and `product_specific_type` is `CUSTOMIZED_PRODUCTS`. <br><br>Product SPU (standard product unit) IDs. |
#| optimization_goal | string | Optimization goal. <br><br>Enum value:<ul><li>`VALUE`: Gross revenue.</li></ul>|
#| roi_protection_enabled | boolean | Whether the campaign is eligible for ROI protection. <br>ROI protection is a feature that automatically issues ad credits when the return on investment (ROI) for your campaign falls below a certain threshold despite following GMV Max guidelines and best practices. To learn about the eligibility criteria for ROI protection and scenarios where the campaign is ineligible for ROI protection, see [About ROI protection for GMV Max campaigns](https://ads.tiktok.com/help/article/about-roi-protection-for-gmv-max-campaigns).<br><br>Supported values: `true`, `false`.|
#| deep_bid_type | string | Bid strategy. <br><br>Enum value:<ul><li>`VO_MIN_ROAS`: Minimum ROAS. Bid with an ROI target.</li></ul>|
#| roas_bid | number | Returned only when `deep_bid_type` is `VO_MIN_ROAS`.<br><br>ROI target. |
#| budget | number | Daily budget. |
#| promotion_days | object | The promotion days setting details. |
##| is_enabled | boolean | Whether to enable promotion days to automatically optimize your campaign for higher GMV during high-intent shopping dates in regions where your ads are delivering. <br><br>Supported values: `true`, `false`. |
##| auto_schedule_enabled | boolean | Whether to use TikTok Shop promotion days schedule.<br><br>Supported values: `true`, `false`. |
##| custom_schedule_list | object[] | Details of the custom promotion day schedules. |
###| start_time | string | The start time of the custom promotion day schedule, in the format of `YYYY-MM-DD` (ad account time zone). |
###| end_time | string | The end time of the custom promotion day schedule, in the format of `YYYY-MM-DD`  (ad account time zone). |
##| roas_bid_multiplier |number | The target ROI index.<br>Changes to your target ROI will be in effect during promotion days and may drive higher GMV for this campaign.<br><br>Enum values:<ul><li>`90`: -10%. To decrease your target ROI by 10% during promotion days.</li><li>`80`: -20%. To decrease your target ROI by 20% during promotion days.</li><li>`70`: -30%. To decrease your target ROI by 30% during promotion days.</li></ul> |
##| adjusted_roas_bid | number | Promotion days target ROI. |
##|budget_increase_percentage|integer|The increase percentage for the budget during promotion days.<br><br>A value of 50 means a 50% budget increase when the automatic budget increase is triggered.|
##|increase_limit|integer|The increase limit for the daily budget during promotion days.<br><br>A value of 10 means allows up to 10 budget increases per day when the automatic budget increases are triggered.|
##| current_budget | number | The specified campaign daily budget (`budget`).|
##| next_increase | number | The increase in budget compared with your specified campaign daily budget (`budget`).<br><br>Formula: `next_increase` = `current_budget` x `budget_increase_percentage`.<br><br>For example, if `budget` is 300 USD and `budget_increase_percentage` is 50, `next_increase` will be 150 USD, indicating a daily budget increase of 150 USD.|
##|remained_times|number|The remaining times of automatic budget increases.|
##|maximum_budget|number|The maximum possible budget.<br><br>Formula: `maximum_budget` = `current_budget` + `current_budget` x `budget_increase_percentage` x `remained_times`.<br><br>For example, if `current_budget` is 200 USD, `budget_increase_percentage` is 50, and `remained_times` is 10,  your `maximum_budget` will be 1200 USD, as the increase can occur up to 10 times daily.|
##| estimated_gross_revenue_increase | string | The estimated percentage increase in gross revenue due to the ROI change during promotion days. <br><br>Example: `24%`. |
#|auto_budget|object|The details of the automatic budget settings during non-promotion days.|
##|auto_budget_enabled|boolean|Whether to enable automatic budget increase during non-promotion days.<br><br>Enabling automatic budget increase will allow your daily budget to be automatically increased to optimize your campaign for more sales on days when your campaign has reached at least 90% of your ROI target and at least 80% of your budget has been used. Your daily budget will reset to its original amount each day.<br><br>Supported values: `true`, `false`.|
##|budget_increase_percentage|integer|The increase percentage for the budget during non-promotion days.<br><br>A value of 50 means a 50% budget increase when the automatic budget increase is triggered.|
##|increase_limit|integer|The increase limit for the daily budget during non-promotion days.<br><br>A value of 10 means allows up to 10 budget increases per day when the automatic budget increases are triggered.|
##|current_budget|number|The specified campaign daily budget (`budget`).|
##|next_increase|number|The increase in budget compared with your specified campaign daily budget (`budget`).<br><br>Formula: `next_increase` = `current_budget` x `budget_increase_percentage`.<br><br>For example, if `budget` is 300 USD and `budget_increase_percentage` is 50, `next_increase` will be 150 USD, indicating a daily budget increase of 150 USD.|
##|remained_times|number|The remaining times of automatic budget increases.|
##|maximum_budget|number|The maximum possible budget.<br><br>Formula: `maximum_budget` = `current_budget` + `current_budget` x `budget_increase_percentage` x `remained_times`.<br><br>For example, if `current_budget` is 200 USD, `budget_increase_percentage` is 50, and `remained_times` is 10,  your `maximum_budget` will be 1200 USD, as the increase can occur up to 10 times daily.|
#| auto_budget_enabled  {-to be deprecated}| boolean | Whether to enable automatic budget increase.<br>When your achieved ROI reaches at least 90% of the target and 80% or more of your budget has been used, your daily campaign budget may automatically increase by up to 10 times to capture more sales. Your budget will reset to its original amount each day.<br><br><ul><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `false`, your ROI target will decrease and your daily budget will automatically increase during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`) and custom promotion days as defined by `custom_schedule_list`. Your daily budget will reset to its original amount every day. During standard days, your ROI target and budget won't change.</li><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `true`, auto budget increase will be turned on during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`), custom promotion days as defined by `custom_schedule_list`,**&nbsp;and standard days**. Your daily budget will reset to its original amount every day. During TikTok Shop promotion days and custom promotion days as defined by `custom_schedule_list`, your ROI target will decrease. During standard days, your ROI target won't change.</li><li>When `is_enabled` within the object `promotion_days` is `false` and`auto_budget_enabled` is `true`, your ROI won't change, your daily campaign budget may automatically increase up to 10 times to optimize gross revenue. Your daily budget will reset to its original amount every day.</li></ul><br>Supported values: `true`, `false`.|
#| schedule_type | string | Schedule type.<br><br>Enum values:<ul><li>`SCHEDULE_FROM_NOW`: To run the campaign continuously after the scheduled start time.</li><li>`SCHEDULE_START_END`: To run the campaign between the scheduled start time and end time.</li></ul> |
#| schedule_start_time | string | Campaign delivery start time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). |
#| schedule_end_time | string | Campaign delivery end time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). |
#| placements | string[] | Placements.<br>The apps where you want to deliver your campaign. <br><br> Enum values: <ul><li>`PLACEMENT_TIKTOK`: TikTok.</li><li>`PLACEMENT_PANGLE`: Pangle.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>Product GMV Max Campaigns automatically explore and choose from the most extensive audience scope across TikTok and Pangle. Learn more [about Pangle](https://ads.tiktok.com/help/article/pangle-placement).</li><li>If you want to opt out of the Pangle placement for your Product GMV Max Campaigns, contact your TikTok representative.</li></ul>|
#| location_ids | string[] | The IDs of the targeted locations. <br><br> To learn about the corresponding location for a location ID, see [Location IDs](https://business-api.tiktok.com/portal/docs?id=1739311040498689). |
#| age_groups | string[] | The targeted age groups.<br><br> For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Age%20Group). |
#| product_video_specific_type | string | The video selection mode. <br><br>Enum values:<ul><li>`AUTO_SELECTION`: To autoselect videos.</li><li>`CUSTOM_SELECTION`: To manually select videos. </li><li>`UNSET`: Unset. </li></ul>|
#|accelerate_testing_for_new_videos|string|Whether to accelerate testing for new videos, that is, prioritize performance testing for your new, recently authorized, and updated videos as part of your campaign.<br>Videos that have been uploaded or authorized in the past 7 days, including recently authorized TikTok account videos, customized posts, and videos with new or updated product links, will be prioritized for testing. This may temporarily reduce gross revenue, but your campaign's overall ROI will stay within your target ROI range. Your campaign will still be eligible for ROI protection when you turn on accelerated learning.<br><br>Enum values:<ul><li>`ON`: To accelerate testing for new videos.</li><li>`OFF`: To not accelerate testing for new videos.</li></ul>|
#| identity_list | object[] | The list of identities (TikTok accounts) associated with the GMV Max Campaign. |
##| identity_id | string | Identity ID. |
##| identity_type | string | Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##| identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`. <br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities. <br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
##| store_id | string | Returned only when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
#|affiliate_posts_enabled |boolean|Whether to enable affiliate posts for your Product GMV Max Campaign.<br>Affiliate posts are TikTok posts created by affiliates (creators participating in the TikTok Shop Affiliate Program) and authorized for use in TikTok Shop Ads. Learn more [about affiliate creatives for TikTok Shop Ads](https://ads.tiktok.com/help/article/about-affiliate-creatives-for-tiktok-shop-ads).<br><br>Supported values: `true`, `false`.|
#| item_list| object[] | The list of authorized TikTok posts or customized TikTok posts associated with the GMV Max campaign. <br><br><ul><li>Authorized TikTok posts are existing posts featuring your selected products from your TikTok accounts or authorized using video codes. </li><li>Customized TikTok posts are newly created posts for the campaign, built by adding customized product links to your selected videos. These posts are exclusive to this Product GMV Max campaign.</li></ul>|
##| item_id | string | The ID of the TikTok post. |
##| text | string | The caption of the TikTok post. |
##| spu_id_list| string[] | The list of Product SPU IDs that the TikTok post is associated with. |
##| identity_info | object | Information about the identity associated with the TikTok post. |
###| identity_id | string | Identity ID. |
###| identity_type | string | Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
###| identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`. <br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
###| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities. <br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
###| store_id | string | Returned only when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
###| profile_image | string | Temporary profile image URL for the TikTok account that is associated with the identity. <br><br>Validity period: around 48 hours. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds.<br><br>Once the URL expires, you need to call [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) to obtain a new URL. |
###| user_name| string | The username of the TikTok account that is associated with the identity. |
##| video_info | object | Details of the video in the post. |
###| video_id | string | The ID of the video. |
###| video_cover_url | string | Temporary URL for the video cover.<br><br>Validity period: around 24 hours. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br><br>Once the URL expires, you need to call [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) to obtain a new URL. |
###| preview_url | string | Temporary preview URL for the video. <br><br>Validity period: six hours.<br><br>Once the URL expires, you need to call [/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762) to obtain a new URL. |
###| height | number | The height of the video in pixels. <br><br> Example: 1920. |
###| width | number | The width of the video in pixels.<br><br>Example: 1080. |
###| bit_rate | number | The bit rate of the video in bps. <br><br>Example: 8500323. |
###| duration | number | The duration of the video in seconds. <br><br>Example: 3.669. |
###| size | number | The size of the video in bytes. <br><br>Example: 3898461. |
###| signature | string | The MD5 of the video. <br><br>Example: `ab1cd2345e678fghi91jkl23b456m7no`. |
###| format | string | The format of the video.<br><br>Example: `mp4`. |
###| definition | string | The definition of the video.<br><br> Example: `1080p`. |
###| fps | number | The frames per second (FPS) of the video. <br><br>Example: 27. |
#|campaign_custom_anchor_video_id{-To-be-deprecated}|string|The ID of the collection of customized posts created in the Product GMV Max Campaign.<br><br>You can pass this field to [/gmv_max/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs?id=1830215925061633) to obtain the details of the customized posts within the collection.<br><br><p><span style="color:darkred"><b>Important</b></span>: This field will be deprecated in the next API version. To ensure a seamless integration, retrieve the details of videos used in customized posts using [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) and [/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3).|
#| custom_anchor_video_list {-To-be-deprecated}| object[] | The list of customized TikTok posts associated with the GMV Max campaign. <br>Customized TikTok posts are posts with custom product links. <br><br><p><span style="color:darkred"><b>Important</b></span>: This field will be deprecated in the next API version. To ensure a seamless integration, retrieve the details of videos used in customized posts using [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) and [/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3).|
##| item_id | string | The ID of the TikTok post. |
##| spu_id_list| string[] | The list of Product SPU IDs that the TikTok post is associated with. |
##| identity_info | object | Information about the identity associated with the TikTok post. |
###| identity_id | string | Identity ID. |
###| identity_type | string | Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
###| identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`. <br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
###| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities. <br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
###| store_id | string | Returned only when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
```

### Example
```xcodeblock
(code Success-Response http)
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
        "budget": 300,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "custom_anchor_video_list": [],
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
            },
            {
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        ],
        "item_group_ids": [
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}",
            "{{item_group_id}}"
        ],
        "item_list": [],
        "location_ids": [
            "1643084"
        ],
        "operation_status": "DISABLE",
        "optimization_goal": "VALUE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_specific_type": "CUSTOMIZED_PRODUCTS",
        "product_video_specific_type": "AUTO_SELECTION",
		"affiliate_posts_enabled": true,
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

