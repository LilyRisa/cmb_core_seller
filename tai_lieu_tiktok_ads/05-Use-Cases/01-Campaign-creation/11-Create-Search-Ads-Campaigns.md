---
title: "Create Search Ads Campaigns"
doc_id: 1815394136739841
path: "Use Cases / Campaign creation / Create Search Ads Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Search Ads Campaigns

This article walks you through the steps to create Search Ads Campaigns.

# Introduction
Search Ads Campaigns are a keyword-based campaign type that will reach TikTok users within the Search Results Page with an organic user experience. With Search Ads Campaigns, you can control campaign budgets specific to search and target specific keywords for optimal reach and engagement.


**You can use Campaign Management API to create Search Ads Campaigns, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To create Search Ads Campaigns, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- Search Ads Campaign under different campaign objectives are controlled by separate allowlists. If you would like to access them, please contact your TikTok representative.

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Advertising objective | Any of the following options:<ul><li><code>WEBSITE_CONVERSIONS</code></li><li><code>TRAFFIC</code></li><li><code>LEAD_GENERATION</code></li></ul> | <code>objective_type</code> | Any of the following values:<ul><li><code>WEB_CONVERSIONS</code></li><li><code>TRAFFIC</code></li><li><code>LEAD_GENERATION</code></li></ul> |
| Search Campaign | Enabled | <code>is_search_campaign</code> | <code>true</code> |
| Smart+ Campaign | Disabled<br><br>You cannot use [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) to enable Smart+ Campaign for a Search Ads Campaign.<br>To learn about how to enable the Smart+ Campaign setting for the Website conversions objective, see [Create Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186). | / | / |
| Special ad categories (Optional) | Enabled or disabled | <code>special_industries</code> | Pass valid values or not passed. |
| Campaign Budget Optimization (CBO) | Disabled | <code>budget_optimize_on</code> | <code>false</code> or not passed. |
```

### Example
#### Advertising objective as Website Conversions
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "is_search_campaign": true,
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_id": "{{campaign_id}}",
        "modify_time": "{{modify_time}}",
        "is_smart_performance_campaign": false,
        "objective": "LANDING_PAGE",
        "budget": {{budget}},
        "roas_bid": 0.0,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_type": "REGULAR_CAMPAIGN",
        "objective_type": "WEB_CONVERSIONS",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "operation_status": "ENABLE",
        "campaign_name": "{{campaign_name}}",
        "deep_bid_type": null,
        "create_time": "{{create_time}}",
        "is_search_campaign": true,
        "is_new_structure": true,
        "advertiser_id": "{{advertiser_id}}",
        "is_advanced_dedicated_campaign": false
    }
}
(/code)
```

#### Advertising objective as Traffic
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "TRAFFIC",
    "is_search_campaign": true,
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "campaign_id": "{{campaign_id}}",
        "modify_time": "{{modify_time}}",
        "is_smart_performance_campaign": false,
        "objective": "LANDING_PAGE",
        "budget": {{budget}},
        "roas_bid": 0.0,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_type": "REGULAR_CAMPAIGN",
        "objective_type": "TRAFFIC",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "operation_status": "ENABLE",
        "campaign_name": "{{campaign_name}}",
        "deep_bid_type": null,
        "create_time": "{{create_time}}",
        "is_search_campaign": true,
        "is_new_structure": true,
        "advertiser_id": "{{advertiser_id}}",
        "is_advanced_dedicated_campaign": false
    }
}
(/code)
```

#### Advertising objective as Lead Generation
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "is_search_campaign": true,
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "is_advanced_dedicated_campaign": false,
        "create_time": "{{create_time}}",
        "catalog_enabled": false,
        "objective": "LANDING_PAGE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "is_smart_performance_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "is_new_structure": true,
        "is_search_campaign": true,
        "campaign_id": "{{campaign_id}}",
        "roas_bid": 0,
        "campaign_name": "{{campaign_name}}",
        "operation_status": "ENABLE",
        "objective_type": "LEAD_GENERATION",
        "modify_time": "{{modify_time}}",
        "deep_bid_type": null,
        "budget": {{budget}}
    }
}
(/code)
```

## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

### For Website Conversions objective
```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Optimization location <br> (Promotion type) | One of the following types:<ul><li>Website</li><li>TikTok Instant Page</li></ul> | `promotion_type`<br>`promotion_website_type` | <ul><li>To specify promotion type as Website:<ul><li>Set `promotion_type` to `WEBSITE`.</li><li>Set `promotion_website_type` to `UNSET` or do not pass this field.</li></ul></li><li>To specify promotion type as TikTok Instant Page:<ul><li>Set `promotion_type` to `WEBSITE`.</li><li>Set `promotion_website_type` to `TIKTOK_NATIVE_PAGE`.</li></ul></li></ul> |
| Pixel | <ul><li>Required when the Optimization location is Website.</li><li>Not supported when the Optimization location is TikTok Instant Page.</li></ul> | `pixel_id` | <ul><li>Required when `promotion_website_type` is set to `UNSET` or Not passed</li><li>Not supported when `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`.</li></ul> |
| Optimization event | Specified.<br><br><p><span style="color:darkred"><b>Note</b></span>: When the Optimization location is TikTok Instant Page, the only supported optimization event is Click Button (to optimize for outbound clicks on your Instant Page). | `optimization_event` | Pass a valid value.<br><br>To obtain the optimization events that have been configured for a Pixel, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978).<br>See [Conversion events - Pixel events](https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events%0A%0A) for enum values.<br><br><p><span style="color:darkred"><b>Note</b></span>: When `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`, this field can only be set to `BUTTON`. |
| Placement | TikTok search result.<br>Show your ads to users when they search for terms that are relevant to your keywords on TikTok. | `placement_type`<br>`placements` | Set `placement_type` to `PLACEMENT_TYPE_NORMAL` and set `placements` to `["PLACEMENT_TIKTOK"]`. |
| Include search results | Disabled<br><br><p><span style="color:darkred"><b>Important</b></span>: The Search Ads Campaign is not compatible with the [Automatic Search Placement](https://business-api.tiktok.com/portal/docs?id=1771747626094593). While Search Ads can extend your in-feed advertising to the TikTok search results page, the Search Ads Campaign is specifically designed to target the TikTok search results page. | `search_result_enabled` | Not passed (recommended)<br>This field will be ignored and default to `false`. |
| Pangle block list | Disabled | `blocked_pangle_app_ids` | Not passed |
| Audience targeting <br>&#183; Saved audience | Disabled | `saved_audience_id` | Not passed |
| Audience targeting <br>&#183; Automatic targeting | Disabled | `auto_targeting_enabled` | Not passed |
| Audience <br>&#183; Include<br>&#183; Exclude | Disabled | `audience_ids`<br>`excluded_audience_ids` | Not passed |
| Smart audience | Disabled | `smart_audience_enabled` | Not passed |
| Interests & Behaviors<br>&#183; Interests| Disabled |`interest_category_ids`<br>`interest_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Purchase intention| Disabled | `purchase_intention_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Video interactions & Creator interactions & Hashtag interactions| Disabled | `actions`| Not passed |
| Smart interests & behaviors | Disabled | `smart_interest_behavior_enabled` | Not passed |
| Pangle audience packages | Disabled | `included_pangle_audience_package_ids`<br>`excluded_pangle_audience_package_ids` | Not passed |
| Targeting expansion | Disabled | `targeting_expansion` | Not passed |
| <b>Keywords</b> | <b>Specify a list of 1 to 1,000 valid search keywords </b>| `keyword`<br>`match_type` | Specify a list of 1 to 1,000 valid search keywords along with the corresponding match types via `keyword` and `match_type`.<ul><li>To obtain recommended search keywords, use [/tool/search_keyword/recommend/](https://business-api.tiktok.com/portal/docs?id=1838630375757825).</li><li>To obtain the review result for search keywords, call [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) and check the `audit_status` within the `search_keywords` object array.</li></ul> |
| Automated keywords | Supported with allowlist | `automated_keywords_enabled` | `true` or `false` (or not passed).<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated keywords in Search Ads Campaigns are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Bid for each keyword & match type combination (Optional) | Disabled | `keyword_bid_type`<br>`keyword_bid` | Not passed |
| Contextual Targeting | Disabled | `contextual_tag_ids` | Not passed |
| Brand safety and suitability<br>&#183; Inventory filter | Disabled or enabled with any of the following tiers:<ul><li>Expanded</li><li>Standard</li><li>Limited</li></ul> | `brand_safety_type` | Not specified, or set to any of the following values:<ul><li>`EXPANDED_INVENTORY`</li><li>`STANDARD_INVENTORY`</li><li>`LIMITED_INVENTORY`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter for Search Ads Campaign is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Third-party filters | Disabled | `brand_safety_partner` | Not specified |
| Brand safety and suitability<br>&#183; Category exclusions | <ul><li>When Inventory filter is Expanded:<ul><li>Disabled</li></ul></li><li>When Inventory filter is Standard or Limited:<ul><li>Disabled or Enabled</li></ul></li></ul> | `category_exclusion_ids` | <ul><li>When `brand_safety_type` is `EXPANDED_INVENTORY` : Not specified</li><li>When `brand_safety_type` is `STANDARD_INVENTORY` or `LIMITED_INVENTORY`: Not specified, or specify valid values</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter and Category exclusions for Search Ads Campaign are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Vertical sensitivity | Disabled | `vertical_sensitivity_id` | Not specified |
| Optimization goal |<ul><li>When the promotion type is Website, the supported optimization goals are Conversion and Click.</li><li>When the promotion type is TikTok Instant Page, the only supported optimization goal is Conversion.</li></ul> | `optimization_goal` | <ul><li>When `promotion_website_type` is set to `UNSET` or not passed, set `optimization_goal` to `CONVERT` or `CLICK`.</li><li>When `promotion_website_type` is set to `TIKTOK_NATIVE_PAGE`, set `optimization_goal` to `CONVERT`. </li></ul> |
| Bid strategy | Cost cap with a Target CPA | `bid_type`<br>`bid_price`<br>`conversion_bid_price` | Set `bid_type` to `BID_TYPE_CUSTOM`.<ul><li>If `optimization_goal` is `CLICK`, pass `bid_price` at the same time.</li><li>If `optimization_goal` is `CONVERT`, pass `conversion_bid_price` at the same time.</li></ul> |
| Billing event | <ul><li>When the optimization goal is Click, the supported billing event is CPC.</li><li>When the optimization goal is Conversion, the supported billing event is oCPM.</li></ul> | `billing_event` | <ul><li>When `optimization_goal` is `CLICK`, set `billing_event` to `CPC`.</li><li>When `optimization_goal` is `CONVERT`, set `billing_event` to `OCPM`. </li></ul> |
| Delivery Type | Standard | `pacing` | `PACING_MODE_SMOOTH` |
| Automated Creative Optimization (ACO) | Disabled | `creative_material_mode` | `CUSTOM` or not passed |
| Split test | Disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: Search ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests. | / | / |
| Negative Keywords (Optional) | You have the option to configure up to 10,000 [Negative Keywords](https://business-api.tiktok.com/portal/docs?id=1775103049049090) at the same time for the ad group. Negative keywords can filter out irrelevant queries or queries that may affect your brand safety. | / | / |
```


#### Example
##### Optimization location as Website with optimization goal as Conversion
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"CONSULT",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "search_result_enabled": false,
    "location_ids": ["1733045"],
    "search_keywords":[
        {
        "keyword":"{{keyword}}",
        "match_type":"BROAD_WORD"
        }
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "network_types": [],
        "adgroup_id": "{{adgroup_id}}",
        "gender": "GENDER_UNLIMITED",
        "schedule_infos": null,
        "inventory_filter_enabled": false,
        "interest_category_ids": [],
        "skip_learning_phase": true,
        "adgroup_app_profile_page_state": null,
        "conversion_bid_price": {{conversion_bid_price}},
        "frequency": null,
        "category_exclusion_ids": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "promotion_website_type": "UNSET",
        "modify_time": "{{modify_time}}",
        "attribution_event_count": "EVERY",
        "bid_price": 0,
        "pacing": "PACING_MODE_SMOOTH",
        "location_ids": [
            "1733045"
        ],
        "rf_estimated_frequency": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "share_disabled": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "auto_targeting_enabled": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "statistic_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "conversion_window": null,
        "app_type": null,
        "languages": [],
        "search_result_enabled": false,
        "is_smart_performance_campaign": false,
        "purchased_impression": null,
        "optimization_goal": "CONVERT",
        "adgroup_name": "{{adgroup_name}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "delivery_mode": null,
        "operating_systems": [],
        "is_new_structure": true,
        "app_id": null,
        "is_hfss": false,
        "actions": [],
        "operation_status": "ENABLE",
        "deep_cpa_bid": 0,
        "rf_estimated_cpr": null,
        "interest_keyword_ids": [],
        "comment_disabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "smart_audience_enabled": null,
        "rf_purchased_type": null,
        "billing_event": "OCPM",
        "click_attribution_window": "SEVEN_DAYS",
        "keywords": null,
        "pixel_id": "{{pixel_id}}",
        "scheduled_budget": 0,
        "isp_ids": [],
        "bid_display_mode": "CPMV",
        "bid_type": "BID_TYPE_CUSTOM",
        "device_price_ranges": null,
        "secondary_optimization_event": null,
        "category_id": "0",
        "frequency_schedule": null,
        "create_time": "{{create_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "creative_material_mode": "CUSTOM",
        "smart_interest_behavior_enabled": null,
        "campaign_name": "{{campaign_name}}",
        "campaign_id": "{{campaign_id}}",
        "budget": {{budget}},
        "brand_safety_partner": null,
        "search_keywords": [
            {
                "match_type": "BROAD_WORD",
                "audit_status": "AUDITING",
                "keyword": "{{keyword}}"
            }
        ],
        "app_download_url": null,
        "purchased_reach": null,
        "audience_ids": [],
        "view_attribution_window": "ONE_DAY",
        "promotion_type": "WEBSITE",
        "optimization_event": "CONSULT",
        "ios14_quota_type": "UNOCCUPIED",
        "video_download_disabled": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "feed_type": null,
        "next_day_retention": null,
        "excluded_audience_ids": [],
        "deep_bid_type": null,
        "age_groups": null
    }
}
(/code)
```

##### Optimization location as TikTok instant Page with optimization goal as Conversion
Request
```xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "promotion_website_type":"TIKTOK_NATIVE_PAGE",
    "optimization_event":"CONSULT",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "search_result_enabled": false,
    "location_ids": ["1733045"],
    "search_keywords":[
        {
        "keyword":"{{keyword}}",
        "match_type":"BROAD_WORD"
        }
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "network_types": [],
        "adgroup_id": "{{adgroup_id}}",
        "gender": "GENDER_UNLIMITED",
        "schedule_infos": null,
        "inventory_filter_enabled": false,
        "interest_category_ids": [],
        "skip_learning_phase": true,
        "adgroup_app_profile_page_state": null,
        "conversion_bid_price": {{conversion_bid_price}},
        "frequency": null,
        "category_exclusion_ids": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "promotion_website_type": "TIKTOK_NATIVE_PAGE",
        "modify_time": "{{modify_time}}",
        "attribution_event_count": "ONCE",
        "bid_price": 0,
        "pacing": "PACING_MODE_SMOOTH",
        "location_ids": [
            "1733045"
        ],
        "rf_estimated_frequency": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "share_disabled": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "auto_targeting_enabled": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "statistic_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "conversion_window": null,
        "app_type": null,
        "languages": [],
        "search_result_enabled": false,
        "is_smart_performance_campaign": false,
        "purchased_impression": null,
        "optimization_goal": "CONVERT",
        "adgroup_name": "{{adgroup_name}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "delivery_mode": null,
        "operating_systems": [],
        "is_new_structure": true,
        "app_id": null,
        "is_hfss": false,
        "actions": [],
        "operation_status": "ENABLE",
        "deep_cpa_bid": 0,
        "rf_estimated_cpr": null,
        "interest_keyword_ids": [],
        "comment_disabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "smart_audience_enabled": null,
        "rf_purchased_type": null,
        "billing_event": "OCPM",
        "click_attribution_window": "SEVEN_DAYS",
        "keywords": null,
        "pixel_id": null,
        "scheduled_budget": 0,
        "isp_ids": [],
        "bid_display_mode": "CPMV",
        "bid_type": "BID_TYPE_CUSTOM",
        "device_price_ranges": null,
        "secondary_optimization_event": null,
        "category_id": "0",
        "frequency_schedule": null,
        "create_time": "{{create_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "creative_material_mode": "CUSTOM",
        "smart_interest_behavior_enabled": null,
        "campaign_name": "{{campaign_name}}",
        "campaign_id": "{{campaign_id}}",
        "budget": {{budget}},
        "brand_safety_partner": null,
        "search_keywords": [
            {
                "match_type": "BROAD_WORD",
                "audit_status": "AUDITING",
                "keyword": "{{keyword}}"
            }
        ],
        "app_download_url": null,
        "purchased_reach": null,
        "audience_ids": [],
        "view_attribution_window": "ONE_DAY",
        "promotion_type": "WEBSITE",
        "optimization_event": "CONSULT",
        "ios14_quota_type": "UNOCCUPIED",
        "video_download_disabled": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "feed_type": null,
        "next_day_retention": null,
        "excluded_audience_ids": [],
        "deep_bid_type": null,
        "age_groups": null
    }
}
(/code)
```
### For Traffic objective
```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Optimization location <br>(Promotion type) | Website | `promotion_type` | `WEBSITE` |
| Pixel & Optimization event | Disabled | `pixel_id`<br>`optimization_event` | Not passed |
| Placement | TikTok search result.<br>Show your ads to users when they search for terms that are relevant to your keywords on TikTok. | `placement_type`<br>`placements` | Set `placement_type` to `PLACEMENT_TYPE_NORMAL` and set `placements` to `["PLACEMENT_TIKTOK"]`. |
| Include search results | Disabled<br><br><p><span style="color:darkred"><b>Important</b></span>: The Search Ads Campaign is not compatible with the [Automatic Search Placement](https://business-api.tiktok.com/portal/docs?id=1771747626094593). While Search Ads can extend your in-feed advertising to the TikTok search results page, the Search Ads Campaign is specifically designed to target the TikTok search results page. | `search_result_enabled` | Not passed (recommended)<br>This field will be ignored and default to `false`. |
| Pangle block list | Disabled | `blocked_pangle_app_ids` | Not passed |
| Audience targeting <br>&#183; Saved audience | Disabled | `saved_audience_id` | Not passed |
| Audience targeting <br>&#183; Automatic targeting | Disabled | `auto_targeting_enabled` | Not passed |
| Audience <br>&#183; Include<br>&#183; Exclude | Disabled | `audience_ids`<br>`excluded_audience_ids` | Not passed |
| Smart audience | Disabled | `smart_audience_enabled` | Not passed |
| Interests & Behaviors<br>&#183; Interests| Disabled |`interest_category_ids`<br>`interest_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Purchase intention| Disabled | `purchase_intention_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Video interactions & Creator interactions & Hashtag interactions| Disabled | `actions`| Not passed |
| Smart interests & behaviors | Disabled | `smart_interest_behavior_enabled` | Not passed |
| Pangle audience packages | Disabled | `included_pangle_audience_package_ids`<br>`excluded_pangle_audience_package_ids` | Not passed |
| Targeting expansion | Disabled | `targeting_expansion` | Not passed |
| <b>Keywords</b> | <b>Specify a list of 1 to 1,000 valid search keywords </b>| `keyword`<br>`match_type` | Specify a list of 1 to 1,000 valid search keywords along with the corresponding match types via `keyword` and `match_type`.<ul><li>To obtain recommended search keywords, use [/tool/search_keyword/recommend/](https://business-api.tiktok.com/portal/docs?id=1838630375757825).</li><li>To obtain the review result for search keywords, call [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) and check the `audit_status` within the `search_keywords` object array.</li></ul> |
| Automated keywords | Supported with allowlist | `automated_keywords_enabled` | `true` or `false` (or not passed).<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated keywords in Search Ads Campaigns are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Bid for each keyword & match type combination (Optional) | Disabled or enabled<ul><li>Only available when bidding strategy is Cost Cap</li><li>Default: Ad group level bid applies to all keyword and match type combinations</li><li>Optional: Customize bids for specific keyword and match type combinations</li></ul> | `keyword_bid_type`<br>`keyword_bid` | Pass valid values or not passed<br>By default, `keyword_bid_type` is `FOLLOW_ADGROUP` and `keyword_bid` uses the ad group level bid (`bid_price`).<br>Optionally, when `bid_type` is set to `BID_TYPE_CUSTOM`, you can customize the bid for a keyword and match type combination by setting `keyword_bid_type` to `CUSTOM` and specifying a desired bid amount via `keyword_bid`. |
| Contextual Targeting | Disabled | `contextual_tag_ids` | Not passed |
| Brand safety and suitability<br>&#183; Inventory filter | Disabled or enabled with any of the following tiers:<ul><li>Expanded</li><li>Standard</li><li>Limited</li></ul> | `brand_safety_type` | Not specified, or set to any of the following values:<ul><li>`EXPANDED_INVENTORY`</li><li>`STANDARD_INVENTORY`</li><li>`LIMITED_INVENTORY`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter and Category exclusions for Search Ads Campaign are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Third-party filters | Disabled | `brand_safety_partner` | Not specified |
| Brand safety and suitability<br>&#183; Category exclusions | <ul><li>When Inventory filter is Expanded:<ul><li>Disabled</li></ul></li><li>When Inventory filter is Standard or Limited:<ul><li>Disabled or Enabled</li></ul></li></ul> | `category_exclusion_ids` | <ul><li>When `brand_safety_type` is `EXPANDED_INVENTORY` : Not specified</li><li>When `brand_safety_type` is `STANDARD_INVENTORY` or `LIMITED_INVENTORY`: Not specified, or specify valid values</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter and Category exclusions for Search Ads Campaign are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Vertical sensitivity | Disabled | `vertical_sensitivity_id` | Not specified |
| Optimization goal | Click | `optimization_goal` | `CLICK` |
| Bid strategy | Any of the following types:<ul><li>Cost Cap</li><li>Maximum Delivery</li></ul> | `bid_type`<br>`bid_price` | <ul><li>To set Bid strategy as Cost Cap, set `bid_type` to `BID_TYPE_CUSTOM` and pass `bid_price` at the same time.</li><li>To set Bid strategy as Maximum Delivery, set `bid_type` to `BID_TYPE_NO_BID`. Do not pass `bid_price`.</li></ul> |
| Billing event | CPC | `billing_event` | `CPC` |
| Delivery Type | Standard | `pacing` | `PACING_MODE_SMOOTH` |
| Automated Creative Optimization (ACO) | Disabled | `creative_material_mode` | `CUSTOM` or not passed |
| Split test | Disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: Search ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests. | / | / |
| Negative Keywords (Optional) | You have the option to configure up to 10,000 [Negative Keywords](https://business-api.tiktok.com/portal/docs?id=1775103049049090) at the same time for the ad group. Negative keywords can filter out irrelevant queries or queries that may affect your brand safety. | / | / |
```

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "search_result_enabled": false,
    "location_ids": ["1733045"],
    "search_keywords":[
        {
        "keyword":"{{keyword}}",
        "match_type":"BROAD_WORD"
        }
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_app_profile_page_state": null,
        "brand_safety_partner": null,
        "category_id": "0",
        "bid_display_mode": "CPMV",
        "skip_learning_phase": false,
        "advertiser_id": "{{advertiser_id}}",
        "age_groups": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "smart_interest_behavior_enabled": null,
        "delivery_mode": null,
        "optimization_goal": "CLICK",
        "keywords": null,
        "campaign_id": "{{campaign_id}}",
        "adgroup_id": "{{adgroup_id}}",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "network_types": [],
        "app_download_url": null,
        "deep_cpa_bid": 0,
        "search_result_enabled": false,
        "pixel_id": null,
        "frequency_schedule": null,
        "statistic_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "create_time": "{{create_time}}",
        "frequency": null,
        "interest_category_ids": [],
        "actions": [],
        "feed_type": null,
        "smart_audience_enabled": null,
        "bid_price": {{bid_price}},
        "purchased_reach": null,
        "app_type": null,
        "conversion_window": null,
        "pacing": "PACING_MODE_SMOOTH",
        "audience_ids": [],
        "rf_estimated_cpr": null,
        "billing_event": "CPC",
        "is_new_structure": true,
        "device_price_ranges": null,
        "deep_bid_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "budget": {{}},
        "operation_status": "ENABLE",
        "category_exclusion_ids": [],
        "app_id": null,
        "secondary_optimization_event": null,
        "ios14_quota_type": "UNOCCUPIED",
        "schedule_infos": null,
        "is_smart_performance_campaign": false,
        "campaign_name": "{{campaign_name}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "optimization_event": null,
        "search_keywords": [
            {
                "match_type": "BROAD_WORD",
                "keyword": "{{keyword}}",
                "audit_status": "AUDITING",
                "keyword_bid_type": "FOLLOW_ADGROUP",
                "keyword_bid": 12.0
            }
        ],
        "schedule_end_time": "{{schedule_end_time}}",
        "location_ids": [
            "1733045"
        ],
        "isp_ids": [],
        "next_day_retention": null,
        "scheduled_budget": 0,
        "adgroup_name": "{{adgroup_name}}",
        "excluded_audience_ids": [],
        "gender": "GENDER_UNLIMITED",
        "auto_targeting_enabled": false,
        "rf_purchased_type": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "is_hfss": false,
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "creative_material_mode": "CUSTOM",
        "operating_systems": [],
        "purchased_impression": null,
        "video_download_disabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "interest_keyword_ids": [],
        "bid_type": "BID_TYPE_CUSTOM",
        "share_disabled": false,
        "rf_estimated_frequency": null,
        "promotion_type": "WEBSITE",
        "modify_time": "{{modify_time}}",
        "languages": [],
        "inventory_filter_enabled": false
    }
}
(/code)
```

### For Lead generation objective
```xtable
| Setting{20%} | Requirement{25%} | Parameter{20%} | How to configure the parameter{35%} |
|---|---|---|
| Optimization location <br>(Promotion type) | Any of the following options:<ul><li>Website</li><li>Instant Form</li></ul> | `promotion_type`<br>`promotion_target_type` | <ul><li>Set `promotion_type` to `LEAD_GENERATION`.</li><li>To specify Optimization location as Website, set `promotion_target_type` to `EXTERNAL_WEBSITE`.</li><li>To specify Optimization location as Instant Form, set `promotion_target_type` to `INSTANT_PAGE` or do not pass this field.</li></ul> |
| Pixel & Optimization event | <ul><li>If Optimization location is Website, you can enable or disable Pixel and Optimization event:<ul><li>If you enable Pixel and Optimization event, you need to set Optimization goal to Conversion.</li><li>If you disable Pixel and Optimization event, you need to set Optimization goal to Click.</li></ul></li><li>If Optimization location is Instant Form:<ul><li>Disabled</li></ul></li></ul> | `pixel_id`<br>`optimization_event` | <ul><li>If `promotion_target_type` is set to `EXTERNAL_WEBSITE`, you can either pass `pixel_id` and `optimization_event` at the same time, or not pass them. To obtain pixels with their associated optimization events, use [/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978).<ul><li>If `pixel_id` and `optimization_event` are passed, set `optimization_goal` to `CONVERT`.</li><li>If `pixel_id` and `optimization_event` are not passed, set `optimization_goal` to `CLICK`.</li></ul></li><li>If `promotion_target_type` is set to `INSTANT_PAGE` or not passed, do not pass `pixel_id` and `optimization_event`.</li></ul> |
| Placement | TikTok search result.<br>Show your ads to users when they search for terms that are relevant to your keywords on TikTok. | `placement_type`<br>`placements` | Set `placement_type` to `PLACEMENT_TYPE_NORMAL` and set `placements` to `["PLACEMENT_TIKTOK"]`. |
| Include search results | Disabled<br><br><p><span style="color:darkred"><b>Important</b></span>: The Search Ads Campaign is not compatible with the [Automatic Search Placement](https://business-api.tiktok.com/portal/docs?id=1771747626094593). While Search Ads can extend your in-feed advertising to the TikTok search results page, the Search Ads Campaign is specifically designed to target the TikTok search results page. | `search_result_enabled` | Not passed (recommended)<br>This field will be ignored and default to `false`. |
| Pangle block list | Disabled | `blocked_pangle_app_ids` | Not passed |
| Audience targeting <br>&#183; Saved audience | Disabled | `saved_audience_id` | Not passed |
| Audience targeting <br>&#183; Automatic targeting | Disabled | `auto_targeting_enabled` | Not passed |
| Audience <br>&#183; Include<br>&#183; Exclude | Disabled | `audience_ids`<br>`excluded_audience_ids` | Not passed |
| Smart audience | Disabled | `smart_audience_enabled` | Not passed |
| Interests & Behaviors<br>&#183; Interests| Disabled |`interest_category_ids`<br>`interest_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Purchase intention| Disabled | `purchase_intention_keyword_ids` | Not passed |
| Interests & Behaviors<br>&#183; Video interactions & Creator interactions & Hashtag interactions| Disabled | `actions`| Not passed |
| Smart interests & behaviors | Disabled | `smart_interest_behavior_enabled` | Not passed |
| Pangle audience packages | Disabled | `included_pangle_audience_package_ids`<br>`excluded_pangle_audience_package_ids` | Not passed |
| Targeting expansion | Disabled | `targeting_expansion` | Not passed |
| <b>Keywords</b> | <b>Specify a list of 1 to 1,000 valid search keywords </b>| `keyword`<br>`match_type` | Specify a list of 1 to 1,000 valid search keywords along with the corresponding match types via `keyword` and `match_type`.<ul><li>To obtain recommended search keywords, use [/tool/search_keyword/recommend/](https://business-api.tiktok.com/portal/docs?id=1838630375757825).</li><li>To obtain the review result for search keywords, call [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) and check the `audit_status` within the `search_keywords` object array.</li></ul> |
| Automated keywords | Supported with allowlist | `automated_keywords_enabled` | `true` or `false` (or not passed).<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated keywords in Search Ads Campaigns are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
| Bid for each keyword & match type combination (Optional) | Disabled | `keyword_bid_type`<br>`keyword_bid` | Not passed |
| Contextual Targeting | Disabled | `contextual_tag_ids` | Not passed |
| Brand safety and suitability<br>&#183; Inventory filter | Disabled or enabled with any of the following tiers:<ul><li>Expanded</li><li>Standard</li><li>Limited</li></ul> | `brand_safety_type` | Not specified, or set to any of the following values:<ul><li>`EXPANDED_INVENTORY`</li><li>`STANDARD_INVENTORY`</li><li>`LIMITED_INVENTORY`</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter and Category exclusions for Search Ads Campaign are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Third-party filters | Disabled | `brand_safety_partner` | Not specified |
| Brand safety and suitability<br>&#183; Category exclusions | <ul><li>When Inventory filter is Expanded:<ul><li>Disabled</li></ul></li><li>When Inventory filter is Standard or Limited:<ul><li>Disabled or Enabled</li></ul></li></ul> | `category_exclusion_ids` | <ul><li>When `brand_safety_type` is `EXPANDED_INVENTORY` : Not specified</li><li>When `brand_safety_type` is `STANDARD_INVENTORY` or `LIMITED_INVENTORY`: Not specified, or specify valid values</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Inventory filter and Category exclusions for Search Ads Campaign are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.|
| Brand safety and suitability<br>&#183; Vertical sensitivity | Disabled | `vertical_sensitivity_id` | Not specified |
| Optimization goal | <ul><li>If Optimization location is Website:<ul><li>Click</li><li>Conversion</li></ul></li><li>If Optimization location is Instant Form:<ul><li>Leads</li></ul></li></ul> | `optimization_goal` | <ul><li>If `promotion_target_type` is set to `EXTERNAL_WEBSITE`, set this field to `CONVERT` or `CLICK`.</li><li>If `promotion_target_type` is set to `INSTANT_PAGE` or not passed, set this field to `LEAD_GENERATION`. </ul></li>|
| Bid strategy | <ul><li>If Optimization location is Website and Optimization goal is Conversion:<ul><li>Cost Cap</li></ul></li><li>If Optimization location is Website and Optimization goal is Click:<ul><li>Cost Cap</li><li>Maximum Delivery</li></ul></li><li> If Optimization location is Instant Form:<ul><li>Cost Cap</li></ul></li></ul> | `bid_type`<br>`bid_price`<br>`conversion_bid_price` | <ul><li> If `promotion_target_type` is set to `EXTERNAL_WEBSITE` and `optimization_goal` is `CONVERT`, set `bid_type` to `BID_TYPE_CUSTOM` and pass `conversion_bid_price` at the same time.</li><li>  If `promotion_target_type` is set to `EXTERNAL_WEBSITE` and `optimization_goal` is `CLICK`:<ul><li>To set Bid strategy as Cost Cap, set `bid_type` to `BID_TYPE_CUSTOM` and pass `bid_price` at the same time.</li><li>To set Bid strategy as Maximum Delivery, set `bid_type` to `BID_TYPE_NO_BID`. Do not pass `bid_price` and `conversion_bid_price`.</li></ul></li><li>If `promotion_target_type` is set to `INSTANT_PAGE` or not passed, set `bid_type` to `BID_TYPE_CUSTOM` and pass `conversion_bid_price` at the same time..</li></ul> |
| Billing event | <ul><li>If Optimization goal is Conversion or Leads:<ul><li>oCPM</li></ul></li><li>If Optimization goal is Click:<ul><li>CPC</li></ul></li></ul> | `billing_event` | <ul><li> If `optimization_goal` is `CONVERT` or `LEAD_GENERATION`, set this field to `OCPM`.</li><li>If `optimization_goal` is `CLICK`, set this field to `CPC`.</ul></li> |
| Delivery Type | Standard | `pacing` | `PACING_MODE_SMOOTH` |
| Automated Creative Optimization (ACO) | Disabled | `creative_material_mode` | `CUSTOM` or not passed |
| Split test | Disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: Search ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split tests. | / | / |
| Negative Keywords (Optional) | You have the option to configure up to 10,000 [Negative Keywords](https://business-api.tiktok.com/portal/docs?id=1775103049049090) at the same time for the ad group. Negative keywords can filter out irrelevant queries or queries that may affect your brand safety. | / | / |
```

#### Example
##### Optimization location as Website with optimization goal as Conversion
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type":"EXTERNAL_WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"ON_WEB_DETAIL",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "search_keywords":[
        {
        "keyword":"{{keyword}}",
        "match_type":"BROAD_WORD"
        }
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "location_ids": [
            "6252001"
        ],
        "device_price_ranges": null,
        "click_attribution_window": "SEVEN_DAYS",
        "keywords": null,
        "isp_ids": [],
        "inventory_filter_enabled": false,
        "smart_interest_behavior_enabled": null,
        "interest_keyword_ids": [],
        "video_download_disabled": false,
        "adgroup_app_profile_page_state": null,
        "app_download_url": null,
        "purchased_reach": null,
        "creative_material_mode": "CUSTOM",
        "skip_learning_phase": true,
        "search_keywords": [
            {
                "audit_status": "AUDITING",
                "keyword": "{{keyword}}",
                "match_type": "BROAD_WORD"
            }
        ],
        "is_smart_performance_campaign": false,
        "operation_status": "ENABLE",
        "app_id": null,
        "rf_estimated_cpr": null,
        "pixel_id": "{{pixel_id}}",
        "campaign_name": "{{campaign_name}}",
        "statistic_type": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "search_result_enabled": false,
        "schedule_infos": null,
        "ios14_quota_type": "UNOCCUPIED",
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_id": "{{campaign_id}}",
        "operating_systems": [],
        "actions": [],
        "delivery_mode": null,
        "comment_disabled": false,
        "frequency": null,
        "audience_ids": [],
        "adgroup_name": "{{adgroup_name}}",
        "scheduled_budget": 0,
        "secondary_optimization_event": null,
        "optimization_goal": "CONVERT",
        "gender": "GENDER_UNLIMITED",
        "network_types": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "rf_estimated_frequency": null,
        "conversion_window": null,
        "frequency_schedule": null,
        "deep_bid_type": null,
        "app_type": null,
        "is_hfss": false,
        "billing_event": "OCPM",
        "smart_audience_enabled": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "attribution_event_count": "ONCE",
        "conversion_bid_price": {{conversion_bid_price}},
        "languages": [],
        "category_id": "0",
        "adgroup_id": "{{adgroup_id}}",
        "category_exclusion_ids": [],
        "budget": {{budget}},
        "pacing": "PACING_MODE_SMOOTH",
        "schedule_end_time": "{{schedule_end_time}}",
        "deep_cpa_bid": 0,
        "bid_display_mode": "CPMV",
        "promotion_type": "LEAD_GENERATION",
        "optimization_event": "ON_WEB_DETAIL",
        "is_new_structure": true,
        "auto_targeting_enabled": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "next_day_retention": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "schedule_type": "SCHEDULE_START_END",
        "advertiser_id": "{{advertiser_id}}",
        "age_groups": null,
        "purchased_impression": null,
        "brand_safety_partner": null,
        "view_attribution_window": "ONE_DAY",
        "modify_time": "{{modify_time}}",
        "bid_price": 0,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "create_time": "{{create_time}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "share_disabled": false,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "rf_purchased_type": null,
        "excluded_audience_ids": [],
        "interest_category_ids": [],
        "feed_type": null
    }
}
(/code)
```
##### Optimization location as Instant Form with optimization goal as Leads
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type":"INSTANT_PAGE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "search_keywords":[
        {
        "keyword":"{{keyword}}",
        "match_type":"BROAD_WORD"
        }
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "LEAD_GENERATION",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "audience_ids": [],
        "actions": [],
        "brand_safety_type": "NO_BRAND_SAFETY",
        "campaign_id": "{{campaign_id}}",
        "share_disabled": false,
        "next_day_retention": null,
        "rf_estimated_frequency": null,
        "is_new_structure": true,
        "search_keywords": [
            {
                "keyword": "{{keyword}}",
                "audit_status": "AUDITING",
                "match_type": "BROAD_WORD"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "frequency_schedule": null,
        "campaign_name": "{{campaign_name}}",
        "smart_audience_enabled": null,
        "delivery_mode": null,
        "operating_systems": [],
        "deep_bid_type": null,
        "pixel_id": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "attribution_event_count": "ONCE",
        "conversion_bid_price": {{conversion_bid_price}},
        "gender": "GENDER_UNLIMITED",
        "smart_interest_behavior_enabled": null,
        "statistic_type": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "pacing": "PACING_MODE_SMOOTH",
        "optimization_goal": "LEAD_GENERATION",
        "is_hfss": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "search_result_enabled": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "feed_type": null,
        "deep_cpa_bid": 0,
        "schedule_infos": null,
        "app_download_url": null,
        "rf_estimated_cpr": null,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "languages": [],
        "adgroup_app_profile_page_state": null,
        "bid_price": 0,
        "promotion_type": "LEAD_GENERATION",
        "promotion_target_type": "INSTANT_PAGE",
        "operation_status": "ENABLE",
        "scheduled_budget": 0,
        "modify_time": "{{modify_time}}",
        "app_id": null,
        "video_download_disabled": false,
        "is_smart_performance_campaign": false,
        "schedule_type": "SCHEDULE_START_END",
        "category_id": "0",
        "location_ids": [
            "6252001"
        ],
        "bid_display_mode": "CPMV",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "secondary_optimization_event": null,
        "network_types": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "auto_targeting_enabled": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "comment_disabled": false,
        "brand_safety_partner": null,
        "optimization_event": "FORM",
        "frequency": null,
        "purchased_reach": null,
        "purchased_impression": null,
        "isp_ids": [],
        "interest_category_ids": [],
        "ios14_quota_type": "UNOCCUPIED",
        "category_exclusion_ids": [],
        "billing_event": "OCPM",
        "rf_purchased_type": null,
        "keywords": null,
        "view_attribution_window": "ONE_DAY",
        "creative_material_mode": "CUSTOM",
        "click_attribution_window": "SEVEN_DAYS",
        "interest_keyword_ids": [],
        "skip_learning_phase": true,
        "inventory_filter_enabled": false,
        "app_type": null,
        "excluded_audience_ids": [],
        "device_price_ranges": null,
        "create_time": "{{create_time}}",
        "budget": {{budget}},
        "conversion_window": null
    }
}
(/code)
```
## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.


<table>
  <thead>
    <tr>
      <th style="width:18%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:26%">Parameters</th>
      <th style="width:30%">How to configure the parameters</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Smart Creative</td>
      <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank">/ad/aco/create/</a> to enable Smart Creative for Search Ads.</td>
      <td>/</td>
      <td>/</td>
    </tr>
    <tr>
      <td rowspan="3">Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Pass a valid value<br><br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
    <tr>
      <td>Ad format</td>
      <td>Any of the following options:<ul><li>Single video</li><li>Carousel images</li></ul></td>
      <td><code>ad_format</code></td>
      <td>Any of the following values:<ul><li><code>SINGLE_VIDEO</code></li><li><code>CAROUSEL_ADS</code></li></ul></td>
    </tr>
    <tr>
      <td>Ad creative</td>
      <td>When Ad format is Single video:<ul><li>For Custom User identity, or Spark Ads PUSH with TikTok User identity or TikTok Account User in Business Center identity, specify a video and a cover image.</li><li>For Authorized User identity, or Spark Ads PULL with TikTok User identity or TikTok Account User in Business Center identity, specify a TikTok video post.</li></ul><br>When Ad format is Carousel images:<ul><li>Follow the instructions in <a href="https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads" target="_blank">Steps for creating Standard Carousel Ads</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads, you can enable Smart Multi Ad Text by specifying up to five ad texts. The system will automatically select the most relevant to the audience and optimize for the best results.</li></ul></td>
      <td><code>video_id</code><br><code>image_ids</code><br><code>ad_text</code><br><code>ad_texts</code><br><code>tiktok_item_id</code><br><code>dark_post_status</code></td>
      <td>When <code>ad_format</code> is <code>SINGLE_VIDEO</code>:<ul><li>If <code>identity_type</code> is <code>CUSTOMIZED_USER</code>, specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and an ad text (<code>ad_text</code>) or multiple ad texts (<code>ad_texts</code>). Do not pass <code>tiktok_item_id</code>.</li><li>If <code>identity_type</code> is <code>TT_USER</code> or <code>BC_AUTH_TT</code>, you can create the ad through Spark Ads PULL or Spark Ads PUSH:<ul><li>Spark Ads PUSH: Specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>). Do not pass <code>tiktok_item_id</code>. You can set <code>dark_post_status</code> to <code>ON</code> to ensure the video only shows as ad and does not organically appear on the TikTok Account.</li><li>Spark Ads PULL: Specify the TikTok video post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, <code>ad_text</code>, and <code>dark_post_status</code>.</li><li> To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.</li></ul></li><li>If <code>identity_type</code> is <code>AUTH_CODE</code>, specify the TikTok video post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, and <code>ad_text</code>.</li></ul><br>When <code>ad_format</code> is <code>CAROUSEL_ADS</code>, see <a href="https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads" target="_blank">Steps for creating Standard Carousel Ads</a> to learn about how to create Standard Carousel Ads.</td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Specified</td>
      <td><code>call_to_action</code><br><code>call_to_action_id</code></td>
      <td>Pass a valid value for either <code>call_to_action</code> or <code>call_to_action_id</code>.<br><br><ul><li>To get the enum values for <code>call_to_action</code>, see <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">Enumerations - Call-to-action</a>.</li><li>For details about dynamic call-to-actions (CTAs) (<code>call_to_action_id</code>), see <a href="https://ads.tiktok.com/marketing_api/docs?id=1740307296329730" target="_blank">CTA Recommendations - Dynamic CTAs</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Interactive add-on (Optional for single video ads)</td>
      <td><ul><li>When Ad format is Single video, the only supported type is Display Card.</li><li>When Ad format is Carousel images, interactive add-ons are not supported.</li></ul></td>
      <td><code>card_id</code></td>
      <td>When <code>ad_format</code> is <code>SINGLE_VIDEO</code>: Not passed or specify the ID of a Display Card portfolio.<br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<br>To learn about how to get the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank">Cards</a>.<br><br>When <code>ad_format</code> is <code>CAROUSEL_ADS</code>: Not passed.</td>
    </tr>
    <tr>
      <td>Destination</td>
      <td><ul><li>When advertising objective is Website conversions at the campaign level:<ul><li>If Optimization location is specified as Website at the ad group level, the destination can be website URL or a TikTok Instant Page.</li><li>If Optimization location is specified as TikTok Instant Page at the ad group level, the destination can only be a TikTok Instant Page.</li></ul></li><li>When advertising objective is Traffic at the campaign level, the destination can be website URL or a TikTok Instant Page.</li><li>When advertising objective is Lead generation at the campaign level:<ul><li>If Optimization location is specified as Website at the ad group level, the destination can be website URL or a TikTok Instant Page.</li><li>If Optimization location is specified as Instant Form at the ad group level, the destination can only be an Instant Form.</li></ul></li></ul> </td>
      <td><code>landing_page_url</code><br><code>page_id</code><br><code>creative_type</code></td>
      <td><ul><li>When <code>objective_type</code> is set to <code>WEB_CONVERSIONS</code> at the campaign level:<ul><li>If <code>promotion_website_type</code> is set to <code>UNSET</code> or not passed at the ad group level, you need to specify a website URL through <code>landing_page_url</code>, or specify a TikTok Instant Page through <code>page_id</code>. To filter TikTok Instant Pages within your ad account, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>. Pass <code>advertiser_id</code> and set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li><li>If <code>promotion_website_type</code> is set to <code>TIKTOK_NATIVE_PAGE</code> at the ad group level, you need to specify a TikTok Instant Page through <code>page_id</code>.<ul><li>If <code>page_id</code> is passed, you can omit <code>creative_type</code>, then the field will default to <code>CUSTOM_INSTANT_PAGE</code>. Or you can manually set <code>creative_type</code> to <code>CUSTOM_INSTANT_PAGE</code>.</li></ul></li></ul></li><li> When <code>objective_type</code> is set to <code>TRAFFIC</code> at the campaign level, you can specify a website URL through <code>landing_page_url</code>, or specify a TikTok Instant Page through <code>page_id</code>.<ul><li>To filter TikTok Instant Pages within your ad account, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>. Pass <code>advertiser_id</code> and set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li></ul></li><li>When <code>objective_type</code> is set to <code>LEAD_GENERATION</code> at the campaign level:<ul><li>If <code>promotion_target_type</code> is set to <code>EXTERNAL_WEBSITE</code> at the ad group level, you need to specify a website URL through <code>landing_page_url</code>, or specify a TikTok Instant Page through <code>page_id</code>.<ul><li>To filter TikTok Instant Pages within your ad account, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>. Pass <code>advertiser_id</code>, set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li></ul></li><li>If <code>promotion_target_type</code> is set to <code>INSTANT_PAGE</code> or not passed at the ad group level, you need to specify a ready Instant Form through <code>page_id</code>.<ul><li>To filter ready Instant Forms within your ad account, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>. Pass <code>advertiser_id</code>, set <code>business_type</code> to <code>LEAD_GEN</code>, and set <code>status</code> to <code>PUBLISHED</code>.</li></ul></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Track viewability with third-party partner</td>
      <td>Disabled</td>
      <td><code>viewability_postbid_partner</code><br><code>viewability_vast_url</code></td>
      <td>Not passed</td>
    </tr>
    <tr>
      <td>Measure brand safety with third-party partner</td>
      <td>Disabled</td>
      <td><code>brand_safety_postbid_partner</code><br><code>brand_safety_vast_url</code></td>
      <td>Not passed</td>
    </tr>
  </tbody>
</table>


### Example
#### Destination as Website URL & ad format as non-Spark Ads Single Video
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "landing_page_url":"{{landing_page_url}}"
    }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "viewability_vast_url": null,
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "card_id": null,
                "vast_moat_enabled": false,
                "impression_tracking_url": null,
                "modify_time": "{{modify_time}}",
                "profile_image_url": "{{profile_image_url}}",
                "playable_url": "",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "fallback_type": "UNSET",
                "is_aco": false,
                "call_to_action": "SHOP_NOW",
                "image_ids": [
                    "{{image_id}}"
                ],
                "ad_format": "SINGLE_VIDEO",
                "app_name": "",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "page_id": null,
                "is_new_structure": true,
                "landing_page_urls": null,
                "display_name": "{{display_name}}",
                "operation_status": "ENABLE",
                "deeplink": "",
                "call_to_action_id": null,
                "identity_type": "CUSTOMIZED_USER",
                "creative_authorized": false,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_texts": null,
                "identity_id": "{{identity_id}}",
                "deeplink_type": "NORMAL",
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "music_id": null,
                "campaign_name": "{{campaign_name}}",
                "campaign_id": "{{campaign_id}}",
                "brand_safety_vast_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "video_id": "{{video_id}}",
                "ad_name": "{{ad_name}}",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "optimization_event": "CONSULT",
                "ad_text": "{{ad_text}}",
                "click_tracking_url": null
            }
        ]
    }
}
(/code)
```
#### Destination as Website URL & ad format as non-Spark Ads Carousel
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "CAROUSEL_ADS",
        "image_ids": [
              "{{image_id}}",
              "{{image_id}}"
         ],
        "music_id": "{{music_id}}",    
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "landing_page_url":"{{landing_page_url}}"
    }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "ad_text": "{{ad_text}}",
                "campaign_name": "{{campaign_name}}",
                "carousel_image_labels": null,
                "ad_format": "CAROUSEL_ADS",
                "is_new_structure": true,
                "landing_page_url": "{{landing_page_url}}",
                "click_tracking_url": null,
                "creative_type": null,
                "operation_status": "ENABLE",
                "call_to_action_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_vast_url": null,
                "identity_id": "{{identity_id}}",
                "card_id": null,
                "deeplink_type": "NORMAL",
                "adgroup_id": "{{adgroup_id}}",
                "ad_name": "{{ad_name}}",
                "viewability_postbid_partner": "UNSET",
                "viewability_vast_url": null,
                "call_to_action": "SHOP_NOW",
                "modify_time": "{{modify_time}}",
                "app_name": "",
                "deeplink": "",
                "image_ids": [
                    "{{image_id}}",
                    "{{image_id}}"
                ],
                "is_aco": false,
                "campaign_id": "{{campaign_id}}",
                "creative_authorized": false,
                "landing_page_urls": null,
                "fallback_type": "UNSET",
                "adgroup_name": "{{adgroup_name}}",
                "profile_image_url": "{{profile_image_url}}",
                "vast_moat_enabled": false,
                "tracking_pixel_id": 0,
                "display_name": "{{display_name}}",
                "identity_type": "CUSTOMIZED_USER",
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "page_id": null,
                "impression_tracking_url": null,
                "video_id": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "playable_url": "",
                "ad_texts": null,
                "music_id": "{{music_id}}",
                "ad_id": "{{ad_id}}",
                "optimization_event": null
            }
        ]
    }
}
(/code)
```

#### Destination as TikTok Instant Page & ad format as non-Spark Ads Single Video
Request
```xcodeblock
(code curl http)
curl --location  --request POST  'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{dvertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [{
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{identity_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "page_id":"{{page_id}}"
    }]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "viewability_vast_url": null,
                "ad_id": "{{ad_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "card_id": null,
                "vast_moat_enabled": false,
                "impression_tracking_url": null,
                "modify_time": "{{modify_time}}",
                "profile_image_url": "{{profile_image_url}}",
                "playable_url": "",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "fallback_type": "UNSET",
                "is_aco": false,
                "call_to_action": "SHOP_NOW",
                "image_ids": [
                    "{{image_id}}"
                ],
                "ad_format": "SINGLE_VIDEO",
                "app_name": "",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "page_id": {{page_id}},
                "is_new_structure": true,
                "landing_page_urls": null,
                "display_name": "{{display_name}}",
                "operation_status": "ENABLE",
                "deeplink": null,
                "call_to_action_id": null,
                "identity_type": "CUSTOMIZED_USER",
                "creative_authorized": false,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_texts": null,
                "identity_id": "{{identity_id}}",
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "landing_page_url": "",
                "music_id": null,
                "campaign_name": "{{campaign_name}}",
                "campaign_id": "{{campaign_id}}",
                "brand_safety_vast_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "video_id": "{{video_id}}",
                "ad_name": "{{ad_name}}",
                "tracking_pixel_id": 0,
                "viewability_postbid_partner": "UNSET",
                "creative_type": "CUSTOM_INSTANT_PAGE",
                "optimization_event": "CONSULT",
                "ad_text": "{{ad_text}}",
                "click_tracking_url": null
            }
        ]
    }
}
(/code)
```


#### Destination as Instant Form (for Lead generation objective with Optimization location as Instant Form) & ad format as non-Spark Ads Single Video
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
        "ad_name": "{{ad_name}}",
        "identity_id":"{{identity_id}}",
        "identity_type":"CUSTOMIZED_USER",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SHOP_NOW",
        "page_id":{{page_id}}
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "vast_moat_enabled": false,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "is_aco": false,
                "ad_text": "{{ad_text}}",
                "profile_image_url": "{{profile_image_url}}",
                "brand_safety_vast_url": null,
                "tracking_pixel_id": 0,
                "call_to_action_id": null,
                "ad_format": "SINGLE_VIDEO",
                "ad_name": "{{ad_name}}",
                "creative_authorized": false,
                "video_id": "{{video_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "call_to_action": "SHOP_NOW",
                "carousel_image_labels": null,
                "is_new_structure": true,
                "creative_type": "LEAD_ADS",
                "campaign_name": "{{campaign_name}}",
                "viewability_postbid_partner": "UNSET",
                "card_id": null,
                "landing_page_url": null,
                "ad_texts": null,
                "music_id": null,
                "click_tracking_url": null,
                "impression_tracking_url": null,
                "campaign_id": "{{campaign_id}}",
                "landing_page_urls": null,
                "display_name": "{{display_name}}",
                "modify_time": "{{modify_time}}",
                "adgroup_name": "{{adgroup_name}}",
                "create_time": "{{create_time}}",
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "app_name": "",
                "adgroup_id": "{{adgroup_id}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "viewability_vast_url": null,
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "advertiser_id": "{{advertiser_id}}",
                "playable_url": "",
                "operation_status": "ENABLE",
                "optimization_event": "FORM",
                "page_id": {{page_id}}
            }
        ]
    }
}
(/code)
```

# Next steps

## Use Search Ads Campaign capabilities in Reporting API
Once you have created Search Ads Campaigns, you can use [Reporting API](https://business-api.tiktok.com/portal/docs?id=1740302665828417/) to retrieve data segmented by Search Keyword using the `search_keyword` dimension or data segmented by Match Type using the `match_type` dimension in [basic reports](https://business-api.tiktok.com/portal/docs?id=1751443956638721).

# Related docs
- [Negative Keywords](https://business-api.tiktok.com/portal/docs?id=1775103049049090)
- [Basic reports](https://business-api.tiktok.com/portal/docs?id=1738864915188737)
