---
title: "Configure destination and deeplink settings for website-promoting Video Shopping Ads"
doc_id: 1774519076905985
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / Create Catalog Ads / Configure destination and deeplink settings for website-promoting Video Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Configure destination and deeplink settings for website-promoting Video Shopping Ads

This article introduces how to configure specific destination and deeplink settings for Video Shopping Ads that promote websites.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Using deeplink in Shopping Ads that promote websites is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Using dynamic destination in Shopping Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul>

1. **Create a campaign** using [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602). 
<br>The parameter requirements listed in the section [Create Video Shopping Ads with products from catalogs-1. Create a campaign](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-1.%20Create%20a%20campaign) must be met.

2. **Create and manage catalogs.** <br>Follow the steps outlined in the section [Create Video Shopping Ads with products from catalogs-2. Create and manage catalogs](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-2.%20Create%20and%20manage%20catalogs).

3. **Create an ad group** using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). <br>Note that in addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-3. Create an ad group](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-3.%20Create%20an%20ad%20group), the following requirements must be met at the same time.

``` xtable
| Setting {20%} | Requirement {25%} | Parameter {20%} | How to configure the parameter {35%} |
|---|---|---|
| Promotion type <br>(Optimization location) | Website| `promotion_type`| `WEBSITE`|
| Optimization goal | Click | `optimization_goal` | `CLICK` |
| Billing event | CPC | `billing_event` | `CPC` |
```
4. **Create an ad** using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). <br>In addition to the requirements listed in the section [Create Video Shopping Ads with products from catalogs-4. Create an ad](https://ads.tiktok.com/marketing_api/docs?id=1750361698613249#item-link-4.%20Create%20an%20ad), you have the flexibility to select from various settings based on your preference for destination and deeplink. There are a total of six combinations available, offering different types of deeplinks and fallback URLs:
- Custom deeplink: Use the deeplink you provide in the ad.
- Product deeplink: Use the deeplink (`ios_url` and `android_url`) you've provided for each product in the catalog.
- Custom link: Fall back to the web page link you provide.
- Product link: Fall back to the web page link (`landing_page_url`) you've provided for each product in the catalog.

To learn more about deeplinks, see [Deeplink](https://business-api.tiktok.com/portal/docs?id=1779541971843073).

You need to select **one** setting from the following options:
<table>
<thead>
  <tr>
    <th colspan="3" style="text-align: center;" "width:45%">Setting</th>
    <th rowspan="2" style="width:20%">Parameters</th>
    <th rowspan="2" style="width:35%">How to configure the parameters</th>
  </tr>
  <tr>
    <th style="width:15%">Destination</th>
    <th style="width:15%">Deeplink</th>
    <th style="width:15%">Fallback URL</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="3">Website - Custom link<br></td>
    <td rowspan="3">Custom deeplink<br></td>
    <td rowspan="3">Custom link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>shopping_ads_deeplink_type</code><br><code>deeplink</code><br><code>deeplink_type</code></td>
    <td>To specify the deeplink type as non-deferred custom deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>CUSTOM</code>,</li><li>Pass <code>deeplink</code>,</li><li>AND set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code><br><code>landing_page_url</code></td>
    <td>To specify the fallback URL as custom link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>,</li><li>AND pass <code>landing_page_url</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Website - Custom link</td>
    <td rowspan="2">❌</td>
    <td rowspan="2">Custom link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code><br><code>landing_page_url</code></td>
    <td>To specify the fallback URL as custom link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>,</li><li>AND pass <code>landing_page_url</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Website - Product link<br></td>
    <td rowspan="3">product deeplink<br></td>
    <td rowspan="3"> Product link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>shopping_ads_deeplink_type</code><br><code>deeplink_type</code></td>
    <td>To specify the deeplink type as non-deferred product deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>SHOPPING_ADS</code>,</li><li>AND set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_deeplink_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li> <code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code></td>
    <td><code>SHOPPING_ADS</code><br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li> <code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Website - Product link</td>
    <td rowspan="2">❌<br></td>
    <td rowspan="2">Product link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code></td>
    <td><code>SHOPPING_ADS</code><br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li> <code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="4">Instant Product Page<br></td>
    <td rowspan="4">Custom deeplink</td>
    <td rowspan="4">Custom link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>instant_product_page_used</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td><code>deeplink</code><br><code>deeplink_type</code><br></td>
    <td>To specify the deeplink type as non-deferred custom deeplink:<ul><li>Pass <code>deeplink</code>,</li><li>AND set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code><br><code>landing_page_url</code></td>
    <td>To specify the fallback URL as custom link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>,</li><li>AND pass <code>landing_page_url</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Instant Product Page<br></td>
    <td rowspan="3">❌</td>
    <td rowspan="3">Custom link</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or do not pass the parameter</td>
  </tr>
  <tr>
    <td><code>instant_product_page_used</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td><code>shopping_ads_fallback_type</code><br><code>landing_page_url</code><br><code>deeplink_type</code></td>
    <td>To specify the fallback URL as custom link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>,</li><li>Pass <code>landing_page_url</code>,</li><li>AND set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul></td>
  </tr>
</tbody>
</table>
