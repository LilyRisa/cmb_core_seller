---
title: "Create a Lead Generation ad with optimization location as instant messaging apps"
doc_id: 1774482999036930
path: "Use Cases / Campaign creation / Create Lead Generation ads / Create a Lead Generation ad with optimization location as instant messaging apps"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Lead Generation ad with optimization location as instant messaging apps

This article introduces how to create a Lead Generation ad with optimization location as instant messaging apps. This enables the ad audience to take two different actions based on their App installation status by clicking the call-to-action button within the ad:
- If the audience has installed the instant messaging app (such as WhatsApp), they can send a message to you (advertiser) on the App.
- If the audience hasn't installed the instant messaging app, they will be directed to view a Custom TikTok Instant Page.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Creating a Lead Generation ad with optimization location as instant messaging apps is generally available for advertisers registered in the following APAC regions: Australia, Bangladesh, Cambodia, Indonesia, Japan, Malaysia, New Zealand, Pakistan, the Philippines, Singapore, South Korea, Sri Lanka, Taiwan, Thailand, and Vietnam.</li><li>For advertisers registered in other regions, this feature is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>

# Prerequisite
- We recommend connecting to one of our integrated [Message Management Tools](https://ads.tiktok.com/help/article/about-message-management-tools) (MMTs) before setting up your campaign to allow for deeper optimization goals and better performance.
- If you've already integrated with an MMT and are optimizing for Conversations, make sure to set up the TikTok integration on your MMT before selecting Conversations and choosing your messaging event.
- If you are using WhatsApp, you will need to use a unique phone number for the WhatsApp Business API that is not used in any other WhatsApp personal or business accounts. You can sign up for the WhatsApp Business API directly on your MMT.
- Some MMTs, such as Pancake, may require you to add to your wallet before you can reply to customer messages. Additional charges for using the WhatsApp Business API will be collected by your MMT and passed back to Meta.


# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). Note that the following requirements must be met.
```xtable
| Setting {20%} | Requirement {30%} | Parameter {20%} | How to configure the parameter {30%} |
|---|---|---|---|
| Advertising objective | Lead Generation | `objective_type` | `LEAD_GENERATION` |
| Smart+ Campaign| Disabled <br><br>If you are not sure about whether the campaign is Smart+ Campaign, you can use the `is_smart_performance_campaign` field returned from [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) to check. | / | / |
```
### Example

Request

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
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
        "campaign_name": "{{campaign_name}}",
        "modify_time": "{{modify_time}}",
        "objective": "LANDING_PAGE",
        "roas_bid": 0,
        "budget": {{budget}},
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": false,
        "create_time": "{{create_time}}",
        "deep_bid_type": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "objective_type": "LEAD_GENERATION",
        "advertiser_id": "{{advertiser_id}}",
        "operation_status": "ENABLE"
    }
}
(/code)
```

## 2. Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.
<table style="width:1130px"><thead>
  <tr>
    <th style="width:15%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
    <th style="width:33%">How to configure the parameter</th>
  </tr></thead>
<tbody>
  <tr>
    <td rowspan="2">Optimization location <br>(Promotion type)</td>
    <td rowspan="2">Instant messaging apps</td>
    <td><code>promotion_type</code> </td>
    <td><code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code></td>
  </tr>
  <tr>
    <td><code>promotion_target_type</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Select your Instant messaging app or use a customized URL<br></td>
    <td>Any of the following options:<ul><li>Messenger with Facebook Page ID</li><li>WhatsApp with WhatsApp phone number</li><li>Zalo with Zalo phone number</li><li> LINE with LINE Business ID</li><li>Instant Messaging URL</li></ul></td>
    <td><code>messaging_app_type</code><br><code>messaging_app_account_id</code><br><code>phone_region_code</code><br><code>phone_region_calling_code</code><br><code>phone_number</code></td>
    <td> <ul><li>To use the Instant messaging app Messenger:<ul><li>Set <code>messaging_app_type</code> to <code>MESSENGER</code> and specify a Facebook Page ID via <code>messaging_app_account_id</code>. <ul><li>To find your Facebook Page ID, go to your Facebook Page and click <b>About</b> below your cover photo, then click <b>Page transparency</b>.</li></ul></li><li>Do not pass <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>.</li></ul></li><li>To use the Instant messaging app WhatsApp:<ul><li>Set <code>messaging_app_type</code> to <code>WHATSAPP</code> and pass <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>.</li><li>Do not pass <code>messaging_app_account_id</code>. Your setting will be ignored and this field will be set as the corresponding WhatsApp phone number automatically populated based on the specified <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>.</li></ul></li><li>To use the Instant messaging app Zalo: <ul> <li>Set <code>messaging_app_type</code> to <code>ZALO</code> and pass <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>.</li> <li>Do not pass <code>messaging_app_account_id</code>.</li> </ul></li><li>To use the Instant messaging app LINE: <ul> <li>Set <code>messaging_app_type</code> to <code>LINE</code> and specify a LINE Business ID via <code>messaging_app_account_id</code>. <ul> <li>To find your LINE Business ID, go to your LINE official account page.</li> </ul> </li> <li>Do not pass <code>phone_region_code</code>, <code>phone_region_calling_code</code>, or <code>phone_number</code>.</li> </ul></li><li>To use Instant Messaging URL:<ul><li>Set <code>messaging_app_type</code> to <code>IM_URL</code>.</li><li>Do not pass <code>messaging_app_account_id</code>, <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Placement</td>
    <td rowspan="2"><b>Select Placement</b> with TikTok placement</td>
    <td><code>placement_type</code></td>
    <td><code>PLACEMENT_TYPE_NORMAL</code></td>
  </tr>
  <tr>
    <td><code>placements</code></td>
    <td><code>["PLACEMENT_TIKTOK"]</code></td>
  </tr>
  <tr>
    <td><br>Include search results</td>
    <td>Not supported</td>
    <td><code>search_result_enabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Age targeting</td>
    <td>Audiences younger than 18 years old can't be targeted.</td>
    <td><code>age_groups</code></td>
    <td>Do not pass the value <code>AGE_13_17</code>.</td>
  </tr>
  <tr>
    <td>Demographics - Location targeting</td>
    <td>The available targeting regions are as follows:<ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul><br>Note that advertisers registered with the Chinese mainland (<code>CN</code>) cannot target locations in Australia and Indonesia.</td>
    <td><code>location_ids</code></td>
    <td>Pass the IDs of locations within the following regions:<ul><li>APAC: Australia, Cambodia, Indonesia, Japan, Malaysia, New Zealand, the Philippines, Singapore, South Korea, Thailand, Vietnam</li><li>LATAM: Brazil, Chile, Colombia, Ecuador, Mexico, Peru</li></ul><br>Note that advertisers registered with the Chinese mainland (<code>CN</code>) cannot target locations in Australia and Indonesia.<br><br>To obtain the available targeting locations, use <a href="https://business-api.tiktok.com/portal/docs?id=1761236883355649"  target="_blank">/tool/targeting/search/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1737189539571713"  target="_blank">/tool/region/</a>.</td>
  </tr>
  <tr>
    <td>Pangle audience packages</td>
    <td>Not supported</td>
    <td><code>included_pangle_audience_package_ids</code><br><code>excluded_pangle_audience_package_ids</code></td>
    <td>Not supported</td>
  </tr>
  <tr>
    <td rowspan="2">Optimization goal</td>
    <td rowspan="2"><br>Any of the following types:<ul><li>Click</li><li>Conversations (supported with allowlist)</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you have selected Zalo, LINE, or Instant Messaging URL, you can only use the optimization goal Click.</td>
    <td><code>optimization_goal</code></td>
    <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERSATION</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>When <code>messaging_app_type</code> is <code>ZALO</code>, <code>LINE</code>, or <code>IM_URL</code>, you can only set <code>optimization_goal</code> to <code>CLICK</code>.</li><li> The optimization goal Conversation (<code>CONVERSATION</code>) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative</li></ul></td>
  </tr>
  <tr>
    <td><code>optimization_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, do not pass <code>optimization_event</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, omit <code>optimization_event</code> or set <code>optimization_event</code> to <code>MESSAGE</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Message event set</td>
    <td>Specify a valid message event set when the following conditions are met:<ul><li>Optimization goal is set to Conversations.</li><li>The messaging app account in your ad group settings doesn't match any existing message event set.</li></ul><br>If the messaging app account in your ad group settings matches a unique existing message event set, the unique message event set associated with the instant messaging app account you choose will be <b>automatically selected</b>.</td>
    <td><code>message_event_set_id</code></td>
    <td>Specify a valid value when the following conditions are met:<ul><li><code>optimization_goal</code> is <code>CONVERSATION</code>.</li><li>The messaging app account in your ad group settings doesn't match any existing message event set.</li></ul><br><ul><li>If the messaging app account, either the Messenger account specified via <code>messaging_app_account_id</code> or the WhatsApp account matched from the specified <code>phone_region_code</code>, <code>phone_region_calling_code</code>, and <code>phone_number</code>, in your ad group settings matches an existing event set, this field will be <b>ignored and automatically populated</b> with the unique message event set associated with the instant messaging app account you choose.<ul><li>To confirm whether a unique message event set is associated with the instant messaging app account you choose, use <a href="https://business-api.tiktok.com/portal/docs?id=1816979158055937"  target="_blank">/ctm/message_event_set/get/</a> and check the returned <code>matched_event_set</code>.</li></ul></li><li>If the instant messaging app account in your ad group settings doesn't match any existing message event set (<code>matched_event_set</code> returned from <code>/ctm/message_event_set/get/</code> is empty), use <a href="https://business-api.tiktok.com/portal/docs?id=1816979158055937"  target="_blank">/ctm/message_event_set/get/</a> to obtain the message event sets available for ad creation via the response parameter <code>message_event_set_list</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td>Any of the following types:<ul><li>Maximum Delivery</li><li>Cost Cap (or Target CPA)</li></ul></td>
    <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code></td>
    <td>Any of the following values for <code>bid_type</code>:<ul><li><code>BID_TYPE_NO_BID</code></li><li><code>BID_TYPE_CUSTOM</code><ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> and <code>optimization_goal</code> is <code>CONVERSATION</code>, pass <code>conversion_bid_price</code> at the same time.</li><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> and <code>optimization_goal</code> is <code>CLICK</code>, pass <code>bid_price</code> at the same time.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Conversations, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERSATION</code>, set this field to <code>OCPM</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td><ul><li>If Bid Strategy is Maximum Delivery, you can only set Delivery Type to Standard.</li><li>If Bid Strategy is Cost Cap, you can set Delivery Type to Standard or Accelerated.</li></ul></td>
    <td><code>pacing</code></td>
    <td><ul><li>If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code> PACING_MODE_SMOOTH</code>.</li><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Automated Creative Optimization (ACO)</td>
    <td>Not supported</td>
    <td><code>creative_material_mode</code></td>
    <td><code>CUSTOM</code> or not passed</td>
  </tr>
</tbody>
</table>


### Example
#### Instant Messaging App as Messenger & Optimization goal as Conversations
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type":"MESSENGER",
    "messaging_app_account_id": "{{messaging_app_account_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["1880251"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERSATION",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "message_event_set_id": "{{message_event_set_id}}",
    "pacing": "PACING_MODE_SMOOTH"
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
        "location_ids": [
            "1880251"
        ],
        "auto_targeting_enabled": false,
        "ios14_quota_type": "UNOCCUPIED",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "is_hfss": false,
        "advertiser_id": "{{advertiser_id}}",
        "languages": [],
        "optimization_goal": "CONVERSATION",
        "rf_purchased_type": null,
        "campaign_id": "{{campaign_id}}",
        "statistic_type": null,
        "modify_time": "{{modify_time}}",
        "pixel_id": null,
        "vbo_window": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "bid_type": "BID_TYPE_NO_BID",
        "rf_estimated_cpr": null,
        "frequency_schedule": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "network_types": [],
        "smart_audience_enabled": null,
        "interest_category_ids": [],
        "schedule_end_time": "{{schedule_end_time}}",
        "category_id": "0",
        "bid_price": 0,
        "conversion_bid_price": 0,
        "share_disabled": false,
        "brand_safety_partner": null,
        "actions": [],
        "keywords": null,
        "scheduled_budget": 0,
        "delivery_mode": null,
        "rf_estimated_frequency": null,
        "schedule_type": "SCHEDULE_START_END",
        "operating_systems": [],
        "view_attribution_window": "ONE_DAY",
        "attribution_event_count": "ONCE",
        "frequency": null,
        "creative_material_mode": "CUSTOM",
        "comment_disabled": false,
        "adgroup_id": "{{adgroup_id}}",
        "purchased_impression": null,
        "adgroup_app_profile_page_state": null,
        "interest_keyword_ids": [],
        "purchased_reach": null,
        "create_time": "{{create_time}}",
        "billing_event": "OCPM",
        "smart_interest_behavior_enabled": null,
        "feed_type": null,
        "is_smart_performance_campaign": false,
        "messaging_app_account_id": "{{messaging_app_account_id}}",
        "deep_cpa_bid": 0,
        "inventory_filter_enabled": false,
        "operation_status": "ENABLE",
        "skip_learning_phase": true,
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_infos": null,
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "gender": "GENDER_UNLIMITED",
        "message_event_set_id": "{{message_event_set_id}}",
        "next_day_retention": null,
        "audience_ids": [],
        "excluded_audience_ids": [],
        "secondary_optimization_event": null,
        "app_id": null,
        "optimization_event": "MESSAGE",
        "campaign_name": "{{campaign_name}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "conversion_window": null,
        "app_download_url": null,
        "isp_ids": [],
        "device_price_ranges": null,
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
        "category_exclusion_ids": [],
        "click_attribution_window": "SEVEN_DAYS",
        "budget": {{budget}},
        "deep_bid_type": null,
        "video_download_disabled": false,
        "adgroup_name": "{{adgroup_name}}",
        "search_result_enabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_new_structure": true,
        "messaging_app_type": "MESSENGER",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "app_type": null
    }
}
(/code)
```
#### Instant Messaging App as WhatsApp & Optimization goal as Conversations
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type":"WHATSAPP",
    "phone_region_calling_code": "{{phone_region_calling_code}}",
    "phone_region_code": "{{phone_region_code}}",
    "phone_number": "{{phone_number}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["1880251"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERSATION",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
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
        "frequency": null,
        "comment_disabled": false,
        "location_ids": [
            "1880251"
        ],
        "rf_estimated_cpr": null,
        "adgroup_id": "{{adgroup_id}}",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_region_code": "{{phone_region_code}}",
        "message_event_set_id": "{{message_event_set_id}}",
        "gender": "GENDER_UNLIMITED",
        "is_hfss": false,
        "category_id": "0",
        "network_types": [],
        "phone_number": "{{phone_number}}",
        "app_id": null,
        "app_type": null,
        "languages": [],
        "purchased_reach": null,
        "secondary_optimization_event": null,
        "advertiser_id": "{{advertiser_id}}",
        "keywords": null,
        "messaging_app_account_id": "{{messaging_app_account_id}}",
        "delivery_mode": null,
        "purchased_impression": null,
        "adgroup_name": "{{adgroup_name}}",
        "auto_targeting_enabled": false,
        "category_exclusion_ids": [],
        "inventory_filter_enabled": false,
        "statistic_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "schedule_end_time": "{{schedule_end_time}}",
        "rf_estimated_frequency": null,
        "smart_audience_enabled": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_infos": null,
        "smart_interest_behavior_enabled": null,
        "creative_material_mode": "CUSTOM",
        "conversion_bid_price": 0,
        "rf_purchased_type": null,
        "optimization_event": "MESSAGE",
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
        "feed_type": null,
        "bid_display_mode": "CPMV",
        "adgroup_app_profile_page_state": null,
        "optimization_goal": "CONVERSATION",
        "click_attribution_window": "SEVEN_DAYS",
        "view_attribution_window": "ONE_DAY",
        "conversion_window": null,
        "is_smart_performance_campaign": false,
        "isp_ids": [],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "audience_ids": [],
        "share_disabled": false,
        "operation_status": "ENABLE",
        "campaign_name": "{{campaign_name}}",
        "budget": {{budget}},
        "app_download_url": null,
        "bid_price": 0,
        "schedule_type": "SCHEDULE_START_END",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "device_price_ranges": null,
        "actions": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "billing_event": "OCPM",
        "messaging_app_type": "WHATSAPP",
        "interest_category_ids": [],
        "phone_region_calling_code": "+86",
        "excluded_audience_ids": [],
        "next_day_retention": null,
        "pixel_id": null,
        "attribution_event_count": "ONCE",
        "search_result_enabled": false,
        "create_time": "{{create_time}}",
        "campaign_id": "{{campaign_id}}",
        "skip_learning_phase": true,
        "deep_cpa_bid": 0,
        "deep_bid_type": null,
        "scheduled_budget": 0,
        "bid_type": "BID_TYPE_NO_BID",
        "operating_systems": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "ios14_quota_type": "UNOCCUPIED",
        "frequency_schedule": null,
        "modify_time": "{{modify_time}}",
        "brand_safety_partner": null,
        "is_new_structure": true,
        "vbo_window": null,
        "video_download_disabled": false,
        "interest_keyword_ids": [],
        "brand_safety_type": "NO_BRAND_SAFETY"
    }
}
(/code)
```
#### Instant Messaging URL & Optimization goal as Click
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
    "messaging_app_type":"IM_URL",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["1880251"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
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
        "comment_disabled": false,
        "optimization_goal": "CLICK",
        "secondary_optimization_event": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "rf_estimated_cpr": null,
        "actions": [],
        "operating_systems": [],
        "deep_bid_type": null,
        "messaging_app_type": "IM_URL",
        "interest_category_ids": [],
        "optimization_event": null,
        "creative_material_mode": "CUSTOM",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "audience_ids": [],
        "location_ids": [
            "1880251"
        ],
        "is_hfss": false,
        "frequency_schedule": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "conversion_bid_price": 0,
        "delivery_mode": null,
        "smart_audience_enabled": null,
        "skip_learning_phase": false,
        "deep_cpa_bid": 0,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "scheduled_budget": 0,
        "device_price_ranges": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "ios14_quota_type": "UNOCCUPIED",
        "pixel_id": null,
        "brand_safety_partner": null,
        "rf_purchased_type": null,
        "app_download_url": null,
        "pacing": "PACING_MODE_SMOOTH",
        "gender": "GENDER_UNLIMITED",
        "interest_keyword_ids": [],
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "vbo_window": null,
        "inventory_filter_enabled": false,
        "feed_type": null,
        "campaign_name": "{{campaign_name}}",
        "search_result_enabled": false,
        "purchased_impression": null,
        "bid_type": "BID_TYPE_NO_BID",
        "schedule_type": "SCHEDULE_START_END",
        "frequency": null,
        "modify_time": "{{modify_time}}",
        "share_disabled": false,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "app_id": null,
        "next_day_retention": null,
        "rf_estimated_frequency": null,
        "conversion_window": null,
        "operation_status": "ENABLE",
        "schedule_infos": null,
        "create_time": "{{create_time}}",
        "adgroup_name": "{{adgroup_name}}",
        "isp_ids": [],
        "app_type": null,
        "network_types": [],
        "excluded_audience_ids": [],
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "purchased_reach": null,
        "billing_event": "CPC",
        "promotion_type": "LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE",
        "is_smart_performance_campaign": false,
        "advertiser_id": "{{advertiser_id}}",
        "adgroup_id": "{{adgroup_id}}",
        "smart_interest_behavior_enabled": null,
        "budget": 380,
        "adgroup_app_profile_page_state": null,
        "statistic_type": null,
        "video_download_disabled": false,
        "auto_targeting_enabled": false,
        "bid_display_mode": "CPMV",
        "languages": [],
        "category_exclusion_ids": [],
        "keywords": null,
        "bid_price": 0,
        "category_id": "0",
        "budget_mode": "BUDGET_MODE_TOTAL"
    }
}
(/code)
```
## 3. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

```xtable
| Setting {18%} | Requirement {27%} | Parameter {24%} | How to configure the parameter {31%} |
|---|---|---|---|
| Smart Creative | Not supported | `is_smart_creative`<br>(in [/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626)) | Not supported |
| Ad format |  Any of the following types: <ul><li>Single video</li><li> Carousel images</li></ul>  | `ad_format` |Any of the following values: <ul><li>`SINGLE_VIDEO` </li><li>`CAROUSEL_ADS`<ul><li> To learn about how to create Standard Carousel Ads, see [Steps for creating Standard Carousel Ads](https://business-api.tiktok.com/portal/docs?id=1766217791987713#item-link-Steps%20for%20creating%20Standard%20Carousel%20Ads).</li></ul></li></ul> |
| Standard call-to-action | Specified.<br>The recommended call-to-action is “Send message”. | `call_to_action` | Pass one value that is not `CALL_NOW`.<br>The recommended value is `SEND_MESSAGE`. |
| Dynamic call-to-action | Not supported | `call_to_action_id` | Not supported |
| Interactive add-on | Not supported | `card_id` | Not supported |
| Destination | To learn about how to configure the setting, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.| `deeplink`<br>`deeplink_type` <br>`fallback_type` <br> `landing_page_url` <br> `page_id`  |To learn about how to configure these parameters, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.|
|(Optional) Tracking - Message event set |To learn about how to configure the setting, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.|`tracking_message_event_set_id`|To learn about how to configure the parameter, see <a href="#item-link-Destination and tracking message event set options for different scenarios">Destination and tracking message event set options for different scenarios</a>.|
```

### Destination and tracking message event set options for different scenarios
The following table illustrates the available ad destination and tracking message event set options based on your ad group settings for instant messaging app (or customized URL) and optimization goal. Learn how to set up parameters for each option.

<table style="width:1130px">
  <thead>
  <tr>
    <th style="width:17%">Instant messaging app or customized URL<br>(<code>messaging_app_type</code>)</th>
    <th style="width:16%">Available optimization goals<br>(<code>optimization_goal</code>)</th>
    <th style="width:15%">Available Destinations</th>
    <th style="width:26%">How to configure the Destination</th>
    <th style="width:26%">How to configure Tracking message event set <br>(<code>tracking_message_event_set_id</code>)</th>
  </tr></thead>
<tbody>
  <tr>
    <td rowspan="3">Messenger<br>(<code>MESSENGER</code>)</td>
    <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
    <td>Instant messaging apps </td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled or enabled with a message event set (<code>tracking_message_event_set_id</code>) to measure.</td>
  </tr>
  <tr>
    <td>TikTok Instant Page</td>
    <td>Specify a TikTok Instant Page via <code>page_id</code> and do not pass <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_id</code> needs to match the same instant messaging app information at the ad group level. <ul><li>To confirm whether the <code>page_id</code> matches the same instant messaging app information at the ad group level, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a> and check the returned <code>messaging_app_type</code> and <code>messaging_app_account_id</code>.</li></ul></td>
    <td>Disabled</td>
  </tr>
  <tr>
    <td>Conversations<br>(<code>CONVERSATION</code>)</td>
    <td>Instant messaging apps </td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled</td>
  </tr>
  <tr>
    <td rowspan="3">WhatsApp<br>(<code>WHATSAPP</code>)</td>
    <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
    <td>Instant messaging apps </td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled or enabled with a message event set (<code>tracking_message_event_set_id</code>) to measure.</td>
  </tr>
  <tr>
    <td>TikTok Instant Page</td>
    <td>Specify a TikTok Instant Page via <code>page_id</code> and do not pass <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_id</code> needs to match the same instant messaging app information at the ad group level. <ul><li>To confirm whether the <code>page_id</code> matches the same instant messaging app information at the ad group level, use <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>and check the returned <code>messaging_app_type</code> and <code>messaging_app_account_id</code>.</li></ul></td>
    <td>Disabled</td>
  </tr>
  <tr>
    <td>Conversations<br>(<code>CONVERSATION</code>)</td>
    <td>Instant messaging apps </td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled</td>
  </tr>
  <tr>
    <td rowspan="2">Zalo<br>(<code>ZALO</code>)</td>
    <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
    <td>Instant messaging apps</td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled</td>
</tr>
<tr>
    <td>TikTok Instant Page</td>
    <td>Specify a TikTok Instant Page via <code>page_id</code> and do not pass <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_id</code> needs to match the same instant messaging app information at the ad group level. </td>
    <td>Disabled</td>
</tr>
<tr>
    <td rowspan="2">Line<br>(<code>LINE</code>)</td>
    <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
    <td>Instant messaging apps</td>
    <td>Do not pass <code>page_id</code>, <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The system will automatically populate <code>landing_page_url</code> based on your instant messaging app setting at the ad group level.</td>
    <td>Disabled</td>
</tr>
<tr>
    <td>TikTok Instant Page</td>
    <td>Specify a TikTok Instant Page via <code>page_id</code> and do not pass <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>. The <code>page_id</code> needs to match the same instant messaging app information at the ad group level.   </td>
    <td>Disabled</td>
  </tr>
  <tr>
    <td rowspan="2">Instant Messaging URL<br>(<code>IM_URL</code>)</td>
    <td rowspan="2">Click<br>(<code>CLICK</code>)</td>
    <td>Instant messaging apps </td>
    <td>Manually specify the Instant messaging URL and do not pass <code>page_id</code>. <br><br><ul><li>To specify the Instant messaging URL as Apple’s universal link or Android App Link:<ul><li>Set <code>landing_page_url</code> to a link starting with <code>http://</code> or <code>https://</code>.</li><li>Do not pass <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.</li></ul></li><li>To specify the Instant messaging URL as a Custom URL scheme of an Instant messaging app:<ul><li>Set <code>deeplink</code> to a link in the format of <code>scheme://resource</code>. For instance, a Custom URL scheme of WhatsApp should follow the format <code>whatsapp://resource</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code> or do not pass <code>deeplink_type</code>.</li><li>Set <code>fallback_type</code> to <code>WEBSITE</code>.</li><li>Set <code>landing_page_url</code> to a fallback URL, in the format of Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To verify whether a URL is a valid Apple’s universal link or Android App Link, or Custom URL scheme, pass the URL to the parameter <code>url</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1774487686007810" target="_blank">/tool/url_validate/</a>.</td>
     <td rowspan="2">Disabled</td>
  </tr>
  <tr>
    <td>TikTok Instant Page</td>
    <td>Specify a TikTok Instant Page via <code>page_id</code> and do not pass <code>landing_page_url</code>, <code>deeplink</code>, <code>deeplink_type</code>, and <code>fallback_type</code>.</td>
  </tr>
</tbody>
</table>


### Example
#### Destination as TikTok Instant Page for Messenger and Optimization goal Click
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SEND_MESSAGE",
        "page_id": "{{page_id}}"
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
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "deeplink": null,
                "profile_image_url": "{{profile_image_url}}",
                "display_name": "{{display_name}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "page_id": {{page_id}},
                "fallback_type": "UNSET",
                "app_name": "",
                "operation_status": "ENABLE",
                "image_ids": [
                    "{{image_id}}"
                ],
                "creative_authorized": false,
                "call_to_action": "SEND_MESSAGE",
                "tracking_pixel_id": 0,
                "adgroup_name": "{{adgroup_name}}",
                "viewability_postbid_partner": "UNSET",
                "ad_format": "SINGLE_VIDEO",
                "landing_page_url": null,
                "modify_time": "{{modify_time}}",
                "create_time": "{{create_time}}",
                "campaign_name": "{{campaign_name}}",
                "identity_type": "CUSTOMIZED_USER",
                "click_tracking_url": null,
                "landing_page_urls": null,
                "impression_tracking_url": null,
                "music_id": null,
                "optimization_event": null,
                "ad_texts": null,
                "viewability_vast_url": null,
                "identity_id": "{{identity_id}}",
                "ad_text": "{{ad_text}}",
                "card_id": null,
                "ad_id": "{{ad_id}}",
                "creative_type": "CUSTOM_INSTANT_PAGE",
                "video_id": "{{video_id}}",
                "campaign_id": "{{campaign_id}}",
                "ad_name": "{{ad_name}}",
                "is_new_structure": true,
                "carousel_image_labels": null,
                "call_to_action_id": null,
                "brand_safety_vast_url": null,
                "playable_url": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "adgroup_id": "{{adgroup_id}}",
                "vast_moat_enabled": false,
                "advertiser_id": "{{advertiser_id}}",
                "is_aco": false
            }
        ]
    }
}
(/code)
```

#### Destination as Instant messaging apps for WhatsApp and Optimization goal Conversations
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
        "ad_name": "{{ad_name}}",
        "identity_type":"{{identity_type}}",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
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
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "phone_number": "{{phone_number}}",
                "is_aco": false,
                "modify_time": "{{modify_time}}",
                "deeplink_type": "NORMAL",
                "campaign_id": "{{campaign_id}}",
                "creative_type": null,
                "display_name": "{{display_name}}",
                "call_to_action_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "tracking_pixel_id": 0,
                "creative_authorized": false,
                "card_id": null,
                "viewability_vast_url": null,
                "playable_url": "",
                "viewability_postbid_partner": "UNSET",
                "image_ids": [
                    "{{image_id}}"
                ],
                "fallback_type": "UNSET",
                "campaign_name": "{{campaign_name}}",
                "ad_text": "{{ad_text}}",
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "click_tracking_url": null,
                "ad_format": "SINGLE_VIDEO",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "ad_id": "{{ad_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "call_to_action": "SEND_MESSAGE",
                "ad_texts": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "operation_status": "ENABLE",
                "music_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "optimization_event": "MESSAGE",
                "phone_region_calling_code": "{{phone_region_calling_code}}",
                "deeplink": "",
                "page_id": null,
                "is_new_structure": true,
                "brand_safety_vast_url": null,
                "vast_moat_enabled": false,
                "landing_page_urls": null,
                "create_time": "{{create_time}}",
                "adgroup_id": "{{adgroup_id}}",
                "app_name": "",
                "identity_id": "{{identity_id}}",
                "carousel_image_labels": null,
                "impression_tracking_url": null,
                "phone_region_code": "{{phone_region_code}}",
                "profile_image_url": "{{profile_image_url}}",
                "video_id": "{{video_id}}"
            }
        ]
    }
}
(/code)
```
#### Destination as universal link or App Link for Instant Messaging URL and Optimization goal Click
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SEND_MESSAGE",
        "landing_page_url":"{{landing_page_url}}"
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
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "ad_id": "{{ad_id}}",
                "display_name": "{{display_name}}",
                "carousel_image_labels": null,
                "app_name": "",
                "image_ids": [
                    "{{image_id}}"
                ],
                "click_tracking_url": null,
                "profile_image_url": "{{profile_image_url}}",
                "deeplink": "",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "tracking_pixel_id": 0,
                "ad_texts": null,
                "optimization_event": null,
                "advertiser_id": "{{advertiser_id}}",
                "deeplink_type": "NORMAL",
                "adgroup_id": "{{adgroup_id}}",
                "create_time": "{{create_time}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "call_to_action_id": null,
                "page_id": null,
                "campaign_id": "{{campaign_id}}",
                "is_new_structure": true,
                "landing_page_url": "{{landing_page_url}}",
                "ad_format": "SINGLE_VIDEO",
                "adgroup_name": "{{adgroup_name}}",
                "call_to_action": "SEND_MESSAGE",
                "viewability_postbid_partner": "UNSET",
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "impression_tracking_url": null,
                "identity_type": "CUSTOMIZED_USER",
                "viewability_vast_url": null,
                "campaign_name": "{{campaign_name}}",
                "music_id": null,
                "landing_page_urls": null,
                "fallback_type": "UNSET",
                "video_id": "{{video_id}}",
                "vast_moat_enabled": false,
                "brand_safety_vast_url": null,
                "ad_name": "{{ad_name}}",
                "operation_status": "ENABLE",
                "playable_url": "",
                "ad_text": "{{ad_text}}",
                "card_id": null,
                "is_aco": false,
                "creative_type": null,
                "creative_authorized": false
            }
        ]
    }
}
(/code)
```
#### Destination as Custom URL scheme for Instant Messaging URL and Optimization goal Click
Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
        "ad_name": "{{ad_name}}",
        "identity_type":"CUSTOMIZED_USER",
        "identity_id":"{{identity_id}}",
        "ad_format": "SINGLE_VIDEO",
        "video_id":"{{video_id}}",
        "image_ids":["{{image_id}}"],
        "ad_text": "{{ad_text}}",
        "call_to_action": "SEND_MESSAGE",
        "deeplink":"{{deeplink}}",
        "deeplink_type":"NORMAL",
        "fallback_type":"WEBSITE",
        "landing_page_url":"{{landing_page_url}}"
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
        "ad_ids": [
            "{{ad_id}}"
        ],
        "need_audit": false,
        "creatives": [
            {
                "display_name": "{{display_name}}",
                "call_to_action_id": null,
                "ad_text": "{{ad_text}}",
                "landing_page_urls": null,
                "creative_type": null,
                "brand_safety_vast_url": null,
                "create_time": "{{create_time}}",
                "optimization_event": null,
                "adgroup_name": "{{adgroup_name}}",
                "tracking_pixel_id": 0,
                "profile_image_url": "{{profile_image_url}}",
                "viewability_vast_url": null,
                "campaign_id": "{{campaign_id}}",
                "ad_format": "SINGLE_VIDEO",
                "call_to_action": "SEND_MESSAGE",
                "image_ids": [
                    "{{image_id}}"
                ],
                "carousel_image_labels": null,
                "campaign_name": "{{campaign_name}}",
                "app_name": "",
                "page_id": null,
                "is_aco": false,
                "video_id": "{{video_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "impression_tracking_url": null,
                "fallback_type": "WEBSITE",
                "music_id": null,
                "ad_name": "{{ad_name}}",
                "card_id": null,
                "ad_id": "{{ad_id}}",
                "viewability_postbid_partner": "UNSET",
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "deeplink_type": "NORMAL",
                "playable_url": "",
                "creative_authorized": false,
                "adgroup_id": "{{adgroup_id}}",
                "vast_moat_enabled": false,
                "advertiser_id": "{{advertiser_id}}",
                "deeplink": "{{deeplink}}",
                "ad_texts": null,
                "landing_page_url": "{{landing_page_url}}",
                "operation_status": "ENABLE",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}",
                    "{{tracking_offline_event_set_id}}"
                ],
                "is_new_structure": true,
                "click_tracking_url": null
            }
        ]
    }
}
(/code)
```
