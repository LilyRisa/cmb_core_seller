---
title: "Create an Upgraded Smart+ Lead Generation Campaign with Location as Website"
doc_id: 1843324606213121
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Lead Generation Campaigns / Create an Upgraded Smart+ Lead Generation Campaign with Location as Website"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ Lead Generation Campaign with Location as Website

An Upgraded Smart+ Lead Generation Campaign with Location as Website is an Upgraded Smart+ Lead Generation Campaign with `promotion_target_type` set to `EXTERNAL_WEBSITE`.

# Steps
## Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
  <thead>
    <tr>
      <th style="width:17%"><b>Setting</b></th>
      <th style="width:26%"><b>Requirement</b></th>
      <th style="width:27%"><b>Parameter</b></th>
      <th style="width:30%"><b>How to configure the parameter</b></th>
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
      <td>Disabled</td>
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
      <td>Special ad categories (Optional)</td>
      <td>Disabled or enabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
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
**Creating a CBO campaign**

Request

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: "{{AccessToken}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "budget": "{{budget}}"
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
        "budget": "{{budget}}",
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "catalog_enabled": false,
        "create_time": "{{create_time}}",
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

<table>
  <thead>
    <tr>
      <th style="width:17%"><b>Setting</b></th>
      <th style="width:26%"><b>Requirement</b></th>
      <th style="width:27%"><b>Parameter</b></th>
      <th style="width:30%"><b>How to configure the parameter</b></th>
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
      <td rowspan="2">Optimization and bidding <br>· Location</td>
      <td rowspan="2">Website<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
      <td><code>promotion_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td><code>promotion_target_type</code></td>
      <td><code>EXTERNAL_WEBSITE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_target_type</code>for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Goal</td>
      <td>Conversion</td>
      <td><code>optimization_goal</code></td>
      <td><code>CONVERT</code></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Event <br>&nbsp;&nbsp;· Data connection</td>
      <td>A valid Pixel</td>
      <td><code>pixel_id</code></td>
      <td>Specify a valid value. <br><br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Event <br>&nbsp;&nbsp;· Optimization event</td>
      <td>Enabled <br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
      <td><code>optimization_event</code></td>
      <td>Specify a valid value. <br><br>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.<br> See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.<br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_event</code>for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Deep funnel optimization (Optional)</td>
      <td>Enabled or disabled <br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
      <td><code>deep_funnel_optimization_status</code> <code>deep_funnel_event_source</code> <code>deep_funnel_event_source_id</code> <code>deep_funnel_optimization_event</code></td>
      <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not specify <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization and bidding <br>· Bid Strategy and (Optional) Target CPA</td>
      <td rowspan="2">The bid strategy can be any of the following: <ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. <ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li> When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>conversion_bid_price</code> or <code>bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>engaged_view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Lead%20generation" target="_blank">Attribution window and event count - Lead Generation</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Delivery Type</td>
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
      <td>Budget and schedule <br>· Goal-based budget increase (when Bid Strategy is Target CPA)</td>
      <td>Enabled or disabled <br><br>To enable this setting, the following conditions should be met: <ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level: <ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values: <ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul> You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met: <ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level: <ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Add spend target per ad group <br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Schedule</td>
      <td>Any of the following types: <ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li><li><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start and end time" in any of the following scenarios: <ul><li>At the campaign level, a lifetime campaign budget is set.</li><li>At the ad group level, a lifetime ad group budget is set.</li></ul></li></ul></td>
      <td><code>schedule_type</code> <code>schedule_start_time</code> <code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> in any of the following scenarios: <ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> </li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Dayparting (Optional)</td>
      <td>Any of the following types: <ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations: <ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td>Any of the following modes: <ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul> To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values: <ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul> To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Placement</td>
      <td>Any of the following types: <ul><li><b>Automatic Placement</b></li><li><b>Select specific placements</b>:<ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></li></ul></td>
      <td><code>placement_type</code> <code>placements</code></td>
      <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.<ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul></li><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search feed</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
      <td>N/A</td>
      <td>N/A <br><br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br> To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· User comment</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video download</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video sharing</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Pangle block list</td>
      <td>Disabled</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
  </tbody>
</table>

### Example
Request

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: "{{AccessToken}}"' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "FORM",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": "{{conversion_bid_price}}",
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
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": "{{conversion_bid_price}}",
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "modify_time": "{{modify_time}}",
        "optimization_event": "FORM",
        "optimization_goal": "CONVERT",
        "pixel_id": "{{pixel_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_GLOBAL_APP_BUNDLE"
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
      <th style="width:17%"><b>Setting</b></th>
      <th style="width:26%"><b>Requirement</b></th>
      <th style="width:27%"><b>Parameter</b></th>
      <th style="width:30%"><b>How to configure the parameter</b></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name <br>(Optional)</td>
      <td>By default, the system will auto-generate the ad name. You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code>(empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>Any of the following options: <ul><li>URL</li><li>Custom Page</li></ul></td>
      <td><code>landing_page_url_list</code> <code>page_list</code></td>
      <td><ul><li>To specify Destination as URL:<ul><li>Specify the landing page URL of the website that has the Website Form via <code>landing_page_url_list</code>.</li><li>Do not specify <code>page_list</code>.</li></ul></li><li>To specify Destination as Custom Page:<ul><li>Specify the TikTok Instant Page that redirects to the website that has the Website Form via <code>page_list</code>.<ul><li>To obtain the page IDs of TikTok Instant Pages within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a> and set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li><li>To create a TikTok Instant Page, you can use <a href="https://business-api.tiktok.com/portal/docs?id=1740307202170881" target="_blank">Instant Page Editor SDK</a> and set <code>businessType</code> to <code>6</code>.</li></ul></li><li>Do not specify <code>landing_page_url_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Creative assets <br>· Identity</td>
      <td rowspan="3">Any of the following options: <ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values: <ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value <br><br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
    <tr>
      <td rowspan="2">Creative assets <br>· Ad creatives & Ad text</td>
      <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives&nbsp;</b>that can consist of the following types: <ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul> To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
      <td><code>ad_format</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>creative_list</code> <code>video_info</code> <code>image_info</code> <code>music_info</code> <code>ad_text_list</code> <code>tiktok_item_id</code></td>
      <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following: <ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul> To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
    </tr>
    <tr>
      <td>Add product and service information (Optional)</td>
      <td>Disabled</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-ons<br> (Optional)<br> (When the ad creatives include videos or video photos )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types: <ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types: <ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul> To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>. <ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking <br>· TikTok events tracking <br>&nbsp;&nbsp;· App events <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_app_id</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking <br>· Third-party tracking settings <br>&nbsp;&nbsp;· Impression tracking URL <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>impression_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking <br>· Third-party tracking settings <br>&nbsp;&nbsp;· Click tracking URL <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>click_tracking_url</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

### Example
Request

```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: "{{AccessToken}}"' \
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
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}",
    "impression_tracking_url": "{{impression_tracking_url}}",
    "click_tracking_url": "{{click_tracking_url}}"
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
        "ad_id": "{{ad_id}}",
        "ad_name": "{{ad_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "adgroup_id": "{{adgroup_id}}",
        "call_to_action_id": "{{call_to_action_id}}",
        "click_tracking_url": "{{click_tracking_url}}",
        "creative_list": [
            {
                "creative_info": {
                    "ad_format": "CAROUSEL_ADS",
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
            }
        ],
        "impression_tracking_url": "{{impression_tracking_url}}",
        "landing_page_url_list": [
            {
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "secondary_status": "AD_STATUS_CREATE"
    }
}
(/code)
```

