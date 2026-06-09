---
title: "Create an Upgraded Smart+ Mini Series Feed Campaign"
doc_id: 1863331595515009
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ App Campaigns / Create an Upgraded Smart+ Mini Series Feed Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ Mini Series Feed Campaign

This article walks you through the steps to create an Upgraded Smart+ Mini Series Feed Campaign.

An Upgraded Smart+ Mini Series Feed Campaign is an Upgraded Smart+ App Campaign promoting a TikTok Series without using a catalog. Series offers exclusive and premium video packages produced by creators. Access to Series is available to purchase through the creator's profile and links attached to TikTok videos.


# Prerequisite
Create an Upgraded Smart+ Mini Series Feed Campaign is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


# Steps
## 1. Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-campaign/v1.3). 

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-campaign/v1.3).

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
      <td>TikTok Minis or Series</td>
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
      <td><b>Disabled</b></td>
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
        <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified:<ul><li>Specify a valid value</li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>If <code>budget_mode</code> is <code>BUDGET_MODE_INFINITE</code>:<ul><li>Not specified</li></ul></li><li>If <code>budget_mode</code> is <code>BUDGET_MODE_DAY</code> or <code>BUDGET_MODE_TOTAL</code>:<ul><li>Specify a valid value</li></ul></li></ul></li></ul></td>
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
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
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

## 2. Create an ad group
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
            <td>Optimization and bidding<br>· Optimization location</td>
            <td>TikTok Series<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>promotion_type</code></td>
            <td><code>NATIVE_SERIES</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td rowspan="3">Optimization and bidding<br>· Identity</td>
            <td rowspan="3">Specify a valid identity (the TikTok account where your Series is hosted and managed)<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>identity_type</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td><code>identity_id</code></td>
            <td>Specify a valid value <br> <br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs/get-the-identity-list/v1.3" target="_blank">/identity/get/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>identity_id</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td><code>identity_authorized_bc_id</code></td>
            <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>identity_authorized_bc_id</code>, if any, for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· TikTok Series</td>
            <td>Select a valid TikTok Series from the selected TikTok account<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>native_series_id</code></td>
            <td>Specify the ID of a valid TikTok Series <br><br>To obtain the list of TikTok Series available from a TikTok account that your ad account can access, use <a href="https://business-api.tiktok.com/portal/docs/get-the-available-tiktok-series-within-an-ad-account/v1.3" target="_blank">/identity/native_series/get/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>native_series_id</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Goal</td>
            <td>Value.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>optimization_goal</code></td>
            <td><code>VALUE</code> (with <code>optimization_event</code> specified).<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Select value</td>
            <td><b>Ad revenue value</b>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad groups within the same campaign. </td>
            <td><code>optimization_event</code></td>
            <td><code>AD_REVENUE_VALUE</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code>, if specified, must be the same for ad groups within the same campaign.</td>
        </tr>
    <tr>
      <td>Optimization and bidding<br>·  Bid Strategy and (Optional) Target ROAS</td>
      <td>Any of the following options:<ul><li>Highest value</li><li>Target ROAS<ul><li>For Target ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
      <td>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
        <tr>
            <td>Optimization and bidding<br>· Time window of the bid strategy</td>
            <td>Day 0 ROAS or Highest Value<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad groups within the same campaign.</td>
            <td><code>vbo_window</code></td>
            <td><code>ZERO_DAY</code> <br><br>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>vbo_window</code>, if specified, must be the same for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Attribution settings</td>
            <td>7-day click</td>
            <td><code>click_attribution_window</code></td>
            <td>Not specified. <code>click_attribution_window</code> will default to <code>SEVEN_DAYS</code>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Billing event</td>
            <td>oCPM.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</td>
            <td><code>billing_event</code></td>
            <td><code>OCPM</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Delivery Type</td>
            <td>Standard (default)</td>
            <td>N/A</td>
            <td>By default, the Delivery Type for the Upgraded Smart+ Ad Group is Standard.</td>
        </tr>
          <tr>
            <td>Budget and schedule<br>· Budget</td>
            <td><ul><li>When you select a campaign budget mode:<ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul></li><li>When you select an ad group budget strategy:<ul><li>If the ad group budget limit is No limit or Lifetime limit, you can use any of the following ad group budget modes:<ul><li>Lifetime</li><li>Daily</li></ul></li><li>If the ad group budget limit is Daily limit, you can use the following ad group budget mode:<ul><li>Daily</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</p></td>
            <td><code>budget_mode</code><br><code>budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code> are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> and <code>budget_mode</code> is <code>BUDGET_MODE_INFINITE</code> or <code>BUDGET_MODE_TOTAL</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DAY</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</p></td>
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
            <td><b>Automatic targeting</b> <ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads. </li></ul><br>To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Mini Series Feed Ad Groups">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Mini Series Feed Ad Groups</a>.</td>
            <td><code>targeting_optimization_mode</code></td>
            <td><code>AUTOMATIC</code> or not specified.<br><br> To learn about the supported targeting settings, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Mini Series Feed Ad Groups">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Mini Series Feed Ad Groups</a>.</td>
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
            <td>These settings only apply to <b>TikTok in-feed</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
            <td>N/A</td>
            <td>N/A <br><br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>. To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
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

### Available targeting settings for different targeting optimization modes in Upgraded Smart+ Mini Series Feed Ad Groups
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
      <td>Specify valid locations<br><br><p><span style="color:darkred"><b>Note</b></span>: When you use the Target ROAS bid strategy, a specific Series can only be promoted in a given location by one active campaign at a time. If another active campaign is already targeting the same location with the same Series, select a different location or Series.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify valid values<br><br><p><span style="color:darkred"><b>Note</b></span>: When you use the Target ROAS bid strategy (<code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>), a specific Series (<code>native_series_id</code>) can only be promoted in a given location by one active campaign at a time. If another active campaign is already targeting the same location with the same Series, provide a different <code>location_ids</code>, <code>zipcode_ids</code>, or <code>native_series_id</code> value.</td>
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

### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "NATIVE_SERIES",
    "identity_type": "BC_AUTH_TT",
    "identity_id": "{{identity_id}}",
    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
    "native_series_id": "{{native_series_id}}",
    "optimization_goal": "VALUE",
    "optimization_event": "AD_REVENUE_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_MIN_ROAS",
    "roas_bid": {{roas_bid}},
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
        "app_config": [],
        "attribution_event_count": "EVERY",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "deep_bid_type": "VO_MIN_ROAS",
        "engaged_view_attribution_window": "OFF",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "native_series_id": "{{native_series_id}}",
        "operation_status": "ENABLE",
        "optimization_event": "IMPRESSION_LEVEL_AD_REVENUE",
        "optimization_event_type": "STANDARD_EVENT",
        "optimization_goal": "VALUE",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "NATIVE_SERIES",
        "roas_bid": {{roas_bid}},
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
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
            "brand_safety_type": "EXPANDED_INVENTORY",
            "excluded_custom_actions": [],
            "gender": "GENDER_UNLIMITED",
            "included_custom_actions": [],
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

## 3. Create an ad
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
            <td rowspan="2">Creative assets<br>· Ad creatives & Ad text</td>
            <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</td>
            <td><code>ad_format</code></td>
            <td>Specify a valid value</td>
        </tr>
        <tr>
            <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
            <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Your creative may be eligible for <a href="https://ads.tiktok.com/help/article/about-smart-plus-paid-and-organic-optimization" target="_blank">paid and organic optimization</a>. As part of this process, the ad destination link and associated URL parameters may be synchronized to your TikTok post as an organic anchor displayed in the For You feed. Organic creatives subject to such optimization will have their performance reported in aggregation with paid ads as a single entity across TikTok and authorized third-party reporting systems.</td>
        </tr>
        <tr>
            <td rowspan="3">Creative assets<br>· Identity</td>
            <td rowspan="3">Any of the following options:<ul><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><p><span style="color:darkred"><b>Note</b></span>: For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</td>
        </tr>
        <tr>
            <td><code>identity_id</code></td>
            <td>Specify a valid value. <br><br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
        </tr>
        <tr>
            <td><code>identity_authorized_bc_id</code></td>
            <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
        </tr>
        <tr>
            <td rowspan="2">Call to action</td>
            <td rowspan="2">Dynamic</td>
            <td><code>call_to_action_id</code></td>
            <td>Specify a valid value.<br><br>To learn about how to create Dynamic CTAs, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs" target="_blank">CTA recommendations > Dynamic CTAs</a>.</td>
        </tr>
        <tr>
            <td><code>call_to_action_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Selling points (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>selling_points</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>
            <td>Disclaimer (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>disclaimer</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Interactive add-ons</td>
            <td>Disabled</td>
            <td><code>interactive_add_on_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Destination</td>
            <td>TikTok Series</td>
            <td><code>landing_page_url_list</code></td>
            <td>Not specified</td>
        </tr>
    </tbody>
</table>

### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                },
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
    "ad_configuration": {
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
            "catalog_creative_info": {},
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "tracking_info": {
                "app_tracking_info_list": []
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
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```


