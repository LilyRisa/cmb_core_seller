---
title: "Create an Upgraded Smart+ Lead Generation Campaign with optimization location as TikTok direct messages"
doc_id: 1847302969710913
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Lead Generation Campaigns / Create an Upgraded Smart+ Lead Generation Campaign with optimization location as TikTok direct messages"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ Lead Generation Campaign with optimization location as TikTok direct messages

This article introduces how to create an Upgraded Smart+ Lead Generation Campaign with optimization location as TikTok direct messages (also known as Upgraded Smart+ TikTok Direct Messaging Ads). This enables the ad audience to directly message your authorized TikTok account in the TikTok app by clicking the "Send message" call-to-action button within the ad.

# Prerequisites
The owner of the authorized TikTok account has set **Potential connections** and **Others on TikTok** to **Requests** for Direct Messages on the TikTok account, allowing anyone to send direct messages to the TikTok account. To learn how to configure the settings, refer to [How to manage who can send you direct messages](https://support.tiktok.com/en/account-and-privacy/account-privacy-settings/direct-message#7).


# Steps
For Upgraded Smart+ TikTok Direct Messaging Ads, choose the appropriate setup based on whether you plan to use a catalog: <a href="#item-link-For the non-catalog solution">the non-catalog solution</a> or <a href="#item-link-For the catalog solution">the catalog solution</a>.

## For the non-catalog solution
In the non-catalog solution, no catalog is integrated into an Upgraded Smart+ TikTok Direct Messaging Ad. Ensure `catalog_enabled` is set to `false` when configuring the campaign.

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
            <td>Special ad categories <br>(Optional)</td>
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

#### Example
**Creating a CBO campaign**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "LEAD_GENERATION",
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
            <td>Optimization and bidding<br>· Goal </td>
            <td>Any of the following types:<ul><li>Click<ul><li>Show your ads to people that are most likely to click on them.</li></ul></li><li>Conversations<ul><li>Show your ads to people that are most likely to start a TikTok direct message conversation.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups.</td>
            <td><code>optimization_goal</code></td>
            <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERSATION</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, omit <code>optimization_event</code> or set <code>optimization_event</code> to <code>MESSAGE</code>.</li></ul></td>
        </tr>
        <tr>
            <td rowspan="2">Optimization and bidding<br>· Optimization location</td>
            <td rowspan="2">TikTok direct messages: Collect and connect with leads through TikTok direct messages<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
            <td><code>promotion_type</code></td>
            <td><code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</td>
        </tr>
        <tr>
            <td><code>promotion_target_type</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td rowspan="3">Optimization and bidding<br>· Select a TikTok account<br>(Optional)</td>
            <td rowspan="3">Select a valid TikTok account<br><br><p><span style="color:darkred"><b>Note</b></span>: Selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
            <td><code>identity_type</code></td>
            <td>Any of the following values when you're allowlisted for selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads:<ul><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><ul><li>If you want to use an identity in TikTok Direct Messaging Ads, you need to have direct message permission for the identity. To confirm this, call <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740218453385217" target="_blank">/identity/info/</a> and check whether the returned <code>can_manage_message</code> is <code>true</code>.<ul><li>If the returned <code>can_manage_message</code> is <code>false</code> and <code>identity_type</code> is <code>TT_USER</code>, you can navigate to <a href="https://ads.tiktok.com/ac/page/settings" target="_blank">the ad account user setting page</a>. In the <b>Profile</b> section of the <b>Personal Info</b> tab, click <b>Edit access</b> to edit your permissions for the associated TikTok account. Ensure that you have <b>"Read and manage messages in your inbox"</b> permission for the TikTok account.</li><li>If the returned <code>can_manage_message</code> is <code>false</code> and <code>identity_type</code> is <code>BC_AUTH_TT</code>, you can ask the Business Center admin to grant you direct message permission for the TikTok account associated with the identity by using <a href="https://business-api.tiktok.com/portal/docs?id=1739438211077121" target="_blank">/bc/asset/assign/</a>. Set <code>asset_type</code> to <code>TT_ACCOUNT</code> and ensure that the <code>tt_account_roles</code> value includes at least <code>POST</code> and <code>DIRECT_MESSAGE</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
        </tr>
        <tr>
            <td><code>identity_id</code></td>
            <td>Specify a valid value when you're allowlisted for selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads.<br><br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: Selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative</td>
        </tr>
        <tr>
            <td><code>identity_authorized_bc_id</code></td>
            <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
        </tr>
        <tr>
            <td rowspan="2">Optimization and bidding<br>· Bid Strategy and (Optional) Target CPA </td>
            <td rowspan="2">The bid strategy can be any of the following:<ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></td>
            <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code></td>
            <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>bid_price</code> or <code>conversion_bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
        </tr>
        <tr>
            <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Optimization and bidding<br>· Billing event</td>
            <td><ul><li>If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Conversations, the billing event should be oCPM.</li></ul></td>
            <td><code>billing_event</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, set this field to <code>OCPM</code>.</li></ul></td>
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
            <td>Budget and schedule<br>· Goal-based budget increase (when Bid Strategy is Target CPA)</td>
            <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level: <ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></li></ul></td>
            <td><code>budget_auto_adjust_strategy</code></td>
            <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level: <ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Add spend target per ad group<br>&nbsp;&nbsp;· Daily minimum</td>
            <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
            <td><code>min_budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Schedule</td>
            <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></td>
            <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
            <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li></ul></td>
        </tr>
        <tr>
            <td>Budget and schedule<br>· Dayparting <br>(Optional)</td>
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
            <td rowspan="2">Placements and brand safety<br>· Placement</td>
            <td rowspan="2">TikTok</td>
            <td><code>placement_type</code></td>
            <td><code>PLACEMENT_TYPE_NORMAL</code></td>
        </tr>
        <tr>
            <td><code>placements</code></td>
            <td><code>["PLACEMENT_TIKTOK"]</code></td>
        </tr>
        <tr>
            <td>Placements and brand safety<br>· Brand safety and suitability</td>
            <td>These settings only apply to <b>TikTok in-feed ads</b>. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
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
    "optimization_goal": "CONVERSATION",
    "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
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
(code curl http)
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
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "engaged_view_attribution_window": "ONE_DAY",
        "incentive_offer_type": "COST_CAP_AND_MIN_ROAS",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "MESSAGE",
        "optimization_goal": "CONVERSATION",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
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
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
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
            <td>Destination</td>
            <td>TikTok direct message</td>
            <td><code>page_list</code><br><code>landing_page_url_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Welcome message</td>
            <td>Enabled<br>Greet people with an automatic message sent by your TikTok account after they start a direct message conversation from one of your ads.</td>
            <td><code>auto_message_list</code></td>
            <td>Specify a valid value.<ul><li>To obtain a list of welcome messages within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1822106498804738" target="_blank">/creative/auto_message/get/</a>.</li><li>To create a welcome message within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1822106113771521" target="_blank">/creative/auto_message/create/</a>.</li></ul></td>
        </tr>
        <tr>
            <td rowspan="2">Creative assets <br>· Ad creatives & Ad text</td>
            <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: If your ad account is allowlisted for selecting a TikTok account at the ad group level in TikTok Direct Messaging Ads, specify the same identity information at the ad group level.</td>
            <td><code>ad_format</code></td>
            <td>Specify a valid value</td>
        </tr>
        <tr>
            <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
            <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: If your ad account is allowlisted for selecting a TikTok account at the ad group level in TikTok Direct Messaging Adsx, specify the identity information (<code>identity_id</code>, <code>identity_type</code>, and if applicable, <code>identity_authorized_bc_id</code>) at the ad group level.</td>
        </tr>
        <tr>
            <td rowspan="2">Call to action</td>
            <td rowspan="2">Send message</td>
            <td><code>call_to_action_id</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>call_to_action_list</code></td>
            <td><code>[{"call_to_action": "SEND_MESSAGE"}]</code></td>
        </tr>
        <tr>
            <td>Interactive add-ons<br>(Optional)<br>(When the ad creatives include videos or video posts)</td>
            <td>Disabled or enabled up to one add-on in the following type:<ul><li>Display Card</li></ul></td>
            <td><code>interactive_add_on_list</code></td>
            <td>Not specified or specify the ID of the following add-on type:<ul><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>You can only use the add-on when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li></ul></td>
        </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
        <tr>
            <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;· App events <br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>tracking_app_id</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Impression tracking URL <br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>impression_tracking_url</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking<br>· Third-party tracking settings<br>&nbsp;&nbsp;· Click tracking URL <br>(Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>click_tracking_url</code></td>
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
    "auto_message_list": [
        {
            "auto_message_id": "{{auto_message_id}}"
        }
    ],
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
        },
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
        },
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "tiktok_item_id": "{{tiktok_item_id}}",
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
    "call_to_action_list": [
        {
            "call_to_action": "SEND_MESSAGE"
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
        "ad_configuration": {
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
        "auto_message_list": [
            {
                "auto_message_id": "{{auto_message_id}}"
            }
        ],
        "call_to_action_list": [
            {
                "call_to_action": "SEND_MESSAGE"
            }
        ],
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
                    "material_name": "{{material_name}}",
                    "video_info": {
                        "file_name": "{{file_name}}",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "SINGLE_VIDEO",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "tiktok_item_id": "{{tiktok_item_id}}"
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "CAROUSEL_ADS",
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
            }
        ],
        "deeplink_list": [],
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
## For the catalog solution
In the catalog solution, an Auto-Inventory or Auto-Model catalog is integrated into an Upgraded Smart+ TikTok Direct Messaging Ad. Ensure `catalog_enabled` is set to `true` when configuring the campaign.

### Prerequisites for the catalog solution
The catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads requires setting up an **Auto-Inventory catalog or Auto-Model catalog** in Business Center first.

To set up the catalog:
1. Create an Auto-Inventory or Auto-Model catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961).
2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
3. Check the product handling results.
	- For CSV feed and JSON schema, use [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
		- Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
	- For online feed, use [/catalog/feed/log/](https://business-api.tiktok.com/portal/docs?id=1740665225631810).
		- Pass in the `feed_id` obtained from Step 2. If the field `error_count` in the response is not 0, examine your online feed and return to Step 2 to reupload the product.
4. (Optional) Create an Auto-Inventory or Auto-Model product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
<br>If you want to have products selected from a product set at the ad level, creating a product set is necessary. Otherwise, you can skip this step.
5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
<br>You can also choose `advertiser_role` that you want to assign to the members invited.
6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
<br>Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.


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
            <td>Enabled<br>To create an Upgraded Smart+ TikTok Direct Messaging Ad without using a catalog, see <a href="#item-link-For the non-catalog solution">For the non-catalog solution</a>.</td>
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

#### Example
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
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "catalog_enabled": true,
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
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
        "budget": 50.01,
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
            <td>Catalog</td>
            <td>Specify <b>an Auto-Inventory or Auto-Model catalog with at least one approved product</b></td>
            <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
            <td>Specify valid values.<br><br>The Auto-Inventory or Auto-Model catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1.<br><br>To learn about how to create an Auto-Inventory or Auto-Model catalog, see <a href="#item-link-Prerequisites for the catalog solution">Prerequisites for the catalog solution</a>.</td>
        </tr>
        <tr>
            <td>Optimization and bidding <br>· Goal </td>
            <td>Any of the following types:<ul><li>Click<ul><li>Show your ads to people that are most likely to click on them.</li></ul></li><li>Conversations<ul><li>Show your ads to people that are most likely to start a TikTok direct message conversation.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups.</p></td>
            <td><code>optimization_goal</code></td>
            <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERSATION</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, omit <code>optimization_event</code> or set <code>optimization_event</code> to <code>MESSAGE</code>.</li></ul></p></td>
        </tr>
        <tr>
            <td rowspan="2">Optimization and bidding <br>· Optimization location</td>
            <td rowspan="2">TikTok direct messages: Collect and connect with leads through TikTok direct messages<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</p></td>
            <td><code>promotion_type</code></td>
            <td><code>LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</p></td>
        </tr>
        <tr>
            <td><code>promotion_target_type</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td rowspan="2">Optimization and bidding <br>· Bid Strategy and (Optional) Target CPA </td>
            <td rowspan="2">The bid strategy can be any of the following:<ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
            <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code></td>
            <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>bid_price</code> or <code>conversion_bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> </li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></td>
        </tr>
        <tr>
            <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Optimization and bidding <br>· Billing event</td>
            <td><ul><li>If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Conversations, the billing event should be oCPM.</li></ul></td>
            <td><code>billing_event</code></td>
            <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, set this field to <code>OCPM</code>.</li></ul></td>
        </tr>
        <tr>
            <td>Optimization and bidding <br>· Delivery Type</td>
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
            <td>Budget and schedule <br>· Goal-based budget increase <br>(when Bid Strategy is Target CPA)</td>
            <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level: <ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></li></ul></td>
            <td><code>budget_auto_adjust_strategy</code> </td>
            <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level: <ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
        </tr>
        <tr>
            <td>Budget and schedule <br>· Add spend target per ad group <br>&nbsp;&nbsp;· Daily minimum</td>
            <td><ul><li>When you use a daily campaign budget:<ul><li>When Bid strategy is Maximum Delivery (Highest volume), you can enable or disable this setting.</li><li>When Bid Strategy is Target CPA or Target ROAS, this setting is not supported.</li></ul></li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
            <td><code>min_budget</code></td>
            <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level:<ul><li>When <code>bid_type</code> is <code>BID_TYPE_NO_BID</code> at the ad group level , you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code>is<code>VO_HIGHEST_VALUE</code> at the ad group level, this field is not supported.</li></ul></li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
        </tr>
        <tr>
            <td>Budget and schedule <br>· Schedule</td>
            <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios: <ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
            <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
            <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
        </tr>
        <tr>
            <td>Budget and schedule <br>· Dayparting (Optional)</td>
            <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
            <td><code>dayparting</code></td>
            <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
        </tr>
        <tr>
            <td>Audience targeting optimization mode</td>
            <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
            <td><code>targeting_optimization_mode</code></td>
            <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
        </tr>
        <tr>
            <td rowspan="2">Placements and brand safety <br>· Placement</td>
            <td rowspan="2">TikTok</td>
            <td><code>placement_type</code></td>
            <td><code>PLACEMENT_TYPE_NORMAL</code></td>
        </tr>
        <tr>
            <td><code>placements</code></td>
            <td><code>["PLACEMENT_TIKTOK"]</code></td>
        </tr>
        <tr>
            <td>Placements and brand safety <br>· Brand safety and suitability</td>
            <td>These settings only apply to <b>TikTok in-feed ads</b> and Search Ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
            <td>N/A</td>
            <td>N/A<br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
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

#### Example
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
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "optimization_goal": "CONVERSATION",
    "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
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
(code http)
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
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "engaged_view_attribution_window": "ONE_DAY",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "MESSAGE",
        "optimization_goal": "CONVERSATION",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_TT_DIRECT_MESSAGE",
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
            <td>Ad name (Optional)</td>
            <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
            <td><code>ad_name</code></td>
            <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code>(empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
        </tr>
        <tr>
            <td>Product source details <br>· Products</td>
            <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
            <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
            <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to specify <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to specify <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to specify a maximum of 20 available products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each Auto-Inventory or Auto-Model product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td rowspan="3">Identity <br>· Select a TikTok account</td>
            <td rowspan="3">Select a valid TikTok account</td>
            <td><code>identity_type</code></td>
            <td>Any of the following values:<ul><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul> If you want to use an identity in TikTok Direct Messaging Ads, you need to have direct message permission for the identity. To confirm this, call <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740218453385217" target="_blank">/identity/info/</a> and check whether the returned <code>can_manage_message</code> is <code>true</code>.<ul><li>If the returned <code>can_manage_message</code> is <code>false</code> and <code>identity_type</code> is <code>TT_USER</code>, you can navigate to <a href="https://ads.tiktok.com/ac/page/settings" target="_blank">the ad account user setting page</a>. In the <b>Profile</b> section of the <b>Personal Info</b> tab, click <b>Edit access</b> to edit your permissions for the associated TikTok account. Ensure that you have <b>"Read and manage messages in your inbox"</b> permission for the TikTok account.</li><li>If the returned <code>can_manage_message</code> is <code>false</code> and <code>identity_type</code> is <code>BC_AUTH_TT</code>, you can ask the Business Center admin to grant you direct message permission for the TikTok account associated with the identity by using <a href="https://business-api.tiktok.com/portal/docs?id=1739438211077121" target="_blank">/bc/asset/assign/</a>. Set <code>asset_type</code> to <code>TT_ACCOUNT</code> and ensure that the <code>tt_account_roles</code> value includes at least <code>POST</code> and <code>DIRECT_MESSAGE</code>.</li></ul></td>
        </tr>
        <tr>
            <td><code>identity_id</code></td>
            <td>Specify a valid value.<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
        </tr>
        <tr>
            <td><code>identity_authorized_bc_id</code></td>
            <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
        </tr>
        <tr>
            <td>Destination</td>
            <td>TikTok direct message</td>
            <td><code>page_list</code><br><code>landing_page_url_list</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td>Message template <br>(Welcome message)</td>
            <td>Enabled<br>Greet people with an automatic message sent by your TikTok account after they start a direct message conversation from one of your ads.</td>
            <td><code>auto_message_list</code></td>
            <td>Specify a valid value.<ul><li>To obtain a list of welcome messages within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1822106498804738" target="_blank">/creative/auto_message/get/</a>.</li><li>To create a welcome message within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1822106113771521" target="_blank">/creative/auto_message/create/</a>.</li></ul></td>
        </tr>
        <tr>
            <td>Creative assets <br>· Create catalog carousel from your catalog</td>
            <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Images and videos in your catalog will be used to automatically generate optimized Catalog Ads in all formats for your products.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads">Customize the ad format combinations in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads</a>.</li></ul></li><li>Disabled<ul><li>Images and videos in your catalog will not be used to automatically generate optimized Catalog Ads in all formats for your products. The system will automatically create single video ads using your specified content.</li><li>Requirements:<ul><li>Specify a total of 1 to 50 items, either as individual videos, video posts, or a combination of both:<ul><li>For Spark Ads Pull, specify video posts.</li><li>For Spark Ads Push, specify  videos with cover images.</li></ul></li><li>Specify 1-5 ad texts</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</p></td>
            <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>ad_format</code></td>
            <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code>, specify a valid value for <code>ad_format</code> in each <code>creative_info</code> object,  and specify an end card image via <code>image_info</code> and a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads">Customize the ad format combinations in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When <code>catalog_creative_toggle</code> is <code>true</code>, both <code>image_info</code> and <code>music_info</code> are required.</p><ul><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>:<ul><li>Set <code>ad_format</code> to <code>SINGLE_VIDEO</code>  and one to five ad texts (<code>ad_text_list</code>) </li><li>Specify a total of 1 to 50 items, either as individual videos, video posts, or a combination of both:<ul><li>To create Spark Ads by pulling existing TikTok posts from the TikTok account, specify video posts through <code>tiktok_item_id</code></li><li>To create Spark Ads by pushing videos to the TikTok account, specify one to 50 videos through <code>video_info</code> with cover images through <code>image_info</code></li></ul></li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Creative assets <br>· Product information<br> (Optional for Auto-Inventory catalogs)</td>
            <td><ul><li>When an <b>Auto-Inventory</b> catalog is specified at the ad group level and the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Disabled or enabled with up to two product attributes to display alongside the vehicle title in your ads:<ul><li>Dealer name</li><li>Make</li><li>Model</li><li>Year</li><li>Mileage</li><li>Exterior color</li><li>Trim</li><li>City</li><li>State of vehicle</li><li>Price</li><li>Sale price</li></ul>The selected product details will be automatically added from your catalog to your ads for best results.<br>By default, only the title of each product will be displayed in your ads</li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
            <td><code>product_display_field_list</code></td>
            <td><ul><li>When an <b>Auto-Inventory</b> catalog is specified through<code>catalog_id</code> at the ad group level and <code>catalog_creative_toggle</code> is set to <code>true</code>:<ul><li>Not specified or specify up to two attributes from the following values:<ul><li><code>DEALER_NAME</code></li><li><code>MAKE</code></li><li><code>MODEL</code></li><li><code>YEAR</code></li><li><code>MILEAGE</code></li><li><code>EXTERIOR_COLOR</code></li><li><code>TRIM</code></li><li><code>ADDRESS_CITY</code></li><li><code>VEHICLE_STATE</code></li><li><code>PRICE</code></li><li><code>SALE_PRICE</code></li></ul>The title of your vehicle will be automatically displayed alongside the specified attributes in your ads.<br>For example, if you set this field to <code>["MAKE","PRICE"]</code>, the title, make and price of your vehicle will be displayed in your ads.<br>By default, only the title of each product will be displayed in your ads.</li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified </li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Creative assets <br>· End card</td>
            <td><ul><li>When the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Enabled. Select the last photo to appear in the carousel to encourage people to browse your full inventory.</li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul>- </td>
            <td><code>image_info</code></td>
            <td><ul><li>When <code>catalog_creative_toggle</code> is <code>true</code>:<ul><li>Specify one end card. The end card will be displayed at the end of the carousel.<br>To get image IDs, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739067433456642" target="_blank">/file/image/ad/upload/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a>.<br>The image should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: below 50 MB.</li></ul></li><li>The value of the parameter <code>is_carousel_usable</code> returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a> for the image is <code>true</code>.</li></ul></li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified </li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Creative assets <br>· Call to action for end card</td>
            <td><ul><li>When the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Enabled with one of the following options:<ul><li>Search inventory (Recommended)</li><li>Learn more</li><li>Shop now</li><li>Sign up</li><li>Contact us</li><li>Book now</li><li>Read more</li><li>View now</li><li>Order now</li><li>Send message</li></ul></li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
            <td><code>end_card_cta</code></td>
            <td><ul><li>When <code>catalog_creative_toggle</code> is <code>true</code>:<ul><li>Enabled with any of the following values:<ul><li><code>SEARCH_INVENTORY</code> (Recommended)</li><li><code>LEARN_MORE</code></li><li><code>SHOP_NOW</code></li><li><code>SIGN_UP</code></li><li><code>CONTACT_US</code></li><li><code>BOOK_NOW</code></li><li><code>READ_MORE</code></li><li><code>VIEW_MORE</code></li><li><code>ORDER_NOW</code></li><li><code>SEND_MESSAGE</code></li></ul></li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified </li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Creative assets <br>· Disclaimer <br>(Optional for the Auto-Model catalog)</td>
            <td><ul><li>When an <b>Auto-Model</b> catalog is specified at the ad group level and the "Create catalog carousel from your catalog" setting is enabled:<ul><li>Disabled or enabled with any of the following options:<ul><li>Emission disclaimer</li><li>Discount or offer disclaimer</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Before using any type of disclaimer, ensure that you have specified the necessary disclaimer details for each vehicle in the Auto-Model catalog.</p><ul><li>To use the emission disclaimer, ensure that you have provided the following details for each vehicle:<ul><li>emission disclaimer</li><li>emission disclaimer URL</li><li>emission overlay disclaimer</li><li>emission image</li></ul></li><li>To use the discount or offer disclaimer, ensure that you have provided the following details for each vehicle:<ul><li>offer disclaimer</li><li>offer disclaimer URL</li></ul></li></ul></li></ul></li><li>When the "Create catalog carousel from your catalog" setting is disabled:<ul><li>Disabled</li></ul></li></ul></td>
            <td><code>auto_disclaimer_types</code></td>
            <td><ul><li>When an <b>Auto-Model</b> catalog is specified through<code>catalog_id</code> at the ad group level and <code>catalog_creative_toggle</code> is set to <code>true</code>:<ul><li>Disabled or enabled with any of the following values:<ul><li><code>EMISSION</code></li><li><code>DISCOUNT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Before using any type of disclaimer, ensure that you have specified the necessary disclaimer details for each vehicle in the Auto-Model catalog.<ul><li>To use the emission disclaimer, ensure that you have provided the following details for each vehicle:<ul><li><code>emission_disclaimer</code></li><li><code>emission_disclaimer_url</code></li><li><code>emission_overlay_disclaimer</code></li><li><code>emission_image_link</code></li></ul></li><li>To use the discount or offer disclaimer, ensure that you have provided the following details for each vehicle:<ul><li><code>offer_disclaimer</code></li><li><code>offer_disclaimer_url</code></li></ul></li></ul>To get the attributes of vehicles in an Auto-Model catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.<br>To update the attributes of vehicles in an Auto-Model catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546" target="_blank">/catalog/product/update/</a>.</p></li></ul></li><li>When <code>catalog_creative_toggle</code> is set to <code>false</code>:<ul><li>Not specified </li></ul></li></ul></td>
        </tr>
        <tr>
            <td rowspan="2">Call to action</td>
            <td rowspan="2">Send message</td>
            <td><code>call_to_action_id</code></td>
            <td>Not specified</td>
        </tr>
        <tr>
            <td><code>call_to_action_list</code></td>
            <td><code>[{"call_to_action": "SEND_MESSAGE"}]</code></td>
        </tr>
        <tr>
            <td>Interactive add-on <br>(Optional)</td>
            <td>Disabled or Enabled with an Inventory Card<br><br>When the Interactive add-on is enabled:<ul><li>For Automotive Ads for Inventory or  Automotive Ads for Models with Single Video format, auto-generated Vehicle Cards will be shown after two seconds of video play. If the audience continues swiping through the Vehicle Cards, the Inventory Card will be displayed at the end.</li><li>The Vehicle Cards showcase the following attributes of the vehicle as defined in the catalog, and a CTA button, which will direct users to the third-party landing page.<ul><li>Main image</li><li>Title</li><li>State of the vehicle</li><li>Mileage</li><li>Price</li></ul></li><li>The Inventory Card includes:<ul><li>A customized image</li><li>A customized description</li><li>Estimated catalog size</li><li>CTA button "Search Inventory", which directs the audience to an auto-generated Instant Product Page</li></ul></li></ul></td>
            <td><code>interactive_add_on_list</code></td>
            <td>Not specified, or specify the ID of an Inventory Card portfolio.<br>To get the Inventory Card portfolio ID (<code>creative_portfolio_id</code>), see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Inventory%20Card" target="_blank">Cards</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: You can update the Inventory Card portfolio in your Automotive Ads for Inventory or Automotive Ads for Models to a different Inventory Card portfolio.</p>When the Interactive add-on is enabled:<ul><li>The Vehicle Cards showcase a CTA button and the following attributes of the vehicle:<ul><li><code>image_url</code></li><li><code>title</code></li><li><code>state_of_vehicle</code></li><li><code>mileage</code></li><li><code>price</code></li></ul></li><li>To get the attributes of a vehicle, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li><li>The Inventory Card includes a CTA button, estimated catalog size, and the following attributes of the Inventory Card:<ul><li><code>image_id</code></li><li><code>description</code></li></ul></li><li>To get the details of an Inventory Card, use <a href="https://business-api.tiktok.com/portal/docs?id=1766324010279938" target="_blank">/creative/portfolio/list/</a>.</li></ul></td>
        </tr>
        <tr>
            <td>Tracking <br>· TikTok events tracking <br>&nbsp;&nbsp;· App events (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>tracking_app_id</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking <br>· Third-party tracking settings <br>&nbsp;&nbsp;· Impression tracking URL (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>impression_tracking_url</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
        <tr>
            <td>Tracking <br>· Third-party tracking settings <br>&nbsp;&nbsp;· Click tracking URL (Optional)</td>
            <td>Enabled or disabled</td>
            <td><code>click_tracking_url</code></td>
            <td>Specify a valid value or not specified</td>
        </tr>
    </tbody>
</table>

#### Customize the ad format combinations in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads
If you enable auto-selection of catalog creatives (<code>catalog_creative_toggle</code> is <code>true</code>) in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads, the system will automatically create ads using combinations of two possible formats: catalog carousel, and single video.

The possible ad format combinations are illustrated in the following table.

<table>
    <thead>
        <tr>
            <th style="width:30%">Is the catalog carousel format available?</th>
            <th style="width:30%">Is the single video format available?</th>
            <th style="width:40%">Ad format combination for your ads</th>
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

To ensure certain ad formats are available in the catalog solution for Upgraded Smart+ TikTok Direct Messaging Ads, follow the instructions in the following table.

<table>
    <thead>
        <tr>
      		<th style="width:17%"><b>Ad format</b></th>
      		<th style="width:20%"><b>Ad format description</b></th>
      		<th style="width:25%"><b>How to ensure the ad format is available</b></th>
      		<th style="width:38%"><b>How to configure the parameters</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="3">catalog carousel</td>
            <td rowspan="3">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
            <td>The catalog needs to contain at least two products supported in Catalog Carousel Ads.</td>
            <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li> Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li> Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Specify a piece of music supported in Catalog Carousel Ads.</td>
            <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://business-api.tiktok.com/portal/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Specify an end card image</td>
            <td>Pass one end card image that is valid for use in Automotive Catalog Carousel Ads via <code>image_info</code>.<br>To get image IDs, use <a href="https://business-api.tiktok.com/portal/docs?id=1739067433456642" target="_blank">/file/image/ad/upload/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a>.<br>The image should meet the following requirements at the same time:<ul><li>Specifications:<ul><li>File Type: JPG, JPEG, or PNG.</li><li>Image Resolution: a maximum of 1242 x 2340 pixels or 2340 x 1242 pixels.</li><li>Aspect Ratio: a maximum of 9:20 or 20:9.</li><li>File Size: below 50 MB.</li></ul></li><li>The value of the parameter <code>is_carousel_usable</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740052016789506" target="_blank">/file/image/ad/search/</a> for the image is <code>true</code>.</li></ul></td>
        </tr>
        <tr>
            <td>single video</td>
            <td>Use specified video creatives to promote your products.</td>
            <td>Specify additional videos or TikTok video posts</td>
            <td>Specify a total of 1 to 50 items, either as individual videos, video posts, or a combination of both.<ul><li>For Spark Ads Pull, specify additional TikTok video posts via <code>tiktok_item_id</code>.</li><li>For Spark Ads Push, specify additional videos via <code>video_info</code> and cover images via <code>image_info</code>.</li></ul></td>
        </tr>
    </tbody>
</table>


#### Example
Request
```xcodeblock
(code http)
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
                "ad_format": "CATALOG_CAROUSEL",
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
        },
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "tiktok_item_id": "{{tiktok_item_id}}",
                "identity_type": "TT_USER",
                "identity_id": "{{identity_id}}"
            }
        }
    ],
    "auto_message_list": [
        {
            "auto_message_id": "{{auto_message_id}}"
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "ad_text"
        },
        {
            "ad_text": "ad_text"
        },
        {
            "ad_text": "ad_text"
        },
        {
            "ad_text": "ad_text"
        },
        {
            "ad_text": "ad_text"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "SEND_MESSAGE"
        }
    ],
    "interactive_add_on_list": [
        {
            "card_id": "{{card_id}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "catalog_creative_toggle": true,
        "end_card_cta": "SEND_MESSAGE"
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
        "ad_configuration": {
            "catalog_creative_toggle": true,
            "dark_post_status": "OFF",
            "deeplink_utm_params": [],
            "end_card_cta": "SEND_MESSAGE",
            "phone_info": {},
            "product_specific_type": "ALL",
            "tracking_info": {},
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "ad_text"
            },
            {
                "ad_text": "ad_text"
            },
            {
                "ad_text": "ad_text"
            },
            {
                "ad_text": "ad_text"
            },
            {
                "ad_text": "ad_text"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [
            {
                "auto_message_id": "{{auto_message_id}}"
            }
        ],
        "call_to_action_list": [
            {
                "call_to_action": "SEND_MESSAGE"
            }
        ],
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
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "SINGLE_VIDEO",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "tiktok_item_id": "{{tiktok_item_id}}"
                },
                "material_operation_status": "ENABLE"
            },
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
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
        "custom_product_page_list": [],
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



