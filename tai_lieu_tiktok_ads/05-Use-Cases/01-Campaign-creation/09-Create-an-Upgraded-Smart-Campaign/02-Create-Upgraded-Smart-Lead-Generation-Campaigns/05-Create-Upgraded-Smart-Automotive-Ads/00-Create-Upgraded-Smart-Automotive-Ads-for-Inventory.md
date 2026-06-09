---
title: "Create Upgraded Smart+ Automotive Ads for Inventory"
doc_id: 1843325008693250
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Lead Generation Campaigns / Create Upgraded Smart+ Automotive Ads / Create Upgraded Smart+ Automotive Ads for Inventory"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Upgraded Smart+ Automotive Ads for Inventory

Upgraded Smart+ Automotive Ads for Inventory are Upgraded Smart+ Automotive Ads created using an Auto-Inventory catalog.

# Prerequisite

Creating Automotive Ads for Inventory requires setting up an **Auto-Inventory catalog** in Business Center first.

To set up the catalog:

1. Create an Auto-Inventory catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961).

2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).

3. Check the product handling results.
	- For CSV feed and JSON schema, use [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
		- Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
	- For online feed, use [/catalog/feed/log/](https://business-api.tiktok.com/portal/docs?id=1740665225631810).
		- Pass in the `feed_id` obtained from Step 2. If the field `error_count` in the response is not 0, examine your online feed and return to Step 2 to reupload the product.

4. (Optional) Create an Auto-Inventory product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
<br>If you want to have products selected from a product set at the ad level, creating a product set is necessary. Otherwise, you can skip this step.

5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
<br>You can also choose `advertiser_role` that you want to assign to the members invited.

6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
<br>Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.

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
      <td>Lead generation</td>
      <td><code>objective_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
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
      <td><code>catalog_enabled</code></td>
      <td><code>true</code></td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories (Optional)</td>
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
        <td>Goal-based budget increase <br> (when Campaign budget strategy is Campaign budget and campaign budget mode is daily)</td>
        <td>Any of the following options:<ul><li>Enabled</li><li>Disabled</li></ul></td>
        <td><code>budget_auto_adjust_strategy</code></td>
        <td>When <code>budget_optimize_on</code> is <code>true</code> or not specified and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>:<ul><li> To enable the setting, use the following value:<ul><li><code>AUTO_BUDGET_INCREASE</code>. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>To disable the setting: <ul><li>Not specified</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
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
(code json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \\
--header 'Access-Token: "{{Access-Token}}"' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "LEAD_GENERATION",
    "catalog_enabled": true,
    "campaign_name": "{{campaign_name}}",
    "budget_optimize_on": true,
    "budget_mode":"BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code json)
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
        "catalog_enabled": true,
        "create_time": "{{create_time}}",
        "is_promotional_campaign": false,
        "modify_time": "{{modify_time}}",
        "objective_type": "LEAD_GENERATION",
        "operation_status": "ENABLE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```
## Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1843314887930946). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1843314887930946).

### When Optimization Location is Website


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
      <td>Specify an <b>Auto-Inventory catalog with at least one approved product</b></td>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Specify valid values.<br><br>The Auto-Inventory catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1.<br><br>To learn about how to create an Auto-Inventory catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Optimization and bidding<br>· Location</td>
      <td rowspan="2">Website<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td><code>promotion_target_type</code></td>
      <td><code>EXTERNAL_WEBSITE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, specify the same <code>promotion_target_type</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal</td>
      <td>Any of the following types:<ul><li>Conversion</li><li>Click</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>CONVERT</code></li><li><code>CLICK</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, specify the same <code>optimization_goal</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Event<br>&nbsp;&nbsp;· Data connection</td>
      <td><ul><li>When Goal is Conversion, specify a valid Pixel.</li><li>When Goal is Click, Pixel is not supported.</li></ul></td>
      <td><code>pixel_id</code></td>
      <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code>.<ul><li>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li></ul></li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Event<br>&nbsp;&nbsp;· Optimization event</td>
      <td><ul><li>Required when Goal is Conversion.</li><li>Not supported when Goal is Click.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_event</code></td>
      <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code>.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.<br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.</li></ul></li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, specify the same <code>optimization_event</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Deep funnel optimization<br>(Optional)</td>
      <td>Enabled or disabled<br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
      <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
      <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></p></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization and bidding<br>· Bid Strategy and (Optional) Target CPA</td>
      <td rowspan="2">The bid strategy can be any of the following:<ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code><br><code>bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, the <code>conversion_bid_price</code> or <code>bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>engaged_view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Lead%20generation" target="_blank">Attribution window and event count - Lead Generation</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td><ul><li>If Goal is Conversions, the billing event should be oCPM.</li><li>If Goal is Click, the billing event should be CPC.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The billing event must be the same across ad groups within the same campaign.</li></ul></p></td>
      <td><code>billing_event</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>, specify <code>billing_event</code> as <code>OCPM</code>.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, specify <code>billing_event</code> as <code>CPC</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>billing_event</code> must be the same across ad groups within the same campaign.</li></ul></p></td>
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
      <td>Budget and schedule<br>· Goal-based budget increase (when Bid Strategy is Target CPA)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start and end time" in any of the following scenarios:<ul><li>At the campaign level, a lifetime campaign budget is set.</li><li>At the ad group level, a lifetime ad group budget is set.</li></ul></p></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li></ul></p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td>Any of the following modes:<ul><li>Automatic targeting<ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li>Custom targeting<ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Placements and brand safety<br>· Placement</td>
      <td rowspan="2">TikTok (including search results)</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>["PLACEMENT_TIKTOK"]</code></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search feed</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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


#### Example
Request
```xcodeblock
(code json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \\
--header 'Access-Token: "{{Access-Token}}"' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "FORM",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_optimization_mode": "AUTOMATIC",
    "targeting_spec": {
        "location_ids": [
            "6252001"
        ]
    },
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ]
}'
(/code)
```

Response
```xcodeblock
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
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
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "FORM",
        "optimization_goal": "CONVERT",
        "pacing": "PACING_MODE_SMOOTH",
        "pixel_id": "{{pixel_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "promotion_type": "LEAD_GENERATION",
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
            "brand_safety_type": "STANDARD_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "6252001"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY"
    }
}
(/code)
```

### When Optimization Location is Instant Form


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
      <td>Specify an <b>Auto-Inventory catalog with at least one approved product</b></td>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Specify valid values.<br><br>The Auto-Inventory catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1.<br><br>To learn about how to create an Auto-Inventory catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Optimization and bidding<br>· Location</td>
      <td rowspan="2">Instant Form<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td><code>promotion_target_type</code></td>
      <td><code>INSTANT_PAGE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, specify the same <code>promotion_target_type</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal</td>
      <td>Form submission<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_goal</code></td>
      <td><code>LEAD_GENERATION</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Deep funnel optimization<br>(Optional)</td>
      <td>Enabled or disabled<br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
      <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
      <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization and bidding<br>· Bid Strategy and (Optional) Target CPA</td>
      <td rowspan="2">The bid strategy can be any of the following:<ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified at the campaign level, the <code>conversion_bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td><code>bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>engaged_view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Lead%20generation" target="_blank">Attribution window and event count - Lead Generation</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Delivery Type</td>
      <td>Standard (default)</td>
      <td>N/A</td>
      <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Budget</td>
      <td><ul><li>When you select an automatic campaign budget mode:<ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul></li><li>When you select a custom ad group budget strategy, you can use any of the following ad group budget modes:<ul><li>Lifetime</li><li>Daily</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</li></ul></td>
      <td><code>budget_mode</code><br><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code>are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Automatic budget increase (when Bid Strategy is Target CPA)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable automatic budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><ul><li><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start and end time" in any of the following scenarios:<ul><li>At the campaign level, a lifetime campaign budget is set.</li><li>At the ad group level, a lifetime ad group budget is set.</li></ul></p></li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td>Any of the following modes:<ul><li>Automatic targeting<ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li>Custom targeting<ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Placements and brand safety<br>· Placement</td>
      <td rowspan="2">TikTok (including search results)</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>["PLACEMENT_TIKTOK"]</code></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search feed</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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

#### Example
Request
```xcodeblock
(code json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \\
--header 'Access-Token: "{{Access-Token}}"' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "INSTANT_PAGE",
    "optimization_goal": "LEAD_GENERATION",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_optimization_mode": "AUTOMATIC",
    "targeting_spec": {
        "location_ids": [
            "6252001"
        ]
    },
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ]
}'
(/code)
```

Response
```xcodeblock
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "attribution_event_count": "ONCE",
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
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "FORM",
        "optimization_goal": "LEAD_GENERATION",
        "pacing": "PACING_MODE_SMOOTH",
        "pixel_id": "{{pixel_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_target_type": "INSTANT_PAGE",
        "promotion_type": "LEAD_GENERATION",
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
            "brand_safety_type": "STANDARD_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "6252001"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY"
    }
}
(/code)
```
<br>

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
      <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to specify <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to specify <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to specify a maximum of 20 available products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each Auto-Inventory product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Creative assets<br>· Identity</td>
      <td rowspan="3">Custom User (custom identity)</td>
      <td><code>identity_type</code></td>
      <td><code>CUSTOMIZED_USER</code><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li></ul></p></td>
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
      <td>Destination</td>
      <td><ul><li>When at the ad group level, Optimization Location is Website:<ul><li><b>Website URL</b>: Your own URL to direct users to browse inventory on your website.</li></ul></li><li>When at the ad group level, Optimization Location is Instant Form:<ul><li><b>Instant Form</b>: An Instant Form that opens instantly when someone interacts with your ad.</li></ul></li></ul></td>
      <td><code>landing_page_url_list</code><br><code>page_list</code></td>
      <td><ul><li>When at the ad group level <code>promotion_target_type</code> is <code>EXTERNAL_WEBSITE</code>:<ul><li>Specify your own URL via <code>landing_page_url_list</code>.</li><li>Do not specify <code>page_list</code>.</li></ul></li><li>When at the ad group level <code>promotion_target_type</code> is <code>INSTANT_PAGE</code>:<ul><li>Specify the ID of an Instant Form through <code>page_list</code>.<ul><li>To obtain the ID of an Instant Form, set <code>business_type</code> to <code>LEAD_GEN</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>.</li><li>To create an Instant Form, use <a href="https://business-api.tiktok.com/portal/docs?id=1740307202170881" target="_blank">Instant Page Editor SDK</a> and set <code>businessType</code> to <code>1</code>.</li></ul></li><li>Do not specify <code>landing_page_url_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>UTM parameters<br>(Optional for Website URL)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>When<code>landing_page_url_list</code> is specified:<ul><li>Specify valid values or not specified.</li></ul>If you set <code>landing_page_url_list</code> to a URL that already includes URL parameters, you can optionally specify <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url_list</code> upon ad delivery.</td>
    </tr>
    <tr>
      <td rowspan="2">Creative assets<br>· Create catalog carousel from your catalog</td>
      <td rowspan="2">Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Images and videos in your catalog will be used to automatically generate optimized Catalog Ads in all formats for your products.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Automotive Ads">Customize the ad format combinations in Upgraded Smart+ Automotive Ads</a>.</li></ul></li><li>Disabled<ul><li>Images and videos in your catalog will not be used to automatically generate optimized Catalog Ads in all formats for your products.The system will automatically create single video ads using your specified content.</li><li>Requirements:<ul><li>When using a non-Spark Ad identity:<ul><li>Specify 1-50 videos with cover images</li><li>Specify 1-5 ad texts</li></ul></li></ul></li><li><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</p></li></ul></li></ul></td>
      <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>ad_format</code></td>
      <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code>, specify a valid value for <code>ad_format</code> in each <code>creative_info</code> object, and specify an end card image via <code>image_info</code> and a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Automotive Ads">Customize the ad format combinations in Upgraded Smart+ Automotive Ads</a>.</li><li><p><span style="color:darkred"><b>Note</b></span>: When <code>catalog_creative_toggle</code> is <code>true</code>, both <code>image_info</code> and <code>music_info</code> are required.</p></li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>, and specify the following:<ul><li>one to 50 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>ad_text_list</code>).</li><li>set <code>ad_format</code> to <code>SINGLE_VIDEO</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td><code>tiktok_item_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Creative assets<br>· Product information<br>(Optional)</td>
      <td><ul><li>When the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Disabled or enabled with up to two product attributes to display alongside the vehicle title in your ads:<ul><li>Dealer name</li><li>Make</li><li>Model</li><li>Year</li><li>Mileage</li><li>Exterior color</li><li>Trim</li><li>City</li><li>State of vehicle</li><li>Price</li><li>Sale price</li></ul>The selected product details will be automatically added from your catalog to your ads for best results.<br>By default, only the title of each product will be displayed in your ads</li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>product_display_field_list</code></td>
      <td><ul><li>When <code>catalog_creative_toggle</code> is set to <code>true</code>:<ul><li>Not specified or specify up to two attributes from the following values:<ul><li><code>DEALER_NAME</code></li><li><code>MAKE</code></li><li><code>MODEL</code></li><li><code>YEAR</code></li><li><code>MILEAGE</code></li><li><code>EXTERIOR_COLOR</code></li><li><code>TRIM</code></li><li><code>ADDRESS_CITY</code></li><li><code>VEHICLE_STATE</code></li><li><code>PRICE</code></li><li><code>SALE_PRICE</code></li></ul>The title of your vehicle will be automatically displayed alongside the specified attributes in your ads.<br>For example, if you set this field to <code>["MAKE","PRICE"]</code>, the title, make and price of your vehicle will be displayed in your ads.<br>By default, only the title of each product will be displayed in your ads.</li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Creative assets<br>· End card</td>
      <td><ul><li>When the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Enabled. Select the last photo to appear in the carousel to encourage people to browse your full inventory.</li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>image_info</code></td>
      <td><ul><li>When <code>catalog_creative_toggle</code> is set to <code>true</code>:<ul><li>Specify one end card. The end card will be displayed at the end of the carousel.</li><li>To get image IDs, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739067433456642" target="_blank">/file/image/ad/upload/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a>.</li><li>The image should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: below 50 MB.</li></ul></li><li>The value of the parameter <code>is_carousel_usable</code> returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a> for the image is <code>true</code>.</li></ul></li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Creative assets<br>· Call to action for end card</td>
      <td><ul><li>When the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Enabled with one of the following options:<ul><li>Search inventory (Recommended)</li><li>Learn more</li><li>Shop now</li><li>Sign up</li><li>Contact us</li><li>Book now</li><li>Read more</li><li>View now</li><li>Order now</li></ul></li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>end_card_cta</code></td>
      <td><ul><li>When <code>catalog_creative_toggle</code> is set to <code>true</code>:<ul><li>Enabled with any of the following values:<ul><li><code>SEARCH_INVENTORY</code> (Recommended)</li><li><code>LEARN_MORE</code></li><li><code>SHOP_NOW</code></li><li><code>SIGN_UP</code></li><li><code>CONTACT_US</code></li><li><code>BOOK_NOW</code></li><li><code>READ_MORE</code></li><li><code>VIEW_MORE</code></li><li><code>ORDER_NOW</code></li></ul></li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Enabled with a standard call to action</td>
      <td><code>call_to_action_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Interactive add-ons</td>
      <td>Enabled with an Inventory Card<br><br>For Automotive Ads for Inventory with Single Video format, auto-generated Vehicle Cards will be shown after two seconds of video play. If the audience continues swiping through the Vehicle Cards, the Inventory Card will be displayed at the end.<br><br>The Vehicle Cards showcase the following attributes of the vehicle as defined in the catalog, and a CTA button, which will direct users to the third-party landing page.<ul><li>Main image</li><li>Title</li><li>State of the vehicle</li><li>Mileage</li><li>Price</li></ul><br>The Inventory Card includes:<ul><li>A customized image</li><li>A customized description</li><li>Estimated catalog size</li><li>CTA button "Search Inventory", which directs the audience to an auto-generated Instant Product Page</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Specify the ID of an Inventory Card portfolio.<br>To get the Inventory Card portfolio ID (<code>creative_portfolio_id</code>), see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Inventory%20Card" target="_blank">Cards</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: You can update the Inventory Card portfolio in your Automotive Ads for Inventory to a different Inventory Card portfolio.</p><br>The Vehicle Cards showcase a CTA button and the following attributes of the vehicle:<ul><li><code>image_url</code></li><li><code>title</code></li><li><code>state_of_vehicle</code></li><li><code>mileage</code></li><li><code>price</code></li></ul>To get the attributes of a vehicle, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.<br><br>The Inventory Card includes a CTA button, estimated catalog size, and the following attributes of the Inventory Card:<ul><li><code>image_id</code></li><li><code>description</code></li></ul>To get the details of an Inventory Card, use <a href="https://business-api.tiktok.com/portal/docs?id=1766324010279938" target="_blank">/creative/portfolio/list/</a>.</td>
    </tr>
    <tr>
      <td>This ad contains AI-generated content<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>aigc_disclosure_type</code></td>
      <td><code>SELF_DISCLOSURE</code> or <code>NOT_DECLARED</code></td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;· App events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_app_id</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Impression tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>impression_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Click tracking URL<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>click_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>
<br>


### Customize the ad format combinations in Upgraded Smart+ Automotive Ads
If you enable auto-selection of catalog creatives (<code>catalog_creative_toggle</code> is <code>true</code>) in your Upgraded Smart+ Automotive Ads, the system will automatically create ads using combinations of two possible formats: catalog carousel, and single video.

The possible ad format combinations for your Upgraded Smart+ Automotive Ads are illustrated in the following table.


<table>
  <thead>
    <tr>
      <th style="width:20%">Is the catalog carousel format available?</th>
      <th style="width:20%">Is the single video format available?</th>
      <th style="width:60%">Ad format combination for your Upgraded Smart+ Automotive Ads</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>✅</td>
      <td>❌</td>
      <td>Catalog carousel only</td>
    </tr>
    <tr>
      <td>✅</td>
      <td>✅</td>
      <td>Catalog carousel and single video</td>
    </tr>
  </tbody>
</table>


To ensure certain ad formats are available in Upgraded Smart+ Automotive Ads, follow the instructions in the following table.

<table>
  <thead>
    <tr>
      <th style="width:20%">Ad format</th>
      <th style="width:20%">Ad format description</th>
      <th style="width:30%">How to ensure the ad format is available</th>
      <th style="width:30%">How to configure the parameters</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="3">catalog carousel</td>
      <td rowspan="3">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
      <td>The catalog needs to contain at least two products supported in Catalog Carousel Ads.</td>
      <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li> Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Specify a piece of music supported in Catalog Carousel Ads.</td>
      <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://business-api.tiktok.com/portal/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Specify an end card image</td>
      <td>Pass one end card image that is valid for use in Automotive Catalog Carousel Ads via <code>image_info</code>.<br><ul><li>To get image IDs, use <a href="https://business-api.tiktok.com/portal/docs?id=1739067433456642" target="_blank">/file/image/ad/upload/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a>.</li><li>The image should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: below 50 MB.</li></ul></li><li>The value of the parameter <code>is_carousel_usable</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a> for the image is <code>true</code>.</li></ul></li></ul></td>
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
#### When Destination is Website URL
Request
```xcodeblock
(code json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \\
--header 'Access-Token: "{{Access-Token}}"' \\
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
                "ad_format":"SINGLE_VIDEO",
                "image_info": [
                    {
                        "web_uri": "{{image_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                }
            }
        },
        {
            "creative_info": {
                "ad_format":"CATALOG_CAROUSEL",
                "image_info": [
                    {
                        "web_uri": "{{image_uri}}"
                    }
                ],
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "CHECK_AVAILABILITY"
        }
    ],
    "interactive_add_on_list": [
        {
            "card_id": "{{card_id}}"
        }
    ],
    "ad_configuration": {
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "product_specific_type": "ALL",
        "catalog_creative_toggle": true,
        "end_card_cta": "ORDER_NOW"
    }
}'
(/code)
```

Response
```xcodeblock
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_configuration": {
            "catalog_creative_toggle": true,
            "end_card_cta": "ORDER_NOW",
            "identity_id": "{{identity_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "product_specific_type": "ALL",
            "tracking_info": {},
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "call_to_action_list": [
            {
                "call_to_action": "CHECK_AVAILABILITY"
            }
        ],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "image_info": [
                        {
                            "web_uri": "{{image_uri}}"
                        }
                    ],
                    "material_name": "",
                    "video_info": {
                        "file_name": "",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "image_info": [
                        {
                            "web_uri": "{{image_uri}}"
                        }
                    ],
                    "material_name": "catalog_carousel",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "deeplink_list": [],
        "interactive_add_on_list": [
            {
                "card_id": "{{card_id}}"
            }
        ],
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

#### When Destination is Instant Form
Request
```xcodeblock
(code json)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \\
--header 'Access-Token: "{{Access-Token}}"' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "page_list": [
        {
            "page_id": "{{page_id}}"
        }
    ],
    "creative_list": [
        {
            "creative_info": {
                "ad_format":"SINGLE_VIDEO",
                "image_info": [
                    {
                        "web_uri": "{{image_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                }
            }
        },
        {
            "creative_info": {
                "ad_format":"CATALOG_CAROUSEL",
                "image_info": [
                    {
                        "web_uri": "{{image_uri}}"
                    }
                ],
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "CHECK_AVAILABILITY"
        }
    ],
    "interactive_add_on_list": [
        {
            "card_id": "{{card_id}}"
        }
    ],
    "ad_configuration": {
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "product_specific_type": "ALL",
        "catalog_creative_toggle": true,
        "end_card_cta": "ORDER_NOW"
    }
}'
(/code)
```

Response
```xcodeblock
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_configuration": {
            "catalog_creative_toggle": true,
            "end_card_cta": "ORDER_NOW",
            "identity_id": "{{identity_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "product_specific_type": "ALL",
            "tracking_info": {},
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "call_to_action_list": [
            {
                "call_to_action": "CHECK_AVAILABILITY"
            }
        ],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "image_info": [
                        {
                            "web_uri": "{{image_uri}}"
                        }
                    ],
                    "material_name": "",
                    "video_info": {
                        "file_name": "",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "image_info": [
                        {
                            "web_uri": "{{image_uri}}"
                        }
                    ],
                    "material_name": "catalog_carousel",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "deeplink_list": [],
        "interactive_add_on_list": [
            {
                "card_id": "{{card_id}}"
            }
        ],
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [
            {
                "page_id": "{{page_id}}"
            }
        ],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```

