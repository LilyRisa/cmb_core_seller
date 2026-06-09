---
title: "Deeplink"
doc_id: 1779541971843073
path: "Marketing API / Campaign Management / Guides / Ad / Deeplink"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Deeplink

Deeplinks are a feature that directs a user to a certain page within the promoted App, optimizing user journeys and improving ad performance.

# Deeplink types and fallback URL
Two deeplink types are supported at the ad level: non-deferred deeplinks and deferred deeplinks.

## Non-deferred deeplink
This deeplink type leads installed users (users who have installed your App) directly to a certain page within the promoted App. Non-deferred deeplinks are useful for re-engagement with installed users. For example, you can set the deeplink to the discount page during the holiday season.

Supported formats: 
  - Custom URL scheme, in the format of `scheme://resource`. For instance, a Custom URL scheme of WhatsApp should follow the format `whatsapp://resource`.
  -  Apple’s universal link starting with `http://` or `https://`.
  -  Android App Link starting with `http://` or `https://`.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Non-deferred deeplinks are currently an allowlist-only feature for some scenarios. To check the scenarios where non-deferred deeplinks are an allowlist-only feature, see the "<a href="#item-link-Deeplink">Availability - Deeplink</a>" section. If you would like to access it for these scenarios, please contact your TikTok representative. </li><li>Non-deferred deeplinks are not an allowlist-only feature for the Lead generation objective with optimization location as social media app, but the optimization location of social media app under the Lead generation objective is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </li></ul>


  
## Deferred deeplink
This deeplink type leads new users who have NOT installed the App to a certain page within the promoted App after they download and install the App.

To learn about the supported scenarios for non-deferred deeplinks and deferred deeplinks, see the "<a href="#item-link-Deeplink">Availability - Deeplink</a>" section.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Deferred deeplinks are currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. Note that implementing deferred deeplinks require technical resources and development capabilities.</li><li>Deferred deeplinks are not available in Dedicated Campaigns. </li></ul>

## Fallback URL
Fallback URL is a URL that directs users who have not installed the App to a designated page (for instance, the homepage of your official website) when a deeplink is configured for an ad. To learn about the supported scenarios for fallback URLs, see the "<a href="#item-link-Fallback type">Availability - Fallback type</a>" section.


## Availability
The following tables outline the supported scenarios for deeplinks, including non-deferred deeplinks and deferred deeplinks, and fallback URLs.

### Deeplink
<div style="overflow-x:auto;">
<table style="width:1800px">
<thead>
  <tr>
    <th rowspan="2" style="width:10%">Advertising objective</th>
    <th rowspan="2" style="width:10%">Optimization location<br>(Promotion type)</th>
    <th rowspan="2"  style="width:24%">Corresponding API settings</th>
    <th colspan="4"  style="width:28%">Non-deferred deeplink<br></th>
    <th colspan="4" style="width:28%">Deferred deeplink</th>
  </tr>
  <tr>
    <th style="width:7%">iOS Dedicated Campaign<br></th>
    <th style="width:9%">iOS non-Dedicated Campaign</th>
    <th style="width:6%">Android Campaign</th>
    <th style="width:6%">Web campaign</th>
    <th style="width:7%">iOS Dedicated Campaign</th>
    <th style="width:9%">iOS non-Dedicated Campaign</th>
    <th style="width:6%">Android Campaign</th>
    <th style="width:6%">Web campaign</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Reach</td>
    <td>-</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>REACH</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>WEBSITE_OR_DISPLAY</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Video views</td>
    <td>-</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>VIDEO_VIEWS</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>WEBSITE_OR_DISPLAY</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Lead generation</td>
    <td>Instant Form</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>LEAD_GENERATION</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>LEAD_GENERATION</code></li><li><code>promotion_target_type</code> is set to <code>INSTANT_PAGE</code> or not passed</li></ul></li></ul><br>To learn about how to create such ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1774482920012801">Create a Lead Generation ad with promotion type as Instant Form</a>.</td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Lead generation</td>
    <td>Social media app </td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>LEAD_GENERATION</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>LEAD_GEN_CLICK_TO_SOCIAL_MEDIA_APP_MESSAGE</code></li></ul></li></ul><br>To learn about how to create such ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1774482999036930">Create a Lead Generation ad with promotion type as social media app</a>.</td>
    <td>❌</td>
    <td>Allowlist</td>
    <td>Allowlist</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Traffic</td>
    <td>App</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>TRAFFIC</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>APP_ANDROID</code> or <code>APP_IOS</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Traffic</td>
    <td>Website <br>(CPC only)</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>TRAFFIC</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>WEBSITE</code></li><li><code>billing_event</code> is set to <code>CPC</code></li></ul></li></ul></td>
    <td>GA</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>App promotion -<br> App install <br>(Click, <br>Mobile App Install, <br>App Event Optimization, <br>Value-Based Optimization)</td>
    <td>App</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>APP_PROMOTION</code></li><li><code>app_promotion_type</code> is set to <code>APP_INSTALL</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>APP_ANDROID</code> or <code>APP_IOS</code></li><li><code>optimization_goal</code> is set to <code>CLICK</code>, <code>INSTALL</code>, <code>IN_APP_EVENT</code> or <code>VALUE</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>Allowlist</td>
    <td>Allowlist</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>App promotion - <br>App retargeting</td>
    <td>App</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>APP_PROMOTION</code></li><li><code>app_promotion_type</code> is set to <code>APP_RETARGETING</code></li></ul></li><li>At the ad group level:<ul><li><code>promotion_type</code> is set to <code>APP_ANDROID</code> or <code>APP_IOS</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Product sales (Catalog) - <br>App retargeting</td>
    <td>App</td>
    <td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>PRODUCT_SALES</code></li><li><code>campaign_product_source</code> is set to <code>CATALOG</code></li></ul></li><li>At the ad group level:<ul><li><code>shopping_ads_type</code> is set to <code>VIDEO</code></li><li><code>promotion_type</code> is set to <code>APP_ANDROID</code> or <code>APP_IOS</code></li><li><code>product_source</code> is set to <code>CATALOG</code></li><li><code>shopping_ads_retargeting_type</code> is set to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code></li></ul></li></ul></td>
    <td>❌</td>
    <td>GA<br></td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Product sales (Catalog) - <br>App prospecting</td>
    <td>App</td>
    <td><ul><li>For deferred deeplinks, the following requirements must be met:<ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>PRODUCT_SALES</code></li><li><code>campaign_product_source</code> is set to <code>CATALOG</code></li></ul></li><li>At the ad group level:<ul><li><code>shopping_ads_type</code> is set to <code>VIDEO</code></li><li><code>promotion_type</code> is set to <code>APP_ANDROID</code> or <code>APP_IOS</code></li><li><code>product_source</code> is set to <code>CATALOG</code></li><li><code>shopping_ads_retargeting_type</code> is set to <code>OFF</code> or not passed</li></ul></li></ul></li><li>For non-deferred deeplinks, see <a href="https://business-api.tiktok.com/portal/docs?id=1782087855154177" target="_blank">Optimize Destination visit in Video Shopping Ads</a>.<br><p><span style="color:darkred"><b>Note</b></span>: The optimization goal Destination visit is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul> </td>
    <td>❌</td>
    <td>GA</td>
    <td>GA</td>
    <td>❌</td>
    <td>❌</td>
    <td>Allowlist</td>
    <td>Allowlist</td>
    <td>❌</td>
  </tr>
  <tr>
    <td>Product Sales (Catalog) </td>
    <td>Website</td>
	<td><ul><li>At the campaign level:<ul><li><code>objective_type</code> is set to <code>PRODUCT_SALES</code></li><li><code>campaign_product_source</code> is set to <code>CATALOG</code></li></ul></li><li>At the ad group level:<ul><li><code>shopping_ads_type</code> is set to <code>VIDEO</code></li><li><code>promotion_type</code> is set to <code>WEBSITE</code></li><li><code>product_source</code> is set to <code>CATALOG</code></li><li><code>optimization_goal</code> is set to <code>CLICK</code></li></ul></li></ul> <br>To learn about how to configure specific destination and deeplink settings for website-promoting Video Shopping Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1774519076905985"  target="_blank">Configure destination and deeplink settings for website-promoting Video Shopping Ads</a>.</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>Allowlist</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
    <td>❌</td>
  </tr>
</tbody>
</table>
</div>

### Fallback type
```xtable
| Advertising objective {20%} | Optimization location{20%}  | Fallback type {60%} |
|---|---|---|
| Reach | - | ❌<br><br>By default, fall back to the promoted web page that is specified through `landing_page_url` at the ad level. |
| Video views | - | ❌ <br><br>By default, fall back to the promoted web page that is specified through `landing_page_url` at the ad level. |
| Lead generation | Social media app | <ul><li> Website: the promoted Apple’s universal link or Android App Link that starts with `http:// ` or `https:// ` and is specified through `landing_page_url` at the ad level.</li></ul> |
| Traffic | App | <ul><li> App store: the App Store or Google Play page for the promoted App (`app_id`).</li><li> Website: the promoted web page that is specified through `landing_page_url` at the ad level. </li></ul>|
| Traffic | Website (CPC only) | ❌<br><br>By default, fall back to the promoted web page that is specified through `landing_page_url` at the ad level. |
| App promotion - <br>App install <br> (Click, <br>Mobile App Install, <br>App Event Optimization, <br>Value Based Optimization)| App | ❌ <br><br>By default, fall back to the App Store or Google Play page for the promoted App specified through `app_id` at the ad group level. |
| App promotion - <br>App retargeting | App | ❌ <br><br>By default, fall back to the App Store or Google Play page for the promoted App specified through `app_id` at the ad group level. |
| Product sales (Catalog) - <br>App retargeting | App | <ul><li> App store: the App Store or Google Play page for the promoted App (`app_id`).</li><li> Product link: the web page link (`landing_page_url`) you've provided for each product in the catalog.</li><li> Custom link: the promoted web page that is specified through `landing_page_url` at the ad level.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Product link is not supported in any of the following scenarios at the ad level: <ul><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO` .</li><li>`ad_format` is set to `CATALOG_CAROUSEL`.</li></ul> |
| Product sales (Catalog) - <br>App prospecting | App | <ul><li>When you use deferred deeplinks, fallback type is not supported.<ul><li>By default, fall back to the App Store or Google Play page for the promoted App specified through `app_id` at the ad group level. </li></ul></li><li>When you use non-deferred deeplinks, the supported fallback types are: <ul><li> App store: the App Store or Google Play page for the promoted App (`app_id`). </li><li> Product link: the web page link (`landing_page_url`) you've provided for each product in the catalog. </li><li> Custom link: the promoted web page that is specified through `landing_page_url` at the ad level.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Product link is not supported in any of the following scenarios at the ad level: <ul><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO`. </li><li>`ad_format` is set to `CATALOG_CAROUSEL`. </li></ul></li></ul>|
| Product sales (Catalog) | Website | <ul><li> Product link: the web page link (`landing_page_url`) you've provided for each product in the catalog. </li><li> Custom link: the promoted web page that is specified through `landing_page_url` at the ad level. </li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Product link is not supported in any of the following scenarios at the ad level: <ul><li>`ad_format` is set to `SINGLE_VIDEO` and `vertical_video_strategy` is set to `SINGLE_VIDEO` .</li><li>`ad_format` is set to `CATALOG_CAROUSEL`.</li></ul> |
```
## Supported deeplink and fallback type settings for specific scenarios
The following table illustrates the supported deeplink and fallback type settings for a certain scenario, and how to configure the corresponding API configurations for the deeplink and fallback URL settings.

<table>
<thead>
  <tr>
    <th colspan="2"  style="width:32%">Scenario</th>
    <th colspan="2"  style="width:30%">Deeplink and fallback URL settings</th>
    <th rowspan="2"  style="width:38%">How to configure the API settings</th>
  </tr>
  <tr>
    <th  style="width:17%">Advertising objective</th>
    <th  style="width:15%">Optimization location</th>
    <th  style="width:15%">Deeplink</th>
    <th  style="width:15%">Fallback type</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Reach</td>
    <td>-<br></td>
    <td>Non-deferred deeplink</td>
    <td>❌</td>
	<td><ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Video views</td>
    <td>-</td>
    <td>Non-deferred deeplink</td>
    <td>❌</td>
    <td><ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Lead generation</td>
    <td>Social media app </td>
    <td>Non-deferred deeplink</td>
    <td>Website</td>
    <td><ul><li>To specify the deeplink type as non-deferred deeplink:<ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></li><li>To specify the fallback type as website:<ul><li>Set <code>fallback_type</code> to <code>WEBSITE</code>.</li><li>Pass <code>landing_page_url</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Traffic</td>
    <td>App</td>
    <td>Non-deferred deeplink</td>
    <td><ul><li>App store</li><li>Website</li></ul></td>
    <td><ul><li>To specify the deeplink type as non-deferred deeplink:<ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li><li> If you set <code>optimization_goal</code> to <code>DESTINATION_VISIT</code> at the ad group level, you need to pass <code>deeplink_format_type</code> at the same time. To learn about how to use the optimization goal Destination Visit in a Traffic ad, see <a href="https://business-api.tiktok.com/portal/docs?id=1782086241778690" target="_blank">Optimize Destination visit in Traffic ads</a>.</li></ul></li><li>To specify the fallback type, pass <code>fallback_type</code>.<ul><li>If you want to specify fallback type as App store, set <code>fallback_type</code> to <code>APP_INSTALL</code>, and do not pass <code>landing_page_url</code>.</li><li>If you want to specify fallback type as Website, set <code>fallback_type</code> to <code>WEBSITE</code>, and pass <code>landing_page_url</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Traffic</td>
    <td>Website (CPC only)</td>
    <td>Non-deferred deeplink</td>
    <td>❌</td>
    <td><ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <tr>
    <td>App promotion - <br>App install <br>(Click, <br>Mobile App Install, <br>App Event Optimization, <br>Value Based Optimization)</td>
    <td>App</td>
    <td>Deferred deeplink</td>
    <td>❌</td>
    <td><ul><li>Set <code>deeplink_type</code> to <code>DEFERRED_DEEPLINK</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <tr>
    <td>App promotion - <br>App retargeting</td>
    <td>App</td>
    <td>Non-deferred deeplink</td>
    <td>❌</td>
    <td><ul><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Product sales (Catalog) - <br>App retargeting</td>
    <td rowspan="2">App</td>
    <td>Non-deferred product deeplink</td>
    <td><ul><li>App store</li><li>Product link</li><li>Custom link</li></ul></td>
    <td><ul><li>To specify the deeplink type as non-deferred product deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>SHOPPING_ADS</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_deeplink_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>To specify the fallback type, pass <code>fallback_type</code> and <code>shopping_ads_fallback_type</code>.<ul><li>If you want to specify fallback type as App store, set <code>fallback_type</code> to <code>APP_INSTALL</code>, set <code>shopping_ads_fallback_type</code> to <code>DEFAULT</code>, and do not pass <code>landing_page_url</code>.</li><li>If you want to specify fallback type as product link, set <code>fallback_type</code> to <code>WEBSITE</code>, set <code>shopping_ads_fallback_type</code> to <code>SHOPPING_ADS</code>, and do not pass <code>landing_page_url</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>If you want to specify fallback type as custom link, set <code>fallback_type</code> to <code>WEBSITE</code>, set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>, and pass <code>landing_page_url</code>.</li></ul>
</li></ul></td>
  </tr>
  <tr>
    <td>Non-deferred custom deeplink</td>
    <td><ul><li>App store</li><li>Product link</li><li>Custom link</li></ul></td>
    <td><ul><li>To specify the deeplink type as non-deferred custom deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>NORMAL</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></li><li>To specify the fallback type, pass <code>fallback_type</code> and <code>shopping_ads_fallback_type</code>.<ul><li>If you want to specify fallback type as App store, set <code>fallback_type</code> to <code>APP_INSTALL</code>, set <code>shopping_ads_fallback_type</code> to <code>DEFAULT</code>, and do not pass <code>landing_page_url</code>.</li><li>If you want to specify fallback type as product link, set <code>fallback_type</code> to <code>WEBSITE</code>, set <code>shopping_ads_fallback_type</code> to <code>SHOPPING_ADS</code>, and do not pass <code>landing_page_url</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>If you want to specify fallback type as custom link, set <code>fallback_type</code> to <code>WEBSITE</code>, set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>, and pass <code>landing_page_url</code>.</li></ul>
</li></ul></td>
  </tr>
  <tr>
    <td rowspan="4">Product sales (Catalog) - <br>App prospecting</td>
    <td rowspan="4">App</td>
    <td>Deferred product deeplink</td>
    <td>❌</td>
   <td><ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>SHOPPING_ADS</code>.</li><li>Set <code>deeplink_type</code> to <code>DEFERRED_DEEPLINK</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_deeplink_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Deferred custom deeplink</td>
    <td>❌</td>
    <td><ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>CUSTOM</code>.</li><li>Set <code>deeplink_type</code> to <code>DEFERRED_DEEPLINK</code>.</li><li>Pass <code>deeplink</code>.</li></ul></td>
  </tr>
  <td>Non-deferred product deeplink</td>
    <td><ul><li>App store</li><li>Product link</li><li>Custom link</li></ul></td>
    <td><ul><li>To specify the deeplink type as non-deferred product deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>SHOPPING_ADS</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Set <code>fallback_type</code> to <code>UNSET</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_deeplink_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>To specify the fallback type, pass <code>shopping_ads_fallback_type</code>.<ul><li>If you want to specify fallback type as App store, set <code>shopping_ads_fallback_type</code> to <code>DEFAULT</code>, and do not pass <code>landing_page_url</code>.</li><li>If you want to specify fallback type as product link, set <code>shopping_ads_fallback_type</code> to <code>SHOPPING_ADS</code>, and do not pass <code>landing_page_url</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>If you want to specify fallback type as custom link, set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>, and pass <code>landing_page_url</code>.</li></ul>
</li></ul></td>
  </tr>
  <tr>
    <td>Non-deferred custom deeplink</td>
    <td><ul><li>App store</li><li>Product link</li><li>Custom link</li></ul></td>
    <td><ul><li>To specify the deeplink type as non-deferred custom deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>NORMAL</code> and pass <code>deeplink</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Set <code>fallback_type</code> to <code>UNSET</code>.</li></ul></li><li>To specify the fallback type, pass <code>shopping_ads_fallback_type</code>.<ul><li>If you want to specify fallback type as App store, set <code>shopping_ads_fallback_type</code> to <code>DEFAULT</code>, and do not pass <code>landing_page_url</code>.</li><li>If you want to specify fallback type as product link, set <code>shopping_ads_fallback_type</code> to <code>SHOPPING_ADS</code>, and do not pass <code>landing_page_url</code>.<br><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>If you want to specify fallback type as custom link, set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>, and pass <code>landing_page_url</code>.</li></ul>
</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Product sales (Catalog) </td>
    <td rowspan="2">Website</td>
    <td>Non-deferred product deeplink</td>
    <td>Product link</td>
    <td><ul><li>To specify the deeplink type as non-deferred product deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>SHOPPING_ADS</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_deeplink_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li><li>To specify the fallback URL as product link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>SHOPPING_ADS</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code> in any of the following scenarios: <ul><li><code>ad_format</code> is set to <code>SINGLE_VIDEO</code> and <code>vertical_video_strategy</code> is set to <code>SINGLE_VIDEO</code>.</li><li><code>ad_format</code> is set to <code>CATALOG_CAROUSEL</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Non-deferred custom deeplink</td>
    <td>Custom link</td>
     <td><ul><li>To specify the deeplink type as non-deferred custom deeplink:<ul><li>Set <code>shopping_ads_deeplink_type</code> to <code>CUSTOM</code>.</li><li>Set <code>deeplink_type</code> to <code>NORMAL</code>.</li><li>Pass <code>deeplink</code>.</li></ul></li><li>To specify the fallback URL as custom link:<ul><li>Set <code>shopping_ads_fallback_type</code> to <code>CUSTOM</code>.</li><li>Pass <code>landing_page_url</code>.</li></ul></li></ul></td>
  </tr>
</tbody>
</table>
