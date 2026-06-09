---
title: "(To be deprecated) Create a Legacy Smart+ Campaign"
doc_id: 1767334204047362
path: "API Reference / (To be deprecated) Legacy Smart+ / (To be deprecated) Create a Legacy Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Create a Legacy Smart+ Campaign

Use this endpoint to create a Smart+ Campaign. 


> <span style="color:DarkRed">**Important**</span><br>
> - **Upgraded Smart+ API**:
>     - We are actively upgrading the Smart+ API to deliver enhanced capabilities and performance. The new Upgraded Smart+ API is already launched. Learn more about [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458) and [available Upgraded Smart+ API endpoints](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
>     - **Get started**:
>         - **Reporting**: No additional allowlist required.
>         - **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.  
> - **Legacy API Deprecation**: 
>     - After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
>     - Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
> - **Legacy API deprecation extension request process for existing legacy Smart+ creation developers**:
>     - 1. **Submit a request**: If you require an extension, please submit an application via the [External Extension Request Form for Legacy Smart+ Creation API](https://bytedance.sg.larkoffice.com/share/base/form/shrlgsm2yMsQaSoAsMy77tcAzng).
>     - 2. **Allowlist processing**: After receiving your request, we will process an allowlist entry for the associated developer app ID. After the process, developers will be able to continue calling the `/campaign/spc/create/` endpoint until **June 30, 2026**.
>     - 3. **Extended timeline for strong business needs**: If you have a strong business case requiring an extension beyond June 30, 2026, please specify your use case in the application form, or contact your sales representative directly.
## Before you start
Check whether your ad account has reached the Smart+ Campaign quota by using [/campaign/spc/quota/get/](https://business-api.tiktok.com/portal/docs?id=1831564498409473).

If you are unable to create new Smart+ Campaigns because you have reached the quota limit, use `/campaign/spc/quota/get/` to identify active Smart+ Campaigns. You can then pause some of the active campaigns to free up quota using [/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178).


## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/


**Method** POST

**Header**

```xtable
|Field{35%}|Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|operation_status|string|The status of the campaign when created. <br><br>Enum values: <ul><li>`ENABLE`: The campaign is enabled when created.<li>`DISABLE`: The campaign is disabled when created.</ul>Default value: `ENABLE`.<br><br>If you want to update the status of the campaign after creation, use the [/campaign/status/update](https://ads.tiktok.com/marketing_api/docs?id=1739320994354178) endpoint.|
|objective_type {Required}|string| Advertising objective. <br><br>Currently, we support `APP_PROMOTION`, `WEB_CONVERSIONS`, and `LEAD_GENERATION`.<br><br>To learn about the detailed steps for creating Smart+ Lead Generation Campaigns, see [Create Smart+ Lead Generation Campaigns](https://business-api.tiktok.com/portal/docs?id=1827645480080385).|
| spc_type | string | Valid only when `objective_type` is `WEB_CONVERSIONS`. <br><br>The web campaign type. <br><br>Enum values: <ul><li>`WEB_ALL_IN_ONE`: Smart+ Web Campaign, where the supported optimization goals include Conversion, Value, Click and Landing page view.</li><li>`UNSET`(<span style="color:Red">to-be-deprecated</span>): Smart Performance Web Campaign, where the supported optimization goals are limited to Conversion, Value, and Click. </li></ul>Default value: `UNSET`. <br><br>To learn about the detailed steps for creating Smart+ Web Campaigns, see [Create Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to [Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186) as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.</li><li>Once set, this field cannot be updated. </li></ul> |
| web_all_in_one_catalog_status | string | Valid only when `spc_type` is `WEB_ALL_IN_ONE`. <br><br>The status of catalog in the Smart+ Web Campaign. <br><br>Enum values: <ul><li> `OPEN`: enabled. Smart+ Web Campaigns with catalog enabled are Smart+ Catalog Ads. To learn about the detailed steps for creating Smart+ Catalog Ads, see [Create Smart+ Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20Smart+%20Catalog%20Ads). </li><li>`UNSET`: disabled. Smart+ Web Campaigns with catalog disabled are regular Smart+ Web Campaigns. To learn about the detailed steps for creating regular Smart+ Web Campaigns, see [Create a regular Smart+ Web Campaign](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20a%20regular%20Smart+%20Web%20Campaign). </li></ul>Default value: `UNSET`. |
|app_promotion_type {+Conditional}|string| Required when `objective_type` is `APP_PROMOTION`. <br><br>App promotion type. <br><br>Currently, we only support  `APP_INSTALL`.|
|virtual_objective_type|string|The new objective type.<br><br>Enum value: `SALES`.<br><br>Use this field to create a campaign with the Sales objective, the combined objective for Website Conversions and Product Sales objectives. Learn more about [the Sales objective](https://business-api.tiktok.com/portal/docs?id=1834189283112962).|
|sales_destination{+Conditional}|string|Required and valid only when `virtual_objective_type` is specified.<br><br>Sales destination, the destination where you want to drive your sales.<br><br>Enum values: <ul><li>`WEBSITE`: Website. Drive sales on your website.</li><li>`WEB_AND_APP`: Website and app. Drive sales on both your website and your app.<ul><li>When `sales_destination` is set to `WEB_AND_APP`, you need to set `web_all_in_one_catalog_status` to `OPEN`.</li><li>To learn about how to create Smart+ Campaigns with this setting, see [Create Smart+ Catalog Ads with Website and App Optimization](https://business-api.tiktok.com/portal/docs?id=1842232769643905).</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Creating Smart+ Catalog Ads with Website and App Optimization (with `sales_destination` set to `WEB_AND_APP`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>|
|campaign_type|string|Campaign type.<br><br>Enum values: `REGULAR_CAMPAIGN`, `IOS14_CAMPAIGN`.<br>Default value: `REGULAR_CAMPAIGN`.|
|campaign_app_profile_page_state|string|Whether to use app profile page to optimize delivery. <br><br>Enum values: `ON`, `OFF`. <br>Default value: `OFF`. <br><br><p><span style="color:darkred"><b>Note:</b></span><ul><li>You can use the field only when `objective_type` is `APP_PROMOTION`and your campaign is an iOS 14 Dedicated Campaign. Otherwise, an error will occur.</li><li>When `ON` is selected, pass in the `page_id` of App Profile Page.<ul><li>  To create an App Profile Page, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881) and set  `businessType` to `4`.</li></ul></li></ul>|
| is_advanced_dedicated_campaign | boolean | Whether the campaign is an Advanced Dedicated Campaign. Advanced Dedicated Campaigns leverage advanced delivery models that benefit from real-time signals. <br><br>Enum values: `true`, `false`. <br>Default value: `false`. <br><br>To learn about how to create such campaigns, see [Create Advanced Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1797011827608577). <br><br><p><span style="color:darkred"><b>Note</b></span>: Once set, this field cannot be updated. |
| disable_skan_campaign | boolean | Valid only when the following conditions are all met: <ul><li>`objective_type` is `APP_PROMOTION`.</li><li>`app_promotion_type` is `APP_INSTALL`.</li><li>`campaign_type` is `IOS14_CAMPAIGN`.</li><li>`app_id` is set to the ID of an iOS app that is eligible for [Advanced Dedicated Campaigns](https://business-api.tiktok.com/portal/docs?id=1797011827608577). To confirm whether an app is eligible for Advanced Dedicated Campaigns, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786) and check the returned `advanced_dedicated_campaign_allowed`.</li></ul><br>Whether to disable SKAN (SKAdNetwork) attribution, Apple's conversion attribution solution for iOS campaigns<br><br>Enum values:<ul><li>`true`: To disable SKAN attribution. The campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [Self Attribution Network (SAN) metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) for the campaign. However, you cannot retrieve [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. Learn more about [SAN integration](https://ads.tiktok.com/help/article/about-self-attribution-transition). <ul><li>For a Dedicated Campaign using an app that is eligible for Advanced Dedicated Campaigns, you need to enable SKAN attribution or Advanced Dedicated Campaign or both. You cannot disable SKAN attribution and Advanced Dedicated Campaign simultaneously.</li></ul></li><li>`false`: To enable SKAN attribution. The campaign will be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [SKAN metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign.<ul><li>If you enable Advanced Dedicated Campaign by setting `is_advanced_dedicated_campaign` to `true` simultaneously, you'll be able to retrieve both [SAN](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) and [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. </li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Disabling SKAN attribution for Dedicated Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>If you are allowlisted for disabling SKAN attribution for Dedicated Campaigns, you cannot set `bid_type` to `BID_TYPE_CUSTOM` when the following conditions are all met:<ul><li>`is_advanced_dedicated_campaign` is `false`.</li><li>`disable_skan_campaign` is `false`.</li><li>`optimization_goal` is `INSTALL` or `IN_APP_EVENT`.</li></ul></li><li>Once set, this field cannot be updated.</li><li>Getting access to Advanced Dedicated Campaign requires the app to meet certain criteria. If your app is ineligible for Advanced Dedicated Campaign, reach out to your TikTok representative. Your TikTok representative will be able to provide troubleshooting support to help your app get access to Advanced Dedicated Campaign.</li></ul> |
|campaign_name {Required}|string| Campaign name.  |
|special_industries|string[]|Ad categories. Enum values: <ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</ul><span style="color:darkred"><b>Note:</b></span><br><ul><li>Once you've specified the industry type, the system will adjust your target options to help you comply with advertising policies. See [Ad targeting](https://ads.tiktok.com/marketing_api/docs?id=1739381236849665) for details.<li>This field is only supported for advertisers who are registered in the US or Canada.</ul>|
| product_source {+Conditional} | string | Required when `web_all_in_one_catalog_status` is `OPEN`.  <br><br> Product source where you want to get products for promotion.  <br><br>Enum value: `CATALOG` (catalog). |
| catalog_id {+Conditional} | string | Required when `web_all_in_one_catalog_status` is `OPEN`.  <br><br> The ID of the catalog to use in the Smart+ Catalog Ads.  <br>Currently, you can only specify an E-commerce catalog. <br><br> To retrieve the catalogs within your Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). |
| catalog_authorized_bc_id {+Conditional} | string | Required when `web_all_in_one_catalog_status` is `OPEN`. <br><br>The ID of the Business Center that the catalog (`catalog_id`) belongs to. |
| product_specific_type {+Conditional} | string | Required when `web_all_in_one_catalog_status` is `OPEN`.<br><br> Different dimensions to choose products.<br><br> Enum values: <ul><li>`ALL`: Allow TikTok to dynamically choose from all products. </li><li> `PRODUCT_SET`: Specify a product set. TikTok will dynamically choose products from this set.</li><li>`CUSTOMIZED_PRODUCTS`: Specify a customized number of products.</li></ul> <br><ul><li>If this field is set to `ALL`, you don't need to input `product_set_id` and `product_ids`. </li><li>If this field is set to `PRODUCT_SET`, you need to pass in `product_set_id`. </li><li>If this field is set to `CUSTOMIZED_PRODUCTS`, `product_ids` is required.</li></ul> |
| product_set_id {+Conditional} | string | Required when `product_specific_type` is `PRODUCT_SET`. <br><br> The ID of a product set. <br><br> <ul><li>To retrieve the product sets within your ad account, use [/catalog/set/get/](https://business-api.tiktok.com/portal/docs?id=1740570556295169). </li><li>To create a product set, use [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).</li></ul>|
| product_ids {+Conditional} | string[] | Required when `product_specific_type` is `CUSTOMIZED_PRODUCTS`. <br><br> The product IDs of the catalog products. <br><br> Max size: 20. <br><br> To retrieve the product ID (`product_id`) of each catalog product, use [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402). |
|promotion_type {Required}|string|Promotion type (Optimization location). You can decide where you'd like to promote your products using this field.<br><br>Currently, we support `APP_ANDROID`, `APP_IOS`, `WEBSITE`, and `LEAD_GENERATION`.<br><br><ul><li>When `objective_type` is `APP_PROMOTION`, set this field to `APP_ANDROID` or `APP_IOS`.</li><li>When `objective_type` is `WEB_CONVERSIONS`, set this field to `WEBSITE`.</li><li>When `objective_type` is `LEAD_GENERATION`, set this field to `LEAD_GENERATION`.</li></ul>|
|app_id {+Conditional}|string|Required when `objective_type` is `APP_PROMOTION` and `app_promotion_type` is `APP_INSTALL`.<br><br>The App ID of the app to promote.<br><br> You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint. |
|promotion_website_type|string|Valid only when `objective_type` is `WEB_CONVERSIONS` and `spc_type` is `UNSET` or not passed. <br><br>TikTok Instant Page type. <br><br>Currently, we only support `TIKTOK_NATIVE_PAGE`(To use TikTok Instant Page). <br><br> If you want to use TikTok Instant Page  in your ads,  you need to pass `page_id` of the TikTok Instant Page and `pixel_id` is not supported.|
|promotion_target_type {+Conditional}|string|Required when `objective_type` is `LEAD_GENERATION`.<br><br>The optimization location for Lead Generation objective.<br><br>Enum values:<ul><li>`INSTANT_PAGE`: Instant Form. To create a fast-loading in-app TikTok Instant Form to collect more leads.</li><li>`EXTERNAL_WEBSITE`: Website Form. To use a landing page that has the Website Form or the TikTok Instant Page that redirects to the website with the Website Form to collect more leads.</li></ul>|
|optimization_goal {Required}|string|The measurable results you'd like to drive with your campaigns. <br><br>Currently, we support `CLICK`, `INSTALL`, `IN_APP_EVENT`, `VALUE`, `CONVERT`, `TRAFFIC_LANDING_PAGE_VIEW`, and `LEAD_GENERATION`.<br><br>To find the detailed description for each optimization goal, see [Enumeration - Optimization Goal](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Optimization%20Goal).|
|pixel_id {+Conditional}|string|<ul><li>Required when `optimization_goal` is set to `CONVERT` or `VALUE`.</li><li>Not supported when `optimization_goal` is not set to `CONVERT` or `VALUE`.</li></ul><br>Pixel ID.<br><br>To obtain the list of Pixel IDs within your ad account, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978).|
|optimization_event{+Conditional}|string|Required in any of the following scenarios:<ul><li>`pixel_id` is specified.</li><li>`pixel_id` is not specified and `optimization_goal` is `IN_APP_EVENT` or `VALUE`.</li></ul><br>Conversion event.<br><br>For the supported app and pixel events, see [Conversion events](https://business-api.tiktok.com/portal/docs?id=1739361474981889).<br>To find out the supported events for your app, use [/app/optimization_event/](https://business-api.tiktok.com/portal/docs?id=1740859338750977).|
|app_config {+Conditional}|object[]|Required when `sales_destination` is `WEB_AND_APP`.<br><br>Details of the app or apps to promote.<br><br>Max size: 2.<br><br>You can specify any of the following in this field:<ul><li>an Android app</li><li>an iOS app</li><li>an Android app and an iOS app</li></ul>|
#|app_id {+Conditional}|string|Required when `app_config` is specified.<br><br>The App ID of the app to promote.<br><br>To obtain the list of App IDs within your ad account, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786) and check the returned `app_id`.|
| deep_funnel_optimization_status | string | Valid only when `promotion_type` is `LEAD_GENERATION`. <br><br> The status of deep funnel optimization.  <br>Deep funnel optimization optimizes both your upper funnel events and deeper funnel events. You can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.  <br><br>Enum values:   <ul><li>`ON`: To enable deep funnel optimization. </li><li> `OFF`: To disable deep funnel optimization. </li></ul>Default value: `OFF`. <br><br> To learn about how to configure deep funnel optimization for Smart+ Lead Generation Campaigns, see [Create Smart+ Lead Generation Campaigns](https://business-api.tiktok.com/portal/docs?id=1827645480080385).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li><li>Deep funnel optimization with Pixel or Offline events is generally available. </li></ul>  |
| deep_funnel_event_source {+Conditional} | string | Required when `deep_funnel_optimization_status` is `ON`.  <br><br> The event source type. <br><br> Enum values:  <ul><li>`PIXEL`: Pixel. </li><li>`OFFLINE`: Offline Event Set.</li><li>`CRM`: CRM Event Set.</li></ul> |
| deep_funnel_event_source_id {+Conditional} | string | Required when `deep_funnel_optimization_status` is `ON`.  <br><br>Event Source ID.  <br><br><ul><li>When `deep_funnel_event_source` is `PIXEL`, specify a Pixel ID via this field.  <ul><li> To obtain a list of Pixels, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978).</li></ul></li><li>When `deep_funnel_event_source` is `OFFLINE`, specify an Offline Event Set ID via this field.  <ul><li>To obtain a list of Offline Event Set IDs, use [/offline/get/](https://business-api.tiktok.com/portal/docs?id=1765596808589313).  </li></ul></li><li>When `deep_funnel_event_source` is `CRM`, specify a CRM Event Set ID via this field.  <ul><li>To obtain a list of CRM Event Set IDs, use [/crm/list/](https://business-api.tiktok.com/portal/docs?id=1780896521680898).  </li></ul></li></ul>|
| deep_funnel_optimization_event {+Conditional} | string | Required when `deep_funnel_optimization_status` is `ON`. <br><br>Deep funnel optimization event. <br><br><ul><li>To find out the supported values for Standard Events available for Pixels, Offline Event Sets, and CRM Event Sets, see the <b>"Event name for ad creation"</b> column in [Supported Pixel events](https://business-api.tiktok.com/portal/docs?id=1739585696931842).  </li><li>To find the list of optimization events for a Pixel, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978) and check the returned `optimization_event`. </li></ul><br>Example: `SHOPPING`.   |
|bid_type|string|Bidding strategy that determines how the system manages your cost per result, spends your budget, and how it delivers campaigns.<br><br>Enum values:<ul><li>`BID_TYPE_NO_BID`: Maximum Delivery.</li><li>`BID_TYPE_CUSTOM`: Cost Cap (or Target CPA).</li></ul>|
| bid_price {+Conditional} | float | Required when the following conditions are met:<ul><li>`spc_type` is `WEB_ALL_IN_ONE`</li><li>`optimization_goal` is `CLICK` </li><li>`bid_type` is `BID_TYPE_CUSTOM`</li></ul><br> The target cost per click. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. <br><br>`bid_price` needs to be lower than budget. See [Bidding-Bidding limits](https://business-api.tiktok.com/portal/docs?id=1745292444424193) to learn more about the bid verification mechanism. |
| conversion_bid_price {+Conditional} | float | Required when the following conditions are met: <ul><li>`spc_type` is `WEB_ALL_IN_ONE`</li><li>`optimization_goal` is `CONVERT` or `TRAFFIC_LANDING_PAGE_VIEW`</li><li> `bid_type` is `BID_TYPE_CUSTOM` </li></ul><br>The target cost per conversion or cost per landing page view. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. <br><br> `conversion_bid_price` needs to be lower than budget. See [Bidding-Bidding limits](https://business-api.tiktok.com/portal/docs?id=1745292444424193) to learn more about the bid verification mechanism. |
|deep_bid_type|string|Bidding strategy for in-app events.<br><br>Currently, we only support `DEFAULT`,  `AEO`, `VO_MIN_ROAS` (supported only for Smart+ Web Campaigns), and `VO_HIGHEST_VALUE` in this field.<br><br>For the descriptions of supported values, see [Enumeration - Deep Event Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Deep%20Event%20Bidding%20Strategy).|
| roas_bid {+Conditional} | float | Required when `deep_bid_type` is `VO_MIN_ROAS`. <br><br>Target ROAS for Value-Based Optimization.<br><br> Value range: 0.01-1,000. |
| vbo_window | string | The time window of the specified bidding strategy for [VBO IAA](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app advertising) or [VBO IAP](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app purchase). <br><br> Enum values: <ul><li>`SEVEN_DAYS`: The first seven days (day 7).  </li><li>`ZERO_DAY`: The current day (day 0). </li></ul>  Default value: `SEVEN_DAYS`.  <br><br><ul><li>When the Minimum ROAS (Target ROAS) bidding strategy is used, this field represents the window type of the target ROAS value (Day 7 ROAS or Day 0 ROAS). The system will aim to keep your average ROAS of the seventh day or of the current day around or higher than the target ROAS value, regardless of your budget. </li><li>When the Highest Value bidding strategy is used, this field represents the Highest Value window type (Day 7 Highest Value or Day 0 Highest Value). The system will aim to spend your budget fully and maximize the value of results within the first seven days or within the current day. </li></ul><br>To learn more about how to use VBO IAA for your ad groups, see [Enable VBO for App](https://business-api.tiktok.com/portal/docs?id=1770019181843458#item-link-Enable%20VBO%20for%20App).  <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>VBO IAP and VBO IAA in different scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li> Day 0 or day 7 bidding (`vbo_window` is `ZERO_DAY` or `SEVEN_DAYS`) for <b>VBO IAP</b> in Advanced Dedicated Campaign or Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li>Day 0 or day 7 bidding (`vbo_window` is `ZERO_DAY` or `SEVEN_DAYS`) for <b>VBO IAA</b> in Advanced Dedicated Campaign or Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li>Once set, this field cannot be updated.</li></ul>  |
| click_attribution_window | string | Click-through window. This attribution window is the time between when a person clicks your ad and then takes an action. <br>Use this field to define the timeframe during which conversions can be attributed to the Smart+ Campaign. <br><br>Enum values: <ul><li>`OFF`: Off. </li><li> `ONE_DAY`: 1-day click.</li><li>`SEVEN_DAYS`: 7-day click. </li><li>`FOURTEEN_DAYS`: 14-day click.</li><li> `TWENTY_EIGHT_DAYS`: 28-day click. </li></ul><br> To learn about the allowed values of this field for different advertising objective scenarios, refer to [Attribution window and event count](https://business-api.tiktok.com/portal/docs?id=1777694366654465).<br><br><p><span style="color:darkred"><b>Note:</b></span> <ul><li>If you want to manually configure the attribution windows, you need to pass at least `click_attribution_window` and `view_attribution_window` simultaneously, and optionally `engaged_view_attribution_window`. Note that the `engaged_view_attribution_window`, once passed, must be accompanied by `click_attribution_window` and `view_attribution_window`.</li><li> If you don't manually pass `click_attribution_window`, `engaged_view_attribution_window`, `view_attribution_window` and `attribution_event_count`, then default settings, if any, will be used. To confirm whether a default setting is used when you don't manually pass this field, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922).</li><li> Once set, this field cannot be updated. </li></ul>|
|engaged_view_attribution_window | string | Engaged view-through window. This attribution window is the time after someone watches at least 6 seconds of your video ad that a conversion is counted.<br>Use this field to define the timeframe during which conversions can be attributed to the Smart+ Campaign. <br><br>Enum values: <ul><li>`ONE_DAY`: 1-day engaged view.</li><li>`SEVEN_DAYS`: 7-day engaged view.</li><li>`FOURTEEN_DAYS`: 14-day engaged view.</li><li>`TWENTY_EIGHT_DAYS`: 28-day engaged view.</li></ul><br> To learn about the allowed values of this field for different advertising objective scenarios, refer to [Attribution window and event count](https://business-api.tiktok.com/portal/docs?id=1777694366654465).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you want to manually configure the attribution windows, you need to pass at least `click_attribution_window` and `view_attribution_window` simultaneously, and optionally `engaged_view_attribution_window`. Note that the `engaged_view_attribution_window`, once passed, must be accompanied by `click_attribution_window` and `view_attribution_window`.</li><li> If you don't manually pass `click_attribution_window`, `engaged_view_attribution_window`, `view_attribution_window` and `attribution_event_count`, then default settings, if any, will be used. To confirm whether a default setting is used when you don't manually pass this field, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922).</li><li> Once set, this field cannot be updated. </li></ul>|
| view_attribution_window | string | View-through window. This attribution window is the time between when a person views your ad and then takes an action. <br>Use this field to define the timeframe during which conversions can be attributed to the Smart+ Campaign. <br><br>Enum values: <ul><li>`OFF`: Off.</li><li>`ONE_DAY`: 1-day view.</li><li>`SEVEN_DAYS`: 7-day view.</li></ul><br> To learn about the allowed values of this field for different advertising objective scenarios, refer to [Attribution window and event count](https://business-api.tiktok.com/portal/docs?id=1777694366654465).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If you want to manually configure the attribution windows, you need to pass at least `click_attribution_window` and `view_attribution_window` simultaneously, and optionally `engaged_view_attribution_window`. Note that the `engaged_view_attribution_window`, once passed, must be accompanied by `click_attribution_window` and `view_attribution_window`.</li><li> If you don't manually pass `click_attribution_window`, `engaged_view_attribution_window`, `view_attribution_window` and `attribution_event_count`, then default settings, if any, will be used. To confirm whether a default setting is used when you don't manually pass this field, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922).</li><li> Once set, this field cannot be updated. </li></ul>|
| attribution_event_count | string | Event count (Statistic type). <br>The way that people's actions are counted after only viewing or clicking an ad. <br><br>Enum values: <ul><li>`UNSET`: Unset. </li><li> `EVERY`: Every. To count multiple events from someone as separate conversions. </li><li> `ONCE`: Once. To count multiple events from someone as 1 conversion.</li></ul><br> To learn about the allowed values of this field for different advertising objective scenarios, refer to [Attribution window and event count](https://business-api.tiktok.com/portal/docs?id=1777694366654465).<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> If you don't manually pass `click_attribution_window`, `engaged_view_attribution_window`, `view_attribution_window` and `attribution_event_count`, then default settings, if any, will be used. To confirm whether a default setting is used when you don't manually pass this field, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922).</li><li> Once set, this field cannot be updated. </li></ul>|
|billing_event {Required}|string|Events that you want to pay for. <br><br>For enum values, see [Enumeration - Billing Event](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Billing%20Event).<br><br>Currently, we only support `OCPM` and `CPC`.|
|location_ids {Required}|string[]|IDs of the locations that you want to target. <br><br>To get the available locations and corresponding IDs based on your placement and objective, use the [/tool/targeting/search/](https://ads.tiktok.com/marketing_api/docs?id=1761236883355649) or [/tool/region/](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) endpoint. <br>To get the list of location IDs, see [Location IDs](https://ads.tiktok.com/marketing_api/docs?id=1739311040498689).<br><br><p><span style="color:darkred"><b>Note:</b></span><br>If you add the US as your target location, then you can not remove the US after campaign creation.|
|languages|string[]|Codes of the languages that you want to target. <br><br>For the list of language codes supported, see [Enumeration - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Language%20Code).|
|gender|string|Gender that you want to target.<br><br>Enum values: `GENDER_FEMALE`, `GENDER_MALE`, `GENDER_UNLIMITED`<br>Default value: `GENDER_UNLIMITED`.<br><br><p><span style="color:darkred"><b>Note:</b></span> The field is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
|spc_audience_age |string|The age group that the campaign targets.<br><br>Enum value:<ul><li>`18+`: 18+.</li><li>`25+`: 25+.<ul><li>The value `25+` is only available when `objective_type` is `LEAD_GENERATION`.</li></ul></li></ul><br>Default value: `18+`.|
|exclude_age_under_eighteen|boolean|Exclude the group aged under eighteen. <br><br><p><span style="color:darkred"><b>Note:</b></span> Starting from September 21st, 2023, you can no longer target the audience aged under eighteen. The only supported value is `true`.  |
|excluded_audience_ids|string[]|List of audience ID to be excluded. <br><br>You can get audience IDs by using the [/dmp/custom_audience/list/ ](https://ads.tiktok.com/marketing_api/docs?id=1739940506015746)endpoint.|
|placement_type|string|The placement strategy that decides where your campaigns will be shown.<br><br><ul><li>`PLACEMENT_TYPE_NORMAL`: Select Placement. You can choose the apps to deliver your ads on.</li><li>`PLACEMENT_TYPE_AUTOMATIC`: Automatic placement. TikTok's ad system will use smart calculations to give you the best combination of ad placements across all apps. </li></ul>Default value: `PLACEMENT_TYPE_NORMAL`.<br><br><ul><li>For Smart+ Android App Campaigns (with `promotion_type` as `APP_ANDROID`), automated placement selection is a key feature. <ul><li>The system will automatically configure placements for your Smart+ Android App Campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately. </li><li>To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842). </li></ul></li><li>For Smart+ iOS App Campaigns  (with `promotion_type` as `APP_IOS`), automated placement selection is a key feature.<ul><li>Starting June 30th, 2025, the system will automatically configure placements for your Smart+ iOS App Campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided after June 30th, 2025, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately.</li><li>To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).</li></ul> </li><li>For Smart+ Web Campaigns, automated placement selection is a key feature. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters `placement_type` and `placements` will be ignored. To retrieve the exact placement setting of a Smart+ Web Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842). <ul><li> If the targeted locations (`location_ids`) of the campaign are all not supported for Pangle placement, the returned `placement_type` will be `PLACEMENT_TYPE_NORMAL` and `placements` will be `&#91;"PLACEMENT_TIKTOK"&#93;`.</li><li> If one or more targeted locations (`location_ids`) of the campaign are supported for Pangle placement, the returned `placement_type` will be `PLACEMENT_TYPE_AUTOMATIC` and `placements` will be `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;`.</li></ul></li><li>For Smart+ Lead Generation Campaigns (with `promotion_type` as `LEAD_GENERATION`), manually configure the placement as TikTok. Set `placement_type` to `PLACEMENT_TYPE_NORMAL` and `placements` to `["PLACEMENT_TIKTOK"]`.</li></ul><br><p><span style="color:darkred"><b>Note:</b></span><ul><li>`placement_type` can not be updated after the campaign creation.</li><li>If you want to opt out of the Pangle placement, contact your TikTok representative.</li></ul>|
|placements|string[]|The apps where you want to deliver your ads.<br><br>Currently, we support `PLACEMENT_TIKTOK`.<br><br><ul><li>For Smart+ Android App Campaigns (with `promotion_type` as `APP_ANDROID`), automated placement selection is a key feature. The system will automatically configure placements for your Smart+ Android App campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately. To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842). </li><li>For Smart+ iOS App Campaigns  (with `promotion_type` as `APP_IOS`), automated placement selection is a key feature. Starting June 30th, 2025, the system will automatically configure placements for your Smart+ iOS App campaigns based on your other settings and select high-quality traffic from various placements to deliver better results. If provided after June 30th, 2025, the parameters `placement_type` and `placements` will be ignored overwritten as `PLACEMENT_TYPE_AUTOMATIC` and `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;` separately. To retrieve the exact placement setting of a Smart+ App Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842). </li><li>For Smart+ Web Campaigns, automated placement selection is a key feature. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters `placement_type` and `placements` will be ignored. To retrieve the exact placement setting of a Smart+ Web Campaign, use [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842). <ul><li> If the targeted locations (`location_ids`) of the campaign are all not supported for Pangle placement, the returned `placement_type` will be `PLACEMENT_TYPE_NORMAL` and `placements` will be `&#91;"PLACEMENT_TIKTOK"&#93;`.</li><li> If one or more targeted locations (`location_ids`) of the campaign are supported for Pangle placement, the returned `placement_type` will be `PLACEMENT_TYPE_AUTOMATIC` and `placements` will be `&#91;"PLACEMENT_TIKTOK","PLACEMENT_PANGLE"&#93;`.</li></ul></li><li>For Smart+ Lead Generation Campaigns (with `promotion_type` as `LEAD_GENERATION`), manually configure the placement as TikTok. Set `placement_type` to `PLACEMENT_TYPE_NORMAL` and `placements` to `["PLACEMENT_TIKTOK"]`.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>`placements` can not be updated after the campaign creation.</li></ul>|
|comment_disabled|boolean|Whether to allow comments on your campaigns on TikTok.<br><br>Default value: `false`.|
|share_disabled|boolean|Whether to disable sharing of the campaign to third-party platforms.<br><br>Supported values: `true`, `false`.|
|video_download_disabled|boolean|Whether users can download your video ads on TikTok(can not be updated once created).<br><br>Default value: `false`.|
|blocked_pangle_app_ids|string[]|Pangle app block ID list. <br><br>You can get an ID via the `app_package_id` field returned by [Get Pangle block list](https://ads.tiktok.com/marketing_api/docs?id=1740039957181441). <br><br>Do not specify this field when you set `placements` only as `PLACEMENT_TIKTOK`.|
|budget_mode|string|Budget mode. <br><br>Enum values:` BUDGET_MODE_DAY`, `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.<br>Default value: `BUDGET_MODE_DAY`.<br><br>To learn about how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114).<br><br><span style="color:darkred"><b>Note</b></span>: <ul><li>Currently, this field cannot be updated after creation.</li><li>For Smart+ Lead Generation Campaigns, the only supported value is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.</li></ul>|
| budget_auto_adjust_strategy | string | Valid only when `budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.<br><br>The daily budget strategy.<br><br>Enum values: <ul><li>`AUTO_BUDGET_INCREASE`: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met.<ul><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, the specified `budget` will be the initial daily budget. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
|budget {Required}|float|Fixed campaign budget or initial campaign budget.<ul><li>When `budget_auto_adjust_strategy` is `UNSET`, this field represents your fixed campaign budget.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents your initial campaign budget. To retrieve the current campaign budget, check the returned `current_budget`.</li></ul><br>To learn about the minimum budget and how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). <br>To directly see the daily budget value range for a currency, see [Currency-Daily budget value range](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433#item-link-Daily%20budget%20value%20range).|
|schedule_type {Required}|string|Schedule type.<br><br>Currently, we support `SCHEDULE_START_END` and `SCHEDULE_FROM_NOW`.|
|schedule_start_time {Required}|string|Schedule start time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br> The start time can be up to 12 hours earlier than the current time, but cannot be later than `2028-01-01 00:00:00`.|
|schedule_end_time {+Conditional}|string|Schedule end time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).<br><br> The end time cannot be later than `2038-01-01 00:00:00`.|
|dayparting|string|Ad delivery arrangement, in the format of a string that consists of 48 x 7 characters. Each character is mapped to a 30-minute timeframe from Monday to Sunday. Each character can be set to either 0 or 1. 1 represents delivery in the 30-minute timeframe, and 0 stands for non-delivery in the 30-minute timeframe. The first character is mapped to 0:01-0:30 of Monday; The second character is mapped to 0:31-1:00 of Monday, and the last character represents 23:31-0:00 Sunday.<br><br><p><span style="color:darkred"><b>Note</b></span>: If not specified, all-0, or all-1 are considered as full-time delivery.|
|identity_type  {+Conditional}|string|Required when you are not using Spark Ads (`tiktok_item_id`). <br><br> Identity type for non-Spark Ads. <br><br>Enum value: `CUSTOMIZED_USER`.<br>Default value: `CUSTOMIZED_USER`.|
|identity_id {+Conditional}|string| Required when you are not using Spark Ads (`tiktok_item_id`). <br><br>Identity ID for non-Spark Ads.|
|media_info_list  {Required} |object[]|List of media information. <br><br>Max size: 30.|
#|media_info {Required}|object|Material information.|
##|video_info {+Conditional}|object|Required for the following types of ads:<ul><li>Non-Spark Ad Single Video ads. </li><li>Spark Ad Single Video ads through Spark Ads Push.</li></ul><br>Video information.|
###|video_id {+Conditional}|string|Required when `video_info` is passed.<br><br>Video ID. <br><br>You can get the video ID when you upload a video using the [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) endpoint.<br><br><p><span style="color:darkred"><b>Note:</b></span>: To ensure a smooth integration journey, we recommend that you use [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) (`upload_type` = `UPLOAD_BY_VIDEO_ID`) to bind video ID with your advertiser ID. |
###|file_name|string|Video name.|
##|image_info {+Conditional}|object[]|<ul><li>Required for the following types of ads:<ul><li>Non-Spark Ad Single Video ads. You need to specify a video cover.</li><li>Spark Ad Single Video ads through Spark Ads Push. You need to specify a video cover.</li><li>Non-Spark Ad Standard Carousel ads, which are only supported for Smart+ Web Campaigns. You need to specify one to 35 carousel images.</li><li>Spark Ad Standard Carousel ads through Spark Ads Push, which are only supported for Smart+ Web Campaigns. You need to specify one to 35 carousel images.</li></ul> </li><li>Not supported for Catalog Carousel ads.</li></ul><br>Image information. |
###|web_uri {+Conditional}|string|Required when `image_info` is passed.<br><br>Image ID.<br><br><ul><li>To upload an image and obtain the image ID, use [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642).</li><li>To search for images within your ad account, use [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506).</li></ul><br><ul><li>The image to be used in Single Video ads should meet the following requirement: The aspect ratio of the image is the same as the video.</li><li>The images to be used in Standard Carousel Ads should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: within 50 MB.</li></ul></li><li>The value of the parameter `is_carousel_usable` returned from [/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506) for the image is `true`. </li></ul></li></ul> |
##| music_info {+Conditional} | object | Required for the following scenarios:<ul><li>When you create Standard Carousel Ads, including:<ul><li>Non-Spark Ad Standard Carousel ads</li><li>Spark Ad Standard Carousel ads through Spark Ads Push</li><li>Spark Ad Standard Carousel ads through Spark Ads Pull</li></ul><li>When `objective_type` is `WEB_CONVERSIONS` and `catalog_creative_toggle` is true. The system will automatically generate catalog carousel ads. To learn about how to ensure that the catalog carousel format is available, see [Customize ad format combinations in Smart+ Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Customize%20the%20ad%20format%20combinations%20in%20Smart+%20Catalog%20Ads). </li></ul>  <br> Music information. |
###| music_id {+Conditional} | string | Required when `music_info` is passed. <br><br>The ID of the piece of music to use in the Carousel Ads.<br><br><ul><li>To obtain a valid music ID for Standard Carousel Ads, you can do any of the following:<ul><li>Filter the pieces of music for Standard Carousel Ads under an ad account by specifying `music_scene` as `CAROUSEL_ADS` in [/file/music/get/](https://business-api.tiktok.com/portal/docs?id=1740053909509122).</li><li>Upload a piece of customized music for Standard Carousel Ads to an ad account through any of the following methods:<ul><li>Specifying `music_scene` as `CAROUSEL_ADS` and passing `music_file` and `music_signature` in [/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650).</li><li>Specifying `music_scene` as `CAROUSEL_ADS`, specifying `upload_type` as `UPLOAD_BY_FILE_ID`, and passing `file_id` in [/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650).</li></ul></li></ul></li><li>To obtain a valid music ID for Catalog Carousel Ads, you can do any of the following:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying `music_scene` as `CATALOG_CAROUSEL` in [/file/music/get/](https://business-api.tiktok.com/portal/docs?id=1740053909509122).</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account through any of the following methods:<ul><li>Specifying `music_scene` as `CATALOG_CAROUSEL` and passing `music_file` and `music_signature` in [/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650).</li><li>Specifying `music_scene` as `CATALOG_CAROUSEL`, specifying `upload_type` as `UPLOAD_BY_FILE_ID`, and passing `file_id` in [/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650).</li></ul></li></ul> </li></ul> |
##| aigc_disclosure_type | string | Valid only when `identity_type` is `CUSTOMIZED_USER`. <br><br>Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>Default value: `NOT_DECLARED`.<br><br>To learn about the supported advertising objectives and ad formats for the toggle and the detailed steps for using the toggle, see [AIGC self-disclosure toggle](https://business-api.tiktok.com/portal/docs?id=1799186631031809). <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only update this field when you update the video (`video_id`).</li></ul>|
##|tiktok_item_id {+Conditional}|string|Required when you create Spark Ads through Spark Ads Pull, including:<ul><li> Spark Ad Single Video ads through Spark Ads Pull. You need to specify a TikTok video post.</li><li>Spark Ad Standard Carousel ads through Spark Ads Pull. You need to specify a TikTok photo post.</li></ul><br>The ID of the TikTok post to be used as an ad (Spark Ad). <br><br>Pass in the `item_id` you get from the response of the [/tt_video/info/](https://ads.tiktok.com/marketing_api/docs?id=1738376324021250) and [/identity/video/get/](https://ads.tiktok.com/marketing_api/docs?id=1740218475032577) endpoints. <br><br>When you pass in `tiktok_item_id`, you don't need to pass in the objects `image_info`, `video_info`, and `title_list`.<br><br><p><span style="color:darkred"><b>Note</b></span>: By using Spark Ads, you confirm that you have the rights to use the music in the videos for commercial purposes.|
##|identity_type {+Conditional}|string|Required when you use Spark Ads.<br><br>Identity type for Spark Ads.<br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br>For details about identities, see [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).<br><br><p><span style="color:darkred"><b>Note</b></span>: If this field is set to `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE` and `objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`, you need to specify a dynamic CTA via `call_to_action_id`. |
##|identity_id {+Conditional}|string|Required when you use Spark Ads. <br><br>Identity ID for Spark Ads.|
##| identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
|dark_post_status |string|Valid only when you create Spark Ads through Spark Ads Push.<br><br>The status of the "Ads-only mode" for your creatives.<br><br>Enum values:<ul><li>`ON`: Enable the ads-only mode to limit your posts to paid traffic.</li><li>`OFF`: Disable the ads-only mode. The post will appear on your TikTok profile and will be eligible to receive organic traffic.</li></ul><br>Default value: `ON`.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can set this to `OFF` only if both the identity's "Show through ads only" mode is disabled and the ad account does not have mandatory "Show through ads only" mode enabled. If the ad account enforces mandatory "Ads-only mode", you can only set this field to `ON` when creating Spark Ads using Spark Ads Push. This prevents the posts from appearing on your TikTok profile and gaining organic traffic, regardless of the identity's own `ads_only_mode` setting. It's a safeguard to ensure your Spark Ads Push content remains ads-only and avoids accidental profile posts.<ul><li>To confirm the "Show through ads only" mode status of an identity, use [/identity/info/](https://business-api.tiktok.com/portal/docs?id=1740218453385217) and check the returned `ads_only_mode`. </li><li>To confirm the mandatory "Show through ads only" mode status of an ad account, use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) and check the returned `ads_only_mode`.</li></ul></li><li>Mandatory "Show through ads only" mode for an ad account is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li></ul></p> |
| catalog_creative_toggle | boolean | Valid only when `web_all_in_one_catalog_status` is `OPEN`.  <br><br> Whether to enable auto-selection of creatives from your catalog.  <br><br> Supported values: `true`, `false`. <br> Default value: `false`.  <br><br>If you set this field to `true`, `music_info` is required and the system will use catalog creatives to automatically generate ads in diverse formats such as catalog video, catalog carousel, and single video. For detailed configuration options and requirements, see [Customize ad format combinations in Smart+ Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Customize%20the%20ad%20format%20combinations%20in%20Smart+%20Catalog%20Ads). |
|title_list {+Conditional}|object[]|List of ad titles (also called ad texts). <br>Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them. <br><br>When you pass in `tiktok_item_id`, you don't need to pass in the objects `image_info`, `video_info`, and `title_list`.|
#|title {+Conditional}|string|Required when `title_list` is passed.<br><br> Ad title (ad text).|
|call_to_action_id {+Conditional}|string|Required in any of the following scenarios:<ul><li>Scenario 1: <ul><li>`objective_type` is `LEAD_GENERATION`.</li></ul></li><li>Scenario 2: <ul><li>`objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`.</li><li>`identity_type` within the `media_info` object is `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE`.</li></ul></li></ul><br>The ID of the CTA portfolio that you want to use in your ads. A CTA portfolio is a group of auto-optimized CTAs.<br><br> For details about auto-optimized CTAs, see [CTA recommendations > Dynamic CTAs](https://ads.tiktok.com/marketing_api/docs?id=1740307296329730#item-link-Dynamic%20CTAs%20).|
|call_to_action_list |object[]|Call-to-action list.<br><br><p><span style="color:darkred"><b>Note</b></span>: This field is not supported in any of the following scenarios and you need to use `call_to_action_id` instead.<ul><li>Scenario 1: <ul><li>`objective_type` is `LEAD_GENERATION`.</li></ul></li><li>Scenario 2: <ul><li>`objective_type` is `APP_PROMOTION` or `WEB_CONVERSIONS`.</li><li>`identity_type` within the `media_info` object is `TT_USER`, `BC_AUTH_TT`, or `AUTH_CODE`.</li></ul></li></ul> |
#|call_to_action {+Conditional}|string| Required when `call_to_action_list` is passed. <br><br> Call-to-action text. <br><br>For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Call-to-action).|
|product_info |object|Additional product information. This information will be used to create ad variations to help achieve advertising goals such as standing out, building trust, or boosting performance.|
#|promo_info_list|object[]|Valid only in [regular Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20a%20regular%20Smart+%20Web%20Campaign).<br><br>Details of promo codes and offers.<br>Promo codes and offers can help boost your ad performance. Make sure your codes and offers are active, accurate, and ready to use at checkout.<br><br>Max size: 10.|
##|discount_type {+Conditional}|string|Required when `promo_info_list` is specified.<br><br>Discount type.<br><br>Enum values:<ul><li>`PERCENTAGE`: Percentage off discount.</li><li>`CASH`: Cash off discount.</li></ul>|
##|discount_value {+Conditional}|float|Required when `promo_info_list` is specified.<br><br>Discount value.<br><br><ul><li>When `discount_type` is `PERCENTAGE`, specify an integer between 1-100.</li><li>When `discount_type` is `CASH`, specify a float greater than 0.</li></ul>|
##|discount_currency {+Conditional}|string|Required when `discount_type` is `CASH`.<br><br>Discount currency.<br><br>For enum values, see <a href="#item-link-List of values for discount_currency or minimum_purchase_currency">List of values for `discount_currency` or `minimum_purchase_currency`</a>.|
##|promo_code|string|The promo code.<br><br><ul><li>For promo codes that require shoppers to enter a code at checkout, specify the code through this field.</li><li>For offers that apply automatically, omit this field.</li></ul><br>Length limit: 30 characters long and cannot contain emoji. Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.<br><br><p><span style="color:darkred"><b>Note</b></span>: Promo codes exclusive to TikTok often perform best.|
##|minimum_purchase_type|string|Minimum purchase type.<br><br>Enum values:<ul><li>`QUANTITY`: Minimum quantity.</li><li>`SUBTOTAL`: Minimum subtotal.</li></ul>|
##|minimum_purchase_value {+Conditional}|float|Required when `minimum_purchase_type` is specified.<br><br>Minimum purchase value.<ul><li> When `minimum_purchase_type` is `QUANTITY`, specify an integer that is 0 or greater.</li><li> When `minimum_purchase_type` is `SUBTOTAL`, specify a float greater than 0.</li></ul>|
##|minimum_purchase_currency {+Conditional}|string|Required when `minimum_purchase_type` is `SUBTOTAL`.<br><br>Minimum purchase currency.<br><br>For enum values, see <a href="#item-link-List of values for discount_currency or minimum_purchase_currency">List of values for `discount_currency` or `minimum_purchase_currency`</a>.|
##|valid_start_time|string|Valid start time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.|
##|valid_end_time|string|Valid end time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.<br><br><ul><li>The `valid_end_time` should be later than the current time.</li><li>If you specify `valid_end_time`, provide `valid_start_time` at the same time.</li></ul>|
|card_list|object[]|Creative portfolio ID list.<br><br>Size range: 0-1.|
#|card_id|string|Creative portfolio ID. <br><br>Pass the ID of one of the following creative portfolio types:<ul><li>call-to-action text.</li><li>Website info card. </li><li>Display Card.</li><li>Download Card.</li><li>Countdown Sticker. </li><li>Gift Code Sticker.</li><li>Pop-up Showcase. </li></ul><br>To create a creative portfolio, use [/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426).<br><ul><li>To learn about how to get the ID of a Display Card, Website info card, or Download Card portfolio, see [Cards](https://business-api.tiktok.com/portal/docs?id=1749019652141058).</li><li>To learn about how to get the ID of a Countdown Sticker or Giftcode Sticker portfolio, see [Stickers](https://business-api.tiktok.com/portal/docs?id=1749019667506177).</li><li>To learn about how to get the ID of a Pop-out Showcase portfolio, see [Premium Add-ons](https://business-api.tiktok.com/portal/docs?id=1749019676181505). |
| automatic_add_on_enabled | boolean | Valid only when `web_all_in_one_catalog_status` is `OPEN`.<br><br> Whether to enable auto-selection of interactive add-ons. <br><br>Supported values: `true`, `false`. <br> Default value: `false`. <br><br>If you set this field to `true`, the system will automatically apply the most suitable interactive add-ons to your ads. To learn more about this setting, see the instructions for this field in [Create Smart+ Catalog Ads](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20Smart+%20Catalog%20Ads). |
|page_list|object[]|Page ID list. <br><br>Size range: 0-1.|
#|page_id|string|Page ID.<br><br><ul><li>To obtain pages within your ad account, use [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586).</li><li>To create instant pages, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881).</li></ul>|
|deeplink|string|The specific location where you want your audience to go if they have your app installed. <br><br>If you pass this field, you should also provide the `deeplink_type` field.|
|deeplink_type|string|The deeplink type. <br><br>Currently, we only support `DEFERRED_DEEPLINK`.<br><br> If you pass this field, you should also provide the `deeplink` field. |
|landing_page_urls|object[]|Landing page URL list. <br><br>Before you use this field, please be aware of the following two modes.<br><ul><li> Single landing page mode:<br> In this mode, all videos/TikTok posts have the same landing page. When you create a campaign in single landing page mode, the size range of this field is 0-1. Additionally, you don't need to specify `video_id` or `tiktok_item_id` in this object array.</li><li>Multiple landing page mode: <br>This mode can only be used when `objective_type` = `WEB_CONVERSIONS`, `promotion_type` is `WEBSITE` and `spc_type` is `UNSET` or not passed. In this mode, each video/TikTok post corresponds to a landing page URL. When you create a campaign in multiple landing page mode, the size range of this field is 0-30, and the size must be equal to the number of videos. For instance, if creating a campaign with three videos (three objects in `media_info_list`), you must specify three objects in `landing_page_urls`. Additionally, you need to specify: <b>either</b> `video_id` when creating a non-Spark Ad, <b>or</b> `tiktok_item_id` when creating a Spark Ad. </li></ul><br><span style="color:darkred"><b>Note</b></span>: Multiple landing page mode is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
#|landing_page_url  {+Conditional}|string|Required when `landing_page_urls` is passed. <br><br>Landing page URL.|
#| video_id {+Conditional}|string| <ul><li> Required when creating a non-Spark Ad campaign in multiple landing page mode.</li><li>You don't need this field when creating a campaign in single landing page mode.</li></ul><br>Video ID. <br><br>Pass the `video_id` that you specified in the `video_info` object. |
#|tiktok_item_id{+Conditional}|string| <ul><li> Required when creating a Spark Ad in multiple landing page mode.</li><li> You don't need this field when creating a campaign in single landing page mode.</li></ul><br>The ID of the TikTok post to be used as an ad (Spark Ad).<br><br> Pass the `tiktok_item_id` that you specified in the `media_info` object. |
|utm_params|object[]|Valid when `objective_type` is `WEB_CONVERSIONS`.<br><br>A list of URL parameters.<br><br>Max size: 14.<br><br>If you set `landing_page_url` to a URL that already includes URL parameters, you can optionally pass `utm_params` at the same time to store the URL parameters used in the URL. In such cases, you need to ensure that `utm_params` exactly matches the used URL parameters. The URL parameters will not be automatically appended to the `landing_page_url` upon ad delivery.|
#|key|string|The supported UTM parameters are:<ul><li>`utm_source`: The app, site, etc., that brings traffic to your website. For example: TikTok.</li><li>`utm_medium`: The advertising or marketing medium. For example: cpm, cpc, banner, and video.</li><li>`utm_content`: The creative content used for promotion. For example: ad name, CTA text, asset, and color.</li><li>`utm_campaign`: The individual campaign name, slogan, or promo code. For example: BlackFridayProm.</li></ul><br>Note that UTM parameters are case-sensitive.<br><br>Length limit when you specify a custom parameter: 100 characters.|
#|value|string|The value of the URL parameter.<br><br>You can specify a custom value or the name of a macro.<br><br>The supported macros are:<ul><li>`__CAMPAIGN_NAME__`: This will be replaced by your campaign name.</li><li>`__CAMPAIGN_ID__`: This will be replaced by your campaign ID.</li><li>`__AID_NAME__`: This will be replaced by your ad group name.</li><li>`__AID__`: This will be replaced by your ad group ID.</li><li>`__CID_NAME__`: This will be replaced by your ad name.</li><li>`__CID__`: This will be replaced by your ad ID.</li><li>`__PLACEMENT__`: This will be replaced by your placement.</li></ul><br>Length limit when you specify a custom value: 600 characters.|
|disclaimer_info |object|Valid only when `sales_destination` is `WEB_AND_APP`.<br><br>Details of the disclaimer.|
#|disclaimer_type |string|The type of disclaimer that you want to add to the ad.<br><br>Enum values:<ul><li>`TEXT_LINK`: clickable disclaimers.</li><li>`TEXT_ONLY`: text-only disclaimers.</li></ul><br>See [Include disclaimers in ads](https://business-api.tiktok.com/portal/docs?id=1739953274550273) to learn about how to configure disclaimer settings.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The disclaimer feature only supports TikTok placement.</li><li>Once added to your ads, the disclaimer cannot be deleted.</li></ul></p>|
#|disclaimer_text {+Conditional}|object|Required when `disclaimer_type` is `TEXT_ONLY`.<br><br>The text-only disclaimer that you want to add to the ad.|
##|text|string|The disclaimer text.<br><br>Length limit: 90 characters.|
#|disclaimer_clickable_texts {+Conditional}|object[]|Required when `disclaimer_type` is `TEXT_LINK`.<br><br>The clickable disclaimer or clickable disclaimers that you want to add to the ad.<br><br>Max size: 3.<br><br>If you use multiple clickable disclaimers, both the overall length for all the texts you pass in and the length of individual text need to be no more than 40 characters.|
##|text|string|The disclaimer text.<br><br>Length limit: 40 characters.|
##|url|string|The URL for the clickable disclaimer. When users tap each text, they will be redirected to the URL and see more disclaimer details.|
|tracking_app_id|string|Valid when `objective_type` is `WEB_CONVERSIONS`. <br><br>The ID of the App that you want to measure.<br>You can use this field to measure offsite app events.<br><br>To get a list of App IDs, use [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786).|
|impression_tracking_url|string|Default Impression Tracking URL. URL generated by your data partner to track impression events in your ads. Generally you can find and copy the URL from their platform.<br><br> <ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Default Impression Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul>|
|click_tracking_url|string|Click Tracking URL. URL generated by your data partner to track click events in your ads. Generally you can find and copy the URL from their platform. <br><br><ul><li> If the partner ID for the App (`app_id` specified at the ad group level ) that you want to track is `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field. If you do, this field will be ignored. You can obtain the partner ID of an App through `partner_id` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li><li> If self-attribution has been enabled for the App (`app_id` specified at the ad group level ) that you want to track and the partner ID for the App is not `44` (TikTok Business SDK) or `49` (TikTok App API), you don't need to pass in this field because this field will default to the Click Tracking URL you have configured for the App, and updates to the URL are not supported. You can check whether self-attribution has been enabled for the App through `self_attribution_enabled` returned from [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) or [/app/info/](https://ads.tiktok.com/marketing_api/docs?id=1740859272887297).</li></ul><p><span style="color:darkred"><b>Note:</b></span> `click_tracking_url` can not be updated if your app info already contains the click monitoring URL.|
|app_tracking_info_list |object[]|Valid only when `sales_destination` is `WEB_AND_APP`.<br><br>Details of Third-party tracking settings.<br><br>Max size: 4.|
#|app_type {+Conditional}|string|Required when `app_tracking_info_list` is specified.<br><br>App type.<br><br>Enum values:<ul><li>`APP_ANDROID`: Android App.</li><li>`APP_IOS`: iOS App.</li></ul> |
#|app_id {+Conditional}|string|Required when `app_tracking_info_list` is specified.<br><br>The App ID of the App.<br><br><ul><li>When `app_type` is `APP_ANDROID`, specify the App ID for an Android App.</li><li>When `app_type` is `APP_IOS`, specify the App ID for an iOS App.</li></ul>|
#|impression_tracking_url {+Conditional}|string|When `app_type` is specified, specify either `impression_tracking_url` or `click_tracking_url` or both.<br><br>Impression tracking URL.<br><br><ul><li>When `app_type` is `APP_ANDROID`, specify an impression tracking URL for Android.</li><li>When `app_type` is `APP_IOS`, specify an impression tracking URL for iOS.</li></ul>|
#|click_tracking_url {+Conditional}|string|When `app_type` is specified, specify either `impression_tracking_url` or `click_tracking_url` or both.<br><br>Click tracking URL.<br><br><ul><li>When `app_type` is `APP_ANDROID`, specify a click tracking URL for Android.</li><li>When `app_type` is `APP_IOS`, specify a click tracking URL for iOS.</li></ul>|
```

### List of values for `discount_currency` or `minimum_purchase_currency`
The following table lists the enum values for `discount_currency` or `minimum_purchase_currency`.

```xtable
| Value for <code>discount_currency</code><br> or <code>minimum_purchase_currency</code>{35%} | Currency {65%}|
|---|---|
| `AED` | UAE Dirham |
| `ARS` | Argentine Peso |
| `AUD` | Australian Dollar |
| `BDT` | Bangladesh Taka |
| `BHD` | Bahraini Dinar |
| `BOB` | Bolivian Boliviano |
| `BRL` | Brazilian Real |
| `BYN` | Belarusian Ruble |
| `CAD` | Canadian Dollar |
| `CHF` | Swiss Franc |
| `CLP` | Chilean Peso |
| `CNY` | Chinese Yuan |
| `COP` | Colombian Peso |
| `CRC` | Costa Rican Colón |
| `CZK` | Czech Republic Koruna |
| `DKK` | Danish Krone |
| `DZD` | Algerian Dinar |
| `EGP` | Egyptian Pound |
| `EUR` | Euro |
| `GBP` | British Pound |
| `GTQ` | Guatemalan Quetzal |
| `HKD` | Hong Kong Dollar |
| `HNL` | Honduran Lempira |
| `HUF` | Hungarian Forint |
| `IDR` | Indonesian Rupiah |
| `ILS` | New Israeli Shekel |
| `INR` | Indian Rupee |
| `IQD` | Iraqi Dinar |
| `ISK` | Icelandic Króna |
| `JOD` | Jordanian Dinar |
| `JPY` | Japanese Yen |
| `KES` | Kenyan Shilling |
| `KHR` | Cambodian Riel |
| `KRW` | South Korean Won |
| `KWD` | Kuwaiti Dinar |
| `KZT` | Tenge |
| `LBP` | Lebanese Pound |
| `LKR` | Sri Lanka Rupee |
| `MAD` | Moroccan Dirham |
| `MOP` | Pataca |
| `MXN` | Mexican Peso |
| `MYR` | Malaysian Ringgit |
| `NGN` | Nigerian Naira |
| `NIO` | Nicaraguan Cordoba |
| `NOK` | Norwegian Krone |
| `NZD` | New Zealand Dollar |
| `OMR` | Rial Omani |
| `PEN` | Peruvian Sol |
| `PHP` | Philippine Piso |
| `PKR` | Pakistan Rupee |
| `PLN` | Polish ZŁOty |
| `PYG` | Paraguayan Guarani |
| `QAR` | Qatari Rial |
| `RON` | Romanian Leu |
| `RSD` | Serbian Dinar |
| `RUB` | Russian Ruble |
| `SAR` | Saudi Riyal |
| `SEK` | Krona |
| `SGD` | Singapore Dollar |
| `THB` | Thai Baht |
| `TRY` | Turkish Lira |
| `TWD` | New Taiwan Dollar |
| `UAH` | Hryvnia |
| `USD` | US Dollar |
| `UYU` | Peso Uruguayo |
| `VEF` | Venezuelan Bolívar |
| `VND` | Vietnamese Dong |
| `ZAR` | South African Rand |
```

### Example
```xcodeblock
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

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
|data|object|Returned data.|
#|advertiser_id |string| Advertiser ID. |
#|campaign_id |string| Campaign ID. |
#|operation_status|string|The status of the campaign. <br><br>Enum values:<ul><li>`ENABLE` : The campaign is enabled.</li><li>`DISABLE` : The campaign is disabled.</li></ul>|
#|campaign_secondary_status|string|Campaign status (Secondary status).<br><br>For the supported campaign secondary statuses for a certain primary status, see [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002).<br>It is equal to `secondary_status` in [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).|
#|is_smart_performance_campaign|boolean|Whether the campaign is an automated campaign type.<br><br>Supported values: <ul><li>`true`: The campaign is either a Smart+ Campaign or a Smart Performance Web Campaign. </li><li>`false`: The campaign is a standard campaign type. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `is_smart_performance_campaign` is `true` and `objective_type` is `WEB_CONVERSIONS`, you can confirm whether the web campaign is a Smart+ Web Campaign or Smart Performance Web Campaign by checking the returned `spc_type`.|
#|objective_type|string| Advertising objective. <br><br>Currently, we support `APP_PROMOTION`,`WEB_CONVERSIONS`, and `LEAD_GENERATION`.|
#| spc_type | string | The web campaign type. <br><br>Enum values: <ul><li>`WEB_ALL_IN_ONE`: Smart+ Web Campaign, where the supported optimization goals include Conversion, Value, Click and Landing page view.</li><li>`UNSET`: Smart Performance Web Campaign, where the supported optimization goals are limited to Conversion, Value, and Click. </li></ul>|
#| web_all_in_one_catalog_status | string | The status of catalog in the Smart+ Web Campaign.<br><br> Enum values: <ul><li>`OPEN`: enabled.</li><li>`UNSET`: disabled.</li></ul>|
#|app_promotion_type|string|App promotion type. <br><br>Currently, we only support `APP_INSTALL`.|
#|virtual_objective_type|string|The new objective type.<br><br>Enum value: `SALES`.<br><br>Use this field to create a campaign with the Sales objective, the combined objective for Website Conversions and Product Sales objectives. Learn more about [the Sales objective](https://business-api.tiktok.com/portal/docs?id=).|
#|sales_destination|string|Sales destination, the destination where you want to drive your sales.<br><br>Enum values: <ul><li>`WEBSITE`: Website. Drive sales on your website.</li><li>`WEB_AND_APP`: Website and app. Drive sales on both your website and your app.</li></ul>|
#|campaign_type|string|Campaign Type.<br><br>Currently, we support `REGULAR_CAMPAIGN` and `IOS14_CAMPAIGN`.|
#|campaign_app_profile_page_state|string|Indicates the status of the App Profile Page.<br><br>Enum values: `INVALID`, `UNSET`, `ON`, `OFF`.|
#| is_advanced_dedicated_campaign | boolean | Whether the campaign is an Advanced Dedicated Campaign. Advanced Dedicated Campaigns leverage advanced delivery models that benefit from real-time signals. <br><br>Enum values: `true`, `false`. |
#| disable_skan_campaign | boolean | Whether to disable SKAN (SKAdNetwork) attribution, Apple's conversion attribution solution for iOS campaigns.<br><br>Enum values:<ul><li>`true`: To disable SKAN attribution. The campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [Self Attribution Network (SAN) metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) for the campaign. However, you cannot retrieve [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. Learn more about [SAN integration](https://ads.tiktok.com/help/article/about-self-attribution-transition). </li><li>`false`: To enable SKAN attribution. The campaign will be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [SKAN metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign.</li></ul>|
#| bid_align_type | string | The attribution type for the Dedicated Campaign. The type determines which attribution network the target CPA (`conversion_bid_price`) or ROAS (`roas_bid`) is based on. <br><br>Enum values: <ul><li>`SAN`: SAN. </li><li>`SKAN`: SKAN.</li></ul>|
#|campaign_name|string|Campaign name.|
#|special_industries|string[]| Ad categories.<br><br>Enum values: <ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul>|
#|adgroup_secondary_status|string|Ad group status (Secondary status).<br><br>For the supported adgroup secondary statuses for a certain primary status, see [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002).<br>It is equal to `secondary_status` in [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).|
#| product_source | string | Product source where you want to get products for promotion. <br><br> Enum value: `CATALOG` (catalog). |
#| catalog_id | string | The ID of the catalog to use in the Smart+ Catalog Ads. |
#| catalog_authorized_bc_id | string | The ID of the Business Center that the catalog (`catalog_id`) belongs to. |
#| product_specific_type | string | Different dimensions to choose products. <br><br> Enum values: <ul><li>`ALL`: Allow TikTok to dynamically choose from all products. </li><li>`PRODUCT_SET`: Specify a product set. TikTok will dynamically choose products from this set.</li><li>`CUSTOMIZED_PRODUCTS`: Specify a customized number of products.</li></ul>|
#| product_set_id | string | The ID of a product set. |
#| product_ids | string[] | The product IDs of the catalog products. |
#|promotion_type|string|Promotion type (Optimization location) and you can decide where you'd like to promote your products using this field. For value definitions, see [Enumerations - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Promotion%20Type).|
#| app_id | string | The App ID of the app to promote.<br><br>You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint. |
#|app_type|string|The type of the promoted app.<br><br>Currently, we support `APP_ANDROID` and `APP_IOS`.|
#|promotion_website_type|string| TikTok Instant Page type.<br><br>Currently, we only support `TIKTOK_NATIVE_PAGE`(To use TikTok Instant Page).|
#|promotion_target_type|string|The optimization location for Lead Generation objective.<br><br>Enum values:<ul><li>`INSTANT_PAGE`: Instant Form. To create a fast-loading in-app TikTok Instant Form to collect more leads.</li><li>`EXTERNAL_WEBSITE`: Website Form. To use a landing page that has the Website Form or the TikTok Instant Page that redirects to the website with the Website Form to collect more leads.</li></ul>|
#|optimization_goal|string|The measurable results that you'd like to drive your ads with.<br><br> For enum values, see [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Optimization%20Goal).|
#|pixel_id|string|Pixel ID. |
#|optimization_event|string|Conversion event for the ad group. <br><br>See [Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889) for more.|
#|app_config|object[]|Returned when `sales_destination` is `WEB_AND_APP`.<br><br>Details of the app or apps to promote.|
##|app_id|string|The App ID of the app to promote.|
#| deep_funnel_optimization_status | string | The status of deep funnel optimization. <br> With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.  <br> <br>Enum values:  <ul><li>`ON`: enabled.  </li><li> `OFF`: disabled.  </li></ul>|
#| deep_funnel_event_source| string | Returned when `deep_funnel_optimization_status` is `ON`.   <br> <br> The event source type. <br> <br>Enum values: <ul><li>`PIXEL`: Pixel.  </li><li>`OFFLINE`: Offline Event Set. </li><li>`CRM`: CRM Event Set.</li></ul> |
#| deep_funnel_event_source_id | string | Returned when `deep_funnel_optimization_status` is `ON`. <br> <br> Event Source ID.  <br> <br><ul><li>When `deep_funnel_event_source` is `PIXEL` , this field represents a Pixel ID.</li><li>When `deep_funnel_event_source` is `OFFLINE`, this field represents an Offline Event Set ID.  </li><li>When `deep_funnel_event_source` is `CRM`, this field represents a CRM Event Set ID.</li></ul> |
#| deep_funnel_optimization_event | string | Returned when `deep_funnel_optimization_status` is `ON`.  <br> <br>Deep funnel optimization event.   <br> <br>Example: `SHOPPING`. |
#|bid_type|string|Bidding strategy. <br><br> For enum values and their descriptions, see [Enumeration - Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Bidding%20Strategy).|
#| bid_price | float | The target cost per click. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. |
#| conversion_bid_price | float | The target cost per conversion or cost per landing page view. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. |
#|deep_bid_type|string|Bidding strategy for in-app events.<br><br> For enum values and their descriptions, see [Enumeration - Deep Event Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Deep%20Event%20Bidding%20Strategy).|
#| roas_bid | float | Target ROAS for Value-Based Optimization. |
#|incentive_offer_type|string|The type of incentive offer applicable to the Smart+ Campaign.<br><br>If your campaign meets the eligibility criteria, but underperforms against certain CPA or Minimum ROAS thresholds you will be reimbursed with part of the gap with ad credits within the same ad account.<br><br>To learn more about the incentive offer eligibility criteria and the calculation of incentive amount, see [Smart+ Incentive Offer (Cost Cap/Minimum ROAS/Target ROAS)](https://ads.tiktok.com/help/article/incentive-offer).<br><br>Enum values:<ul><li>`INELIGIBLE`: The campaign is ineligible for any incentive offer.</li><li>`COST_CAP_AND_MIN_ROAS`: Ads groups within the campaign that use the Cost Cap or Minimum ROAS/Target ROAS bidding strategy are eligible for the incentive offer.</li></ul>|
#| vbo_window | string | The time window of the specified bidding strategy for [VBO IAA](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app advertising) or [VBO IAP](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app purchase).  <br><br> Enum values: <ul><li>`SEVEN_DAYS`: The first seven days (day 7).  </li><li>`ZERO_DAY`: The current day (day 0). </li></ul>|
#| click_attribution_window | string | Click-through window. This attribution window is the time between when a person clicks your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off. </li><li> `ONE_DAY`: 1-day click.</li><li>`SEVEN_DAYS`: 7-day click. </li><li>`FOURTEEN_DAYS`: 14-day click.</li><li> `TWENTY_EIGHT_DAYS`: 28-day click. </li></ul>|
#| engaged_view_attribution_window | string | Engaged view-through window. This attribution window is the time after someone watches at least 6 seconds of your video ad that a conversion is counted.<br><br>Enum values: <ul><li>`ONE_DAY`: 1-day engaged view.</li><li>`SEVEN_DAYS`: 7-day engaged view.</li><li>`FOURTEEN_DAYS`: 14-day engaged view.</li><li>`TWENTY_EIGHT_DAYS`: 28-day engaged view.</li></ul>|
#| view_attribution_window | string | View-through window. This attribution window is the time between when a person views your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off.</li><li>`ONE_DAY`: 1-day view.</li><li>`SEVEN_DAYS`: 7-day view.</li></ul>|
#| attribution_event_count | string | Event count (Statistic type). <br>The way that people's actions are counted after only viewing or clicking an ad. <br><br>Enum values: <ul><li>`UNSET`: Unset. </li><li> `EVERY`: Every. To count multiple events from someone as separate conversions. </li><li> `ONCE`: Once. To count multiple events from someone as 1 conversion.</li></ul>|
#|billing_event|string|Bid method. See [Enumerations - Bid method](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for optional values.|
#|location_ids|string[]|IDs of the locations that you want to target. <br><br>Use [Get locations](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) to get available locations.|
#|languages|string[]|Codes of the languages that you want to target. For the list of language codes supported, see [Enumerations - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Language%20Code).|
#|gender|string|Gender that you want to target.<br><br>Enum values: `GENDER_FEMALE`, `GENDER_MALE`, `GENDER_UNLIMITED`.|
#|spc_audience_age| string | The age group that the campaign targets.<br><br>Enum values: `18+`, `25+`. |
#|exclude_age_under_eighteen|boolean|Whether to exclude the group aged under eighteen. |
#|excluded_audience_ids|string[]|List of audience IDs to be excluded.|
#|placement_type|string|The placement strategy that decides where your ads will be shown. <br><br>Currently, we support `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TYPE_AUTOMATIC`.|
#|placements|string[]|The apps where you want to deliver your ads. <br><br>Currently, we support `PLACEMENT_TIKTOK` and `PLACEMENT_PANGLE`.|
#|comment_disabled|boolean|Whether to allow comments on your ads on TikTok.|
#|share_disabled|boolean|Whether sharing to third-party platforms is disabled for ads in this ad group.|
#|video_download_disabled|boolean|Whether users can download your video ads on TikTok.|
#|blocked_pangle_app_ids|string[]|Pangle app block ID list.|
#| brand_safety_type | string | Inventory filter tier for the Smart+ Campaign.<br><br>Enum values: <ul><li>`EXPANDED_INVENTORY`: Expanded inventory. Your ads will not appear next to explicitly inappropriate content, but they may appear next to content that features mature themes.</li><li>`STANDARD_INVENTORY`: Standard inventory. Your ads will appear next to content that is appropriate for most brands.</li><li>`LIMITED_INVENTORY`: Limited inventory. Your ads will not appear next to content that contains mature themes.</li><li>`NO_BRAND_SAFETY`: Full inventory without using any brand safety solution, which means your ads may appear next to some content featuring mature themes.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The setting is automatically applied based on your [Brand Safety Hub](https://business-api.tiktok.com/portal/docs?id=1739381752981505#item-link-Brand%20Safety%20Hub) configurations. To retrieve the details of such settings for your ad account, use [/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073). |
#|category_exclusion_ids | string[] | The ID list of content categories that you want to avoid showing the Smart+ Campaign next to.<br><br>To obtain the details of these content categories based on category IDs, use [/tool/content_exclusion/info/](https://business-api.tiktok.com/portal/docs?id=1769738105333826).<br><br><p><span style="color:darkred"><b>Note</b></span>: The setting is automatically applied based on your [Brand Safety Hub](https://business-api.tiktok.com/portal/docs?id=1739381752981505#item-link-Brand%20Safety%20Hub) configurations. To retrieve the details of such settings for your ad account, use [/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073).  |
#|budget_mode |string| Budget mode. <br><br>See [Enumerations-Budget Mode](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Budget%20Mode).  |
#| budget_auto_adjust_strategy | string | Returned only when `budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.<br><br>The campaign budget strategy for automatic daily campaign budget.<br><br>Enum values: <ul><li>`AUTO_BUDGET_INCREASE`: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. <ul><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, the specified `budget` will be the initial daily budget. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>`UNSET`: To disable automatic budget increase.</li></ul> |
#| budget | float | Fixed campaign budget or initial campaign budget.<ul><li>When `budget_auto_adjust_strategy` is `UNSET`, this field represents your fixed campaign budget.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents your initial campaign budget. To retrieve the current campaign budget, check the returned `current_budget`.</li></ul> |
#| current_budget | float | Returned only when `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`.<br><br>Current campaign budget for a campaign with automatic budget increase enabled. |
#|scheduled_budget|float|Scheduled campaign budget for the next day. <br><br>A value not equal to 0 means the scheduled budget is set and the value represents the budget; a value of 0 means the scheduled budget is not set.|
#|schedule_type|string|Schedule type.<br><br>Currently, we support `SCHEDULE_START_END` and `SCHEDULE_FROM_NOW`.|
#|schedule_start_time|string|Ad delivery start time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
#|schedule_end_time|string|Ad delivery end time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
#|dayparting|string|Ad delivery arrangement, in the format of a string that consists of 48 x 7 characters. Each character is mapped to a 30-minute timeframe from Monday to Sunday. Each character can be set to either 0 or 1. 1 represents delivery in the 30-minute timeframe, and 0 stands for non-delivery in the 30-minute timeframe. The first character is mapped to 0:01-0:30 of Monday; The second character is mapped to 0:31-1:00 of Monday, and the last character represents 23:31-0:00 Sunday.<br><p><span style="color:darkred"><b>Note</b></span>: Not specified, all-0, or all-1 are considered as full-time delivery.|
#|skip_learning_phase|boolean|Whether to skip the learning phase. |
#|identity_id |string|Identity ID when you are not using Spark Ads.|
#|identity_type |string|Identity type when you are not using Spark Ads.<br><br> Enum value: `CUSTOMIZED_USER`.|
#|app_download_url|string|App download link.|
#|app_name|string|The display name of the app download ad.|
#|media_info_list|object[]|List of media information.|
##|media_info |object|Material information.|
###|video_info |object|Video information.|
####|video_id |string|Video ID. |
####|file_name|string|Video name.|
###|image_info |object[]|Image information.  |
####|web_uri|string|Image ID.|
###| music_info | object | Music information. |
####| music_id | string | The ID of the piece of music to use in the Carousel Ads. |
###|aigc_disclosure_type | string | Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>|
###|tiktok_item_id|string|The ID of the TikTok post to be used as an ad (Spark Ad).|
###|identity_id |string|Identity ID when you use Spark Ads.|
###|identity_type |string|Identity type when you use Spark Ads. <br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br>For details about identities, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).|
###| identity_authorized_bc_id | string | Returned when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
#|dark_post_status |string|The status of the "Ads-only mode" for your creatives.<br><br>Enum values:<ul><li>`ON`: Enable the ads-only mode to limit your posts to paid traffic.</li><li>`OFF`: Disable the ads-only mode. The post will appear on your TikTok profile and will be eligible to receive organic traffic.</li></ul>|
#| catalog_creative_toggle | boolean | Whether to enable auto-selection of creatives from your catalog.<br><br> Supported values: `true`, `false`.<br><br> If you set this field to `true`, the system will use catalog creatives to automatically generate ads in diverse formats such as catalog video, catalog carousel, and single video. |
#|title_list|object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
##|title |string|Ad title (ad text).|
#|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads.|
#|call_to_action_list|object[]|Call-to-action list.|
##|call_to_action|string|Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Call-to-action).|
#|product_info|object|Additional product information. This information will be used to create ad variations to help achieve advertising goals such as standing out, building trust, or boosting performance.|
##|promo_info_list|object[]|Valid only in [regular Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20a%20regular%20Smart+%20Web%20Campaign).<br><br>Details of promo codes and offers.<br>Promo codes and offers can help boost your ad performance. Make sure your codes and offers are active, accurate, and ready to use at checkout.|
###|discount_type|string|Returned when `promo_info_list` is specified.<br><br>Discount type.<br><br>Enum values:<ul><li>`PERCENTAGE`: Percentage off discount.</li><li>`CASH`: Cash off discount.</li></ul>|
###|discount_value|string|Returned when `promo_info_list` is specified.<br><br>Discount value.<br><br><ul><li>When `discount_type` is `PERCENTAGE`, this field will be an integer between 1-100.</li><li> When `discount_type` is `CASH`, this field will be a float greater than 0.</li></ul>|
###|discount_currency|string|Returned when `discount_type` is `CASH`.<br><br>Discount currency.<br><br>For enum values, see <a href="#item-link-List of values for discount_currency or minimum_purchase_currency">List of values for `discount_currency` or `minimum_purchase_currency`</a>.|
###|minimum_purchase_type|string|Minimum purchase type.<br><br>Enum values:<ul><li>`QUANTITY`: Minimum quantity.</li><li>`SUBTOTAL`: Minimum subtotal.</li></ul>|
###|minimum_purchase_value|float|Returned when `minimum_purchase_type` is specified.<br><br>Minimum purchase value.<br><br><ul><li> When `minimum_purchase_type` is `QUANTITY`, this field will be an integer that is 0 or greater.</li><li>When `minimum_purchase_type` is `SUBTOTAL`, this field will be a float greater than 0.</li></ul>|
###|minimum_purchase_currency|string|Returned when `minimum_purchase_type` is `SUBTOTAL`.<br><br>Minimum purchase currency.<br><br>For enum values, see <a href="#item-link-List of values for discount_currency or minimum_purchase_currency">List of values for `discount_currency` or `minimum_purchase_currency`</a>.|
###|valid_start_time|string|Valid start time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.|
###|valid_end_time|string|Valid end time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.|
#|card_list|object[]|Creative portfolio ID list.|
##|card_id|string|Creative portfolio ID.|
#| automatic_add_on_enabled | boolean |Whether to enable auto-selection of interactive add-ons. <br><br> Supported values: `true`, `false`. <br><br>If you set this field to `true`, the system will automatically apply the most suitable interactive add-ons to your ads. |
#|page_list|object[]|Page ID list. |
##|page_id |string|Page ID.|
#|deeplink|string|The specific location where you want your audience to go if they have your app installed. |
#|deeplink_type|string|The deeplink type.|
#|landing_page_urls|object[]|Landing page URL list.  |
##|landing_page_url|string|Landing page URL.|
##|video_id|string|Returned when the campaign is in multiple landing page mode.<br><br>Video ID. |
##|tiktok_item_id|string|Returned when the campaign is in multiple landing page mode.<br><br>The ID of the TikTok post to be used as an ad (Spark Ad). |
#|utm_params|object[]|A list of URL parameters.<br>URL parameters are snippets of code that can be added to the end of the URLs to help you track clicks across different channels and understand how visitors interact with a website through third-party analytics platforms. They consist of key-value pairs that are specified through `key` and `value`.|
##|key|string|The URL parameter.<br>The parameter can be a custom parameter or a UTM parameter.<br><br>The supported UTM parameters are:<ul><li>`utm_source`: The app, site, etc., that brings traffic to your website. For example: TikTok.</li><li>`utm_medium`: The advertising or marketing medium. For example: cpm, cpc, banner, and video.</li><li>`utm_content`: The creative content used for promotion. For example: ad name, CTA text, asset, and color.</li><li>`utm_campaign`: The individual campaign name, slogan, or promo code. For example: BlackFridayProm.</li></ul><br>Note that UTM parameters are case-sensitive.|
##|value|string|The value of the URL parameter.<br>The value can be a custom value or the name of a macro.<br><br>The supported macros are:<ul><li>`__CAMPAIGN_NAME__`: This will be replaced by your campaign name.</li><li>`__CAMPAIGN_ID__`: This will be replaced by your campaign ID.</li><li>`__AID_NAME__`: This will be replaced by your ad group name.</li><li>`__AID__`: This will be replaced by your ad group ID.</li><li>`__CID_NAME__`: This will be replaced by your ad name.</li><li>`__CID__`: This will be replaced by your ad ID.</li><li>`__PLACEMENT__`: This will be replaced by your placement.</li></ul>|
#|disclaimer_info|object|Details of the disclaimer.|
##|disclaimer_type|string|The type of disclaimer that you want to add to the ad.<br><br>Enum values:<ul><li>`TEXT_LINK`: clickable disclaimers.</li><li>`TEXT_ONLY`: text-only disclaimers.</li></ul> |
##|disclaimer_text|object|Returned when `disclaimer_type` is `TEXT_ONLY`.<br><br>The text-only disclaimer that you want to add to the ad.|
###|text|string|The disclaimer text.<br><br>Length limit: 90 characters.|
##|disclaimer_clickable_texts|object[]|Returned when `disclaimer_type` is `TEXT_LINK`.<br><br>The clickable disclaimer or clickable disclaimers that you want to add to the ad.|
###|text|string|The disclaimer text.<br><br>Length limit: 40 characters.|
###|url|string|The URL for the clickable disclaimer. When users tap each text, they will be redirected to the URL and see more disclaimer details.|
#|tracking_app_id|string|The ID of the app that is measured.|
#|impression_tracking_url|string|Default Impression Tracking URL.|
#|click_tracking_url|string|Click Tracking URL.|
#|app_tracking_info_list|object[]|Details of Third-party tracking settings.|
##|app_type|string|Returned when `app_tracking_info_list` is specified.<br><br>App type.<br><br>Enum values:<ul><li>`APP_ANDROID`: Android App.</li><li>`APP_IOS`: iOS App.</li></ul>|
##|app_id|string|Returned when `app_tracking_info_list` is specified.<br><br>The App ID of the App.<br><br><ul><li>When `app_type` is `APP_ANDROID`, this field represents the App ID for an Android App.</li><li>When `app_type` is `APP_IOS`, this field represents the App ID for an iOS App.</li></ul>|
##|impression_tracking_url|string|Impression tracking URL.<br><br>When `app_type` is `APP_ANDROID`, this field represents an impression tracking URL for Android.</li><li><ul><li>When `app_type` is `APP_IOS`, this field represents an impression tracking URL for iOS.</li></ul>|
##|click_tracking_url|string|Click tracking URL.<br><br><ul><li>When `app_type` is `APP_ANDROID`, this field represents a click tracking URL for Android.</li><li>When `app_type` is `APP_IOS`, this field represents a click tracking URL for iOS.</li></ul>|
#|create_time|string|The time when the campaign was created, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
#|modify_time|string|The time when the campaign was modified, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
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


