---
title: "Update a GMV Max Campaign"
doc_id: 1822001009002497
path: "API Reference / GMV Max / Update a GMV Max Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a GMV Max Campaign

Use this endpoint to update a GMV Max Campaign.

This endpoint supports incremental updates.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you want to update the on/off status of a GMV Max Campaign, use [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/update/

**Method** POST

**Header**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. <br><br>To ensure that the ad account has exclusive GMV Max authorization for the TikTok Shop that you want to promote, call [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `advertiser_id` within `exclusive_authorized_advertiser_info` is the same as the ad account that you want to use to create GMV Max Campaigns.<ul><li>If the returned `advertiser_id` is not the same as the ad account that you want to use to create GMV Max Campaigns, use [/gmv_max/exclusive_authorization/create/](https://business-api.tiktok.com/portal/docs?id=1822001200356354) to grant the ad account exclusive GMV Max authorization for the TikTok Shop.</li></ul>|
| campaign_id {Required} | string | The ID of a GMV Max Campaign.<br><br><ul><li>To create a GMV Max Campaign and obtain the campaign ID, use [/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089).</li><li>To filter existing GMV Max Campaigns within your ad account, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177).</li></ul> |
| roas_bid | number | Valid only when `deep_bid_type` of the campaign is `VO_MIN_ROAS`.<br><br> ROI target, kept to up to one decimal place. <br><br>Examples: 3.2, 3.<br><br>To obtain the recommended ROI target, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `roas_bid`. |
| budget | number | Daily budget. <br><br> To obtain the recommended budget, use [/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897) and check the returned `budget`. |
| promotion_days | object | The promotion days setting details.<br><br><p><span style="color:darkred"><b>Note</b></span>: To customize the percentage and the maximum times the budget can increase per day within your promotion days settings, use `budget_increase_percentage` and `increase_limit`within this object.|
#| is_enabled | boolean | Whether to enable promotion days to automatically optimize your campaign for higher GMV during high-intent shopping dates in regions where your ads are delivering. <br><br>Supported values: `true`, `false`.<br><br>When `promotion_days` is specified, this field will be automatically set to `true`. |
#| auto_schedule_enabled | boolean | Valid only when `is_enabled` is `true`.<br><br>Whether to use TikTok Shop promotion days schedule.<br><br>Supported values: `true`, `false`.<br><br>When`is_enabled` is `true`, this field will be automatically set to `true`. |
#| custom_schedule_list {+Conditional} | object[] | Details of the custom promotion day schedules.<br><br><ul><li>If `auto_schedule_enabled` is `false`, specify at least one set of `start_date` and `end_date` in `custom_schedule_list`.</li><li>If `auto_schedule_enabled` is `true`, `custom_schedule_list` is optional.</li></ul>Max size: 50. |
##| start_date | string | The start date of the custom promotion day schedule, in the format of `YYYY-MM-DD`(ad account time zone).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The start date cannot be after the `end_date`.</li><li>The start date cannot be earlier than the current date.</li><li>The start date must be on the same day as or after the `schedule_start_time` of the campaign.</li><li>The custom promotion days schedule will automatically start at 00:00:00 of the start date in the ad account time zone.</li></ul> |
##| end_date | string | The end date of the custom promotion day schedule, in the format of `YYYY-MM-DD` (ad account time zone) .<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The end date cannot be earlier than the current time.</li><li>The custom promotion days schedule will automatically end at 00:00:00 of the end date in the ad account time zone.</li></ul> |
#| roas_bid_multiplier | number | The target ROI index.<br>Changes to your target ROI will be in effect during promotion days and may drive higher GMV for this campaign.<br><br>Enum values:<ul><li>`90`: -10%. To decrease your target ROI by 10% during promotion days.</li><li>`80`: -20%. To decrease your target ROI by 20% during promotion days.</li><li>`70`: -30%. To decrease your target ROI by 30% during promotion days.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: We will be updating the way ROI goals are calculated during promotions to improve performance accuracy and ensure fairness across campaigns. Instead of using just the value you manually specify, the system will calculate your promotional ROI goal based on <b>the lower of your recent campaign performance (last 7 non-promotion days) or your entered ROI</b>, and then apply the selected reduction (10%, 20%, or 30%). This will be an allowlist-only feature. You might not need to make code changes, but the returned value of ROI goal will be different. |
#|budget_increase_percentage|integer|The increase percentage for the budget during promotion days.<br><br>Value range: 50-300.<br>A value of 50 means a 50% budget increase when the automatic budget increase is triggered.|
#|increase_limit|integer|The increase limit for the daily budget during promotion days.<br><br>Value range: 1-10.<br>A value of 10 means allows up to 10 budget increases per day when the automatic budget increases are triggered.|
|auto_budget|object|The details of the automatic budget settings during non-promotion days.|
#|auto_budget_enabled|boolean|Whether to enable automatic budget increase during non-promotion days.<br>Enabling automatic budget increase will allow your daily budget to be automatically increased to optimize your campaign for more sales on days when your campaign has reached at least 90% of your ROI target and at least 80% of your budget has been used. Your daily budget will reset to its original amount each day.<br><br>Supported values: `true`, `false`.|
#|budget_increase_percentage|integer|The increase percentage for the budget during non-promotion days.<br><br>Value range: 50-300.<br>A value of 50 means a 50% budget increase when the automatic budget increase is triggered.|
#|increase_limit|integer|The increase limit for the budget during non-promotion days.<br><br>Value range: 1-10.<br>A value of 10 means allows up to 10 budget increases per day when the automatic budget increases are triggered.|
| auto_budget_enabled {-to be deprecated} | boolean | Whether to enable automatic budget increase.<br>When your achieved ROI reaches at least 90% of the target and 80% or more of your budget has been used, your daily campaign budget may automatically increase by up to 10 times to capture more sales. Your budget will reset to its original amount each day.<br><br><ul><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `false`, your ROI target will decrease and your daily budget will automatically increase during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`) and custom promotion days as defined by `custom_schedule_list`. Your daily budget will reset to its original amount every day. During standard days, your ROI target and budget won't change.</li><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `true`, auto budget increase will be turned on during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`), custom promotion days as defined by `custom_schedule_list`,<b>and standard days</b>. Your daily budget will reset to its original amount every day. During TikTok Shop promotion days and custom promotion days as defined by `custom_schedule_list`, your ROI target will decrease. During standard days, your ROI target won't change.</li><li>When `is_enabled` within the object `promotion_days` is `false` and`auto_budget_enabled` is `true`, your ROI won't change, your daily campaign budget may automatically increase up to 10 times to optimize gross revenue. Your daily budget will reset to its original amount every day.<ul><li>However, if you specify `auto_budget_enabled` and `auto_budget` at the same time, `auto_budget_enabled` will be ignored.</li></ul></li></ul><br>Supported values: `true`, `false`.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you use this parameter, the automatic budget increase settings for GMV Max Campaigns are fixed in terms of percentage and maximum frequency. If you want to customize the percentage and the maximum times the budget can increase per day during non-promotion days, use `auto_budget` instead. |
| item_group_ids | string[] | Valid when `shopping_ads_type` is `PRODUCT` and `product_specific_type` is `CUSTOMIZED_PRODUCTS`. <br><br>Product SPU (standard product unit) IDs. <br><br> Max size: 50.<br><br>To obtain the list of SPU IDs for products within a TikTok Shop, use [/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130). Set `ad_creation_eligible` to `GMV_MAX` and select `item_group_id` values where `status` is `AVAILABLE` and `gmv_max_ads_status` is `UNOCCUPIED`. |
| schedule_type | string | Schedule type.<br><br>Enum values:<ul><li>`SCHEDULE_FROM_NOW`: To run the campaign continuously after the scheduled start time.</li><li>`SCHEDULE_START_END`: To run the campaign between the scheduled start time and end time.</li></ul> |
| schedule_end_time {+Conditional} | string | <ul><li>Required when `schedule_type` is `SCHEDULE_START_END`. </li><li>Not supported when `schedule_type` is `SCHEDULE_FROM_NOW`. </li></ul><br>Campaign delivery end time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). |
|affiliate_posts_enabled |boolean|Valid only when the following conditions are both met:<ul><li>`shopping_ads_type` is `PRODUCT`</li><li>`product_video_specific_type` is `AUTO_SELECTION`</li></ul>Whether to enable affiliate posts for your Product GMV Max Campaign.<br>Affiliate posts are TikTok posts created by affiliates (creators participating in the TikTok Shop Affiliate Program) and authorized for use in TikTok Shop Ads. Learn more [about affiliate creatives for TikTok Shop Ads](https://ads.tiktok.com/help/article/about-affiliate-creatives-for-tiktok-shop-ads).<br><br>Supported values: `true`, `false`.|
| item_list  | object[] | Valid when `shopping_ads_type` is `PRODUCT` and `product_video_specific_type` is `CUSTOM_SELECTION`. <br><br>The list of authorized TikTok posts or customized TikTok posts to associate with the Product GMV Max campaign.<ul><li>Authorized TikTok posts are existing posts featuring your selected products from your TikTok accounts or authorized using video codes.<ul><li>If you want to manually select authorized TikTok posts only for the Product GMV Max campaign, include their details using `item_list` and do not specify `custom_anchor_video_list`.</li></ul></li><li>Customized TikTok posts are posts built by adding customized product links to videos. Two types are supported: campaign-level customized posts and shop-level customized posts.<ul><li>Campaign-level customized TikTok posts are newly created posts for the campaign, built by adding customized product links to your selected videos at the campaign level. These posts will be exclusive to this Product GMV Max campaign.<ul><li>If you want to manually select campaign-level customized TikTok posts for the Product GMV Max campaign, include details of the videos to be used in customized posts using `item_list` and specify `custom_anchor_video_list` simultaneously.</li></ul></li><li>Shop-level customized TikTok posts are newly created posts for the TikTok Shop, built by adding customized product links to your selected videos at the TikTok Shop level. These posts will be automatically added to this Product GMV Max campaign if the linked products are included in the campaign.<ul><li>If you want to manually select shop-level customized TikTok posts for the Product GMV Max campaign, include their details using `item_list` and do not specify `custom_anchor_video_list`.</li><li>To obtain shop-level customized TikTok posts, use [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) with the `campaign_id` filter unspecified.</li></ul></li></ul></li></ul><br>Max size: 50.<br><br>To obtain a list of TikTok posts available for a Product GMV Max Campaign using the same TikTok Shop, use [/gmv_max/video/get/](https://business-api.tiktok.com/portal/docs?id=1822001168512129). <br><br><p><span style="color:darkred"><b>Important</b></span>: The campaign-level customized posts feature will be deprecated in the next API version. To ensure a seamless integration, create shop-level customized posts using [/gmv_max/creation/custom_anchor_video_list/create/](https://business-api.tiktok.com/portal/docs/create-shop-level-customized-tiktok-posts/v1.3) and retrieve the details of videos used in customized posts using [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) and [/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3).|
#| item_id {+Conditional} | string | Required when `item_list` is passed.<br><br> The ID of the TikTok post. |
#| spu_id_list {+Conditional} | string[] | Required when `item_list` is passed. <br><br> Product SPU IDs associated with the TikTok post. |
#| identity_info {+Conditional} | object | Required when `item_list` is passed. <br><br>Information about the identity associated with the TikTok post. |
##| identity_id {+Conditional} | string | Required when `item_list` is passed.<br><br> Identity ID. |
##| identity_type {+Conditional} | string | Required when `item_list` is passed.<br><br>Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##| identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br><br> The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##| identity_authorized_shop_id {+Conditional} | string | Required only when `dentity_type` is `BC_AUTH_TT` and `identity_authorized_shop_id` is returned for the identity from [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882). <br><br> The ID of the TikTok Shop that a TikTok Account User in Business Center identity is associated with. |
##| store_id {+Conditional} | string | Required when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
#| video_info {+Conditional} | object | Required when `item_list` is passed. <br><br>Details of the video in the post. |
##| video_id {+Conditional} | string | Required when `item_list` is passed. <br><br> The ID of the video. |
| custom_anchor_video_list{-To-be-deprecated} | object[] | The list of customized TikTok posts to associate with the Product GMV Max campaign.<br>Customized TikTok posts are posts created during campaign setup by adding customized product links for your products (`spu_id_list` within `custom_anchor_video_list`) to specified videos (`item_id` within `custom_anchor_video_list`). These posts will be exclusive to this Product GMV Max campaign.<ul><li>For manual selection: When `shopping_ads_type` is `PRODUCT` and `product_video_specific_type` is `CUSTOM_SELECTION`, specify the videos to be used in customized posts using this field.</li><li>For auto selection: When `shopping_ads_type` is `PRODUCT` and `product_video_specific_type` is `AUTO_SELECTION`, specify the videos to be auto-selected for customized posts using this field.</li></ul><br>Max size: 2,000.<br><br><p><span style="color:darkred"><b>Important</b></span>: This field will be deprecated along with the campaign-level customized posts feature in the next API version. To ensure a seamless integration, create shop-level customized posts using [/gmv_max/creation/custom_anchor_video_list/create/](https://business-api.tiktok.com/portal/docs/create-shop-level-customized-tiktok-posts/v1.3) and retrieve the details of videos used in customized posts using [/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3) and [/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you want to specify more than 200 customized posts for a Product GMV Max Campaign, you can update the `custom_anchor_video_list` of the campaign incrementally, with updates limited to 200 additional posts per request. <ul><li>For example, if you created the campaign with 200 customized posts and want to increase the post count to 400, specify 400 customized posts including the original 200 and the additional 200 ones through this field. </li></ul></li><li>If you want to remove certain customized posts from the Product GMV Max Campaign, make sure to remove the posts from both `custom_anchor_video_list` and `item_list` simultaneously. </li></ul>|
#| item_id {+Conditional}| string | Required when `custom_anchor_video_list` is passed.<br><br>The ID of a TikTok video to be used in a customized post.<br><br>To retrieve eligible videos, use [/gmv_max/video/get/](https://business-api.tiktok.com/portal/docs?id=1822001168512129) with `custom_posts_eligible` set to true. |
#| spu_id_list {+Conditional}| string[] | Required when `custom_anchor_video_list` is passed.<br><br>The SPU IDs of the products to associate with the customized product anchor link in the TikTok post.<br><br>Max size: 1. |
#| identity_info {+Conditional}| object | Required when `custom_anchor_video_list` is passed.<br><br>Information about the identity to associate with the post. |
##| identity_id {+Conditional}| string | Required when `custom_anchor_video_list` is passed.<br><br>Identity ID.<br><br>To obtain a list of identities available for GMV Max Campaigns, use [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882). |
##| identity_type{+Conditional} | string | Required when `custom_anchor_video_list` is passed.<br><br>Identity type.<br><br>Enum values:<ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request.</li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##| identity_authorized_bc_id {+Conditional}| string | Required when `identity_type` is `BC_AUTH_TT`.<br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##| identity_authorized_shop_id {+Conditional}| string | Required only when `identity_type` is `BC_AUTH_TT` and `identity_authorized_shop_id` is returned for the identity from [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882).<br><br>The ID of the TikTok Shop that a TikTok Account User in Business Center identity is associated with. |
##| store_id {+Conditional}| string | Required when `identity_type` is `TTS_TT`.<br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
| campaign_name | string | The name of the GMV Max Campaign. |
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/gmv_max/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "campaign_name": "{{campaign_name}}"
}'
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
##| is_enabled | boolean | Whether to enable promotion days to automatically optimize your campaign for higher GMV during high-intent shopping dates in regions where your ads are delivering. <br>When promotion days are active for this campaign, your ad credits will be calculated for your promotion days target ROI setting.<br><br>Supported values: `true`, `false`. |
##| auto_schedule_enabled | boolean | Whether to use TikTok Shop promotion days schedule.<br><br>Supported values: `true`, `false`. |
##| custom_schedule_list | object[] | Details of the custom promotion day schedules. |
###| start_time | string | The start time of the custom promotion day schedule, in the format of `YYYY-MM-DD` (ad account time zone). |
###| end_time | string | The end time of the custom promotion day schedule, in the format of `YYYY-MM-DD`  (ad account time zone). |
##| roas_bid_multiplier | number | The target ROI index.<br>Changes to your target ROI will be in effect during promotion days and may drive higher GMV for this campaign.<br><br>Enum values:<ul><li>`90`: -10%. To decrease your target ROI by 10% during promotion days.</li><li>`80`: -20%. To decrease your target ROI by 20% during promotion days.</li><li>`70`: -30%. To decrease your target ROI by 30% during promotion days.</li></ul> |
##| adjusted_roas_bid | number | Promotion days target ROI. |
##|budget_increase_percentage|integer|The increase percentage for the budget during promotion days.<br><br>A value of 50 means a 50% budget increase when the automatic budget increase is triggered.|
##|increase_limit|integer|The increase limit for the daily budget during promotion days.<br><br>A value of 10 means allows up to 10 budget increases per day when the automatic budget increases are triggered.|
##| current_budget | number |The specified campaign daily budget (`budget`). |
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
#| auto_budget_enabled{-to be deprecated} | boolean | Whether to enable automatic budget increase.<br>When your achieved ROI reaches at least 90% of the target and 80% or more of your budget has been used, your daily campaign budget may automatically increase by up to 10 times to capture more sales. Your budget will reset to its original amount each day.<br><br><ul><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `false`, your ROI target will decrease and your daily budget will automatically increase during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`) and custom promotion days as defined by `custom_schedule_list`. Your daily budget will reset to its original amount every day. During standard days, your ROI target and budget won't change.</li><li>When `is_enabled` within the object `promotion_days` is `true` and`auto_budget_enabled` is `true`, auto budget increase will be turned on during TikTok Shop promotion days (when `auto_schedule_enabled` is `true`), custom promotion days as defined by `custom_schedule_list`,<b>and standard days</b>. Your daily budget will reset to its original amount every day. During TikTok Shop promotion days and custom promotion days as defined by `custom_schedule_list`, your ROI target will decrease. During standard days, your ROI target won't change.</li><li>When `is_enabled` within the object `promotion_days` is `false` and`auto_budget_enabled` is `true`, your ROI won't change, your daily campaign budget may automatically increase up to 10 times to optimize gross revenue. Your daily budget will reset to its original amount every day.</li></ul><br>Supported values: `true`, `false`.|
#| schedule_type | string | Schedule type.<br><br>Enum values:<ul><li>`SCHEDULE_FROM_NOW`: To run the campaign continuously after the scheduled start time.</li><li>`SCHEDULE_START_END`: To run the campaign between the scheduled start time and end time.</li></ul> |
#| schedule_start_time | string | Campaign delivery start time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). |
#| schedule_end_time | string | Campaign delivery end time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0). |
#| placements | string[] | Placements.<br>The apps where you want to deliver your campaign. <br><br> Enum values: <ul><li>`PLACEMENT_TIKTOK`: TikTok.</li><li>`PLACEMENT_PANGLE`: Pangle.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>Product GMV Max Campaigns automatically explore and choose from the most extensive audience scope across TikTok and Pangle. Learn more [about Pangle](https://ads.tiktok.com/help/article/pangle-placement).</li><li>If you want to opt out of the Pangle placement for your Product GMV Max Campaigns, contact your TikTok representative.</li></ul>|
#| location_ids | string[] | The IDs of the targeted locations. <br><br> To learn about the corresponding location for a location ID, see [Location IDs](https://business-api.tiktok.com/portal/docs?id=1739311040498689). |
#| age_groups | string[] | The targeted age groups.<br><br> For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Age%20Group). |
#| product_video_specific_type | string | The video selection mode. <br><br>Enum values:<ul><li>`AUTO_SELECTION`: To autoselect videos.</li><li>`CUSTOM_SELECTION`: To manually select videos. </li><li>`UNSET`: Unset. </li></ul>|
#| identity_list | object[] | The list of identities (TikTok accounts) associated with the GMV Max Campaign. |
##| identity_id | string | Identity ID. |
##| identity_type | string | Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##| identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`. <br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities. <br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
##| store_id | string | Returned only when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
#|affiliate_posts_enabled |boolean|Whether to enable affiliate posts for your Product GMV Max Campaign.<br>Affiliate posts are TikTok posts created by affiliates (creators participating in the TikTok Shop Affiliate Program) and authorized for use in TikTok Shop Ads. Learn more [about affiliate creatives for TikTok Shop Ads](https://ads.tiktok.com/help/article/about-affiliate-creatives-for-tiktok-shop-ads).<br><br>Supported values: `true`, `false`.|
#| item_list| object[] | The list of authorized TikTok posts associated with the GMV Max campaign. <br>Authorized TikTok posts are existing posts featuring your selected products from your TikTok accounts or authorized using video codes. |
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
		"affiliate_posts_enabled": true,
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

