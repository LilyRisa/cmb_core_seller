---
title: "Create Upgraded Smart+ Non-Catalog Ads with Website and App Optimization"
doc_id: 1855007163018689
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Ads with Website and App Optimization / Create Upgraded Smart+ Non-Catalog Ads with Website and App Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Upgraded Smart+ Non-Catalog Ads with Website and App Optimization

Upgraded Smart+ Non-Catalog Ads with Website and App Optimization are Upgraded Smart+ Sales Campaigns without using catalog products to drive sales on both your website and your app. For such campaigns, you need to set `objective_type` to `WEB_CONVERSIONS`, `sales_destination` to `WEB_AND_APP`, and `catalog_enabled` to `false` at the campaign level.

## Steps
### Create a campaign
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
            <td><b>Disabled</b></td>
            <td><code>catalog_enabled </code></td>
            <td><code>false</code></td>
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

#### Example
**Creating a CBO campaign**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "WEB_AND_APP",
    "catalog_enabled": false,
    "campaign_name": "{{campaign_name}}",
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
        "campaign_type": "REGULAR_CAMPAIGN",
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
### Create an ad group
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
            <td>Any of the following types:<ul><li>Conversion</li><li>Value</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>optimization_goal</code></td>
            <td>Any of the following values:<ul><li><code>CONVERT</code></li><li><code>VALUE</code></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Data connection</td>
            <td>Specify a valid Pixel</td>
            <td><code>pixel_id</code></td>
            <td>Specify a valid value.<br><br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Optimization event</td>
            <td>Specify a valid optimization event<ul><li>When optimization goal is Value, specify the optimization event as Complete Payment.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>optimization_event</code></td>
            <td>Specify a valid value.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.</p><br>To learn about the supported event types, see  <a href="#item-link-List of values for optimization_event for Upgraded Smart+ Ads with Website and App Optimization">List of values for <code>optimization_event</code> for Upgraded Smart+ Ads with Website and App Optimization</a>.<br><br>You need to ensure the specified optimization event is active for both the Pixel and the app or apps.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li><li>To obtain the active optimization events for an app, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_event</code>for ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Bid Strategy, (Optional) Target CPA, <br>and (Optional) Target ROAS</td>
            <td><ul><li>If Goal is Conversion, the strategy can only be Maximum Delivery or Cost Cap.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
            <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code>  (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li> When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution window<br>&nbsp;&nbsp;· Click-through window</td>
            <td>Any of the following types:<ul><li>1-day click</li><li>7-day click</li><li>14-day click</li><li>28-day click</li></ul></td>
            <td><code>click_attribution_window</code></td>
            <td>Any of the following values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution window<br>&nbsp;&nbsp; Engaged view-through window</td>
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
            <td><ul><li>If Goal is Click, the billing event should be CPC.</li><li>If Goal is Install, In-app event, or Value, the billing event should be oCPM.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</li></ul></p></td>
            <td><code>billing_event</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>,  or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code> for ad groups within the same campaign.</p></td>
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
            <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
            <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
            <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></p></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Dayparting (Optional)</td>
            <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
            <td><code>dayparting</code></td>
            <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
        </tr>
        <tr>
            <td>Audience targeting optimization mode</td>
            <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
            <td><code>targeting_optimization_mode</code></td>
            <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
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

#### List of values for `optimization_event` for Upgraded Smart+ Ads with Website and App Optimization
The following tables list the events supported for Upgraded Smart+ Ads with Website and App Optimization.

<table>
    <thead>
        <tr>
            <th style="width:20%">Event name</th>
            <th style="width:25%"><code>optimization_event</code> value</th>
            <th style="width:25%">Corresponding Pixel event<br>(<code>optimization_event</code>)</th>
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
            <td>Initiate Checkout/Checkout</td>
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

#### Example
Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
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
            "{{location_ids}}"
        ]
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
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
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
        "product_source": "UNSET",
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
            "category_exclusion_ids": [
                "{{category_exclusion_ids}}",
                "{{category_exclusion_ids}}",
                "{{category_exclusion_ids}}"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
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
### Create an ad
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
            <td>Specify valid values or not specified.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>landing_page_url_list</code> to a URL that already includes URL parameters, you can optionally specify <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url_list</code> upon ad delivery.</td>
        </tr>
        <tr>
            <td rowspan="3">Creative assets <br>· Identity</td>
            <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities (<code>AUTH_CODE</code>,<code>TT_USER</code>,<code>BC_AUTH_TT</code>), specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
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
            <td rowspan="2">Creative assets<br>· Ad creatives & Ad text</td>
            <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
            <td><code>ad_format</code></td>
            <td>Specify a valid value</td>
        </tr>
        <tr>
            <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
            <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
        </tr>
        <tr>
            <td>Add promo code or offer<br>(Optional)</td>
            <td>Disabled</td>
            <td>N/A</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Call to action</td>
            <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</p></td>
            <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
            <td><ul><li>To use dynamic call to action, specify <code>call_to_action_id</code>.</li><li>To use standard call to action, specify <code>call_to_action_list</code>.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>creative_info</code> object to <code>TT_USER</code>, <code>BC_AUTH_TT</code>, or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</p></td>
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
            <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></p><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Impression tracking URL for iOS<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><ul><li><code>app_id</code></li><li><code>app_type</code></li><li><code>impression_tracking_url</code></li></ul></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for iOS</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Click tracking URL for iOS<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><ul><li><code>app_id</code></li><li><code>app_type</code></li><li><code>click_tracking_url</code></li></ul></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for iOS</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Impression tracking URL for Android<br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><ul><li><code>app_id</code></li><li><code>app_type</code></li><li><code>impression_tracking_url</code></li></ul></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for Android</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Third-party tracking settings<br>· Click tracking URL for Android <br>(Optional)</td>
            <td>Disabled or enabled</td>
            <td><ul><li><code>app_id</code></li><li><code>app_type</code></li><li><code>click_tracking_url</code></li></ul></td>
            <td>Any of the following configurations:<ul><li>Not specified </li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for Android</li></ul></li></ul></td>
        </tr>
    </tbody>
</table>

#### Example
Request
```xcodeblock
(code http)
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
        "dark_post_status": "ON",
        "call_to_action_id": "{{call_to_action_id}}"
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
            "call_to_action_id": "{{call_to_action_id}}",
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
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
                    "ad_format": "SINGLE_VIDEO",
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "material_name": "",
                    "video_info": {
                        "file_name": "",
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

