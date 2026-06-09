---
title: "Realtime API"
doc_id: 1805437148769281
path: "Marketing API / Campaign Management / Guides / Campaign / Realtime API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Realtime API

This article introduces how to enable Realtime API (RTA) for your ads.

# Introduction
You can utilize real-time audience data directly in your ad creation workflow to streamline the ad creation process and help you create more tailored campaigns through real-time targeting capabilities. The RTA enables you to integrate real-time audience data to customize ad auction and targeting, thereby delivering better performance.
 
**You can use Campaign Management API to set up campaigns that utilize the RTA functionality. This helps you save time and focus on optimizing your overall advertising strategy.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To enable Realtime API, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.  
- Enabling RTA for your ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. You will need to complete the onboarding process to be able to receive and respond to RTA requests sent to your system first.
- Using RTA bids for your ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

# Steps
Based on your preference for the advertising objective, you can enable RTA for App Promotion campaigns, Web Conversions campaigns, or Product Sales campaigns. For detailed instructions on each scenario, refer to their respective sections: "<a href="#item-link-Enable RTA for App Promotion campaigns">Enable RTA for App Promotion campaigns</a>", "<a href="#item-link-Enable RTA for Web Conversions campaigns">Enable RTA for Web Conversions campaigns</a>", and "<a href="#item-link-Enable RTA for Product Sales campaigns">Enable RTA for Product Sales campaigns</a>".

## Enable RTA for App Promotion campaigns
1. Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%}| Requirement {25%}| Parameters{25%} | How to configure the parameters{30%} |
|---|---|---|---|
| Advertising objective | App Promotion | `objective_type` | `APP_PROMOTION` |
| App promotion type | Any of the following types:<ul><li>App install </li><li>App retargeting  </li></ul> | `app_promotion_type` | Any of the following values: <ul><li>`APP_INSTALL`</li><li>`APP_RETARGETING`</li></ul>|
| Smart+ Campaign | Disabled <br><br> You cannot use [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) to create Smart+ Campaigns with RTA enabled. | / | / |
| Realtime API (RTA) | Enabled | `rta_id` | Pass one of the RTA IDs associated with your ad account.<br><br>  To obtain the list of RTA IDs associated with your ad account, contact your TikTok representative. |
| Use RTA bid (Optional) | Enabled or disabled | `rta_bid_enabled` | `true` or `false` (or not passed)<br><br><p><span style="color:darkred"><b>Note</b></span>: Using RTA bids for your ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
```

2. Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

```xtable
| Setting {20%}| Requirement {25%}| Parameters{25%} | How to configure the parameters{30%} |
|---|---|---|---|
| Placement | <b>Select Placement</b> with TikTok placement | `placement_type`<br>`placements`  | Set `placement_type` to `PLACEMENT_TYPE_NORMAL`, and set `placements` to `["PLACEMENT_TIKTOK"]`. |
| Include search results <br>(Optional)| Enabled (recommended) or Disabled | `search_result_enabled`|`true` or `false`|
| Audience targeting<br>&nbsp;&nbsp;• Audience <br>&nbsp;&nbsp;&nbsp;&nbsp;◦ Include <br>&nbsp;&nbsp;&nbsp;&nbsp;◦ Exclude | Disabled | `audience_ids`<br> `excluded_audience_ids` | Not passed |
```

3. Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354).

## Enable RTA for Web Conversions campaigns
1. Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%}| Requirement {25%}| Parameters{25%} | How to configure the parameters{30%} |
|---|---|---|---|
| Advertising objective |Website Conversions | `objective_type` | `WEB_CONVERSIONS` |
| Smart+ Campaign | Disabled <br><br> You cannot use [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) to create Smart+ Campaigns with RTA enabled. | / | / |
| Realtime API (RTA) | Enabled | `rta_id` | Pass one of the RTA IDs associated with your ad account.<br><br>  To obtain the list of RTA IDs associated with your ad account, contact your TikTok representative. |
| Use RTA bid (Optional) | Enabled or disabled | `rta_bid_enabled` | `true` or `false` (or not passed)<br><br><p><span style="color:darkred"><b>Note</b></span>: Using RTA bids for your ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. |
```

2. Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). 

To learn about how to configure ad group level and ad level parameters for Web Conversions campaigns, see [Create Website Conversions ads](https://business-api.tiktok.com/portal/docs?id=1775548501843970).

3. Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354).


## Enable RTA for Product Sales campaigns
1. Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.

```xtable
| Setting {20%}| Requirement {25%}| Parameters{25%} | How to configure the parameters{30%} |
|---|---|---|---|
| Advertising objective | Product Sales | `objective_type` | `PRODUCT_SALES` |
| Smart+ Campaign| Disabled <br><br>You cannot use  [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362) to create Smart+ Campaigns with RTA enabled. | / | / |
| Campaign product source | Catalog | `campaign_product_source` | `CATALOG` |
| Realtime API (RTA) | Enabled | `rta_id` | Pass one of the RTA IDs associated with your ad account. <br><br>To obtain the list of `RTA` IDs associated with your ad account, contact your TikTok representative. |
| Use RTA to automatically select products <br>(Optional)| Enabled or disabled | `rta_product_selection_enabled` | `true` or `false` (or not passed) |
```

2. Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
  <thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th  style="width:25%">Requirement</th>
    <th  style="width:25%">Parameters</th>
    <th  style="width:30%">How to configure the parameters</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td>Shopping ads type</td>
    <td>Video Shopping Ads</td>
    <td><code>shopping_ads_type</code></td>
    <td><code>VIDEO</code></td>
  </tr>
  <tr>
    <td>Optimization location <br>(Promotion type)</td>
    <td>Any of the following options:<ul><li>App</li><li>Web</li></ul> </td>
    <td><code>promotion_type</code></td>
    <td>Any of the following values:<ul><li><code>APP_ANDRIOD</code> or <code>APP_IOS</code></li><li><code>WEBSITE</code></li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Product source</td>
    <td rowspan="2">Catalog</td>
    <td><code>product_source</code></td>
    <td><code>CATALOG</code></td>
  </tr>
  <tr>
    <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
    <td>Pass valid values.<br><br>To obtain the IDs of catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>.</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td><b>Select Placement</b> with TikTok placement</td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and set <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</td>
  </tr>
  <tr>
    <td>Include search results</td>
    <td>Disabled</td>
    <td><code>search_result_enabled</code></td>
    <td><code>false</code></td>
  </tr>
  <tr>
  	<td>Audience targeting</td>
  	<td>
    	<ul>
      		<li>Audience: Find prospective customers, including those who have not interacted with your products.</li>
      		<li>Retarget audience is not supported.</li>
    	</ul>
  	</td>
  	<td><code>shopping_ads_retargeting_type</code><br><code>shopping_ads_retargeting_actions_days</code><br><code>included_custom_actions</code><br><code>excluded_custom_actions</code><br><code>shopping_ads_retargeting_custom_audience_relation</code></td>
  	<td>Set <code> shopping_ads_retargeting_type</code> to <code>OFF</code>. <br><br> Do not pass <code>shopping_ads_retargeting_actions_days</code>, <code>included_custom_actions</code>, <code>excluded_custom_actions</code>, and <code>shopping_ads_retargeting_custom_audience_relation</code>. Once passed, the parameters <code>shopping_ads_retargeting_actions_days</code>, <code>included_custom_actions</code>, <code>excluded_custom_actions</code>, and <code>shopping_ads_retargeting_custom_audience_relation</code> will be ignored.</td>
   </tr>
  <tr>
    <td>Audience targeting<br>&nbsp;&nbsp;• Demographics - Location</td>
    <td>Specify locations within the targeting country or region of the catalog</td>
    <td><code>location_ids</code></td>
    <td>Pass the IDs of locations within the targeting country or region of the catalog (<code>catalog_id</code>).<br><br>To obtain the location IDs that you can target, use <a href="https://business-api.tiktok.com/portal/docs?id=1737189539571713" target="_blank">/tool/region/</a>.</td>
  </tr>
</tbody>
</table>

3. Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). Note that the following requirements must be met. 

```xtable
| Setting {20%}| Requirement {25%}| Parameters{25%} | How to configure the parameters{30%} |
|---|---|---|---|
| Products |<ul><li>When "Use RTA to automatically select products" is enabled at the campaign level: <b>All products</b></li><li>When "Use RTA to automatically select products" is disabled, use any of the following options:<ul><li><b>All products</b></li><li><b>Product set</b></li><li><b>Specific products</b></li></ul></li></ul> | `product_specific_type` | <ul><li>When `rta_product_selection_enabled` is true at the campaign level: `ALL`</li><li>When `rta_product_selection_enabled` is false or not passed at the campaign level, use any of the following values:<ul><li>`ALL`</li><li>`PRODUCT_SET`</li><li>`CUSTOMIZED_PRODUCTS`</li></ul></li></ul> |
```
