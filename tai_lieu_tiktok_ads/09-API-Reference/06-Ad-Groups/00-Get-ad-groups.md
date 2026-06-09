---
title: "Get ad groups"
doc_id: 1739314558673922
path: "API Reference / Ad Groups / Get ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get ad groups

Use this endpoint to obtain detailed information of an ad group or ad groups.

The returned information from this query can be customized by specifying the information needed in the `fields` field of the request. Use the `filtering` field to specify which ad group is returned. Supported filters include campaign ID, advertising objective , ad group ID, ad group name, ad group creation time, billing event, and the ad group status.


>  <span style="color:DodgerBlue">**Note**</span><br>If you wish to include deleted data in your query,  either specify the value of `STATUS_DELETE` in the `primary_status` field or `ADGROUP_STATUS_DELETE` /`ADGROUP_STATUS_CAMPAIGN_DELETE` in the `secondary_status` field. By default deleted data is not queried. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/get/|/v1.3/adgroup/get/|
|Request parameter name|`create_start_time` <br>`create_end_time`<br> `status`  |  `creation_filter_start_time`<br> `creation_filter_end_time`<br>`secondary_status`  |
|Request parameter data type|`advertiser_id`: number<br>`campaign_ids`: number[]<br>`adgroup_ids`: number[]|`advertiser_id`: string <br>`campaign_ids`: string[]<br>`adgroup_ids`: string[]|
|New request parameters|/|`promotion_type`<br>`exclude_field_types_in_response`<br>`creative_material_mode`<br>`bid_strategy`<br>`optimization_goal` <br>`campaign_system_origins` <br>`buying_types`<br>`split_test_enabled`<br>`campaign_automation_type`|
|Response parameter name|`is_share_disable`<br>`placement`<br>`enable_inventory_filter`<br>`external_type`<br> `external_action`<br> `deep_external_action` <br>`age`<br>`operation_system` <br>`connection_type`<br> `device_price`<br> `android_osv`<br> `ios_target_device` <br>`ios_osv`<br> `optimize_goal`<br> `bid`<br>`conversion_bid`<br>`deep_cpabid`<br>`daily_retention_ratio`<br>`status`<br>`opt_status`<br>`action_v2`<br>`user_actions`<br>`rf_buy_type`<br>`buy_impression`<br>`buy_reach`<br>`rf_predict_cpr`<br>`rf_predict_frequency`<br>`include_custom_actions`<br>`exclude_custom_actions`<br>`dpa_retargeting_type`<br>`brand_safety`<br>`enable_expansion`<br>`ad_app_profile_page_type`<br>`display_mode`|`share_disabled`<br>`placements`<br>`inventory_filter_enabled`<br>`promotion_type`<br>`optimization_event`<br>`secondary_optimization_event`<br>`age_groups`<br>`operating_systems`<br>`network_types`<br>`device_price_ranges`<br>`min_android_version`<br>`ios14_targeting`<br>`min_ios_version`<br>`optimization_goal`<br>`bid_price`<br>`conversion_bid_price`<br>`deep_cpa_bid`<br>`next_day_retention`<br>`secondary_status`<br>`operation_status`<br>`actions`<br>`video_user_actions`<br>`rf_purchased_type`<br>`purchased_impression`<br>`purchased_reach`<br>`rf_estimated_cpr`<br>`rf_estimated_frequency`<br>`included_custom_actions`<br>`excluded_custom_actions`<br>`shopping_ads_retargeting_type`<br>`brand_safety_type`<br>`expansion_enabled`<br>`adgroup_app_profile_page_state`<br>`delivery_mode`|
|Response parameter data type|`advertiser_id`: number<br>`campaign_id`: number<br>`adgroup_id`: number<br>`app_id`: number <br>`store_id`: number <br>`pixel_id`: number<br>`skip_learning_phase`: number<br>`catalog_id`: number<br>`schedule_id`: number |`advertiser_id`: string <br>`campaign_id`: string<br>`adgroup_id`: string<br>`app_id`: string <br>`store_id`: string<br>`pixel_id`: string <br>`skip_learning_phase`: boolean<br>`catalog_id`: string <br>`schedule_id`: string |
|Response parameter name and data type|`is_comment_disable`: number<br> `shop_authorized_bc`: number<br> `audience`: number[]<br> `excluded_audience`: number[]  <br>`location`: number[]  <br>`interest_category_v2`: number[]<br>`interest_keywords`: number[] <br>`device_models`: number[]<br>`carriers_v2`: number[] <br> `video_download`: string  <br> `pangle_block_app_list_id`: number[]<br> `action_categories`: number[]  <br> `pangle_audience_package_include`: number[]<br> `pangle_audience_package_exclude`: number[]<br>`catalog_authorized_bc`: number<br>`automated_targeting`: string |`comment_disabled`: boolean<br> `store_authorized_bc_id`: string<br> `audience_ids`: string[] <br> `excluded_audience_ids`: string[]<br> `location_ids`: string[]<br>`interest_category_ids`: string[]<br>` interest_keyword_ids `: string[] <br>` device_model_ids `: string[] <br>`carrier_ids`: string[] <br>` video_download_disabled`: boolean <br> `blocked_pangle_app_ids`: string[]<br> `action_category_ids`: string[]<br> ` included_pangle_audience_package_ids`: string[]<br> ` excluded_pangle_audience_package_ids`: string[] <br>`catalog_authorized_bc_id`: string<br>`auto_targeting_enabled`: boolean |
|New response parameters|/ | `shopping_ads_type`<br> `product_source`<br>`split_test_group_id`<br>`split_test_status`<br> `contextual_tag_ids`<br> `purchase_intention_keyword_ids`<br>`spending_power`<br>`zipcode_ids`<br>`isp_ids`<br>`category_exclusion_ids`<br>`vertical_sensitivity_id`<br>`is_smart_performance_campaign`<br> `search_result_enabled`<br>`click_attribution_window` <br>`view_attribution_window`<br>`attribution_event_count` <br>`shopping_ads_retargeting_custom_audience_relation`   <br>`smart_audience_enabled` <br>`smart_interest_behavior_enabled` <br>`campaign_system_origin` <br>`engaged_view_attribution_window`  <br>`vbo_window`<br>`predict_impression` <br>`topview_reach_range`<br>`pre_discount_cpm` <br>`cpm`<br> `discount_type`<br> `discount_amount`<br> `discount_percentage`<br>`pre_discount_budget`<br>`tiktok_subplacements`<br> `messaging_app_type` <br>`messaging_app_account_id` <br> `phone_region_code`<br> `phone_region_calling_code`<br>`phone_number`<br>`message_event_set_id` <br> `deep_funnel_optimization_status` <br>`deep_funnel_event_source`  <br> `deep_funnel_event_source_id`   <br>`deep_funnel_optimization_event`<br> `search_keywords`<br> `automated_keywords_enabled`<br> `app_config` <br> `custom_conversion_id`<br>`is_lhf_compliance`<br>`campaign_automation_type` |
|Response parameters deprecated in v1.3|/ |`carriers` |
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/adgroup/get/


**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|fields |string[]| Fields that you want to get. When not specified, all fields are returned by default. For allowed fields, see the fields under `list` in the response section.|
|exclude_field_types_in_response| string[]|The type of fields that you want to remove from the response.<br>Allowed enum values: <ul><li>`NULL_FIELD`: Fields with null values.</li></ul>|
|filtering |object|Filter conditions. Set these conditions according to your requirements. If not set, all ad groups under the advertiser will be returned. <br><br>Example: `filtering=&#123;"campaign_automation_type":"UPGRADED_SMART_PLUS"&#125;`|
#| campaign_automation_type | string | Campaign automation type.<br><br>Enum values:<ul><li>`MANUAL`: Manual Campaigns.</li><li>`SMART_PLUS`: Smart+ Campaigns.</li><li>`UPGRADED_SMART_PLUS`: Upgraded Smart+ Campaigns, a new automated campaign type. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).</li></ul> |
#|campaign_ids |string[]| List of campaign IDs. <br>Max size: 100. |
#| campaign_system_origins| string[] | The origins (sources) of the campaigns that the ad groups belong to. <br> <br> Enum values: <ul><li>`PROMOTE`: The campaign is a Promote campaign created through the TikTok mobile App.</li><li>`TT_ADS_PLATFORM`: The campaign is a non-Promote campaign created through the API or in TikTok Ads Manager. </li></ul> Default value: `&#91;"TT_ADS_PLATFORM"&#93;`.<br><br>Only the following settings can be retrieved for a Promote ad group: <ul><li>`advertiser_id`</li><li>`campaign_id`</li><li> `campaign_name`</li><li>`adgroup_id` </li><li>`adgroup_name` </li><li>`create_time`</li><li>`budget` </li><li>`schedule_type`</li><li>`schedule_start_time`</li><li> `schedule_end_time`</li><li>`operation_status`</li><li>`secondary_status`</li></ul><br> To learn more about the availability of and the supported filters for Promote ad groups, see [Promote campaigns](https://business-api.tiktok.com/portal/docs?id=1785880454546433). |
#|adgroup_ids |string[]| List of Ad group IDs. <br>Max size: 100. |
#|adgroup_name |string|Ad group name. |
#|primary_status |string|Primary status. For enum values, see [Enumeration - Primary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). The default value is `STATUS_NOT_DELETE`, which returns ad groups in all statuses excluding `STATUS_DELETE`. If you want to get ad groups in all statuses including `STATUS_DELETE`, use `STATUS_ALL` instead.|
#|secondary_status|string| Ad group secondary status. For enum values, see [Enumeration - Ad Group Status - Secondary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>This field is not returned in the sandbox environment because the ad group is not actually delivered.</li><li>See [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002) to learn about the valid values you can pass in via `secondary_status` for the primary status you specify.</li></ul>|
#|objective_type |string| Advertising objective. <br><br>For details, see [Enumeration - Advertising Objective](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).<br><br><p><span style="color:darkred"><b>Note</b></span>: Whether you choose `WEB_CONVERSIONS` or  `CONVERSIONS` as the objective filter, we will return data of both `WEB_CONVERSIONS` and  `CONVERSIONS`.  |
#| buying_types | string[] | Filter by buying types. <br><br>Enum values: <ul><li> `AUCTION`: Auction ads. </li><li>`RESERVATION_RF`: Reservation Reach & Frequency ads and TikTok Pulse ads.</li><li>`RESERVATION_TOP_VIEW`: Reservation TopView ads.</li></ul><br>Default value: `&#91;"AUCTION", "RESERVATION_RF"&#93;`.<br><br> To learn more about the availability of TopView ads in the API, see [TopView](https://business-api.tiktok.com/portal/docs?id=1804258360285186). <br><br><p><span style="color:darkred"><b>Note</b></span>: The enum value `RESERVATION_TOP_VIEW` cannot be combined with other values. To filter TopView ads, set this field to `["RESERVATION_TOP_VIEW"]`. |
#|optimization_goal|string|Optimization goal.   Enum values:  [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Optimization%20Goal).|
#|promotion_type |string| The promotion type (optimization location) that you want to filter by. <br>Enum values: <ul><li>`APP`: App. </li><li>`WEBSITE`: Landing page. </li><li>`INSTANT_FORM`: Instant Form. </li><li>`LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE`: TikTok direct message.</li><li>`LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE`: Social media app.  </li><li> `LEAD_GEN_CLICK_TO_CALL`: Phone call. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> This filter field is different from the `promotion_type` used for ad group creation, which is also returned in the response of this endpoint. `promotion_type` as filter is also supported in synchronous basic reports and synchronous DSA reports.</li><li> See [Appendix-Mapping between different promotion types](https://ads.tiktok.com/marketing_api/docs?id=1758485431181314) to learn about how you can set this field to filter out certain promotion types that are used for ad group creation.</li></ul> |
#|bid_strategy |string| Bidding strategy. Enum values: `BID_STRATEGY_COST_CAP`,` BID_STRATEGY_BID_CAP`, `BID_STRATEGY_MAX_CONVERSION` and `BID_STRATEGY_LOWEST_COST`|
#|creative_material_mode |string| The strategy that your creatives will be delivered.  <br><br>Enum values: `CUSTOM`(custom), `SMART_CREATIVE`(Smart Creative). |
#|billing_events |string[]|Events that you want to pay for.  For enum values, see [Enumeration - Billing Event](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|creation_filter_start_time |string|Filter by lower range of ad group creation time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time zone). Ad groups created later than this time will be returned.<br>Suggestion: A time range within 6 months is suggested when applying a creation time filter, to ensure that the success and speed of the task won't be affected.|
#|creation_filter_end_time|string|Filter by higher range of ad group creation time, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time zone). Ad groups created earlier than this time will be returned.<br>Suggestion: A time range within 6 months is suggested when applying a creation time filter, to ensure that the success and speed of the task won't be affected.|
#|split_test_enabled|boolean|Filter by whether split test has been enabled for the ad group or not.<br>`true`: Only get ad groups with enabled split test,  `false`: Only get disabled ad groups. <br><p><span style="color:darkred"><b>Note</b></span>: If you do not specify this field, you will get all ad groups.|
|page |number|Current page number. <br><br>Default value: 1. Value range: ≥ 1.|
|page_size |number|Page size. <br><br>Default value is: 10. Value range: 1-1,000.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you use the enum value `RESERVATION_TOP_VIEW` for the filter field `buying_types`, the maximum value you can specify for this field is 100. |
```

### Example
#### Get undeleted ad groups within your ad account
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}' \
--header 'Access-Token: {{Access-Token}}'

(/code)
```

#### Get all ad groups within your ad account
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}&filtering={"primary_status":"STATUS_ALL"}' \
--header 'Access-Token: {{Access-Token}}'

(/code)
```

#### Get ad groups within a specific campaign
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}&filtering={"campaign_ids":["{{campaign_id}}"]}' \
--header 'Access-Token: {{Access-Token}}'

(/code)
```

#### Get ad groups created within a specific time range
```xcodeblock
(code curl http)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/adgroup/get/?advertiser_id={{advertiser_id}}&filtering={"creation_filter_start_time":"2024-01-01 00:00:00","creation_filter_end_time":"2024-01-30 00:00:00"}' \
--header 'Access-Token: {{Access-Token}}'

(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log id of a request, which uniquely identifies the request.|
|data |object|Returned data. The returned list is sorted by ad group ID (`adgroup_id`) in reverse order by default. <br><br><p><span style="color:darkred"><b>Note</b></span>: The data for [Product GMV max ads](https://ads.tiktok.com/help/article/about-automated-product-ads?lang=en) and LIVE GMV max ads created from TikTok Shop is excluded from the response. Shopping Ads created via API or TikTok Ads Manager are not affected.|
#|list |object[]|Data list. The returned fields are generated based on the `fields` field in the request parameters. If not specified in the request, all fields are returned by default.|
##|advertiser_id |string|Advertiser ID|
##|campaign_id |string| Campaign ID |
##|campaign_name |string|The name of the campaign that the ad group belongs to.|
##| campaign_system_origin | string | Returned for Promote ad groups only. <br>Not returned for non-Promote ad groups. <br><br>The origin (source) of the campaign that the ad group belongs to. <br><br>Enum values: <ul><li> `PROMOTE`: The campaign is a Promote campaign created through the TikTok mobile App.</li></ul> <br>Only the following settings can be retrieved for a Promote ad group (`campaign_system_origin` is `PROMOTE` at the campaign level): <ul><li>`advertiser_id`</li><li>`campaign_id`</li><li> `campaign_name`</li><li>`adgroup_id` </li><li>`adgroup_name` </li><li>`create_time`</li><li>`budget` </li><li>`schedule_type`</li><li>`schedule_start_time`</li><li> `schedule_end_time`</li><li>`operation_status`</li><li>`secondary_status`</li></ul>|
##| campaign_automation_type | string | Campaign automation type.<br><br>Enum values:<ul><li>`MANUAL`: Manual Campaigns.</li><li>`SMART_PLUS`: Smart+ Campaigns.</li><li>`UPGRADED_SMART_PLUS`: Upgraded Smart+ Campaigns, a new automated campaign type. To learn more about Upgraded Smart+ Campaigns, see [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458).</li></ul> |
##| is_smart_performance_campaign | boolean | Whether the campaign is an automated campaign type.<br><br>Supported values:<ul><li>`true`: The campaign is a legacy Smart+ Campaign.</li><li>`false`: The campaign is a Manual Campaign or an Upgraded Smart+ Campaign.<ul><li>To determine whether a campaign is a Manual Campaign or an Upgraded Smart+ Campaign, check the returned `campaign_automation_type`.<ul><li>When `campaign_automation_type` is `MANUAL`, the campaign is a Manual Campaign.</li><li>When `campaign_automation_type` is `UPGRADED_SMART_PLUS`, the campaign is an Upgraded Smart+ Campaign .</li></ul></li></ul></li></ul> |
##|adgroup_id |string| Ad group ID |
##|adgroup_name |string| Ad group name|
##| create_time | string | The time at which the ad group was created, in the format of `"YYYY-MM-DD HH:MM:SS"`. <br><br>Example: `"2023-01-01 00:00:01"`. |
##| modify_time | string | The time at which the ad group was modified, in the format of `"YYYY-MM-DD HH:MM:SS"`.  <br><br> Example: `"2023-01-01 00:00:01"`. |
##|shopping_ads_type|string|Returned when `objective_type` at the campaign level is `PRODUCT_SALES`.<br><br>Shopping ads type. <br><br>Enum values: <ul><li>`VIDEO`: Video Shopping Ads.</li><li>`LIVE`: Live Shopping Ads.</li><li>`PRODUCT_SHOPPING_ADS`: Product Shopping Ads.</li><li>`CATALOG_LISTING_ADS`: Catalog Listing Ads.</li><li>`UNSET`: Unset.</li></ul>|
##|identity_id |string|Returned when `shopping_ads_type` is `VIDEO` and `product_source` is `SHOWCASE` or `shopping_ads_type` is `LIVE`.<br><br>Identity ID. |
##|identity_type|string|Returned when `shopping_ads_type` is `VIDEO` and `product_source` is `SHOWCASE` or `shopping_ads_type` is `LIVE`.<br><br>Identity type. Enum values: `AUTH_CODE` (Authorized Post User), `TT_USER` (TikTok Business Account User), `BC_AUTH_TT`(the TikTok account that a Business Center is authorized to access). Returned when `objective_type` is `PRODUCT_SALES`. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. |
##|identity_authorized_bc_id|string|Returned when `identity_type` is `BC_AUTH_TT`.<br><br>  ID of the Business Center that a TikTok Account User in Business Center identity is associated with.  |
##|product_source|string|Returned for Video Shopping Ads and Product Shopping Ads.<br><br>Product source where you want to get products for promotion. <br>Enum values: `UNSET` ,`CATALOG`(Catalog), `STORE` (TikTok Shop) ,`SHOWCASE`(TikTok Showcase).|
##|catalog_id|string| Returned in any of the following scenarios:<ul><li>`product_source` is `CATALOG` or `STORE`.</li><li>`shopping_ads_type` is `LIVE` and this field is specified in the request.</li></ul><br>Catalog ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, `catalog_id` will no longer be returned for newly created Shopping Ads where `product_source` is `STORE` because the specified `catalog_id` will be ignored. Existing Shopping Ads where `product_source` is `STORE` will not be affected unless you update them. If you update the existing ad groups, the existing `catalog_id`, if present for the ad groups, will no longer be returned.|
##|catalog_authorized_bc_id|string| Returned in any of the following scenarios:<ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `CATALOG` or `STORE`.</li><li>`shopping_ads_type` is `LIVE` and this field is specified in the request.</li></ul><br>For catalogs in Business Center, this field returns the ID of the Business Center that a catalog belongs to.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, `catalog_authorized_bc_id` will no longer be returned for newly created Shopping Ads where `product_source` is `STORE` because the specified `catalog_authorized_bc_id` will be ignored. Existing Shopping Ads where `product_source` is `STORE` will not be affected unless you update them. If you update the existing ad groups, the existing `catalog_authorized_bc_id`, if present for the ad groups, will no longer be returned.|
##|store_id |string| Returned in any of the following scenarios:<ul><li>`shopping_ads_type` is `VIDEO` and `product_source` is `STORE`.</li><li>`shopping_ads_type` is `PRODUCT_SHOPPING_ADS` and `product_source` is `STORE`.</li><li>`shopping_ads_type` is `LIVE` and this field is specified in the request.</li></ul><br>ID of the TikTok Shop.  |
##|store_authorized_bc_id |string|Returned when `store_id` is passed. <br><br>ID of the Business Center that is authorized to access the store (`store_id`). |
##|promotion_type |string|Promotion type (Optimization location).<br> You can decide where you'd like to promote your products using this field. For value definitions, see [Enumeration - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##|promotion_target_type |string| The promotion type for Lead Generation objective. <br>Enum values: <ul><li> `INSTANT_PAGE`：Instant Form. To create a fast-loading in-app TikTok Instant Form to collect more leads.</li><li> `EXTERNAL_WEBSITE`：Website Form. To use a landing page that has the Website Form or the TikTok Instant Page that redirects to the website with the Website Form to collect more leads. </li><li>`UNSET`.</li></ul> |
##| messaging_app_type | string | The type of instant messaging app or customized URL to use in the Instant Messaging Ad Group.<br><br>Enum values:  <ul><li> `MESSENGER`: Messenger. </li><li>`WHATSAPP`: WhatsApp.</li><li>`ZALO`: Zalo. </li><li>`LINE`: Line.</li><li>`IM_URL`: Instant Messaging URL.</li></ul>|
##| messaging_app_account_id | string | The ID of the instant messaging app account.<ul><li>When `messaging_app_type` is `MESSENGER`, this field represents the Facebook Page ID.</li><li>When `messaging_app_type` is `LINE`, this field represents the LINE Business ID.</li><li>When `messaging_app_type` is `WHATSAPP`, this field represents the WhatsApp phone number automatically populated based on the specified `phone_region_code`, `phone_region_calling_code`, and `phone_number`.</li></ul>|
##| phone_region_code | string | The region code for the WhatsApp or Zalo phone number. <br><br> Example: `US`. |
##| phone_region_calling_code | string | The region calling code for the WhatsApp or Zalo phone number. <br><br>Example: `+1`. |
##| phone_number | string | The WhatsApp or Zalo phone number. |
##|promotion_website_type|string|TikTok Instant Page type in the ad group.  <br>Enum values:<ul><li>`UNSET`: To not use TikTok Instant Page. </li><li>`TIKTOK_NATIVE_PAGE`: To use TikTok Instant Page.  </li></ul>|
##|app_id |string|The App ID of the app to promote.You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint.|
##|app_type |string| The type of the promoted app. Enum values: `APP_ANDROID` (Android), `APP_IOS` (iOS).|
##|app_download_url |string|App download link|
##|pixel_id |string|Pixel ID. Only applicable for landing pages. |
##|optimization_event |string|Conversion event for the ad group. See [Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889) for more. |
##|custom_conversion_id|string|The ID of the Custom Conversion used in the ad group.|
##| app_config | object[] | Returned when `sales_destination` is `WEB_AND_APP` at the campaign level.<br><br>  Details of the app or apps to promote. |
###| app_id | string | The App ID of the app to promote. |
##| deep_funnel_optimization_status | string | The status of deep funnel optimization. <br> With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.  <br> <br>Enum values:  <ul><li>`ON`: enabled.  </li><li> `OFF`: disabled.  </li></ul>|
##| deep_funnel_event_source| string | Returned when `deep_funnel_optimization_status` is `ON`.   <br> <br> The event source type. <br> <br>Enum values: <ul><li>`PIXEL`: Pixel.  </li><li>`OFFLINE`: Offline Event Set. </li><li>`CRM`: CRM Event Set.</li></ul> |
##| deep_funnel_event_source_id | string | Returned when `deep_funnel_optimization_status` is `ON`. <br> <br> Event Source ID.  <br> <br><ul><li>When `deep_funnel_event_source` is `PIXEL` , this field represents a Pixel ID.</li><li>When `deep_funnel_event_source` is `OFFLINE`, this field represents an Offline Event Set ID.  </li><li>When `deep_funnel_event_source` is `CRM`, this field represents a CRM Event Set ID.</li></ul> |
##| deep_funnel_optimization_event | string | Returned when `deep_funnel_optimization_status` is `ON`.  <br> <br>Deep funnel optimization event.   <br> <br>Example: `SHOPPING`. |
##|placement_type |string|The placement strategy that decides where your ads will be shown. Enum values: `PLACEMENT_TYPE_AUTOMATIC` (Automatic placement), `PLACEMENT_TYPE_NORMAL` (Select placement). |
##|placements | string[] | The apps where you want to deliver your ads. For enum values, see [Enumeration - Placement](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> If `placement_type` of the ad group is `PLACEMENT_TYPE_AUTOMATIC` (Automatic placement), the placements supported for your advertising objective will be returned for this field. For instance, if the actual supported placement is TikTok only, the returned value for this field will be `PLACEMENT_TIKTOK`. <ul><li> However, if `PLACEMENT_GLOBAL_APP_BUNDLE` is supported for your objective, but you are not allowlisted for the Global App Bundle placement feature, `PLACEMENT_GLOBAL_APP_BUNDLE` will not be included in the returned value for this field.</li></ul></li><li> If `placement_type` of the ad group is `PLACEMENT_TYPE_NORMAL` (Select placement), the placements you specified through `placements` in the request will usually be returned for this field. <ul><li> The only exception is that if `PLACEMENT_GLOBAL_APP_BUNDLE` is specified through `placements` in the request, but you are not allowlisted for the Global App Bundle placement feature, `PLACEMENT_GLOBAL_APP_BUNDLE` will be filtered out from the returned value for this field. For instance, if you specify placements as `["PLACEMENT_TIKTOK", "PLACEMENT_GLOBAL_APP_BUNDLE"]` in the request, but you are not allowlisted for Global App Bundle, the returned value for this field will be `["PLACEMENT_TIKTOK"]`. </li></ul> </li></ul>|
##| tiktok_subplacements | string[] | The subplacements within TikTok for your ads, allowing you to choose where your ads will appear. <br><br>Enum value: <ul><li>`IN_FEED`: In-feed. Ads will be placed in the For You feed and might also be placed in Profile Page and Following feeds.</li><li>`SEARCH_FEED`: Search feed.</li><li>`TIKTOK_LITE`: TikTok Lite, a streamlined version of TikTok that features a smaller app size and faster video loading speed. The TikTok Lite subplacement is currently available for Japan or South Korea as targeting locations.</li><li>`LEMON8`: Lemon8, a community app for lifestyle content, focusing on real-life experiences, tips, guides, and product reviews. By including Lemon8 as a subplacement, your ads will appear in its For You feed, Search feed, and Immersive Video feed. [Learn more about Lemon8](https://ads.tiktok.com/help/article/about-lemon8-in-tiktok-ads-manager)</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `tiktok_subplacements` is not specified when you create the ad group, the value of this field will be an empty list (`[]`). |
##| search_result_enabled | boolean | Whether to include your ads in Search Ads, namely to show your ads to users when they search for your business on TikTok. |
##|automated_keywords_enabled| boolean |Whether to enable automated keywords and let the system automatically generate keywords after you add ads. This expands high-quality traffic to improve performance. View high-performing automated keywords in reporting.<br><br>Supported values: `true`, `false`. |
##|search_keywords|object[] |Returned only for Search Ads Campaigns.<br><br> A list of search keywords, that is, words or phrases that are used to match your ads with the terms people are searching for.  |
###|keyword | string | The search keyword. |
###|match_type | string | The match type for the search keyword.<br><br>Enum values:<ul><li>`PRECISE_WORD`: exact match. Your ads will only show for user search queries that exactly match these keywords.</li><li>`PHRASE_WORD`: phrase match. Your ads will only show for these specific keywords if users search these keywords in the same order.</li><li>`BROAD_WORD`: broad match. Your ads will show for user search queries for any of the words you have included, even if the words are in a different order.</li></ul> |
###|keyword_bid_type | string | The bid type for the keyword and match type combination.<br><br>Enum values:<ul><li>`FOLLOW_ADGROUP`: To use the ad group level bid (`bid_price`) as the `keyword_bid`.</li><li>`CUSTOM`: To customize the bid via `keyword_bid`.</li></ul> |
###|keyword_bid | float | The bid price for the keyword and match type combination. |
###|audit_status | string | The review status of the search keyword.<br><br>Enum values:<ul><li>`AUDITING`: The keyword is under review.</li><li>`PASS`: The keyword has passed review and can be delivered.</li><li>`REJECTED`: The keyword failed to pass the review and cannot be delivered.</li></ul>|
###|reject_info | object[] | Returned only when `audit_status` is `REJECTED`.<br><br>Details about the rejection.|
####|forbidden_location| string |The targeted region that failed the review. <br><br>For enum values, see [Appendix - Location codes](https://business-api.tiktok.com/portal/docs?id=1737585867307010).|
####|reject_reasons| object[] |List of rejection reasons.|
#####|reason| string |The rejection reason. |
##|comment_disabled |boolean|Whether to allow comments on your ads on TikTok.  |
##|video_download_disabled |boolean| Whether users can download your video ads on TikTok. |
##|share_disabled|boolean|Whether sharing to third-party platforms is disabled for ads in this ad group. |
##|blocked_pangle_app_ids |string[]|Pangle app block list ID.|
##|audience_type |string| App retargeting audience type. For enum values, see [Enumeration - App Retargeting Audience Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
##|audience_rule |object| Rules that specify your audience.  |
##|auto_targeting_enabled{-To be deprecated}|boolean|Whether to enable automated targeting. <br><br><p><span style="color:darkred"><b>Note</b></span>:  Starting June, 2024, you can no longer enable Automatic targeting or Targeting expansion for your ad groups. To ensure a smooth API integration, we recommend you migrate to [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
##| shopping_ads_retargeting_type | string | Returned when `shopping_ads_type` is `VIDEO` and `product_source` is `CATALOG`. <br><br>The retargeting type of shopping ads. Enum values: <ul><li>`LAB1`: Retargeting audiences who viewed products or added products to cart but didn't purchase products. </li><li>`LAB2`: Retargeting audiences who added products to cart but didn't purchase products. </li><li>`LAB3`: Retargeting audiences using custom combination. </li><li>`OFF`: No retargeting. </li></ul> |
##| shopping_ads_retargeting_actions_days | number | The valid time range for the specified audience action. Audiences who have completed the specified action within the time range will be retargeted by the shopping ads. <br><br>Value range: 1, 2, 3, 7, 14, 30, 60, 90, 180. |
##| included_custom_actions | object[] | The custom action that you want to use as "Include" conditions for filtering the shopping ads audiences to be retargeted. |
###| code | string | The custom action used to filter the audiences to be retargeted. Enum values: <ul><li>`VIEW_PRODUCT`: The audience viewed the product. </li><li>`ADD_TO_CART`: The audience added the product to the cart. </li><li>`PURCHASE`: The audience purchased the product. </li></ul>|
###| days | integer | The time range used to filter the audiences that completed the specified action. Value range: [1,180]. |
##| excluded_custom_actions | object[] | The custom action that you want to use as "Exclude" conditions for filtering the shopping ads audiences to be retargeted. |
###| code | string | The custom action used to filter the audiences to be retargeted. Enum values: <ul><li>`VIEW_PRODUCT`: The audience viewed the product. </li><li>`ADD_TO_CART`: The audience added the product to the cart. </li><li>`PURCHASE`: The audience purchased the product. </li></ul>|
###| days | integer | The time range used to filter the audiences that didn't complete the specified action. Value range: [1,180]. |
##| shopping_ads_retargeting_custom_audience_relation | string | The logical relation between the Video Shopping Ads (VSA) retargeting audience specified by `shopping_ads_retargeting_type` and the custom audience specified by `audience_ids`.<br><br>Enum values: <ul><li>`OR`: To combine the VSA retargeting audience and the custom audience to create the targeted audience. The targeted audience will consist of individuals who belong to either the VSA retargeting audience or the custom audience. </li><li>`AND`: To intersect between the VSA retargeting audience and the custom audience to create the targeted audience. The targeted audience will consist of individuals who belong to both the VSA retargeting audience and the custom audience. </li></ul>|
##|location_ids |string[]|IDs of the targeted locations. |
##| zipcode_ids|string[]|Zip code IDs or postal code IDs of the targeted locations. <br><p><span style="color:darkred"><b>Note</b></span>:<ul><li> Zip code targeting is currently only supported for the US and postal code targeting is currently only supported for Canada, Brazil, Indonesia, Thailand, and Vietnam. </li><li> To get information about zip code IDs or postal code IDs, you can only use [/tool/targeting/info/](https://ads.tiktok.com/marketing_api/docs?id=1761237001980929).</li></ul>|
##|languages |string[]|Codes of the languages that you want to target. For the list of languages codes supported, see [Enumeration - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|gender |string|Gender that you want to target. Enum values: `GENDER_FEMALE`,`GENDER_MALE`,`GENDER_UNLIMITED`|
##|age_groups |string[]|Age groups you want to target. For enum values, see [Enumeration - Age Group](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|spending_power|string|Spending power that you want to target. Enum values: `ALL`, `HIGH`.   If it is set to `HIGH`, you can target high spending users who typically spend more on purchases on TikTok ads than average users.|
##|household_income| string[]| Household income that you want to target. Enum values: `TOP5`(Top 5% of ZIP codes), `TOP10`(Top 10% of ZIP codes), `TOP10_25`(Top 10% -25% of ZIP codes), `TOP25_50`(Top 25% - 50% of ZIP codes).  |
##|audience_ids|string[]|A list of audience IDs. |
##| smart_audience_enabled | boolean | Whether Smart audience is turned on. <br><br>Enum values: `true`, `false`. <br><br>To learn more about Smart audience and how to turn on Smart audience, see [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
##|excluded_audience_ids |string[]|A list of excluded audience ID.|
##|interest_category_ids |string[]|IDs of general interest keywords that you want to use to target audiences. |
##|interest_keyword_ids|string[]|IDs of additional interest keywords that you want to use to target audiences. |
##|purchase_intention_keyword_ids|string[]|IDs of purchase intention categories that you want to use to target audiences with an interest in purchases related to a content category. |
##|actions| object[] | A list of targeting behavioral category objects.|
###|action_scene | string | The type of user behavior that you want to target.<br><br> Enum values: <ul><li>`VIDEO_RELATED`: Video interactions.</li><li>`CREATOR_RELATED`: Creator interactions.</li><li>`HASHTAG_RELATED`: Hashtag interactions.</li></ul>|
###|action_period |number| The time period to include behaviors from.  |
###|video_user_actions|string[]| The specific user interactions that you want to target for the user behavior type.<br><br><ul><li>If `action_scene` is `VIDEO_RELATED`, the allowed values are: `WATCHED_TO_END`,`LIKED`,`COMMENTED`,`SHARED`.</li><li> If `action_scene` is `CREATOR_RELATED`, the allowed values are: `FOLLOWING`, `VIEW_HOMEPAGE`. </li><li>If `action_scene` is `HASHTAG_RELATED`, the allowed value is `VIEW_HASHTAG`.</li></ul> |
###|action_category_ids |string[]|IDs of the video interactions categories, creator interactions categories, hashtags, or hashtag bundles that you want to use to target audiences.|
##| smart_interest_behavior_enabled | boolean | Whether Smart interests & behaviors is turned on. <br><br>Enum values: `true`, `false`. <br><br>To learn more about Smart interests & behaviors and how to turn on Smart interests & behaviors, see [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
##|included_pangle_audience_package_ids |string[]| IDs of the Pangle audiences that you want to include. Valid only for Pangle placement. You can get audience IDs (`package_id`) by using the [/pangle_audience_package/get/](https://ads.tiktok.com/marketing_api/docs?id=1740040177229826) endpoint. You need to set `bind_type` to `INCLUDE`.  Do not specify this field and `excluded_pangle_audience_package_ids` at the same time.|
##|excluded_pangle_audience_package_ids |string[]| IDs of the Pangle audiences that you want to exclude. Valid only for Pangle placement. You can get audience IDs (`package_id`) by using the [/pangle_audience_package/get/](https://ads.tiktok.com/marketing_api/docs?id=1740040177229826) endpoint. You need to set `bind_type` to `EXCLUDE`. Do not specify this field and `included_pangle_audience_package_ids` at the same time.|
##|operating_systems |string[]|Device operating systems that you want to target. Only one value is allowed.  Enum: `ANDROID`, `IOS`|
##|min_android_version|string|Minimum Android version. For enum values, see [Enumeration - Minimum Android Version](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|ios14_targeting|string|The iOS devices that you want to target. <br><br>Enum values:<ul><li>`UNSET`: Devices with iOS 14.4 or earlier versions. </li><li>`IOS14_MINUS`: Devices with iOS 14.0 or earlier versions, which are not affected by the iOS 14 privacy update. </li><li>`IOS14_PLUS`: Devices with iOS 14.5 or later versions. The iOS 14 privacy update has been enforced in this group of devices. This value is only supported for Dedicated Campaigns. </li><li>`ALL`:  Devices with iOS 14.5 or later versions. The iOS 14 privacy update has been enforced in this group of devices. This value is only supported for iOS App Retargeting ads and iOS retargeting Video Shopping Ads with product source as catalog and optimization location as App.  </li></ul>|
##|min_ios_version|string|Audience minimum ios version. For enum values, see [Enumeration - Minimum iOS Version](https://ads.tiktok.com/marketing_api/docs?id=1738308662898689).|
##|ios14_quota_type|string|Whether the campaign will be counted against the iOS 14 dedicated campaign quota. Enum: `OCCUPIED`, `UNOCCUPIED`. For non-R&F campaigns, when `ios14_targeting` is `IOS14_PLUS`, this field is automatically set to `OCCUPIED`.|
##|device_model_ids| string[]| List of device model IDs. For more details about device models, see [Device Models](https://ads.tiktok.com/marketing_api/docs?id=1737172880570369).|
##|network_types |string[]|Network types that you want to target. For enum values, see [Enumeration - Connection Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|carrier_ids |string[]| Carriers  that you want to target. A carrier is valid only when the `in_use` field for the carrier is `true`. For detailed information, see [get carriers](https://ads.tiktok.com/marketing_api/docs?id=1737168013095938). |
##| isp_ids | string[] |IDs of the targeted Internet service providers. |
##|device_price_ranges|number[]|Targeting device price range. 10000 means 1000+. The numbers must be in multiples of 50. <br><b><span style="color: darkred">Important</span></b>: The upper limit you set will be added by 50 and the resulting new number will be used as the actual upper limit for device targeting. The actual upper limit is shown in the ad group settings in TikTok Ads Manager. If you set and get the price range of [0, 250], it actually means [0, 300].|
##|targeting_expansion{-To be deprecated}| object | Settings about targeting expansion.<br><br><p><span style="color:darkred"><b>Note</b></span>:  Starting June, 2024, you can no longer enable Automatic targeting or Targeting expansion for your ad groups. To ensure a smooth API integration, we recommend you migrate to [Smart Targeting](https://business-api.tiktok.com/portal/docs?id=1783164662979586). |
###|expansion_enabled|boolean| Whether to enable targeting expansion. |
###|expansion_types |string[]| The target audience types that you want to expand.|
##|saved_audience_id|string|Returned when you have specified `saved_audience_id` when creating an ad group. <br>Saved Audience ID.|
##|contextual_tag_ids |string[]|Contextual tag IDs. See [Contextual targeting](https://ads.tiktok.com/marketing_api/docs?id=1745292519923713)  to learn more about how to use contextual targeting.|
##|brand_safety_type|string|Brand safety type. Enum values: <ul><li>`NO_BRAND_SAFETY`:  To not use any brand safety solution.  Full inventory, which means your ads may appear next to some content featuring mature themes.</li><li>`EXPANDED_INVENTORY`: Use TikTok's brand safety solution. Expanded inventory means that your ads will appear next to content where most inappropriate content has been removed, and that does not contain mature themes. In the next API version, `EXPANDED_INVENTORY` will replace `NO_BRAND_SAFETY` and will be the default brand safety option. </li><li>`STANDARD_INVENTORY`: Use TikTok's brand safety solution. Standard inventory means that ads will appear next to content that is appropriate for most brands. </li><li>`LIMITED_INVENTORY`: Use TikTok's brand safety solution. Limited inventory means that your ads will not appear next to content that contains mature themes.</li><li>`THIRD_PARTY`: Use a third-party brand safety solution.</li></ul> To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid first-party Brand Safety filtering for `APP_PROMOTION`,  `WEB_CONVERSIONS`, `TRAFFIC`,  `LEAD_GENERATION` and `PRODUCT_SALES` objectives in Auction ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li> Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li></ul>|
##|brand_safety_partner|string| Brand safety partner. Available only when `brand_safety_type` is `THIRD_PARTY`. Enum values: `IAS`, `OPEN_SLATE`(The partner is named <strong>DoubleVerify</strong> on TikTok Ads Manager because the partner has been acquired by DoubleVerify). <br>To get the countries and regions that your ads can be deployed to based on your brand safety settings, use the [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. You need to pass in the brand safety type and brand safety partner.<br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> Pre-bid third-party brand safety solutions are currently allowlist-only features. If you would like to access them, please contact your TikTok representative. </li></ul>|
##|inventory_filter_enabled|boolean|Inventory filtering (filtering security videos, hides unsafe videos), valid only for the `PLACEMENT_TIKTOK` placement. Optional values are: true to filter, false not to filter. |
##|category_exclusion_ids|string[]|Content exclusion category IDs.|
##|vertical_sensitivity_id|string|Vertical sensitivity category ID.  |
##|budget_mode |string|Budget mode. If Campaign Budget Optimization is enabled, `BUDGET_MODE_INFINITE` will be returned. For more information about budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). |
##|budget |float|Ad group budget. <br><br>Returns 0.0 when Campaign Budget Optimization (`budget_optimize_on`) is on.<br><br> For TopView ad groups, this field represents the estimated ad group budget after applying any discount.|
##|scheduled_budget|float|Scheduled ad budget for next day. A value not equal to 0 means the scheduled budget is set and the value represents the budget; a value equals to 0 means the scheduled budget is not set.|
##|schedule_type |string|The schedule type can be either `SCHEDULE_START_END` or `SCHEDULE_FROM_NOW`. If you choose `SCHEDULE_START_END`, you need to specify a start time and an end time. If you choose `SCHEDULE_FROM_NOW`, you only need to specify a start time.|
##|schedule_start_time |datetime|Ad delivery start time (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`.|
##|schedule_end_time |datetime|Ad delivery end time (UTC+0), in the format of `YYYY-MM-DD HH:MM:SS`.|
##| predict_impression | number | Returned only for TopView ads. <br><br>The estimated number of impressions counted in thousands.<br><br>For example, a value of `1291` represents an estimated 1,291,000 impressions. |
##| topview_reach_range | number[] | Returned only for TopView ads. <br><br> The estimated range of reach counted in thousands. <br><br>For example, a value of `[342, 456]` represents an estimated reach of between 342,000 and 456,000 individuals. <br><br>To determine the estimated frequency range, use the following formula: <ul><li>frequency = impressions /reach</li></ul>For example, if `predict_impression` is `1291` (representing 1,291,000) and `topview_reach_range` is `[342, 456]` (representing 342,000 to 456,000), the estimated frequency range will be 2.8 to 3.8. |
##| pre_discount_cpm | number | Returned only for TopView ads. <br><br>The estimated cost per mille (CPM) before applying any budget discount. <br><br>Example: 11.33. |
##| cpm | number | Returned only for TopView ads.<br><br>The estimated cost per mille (CPM) after applying any budget discount. <br><br>Example: 0. |
##| discount_type | string | Returned only for TopView ads.<br><br>The type of discount applied to the budget. <br><br> Enum values: <ul><li>`NO_DISCOUNT`: No discount applied.</li><li> `BY_PERCENTAGE`: Discount applied as a percentage.</li><li>`BY_AMOUNT`: Discount applied as a fixed amount. </li></ul>|
##| discount_amount | number | Returned only for TopView ads when `discount_type` is `BY_AMOUNT`.<br><br>The fixed amount by which the budget is discounted.<br><br>Example: 14615.8. |
##| discount_percentage | number | Returned only for TopView ads when `discount_type` is `BY_PERCENTAGE`. <br><br>The percentage by which the budget is discounted. |
##| pre_discount_budget | number | Returned only for TopView ads.<br><br>The budget amount before applying any discount. <br><br> Example: 14616. |
##|schedule_infos |object[]| Ad delivery information of R&F ad groups. |
###|schedules |object[]| The details of the scheduled delivery for the ad. |
####|start_time |string| Ad delivery start time. |
####|end_time |string| Ad delivery end time. |
###|expected_orders |number[]| The delivery order for an ad within the ad group. <br><br>For example, a value of `[1]` indicates that the ad will be delivered first.|
###|is_draft |boolean| Whether the ad delivery information is in draft mode.<br><br>If the value of this field is `true`, then the ad delivery information defined in `schedule_infos` has not been associated with any specific ads. <br><br>To associate the ad delivery information with ads, pass the value of the returned `schedule_id` to the request parameter `schedule_id` in [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). After you associate the ad delivery information with an ad, the value of this field will automatically be set to `false`, and the value of `schedule_id` will be the ID of the ad that you have associated the ad delivery information with. |
###|schedule_id |string| Schedule ID. <br><br>You can associate a schedule ID with an ad through the `schedule_id` in the request of [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). After you associate the `schedule_id` with an ad, the value of this field will set to the ID of the ad that you have associated the ad delivery information with, and `is_draft` will automatically be set to `false`.  |
##|delivery_mode |string|  The strategy for sequencing and scheduling your ad delivery in a Reach & Frequency ad group. <br><br>Enum values:<ul><li>`STANDARD`: Standard delivery. Your ads will be distributed evenly and are expected to achieve similar traffic size. </li><li>`SCHEDULE`: Scheduled delivery.  Set specific time periods to deliver each ad in. </li><li>`SEQUENCE`: Sequenced delivery. Set a specific sequence to deliver your ads in. </li><li>`VIEW_OPTIMIZE`: View optimized delivery. Your ads are distributed to maximize view-through rate (VTR), with more impressions allocated to ads predicted to achieve higher VTR.</li><li>`OPTIMIZE`: Optimized delivery that delivers ads to achieve the best performance.</li></ul>|
##|dayparting |string|"Ad delivery arrangement, in the format of a string that consists of 48 x 7 characters. Each character is mapped to a 30-minute timeframe from Monday to Sunday. Each character can be set to either 0 or 1.  1 represents delivery in the 30-minute timeframe, and 0 stands for non-delivery in the 30-minute timeframe. The first character is mapped to 0:01-0:30 of Monday; The second character is mapped to 0:31-1:00 of Monday, and the last character represents 23:31-0:00 Sunday.<p><span style="color: darkred"><b>Note</b></span><br>Not specified, all-0, or all-1 are considered as full-time delivery. </p>|
##|optimization_goal|string|The measurable results that you'd like to drive your ads with. For enum values, see [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|secondary_optimization_event|string|The secondary goal when `optimization_goal` is `INSTALL` or `VALUE`. For enum values, see [Conversion events - Secondary-goal events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889).|
##| message_event_set_id | string | The ID of the message event set to use in the Instant Messaging Ad Group. |
##|frequency |number| Frequency, together with `frequency_schedule`, controls how often people see your ad (only available for `REACH` ads). <br>The below conditions should be both met:<ul><li>1 <= `frequency` <= 1000 </li><li>1 <= `frequency_schedule` <= 30 </li></ul>For example, `frequency` = 2 & `frequency_schedule` = 3 means "show ads no more than twice every 3 day". |
##|frequency_schedule |number| Frequency_schedule, together with `frequency`, controls how often people see your ad (only available for `REACH` ads). See `frequency` fields for more. |
##|bid_type |string|Bidding strategy that determines how the system manages your cost per result, spends your budget, and how it delivers ads. <br data-tomark-pass>See [Enumeration - Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|bid_price |float|Bid price. The average cost per result that you want to achieve (for Cost Cap bidding strategy). |
##|conversion_bid_price |float|Where you can set a target cost per conversion for oCPM(Optimized Cost per Mille).|
##|deep_bid_type |string|Bidding strategy for in-app events. For enum values and their descriptions, see [Enumeration - Deep-level Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
##|roas_bid|float|ROAS goal for Value Optimization.|
##| vbo_window | string | The time window of the specified bidding strategy for [VBO IAA](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app advertising) or [VBO IAP](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app purchase).  <br><br> Enum values: <ul><li>`SEVEN_DAYS`: The first seven days (day 7).  </li><li>`ZERO_DAY`: The current day (day 0). </li></ul>|
##|bid_display_mode|string|How you calculate and measure Cost per View.  |
##|deep_cpa_bid |float|Specify bid price in this field after you've chosen a bidding strategy for in-app events, for example VO_MIN.|
##|cpv_video_duration |string|Optimized video playback duration <br data-tomark-pass> Optional values include: `SIX_SECONDS` (video playback 6 seconds) and ` TWO_SECONDS` (video playback 2 seconds) |
##|next_day_retention | float | Day 2 retention ratio. Formula: `next_day_retention` = `conversion_bid_price`/`deep_cpa_bid`. Value range is (0,1]. Only valid when `placements` is `PLACEMENT_PANGLE` and `secondary_optimization_event` is `NEXT_DAY_OPEN`. |
##| click_attribution_window | string | Click-through window for the ad group. This attribution window is the time between when a person clicks your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off. </li><li> `ONE_DAY`: 1-day click.</li><li>`SEVEN_DAYS`: 7-day click. </li><li>`FOURTEEN_DAYS`: 14-day click.</li><li> `TWENTY_EIGHT_DAYS`: 28-day click. </li></ul>|
##| engaged_view_attribution_window | string | Engaged view-through window for the ad group. This attribution window is the time after someone watches at least 6 seconds of your video ad that a conversion is counted.<br><br>Enum values: <ul><li>`ONE_DAY`: 1-day engaged view.</li><li>`SEVEN_DAYS`: 7-day engaged view.</li></ul>|
##| view_attribution_window | string | View-through window for the ad group. This attribution window is the time between when a person views your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off.</li><li>`ONE_DAY`: 1-day view.</li><li>`SEVEN_DAYS`: 7-day view.</li></ul>|
##| attribution_event_count | string | Event count (Statistic type) for the ad group. <br>The way that people's actions are counted after only viewing or clicking an ad. <br><br>Enum values: <ul><li>`UNSET`: Unset. </li><li> `EVERY`: Every. To count multiple events from someone as separate conversions. </li><li> `ONCE`: Once. To count multiple events from someone as 1 conversion.</li></ul>|
##|billing_event |string| Billing event. <br>To learn about the enum values, see [Enumerations - Billing Event](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Billing%20Event).|
##|pacing |string| You can choose between `PACING_MODE_SMOOTH` and `PACING_MODE_FAST`. For `PACING_MODE_SMOOTH`, the budget is allocated evenly within the scheduled time. `PACING_MODE_FAST` would consume budget and produce results as soon as possible. When Campaign Budget Optimization (`budget_optimize_on`) is on, your setting will be ignored and it will be set as `PACING_MODE_SMOOTH`. Otherwise, this field is required. |
##|operation_status |string|Operation status.<br><br>Enum values:<ul><li>`ENABLE` : The ad group is enabled (in 'ON' status).</li><li>`DISABLE`: The ad group is disabled (in 'OFF' status). </li><li>`FROZEN`: The ad group is terminated and cannot be enabled.</li></ul> |
##|secondary_status |string|Ad group status（secondary status). For enum values, see [Enumeration - Ad Group Status - Secondary Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: This field is not returned in the sandbox environment because the ad group is not actually delivered.|
##|statistic_type |string| Conversion bid statistic type, bid for `EVERYTIME` (Each Purchase)/ `NONE` (Unique Purchase)|
##|is_hfss |boolean|Whether the promoted content is HFSS (High Fat, Salt, Sugar) Product/Brand.<br><br>Supported values: `true`,`false`. |
##| is_lhf_compliance | boolean | Whether the promoted content complies with LHF (Less Healthy Foods) regulations.<br><br>When `is_lhf_compliance` is `true`, you confirm that any food or drink products you advertise on TikTok in the UK comply with the [2024 Less Healthy Foods Regulations](https://www.legislation.gov.uk/uksi/2024/1266/made) and all other applicable laws.<br><br>Supported values: `true`,`false`. |
##|creative_material_mode |string|The strategy that your creatives will be delivered. <br><br>Enum values: `CUSTOM`(custom), `DYNAMIC`(Automated Creative Optimization), and `SMART_CREATIVE`(Smart Creative). <br><br>For Upgraded Smart+ Ad Groups, this field will be null.|
##|adgroup_app_profile_page_state|string| Indicates whether the adgroup is using app profile page. Enum: `INVALID`, `UNSET`, `ON`, `OFF`.|
##|feed_type |string| Feed type option. Enum values: `STANDARD_FEED`, `TOP_FEED`.  |
##| rf_purchased_type  | string | Billing method of Reach & Frequency ad groups. For more details, see [Enumeration - Reach & Frequency Buy Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). <br><p><span style="color:darkred"><b>Note</b></span>: For non-Reach & Frequency ads, the value of this field will always be null.|
##| purchased_impression | number | Impressions to be purchased. <br><p><span style="color:darkred"><b>Note</b></span>: For non-Reach & Frequency ads, the value of this field will always be null.|
##| purchased_reach  | number | Purchased user reach. <br><p><span style="color:darkred"><b>Note</b></span>: For non-Reach & Frequency ads, the value of this field will always be null. |
##| rf_estimated_cpr | number |  The estimated cost per mile reach. <br><p><span style="color:darkred"><b>Note</b></span>: For non-Reach & Frequency ads, the value of this field will always be null.|
##| rf_estimated_frequency | number | The estimated show frequency. <br><p><span style="color:darkred"><b>Note</b></span>: For non-Reach & Frequency ads, the value of this field will always be null.|
##| split_test_group_id | string | Split test group ID. Returned if the ad group is part of a split test. |
##| split_test_status | string | Split test status. Returned if the ad group is part of a split test. |
##|is_new_structure|boolean|Whether the campaign is a new structure |
##|skip_learning_phase |boolean|Whether to skip the learning stage. |
##|conversion_window (deprecated) |string| The time frame when you would like a conversion to happen after a user clicks on or views your ad. Your ad delivery will be optimized using the conversion data during the time frame you select. This setting will not impact your attribution data. For enum values, ee [Enumeration - Conversion Window](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|page_info |object|Paging information |
##|page |number|current page number |
##|page_size |number|Paging Size |
##|total_number |number|Total, the total number of eligible ad groups |
##|total_page |number|total pages |
```
### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "budget_mode": "BUDGET_MODE_TOTAL",
                "included_custom_actions": [],
                "delivery_mode": null,
                "auto_targeting_enabled": false,
                "app_download_url": null,
                "placements": [
                    "PLACEMENT_TIKTOK"
                ],
                "pixel_id": null,
                "conversion_window": null,
                "schedule_type": "SCHEDULE_START_END",
                "brand_safety_partner": null,
                "interest_category_ids": [],
                "adgroup_id": "{{adgroup_id}}",
                "campaign_name": "{{campaign_name}}",
                "schedule_infos": null,
                "share_disabled": false,
                "brand_safety_type": "NO_BRAND_SAFETY",
                "campaign_id": "{{campaign_id}}",
                "ios14_quota_type": "UNOCCUPIED",
                "network_types": [],
                "app_type": null,
                "bid_type": "BID_TYPE_NO_BID",
                "conversion_bid_price": 0,
                "optimization_goal": "CLICK",
                "deep_bid_type": null,
                "video_download_disabled": false,
                "schedule_end_time": "{{schedule_end_time}}",
                "scheduled_budget": 0,
                "is_hfss": false,
                "frequency": null,
                "bid_price": 0,
                "actions": [],
                "deep_cpa_bid": 0,
                "adgroup_name": "{{adgroup_name}}",
                "category_id": "0",
                "purchased_impression": null,
                "budget": {{budget}},
                "app_id": null,
                "promotion_type": "WEBSITE",
                "age_groups": null,
                "next_day_retention": null,
                "pacing": "PACING_MODE_SMOOTH",
                "device_price_ranges": null,
                "location_ids": [
                    "{{location_id}}"
                ],
                "secondary_status": "ADGROUP_STATUS_CREATE",
                "billing_event": "CPC",
                "advertiser_id": "{{advertiser_id}}",
                "create_time": "{{create_time}}",
                "operation_status": "ENABLE",
                "placement_type": "PLACEMENT_TYPE_NORMAL",
                "optimization_event": null,
                "audience_ids": [],
                "secondary_optimization_event": null,
                "excluded_custom_actions": [],
                "languages": [],
                "modify_time": "{{modify_time}}",
                "excluded_audience_ids": [],
                "rf_estimated_cpr": null,
                "skip_learning_phase": false,
                "operating_systems": [
                    "ANDROID"
                ],
                "creative_material_mode": "CUSTOM",
                "search_result_enabled": false,
                "rf_purchased_type": null,
                "inventory_filter_enabled": false,
                "adgroup_app_profile_page_state": null,
                "rf_estimated_frequency": null,
                "gender": "GENDER_UNLIMITED",
                "statistic_type": null,
                "purchased_reach": null,
                "is_new_structure": true,
                "interest_keyword_ids": [],
                "bid_display_mode": "CPMV",
                "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
                "keywords": null,
                "schedule_start_time": "{{schedule_start_time}}",
                "comment_disabled": false,
                "frequency_schedule": null,
                "feed_type": null
            },
            {
                "budget_mode": "BUDGET_MODE_TOTAL",
                "included_custom_actions": [],
                "delivery_mode": null,
                "auto_targeting_enabled": false,
                "app_download_url": null,
                "placements": [
                    "PLACEMENT_TIKTOK"
                ],
                "pixel_id": null,
                "conversion_window": null,
                "schedule_type": "SCHEDULE_START_END",
                "brand_safety_partner": null,
                "interest_category_ids": [],
                "adgroup_id": "{{adgroup_id}}",
                "campaign_name": "{{campaign_name}}",
                "schedule_infos": null,
                "share_disabled": false,
                "brand_safety_type": "NO_BRAND_SAFETY",
                "campaign_id": "{{campaign_id}}",
                "ios14_quota_type": "UNOCCUPIED",
                "network_types": [],
                "app_type": null,
                "bid_type": "BID_TYPE_NO_BID",
                "conversion_bid_price": 0,
                "optimization_goal": "CLICK",
                "deep_bid_type": null,
                "video_download_disabled": false,
                "schedule_end_time": "{{schedule_end_time}}",
                "scheduled_budget": 0,
                "is_hfss": false,
                "frequency": null,
                "bid_price": 0,
                "actions": [],
                "deep_cpa_bid": 0,
                "adgroup_name": "{{adgroup_name}}",
                "category_id": "0",
                "purchased_impression": null,
                "budget": {{budget}},
                "app_id": null,
                "promotion_type": "WEBSITE",
                "age_groups": null,
                "next_day_retention": null,
                "pacing": "PACING_MODE_SMOOTH",
                "device_price_ranges": null,
                "location_ids": [
                    "{{location_id}}"
                ],
                "secondary_status": "ADGROUP_STATUS_CREATE",
                "billing_event": "CPC",
                "advertiser_id": "{{advertiser_id}}",
                "create_time": "{{create_time}}",
                "operation_status": "ENABLE",
                "placement_type": "PLACEMENT_TYPE_NORMAL",
                "optimization_event": null,
                "audience_ids": [],
                "secondary_optimization_event": null,
                "excluded_custom_actions": [],
                "languages": [],
                "modify_time": "{{modify_time}}",
                "excluded_audience_ids": [],
                "rf_estimated_cpr": null,
                "skip_learning_phase": false,
                "operating_systems": [
                    "ANDROID"
                ],
                "creative_material_mode": "CUSTOM",
                "search_result_enabled": false,
                "rf_purchased_type": null,
                "inventory_filter_enabled": false,
                "adgroup_app_profile_page_state": null,
                "rf_estimated_frequency": null,
                "gender": "GENDER_UNLIMITED",
                "statistic_type": null,
                "purchased_reach": null,
                "is_new_structure": true,
                "interest_keyword_ids": [],
                "bid_display_mode": "CPMV",
                "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
                "keywords": null,
                "schedule_start_time": "{{schedule_start_time}}",
                "comment_disabled": false,
                "frequency_schedule": null,
                "feed_type": null
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 1,
            "total_number": 2
        }
    }
}
(/code)
```


