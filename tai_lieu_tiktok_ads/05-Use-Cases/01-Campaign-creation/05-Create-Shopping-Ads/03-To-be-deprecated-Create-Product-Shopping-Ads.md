---
title: "(To-be-deprecated) Create Product Shopping Ads"
doc_id: 1785886237030401
path: "Use Cases / Campaign creation / Create Shopping Ads / (To-be-deprecated) Create Product Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To-be-deprecated) Create Product Shopping Ads

This article walks you through the steps to create Product Shopping Ads (PSA).

> <span style="color:DarkRed">**Important**</span><br>
> Starting Q3, 2025, [GMV Max](https://business-api.tiktok.com/portal/docs?id=1822009058467842) will be the default and only supported campaign type for TikTok Shop Ads. If you create ads using the Product Sales objective and TikTok Shop as your sales destination, you will no longer be able to use the API to create, edit, or duplicate [LIVE Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554), [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), or [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942291730434). Please migrate to the [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465) as early as possible. If you have questions or need support, please contact your TikTok representative.  Learn more about [the detailed changes by ad type and endpoint](https://business-api.tiktok.com/portal/docs?id=1837161048383489).


> <span style="color:DodgerBlue">**Note**</span><br>
> Audience size estimation is not available for PSA.

# Steps

## 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.
> <span style="color:DodgerBlue">**Note**</span><br>
> After you create a Product Shopping campaign, you can only update the following settings of the campaign:<ul><li>`campaign_name`</li><li>`budget`</li><li>`operation_status`</li></ul>

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:29%">Parameters</th>
    <th style="width:26%">How to configure the parameters</th>
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
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362"  target="_blank">/campaign/spc/create/</a> to create a Smart+ Product Shopping campaign.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Campaign product source</td>
    <td>TikTok Shop</td>
    <td><code>campaign_product_source</code></td>
    <td><code>STORE</code></td>
  </tr>
  <tr>
    <td>Special ad categories (Optional)</td>
    <td>Supported with allowlist</td>
    <td><code>special_industries</code></td>
    <td>Pass valid values or not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Using special ad categories in Shopping Ads with campaign product source as TikTok Shop is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>Disabled</td>
    <td><code>budget_optimize_on</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Campaign budget mode</td>
    <td>Any of the following types:<ul><li>Daily budget</li><li>Lifetime budget</li><li>Infinite budget</li></ul></td>
    <td><code>budget_mode</code></td>
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY </code></li><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_INFINITE</code></li></ul></td>
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
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_product_source": "STORE",
    "campaign_name": "{{campaign_name}}",
    "budget_optimize_on": false,
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
        "operation_status": "ENABLE",
        "create_time": "{{create_time}}",
        "is_search_campaign": false,
        "campaign_id": "{{campaign_id}}",
        "objective": "LANDING_PAGE",
        "campaign_app_profile_page_state": "UNSET",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_product_source": "STORE",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "roas_bid": 0,
        "objective_type": "PRODUCT_SALES",
        "budget": {{budget}},
        "modify_time": "{{modify_time}}",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_new_structure": true,
        "is_smart_performance_campaign": false,
        "deep_bid_type": null,
        "budget_mode": "BUDGET_MODE_TOTAL"
    }
}
(/code)
```
## 2. Create and manage TikTok Shops
TikTok Commerce (formerly known as TikTok Shopping) offers TikTok Shops for brands and sellers who want to sell their products directly on their TikTok account. 

You can set up TikTok Shop by creating TikTok Shops on TikTok Seller Center. For detailed instructions on how to create a TikTok Shop, see [Set Up TikTok Shop Using TikTok Seller Center](https://ads.tiktok.com/help/article?aid=10005127).

After you have created a TikTok Shop, follow the procedures below:
- i. Add the TikTok Shop to the Business Center. To learn about how to request TikTok Shop access from a Business Center, see [here](https://ads.tiktok.com/help/article?aid=10012166). 
- ii. Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). You can also choose `advertiser_role` that you want to assign to the members invited.
- iii. Share a TikTok Shop with members and grant the TikTok Shop management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). Make sure to specify `TIKTOK_SHOP` in the `asset_type` field and `AD_PROMOTION` in the `store_role` field.

## 3. Create an ad group
Create an ad group using [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114). Note that the following requirements must be met.

> <span style="color:DodgerBlue">**Note**</span><br>
> After you create a Product Shopping ad group, you can only update the following settings of the ad group:<ul><li>`adgroup_name`</li><li>`budget`</li><li>`schedule_end_time`</li><li>`roas_bid`</li><li>Targeting settings such as `location_ids`, `gender`, and `age_groups`</li></ul>

<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:29%">Parameters</th>
    <th style="width:26%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Shopping ads type</td>
    <td>PSA</td>
	<td><code>shopping_ads_type</code></td>
    <td><code>PRODUCT_SHOPPING_ADS</code></td>
  </tr>
  <tr>
    <td>Identity</td>
    <td>Disabled</td>
    <td><ul><li> <code>identity_id</code></li><li><code>identity_type</code></li><li><code>identity_authorized_bc_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="4">Product source</td>
    <td rowspan="4">TikTok Shop with the targeting location as any of the following regions: <ul><li>Indonesia</li><li>Malaysia</li><li>Singapore</li><li>Thailand</li><li>Vietnam</li><li>the Philippines</li></ul></td>
    <td><code>product_source</code></td>
    <td><code>STORE</code></td>
  </tr>
  <tr>
    <td><code>catalog_id</code></td>
    <td>Specify the ID of the catalog that is bound to the TikTok Shop.<br>You can use <a href="https://ads.tiktok.com/marketing_api/docs?id=1752267762718722"  target="_blank">/store/list/</a> to get the <code>catalog_id</code>, <code>store_id</code> and <code>store_authorized_bc_id</code> for available stores under an ad account.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass <code>catalog_id</code> because it will be ignored.</td>
  </tr>
  <tr>
    <td><code>catalog_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><ul><li><code>store_id</code></li><li> <code>store_authorized_bc_id</code></li></ul></td>
    <td>Specified. <br>The targeting location of the TikTok Shop (<code>store_id</code>) should be any of the following regions: <ul><li>Indonesia</li><li>Malaysia</li><li>Singapore</li><li>Thailand</li><li>Vietnam</li><li>the Philippines</li></ul></td>
  </tr>
  <tr>
    <td>Promotion type <br>(Optimization location)</td>
    <td>Unset.<br>The default setting is PSA products.</td>
    <td><code>promotion_type</code></td>
    <td><code>PSA_PRODUCT</code></td>
  </tr>
  <tr>
    <td>Promoted website or App</td>
    <td>Disabled</td>
    <td><ul><li><code>app_id</code></li><li><code>pixel_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Placement</td>
    <td rowspan="2"><strong>Select Placement </strong>with TikTok placement only</td>
    <td><code>placement_type</code></td>
    <td><code>PLACEMENT_TYPE_NORMAL</code></td>
  </tr>
  <tr>
    <td><code>placements</code></td>
    <td><code>["PLACEMENT_TIKTOK"]</code></td>
  </tr>
  <tr>
    <td>Include search results</td>
    <td>Disabled</td>
    <td><code>search_result_enabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>User comment</td>
    <td>Disabled</td>
    <td><code>comment_disabled</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>Video download</td>
    <td>Disabled</td>
    <td><code>video_download_disabled</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>Video sharing</td>
    <td>Enabled</td>
    <td><code>share_disabled</code></td>
    <td><code>false</code> or not passed</td>
  </tr>
  <tr>
    <td>Pangle block list</td>
    <td>Disabled</td>
    <td><code>blocked_pangle_app_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Saved audience</li></ul></td>
    <td>Disabled</td>
    <td><code>saved_audience_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Automatic targeting</li></ul></td>
    <td>Disabled</td>
    <td><code>auto_targeting_enabled</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Retarget audience</li></ul></td>
    <td>Disabled</td>
    <td><ul><li><code>shopping_ads_retargeting_type</code></li><li><code>shopping_ads_retargeting_actions_days</code></li><li><code> included_custom_actions </code></li><li><code>excluded_custom_actions</code></li><li><code>shopping_ads_retargeting_custom_audience_relation</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Demographics - Location</li></ul></td>
    <td>The location should match or be the subset of the targeting location of your TikTok Shop. </td>
    <td><code>location_ids</code></td>
    <td>IDs of locations that match or be a subset of the targeting location of <code>store_id</code>.</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Demographics - zip code targeting</li></ul></td>
    <td>Disabled</td>
    <td><code>zipcode_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Demographics - Age</li></ul> </td>
    <td>Select one or more age groups, excluding the 13-17 age group.<br></td>
    <td><code>age_groups</code></td>
    <td>One or more of the following values:<ul><li><code>AGE_18_24</code></li><li><code>AGE_25_34</code></li><li><code>AGE_35_44</code></li><li><code>AGE_45_54</code></li><li><code>AGE_55_100</code></li></ul></td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Demographics - Spending power</li></ul></td>
    <td>Disabled</td>
    <td><code>spending_power</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Interests & Behaviors - Purchase intention</li></ul></td>
    <td>Disabled</td>
    <td><code>purchase_intention_keyword_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Audience<ul><li>Smart audience</li></ul></li></ul></td>
    <td>Disabled</td>
    <td><code>smart_audience_enabled</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting<ul><li>Interests & Behaviors <ul><li>Smart interests & behaviors</li></ul></li></ul></td>
    <td>Disabled</td>
    <td><code>smart_interest_behavior_enabled</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li>Pangle audience packages</li></ul></td>
    <td>Disabled</td>
    <td><ul><li><code>included_pangle_audience_package_ids</code></li><li><code>excluded_pangle_audience_package_ids</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li>Targeting expansion</li></ul></td>
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
    <td>Content exclusions</td>
    <td>Disabled</td>
    <td><ul><li><code>brand_safety_type</code></li><li><code>brand_safety_partner</code></li><li><code>category_exclusion_ids</code></li><li><code>vertical_sensitivity_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Dayparting</td>
    <td>Disabled</td>
    <td><code>dayparting</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following types:<ul><li>Gross revenue</li><li>Purchases</li><li>Clicks</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td>Any of the following values:<ul><li><code>VALUE</code></li><li><code>CONVERT</code></li><li><code>CLICK</code></li></ul></td>
  </tr>
  <tr>
    <td>Optimization event</td>
    <td>Unset.<br>The default setting for the optimization goals Gross revenue and Purchases is Complete Payment.</td>
    <td><code>optimization_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>, set this field to <code>SHOPPING</code>.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, do not pass this field.</li></ul></td>
  </tr>
  <tr>
    <td>Frequency cap</td>
    <td>Disabled</td>
    <td><ul><li><code>frequency</code></li><li><code> frequency_schedule</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td><ul><li>If optimization goal is Purchases or Clicks, the strategy can be any of the following options: <ul><li><b>Maximum Delivery</b>: Aim to spend your entire budget to get the most results. </li><li><b>Cost Cap</b> or <b>Target CPA</b>: Keep the average cost per result around the stated amount.</li></ul></li><li>If optimization goal is Gross revenue, the strategy can be any of the following options: <ul><li><b>Highest Gross Revenue</b>: Aim to spend your entire budget to get the most gross revenue. </li><li><b>Target ROAS</b>: Keep the average ROAS around the stated amount. </li></ul></li></ul></td>
    <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code> or <code>CLICK</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not pass <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>optimization_goal</code> is <code>CONVERT</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> (Target CPA), pass <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> (Cost Cap), pass <code>bid_price</code> at the same time.</li></ul></li></ul></li><li> If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time.</li></ul></li></ul> <p><span style="color:darkred"><b>Note</b></span>: The value range for <code>roas_bid</code> is 0.01-20.</li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Attribution settings<br>(supported only if optimization goal is Gross revenue or Purchases)</td>
    <td>The supported Click-through windows are:<ul><li>1-day click</li><li>7-day click</li><li>14-day click</li><li>28-day click</li></ul></td>
    <td><code>click_attribution_window</code><br>(if <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>)</td>
    <td>Any of the following values: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS </code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul> </td>
  </tr>
  <tr>
    <td>The supported View-through windows are:<ul><li>Off</li><li>1-day view</li><li>7-day view</li></ul></td>
    <td><code>view_attribution_window</code><br>(if <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>)</td>
    <td>Any of the following values: <ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul> </td>
  </tr>
  <tr>
    <td>Event count: Once.</td>
    <td><code>attribution_event_count</code><br>(if <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>)</td>
    <td><code>ONCE</code> or not passed</td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Clicks, the billing event should be CPC.</li><li> If optimization goal is Purchases or Gross revenue, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERT</code> or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul> </td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td><ul><li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li>If Bid Strategy is set to Maximum Delivery, Highest Value or Minimum ROAS, you can only set Delivery Type to Standard.</li></ul> </td>
    <td><code>pacing</code></td>
    <td><ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li><li>If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul> </td>
  </tr>
  <tr>
    <td>Automated Creative Optimization (ACO)</td>
    <td>Disabled</td>
    <td><code>creative_material_mode</code></td>
    <td><code>CUSTOM</code> or not passed</td>
  </tr>
  <tr>
    <td>Split test</td>
    <td>Disabled<br>PSA ad groups cannot be used in <a href="https://business-api.tiktok.com/portal/docs?id=1742666471475201"  target="_blank">/split_test/create/</a> to create split tests.</td>
    <td>/</td>
    <td>/</td>
  </tr>
</tbody>
</table>


### Example 
#### Optimization goal as Gross revenue
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
    "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
    "product_source": "STORE",
    "catalog_id": "{{catalog_id}}",
    "store_id": "{{store_id}}",
    "store_authorized_bc_id": "{{store_authorized_bc_id}}",
    "promotion_type": "PSA_PRODUCT",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "comment_disabled": true,
    "video_download_disabled": true,
    "location_ids": ["7115989"],
    "age_groups":["AGE_18_24","AGE_25_34","AGE_35_44","AGE_45_54","AGE_55_100"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "VALUE",
    "optimization_event": "SHOPPING",
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
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "adgroup_id": "{{adgroup_id}}",
        "keywords": null,
        "campaign_name": "{{campaign_name}}",
        "feed_type": null,
        "view_attribution_window": "ONE_DAY",
        "scheduled_budget": 0,
        "category_id": "0",
        "schedule_infos": null,
        "frequency_schedule": null,
        "roas_bid": {{roas_bid}},
        "budget": {{budget}},
        "app_id": null,
        "share_disabled": false,
        "gender": "GENDER_UNLIMITED",
        "is_new_structure": true,
        "pixel_id": null,
        "isp_ids": [],
        "schedule_start_time": "{{}}",
        "search_result_enabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "store_id": "{{store_id}}",
        "brand_safety_partner": null,
        "location_ids": [
            "7115989"
        ],
        "skip_learning_phase": true,
        "comment_disabled": true,
        "is_hfss": false,
        "secondary_optimization_event": null,
        "auto_targeting_enabled": false,
        "app_type": null,
        "adgroup_app_profile_page_state": null,
        "rf_purchased_type": null,
        "next_day_retention": null,
        "pacing": "PACING_MODE_SMOOTH",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "billing_event": "OCPM",
        "interest_category_ids": [],
        "audience_ids": [],
        "promotion_type": "PSA_PRODUCT",
        "rf_estimated_cpr": null,
        "deep_bid_type": "VO_MIN_ROAS",
        "frequency": null,
        "conversion_window": null,
        "modify_time": "{{modify_time}}",
        "ios14_quota_type": "UNOCCUPIED",
        "bid_display_mode": "CPMV",
        "operation_status": "ENABLE",
        "adgroup_name": "{{adgroup_name}}",
        "languages": [],
        "rf_estimated_frequency": null,
        "conversion_bid_price": 0,
        "creative_material_mode": "CUSTOM",
        "product_source": "STORE",
        "bid_type": "BID_TYPE_NO_BID",
        "inventory_filter_enabled": false,
        "is_smart_performance_campaign": false,
        "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
        "optimization_goal": "VALUE",
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "excluded_audience_ids": [],
        "create_time": "{{create_time}}",
        "delivery_mode": null,
        "advertiser_id": "{{advertiser_id}}",
        "statistic_type": null,
        "purchased_reach": null,
        "actions": [],
        "bid_price": 0,
        "network_types": [],
        "click_attribution_window": "SEVEN_DAYS",
        "schedule_type": "SCHEDULE_START_END",
        "deep_cpa_bid": 0,
        "campaign_id": "{{campaign_id}}",
        "purchased_impression": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "operating_systems": [],
        "device_price_ranges": null,
        "attribution_event_count": "ONCE",
        "catalog_id": "{{catalog_id}}",
        "interest_keyword_ids": [],
        "optimization_event": "SHOPPING",
        "video_download_disabled": true,
        "category_exclusion_ids": [],
        "store_authorized_bc_id": "{{store_authorized_bc_id}}"
    }
}
(/code)
```
#### Optimization goal as Purchases
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
    "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
    "product_source": "STORE",
    "catalog_id": "{{catalog_id}}",
    "store_id": "{{store_id}}",
    "store_authorized_bc_id": "{{store_authorized_bc_id}}",
    "promotion_type": "PSA_PRODUCT",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "comment_disabled": true,
    "video_download_disabled": true,
    "location_ids": ["7115989"],
    "age_groups":["AGE_18_24","AGE_25_34","AGE_35_44","AGE_45_54","AGE_55_100"],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "optimization_goal": "CONVERT",
    "optimization_event": "SHOPPING",
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
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "adgroup_id": "{{adgroup_id}}",
        "keywords": null,
        "campaign_name": "{{campaign_name}}",
        "feed_type": null,
        "view_attribution_window": "ONE_DAY",
        "scheduled_budget": 0,
        "category_id": "0",
        "schedule_infos": null,
        "frequency_schedule": null,
        "budget": {{budget}},
        "app_id": null,
        "share_disabled": false,
        "gender": "GENDER_UNLIMITED",
        "is_new_structure": true,
        "pixel_id": null,
        "isp_ids": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "search_result_enabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "store_id": "{{store_id}}",
        "brand_safety_partner": null,
        "location_ids": [
            "7115989"
        ],
        "skip_learning_phase": true,
        "comment_disabled": true,
        "is_hfss": false,
        "secondary_optimization_event": null,
        "auto_targeting_enabled": false,
        "app_type": null,
        "adgroup_app_profile_page_state": null,
        "rf_purchased_type": null,
        "next_day_retention": null,
        "pacing": "PACING_MODE_SMOOTH",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "billing_event": "OCPM",
        "interest_category_ids": [],
        "audience_ids": [],
        "promotion_type": "PSA_PRODUCT",
        "rf_estimated_cpr": null,
        "deep_bid_type": null,
        "frequency": null,
        "conversion_window": null,
        "modify_time": "{{modify_time}}",
        "ios14_quota_type": "UNOCCUPIED",
        "bid_display_mode": "CPMV",
        "operation_status": "ENABLE",
        "adgroup_name": "{{adgroup_name}}",
        "languages": [],
        "rf_estimated_frequency": null,
        "conversion_bid_price": {{conversion_bid_price}},
        "creative_material_mode": "CUSTOM",
        "product_source": "STORE",
        "bid_type": "BID_TYPE_CUSTOM",
        "inventory_filter_enabled": false,
        "is_smart_performance_campaign": false,
        "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
        "optimization_goal": "CONVERT",
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "excluded_audience_ids": [],
        "create_time": "{{create_time}}",
        "delivery_mode": null,
        "advertiser_id": "{{advertiser_id}}",
        "statistic_type": null,
        "purchased_reach": null,
        "actions": [],
        "bid_price": 0,
        "network_types": [],
        "click_attribution_window": "SEVEN_DAYS",
        "schedule_type": "SCHEDULE_START_END",
        "deep_cpa_bid": 0,
        "campaign_id": "{{campaign_id}}",
        "purchased_impression": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "operating_systems": [],
        "device_price_ranges": null,
        "attribution_event_count": "ONCE",
        "catalog_id": "{{catalog_id}}",
        "interest_keyword_ids": [],
        "optimization_event": "SHOPPING",
        "video_download_disabled": true,
        "category_exclusion_ids": [],
        "store_authorized_bc_id": "{{store_authorized_bc_id}}"
    }
}
(/code)
```
#### Optimization goal as Clicks
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
    "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
    "product_source": "STORE",
    "catalog_id": "{{catalog_id}}",
    "store_id": "{{store_id}}",
    "store_authorized_bc_id": "{{store_authorized_bc_id}}",
    "promotion_type": "PSA_PRODUCT",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    "comment_disabled": true,
    "video_download_disabled": true,
    "location_ids": ["7115989"],
    "age_groups":["AGE_18_24","AGE_25_34","AGE_35_44","AGE_45_54","AGE_55_100"],
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
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "adgroup_id": "{{adgroup_id}}",
        "keywords": null,
        "campaign_name": "{{campaign_name}}",
        "feed_type": null,
        "scheduled_budget": 0,
        "category_id": "0",
        "schedule_infos": null,
        "frequency_schedule": null,
        "budget": {{budget}},
        "app_id": null,
        "share_disabled": false,
        "gender": "GENDER_UNLIMITED",
        "is_new_structure": true,
        "pixel_id": null,
        "isp_ids": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "search_result_enabled": false,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "store_id": "{{store_id}}",
        "brand_safety_partner": null,
        "location_ids": [
            "7115989"
        ],
        "skip_learning_phase": false,
        "comment_disabled": true,
        "is_hfss": false,
        "secondary_optimization_event": null,
        "auto_targeting_enabled": false,
        "app_type": null,
        "adgroup_app_profile_page_state": null,
        "rf_purchased_type": null,
        "next_day_retention": null,
        "pacing": "PACING_MODE_SMOOTH",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "billing_event": "CPC",
        "interest_category_ids": [],
        "audience_ids": [],
        "promotion_type": "PSA_PRODUCT",
        "rf_estimated_cpr": null,
        "deep_bid_type": null,
        "frequency": null,
        "conversion_window": null,
        "modify_time": "{{modify_time}}",
        "ios14_quota_type": "UNOCCUPIED",
        "bid_display_mode": "CPMV",
        "operation_status": "ENABLE",
        "adgroup_name": "{{adgroup_name}}",
        "languages": [],
        "rf_estimated_frequency": null,
        "conversion_bid_price": 0,
        "creative_material_mode": "CUSTOM",
        "product_source": "STORE",
        "bid_type": "BID_TYPE_NO_BID",
        "inventory_filter_enabled": false,
        "is_smart_performance_campaign": false,
        "shopping_ads_type": "PRODUCT_SHOPPING_ADS",
        "optimization_goal": "CLICK",
        "age_groups": [
            "AGE_18_24",
            "AGE_25_34",
            "AGE_35_44",
            "AGE_45_54",
            "AGE_55_100"
        ],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "excluded_audience_ids": [],
        "create_time": "{{create_time}}",
        "delivery_mode": null,
        "advertiser_id": "{{advertiser_id}}",
        "statistic_type": null,
        "purchased_reach": null,
        "actions": [],
        "bid_price": 0,
        "network_types": [],
        "schedule_type": "SCHEDULE_START_END",
        "deep_cpa_bid": 0,
        "campaign_id": "{{campaign_id}}",
        "purchased_impression": null,
        "brand_safety_type": "NO_BRAND_SAFETY",
        "operating_systems": [],
        "device_price_ranges": null,
        "catalog_id": "{{}}",
        "interest_keyword_ids": [],
        "optimization_event": null,
        "video_download_disabled": true,
        "category_exclusion_ids": [],
        "store_authorized_bc_id": "{{store_authorized_bc_id}}"
    }
}
(/code)
```

## 4. Create an ad
Create an ad using [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354). Note that the following requirements must be met. Additionally, you can create only one ad in each PSA ad group.
> <span style="color:DodgerBlue">**Note**</span><br>
> After you create a Product Shopping ad, you can update the `ad_name` of the ad.
<table>
<thead>
  <tr>
    <th style="width:18%">Setting</th>
    <th style="width:27%">Requirement</th>
    <th style="width:29%">Parameters</th>
    <th style="width:26%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626"  target="_blank">/ad/aco/create/</a> to create Smart Creative PSA ads.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td>Identity</td>
    <td>Disabled</td>
    <td><ul><li><code>identity_type</code></li><li><code>identity_id</code> </li><li><code>identity_authorized_bc_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Products</td>
    <td rowspan="2"><strong>All products</strong> or <strong>Specific products</strong> with a maximum of 50 products</td>
    <td><ul><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code>product_set_id</code></li><li><code>sku_ids</code></li></ul></td>
    <td><ul><li>Set <code>product_specific_type</code> to <code>ALL</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 50 products through <code>item_group_ids</code> at the same time.</li></ul></li><li>Do not pass <code>product_set_id</code> and <code>sku_ids</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To retrieve the <code>item_group_ids</code> of products within a TikTok Shop and determine whether they can be used in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1793482248880130" target="_blank">/store/product/get/</a>. If the returned <code>status</code> for a product is <code>AVAILABLE</code>, you can use the product in ads.</td>
  </tr>
  <tr>
    <td><code>showcase_products</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad details - Ad format</td>
    <td>Disabled</td>
    <td><ul><li><code>ad_format</code></li><li><code>vertical_video_strategy</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad details - Dynamic format</td>
    <td>Disabled</td>
    <td><code>dynamic_format</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Ad details - Ad creative<br></td>
    <td rowspan="2">Disabled<br>The product visuals will be used as your ad creative.</td>
    <td><ul><li><code>video_id</code></li><li><code>image_ids</code></li><li><code>music_id</code></li><li><code>tiktok_item_id</code></li><li><code>promotional_music_disabled</code></li><li><code>item_duet_status </code></li><li><code>item_stitch_status</code></li><li><code>dark_post_status</code></li><li><code>shopping_ads_video_package_id</code></li><li><code>ad_text</code></li><li><code>call_to_action</code></li><li><code> call_to_action_id</code></li><li><code>card_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>creative_type</code></td>
    <td><code>PSA</code> or not passed (recommended)</td>
  </tr>
  <tr>
    <td>Destination</td>
    <td>Disabled</td>
    <td><ul><li><code>landing_page_url</code></li><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code </code></li><li><code>phone_region_calling_code </code></li><li><code>phone_number</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li><li><code>dynamic_destination</code></li><li><code>utm_params</code></li><li><code>instant_product_page_used</code></li><li><code>page_image_index</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disabled</td>
    <td><ul><li><code>disclaimer_type</code></li><li><code>disclaimer_text</code></li><li><code>disclaimer_clickable_texts</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Tracking</td>
    <td>Disabled</td>   	      
	<td><ul><li><code>tracking_pixel_id</code></li><li><code>tracking_app_id</code></li><li><code>tracking_offline_event_set_ids</code></li><li><code>viewability_postbid_partner</code></li><li><code>viewability_vast_url</code></li><li><code>brand_safety_postbid_partner</code></li><li><code>brand_safety_vast_url</code></li><li><code>impression_tracking_url</code></li><li><code>click_tracking_url</code></li></ul></td>
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

### Example
#### All products
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
            "ad_name":"{{ad_name}}",
            "product_specific_type": "ALL"
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
                "viewability_vast_url": null,
                "campaign_name": "{{campaign_name}}",
                "brand_safety_postbid_partner": "UNSET",
                "card_id": null,
                "deeplink": null,
                "music_id": null,
                "is_new_structure": true,
                "is_aco": false,
                "product_specific_type": "ALL",
                "landing_page_url": null,
                "landing_page_urls": null,
                "creative_authorized": false,
                "playable_url": "",
                "click_tracking_url": null,
                "dynamic_format": "UNSET",
                "app_name": null,
                "identity_type": "UNSET",
                "ad_name": "{{ad_name}}",
                "ad_text": "",
                "ad_format": null,
                "vertical_video_strategy": "UNSET",
                "page_id": null,
                "shopping_ads_video_package_id": "",
                "call_to_action_id": null,
                "modify_time": "{{modify_time}}",
                "display_name": null,
                "impression_tracking_url": null,
                "video_id": null,
                "operation_status": "ENABLE",
                "image_ids": [],
                "brand_safety_vast_url": null,
                "dynamic_destination": "UNSET",
                "adgroup_name": "{{adgroup_name}}",
                "avatar_icon_web_uri": "",
                "identity_id": "",
                "ad_texts": null,
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_id": "{{campaign_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": "PSA",
                "vast_moat_enabled": false,
                "catalog_id": "{{catalog_id}}",
                "optimization_event": "SHOPPING",
                "profile_image_url": ""
            }
        ]
    }
}
(/code)
```
#### Specific products
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
            "ad_name":"{{ad_name}}",
            "product_specific_type": "CUSTOMIZED_PRODUCTS",
            "item_group_ids":["{{item_group_id}}","{{item_group_id}}"]
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
                "viewability_vast_url": null,
                "campaign_name": "{{campaign_name}}",
                "brand_safety_postbid_partner": "UNSET",
                "card_id": null,
                "deeplink": null,
                "music_id": null,
                "is_new_structure": true,
                "is_aco": false,
                "product_specific_type": "CUSTOMIZED_PRODUCTS",
                "landing_page_url": null,
                "landing_page_urls": null,
                "creative_authorized": false,
                "playable_url": "",
                "click_tracking_url": null,
                "dynamic_format": "UNSET",
                "app_name": null,
                "identity_type": "UNSET",
                "ad_name": "{{ad_name}}",
                "item_group_ids": [
                    "{{item_group_id}}",
                    "{{item_group_id}}"
                ],
                "ad_text": "",
                "ad_format": null,
                "vertical_video_strategy": "UNSET",
                "page_id": null,
                "shopping_ads_video_package_id": "",
                "call_to_action_id": null,
                "modify_time": "{{modify_time}}",
                "display_name": null,
                "impression_tracking_url": null,
                "video_id": null,
                "operation_status": "ENABLE",
                "image_ids": [],
                "brand_safety_vast_url": null,
                "dynamic_destination": "UNSET",
                "adgroup_name": "{{adgroup_name}}",
                "avatar_icon_web_uri": "",
                "identity_id": "",
                "ad_texts": null,
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_id": "{{campaign_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "viewability_postbid_partner": "UNSET",
                "creative_type": "PSA",
                "vast_moat_enabled": false,
                "catalog_id": "{{catalog_id}}",
                "optimization_event": "SHOPPING",
                "profile_image_url": ""
            }
        ]
    }
}
(/code)
```

# Related docs
- [Create Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942179356738)
- [Advertise using product images from TikTok Shop](https://ads.tiktok.com/help/article/advertise-using-product-images-from-tiktok-shop)
- [About Shopping Center and Search](https://ads.tiktok.com/help/article/about-onsite-shopping-placements?lang=en)

