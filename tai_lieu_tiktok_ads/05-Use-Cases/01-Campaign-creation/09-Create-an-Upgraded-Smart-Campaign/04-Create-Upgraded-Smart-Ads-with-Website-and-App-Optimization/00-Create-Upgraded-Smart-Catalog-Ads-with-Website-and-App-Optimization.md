---
title: "Create Upgraded Smart+ Catalog Ads with Website and App Optimization"
doc_id: 1855007137120578
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Ads with Website and App Optimization / Create Upgraded Smart+ Catalog Ads with Website and App Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Upgraded Smart+ Catalog Ads with Website and App Optimization

Upgraded Smart+ Catalog Ads with Website and App Optimization are Upgraded Smart+ Sales Campaigns that use catalog products to drive sales on both your website and your app. For such campaigns, you need to set `objective_type` to `WEB_CONVERSIONS`, `sales_destination` to `WEB_AND_APP`, and `catalog_enabled `to `true` at the campaign level. 


# Prerequisite
Creating Upgraded Smart+ Catalog Ads with Website and App Optimization requires setting up a catalog in your Business Center first.

To set up the catalog:

1. Create a catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961).
<br>The catalog type can be any of the following:
- E-commerce
- hotel
- flight
- destination
- entertainment

2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
3. Check the product handling results.
	- For CSV feed and JSON schema, use [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
		- Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
	- For online feed, use [/catalog/feed/log/](https://business-api.tiktok.com/portal/docs?id=1740665225631810).
		- Pass in the `feed_id` obtained from Step 2. If the field `error_count` in the response is not 0, examine your online feed and return to Step 2 to reupload the product.
4. (Optional) Create a product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
<br>If you want to have products selected from a product set, creating a product set is necessary. Otherwise, you can skip this step.
5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
<br>You can also choose `advertiser_role` that you want to assign to the members invited.
6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
<br>Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.

For all catalog APIs, see [here](https://business-api.tiktok.com/portal/docs?id=1739578477445121).

# Steps
## Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

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
            <td>Advertising objective</td>
            <td>Sales</td>
            <td><code>objective_type</code></td>
            <td><code>WEB_CONVERSIONS</code></td>
        </tr>
        <tr>
            <td>Sales destination</td>
            <td>Website and app</td>
            <td><code>sales_destination</code></td>
            <td><code>WEB_AND_APP</code></td>
        </tr>
        <tr>
            <td rowspan="3">iOS 14 dedicated campaign</td>
            <td rowspan="3">Disabled</td>
            <td><code>campaign_type</code></td>
            <td><code>REGULAR_CAMPAIGN</code> or not specified</td>
        </tr>
        <tr>
            <td><code>app_id</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>campaign_app_profile_page_state</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Use catalog</td>
            <td><b>Enabled</b></td>
            <td><code>catalog_enabled </code></td>
            <td><code>true</code></td>
        </tr>
        <tr>
            <td>Catalog type</td>
            <td>Any of the following types:<ul><li>E-commerce</li><li>Travel and entertainment</li></ul></td>
            <td><code>catalog_type</code></td>
            <td>Any of the following values:<ul><li><code>ECOMMERCE</code></li><li><code>TRAVEL_ENTERTAINMENT</code></li></ul></td>
        </tr>
        <tr>
            <td>Campaign name</td>
            <td>Specify a valid name</td>
            <td><code>campaign_name</code></td>
            <td>Specify a valid value</td>
        </tr>
        <tr>
            <td>Special ad categories<br>(Optional)</td>
            <td>Disabled or enabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</p></td>
            <td><code>special_industries</code></td>
            <td>Not specified or specify a valid value</td>
        </tr>
    <tr>
        <td>Campaign budget strategy</td>
        <td>Any of the following types:<ul><li><strong>Campaign budget</strong>: Your budget will be automatically allocated and optimized across ad groups. All ad groups must share the same budget and optimization settings.</li><li><strong>Ad group budget</strong>: Precisely control spend for each ad group, using different budget and optimization settings for each.</li></ul></td>
        <td><code>budget_optimize_on</code></td>
        <td><ul><li>To set a campaign budget, set this field to <code>true</code> or leave it unspecified.</li><li>To set an ad group budget, set this field to <code>false</code>.</li></ul></td>
    </tr>
	<tr>
        <td>Campaign budget mode</td>
        <td><ul><li>When Campaign budget strategy is Campaign budget, specify any of the following modes:<ul><li>Daily</li><li>Lifetime</li></ul></li><li>When Campaign budget strategy is Ad group budget, specify any of the following modes to determine the budget limit for all ad groups:<ul><li>No limit</li><li>Daily</li><li>Lifetime</li></ul></li></ul></td>
        <td><code>budget_mode</code></td>
        <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, use any of the following values:<ul><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>, use any of the following values:<ul><li><code>BUDGET_MODE_INFINITE</code></li><li><code>BUDGET_MODE_DAY</code></li><li><code>BUDGET_MODE_TOTAL</code></li></ul></li></ul></td>
    </tr>
	<tr>
        <td>Campaign budget</td>
        <td><ul><li>When Campaign budget strategy is Campaign budget:<ul><li>Specify a valid budget.</li></ul></li><li>When Campaign budget strategy is Ad group budget:<ul><li>If the budget limit is No limit:<ul><li>Disabled</li></ul></li><li>If the budget limit is Daily or Lifetime limit:<ul><li>Specify a valid budget</li></ul></li></ul></li></ul></td>
        <td><code>budget</code></td>
        <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified:<ul><li>Specify a valid value.</li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>If <code>budget_mode</code> is <code>BUDGET_MODE_INFINITE</code>:<ul><li>Not specified</li></ul></li><li>If <code>budget_mode</code> is <code>BUDGET_MODE_DAY</code> or <code>BUDGET_MODE_TOTAL</code>:<ul><li>Specify a valid value</li></ul></li></ul></li></ul></td>
    </tr>
        <tr>
            <td>PO number (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>po_number</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
    </tbody>
</table>

### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "WEB_AND_APP",
    "catalog_enabled": true,
    "catalog_type": "ECOMMERCE",
    "campaign_name": "{{campaign_name}}",
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
        "advertiser_id": "{{advertiser_id}}",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "catalog_enabled": true,
        "catalog_type": "ECOMMERCE",
        "create_time": "{{create_time}}",
        "is_promotional_campaign": false,
        "modify_time": "{{modify_time}}",
        "objective_type": "WEB_CONVERSIONS",
        "operation_status": "ENABLE",
        "sales_destination": "WEB_AND_APP",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}

(/code)
```

## Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1843314887930946). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1843314887930946).

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
            <td>Product source details<br>· Catalog</td>
            <td>Specify <b>a catalog with at least two approved products</b><br><br>The catalog type can be any of the following:<ul><li>E-commerce</li><li>hotel</li><li>flight</li><li>destination</li><li>entertainment</li></ul></td>
            <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
            <td>Specify valid values.<br><br>The catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 2. <br>The <code>catalog_type</code> of the catalog (<code>catalog_id</code>)  can be any of the following:<ul><li><code>ECOM</code></li><li><code>HOTEL</code></li><li><code>FLIGHT</code></li><li><code>DESTINATION</code></li><li><code>ENTERTAINMENT</code></li></ul><br><ul><li>To obtain catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>.</li><li>To learn about how to create a catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</li></ul> </td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Optimization location</td>
            <td>Website<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>promotion_type</code></td>
            <td><code>WEBSITE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Android & iOS</td>
            <td>Specify any of the following:<ul><li>an Android app</li><li>an iOS app</li><li>an Android app and an iOS app</li></ul></td>
            <td><code>app_config</code></td>
            <td>Specify any of the following:<ul><li>App ID of an Android app</li><li>App ID of an iOS app</li><li>App IDs of an Android app and an iOS app</li></ul><br>To obtain the list of App IDs within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a> and check the returned <code>app_id</code>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Goal</td>
            <td>Any of the following types:<ul><li>Conversion</li><li>Value</li></ul><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>optimization_goal</code></td>
            <td>Any of the following values:<ul><li><code>CONVERT</code></li><li><code>VALUE</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:  When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Data connection</td>
            <td>Specify a valid Pixel</td>
            <td><code>pixel_id</code></td>
            <td>Specify a valid value.<br><br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Optimization event</td>
            <td>Specify a valid optimization event<ul><li>When optimization goal is Value, specify the optimization event as Complete Payment.</li></ul><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>optimization_event</code></td>
            <td>Specify a valid value.<br><br><p><span style="color:darkred"><b>Note</b></span>:  When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.<br><br>To learn about the supported event types, see <a href="#item-link-List of values for optimization_event for Upgraded Smart+ Ads with Website and App Optimization">List of values for <code>optimization_event</code> for Upgraded Smart+ Ads with Website and App Optimization</a>.<br>You need to ensure the specified optimization event is active for both the Pixel and the app or apps.<br><ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li><li>To obtain the active optimization events for an app, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>:  When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_event</code>for ad groups within the same campaign.</td>
        </tr>
         <tr>
            <td>Optimization and bidding<br>· Bid Strategy, (Optional) Target CPA, <br>and (Optional) Target ROAS</td>
            <td><ul><li>If Goal is Conversion, the strategy can only be Maximum Delivery or Cost Cap.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
            <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code>  (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:  <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li> When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
        </tr>
    </tbody>
        <tr>
            <td>Optimization and bidding<br>· Attribution window<br>&nbsp;&nbsp;· Click-through window</td>
            <td>Any of the following types:<ul><li>1-day click</li><li>7-day click</li><li>14-day click</li><li>28-day click</li></ul></td>
            <td><code>click_attribution_window</code></td>
            <td>Any of the following values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution window<br>&nbsp;&nbsp;· Engaged view-through window</td>
            <td>Any of the following types:<ul><li>1-day engaged view</li><li>7-day engaged view</li><li>14-day engaged view</li><li>28-day engaged view</li></ul></td>
            <td><code>view_attribution_window</code></td>
            <td>Any of the following values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution window<br>&nbsp;&nbsp;· View-through window</td>
            <td>Any of the following types:<ul><li>Off</li><li>1-day view</li><li>7-day view</li></ul></td>
            <td><code>engaged_view_attribution_window</code></td>
            <td>Any of the following values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Event count</td>
            <td>Any of the following types:<ul><li>Every</li><li>Once</li></ul></td>
            <td><code>attribution_event_count</code></td>
            <td>Any of the following values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Billing event</td>
            <td><ul><li>If Goal is Click, the billing event should be CPC.</li><li>If Goal is Install, In-app event, or Value, the billing event should be oCPM.</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</li></ul></p></td>
            <td><code>billing_event</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>,  or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Delivery Type</td>
            <td>Standard (default)</td>
            <td>N/A</td>
            <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Budget</td>
            <td><ul><li>When you select a campaign budget mode:<ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul></li><li>When you select an ad group budget strategy:<ul><li>If the ad group budget limit is No limit or Lifetime limit, you can use any of the following ad group budget modes:<ul><li>Lifetime</li><li>Daily</li></ul></li><li>If the ad group budget limit is Daily limit, you can use the following ad group budget mode:<ul><li>Daily</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</p></td>
            <td><code>budget_mode</code><br><code>budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code> are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> and <code>budget_mode</code> is <code>BUDGET_MODE_INFINITE</code> or <code>BUDGET_MODE_TOTAL</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DAY</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
            <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
            <td><code>min_budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Schedule</td>
            <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
            <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
            <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></p></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Dayparting (Optional)</td>
            <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
            <td><code>dayparting</code></td>
            <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
        </tr>
        <tr>
            <td>Audience targeting optimization mode</td>
            <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads with Website and App Optimization">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads with Website and App Optimization</a>.</td>
            <td><code>targeting_optimization_mode</code></td>
            <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads with Website and App Optimization">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads with Website and App Optimization</a>.</td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Placement</td>
            <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select specific placements</b>:<ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></li></ul></td>
            <td><code>placement_type</code><br><code>placements</code></td>
            <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.<ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul></li><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Brand safety and suitability</td>
            <td>These settings only apply to <b>TikTok in-feed</b> and <b>search</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
            <td>N/A</td>
            <td>N/A<br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· User comment</td>
            <td>Enabled or disabled</td>
            <td><code>comment_disabled</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Allow video download</td>
            <td>Enabled or disabled</td>
            <td><code>share_disabled</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Allow video sharing</td>
            <td>Enabled or disabled</td>
            <td><code>video_download_disabled</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Pangle block list</td>
            <td>Disabled</td>
            <td>N/A</td>
            <td>N/A</td>
        </tr>
    </tbody>
</table>

### List of values for `optimization_event` for Upgraded Smart+ Ads with Website and App Optimization
The following tables list the events supported for Upgraded Smart+ Ads with Website and App Optimization.

<table>
    <thead>
        <tr>
            <th style="width:17%">Event name</th>
            <th style="width:26%"><code>optimization_event</code> value</th>
            <th style="width:27%">Corresponding Pixel event<br>(<code>optimization_event</code>)</th>
            <th style="width:30%">Corresponding app event<br>(<code>optimization_event</code> or <code>secondary_optimization_event</code>)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Add payment info</td>
            <td><code>ADD_BILLING</code></td>
            <td><code>ADD_BILLING</code></td>
            <td><code>ADD_PAYMENT_INFO</code></td>
        </tr>
        <tr>
            <td>Add to Cart</td>
            <td><code>ON_WEB_CART</code></td>
            <td><code>ON_WEB_CART</code></td>
            <td><code>IN_APP_CART</code></td>
        </tr>
        <tr>
            <td>Add to wishlist</td>
            <td><code>ON_WEB_ADD_TO_WISHLIST</code></td>
            <td><code>ON_WEB_ADD_TO_WISHLIST</code></td>
            <td><code>ADD_TO_WISHLIST</code></td>
        </tr>
        <tr>
            <td>Initiate Checkout<br>/Checkout</td>
            <td><code>INITIATE_ORDER</code></td>
            <td><code>INITIATE_ORDER</code></td>
            <td><code>IN_APP_ORDER</code></td>
        </tr>
        <tr>
            <td>Purchase</td>
            <td><code>SHOPPING</code></td>
            <td><code>SHOPPING</code></td>
            <td><code>ACTIVE_PAY</code></td>
        </tr>
        <tr>
            <td>Search</td>
            <td><code>ON_WEB_SEARCH</code></td>
            <td><code>ON_WEB_SEARCH</code></td>
            <td><code>SEARCH</code></td>
        </tr>
        <tr>
            <td>Subscribe</td>
            <td><code>ON_WEB_SUBSCRIBE</code></td>
            <td><code>ON_WEB_SUBSCRIBE</code></td>
            <td><code>SUBSCRIBE</code></td>
        </tr>
        <tr>
            <td>View Content</td>
            <td><code>ON_WEB_DETAIL</code></td>
            <td><code>ON_WEB_DETAIL</code></td>
            <td><code>IN_APP_DETAIL_UV</code></td>
        </tr>
    </tbody>
</table>

### Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads with Website and App Optimization

<table>
    <thead>
        <tr>
            <th style="width:21%">Targeting optimization mode</th>
            <th style="width:18%">Available targeting setting</th>
            <th style="width:18%">Requirement</th>
            <th style="width:22%">Parameter</th>
            <th style="width:21%">How to configure the parameter</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="10">Automatic targeting<br>(<code>targeting_optimization_mode</code><br> as <code>AUTOMATIC</code> or not specified)</td>
            <td>Audience controls<br>· Location</td>
            <td>Specify valid locations.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
            <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
            <td>Specify valid values.<br><br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610">/catalog/get/</a> and check the returned <code>country</code>.</td>
        </tr>
        <tr>
            <td>Audience controls<br>· Minimum age</td>
            <td>Any of the following options:<ul><li>18</li><li>25</li></ul></td>
            <td><code>spc_audience_age</code></td>
            <td>Any of the following values:<ul><li><code>OVER_EIGHTEEN</code> or unspecified</li><li><code>OVER_TWENTY_FIVE</code></li></ul></td>
        </tr>
        <tr>
            <td>Audience controls<br>· Languages<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>languages</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience controls<br>· Exclude audience<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>excluded_audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Age<br> (Optional)<br><br><p><span style="color:darkred"><b>Note</b></span>: Audience suggestions guide automatic targeting by choosing additional audience settings. These serve as suggestions only, and delivery to those audiences is not guaranteed.</p></td>
            <td>Enabled or disabled</td>
            <td><code>age_groups</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Gender<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>gender</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Custom audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Interests<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Purchase intention<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>purchase_intention_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Video interactions<br>&nbsp;&nbsp;· Creator interactions<br>&nbsp;&nbsp;· Hashtag interactions<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>actions</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td rowspan="25">Custom targeting<br>(<code>targeting_optimization_mode</code> <br>as <code>MANUAL</code>)</td>
            <td>Retarget<br>· Custom audience<br>(Optional when an E-commerce catalog is used)</td>
            <td>Enabled or disabled.</td>
            <td><code>shopping_ads_retargeting_type</code><br><code>shopping_ads_retargeting_actions_days</code><br><code>included_custom_actions</code><br><code>excluded_custom_actions</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Retarget<br>· Custom audience<br>&nbsp;&nbsp;· Include<br>&nbsp;&nbsp;&nbsp;&nbsp;· Target anyone in catalog or custom audiences<br>(Optional when both a Catalog Include Audience and a custom audience are specified)</td>
            <td>Enabled or disabled</td>
            <td><code>shopping_ads_retargeting_custom_audience_relation</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Demographics<br>· Location</td>
            <td>Specify a valid value.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
            <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
            <td>Specify a valid value.<br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610">/catalog/get/</a> and check the returned <code>country</code>.</td>
        </tr>
        <tr>
            <td>Demographics<br>· HFSS Product/Brand</td>
            <td>Enabled or disabled<br><ul><li>You can enable this setting when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</li></ul></td>
            <td><code>is_hfss</code></td>
            <td><code>true</code> or <code>false</code>.<ul><li>You can set it to <code>true</code> when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</li></ul></td>
        </tr>
        <tr>
            <td>Demographics<br>· LHF (Less Healthy Foods) compliance</td>
            <td>Enabled or disabled<br><ul><li>This setting must be enabled when your targeting locations include locations in the UK and HFSS Product/Brand is enabled.</li></ul></td>
            <td><code>is_lhf_compliance</code></td>
            <td><code>true</code> or <code>false</code>.<ul><li>You need to set it to <code>true</code> when your targeting locations include locations in the UK and <code>is_hfss</code> is <code>true</code>.</li></ul></td>
        </tr>
        <tr>
            <td>Demographics<br>· Age<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>age_groups</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Demographics<br>· Gender<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>gender</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Demographics<br>· Languages<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>languages</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Custom audience<br>· Include audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Custom audience<br>· Smart audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>smart_audience_enabled</code></td>
            <td><code>true</code> or <code>false</code></td>
        </tr>
        <tr>
            <td>Custom audience<br>· Exclude audience<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>excluded_audience_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Interests<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Purchase intention<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>purchase_intention_keyword_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Video interactions<br>&nbsp;&nbsp;· Creator interactions<br>&nbsp;&nbsp;· Hashtag interactions<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>actions</code></td>
            <td><code>true</code> or <code>false</code></td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Interests & behaviors<br>&nbsp;&nbsp;· Smart interests & behaviors<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>smart_interest_behavior_enabled</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Spending power<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>spending_power</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Detailed targeting<br>· Household income<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>household_income</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Operating system<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>operating_systems</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· OS versions<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>min_android_version</code><br><code>min_ios_version</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Device model<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>device_model_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Connection type<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>network_types</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Carriers<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>carrier_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Internet service provider<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>isp_ids</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Device<br>· Device price<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>device_price_ranges</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Use saved audience<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>saved_audience_id</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
    </tbody>
</table>

### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "app_config": [
        {
            "app_id": "{{app_id}}"
        },
        {
            "app_id": "{{app_id}}"
        }
    ],
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "ADD_BILLING",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_spec": {
        "location_ids": [
            "{{location_id}}"
        ]
    }
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
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "app_config": [
            {
                "app_id": "{{app_id}}"
            },
            {
                "app_id": "{{app_id}}"
            }
        ],
        "attribution_event_count": "EVERY",
        "bid_type": "BID_TYPE_CUSTOM",
        "billing_event": "OCPM",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "catalog_id": "{{catalog_id}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "engaged_view_attribution_window": "ONE_DAY",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "ADD_BILLING",
        "optimization_goal": "CONVERT",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "pixel_id": "{{pixel_id}}",
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_GLOBAL_APP_BUNDLE",
            "PLACEMENT_PANGLE"
        ],
        "product_source": "CATALOG",
        "promotion_type": "WEBSITE",
        "promotion_website_type": "UNSET",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
        "skip_learning_phase": true,
        "targeting_optimization_mode": "AUTOMATIC",
        "targeting_spec": {
            "actions": [],
            "age_groups": [
                "AGE_18_24",
                "AGE_25_34",
                "AGE_35_44",
                "AGE_45_54",
                "AGE_55_100"
            ],
            "app_targeting_type": "PROSPECT",
            "brand_safety_type": "STANDARD_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_id}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": true,
        "view_attribution_window": "ONE_DAY"
    }
}

(/code)
```

## Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522).

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
            <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code>(empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
        </tr>
        <tr>
            <td>Product source details<br>· Products</td>
            <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
            <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
            <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to specify <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to specify <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to specify a maximum of 20 available catalog products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td rowspan="2">Deeplink</td>
            <td rowspan="2">Enabled with a custom deeplink.<br>The deeplink format can be an Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</td>
            <td><code>landing_page_url_list</code></td>
            <td>Specify a deeplink in the format of an Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code> through <code>landing_page_url_list</code>.</td>
        </tr>
        <tr>
            <td><code>deeplink</code><br><code>deeplink_type</code><br><code>fallback_type</code><br><code>page_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Added URL parameters (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>utm_params</code></td>
            <td>Specify valid values or not specified.<br>- <p><span style="color:darkred"><b>Note</b></span>:  If you set <code>landing_page_url_list</code> to a URL that already includes URL parameters, you can optionally specify <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url_list</code> upon ad delivery.</td>
        </tr>
        <tr>
            <td rowspan="3">Creative assets <br>· Identity</td>
            <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities (<code>AUTH_CODE</code>,<code>TT_USER</code>,<code>BC_AUTH_TT</code>), specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
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
            <td>Creative assets <br>· Autoselect from catalog</td>
            <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Images and videos in your catalog will be used to automatically generate optimized Catalog Ads in all formats for your products.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads with Website and App Optimization">Customize the ad format combinations in Upgraded Smart+ Catalog Ads with Website and App Optimization</a>.</li></ul></li><li>Disabled<ul><li>Images and videos in your catalog will not be used to automatically generate optimized Catalog Ads in all formats for your products. The system will automatically create single video ads using your specified content.</li><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</p></li></ul></td>
            <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code><br><code>ad_format</code></td>
            <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code>, specify a valid value for <code>ad_format</code> in each <code>creative_info</code> object, and specify a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads with Website and App Optimization">Customize the ad format combinations in Upgraded Smart+ Catalog Ads with Website and App Optimization</a>.<br><p><span style="color:darkred"><b>Note</b></span>: <code>music_info</code> is required when <code>catalog_creative_toggle</code> is <code>true</code>.</li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>.<ul><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>For each creative, specify the corresponding value for <code>ad_format</code> in each <code>creative_info</code> object.</li><li>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>. </li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Add promo code or offer<br>(Optional)</td>
            <td>Disabled</td>
            <td>N/A</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Call to action</td>
            <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</p></td>
            <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
            <td><ul><li>To use dynamic call to action, specify <code>call_to_action_id</code>.</li><li>To use standard call to action, specify <code>call_to_action_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>creative_info</code> object to <code>TT_USER</code>, <code>BC_AUTH_TT</code>, or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</p></td>
        </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
        <tr>
            <td>Interactive add-ons<br>(Optional)<br>(When the ad creatives include videos or video posts)</td>
            <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul><p><span style="color:darkred"><b>Note</b></span>: By using interactive add-ons, your ad will no longer display product cards. </p></td>
            <td><code>interactive_add_on_list</code></td>
            <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></p>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Impression tracking URL for iOS<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><code>app_id</code><br><code>app_type</code><br><code>impression_tracking_url</code></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for iOS</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Click tracking URL for iOS<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><code>app_id</code><br><code>app_type</code><br><code>click_tracking_url</code></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for iOS</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Impression tracking URL for Android<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><code>app_id</code><br><code>app_type</code><br><code>impression_tracking_url</code></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for Android</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Click tracking URL for Android <br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><code>app_id</code><br><code>app_type</code><br><code>click_tracking_url</code></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for Android</li></ul></li></ul></td>
        </tr>
    </tbody>
</table>

### Customize the ad format combinations in Upgraded Smart+ Catalog Ads with Website and App Optimization
If you enable auto-selection of catalog creatives (<code>catalog_creative_toggle</code> is <code>true</code>), the system will automatically create ads using combinations of three possible formats: catalog carousel, catalog video, and single video.

The possible ad format combinations for your Upgraded Smart+ Catalog Ads with Website and App Optimization are illustrated in the following table.

<table>
    <thead>
        <tr>
            <th style="width:17%">Is the catalog carousel format available?</th>
            <th style="width:26%">Is the catalog video format available?</th>
            <th style="width:27%">Is the single video format available?</th>
            <th style="width:30%">Ad format combination for your Upgraded Smart+ Catalog Ads with Website and App Optimization</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>Catalog carousel only</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>❌</td>
            <td>✅</td>
            <td>Catalog carousel and single video</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>Catalog carousel and catalog video</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>Catalog carousel, catalog video, and single video</td>
        </tr>
    </tbody>
</table>

To ensure certain ad formats are available in Upgraded Smart+ Catalog Ads with Website and App Optimization, follow the instructions in the following table.

<table>
    <thead>
        <tr>
            <th style="width:17%">Ad format</th>
            <th style="width:26%">Ad format description</th>
            <th style="width:27%">How to ensure the ad format is available</th>
            <th style="width:30%">How to configure the parameters</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="2">catalog carousel</td>
            <td rowspan="2">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
            <td>The catalog needs to contain at least two products supported in Catalog Carousel Ads.</td>
            <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Specify a piece of music supported in Catalog Carousel Ads.</td>
            <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>catalog video</td>
            <td>Generate a dynamic video based on information from the product catalog</td>
            <td>The catalog needs to be bound to at least one video template or you need to configure a video URL for each catalog product.<br>The system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video or use the video URL for the catalog product.</td>
            <td>Ensure that at least one video template is bound to your catalog (<code>catalog_id</code>) or a video URL (<code>video_url</code>) has been configured for each catalog product.<ul><li>To ensure a video URL has been configured for each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a> and check the returned <code>video_url</code>.<ul><li>If you want to add video URLs for your catalog products, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546" target="_blank">/catalog/product/update/</a>.</li></ul></li><li>To ensure that at least one video template (video package) is bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank">/catalog/video_package/get/</a>.<ul><li>To learn about how to create video templates on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog?lang=en" target="_blank">How to create video packages in a Catalog</a>.</li><li>To retrieve video templates for your catalog via API, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank">/catalog/video_package/get/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>single video</td>
            <td>Use specified video creatives to promote your products.</td>
            <td>Specify additional videos</td>
            <td>Specify additional videos via <code>video_info</code> and cover images via <code>image_info</code>.</td>
        </tr>
    </tbody>
</table>

### Example

#### Using catalog creatives
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "CATALOG_CAROUSEL",
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "catalog_creative_toggle": true,
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_toggle": true,
            "dark_post_status": "OFF",
            "deeplink_utm_params": [],
            "identity_id": "{{identity_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [
                    {
                        "app_id": "{{app_id}}",
                        "app_type": "APP_ANDROID",
                        "click_tracking_url": "",
                        "impression_tracking_url": ""
                    },
                    {
                        "app_id": "{{app_id}}",
                        "app_type": "APP_IOS",
                        "click_tracking_url": "",
                        "impression_tracking_url": ""
                    }
                ]
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "material_name": "catalog_carousel_or_single_image",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}

(/code)
```

#### Without using catalog creatives
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "BC_AUTH_TT"
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "catalog_creative_toggle": false,
        "dark_post_status": "ON",
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_toggle": false,
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [
                    {
                        "app_id": "{{app_id}}",
                        "app_type": "APP_ANDROID",
                        "click_tracking_url": "{{click_tracking_url}}",
                        "impression_tracking_url": "{{impression_tracking_url}}"
                    },
                    {
                        "app_id": "{{app_id}}",
                        "app_type": "APP_IOS",
                        "click_tracking_url": "{{click_tracking_url}}",
                        "impression_tracking_url": "{{impression_tracking_url}}"
                    }
                ]
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "SINGLE_VIDEO",
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "material_name": "{{material_name}}",
                    "video_info": {
                        "file_name": "{{file_name}}",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}

(/code)
```


