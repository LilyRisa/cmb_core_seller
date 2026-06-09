---
title: "Update an Upgraded Smart+ Campaign"
doc_id: 1843312876001282
path: "API Reference / Upgraded Smart+ / Campaigns / Update an Upgraded Smart+ Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update an Upgraded Smart+ Campaign

Use this endpoint to update an Upgraded Smart+ Campaign.

This endpoint supports incremental updates.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/update/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `application/json`.  |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| campaign_id {Required} | string | Campaign ID.<br><br>To obtain campaign IDs, use [/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1843312818332930). |
| campaign_name | string | Campaign name.<br><br>Length limit: 512 characters. Emoji is not supported.<br>Each word in Chinese or Japanese counts as two characters, while each letter in English counts as one character. |
|budget_auto_adjust_strategy|string|Valid only when the following conditions are all met:<ul><li>`budget_optimize_on` is `true`.</li><li>`budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.</li><li>`app_promotion_type` is not `MINIS`.</li></ul><br>The campaign budget strategy for automatic daily campaign budget.<br><br>Enum values: <ul><li>`AUTO_BUDGET_INCREASE`: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. <ul><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, the specified `budget` will be the initial daily budget. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>`UNSET`: To disable Goal-based budget increase.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>|
|budget|float|Fixed campaign budget or initial campaign budget.<ul><li>When `budget_auto_adjust_strategy`  is `UNSET`, this field represents your fixed campaign budget.</li><li>When `budget_auto_adjust_strategy`  is `AUTO_BUDGET_INCREASE`, this field represents your initial campaign budget. To retrieve the current campaign budget, check the returned `current_budget`.</li></ul><br>To learn about the minimum budget and how to set budget modes, see [Budget](https://business-api.tiktok.com/portal/docs?id=1739381246298114).<br>To directly see the daily budget value range for a currency, see [Currency-Daily budget value range](https://business-api.tiktok.com/portal/docs?id=1737585839634433#item-link-Daily%20budget%20value%20range).|
|po_number|string|PO (purchase order) number.<br><br>PO numbers are useful for invoice tracking and reconciliation. <br><br>[Learn more about PO numbers on monthly invoices](https://ads.tiktok.com/help/article/update-po-number-on-monthly-invoices).|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/update/' \
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
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|advertiser_id|string|Advertiser ID.|
#|campaign_id|string|Campaign ID.|
#|create_time|string|The time when the campaign was created, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time)|
#|modify_time|string|The time when the campaign was last modified, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time)|
#|objective_type|string|Advertising objective.<br><br>Currently, we support `APP_PROMOTION`, `WEB_CONVERSIONS`, and `LEAD_GENERATION`. <br>For detailed explanation of enum values, see [Enumeration-Advertising Objective](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Advertising%20Objective).|
#|app_promotion_type|string|App promotion type.<br><br>Enum value:<ul><li>`APP_INSTALL`: App install. Get new users to install your app.</li><li>`APP_RETARGETING`: App retargeting. Re-engage existing app users to take action in your app.</li><li>`MINIS`: TikTok Minis or Series. Promote your Series, mini series, or mini game by optimizing for organic and ads traffic.</li></ul>|
#|sales_destination|string |Sales destination, the destination where you want to drive your sales.<br><br>Enum values:<ul><li>`WEBSITE`: Website. Drive sales on your website.</li><li>`APP`: App. Drive sales on your app (product catalog required).</li><li>`WEB_AND_APP`: Website and app. Drive sales on both your website and your app.</li></ul>|
#|campaign_type|string|Campaign Type.<br><br>Currently, we support `REGULAR_CAMPAIGN` and `IOS14_CAMPAIGN`.|
#|app_id|string|The App ID of the app to promote.<br><br>To get a list of App IDs, use [/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786).|
#|gaming_ad_compliance_agreement|string|Whether to agree to the Compliance Assurance Policy for Gaming Advertisers on TikTok.<br><br>The policy is as follows: Yo confirms and attest that any gaming application, product or service (game) you desire to advertise on TikTok, including any associated URL(s), (a) complies with all applicable laws and regulations of the jurisdictions where the game can be accessed or played, and upon request, can provide supporting documentation as evidence of why the game is not considered illegal gambling or lottery; and (b) has not been and is not part of any investigation or lawsuit regarding the game's legality or regulatory compliance.<br><br>Enum values: <ul><li>`ON`: To agree to the policy.</li><li>`OFF`: To leave the policy not accepted.</li></ul>|
#|is_advanced_dedicated_campaign|boolean|Whether the campaign is an Advanced Dedicated Campaign. Advanced Dedicated Campaigns leverage advanced delivery models that benefit from real-time signals.<br><br>Supported values: `true`, `false`.|
#|disable_skan_campaign|boolean|Whether to disable SKAN (SKAdNetwork) attribution, Apple's conversion attribution solution for iOS campaigns.<br><br>Enum values:<ul><li>`true`: To disable SKAN attribution. The campaign will not be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [Self Attribution Network (SAN) metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics) for the campaign. However, you cannot retrieve [SKAN reporting metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign. Learn more about [SAN integration](https://ads.tiktok.com/help/article/about-self-attribution-transition).</li><li>`false`: To enable SKAN attribution. The campaign will be bound by [Dedicated Campaign quota limits](https://business-api.tiktok.com/portal/docs?id=1752256248260610) and you will be able to retrieve [SKAN metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics) for the campaign.</li></ul>|
#|bid_align_type|string|The attribution type for the Dedicated Campaign. The type determines which attribution network the target CPA (conversion_bid_price) or ROAS (roas_bid) at the ad group level is based on.<br><br>Enum values:<ul><li>`SAN`: SAN.</li><li>`SKAN`: SKAN.</li></ul>|
#|campaign_app_profile_page_state|string|Indicates the status of the App Profile Page.<br><br>Enum values: `ON`, `OFF`.|
#|catalog_enabled|boolean|Whether to use your catalog to automatically advertise relevant products or services to people based on their unique interests, intent and actions.<br><br>Supported values: `true`, `false`.|
#|catalog_type|string|The type of catalog.<br>Enum values: <ul><li>`ECOMMERCE`: e-commerce.</li><li>`TRAVEL_ENTERTAINMENT`: travel and entertainment.</li><li>`MINI_SERIES`: mini series.</li></ul>|
#|campaign_name|string|Campaign name.|
#|special_industries|string[]|Ad categories.<br><br>Enum values:<ul><li>`HOUSING`: Ads for real estate listings, homeowners insurance, mortgage loans or other related opportunities.</li><li>`EMPLOYMENT`: Ads for job offers, internships, professional certification programs or other related opportunities.</li><li>`CREDIT`: Ads for credit card offers, auto loans, long-term financing or other related opportunities.</li></ul>|
#|budget_optimize_on|boolean|Whether CBO is enabled.<br><br>Supported values: `true` (enabled), `false` (disabled).|
#|budget_mode|string|Budget mode.<br><br><ul><li>When `budget_optimize_on` is `true` or not specified, the enum values are:<ul><li>`BUDGET_MODE_DYNAMIC_DAILY_BUDGET`: Dynamic daily campaign budget. It is the average daily budget over a week. Daily costs will not exceed 125% of the average daily budget. Weekly costs will not exceed the average daily budget * 7.</li><li>`BUDGET_MODE_TOTAL`: Lifetime campaign budget.</li></ul></li><li>When `budget_optimize_on` is `false`, the enum values are:<ul><li>`BUDGET_MODE_INFINITE`: No limit for all ad groups under this campaign.</li><li>`BUDGET_MODE_DAY`: Daily budget limit for all ad groups under this campaign.</li><li>`BUDGET_MODE_TOTAL`: Total budget limit for all ad groups under this campaign.</li></ul></li></ul>|
#|budget_auto_adjust_strategy|string|Returned only when the following conditions are all met:<ul><li>`budget_optimize_on` is `true` or not specified.</li><li>`budget_mode` is `BUDGET_MODE_DYNAMIC_DAILY_BUDGET`.</li><li>`app_promotion_type` is not `MINIS`.</li></ul><br>The campaign budget strategy for automatic daily campaign budget.<br><br>Enum values: <ul><li>`AUTO_BUDGET_INCREASE`: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. <ul><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, the specified `budget` will be the initial daily budget. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>`UNSET`: To disable Goal-based budget increase.</li></ul>|
#|budget|float|<ul><li>When `budget_optimize_on` is `true` or not specified, this field represents fixed campaign budget or initial campaign budget.<ul><li>When `budget_auto_adjust_strategy` is `UNSET`, this field represents your fixed campaign budget.</li><li>When `budget_auto_adjust_strategy` is `AUTO_BUDGET_INCREASE`, this field represents your initial campaign budget. To retrieve the current campaign budget, check the returned `current_budget`.</li></ul></li><li>When `budget_optimize_on` is `false`, this field represents the limit for all ad groups under this campaign.<ul><li>When `budget_mode` is `BUDGET_MODE_DAY`, this field represents the daily limit for all ad groups under this campaign.</li><li>When `budget_mode` is `BUDGET_MODE_TOTAL`, this field represents the total limit for all ad groups under this campaign.</li></ul></li></ul>|
#|current_budget|float|Returned only when `budget_auto_adjust_strategy`  is `AUTO_BUDGET_INCREASE`.<br><br>Current campaign budget for a campaign with Goal-based budget increase enabled.|
#|operation_status|string|Operation status.<br><br>Enum values:<ul><li>`ENABLE` : The campaign is enabled (in 'ON' status).</li><li>`DISABLE`: The campaign is disabled (in 'OFF' status).</li></ul>|
#|secondary_status|string|Campaign secondary status.<br><br>For enum values, see [Enumeration- Campaign Status - Secondary Status](https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Campaign%20Status%20-%20Secondary%20Status).|
#|smart_plus_adgroup_mode|string|The ad group mode for the campaign.<br><br>Enum values:<ul><li>`SINGLE`: You can only create one single ad group within the campaign.</li><li>`MULTIPLE`: You can only create multiple ad groups within the campaign.</li></ul>|
#|postback_window_mode|string|The mode that determines which SKAN 4.0 postback you want to secure.<br><br>Enum values:<ul><li>`POSTBACK_WINDOW_MODE1`: This option secures the first postback, which corresponds to the 0-2 day attribution window. The data can take up to 4 days to return, and the campaign will wait for 4 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE2`: This mode secures the first two postbacks, which correspond to the 3-7 day attribution window. The data can take up to 13 days to return, and the campaign will wait for 13 days to release the campaign quota.</li><li>`POSTBACK_WINDOW_MODE3`: This mode secures all three postbacks, which correspond to the 8-35 day attribution window. The data can take up to 41 days to return, and the campaign will wait for 41 days to release the campaign quota.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you have set up Mobile Measurement Partner (MMP) Tracking with <b>Adjust</b>, <b>Airbridge</b>, <b>Appsflyer</b>, <b>Branch</b>, <b>Kochava</b>, or <b>Singular</b> for your App and your MMP SDK version is updated to a SKAN 4.0 supported SDK, you can transition your App to SKAN 4.0 on Events Manager. To learn about how to set up MMP tracking for your App, see [How to Set Up App Attribution in TikTok Ads Manager](https://ads.tiktok.com/help/article/set-up-app-attribution-tiktok-ads-manager). To learn more about how to transition your App to SKAN 4.0, see [About SKAN 4.0 and TikTok](https://ads.tiktok.com/help/article/about-skan-4-0-and-tiktok) and [How to transition to SKAN 4.0](https://ads.tiktok.com/help/article/how-to-transition-to-skan-4-0).|
#|po_number|string|PO (purchase order) number.<br><br>PO numbers are useful for invoice tracking and reconciliation. <br><br>[Learn more about PO numbers on monthly invoices](https://ads.tiktok.com/help/article/update-po-number-on-monthly-invoices).|
#|is_promotional_campaign|boolean|Whether to use promotion campaign settings.<br><br>Enable this feature to activate specialized campaign setup flows and optimization for theatrical releases and streaming service promotions.<br><br>Supported values: `true`, `false`.|
#|rta_id|string|Realtime API (RTA) ID, the RTA strategy identifier.|
#|rta_bid_enabled|boolean|Whether to use RTA bid.<br><br>Supported values:<ul><li>`true`: enabled.</li><li>`false`: not enabled.</li></ul>|
#|rta_product_selection_enabled|boolean|Whether to use RTA to automatically select products.<br><br>Enum values:<ul><li>`true`: enabled.</li><li>`false`: not enabled.</li></ul>|
```

### Example

```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_id": "{{advertiser_id}}",
        "app_id": "{{app_id}}",
        "app_promotion_type": "APP_INSTALL",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_app_profile_page_state": "ON",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "IOS14_CAMPAIGN",
        "create_time": "{{create_time}}",
        "disable_skan_campaign": false,
        "is_advanced_dedicated_campaign": true,
        "modify_time": "{{modify_time}}",
        "objective_type": "APP_PROMOTION",
        "operation_status": "ENABLE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```

