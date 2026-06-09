---
title: "Create an Upgraded Smart+ non-DC App Campaign"
doc_id: 1853377390815233
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ App Campaigns / Create an Upgraded Smart+ non-DC App Campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ non-DC App Campaign

An Upgraded Smart+ non-DC App Campaign is an Upgraded Smart+ App Campaign with iOS 14 dedicated campaign setting disabled and without using a catalog. For such campaigns, you can set the `campaign_type` parameter to `REGULAR_CAMPAIGN` or leave `campaign_type` unspecified. 


# Steps

## Steps for App Install Campaigns
An App Install Campaign is an App Campaign that gets people to install and use your app. For such campaigns, you need to set `app_promotion_type` to `APP_INSTALL`.


### Create a campaign
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
      <td>App install</td>
      <td><code>app_promotion_type</code></td>
      <td><code>APP_INSTALL</code></td>
    </tr>
    <tr>
      <td rowspan="3">iOS 14 dedicated campaign</td>
      <td rowspan="3"><strong>Disabled</strong><br><br>To learn about how to create an Upgraded Smart+ App Campaign with iOS 14 Dedicated Campaign enabled, see <a href="https://business-api.tiktok.com/portal/docs?id=1853377803841537" target="_blank">Create an Upgraded Smart+ DC App Campaign</a>.</td>
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
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories<br>(Optional)</td>
      <td>Disabled or enabled<br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</p></td>
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
    "app_promotion_type": "APP_INSTALL",
    "campaign_type": "REGULAR_CAMPAIGN",
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
        "app_promotion_type": "APP_INSTALL",
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
      <td>Optimization and bidding<br>· App</td>
      <td>Any of the following types:<ul><li>Android App</li><li>iOS App</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code><br><code>app_id</code></td>
      <td><ul><li>To specify an Android App:<ul><li>Specify an Android App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_ANDROID</code>.</li></ul></li><li>To specify an iOS App:<ul><li>Specify an iOS App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_IOS</code>.</li></ul></li><li>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.<ul><li>For Android Apps, the returned <code>platform</code> will be <code>ANDROID</code>.</li><li>For iOS Apps, the returned <code>platform</code> will be <code>IOS</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal</td>
      <td>Any of the following types:<ul><li>Click<ul><li>We'll deliver your ads to the people most likely to click on your ad.</li></ul></li><li>Install<ul><li>We'll show your ads to the people who are most likely to install your app.</li></ul></li><li>In-app event<ul><li>We'll show your ads to the people who are most likely to perform certain actions within your app.</li></ul></li><li>Value<ul><li>We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.<ul><li>In Upgraded Smart+ non-DC App Campaigns, this Goal is only available for Android Apps.</li><li>If you want to enable the Value goal for an iOS App, <a href="https://business-api.tiktok.com/portal/docs?id=1853377803841537" target="_blank">Create an Upgraded Smart+ DC App Campaign</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>INSTALL</code><ul><li>To use <code>INSTALL</code>, you need to ensure third-party tracking has been set up for the selected App.</li><li>To check whether third-party tracking (<code>tracking_url</code>) has been configured for an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297" target="_blank">/app/info/</a>.</li><li>To add third-party tracking for an existing App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859300069378" target="_blank">/app/update/</a>. To learn about how to obtain tracking links from a third-party partner, see <a href="https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking" target="_blank">Mobile Measurement Partner Tracking</a>.</li></ul></li><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified)<ul><li>To use <code>IN_APP_EVENT</code>, you need to ensure the specified event (<code>optimization_event</code>) is available for the selected App.</li></ul></li><li><code>VALUE</code> (with <code>optimization_event</code> specified)<ul><li>When you set this field to <code>VALUE</code>, specify the placement parameters (<code>placement_type</code> or both <code>placement_type</code> and <code>placements</code>) at the same time.</li><li>Learn more about <a href="https://business-api.tiktok.com/portal/docs?id=1739381743067137" target="_blank">Value-Based Optimization</a>.<ul><li>In Upgraded Smart+ non-DC App Campaigns, this Goal is only available for Android Apps.</li><li>If you want to enable the <code>VALUE</code> goal for an iOS App, <a href="https://business-api.tiktok.com/portal/docs?id=1853377803841537" target="_blank">Create an Upgraded Smart+ DC App Campaign</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Select in-app event<br>(when Goal is In-app event)</td>
      <td>Any of the following types:<ul><li>Standard Event</li><li>Custom Conversion</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</p></td>
      <td><code>optimization_event</code><br><code>custom_conversion_id</code></td>
      <td>When <code>optimization_goal</code> is <code>IN_APP_EVENT</code>, specify any of the following:<ul><li>A Standard Event through <code>optimization_event</code></li><li>A Custom Conversion along with the associated conversion event through <code>custom_conversion_id</code> and <code>optimization_event</code></li></ul><br><ul><li>To obtain the available optimization events (<code>optimization_event</code>) for the App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li><li>To obtain the list of Custom Conversions associated with an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1842225174460673" target="_blank">/custom_conversion/list/</a>.<ul><li>To confirm the eligibility of the Custom Conversion for ad group creation:<ul><li>Ensure the returned <code>optimization_event</code> matches the <code>optimization_event</code> specified during ad group creation.</li><li>Ensure the <code>activity_status</code> is <code>NO_RECENT_ACTIVITY</code> or <code>ACTIVE</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code> and <code>custom_conversion_id</code>, if specified, must be the same for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Select value<br>(when Goal is Value)</td>
      <td>When Goal is Value, use the following type:<ul><li><strong>Purchase value</strong> (also known as VBO IAP, which is short for Value-Based Optimization for in-app purchases)<ul><li>Maximize your revenue from in-app purchases.</li></ul></li><li><strong>Ad revenue value</strong> (also known as VBO IAA, which is short for Value-Based Optimization for in-app advertising)<ul><li>Optimize your revenue from in-app advertising.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</p></td>
      <td><code>optimization_event</code></td>
      <td>When <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>To use Purchase value, set <code>optimization_event</code> to <code>ACTIVE_PAY</code>.</li><li>To use Ad revenue value, set <code>optimization_event</code> to <code>AD_REVENUE_VALUE</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code>, if specified, must be the same for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Bid Strategy, (Optional) Target CPA,<br>and (Optional) Target ROAS</td>
      <td><ul><li>If Goal is Click, Install, or In-app event, the strategy can only be <strong>Maximum Delivery</strong> or <strong>Cost Cap</strong>.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be <strong>Highest value</strong> or <strong>Minimum ROAS</strong>.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, <code>INSTALL</code>, or <code>IN_APP_EVENT</code>:
  	     <ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.
		   <ul><li>If <code>optimization_goal</code> is <code>INSTALL</code> or <code>IN_APP_EVENT</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li></ul>
		   </li></ul>
		   </li><li>If <code>optimization_goal</code> is <code>VALUE</code>:
  	          <ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.
				<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul>
				</li></ul>
  	       </li></ul><br><p><span style="color:darkred"><b>Note</b></span>: 
  <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li>
	<li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li>
	<li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Time window of the bid strategy<br>(when Goal is Value)</td>
      <td>When Goal is Value, use any of the following types:<ul><li>Day 7 ROAS or Highest Value</li><li>Day 0 ROAS or Highest Value</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad groups within the same campaign.</p></td>
      <td><code>vbo_window</code></td>
      <td><ul><li>When <code>optimization_goal</code> is <code>VALUE</code>, use any of the following values:<ul><li><code>SEVEN_DAYS</code></li><li><code>ZERO_DAY</code></li></ul></li><li>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>vbo_window</code>, if specified, must be the same for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><code>click_attribution_window</code><br><code>view_attribution_window</code><br><code>engaged_view_attribution_window</code><br><code>attribution_event_count</code></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-App promotion" target="_blank">Attribution window and event count - App promotion</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td><ul><li>If Goal is Click, the billing event should be <strong>CPC</strong>.</li><li>If Goal is Install, In-app event, or Value, the billing event should be <strong>oCPM</strong>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</p></td>
      <td><code>billing_event</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code> for ad groups within the same campaign.</p></td>
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
      <td>Budget and schedule <br>· Goal-based budget increase (when Goal is Value)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Goal is Value</li><li>Bid Strategy is Target ROAS</li><li>Time window of the bid strategy is Day 0 ROAS</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>optimization_goal</code> is <code>VALUE</code>.</li><li><code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>.</li><li><code>vbo_window</code> is <code>ZERO_DAY</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a lifetime campaign budget, you can enable or disable this setting.</li><li>When you use a daily campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios:<ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
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
      <td>Any of the following values:<ul><li><strong>Automatic targeting</strong><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><strong>Custom targeting</strong><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Placement</td>
      <td>Any of the following types:<ul><li><strong>Automatic Placement</strong></li><li><strong>Select specific placements</strong>:<ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></li></ul></td>
      <td><code>placement_type</code><br><code>placements</code></td>
      <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.<ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul></li><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <strong>TikTok in-feed</strong> and <strong>search feed</strong> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "app_id": "{{app_id}}",
    "promotion_type": "APP_ANDROID",
    "optimization_goal": "INSTALL",
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
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "smart_plus_adg_app_non_DC_Android_0723",
        "advertiser_id": "{{advertiser_id}}",
        "app_id": "{{app_id}}",
        "attribution_event_count": "ONCE",
        "bid_type": "BID_TYPE_CUSTOM",
        "billing_event": "OCPM",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": "DEFAULT",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "modify_time": "{{modify_time}}",
        "optimization_event": "ACTIVE",
        "optimization_goal": "INSTALL",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_GLOBAL_APP_BUNDLE"
        ],
        "promotion_type": "APP_ANDROID",
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
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code> (empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td  rowspan="3">Creative assets <br>· Identity</td>
      <td  rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
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
      <td>Selling points (Optional)</td>
      <td>Disabled</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</p></td>
      <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
      <td><ul><li>To use dynamic call to action, specify <code>call_to_action_id</code>.</li><li>To use standard call to action, specify <code>call_to_action_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>creative_info</code> object to <code>TT_USER</code>, <code>BC_AUTH_TT</code>, or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</p></td>
    </tr>
    <tr>
      <td>Interactive add-ons<br>(Optional)<br>(When the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</p><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul> </td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>When the promoted App is an Android App: Google Play<br><br>When the promoted App is an iOS App: App Store</td>
      <td><code>landing_page_url_list</code><br><code>page_list</code></td>
      <td><ul><li>When <code>promotion_type</code> at the ad group level is <code>APP_ANDROID</code>, you can only set the Destination as Google Play.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li><li>When <code>promotion_type</code> at the ad group level is <code>APP_IOS</code>, you can only set the Destination as App Store.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td  rowspan="2">Deferred deeplink<br>(Optional)<br>(When Destination is Google Play/App Store)</td>
      <td  rowspan="2">Disabled or enabled with a valid deeplink.<br>A deferred deeplink sends the user to a specific page when they open your app for the first time after installing. It must be used with a third-party tracking link.</td>
      <td><code>deeplink_type</code></td>
      <td>Not specified or specify <code>DEFERRED_DEEPLINK</code></td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td><ul><li>When <code>deeplink_type</code> is not specified:<ul><li>Not specified</li></ul></li><li>When <code>deeplink_type</code> is <code>DEFERRED_DEEPLINK</code><ul><li>Specify a custom deeplink</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;· Website<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_pixel_id</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;· Offline events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_offline_event_set_ids</code></td>
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
    "interactive_add_on_list": [
        {
            "card_id": "{{card_id}}"
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
            "tracking_info": {
                "tracking_app_id": "{{tracking_app_id}}"
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
                    "material_name": "Image carousel 1",
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
        "interactive_add_on_list": [
            {
                "card_id": "{{card_id}}"
            }
        ],
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

## Steps for App Retargeting Campaigns
An App Retargeting Campaign is an App Campaign that re-engages existing app users to take action in your app. For such campaigns, you need to set `app_promotion_type` to `APP_RETARGETING`.


### Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-campaign/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-campaign/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:15%">Setting</th>
      <th style="width:28%">Requirement</th>
      <th style="width:26%">Parameter</th>
      <th style="width:31%">How to configure the parameter</th>
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
      <td>App retargeting</td>
      <td><code>app_promotion_type</code></td>
      <td><code>APP_RETARGETING</code></td>
    </tr>
    <tr>
      <td rowspan="3">iOS 14 dedicated campaign</td>
      <td rowspan="3"><b>Disabled</b></td>
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
    <tr>
      <td>Realtime API (RTA)<br>(Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: The available placements at the ad group level might vary based on your RTA setting.</p></td>
      <td><code>rta_id</code></td>
      <td>Specify one of the RTA IDs associated with your ad account.<br><br>To obtain the list of RTA IDs associated with your ad account, contact your TikTok representative.<br><br><p><span style="color:darkred"><b>Note</b></span>: The available placement setting (<code>placement_type</code> and <code>placements</code>) at the ad group level might vary based on your <code>rta_id</code> configuration.</p></td>
    </tr>
    <tr>
      <td>Use RTA bid <br>(Optional)</td>
      <td><ul><li>When RTA is disabled, this setting is not supported.</li><li>When RTA is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>rta_bid_enabled</code></td>
      <td><ul><li>When <code>rta_id</code> is not specified:<ul><li><code>false</code> or not specified</li></ul></li><li>When <code>rta_id</code> is specified, use any of the following configurations :<ul><li><code>true</code></li><li><code>false</code> or not specified</li></ul></li></ul></td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com//open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "APP_PROMOTION",
    "app_promotion_type": "APP_RETARGETING",
    "campaign_type": "REGULAR_CAMPAIGN",
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
        "app_promotion_type": "APP_RETARGETING",
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
      <th style="width:28%">Requirement</th>
      <th style="width:26%">Parameter</th>
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
      <td>Optimization and bidding<br>· App</td>
      <td>Any of the following types:<ul><li>Android App</li><li>iOS App</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>promotion_type</code><br><code>app_id</code></td>
      <td><ul><li>To specify an Android App:<ul><li>Specify an Android App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_ANDROID</code>.</li></ul></li><li>To specify an iOS App:<ul><li>Specify an iOS App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_IOS</code>.</li></ul></li></ul>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.<ul><li>For Android Apps, the returned <code>platform</code> will be <code>ANDROID</code>.</li><li>For iOS Apps, the returned <code>platform</code> will be <code>IOS</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Agree to the Compliance Assurance Policy for Gaming Advertisers on TikTok</td>
      <td>Any of the following options:<ul><li>Agreed<ul><li>By agreeing to the policy, you confirm and attest that any gaming application, product or service (game) you desire to advertise on TikTok, including any associated URL(s), (a) complies with all applicable laws and regulations of the jurisdictions where the game can be accessed or played, and upon request, can provide supporting documentation as evidence of why the game is not considered illegal gambling or lottery; and (b) has not been and is not part of any investigation or lawsuit regarding the game's legality or regulatory compliance.</li></ul></li><li>Not agreed</li></ul></td>
      <td><code>gaming_ad_compliance_agreement</code></td>
      <td>Any of the following values:<ul><li><code>true</code></li><li><code>false</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Agreeing to the Compliance Assurance Policy for Gaming Advertisers on TikTok is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Goal </td>
      <td>Any of the following types:<ul><li>In-app event<ul><li>We'll show your ads to the people who are most likely to perform certain actions within your app.</li></ul></li><li>Value <ul><li>We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified)<ul><li>To use <code>IN_APP_EVENT</code>, you need to ensure the specified event (<code>optimization_event</code>) is available for the selected App. </li></ul></li><li><code>VALUE</code> (with <code>optimization_event</code> specified)<ul><li>When you set this field to <code>VALUE</code>, specify the placement parameters (<code>placement_type</code> or both <code>placement_type</code> and <code>placements</code>) at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Select in-app event<br>(when Goal is In-app event)</td>
      <td>Any of the following types:<ul><li>Standard Event</li><li>Custom Conversion</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</p></td>
      <td><code>optimization_event</code><br><code>custom_conversion_id</code></td>
      <td>When <code>optimization_goal</code> is <code>IN_APP_EVENT</code>, specify any of the following:<ul><li>A Standard Event through <code>optimization_event</code></li><li>A Custom Conversion along with the associated conversion event through <code>custom_conversion_id</code> and <code>optimization_event</code></li><li>To obtain the available optimization events (<code>optimization_event</code>) for the App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li><li>To obtain the list of Custom Conversions associated with an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1842225174460673" target="_blank">/custom_conversion/list/</a>.<ul><li>To confirm the eligibility of the Custom Conversion for ad group creation:<ul><li>Ensure the returned <code>optimization_event</code> matches the <code>optimization_event</code> specified during ad group creation.</li><li>Ensure the <code>activity_status</code> is <code>NO_RECENT_ACTIVITY</code> or <code>ACTIVE</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code> and <code>custom_conversion_id</code>, if specified, must be the same for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Optimization event<br>(when Goal is Value)</td>
      <td><b>Purchase value</b> (also known as VBO IAP, which is short for Value-Based Optimization for in-app purchases)<ul><li>Maximize your revenue from in-app purchases.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</p></td>
      <td><code>optimization_event</code></td>
      <td>When <code>optimization_goal</code> is <code>VALUE</code>: <code>ACTIVE_PAY</code> .<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code>, if specified, must be the same for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Bid Strategy, (Optional) Target CPA, <br>and (Optional) Target ROAS</td>
      <td><ul><li>If Goal is In-app event, the strategy can only be Maximum Delivery or Cost Cap.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>IN_APP_EVENT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>optimization_goal</code> is <code>IN_APP_EVENT</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li> When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><code>click_attribution_window</code><br><code>view_attribution_window</code><br><code>engaged_view_attribution_window</code><br><code>attribution_event_count</code></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-App%20promotion" target="_blank">Attribution window and event count - App promotion</a>.</td>
    </tr>
    <tr>
      <td>Optimization and bidding<br>· Billing event</td>
      <td>oCPM<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</p></td>
      <td><code>billing_event</code></td>
      <td> <code>OCPM</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code> for ad groups within the same campaign.</p></td>
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
      <td>Budget and schedule <br>· Goal-based budget increase (when Goal is Value)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Goal is Value</li><li>Bid Strategy is Target ROAS</li><li>Time window of the bid strategy is Day 0 ROAS</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>optimization_goal</code> is <code>VALUE</code>.</li><li><code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>.</li><li><code>vbo_window</code> is <code>ZERO_DAY</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget:<ul><li>When Bid strategy is Maximum Delivery (Highest volume), you can enable or disable this setting.</li><li>When Bid Strategy is Target CPA or Target ROAS, this setting is not supported.</li></ul></li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level:<ul><li>When <code>bid_type</code> is <code>BID_TYPE_NO_BID</code> at the ad group level , you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> is <code>VO_HIGHEST_VALUE</code> at the ad group level, this field is not supported.</li></ul></li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
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
      <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ App Retargeting Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ App Retargeting Campaigns</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ App Retargeting Campaigns">Available targeting settings for different targeting optimization modes in Upgraded Smart+ App Retargeting Campaigns</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Placement</td>
      <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select specific placements</b>:<ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When Realtime API is enabled at the campaign level, the only supported type is <b>Select specific placements</b> with TikTok only placement.</p></td>
      <td><code>placement_type</code><br><code>placements</code></td>
      <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.<ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul></li><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>rta_id</code> is specified at the campaign level, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</p></td>
    </tr>
    <tr>
      <td>Placements and brand safety<br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search feed</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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
  </tbody>
</table>

#### Available targeting settings for different targeting optimization modes in Upgraded Smart+ App Retargeting Campaigns

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
      <td rowspan="10">Automatic targeting<br>(<code>targeting_optimization_mode</code><br> as <code>AUTOMATIC</code> or not specified)</td>
      <td>Audience controls<br>· Location</td>
      <td>Specify valid locations</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify valid values</td>
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
      <td>Audience controls<br>· Retargeting custom audience<br>(Optional)<br>&nbsp;&nbsp;· Include audience (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>audience_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience controls<br>· Retargeting custom audience<br>(Optional)<br>&nbsp;&nbsp;· Exclude audience (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience suggestions<br>· Age<br> (Optional)<br><br><p><span style="color:darkred"><b>Note</b></span>: Audience suggestions guide automatic targeting by choosing additional audience settings. These serve as suggestions only, and delivery to those audiences is not guaranteed.</p></td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>age_groups</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience suggestions<br>· Gender<br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>gender</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Interests<br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Purchase intention<br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>purchase_intention_keyword_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Audience suggestions<br>· Interests & behaviors<br>&nbsp;&nbsp;· Video interactions<br>&nbsp;&nbsp;· Creator interactions<br>&nbsp;&nbsp;· Hashtag interactions<br>(Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>actions</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td rowspan="22">Custom targeting<br>(<code>targeting_optimization_mode</code> <br>as <code>MANUAL</code>)</td>
      <td>Demographics<br>· Location</td>
      <td>Specify a valid value.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify a valid value.<br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
    </tr>
    <tr>
      <td>Demographics<br>· HFSS Product/Brand</td>
      <td>Enabled or disabled<ul><li>You can enable this setting when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</li></ul></td>
      <td><code>is_hfss</code></td>
      <td><code>true</code> or <code>false</code>.<br><br>You can set it to <code>true</code> when your targeting locations include locations in the UK, Australia, New Zealand, and the European Union.</td>
    </tr>
    <tr>
      <td>Demographics<br>· LHF (Less Healthy Foods) compliance</td>
      <td>Enabled or disabled<ul><li>This setting must be enabled when your targeting locations include locations in the UK and HFSS Product/Brand is enabled.</li></ul></td>
      <td><code>is_lhf_compliance</code></td>
      <td><code>true</code> or <code>false</code>.<br><br> You need to set it to <code>true</code> when your targeting locations include locations in the UK and <code>is_hfss</code> is <code>true</code>.</td>
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
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>audience_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
    </tr>
    <tr>
      <td>Custom audience<br>· Exclude audience<br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level Realtime API is enabled, this setting is not supported.</p></td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified<br><br><p><span style="color:darkred"><b>Note</b></span>: When at the campaign level <code>rta_id</code> is specified, this setting is not supported.</p></td>
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
      <td>Detailed targeting<br>· Spending power (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>spending_power</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting<br>· Household income (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>household_income</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Operating system (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>operating_systems</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· OS versions (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>min_android_version</code><br><code>min_ios_version</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Device model (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_model_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Connection type (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>network_types</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Carriers (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>carrier_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Internet service provider (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>isp_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device<br>· Device price (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_price_ranges</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Use saved audience (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>saved_audience_id</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com//open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "app_id": "{{app_id}}",
    "promotion_type": "APP_IOS",
    "optimization_goal": "IN_APP_EVENT",
    "optimization_event": "ADD_PAYMENT_INFO",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_optimization_mode": "AUTOMATIC",
    "targeting_spec": {
        "location_ids": [
            "{{location_id}}"
        ],
        "spc_audience_age": "OVER_EIGHTEEN",
        "excluded_audience_ids": [
            "{{excluded_audience_id}",
            "{{excluded_audience_id}}"
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
        "app_id": "{{app_id}}",
        "attribution_event_count": "ONCE",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": 0,
        "create_time": "{{create_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_bid_type": "AEO",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "gaming_ad_compliance_agreement": "OFF",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "ADD_PAYMENT_INFO",
        "optimization_goal": "IN_APP_EVENT",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_GLOBAL_APP_BUNDLE",
            "PLACEMENT_PANGLE"
        ],
        "promotion_type": "APP_IOS",
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
            "excluded_audience_ids": [
                "{{excluded_audience_id}}",
                "{{excluded_audience_id}}"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_id}}"
            ],
            "operating_systems": [
                "IOS"
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

### Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:20%">Setting</th>
      <th style="width:28%">Requirement</th>
      <th style="width:21%">Parameter</th>
      <th style="width:31%">How to configure the parameter</th>
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
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
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
      <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives </b>that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
      <td><code>ad_format</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
      <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
    </tr>
    <tr>
      <td>Selling points (Optional)</td>
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
      <td>Interactive add-ons<br>(Optional)<br>(When the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Destination </td>
      <td><ul><li>When the promoted App is an Android App: Google Play or TikTok Instant Page</li><li>When the promoted App is an iOS App: App Store</li></ul></td>
      <td><code>landing_page_url_list</code><br><code>page_list</code></td>
      <td><ul><li>When <code>promotion_type</code> at the ad group level is <code>APP_ANDROID</code>, you can set the Destination to Google Play or TikTok Instant Page.<ul><li>To set the Destination to Google Play, do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li><li>To set the Destination to TikTok Instant Page, specify a valid TikTok Instant Page via <code>landing_page_url_list</code> and do not specify <code>landing_page_url_list</code>.<ul><li>The app details on the TikTok Instant Page should match the promoted <code>app_id</code> at the ad group level.</li></ul></li></ul></li><li>When <code>promotion_type</code> at the ad group level is <code>APP_IOS</code>, you can only set the Destination as App Store.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Add specific custom product page as destination (Optional)<br>(When Destination is App Store)</td>
      <td>When the promoted App is an iOS App:<ul><li>Disabled or enabled with a valid custom product page.</li></ul></td>
      <td><code>custom_product_page_list</code></td>
      <td>When <code>promotion_type</code> at the ad group level is <code>APP_IOS</code>: Not specified or specify a custom product page.</td>
    </tr>
    <tr>
      <td rowspan="2">Direct users to deeplink first<br>(Optional)<br>(When Destination is Google Play/App Store)</td>
      <td rowspan="2">Disabled or enabled with a valid deeplink.<br>A deeplink is a URL that takes people to a specific location within your app, such as a product page.</td>
      <td><code>deeplink_type</code></td>
      <td>Not specified or specify <code>NORMAL</code></td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td><ul><li>When <code>deeplink_type</code> is not specified:<ul><li>Not specified</li></ul></li><li>When <code>deeplink_type</code> is <code>NORMAL</code>:<ul><li>Specify a custom deeplink. Scheme, Universal, and App Link formats are supported.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;· Website<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_pixel_id</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
      <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;· Offline events<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>tracking_offline_event_set_ids</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com//open_api/v1.3/smart_plus/ad/create/' \
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
                "identity_type": "TT_USER",
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
    "custom_product_page_list": [
        {
            "custom_product_page_url": "{{custom_product_page_url}}"
        }
    ],
    "deeplink_list": [
        {
            "deeplink": "{{deeplink}}",
            "deeplink_type": "NORMAL"
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
            "dark_post_status": "OFF",
            "deeplink_utm_params": [],
            "fallback_type": "UNSET",
            "phone_info": {},
            "tracking_info": {
                "tracking_app_id": "{{tracking_app_id}}"
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
            }
        ],
        "custom_product_page_list": [
            {
                "custom_product_page_url": "{{custom_product_page_url}}"
            }
        ],
        "deeplink_list": [
            {
                "deeplink": "{{deeplink}}",
                "deeplink_type": "NORMAL"
            }
        ],
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


