---
title: "App promotion"
doc_id: 1747553433902082
path: "Marketing API / Campaign Management / Guides / Campaign / Advertising objective / App promotion"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# App promotion

With the App promotion objective, you can find users to install your mobile app and re-engage your app's users. See [About the New App Promotion Advertising Objective](https://ads.tiktok.com/help/article?aid=10012707) for more details.  
><span style="color:DodgerBlue">**Note**</span><br>The App promotion objective is only available in TikTok API for Business v1.3. 

Currently, App promotion supports the following three app promotion types: 
- App install (`APP_INSTALL`): Get new users to install and take action in your app. <br>**It is identical to the existing App installs objective**.
- App retargeting (`APP_RETARGETING`): Re-engage existing users to take action on your app. <br>**The App retargeting campaign type replaces the functionality of an App conversion campaign**. 
><span style="color:DodgerBlue">**Note**</span><br>App retargeting is not supported in iOS14 Dedicated Campaigns. 
- App Pre-Registration (`APP_PREREGISTRATION`): Get new users to pre-register before your app launches. To learn about how to create App Pre-Registration ads, refer to [Create App Pre-Registration ads](https://ads.tiktok.com/marketing_api/docs?id=1772549012687873).
> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>App Pre-Registration is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>App Pre-Registration is not supported in iOS14 Dedicated Campaigns.</li></ul>


## Create App promotion ads
1. Use [/campaign/create/](https://ads.tiktok.com/marketing_api/docs?id=1739318962329602) to create a campaign. 

Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:15%">Setting</th>
    <th style="width:35%">Requirement</th>
    <th style="width:17%">Parameter</th>
    <th style="width:33%">How to configure the parameter</th>
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
    <td>One of the following types: <ul><li> App install </li><li> App retargeting </li><li> App pre-registration </li></ul></td>
    <td><code>app_promotion_type</code></td>
    <td>Any value below: <ul><li><code>APP_INSTALL</code></li><li><code>APP_RETARGETING</code> </li><li><code>APP_PREREGISTRATION</code></li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3"><ul><li> Supported only when App promotion type is App install.</li><li> Not supported when App promotion type is App retargeting or App pre-registration.</li></ul></td>
    <td><code>campaign_type</code></td>
    <td><ul><li> Can be set to <code>IOS14_CAMPAIGN</code> only when <code>app_promotion_type</code> is <code>APP_INSTALL</code>.</li><li> Cannot be set to <code>IOS14_CAMPAIGN</code> when <code>app_promotion_type</code> = <code>APP_RETARGETING</code> or <code>APP_PREREGISTRATION</code>. </li></ul></td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td><ul><li> Supported only when <code>app_promotion_type</code> is <code>APP_INSTALL</code>. </li><li> Not supported when <code>app_promotion_type</code> = <code>APP_RETARGETING</code> or <code>APP_PREREGISTRATION</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td><ul><li> Supported only when <code>app_promotion_type</code> is <code>APP_INSTALL</code>. This field can be used to determine whether to use App Profile Page to optimize delivery. Default value is <code>OFF</code>.</li><li> Not supported when <code>app_promotion_type</code> = <code>APP_RETARGETING</code> or <code>APP_PREREGISTRATION</code>. </li></ul></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td><ul><li> Supported only for App Install campaigns (<code>app_promotion_type</code> =<code>APP_INSTALL</code>) </li><li> Not supported for App retargeting campaigns or App Pre-Registration campaign (<code>app_promotion_type</code> = <code>APP_RETARGETING</code> or <code>APP_PREREGISTRATION</code>). </li></ul>If you are not sure about whether the campaign is Smart+ Campaign, you can use the <code>is_smart_performance_campaign</code> field returned from <a href="https://ads.tiktok.com/marketing_api/docs?id=1739315828649986"  target="_blank">/campaign/get/</a> to check.</td>
    <td>/</td>
    <td>/</td>
  </tr>
</tbody>
</table>

2. Use [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114) to create an ad group. 
   <br>If your campaign is an iOS14 Dedicated Campaign, note that: 
  -    In the `ios14_targeting` field, you need to either specify a consistent iOS14 value (i.e. what you enter in the `campaign_type` field at the campaign level) , or choose not to pass in any value and we will automatically set them. <br>For example, if `campaign_type` is `IOS14_CAMPAIGN`, you should either specify `ios14_targeting` as `IOS14_PLUS` or pass in no value. 
  - In the `app_id` field, you need to specify the same ID as you entered in `app_id` at the campaign level , or choose not to pass in any value and we will automatically set it.  
  - In the `adgroup_app_profile_page_state` field, you need to specify the same value as you entered in the `campaign_app_profile_page_state` at the campaign level, or choose not to pass in any value and we will automatically set it. 

If you want to create an App Pre-Registration ad group, refer to [Create App Pre-Registration ads](https://ads.tiktok.com/marketing_api/docs?id=1772549012687873).

3. Use [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354) to create an ad. 

If you want to create an App Pre-registration ad, refer to [Create App Pre-Registration ads](https://ads.tiktok.com/marketing_api/docs?id=1772549012687873).



## Related use cases
[Create App Pre-Registration ads](https://business-api.tiktok.com/portal/docs?id=1772549012687873)
