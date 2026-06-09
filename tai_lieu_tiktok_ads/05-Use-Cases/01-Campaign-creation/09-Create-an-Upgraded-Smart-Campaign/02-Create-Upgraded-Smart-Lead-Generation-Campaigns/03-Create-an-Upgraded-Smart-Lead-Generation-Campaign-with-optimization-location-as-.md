---
title: "Create an Upgraded Smart+ Lead Generation Campaign with optimization location as instant messaging apps"
doc_id: 1847302988449921
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Lead Generation Campaigns / Create an Upgraded Smart+ Lead Generation Campaign with optimization location as instant messaging apps"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Upgraded Smart+ Lead Generation Campaign with optimization location as instant messaging apps

This article introduces how to create an Upgraded Smart+ Lead Generation Campaign with optimization location as instant messaging apps. This enables the ad audience to take two different actions based on their app installation status by clicking the call-to-action button within the ad:
- If the audience has installed the instant messaging app (such as WhatsApp), they can send a message to you (advertiser) on the App.
- If the audience hasn't installed the instant messaging app, they will be directed to view a Custom TikTok Instant Page.

# Prerequisites
- We recommend connecting to one of our integrated [Message Management Tools](https://ads.tiktok.com/help/article/about-message-management-tools) (MMTs) before setting up your campaign to allow for deeper optimization goals and better performance.
- If you've already integrated with an MMT and are optimizing for Conversations, make sure to set up the TikTok integration on your MMT before selecting Conversations and choosing your messaging event.
- If you are using WhatsApp, you will need to use a unique phone number for the WhatsApp Business API that is not used in any other WhatsApp personal or business accounts. You can sign up for the WhatsApp Business API directly on your MMT.
- Some MMTs, such as Pancake, may require you to add to your wallet before you can reply to customer messages. Additional charges for using the WhatsApp Business API will be collected by your MMT and passed back to Meta.

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
        <td>Special ad categories<br>(Optional)</td>
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
(code curl http)
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
        <th style="width:24%">Parameter</th>
        <th style="width:33%">How to configure the parameter</th>
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
        <td>Optimization and bidding<br>· Goal</td>
        <td>Any of the following types:<ul><li>Click<ul><li>Show your ads to people that are most likely to click on them.</li></ul></li><li>Conversations<ul><li>Show your ads to people that are most likely to start a conversation on WhatsApp, Messenger, Zalo, LINE, and other messaging apps.</li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups.</td>
        <td><code>optimization_goal</code></td>
        <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERSATION</code></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, omit <code>optimization_event</code> or set <code>optimization_event</code> to <code>MESSAGE</code>.</li></ul></td>
    </tr>
    <tr>
        <td rowspan="2">Optimization and bidding<br>· Optimization location</td>
        <td rowspan="2">Instant messaging apps: When people click your ad, they'll automatically be redirected to your preferred instant messaging app, such as WhatsApp or Messenger.<br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
        <td><code>promotion_type</code></td>
        <td><code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</td>
    </tr>
    <tr>
        <td><code>promotion_target_type</code></td>
        <td>Not specified</td>
    </tr>
    <tr>
        <td>Optimization and bidding<br>· Select your Instant messaging app or use a customized URL</td>
        <td>Any of the following options:<ul><li>Messenger with Facebook Page ID</li><li>WhatsApp with WhatsApp phone number</li><li>Zalo with Zalo Official Account ID or Zalo phone number<ul><li>When Goal is Click and the selected Instant message app is Zalo, you can only input a Zalo phone number</li></ul></li><li>LINE with LINE Business ID</li><li>Instant Messaging URL</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When Goal is Conversations, you can only select from the following options:</p><ul><li>Messenger with Facebook Page ID</li><li>WhatsApp with WhatsApp phone number</li><li>Zalo with Zalo Official Account ID or Zalo phone number</li></ul></td>
        <td><code>messaging_app_type</code><br><code>zalo_id_type</code><br><code>messaging_app_account_id</code><br><code>phone_info</code></td>
        <td> <ul> <li>To use the Instant messaging app Messenger: <ul> <li>Set <code>messaging_app_type</code> to <code>MESSENGER</code> and specify a Facebook Page ID via <code>messaging_app_account_id</code>. <ul> <li>To find your Facebook Page ID, go to your Facebook Page and click <b>About</b> below your cover photo, then click <b>Page transparency</b>.</li> </ul> </li> <li>Do not specify <code>zalo_id_type</code> and <code>phone_info</code>.</li> </ul> </li> <li>To use the Instant messaging app WhatsApp: <ul> <li>Set <code>messaging_app_type</code> to <code>WHATSAPP</code> and specify <code>phone_info</code>.</li> <li>Do not specify <code>zalo_id_type</code> and and <code>messaging_app_account_id</code>. Your <code>messaging_app_account_id</code> setting will be ignored and this field will be set as the corresponding WhatsApp phone number automatically populated based on the specified <code>phone_info</code>.</li> </ul> </li> <li>To use the Instant messaging app Zalo: <ul> <li>Set <code>messaging_app_type</code> to <code>ZALO</code>.</li> <li>Specify the Zalo contact format through <code>zalo_id_type</code>. <ul> <li>To use Zalo Official Account ID, set <code>zalo_id_type</code> to <code>ZALO_OFFICIAL_ACCOUNT</code> and specify the Zalo Official Account ID via <code>messaging_app_account_id</code>.</li> <li>To use Zalo phone number, set <code>zalo_id_type</code> to <code>ZALO_PHONE_ACCOUNT</code> and specify the Zalo phone number via <code>phone_info</code>. <br> <p><span style="color:darkred"><b>Note</b></span>: You can only set <code>zalo_id_type</code> to <code>ZALO_PHONE_ACCOUNT</code> when <code>optimization_goal</code> is <code>CLICK</code>.</p> </li> </ul> </li> </ul> </li> <li>To use the Instant messaging app LINE: <ul> <li>Set <code>messaging_app_type</code> to <code>LINE</code> and specify a LINE Business ID via <code>messaging_app_account_id</code>. <ul> <li>To find your LINE Business ID, go to your LINE official account page.</li> </ul> </li> <li>Do not specify <code>zalo_id_type</code> and <code>phone_info</code>.</li> </ul> </li> <li>To use Instant Messaging URL: <ul> <li>Set <code>messaging_app_type</code> to <code>IM_URL</code>.</li> <li>Do not specify <code>zalo_id_type</code>, <code>messaging_app_account_id</code>, and <code>phone_info</code>. </li> </ul> </li> </ul> <br> <p><span style="color:darkred"><b>Note</b></span>: When <code>optimization_goal</code> is <code>CONVERSATION</code>, you can only set <code>messaging_app_type</code> to any of the following values: <ul> <li><code>MESSENGER</code></li> <li><code>WHATSAPP</code></li> <li><code>ZALO</code></li> </ul> </p></td>
    </tr>
    <tr>
        <td>Optimization and bidding<br>· Message event set</td>
        <td>Specify a valid message event set when the following conditions are met:<ul><li>Goal is set to Conversations.</li><li>The messaging app account in your ad group settings doesn't match any existing message event set.</li></ul><br> If the messaging app account in your ad group settings matches a unique existing message event set, the unique message event set associated with the instant messaging app account you choose will be <b>automatically selected</b>.</td>
        <td><code>message_event_set_id</code></td>
        <td>Specify a valid value when the following conditions are met:<ul><li><code>optimization_goal</code> is <code>CONVERSATION</code>.</li><li>The messaging app account in your ad group settings doesn't match any existing message event set.</li></ul><br><ul><li>If the instant messaging app account, either the Messenger account or Zalo Official Account specified via <code>messaging_app_account_id</code> or the WhatsApp or Zalo phone account populated from the specified <code>phone_info</code>, in your ad group settings matches an existing event set, this field will be <b>ignored and automatically populated</b> with the unique message event set associated with the instant messaging app account you choose.<ul><li>To confirm whether a unique message event set is associated with the instant messaging app account you choose, use <a href="https://business-api.tiktok.com/portal/docs?id=1816979158055937" target="_blank">/ctm/message_event_set/get/</a> and check the returned <code>matched_event_set</code>.</li></ul><li>If the instant messaging app account in your ad group settings doesn't match any existing message event set (<code>matched_event_set</code> returned from <code>/ctm/message_event_set/get/</code> is empty), use <a href="https://business-api.tiktok.com/portal/docs?id=1816979158055937" target="_blank">/ctm/message_event_set/get/</a> to obtain the message event sets available for ad creation via the response parameter <code>message_event_set_list</code>.</li></ul></td>
    </tr>
    <tr>
        <td rowspan="2">Optimization and bidding<br>· Bid Strategy and (Optional) Target CPA</td>
        <td rowspan="2">The bid strategy can be any of the following:<ul><li>Maximum Delivery.</li><li>Cost Cap. You need to specify a Target CPA at the same time.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA, if specified, must be the same across ad groups.</li><li>Target CPA is only available when your campaign or ad group budget mode is set to daily.</li></ul></td>
        <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code></td>
        <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>bid_price</code> or <code>conversion_bid_price</code>, if specified, should be the same for ad groups within the same campaign.</li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></ul></td>
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
        <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Bid Strategy is Target CPA</li></ul></ul></td>
        <td><code>budget_auto_adjust_strategy</code></td>
        <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable Goal-based budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable Goal-based budget increase.</li></ul><br><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>.</li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: Enabling Goal-based budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
    </tr>
    <tr>
        <td>Budget and schedule<br>· Add spend target per ad group<br>· Daily minimum</td>
        <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
        <td><code>min_budget</code></td>
        <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
        <td>Budget and schedule<br>· Schedule</td>
        <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start and end time" in any of the following scenarios:</p><ul><li>At the campaign level, a lifetime campaign budget is set.</li><li>At the ad group level, a lifetime ad group budget is set.</li></ul></td>
        <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
        <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> in any of the following scenarios:</p><ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code></li></ul></td>
    </tr>
    <tr>
        <td>Budget and schedule<br>· Dayparting (Optional)</td>
        <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
        <td><code>dayparting</code></td>
        <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
        <td>Audience targeting optimization mode</td>
        <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance. </li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
        <td><code>targeting_optimization_mode</code></td>
        <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul><br> To learn about the supported targeting settings for each mode, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839827327106" target="_blank">Available targeting settings for different targeting optimization modes</a>.</td>
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
        <td>N/A<br><br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
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

### Example

#### Goal as Click and Instant messaging app as Messenger

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
    "optimization_goal": "CLICK",
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type": "MESSENGER",
    "messaging_app_account_id": "{{messaging_app_account_id}}",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
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
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "bid_price": {{bid_price}},
        "bid_type": "BID_TYPE_CUSTOM",
        "billing_event": "CPC",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "comment_disabled": false,
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "messaging_app_account_id": "{{messaging_app_account_id}}",
        "messaging_app_type": "MESSENGER",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_goal": "CLICK",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
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
                "{{category_exclusion_ids}}"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false
    }
}

(/code)
```

#### Goal as Conversations and Instant messaging app as WhatsApp
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
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type": "WHATSAPP",
    "phone_info": {
        "phone_number": "{{phone_number}}",
        "phone_region_code": "US",
        "phone_region_calling_code": "+1"
    },
    "message_event_set_id": "{{message_event_set_id}}",
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
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "engaged_view_attribution_window": "ONE_DAY",
        "incentive_offer_type": "COST_CAP_AND_MIN_ROAS",
        "message_event_set_id": "{{message_event_set_id}}",
        "messaging_app_account_id": "{{messaging_app_account_id}}",
        "messaging_app_type": "WHATSAPP",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "MESSAGE",
        "optimization_goal": "CONVERSATION",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {
            "phone_number": "{{phone_number}}",
            "phone_region_calling_code": "+1",
            "phone_region_code": "US"
        },
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
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
            "brand_safety_type": "LIMITED_INVENTORY",
            "category_exclusion_ids": [
                "{{category_exclusion_ids}}"
            ],
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

#### Goal as Click with Instant Messaging URL
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
    "optimization_goal": "CLICK",
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type": "IM_URL",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
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
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "bid_price": {{bid_price}},
        "bid_type": "BID_TYPE_CUSTOM",
        "billing_event": "CPC",
        "budget": 0,
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "comment_disabled": false,
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "messaging_app_type": "IM_URL",
        "min_budget": 0,
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_goal": "CLICK",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
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
                "{{category_exclusion_ids}}"
            ],
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false
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
        <td>Destination</td>
        <td>To learn about how to configure the setting, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.</td>
        <td><code>landing_page_url_list</code><br><code>page_list</code></td>
        <td>To learn about how to configure these parameters, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.</td>
    </tr>
    <tr>
        <td rowspan="2">Creative assets<br>· Ad creatives & Ad text</td>
        <td rowspan="2">For a single ad, you can include one to <b>50 ad creatives</b>that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel </li></ul><br>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
        <td><code>ad_format</code></td>
        <td>Specify a valid value</td>
    </tr>
    <tr>
        <td><code>creative_list</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code></td>
        <td>Specify one to 50 ad creatives through <code>creative_list</code>. The ad creatives can be any of the following:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul><br>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</td>
    </tr>
    <tr>
        <td>Add product and service information<br>(Optional)</td>
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
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
        <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;· App events<br>(Optional)</td>
        <td>Enabled or disabled</td>
        <td><code>tracking_app_id</code></td>
        <td>Specify a valid value or not specified</td>
    </tr>
    <tr>
        <td>Tracking<br>· TikTok events tracking<br>&nbsp;&nbsp;· Message event set<br>(Optional)</td>
        <td>To learn about how to configure the setting, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.</td>
        <td><code>tracking_message_event_set_id</code></td>
        <td>To learn about how to configure the parameter, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.</td>
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

### Destination and tracking message event set options for different scenarios
The following table illustrates the available ad destination and tracking message event set options based on your ad group settings for instant messaging app (or customized URL) and optimization goal. Learn how to set up parameters for each option.

<table>
    <thead>
    <tr>
        <th style="width:18%">Instant messaging app or customized URL
<br>(<code>messaging_app_type</code>)</th>
        <th style="width:17%">Available optimization goals
(<code>optimization_goal</code>)</th>
        <th style="width:17%">Available Destinations</th>
        <th style="width:26%">How to configure the Destination</th>
        <th style="width:22%">How to configure Tracking message event set
<br>(<code>tracking_message_event_set_id</code>)</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td rowspan="3">Messenger<br>(<code>MESSENGER</code>)</td>
        <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled or enabled with a message event set (<code>tracking_message_event_set_id</code>) to measure.</td>
    </tr>
    <tr>
        <td>TikTok Instant Page</td>
        <td>Specify a TikTok Instant Page via <code>page_list</code> and do not specify<code>landing_page_url_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_list</code> needs to match the same instant messaging app information at the ad group level.<ul><li>To confirm whether the <code>page_list</code> matches the same instant messaging app information at the ad group level, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a> and check the returned <code>messaging_app_type</code> and <code>messaging_app_account_id</code>.</li></ul></td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td>Conversations<br>(<code>CONVERSATION</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td rowspan="3">WhatsApp<br>(<code>WHATSAPP</code>)</td>
        <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled or enabled with a message event set (<code>tracking_message_event_set_id</code>) to measure.</td>
    </tr>
    <tr>
        <td>TikTok Instant Page</td>
        <td>Specify a TikTok Instant Page via <code>page_list</code> and do not specify<code>landing_page_url_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_list</code> needs to match the same instant messaging app information at the ad group level.<ul><li>To confirm whether the <code>page_list</code> matches the same instant messaging app information at the ad group level, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a> and check the returned <code>messaging_app_type</code> and <code>messaging_app_account_id</code>.</li></ul></td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td>Conversations<br>(<code>CONVERSATION</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td rowspan="3">Zalo<br>(<code>ZALO</code>)</td>
        <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td>TikTok Instant Page</td>
        <td>Specify a TikTok Instant Page via <code>page_list</code> and do not specify<code>landing_page_url_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_list</code> needs to match the same instant messaging app information at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td>Conversations<br>(<code>CONVERSATION</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td rowspan="2">Line<br>(<code>LINE</code>)</td>
        <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
        <td>Instant messaging apps</td>
        <td>Do not specify <code>landing_page_url_list</code>, <code>page_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.<br>The system will automatically populate <code>landing_page_url_list</code> based on your instant messaging app setting at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td>TikTok Instant Page</td>
        <td>Specify a TikTok Instant Page via <code>page_list</code> and do not specify<code>landing_page_url_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_list</code> needs to match the same instant messaging app information at the ad group level.</td>
        <td>Disabled</td>
    </tr>
    <tr>
        <td rowspan="2">Instant Messaging URL<br>(<code>IM_URL</code>)</td>
        <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
        <td>Instant messaging apps</td>
        <td>Manually specify the Instant messaging URL and do not pass <code>page_list</code>.<ul><li>To specify the Instant messaging URL as Apple’s universal link or Android App Link:<ul><li>Set <code>landing_page_url_list</code> to a link starting with <code>http://</code> or <code>https://</code>.</li><li>Do not pass <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.</li></ul><li>To specify the Instant messaging URL as a Custom URL scheme of an Instant messaging app:<ul><li>Set <code>deeplink</code> to a link in the format of <code>scheme://resource</code>. For instance, a Custom URL scheme of WhatsApp should follow the format <code>whatsapp://resource</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Set <code>fallback_type</code> to <code>WEBSITE</code>.</li><li>Set <code>landing_page_url_list</code> to a fallback URL, in the format of Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>. <br><br><p><span style="color:darkred"><b>Note</b></span>: To verify whether a URL is a valid Apple’s universal link or Android App Link, or Custom URL scheme, pass the URL to the parameter <code>url</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1774487686007810" target="_blank">/tool/url_validate/</a>.</ul></ul></td>
        <td rowspan="2">Disabled</td>
    </tr>
    <tr>
        <td>TikTok Instant Page</td>
        <td>Specify a TikTok Instant Page via <code>page_list</code> and do not specify <code>landing_page_url_list</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.</td>
    </tr>
</tbody>
</table>

### Example
#### Destination as Instant messaging apps
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
    "ad_configuration": {
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
            "dark_post_status": "OFF",
            "fallback_type": "UNSET",
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

#### Destination as Instant Messaging URL using Apple’s universal link or Android App Link
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
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
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
(code json)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "dark_post_status": "OFF",
            "fallback_type": "UNSET",
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


#### Destination as Instant Messaging URL using Custom URL scheme
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
    "landing_page_url_list": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "deeplink_list": [
        {
            "deeplink": "{{deeplink}}",
            "deeplink_type": "NORMAL"
        }
    ],
    "ad_configuration": {
        "call_to_action_id": "{{call_to_action_id}}",
        "fallback_type": "WEBSITE"
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
            "dark_post_status": "OFF",
            "fallback_type": "WEBSITE",
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
        "deeplink_list": [
            {
                "deeplink": "{{deeplink}}",
                "deeplink_type": "NORMAL"
            }
        ],
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

