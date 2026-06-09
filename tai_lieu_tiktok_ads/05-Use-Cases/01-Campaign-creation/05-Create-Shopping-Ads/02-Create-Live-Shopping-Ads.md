---
title: "Create Live Shopping Ads"
doc_id: 1754162402455554
path: "Use Cases / Campaign creation / Create Shopping Ads / Create Live Shopping Ads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Live Shopping Ads

This article introduces how to create Live Shopping Ads (LSA). 

> <span style="color:DarkRed">**Important**</span><br>
> Starting Q3, 2025, [GMV Max](https://business-api.tiktok.com/portal/docs?id=1822009058467842) will be the default and only supported campaign type for TikTok Shop Ads. If you create ads using the Product Sales objective and TikTok Shop as your sales destination, you will no longer be able to use the API to create, edit, or duplicate [LIVE Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1754162402455554), [Product Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1785886237030401), or [Video Shopping Ads](https://business-api.tiktok.com/portal/docs?id=1741942291730434). Please migrate to the [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465) as early as possible. If you have questions or need support, please contact your TikTok representative.  Learn more about [the detailed changes by ad type and endpoint](https://business-api.tiktok.com/portal/docs?id=1837161048383489).


# Supported market
Please see the table below for the available countries or regions that you can use Live Shopping Ads to target.

``` xtable
| Country or region code {20%}| Market {60%}|
|---|---|
| `GB` | United Kingdom |
| `ID` | Indonesia |
| `MY` | Malaysia |
| `PH` | Philippines |
| `SG` | Singapore |
| `TH` | Thailand |
| `US` | United States |
|`VN` | Vietnam |
```

# Steps
## 1. Create a campaign
Create a campaign using [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th style="width:25%">Requirement</th>
    <th style="width:25%">Parameters</th>
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
    <td>Disabled<br><br>You cannot use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362"  target="_blank">/campaign/spc/create/</a> to create a Smart+ Live Shopping campaign.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed.</td>
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
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY</code></li><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_INFINITE</code></li></ul></td>
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
    "campaign_name": "{{campaign_name}}",
    "campaign_product_source":"STORE",
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
        "objective": "LANDING_PAGE",
        "is_smart_performance_campaign": false,
        "deep_bid_type": null,
        "campaign_app_profile_page_state": "UNSET",
        "campaign_product_source": "STORE",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "roas_bid": 0,
        "is_search_campaign": false,
        "campaign_id": "{{campaign_id}}",
        "is_new_structure": true,
        "create_time": "{{create_time}}",
        "operation_status": "ENABLE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "advertiser_id": "{{advertiser_id}}",
        "campaign_name": "{{campaign_name}}",
        "budget": {{budget}},
        "objective_type": "PRODUCT_SALES",
        "campaign_type": "REGULAR_CAMPAIGN",
        "modify_time": "{{modify_time}}"
    }
}
(/code)
```

## 2. (Optional) Create and manage TikTok Shops
> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>If you specify a TikTok Shop, sales of products belonging to the TikTok Shop will be reported. </li><li>If you don't specify a TikTok Shop, sales of products showcased in the TikTok account will be reported. Skip to <a href="#item-link-3.  Create an ad group">Step 3</a> if you don't want to specify a TikTok Shop for your LSA.</li></ul>

TikTok Commerce offers TikTok Shops for brands and sellers who want to sell their products directly on their TikTok account. 

You can set up TikTok Shop by creating TikTok Shops on TikTok Seller Center. For detailed instructions on how to create a TikTok Shop, see [Set Up TikTok Shop Using TikTok Seller Center](https://ads.tiktok.com/help/article?aid=10005127).

After you have created a TikTok Shop, follow the procedures below:
- i. Add the TikTok Shop to the Business Center. To learn about how to request TikTok Shop access from a Business Center, see [here](https://ads.tiktok.com/help/article?aid=10012166). 
- ii. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505). You can also choose `advertiser_role` that you want to assign to the members invited.
- iii. (Optional) Share a TikTok Shop with members and grant the TikTok Shop management access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). Make sure to specify `TIKTOK_SHOP` in the `asset_type` field and `AD_PROMOTION` in the `store_role` field.

## 3.  Create an ad group
Create an ad group using [/adgroup/create/](https://ads.tiktok.com/marketing_api/docs?id=1739499616346114). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:17%">Setting</th>
    <th style="width:24%">Requirement</th>
    <th style="width:29%">Parameters</th>
    <th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Shopping ads type</td>
    <td>LSA</td>
    <td><code>shopping_ads_type</code></td>
    <td><code>LIVE</code></td>
  </tr>
  <tr>
    <td rowspan="3">Identity</td>
    <td rowspan="3">Select the TikTok account that you'll use to go LIVE as your identity. Your ad will start running when a LIVE begins on this account during the scheduled ad time.</td>
    <td><code>identity_type</code></td>
    <td>Any of the following values:<ul><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: For <code>BC_AUTH_TT</code>, you need to have the permission <strong>ad delivery with existing posts</strong> from the TikTok account.</td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Specify the TikTok account that you'll use to go LIVE.<br><br>To get the list of identities under an ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057"  target="_blank">/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
  </tr>
  <tr>
    <td>Product source</td>
    <td>Disabled</td>
    <td><code>product_source</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Shop (Optional)</td>
    <td rowspan="2">Specified or not specified<br><br><ul><li>If you specify a TikTok Shop, sales of products belonging to the TikTok Shop will be reported.<br> To learn about how to manage TikTok Shops, see <a href="#item-link-2. (Optional) Create and manage TikTok Shops">Step 2</a>.</li><li>If you don't specify a TikTok Shop, sales of products showcased in the TikTok account will be reported.</li></ul></td>
    <td><ul><li><code>store_id</code></li><li><code>store_authorized_bc_id</code></li><li><code>catalog_id</code></li></ul></td>
    <td>Pass valid values or not passed<br>You can use <a href="https://ads.tiktok.com/marketing_api/docs?id=1752267762718722" target="_blank">/store/list/</a> to get the <code>store_id</code>, <code>store_authorized_bc_id</code>, and <code>catalog_id</code> for available stores under an ad account.<br><br><p><span style="color:darkred"><b>Note</b></span>: Starting June 30th, 2024, you will no longer need to pass <code>catalog_id</code> because it will be ignored.</td>
  </tr>
  <tr>
    <td><code>catalog_authorized_bc_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Promotion type <br>(Optimization location)</td>
    <td>Unset.<br>The default setting is LIVE content.</td>
    <td><code>promotion_type</code></td>
    <td><code>LIVE_SHOPPING</code></td>
  </tr>
  <tr>
    <td>Promoted website or App</td>
    <td>Disabled</td>
    <td><ul><li><code>app_id</code></li><li><code>pixel_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Placement</td>
    <td rowspan="2"><strong>Select Placement</strong> with TikTok placement only</td>
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
    <td><ul><li><code>shopping_ads_retargeting_type</code></li><li><code>shopping_ads_retargeting_actions_days </code></li><li><code>included_custom_actions</code></li><li><code>excluded_custom_actions</code></li><li><code>shopping_ads_retargeting_custom_audience_relation</code></li></ul></td>
    <td>Not passed</td>
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
    <td>Audience targeting <ul><li>Audience<ul><li>Smart audience</li></ul></li></ul></td>
    <td>Disabled</td>
    <td><code>smart_audience_enabled</code></td>
    <td>Not passed<br></td>
  </tr>
  <tr>
    <td>Audience targeting <ul><li>Interests & Behaviors <ul><li>Smart interests & behaviors</li></ul></li></ul></td>
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
    <td rowspan="2">Content exclusions <ul><li>Inventory filter</li></ul> </td>
    <td rowspan="2">Supported with allowlist</td>
    <td><code>brand_safety_type</code></td>
    <td>Pass a valid value (<code>EXPANDED_INVENTORY</code>, <code>STANDARD_INVENTORY</code>, or <code>LIMITED_INVENTORY</code>) or not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: TikTok inventory filter in LSA is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td><code>brand_safety_partner</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions <ul><li>Category exclusions</li></ul> </td>
    <td>Disabled</td>
    <td><code>category_exclusion_ids</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Content exclusions <ul><li>Vertical sensitivity control</li></ul></td>
    <td>Disabled</td>
    <td><code>vertical_sensitivity_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Schedule</td>
    <td>Define a schedule that closely aligns with the actual live duration to ensure optimal ad delivery.<br>Note that ads will only run when the seller is live during the specified period.<ul><li>For example, if a campaign is scheduled to run from 1 PM to 11 PM today, and the seller livestreams between 2 PM and 4 PM, as well as 10 PM and 11 PM, the campaign will only be delivered during these specific time slots: 2-4 PM and 10-11 PM.</li></ul></td>
    <td><ul><li><code>schedule_type</code></li><li><code> schedule_start_time</code></li><li><code>schedule_end_time</code></li></ul></td>
    <td>Specify a schedule that closely aligns with the actual live duration</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following types:<ul><li>Gross revenue (recommended)</li><li>Purchases (recommended)</li><li>Initiate checkouts</li><li>Product clicks in LIVE</li><li>Viewer retention</li><li>Clicks</li></ul></td>
    <td><ul><li><code>optimization_goal</code></li><li><code>optimization_event</code></li></ul></td>
    <td>Any of the following settings:<ul><li>Set <code>optimization_goal</code> to <code>VALUE</code> and <code>optimization_event</code> to <code>SHOPPING</code>.</li><li> Set <code>optimization_goal</code> to <code>CONVERT</code> and <code>optimization_event</code> to <code>SHOPPING</code>.</li><li>Set <code>optimization_goal</code> to <code>CONVERT</code> and <code>optimization_event</code> to <code>INITIATE_ORDER</code>.</li><li> Set <code>optimization_goal</code> to <code>PRODUCT_CLICK_IN_LIVE</code> and <code>optimization_event</code> to <code>LIVE_CLICK_PRODUCT_ACTION</code>.</li><li> Set <code>optimization_goal</code> to <code>MT_LIVE_ROOM</code> and <code>optimization_event</code> to <code>LIVE_STAY_TIME</code>.</li><li>Set <code>optimization_goal</code> to <code>CLICK</code>.</li></ul>  </td>
  </tr>
  <tr>
    <td>Frequency cap</td>
    <td>Disabled <br></td>
    <td><ul><li><code>frequency</code></li><li><code>frequency_schedule</code></li></ul> </td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td><ul><li>If optimization goal is Purchases, Initiate checkouts, Product clicks in LIVE, Viewer retention, or Clicks, the strategy can be any of the following options:  <ul><li><b>Maximum Delivery</b>: Aim to spend your entire budget to get the most results. </li><li><b>Cost Cap</b> or <b>Target CPA</b>: Keep the average cost per result around the stated amount.</li></ul></li><li>If optimization goal is Gross revenue, the strategy can be any of the following options: <ul><li><b>Highest Gross Revenue</b>: Aim to spend your entire budget to get the most gross revenue. </li><li><b>Target ROAS</b>: Keep the average ROAS around the stated amount. </li></ul></li></ul></td>
    <td><ul><li><code>bid_type</code></li><li><code>bid_price</code></li><li><code>conversion_bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>, <code>PRODUCT_CLICK_IN_LIVE</code>, <code>MT_LIVE_ROOM</code>, or <code>CLICK</code>: <ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not pass <code>deep_bid_type</code> and <code>roas_bid</code>.  <ul><li> If <code>optimization_goal</code> is <code>CONVERT</code>, <code>PRODUCT_CLICK_IN_LIVE</code> or <code>MT_LIVE_ROOM</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> (Target CPA), pass <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code> (Cost Cap), pass <code>bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>: <ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, pass in <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul></td>
  </tr>
  <tr>
    <td rowspan="3">Attribution settings<br>(supported only if optimization goal is Gross revenue, Purchases, Initiate checkouts, Product clicks in LIVE, or Viewer retention)<br></td>
    <td><ul><li>If optimization goal is Gross revenue, Purchases, or Initiate checkouts, the supported Click-through windows are:<ul><li>1-day click</li><li>7-day click</li><li>14-day click</li><li>28-day click</li></ul></li><li>If optimization goal is Product clicks in LIVE, or Viewer retention, the supported Click-through window is 7-day click.</li></ul></td>
    <td><code>click_attribution_window</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>, the supported values are: <ul><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li><li><code>FOURTEEN_DAYS</code></li><li><code>TWENTY_EIGHT_DAYS</code></li></ul></li><li>If <code>optimization_goal</code> is <code>PRODUCT_CLICK_IN_LIVE</code> or <code>MT_LIVE_ROOM</code>, the supported value is <code>SEVEN_DAYS</code>.</li></ul></td>
  </tr>
  <tr>
    <td><ul><li> If optimization goal is Gross revenue, Purchases, or Initiate checkouts, the supported View-through windows are:<ul><li>Off</li><li>1-day view</li><li>7-day view</li></ul> </li><li>If optimization goal is Product clicks in LIVE, or Viewer retention, the supported View-through window is 1-day view.</li></ul></td>
    <td><code>view_attribution_window</code></td>
    <td><ul><li> If <code>optimization_goal</code> is <code>VALUE</code> or <code>CONVERT</code>, the supported values are:<ul><li><code>OFF</code></li><li><code>ONE_DAY</code></li><li><code>SEVEN_DAYS</code></li></ul> </li><li>If <code>optimization_goal</code> is <code>PRODUCT_CLICK_IN_LIVE</code> or <code>MT_LIVE_ROOM</code>, the supported value is <code>ONE_DAY</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Event count: Once.</td>
    <td><code>attribution_event_count</code></td>
    <td><code>ONCE</code> or not passed</td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Clicks, the billing event should be CPC.</li><li>If optimization goal is Gross revenue, Purchases, Initiate checkouts, Product clicks in LIVE, or Viewer retention, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li> If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>VALUE</code>,<code>CONVERT</code>, <code>PRODUCT_CLICK_IN_LIVE</code>, or <code>MT_LIVE_ROOM</code>, set this field to <code>OCPM</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Delivery Type</td>
    <td><ul><li>If Bid Strategy is set to Cost Cap, you can set Delivery Type to Standard or Accelerated.</li><li>If Bid Strategy is set to Maximum Delivery, Highest Value or Minimum ROAS, you can only set Delivery Type to Standard.</li></ul></td>
    <td><code>pacing</code></td>
    <td><ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, you can set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code> or <code>PACING_MODE_FAST</code>.</li><li>If <code>bid_type</code> is <code>BID_TYPE_NO_BID</code>, you can only set <code>pacing</code> to <code>PACING_MODE_SMOOTH</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Automated Creative Optimization (ACO)</td>
    <td>Disabled</td>
    <td><code>creative_material_mode</code></td>
    <td><code>CUSTOM</code> or not passed</td>
  </tr>
  <tr>
    <td>Split test</td>
    <td>Disabled<br><br>Live Shopping ad groups cannot be used in <a href="https://business-api.tiktok.com/portal/docs?id=1742666471475201"  target="_blank">/split_test/create/</a> to create split tests.</td>
    <td>/</td>
    <td>/</td>
  </tr>
</tbody>
</table>



### Example
#### Optimization goal as Gross revenue  (with TikTok Shop specified)
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "store_id":"{{store_id}}",
    "store_authorized_bc_id":"{{store_authorized_bc_id}}",
    "catalog_id":"{{catalog_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    <span style="background-color: green;">"optimization_goal": "VALUE",
    "optimization_event": "SHOPPING",</span>
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_MIN_ROAS",
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
        "operating_systems": [],
        "campaign_name": "{{campaign_name}}",
        "billing_event": "OCPM",
        "schedule_type": "SCHEDULE_START_END",
        "frequency_schedule": null,
        "store_id": "{{store_id}}",
        "rf_purchased_type": null,
        "smart_audience_enabled": null,
        "isp_ids": [],
        "adgroup_name": "{{adgroup_name}}",
        "age_groups": null,
        "purchased_reach": null,
        "identity_id": "{{identity_id}}",
        "adgroup_app_profile_page_state": null,
        "create_time": "{{create_time}}",
        "category_exclusion_ids": [],
        "budget": {{budget}},
        "attribution_event_count": "ONCE",
        "ios14_quota_type": "UNOCCUPIED",
        "deep_bid_type": "VO_MIN_ROAS",
        "location_ids": [
            "1643084"
        ],
        "video_download_disabled": false,
        "is_hfss": false,
        "search_result_enabled": false,
        "app_type": null,
        "next_day_retention": null,
        "catalog_id": "{{catalog_id}}",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "rf_estimated_frequency": null,
        "statistic_type": null,
        "click_attribution_window": "SEVEN_DAYS",
        "shopping_ads_type": "LIVE",
        "feed_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "audience_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "optimization_event": "SHOPPING",
        "deep_cpa_bid": 0,
        "bid_type": "BID_TYPE_NO_BID",
        "languages": [],
        "excluded_audience_ids": [],
        "gender": "GENDER_UNLIMITED",
        "promotion_type": "LIVE_SHOPPING",
        "conversion_bid_price": 0,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "schedule_infos": null,
        "inventory_filter_enabled": false,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "conversion_window": null,
        "is_new_structure": true,
        "interest_keyword_ids": [],
        "rf_estimated_cpr": null,
        "brand_safety_partner": null,
        "delivery_mode": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "store_authorized_bc_id": "{{store_authorized_bc_id}}",
        "view_attribution_window": "ONE_DAY",
        "campaign_id": "{{campaign_id}}",
        "app_download_url": null,
        "creative_material_mode": "CUSTOM",
        "secondary_optimization_event": null,
        "smart_interest_behavior_enabled": null,
        "network_types": [],
        "bid_display_mode": "CPMV",
        "schedule_start_time": "{{schedule_start_time}}",
        "scheduled_budget": 0,
        "category_id": "0",
        "adgroup_id": "{{adgroup_id}}",
        "comment_disabled": false,
        "pacing": "PACING_MODE_SMOOTH",
        "purchased_impression": null,
        "product_source": "UNSET",
        "is_smart_performance_campaign": false,
        "interest_category_ids": [],
        "skip_learning_phase": true,
        "identity_type": "TT_USER",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "share_disabled": false,
        "bid_price": 0,
        "modify_time": "{{modify_time}}",
        "actions": [],
        "frequency": null,
        "optimization_goal": "VALUE",
        "roas_bid": {{roas_bid}},
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "keywords": null,
        "pixel_id": null,
        "device_price_ranges": null,
        "operation_status": "ENABLE",
        "app_id": null,
        "auto_targeting_enabled": false
    }
}
(/code)
```

#### Optimization goal as Purchases
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    <span style="background-color: green;">"optimization_goal": "CONVERT",
    "optimization_event": "SHOPPING",</span>
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
        "skip_learning_phase": true,
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY",
        "category_id": "0",
        "deep_bid_type": null,
        "share_disabled": false,
        "comment_disabled": false,
        "ios14_quota_type": "UNOCCUPIED",
        "modify_time": "{{modify_time}}",
        "keywords": null,
        "feed_type": null,
        "age_groups": null,
        "isp_ids": [],
        "next_day_retention": null,
        "identity_type": "TT_USER",
        "bid_price": 0,
        "excluded_audience_ids": [],
        "schedule_start_time": "{{schedule_start_time}}",
        "budget": {{budget},
        "campaign_name": "{{campaign_name}}",
        "purchased_impression": null,
        "app_type": null,
        "adgroup_id": "{{adgroup_id}}",
        "inventory_filter_enabled": false,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "conversion_window": null,
        "app_download_url": null,
        "search_result_enabled": false,
        "click_attribution_window": "SEVEN_DAYS",
        "operation_status": "ENABLE",
        "optimization_event": "SHOPPING",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "product_source": "UNSET",
        "is_smart_performance_campaign": false,
        "is_new_structure": true,
        "deep_cpa_bid": 0,
        "promotion_type": "LIVE_SHOPPING",
        "category_exclusion_ids": [],
        "scheduled_budget": 0,
        "languages": [],
        "rf_estimated_cpr": null,
        "smart_audience_enabled": null,
        "app_id": null,
        "advertiser_id": "{{advertiser_id}}",
        "network_types": [],
        "smart_interest_behavior_enabled": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "attribution_event_count": "ONCE",
        "rf_purchased_type": null,
        "gender": "GENDER_UNLIMITED",
        "conversion_bid_price": {{conversion_bid_price}},
        "campaign_id": "{{campaign_id}}",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "secondary_optimization_event": null,
        "location_ids": [
            "1643084"
        ],
        "interest_keyword_ids": [],
        "identity_id": "{{identity_id}}",
        "pacing": "PACING_MODE_SMOOTH",
        "create_time": "{{create_time}}",
        "statistic_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "frequency_schedule": null,
        "interest_category_ids": [],
        "schedule_infos": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "delivery_mode": null,
        "brand_safety_partner": null,
        "creative_material_mode": "CUSTOM",
        "audience_ids": [],
        "adgroup_name": "{{adgroup_name}}",
        "bid_display_mode": "CPMV",
        "optimization_goal": "CONVERT",
        "bid_type": "BID_TYPE_CUSTOM",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "rf_estimated_frequency": null,
        "device_price_ranges": null,
        "is_hfss": false,
        "purchased_reach": null,
        "pixel_id": null,
        "actions": [],
        "billing_event": "OCPM",
        "schedule_type": "SCHEDULE_START_END",
        "shopping_ads_type": "LIVE",
        "auto_targeting_enabled": false,
        "adgroup_app_profile_page_state": null,
        "frequency": null,
        "operating_systems": []
    }
}
(/code)
```
#### Optimization goal as Initiate checkouts
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
     <span style="background-color: green;">"optimization_goal": "CONVERT",
    "optimization_event": "INITIATE_ORDER",</span>
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
        "view_attribution_window": "ONE_DAY",
        "category_id": "0",
        "operation_status": "ENABLE",
        "advertiser_id": "{{advertiser_id}}",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "search_result_enabled": false,
        "frequency": null,
        "schedule_infos": null,
        "purchased_impression": null,
        "adgroup_name": "{{adgroup_name}}",
        "product_source": "UNSET",
        "secondary_optimization_event": null,
        "is_new_structure": true,
        "category_exclusion_ids": [],
        "brand_safety_partner": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "location_ids": [
            "1643084"
        ],
        "billing_event": "OCPM",
        "languages": [],
        "operating_systems": [],
        "schedule_type": "SCHEDULE_START_END",
        "audience_ids": [],
        "pacing": "PACING_MODE_SMOOTH",
        "shopping_ads_type": "LIVE",
        "share_disabled": false,
        "feed_type": null,
        "ios14_quota_type": "UNOCCUPIED",
        "device_price_ranges": null,
        "rf_estimated_cpr": null,
        "conversion_window": null,
        "smart_interest_behavior_enabled": null,
        "click_attribution_window": "SEVEN_DAYS",
        "budget": {{budget}},
        "app_id": null,
        "adgroup_app_profile_page_state": null,
        "isp_ids": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deep_cpa_bid": 0,
        "is_smart_performance_campaign": false,
        "auto_targeting_enabled": false,
        "smart_audience_enabled": null,
        "optimization_event": "INITIATE_ORDER",
        "schedule_end_time": "{{schedule_end_time}}",
        "adgroup_id": "{{adgroup_id}}",
        "frequency_schedule": null,
        "actions": [],
        "rf_purchased_type": null,
        "deep_bid_type": null,
        "is_hfss": false,
        "scheduled_budget": 0,
        "age_groups": null,
        "inventory_filter_enabled": false,
        "keywords": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "delivery_mode": null,
        "skip_learning_phase": true,
        "interest_category_ids": [],
        "network_types": [],
        "comment_disabled": false,
        "purchased_reach": null,
        "bid_price": 0,
        "app_download_url": null,
        "gender": "GENDER_UNLIMITED",
        "statistic_type": null,
        "promotion_type": "LIVE_SHOPPING",
        "creative_material_mode": "CUSTOM",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "conversion_bid_price":{{conversion_bid_price}}},
        "campaign_id": "{{campaign_id}}",
        "identity_type": "TT_USER",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "bid_display_mode": "CPMV",
        "excluded_audience_ids": [],
        "bid_type": "BID_TYPE_CUSTOM",
        "attribution_event_count": "ONCE",
        "create_time": "{{create_time}}",
        "video_download_disabled": false,
        "identity_id": "{{identity_id}}",
        "pixel_id": null,
        "app_type": null,
        "campaign_name": "{{campaign_name}}",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "modify_time": "{{modify_time}}",
        "optimization_goal": "CONVERT",
        "interest_keyword_ids": [],
        "rf_estimated_frequency": null,
        "next_day_retention": null
    }
}
(/code)
```
#### Optimization goal as Product clicks in LIVE
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
     <span style="background-color: green;">"optimization_goal": "PRODUCT_CLICK_IN_LIVE",
    "optimization_event": "LIVE_CLICK_PRODUCT_ACTION",</span>
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
        "campaign_id": "{{campaign_id}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "purchased_reach": null,
        "secondary_optimization_event": null,
        "pacing": "PACING_MODE_SMOOTH",
        "app_type": null,
        "device_price_ranges": null,
        "bid_display_mode": "CPMV",
        "creative_material_mode": "CUSTOM",
        "rf_estimated_frequency": null,
        "scheduled_budget": 0,
        "delivery_mode": null,
        "deep_bid_type": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "ios14_quota_type": "UNOCCUPIED",
        "next_day_retention": null,
        "schedule_type": "SCHEDULE_START_END",
        "video_download_disabled": false,
        "excluded_audience_ids": [],
        "is_new_structure": true,
        "conversion_window": null,
        "app_download_url": null,
        "languages": [],
        "interest_category_ids": [],
        "age_groups": null,
        "click_attribution_window": "SEVEN_DAYS",
        "is_hfss": false,
        "brand_safety_partner": null,
        "network_types": [],
        "view_attribution_window": "ONE_DAY",
        "optimization_goal": "PRODUCT_CLICK_IN_LIVE",
        "bid_price": 0,
        "schedule_infos": null,
        "smart_interest_behavior_enabled": null,
        "product_source": "UNSET",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "frequency_schedule": null,
        "comment_disabled": false,
        "modify_time": "{{modify_time}}",
        "adgroup_id": "{{adgroup_id}}",
        "rf_estimated_cpr": null,
        "statistic_type": null,
        "smart_audience_enabled": null,
        "feed_type": null,
        "auto_targeting_enabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "keywords": null,
        "attribution_event_count": "ONCE",
        "budget_mode": "BUDGET_MODE_TOTAL",
        "skip_learning_phase": true,
        "adgroup_name": "{{adgroup_name}}",
        "adgroup_app_profile_page_state": null,
        "operating_systems": [],
        "budget": {{budget}},
        "inventory_filter_enabled": false,
        "shopping_ads_type": "LIVE",
        "gender": "GENDER_UNLIMITED",
        "pixel_id": null,
        "identity_id": "{{identity_id}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "isp_ids": [],
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "app_id": null,
        "billing_event": "OCPM",
        "promotion_type": "LIVE_SHOPPING",
        "search_result_enabled": false,
        "identity_type": "TT_USER",
        "conversion_bid_price": {{conversion_bid_price}},
        "rf_purchased_type": null,
        "frequency": null,
        "location_ids": [
            "1643084"
        ],
        "audience_ids": [],
        "operation_status": "ENABLE",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "category_id": "0",
        "is_smart_performance_campaign": false,
        "deep_cpa_bid": 0,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "optimization_event": "LIVE_CLICK_PRODUCT_ACTION",
        "purchased_impression": null,
        "actions": [],
        "share_disabled": false,
        "category_exclusion_ids": [],
        "interest_keyword_ids": [],
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "advertiser_id": "{{advertiser_id}}"
    }
}
(/code)
```
#### Optimization goal as Viewer retention
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    <span style="background-color: green;">"optimization_goal": "MT_LIVE_ROOM",
    "optimization_event": "LIVE_STAY_TIME",</span>
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
        "attribution_event_count": "ONCE",
        "bid_price": 0,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "rf_purchased_type": null,
        "inventory_filter_enabled": false,
        "pixel_id": null,
        "schedule_infos": null,
        "optimization_goal": "MT_LIVE_ROOM",
        "operating_systems": [],
        "network_types": [],
        "auto_targeting_enabled": false,
        "click_attribution_window": "SEVEN_DAYS",
        "keywords": null,
        "is_hfss": false,
        "purchased_impression": null,
        "delivery_mode": null,
        "is_new_structure": true,
        "app_download_url": null,
        "share_disabled": false,
        "brand_safety_partner": null,
        "feed_type": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "languages": [],
        "budget_mode": "BUDGET_MODE_TOTAL",
        "location_ids": [
            "1643084"
        ],
        "interest_category_ids": [],
        "smart_audience_enabled": null,
        "is_smart_performance_campaign": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "next_day_retention": null,
        "optimization_event": "LIVE_STAY_TIME",
        "category_id": "0",
        "pacing": "PACING_MODE_SMOOTH",
        "bid_display_mode": "CPMV",
        "brand_safety_type": "NO_BRAND_SAFETY",
        "scheduled_budget": 0,
        "device_price_ranges": null,
        "advertiser_id": "{{advertiser_id}}",
        "frequency_schedule": null,
        "promotion_type": "LIVE_SHOPPING",
        "interest_keyword_ids": [],
        "adgroup_app_profile_page_state": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "search_result_enabled": false,
        "creative_material_mode": "CUSTOM",
        "video_download_disabled": false,
        "modify_time": "{{modify_time}}",
        "deep_bid_type": null,
        "rf_estimated_cpr": null,
        "excluded_audience_ids": [],
        "app_type": null,
        "view_attribution_window": "ONE_DAY",
        "conversion_window": null,
        "actions": [],
        "statistic_type": null,
        "adgroup_name": "{{adgroup_name}}",
        "billing_event": "OCPM",
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "ios14_quota_type": "UNOCCUPIED",
        "app_id": null,
        "purchased_reach": null,
        "conversion_bid_price": {{conversion_bid_price}},
        "category_exclusion_ids": [],
        "comment_disabled": false,
        "audience_ids": [],
        "frequency": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "secondary_optimization_event": null,
        "gender": "GENDER_UNLIMITED",
        "campaign_name": "{{campaign_name}}",
        "isp_ids": [],
        "identity_type": "TT_USER",
        "adgroup_id": "{{adgroup_id}}",
        "shopping_ads_type": "LIVE",
        "budget": {{budget}},
        "skip_learning_phase": true,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "product_source": "UNSET",
        "create_time": "{{create_time}}",
        "identity_id": "{{identity_id}}",
        "operation_status": "ENABLE",
        "schedule_type": "SCHEDULE_START_END",
        "campaign_id": "{{campaign_id}}",
        "age_groups": null,
        "smart_interest_behavior_enabled": null,
        "rf_estimated_frequency": null
    }
}
(/code)
```
#### Optimization goal as Clicks
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "shopping_ads_type": "LIVE", 
    "identity_type": "TT_USER", 
    "identity_id": "{{identity_id}}",
    "promotion_type": "LIVE_SHOPPING",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "1643084"
    ],
    "budget_mode": "BUDGET_MODE_TOTAL",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    <span style="background-color: green;">"optimization_goal": "CLICK",</span>
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
        "brand_safety_type": "NO_BRAND_SAFETY",
        "deep_bid_type": null,
        "smart_audience_enabled": null,
        "statistic_type": null,
        "interest_category_ids": [],
        "app_download_url": null,
        "audience_ids": [],
        "advertiser_id": "{{advertiser_id}}",
        "search_result_enabled": false,
        "network_types": [],
        "creative_material_mode": "CUSTOM",
        "ios14_quota_type": "UNOCCUPIED",
        "product_source": "UNSET",
        "secondary_optimization_event": null,
        "gender": "GENDER_UNLIMITED",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "schedule_end_time": "{{schedule_end_time}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "create_time": "{{create_time}}",
        "bid_display_mode": "CPMV",
        "languages": [],
        "optimization_event": null,
        "next_day_retention": null,
        "inventory_filter_enabled": false,
        "frequency": null,
        "optimization_goal": "CLICK",
        "excluded_audience_ids": [],
        "app_id": null,
        "promotion_type": "LIVE_SHOPPING",
        "is_smart_performance_campaign": false,
        "conversion_window": null,
        "pacing": "PACING_MODE_SMOOTH",
        "rf_estimated_cpr": null,
        "campaign_name": "{{campaign_name}}",
        "rf_estimated_frequency": null,
        "age_groups": null,
        "adgroup_app_profile_page_state": null,
        "scheduled_budget": 0,
        "purchased_reach": null,
        "video_download_disabled": false,
        "bid_type": "BID_TYPE_CUSTOM",
        "frequency_schedule": null,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "schedule_infos": null,
        "device_price_ranges": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "actions": [],
        "is_new_structure": true,
        "comment_disabled": false,
        "auto_targeting_enabled": false,
        "identity_type": "TT_USER",
        "conversion_bid_price": 0,
        "share_disabled": false,
        "budget": {{budget}},
        "schedule_type": "SCHEDULE_START_END",
        "deep_cpa_bid": 0,
        "identity_id": "{{identity_id}}",
        "pixel_id": null,
        "purchased_impression": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "feed_type": null,
        "category_exclusion_ids": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "brand_safety_partner": null,
        "interest_keyword_ids": [],
        "billing_event": "CPC",
        "adgroup_name": "{{adgroup_name}}",
        "keywords": null,
        "isp_ids": [],
        "category_id": "0",
        "skip_learning_phase": false,
        "smart_interest_behavior_enabled": null,
        "delivery_mode": null,
        "budget_mode": "BUDGET_MODE_TOTAL",
        "is_hfss": false,
        "operating_systems": [],
        "app_type": null,
        "shopping_ads_type": "LIVE",
        "bid_price": {{bid_price}},
        "location_ids": [
            "1643084"
        ],
        "adgroup_id": "{{adgroup_id}}",
        "campaign_id": "{{campaign_id}}",
        "rf_purchased_type": null
    }
}
(/code)
```



## 4. Create an ad
Create an ad using [/ad/create/](https://ads.tiktok.com/marketing_api/docs?id=1739953377508354). Note that the following requirements must be met.

<table>
<thead>
  <tr>
    <th style="width:20%">Setting</th>
    <th style="width:25%">Requirement</th>
    <th style="width:25%">Parameters</th>
    <th style="width:30%">How to configure the parameters</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative</td>
    <td>Disabled<br><br>You cannot pass <code>is_smart_creative</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626"  target="_blank">/ad/aco/create/</a> to create Smart Creative LSA.</td>
    <td>/</td>
    <td>/</td>
  </tr>
  <tr>
    <td>Identity</td>
    <td>The same TikTok account that you've selected to go LIVE at the ad group level</td>
    <td><ul><li><code>identity_type</code></li><li><code>identity_id</code></li><li><code>identity_authorized_bc_id</code></li></ul></td>
    <td>Set these parameters to the same values at the ad group level</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Disabled</td>
    <td><ul><li><code>product_specific_type</code></li><li><code>item_group_ids</code></li><li><code> product_set_id </code></li><li><code>sku_ids</code></li><li><code>showcase_products</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="2">Ad details - Ad format</td>
    <td rowspan="2">Any of the following options:<ul><li>Single video: Users will be directed to the liveroom when they click the "Watch LIVE" button, the auto-generated product card (if any), or the profile icon.</li><li>Real-time LIVE: Users will be directed to the liveroom when they click on the LIVE creative.</li></ul></td>
    <td><code>ad_format</code></td>
    <td><code>LIVE_CONTENT</code></td>
  </tr>
  <tr>
    <td><ul><li><code>creative_type</code></li><li><code>vertical_video_strategy</code></li></ul></td>
    <td><ul><li>To set Ad format as Single video, set <code>creative_type</code> to <code>SHORT_VIDEO_LIVE</code> and <code>vertical_video_strategy</code> to <code>SINGLE_VIDEO</code>.</li><li>To set Ad format as Real-time LIVE, set <code>creative_type</code> to <code>DIRECT_LIVE</code> and <code>vertical_video_strategy</code> to <code>LIVE_STREAM</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Ad details - Dynamic format</td>
    <td>Disabled</td>
    <td><code>dynamic_format</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="3">Ad details - Ad creative<br></td>
    <td rowspan="3"><ul><li>If Ad format is Single video, specify a video or select a TikTok post to use from all public posts published by the designated TikTok Account, and the call-to-action Watch LIVE.<br><p><span style="color:darkred"><b>Note</b></span>: One ad group can support up to 20 ads with the ad format Single video.</li><li>If Ad format is Real-time LIVE, the LIVE content will be used as your ad creative.<br><p><span style="color:darkred"><b>Note</b></span>: One ad group can support only one ad with the ad format Real-time LIVE.</li></ul></td>
    <td><ul><li><code>video_id</code></li><li><code>image_ids</code></li><li><code>ad_text</code></li><li><code>tiktok_item_id</code></li><li><code>dark_post_status</code></li></ul></td>
    <td><ul><li>If <code>creative_type</code> is <code>SHORT_VIDEO_LIVE</code>, you can create the ad through Spark Ads PULL or Spark Ads PUSH:<ul><li>Spark Ads PUSH: Specify the video (<code>video_id</code>), video cover (<code>image_ids</code>), and ad text (<code>ad_text</code>) . You can set <code>dark_post_status</code> to <code>ON</code> to ensure the video does not organically appear on the TikTok Account. Do not <code>pass tiktok_item_id</code>.</li><li>Spark Ads PULL: Specify the TikTok post through <code>tiktok_item_id</code>. Do not pass <code>video_id</code>, <code>image_ids</code>, <code>ad_text</code>, and <code>dark_post_status</code>.<br>  To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298"  target="_blank">Create Spark Ads</a>. </li></ul></li><li>If <code>creative_type</code> is <code>DIRECT_LIVE</code>, these parameters are not supported.</li></ul></td>
  </tr>
  <tr>
    <td><code>call_to_action</code></td>
    <td><ul><li>If <code>creative_type</code> is <code>SHORT_VIDEO_LIVE</code>, set this field to <code>WATCH_LIVE</code>.</li><li>If <code>creative_type</code> is <code>DIRECT_LIVE</code>, omit this field or assign a null value to this field.</li></ul></td>
  </tr>
  <tr>
    <td><ul><li><code> promotional_music_disabled</code></li><li><code>item_duet_status</code></li><li><code>item_stitch_status</code></li><li><code>music_id</code></li><li><code>shopping_ads_video_package_id</code></li><li><code>call_to_action_id</code></li></ul></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Ad details - Interactive add-on</td>
    <td>Disabled<br><br><ul><li>If a pinned product is present in your liveroom, an automatically generated Product Card showcasing the pinned product will appear after two seconds. Users can click on the Product Card to enter the live shopping stream with the product detail page overlay.</li><li>If there is no pinned product or if the pinned product is sold out, no Product Card will be shown.</li></ul></td>
    <td><code>card_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Destination</td>
    <td>Disabled</td>
    <td><ul><li><code>landing_page_url</code></li><li><code>page_id</code></li><li><code>tiktok_page_category</code></li><li><code>phone_region_code</code></li><li><code>phone_region_calling_code </code></li><li><code>phone_number</code></li><li><code>deeplink</code></li><li><code>deeplink_type</code></li><li><code>shopping_ads_deeplink_type</code></li><li><code>shopping_ads_fallback_type</code></li><li><code>fallback_type</code></li><li><code>dynamic_destination</code></li><li><code>utm_params</code></li><li><code>instant_product_page_used</code></li><li><code>page_image_index</code></li></ul></td>
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

#### Ad format as Real-time LIVE
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "ad_format": "LIVE_CONTENT",
            <span style="background-color: green;">"creative_type": "DIRECT_LIVE",
            "vertical_video_strategy": "LIVE_STREAM"</span>
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
                "campaign_name": "{{campaign_name}}",
                "ad_id": "{{ad_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "identity_id": "{{identity_id}}",
                "ad_text": "",
                "call_to_action_id": null,
                "app_name": "{{app_name}}",
                "campaign_id": "{{campaign_id}}",
                "shopping_ads_video_package_id": "",
                "operation_status": "ENABLE",
                "is_new_structure": true,
                "display_name": "{{display_name}}",
                "ad_name": "{{ad_name}}",
                "avatar_icon_web_uri": "",
                "creative_type": "DIRECT_LIVE",
                "is_aco": false,
                "identity_type": "TT_USER",
                "adgroup_name": "{{adgroup_name}}",
                "ad_format": "LIVE_CONTENT",
                "deeplink": null,
                "music_id": null,
                "product_specific_type": "UNSET",
                "impression_tracking_url": null,
                "landing_page_urls": null,
                "adgroup_id": "{{adgroup_id}}",
                "profile_image_url": "",
                "click_tracking_url": null,
                "viewability_postbid_partner": "UNSET",
                "viewability_vast_url": null,
                "landing_page_url": null,
                "video_id": null,
                "creative_authorized": false,
                "brand_safety_postbid_partner": "UNSET",
                "vertical_video_strategy": "LIVE_STREAM",
                "optimization_event": "LIVE_STAY_TIME",
                "image_ids": [],
                "dynamic_destination": "UNSET",
                "create_time": "{{create_time}}",
                "card_id": null,
                "brand_safety_vast_url": null,
                "modify_time": "{{modify_time}}",
                "ad_texts": null,
                "playable_url": "",
                "page_id": null,
                "dynamic_format": "UNSET",
                "vast_moat_enabled": false
            }
        ]
    }
}
(/code)
```

#### Ad format as Single video via Spark Ads PULL
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "ad_format": "LIVE_CONTENT",
            <span style="background-color: green;">"creative_type": "SHORT_VIDEO_LIVE",
            "vertical_video_strategy": "SINGLE_VIDEO",</span>
            "tiktok_item_id": "{{tiktok_item_id}}",
            <span style="background-color: green;">"call_to_action": "WATCH_LIVE"</span>
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
                "campaign_id": "{{campaign_id}}",
                "vertical_video_strategy": "SINGLE_VIDEO",
                "landing_page_url": null,
                "avatar_icon_web_uri": "",
                "advertiser_id": "{{advertiser_id}}",
                "profile_image_url": "",
                "card_id": null,
                "is_aco": false,
                "product_specific_type": "UNSET",
                "ad_id": "{{ad_id}}",
                "deeplink": null,
                "modify_time": "{{modify_time}}",
                "impression_tracking_url": null,
                "adgroup_name": "{{adgroup_name}}",
                "creative_type": "SHORT_VIDEO_LIVE",
                "page_id": null,
                "promotional_music_disabled": true,
                "image_ids": [
                    ""
                ],
                "call_to_action_id": null,
                "music_id": null,
                "identity_type": "TT_USER",
                "video_id": null,
                "app_name": "{{app_name}}",
                "campaign_name": "{{campaign_name}}",
                "operation_status": "ENABLE",
                "dynamic_format": "UNSET",
                "ad_text": "{{ad_text}}",
                "viewability_vast_url": null,
                "landing_page_urls": null,
                "create_time": "{{create_time}}",
                "identity_id": "{{identity_id}}",
                "ad_format": "SINGLE_VIDEO",
                "adgroup_id": "{{adgroup_id}}",
                "display_name": "{{display_name}}",
                "ad_name": "{{ad_name}}",
                "click_tracking_url": null,
                "creative_authorized": false,
                "brand_safety_postbid_partner": "UNSET",
                "optimization_event": null,
                "brand_safety_vast_url": null,
                "call_to_action": "WATCH_LIVE",
                "shopping_ads_video_package_id": "",
                "playable_url": "",
                "dynamic_destination": "UNSET",
                "viewability_postbid_partner": "UNSET",
                "ad_texts": null,
                "is_new_structure": true,
                "vast_moat_enabled": false,
                "tiktok_item_id": "{{tiktok_item_id}}"
            }
        ]
    }
}
(/code)
```

#### Ad format as Single video via Spark Ads PUSH with "Only show as ad" disabled
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "ad_format": "LIVE_CONTENT",
            <span style="background-color: green;">"creative_type": "SHORT_VIDEO_LIVE",
            "vertical_video_strategy": "SINGLE_VIDEO",</span>
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
            "ad_text": "{{ad_text}}",
            <span style="background-color: green;">"call_to_action": "WATCH_LIVE"</span>
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
                "modify_time": "{{modify_time}}",
                "card_id": null,
                "call_to_action_id": null,
                "operation_status": "ENABLE",
                "ad_text": "{{ad_text}}",
                "campaign_name": "{{campaign_name}}",
                "creative_authorized": false,
                "image_ids": [
                    "{{image_id}}"
                ],
                "is_aco": false,
                "optimization_event": null,
                "landing_page_urls": null,
                "branded_content_disabled": false,
                "music_id": null,
                "impression_tracking_url": null,
                "brand_safety_vast_url": null,
                "shopping_ads_video_package_id": "",
                "adgroup_id": "{{adgroup_id}}",
                "create_time": "{{create_time}}",
                "viewability_vast_url": null,
                "vast_moat_enabled": false,
                "landing_page_url": null,
                "brand_safety_postbid_partner": "UNSET",
                "click_tracking_url": null,
                "profile_image_url": "",
                "deeplink": null,
                "dark_post_status": "OFF",
                "call_to_action": "WATCH_LIVE",
                "viewability_postbid_partner": "UNSET",
                "ad_format": "SINGLE_VIDEO",
                "display_name": "{{display_name}}",
                "video_id": "{{video_id}}",
                "avatar_icon_web_uri": "",
                "product_specific_type": "UNSET",
                "page_id": null,
                "is_new_structure": true,
                "ad_id": "{{ad_id}}",
                "identity_id": "{{identity_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "dynamic_format": "UNSET",
                "campaign_id": "{{campaign_id}}",
                "ad_texts": null,
                "app_name": "{{app_name}}",
                "ad_name": "{{ad_name}}",
                "identity_type": "TT_USER",
                "dynamic_destination": "UNSET",
                "creative_type": "SHORT_VIDEO_LIVE",
                "adgroup_name": "{{adgroup_name}}",
                "playable_url": "",
                "vertical_video_strategy": "SINGLE_VIDEO"
            }
        ]
    }
}
(/code)
```
#### Ad format as Single video via Spark Ads PUSH with "Only show as ad" enabled
Request
```xcodeblock
(code)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "creatives": [
        {
            "ad_name": "{{ad_name}}",
            "identity_type": "TT_USER",
            "identity_id": "{{identity_id}}",
            "ad_format": "LIVE_CONTENT",
            <span style="background-color: green;">"creative_type": "SHORT_VIDEO_LIVE",
            "vertical_video_strategy": "SINGLE_VIDEO",</span>
            "video_id": "{{video_id}}",
            "image_ids": ["{{image_id}}"],
           <span style="background-color: green;">"dark_post_status":"ON",</span>
            "ad_text": "{{ad_text}}",
            <span style="background-color: green;">"call_to_action": "WATCH_LIVE"</span>
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
                "creative_type": "SHORT_VIDEO_LIVE",
                "ad_id": "{{ad_id}}",
                "page_id": null,
                "music_id": null,
                "brand_safety_vast_url": null,
                "image_ids": [
                    "{{image_id}}"
                ],
                "dynamic_destination": "UNSET",
                "call_to_action_id": null,
                "ad_text": "{{ad_text}}",
                "branded_content_disabled": false,
                "video_id": "{{video_id}}",
                "click_tracking_url": null,
                "profile_image_url": "",
                "identity_id": "{{identity_id}}",
                "creative_authorized": false,
                "brand_safety_postbid_partner": "UNSET",
                "is_new_structure": true,
                "landing_page_urls": null,
                "dynamic_format": "UNSET",
                "viewability_vast_url": null,
                "ad_texts": null,
                "landing_page_url": null,
                "avatar_icon_web_uri": "",
                "campaign_id": "{{campaign_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "dark_post_status": "ON",
                "campaign_name": "{{campaign_name}}",
                "modify_time": "{{modify_time}}",
                "is_aco": false,
                "vast_moat_enabled": false,
                "viewability_postbid_partner": "UNSET",
                "card_id": null,
                "vertical_video_strategy": "SINGLE_VIDEO",
                "ad_name": "{{ad_name}}",
                "identity_type": "TT_USER",
                "display_name": "{{display_name}}",
                "ad_format": "SINGLE_VIDEO",
                "create_time": "{{create_time}}",
                "adgroup_name": "{{adgroup_name}}",
                "operation_status": "ENABLE",
                "deeplink": null,
                "call_to_action": "WATCH_LIVE",
                "shopping_ads_video_package_id": "",
                "optimization_event": null,
                "adgroup_id": "{{adgroup_id}}",
                "product_specific_type": "UNSET",
                "playable_url": "",
                "app_name": "{{app_name}}",
                "impression_tracking_url": null
            }
        ]
    }
}
(/code)
```
