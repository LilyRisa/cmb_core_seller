---
title: "Create Catalog Ads"
doc_id: 1750361698613249
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Video Shopping Ads / Create Catalog Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Catalog Ads

This article introduces how to create Catalog Ads (formerly known as Video Shopping Ads (VSA) with products from catalogs).

> <span style="color:DodgerBlue">**Note**</span><br>
> Catalog Ads are currently an allowlist-only feature for Commerce App advertisers. If you are a Commerce App advertiser and would like to access it, please contact your TikTok representative. If you are a Web Commerce advertiser, you automatically get access to the feature.

# Steps

## 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
    <th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Advertising objective</td>
    <td>Product sales</td>
    <td><code>objective_type</code></td>
    <td><code>PRODUCT_SALES</code></td>
  </tr>
  <tr>
    <td>Smart+ Campaign</td>
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362"  target="_blank">/campaign/spc/create/</a> to create a Smart+ Video Shopping campaign.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled or Enabled</td>
    <td><code>campaign_type</code></td>
    <td>Any of the following values:<ul><li><code>REGULAR_CAMPAIGN</code> or not passed</li><li><code>IOS14_CAMPAIGN</code></li></ul> </td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td><ul><li> Not passed when <code>campaign_type</code> is <code>REGULAR_CAMPAIGN</code> or not passed</li><li>Specify the ID of an iOS App when <code>campaign_type</code> is <code>IOS14_CAMPAIGN</code>.<br>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786"  target="_blank">/app/list/</a>. </li></ul> </td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Campaign product source</td>
    <td>Catalog</td>
    <td><code>campaign_product_source</code></td>
    <td><code>CATALOG</code></td>
  </tr>
  <tr>
    <td>Special ad categories (Optional)</td>
    <td>Supported with allowlist</td>
    <td><code>special_industries</code></td>
    <td>Pass valid values or not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Using special ad categories in Shopping Ads with campaign product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>Supported with allowlist</td>
    <td><code>budget_optimize_on</code></td>
    <td>Any of the following values:<ul><li> <code>false</code> or not passed</li><li><code>true</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Campaign Budget Optimization for Video Shopping Ads with product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Campaign budget mode</td>
    <td>Any of the following types:<ul><li>Daily budget</li><li>Dynamic daily budget (with CBO enabled)</li><li>Lifetime budget</li><li>Infinite budget</li></ul></td>
    <td><code>budget_mode</code></td>
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY </code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> (only supported when <code>budget_optimize_on</code> is <code>true</code>)</li><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_INFINITE</code></li></ul></td>
  </tr>
</tbody>
</table>

### Example

Request
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "PRODUCT_SALES",
    "campaign_product_source":"CATALOG",
    "campaign_name": "{{campaign_name}}",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}}
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "operation_status": "ENABLE",
        "roas_bid": 0,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_app_profile_page_state": "UNSET",
        "objective_type": "PRODUCT_SALES",
        "campaign_type": "REGULAR_CAMPAIGN",
        "modify_time": "{{modify_time}}",
        "campaign_product_source": "CATALOG",
        "is_search_campaign": false,
        "budget": {{budget}},
        "objective": "LANDING_PAGE",
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "campaign_name": "{{campaign_name}}",
        "deep_bid_type": null,
        "is_smart_performance_campaign": false,
        "create_time": "{{create_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE"
    }
}
(/code)
```
## 2. Create and manage catalogs

Create and manage catalogs using Catalog APIs or TikTok Business Center. 
* Catalog APIs:  For all catalog APIs, see [here](https://ads.tiktok.com/marketing_api/docs?id=1739578477445121). 
  1. Create an **E-commerce** catalog using [/catalog/create/](https://ads.tiktok.com/marketing_api/docs?id=1740306481704961). 
  2. Upload products to the catalog using [/catalog/product/upload/](https://ads.tiktok.com/marketing_api/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://ads.tiktok.com/marketing_api/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
  3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889). 
  <br>Pass in the `feed_log_id` obtained from Step ii. If the field `warn_affected_products` in the response is not null, examine the issue details and return to Step ii. to reupload the product.
  4. (Optional) Create an E-commerce product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
  <br>If you want to have products selected from a product set at the ad level, creating a product set is necessary. Otherwise, you can skip this step.
  5. (Optional) Invite members to Business Center and grant the Admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). 
   <br> You can also choose `advertiser_role` that you want to assign to the members invited.
  6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). 
  <br> Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field. 
  
* TikTok Business Center
  1. Create a catalog in TikTok Business Center.
    <br>For detailed instructions, see [Manage Catalog in Business Center](https://ads.tiktok.com/help/article?aid=10012889).
  2. Upload your products to your catalog.
  3. (Optional) Invite a member to your Business Center and grant admin permission. 
    <br>For detailed instructions, see [Add Users to TikTok Business Center](https://ads.tiktok.com/help/article?aid=12790). 
  4. (Optional) Choose the advertiser account to be shared, add members to the account and grant them the admin access. 
  5. (Optional) Share a catalog with members and grant catalog management access. 
    <br>For detailed instructions, see [Manage Catalog in Business Center - Share Catalog with a Member](https://ads.tiktok.com/help/article?aid=10012889).

## 3. Create an ad group
Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:17%">Setting</th>
    <th style="width:25%">Requirement</th>
    <th style="width:23%">Parameters</th>
    <th style="width:35%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Shopping ads type</td>
    <td>VSA</td>
    <td><code>shopping_ads_type</code></td>
    <td><code>VIDEO</code></td>
  </tr>
  <tr>
    <td>Identity</td>
<td>Disabled</td>
<td><ul><li><code>identity_type</code></li><li><code>identity_id</code></li><li><code>identity_authorized_bc_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="3">Product source</td>
    <td rowspan="3">Catalog</td>
    <td><code>product_source</code></td>
    <td><code>CATALOG</code></td>
  </tr>
  <tr>
<td><ul><li><code>catalog_id</code></li><li><code>catalog_authorized_bc_id</code></li></ul></td>
    <td>Pass valid values<br>To learn about how to obtain a catalog ID, follow the steps outlined in <a href="#item-link-2. Create and manage catalogs">Create and manage catalogs</a>.</td>
  </tr>
  <tr>
<td><ul><li><code>store_id</code></li><li><code>store_authorized_bc_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Promotion type <br>(Optimization location)</td>
    <td>Website or App</td>
    <td><code>promotion_type</code></td>
    <td>Any of the following values: <ul><li><code>WEBSITE</code></li><li><code>APP_ANDRIOD</code></li><li><code>APP_IOS</code></li></ul></td>
  </tr>
  <tr>
    <td>Promoted website</td>
<td><ul><li>Specified when Optimization location is Website and Optimization goal is Conversion or Value </li><li> Not specified when Optimization location is Website and Optimization goal is Click or Landing page view, or when Optimization location is App </li></ul></td>
<td><ul><li><code>pixel_id</code></li><li><code>optimization_event</code></li></ul> </td>
    <td><ul><li> Pass valid values when <code>promotion_type</code> is <code>WEBSITE</code> and <code>optimization_goal</code> is <code>CONVERT</code> or <code>VALUE</code>. <br>To obtain the list of pixels under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>. </li><li> Not passed in the following scenarios:<ul><li><code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>.</li><li> <code>promotion_type</code> is <code>WEBSITE</code> and <code>optimization_goal</code> is <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></li></ul></td>
  </tr>
 
  <tr>
    <td rowspan="2">Promoted App</td>
    <td rowspan="2"><ul><li>Specified when Optimization location is App</li><li>Not specified when Optimization location is Website</li></ul> </td>
    <td><code>app_id</td>
    <td><ul><li> Pass a valid value when <code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>. <br>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.</li><li> Not passed when <code>promotion_type</code> is <code>WEBSITE</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>operating_systems</code></td>
    <td><ul><li> If you specify an Android App through <code>app_id</code>, set this field to <code>["ANDROID"]</code>.</li><li> If you specify an iOS App through <code>app_id</code>, set this field to <code> ["IOS"]</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following options: <ul><li> <strong>Automatic Placement</strong> </li><li> <strong>Select Placement</strong> with TikTok placement </li></ul></td>
    <td><ul><li><code>placement_type</code></li><li><code>placements</code></li></ul></td>
    <td>Any of the following settings: <ul><li> Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li> Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and set <code>placements</code> to <code>["PLACEMENT_TIKTOK"]</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Include search results</td>
    <td>Disabled</td>
    <td><code>search_result_enabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Video sharing</td>
    <td>Enabled</td>
    <td><code>share_disabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Pangle block list</td>
    <td> Disabled</td>
    <td><code>blocked_pangle_app_ids</code></td>
    <td> Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li> Saved audience </li></ul></td>
    <td>Disabled</td>
    <td><code>saved_audience_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Automatic targeting</li></ul></td>
    <td>Disabled</td>
    <td><code>auto_targeting_enabled</code></td>
    <td>Not passed </td>
  </tr>
    <tr>
    <td>Audience targeting<ul><li>Audience</li></ul></td>
    <td>Any of the following options: <ul><li> Find prospective customers: Find prospective customers, including those who have not interacted with your products.</li><li> Retarget audience: Promote products to people who have already interacted with them. <ul><li> If you select Retarget audience, ensure that an event source has been connected to the catalog. This will enable you to precisely target users who have interacted with your products.</li></ul></li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Retarget audience is not supported if you have enabled iOS 14 Dedicated Campaign at the campaign level.</td>
    <td><ul><li><code>shopping_ads_retargeting_type</code></li><li><code>shopping_ads_retargeting_actions_days</code></li><li><code>included_custom_actions</code></li><li><code>excluded_custom_actions</code></li><li><code>shopping_ads_retargeting_custom_audience_relation</code></li></ul></td>
    <td>Pass valid values<br><ul><li> To find prospective customers, set <code>shopping_ads_retargeting_type</code> to <code>OFF</code>.</li><li> To retarget audience, set <code>shopping_ads_retargeting_type</code> to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code>, and pass other related parameters. You also need to ensure an event source has been bound to the catalog (<code>catalog_id</code>). Otherwise, an error will occur. <ul><li> To check if an event source has been bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740492531343362" target="_blank" >/catalog/eventsource_bind/get/</a>.</li><li> To bind an event source to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740492491200513" target="_blank" >/catalog/eventsource/bind/</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you have set <code>campaign_type</code> to <code>IOS14_CAMPAIGN</code> at the campaign level, you cannot set <code>shopping_ads_retargeting_type</code> to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code>.</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li> Demographics - Spending power </li></ul></td>
    <td>Disabled</td>
    <td><code>spending_power</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li> Audience<ul><li> Smart audience </li></ul></li></ul> </td>
    <td>Disabled<br></td>
    <td><code>smart_audience_enabled</code></td>
    <td>Not passed<br></td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li> Interests & Behaviors <ul><li> Smart interests & behaviors </li></ul> </li></ul> </td>
    <td>Disabled </td>
    <td><code>smart_interest_behavior_enabled</code></td>
    <td>Not passed </td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li> Pangle audience packages </li></ul> </td>
    <td>Disabled</td>
    <td><ul><li><code>included_pangle_audience_package_ids </code></li><li><code>excluded_pangle_audience_package_ids </code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li> Targeting expansion </li></ul></td>
    <td>Disabled</td>
    <td><code>targeting_expansion</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Contextual Targeting</td>
    <td>Disabled</td>
    <td><code>contextual_tag_ids</code></td>
    <td>Not passed</td>
  </tr>
   <tr>
    <td rowspan="2">Content exclusions <ul><li> Inventory filter</li></ul></td>
    <td rowspan="2">Supported for <strong>Select Placement</strong> with TikTok placement with allowlist</td>
    <td><code>brand_safety_type</code></td>
    <td>When <code>placement_type</code> is <code>PLACEMENT_TYPE_NORMAL</code>, and <code>placements</code> is <code> ["PLACEMENT_TIKTOK"]</code>, you can pass a valid value (<code>EXPANDED_INVENTORY</code>, <code>STANDARD_INVENTORY</code>, or <code>LIMITED_INVENTORY</code>), or omit this field.<br><br><p><span style="color:darkred"><b>Note</b></span>: TikTok inventory filter in VSA with product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td><code>brand_safety_partner</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions <ul><li> Category exclusions</li></ul></td>
    <td>Disabled</td>
    <td><code>category_exclusion_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions <ul><li>Vertical sensitivity control</li></ul> </td>
    <td>Disabled</td>
    <td><code>vertical_sensitivity_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Optimization goal</td>
    <td>Any of the following types when Optimization location is App: <ul><li> Click </li><li> Install </li><li> In-app event </li><li> Destination visit (supported with allowlist)</li><li> Value </li></ul></td>
    <td rowspan="2"><code>optimization_goal</code></td>
<td>Any of the following values when <code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>:<ul><li><code>CLICK</code></li><li><code>INSTALL</code><ul><li> To use <code>INSTALL</code>, you need to ensure third-party tracking has been set up for the selected App. You can use <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297" target="_blank" >/app/info/</a> to check if third-party tracking (<code>tracking_url</code>) has been configured for an App. </li><li> To add third-party tracking for an existing App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859300069378" target="_blank" >/app/update/</a>. To learn about how to obtain tracking links from a third-party partner, see <a href="https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking?lang=en" target="_blank" >Mobile Measurement Partner (MMP) Tracking</a>.</li></ul></li><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified) <ul><li> To use <code>IN_APP_EVENT</code>, you need to configure your mobile measurement partner (MMP) to integrate with your ad account. To learn about the steps, see <a href="https://ads.tiktok.com/help/article/catalog-sales-mobile-measurement-partner-set-up?lang=en#anchor-0" target="_blank" >How to set up your Mobile Measurement Partner for Video Shopping Ads</a>.</li></ul></li><li><code>DESTINATION_VISIT</code><ul><li> To learn about how to use the optimization goal Destination Visit for the Product Sales objective, see <a href="https://business-api.tiktok.com/portal/docs?id=1782087855154177" target="_blank" >Optimize Destination Visit in Video Shopping Ads</a>.</li></ul></li><li><code>VALUE</code>  (with <code>optimization_event</code> set to <code>ACTIVE_PAY</code>)<ul><li> To learn about how to use the optimization goal Value for the Product Sales objective, see <a href="https://business-api.tiktok.com/portal/docs?id=1770019181843458" target="_blank">Enable Value-Based Optimization</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The optimization goal Destination Visit (<code>DESTINATION_VISIT</code>) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Any of the following types when Optimization location is Website: <ul><li> Click </li><li> Conversion </li><li> Landing page view (supported with allowlist)</li><li> Value </li></ul></td>
<td>Any of the following settings when <code>promotion_type</code> is <code>WEBSITE</code>:<ul><li><code>CLICK</code></li><li><code>CONVERT</code> (with <code>pixel_id</code> and <code>optimization_event</code> specified)</li><li><code>TRAFFIC_LANDING_PAGE_VIEW</code><ul><li> To learn about how to use the optimization goal Landing page view for the Product Sales objective, refer to <a href="https://business-api.tiktok.com/portal/docs?id=1775099621140482" target="_blank">Optimize Landing page view for Video Shopping Ads</a>.</li></ul></li><li><code>VALUE</code> (with <code>pixel_id</code> specified and <code>optimization_event</code> set to <code>SHOPPING</code>)<ul><li> To learn about how to use the optimization goal Value for the Product Sales objective, see <a href="https://business-api.tiktok.com/portal/docs?id=1770019181843458" target="_blank">Enable Value-Based Optimization</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Using the optimization goal Landing page view in Shopping Ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Frequency cap</td>
    <td>Disabled</td>
    <td><ul><li><code>frequency</code></li><li><code>frequency_schedule </code></li></ul></td>
    <td>Not passed</td>
  </tr>
   <tr>
    <td>Bid Strategy</td>
    <td><ul><li> If optimization goal is Click, Install, In-app event, Destination visit, Conversion, or Landing page view, the strategy can be Maximum Delivery or Cost Cap.</li><li> If optimization goal is Value, the strategy can be Highest value or Minimum ROAS.</li></ul></td>
    <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
<td><ul><li> If <code>optimization_goal</code> is <code>CLICK</code>, <code>INSTALL</code>, <code>IN_APP_EVENT</code>, <code>DESTINATION_VISIT</code>, <code>CONVERT</code> , or <code>TRAFFIC_LANDING_PAGE_VIEW</code>:<ul><li> Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li> If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>, <code>DESTINATION_VISIT</code>, <code>CONVERT</code> , or <code>TRAFFIC_LANDING_PAGE_VIEW</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li><li> If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>bid_price</code> at the same time.</li></ul></li></ul></li><li> If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li> Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li> If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Attribution settings</td>
    <td>Select from the supported attribution setting options for the specific scenario. </td>
    <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
    <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Product%20Sales" target="_blank" >Attribution window and event count - Product Sales</a>. </td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li> If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Install, In-app event, Destination visit, Conversion, or Landing page view, or Value, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li> If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li> If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>, <code>DESTINATION_VISIT</code>, <code>CONVERT</code> , <code>TRAFFIC_LANDING_PAGE_VIEW</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td><ul><li> If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li> If Bid Strategy is set to Maximum Delivery, Highest Value or Minimum ROAS, you can only set Delivery Type to Standard.</li></ul></td>
    <td><code>pacing</code></td>
<td><ul><li> If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.
</li><li> If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Automated Creative Optimization (ACO)</td>
    <td>Disabled</td>
    <td><code>creative_material_mode</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Split test</td>
    <td>Supported <br><br>VSA ad groups with product source as catalog can be used in <a href="https://business-api.tiktok.com/portal/docs?id=1742666471475201" target="_blank" >/split_test/create/</a> to create split tests.</td>
    <td>/</td>
    <td>/</td>
  </tr>
</tbody>
</table>

### Example
Depending on your preferences for optimization location and optimization goal, you can create a Video Shopping ad group with products from catalogs using any of the following code examples.

#### Optimization location as App (with retargeting disabled)

##### Optimization goal as Click
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "operating_systems":["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{chedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "adgroup_app_profile_page_state": null,
        "ios14_quota_type": "UNOCCUPIED",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "bid_price": {{bid_price}},
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "excluded_audience_ids": [],
        "languages": [],
        "shopping_ads_type": "VIDEO",
        "rf_estimated_frequency": null,
        "optimization_event": "ACTIVE",
        "deep_bid_type": null,
        "interest_keyword_ids": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "modify_time": "{{modify_time}}",
        "optimization_goal": "CLICK",
        "is_hfss": false,
        "adgroup_name": "{{adgroup_nam}}",
        "creative_material_mode": "CATALOG_SALES",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "shopping_ads_retargeting_type": "OFF",
        "create_time": "{{create_time}}",
        "actions": [],
        "budget": {{budget}},
        "comment_disabled": false,
        "interest_category_ids": [],
        "scheduled_budget": 0,
        "bid_type": "BID_TYPE_CUSTOM",
        "device_price_ranges": null,
        "audience_ids": [],
        "category_exclusion_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "brand_safety_partner": null,
        "app_download_url": "{{app_download_url}}",
        "skip_learning_phase": false,
        "location_ids": [
            "6252001"
        ],
        "smart_audience_enabled": null,
        "pixel_id": null,
        "secondary_optimization_event": null,
        "category_id": "0",
        "operation_status": "ENABLE",
        "rf_purchased_type": null,
        "purchased_impression": null,
        "campaign_name": "{{campaign_name}}",
        "is_new_structure": true,
        "campaign_id": "{{campaign_id}}",
        "schedule_infos": null,
        "operating_systems": [
            "ANDROID"
        ],
        "billing_event": "CPC",
        "video_download_disabled": false,
        "adgroup_id": "{{adgroup_id}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "delivery_mode": null,
        "frequency": null,
        "app_type": "APP_ANDROID",
        "network_types": [],
        "conversion_bid_price": 0,
        "statistic_type": null,
        "app_id": "{{app_id}}",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "conversion_window": null,
        "deep_cpa_bid": 0,
        "feed_type": null,
        "keywords": null,
        "purchased_reach": null,
        "catalog_id": "{{catalog_id}}",
        "package": "{{package}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "age_groups": null,
        "auto_targeting_enabled": false,
        "frequency_schedule": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "schedule_end_time": "{{schedule_end_time}}",
        "product_source": "CATALOG",
        "promotion_type": "APP_ANDROID",
        "share_disabled": false,
        "gender": "GENDER_UNLIMITED",
        "rf_estimated_cpr": null,
        "advertiser_id": "{{advertiser_id}}",
        "next_day_retention": null,
        "inventory_filter_enabled": false,
        "smart_interest_behavior_enabled": null,
        "isp_ids": [],
        "is_smart_performance_campaign": false,
        "bid_display_mode": "CPMV",
        "search_result_enabled": false
    }
}
(/code)
```
##### Optimization goal as Install
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{dvertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "operating_systems":["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "INSTALL",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "keywords": null,
        "app_id": "{{app_id}}",
        "actions": [],
        "age_groups": null,
        "operating_systems": [
            "ANDROID"
        ],
        "adgroup_id": "{{adgroup_id}}",
        "delivery_mode": null,
        "share_disabled": false,
        "deep_cpa_bid": 0,
        "location_ids": [
            "6252001"
        ],
        "is_smart_performance_campaign": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "audience_ids": [],
        "smart_audience_enabled": null,
        "brand_safety_partner": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "excluded_audience_ids": [],
        "deep_bid_type": null,
        "budget": {{budget}},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "is_hfss": false,
        "video_download_disabled": false,
        "smart_interest_behavior_enabled": null,
        "pacing": "PACING_MODE_SMOOTH",
        "purchased_impression": null,
        "isp_ids": [],
        "category_id": "0",
        "catalog_id": "{{catalog_id}}",
        "bid_display_mode": "CPMV",
        "conversion_window": null,
        "bid_price": 0,
        "next_day_retention": null,
        "adgroup_name": "{{adgroup_name}}",
        "pixel_id": null,
        "campaign_name": "{{campaign_name}}",
        "frequency_schedule": null,
        "creative_material_mode": "CATALOG_SALES",
        "languages": [],
        "device_price_ranges": null,
        "adgroup_app_profile_page_state": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "schedule_infos": null,
        "feed_type": null,
        "conversion_bid_price": {{conversion_bid_price}}},
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "schedule_type": "SCHEDULE_START_END",
        "optimization_event": "ACTIVE",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "campaign_id": "{{campaign_id}}",
        "category_exclusion_ids": [],
        "network_types": [],
        "attribution_event_count": "ONCE",
        "statistic_type": null,
        "frequency": null,
        "shopping_ads_retargeting_type": "OFF",
        "interest_category_ids": [],
        "app_type": "APP_ANDROID",
        "schedule_end_time": "{{schedule_end_tim}}",
        "rf_estimated_frequency": null,
        "shopping_ads_type": "VIDEO",
        "promotion_type": "APP_ANDROID",
        "advertiser_id": "{{advertiser_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "auto_targeting_enabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "modify_time": "{{modify_time}}",
        "skip_learning_phase": true,
        "product_source": "CATALOG",
        "rf_estimated_cpr": null,
        "secondary_optimization_event": null,
        "package": "{{package}}",
        "scheduled_budget": 0,
        "app_download_url": "{{app_download_url}}",
        "rf_purchased_type": null,
        "is_new_structure": true,
        "click_attribution_window": "SEVEN_DAYS",
        "search_result_enabled": false,
        "purchased_reach": null,
        "gender": "GENDER_UNLIMITED",
        "inventory_filter_enabled": false,
        "create_time": "{{create_time}}",
        "optimization_goal": "INSTALL",
        "operation_status": "ENABLE",
        "ios14_quota_type": "UNOCCUPIED",
        "view_attribution_window": "ONE_DAY",
        "comment_disabled": false,
        "interest_keyword_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "billing_event": "OCPM"
    }
}
(/code)
```
##### Optimization goal as In-app event
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "operating_systems":["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "IN_APP_EVENT",
    "optimization_event": "COMPLETE_TUTORIAL",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "optimization_event": "COMPLETE_TUTORIAL",
        "click_attribution_window": "SEVEN_DAYS",
        "shopping_ads_type": "VIDEO",
        "purchased_impression": null,
        "keywords": null,
        "ios14_quota_type": "UNOCCUPIED",
        "catalog_id": "{{catalog_id}}",
        "brand_safety_partner": null,
        "gender": "GENDER_UNLIMITED",
        "interest_category_ids": [],
        "audience_ids": [],
        "operation_status": "ENABLE",
        "conversion_window": null,
        "rf_estimated_cpr": null,
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "actions": [],
        "search_result_enabled": false,
        "is_new_structure": true,
        "languages": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "schedule_start_time": "{{schedule_start_time}}",
        "operating_systems": [
            "ANDROID"
        ],
        "package": "{{package}}",
        "rf_purchased_type": null,
        "inventory_filter_enabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_hfss": false,
        "purchased_reach": null,
        "attribution_event_count": "ONCE",
        "smart_audience_enabled": null,
        "skip_learning_phase": true,
        "pacing": "PACING_MODE_SMOOTH",
        "create_time": "{{create_time}}",
        "product_source": "CATALOG",
        "rf_estimated_frequency": null,
        "auto_targeting_enabled": false,
        "category_id": "0",
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "video_download_disabled": false,
        "modify_time": "{{modify_time}}",
        "shopping_ads_retargeting_type": "OFF",
        "next_day_retention": null,
        "campaign_name": "{{campaign_name}}",
        "bid_price": 0,
        "promotion_type": "APP_ANDROID",
        "location_ids": [
            "6252001"
        ],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "interest_keyword_ids": [],
        "creative_material_mode": "CATALOG_SALES",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "network_types": [],
        "bid_display_mode": "CPMV",
        "device_price_ranges": null,
        "campaign_id": "{{campaign_id}}",
        "is_smart_performance_campaign": false,
        "adgroup_id": "{{adgroup_id}}",
        "feed_type": null,
        "adgroup_name": "{{adgroup_name}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "optimization_goal": "IN_APP_EVENT",
        "age_groups": null,
        "scheduled_budget": 0,
        "statistic_type": null,
        "adgroup_app_profile_page_state": null,
        "delivery_mode": null,
        "view_attribution_window": "ONE_DAY",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "smart_interest_behavior_enabled": null,
        "excluded_audience_ids": [],
        "isp_ids": [],
        "pixel_id": null,
        "app_id": "{{app_id}}",
        "frequency_schedule": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "billing_event": "OCPM",
        "secondary_optimization_event": null,
        "app_download_url": "{{app_download_url}}",
        "advertiser_id": "{{advertiser_id}}",
        "schedule_infos": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "budget": {{budget}},
        "brand_safety_type": "NO_BRAND_SAFETY",
        "frequency": null,
        "category_exclusion_ids": [],
        "app_type": "APP_ANDROID",
        "deep_bid_type": "AEO",
        "deep_cpa_bid": 0
    }
}
(/code)
```
##### Optimization goal as Destination visit
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "operating_systems":["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "DESTINATION_VISIT",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "deep_cpa_bid": 0,
        "interest_category_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "shopping_ads_retargeting_type": "OFF",
        "audience_ids": [],
        "network_types": [],
        "app_type": "APP_ANDROID",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "shopping_ads_type": "VIDEO",
        "create_time": "{{create_time}}",
        "search_result_enabled": false,
        "auto_targeting_enabled": false,
        "isp_ids": [],
        "frequency_schedule": null,
        "secondary_optimization_event": null,
        "bid_price": 0,
        "purchased_impression": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "optimization_event": "DESTINATION_VISIT",
        "share_disabled": false,
        "device_price_ranges": null,
        "frequency": null,
        "video_download_disabled": false,
        "schedule_type": "SCHEDULE_START_END",
        "app_download_url": "{{app_download_url}}",
        "actions": [],
        "campaign_name": "{{campaign_name}}",
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "comment_disabled": false,
        "delivery_mode": null,
        "operating_systems": [
            "ANDROID"
        ],
        "modify_time": "{{modify_time}}",
        "product_source": "CATALOG",
        "catalog_id": "{{catalog_id}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "campaign_id": "{{campaign_id}}",
        "excluded_audience_ids": [],
        "adgroup_name": "{{adgroup_name}}",
        "next_day_retention": null,
        "smart_audience_enabled": null,
        "brand_safety_partner": null,
        "adgroup_id": "{{adgroup_id}}",
        "age_groups": null,
        "inventory_filter_enabled": false,
        "purchased_reach": null,
        "deep_bid_type": null,
        "rf_purchased_type": null,
        "click_attribution_window": "SEVEN_DAYS",
        "rf_estimated_cpr": null,
        "conversion_window": null,
        "location_ids": [
            "6252001"
        ],
        "statistic_type": null,
        "keywords": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "is_hfss": false,
        "ios14_quota_type": "UNOCCUPIED",
        "is_smart_performance_campaign": false,
        "is_new_structure": true,
        "optimization_goal": "DESTINATION_VISIT",
        "rf_estimated_frequency": null,
        "budget": {{budget}},
        "conversion_bid_price": {{conversion_bid_price}},
        "gender": "GENDER_UNLIMITED",
        "languages": [],
        "advertiser_id": "{{advertiser_id}}",
        "smart_interest_behavior_enabled": null,
        "view_attribution_window": "ONE_DAY",
        "billing_event": "OCPM",
        "interest_keyword_ids": [],
        "app_id": "{{app_id}}",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "attribution_event_count": "ONCE",
        "skip_learning_phase": true,
        "category_exclusion_ids": [],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "adgroup_app_profile_page_state": null,
        "pixel_id": null,
        "schedule_infos": null,
        "feed_type": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "scheduled_budget": 0,
        "operation_status": "ENABLE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "category_id": "0",
        "promotion_type": "APP_ANDROID",
        "package": "{{package}}",
        "creative_material_mode": "CATALOG_SALES"
    }
}
(/code)
```

##### Optimization goal as Value
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "APP_ANDROID",
    "app_id":"{{app_id}}",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "operating_systems":["ANDROID"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "optimization_event": "ACTIVE_PAY",
    "secondary_optimization_event":"PURCHASE_ROI",
    "deep_bid_type": "VO_MIN_ROAS",
    "bid_type": "BID_TYPE_NO_BID",
    "roas_bid": {{roas_bid}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "view_attribution_window": "ONE_DAY",
        "actions": [],
        "age_groups": null,
        "click_attribution_window": "SEVEN_DAYS",
        "campaign_name": "{{campaign_name}}",
        "audience_ids": [],
        "create_time": "{{create_time}}",
        "skip_learning_phase": true,
        "brand_safety_partner": null,
        "optimization_goal": "VALUE",
        "inventory_filter_enabled": false,
        "schedule_infos": null,
        "device_price_ranges": null,
        "comment_disabled": false,
        "feed_type": null,
        "modify_time": "{{modify_time}}",
        "category_id": "0",
        "interest_keyword_ids": [],
        "deep_bid_type": "VO_MIN_ROAS",
        "billing_event": "OCPM",
        "is_smart_performance_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "secondary_optimization_event": "PURCHASE_ROI",
        "budget": {{budget}},
        "adgroup_app_profile_page_state": null,
        "app_type": "APP_ANDROID",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "conversion_bid_price": 0,
        "roas_bid": {{roas_bid}},
        "scheduled_budget": 0,
        "is_new_structure": true,
        "bid_price": 0,
        "pixel_id": null,
        "rf_purchased_type": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "location_ids": [
            "6252001"
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "smart_audience_enabled": null,
        "product_source": "CATALOG",
        "bid_display_mode": "CPMV",
        "optimization_event": "ACTIVE_PAY",
        "next_day_retention": null,
        "keywords": null,
        "shopping_ads_retargeting_type": "OFF",
        "frequency_schedule": null,
        "ios14_quota_type": "UNOCCUPIED",
        "category_exclusion_ids": [],
        "deep_cpa_bid": 0,
        "catalog_id": "{{catalog_id}}",
        "conversion_window": null,
        "network_types": [],
        "purchased_impression": null,
        "creative_material_mode": "CATALOG_SALES",
        "delivery_mode": null,
        "gender": "GENDER_UNLIMITED",
        "promotion_type": "APP_ANDROID",
        "smart_interest_behavior_enabled": null,
        "app_id": "{{app_id}}",
        "rf_estimated_frequency": null,
        "excluded_audience_ids": [],
        "frequency": null,
        "is_hfss": false,
        "app_download_url": "{{app_download_url}}",
        "shopping_ads_type": "VIDEO",
        "pacing": "PACING_MODE_SMOOTH",
        "isp_ids": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "video_download_disabled": false,
        "auto_targeting_enabled": false,
        "attribution_event_count": "EVERY",
        "adgroup_name": "{{adgroup_name}}",
        "schedule_type": "SCHEDULE_START_END",
        "campaign_id": "{{campaign_id}}",
        "operating_systems": [
            "ANDROID"
        ],
        "statistic_type": null,
        "package": "{{package}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "interest_category_ids": [],
        "purchased_reach": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "adgroup_id": "{{adgroup_id}}",
        "operation_status": "ENABLE",
        "languages": [],
        "search_result_enabled": false,
        "advertiser_id": "{{advertiser_id}}",
        "share_disabled": false,
        "bid_type": "BID_TYPE_NO_BID",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "rf_estimated_cpr": null
    }
}
(/code)
```

#### Optimization location as Website (with retargeting disabled)
##### Optimization goal as Click
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "keywords": null,
        "app_id": null,
        "actions": [],
        "age_groups": null,
        "operating_systems": [],
        "adgroup_id": "{{adgroup_id}}",
        "delivery_mode": null,
        "share_disabled": false,
        "deep_cpa_bid": 0,
        "location_ids": [
            "6252001"
        ],
        "is_smart_performance_campaign": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "audience_ids": [],
        "smart_audience_enabled": null,
        "brand_safety_partner": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "excluded_audience_ids": [],
        "deep_bid_type": null,
        "budget": {{budget}},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "is_hfss": false,
        "video_download_disabled": false,
        "smart_interest_behavior_enabled": null,
        "pacing": "PACING_MODE_SMOOTH",
        "purchased_impression": null,
        "isp_ids": [],
        "category_id": "0",
        "catalog_id": "{{catalog_id}}",
        "bid_display_mode": "CPMV",
        "conversion_window": null,
        "bid_price": {{bid_price}},
        "next_day_retention": null,
        "adgroup_name": "{{adgroup_name}}",
        "pixel_id": null,
        "campaign_name": "{{campaign_name}}",
        "frequency_schedule": null,
        "creative_material_mode": "CATALOG_SALES",
        "languages": [],
        "device_price_ranges": null,
        "adgroup_app_profile_page_state": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "schedule_infos": null,
        "feed_type": null,
        "conversion_bid_price": 0,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "schedule_type": "SCHEDULE_START_END",
        "optimization_event": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "campaign_id": "{{campaign_id}}",
        "category_exclusion_ids": [],
        "network_types": [],
        "statistic_type": null,
        "frequency": null,
        "shopping_ads_retargeting_type": "OFF",
        "interest_category_ids": [],
        "app_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "rf_estimated_frequency": null,
        "shopping_ads_type": "VIDEO",
        "promotion_type": "WEBSITE",
        "advertiser_id": "{{advertiser_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "auto_targeting_enabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "modify_time": "{{modify_time}}",
        "skip_learning_phase": false,
        "product_source": "CATALOG",
        "rf_estimated_cpr": null,
        "secondary_optimization_event": null,
        "scheduled_budget": 0,
        "app_download_url": null,
        "rf_purchased_type": null,
        "is_new_structure": true,
        "search_result_enabled": false,
        "purchased_reach": null,
        "gender": "GENDER_UNLIMITED",
        "inventory_filter_enabled": false,
        "create_time": "{{create_time}}",
        "optimization_goal": "CLICK",
        "operation_status": "ENABLE",
        "ios14_quota_type": "UNOCCUPIED",
        "comment_disabled": false,
        "interest_keyword_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "billing_event": "CPC"
    }
}
(/code)
```

##### Optimization goal as Conversion
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"ADD_BILLING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK

{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "scheduled_budget": 0,
        "delivery_mode": null,
        "conversion_window": null,
        "audience_ids": [],
        "device_price_ranges": null,
        "excluded_audience_ids": [],
        "catalog_id": "{{catalog_id}}",
        "network_types": [],
        "actions": [],
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "brand_safety_partner": null,
        "isp_ids": [],
        "product_source": "CATALOG",
        "optimization_event": "ADD_BILLING",
        "app_type": null,
        "feed_type": null,
        "shopping_ads_retargeting_type": "OFF",
        "shopping_ads_type": "VIDEO",
        "gender": "GENDER_UNLIMITED",
        "app_id": null,
        "category_id": "0",
        "interest_category_ids": [],
        "optimization_goal": "CONVERT",
        "budget": {{budget}},
        "purchased_reach": null,
        "location_ids": [
            "6252001"
        ],
        "deep_bid_type": null,
        "click_attribution_window": "SEVEN_DAYS",
        "video_download_disabled": false,
        "ios14_quota_type": "UNOCCUPIED",
        "conversion_bid_price": {{conversion_bid_price}},
        "adgroup_id": "{{adgroup_id}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "promotion_type": "WEBSITE",
        "frequency": null,
        "is_smart_performance_campaign": false,
        "pixel_id": "{{pixel_id}}",
        "share_disabled": false,
        "schedule_type": "SCHEDULE_START_END",
        "rf_estimated_cpr": null,
        "smart_audience_enabled": null,
        "bid_price": 0,
        "schedule_infos": null,
        "operating_systems": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_purchased_type": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "operation_status": "ENABLE",
        "schedule_end_time": "{{schedule_end_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "create_time": "{{create_time}}",
        "campaign_name": "{{campaign_name}}",
        "purchased_impression": null,
        "advertiser_id": "{{advertiser_id}}",
        "keywords": null,
        "comment_disabled": false,
        "auto_targeting_enabled": false,
        "adgroup_name": "{{adgroup_name}}",
        "secondary_optimization_event": null,
        "app_download_url": null,
        "is_hfss": false,
        "statistic_type": null,
        "rf_estimated_frequency": null,
        "category_exclusion_ids": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "campaign_id": "{{campaign_id}}",
        "billing_event": "OCPM",
        "interest_keyword_ids": [],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "pacing": "PACING_MODE_SMOOTH",
        "inventory_filter_enabled": false,
        "adgroup_app_profile_page_state": null,
        "view_attribution_window": "ONE_DAY",
        "schedule_start_time": "{{schedule_start_time}}",
        "is_new_structure": true,
        "bid_display_mode": "CPMV",
        "deep_cpa_bid": 0,
        "frequency_schedule": null,
        "smart_interest_behavior_enabled": null,
        "attribution_event_count": "EVERY",
        "next_day_retention": null,
        "age_groups": null,
        "languages": [],
        "creative_material_mode": "CATALOG_SALES",
        "skip_learning_phase": true,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "search_result_enabled": false,
        "modify_time": "{{modify_time}}"
    }
}
(/code)
```
##### Optimization goal as Landing page view
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
    "optimization_event": "LANDING_PAGE_VIEW",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "schedule_type": "SCHEDULE_START_END",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "pacing": "PACING_MODE_SMOOTH",
        "comment_disabled": false,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_id": "{{campaign_id}}",
        "shopping_ads_retargeting_type": "OFF",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "audience_ids": [],
        "scheduled_budget": 0,
        "secondary_optimization_event": null,
        "billing_event": "OCPM",
        "device_price_ranges": null,
        "view_attribution_window": "ONE_DAY",
        "skip_learning_phase": true,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "search_result_enabled": false,
        "is_smart_performance_campaign": false,
        "optimization_event": "LANDING_PAGE_VIEW",
        "modify_time": "{{modify_time}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "promotion_type": "WEBSITE",
        "purchased_reach": null,
        "rf_estimated_cpr": null,
        "deep_bid_type": null,
        "budget": {{budget}},
        "adgroup_name": "{{adgroup_name}}",
        "app_download_url": null,
        "smart_audience_enabled": null,
        "deep_cpa_bid": 0,
        "frequency": null,
        "location_ids": [
            "6252001"
        ],
        "excluded_audience_ids": [],
        "bid_display_mode": "CPMV",
        "purchased_impression": null,
        "shopping_ads_type": "VIDEO",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "attribution_event_count": "ONCE",
        "interest_category_ids": [],
        "gender": "GENDER_UNLIMITED",
        "conversion_bid_price": {{conversion_bid_price}},
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_name": "{{campaign_name}}",
        "actions": [],
        "conversion_window": null,
        "rf_estimated_frequency": null,
        "brand_safety_partner": null,
        "advertiser_id": "{{advertiser_id}}",
        "statistic_type": null,
        "adgroup_id": "{{adgroup_id}}",
        "interest_keyword_ids": [],
        "feed_type": null,
        "click_attribution_window": "SEVEN_DAYS",
        "catalog_id": "{{catalog_id}}",
        "smart_interest_behavior_enabled": null,
        "product_source": "CATALOG",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
        "frequency_schedule": null,
        "languages": [],
        "category_id": "0",
        "app_type": null,
        "is_hfss": false,
        "age_groups": null,
        "operation_status": "ENABLE",
        "keywords": null,
        "bid_price": 0,
        "is_new_structure": true,
        "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
        "adgroup_app_profile_page_state": null,
        "auto_targeting_enabled": false,
        "category_exclusion_ids": [],
        "isp_ids": [],
        "network_types": [],
        "bid_type": "BID_TYPE_CUSTOM",
        "operating_systems": [],
        "delivery_mode": null,
        "schedule_infos": null,
        "creative_material_mode": "CATALOG_SALES",
        "create_time": "{{create_time}}",
        "inventory_filter_enabled": false,
        "video_download_disabled": false,
        "next_day_retention": null,
        "ios14_quota_type": "UNOCCUPIED",
        "rf_purchased_type": null,
        "pixel_id": null,
        "app_id": null
    }
}
(/code)
```
##### Optimization goal as Value
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "pixel_id":"{{pixel_id}}",
    "optimization_event":"SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"OFF",
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "deep_bid_type": "VO_MIN_ROAS",
    "bid_type": "BID_TYPE_NO_BID",
    "roas_bid": {{roas_bid}},
    "billing_event": "OCPM",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "shopping_ads_type": "VIDEO",
        "network_types": [],
        "is_smart_performance_campaign": false,
        "interest_category_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "schedule_infos": null,
        "app_id": null,
        "age_groups": null,
        "app_download_url": null,
        "actions": [],
        "secondary_optimization_event": null,
        "optimization_goal": "VALUE",
        "promotion_type": "WEBSITE",
        "isp_ids": [],
        "skip_learning_phase": true,
        "device_price_ranges": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "interest_keyword_ids": [],
        "keywords": null,
        "app_type": null,
        "campaign_id": "{{campaign_id}}",
        "shopping_ads_retargeting_type": "OFF",
        "comment_disabled": false,
        "product_source": "CATALOG",
        "catalog_id": "{{catalog_id}}",
        "inventory_filter_enabled": false,
        "campaign_name": "{{campaign_name}}",
        "conversion_bid_price": 0,
        "auto_targeting_enabled": false,
        "rf_estimated_cpr": null,
        "adgroup_id": "{{adgroup_id}}",
        "is_hfss": false,
        "frequency": null,
        "deep_bid_type": "VO_MIN_ROAS",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "roas_bid": {{roas_bid}},
        "budget_mode": "BUDGET_MODE_TOTAL",
        "optimization_event": "SHOPPING",
        "delivery_mode": null,
        "modify_time": "{{modify_time}}",
        "languages": [],
        "video_download_disabled": false,
        "conversion_window": null,
        "excluded_audience_ids": [],
        "gender": "GENDER_UNLIMITED",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "bid_price": 0,
        "category_exclusion_ids": [],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "is_new_structure": true,
        "pixel_id": "{{pixel_id}}",
        "billing_event": "OCPM",
        "bid_type": "BID_TYPE_NO_BID",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "rf_estimated_frequency": null,
        "audience_ids": [],
        "frequency_schedule": null,
        "statistic_type": null,
        "scheduled_budget": 0,
        "operating_systems": [],
        "view_attribution_window": "ONE_DAY",
        "smart_audience_enabled": null,
        "adgroup_app_profile_page_state": null,
        "advertiser_id": "{{advertiser_id}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "smart_interest_behavior_enabled": null,
        "share_disabled": false,
        "attribution_event_count": "EVERY",
        "creative_material_mode": "CATALOG_SALES",
        "category_id": "0",
        "feed_type": null,
        "brand_safety_partner": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "rf_purchased_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "operation_status": "ENABLE",
        "adgroup_name": "{{adgroup_name}",
        "bid_display_mode": "CPMV",
        "ios14_quota_type": "UNOCCUPIED",
        "purchased_reach": null,
        "next_day_retention": null,
        "search_result_enabled": false,
        "purchased_impression": null,
        "deep_cpa_bid": 0,
        "budget": {{budget}},
        "click_attribution_window": "SEVEN_DAYS",
        "create_time": "{{create_time}}",
        "location_ids": [
            "6252001"
        ]
    }
}
(/code)
```

#### Retargeting audience (with Optimization location as Website and Optimization goal as Click)
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "VIDEO",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "promotion_type": "WEBSITE",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "location_ids": ["6252001"],
    "shopping_ads_retargeting_type":"LAB1",
    "shopping_ads_retargeting_actions_days":180,
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",
    "pacing": "PACING_MODE_SMOOTH"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "is_smart_performance_campaign": false,
        "conversion_window": null,
        "auto_targeting_enabled": false,
        "network_types": [],
        "audience_ids": [],
        "category_id": "0",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "skip_learning_phase": false,
        "advertiser_id": "{{advertiser_id}}",
        "catalog_id": "{{catalog_id}}",
        "create_time": "{{create_time}}",
        "operation_status": "ENABLE",
        "deep_bid_type": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "excluded_custom_actions": [
            {
                "code": "PURCHASE",
                "days": 180
            }
        ],
        "smart_audience_enabled": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "device_price_ranges": null,
        "inventory_filter_enabled": false,
        "conversion_bid_price": 0,
        "secondary_optimization_event": null,
        "adgroup_name": "{{}}",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "smart_interest_behavior_enabled": null,
        "is_new_structure": true,
        "video_download_disabled": false,
        "actions": [],
        "age_groups": null,
        "adgroup_id": "{{adgroup_id}}",
        "shopping_ads_type": "VIDEO",
        "location_ids": [
            "6252001"
        ],
        "ios14_quota_type": "UNOCCUPIED",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "schedule_end_time": "{{schedule_end_time}}",
        "bid_price": {{bid_price}},
        "schedule_type": "SCHEDULE_START_END",
        "catalog_authorized_bc_id": "{{atalog_authorized_bc_id}}",
        "search_result_enabled": false,
        "deep_cpa_bid": 0,
        "schedule_infos": null,
        "delivery_mode": null,
        "product_source": "CATALOG",
        "rf_purchased_type": null,
        "creative_material_mode": "CUSTOM",
        "gender": "GENDER_UNLIMITED",
        "pacing": "PACING_MODE_SMOOTH",
        "languages": [],
        "app_download_url": null,
        "app_id": null,
        "excluded_audience_ids": [],
        "adgroup_app_profile_page_state": null,
        "included_custom_actions": [
            {
                "code": "VIEW_PRODUCT",
                "days": 180
            },
            {
                "code": "ADD_TO_CART",
                "days": 180
            }
        ],
        "next_day_retention": null,
        "purchased_reach": null,
        "frequency": null,
        "statistic_type": null,
        "campaign_name": "{{campaign_name}}",
        "scheduled_budget": 0,
        "comment_disabled": false,
        "is_hfss": false,
        "billing_event": "CPC",
        "interest_category_ids": [],
        "brand_safety_partner": null,
        "isp_ids": [],
        "share_disabled": false,
        "promotion_type": "WEBSITE",
        "shopping_ads_retargeting_type": "LAB1",
        "category_exclusion_ids": [],
        "rf_estimated_frequency": null,
        "optimization_goal": "CLICK",
        "pixel_id": null,
        "keywords": null,
        "operating_systems": [],
        "rf_estimated_cpr": null,
        "bid_display_mode": "CPMV",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "budget": {{budget}},
        "frequency_schedule": null,
        "interest_keyword_ids": [],
        "campaign_id": "{{campaign_id}}",
        "app_type": null,
        "purchased_impression": null,
        "feed_type": null,
        "optimization_event": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "modify_time": "{{modify_time}}"
    }
}
(/code)
```

## 4. Create an ad
Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). Note that the following requirements must be met according to the ad format that you select. 

### Ad format as Dynamic format
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
<th style="width:30%">How to configure the parameters</th>
</tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank" >/ad/aco/create/</a> to create Smart Creative VSA ads.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Custom User (custom identity) </td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank" >/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options: <ul><li><strong>All products</strong></li><li><strong>Product set</strong></li><li><strong>Specific products</strong> with a maximum of 20 products </li></ul></td>
    <td><ul><li><code>catalog_id</code></li><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code>product_set_id</code></li><li><code>sku_ids</code></li></ul></td>
<td><ul><li> Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li> If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>sku_ids</code> , <code>item_group_ids</code> , and <code>product_set_id</code>.</li><li> If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass either <code>item_group_ids</code> or <code>product_set_id</code>. <code>sku_ids</code> is not needed.</li><li> If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>sku_ids</code>. <code>item_group_ids</code> and <code>product_set_id</code> are not needed.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</td>
  </tr>
  <tr>
    <td rowspan="3">Ad details - Dynamic format<br></td>
    <td rowspan="3"><strong>Enabled</strong>: To display different videos and destinations to each person. TikTok automatically generates and combines specific ad formats, including add-ons and landing pages, for each person. This is recommended to optimize the user journey and ad conversion.<br>You need to have both generic and catalog videos to use this format. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you have selected Retarget audience at the ad group level, you cannot enable Dynamic format. </td>
    <td><code>dynamic_format</code></td>
    <td><code>DYNAMIC_CREATIVE</code><br><br><p><span style="color:darkred"><b>Note</b></span>: If <code>shopping_ads_retargeting_type</code> is set to <code>LAB1</code>, <code>LAB2</code>, or <code>LAB3</code> at the ad group level, you cannot set this field to <code>DYNAMIC_CREATIVE</code>.</td>
  </tr>
  <tr>
    <td><code>ad_format</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
  <tr>
    <td><code>vertical_video_strategy</code></td>
    <td><code>UNSET</code> or not passed</td>
  </tr>
  <tr>
    <td rowspan="4">Ad creative</td>
    <td rowspan="4">Specify the following simultaneously: <ul><li> A generic video with its cover image </li><li> A catalog video template option: <ul><li> If you select a video template that is bound to the catalog, the video template will be used to automatically generate the dynamic catalog video for each catalog product.</li><li> If you don't select the video template that is bound to the catalog, the system will automatically select one of your video templates for each catalog product to generate the corresponding dynamic catalog video. When a product has a video URL, that video will be used instead.</li></ul> </li></ul></td>
    <td><ul><li><code>video_id</code></li><li><code>image_ids</code></li></ul></td>
    <td>Specify the generic video through <code>video_id</code> and the cover image through <code>image_ids</code>.</td>
  </tr>
  <tr>
    <td><code>shopping_ads_video_package_id</code></td>
    <td>Specified or not passed. <ul><li> If this field is specified, the specified video template will be used to automatically generate the catalog video for each catalog product. </li><li> If this field is not specified, the system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video. When a product has a video URL, that video will be used instead.</li></ul><br>To obtain the IDs of video templates (video packages) bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank" >/catalog/video_package/get/</a>.<br>To learn about how to create video packages on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog?lang=en" target="_blank">How to create video packages in a Catalog</a>.<br></td>
  </tr>
  <tr>
    <td><ul><li><code>tiktok_item_id</code></li><li><code>dark_post_status</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>music_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad text</td>
    <td>Specified</td>
    <td><code>ad_text</code></td>
    <td>Pass a valid value</td>
  </tr>
  <tr>
    <td>Interactive add-on </td>
    <td>Disabled</td>
    <td><code>card_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="4">Destination</td>
    <td rowspan="4"><ul><li> If Optimization location is Website and Optimization goal is Click or Landing Page View, the Destination must be Website. Dynamic destination is not supported.</li><li> If Optimization location is Website and Optimization goal is Conversion or Value, the destination must be Dynamic destination.<ul><li> When Dynamic Destination is enabled, your audience will be directed to your landing page or an automatically created TikTok Instant Page, depending on what is most likely to generate the best results for your business.</li></ul></li><li> If Optimization location is App, the destination must be App. Dynamic destination is not supported.</li></ul> <br>Regardless of whether Optimization location is Website or App, Instant Product Page should be disabled. </td>
    <td><ul><li><code> instant_product_page_used </code></li><li><code> page_image_index </code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><ul><li><code>landing_page_url</code></li><li><code>utm_params</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>deeplink_utm_params</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li></ul></td>
    <td>Configure these parameters according to your business needs. <ul><li> Note that the available deeplink settings may vary based on the optimization goal. To learn more about deeplinks, see <a href="https://business-api.tiktok.com/portal/docs?id=1779541971843073" target="_blank" >Deeplink</a>.</li><li> To learn about the supported destination and deeplink settings for the Click goal, see <a href="https://business-api.tiktok.com/portal/docs?id=1774519076905985" target="_blank" >Configure destination and deeplink settings for website-promoting Video Shopping Ads</a>.</li></ul></td>
  </tr>
  <tr>
    <td><code>dynamic_destination</code></td>
    <td><ul><li> If <code>promotion_type</code> is <code>WEBSITE</code> and <code>optimization_goal</code> is <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>, omit this field or set this field to <code>UNSET</code>.</li><li> If <code>promotion_type</code> is <code>WEBSITE</code> and <code>optimization_goal</code> is <code>CONVERT</code> or <code>VALUE</code>, set this field to <code>DLP</code>.</li><li> If <code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>, omit this field or set this field to <code>UNSET</code>. </li></ul></td>
  </tr>
  <tr>
    <td><ul><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code</code></li><li><code>phone_region_calling_code</code></li><li><code>phone_number</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Specified</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value.<br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank" >Enumerations - Call-to-action</a>.</td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disabled</td>
    <td> <ul><li><code>disclaimer_type</code></li><li><code>disclaimer_text</code></li><li><code>disclaimer_clickable_texts</code></li></ul>
</td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Playable</td>
    <td>Disabled</td>
    <td><code>playable_url</code></td>
    <td>Not passed</td>
  </tr>
</tbody>
</table>

#### Example

##### Destination as App with dynamic destination disabled and specified video template
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"DYNAMIC_CREATIVE",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "UNSET",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "shopping_ads_video_package_id":"{{shopping_ads_video_package_id}}",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "identity_type": "CUSTOMIZED_USER",
                "image_ids": [
                    "{{image_id}}"
                ],
                "viewability_postbid_partner": "UNSET",
                "modify_time": "{{modify_time}}",
                "creative_authorized": false,
                "shopping_ads_fallback_type": "DEFAULT",
                "ad_name": "{{ad_name}}",
                "click_tracking_url": "{{click_tracking_url}}",
                "ad_id": "{{ad_id}}",
                "dynamic_destination": "UNSET",
                "optimization_event": "ACTIVE",
                "tracking_app_id": "{{tracking_app_id}}",
                "call_to_action_id": null,
                "vertical_video_strategy": "UNSET",
                "impression_tracking_url": "{{impression_tracking_url}}",
                "app_name": "{{app_name}}",
                "shopping_ads_deeplink_type": "NONE",
                "card_id": "{{card_id}}",
                "is_aco": false,
                "adgroup_id": "{{adgroup_id}}",
                "campaign_id": "{{campaign_id}}",
                "ad_text": "{{ad_text}}",
                "tracking_pixel_id": 0,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "dynamic_format": "DYNAMIC_CREATIVE",
                "brand_safety_vast_url": null,
                "call_to_action": "LEARN_MORE",
                "deeplink_type": "NORMAL",
                "profile_image_url": "{{profile_image_url}}",
                "adgroup_name": "{{adgroup_name}}",
                "ad_format": "SINGLE_VIDEO",
                "display_name": "",
                "product_specific_type": "ALL",
                "operation_status": "ENABLE",
                "catalog_id": "{{catalog_id}}",
                "page_id": null,
                "creative_type": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "viewability_vast_url": null,
                "landing_page_urls": null,
                "shopping_ads_video_package_id": "{{shopping_ads_video_package_id}}",
                "identity_id": "{{identity_id}}",
                "music_id": null,
                "is_new_structure": true,
                "create_time": "{{create_time}}",
                "vast_moat_enabled": false,
                "deeplink": "",
                "landing_page_url": "",
                "ad_texts": null,
                "campaign_name": "{{campaign_name}}",
                "playable_url": "",
                "video_id": "{{video_id}}",
                "advertiser_id": "{{}advertiser_id}"
            }
        ]
    }
}
(/code)
```

##### Destination as Dynamic destination with specified video template under Conversion goal
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"DYNAMIC_CREATIVE",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "UNSET",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "shopping_ads_video_package_id":"{{shopping_ads_video_package_id}}",
            "ad_text": "{{ad_text}}",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "dynamic_destination":"DLP",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "shopping_ads_fallback_type": "CUSTOM",
                "shopping_ads_video_package_id": "{{shopping_ads_video_package_id}}",
                "creative_authorized": false,
                "ad_id": "{{ad_id}}",
                "playable_url": "",
                "shopping_ads_deeplink_type": "NONE",
                "page_id": {{page_id}},
                "is_aco": false,
                "advertiser_id": "{{advertiser_id}}",
                "video_id": "{{video_id}}",
                "vertical_video_strategy": "UNSET",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "deeplink": null,
                "product_specific_type": "ALL",
                "ad_texts": null,
                "click_tracking_url": null,
                "ad_name": "{{ad_name}}",
                "music_id": null,
                "optimization_event": "ADD_BILLING",
                "modify_time": "{{modify_time}}",
                "viewability_postbid_partner": "UNSET",
                "viewability_vast_url": null,
                "campaign_id": "{{campaign_id}}",
                "landing_page_urls": null,
                "landing_page_url": "{{landing_page_url}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "app_name": "",
                "call_to_action": "LEARN_MORE",
                "dynamic_format": "DYNAMIC_CREATIVE",
                "adgroup_id": "{{adgroup_id}}",
                "identity_id": "{{identity_id}}",
                "dynamic_destination": "DLP",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "vast_moat_enabled": false,
                "impression_tracking_url": null,
                "ad_text": "{{ad_text}}",
                "operation_status": "ENABLE",
                "is_new_structure": true,
                "ad_format": "SINGLE_VIDEO",
                "catalog_id": "{{catalog_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "card_id": "{{card_id}}",
                "create_time": "{{create_time}}",
                "profile_image_url": "{{profile_image_url}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "call_to_action_id": null,
                "creative_type": "SHOPPING_CATALOG_PAGE",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "brand_safety_vast_url": null,
                "campaign_name": "{{campaign_name}}",
                "adgroup_name": "{{adgroup_name}}"
            }
        ]
    }
}
(/code)
```

##### Destination as Website with dynamic destination disabled and no specified video template under Click goal
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"DYNAMIC_CREATIVE",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "UNSET",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK

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
                "brand_safety_vast_url": null,
                "product_specific_type": "ALL",
                "campaign_id": "{{campaign_id}}",
                "dynamic_destination": "UNSET",
                "shopping_ads_deeplink_type": "NONE",
                "landing_page_urls": null,
                "tracking_pixel_id": 0,
                "shopping_ads_video_package_id": "",
                "is_aco": false,
                "click_tracking_url": null,
                "music_id": null,
                "image_ids": [
                    "{{image_id}}"
                ],
                "dynamic_format": "DYNAMIC_CREATIVE",
                "ad_name": "{{ad_name}}",
                "optimization_event": null,
                "playable_url": "",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "landing_page_url": "{{landing_page_url}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "campaign_name": "{{campaign_name}}",
                "app_name": "",
                "call_to_action_id": null,
                "page_id": null,
                "profile_image_url": "{{profile_image_url}}",
                "card_id": "{{card_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "vast_moat_enabled": false,
                "ad_text": "{{ad_text}}",
                "modify_time": "{{modify_time}}",
                "display_name": "{{display_name}}",
                "ad_id": "{{ad_id}}",
                "catalog_id": "{{catalog_id}}",
                "is_new_structure": true,
                "viewability_vast_url": null,
                "deeplink": "",
                "create_time": "{{create_time}}",
                "creative_authorized": false,
                "shopping_ads_fallback_type": "CUSTOM",
                "ad_texts": null,
                "vertical_video_strategy": "UNSET",
                "creative_type": null,
                "operation_status": "ENABLE",
                "identity_id": "{{identity_id}}",
                "ad_format": "SINGLE_VIDEO",
                "adgroup_name": "{{adgroup_name}}",
                "viewability_postbid_partner": "UNSET",
                "identity_type": "CUSTOMIZED_USER",
                "video_id": "{{video_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "deeplink_type": "NORMAL",
                "call_to_action": "LEARN_MORE",
                "impression_tracking_url": null
            }
        ]
    }
}
(/code)
```

##### Destination as Website with dynamic destination disabled and specified video template under Click goal
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"DYNAMIC_CREATIVE",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "UNSET",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "shopping_ads_video_package_id":"{{shopping_ads_video_package_id}}",
            "ad_text": "{{ad_text}}",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "click_tracking_url": null,
                "app_name": "",
                "catalog_id": "{{catalog_id}}",
                "vast_moat_enabled": false,
                "tracking_pixel_id": 0,
                "music_id": null,
                "create_time": "{{create_time}}",
                "product_specific_type": "ALL",
                "landing_page_url": "{{landing_page_url}}",
                "identity_type": "CUSTOMIZED_USER",
                "page_id": null,
                "card_id": "{{card_id}}",
                "impression_tracking_url": null,
                "landing_page_urls": null,
                "ad_text": "{{ad_text}}",
                "creative_authorized": false,
                "deeplink_type": "NORMAL",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "playable_url": "",
                "is_new_structure": true,
                "campaign_id": "{{campaign_id}}",
                "display_name": "{{display_name}}",
                "ad_format": "SINGLE_VIDEO",
                "ad_name": "{{ad_name}}",
                "dynamic_format": "DYNAMIC_CREATIVE",
                "operation_status": "ENABLE",
                "is_aco": false,
                "adgroup_name": "{{adgroup_name}}",
                "modify_time": "{{modify_time}}",
                "shopping_ads_fallback_type": "CUSTOM",
                "call_to_action_id": null,
                "shopping_ads_video_package_id": "{{shopping_ads_video_package_id}}",
                "deeplink": "",
                "optimization_event": null,
                "profile_image_url": "{{profile_image_url}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "brand_safety_vast_url": null,
                "dynamic_destination": "UNSET",
                "campaign_name": "{{campaign_name}}",
                "video_id": "{{video_id}}",
                "ad_id": "{{ad_id}}",
                "ad_texts": null,
                "creative_type": null,
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "identity_id": "{{identity_id}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "vertical_video_strategy": "UNSET",
                "call_to_action": "LEARN_MORE",
                "shopping_ads_deeplink_type": "NONE",
                "viewability_vast_url": null
            }
        ]
    }
}
(/code)
```

### Ad format as Single Video
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
<th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank" >/ad/aco/create/</a> to create Smart Creative VSA ads.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Any of the following options: <ul><li> Custom User (custom identity)</li><li> Authorized User </li><li> TikTok User </li><li> TikTok Account User in Business Center </li></ul></td>
    <td><code>identity_type</code></td>
    <td>Any of the following values: <ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank" >/identity/get/</a>.</td>
  </tr>
  <tr>
  	<td><code>identity_authorized_bc_id</code></td>
    <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options: <ul><li><strong>All products</strong></li><li><strong>Product set</strong></li><li><strong>Specific products</strong> with a maximum of 20 products </li></ul></td>
    <td><ul><li><code>catalog_id</code></li><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code>product_set_id</code></li><li><code>sku_ids</code></li></ul></td>
<td><ul><li> Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li> If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>sku_ids</code> , <code>item_group_ids</code> , and <code>product_set_id</code>.</li><li> If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass either <code>item_group_ids</code> or <code>product_set_id</code>. <code>sku_ids</code> is not needed.</li><li> If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>sku_ids</code>. <code>item_group_ids</code> and <code>product_set_id</code> are not needed.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</tr></td>
  <tr>
    <td>Ad details - Dynamic format</td>
    <td>Disabled</td>
    <td><code>dynamic_format</code></td>
    <td><code>UNSET</code> or not passed<br></td>
  </tr>
  <tr>
    <td rowspan="2">Ad format</td>
    <td rowspan="2"><strong>Single video</strong>: Use a video creative to promote your products.</td>
    <td><code>ad_format</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
  <tr>
    <td><code>vertical_video_strategy</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
    <tr>
    <td rowspan="3">Ad creative</td>
    <td rowspan="3"><ul><li> For Custom User identity or Spark Ads PUSH with TikTok User identity, specify a video and a cover image.</li><li> For TikTok Account User in Business Center identity, Authorized User identity, or Spark Ads PULL with TikTok User identity, specify a TikTok post.</li></ul> </td>
    <td><ul><li><code>video_id</code></li><li><code>image_ids</code></li><li><code>ad_text</code></li><li><code>tiktok_item_id</code></li><li><code>dark_post_status</code></li></ul></td>
<td><ul><li> If <code>identity_type</code> is <code>CUSTOMIZED_USER</code>, specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>).  Do not pass <code>tiktok_item_id</code>.</li><li> If <code>identity_type</code> is <code>TT_USER</code>, you can create the ad through Spark Ads PULL or Spark Ads PUSH:<ul><li> Spark Ads PUSH: Specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>) . Do not pass <code>tiktok_item_id</code>. You can set <code>dark_post_status</code> to <code>ON</code> to ensure the video only shows as ad and does not organically appear on the TikTok Account. </li><li> Spark Ads PULL: Specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, <code>ad_text</code>, and <code>dark_post_status</code>.</li></ul> To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank" >Create Spark Ads</a>.</li><li> If <code>identity_type</code> is <code>AUTH_CODE</code> or <code>BC_AUTH_TT</code>, specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, and <code>ad_text</code>.</li></ul></td>
  </tr>
  <tr>
    <td><code>shopping_ads_video_package_id</code> </td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>music_id</code> </td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Interactive add-on (Optional)</td>
    <td>Disabled or enabled with any of the following types: <ul><li> Countdown Sticker </li><li> Gift Code Sticker </li><li> Display Card </li><li> Product Card </li><li> Product Tiles (supported with allowlist)</li></ul></td>
    <td><code>card_id</code></td>
    <td>Not passed or specify the ID of one of the following creative portfolio types: <ul><li> Countdown Sticker </li><li> Gift Code Sticker </li><li> Display Card </li><li> Product Card </li><li> Product Tiles</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank"  >/creative/portfolio/create/</a>.<ul><li> To learn about how to get the ID of a Display Card, Product Card, or Product Tiles portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank"  >Cards</a>.</li><li> To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank"  >Stickers</a>.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td rowspan="3">Destination</td>
<td rowspan="3"><ul><li> If Optimization location is Website, select any of the following options:<ul><li> Dynamic destination <ul><li> When Dynamic Destination is enabled, your audience will be directed to your landing page or an automatically created TikTok Instant Page, depending on what is most likely to generate the best results for your business. </li></ul></li><li> Website </li><li> Instant Product Page<ul><li> With an Instant Product Page, you can lead your customers to a native TikTok landing page where you can display a range of products and increase signal collection to help improve your campaign performance.</li></ul> </li></ul> </li><li> If Optimization location is App, the destination is App.</li></ul> <br>Dynamic destination is not supported in the following scenarios: <ul><li> Optimization location is App.</li><li> Optimization location is Website and Optimization goal is Click or Landing Page View.</li></ul></td>
     <td><ul><li><code>dynamic_destination</code></li><li><code>instant_product_page_used</code></li><li><code>page_image_index</code></li></ul></td>
    <td><ul><li> If <code>promotion_type</code> is <code>WEBSITE</code>:<ul><li> To specify Destination as Dynamic destination, set <code>dynamic_destination</code> to <code>DLP</code>. Omit Instant Product Page parameters (<code>instant_product_page_used</code> and <code>page_image_index</code>).</li><li> To specify Destination as Website, omit the Dynamic destination and Instant Product Page parameters (<code>dynamic_destination</code> , <code>instant_product_page_used</code>, and <code>page_image_index</code>).</li><li> To specify Destination as Instant Product Page, set <code>instant_product_page_used</code> to <code>true</code>, and optionally pass <code>utm_params</code> and <code>page_image_index</code>. Omit the Dynamic destination parameter <code>dynamic_destination</code>.</li></ul></li><li> If <code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>, omit the Dynamic destination and Instant Product Page parameters (<code>dynamic_destination</code>, <code>instant_product_page_used</code>, and <code>page_image_index</code>).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You cannot set <code>dynamic_destination</code> to <code>DLP</code> in the following scenarios: <ul><li><code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>.</li><li><code>promotion_type</code> is <code>WEBSITE</code> and the <code>optimization_goal</code> is <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
  <tr>
    <td><ul><li><code>landing_page_url</code></li><li><code>utm_params</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>deeplink_utm_params</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li></ul></td>
    <td>Configure these parameters according to your business needs. <ul><li> Note that the available deeplink settings may vary based on the optimization goal. To learn more about deeplinks, see <a href="https://business-api.tiktok.com/portal/docs?id=1779541971843073" target="_blank" >Deeplink</a>.</li><li> To learn about the supported destination and deeplink settings for the Click goal, see <a href="https://business-api.tiktok.com/portal/docs?id=1774519076905985" target="_blank" >Configure destination and deeplink settings for website-promoting Video Shopping Ads</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Product deeplinks and Product links are not supported. Therefore, <code> shopping_ads_deeplink_type</code> and <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code>.</td>
  </tr>
  <tr>
    <td><ul><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code</code></li><li><code>phone_region_calling_code</code></li><li><code>phone_number</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Specified</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value.<br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank" >Enumerations - Call-to-action</a>.</td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disabled</td>
    <td> <ul><li><code>disclaimer_type</code></li><li><code>disclaimer_text</code></li><li><code>disclaimer_clickable_texts</code></li></ul>
</td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Playable</td>
    <td>Disabled</td>
    <td><code>playable_url</code></td>
    <td>Not passed</td>
  </tr>
</tbody>
</table>

#### Example
##### Destination as App
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "product_specific_type": "ALL",
                "ad_format": "SINGLE_VIDEO",
                "is_new_structure": true,
                "card_id": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "video_id": "{{video_id}}",
                "dynamic_format": "UNSET",
                "deeplink": "",
                "shopping_ads_deeplink_type": "NONE",
                "tracking_pixel_id": 0,
                "campaign_id": "{{campaign_id}}",
                "impression_tracking_url": "{{impression_tracking_url}}",
                "brand_safety_vast_url": null,
                "page_id": null,
                "modify_time": "{{modify_time}}",
                "ad_name": "{{ad_name}}",
                "call_to_action_id": null,
                "ad_id": "{{ad_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "operation_status": "ENABLE",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_name": "{{campaign_name}}",
                "click_tracking_url": "{{click_tracking_url}}",
                "app_name": "{{app_name}}",
                "create_time": "{{create_time}}",
                "catalog_id": "{{catalog_id}}",
                "identity_id": "{{identity_id}}",
                "landing_page_url": "",
                "tracking_app_id": "{{tracking_app_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "creative_type": null,
                "viewability_vast_url": null,
                "music_id": null,
                "ad_text": "{{ad_text}}",
                "shopping_ads_fallback_type": "DEFAULT",
                "call_to_action": "LEARN_MORE",
                "shopping_ads_video_package_id": "",
                "deeplink_type": "NORMAL",
                "playable_url": "",
                "vast_moat_enabled": false,
                "vertical_video_strategy": "SINGLE_VIDEO",
                "ad_texts": null,
                "display_name": "",
                "optimization_event": "ACTIVE",
                "viewability_postbid_partner": "UNSET",
                "adgroup_id": "{{adgroup_id}}",
                "creative_authorized": false,
                "profile_image_url": "{{profile_image_url}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "is_aco": false,
                "adgroup_name": "{{adgroup_name}}",
                "dynamic_destination": "UNSET"
            }
        ]
    }
}
(/code)
```

##### Destination as Dynamic destination
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"DLP",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "adgroup_name": "{{adgroup_name}}",
                "page_id": {{page_id}},
                "ad_id": "{{ad_id}}",
                "create_time": "{{create_time}}",
                "call_to_action": "LEARN_MORE",
                "operation_status": "ENABLE",
                "creative_authorized": false,
                "impression_tracking_url": null,
                "image_ids": [
                    "{{image_id}}"
                ],
                "catalog_id": "{{catalog_id}}",
                "ad_text": "{{ad_text}}",
                "identity_type": "CUSTOMIZED_USER",
                "ad_name": "{{ad_name}}",
                "landing_page_urls": null,
                "call_to_action_id": null,
                "deeplink": null,
                "optimization_event": "ADD_BILLING",
                "ad_texts": null,
                "creative_type": "SHOPPING_CATALOG_PAGE",
                "adgroup_id": "{{adgroup_id}}",
                "shopping_ads_fallback_type": "CUSTOM",
                "modify_time": "{{modify_time}}",
                "dynamic_format": "UNSET",
                "viewability_vast_url": null,
                "ad_format": "SINGLE_VIDEO",
                "brand_safety_vast_url": null,
                "click_tracking_url": null,
                "playable_url": "",
                "identity_id": "{{identity_id}}",
                "vast_moat_enabled": false,
                "profile_image_url": "{{profile_image_url}}",
                "music_id": null,
                "is_new_structure": true,
                "shopping_ads_deeplink_type": "NONE",
                "advertiser_id": "{{advertiser_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "shopping_ads_video_package_id": "",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "card_id": null,
                "video_id": "{{video_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "viewability_postbid_partner": "UNSET",
                "app_name": "",
                "is_aco": false,
                "product_specific_type": "ALL",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "display_name": "{{display_name}}",
                "campaign_id": "{{campaign_id}}",
                "campaign_name": "{{campaign_name}}",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "dynamic_destination": "DLP"
            }
        ]
    }
}
(/code)
```

##### Destination as Website
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "video_id": "{{video_id}}",
                "brand_safety_vast_url": null,
                "adgroup_id": "{{adgroup_id}}",
                "call_to_action_id": null,
                "deeplink_type": "NORMAL",
                "app_name": "",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "advertiser_id": "{{advertiser_id}}",
                "identity_id": "{{identity_id}}",
                "is_aco": false,
                "identity_type": "CUSTOMIZED_USER",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "playable_url": "",
                "ad_name": "{{ad_name}}",
                "ad_id": "{{ad_id}}",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "shopping_ads_fallback_type": "CUSTOM",
                "card_id": null,
                "vast_moat_enabled": false,
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "call_to_action": "LEARN_MORE",
                "creative_authorized": false,
                "display_name": "{{display_name}}",
                "viewability_vast_url": null,
                "profile_image_url": "{{profile_image_url}}",
                "deeplink": "",
                "ad_texts": null,
                "create_time": "{{create_time}}",
                "creative_type": null,
                "landing_page_url": "{{landing_page_url}}",
                "click_tracking_url": null,
                "product_specific_type": "ALL",
                "page_id": null,
                "adgroup_name": "{{adgroup_name}}",
                "ad_text": "{{ad_text}}",
                "shopping_ads_deeplink_type": "NONE",
                "campaign_name": "{{campaign_name}}",
                "shopping_ads_video_package_id": "",
                "is_new_structure": true,
                "landing_page_urls": null,
                "catalog_id": "{{catalog_id}}",
                "viewability_postbid_partner": "UNSET",
                "impression_tracking_url": null,
                "dynamic_destination": "UNSET",
                "image_ids": [
                    "{{image_id}}"
                ],
                "modify_time": "{{modify_time}}",
                "dynamic_format": "UNSET",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "music_id": null,
                "optimization_event": "ADD_BILLING",
                "campaign_id": "{{campaign_id}}",
                "ad_format": "SINGLE_VIDEO",
                "operation_status": "ENABLE"
            }
        ]
    }
}
(/code)
```

##### Destination as Instant Product Page
Request
```xcodeblock
(code curl http)

curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "SINGLE_VIDEO",
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "instant_product_page_used":true,
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'

(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "advertiser_id": "{{advertiser_id}}",
                "campaign_id": "{{campaign_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "shopping_ads_fallback_type": "CUSTOM",
                "click_tracking_url": null,
                "is_aco": false,
                "call_to_action": "LEARN_MORE",
                "identity_id": "{{identity_id}}",
                "landing_page_urls": null,
                "ad_name": "{{ad_name}}",
                "image_ids": [
                    "{{image_id}}"
                ],
                "shopping_ads_video_package_id": "",
                "ad_id": "{{ad_id}}",
                "optimization_event": "ADD_BILLING",
                "ad_texts": null,
                "ad_text": "{{ad_text}}",
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_url": "{{landing_page_url}}",
                "card_id": null,
                "shopping_ads_deeplink_type": "NONE",
                "profile_image_url": "{{profile_image_url}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "product_specific_type": "ALL",
                "adgroup_name": "{{adgroup_name}}",
                "display_name": "{{display_name}}",
                "playable_url": "",
                "call_to_action_id": null,
                "viewability_postbid_partner": "UNSET",
                "ad_format": "SINGLE_VIDEO",
                "campaign_name": "{{campaign_name}}",
                "vast_moat_enabled": false,
                "vertical_video_strategy": "SINGLE_VIDEO",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "creative_type": "SHOPPING_CATALOG_PAGE",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "dynamic_format": "UNSET",
                "page_id": {{page_id}},
                "is_new_structure": true,
                "catalog_id": "{{catalog_id}}",
                "dynamic_destination": "UNSET",
                "viewability_vast_url": null,
                "create_time": "{{create_time}}",
                "modify_time": "{{modify_time}}",
                "deeplink": "",
                "app_name": "",
                "brand_safety_vast_url": null,
                "music_id": null,
                "video_id": "{{video_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "impression_tracking_url": null,
                "creative_authorized": false,
                "operation_status": "ENABLE"
            }
        ]
    }
}
(/code)
```


### Ad format as Catalog Video
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
<th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
<td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank" >/ad/aco/create/</a> to create Smart Creative VSA ads.</td>
 <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Custom User (custom identity) </td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank" >/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
<tr>
    <td>Products</td>
    <td>Any of the following options: <ul><li><strong>All products</strong></li><li><strong>Product set</strong></li><li><strong>Specific products</strong> with a maximum of 20 products </li></ul></td>
    <td><ul><li><code>catalog_id</code></li><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code>product_set_id</code></li><li><code>sku_ids</code></li></ul></td>
<td><ul><li> Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li> If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>sku_ids</code> , <code>item_group_ids</code> , and <code>product_set_id</code>.</li><li> If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass either <code>item_group_ids</code> or <code>product_set_id</code>. <code>sku_ids</code> is not needed.</li><li> If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 products through <code>sku_ids</code>. <code>item_group_ids</code> and <code>product_set_id</code> are not needed.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</td>  
</tr>
  <tr>
    <td>Ad details - Dynamic format</td>
    <td>Disabled</td>
    <td><code>dynamic_format</code></td>
    <td><code>UNSET</code> or not passed<br></td>
  </tr>
  <tr>
    <td rowspan="2">Ad format</td>
    <td rowspan="2"><strong> Catalog video </strong>: Generate a dynamic video based on information from the product catalog. <br><br><p><span style="color:darkred"><b>Note</b></span>:  If you want to select catalog video format using uploaded videos, ensure that you have set Optimization Location as Website at the ad group level.</td>
    <td><code>ad_format</code></td>
    <td><code>SINGLE_VIDEO</code></td>
  </tr>
  <tr>
    <td><code>vertical_video_strategy</code></td>
    <td>Any of the following values:<ul><li><code>CATALOG_UPLOADED_VIDEOS</code>: catalog video using uploaded videos.<br>To learn more about how to manage uploaded catalog videos, see <a href="https://business-api.tiktok.com/portal/docs?id=1803654904260674" target="_blank">Upload catalog videos to associate with catalog products</a>.</li><li><code>CATALOG_VIDEOS</code>: catalog video using video templates.</li></ul>
<br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Creating VSA for Catalog with catalog video format using uploaded catalog videos (with <code>vertical_video_strategy</code> as <code>CATALOG_UPLOADED_VIDEOS</code>) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>To set <code>vertical_video_strategy</code> as <code>CATALOG_UPLOADED_VIDEOS</code>, you need to set <code>promotion_type</code> to <code>WEBSITE</code> at the ad group level first.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Ad creative</td>
    <td rowspan="2">A catalog video.<br>You need to ensure that at least one uploaded video or video template has been bound to your catalog or a video URL has been configured for each catalog product. <ul><li>If you select Your Videos (uploaded videos), the system will automatically select uploaded videos featuring your products. </li><li> If you select a video template, the video template will be used to automatically generate the catalog video for each catalog product. </li><li> If you don't select the video template, the system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video. When a product has a video URL, that video will be used instead.</li></ul> </td>
    <td><code>shopping_ads_video_package_id</code></td>
    <td>Specified or not passed. <ul><li> If this field is not specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_UPLOADED_VIDEOS</code>, the system will automatically select uploaded videos featuring your products.</li><li> If this field is specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_VIDEOS</code>, the specified video template will be used to automatically generate the catalog video for each catalog product. </li><li> If this field is not specified and <code>vertical_video_strategy</code> is set to <code>CATALOG_VIDEOS</code>, the system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video. When a product has a video URL, that video will be used instead.</li></ul>  <br>To obtain the IDs of video templates (video packages) bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank"  >/catalog/video_package/get/</a>.<br>To learn about how to create video packages on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/create-manage-catalogs?lang=en#anchor-19" target="_blank"  >Create Video Packages</a>.</td>
  </tr>
  <tr>
    <td><ul><li><code>video_id</code></li><li><code>image_ids</code></li><li><code>tiktok_item_id</code></li><li><code>dark_post_status</code></li><li><code>music_id</code></li></ul></td>
    <td>Not passed<br></td>
  </tr>
  <tr>
    <td>Ad text</td>
    <td>Specified</td>
    <td><code>ad_text</code></td>
    <td>Pass a valid value.</td>
  </tr>
    <tr>
    <td>Interactive add-on (Optional)</td>
    <td>Disabled or enabled with any of the following types: <ul><li> Countdown Sticker </li><li> Gift Code Sticker </li><li> Display Card </li><li> Product Card </li><li> Product Tiles (supported with allowlist)</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: The Product Tiles portfolio is supported only when you use uploaded catalog videos.</td>
    <td><code>card_id</code></td>
    <td>Not passed or specify the ID of one of the following creative portfolio types: <ul><li> Countdown Sticker </li><li> Gift Code Sticker </li><li> Display Card </li><li> Product Card </li><li> Product Tiles <ul><li>To use a Product Tiles portfolio for VSA with the catalog video format, you need to set <code>vertical_video_strategy</code> to <code>CATALOG_UPLOADED_VIDEOS</code>.</li></ul></li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank"  >/creative/portfolio/create/</a>.<ul><li> To learn about how to get the ID of a Display Card, Product Card, or Product Tiles portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank"  >Cards</a>.</li><li> To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank"  >Stickers</a>.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: Using a Product Tiles portfolio to create ads is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td rowspan="3">Destination</td>
<td rowspan="3"><ul><li> If Optimization location is Website, select any of the following options:<ul><li> Dynamic destination <ul><li>When Dynamic Destination is enabled, your audience will be directed to your landing page or an automatically created TikTok Instant Page, depending on what is most likely to generate the best results for your business. </li></ul> </li><li> Website </li><li> Instant Product Page<ul><li>With an Instant Product Page, you can lead your customers to a native TikTok landing page where you can display a range of products and increase signal collection to help improve your campaign performance.</li></ul> </li></ul> </li><li> If Optimization location is App, the destination is App.</li></ul> <br>Dynamic destination is not supported in the following scenarios: <ul><li>Optimization location is App.</li><li> Optimization location is Website and Optimization goal is Click or Landing Page View.</li></ul></td>
     <td><ul><li><code>dynamic_destination</code></li><li><code>instant_product_page_used</code></li><li><code>page_image_index</code></li></ul></td>
    <td><ul><li> If <code>promotion_type</code> is <code>WEBSITE</code>:<ul><li> To specify Destination as Dynamic destination, set <code>dynamic_destination</code> to <code>DLP</code>. Omit Instant Product Page parameters (<code>instant_product_page_used</code> and <code>page_image_index</code>).</li><li> To specify Destination as Website, omit the Dynamic destination and Instant Product Page parameters (<code>dynamic_destination</code> , <code>instant_product_page_used</code>, and <code>page_image_index</code>).</li><li> To specify Destination as Instant Product Page, set <code>instant_product_page_used</code> to <code>true</code>, and optionally pass <code>utm_params</code> and <code>page_image_index</code>. Omit the Dynamic destination parameter <code>dynamic_destination</code>.</li></ul></li><li> If <code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>, omit the Dynamic destination and Instant Product Page parameters (<code>dynamic_destination</code>, <code>instant_product_page_used</code>, and <code>page_image_index</code>).</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You cannot set <code>dynamic_destination</code> to <code>DLP</code> in the following scenarios: <ul><li><code>promotion_type</code> is <code>APP_ANDRIOD</code> or <code>APP_IOS</code>.</li><li><code>promotion_type</code> is <code>WEBSITE</code> and the <code>optimization_goal</code> is <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
<td><ul><li><code>landing_page_url</code></li><li><code>utm_params</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>deeplink_utm_params</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li></ul></td>
    <td>Configure these parameters according to your business needs. <ul><li> Note that the available deeplink settings may vary based on the optimization goal. To learn more about deeplinks, see <a href="https://business-api.tiktok.com/portal/docs?id=1779541971843073" target="_blank" >Deeplink</a>.</li><li> To learn about the supported destination and deeplink settings for the Click goal, see <a href="https://business-api.tiktok.com/portal/docs?id=1774519076905985" target="_blank" >Configure destination and deeplink settings for website-promoting Video Shopping Ads</a>.</li></ul></td>
  </tr>
  <tr>
    <td><ul><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code</code></li><li><code>phone_region_calling_code</code></li><li><code>phone_number</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Specified</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value.<br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank" >Enumerations - Call-to-action</a>.</td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disabled</td>
    <td> <ul><li><code>disclaimer_type</code></li><li><code>disclaimer_text</code></li><li><code>disclaimer_clickable_texts</code></li></ul>
</td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Playable</td>
    <td>Disabled</td>
    <td><code>playable_url</code></td>
    <td>Not passed</td>
  </tr>
</tbody>
</table>

#### Example
##### Destination as App with no specified video template
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK

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
                "advertiser_id": "{{advertiser_id}}",
                "campaign_id": "{{campaign_id}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "shopping_ads_fallback_type": "DEFAULT",
                "click_tracking_url": "{{click_tracking_url}}",
                "is_aco": false,
                "call_to_action": "LEARN_MORE",
                "identity_id": "{{identity_id}}",
                "landing_page_urls": null,
                "ad_name": "{{ad_name}}",
                "image_ids": [],
                "shopping_ads_video_package_id": "",
                "ad_id": "{{ad_id}}",
                "optimization_event": "ACTIVE",
                "ad_texts": null,
                "ad_text": "{{ad_text}}",
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_url": "",
                "card_id": null,
                "shopping_ads_deeplink_type": "NONE",
                "profile_image_url": "{{profile_image_url}}",
                "deeplink_type": "NORMAL",
                "product_specific_type": "ALL",
                "tracking_app_id": "{{tracking_app_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "display_name": "",
                "playable_url": "",
                "call_to_action_id": null,
                "viewability_postbid_partner": "UNSET",
                "ad_format": "SINGLE_VIDEO",
                "campaign_name": "{{campaign_name}}",
                "vast_moat_enabled": false,
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "creative_type": null,
                "tracking_pixel_id": 0,
                "dynamic_format": "UNSET",
                "page_id": null,
                "is_new_structure": true,
                "catalog_id": "{{catalog_id}}",
                "dynamic_destination": "UNSET",
                "viewability_vast_url": null,
                "create_time": "{{create_time}}",
                "modify_time": "{{modify_time}}",
                "deeplink": "",
                "app_name": "{{app_name}}",
                "brand_safety_vast_url": null,
                "music_id": null,
                "video_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "impression_tracking_url": "{{impression_tracking_url}}",
                "creative_authorized": false,
                "operation_status": "ENABLE"
            }
        ]
    }
}
(/code)
```

##### Destination as App with specified video template 
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "shopping_ads_video_package_id":"{{shopping_ads_video_package_id}}",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "identity_id": "{{identity_id}}",
                "optimization_event": "ACTIVE",
                "music_id": null,
                "identity_type": "CUSTOMIZED_USER",
                "call_to_action": "LEARN_MORE",
                "creative_authorized": false,
                "vast_moat_enabled": false,
                "campaign_name": "{{campaign_name}}",
                "display_name": "",
                "product_specific_type": "ALL",
                "viewability_vast_url": null,
                "ad_texts": null,
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "modify_time": "{{modify_time}}",
                "page_id": null,
                "viewability_postbid_partner": "UNSET",
                "catalog_id": "{{catalog_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "brand_safety_vast_url": null,
                "is_new_structure": true,
                "ad_name": "{{ad_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "tracking_app_id": "{{tracking_app_id}}",
                "ad_format": "SINGLE_VIDEO",
                "profile_image_url": "{{profile_image_url}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "dynamic_format": "UNSET",
                "click_tracking_url": "{{click_tracking_url}}",
                "creative_type": null,
                "app_name": "{{app_name}}",
                "campaign_id": "{{campaign_id}}",
                "operation_status": "ENABLE",
                "image_ids": [],
                "landing_page_url": "",
                "shopping_ads_video_package_id": "{{shopping_ads_video_package_id}}",
                "deeplink": "",
                "tracking_pixel_id": 0,
                "call_to_action_id": null,
                "shopping_ads_fallback_type": "DEFAULT",
                "deeplink_type": "NORMAL",
                "create_time": "{{create_time}}",
                "playable_url": "",
                "impression_tracking_url": "{{impression_tracking_url}}",
                "adgroup_id": "{{adgroup_id}}",
                "shopping_ads_deeplink_type": "NONE",
                "ad_id": "{{ad_id}}",
                "video_id": null,
                "landing_page_urls": null,
                "is_aco": false,
                "card_id": null,
                "ad_text": "{{ad_text}}",
                "dynamic_destination": "UNSET"
            }
        ]
    }
}
(/code)
```

##### Destination as Dynamic destination with no specified video template 
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"DLP",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "shopping_ads_deeplink_type": "NONE",
                "ad_texts": null,
                "brand_safety_vast_url": null,
                "is_aco": false,
                "tracking_pixel_id": {{tracking_pixel_id}},
                "ad_format": "SINGLE_VIDEO",
                "ad_name": "{{ad_name}}",
                "adgroup_id": "{{adgroup_id}}",
                "product_specific_type": "ALL",
                "landing_page_url": "{{landing_page_url}}",
                "call_to_action_id": null,
                "click_tracking_url": null,
                "operation_status": "ENABLE",
                "adgroup_name": "{{adgroup_name}}",
                "identity_type": "CUSTOMIZED_USER",
                "profile_image_url": "{{profile_image_url}}",
                "ad_text": "{{ad_text}}",
                "vast_moat_enabled": false,
                "shopping_ads_fallback_type": "CUSTOM",
                "creative_type": "SHOPPING_CATALOG_PAGE",
                "dynamic_format": "UNSET",
                "identity_id": "{{identity_id}}",
                "display_name": "{{display_name"}}",
                "video_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "catalog_id": "{{catalog_id}}",
                "impression_tracking_url": null,
                "page_id": {{page_id}},
                "tracking_offline_event_set_ids": [
                    "{{racking_offline_event_set_id}}"
                ],
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "viewability_postbid_partner": "UNSET",
                "campaign_id": "{{campaign_id"}}",
                "dynamic_destination": "DLP",
                "playable_url": "",
                "optimization_event": "ADD_BILLING",
                "image_ids": [],
                "modify_time": "{{modify_time}}",
                "card_id": null,
                "ad_id": "{{ad_id}}",
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "deeplink": null,
                "create_time": "{{create_time}}",
                "viewability_vast_url": null,
                "music_id": null,
                "shopping_ads_video_package_id": "",
                "call_to_action": "LEARN_MORE",
                "creative_authorized": false,
                "campaign_name": "{{campaign_name}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "landing_page_urls": null,
                "is_new_structure": true,
                "app_name": ""
            }
        ]
    }
}
(/code)
```
##### Destination as Website with no specified video template 
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "ad_texts": null,
                "creative_authorized": false,
                "tracking_pixel_id": {{tracking_pixel_id}},
                "deeplink_type": "NORMAL",
                "ad_text": "{{ad_text}}",
                "impression_tracking_url": null,
                "optimization_event": "ADD_BILLING",
                "app_name": "",
                "landing_page_urls": null,
                "ad_id": "{{ad_id}}",
                "identity_type": "CUSTOMIZED_USER",
                "playable_url": "",
                "music_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "call_to_action": "LEARN_MORE",
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "viewability_vast_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "campaign_id": "{{campaign_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "viewability_postbid_partner": "UNSET",
                "advertiser_id": "{{advertiser_id}}",
                "product_specific_type": "ALL",
                "operation_status": "ENABLE",
                "catalog_id": "{{catalog_id}}",
                "dynamic_format": "UNSET",
                "page_id": null,
                "shopping_ads_deeplink_type": "NONE",
                "image_ids": [],
                "ad_name": "{{ad_name}}",
                "display_name": "{{display_name}}",
                "shopping_ads_video_package_id": "",
                "create_time": "{{create_time}}",
                "brand_safety_vast_url": null,
                "shopping_ads_fallback_type": "CUSTOM",
                "campaign_name": "{{campaign_name}}",
                "is_new_structure": true,
                "profile_image_url": "{{profile_image_url}}",
                "video_id": null,
                "ad_format": "SINGLE_VIDEO",
                "dynamic_destination": "UNSET",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "modify_time": "{{modify_time}}",
                "is_aco": false,
                "identity_id": "{{identity_id"}}",
                "card_id": null,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "deeplink": "",
                "vast_moat_enabled": false,
                "click_tracking_url": null,
                "call_to_action_id": null,
                "creative_type": null
            }
        ]
    }
}
(/code)
```
##### Destination as Instant Product Page with no specified video template 
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "SINGLE_VIDEO",
            "vertical_video_strategy": "CATALOG_VIDEOS",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "instant_product_page_used":true,
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "catalog_id": "{{catalog_id}}",
                "image_ids": [],
                "ad_id": "{{ad_id}}",
                "creative_type": "SHOPPING_CATALOG_PAGE",
                "campaign_name": "{{campaign_name}}",
                "video_id": null,
                "advertiser_id": "{{advertiser_id}}",
                "page_id": {{page_id}},
                "playable_url": "",
                "shopping_ads_fallback_type": "CUSTOM",
                "call_to_action": "LEARN_MORE",
                "modify_time": "{{modify_time}}",
                "is_new_structure": true,
                "operation_status": "ENABLE",
                "viewability_postbid_partner": "UNSET",
                "click_tracking_url": null,
                "app_name": "",
                "brand_safety_vast_url": null,
                "identity_id": "{{identity_id}}",
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "shopping_ads_video_package_id": "",
                "tracking_pixel_id": {{tracking_pixel_id}},
                "vast_moat_enabled": false,
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "ad_text": "{{ad_text}}",
                "landing_page_urls": null,
                "dynamic_destination": "UNSET",
                "card_id": null,
                "shopping_ads_deeplink_type": "NONE",
                "deeplink": "",
                "dynamic_format": "UNSET",
                "adgroup_name": "{{adgroup_name}}",
                "viewability_vast_url": null,
                "display_name": "{{display_name}}",
                "call_to_action_id": null,
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "product_specific_type": "ALL",
                "music_id": null,
                "adgroup_id": "{{adgroup_id}}",
                "profile_image_url": "{{profile_image_url}}",
                "ad_name": "{{ad_name}}",
                "creative_authorized": false,
                "identity_type": "CUSTOMIZED_USER",
                "create_time": "{{create_time}}",
                "ad_texts": null,
                "impression_tracking_url": null,
                "optimization_event": "ADD_BILLING",
                "campaign_id": "{{campaign_id}}",
                "ad_format": "SINGLE_VIDEO",
                "is_aco": false,
                "vertical_video_strategy": "CATALOG_VIDEOS",
                "landing_page_url": "{{landing_page_url}}"
            }
        ]
    }
}
(/code)
```



### Ad format as Catalog Carousel

To use Catalog Carousel format, ensure that at the ad group level `placement_type`  is set to `PLACEMENT_TYPE_AUTOMATIC`,  or `placement_type` is set to `PLACEMENT_TYPE_NORMAL`, and `PLACEMENT_TIKTOK` is included in the value of `placements`.


<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:26%">Requirement</th>
    <th style="width:26%">Parameters</th>
<th style="width:30%">How to configure the parameters</th>
</tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
<td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank" >/ad/aco/create/</a> to create Smart Creative VSA ads.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Custom User (custom identity) </td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank" >/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad details - Dynamic format</td>
    <td>Disabled<br></td>
    <td><code>dynamic_format</code></td>
    <td><code>UNSET</code> or not passed<br></td>
  </tr>
  <tr>
    <td rowspan="2">Ad format</td>
    <td rowspan="2"><strong>Catalog Carousel</strong>: Create an ad with 2–20 products displayed in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
    <td><code>ad_format</code></td>
    <td><code>CATALOG_CAROUSEL</code></td>
  </tr>
  <tr>
    <td><code>vertical_video_strategy</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options: <ul><li><strong>All products</strong></li><li><strong>Product set</strong></li><li><strong>Specific products</strong> with a maximum of 20 products </li></ul></td>
    <td rowspan="2"><ul><li><code>catalog_id</code></li><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code>product_set_id</code></li><li><code>sku_ids</code></li><li><code>carousel_image_index</code></li></ul></td>
<td rowspan="2"><ul><li> Set <code>catalog_id</code> to ID of the same catalog specified at the ad group level.</li><li> Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Carousel Ads. You can specify the scope of product images to choose from using the <code>product_specific_type</code> parameter:<ul><li> To have the Carousel images dynamically chosen from all products in the catalog, set <code>product_specific_type</code> to <code>ALL</code>, and do not pass any of <code>item_group_ids</code>, <code>product_set_id</code>, or <code>sku_ids</code>. The catalog (<code>catalog_id</code>) needs to include at least two products.</li><li> To have the Carousel images dynamically chosen from products in a product set, set <code>product_specific_type</code> to <code>PRODUCT_SET</code>, and pass either <code>item_group_ids</code> or <code>product_set_id</code>. The specified <code>item_group_ids</code> or <code>product_set_id</code> needs to include at least two products.</li><li> To have the images dynamically chosen from a customized product range consisting of up to 20 products, set <code>product_specific_type</code> to <code>CUSTOMIZED_PRODUCTS</code>, and pass <code>sku_ids</code>. The specified <code>sku_ids</code> needs to include at least two products.<br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</li></ul></li><li> Carousel image type:<ul><li> Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li><li> Use the additional images of the products: Optionally, you can specify the position of the product image within the additional image list (<code>additional_image_urls</code>) for each selected product by using the <code>carousel_image_index</code> parameter to select from the additional images.</li></ul></li></ul></li></ul></td>
  </tr>
  <tr>
    <td rowspan="2">Ad creative - image</td>
    <td rowspan="2">At least 2 images supported in the Carousel Ad</td>
  </tr>
  <tr>
    <td><code>image_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad creative - video</td>
    <td>Disabled</td>
    <td><ul><li><code>video_id</code></li><li><code>tiktok_item_id</code></li><li><code>dark_post_status</code></li><li><code>shopping_ads_video_package_id</code></li></ul>
</td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad creative - music</td>
    <td>A piece of music supported in the Carousel Ad</td>
    <td><code>music_id</code></td>
<td>Pass one music ID that is valid for use in VSA Carousel Ads. <br>To obtain a valid music ID, you can use any of the following methods: <ul><li> Filter the pieces of music for VSA Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122" target="_blank" >/file/music/get/</a>.</li><li> Upload a piece of customized music for VSA Carousel Ads to an ad account by using any of the following methods:<ul><li> Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank" >/file/music/upload/</a>.</li><li> Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank" >/file/music/upload/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Ad text</td>
    <td>Specified</td>
    <td><code>ad_text</code></td>
    <td>Pass a valid value</td>
  </tr>
  <tr>
    <td>Interactive add-on</td>
    <td>Disabled</td>
    <td><code>card_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="4">Destination<br></td>
    <td rowspan="4"><ul><li> If Optimization location is Website, the destination is Website.</li><li> If Optimization location is App, the destination is App. </li></ul><br>Instant Product Page and Dynamic destination should be disabled.</td>
    <td><code>dynamic_destination</code></td>
    <td><code>UNSET</code> or not passed<br></td>
  </tr>
  <tr>
    <td><ul><li><code> instant_product_page_used </code></li><li><code> page_image_index </code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
   <td><ul><li><code>landing_page_url</code></li><li><code>utm_params</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>deeplink_utm_params</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li></ul></td>
    <td>Configure these parameters according to your business needs. <ul><li> Note that the available deeplink settings may vary based on the optimization goal. To learn more about deeplinks, see <a href="https://business-api.tiktok.com/portal/docs?id=1779541971843073" target="_blank" >Deeplink</a>.</li><li> To learn about the supported destination and deeplink settings for the Click goal, see <a href="https://business-api.tiktok.com/portal/docs?id=1774519076905985" target="_blank" >Configure destination and deeplink settings for website-promoting Video Shopping Ads</a>.</li><br><p><span style="color:darkred"><b>Note</b></span>: Product deeplinks and Product links are not supported. Therefore, <code>shopping_ads_deeplink_type</code> and <code>shopping_ads_fallback_type</code> cannot be set to <code>SHOPPING_ADS</code>.</ul></td>
  </tr>
  <tr>
<td><ul><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code</code></li><li><code>phone_region_calling_code</code></li><li><code>phone_number</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Specified</td>
    <td><code>call_to_action</code></td>
    <td>Pass a valid value.<br>To get the enum values, see <a href="https://business-api.tiktok.com/portal/docs?id=1737174886619138#item-link-Call-to-action" target="_blank" >Enumerations - Call-to-action</a>.</td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disabled</td>
    <td> <ul><li><code>disclaimer_type</code></li><li><code>disclaimer_text</code></li><li><code>disclaimer_clickable_texts</code></li></ul>
</td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Playable</td>
    <td>Disabled</td>
    <td><code>playable_url</code></td>
    <td>Not passed</td>
  </tr>
</tbody>
</table>

#### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "CUSTOMIZED_USER",
            "identity_id": "{{identity_id}}",
            "catalog_id":"{{catalog_id}}",
            "product_specific_type":"ALL",
            "dynamic_format":"UNSET",
            "ad_format": "CATALOG_CAROUSEL",
            "music_id":"{{music_id}}",
            "ad_text": "{{ad_text}}",
            "dynamic_destination":"UNSET",
            "shopping_ads_fallback_type":"CUSTOM",
            "landing_page_url":"{{landing_page_url}}",
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
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
                "vast_moat_enabled": false,
                "ad_name": "{{ad_name}}",
                "call_to_action": "LEARN_MORE",
                "call_to_action_id": null,
                "is_new_structure": true,
                "adgroup_id": "{{adgroup_id}}",
                "ad_id": "{{ad_id}}",
                "is_aco": false,
                "card_id": null,
                "vertical_video_strategy": null,
                "app_name": "",
                "ad_text": "{{ad_text}}",
                "identity_type": "CUSTOMIZED_USER",
                "landing_page_urls": null,
                "campaign_name": "{{campaign_name}}",
                "product_specific_type": "ALL",
                "music_id": "{{music_id}}",
                "shopping_ads_video_package_id": "",
                "viewability_postbid_partner": "UNSET",
                "ad_format": "CATALOG_CAROUSEL",
                "ad_texts": null,
                "video_id": null,
                "campaign_id": "{{campaign_id}}",
                "shopping_ads_fallback_type": "CUSTOM",
                "adgroup_name": "{{adgroup_name}}",
                "deeplink": "",
                "create_time": "{{create_time}}",
                "tracking_pixel_id": {{tracking_pixel_i}},
                "catalog_id": "{{catalog_id}}",
                "identity_id": "{{identity_id}}",
                "brand_safety_vast_url": null,
                "dynamic_destination": "UNSET",
                "tracking_offline_event_set_ids": [
                    "{{tracking_offline_event_set_id}}"
                ],
                "profile_image_url": "{{profile_image_url}}",
                "deeplink_type": "NORMAL",
                "modify_time": "{{modify_time}}",
                "creative_type": null,
                "page_id": null,
                "display_name": "{{display_name}}",
                "impression_tracking_url": null,
                "ad_ref_pixel_id": {{ad_ref_pixel_id}},
                "playable_url": "",
                "click_tracking_url": null,
                "advertiser_id": "{{advertiser_id}}",
                "landing_page_url": "{{landing_page_url}}",
                "avatar_icon_web_uri": "{{avatar_icon_web_uri}}",
                "image_ids": [],
                "creative_authorized": false,
                "dynamic_format": "UNSET",
                "shopping_ads_deeplink_type": "NONE",
                "viewability_vast_url": null,
                "optimization_event": "ADD_BILLING",
                "operation_status": "ENABLE"
            }
        ]
    }
}
(/code)
```
## 5. Preview an ad
You can preview your ad using [/creative/ads_preview/create/](https://ads.tiktok.com/marketing_api/docs?id=1739403070695426). <br>
 We offer preview types of `AD` and `ADS_CREATION`to enable you to preview existing ads and ads that you plan to create.
