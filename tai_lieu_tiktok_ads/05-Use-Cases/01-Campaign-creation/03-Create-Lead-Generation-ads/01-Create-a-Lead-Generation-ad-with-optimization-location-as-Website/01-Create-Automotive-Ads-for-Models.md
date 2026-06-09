---
title: "Create Automotive Ads for Models"
doc_id: 1829635758164994
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as Website / Create Automotive Ads for Models"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Automotive Ads for Models

This article walks you through the steps to create Automotive Ads for Models.

# Introduction
Automotive Ads for Models leverage the model and offer information stored in your Auto-Model catalog to showcase the most relevant vehicles to potential buyers. Buyers can interact with the ads to enter their zip code, then view and filter available models on an Instant Product Page based on attributes such as make, model, price, year, and more. This gives buyers a streamlined way to browse and identify vehicles that meet their needs directly from your ad.

**You can use Campaign Management API to create Automotive Ads for Models, and this helps you streamline your ad creation experience, and elevate operational efficiency and scalability.**


# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details. 
  - To create Automotive Ads for Models, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.  
- Creating Automotive Ads for Models requires setting up an Auto-Model catalog in Business Center first.
<br>To set up the catalog:
  1. Create an Auto-Model catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961). 
  2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
  3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889). 
 <br> Pass in the `feed_log_id` obtained from Step 2. If the field `warn_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
  4. (Optional) Create an Auto-Model product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
  <br> If you want to have products selected from a product set at the ad level, creating a product set is necessary. Otherwise, you can skip this step.
  5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505). 
  <br> You can also choose `advertiser_role` that you want to assign to the members invited.
  6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121). 
  <br> Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameter</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>Lead generation</td>
    <td><code>objective_type</code></td>
    <td><code>LEAD_GENERATION</code></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362" target="_blank">/campaign/spc/create/</a> to create a Smart+ Automotive Campaign for Models.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Use catalog</td>
    <td>Enabled</td>
    <td><code>catalog_enabled</code></td>
    <td><code>true</code></td>
  </tr>
</tbody>
</table>


### Example
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "catalog_enabled": true
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "catalog_enabled": true,
        "deep_bid_type": null,
        "campaign_name": "{{campaign_name}}",
        "is_search_campaign": false,
        "is_smart_performance_campaign": false,
        "operation_status": "ENABLE",
        "modify_time": "{{modify_time}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "create_time": "{{create_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "objective": "LANDING_PAGE",
        "roas_bid": 0.0,
        "advertiser_id": "{{advertiser_id}}",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_TOTAL",
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "objective_type": "LEAD_GENERATION"
    }
}
(/code)
```
## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameter</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td  rowspan="2">Product source</td>
	<td  rowspan="2">An Auto-Model catalog with <b>at least one approved product</b></td>
    <td><code>product_source</code></td>
    <td><code>CATALOG</code></td>
  </tr>
  <tr>
    <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
    <td>Pass valid values.<br><br>The Auto-Model catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1. <br><br>To learn about how to create an Auto-Model catalog, see <a href="#item-link-Prerequisites">Prerequisites</a>.</td>
  </tr>
  <tr>
    <td  rowspan="3">Optimization location <br>(Promotion type)</td>
	<td  rowspan="3">Website<ul><li> If you want to use the optimization goal Conversions, specify a TikTok Pixel and the corresponding optimization event.</li><li> If you want to use the optimization goal Click, do not specify TikTok Pixel and the corresponding optimization event.</li></ul></td>
    <td><code>promotion_type</code></td>
    <td><code>LEAD_GENERATION</code></td>
  </tr>
  <tr>
    <td><code>promotion_target_type</code></td>
    <td><code>EXTERNAL_WEBSITE</code></td>
  </tr>
  <tr>
    <td><code>pixel_id</code><br><code>optimization_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>, pass in <code>pixel_id</code> and <code>optimization_event</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, do not pass in <code>pixel_id</code> and <code>optimization_event</code>.</li></ul><br>To obtain Pixels with their associated optimization events, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
  </tr>
    <tr>
    <td> Deep funnel optimization<br>(Optional)</td>
    <td>Enabled or disabled<br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
    <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
    <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Placement</td>
    <td rowspan="2"><b>Select Placement</b> with TikTok placement only</td>
    <td><code>placement_type</code></td>
    <td><code>PLACEMENT_TYPE_NORMAL</code></td>
  </tr>
  <tr>
    <td><code>placements</code></td>
    <td><code>["PLACEMENT_TIKTOK"]</code></td>
  </tr>
  <tr>
    <td>Include search results</td>
    <td>Disabled</td>
    <td><code>search_result_enabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Demographics - Location</td>
    <td>The location should match or be the subset of the targeting location of your catalog.</td>
    <td><code>location_ids</code></td>
    <td>IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.</td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Demographics - zip code targeting</td>
    <td>Supported when the targeting location of your catalog is in the United States.</td>
    <td><code>zipcode_ids</code></td>
    <td>Supported when the targeting location of <code>catalog_id</code> is in the United States.</td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Demographics - Age</td>
    <td>Audiences younger than 18 years old can't be targeted.</td>
    <td><code>age_groups</code></td>
    <td>Do not pass the value <code>AGE_13_17</code>.</td>
  </tr>
  <tr>
    <td rowspan="2">Content exclusions - Inventory filter</td>
    <td rowspan="2">Supported with allowlist</td>
    <td><code>brand_safety_type</code></td>
    <td>Pass a valid value (<code>EXPANDED_INVENTORY</code>, <code>STANDARD_INVENTORY</code>, or <code>LIMITED_INVENTORY</code>) or not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: TikTok inventory filter is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
  </tr>
  <tr>
    <td><code>brand_safety_partner</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions - Category exclusions</td>
    <td>Disabled</td>
    <td><code>category_exclusion_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions - Vertical sensitivity control</td>
    <td>Disabled</td>
    <td><code>vertical_sensitivity_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following options:<ul><li>Conversions</li><li>Click</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td>Any of the following values:<ul><li><code>CONVERT</code></li><li><code>CLICK</code></li></ul></td>
  </tr>
  <tr>
    <td rowspan="4">Attribution settings <br>(supported only if Optimization goal is Conversions)</td>
    <td>The supported Click-through windows are:<ul><li>1-day click</li><li>7-day click</li><li>14-day click</li><li>28-day click</li></ul></td>
    <td><code>click_attribution_window</code></td>
    <td>If <code>optimization_goal</code> is <code>CONVERT</code>, the supported values are:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
  </tr>
  <tr>
	<td>The supported View-through windows are:<ul><li>Off</li><li>1-day view</li><li>7-day view</li></ul></td>
    <td><code>view_attribution_window</code></td>
    <td>If <code>optimization_goal</code> is <code>CONVERT</code>, the supported values are:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
  </tr>
  <tr>
	<td>The supported Engaged view-through windows are:<ul><li>Off</li><li>1-day engaged view</li><li>7-day engaged view</li><li>14-day engaged view</li><li>28-day engaged view</li></ul></td>
    <td><code>engaged_view_attribution_window</code></td>
    <td>If <code>optimization_goal</code> is <code>CONVERT</code>, the supported values are:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
  </tr>
  <tr>
    <td>The supported Event count options are: <ul><li> Once</li><li>Every</li></ul></td>
    <td><code>attribution_event_count</code></td>
    <td>If optimization_goal is <code>CONVERT</code>, the supported values are:<ul><li><code>ONCE</code></li><li><code>EACH</code></li></ul></td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If Optimization goal is set to Conversions, set Billing event to oCPM.</li><li>If Optimization goal is set to Click, set Billing event to CPC.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is set to <code>CLICK</code>, specify <code>billing_event</code> as <code>CPC</code>.</li><li> If <code>optimization_goal</code> is set to <code>CONVERT</code>, specify <code>billing_event</code> as <code>OCPM</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid strategy <br>& Delivery type</td>
    <td><ul><li>If Bid strategy is set to Cost Cap, you can set Delivery type to Standard or Accelerated.</li><li>If Bid strategy is set to Maximum Delivery, you can only set Delivery type to Standard.</li></ul></td>
    <td><code>bid_type</code><br><code>pacing</code></td>
    <td><ul><li>If you specify <code>bid_type</code> as <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li><li>If you specify <code>bid_type</code> as <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Split test</td>
    <td>Disabled<br><br>Automotive Ad Groups for Models cannot be used in <a href="https://business-api.tiktok.com/portal/docs?id=1742666471475201" target="_blank">/split_test/create/</a> to create split tests.</td>
    <td>/</td>
    <td>/</td>
  </tr>
</tbody>
</table>


### Example
#### Optimization goal as Click
Request
```xcodeblock
(code curl http)
curl --location   --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "creative_material_mode": "CUSTOM",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_FAST"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ios14_quota_type": "UNOCCUPIED",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "age_groups": null,
        "bid_price": {{bid_price}},
        "inventory_filter_enabled": false,
        "smart_audience_enabled": null,
        "app_id": null,
        "next_day_retention": null,
        "category_id": "0",
        "comment_disabled": false,
        "excluded_audience_ids": [],
        "pacing": "PACING_MODE_FAST",
        "network_types": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "location_ids": [
            "6252001"
        ],
        "share_disabled": false,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "optimization_goal": "CLICK",
        "smart_interest_behavior_enabled": null,
        "schedule_infos": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "scheduled_budget": 0,
        "frequency_schedule": null,
        "audience_ids": [],
        "rf_estimated_cpr": null,
        "adgroup_app_profile_page_state": null,
        "deep_funnel_optimization_status": null,
        "budget": {{budget}},
        "is_hfss": false,
        "delivery_mode": null,
        "adgroup_name": "{{adgroup_name}}",
        "campaign_id": "{{campaign_id}}",
        "optimization_event": null,
        "promotion_type": "LEAD_GENERATION",
        "conversion_window": null,
        "interest_category_ids": [],
        "campaign_name": "{{campaign_name}}",
        "auto_targeting_enabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "keywords": null,
        "rf_purchased_type": null,
        "billing_event": "CPC",
        "purchased_reach": null,
        "deep_funnel_event_source_id": null,
        "frequency": null,
        "schedule_type": "SCHEDULE_START_END",
        "vbo_window": null,
        "deep_bid_type": null,
        "actions": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "modify_time": "{{modify_time}}",
        "deep_funnel_optimization_event": null,
        "device_price_ranges": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "catalog_id": "{{catalog_id}}",
        "languages": [],
        "adgroup_id": "{{adgroup_id}}",
        "app_download_url": null,
        "is_smart_performance_campaign": false,
        "purchased_impression": null,
        "category_exclusion_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "create_time": "{{create_time}}",
        "bid_display_mode": "CPMV",
        "interest_keyword_ids": [],
        "operation_status": "ENABLE",
        "search_result_enabled": false,
        "operating_systems": [],
        "brand_safety_partner": null,
        "isp_ids": [],
        "conversion_bid_price": 0,
        "is_new_structure": true,
        "feed_type": null,
        "gender": "GENDER_UNLIMITED",
        "bid_type": "BID_TYPE_CUSTOM",
        "app_type": null,
        "statistic_type": null,
        "secondary_optimization_event": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "pixel_id": null,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "deep_funnel_event_source": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "rf_estimated_frequency": null,
        "deep_cpa_bid": 0,
        "creative_material_mode": "CUSTOM",
        "skip_learning_phase": false,
        "video_download_disabled": false
    }
}
(/code)
```

#### Optimization goal as Conversions
Request
```xcodeblock
(code curl http)
curl --location   --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "{{optimization_event}}",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_FAST",
    "creative_material_mode": "CUSTOM"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "app_download_url": null,
        "category_exclusion_ids": [],
        "deep_funnel_event_source": null,
        "creative_material_mode": "CUSTOM",
        "next_day_retention": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "comment_disabled": false,
        "pixel_id": "{{pixel_id}}",
        "frequency_schedule": null,
        "feed_type": null,
        "conversion_window": null,
        "share_disabled": false,
        "frequency": null,
        "statistic_type": null,
        "category_id": "0",
        "deep_bid_type": null,
        "device_price_ranges": null,
        "rf_estimated_cpr": null,
        "deep_funnel_optimization_event": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "is_hfss": false,
        "skip_learning_phase": true,
        "secondary_optimization_event": null,
        "actions": [],
        "modify_time": "{{modify_time}}",
        "inventory_filter_enabled": false,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "adgroup_id": "{{adgroup_id}}",
        "campaign_name": "{{campaign_name}}",
        "app_type": null,
        "isp_ids": [],
        "bid_display_mode": "CPMV",
        "conversion_bid_price": "{{conversion_bid_price}}",
        "create_time": "{{create_time}}",
        "pacing": "PACING_MODE_FAST",
        "promotion_type": "LEAD_GENERATION",
        "interest_category_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "operating_systems": [],
        "vbo_window": null,
        "is_new_structure": true,
        "scheduled_budget": 0,
        "auto_targeting_enabled": false,
        "is_smart_performance_campaign": false,
        "click_attribution_window": "SEVEN_DAYS",
        "delivery_mode": null,
        "excluded_audience_ids": [],
        "deep_cpa_bid": 0,
        "audience_ids": [],
        "operation_status": "ENABLE",
        "catalog_id": "{{catalog_id}}",
        "schedule_type": "SCHEDULE_START_END",
        "schedule_end_time": "{{schedule_end_time}}",
        "bid_price": "{{bid_price}}",
        "app_id": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "purchased_impression": null,
        "rf_estimated_frequency": null,
        "location_ids": [
            "6252001"
        ],
        "interest_keyword_ids": [],
        "optimization_goal": "CONVERT",
        "billing_event": "OCPM",
        "video_download_disabled": false,
        "adgroup_app_profile_page_state": null,
        "deep_funnel_optimization_status": null,
        "ios14_quota_type": "UNOCCUPIED",
        "smart_interest_behavior_enabled": null,
        "optimization_event": "ON_WEB_DETAIL",
        "deep_funnel_event_source_id": null,
        "campaign_id": "{{campaign_id}}",
        "age_groups": null,
        "schedule_infos": null,
        "gender": "GENDER_UNLIMITED",
        "languages": [],
        "attribution_event_count": "EVERY",
        "brand_safety_partner": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "purchased_reach": null,
        "smart_audience_enabled": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "budget": "{{budget}}",
        "keywords": null,
        "view_attribution_window": "ONE_DAY",
        "rf_purchased_type": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "network_types": [],
        "search_result_enabled": false
    }
}
(/code)
```


## 3. Create an ad
Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). Note that the following requirements must be met.

### Ad format as Single Video
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank">/ad/aco/create/</a> to create Smart Creative Automotive Ads for Models.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="2">Identity</td>
    <td rowspan="2">Any of the following options:<br><ul><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
    <td><code>identity_type</code></td>
    <td>Any of the following values:<ul><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
    <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>vehicle_ids</code></td>
    <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>product_set_id</code> and <code>vehicle_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass <code>product_set_id</code>.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>vehicle_ids</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Ad format</td>
    <td rowspan="2">Single video</td>
    <td><code>ad_format</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
  <tr>
    <td><code>dynamic_format</code><br><code>vertical_video_strategy</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="3">Ad creative</td>
    <td rowspan="3"><ul><li>For Spark Ads PUSH with TikTok User identity, specify a video and a cover image.</li><li>For TikTok Account User in Business Center identity, Authorized User identity, or Spark Ads PULL with TikTok User identity, specify a TikTok post.</li></ul></td>
    <td><code>video_id</code><br><code>image_ids</code><br><code>ad_text</code><br><code>tiktok_item_id</code></td>
    <td><ul><li>If <code>identity_type</code> is <code>TT_USER</code>, you can create the ad through Spark Ads PULL or Spark Ads PUSH:<ul><li>Spark Ads PUSH: Specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>). Do not pass <code>tiktok_item_id</code>.</li><li>Spark Ads PULL: Specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, and <code>ad_text</code>.</li></ul>To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.<br>The recommended value for <code>ad_text</code> is <code>CHECK_AVAILABLILITY</code> (check availability).</li><li>If <code>identity_type</code> is <code>AUTH_CODE</code> or <code>BC_AUTH_TT</code>, specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, and <code>ad_text</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>music_id</code><br><code>shopping_ads_video_package_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>creative_type</code></td>
    <td>(Recommended) Not passed<ul><li>If Destination is Vehicle Inventory Page (<code>landing_page_url</code> is not passed), the value of this field will default to <code>AUTO_INVENTORY_INSTANT_PAGE</code>.</li><li>If Destination is Website URL (<code>landing_page_url</code> is passed), the value of this field will be null. If you manually specify a value that is not null, the value will be ignored.</li></ul></td>
  </tr>
  <tr>
    <td>Interactive add-on</td>
    <td>Inventory Card.<br><br>For Automotive Ads for Models with Single Video format, auto-generated Vehicle Cards will be shown after two seconds of video play. If the audience continues swiping through the Vehicle Cards, the Inventory Card will be displayed at the end.<br><br>The Inventory Card includes:<ul><li>A customized image</li><li>A customized description</li><li> CTA button "Search Inventory", which directs the audience to an auto-generated Instant Product Page</li></ul></td>
    <td><code>card_id</code></td>
    <td>Specify the ID of an Inventory Card portfolio.<br>To get the Inventory Card portfolio ID (<code>creative_portfolio_id</code>), see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Inventory%20Card" target="_blank">Cards</a>.<br><br>The Inventory Card includes a CTA button and the following attributes of the Inventory Card:<ul><li><code>image_id</code></li><li><code>description</code></li></ul>To get the details of an Inventory Card, use <a href="https://business-api.tiktok.com/portal/docs?id=1766324010279938" target="_blank">/creative/portfolio/list/</a>.</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Enabled.<br>The recommended setting is "Check availability".</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value.<br>The recommended value is <code>CHECK_AVAILABLILITY</code>.<br>To get other supported values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank">Enumerations - Call-to-action</a>.</td>
  </tr>
  <tr>
    <td rowspan="2">Destination</td>
    <td rowspan="2">Any of the following options:<ul><li>Website URL: Your own URL to direct users to browse inventory on your website.</li><li>Vehicle Inventory Page: The Instant Product Page to access your full Vehicle Catalog.<ul><li>If Destination is Vehicle Inventory Page, an Instant Product Page will be automatically generated. </li></ul></li></ul>
    <td><code>landing_page_url</code></td>
    <td><ul><li>To set Destination as Website URL, specify your own URL via <code>landing_page_url</code>.</li><li>To set Destination as Vehicle Inventory Page, do not pass <code>landing_page_url</code>. An auto-generated Instant Product Page will be returned through the response parameter <code>page_id</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>page_id</code><br><code>deeplink</code><br><code>deeplink_type</code><br><code>shopping_ads_deeplink_type</code><br><code>shopping_ads_fallback_type</code><br><code>fallback_type</code><br><code>dynamic_destination</code><br><code>instant_product_page_used</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Build URL parameters <br>(Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>utm_params</code></td>
    <td>Pass a valid value or do not pass the field.</td>
  </tr>
</tbody>
</table>



#### Example

##### Spark Ads PUSH
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
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_ids}}"],
            "ad_text": "{{ad_text}}",
            "call_to_action": "CHECK_AVAILABILITY",
            "card_id": "{{card_id}}"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
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
                "create_time": "{{create_time}}",
                "image_ids": [
                    "{{image_ids}}"
                ],
                "campaign_name": "{{campaign_name}}",
                "app_name": "{{app_name}}",
                "card_id": "{{card_id}}",
                "creative_authorized": false,
                "ad_text": "{{ad_text}}",
                "profile_image_url": "",
                "carousel_image_labels": null,
                "viewability_postbid_partner": "UNSET",
                "dark_post_status": "OFF",
                "campaign_id": "{{campaign_id}}",
                "viewability_vast_url": null,
                "landing_page_urls": null,
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "brand_safety_vast_url": null,
                "playable_url": "",
                "ad_name": "{{ad_name}}",
                "operation_status": "ENABLE",
                "is_new_structure": true,
                "ad_texts": null,
                "impression_tracking_url": null,
                "product_specific_type": "ALL",
                "identity_type": "TT_USER",
                "display_name": "{{display_name}}",
                "branded_content_disabled": false,
                "ad_ref_pixel_id": 7031154132622917634,
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "call_to_action_id": null,
                "optimization_event": "ON_WEB_DETAIL",
                "avatar_icon_web_uri": "",
                "music_id": null,
                "landing_page_url": null,
                "is_aco": false,
                "creative_type": "AUTO_INVENTORY_INSTANT_PAGE",
                "page_id": "{{page_id}}",
                "video_id": "{{video_id}}",
                "call_to_action": "CHECK_AVAILABILITY",
                "modify_time": "{{modify_time}}",
                "adgroup_id": "{{adgroup_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "click_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "vast_moat_enabled": false,
                "adgroup_name": "{{adgroup_name}}",
                "deeplink": null
            }
        ]
    }
}
(/code)
```

##### Spark Ads PULL
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
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "tiktok_item_id": "{{tiktok_item_id}}",
            "call_to_action": "CHECK_AVAILABILITY",
            "card_id": "{{card_id}}"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
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
                "product_specific_type": "ALL",
                "vast_moat_enabled": false,
                "promotional_music_disabled": true,
                "profile_image_url": "",
                "operation_status": "ENABLE",
                "video_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "campaign_id": "{{campaign_id}}",
                "avatar_icon_web_uri": "",
                "is_aco": false,
                "campaign_name": "{{campaign_name}}",
                "creative_authorized": false,
                "creative_type": "AUTO_INVENTORY_INSTANT_PAGE",
                "ad_name": "{{ad_name}}",
                "click_tracking_url": null,
                "landing_page_urls": null,
                "ad_texts": null,
                "identity_id": "{{identity_id}}",
                "identity_type": "TT_USER",
                "music_id": null,
                "ad_format": "SINGLE_VIDEO",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "ad_text": "{{ad_text}}",
                "page_id": "{{page_id}}",
                "deeplink": null,
                "display_name": "{{display_name}}",
                "adgroup_id": "{{adgroup_id}}",
                "landing_page_url": null,
                "is_new_structure": true,
                "app_name": "{{app_name}}",
                "optimization_event": "ON_WEB_DETAIL",
                "viewability_postbid_partner": "UNSET",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "brand_safety_vast_url": null,
                "carousel_image_labels": null,
                "playable_url": "",
                "impression_tracking_url": null,
                "modify_time": "{{modify_time}}",
                "create_time": "{{create_time}}",
                "call_to_action_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "viewability_vast_url": null,
                "image_ids": [],
                "card_id": "{{card_id}}",
                "call_to_action": "CHECK_AVAILABILITY",
                "ad_id": "{{ad_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"
            }
        ]
    }
}
(/code)
```

### Ad format as Catalog Carousel
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
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank">/ad/aco/create/</a> to create Smart Creative Automotive Ads for Models.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="2">Identity</td>
    <td rowspan="2">Custom User (custom identity)</td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value</td>
  </tr>
  <tr>
    <td rowspan="2">Ad format</td>
    <td rowspan="2">Catalog Carousel</td>
    <td><code>ad_format</code></td>
    <td><code>CATALOG_CAROUSEL</code></td>
  </tr>
  <tr>
    <td><code>vertical_video_strategy</code><br><code>dynamic_format</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
    <td rowspan="2"><code>catalog_id</code><br><code>product_specific_type</code><br><code>product_set_id</code><br><code>vehicle_ids</code></td>
    <td rowspan="2"><ul><li>Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Carousel Ads. You can specify the scope of product images to choose from using the <code>product_specific_type</code> parameter:<ul><li>To have the Carousel images dynamically chosen from all products in the catalog, set <code>product_specific_type</code> to <code>ALL</code>, and do not pass <code>product_set_id</code> and <code>vehicle_ids</code>. The catalog (<code>catalog_id</code>) needs to include at least <b>one</b> available product.</li><li>To have the Carousel images dynamically chosen from products in a product set, set <code>product_specific_type</code> to <code>PRODUCT_SET</code>, pass <code>product_set_id</code>, and do not pass <code>vehicle_ids</code>. The specified <code>product_set_id</code> needs to include at least <b>one</b> available product.</li><li>To have the images dynamically chosen from a customized product range consisting of up to 20 products, set <code>product_specific_type</code> to <code>CUSTOMIZED_PRODUCTS</code>, pass <code>vehicle_ids</code>, and do not pass <code>product_set_id</code>. The specified <code>vehicle_ids</code> needs to include at least <b>one</b> available product.</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Ad creative - images</td>
    <td rowspan="3">N/A<br>The first image (main image) of each selected product in your catalog will be used.</td>
  </tr>
  <tr>
    <td><code>carousel_image_index</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>creative_type</code></td>
    <td>(Recommended) Not passed<ul><li>If Destination is Vehicle Inventory Page (<code>landing_page_url</code> is not passed), the value of this field will default to <code>AUTO_INVENTORY_INSTANT_PAGE</code>.</li><li>If Destination is Website URL (<code>landing_page_url</code> is passed), the value of this field will be null. If you manually specify a value that is not null, the value will be ignored.</li></ul></td>
  </tr>
  <tr>
    <td>End card</td>
    <td>Select the last photo to appear in the carousel to encourage people to browse your full inventory.</td>
    <td><code>image_ids</code></td>
    <td>Specify one end card through <code>image_ids</code>. The end card will be displayed at the end of the carousel. <br>You can get image IDs by using <a href="https://ads.tiktok.com/marketing_api/docs?id=1739067433456642" target="_blank">/file/image/ad/upload/</a> and <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a>. <br>The image should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: below 50 MB.</li></ul></li><li>The value of the parameter <code>is_carousel_usable</code> returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a> for the image is <code>true</code>.</td>
  </tr>
  <tr>
    <td>Call to action for end card</td>
    <td>Any of the following options:<ul><li>Search inventory (Recommended)</li><li>Learn more</li><li>Shop now</li><li>Sign up</li><li>Contact us</li><li>Book now</li><li>Read more</li><li>View now</li><li>Order now</li></ul></td>
    <td><code>end_card_cta</code></td>
    <td>Any of the following values:<ul><li><code>SEARCH_INVENTORY</code> (Recommended)</li><li><code>LEARN_MORE</code></li><li><code>SHOP_NOW</code></li><li><code>SIGN_UP</code></li><li><code>CONTACT_US</code></li><li><code>BOOK_NOW</code></li><li><code>READ_MORE</code></li><li><code>VIEW_MORE</code></li><li><code>ORDER_NOW</code></li></ul></td>
  </tr>
  <tr>
    <td>Ad creative - Music</td>
    <td>A piece of music supported in the Carousel Ad</td>
    <td><code>music_id</code></td>
    <td>Pass one music ID that can be used in Carousel Ads. To obtain a valid music ID, you can follow any of the following methods:<ul><li>Filter the pieces of music for Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Carousel Ads to an ad account using either of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, specify <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Ad creative - Video</td>
    <td>Disabled</td>
    <td><code>video_id</code><br><code>shopping_ads_video_package_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad creative - Text</td>
    <td>Enabled</td>
    <td><code>ad_text</code></td>
    <td>Pass a valid value.<br> The recommended value is the new enum value <code>CHECK_AVAILABLILITY</code> (check availability).</td>
  </tr>
  <tr>
    <td>Interactive add-on</td>
    <td>Disabled</td>
    <td><code>card_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Enabled. <br>The recommended setting is "Check availability".</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value. <br>The recommended value is <code>CHECK_AVAILABLILITY</code>.</td>
  </tr>
  <tr>
	<td>Disclaimer</td>
	<td>Any of the following options:<ul><li>Emission disclaimer</li><li>Discount or offer disclaimer</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Before using any type of disclaimer, ensure that you have specified the necessary disclaimer details for each vehicle in the Auto-Model catalog.<ul><li>To use the emission disclaimer, ensure that you have provided the following details for each vehicle:<ul><li>emission disclaimer</li><li>emission disclaimer URL</li><li>emission overlay disclaimer</li><li>emission image</li></ul></li><li>To use the discount or offer disclaimer, ensure that you have provided the following details for each vehicle:<ul><li>offer disclaimer</li><li>offer disclaimer URL</li></ul></li></ul></td>
	<td><code>auto_disclaimer_types</code></td>
	<td>Any of the following values:<ul><li><code>EMISSION</code></li><li><code>DISCOUNT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Before using any type of disclaimer, ensure that you have specified the necessary disclaimer details for each vehicle in the Auto-Model catalog.<ul><li>To use the emission disclaimer, ensure that you have provided the following details for each vehicle:<ul><li><code>emission_disclaimer</code></li><li><code>emission_disclaimer_url</code></li><li><code>emission_overlay_disclaimer</code></li><li><code>emission_image_link</code></li></ul></li><li>To use the discount or offer disclaimer, ensure that you have provided the following details for each vehicle:<ul><li><code>offer_disclaimer</code></li><li><code>offer_disclaimer_url </code></li></ul></li></ul>To get the attributes of vehicles in an Auto-Model catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.<br>To update the attributes of vehicles in an Auto-Model catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546" target="_blank">/catalog/product/update/</a>.</td>
</tr>
  <tr>
    <td rowspan="2">Destination</td>
    <td rowspan="2">Any of the following options:<ul><li>Website URL: Your own URL to direct users to browse inventory on your website.</li><li>Vehicle Inventory Page: The Instant Product Page to access your full Vehicle Catalog.<ul><li>If the Destination is Vehicle Inventory Page, an Instant Product Page will be automatically generated. </li></ul></li></ul></td>
    <td><code>landing_page_url</code></td>
    <td><ul><li>To set Destination as Website URL, specify your own URL via <code>landing_page_url</code>. </li><li>To set Destination as Vehicle Inventory Page, do not pass <code>landing_page_url</code>. An auto-generated Instant Product Page will be returned through the response parameter <code>page_id</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>page_id</code><br><code>deeplink</code><br><code>deeplink_type</code><br><code>shopping_ads_deeplink_type</code><br><code>shopping_ads_fallback_type</code><br><code>fallback_type</code><br><code>dynamic_destination</code><br><code>instant_product_page_used</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Build URL parameters <br>(Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>utm_params</code></td>
    <td>Pass a valid value or do not pass the field.</td>
  </tr>
</tbody>
</table>



#### Example
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
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "ad_format": "CATALOG_CAROUSEL",
            "product_specific_type": "ALL",
            "image_ids": ["{{image_ids}}"],
            "end_card_cta": "SEARCH_INVENTORY",
            "music_id": "{{music_id}}",
            "ad_text": "{{ad_text}}",
            "call_to_action": "CHECK_AVAILABLILITY",
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
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
                "carousel_image_labels": null,
                "music_id": "{{music_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "ad_name": "{{ad_name}}",
                "brand_safety_vast_url": null,
                "optimization_event": "ON_WEB_DETAIL",
                "deeplink": null,
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "operation_status": "ENABLE",
                "display_name": "{{display_name}}",
                "impression_tracking_url": null,
                "ad_texts": null,
                "app_name": "",
                "product_specific_type": "ALL",
                "page_id": null,
                "creative_type": null,
                "card_id": null,
                "viewability_vast_url": null,
                "video_id": null,
                "is_new_structure": true,
                "adgroup_id": "{{adgroup_id}}",
                "profile_image_url": "{{profile_image_url}}",
                "end_card_cta": "SEARCH_INVENTORY",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "is_aco": false,
                "playable_url": "",
                "create_time": "{{create_time}}",
                "click_tracking_url": null,
                "ad_id": "{{ad_id}}",
                "ad_text": "{{ad_text}}",
                "advertiser_id": "{{advertiser_id}}",
                "vast_moat_enabled": false,
                "campaign_id": "{{campaign_id}}",
                "ad_format": "CATALOG_CAROUSEL",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "campaign_name": "{{campaign_name}}",
                "landing_page_urls": null,
                "viewability_postbid_partner": "UNSET",
                "ad_ref_pixel_id": 7031154132622917634,
                "call_to_action": "CHECK_AVAILABLILITY",
                "image_ids": [
                    "{{image_ids}}"
                ],
                "call_to_action_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "creative_authorized": false
            }
        ]
    }
}
(/code)
```
