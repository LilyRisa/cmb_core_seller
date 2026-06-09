---
title: "(To be deprecated) Get Legacy Smart+ Campaigns"
doc_id: 1767334299811842
path: "API Reference / (To be deprecated) Legacy Smart+ / (To be deprecated) Get Legacy Smart+ Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Get Legacy Smart+ Campaigns

Use this endpoint to obtain detailed information of Smart+ Campaigns and Smart Performance Web Campaigns.  

> <span style="color:DarkRed">**Important**</span><br>
>
> - **Upgraded Smart+ API**:
>   - We are actively upgrading the Smart+ API to deliver enhanced capabilities and performance. The new Upgraded Smart+ API is already launched. Learn more about [Upgraded Smart+ Campaign](https://business-api.tiktok.com/portal/docs?id=1853452461203458) and [available Upgraded Smart+ API endpoints](https://business-api.tiktok.com/portal/docs?id=1843301794874370).
>   - **Get started**:
>     - **Reporting**: No additional allowlist required.
>     - **Campaign Management**: To access the upgraded Smart+ experience, contact your TikTok sales representative to have your ad account allowlisted. If your ad account is already in the allowlist, you can directly use the Upgraded Smart+ API creation and modification endpoints.  
> - **Legacy API Deprecation**: 
>   - After **March 31, 2026**, you will no longer be able to create legacy Smart+ Campaigns using the [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) endpoint.
>   - Note that legacy Smart+ Campaigns that have not been migrated will not be transitioned to the new API automatically. Attempts to use the legacy Smart+ API endpoint afterward will result in errors, rendering new campaign creation via this endpoint unavailable.
> - **Legacy API deprecation extension request process for existing legacy Smart+ creation developers**:
>     - 1. **Submit a request**: If you require an extension, please submit an application via the [External Extension Request Form for Legacy Smart+ Creation API](https://bytedance.sg.larkoffice.com/share/base/form/shrlgsm2yMsQaSoAsMy77tcAzng).
>     - 2. **Allowlist processing**: After receiving your request, we will process an allowlist entry for the associated developer app ID. After the process, developers will be able to continue calling the `/campaign/spc/create/` endpoint until **June 30, 2026**.
>     - 3. **Extended timeline for strong business needs**: If you have a strong business case requiring an extension beyond June 30, 2026, please specify your use case in the application form, or contact your sales representative directly.

> <span style="color:DodgerBlue">**Note**</span><br>
> Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to [Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186) as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/campaign/spc/get/>

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|campaign_ids {Required}|string[]| A list of campaign IDs. <br><br>Max size: 100.|
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:{{Access-Token}}" -H "Content-Type:application/json" -X GET \
-d '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_ids": ["{{campaign_ids}}"]
}' \
https://business-api.tiktok.com/open_api/v1.3/campaign/spc/get/
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id| string| The log id of a request, which uniquely identifies the request.|
|data |object|Returned data. |
#| list |object[]| A list of Smart+ Campaigns or Smart Performance Web Campaigns. |
##|advertiser_id |string| Advertiser ID. |
##|campaign_id |string| Campaign ID. |
##|operation_status|string|The status of the campaign. <br><br>Enum values:<ul><li>`ENABLE` : The campaign is enabled.</li><li>`DISABLE` : The campaign is disabled.</li></ul>|
##|campaign_secondary_status|string|Campaign status (Secondary status).<br><br>For the supported campaign secondary statuses for a certain primary status, see [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002).<br>It is equal to `secondary_status` in [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).|
##|is_smart_performance_campaign|boolean|Whether the campaign is an automated campaign type.<br><br>Supported values: <ul><li>`true`: The campaign is either a Smart+ Campaign or a Smart Performance Web Campaign. </li><li>`false`: The campaign is a standard campaign type. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `is_smart_performance_campaign` is `true` and `objective_type` is `WEB_CONVERSIONS`, you can confirm whether the web campaign is a Smart+ Web Campaign or Smart Performance Web Campaign by checking the returned `spc_type`.|
##|objective_type|string| Advertising objective. <br><br>Currently, we support `APP_PROMOTION`, `WEB_CONVERSIONS`, and `LEAD_GENERATION`.|
##| spc_type | string | The web campaign type. <br><br>Enum values: <ul><li>`WEB_ALL_IN_ONE`: Smart+ Web Campaign, where the supported optimization goals include Conversion, Value, Click and Landing page view.</li><li>`UNSET`: Smart Performance Web Campaign, where the supported optimization goals are limited to Conversion, Value, and Click. </li></ul>|
##| web_all_in_one_catalog_status | string | The status of catalog in the Smart+ Web Campaign.<br><br> Enum values: <ul><li>`OPEN`: enabled.</li><li>`UNSET`: disabled.</li></ul>|
##|app_promotion_type|string|App promotion type. <br><br>Currently, we only support `APP_INSTALL`.|
##|virtual_objective_type|string|The new objective type.<br><br>Enum value: `SALES`.<br><br>Use this field to create a campaign with the Sales objective, the combined objective for Website Conversions and Product Sales objectives. Learn more about [the Sales objective](https://business-api.tiktok.com/portal/docs?id=).|
##|sales_destination|string|Sales destination, the destination where you want to drive your sales.<br><br>Enum values: <ul><li>`WEBSITE`: Website. Drive sales on your website.</li><li>`WEB_AND_APP`: Website and app. Drive sales on both your website and your app.</li></ul>|
##|campaign_type|string|Campaign Type.<br><br>Currently, we support `REGULAR_CAMPAIGN` and `IOS14_CAMPAIGN`.|
##|campaign_app_profile_page_state|string|Indicates the status of the App Profile Page.<br><br>Enum values: `INVALID`, `UNSET`, `ON`, `OFF`.|
##| is_advanced_dedicated_campaign | boolean | Whether the campaign is an Advanced Dedicated Campaign. Advanced Dedicated Campaigns leverage advanced delivery models that benefit from real-time signals. <br><br>Enum values: `true`, `false`. |
##| disable_skan_campaign | boolean | Whether to disable SKAN (SKAdNetwork) attribution, Apple's conversion attribution solution for iOS campaigns.<br><br>Enum values:<ul><li>`true`: To disable SKAN attribution. The campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [Self Attribution Network (SAN) metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) for the campaign. However, you cannot retrieve [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. Learn more about [SAN integration](https://ads.tiktok.com/help/article/about-self-attribution-transition). </li><li>`false`: To enable SKAN attribution. The campaign will be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [SKAN metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign.</li></ul>|
##| bid_align_type | string | The attribution type for the Dedicated Campaign. The type determines which attribution network the target CPA (`conversion_bid_price`) or ROAS (`roas_bid`) is based on. <br><br>Enum values: <ul><li>`SAN`: SAN. </li><li>`SKAN`: SKAN.</li></ul>|
##|campaign_name|string|Campaign name.|
##|special_industries|string[]| Ad categories.<br><br>Enum values: <ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul>|
##|adgroup_secondary_status|string|Ad group status (Secondary status).<br><br>For the supported adgroup secondary statuses for a certain primary status, see [Supported secondary statuses for a primary status](https://ads.tiktok.com/marketing_api/docs?id=1757239620352002).<br>It is equal to `secondary_status` in [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986).|
##| product_source | string | Product source where you want to get products for promotion. <br><br> Enum value: `CATALOG` (catalog). |
##| catalog_id | string | The ID of the catalog to use in the Smart+ Catalog Ads. |
##| catalog_authorized_bc_id | string | The ID of the Business Center that the catalog (`catalog_id`) belongs to. |
##| product_specific_type | string | Different dimensions to choose products. <br><br> Enum values: <ul><li>`ALL`: Allow TikTok to dynamically choose from all products. </li><li>`PRODUCT_SET`: Specify a product set. TikTok will dynamically choose products from this set.</li><li>`CUSTOMIZED_PRODUCTS`: Specify a customized number of products.</li></ul>|
##| product_set_id | string | The ID of a product set. |
##| product_ids | string[] | The product IDs of the catalog products. |
##|promotion_type|string|Promotion type (Optimization location) and you can decide where you'd like to promote your products using this field. For value definitions, see [Enumerations - Promotion Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Promotion%20Type).|
##| app_id | string | The App ID of the app to promote.<br><br>You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint. |
##|app_type|string|The type of the promoted app.<br><br>Currently, we support `APP_ANDROID` and `APP_IOS`.|
##|promotion_website_type|string| TikTok Instant Page type.<br><br>Currently, we only support `TIKTOK_NATIVE_PAGE`(To use TikTok Instant Page).|
##|promotion_target_type|string|The optimization location for Lead Generation objective.<br><br>Enum values:<ul><li>`INSTANT_PAGE`: Instant Form. To create a fast-loading in-app TikTok Instant Form to collect more leads.</li><li>`EXTERNAL_WEBSITE`: Website Form. To use a landing page that has the Website Form or the TikTok Instant Page that redirects to the website with the Website Form to collect more leads.</li></ul>|
##|optimization_goal|string|The measurable results that you'd like to drive your ads with.<br><br> For enum values, see [Enumeration - Optimization Goal](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Optimization%20Goal).|
##|pixel_id|string|Pixel ID. |
##|optimization_event|string|Conversion event for the ad group. <br><br>See [Conversion events](https://ads.tiktok.com/marketing_api/docs?id=1739361474981889) for more.|
##|app_config|object[]|Returned when `sales_destination` is `WEB_AND_APP`.<br><br>Details of the app or apps to promote.|
###|app_id|string|The App ID of the app to promote.|
##| deep_funnel_optimization_status | string | The status of deep funnel optimization. <br> With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.  <br> <br>Enum values:  <ul><li>`ON`: enabled.  </li><li> `OFF`: disabled.  </li></ul>|
##| deep_funnel_event_source| string | Returned when `deep_funnel_optimization_status` is `ON`.   <br> <br> The event source type. <br> <br>Enum values: <ul><li>`PIXEL`: Pixel.  </li><li>`OFFLINE`: Offline Event Set. </li><li>`CRM`: CRM Event Set.</li></ul> |
##| deep_funnel_event_source_id | string | Returned when `deep_funnel_optimization_status` is `ON`. <br> <br> Event Source ID.  <br> <br><ul><li>When `deep_funnel_event_source` is `PIXEL` , this field represents a Pixel ID.</li><li>When `deep_funnel_event_source` is `OFFLINE`, this field represents an Offline Event Set ID.  </li><li>When `deep_funnel_event_source` is `CRM`, this field represents a CRM Event Set ID.</li></ul> |
##| deep_funnel_optimization_event | string | Returned when `deep_funnel_optimization_status` is `ON`.  <br> <br>Deep funnel optimization event.   <br> <br>Example: `SHOPPING`. |
##|bid_type|string|Bidding strategy. <br><br> For enum values and their descriptions, see [Enumeration - Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Bidding%20Strategy).|
##| bid_price | float | The target cost per click. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. |
##| conversion_bid_price | float | The target cost per conversion or cost per landing page view. The system will aim to get the most results while keeping the average cost per result around or lower than the specified amount. |
##|deep_bid_type|string|Bidding strategy for in-app events.<br><br> For enum values and their descriptions, see [Enumeration - Deep Event Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Deep%20Event%20Bidding%20Strategy).|
##| roas_bid | float | Target ROAS for Value-Based Optimization. |
##|incentive_offer_type|string|The type of incentive offer applicable to the Smart+ Campaign.<br><br>If your campaign meets the eligibility criteria, but underperforms against certain CPA or Minimum ROAS thresholds you will be reimbursed with part of the gap with ad credits within the same ad account.<br><br>To learn more about the incentive offer eligibility criteria and the calculation of incentive amount, see [Smart+ Incentive Offer (Cost Cap/Minimum ROAS/Target ROAS)](https://ads.tiktok.com/help/article/incentive-offer).<br><br>Enum values:<ul><li>`INELIGIBLE`: The campaign is ineligible for any incentive offer.</li><li>`COST_CAP_AND_MIN_ROAS`: Ads groups within the campaign that use the Cost Cap or Minimum ROAS/Target ROAS bidding strategy are eligible for the incentive offer.</li></ul>|
##| vbo_window | string | The time window of the specified bidding strategy for [VBO IAA](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app advertising) or [VBO IAP](https://business-api.tiktok.com/portal/docs?id=1739381743067137) (Value-Based Optimization for in-app purchase).  <br><br> Enum values: <ul><li>`SEVEN_DAYS`: The first seven days (day 7).  </li><li>`ZERO_DAY`: The current day (day 0). </li></ul>|
##| click_attribution_window | string | Click-through window. This attribution window is the time between when a person clicks your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off. </li><li> `ONE_DAY`: 1-day click.</li><li>`SEVEN_DAYS`: 7-day click. </li><li>`FOURTEEN_DAYS`: 14-day click.</li><li> `TWENTY_EIGHT_DAYS`: 28-day click. </li></ul>|
##| view_attribution_window | string | View-through window. This attribution window is the time between when a person views your ad and then takes an action. <br><br>Enum values: <ul><li>`OFF`: Off.</li><li>`ONE_DAY`: 1-day view.</li><li>`SEVEN_DAYS`: 7-day view.</li></ul>|
##| engaged_view_attribution_window | string | Engaged view-through window. This attribution window is the time after someone watches at least 6 seconds of your video ad that a conversion is counted.<br><br>Enum values: <ul><li>`ONE_DAY`: 1-day engaged view.</li><li>`SEVEN_DAYS`: 7-day engaged view.</li><li>`FOURTEEN_DAYS`: 14-day engaged view.</li><li>`TWENTY_EIGHT_DAYS`: 28-day engaged view.</li></ul>|
##| attribution_event_count | string | Event count (Statistic type). <br>The way that people's actions are counted after only viewing or clicking an ad. <br><br>Enum values: <ul><li>`UNSET`: Unset. </li><li> `EVERY`: Every. To count multiple events from someone as separate conversions. </li><li> `ONCE`: Once. To count multiple events from someone as 1 conversion.</li></ul>|
##|billing_event|string|Bid method. See [Enumerations - Bid method](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138) for optional values.|
##|location_ids|string[]|IDs of the locations that you want to target. <br><br>Use [Get locations](https://ads.tiktok.com/marketing_api/docs?id=1737189539571713) to get available locations.|
##|languages|string[]|Codes of the languages that you want to target. For the list of language codes supported, see [Enumerations - Language Code](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Language%20Code).|
##|gender|string|Gender that you want to target.<br><br>Enum values: `GENDER_FEMALE`, `GENDER_MALE`, `GENDER_UNLIMITED`.|
##| spc_audience_age | string | The age group that the campaign targets. <br><br>Enum value: `18+`, `25+`.<br><br><ul><li>For campaigns with the `LEAD_GENERATION` objective (Smart+ Lead Generation Campaigns), the value of this field can be either `18+` or `25+`. </li><li>For campaigns with the `APP_PROMOTION` objective (Smart+ App Campaigns) or `WEB_CONVERSIONS` objective (Smart+ Web Campaigns or Smart Performance Web Campaigns), the value of this field can only be `18+`.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Currently, creating Smart+ Lead Generation Campaigns via API is not supported. To filter existing Smart+ Lead Generation Campaigns with the `LEAD_GENERATION` objective within your ad account, call [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) and set `filtering` to `&#123;"objective_type":"LEAD_GENERATION", "is_smart_performance_campaign": true&#125;`. |
##|exclude_age_under_eighteen|boolean|Whether to exclude the group aged under eighteen. |
##|excluded_audience_ids|string[]|List of audience IDs to be excluded.|
##|placement_type|string|The placement strategy that decides where your ads will be shown. <br><br>Currently, we support `PLACEMENT_TYPE_NORMAL` and `PLACEMENT_TYPE_AUTOMATIC`.|
##|placements|string[]|The apps where you want to deliver your ads. <br><br>Currently, we support `PLACEMENT_TIKTOK` and `PLACEMENT_PANGLE`.|
##|comment_disabled|boolean|Whether to allow comments on your ads on TikTok.|
##|share_disabled|boolean|Whether sharing to third-party platforms is disabled for ads in this ad group.|
##|video_download_disabled|boolean|Whether users can download your video ads on TikTok.|
##|blocked_pangle_app_ids|string[]|Pangle app block ID list.|
##| brand_safety_type | string | Inventory filter tier for the Smart+ Campaign.<br><br>Enum values: <ul><li>`EXPANDED_INVENTORY`: Expanded inventory. Your ads will not appear next to explicitly inappropriate content, but they may appear next to content that features mature themes.</li><li>`STANDARD_INVENTORY`: Standard inventory. Your ads will appear next to content that is appropriate for most brands.</li><li>`LIMITED_INVENTORY`: Limited inventory. Your ads will not appear next to content that contains mature themes.</li><li>`NO_BRAND_SAFETY`: Full inventory without using any brand safety solution, which means your ads may appear next to some content featuring mature themes.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The setting is automatically applied based on your [Brand Safety Hub](https://business-api.tiktok.com/portal/docs?id=1739381752981505#item-link-Brand%20Safety%20Hub) configurations. To retrieve the details of such settings for your ad account, use [/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073). |
##|category_exclusion_ids | string[] | The ID list of content categories that you want to avoid showing the Smart+ Campaign next to.<br><br>To obtain the details of these content categories based on category IDs, use [/tool/content_exclusion/info/](https://business-api.tiktok.com/portal/docs?id=1769738105333826). <br><br><p><span style="color:darkred"><b>Note</b></span>: The setting is automatically applied based on your [Brand Safety Hub](https://business-api.tiktok.com/portal/docs?id=1739381752981505#item-link-Brand%20Safety%20Hub) configurations. To retrieve the details of such settings for your ad account, use [/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073). |
##|budget_mode |string| Budget mode. <br><br>See [Enumerations-Budget Mode](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Budget%20Mode). |
##| budget_auto_adjust_strategy | string | Returned only when `budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.<br><br>The campaign budget strategy for automatic daily campaign budget.<br><br>Enum values: <ul><li>`AUTO_BUDGET_INCREASE`: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. <ul><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, the specified `budget` will be the initial daily budget. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>`UNSET`: To disable automatic budget increase.</li></ul> |
##| budget | float | Fixed campaign budget or initial campaign budget.<ul><li>When `budget_auto_adjust_strategy` is `UNSET`, this field represents your fixed campaign budget.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents your initial campaign budget. To retrieve the current campaign budget, check the returned `current_budget`.</li></ul> |
##| current_budget | float | Returned only when `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`.<br><br>Current campaign budget for a campaign with automatic budget increase enabled. |
##|scheduled_budget|float|Scheduled campaign budget for the next day. <br><br>A value not equal to 0 means the scheduled budget is set and the value represents the budget; a value of 0 means the scheduled budget is not set.|
##|schedule_type|string|Schedule type.<br><br>Currently, we support `SCHEDULE_START_END` and `SCHEDULE_FROM_NOW`.|
##|schedule_start_time|string|Ad delivery start time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
##|schedule_end_time|string|Ad delivery end time, in the format of`YYYY-MM-DD HH:MM:SS` (UTC+0).|
##|dayparting|string|Ad delivery arrangement, in the format of a string that consists of 48 x 7 characters. Each character is mapped to a 30-minute timeframe from Monday to Sunday. Each character can be set to either 0 or 1. 1 represents delivery in the 30-minute timeframe, and 0 stands for non-delivery in the 30-minute timeframe. The first character is mapped to 0:01-0:30 of Monday; The second character is mapped to 0:31-1:00 of Monday, and the last character represents 23:31-0:00 Sunday.<br><p><span style="color:darkred"><b>Note</b></span>: Not specified, all-0, or all-1 are considered as full-time delivery.|
##|skip_learning_phase|boolean|Whether to skip the learning phase. |
##|identity_id |string|Identity ID when you are not using Spark Ads.|
##|identity_type |string|Identity type when you are not using Spark Ads.<br><br> Enum value: `CUSTOMIZED_USER`.|
##|app_download_url|string|App download link.|
##|app_name|string|The display name of the app download ad.|
##|media_info_list|object[]|List of media information.|
###|ad_material_id|string|The ad material ID for the creative within the campaign.<br>An ad material ID is an ad-specific material ID generated when a particular creative is used in an ad. <br>This ID differs from the creative ID that you obtain when uploading the creative to your ad account’s Creative Library.|
###|material_operation_status|string|The status of the creative.<br><br>Enum values:<ul><li>`DISABLE`: The creative is disabled (in 'OFF' status).</li><li>`ENABLE`: The creative is enabled (in 'ON' status).</li></ul> |
###|media_info |object|Material information.|
####|image_info |object[]|Image information. |
#####|web_uri|string|Image ID.|
####|video_info |object|Video information.|
#####|video_id |string|Video ID. |
#####|file_name|string|Video name.|
####| music_info | object | Music information. |
#####| music_id | string | The ID of the piece of music to use in the Carousel Ads. |
####|aigc_disclosure_type | string | Whether to turn on the AIGC (Artificial Intelligence Generated Content) self-disclosure toggle to indicate the ad contains AI-generated content. After the toggle is turned on, your ad will carry an "Advertiser labeled as Al-generated" label when viewed in full. <br><br> Enum values: <ul><li>`SELF_DISCLOSURE`: To turn on the toggle to declare that the ad contains AI-generated content. </li><li>`NOT_DECLARED`: To not declare that the ad contains AI-generated content.</li></ul>|
####|tiktok_item_id|string|The ID of the TikTok post to be used as an ad (Spark Ad).|
####|identity_type |string|Identity type when you use Spark Ads.<br><br>Enum values: `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. <br>For details about identities, see [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).|
####|identity_id |string|Identity ID when you use Spark Ads.|
####| identity_authorized_bc_id | string | Returned when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##|dark_post_status |string|The status of the "Ads-only mode" for your creatives.<br><br>Enum values:<ul><li>`ON`: Enable the ads-only mode to limit your posts to paid traffic.</li><li>`OFF`: Disable the ads-only mode. The post will appear on your TikTok profile and will be eligible to receive organic traffic.</li></ul>|
##| catalog_creative_toggle | boolean | Whether to enable auto-selection of creatives from your catalog.<br><br> Supported values: `true`, `false`. <br><br> If you set this field to `true`, the system will use catalog creatives to automatically generate ads in diverse formats such as catalog video, catalog carousel, and single video. |
##|title_list|object[]|List of ad titles (also called ad texts). Ad titles are shown to your audience as part of your ad creative, to deliver the message you intend to communicate to them.|
###|title |string|Ad title (ad text).|
##|call_to_action_id|string|The ID of the CTA portfolio that you want to use in your ads.|
##|call_to_action_list|object[]|Call-to-action list.|
###|call_to_action|string|Call-to-action text. For enum values, see [Enumeration - Call-to-action](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138#item-link-Call-to-action).|
##|product_info|object|Additional product information. This information will be used to create ad variations to help achieve advertising goals such as standing out, building trust, or boosting performance.|
###|promo_info_list|object[]|Valid only in [regular Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186#item-link-Create%20a%20regular%20Smart+%20Web%20Campaign).<br><br>Details of promo codes and offers.<br>Promo codes and offers can help boost your ad performance. Make sure your codes and offers are active, accurate, and ready to use at checkout.|
####|discount_type|string|Returned when `promo_info_list` is specified.<br><br>Discount type.<br><br>Enum values:<ul><li>`PERCENTAGE`: Percentage off discount.</li><li>`CASH`: Cash off discount.</li></ul>|
####|discount_value|string|Returned when `promo_info_list` is specified.<br><br>Discount value.<br><br><ul><li>When `discount_type` is `PERCENTAGE`, this field will be an integer between 1-100.</li><li> When `discount_type` is `CASH`, this field will be a float greater than 0.</li></ul>|
####|discount_currency|string|Returned when `discount_type` is `CASH`.<br><br>Discount currency.<br><br>For enum values, see [List of values for `discount_currency` or `minimum_purchase_currency`](https://business-api.tiktok.com/portal/docs?id=1767334204047362#item-link-List%20of%20values%20for%20discount_currency%20or%20minimum_purchase_currency).|
####|minimum_purchase_type|string|Minimum purchase type.<br><br>Enum values:<ul><li>`QUANTITY`: Minimum quantity.</li><li>`SUBTOTAL`: Minimum subtotal.</li></ul>|
####|minimum_purchase_value |float|Returned when `minimum_purchase_type` is specified.<br><br>Minimum purchase value.<br><br><ul><li> When `minimum_purchase_type` is `QUANTITY`, this field will be an integer that is 0 or greater.</li><li>When `minimum_purchase_type` is `SUBTOTAL`, this field will be a float greater than 0.</li></ul>|
####|minimum_purchase_currency|string|Returned when `minimum_purchase_type` is `SUBTOTAL`.<br><br>Minimum purchase currency.<br><br>For enum values, see [List of values for `discount_currency` or `minimum_purchase_currency`](https://business-api.tiktok.com/portal/docs?id=1767334204047362#item-link-List%20of%20values%20for%20discount_currency%20or%20minimum_purchase_currency).|
####|valid_start_time|string|Valid start time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.|
####|valid_end_time|string|Valid end time (UTC+0) for the promo code or offer, in the format of `YYYY-MM-DD HH:MM:SS`.|
##|card_list|object[]|Creative portfolio ID list.|
###|card_id|string|Creative portfolio ID.|
##| automatic_add_on_enabled | boolean |  Whether to enable auto-selection of interactive add-ons. <br><br> Supported values: `true`, `false`. <br><br>If you set this field to `true`, the system will automatically apply the most suitable interactive add-ons to your ads. |
##|page_list|object[]|Page ID list. |
###|page_id |string|Page ID.|
##|deeplink|string|The specific location where you want your audience to go if they have your app installed. |
##|deeplink_type|string|The deeplink type.|
##|landing_page_urls|object[]|Landing page URL list. |
###|landing_page_url|string|Landing page URL.|
###|video_id|string|Returned when the campaign is in multiple landing page mode.<br><br>Video ID. |
###|tiktok_item_id|string|Returned when the campaign is in multiple landing page mode.<br><br>The ID of the TikTok post to be used as an ad (Spark Ad). |
##|utm_params|object[]|A list of URL parameters.<br>URL parameters are snippets of code that can be added to the end of the URLs to help you track clicks across different channels and understand how visitors interact with a website through third-party analytics platforms. They consist of key-value pairs that are specified through `key` and `value`.|
###|key|string|The URL parameter.<br>The parameter can be a custom parameter or a UTM parameter.<br><br>The supported UTM parameters are:<ul><li>`utm_source`: The app, site, etc., that brings traffic to your website. For example: TikTok.</li><li>`utm_medium`: The advertising or marketing medium. For example: cpm, cpc, banner, and video.</li><li>`utm_content`: The creative content used for promotion. For example: ad name, CTA text, asset, and color.</li><li>`utm_campaign`: The individual campaign name, slogan, or promo code. For example: BlackFridayProm.</li></ul><br>Note that UTM parameters are case-sensitive.|
###|value|string|The value of the URL parameter.<br>The value can be a custom value or the name of a macro.<br><br>The supported macros are:<ul><li>`__CAMPAIGN_NAME__`: This will be replaced by your campaign name.</li><li>`__CAMPAIGN_ID__`: This will be replaced by your campaign ID.</li><li>`__AID_NAME__`: This will be replaced by your ad group name.</li><li>`__AID__`: This will be replaced by your ad group ID.</li><li>`__CID_NAME__`: This will be replaced by your ad name.</li><li>`__CID__`: This will be replaced by your ad ID.</li><li>`__PLACEMENT__`: This will be replaced by your placement.</li></ul>|
##|disclaimer_info|object|Details of the disclaimer.|
###|disclaimer_type|string|The type of disclaimer that you want to add to the ad.<br><br>Enum values:<ul><li>`TEXT_LINK`: clickable disclaimers.</li><li>`TEXT_ONLY`: text-only disclaimers.</li></ul> |
###|disclaimer_text|object|Returned when `disclaimer_type` is `TEXT_ONLY`.<br><br>The text-only disclaimer that you want to add to the ad.|
####|text|string|The disclaimer text.<br><br>Length limit: 90 characters.|
###|disclaimer_clickable_texts|object[]|Returned when `disclaimer_type` is `TEXT_LINK`.<br><br>The clickable disclaimer or clickable disclaimers that you want to add to the ad.|
####|text|string|The disclaimer text.<br><br>Length limit: 40 characters.|
####|url|string|The URL for the clickable disclaimer. When users tap each text, they will be redirected to the URL and see more disclaimer details.|
##|tracking_app_id|string|The ID of the app that is measured.|
##|impression_tracking_url|string|Default Impression Tracking URL.|
##|click_tracking_url|string|Click Tracking URL.|
##|app_tracking_info_list|object[]|Details of Third-party tracking settings.|
###|app_type|string|Returned when `app_tracking_info_list` is specified.<br><br>App type.<br><br>Enum values:<ul><li>`APP_ANDROID`: Android App.</li><li>`APP_IOS`: iOS App.</li></ul>|
###|app_id|string|Returned when `app_tracking_info_list` is specified.<br><br>The App ID of the App.<br><br><ul><li>When `app_type` is `APP_ANDROID`, this field represents the App ID for an Android App.</li><li>When `app_type` is `APP_IOS`, this field represents the App ID for an iOS App.</li></ul>|
###|impression_tracking_url|string|Impression tracking URL.<br><br>When `app_type` is `APP_ANDROID`, this field represents an impression tracking URL for Android.</li><li><ul><li>When `app_type` is `APP_IOS`, this field represents an impression tracking URL for iOS.</li></ul>|
###|click_tracking_url|string|Click tracking URL.<br><br><ul><li>When `app_type` is `APP_ANDROID`, this field represents a click tracking URL for Android.</li><li>When `app_type` is `APP_IOS`, this field represents a click tracking URL for iOS.</li></ul>|
##|create_time|string|The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).|
##|modify_time|string|The time when the campaign was modified, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0).|
```

### Example

``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [        
       { "campaign_id": "{{campaign_id}}",
        "advertiser_id": "{{advertiser_id}}",
        "app_id": "{{app_id}}",
        "pixel_id": null,
        "app_type": "APP_ANDROID",
        "objective_type": "APP_PROMOTION",
        "campaign_name": "{{campaign_name}}",
        "is_smart_performance_campaign": true,
        "operation_status": "ENABLE",
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "adgroup_secondary_status": "ADGROUP_STATUS_CREATE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "app_promotion_type": "APP_INSTALL",
        "optimization_goal": "CLICK",
        "bid_type": "BID_TYPE_NO_BID",
        "deep_bid_type": null,
        "skip_learning_phase": null,
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DAY",
        "scheduled_budget": {{scheduled_budget}},
        "promotion_type": "APP_ANDROID",
        "optimization_event": "ACTIVE",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "promotion_website_type": null,
        "comment_disabled": true,
        "share_disabled": true,
        "video_download_disabled": true,
        "location_ids": [
            "{{location_ids}}"
        ],
        "languages": [
            "{{languages}}"
        ],
        "exclude_age_under_eighteen": false,
        "excluded_audience_ids": null,
        "blocked_pangle_app_ids": null,
        "special_industries": null,
        "schedule_type": "SCHEDULE_FROM_NOW",
        "schedule_start_time": "2024-04-17 22:00:00",
        "schedule_end_time": "2034-04-15 22:00:00",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "billing_event": "CPC",
        "app_download_url": "{{app_download_url}}",
        "app_name": "{{app_name}}",
        "call_to_action_id": null,
        "call_to_action_list": [
            {
                "call_to_action": "INSTALL_NOW"
            },
            {
                "call_to_action": "DOWNLOAD_NOW"
            }
        ],
        "media_info_list": [
            {
                "media_info": {
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": "{{file_name}}"
                    },
                    "identity_id": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "identity_type": null,
                    "tiktok_item_id": null
                }
            }
        ],
        "title_list": [
            {
                "title": "{{title}}"
            },
            {
                "title": "{{title}}"
            }
        ],
        "page_list": null,
        "card_list": null,
        "landing_page_urls": null,
        "deeplink": "{{deeplink}}",
        "deeplink_type": "DEFERRED_DEEPLINK",
        "identity_id": "{{identity_id}}",
        "identity_type": "CUSTOMIZED_USER",
        "impression_tracking_url": null,
        "click_tracking_url": "{{click_tracking_url}}",
        "create_time": "{{create_time}}",
        "modify_time": "{{modify_time}}"
        }
	  ]    
    }
}
(/code)
```

