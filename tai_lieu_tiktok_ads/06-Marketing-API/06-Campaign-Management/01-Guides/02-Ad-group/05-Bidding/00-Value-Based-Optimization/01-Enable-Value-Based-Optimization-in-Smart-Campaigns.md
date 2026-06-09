---
title: "Enable Value-Based Optimization in Smart+ Campaigns"
doc_id: 1840137502583809
path: "Marketing API / Campaign Management / Guides / Ad group / Bidding / Value-Based Optimization / Enable Value-Based Optimization in Smart+ Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Enable Value-Based Optimization in Smart+ Campaigns

This article walks you through the steps to enable Value-Based Optimization (VBO) in Smart+ Campaigns.

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To enable VBO, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  
- VBO IAP (Value-Based Optimization for in-app purchases) and VBO IAA (Value-Based Optimization for in-app advertising) in different scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.
- Some VBO IAA and VBO IAP features are available after you meet the unlocking criteria. To find out the unlocking criteria, refer to the <strong>"Unlocking criteria for VBO"</strong> section below. To learn more about VBO IAP and VBO IAA, see [Value-Based Optimization - Value types](https://business-api.tiktok.com/portal/docs?id=1739381743067137#item-link-Value%20types).

 
# Unlocking criteria for VBO
VBO will be automatically unlocked for the promoted App (`app_id`) or website (`pixel_id`) when the App or website meets the following unlocking criteria.

```xtable
| Promoted object {15%}| Unlocking criteria {35%}| Parameter {15%}| Requirement {35%}|
|---|---|---|
| App  (VBO IAP)| The criteria depend on the placement setting you want to use: <ul><li> To use Automatic Placement or non-Pangle-only Select Placement, the App needs to have at least <strong>30 unique Purchase events with value attributed to TikTok or Global App Bundle placements over any consecutive 7 days. </strong></li><li> To use Pangle-only Select Placement, you need to first be allowlisted for VBO with Pangle-only placement. Then the App needs to have at least <strong>50 unique Purchase events with value attributed to Pangle placement in its lifetime. </strong></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li> Once unlocked, VBO IAP will remain available to use for the app.</li><li> VBO with Pangle-only placement is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>  | `app_id` | <ul><li> If you don't want to set `placements` to `PLACEMENT_PANGLE`, the App (`app_id`) needs to have at least 30 unique Purchase events (`optimization_event`= `ACTIVE_PAY`) , which are created with `value` specified in the `properties` object and attributed to TikTok or Global App Bundle placements over any consecutive 7 days. </li><li> If you want to set `placements` to `PLACEMENT_PANGLE`, the App (`app_id`) needs to have at least 50 unique Purchase events (`optimization_event`= `ACTIVE_PAY`), which are created with `value` specified in the `properties` object and attributed to Pangle placement in its lifetime. </li></ul> To check whether the App has accumulated enough events and is supported for a certain placement setting, pass `placements` and the App ID to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753). If the App has accumulated enough events and is supported for the specified `placements`, the returned `vo_status` will be `QUALIFIED`. <br>To report App Events for your App, use [/app/track/](https://ads.tiktok.com/marketing_api/docs?id=1740859196043266) or [/app/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740859218541569). |
| App (VBO IAA) | To unlock VBO IAA for Automatic Placement, TikTok-only, or Pangle-only Select Placement, the App must meet the following requirements: <ul><li> The App is an Android App. </li><li> The Mobile Measurement Partner for the App is AppsFlyer or Adjust.</li><li> The Ads impression ROAS for the App is greater than or equal to 1.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Once unlocked, VBO IAA will remain available to use for the app.  | `app_id` | To enable VBO IAA with `PLACEMENT_TYPE_AUTOMATIC`, `PLACEMENT_TIKTOK`, or `PLACEMENT_PANGLE`, the App (`app_id`) must meet the following requirements: <ul><li> The `platform` returned from [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297) for the App is `ANDROID`.</li><li> The `partner_name` returned from [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297)  for the App is `AppsFlyer` or `Adjust`.</li><li> The <strong> Ads impression ROAS</strong> for the App meets or exceeds 1 during any previous time frame. <ul><li> To confirm if the Ads impression ROAS requirement is met, you can use any of the following methods: <ul><li><strong>Method 1 (Recommended) </strong>: Pass the `app_id` directly to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753) to check whether the App is eligible for VBO IAA.</li><li><strong>Method 2</strong>: Run a synchronous basic report by using [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353). Set `query_lifetime` to `true`, and include the In-app event metric `total_ad_impression_roas` and the attribute metric `tt_app_id` in the request parameter `metrics`. The Ads impression ROAS (`total_ad_impression_roas`) for the App (`tt_app_id`) should be equal to or greater than 1. </li></ul></li></ul></li></ul>  |
| Website | The pixel needs to have at least <strong>20 unique Complete Payment events with value and currency attributed to TikTok, Pangle, or Global App Bundle placements over any consecutive 7 days. </strong><ul><li> If the pixel, over any consecutive 7 days, obtained <strong>at least 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>another 20</strong> attributed to Pangle, any placement is valid.  </li><li> If the pixel, over any consecutive 7 days, obtained <strong>at least 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>less than 20</strong> attributed to Pangle, only the TikTok placement is valid. </li><li> If the pixel, over any consecutive 7 days, obtained <strong>less than 20</strong> unique attributed Complete Payment events with value attributed to TikTok, and <strong>more than 20 </strong> attributed to Pangle, only the Pangle placement is valid. </li></ul> <p><span style="color:darkred"><b>Note</b></span>: Once unlocked, VBO will remain available to use for the pixel.| `pixel_id` | The pixel (`pixel_id`) needs to have at least 20 unique Complete Payment events (`optimization_event`= `SHOPPING`) , which are created with `value` and `currency` specified in the `properties` object and attributed to TikTok, Pangle, or Global App Bundle placements over any consecutive 7 days. <ul><li> If the pixel, over any consecutive 7 days, obtained at least 20 unique attributed Complete Payment events with value attributed to TikTok, and another 20 attributed to Pangle, any configurations through `placement_type` and `placements` are supported.</li><li> If the pixel, over any consecutive 7 days, obtained at least 20 unique attributed Complete Payment events with value attributed to TikTok, and less than 20 attributed to Pangle, you can only set `placements` to `PLACEMENT_TIKTOK`.</li><li> If the pixel, over any consecutive 7 days, obtained less than 20 unique attributed Complete Payment events with value attributed to TikTok, and more than 20 attributed to Pangle, you can only set `placements` to `PLACEMENT_PANGLE`.</li></ul>   To check whether the Pixel has accumulated enough events and is supported for a certain placement setting, pass `placements` and the Pixel ID to [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753). If the Pixel has accumulated enough events and is supported for the specified `placements`, the returned `vo_status` will be `QUALIFIED`. <br>To report Pixel Events for your App, use [/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) or [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225). |
```

# Steps
> <span style="color:DodgerBlue">**Note**</span><br>
> Before enabling VBO, it is recommended to use [/tool/vbo_status/](https://ads.tiktok.com/marketing_api/docs?id=1770016073586753) to check whether your settings are eligible for VBO.

## Enable VBO for Smart+ App Campaigns
### Enable VBO for Smart+ Android App Campaigns
Create a Smart+ Campaign using [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising Objective</td>
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
	<td>Smart+ Campaign</td>
	<td>Enabled.<br>Campaigns created via <code>/campaign/spc/create/</code> are all Smart+ Campaigns.</td>	
	<td>/</td>
	<td>/</td>
  </tr>
  <tr>
    <td>iOS 14 Dedicated Campaign</td>
    <td>Disabled</td>
    <td><code>REGULAR_CAMPAIGN</code> or not specified.</td>
  </tr>
  <tr>
    <td  rowspan="2">Optimization Location <br>(Promotion type)</td>
    <td  rowspan="2">An <b>Android</b> App that is eligible for VBO</td>
    <td><code>promotion_type</code></td>
    <td><code>APP_ANDROID</code></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Specify the ID of an Android app that is eligible for VBO.<br><br>You can get IDs of Android Apps from <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>. For Android Apps, the returned <code>platform</code> will be <code>ANDROID</code>.<br><br>To check whether an App ID is eligible for VBO, pass the App ID to the parameter <code>app_id</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both</li></ul></td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Any of the following configurations:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code></li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and only include <code>PLACEMENT_TIKTOK</code> or <code>PLACEMENT_PANGLE</code> or both in the value of <code>placements</code></li></ul></td>
  </tr>
  <tr>
    <td>Optimization Goal</td>
    <td>Value</td>
    <td><code>optimization_goal</code></td>
    <td><code>VALUE</code></td>
  </tr>
  <tr>
    <td>Select Value</td>
    <td>Any of the following types:<ul><li><b>Purchase value</b> (also known as VBO IAP)</li><li><b>Ad revenue value</b> (also known as VBO IAA)</li></ul></td>
    <td><code>optimization_event</code></td>
    <td><ul><li>If you want to use Purchase value, set <code>optimization_event</code> to <code>ACTIVE_PAY</code>.</li><li>If you want to use Ad revenue value, set <code>optimization_event</code> to <code>AD_REVENUE_VALUE</code>.</li></ul></td>
  </tr>
    <tr>
    <td rowspan="3">Bid Strategy</td>
    <td rowspan="3">Any of the following types:<ul><li>Highest Value: To spend your budget fully and maximize the value of results</li><li>Minimum ROAS: To keep your average ROAS around or higher than the target ROAS value</li></ul></td>
    <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
    <td>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>.<br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time. </td>
  </tr>
  <tr>
    <td><code>bid_type</td>
    <td><code>BID_TYPE_NO_BID</code></td>
  </tr>
  <tr>
    <td><code>conversion_bid_price</td>
    <td>Not specified</td>
  </tr> 
  <tr>
    <td>Time Window of the Bid Strategy</td>
    <td>Any of the following types:<ul><li>Day 7 ROAS or Highest Value</li><li>Day 0 ROAS or Highest Value</li></ul></td>
    <td><code>vbo_window</code></td>
    <td>Any of the following values:<ul><li><code>SEVEN_DAYS</code></li><li><code>ZERO_DAY</code></li></ul><br>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br><br><p><span style="color:darkred"><b>Note</b></span>: Day 0 or Day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for VBO IAA in Android campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Billing Event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td>Standard</td>
    <td>N/A</td>
    <td>N/A</td>
  </tr>
</tbody>
</table>

### Enable VBO for Smart+ iOS App Campaigns
Create a Smart+ Campaign using [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:28%">Parameter</th>
    <th style="width:30%">How to configure the parameter</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising Objective</td>
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
    <td>Smart+ Campaign</td>
    <td>Enabled.<br><br>Campaigns created via <code>/campaign/spc/create/</code> are all Smart+ Campaigns.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td>iOS 14 Dedicated Campaign</td>
    <td>Disabled or Enabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> (or not passed) or <code>IOS14_CAMPAIGN</code></td>
  </tr>
  <tr>
    <td rowspan="2">Optimization Location <br>(Promotion type)</td>
    <td rowspan="2">An <b>iOS</b> App that is eligible for VBO</td>
    <td><code>promotion_type</code></td>
    <td><code>APP_IOS</code></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Specify the ID of an iOS app that is eligible for VBO.<br><br>You can get IDs of iOS Apps from <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>. For iOS Apps, the returned <code>platform</code> will be <code>IOS</code>.<br><br>To check whether an App ID is eligible for VBO, pass the App ID to the parameter <code>app_id</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a>.</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok placement or Pangle placement or both</li></ul></td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Any of the following configurations:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code></li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and only include <code>PLACEMENT_TIKTOK</code> or <code>PLACEMENT_PANGLE</code> or both in the value of <code>placements</code></li></ul></td>
  </tr>
  <tr>
    <td>Optimization Goal</td>
    <td>Value</td>
    <td><code>optimization_goal</code></td>
    <td><code>VALUE</code></td>
  </tr>
  <tr>
    <td>Select Value</td>
    <td>Any of the following types:<ul><li><b>Purchase value</b> (also known as VBO IAP, which is short for VBO IAP (Value-Based Optimization for in-app purchases)</li><li><b>Ad revenue value</b> (also known as VBO IAA, which is short for Value-Based Optimization for in-app advertising)</li></ul></td>
    <td><code>optimization_event</code></td>
    <td><ul><li>If you want to use Purchase value, set <code>optimization_event</code> to <code>ACTIVE_PAY</code>.</li><li>If you want to use Ad revenue value, set <code>optimization_event</code> to <code>AD_REVENUE_VALUE</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Bid Strategy</td>
    <td rowspan="3">Any of the following types:<ul><li>Highest Value: To spend your budget fully and maximize the value of results</li><li>Minimum ROAS: To keep your average ROAS around or higher than the target ROAS value</li></ul></td>
    <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
    <td>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>.<br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time. </td>
  </tr>
  <tr>
    <td><code>bid_type</td>
    <td><code>BID_TYPE_NO_BID</code></td>
  </tr>
  <tr>
    <td><code>conversion_bid_price</td>
    <td>Not specified</td>
  </tr> 
  <tr>
    <td>Time Window of the Bid Strategy</td>
    <td>Any of the following types:<ul><li>Day 7 ROAS or Highest Value</li><li>Day 0 ROAS or Highest Value</li></ul></td>
    <td><code>vbo_window</code></td>
    <td>Any of the following values:<ul><li><code>SEVEN_DAYS</code></li><li><code>ZERO_DAY</code></li></ul><br>For example, if you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code>, and set <code>vbo_window</code> to <code>ZERO_DAY</code>, the system will aim to keep your average ROAS of the current day around or higher than the target ROAS value.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Day 0 or day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for VBO IAP in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li><li>Day 0 or day 7 bidding (<code>vbo_window</code> is <code>ZERO_DAY</code> or <code>SEVEN_DAYS</code>) for VBO IAA in Advanced Dedicated Campaign scenarios are currently allowlist-only features. If you would like to access them, please contact your TikTok representative.</li></ul></td>
  </tr>
  <tr>
    <td>Billing Event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td>Standard</td>
    <td>N/A</td>
    <td>N/A</td>
  </tr>
</tbody>
</table>


## Enable VBO for Smart+ Web Campaigns
To learn about how to enable VBO for Smart+ Web Campaigns, see [Create Smart+ Web Campaigns](https://business-api.tiktok.com/portal/docs?id=1814122471133186) and set `optimization_goal` to `VALUE`.

