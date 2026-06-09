---
title: "Attribution window and event count"
doc_id: 1777694366654465
path: "Marketing API / Campaign Management / Guides / Ad group / Attribution window and event count"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Attribution window and event count

At the ad group level, you have the flexibility to set different attribution windows for your campaigns or ad groups. This allows you to customize how conversions are measured based on specific timeframes.

 The supported attribution setting options at the ad group levels are as follows:    
 - Click-through attribution window (`click_attribution_window`) : This defines the timeframe within which a conversion will be measured after a person clicks your ad. You can set your window to a maximum of 28 days, capturing conversions that occur within 28 
days after the ad click.
- Engaged view-through attribution window (`engaged_view_attribution_window`): This defines the timeframe within which a conversion will be measured after a person watches at least six seconds of your video ad. You can set your window to a maximum of seven days, capturing conversions that occur within seven days after the 6-second video view.
 - View-through attribution window (`view_attribution_window`) : This defines the timeframe that a conversion will be measured after a person views your ad. You can set your 
window to a maximum of seven days, capturing conversions that occur within seven days 
after the ad view.
 - Event count (`attribution_event_count`): This refers to the way that people's actions are counted after only clicking or viewing an ad.

## Supported attribution setting options for different advertising objectives

To learn about the supported values of the fields `click_attribution_window`, `engaged_view_attribution_window`, `view_attribution_window`, and `attribution_event_count` for specific settings at the campaign level and the ad group level, refer to the following tables.

Note that for advertising objectives not listed in the tables below, you cannot manually select from a range of attribution settings. In such cases, a default setting may be applied. To confirm whether a default setting is used for your ad group when you don't manually set the attribution settings, use [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922) or [/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842).

### App promotion
<div style="overflow-x:auto;">
<table  style="width:2500px">
<thead>
  <tr>
    <th rowspan="2" style="width:10%">Advertising objective<br>(<code>objective_type</code>)</th>
    <th rowspan="2" style="width:11%">App promotion type<br>(<code>app_promotion_type</code>)</th>
    <th colspan="3" rowspan="2"  style="width:19%; text-align: center;">Optimization location</th>
    <th colspan="2" rowspan="2"  style="width:11%">Optimization goal</th>
    <th rowspan="2"  style="width:7%">Billing Event<br>(<code>billing_event</code>)</th>
    <th colspan="3"  style="width:32%; text-align: center;">Attribution window</th>
    <th rowspan="2"  style="width:14%">Event count<br>(<code>attribution_event_count</code>)</th>
  </tr>
  <tr>
    <th  style="width:10%">Click-through attribution window<br>(<code>click_attribution_window</code>)</th>
    <th  style="width:10%">View-through attribution window<br>(<code>view_attribution_window</code>)</th>
	<th style="width:12%">Engaged view-through window <br>(<code>engaged_view_attribution_window</code>)</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="17">App promotion<br>(<code>APP_PROMOTION</code>)</td>
    <td rowspan="10">App install<br>(<code>APP_INSTALL</code>)</td>
    <td colspan="2" rowspan="6"  style="width:9%">Android App<br>(<code>promotion_type</code> is set to <code>APP_ANDROID</code>)</td>
    <td rowspan="5"  style="width:10%">Self Attribution Network (SAN) App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">Install<br>(<code>optimization_goal</code> is set to <code>INSTALL</code>)</td>
    <td rowspan="5">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="5">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="5">Supported values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
	<td rowspan="5">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Install with in-app event<br> (<code>optimization_goal</code> is set to <code>INSTALL</code>, and <code>secondary_optimization_event</code> is specified)</td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event as purchase<br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event not as purchase<br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to a value other than <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value<br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>Non-SAN App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>false</code>)</td>
    <td colspan="2">In-app event, with the optimization event as purchase<br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>oCPM<br> (<code>OCPM</code>)</td>
    <td>Supported value: <ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value: <ul><li><code>ONE_DAY</code></li></ul></td>
	<td>Supported value: <ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="4">iOS App in a Non-Dedicated Campaign<br>(<code>promotion_type</code> is set to <code>APP_IOS</code> and the <code>campaign_type</code> of the campaign is <code>REGULAR_CAMPAIGN</code>)</td>
    <td rowspan="4">SAN App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">Install<br>(<code>optimization_goal</code> is set to <code>INSTALL</code>)</td>
    <td rowspan="4">oCPM<br> (<code>OCPM</code>)</td>
    <td rowspan="4">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="4">Supported values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
	<td rowspan="4">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Install with in-app event <br> (<code>optimization_goal</code> is set to <code>INSTALL</code>, and <code>secondary_optimization_event</code> is specified)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event <br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value<br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td rowspan="6">App retargeting<br>(<code>APP_RETARGETING</code>)</td>
    <td colspan="2" rowspan="4">Android App<br>(<code>promotion_type</code> is set to <code>APP_ANDROID</code>)</td>
    <td rowspan="3">SAN App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">In-app event, with the optimization event as purchase<br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td rowspan="3">oCPM<br> (<code>OCPM</code>)</td>
    <td rowspan="3">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="3">Supported values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
	<td rowspan="3">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event not as purchase <br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to a value other than <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value<br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>Non-SAN App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>false</code>)</td>
    <td colspan="2">In-app event, with the optimization event as purchase<br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>oCPM<br> (<code>OCPM</code>)</td>
    <td>Supported value: <ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value: <ul><li><code>ONE_DAY</code></li></ul></td>
    <td>Supported value: <ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="2">iOS App<br>(<code>promotion_type</code> is set to <code>APP_IOS</code>)</td>
    <td rowspan="2">SAN App<br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">In-app event <br> (<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">Supported values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
	<td rowspan="2">Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value<br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>App pre-registration<br> (<code>APP_PREREGISTRATION</code>)</td>
    <td colspan="3">Website <br>(<code>promotion_type</code> is set to <code>WEBSITE</code>)<br></td>
    <td colspan="2">Conversion<br> (<code>optimization_goal</code> is set to <code>CONVERT</code>)<br></td>
    <td>oCPM<br> (<code>OCPM</code>)</td>
    <td>Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td>Supported values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>/</td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
</tbody>
</table>
  </div>
  
  
### Lead generation
<div style="overflow-x:auto;">
<table  style="width:1500px">
<thead>
  <tr>
    <th rowspan="2"  style="width:13%">Advertising objective<br>(<code>objective_type</code>)</th>
    <th rowspan="2"  style="width:15%">Optimization location</th>
    <th rowspan="2"  style="width:12%">Optimization goal <br>(<code>optimization_goal</code>)</th>
    <th rowspan="2"  style="width:11%">Billing Event<br>(<code>billing_event</code>)</th>
    <th colspan="2" style="width:33%; text-align: center;">Attribution window</th>
    <th rowspan="2"  style="width:16%">Event count<br>(<code>attribution_event_count</code>)</th>
  </tr>
  <tr>
    <th  style="width:16%">Click-through attribution window<br>(<code>click_attribution_window</code>)</th>
    <th  style="width:17%">View-through attribution window<br>(<code>view_attribution_window</code>)</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Lead generation (<code>LEAD_GENERATION</code>)</td>
    <td>Website <br>(<code>promotion_type</code> is set to <code>LEAD_GENERATION</code> , and <code>promotion_target_type</code> is set to <code>EXTERNAL_WEBSITE</code>)</td>
    <td>Conversion<br>(<code>CONVERT</code>)</td>
    <td>oCPM<br> (<code>OCPM</code>)</td>
    <td>Supported values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td>Supported Values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
</tbody>
</table>
</div>

### Product Sales
  
  
<div style="overflow-x:auto;">
<table style="width:2500px">
<thead>
  <tr>
    <th rowspan="2"  style="width:8%">Advertising objective <br>(<code>objective_type</code>)</th>
    <th rowspan="2"  style="width:10%">Campaign product source <br>(<code>campaign_product_source</code>)</th>
    <th colspan="3" rowspan="2" style="width:20%; text-align: center;">Optimization location</th>
    <th colspan="2" rowspan="2"  style="width:11%">Optimization goal</th>
    <th rowspan="2"  style="width:7%">Billing Event <br>(<code>billing_event</code>)</th>
    <th colspan="3"  style="width:34%; text-align: center;">Attribution window</th>
    <th rowspan="2"  style="width:12%">Event count <br>(<code>attribution_event_count</code>)</th>
  </tr>
  <tr>
    <th style="width:11%">Click-through attribution window <br>(<code>click_attribution_window</code>)</th>
    <th style="width:11%">View-through attribution window <br>(<code>view_attribution_window</code>)</th>
	<th style="width:12%">Engaged view-through window <br>(<code>engaged_view_attribution_window</code>)</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="21">Product sales <br>(<code>PRODUCT_SALES</code>)</td>
    <td rowspan="17">Catalog <br>(<code>CATALOG</code>)</td>
    <td colspan="3" rowspan="2">Website <br>(<code>promotion_type</code> is set to <code>WEBSITE</code>)</td>
    <td colspan="2">Conversion <br>(<code>optimization_goal</code> is set to <code>CONVERT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td rowspan="2">Supported Values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">/</td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="5"  style="width:11%">Android App, with retargeting disabled <br>(<code>promotion_type</code> is set to <code>APP_ANDROID</code>, and <code>shopping_ads_retargeting_type</code> is set to <code>OFF</code> or not passed)</td>
    <td rowspan="4"   style="width:9%">SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">Install <br>(<code>optimization_goal</code> is set to <code>INSTALL</code>)</td>
    <td rowspan="4">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="4">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="4">Supported Values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
    <td rowspan="4">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event not as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to a value other than <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>Non-SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>false</code>)</td>
    <td colspan="2">In-app event, with the optimization event as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>oCPM <br>(<code>OCPM</code>)<br></td>
    <td>Supported value:<ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONE_DAY</code></li></ul></td>
    <td>Supported value:<ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="4">Android App, with retargeting enabled <br>(<code>promotion_type</code> is set to <code>APP_ANDROID</code>, and <code>shopping_ads_retargeting_type</code> is set to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code>)</td>
    <td rowspan="3">SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)<br></td>
    <td colspan="2">In-app event, with the optimization event as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td rowspan="3">oCPM <br>(<code>OCPM</code>)<br></td>
    <td rowspan="3">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="3">Supported values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
    <td rowspan="3">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event, with the optimization event not as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to a value other than <code>ACTIVE_PAY</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>Non-SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>false</code>)</td>
    <td colspan="2">In-app event, with the optimization event as purchase <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>, and <code>optimization_event</code> is set to <code>ACTIVE_PAY</code>)</td>
    <td>oCPM <br>(<code>OCPM</code>)</td>
    <td>Supported value:<ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONE_DAY</code></li></ul></td>
    <td>Supported value:<ul><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="4">iOS App in a Non-Dedicated Campaign, with retargeting disabled <br>(<code>promotion_type</code> is set to <code>APP_IOS</code>, the <code>campaign_type</code> of the campaign is <code>REGULAR_CAMPAIGN</code>, and <code>shopping_ads_retargeting_type</code> is set to <code>OFF</code> or not passed)</td>
    <td rowspan="4">SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">Install <br>(<code>optimization_goal</code> is set to <code>INSTALL</code>)</td>
    <td rowspan="4">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="4">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="4">Supported values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
    <td rowspan="4">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Install with in-app event <br>(<code>optimization_goal</code> is set to <code>INSTALL</code>, and <code>secondary_optimization_event</code> is specified)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">In-app event <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>)</td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2" rowspan="2">iOS App, with retargeting enabled <br>(<code>promotion_type</code> is set to <code>APP_IOS</code>, and <code>shopping_ads_retargeting_type</code> is set to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code>)</td>
    <td rowspan="2">SAN App <br>(The value of the <code>self_attribution_enabled</code> field returned in <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297"  target="_blank">/app/info/</a> for the App (<code>app_id</code>) is <code>true</code>)</td>
    <td colspan="2">In-app event <br>(<code>optimization_goal</code> is set to <code>IN_APP_EVENT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">Supported values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li></ul></td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td rowspan="4">TikTok Shop <br>(<code>STORE</code>)</td>
    <td colspan="2" rowspan="2">Video Shopping Ad <br>(<code>promotion_type</code> is set to <code>VIDEO_SHOPPING</code>, and <code>shopping_ads_type</code> is set to <code>VIDEO</code>)</td>
    <td rowspan="2">TikTok Shop <br>(<code>store_id</code> is set to a TikTok Shop ID)</td>
    <td colspan="2">Conversion <br>(<code>optimization_goal</code> is set to <code>CONVERT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td rowspan="2">Supported Values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">/</td>
    <td rowspan="2">Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
  </tr>
  <tr>
    <td colspan="2" rowspan="2">Live Shopping Ad <br>(<code>promotion_type</code> is set to <code>LIVE_SHOPPING</code>, and <code>shopping_ads_type</code> is set to <code>LIVE</code>)</td>
    <td rowspan="2">TikTok Shop <br>(<code>store_id</code> is set to a TikTok Shop ID)</td>
    <td colspan="2">Conversion <br>(<code>optimization_goal</code> is set to <code>CONVERT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td rowspan="2">Supported Values:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td rowspan="2">/<br></td>
    <td rowspan="2">Supported value:<ul><li><code>ONCE</code></li></ul></td>
  </tr>
  <tr>
    <td colspan="2">Value <br>(<code>optimization_goal</code> is set to <code>VALUE</code>)</td>
  </tr>
</tbody>
</table>
</div>


### Website conversions

<div style="overflow-x:auto;">
<table  style="width:1500px">
<thead>
    <tr>
    <th rowspan="2"  style="width:13%">Advertising objective<br>(<code>objective_type</code>)</th>
    <th rowspan="2"  style="width:16%">Optimization location</th>
    <th rowspan="2"  style="width:12%">Optimization goal <br>(<code>optimization_goal</code>)</th>
    <th rowspan="2"  style="width:10%">Billing Event<br>(<code>billing_event</code>)</th>
    <th colspan="2" style="width:33%; text-align: center;">Attribution window</th>
    <th rowspan="2"  style="width:16%">Event count<br>(<code>attribution_event_count</code>)</th>
  </tr>
  <tr>
    <th  style="width:16%">Click-through attribution window<br>(<code>click_attribution_window</code>)</th>
    <th  style="width:17%">View-through attribution window<br>(<code>view_attribution_window</code>)</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="2">Website conversions<br>(<code>WEB_CONVERSIONS</code>)</td>
    <td rowspan="2">Website<br>(<code>promotion_type</code> is set to <code>WEBSITE</code>, and<code>promotion_website_type</code> is set to <code>UNSET</code> or not passed)</td>
    <td>Conversion<br>(<code>CONVERT</code>)</td>
    <td rowspan="2">oCPM <br>(<code>OCPM</code>)</td>
    <td rowspan="2">Supported values:<ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></td>
    <td rowspan="2">Supported Values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul></td>
    <td>Supported values:<ul><li><code>ONCE</code></li><li><code>EVERY</code></li></ul></td>
  </tr>
  <tr>
    <td>Value <br> (<code>VALUE</code>)</td>
    <td>Supported value:<ul><li><code>EVERY</code></li></ul></td>
  </tr>
</tbody>
</table>
  </div>
