---
title: "Create a regular Upgraded Smart+ Web Campaign"
doc_id: 1847302878676994
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Web Campaigns / Create a regular Upgraded Smart+ Web Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a regular Upgraded Smart+ Web Campaign

A regular Upgraded Smart+ Web Campaign is an Upgraded Smart+ Web Campaign that doesn't use catalog products to deliver ads. 

For such campaigns, you can set the parameter `catalog_enabled` to `false` or leave `catalog_enabled` unspecified at the campaign level.

# Steps

## Create a campaign

Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
    <thead>
        <tr>
            <th style="width:17%">Setting</th>
            <th style="width:25%">Requirement</th>
            <th style="width:28%">Parameter</th>
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
        <td>Website</td>
        <td><code>sales_destination</code></td>
        <td><code>WEBSITE</code></td>
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
        <td><strong>Disabled</strong><br><br>To use a catalog in your Upgraded Smart+ Web Campaign, see the following sections:<ul><li>For using an E-commerce catalog: <a href="https://business-api.tiktok.com/portal/docs?id=1847302895272962" target="_blank">Create Upgraded Smart+ E-commerce Catalog Ads</a>.</li><li>For using a travel (hotel, flight, or destination) catalog: <a href="https://business-api.tiktok.com/portal/docs?id=1847302908327938" target="_blank">Create Upgraded Smart+ Travel Ads</a>.</li><li>For using an entertainment catalog: <a href="https://business-api.tiktok.com/portal/docs?id=1847302920924306" target="_blank">Create Upgraded Smart+ Streaming Ads</a>.</li><li>For using a mini series catalog: <a href="https://business-api.tiktok.com/portal/docs?id=1847302935967746" target="_blank">Create Upgraded Smart+ Mini Series Catalog Ads</a>.</li></ul></td>
        <td><code>catalog_enabled</code></td>
        <td><code>false</code> or not specified</td>
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
      <td>Campaign budget strategy</td>
      <td>Any of the following types:<ul><li><strong>Automatic campaign budget</strong>: Your budget will be automatically allocated and optimized across ad groups. All ad groups must share the same budget and optimization settings.</li><li><strong>Custom ad group budget</strong>: Precisely control spend for each ad group, using different budget and optimization settings for each.</li></ul></td>
      <td><code>budget_optimize_on</code></td>
      <td><ul><li>To set an automatic campaign budget, set this field to <code>true</code> or leave it unspecified.</li><li>To set a custom ad group budget, set this field to <code>false</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Campaign budget mode</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify any of the following modes:<ul><li>Daily</li><li>Lifetime</li></ul></li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget_mode</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, use any of the following values :<ul><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
	<tr>
      <td>Automatic budget increase (when Campaign budget strategy is Automatic campaign budget and campaign budget mode is daily)</td>
      <td>Any of the following options:<ul><li>Enabled</li><li>Disabled</li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>When <code>budget_optimize_on</code> is <code>true</code> or not specified and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>:<ul><li> To enable the setting, use the following value:<ul><li><code>AUTO_BUDGET_INCREASE</code>. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>To disable the setting: <ul><li>Not specified</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Campaign budget</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify a valid budget.</li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, specify a valid value.</li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    </tbody>
</table>

### Example

**Creating a CBO campaign**

Request

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "WEBSITE",
    "campaign_name": "{{campaign_name}}",
    "budget": {{budget}}
}'
(/code)
```

Response

```xcodeblock
(code http)
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
        "campaign_name": "s++_campaign_web_non_catalog_0723",
        "campaign_type": "REGULAR_CAMPAIGN",
        "create_time": "{{create_time}}",
        "modify_time": "{{modify_time}}",
        "objective_type": "WEB_CONVERSIONS",
        "operation_status": "ENABLE",
        "sales_destination": "WEBSITE",
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
            <td rowspan="2">Optimization and bidding<br>· Location</td>
            <td rowspan="2">Website</td>
            <td><code>promotion_type</code></td>
            <td><code>WEBSITE</code></td>
        </tr>
        <tr>
            <td><code>promotion_target_type</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Goal </td>
            <td>Any of the following types:<ul><li>Click</li><li>Conversion</li><li>Landing page view</li><li>Value</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>optimization_goal</code></td>
            <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERT</code></li><li><code>TRAFFIC_LANDING_PAGE_VIEW</code></li><li><code>VALUE</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Event <br>&nbsp;&nbsp;· Data connection</td>
            <td><ul><li>When Goal is Conversion or Value, specify a valid Pixel.</li><li>When Goal is Click or Landing page view, Pixel is not supported.</li></ul></td>
            <td><code>pixel_id</code></td>
            <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.</li><ul><li>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li></ul><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Event <br>&nbsp;&nbsp;·· Optimization event</td>
            <td><ul><li>Required when Goal is Conversion or Value.</li><ul><li>When Goal is Value, specify the optimization event as Complete Payment.</li><li>When Goal is Conversion, specify any of the following:</li><ul><li>Standard Event</li><li>Custom Conversion</li></ul></ul><li>Not supported when Goal is Click or Landing page view.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</td>
            <td><code>optimization_event</code><br><code>custom_conversion_id</code></td>
            <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.</li><ul><li>When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.</li><li>When <code>optimization_goal</code> is set to <code>CONVERT</code>, specify any of the following:</li><ul><li>A Standard Event through <code>optimization_event</code> .<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>. <br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.</li></ul></li><li>A Custom Conversion along with the associated conversion event through <code>custom_conversion_id</code> and <code>optimization_event</code>.<ul><li>To obtain the list of Custom Conversions associated with a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1842225174460673" target="_blank">/custom_conversion/list/</a>.<ul><li>To confirm the eligibility of the Custom Conversion for ad group creation:<ul><li>Ensure the returned <code>optimization_event</code> matches the <code>optimization_event</code> specified during ad group creation. </li><li>Ensure the <code>activity_status</code> is <code>NO_RECENT_ACTIVITY</code> or <code>ACTIVE</code>.</li></ul></li></ul></li></ul></li></ul></li></ul></li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code>.</li><li>Can be omitted when <code>optimization_goal</code> is set to <code>TRAFFIC_LANDING_PAGE_VIEW</code>. Then <code>optimization_event</code> will default to <code>LANDING_PAGE_VIEW</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code> and <code>custom_conversion_id</code>, if specified, must be the same for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Bid Strategy, <br>(Optional) Target CPA, <br>and (Optional) Target ROAS</td>
            <td><ul><li>If Goal is Click, Conversion, or Landing page view, the strategy can be Maximum Delivery or Cost Cap.</li><ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.</li><ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></td>
            <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, <code>CONVERT</code>, or <code>TRAFFIC_LANDING_PAGE_VIEW</code>:</li><ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not specify<code>deep_bid_type</code> and <code>roas_bid</code>.</li><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li></ul></ul><li>If <code>optimization_goal</code> is <code>VALUE</code>, set <code>deep_bid_type</code> to <code>VO_HIGHEST_VALUE</code> or <code>VO_MIN_ROAS</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify<code>conversion_bid_price</code> and <code>bid_price</code>.</li><ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:</li><ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:</li><ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution settings</td>
            <td>Select from the supported attribution setting options for the specific scenario.</td>
            <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>engaged_view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
            <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Website%20conversions" target="_blank">Attribution window and event count - Website conversions</a>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Billing event</td>
            <td><ul><li>If Goal is Click, the billing event should be CPC.</li><li>If Goal is Conversion, Landing page view, or Value, the billing event should be oCPM.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</td>
            <td><code>billing_event</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERT</code>, <code>TRAFFIC_LANDING_PAGE_VIEW</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Delivery Type</td>
            <td>Standard (default)</td>
            <td>N/A</td>
            <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Budget</td>
            <td><ul><li>When you select a campaign budget mode:</li><ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul><li>When you select an ad group budget strategy, you can use any of the following ad group budget modes:</li><ul><li>Lifetime</li><li>Daily</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</td>
            <td><code>budget_mode</code><br><code>budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code>are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> at the campaign level:</li><ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</td>
        </tr>
	    <tr>
            <td>Budget and schedule <br>· Automatic budget increase (when Goal is Value)</td>
            <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Goal is Value</li><li>Bid Strategy is Target ROAS</li><li>Time window of the bid strategy is Day 0 ROAS</li></ul></li></ul></td>
            <td><code>budget_auto_adjust_strategy</code></td>
            <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable automatic budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>optimization_goal</code> is <code>VALUE</code>.</li><li><code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>.</li><li><code>vbo_window</code> is <code>ZERO_DAY</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
            <td><ul><li>When you use a lifetime campaign budget, you can enable or disable this setting.</li><li>When you use a daily campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
            <td><code>min_budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
        </tr>
    <tr>
        <td>Budget and schedule<br>· Schedule</td>
        <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></td>
        <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
        <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios: <ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
    </tr>
    <tr>
        <td>Budget and schedule<br>· Dayparting (Optional)</td>
        <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
        <td><code>dayparting</code></td>
        <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
        <td>Audience targeting optimization mode</td>
        <td>Any of the following modes:<ul><li><strong>Automatic targeting</strong></li><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul><li><strong>Custom targeting</strong></li><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
        <td><code>targeting_optimization_mode</code></td>
        <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
    </tr>
    <tr>
        <td>Placements and brand safety<br>· Placement</td>
        <td>Any of the following types:<ul><li><strong>Automatic Placement</strong></li><li><strong>Select specific placements</strong>:</li><ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></ul></td>
        <td><code>placement_type</code><br><code>placements</code></td>
        <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.</li><ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:</li><ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></ul></td>
    </tr>
    <tr>
        <td>Placements and brand safety<br>· Brand safety and suitability</td>
        <td>These settings only apply to <strong>TikTok in-feed</strong> and <strong>search feed</strong> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
        <td>N/A</td>
        <td>N/A<br><br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
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
</table>

### Example

Request

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "WEBSITE",
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
            "1562822"
        ]
    }
}'
(/code)
```

Response

```xcodeblock
(code http)
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
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "modify_time": "{{modify_time}}",
        "optimization_event": "ADD_BILLING",
        "optimization_goal": "CONVERT",
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
            "age_groups": [
                "AGE_18_24",
                "AGE_25_34",
                "AGE_35_44",
                "AGE_45_54",
                "AGE_55_100"
            ],
            "brand_safety_type": "EXPANDED_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "1562822"
            ],
            "spc_audience_age": "OVER_EIGHTEEN"
        },
        "video_download_disabled": false,
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
            <td rowspan="2">Destination URL</td>
            <td rowspan="2">Enabled</td>
            <td><code>landing_page_url_list</code></td>
            <td>Provide one URL of the landing page or website you want to promote. The link should match the data connection you specified in the ad group.</td>
        </tr>
        <tr>
            <td><code>page_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>UTM parameters<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>utm_params</code></td>
            <td>Specify valid values or not specified.<br><br>If you set <code>landing_page_url_list</code> to a URL that already includes URL parameters, you can optionally specify <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url_list</code> upon ad delivery.</td>
        </tr>
        <tr>
            <td rowspan="3">Creative assets <br>· Identity</td>
            <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></td>
        </tr>
        <tr>
            <td><code>identity_id</code></td>
            <td>Specify a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
        </tr>
        <tr>
            <td><code>identity_authorized_bc_id</code></td>
            <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
        </tr>
        <tr>
            <td rowspan="2">Creative assets <br>· Ad creatives & Ad text</td>
            <td rowspan="2">For a single ad, you can include one to <strong>50 ad creatives</strong> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
            <td><code>ad_format</code></td>
            <td>Specify a valid value</td>
        </tr>
        <tr>
            <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
            <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
        </tr>
        <tr>
            <td>Add product information<br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>product_info</code></td>
            <td>Specify a valid value or not specified.<br><br>To learn about the specific parameters within this field that you can use for the scenario, see <a href="https://business-api.tiktok.com/portal/docs/available-product-information-settings-in-upgraded-smart-campaigns/v1.3" target="_blank">Available product information settings in Upgraded Smart+ Campaigns</a>.</td>
        </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
        <tr>
            <td>Call to action</td>
            <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</td>
            <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
            <td><ul><li>To use dynamic call to action, specify <code>call_to_action_id</code>.</li><li>To use standard call to action, specify <code>call_to_action_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>creative_info</code> object to <code>TT_USER</code>, <code>BC_AUTH_TT</code>, or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</td>
        </tr>
    <tr>
      <td>Interactive add-ons<br>(Optional)<br>(When the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
  <tr>
    <td rowspan="2">Tracking <br>· Third-party tracking settings <br>&nbsp;&nbsp;· Track viewability with third-party partner <br> (Optional)</td>
    <td rowspan="2">Disable or enabled with any of the following partners:<ul><li>DoubleVerify</li><li>IAS with IAS wrapped tag</li><li>Zefr</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you configure both the "Track viewability with third-party partner" and "Measure brand safety with third-party partner" settings:<ul><li>If you set either of these two settings to IAS, you need to set both to IAS, and specify the same IAS wrapped tag.</li><li>If you set either of these two settings to Zefr, you need to set both to Zefr.</li></ul></td>
    <td><code>viewability_postbid_partner</code></td>
    <td>Not specified or specify any of the following values:<ul><li><code>DOUBLE_VERIFY</code></li><li><code>IAS</code></li><li><code>ZEFR</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you configure both <code>brand_safety_postbid_partner</code> and <code>viewability_postbid_partner</code>:<ul><li>If you set either of these two fields to <code>IAS</code>, you need to set both to <code>IAS</code>, and assign the same value (the wrapped VAST URL that you obtain from the measurement partner IAS) to <code>brand_safety_vast_url</code> and <code>viewability_vast_url</code>.</li><li>If you set either of these two fields to <code>ZEFR</code>, you need to set both to <code>ZEFR</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>viewability_vast_url</code></td>
    <td>Specify a valid value when <code>viewability_postbid_partner</code> is <code>IAS</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you set both <code>brand_safety_postbid_partner</code> and <code>viewability_postbid_partner</code> to <code>IAS</code>, <code>brand_safety_vast_url</code> should be the same as <code>viewability_vast_url</code>.</td>
  </tr>
  <tr>
    <td  rowspan="2">Tracking <br> · Third-party tracking settings <br>&nbsp;&nbsp; · Measure brand safety with third-party partner <br> (Optional)</td>
    <td  rowspan="2">Disable or enabled with any of the following partners:<ul><li>DoubleVerify</li><li>IAS with IAS wrapped tag</li><li>Zefr</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you configure both the "Track viewability with third-party partner" and "Measure brand safety with third-party partner" settings:<ul><li>If you set either of these two settings to IAS, you need to set both to IAS, and specify the same IAS wrapped tag.</li><li>If you set either of these two settings to Zefr, you need to set both to Zefr.</li></ul></td>
    <td><code>brand_safety_postbid_partner</code></td>
    <td>Not specified or specify any of the following values:<ul><li><code>DOUBLE_VERIFY</code></li><li><code>IAS</code></li><li><code>ZEFR</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you configure <code>brand_safety_postbid_partner</code> and <code>viewability_postbid_partner</code>:<ul><li>If you set either of these two fields to <code>IAS</code>, you need to specify both to <code>IAS</code>, and assign the same value (the wrapped VAST URL that you obtain from the measurement partner IAS) to <code>brand_safety_vast_url</code> and <code>viewability_vast_url</code>.</li><li>If you set either of these two fields to <code>ZEFR</code>, you need to set both to <code>ZEFR</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>brand_safety_vast_url</code></td>
    <td>Specify a valid value when <code>brand_safety_postbid_partner</code> is <code>IAS</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you set both <code>brand_safety_postbid_partner</code> and <code>viewability_postbid_partner</code> to <code>IAS</code>, <code>brand_safety_vast_url</code> should be the same as <code>viewability_vast_url</code>.</td>
  </tr>
        <tr>
            <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;· App events<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>tracking_app_id</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Impression tracking URL<br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>impression_tracking_url</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Click tracking URL <br> (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>click_tracking_url</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
    </tbody>
</table>

### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
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
                "ad_format":"CAROUSEL_ADS",
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "music_info": {
                    "music_id": "{{music_id}}"
                },
                "identity_type": "TT_USER",
                "identity_id": "{{identity_id}}"
            }
        },
        {
            "creative_info": {
                "ad_format":"SINGLE_VIDEO",
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "identity_type": "TT_USER",
                "identity_id": "{{identity_id}}"
            }
        },
        {
            "creative_info": {
                "ad_format":"SINGLE_VIDEO",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_type": "TT_USER",
                "identity_id": "{{identity_id}}"
            }
        },
        {
            "creative_info": {
                "ad_format":"SINGLE_VIDEO",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            }
        },
        {
            "creative_info": {
                "ad_format":"SINGLE_VIDEO",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_type": "AUTH_CODE",
                "identity_id": "{{identity_id}}"
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
        "utm_params": [
            {
                "key": "{{key}}",
                "value": "{{value}}"
            }
        ],
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
            "tracking_info": {},
            "utm_params": [
                {
                    "key": "{{key}}",
                    "value": "{{value}}"
                }
            ]
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
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_authorized_bc_id": "0",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
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
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_authorized_bc_id": "0",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "tiktok_item_id": "{{tiktok_item_id}}"
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "tiktok_item_id": "{{tiktok_item_id}}"
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"CAROUSEL_ADS",
                    "identity_authorized_bc_id": "0",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "material_name": "{{material_name}}",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format":"SINGLE_VIDEO",
                    "identity_authorized_bc_id": "0",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "AUTH_CODE",
                    "tiktok_item_id": "{{tiktok_item_id}}"
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "deeplink_list": [],
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


