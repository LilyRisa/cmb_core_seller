---
title: "Update a campaign"
doc_id: 1739320422086657
path: "API Reference / Campaign / Update a campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update a campaign

Use this endpoint to modify a campaign after it has been created. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/campaign/update/|/v1.3/campaign/update/|
|Request parameter name|`industry_types`| `special_industries` |
|Request parameter data type |`advertiser_id`: number<br>`campaign_id`: number|`advertiser_id`: string<br>`campaign_id`: string|
|Request parameters deprecated in v1.3|/| `roas_bid`|
|New request parameters|/| `po_number`|
|Response parameter| We now return the full info of ad entity (including new parameter `app_id`,`app_promotion_type`,  `campaign_app_profile_page_state`, `rf_campaign_type`,`disable_skan_campaign`, `bid_align_type`, `catalog_enabled`,`rta_bid_enabled`,`virtual_objective_type`, `sales_destination`, and `po_number`) in v1.3. For details, see the Response section on this page.|
```
## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/campaign/update/>

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string |Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type <br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|  Advertiser ID. |
|campaign_id {Required}|string| Campaign ID |
|campaign_name |string|Campaign name.  <br> <br>Length limit: 512 characters. Emoji is not supported. <br> <br>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character.|
|special_industries|string[]| Special ad categories.  <br><br>By selecting a housing, employment, or credit category for your campaign, you certify not to use TikTok to discriminate based on protected characteristics in ads relating to housing, employment, or credit and to adhere to [TikTok's special ad category policy](https://ads.tiktok.com/help/article/housing-employment-credit-hec-ad-policy?redirected=2). <br><br>Once you use this field to indicate that your campaign is promoting opportunities in any of the ad categories, the targeting options at the ad group level will be limited to help you comply with anti-discrimination laws in these categories. See [Targeting requirements for ad groups in a campaign with special ad categories](https://business-api.tiktok.com/portal/docs?id=1739381236849665#item-link-Targeting%20requirements%20for%20ad%20groups%20in%20a%20campaign%20with%20special%20ad%20categories) to learn about the requirements.<br><br>Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Using special ad categories in Shopping Ads with campaign product source as Catalog and in Shopping Ads with campaign product source as non-Catalog are currently separate allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li>This field is generally available to advertisers registered in America or Canada. Advertisers who are not registered in America or Canada but want to target these countries with special ad categories need to apply for an additional allowlist.</li><li> If you have selected special ad categories for a campaign, you can remove the special ad categories, but you cannot change them or enable the special ad categories for an existing campaign if the special ad categories are currently disabled.</li></ul> |
|budget |float| Campaign budget. <br> <br>To learn about the minimum budget and how to set budget modes, see [Budget](https://ads.tiktok.com/marketing_api/docs?id=1739381246298114). To directly see the daily budget value range for a currency, see [Currency-Daily budget value range](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433).<br> <br><p><span style="color:darkred"><b>Note</b></span>: The new budget should be at least 105% of the current spend. You can run [a synchronous report](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) or [an asynchronous report](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) to get the spend data via the Basic data metric `spend` .|
|po_number|string|PO (purchase order) number.<br>PO numbers are useful for invoice tracking and reconciliation.<br><br>[Learn more about PO numbers on monthly invoices](https://ads.tiktok.com/help/article/update-po-number-on-monthly-invoices).|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/update/' \
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

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log id of a request, which uniquely identifies the request.|
|data |object|Returned data. |
#|advertiser_id |string| Advertiser ID. |
#|campaign_id |string| Campaign ID. |
#|create_time |string| Time at which the campaign was created. |
#|modify_time |string| Time at which the campaign was Modified. |
#| objective_type |string| Advertising objective. <br><br>For enum values, see [Enumeration-Advertising Objectives](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#| app_promotion_type | string | App promotion type. <br><br>Enum values: <ul><li>`APP_INSTALL`: App install. Get new users to install your app.</li><li>`APP_RETARGETING`: App retargeting. Re-engage existing users to take action in your app. </li><li>`APP_PREREGISTRATION`: App pre-registration. Get new users to pre-register before your app launches.</li><li>`APP_POSTS_PROMOTION`: App posts promotion. Promote TikTok posts to increase app awareness and measure conversions.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Currently, creating App posts promotion campaigns via API is not supported. You can only update or retrieve such campaigns via API. |
#|virtual_objective_type|string|The new objective type.<br><br>Enum value: `SALES`.<br><br>Use this field to create a campaign with the Sales objective, the combined objective for Website Conversions and Product Sales objectives. Learn more about [the Sales objective](https://business-api.tiktok.com/portal/docs?id=).|
#|sales_destination|string|Sales destination, the destination where you want to drive your sales.<br><br>Enum values: <ul><li>`TIKTOK_SHOP`: TikTok Shop. Drive sales on your TikTok Shop.</li><li>`WEBSITE`: Website. Drive sales on your website.</li><li>`APP`: App. Drive sales on your app (product catalog required).</li><li>`WEB_AND_APP`: Website and app. Drive sales on both your website and your app.</li></ul>|
#| is_search_campaign | boolean | Whether the campaign is a Search Ads Campaign. With Search Ads Campaigns, you can select keywords and serve ads within TikTok's search result page. <br><br>Supported values: `true`, `false`.  |
#| is_smart_performance_campaign|boolean| Whether the campaign is an automated campaign type.<br><br>Supported values: <ul><li>`true`: The campaign is either a Smart+ Campaign or a Smart Performance Web Campaign. </li><li>`false`: The campaign is a standard campaign type. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If `is_smart_performance_campaign` is `true` and `objective_type` is `WEB_CONVERSIONS`, you can confirm whether the web campaign is a Smart+ Web Campaign or Smart Performance Web Campaign by checking the returned `spc_type` from [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).|
#|campaign_type|string| Campaign Type, which indicates the campaign is a regular campaign or iOS 14 campaign.<br><br> Enum values: `REGULAR_CAMPAIGN`, `IOS14_CAMPAIGN`. |
#| app_id | string | The Application ID of the promoted app. <br><br>You can get `app_id` by using the [/app/list/](https://ads.tiktok.com/marketing_api/docs?id=1740859313270786) endpoint. |
#| is_advanced_dedicated_campaign | boolean | Whether the campaign is an Advanced Dedicated Campaign. Advanced Dedicated Campaigns leverage advanced delivery models that benefit from real-time signals. <br><br>Enum values: `true`, `false`. |
#| disable_skan_campaign | boolean | Whether to disable SKAN (SKAdNetwork) attribution, Apple's conversion attribution solution for iOS campaigns.<br><br>Enum values:<ul><li>`true`: To disable SKAN attribution. The campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [Self Attribution Network (SAN) metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) for the campaign. However, you cannot retrieve [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. Learn more about [SAN integration](https://ads.tiktok.com/help/article/about-self-attribution-transition). </li><li>`false`: To enable SKAN attribution. The campaign will be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [SKAN metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign.</li></ul>|
#| bid_align_type | string | The attribution type for the Dedicated Campaign. The type determines which attribution network the target CPA (`conversion_bid_price`) or ROAS (`roas_bid`) at the ad group level is based on. <br><br>Enum values: <ul><li>`SAN`: SAN. </li><li>`SKAN`: SKAN.</li></ul>|
#|campaign_app_profile_page_state|string| Indicates the status of App Profile Page. <br><br>Enum values: `INVALID`, `UNSET`, `ON`, `OFF`. |
#|rf_campaign_type |string|When `objective_type` is specified as `RF_REACH`, this field shows whether the campaign has been set as a TikTok Pulse campaign.<br><br>Enum values: `STANDARD` (Reach & Frequency  campaign), `PULSE` (TikTok Pulse  campaign). |
#| campaign_product_source | string | Returned only when `objective_type` for the campaign is `PRODUCT_SALES` and the product source is configured for the campaign.<br><br>Product source of the campaign.<br><br>Enum values: <ul><li>`CATALOG` : Catalog.</li><li>`STORE`: TikTok Shop, or TikTok Showcase. </li></ul>   |
#|catalog_enabled|boolean|Whether to use your catalog to automatically advertise relevant products or services to people based on their unique interests, intent and actions.|
#|campaign_name |string| Campaign name. |
#|special_industries|string[]| Special ad categories. <br><br>Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internship, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul>|
#|budget_optimize_on|boolean| Returned only for campaigns with Campaign Budget Optimization (CBO) enabled.<br><br>Whether CBO is enabled. <br><br>Enum values: `true` (enabled).<br><br>For details about CBO, see [Campaign Budget Optimization](https://ads.tiktok.com/marketing_api/docs?id=1739381757818881).|
#|bid_type |string| Bidding strategy on the campaign level.|
#|deep_bid_type |string| Bidding strategy for in-app events. <br><br>For enum values and their descriptions, see [Enumeration - Deep-level Bidding Strategy](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
#|roas_bid |float| ROAS goal for Value Optimization.|
#|optimization_goal |string |Optimization goal. |
#|budget_mode |string| Budget mode.<br><br> Enum values:<ul><li>`BUDGET_MODE_INFINITE`: Unlimited budget.</li><li>`BUDGET_MODE_TOTAL`: Lifetime budget.</li><li>`BUDGET_MODE_DAY` : Daily budget.</li><li>`BUDGET_MODE_DYNAMIC_DAILY_BUDGET`: Dynamic daily budget. It is the average daily budget over a week. Daily costs will not exceed 125% of the average daily budget. Weekly costs will not exceed the average daily budget * 7.</li></ul>|
#|budget |float| Campaign budget. |
#| rta_id | string | Realtime API (RTA) ID, the RTA strategy identifier. |
#| rta_bid_enabled | boolean | Whether to use RTA bid. <br><br> Supported values: <ul><li> `true`: enabled.</li><li> `false`: not enabled.</li></ul>  |
#| rta_product_selection_enabled | boolean | Whether to use RTA to automatically select products.<br><br>Enum values: <ul><li>`true`: enabled.</li><li>`false`: not enabled. </li></ul>|
#|operation_status |string| Operation status.<br><br>Enum values:<ul><li>`ENABLE` : The campaign is enabled (in 'ON' status).</li><li>`DISABLE`: The campaign is disabled (in 'OFF' status). </li></ul> |
#|secondary_status|string|Campaign status (Secondary status).|
#| postback_window_mode| string | The mode that determines which SKAN 4.0 postback you want to secure. <br><br>Enum values: <ul><li> `POSTBACK_WINDOW_MODE1`: This option secures the first postback, which corresponds to the 0-2 day attribution window. The data can take up to 4 days to return, and the campaign will wait for 4 days to release the campaign quota. </li><li>`POSTBACK_WINDOW_MODE2`: This mode secures the first two postbacks, which correspond to the 3-7 day attribution window. The data can take up to 13 days to return, and the campaign will wait for 13 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE3`: This mode secures all three postbacks, which correspond to the 8-35 day attribution window. The data can take up to 41 days to return, and the campaign will wait for 41 days to release the campaign quota. </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you have set up Mobile Measurement Partner (MMP) Tracking with <b>Adjust</b>, <b>Airbridge</b>, <b>Appsflyer</b>,  <b>Branch</b>, <b>Kochava</b>, or <b>Singular</b> for your App and your MMP SDK version is updated to a SKAN 4.0 supported SDK, you can transition your App to SKAN 4.0 on Events Manager. To learn about how to set up MMP tracking for your App, see [How to Set Up App Attribution in TikTok Ads Manager](https://ads.tiktok.com/help/article/set-up-app-attribution-tiktok-ads-manager?lang=en). To learn more about how to transition your App to SKAN 4.0, see [About SKAN 4.0 and TikTok](https://ads.tiktok.com/help/article/about-skan-4-0-and-tiktok?lang=en) and [How to transition to SKAN 4.0](https://ads.tiktok.com/help/article/how-to-transition-to-skan-4-0).  |
#|is_new_structure|bool|Whether the campaign is a new structure (for the same campaign, the structure of campaign, ad groups and ads are the same).|
#|objective|string| Campaign type (application or landing page).<br><br>Enum values: `APP`(application), `LANDING_PAGE`(Landing page). |
#|po_number|string|PO (purchase order) number.<br>PO numbers are useful for invoice tracking and reconciliation.<br><br>[Learn more about PO numbers on monthly invoices](https://ads.tiktok.com/help/article/update-po-number-on-monthly-invoices).|
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
        "is_new_structure": true,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_TOTAL",
        "special_industries": [],
        "deep_bid_type": null,
        "advertiser_id": "{{advertiser_id}}",
        "objective_type": "TRAFFIC",
        "create_time": "{{create_time}}",
        "operation_status": "ENABLE",
        "is_smart_performance_campaign": false,
        "campaign_id": "{{campaign_id}}",
        "roas_bid": 0,
        "campaign_name": "{{campaign_name}}",
        "objective": "LANDING_PAGE",
        "rf_campaign_type": "STANDARD",
        "modify_time": "{{modify_time}}"
    }
}
(/code);
```
