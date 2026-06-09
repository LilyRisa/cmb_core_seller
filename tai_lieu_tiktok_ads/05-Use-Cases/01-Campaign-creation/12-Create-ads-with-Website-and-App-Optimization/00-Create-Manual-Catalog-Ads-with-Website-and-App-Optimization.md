---
title: "Create Manual Catalog Ads with Website and App Optimization"
doc_id: 1839882947475458
path: "Use Cases / Campaign creation / Create ads with Website and App Optimization / Create Manual Catalog Ads with Website and App Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Manual Catalog Ads with Website and App Optimization

This article walks you through the steps to create Manual Catalog Ads with Website and App Optimization.

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.
<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td  rowspan="2">Advertising objective</td>
      <td  rowspan="2">Sales</td>
      <td><code>virtual_objective_type</code></td>
      <td><code>SALES</code></td>
    </tr>
    <tr>
      <td><code>objective_type</code></td>
      <td><code>PRODUCT_SALES</code></td>
    </tr>
    <tr>
      <td>Sales destination</td>
      <td>Website and app</td>
      <td><code>sales_destination</code></td>
      <td><code>WEB_AND_APP</code></td>
    </tr>
    <tr>
      <td>Use catalog</td>
      <td><b>Enabled with an E-commerce catalog</b></td>
      <td><code>catalog_enabled</code></td>
      <td><code>true</code> or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>objective_type</code> is <code>PRODUCT_SALES</code> and <code>sales_destination</code> is <code>WEB_AND_APP</code>, this field will default to <code>true</code>.</td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories<br>(Optional)</td>
      <td>Disabled or enabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
      <td><code>special_industries</code></td>
      <td>Not specified or specify a valid value</td>
    </tr>
    <tr>
      <td>Campaign budget optimization<br>(Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>budget_optimize_on</code></td>
      <td><code>false</code> (or not specified) or <code>true</code></td>
    </tr>
    <tr>
      <td rowspan="2">Set campaign budget<br>(Optional)</td>
      <td rowspan="2">Disabled or enabled with any of the following budget types:<ul><li>Daily</li><li>Lifetime</li></ul></td>
      <td><code>budget_mode</code></td>
      <td>Any of the following values:<ul><li><code>BUDGET_MODE_INFINITE</code></li><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li><code>BUDGET_MODE_DAY</code></li></ul></td>
    </tr>
    <tr>
      <td><code>budget</code></td>
      <td>Specify a valid value when <code>budget_mode</code> is any of the following values:<ul><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li><code>BUDGET_MODE_DAY</code></li></ul></td>
    </tr>
    <tr>
      <td>Use RTA to automatically select products<br>(when RTA is enabled)<br>(Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>rta_product_selection_enabled</code></td>
      <td><code>false</code> (or not specified) or <code>true</code></td>
    </tr>
    <tr>
	   <td>Use RTA BID<br>(when RTA is enabled)<br>(Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>rta_bid_enabled</code></td>
      <td><code>false</code> (or not specified) or <code>true</code></td>
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
    "virtual_objective_type": "SALES",
    "objective_type": "PRODUCT_SALES",
    "sales_destination": "WEB_AND_APP",
    "catalog_enabled": true,
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
        "rta_product_selection_enabled": false,
        "roas_bid": 0,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rta_bid_enabled": false,
        "campaign_automation_type": "MANUAL",
        "campaign_app_profile_page_state": "UNSET",
        "is_smart_performance_campaign": false,
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "objective_type": "PRODUCT_SALES",
        "virtual_objective_type": "SALES",
        "sales_destination": "WEB_AND_APP",
        "rta_id": null,
        "is_new_structure": true,
        "operation_status": "ENABLE",
        "is_advanced_dedicated_campaign": false,
        "campaign_id": "{{campaign_id}}",
        "budget": {{budget}},
        "disable_skan_campaign": null,
        "deep_bid_type": null,
        "campaign_name": "{{campaign_name}}",
        "campaign_product_source": "CATALOG",
        "create_time": "{{create_time}}",
        "advertiser_id": "{{advertiser_id}}",
        "is_search_campaign": false,
        "catalog_enabled": true,
        "objective": "LANDING_PAGE",
        "modify_time": "{{modify_time}}",
        "campaign_type": "REGULAR_CAMPAIGN"
    }
}
(/code)
```
## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad group name</td>
      <td>Specify a valid name</td>
      <td><code>adgroup_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td rowspan="2">Product source details<br>&#183; Catalog</td>
      <td rowspan="2">Specify an <b>E-commerce catalog with at least one approved product</b></td>
      <td><code>product_source</code></td>
      <td><code>CATALOG</code></td>
    </tr>
    <tr>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Pass valid values.<br><br>The catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1.<br><br><ul><li>To obtain E-commerce catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>. For E-commerce catalogs, the <code>catalog_type</code> should be <code>ECOM</code>.</li><li>To create an E-commerce catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740306481704961" target="_blank">/catalog/create/</a>.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization location<br>&#183; Web data connection</td>
      <td rowspan="2">Specify a valid Pixel</td>
      <td><code>pixel_id</code></td>
      <td>Specify a valid value.<br><br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
    </tr>
    <tr>
      <td><code>promotion_type</code></td>
      <td><code>WEBSITE</code></td>
    </tr>
    <tr>
      <td>Optimization location<br>&#183; iOS & App</td>
      <td>Specify any of the following:<ul><li>an Android app</li><li>an iOS app</li><li>an Android app and an iOS app</li></ul></td>
      <td><code>app_config</code></td>
      <td>Specify any of the following:<ul><li>App ID of an Android app</li><li>App ID of iOS app</li><li>App ID of an Android app and that of an iOS app</li></ul><br>To obtain the list of App IDs within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a> and check the returned <code>app_id</code>.</td>
    </tr>
    <tr>
      <td>Optimization location<br>&#183; Optimization event</td>
      <td>Specify a valid event</td>
      <td><code>optimization_event</code></td>
      <td>Specify a valid value.<br><br>To learn about the supported event types, see <a href="#item-link-List of values for optimization_event for Manual Catalog Ads">List of values for <code>optimization_event</code> for Manual Catalog Ads</a>.<br><br>You need to ensure the specified optimization event is active for both the Pixel and the app or apps.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li><li>To obtain the active optimization events for an app, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Placement</td>
      <td>Any of the following options:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with any of the following placements:<ul><li>TikTok</li><li>Pangle</li><li>Global App Bundle</li></ul></li></ul></td>
      <td><code>placement_type</code><br><code>placements</code></td>
      <td>Any of the following settings:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and include any of the following placements in the value of <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>User comment<br>(Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>comment_disabled</code></td>
      <td><code>true</code> or <code>false</code></td>
    </tr>
    <tr>
      <td>Allow video download<br>(Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>video_download_disabled</code></td>
      <td><code>true</code> or <code>false</code></td>
    </tr>
    <tr>
      <td>Pangle block list<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>blocked_pangle_app_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Audience</td>
      <td>Any of the following options:<ul><li>Find prospective customers: Find prospective customers, including those who have not interacted with your products.</li><li>Retarget audience: Promote products to people who have already interacted with them.<ul><li>If you select Retarget audience, ensure that an event source has been connected to the catalog. This will enable you to precisely target users who have interacted with your products.</li></ul></li></ul></td>
      <td><code>shopping_ads_retargeting_type</code><br><code>shopping_ads_retargeting_actions_days</code><br><code>included_custom_actions</code><br><code>excluded_custom_actions</code><br><code>shopping_ads_retargeting_custom_audience_relation</code></td>
      <td>Pass valid values<ul><li>To find prospective customers, set <code>shopping_ads_retargeting_type</code> to <code>OFF</code>.</li><li>To retarget audience, set <code>shopping_ads_retargeting_type</code> to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code>, and pass other related parameters. You also need to ensure an event source has been bound to the catalog (<code>catalog_id</code>). Otherwise, an error will occur.<ul><li>To check if an event source has been bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740492531343362" target="_blank">/catalog/eventsource_bind/get/</a>.</li><li>To bind an event source to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740492491200513" target="_blank">/catalog/eventsource/bind/</a>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Audience<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Include (Optional)<br>(for Find prospective customers mode)</td>
      <td>Enabled or disabled</td>
      <td><code>audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Audience<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Exclude (Optional)<br>(for Find prospective customers mode)</td>
      <td>Enabled or disabled</td>
      <td><code>exclude_audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Demographics<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Location</td>
      <td>The location should match or be the subset of the targeting location of your catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br><br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Demographics<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Age (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>age_groups</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Demographics<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Gender (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>gender</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Demographics<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Languages (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Interests & behaviors<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Interests (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Interests & behaviors<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Purchase intention (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>purchase_intention_keyword_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Interests & behaviors<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Video interactions<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Creator interactions<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Hashtag interactions<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>actions</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Operating system (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>operating_systems</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; OS versions (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>min_android_version</code> or <code>min_ios_version</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Device model (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_model_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Connection type (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>network_types</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Carriers (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>carrier_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Internet service provider (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>isp_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Device<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; Device price (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_price_ranges</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Content exclusions<br>&#183; Inventory filter (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>brand_safety_type</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Budget and schedule<br>&#183; Budget mode & budget</td>
      <td>Specify a valid budget</td>
      <td><code>budget_mode</code><br><code>budget</code></td>
      <td>Specify valid values</td>
    </tr>
    <tr>
      <td>Budget and schedule<br>&#183; Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td>Any of the following configurations:<ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>&#183; Dayparting</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Bidding and optimization<br>&#183; Optimization goal</td>
      <td>Conversion</td>
      <td><code>optimization_goal</code></td>
      <td><code>CONVERT</code></td>
    </tr>
    <tr>
      <td>Bidding and optimization<br>&#183; Bid Strategy</td>
      <td>Any of the following types:<ul><li>Maximum Delivery</li><li>Cost Cap (or Target CPA)</li></ul></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul></td>
    </tr>
    <tr>
      <td>Bidding and optimization<br>&#183; Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><code>click_attribution_window</code><br><code>view_attribution_window</code><br><code>engaged_view_attribution_window</code><br><code>attribution_event_count</code></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Product%20Sales" target="_blank">Attribution window and event count - Product Sales</a>.</td>
    </tr>
    <tr>
      <td>Bidding and optimization<br>&#183; Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td>Bidding and optimization<br>&#183; Delivery type</td>
      <td><ul><li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li>If Bid Strategy is set to Maximum Delivery, you can only set Delivery Type to Standard.</li></ul></td>
      <td><code>pacing</code></td>
      <td><ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li><li>If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul></td>
    </tr>
  </tbody>
</table>

### List of values for `optimization_event` for Manual Catalog Ads

The following tables list the events supported for Manual Catalog Ads with Website and App Optimization.

``` xtable
| Event name{20%} | <code>optimization_event</code> value{25%} | Corresponding Pixel event<br>(<code>optimization_event</code>){20%} | Corresponding app event<br>(<code>optimization_event</code> or <code>secondary_optimization_event</code>){35%} |
|---|---|---|---|
| Add payment info | `ADD_BILLING` | `ADD_BILLING` | `ADD_PAYMENT_INFO` |
| Add to Cart | `ON_WEB_CART` | `ON_WEB_CART` | `IN_APP_CART` |
| Add to wishlist | `ON_WEB_ADD_TO_WISHLIST` | `ON_WEB_ADD_TO_WISHLIST` | `ADD_TO_WISHLIST` |
| Initiate Checkout/Checkout | `INITIATE_ORDER` | `INITIATE_ORDER` | `IN_APP_ORDER` |
| Purchase | `SHOPPING` | `SHOPPING` | `ACTIVE_PAY` |
| Search | `ON_WEB_SEARCH` | `ON_WEB_SEARCH` | `SEARCH` |
| Subscribe | `ON_WEB_SUBSCRIBE` | `ON_WEB_SUBSCRIBE` | `SUBSCRIBE` |
| View Content | `ON_WEB_DETAIL` | `ON_WEB_DETAIL` | `IN_APP_DETAIL_UV` |
```

### Example
#### Find prospective customers
Request
```xcodeblock
(code curl http)
url --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "pixel_id": "{{pixel_id}}",
    "promotion_type": "WEBSITE",
    "app_config": [
        {
            "app_id": "{{app_id}}"
        },
        {
            "app_id": "{{app_id}}"
        }
    ],
    "optimization_event": "ADD_BILLING",
    "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
    "shopping_ads_retargeting_type":"OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "location_ids": [
        "6252001"
    ],
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM"
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
        "age_groups": null,
        "vbo_window": null,
        "keywords": null,
        "statistic_type": null,
        "skip_learning_phase": true,
        "interest_keyword_ids": [],
        "excluded_audience_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "pacing": "PACING_MODE_FAST",
        "ios14_quota_type": "UNOCCUPIED",
        "deep_funnel_event_source_id": null,
        "campaign_name": "{{campaign_name}}",
        "languages": [],
        "budget": {{budget}},
        "adgroup_name": "{{adgroup_name}}",
        "adgroup_app_profile_page_state": null,
        "next_day_retention": null,
        "isp_ids": [],
        "network_types": [],
        "actions": [],
        "modify_time": "{{modify_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "billing_event": "OCPM",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_estimated_cpr": null,
        "deep_funnel_optimization_status": null,
        "purchased_reach": null,
        "rf_estimated_frequency": null,
        "click_attribution_window": "SEVEN_DAYS",
        "shopping_ads_retargeting_type": "OFF",
        "audience_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "app_config": [
            {
                "app_id": "{{app_id}}"
            },
            {
                "app_id": "{{app_id}}"
            }
        ],
        "conversion_window": null,
        "gender": "GENDER_UNLIMITED",
        "deep_funnel_optimization_event": null,
        "purchased_impression": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "category_exclusion_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "deep_cpa_bid": 0,
        "is_hfss": false,
        "location_ids": [
            "6252001"
        ],
        "promotion_type": "WEBSITE",
        "search_result_enabled": false,
        "device_price_ranges": null,
        "operation_status": "ENABLE",
        "inventory_filter_enabled": false,
        "operating_systems": [],
        "view_attribution_window": "ONE_DAY",
        "create_time": "{{create_time}}",
        "optimization_event": "ADD_BILLING",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "auto_targeting_enabled": false,
        "catalog_id": "{{catalog_id}}",
        "smart_interest_behavior_enabled": null,
        "bid_display_mode": "CPMV",
        "delivery_mode": null,
        "product_source": "CATALOG",
        "attribution_event_count": "EVERY",
        "conversion_bid_price": 11,
        "smart_audience_enabled": null,
        "pixel_id": "{{pixel_id}}",
        "creative_material_mode": "CATALOG_SALES",
        "app_download_url": null,
        "category_id": "0",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "app_id": null,
        "brand_safety_partner": null,
        "share_disabled": false,
        "campaign_automation_type": "MANUAL",
        "interest_category_ids": [],
        "app_type": null,
        "video_download_disabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "feed_type": null,
        "scheduled_budget": 0,
        "schedule_infos": null,
        "frequency": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_smart_performance_campaign": false,
        "shopping_ads_type": "VIDEO",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "bid_price": 0,
        "secondary_optimization_event": null,
        "deep_bid_type": null,
        "rf_purchased_type": null,
        "frequency_schedule": null,
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "optimization_goal": "CONVERT",
        "comment_disabled": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "advertiser_id": "{{advertiser_id}}",
        "deep_funnel_event_source": null,
        "automated_keywords_enabled": null,
        "engaged_view_attribution_window": "ONE_DAY"
    }
}
(/code)
```
#### Retarget audience
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "pixel_id": "{{pixel_id}}",
    "promotion_type": "WEBSITE",
    "app_config": [
        {
            "app_id": "{{app_id}}"
        },
        {
            "app_id": "{{app_id}}"
        }
    ],
    "optimization_event": "ADD_BILLING",
    "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
    "shopping_ads_retargeting_type": "LAB1",
    "shopping_ads_retargeting_actions_days": 30,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "location_ids": [
        "1861060"
    ],
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM"
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
        "age_groups": null,
        "vbo_window": null,
        "keywords": null,
        "statistic_type": null,
        "skip_learning_phase": true,
        "interest_keyword_ids": [],
        "excluded_audience_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "pacing": "PACING_MODE_FAST",
        "ios14_quota_type": "UNOCCUPIED",
        "deep_funnel_event_source_id": null,
        "campaign_name": "{{campaign_name}}",
        "languages": [],
        "budget": {{budget}},
        "adgroup_name": "{{adgroup_name}}",
        "adgroup_app_profile_page_state": null,
        "next_day_retention": null,
        "isp_ids": [],
        "network_types": [],
        "actions": [],
        "modify_time": "{{modify_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "billing_event": "OCPM",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_estimated_cpr": null,
        "deep_funnel_optimization_status": null,
        "purchased_reach": null,
        "rf_estimated_frequency": null,
        "click_attribution_window": "SEVEN_DAYS",
        "shopping_ads_retargeting_type": "LAB1",
        "audience_ids": [],
        "adgroup_id": "{{adgroup_id}}",
        "app_config": [
            {
                "app_id": "{{app_id}}"
            },
            {
                "app_id": "{{app_id}}"
            }
        ],
        "conversion_window": null,
        "gender": "GENDER_UNLIMITED",
        "deep_funnel_optimization_event": null,
        "purchased_impression": null,
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "category_exclusion_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "deep_cpa_bid": 0,
        "is_hfss": false,
        "location_ids": [
            "1861060"
        ],
        "promotion_type": "WEBSITE",
        "search_result_enabled": false,
        "device_price_ranges": null,
        "operation_status": "ENABLE",
        "inventory_filter_enabled": false,
        "operating_systems": [],
        "view_attribution_window": "ONE_DAY",
        "create_time": "{{create_time}}",
        "optimization_event": "ADD_BILLING",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "auto_targeting_enabled": false,
        "catalog_id": "{{catalog_id}}",
        "smart_interest_behavior_enabled": null,
        "bid_display_mode": "CPMV",
        "delivery_mode": null,
        "product_source": "CATALOG",
        "attribution_event_count": "EVERY",
        "conversion_bid_price": 11,
        "smart_audience_enabled": null,
        "pixel_id": "{{pixel_id}}",
        "creative_material_mode": "CUSTOM",
        "app_download_url": null,
        "category_id": "0",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "app_id": null,
        "brand_safety_partner": null,
        "share_disabled": false,
        "campaign_automation_type": "MANUAL",
        "interest_category_ids": [],
        "app_type": null,
        "video_download_disabled": true,
        "schedule_start_time": "{{schedule_start_time}}",
        "feed_type": null,
        "scheduled_budget": 0,
        "schedule_infos": null,
        "frequency": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_smart_performance_campaign": false,
        "shopping_ads_type": "VIDEO",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "bid_price": 0,
        "secondary_optimization_event": null,
        "deep_bid_type": null,
        "rf_purchased_type": null,
        "frequency_schedule": null,
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "optimization_goal": "CONVERT",
        "comment_disabled": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "advertiser_id": "{{advertiser_id}}",
        "deep_funnel_event_source": null,
        "automated_keywords_enabled": null,
        "engaged_view_attribution_window": "ONE_DAY"
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
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name<br>(Optional)</td>
      <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code> (empty string).</li><li> To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value.<br><br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
    <tr>
      <td>Products</td>
      <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
      <td><code>catalog_id</code><br><code>product_specific_type</code><br><code>item_group_ids</code><br><code>product_set_id</code><br><code>sku_ids</code></td>
      <td><ul><li>Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>sku_ids</code>, <code>item_group_ids</code>, and <code>product_set_id</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass either <code>item_group_ids</code> or <code>product_set_id</code>. <code>sku_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>sku_ids</code>. <code>item_group_ids</code> and <code>product_set_id</code> are not needed.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</td>
    </tr>
    <tr>
      <td rowspan="2">Ad format</td>
      <td rowspan="2"><b>Single video</b>: Use a video creative to promote your products.</td>
      <td><code>ad_format</code></td>
      <td><code>SINGLE_VIDEO</code></td>
    </tr>
	<tr>
      <td><code>vertical_video_strategy</code></td>
      <td><code>SINGLE_VIDEO</code></td>
    </tr>
    <tr>
      <td  rowspan="3">Ad creative</td>
      <td  rowspan="3"><ul><li> For Custom User identity or Spark Ads PUSH with TikTok User identity, specify a video and a cover image.</li><li>For TikTok Account User in Business Center identity, Authorized User identity, or Spark Ads PULL with TikTok User identity, specify a TikTok post.</li></ul></td>
      <td><code>video_id</code><br><code>image_ids</code><br><code>ad_text</code><br><code>tiktok_item_id</code><br><code>dark_post_status</code></td>
      <td><ul><li>If <code>identity_type</code> is <code>CUSTOMIZED_USER</code>, specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>). Do not pass <code>tiktok_item_id</code>.</li><li>If <code>identity_type</code> is <code>TT_USER</code>, you can create the ad through Spark Ads PULL or Spark Ads PUSH:<ul><li>Spark Ads PUSH: Specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>). Do not pass <code>tiktok_item_id</code>. You can set <code>dark_post_status</code> to <code>ON</code> to ensure the video only shows as ad and does not organically appear on the TikTok Account.</li><li>Spark Ads PULL: Specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, <code>ad_text</code>, and <code>dark_post_status</code>.</li></ul>To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.</li><li>If <code>identity_type</code> is <code>AUTH_CODE</code> or <code>BC_AUTH_TT</code>, specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, and <code>ad_text</code>.</li></ul>
</td>
    </tr>
    <tr>
      <td><code>shopping_ads_video_package_id</code></td>
      <td>Not specified</td>
    </tr>
	<tr>
      <td><code>music_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-on<br>(Optional)</td>
      <td>Disabled or enabled with any of the following types:<ul><li>Product Card</li><li>Product Tiles (supported with allowlist)</li><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul></td>
      <td><code>card_id</code></td>
      <td>Not specified or specify the ID of one of the following creative portfolio types:<ul><li>Product Card</li><li>Product Tiles</li><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card, Product Card, or Product Tiles portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank">Cards</a>.</li><li>To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>Website</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td  rowspan="3">Deeplink</td>
      <td  rowspan="3">Enabled with a custom deeplink</td>
      <td><code>shopping_ads_deeplink_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
	<tr>
      <td><code>deeplink_type</code></td>
      <td><code>NORMAL</code></td>
    </tr>
	<tr>
      <td><code>deeplink</code></td>
      <td>Specify a custom deeplink.<br>The deeplink format can be any of the following:<ul><li>Custom URL scheme, in the format of <code>scheme://resource</code>. For instance, a Custom URL scheme of WhatsApp should follow the format <code>whatsapp://resource</code>.</li><li>Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Deeplink parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>deeplink_utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add deeplink URL parameters to the custom deeplink by directly setting <code>deeplink</code> to a deeplink that already includes URL parameters.<ul><li>However, if you use a custom deeplink in your ad and specify <code>deeplink_utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom deeplink (<code>deeplink</code>) upon ad delivery. In such cases, you need to ensure that <code>deeplink_utm_params</code> exactly matches the used deeplink URL parameters. Otherwise, an error will occur.</li></ul> </td>
    </tr>
    <tr>
      <td  rowspan="2">Fallback URL</td>
      <td  rowspan="2">Enabled with a custom link (the promoted web page)</td>
      <td><code>shopping_ads_fallback_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
	 <tr>
      <td><code>landing_page_url</code></td>
      <td>Specify a custom link.<br>This URL directs users who have not installed the App to a designated page (for instance, the homepage of your official website) when a deeplink is configured for an ad.</td>
    </tr>
    <tr>
      <td>URL parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add URL parameters to the custom link by directly setting <code>landing_page_url</code> to a URL that already includes URL parameters.<ul><li>However, if you use a custom link in your ad and specify <code>utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom link (<code>landing_page_url</code>) upon ad delivery. In such cases, you need to ensure that you specify a <code>landing_page_url</code> with the URL parameters already appended, and that <code>utm_params</code> exactly matches the used URL parameters. Otherwise, an error will occur.</li></ul></td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Enabled</td>
      <td><code>call_to_action</code></td>
      <td>Specify a valid value.<br><br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank">Enumerations - Call-to-action</a>. </td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;· Offline events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_offline_event_set_ids</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Impression tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>impression_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Click tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>click_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Measure brand safety with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>brand_safety_postbid_partner</code><br><code>brand_safety_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Track viewability with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>viewability_postbid_partner</code><br><code>viewability_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
##### Spark Ads Pull
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
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
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "tiktok_item_id": "{{tiktok_item_id}}",
            "card_id": "{{card_id}}",
            "shopping_ads_deeplink_type": "CUSTOM",
            "deeplink_type": "NORMAL",
            "deeplink": "{{deeplink}}",
            "shopping_ads_fallback_type": "CUSTOM",
            "landing_page_url": "{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
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
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": "{{deeplink}}",
                "profile_image_url": "",
                "card_id": "{{card_id}}",
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "viewability_vast_url": null,
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "promotional_music_disabled": true,
                "image_ids": [],
                "ad_id": "{{ad_id}}",
                "dynamic_format": "UNSET",
                "music_id": null,
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "product_specific_type": "ALL",
                "identity_type": "TT_USER",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "avatar_icon_web_uri": "",
                "operation_status": "ENABLE",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "create_time": "{{create_time}}",
                "optimization_event": "ADD_BILLING",
                "dynamic_destination": "UNSET",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_text": "{{ad_text}}",
                "deeplink_type": "NORMAL",
                "catalog_id": "{{catalog_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "shopping_ads_deeplink_type": "CUSTOM",
                "creative_authorized": false,
                "carousel_image_labels": null,
                "app_name": "{{app_name}}",
                "campaign_automation_type": "MANUAL",
                "impression_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}"
                ],
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "page_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "shopping_ads_fallback_type": "CUSTOM"
            }
        ]
    }
}
(/code)
```
##### Spark Ads Push
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{AccessToken}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": [
                "{{image_ids}}"
            ],
            "ad_text": "{{ad_text}}",
            "card_id": "{{card_id}}",
            "shopping_ads_deeplink_type": "CUSTOM",
            "deeplink_type": "NORMAL",
            "deeplink": "{{deeplink}}",
            "shopping_ads_fallback_type": "CUSTOM",
            "landing_page_url": "{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
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
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": "{{deeplink}}",
                "profile_image_url": "",
                "card_id": "{{card_id}}",
                "campaign_name": "{{campaign_name}}",
                "video_id": "{{video_id}}",
                "viewability_vast_url": null,
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "dark_post_status": "OFF",
                "image_ids": [
                    "{{image_ids}}"
                ],
                "ad_id": "{{ad_id}}",
                "dynamic_format": "UNSET",
                "music_id": null,
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "product_specific_type": "ALL",
                "identity_type": "TT_USER",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "avatar_icon_web_uri": "",
                "operation_status": "ENABLE",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "create_time": "{{create_time}}",
                "optimization_event": "ADD_BILLING",
                "dynamic_destination": "UNSET",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_text": "{{ad_text}}",
                "deeplink_type": "NORMAL",
                "catalog_id": "{{catalog_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "shopping_ads_deeplink_type": "CUSTOM",
                "creative_authorized": false,
                "branded_content_disabled": false,
                "carousel_image_labels": null,
                "app_name": "{{app_name}}",
                "campaign_automation_type": "MANUAL",
                "impression_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}"
                ],
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "page_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "shopping_ads_fallback_type": "CUSTOM"
            }
        ]
    }
}
(/code)
```

### Ad format as Catalog Video

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name<br>(Optional)</td>
      <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code> (empty string).</li><li> To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Identity</td>
      <td rowspan="3">Custom User (custom identity)</td>
      <td><code>identity_type</code></td>
      <td><code>CUSTOMIZED_USER</code></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value.<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Products</td>
      <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
      <td><code>catalog_id</code><br><code>product_specific_type</code><br><code>item_group_ids</code><br><code>product_set_id</code><br><code>sku_ids</code></td>
      <td><ul><li>Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>sku_ids</code>, <code>item_group_ids</code>, and <code>product_set_id</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass either <code>item_group_ids</code> or <code>product_set_id</code>. <code>sku_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>sku_ids</code>. <code>item_group_ids</code> and <code>product_set_id</code> are not needed.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</td>
    </tr>
    <tr>
      <td rowspan="2">Ad format</td>
      <td rowspan="2"><b>Catalog video</b>: Generate a dynamic video based on information from the product catalog.</td>
      <td><code>ad_format</code></td>
      <td><code>SINGLE_VIDEO</code></td>
    </tr>
    <tr>
      <td><code>vertical_video_strategy</code></td>
      <td>Any of the following values:<ul><li><code>CATALOG_UPLOADED_VIDEOS</code>: catalog video using uploaded videos. To learn more about how to manage uploaded catalog videos, see <a href="https://business-api.tiktok.com/portal/docs?id=1803654904260674" target="_blank">Upload catalog videos to associate with catalog products</a>.</li><li><code>CATALOG_VIDEOS</code>: catalog video using video templates.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Ad creative</td>
      <td rowspan="2">A catalog video. You need to ensure that at least one uploaded video or video template has been bound to your catalog or a video URL has been configured for each catalog product.<ul><li>If you select Your Videos (uploaded videos), the system will automatically select uploaded videos featuring your products.</li><li>If you select a video template, the video template will be used to automatically generate the catalog video for each catalog product.</li><li>If you don't select the video template, the system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video. When a product has a video URL, that video will be used instead.</li></ul></td>
      <td><code>shopping_ads_video_package_id</code></td>
      <td>Specified or not specified.<ul><li>If this field is not specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_UPLOADED_VIDEOS</code>, the system will automatically select uploaded videos featuring your products.</li><li>If this field is specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_VIDEOS</code>, the specified video template will be used to automatically generate the catalog video for each catalog product.</li><li>If this field is not specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_VIDEOS</code>, the system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video. When a product has a video URL, that video will be used instead.</li></ul><br>To obtain the IDs of video templates (video packages) bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank">/catalog/video_package/get/</a>.<br>To learn about how to create video packages on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog" target="_blank">Create Video Packages</a>.</td>
    </tr>
    <tr>
      <td><code>video_id</code><br><code>image_ids</code><br><code>tiktok_item_id</code><br><code>music_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Ad text</td>
      <td>Specified</td>
      <td><code>ad_text</code></td>
      <td>Specify a valid value.</td>
    </tr>
    <tr>
      <td>Interactive add-on<br>(Optional)</td>
      <td>Disabled or enabled with any of the following types:<ul><li>Product Card</li><li>Product Tiles (supported with allowlist)</li><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul></td>
      <td><code>card_id</code></td>
      <td>Not specified or specify the ID of one of the following creative portfolio types:<ul><li>Product Card</li><li>Product Tiles</li><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card, Product Card, or Product Tiles portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank">Cards</a>.</li><li>To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>Website</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td rowspan="3">Deeplink</td>
      <td rowspan="3">Enabled with a custom deeplink</td>
      <td><code>shopping_ads_deeplink_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
    <tr>
      <td><code>deeplink_type</code></td>
      <td><code>NORMAL</code></td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td>Specify a custom deeplink.<br>The deeplink format can be any of the following:<ul><li>Custom URL scheme, in the format of <code>scheme://resource</code>. For instance, a Custom URL scheme of WhatsApp should follow the format <code>whatsapp://resource</code>.</li><li>Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Deeplink parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>deeplink_utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add deeplink URL parameters to the custom deeplink by directly setting <code>deeplink</code> to a deeplink that already includes URL parameters.<ul><li>However, if you use a custom deeplink in your ad and specify <code>deeplink_utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom deeplink (<code>deeplink</code>) upon ad delivery. In such cases, you need to ensure that <code>deeplink_utm_params</code> exactly matches the used deeplink URL parameters. Otherwise, an error will occur.</li></ul> </td>
    </tr>
    <tr>
      <td rowspan="2">Fallback URL</td>
      <td rowspan="2">Enabled with a custom link (the promoted web page)</td>
      <td><code>shopping_ads_fallback_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
    <tr>
      <td><code>landing_page_url</code></td>
      <td>Specify a custom link.<br>This URL directs users who have not installed the App to a designated page (for instance, the homepage of your official website) when a deeplink is configured for an ad.</td>
    </tr>
    <tr>
      <td>URL parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add URL parameters to the custom link by directly setting <code>landing_page_url</code> to a URL that already includes URL parameters.<ul><li>However, if you use a custom link in your ad and specify <code>utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom link (<code>landing_page_url</code>) upon ad delivery. In such cases, you need to ensure that you specify a <code>landing_page_url</code> with the URL parameters already appended, and that <code>utm_params</code> exactly matches the used URL parameters. Otherwise, an error will occur.</li></ul></td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Enabled</td>
      <td><code>call_to_action</code></td>
      <td>Specify a valid value.<br><br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank">Enumerations - Call-to-action</a>. </td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;- Offline events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_offline_event_set_ids</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Impression tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>impression_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Click tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>click_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Measure brand safety with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>brand_safety_postbid_partner</code><br><code>brand_safety_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Track viewability with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>viewability_postbid_partner</code><br><code>viewability_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
##### Using video templates
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{AccessToken}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "ad_text": "{{ad_text}}",
            "card_id": "{{card_id}}",
            "shopping_ads_deeplink_type": "CUSTOM",
            "deeplink_type": "NORMAL",
            "deeplink": "{{deeplink}}",
            "shopping_ads_fallback_type": "CUSTOM",
            "landing_page_url": "{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
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
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": "{{deeplink}}",
                "profile_image_url": "",
                "card_id": "{{card_id}}",
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "viewability_vast_url": null,
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "image_ids": [],
                "ad_id": "{{ad_id}}",
                "dynamic_format": "UNSET",
                "music_id": null,
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "product_specific_type": "ALL",
                "identity_type": "CUSTOMIZED_USER",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "avatar_icon_web_uri": "",
                "operation_status": "ENABLE",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "create_time": "{{create_time}}",
                "optimization_event": "ADD_BILLING",
                "dynamic_destination": "UNSET",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_text": "{{ad_text}}",
                "deeplink_type": "NORMAL",
                "catalog_id": "{{catalog_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "shopping_ads_deeplink_type": "CUSTOM",
                "creative_authorized": false,
                "carousel_image_labels": null,
                "app_name": "",
                "campaign_automation_type": "MANUAL",
                "impression_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}"
                ],
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "page_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "shopping_ads_fallback_type": "CUSTOM"
            }
        ]
    }
}
(/code)
```
##### Using uploaded catalog videos
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{AccessToken}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_UPLOADED_VIDEOS",
            "ad_text": "{{ad_text}}",
            "card_id": "{{card_id}}",
            "shopping_ads_deeplink_type": "CUSTOM",
            "deeplink_type": "NORMAL",
            "deeplink": "{{deeplink}}",
            "shopping_ads_fallback_type": "CUSTOM",
            "landing_page_url": "{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
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
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": "{{deeplink}}",
                "profile_image_url": "",
                "card_id": "{{card_id}}",
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "viewability_vast_url": null,
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "image_ids": [],
                "ad_id": "{{ad_id}}",
                "dynamic_format": "UNSET",
                "music_id": null,
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "product_specific_type": "ALL",
                "identity_type": "CUSTOMIZED_USER",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "avatar_icon_web_uri": "",
                "operation_status": "ENABLE",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "create_time": "{{create_time}}",
                "optimization_event": "ADD_BILLING",
                "dynamic_destination": "UNSET",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_text": "{{ad_text}}",
                "deeplink_type": "NORMAL",
                "catalog_id": "{{catalog_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "shopping_ads_deeplink_type": "CUSTOM",
                "creative_authorized": false,
                "carousel_image_labels": null,
                "app_name": "",
                "campaign_automation_type": "MANUAL",
                "impression_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}"
                ],
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "vertical_video_strategy": "CATALOG_UPLOADED_VIDEOS",
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "page_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "shopping_ads_fallback_type": "CUSTOM"
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
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name<br>(Optional)</td>
      <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code> (empty string).</li><li> To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Identity</td>
      <td rowspan="3">Custom User (custom identity)</td>
      <td><code>identity_type</code></td>
      <td><code>CUSTOMIZED_USER</code></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td rowspan="2">Ad format</td>
      <td rowspan="2"><b>Catalog Carousel</b>: Create an ad with 2–20 products displayed in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
      <td><code>ad_format</code></td>
      <td><code>CATALOG_CAROUSEL</code></td>
    </tr>
    <tr>
      <td><code>vertical_video_strategy</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Products</td>
      <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
      <td rowspan="2"><code>catalog_id</code><br><code>product_specific_type</code><br><code>item_group_ids</code><br><code>product_set_id</code><br><code>sku_ids</code></td>
      <td  rowspan="2"><ul><li>Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Carousel Ads. You can specify the scope of product images to choose from using the <code>product_specific_type</code> parameter:<ul><li>To have the Carousel images dynamically chosen from all products in the catalog, set <code>product_specific_type</code> to <code>ALL</code>, and do not pass any of <code>item_group_ids</code>, <code>product_set_id</code>, or <code>sku_ids</code>. The catalog (<code>catalog_id</code>) needs to include at least two products.</li><li>To have the Carousel images dynamically chosen from products in a product set, set <code>product_specific_type</code> to <code>PRODUCT_SET</code>, and pass either <code>item_group_ids</code> or <code>product_set_id</code>. The specified <code>item_group_ids</code> or <code>product_set_id</code> needs to include at least two products.</li><li>To have the images dynamically chosen from a customized product range consisting of up to 20 products, set <code>product_specific_type</code> to <code>CUSTOMIZED_PRODUCTS</code>, and pass <code>sku_ids</code>. The specified <code>sku_ids</code> needs to include at least two products.<br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Ad creative<br>· image</td>
      <td rowspan="2">At least two images supported in the Carousel Ad</td>
    </tr>
	<tr>
      <td><code>image_ids</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Ad creative <br>· video</td>
      <td>Disabled</td>
      <td><code>video_id</code><br><code>tiktok_item_id</code><br><code>shopping_ads_video_package_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Ad creative <br>· music</td>
      <td>A piece of music supported in the Carousel Ad</td>
      <td><code>music_id</code></td>
      <td>Pass one music ID that is valid for use in Catalog Carousel Ads.<br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Ad text</td>
      <td>Specified</td>
      <td><code>ad_text</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Interactive add-on</td>
      <td>Disabled</td>
      <td><code>card_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>Website</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td rowspan="3">Deeplink</td>
      <td rowspan="3">Enabled with a custom deeplink</td>
      <td><code>shopping_ads_deeplink_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
    <tr>
      <td><code>deeplink_type</code></td>
      <td><code>NORMAL</code></td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td>Specify a custom deeplink.<br>The deeplink format can be any of the following:<ul><li>Custom URL scheme, in the format of <code>scheme://resource</code>. For instance, a Custom URL scheme of WhatsApp should follow the format <code>whatsapp://resource</code>.</li><li>Apple's universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Deeplink parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>deeplink_utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add deeplink URL parameters to the custom deeplink by directly setting <code>deeplink</code> to a deeplink that already includes URL parameters.<ul><li>However, if you use a custom deeplink in your ad and specify <code>deeplink_utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom deeplink (<code>deeplink</code>) upon ad delivery. In such cases, you need to ensure that <code>deeplink_utm_params</code> exactly matches the used deeplink URL parameters. Otherwise, an error will occur.</li></ul> </td>
    </tr>
    <tr>
      <td rowspan="2">Fallback URL</td>
      <td rowspan="2">Enabled with a custom link (the promoted web page)</td>
      <td><code>shopping_ads_fallback_type</code></td>
      <td><code>CUSTOM</code></td>
    </tr>
    <tr>
      <td><code>landing_page_url</code></td>
      <td>Specify a custom link.<br>This URL directs users who have not installed the App to a designated page (for instance, the homepage of your official website) when a deeplink is configured for an ad.</td>
    </tr>
    <tr>
      <td>URL parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add URL parameters to the custom link by directly setting <code>landing_page_url</code> to a URL that already includes URL parameters.<ul><li>However, if you use a custom link in your ad and specify <code>utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom link (<code>landing_page_url</code>) upon ad delivery. In such cases, you need to ensure that you specify a <code>landing_page_url</code> with the URL parameters already appended, and that <code>utm_params</code> exactly matches the used URL parameters. Otherwise, an error will occur.</li></ul></td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Enabled</td>
      <td><code>call_to_action</code></td>
      <td>Specify a valid value.<br><br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank">Enumerations - Call-to-action</a>. </td>
    </tr>
    <tr>
      <td>Tracking<br>- TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;- Offline events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_offline_event_set_ids</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Impression tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>impression_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Click tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>click_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Measure brand safety with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>brand_safety_postbid_partner</code><br><code>brand_safety_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;&nbsp;&nbsp;· Track viewability with third-party partner<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>viewability_postbid_partner</code><br><code>viewability_vast_url</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>



#### Example
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{AccessToken}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id": "{{catalog_id}}",
            "product_specific_type": "ALL",
            "ad_format": "CATALOG_CAROUSEL",
            "music_id": "{{music_id}}",
            "ad_text": "{{ad_text}}",
            "shopping_ads_deeplink_type": "CUSTOM",
            "deeplink_type": "NORMAL",
            "deeplink": "{{deeplink}}",
            "shopping_ads_fallback_type": "CUSTOM",
            "landing_page_url": "{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
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
            "{{ad_ids}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": "{{deeplink}}",
                "profile_image_url": "",
                "card_id": null,
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "viewability_vast_url": null,
                "shopping_ads_video_package_id": "",
                "ad_texts": null,
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "image_ids": [],
                "ad_id": "{{ad_id}}",
                "dynamic_format": "UNSET",
                "music_id": "{{music_id}}",
                "landing_page_urls": null,
                "identity_id": "{{identity_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "product_specific_type": "ALL",
                "identity_type": "CUSTOMIZED_USER",
                "playable_url": "",
                "call_to_action": "LEARN_MORE",
                "avatar_icon_web_uri": "",
                "operation_status": "ENABLE",
                "ad_ref_pixel_id": "{{ad_ref_pixel_id}}",
                "create_time": "{{create_time}}",
                "optimization_event": "ADD_BILLING",
                "dynamic_destination": "UNSET",
                "display_name": "{{display_name}}",
                "vast_moat_enabled": false,
                "ad_text": "test",
                "deeplink_type": "NORMAL",
                "catalog_id": "{{catalog_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": null,
                "shopping_ads_deeplink_type": "CUSTOM",
                "creative_authorized": false,
                "carousel_image_labels": null,
                "app_name": "",
                "campaign_automation_type": "MANUAL",
                "impression_tracking_url": null,
                "ad_format": "CATALOG_CAROUSEL",
                "click_tracking_url": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_ids}}"
                ],
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}",
                "vertical_video_strategy": null,
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "page_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "shopping_ads_fallback_type": "CUSTOM"
            }
        ]
    }
}
(/code)
```
