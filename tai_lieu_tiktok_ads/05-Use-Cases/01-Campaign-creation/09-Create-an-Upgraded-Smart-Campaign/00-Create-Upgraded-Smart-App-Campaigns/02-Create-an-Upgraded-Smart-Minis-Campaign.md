---
title: "Create an Upgraded Smart+ Minis Campaign"
doc_id: 1853377811982657
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ App Campaigns / Create an Upgraded Smart+ Minis Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ Minis Campaign

This article walks you through the steps to create an Upgraded Smart+ Minis Campaign.

Based on your preference for using a catalog to promote TikTok Minis, you can create Upgraded Smart+ Minis Non-Catalog Campaigns or Upgraded Smart+ Minis Catalog Campaigns. For detailed instructions on creating each type, refer to their respective sections: "<a href="#item-link-Create an Upgraded Smart+ Minis Non-Catalog Campaign">Create an Upgraded Smart+ Minis Non-Catalog Campaign</a>" and "<a href="#item-link-Create an Upgraded Smart+ Minis Catalog Campaign">Create an Upgraded Smart+ Minis Catalog Campaign</a>".

# Create an Upgraded Smart+ Minis Non-Catalog Campaign
An Upgraded Smart+ Minis Non-Catalog Campaign is an Upgraded Smart+ App Campaign promoting a TikTok Minis without using a mini series catalog. 

## Steps
### Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>App promotion</td>
      <td><code>objective_type</code></td>
      <td><code>APP_PROMOTION</code></td>
    </tr>
    <tr>
      <td>App promotion type</td>
      <td>TikTok Minis</td>
      <td><code>app_promotion_type</code></td>
      <td><code>MINIS</code></td>
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
      <td><b>Disabled</b><br><br>To create an Upgraded Smart+ Minis Campaign using a mini series catalog, see <a href="#item-link-Create an Upgraded Smart+ Minis Catalog Campaign">Create an Upgraded Smart+ Minis Catalog Campaign</a>.</td>
      <td><code>catalog_enabled</code></td>
      <td><code>false</code></td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
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
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "MINIS",
    "campaign_type": "REGULAR_CAMPAIGN",
    "catalog_enabled": false,
    "campaign_name": "{{campaign_name}}",
    "budget_optimize_on": true,
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
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
        "app_promotion_type": "MINIS",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "create_time": "{{create_time}}",
        "modify_time": "{{modify_time}}",
        "objective_type": "APP_PROMOTION",
        "operation_status": "ENABLE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```

### Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
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
      <td rowspan="2">Optimization and bidding<br>· TikTok Minis</td>
      <td rowspan="2">Specify an active TikTok Minis of the mini series or mini game type<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code></td>
      <td><ul><li>For a TikTok Minis of the mini series type, set this field to <code>MINI_APP</code>.</li><li>For a TikTok Minis of the mini game type, set this field to <code>MINI_GAME</code>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td><code>minis_id</code></td>
      <td>Specify the ID of an active TikTok Minis.<br>To obtain the ID of an active TikTok Minis within an ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1853450329535490" target="_blank">/minis/get/</a> and select a TikTok Minis with <code>minis_status</code> as <code>ACTIVE</code>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal </td>
      <td>Value: We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_goal</code></td>
      <td><code>VALUE</code> (with <code>optimization_event</code> specified)<br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Select value</td>
      <td>Any of the following types:<ul><li><b>Purchase value</b></li><li><b>Ad revenue value</b></li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</li><li>Ad revenue value for Upgraded Smart+ Minis Ads for Mini Series is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></p></td>
      <td><code>optimization_event</code></td>
      <td><ul><li>To use Purchase value, set this field to <code>ACTIVE_PAY</code> .</li><li>To use Ad revenue value, set this field to <code>AD_REVENUE_VALUE</code>.</li></ul>To confirm whether your settings are eligible for enabling Purchase value or Ad revenue value, use <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code>, if specified, must be the same for ad groups within the same campaign.</li><li>Ad revenue value (<code>AD_REVENUE_VALUE</code>) for Upgraded Smart+ Minis Ads for Mini Series is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>·  Bid Strategy and (Optional) Target ROAS</td>
      <td>Any of the following options<ul><li>Highest value</li><li>Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
      <td>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Time window of the bid strategy</td>
      <td>Any of the following types:<ul><li>Day 7 ROAS or Highest Value</li><li>Day 0 ROAS or Highest Value</li></ul><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad groups within the same campaign.</p></td>
      <td><code>vbo_window</code></td>
      <td>Any of the following values:<ul><li><code>SEVEN_DAYS</code></li><li><code>ZERO_DAY</code></li></ul>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br><br>To confirm whether your settings are eligible for enabling the Day 0 time window or Day 7 time window, use <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753"  target="_blank">/tool/vbo_status/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>vbo_window</code>, if specified, must be the same for ad groups within the same campaign:</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td><ul><li>When the type of the selected TikTok Minis is mini series and Purchase value is selected, the attribution window will default to 180-day click and the event count will default to every.</li><li>When the type of the selected TikTok Minis is mini game, the attribution window will default to 30-day click and the event count will default to every.</li></ul></td>
      <td><ul><li><code>click_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
      <td>Not specified<ul><li>When the <code>minis_type</code> of the specified <code>minis_id</code> is <code>MINI_SERIES</code> and <code>optimization_event</code> is <code>active_pay</code>:<ul><li><code>click_attribution_window</code> will default to <code>ONE_HUNDRED_EIGHTY_DAYS</code>.</li><li><code>attribution_event_count</code>will default to <code>EVERY</code>.</li></ul></li><li>When the <code>minis_type</code> of the specified <code>minis_id</code> is  <code>MINI_GAME</code>:<ul><li><code>click_attribution_window</code> will default to <code>THIRTY_DAYS</code>.</li><li><code>attribution_event_count</code>will default to <code>EVERY</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td>oCPM<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</p></td>
      <td><code>billing_event</code></td>
      <td> <code>OCPM</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</p></td>
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
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios:<ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
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
      <td><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul>To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Non-Catalog Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Non-Catalog Campaigns</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td><code>AUTOMATIC</code> or not specified<br><br>To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Non-Catalog Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Non-Catalog Campaigns</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Placements and brand safety<br>· Placement</td>
      <td rowspan="2">TikTok</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code> </td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>PLACEMENT_TIKTOK</code></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and search ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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

#### Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Non-Catalog Campaigns
<table>
  <thead>
    <tr>
      <th style="width:23%">Targeting optimization mode</th>
      <th style="width:18%">Available targeting setting</th>
      <th style="width:18%">Requirement</th>
      <th style="width:19%">Parameter</th>
      <th style="width:22%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="5">Automatic targeting<br>(<code>targeting_optimization_mode</code> as <code>AUTOMATIC</code> or not specified)</td>
      <td>Audience controls<br>· Location</td>
      <td>The targeted locations should match or be the subset of the targeting locations of your TikTok Minis.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify IDs of locations that match or are the subset of the targeting locations of the specified <code>minis_id</code>.<br>To obtain the targeting locations of a TikTok Minis, use <a href="https://business-api.tiktok.com/portal/docs?id=1853450329535490" target="_blank">/minis/get/</a> and check the returned <code>region_codes</code>.</td>
    </tr>
    <tr>
      <td>Audience controls<br>· Minimum age</td>
      <td>Any of the following options:
        <ul>
          <li>18</li>
          <li>25</li>
        </ul>
      </td>
      <td><code>spc_audience_age</code></td>
      <td>Any of the following values:
        <ul>
          <li><code>OVER_EIGHTEEN</code> or unspecified</li>
          <li><code>OVER_TWENTY_FIVE</code></li>
        </ul>
      </td>
    </tr>
    <tr>
      <td>Audience controls<br>· Languages (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls<br>· Gender (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>gender</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls<br>· Exclude audience (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example

##### When the TikTok Minis is of the mini series type
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "MINI_APP",
    "minis_id": "{{minis_id}}",
    "optimization_goal": "VALUE",
    "optimization_event": "ACTIVE_PAY",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_HIGHEST_VALUE",
    "vbo_window": "ZERO_DAY",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "targeting_optimization_mode": "AUTOMATIC",
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
        "attribution_event_count": "EVERY",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "THIRTY_TWO_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "engaged_view_attribution_window": "OFF",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "minis_id": "{{minis_id}}",
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "ACTIVE_PAY",
        "optimization_goal": "VALUE",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "MINI_APP",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "secondary_optimization_event": "PURCHASE_ROI",
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
            "brand_safety_type": "LIMITED_INVENTORY",
            "category_exclusion_ids": [
                "200",
                "202",
                "203"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_id}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN"
        },
        "vbo_window": "ZERO_DAY",
        "video_download_disabled": false,
        "view_attribution_window": "OFF"
    }
}
(/code)
```

##### When the TikTok Minis is of the mini game type
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "MINI_GAME",
    "minis_id": "{{minis_id}}",
    "optimization_goal": "VALUE",
    "optimization_event": "ACTIVE_PAY",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_HIGHEST_VALUE",
    "vbo_window": "ZERO_DAY",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "targeting_optimization_mode": "AUTOMATIC",
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
        "attribution_event_count": "EVERY",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "THIRTY_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "engaged_view_attribution_window": "OFF",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "minis_id": "{{minis_id}}",
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "ACTIVE_PAY",
        "optimization_goal": "VALUE",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "MINI_GAME",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "secondary_optimization_event": "PURCHASE_ROI",
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
            "brand_safety_type": "LIMITED_INVENTORY",
            "category_exclusion_ids": [
                "200",
                "202",
                "203"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_id}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN"
        },
        "vbo_window": "ZERO_DAY",
        "video_download_disabled": false,
        "view_attribution_window": "OFF"
    }
}
(/code)
```

### Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
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
      <td rowspan="3">Creative assets <br>· Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
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
      <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</p></td>
      <td><code>ad_format</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
      <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</p></td>
    </tr>
    <tr>
      <td>Selling points (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>selling_points</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value<br><br>To learn about how to create Dynamic CTAs, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs" target="_blank">CTA recommendations > Dynamic CTAs</a>.</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-ons<br>(Optional)<br>(When a TikTok Minis of the mini series type is specified and the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You can only use add-ons when the following conditions are both met:<ul><li>At the ad group level: A TikTok Minis of the mini series type is specified through <code>minis_id</code>.</li><li>At the ad level: <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></li></ul>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></p></td>
    </tr>
    <tr>
      <td>Destination </td>
      <td>TikTok Minis URL. This is a link that will lead directly to the TikTok Minis interface through an ad.</td>
      <td><code>landing_page_url_list</code></td>
      <td>Specify a valid TikTok Minis URL.<br>Ensure the URL matches the <code>minis_id</code> specified at the ad group level.<br>To obtain a valid TikTok Minis URL, contact your TikTok representative.</td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
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
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
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
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "ad_configuration": {
        "call_to_action_id": "{{call_to_action_id}}",
        "product_info": {
            "selling_points": [
                "{{selling_point}}"
            ]
        }
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
            "dark_post_status": "OFF",
            "deeplink_utm_params": [],
            "phone_info": {},
            "tracking_info": {},
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
                    "identity_authorized_bc_id": "{{bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{image_web_uri}}"
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
        "smart_plus_ad_id": "{{ad_id}}"
    }
}
(/code)
```

# Create an Upgraded Smart+ Minis Catalog Campaign
An Upgraded Smart+ Minis Catalog Campaign is an Upgraded Smart+ App Campaign promoting a TikTok Minis using a mini series catalog.

## Prerequisite
Creating an Upgraded Smart+ Minis Catalog Campaign requires setting up a mini series catalog in Business Center first.

To set up the mini series catalog:

1. Create a **mini series** catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961).

2. Upload mini series to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740564136678402) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).

3. Check the product handling results.
	- For CSV feed and JSON schema, use [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
		- Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
	- For online feed, use [/catalog/feed/log/](https://business-api.tiktok.com/portal/docs?id=1740665225631810).
		- Pass in the `feed_id` obtained from Step 2. If the field `error_count` in the response is not 0, examine your online feed and return to Step 2 to reupload the product.

4. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
You can also choose `advertiser_role` that you want to assign to the members invited.

5. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.

## Steps
### Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>App promotion</td>
      <td><code>objective_type</code></td>
      <td><code>APP_PROMOTION</code></td>
    </tr>
    <tr>
      <td>App promotion type</td>
      <td>TikTok Minis</td>
      <td><code>app_promotion_type</code></td>
      <td><code>MINIS</code></td>
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
      <td><b>Enabled</b><br><br>To create an Upgraded Smart+ Minis Campaign without using a mini series catalog, see <a href="#item-link-Create an Upgraded Smart+ Minis Non-Catalog Campaign">Create an Upgraded Smart+ Minis Non-Catalog Campaign</a>.</td>
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
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "MINIS",
    "campaign_type": "REGULAR_CAMPAIGN",
    "catalog_enabled": true,
    "campaign_name": "{{campaign_name}}",
    "budget_optimize_on": true,
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
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
        "app_promotion_type": "MINIS",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "catalog_enabled": true,
        "create_time": "{{create_time}}",
        "modify_time": "{{modify_time}}",
        "objective_type": "APP_PROMOTION",
        "operation_status": "ENABLE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```

### Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
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
      <td rowspan="2">Optimization and bidding<br>· TikTok Minis</td>
      <td rowspan="2">Specify an active TikTok Minis of the mini series type<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code></td>
      <td><code>MINI_APP</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td><code>minis_id</code></td>
      <td>Specify the ID of an active TikTok Minis of the mini series type.<br>To obtain the ID of an active TikTok Minis of the mini series type within an ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1853450329535490" target="_blank">/minis/get/</a> and select a TikTok Minis with <code>minis_status</code> as <code>ACTIVE</code> and <code>minis_type</code> as <code>MINI_SERIES</code>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal </td>
      <td>Value: We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.<br><br><p><span style="color:darkred"><b>Note</b></span>: <br><ul><li>When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</li></ul></p></td>
      <td><code>optimization_goal</code></td>
      <td><code>VALUE</code> (with <code>optimization_event</code> specified)<br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Select value</td>
      <td>Any of the following types:<ul><li><b>Purchase value</b></li><li><b>Ad revenue value</b></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</li><li>Ad revenue value for Upgraded Smart+ Minis Ads for Mini Series is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></p></td>
      <td><code>optimization_event</code></td>
      <td><ul><li>To use Purchase value, set this field to <code>ACTIVE_PAY</code> .</li><li>To use Ad revenue value, set this field to <code>AD_REVENUE_VALUE</code>.</li></ul><br>To confirm whether your settings are eligible for enabling Purchase value or Ad revenue value, use <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code>, if specified, must be the same for ad groups within the same campaign.</li><li>Ad revenue value (<code>AD_REVENUE_VALUE</code>) for Upgraded Smart+ Minis Ads for Mini Series is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>·  Bid Strategy and (Optional) Target ROAS</td>
      <td>Any of the following options<ul><li>Highest value</li><li>Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li> When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Time window of the bid strategy</td>
      <td>Any of the following types:<ul><li>Day 7 ROAS or Highest Value</li><li>Day 0 ROAS or Highest Value</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad groups within the same campaign.</p></td>
      <td><code>vbo_window</code></td>
      <td>Any of the following values:<ul><li><code>SEVEN_DAYS</code></li><li><code>ZERO_DAY</code></li></ul><br>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br><br>To confirm whether your settings are eligible for enabling the Day 0 time window or Day 7 time window, use <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>vbo_window</code>, if specified, must be the same for ad groups within the same campaign:</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td>The attribution window will default to 180-day click and the event count will default to every.</td>
      <td><code>click_attribution_window</code><br><code>attribution_event_count</code></td>
      <td>Not specified<br><ul><li><code>click_attribution_window</code> will default to <code>ONE_HUNDRED_EIGHTY_DAYS</code>.</li><li><code>attribution_event_count</code>will default to <code>EVERY</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td>oCPM<br><br><p><span style="color:darkred"><b>Note</b></span>: <br><ul><li>When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</li></ul></td>
      <td><code>billing_event</code></td>
      <td> <code>OCPM</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Delivery Type</td>
      <td>Standard (default)</td>
      <td>N/A</td>
      <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
    </tr>
    <tr>
      <td>Catalog</td>
      <td>Specify <b>a mini series catalog with at least one approved mini series</b></td>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Specify valid values.<br>The catalog (<code>catalog_id</code>) needs to have at least one approved mini series. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1. <br><br><ul><li>To obtain mini series catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>. The <code>catalog_type</code> of a mini series catalog will be <code>MINI_SERIES</code>.</li><li>To learn about how to create a mini series catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</li></ul></td>
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
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul><br>To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Catalog Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Catalog Campaigns</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td><code>AUTOMATIC</code> or not specified<br><br>To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Catalog Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Catalog Campaigns</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Placements and brand safety<br>· Placement</td>
      <td rowspan="2">TikTok</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>PLACEMENT_TIKTOK</code></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and search ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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

#### Available targeting settings for different targeting optimization modes in Upgraded Smart+ Minis Catalog Campaigns
<table>
  <thead>
    <tr>
      <th style="width:23%">Targeting optimization mode</th>
      <th style="width:18%">Available targeting setting</th>
      <th style="width:18%">Requirement</th>
      <th style="width:19%">Parameter</th>
      <th style="width:22%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="5">Automatic targeting<br>(<code>targeting_optimization_mode</code><br> as <code>AUTOMATIC</code> or not specified)</td>
      <td>Audience controls<br>· Location</td>
      <td>The targeted locations should match or be the subset of the targeting locations of both your TikTok Minis and the mini series catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify IDs of locations that match or are the subset of the targeting locations of the specified <code>minis_id</code> and <code>catalog_id</code>.<br><ul><li>To obtain the targeting locations of a TikTok Minis, use <a href="https://business-api.tiktok.com/portal/docs?id=1853450329535490" target="_blank">/minis/get/</a> and check the returned <code>region_codes</code>.</li><li>To obtain the targeting locations of a mini series catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a> and check the returned <code>country</code> and <code>additional_config_list</code>.</li></ul></td>
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
      <td>Audience controls<br>· Gender<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>gender</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls<br>· Exclude audience<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "MINI_APP",
    "minis_id": "{{minis_id}}",
    "optimization_goal": "VALUE",
    "optimization_event": "ACTIVE_PAY",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_HIGHEST_VALUE",
    "vbo_window": "ZERO_DAY",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "targeting_optimization_mode": "AUTOMATIC",
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
        "attribution_event_count": "EVERY",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "catalog_id": "{{catalog_id}}",
        "click_attribution_window": "THIRTY_TWO_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "create_time": "{{create_time}}",
        "dayparting": "11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "engaged_view_attribution_window": "OFF",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "minis_id": "{{minis_id}}",
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "ACTIVE_PAY",
        "optimization_goal": "VALUE",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_source": "CATALOG",
        "promotion_type": "MINI_APP",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "secondary_optimization_event": "PURCHASE_ROI",
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
            "brand_safety_type": "LIMITED_INVENTORY",
            "category_exclusion_ids": [
                "200",
                "202",
                "203"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_id}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN"
        },
        "vbo_window": "ZERO_DAY",
        "video_download_disabled": false,
        "view_attribution_window": "OFF"
    }
}
(/code)
```

### Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

<table>
  <thead>
    <tr>
         <th style="width:16%">Setting</th>
         <th style="width:26%">Requirement</th>
         <th style="width:26%">Parameter</th>
         <th style="width:32%">How to configure the parameter</th>
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
      <td rowspan="3">Specific series</td>
      <td rowspan="3">Select one mini series</td>
      <td><code>product_specific_type</code></td>
      <td> <code>CUSTOMIZED_PRODUCTS</code></td>
    </tr>
    <tr>
      <td><code>product_ids</code></td>
      <td>Specify one available mini series within the <code>catalog_id</code> specified at the ad group level.<br><ul><li>To retrieve the product ID (<code>product_id</code>) of each mini series, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a> and select one product with <code>audit_status</code> as <code>approved</code>.</li></ul></td>
    </tr>
    <tr>
      <td><code>product_set_id</code></td>
      <td>Not specified</td>
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
      <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</p></td>
      <td><code>ad_format</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
      <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</p></td>
    </tr>
    <tr>
      <td>Selling points </td>
      <td>Disabled</td>
      <td><code>selling_points</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value<br><br>To learn about how to create Dynamic CTAs, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs" target="_blank">CTA recommendations > Dynamic CTAs</a>.</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-ons<br>(Optional)<br>(When a TikTok Minis of the mini series type is specified and the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when the following conditions are both met:<ul><li>At the ad group level: A TikTok Minis of the mini series type is specified through <code>minis_id</code>.</li><li>At the ad level: <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></li></ul><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Destination </td>
      <td>TikTok Minis URL. This is a link that will lead directly to the TikTok Minis interface through an ad.</td>
      <td><code>landing_page_url_list</code></td>
      <td>Specify a valid TikTok Minis URL.<br>Ensure the URL matches the <code>minis_id</code> specified at the ad group level.<br>To obtain a valid TikTok Minis URL, contact your TikTok representative.</td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
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
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{bc_id}}"
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
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "ad_configuration": {
        "call_to_action_id": "{{call_to_action_id}}",
        "product_specific_type": "CUSTOMIZED_PRODUCTS",
        "product_ids": [
            "{{product_id}}"
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "dark_post_status": "OFF",
            "deeplink_utm_params": [],
            "phone_info": {},
            "product_ids": [
                "{{product_id}}"
            ],
            "product_specific_type": "CUSTOMIZED_PRODUCTS",
            "tracking_info": {},
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
                    "identity_authorized_bc_id": "{{bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{image_web_uri}}"
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


