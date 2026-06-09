---
title: "Create Upgraded Smart+ Catalog Ads for App"
doc_id: 1847303035303106
path: "Use Cases / Campaign creation / Create an Upgraded Smart+ Campaign / Create Upgraded Smart+ Catalog Ads for App"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Upgraded Smart+ Catalog Ads for App

This article walks you through the steps to create Upgraded Smart+ Catalog Ads for App using the Sales objective.

Upgraded Smart+ Catalog Ads for App are Upgraded Smart+ Sales Campaigns that use catalog products to deliver ads. For such campaigns, you need to set `objective_type` to `WEB_CONVERSIONS` and `sales_destination` to `APP` at the campaign level. By utilizing Upgraded Smart+ Catalog Ads for App, you can grow your app user base in two primary ways: by acquiring new users (Prospecting) and re-engaging existing ones (Retargeting).

Based on your preference for creating a [Dedicated Campaign](https://business-api.tiktok.com/portal/docs?id=1740029173531649), you can create Upgraded Smart+ Catalog non-DC Ads for App, or Upgraded Smart+ Catalog DC Ads for App. For detailed instructions on creating each type, refer to their respective sections: "[Create Upgraded Smart+ Catalog non-DC Ads for App](https://haapi.byteintl.net/gateway/document/info?openscene_id=7089534145277198337&doc_platform_id=12&doc_id=1847303035303106&language=ENGLISH#item-link-Create%20Upgraded%20Smart+%20Catalog%20non-DC%20Ads%20for%20App)", "[Create Upgraded Smart+ Catalog DC Ads for App](https://haapi.byteintl.net/gateway/document/info?openscene_id=7089534145277198337&doc_platform_id=12&doc_id=1847303035303106&language=ENGLISH#item-link-Create%20Upgraded%20Smart+%20Catalog%20DC%20Ads%20for%20App)".


# Create Upgraded Smart+ Catalog non-DC Ads for App
Upgraded Smart+ Catalog non-DC Ads for App are Upgraded Smart+ Sales Campaigns with iOS 14 dedicated campaign setting disabled that use catalog products to deliver ads. For such campaigns, you can set the `campaign_type` parameter to `REGULAR_CAMPAIGN` or leave `campaign_type` unspecified.


## Prerequisite for creating Upgraded Smart+ Catalog non-DC Ads for App
Creating Upgraded Smart+ Catalog Ads for App requires setting up a catalog in your Business Center first.

To set up the catalog:
1. Create a catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961). 
	- The catalog type can be any of the following:
		- E-commerce
		- hotel
		- flight
		- destination
		- entertainment
2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
  Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
4. (Optional) Create a product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
  If you want to have products selected from a product set, creating a product set is necessary. Otherwise, you can skip this step.
5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
  You can also choose `advertiser_role` that you want to assign to the members invited.
6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
  Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.
For all catalog APIs, see [here](https://business-api.tiktok.com/portal/docs?id=1739578477445121).

## Steps
### Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>Sales</td>
      <td><code>objective_type</code></td>
      <td><code>WEB_CONVERSIONS</code></td>
    </tr>
    <tr>
      <td>Sales destination</td>
      <td>App</td>
      <td><code>sales_destination</code></td>
      <td><code>APP</code></td>
    </tr>
        <tr>
            <td rowspan="2">Catalog type</td>
            <td rowspan="2">Any of the following types:<ul><li>E-commerce</li><li>Travel and entertainment</li></ul></td>
            <td><code>catalog_enabled</code></td>
            <td><code>true</code></td>
        </tr>
        <tr>
            <td><code>catalog_type</code></td>
            <td>Any of the following values:<ul><li><code>ECOMMERCE</code></li><li><code>TRAVEL_ENTERTAINMENT</code></li></ul></td>
        </tr>
    <tr>
      <td rowspan="3">iOS 14 dedicated campaign</td>
      <td rowspan="3"><b>Disabled</b><br><br>To learn about how to create Upgraded Smart+ Catalog Ads for App with iOS 14 Dedicated Campaign enabled, see <a href="#item-link-Create Upgraded Smart+ Catalog DC Ads for App">Create Upgraded Smart+ Catalog DC Ads for App</a>.</td>
      <td><code>campaign_type</code></td>
      <td><code>REGULAR_CAMPAIGN</code> or not specified</td>
    </tr>
    <tr>
      <td><code>app_id</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td><code>campaign_app_profile_page_state</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories (Optional)</td>
      <td>Disabled or enabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</p></td>
      <td><code>special_industries</code></td>
      <td>Not specified or specify a valid value</td>
    </tr>
    <tr>
      <td>Campaign budget strategy</td>
      <td>Any of the following types:<ul><li><b>Automatic campaign budget</b>: Your budget will be automatically allocated and optimized across ad groups. All ad groups must share the same budget and optimization settings.</li><li><b>Custom ad group budget</b>: Precisely control spend for each ad group, using different budget and optimization settings for each.</li></ul></td>
      <td><code>budget_optimize_on</code></td>
      <td><ul><li>To set an automatic campaign budget, set this field to <code>true</code> or leave it unspecified.</li><li>To set a custom ad group budget, set this field to <code>false</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Campaign budget mode</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify any of the following modes:<ul><li>Daily</li><li>Lifetime</li></ul></li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget_mode</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, use any of the following values :<ul><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Automatic budget increase (when Campaign budget strategy is Automatic campaign budget and campaign budget mode is daily)</td>
      <td>Any of the following options:<ul><li>Enabled</li><li>Disabled</li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>When <code>budget_optimize_on</code> is <code>true</code> or not specified and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>:<ul><li> To enable the setting, use the following value:<ul><li><code>AUTO_BUDGET_INCREASE</code>. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>To disable the setting: <ul><li>Not specified</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Campaign budget</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify a valid budget</li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, specify a valid value.</li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Realtime API (RTA) (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: The available placements at the ad group level might vary based on your RTA setting.</p></td>
      <td><code>rta_id</code></td>
      <td>Specify one of the RTA IDs associated with your ad account.<br><br>To obtain the list of RTA IDs associated with your ad account, contact your TikTok representative.<br><br><p><span style="color:darkred"><b>Note</b></span>: The available placement setting (<code>placement_type</code> and <code>placements</code>) at the ad group level might vary based on your <code>rta_id</code> configuration.</p></td>
    </tr>
    <tr>
      <td>Use RTA to automatically select products (Optional)</td>
      <td><ul><li>When RTA is disabled, this setting is not supported.</li><li>When RTA is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>rta_product_selection_enabled</code></td>
      <td><ul><li>When <code>rta_id</code> is not specified:<ul><li><code>false</code> or not specified</li></ul></li><li>When <code>rta_id</code> is specified, use any of the following configurations :<ul><li><code>true</code></li><li><code>false</code> or not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Use RTA bid (Optional)</td>
      <td><ul><li>When RTA is disabled, this setting is not supported.</li><li>When RTA is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>rta_bid_enabled</code></td>
      <td><ul><li>When <code>rta_id</code> is not specified:<ul><li><code>false</code> or not specified</li></ul></li><li>When <code>rta_id</code> is specified, use any of the following configurations :<ul><li><code>true</code></li><li><code>false</code> or not specified</li></ul></li></ul></td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": {{request_id}},
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "APP",
    "catalog_enabled": true,
    "catalog_type": "ECOMMERCE",
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_name": "{{campaign_name}}",
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
        "advertiser_id": "{{advertiser_id}}",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "REGULAR_CAMPAIGN",
        "catalog_enabled": true,
        "catalog_type": "ECOMMERCE",
        "create_time": "{{create_time}}",
        "is_promotional_campaign": false,
        "modify_time": "{{modify_time}}",
        "objective_type": "WEB_CONVERSIONS",
        "operation_status": "ENABLE",
        "sales_destination": "APP",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```

### Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad group name</td>
      <td>Specify a valid name</td>
      <td><code>adgroup_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Product source details <br>· Catalog</td>
      <td>Specify <b>a catalog with at least two approved products</b><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When RTA is disabled at the campaign level, you can specify any of the following catalog types:<ul><li>E-commerce</li><li>hotel</li><li>flight</li><li>destination</li><li>entertainment</li></ul></li><li>When RTA is enabled at the campaign level, you can specify any of the following catalog types:<ul><li>E-commerce</li><li>entertainment</li></ul></li></ul></p></td>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Specify valid values.<br>The catalog (<code>catalog_id</code>) needs to have at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 2.<br><br><ul><li>To obtain catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>.</li><li>To learn about how to create a catalog, see <a href="#item-link-Prerequisite for creating Upgraded Smart+ Catalog non-DC Ads for App"> Prerequisite for creating Upgraded Smart+ Catalog non-DC Ads for App</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When <code>rta_id</code> is not specified at the campaign level, the <code>catalog_type</code> of the catalog can be any of the following:<ul><li><code>ECOM</code></li><li><code>HOTEL</code></li><li><code>FLIGHT</code></li><li><code>DESTINATION</code></li><li><code>ENTERTAINMENT</code></li></ul></li><li>When <code>rta_id</code> is specified at the campaign level, the <code>catalog_type</code> of the catalog can be any of the following:<ul><li><code>ECOM</code></li><li><code>ENTERTAINMENT</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· App</td>
      <td>Any of the following types:<ul><li>Android App</li><li>iOS App</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
      <td><code>promotion_type</code><br><code>app_id</code></td>
      <td><ul><li>To specify an Android App:<ul><li>Specify an Android App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_ANDROID</code>.</li></ul></li><li>To specify an iOS App:<ul><li>Specify an iOS App via <code>app_id</code>.</li><li>Set <code>promotion_type</code> to <code>APP_IOS</code>.</li></ul></li></ul>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.<ul><li>For Android Apps, the returned <code>platform</code> will be <code>ANDROID</code>.</li><li>For iOS Apps, the returned <code>platform</code> will be <code>IOS</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Goal</td>
      <td>Any of the following types:<ul><li>Click<ul><li>We'll deliver your ads to the people most likely to click on your ad.</li></ul></li><li>Install<ul><li>We'll show your ads to the people who are most likely to install your app.</li></ul></li><li>In-app event<ul><li>We'll show your ads to the people who are most likely to perform certain actions within your app.</li></ul></li><li>Value<ul><li>We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</li></ul></td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>INSTALL</code> (with <code>optimization_event</code> as <code>ACTIVE</code>)<ul><li>To use <code>INSTALL</code>, you need to ensure third-party tracking has been set up for the selected App.<ul><li>To check whether third-party tracking (<code>tracking_url</code>) has been configured for an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297" target="_blank">/app/info/</a>.</li><li>To add third-party tracking for an existing App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859300069378" target="_blank">/app/update/</a>. To learn about how to obtain tracking links from a third-party partner, see <a href="https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking" target="_blank">Mobile Measurement Partner Tracking</a>.</li></ul></li></ul></li><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified)<ul><li>To use <code>IN_APP_EVENT</code>, you need to ensure the specified event (<code>optimization_event</code>) is available for the selected App.</li></ul></li><li><code>VALUE</code> (with <code>optimization_event</code> specified)<ul><li>When you set this field to <code>VALUE</code>, specify the placement parameters (<code>placement_type</code> or both <code>placement_type</code> and <code>placements</code>) at the same time.</li><li>Learn more about <a href="https://business-api.tiktok.com/portal/docs?id=1739381743067137" target="_blank">Value-Based Optimization</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Select in-app event (when Goal is In-app event)</td>
      <td>Any of the following types:<ul><li>Standard Event</li><li>Custom Conversion</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</td>
      <td><code>optimization_event</code><br><code>custom_conversion_id</code></td>
      <td>When <code>optimization_goal</code> is <code>IN_APP_EVENT</code>, specify any of the following:<ul><li>A Standard Event through<code>optimization_event</code></li><li>A Custom Conversion along with the associated conversion event through <code>custom_conversion_id</code> and <code>optimization_event</code></li></ul><br><ul><li>To obtain the available optimization events (<code>optimization_event</code>) for the App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li><li>To obtain the list of Custom Conversions associated with an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1842225174460673" target="_blank">/custom_conversion/list/</a>.<ul><li>To confirm the eligibility of the Custom Conversion for ad group creation:<ul><li>Ensure the returned <code>optimization_event</code> matches the <code>optimization_event</code> specified during ad group creation.</li><li>Ensure the <code>activity_status</code> is <code>NO_RECENT_ACTIVITY</code> or <code>ACTIVE</code>.</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code> and <code>custom_conversion_id</code>, if specified, must be the same for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Bid Strategy, (Optional) Target CPA, and (Optional) Target ROAS</td>
      <td><ul><li>If Goal is Click, Install, or In-app event, the strategy can only be Maximum Delivery or Cost Cap.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, <code>INSTALL</code>, or <code>IN_APP_EVENT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>optimization_goal</code> is <code>INSTALL</code> or <code>IN_APP_EVENT</code>and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Billing event</td>
      <td><ul><li>If Goal is Click, the billing event should be CPC.</li><li>If Goal is Install, In-app event, or Value, the billing event should be oCPM.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</li></ul></p></td>
      <td><code>billing_event</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</p></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Delivery Type</td>
      <td>Standard (default)</td>
      <td>N/A</td>
      <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Budget</td>
      <td><ul><li>When you select an automatic campaign budget mode:<ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul></li><li>When you select a custom ad group budget strategy, you can use any of the following ad group budget modes:<ul><li>Lifetime</li><li>Daily</li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</li></ul></p></td>
      <td><code>budget_mode</code><br><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code>are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</li></ul></p></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Automatic budget increase (when Goal is Value)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Goal is Value</li><li>Bid Strategy is Target ROAS</li><li>Time window of the bid strategy is Day 0 ROAS</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable automatic budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>optimization_goal</code> is <code>VALUE</code>.</li><li><code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>.</li><li><code>vbo_window</code> is <code>ZERO_DAY</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Add spend target per ad group <br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios:<ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></p></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></p></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting</td>
      <td>Any of the following modes:<ul><li><b>Prospect</b><ul><li>Find new customers who want to install your app</li></ul></li><li><b>Retarget</b><ul><li>Show ads to customers who are already using your app</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When Optimization goal is Install, this setting is not supported.</td>
      <td><code>app_targeting_type</code></td>
      <td>Any of the following values:<ul><li><code>PROSPECT</code></li><li><code>RETARGETING</code></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>optimization_goal</code> is <code>INSTALL</code>, <code>app_targeting_type</code> is not supported.</td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Placement</td>
      <td>Any of the following types:<ul><li><b>Automatic Placement</b></li><li><b>Select specific placements</b>:<ul><li>TikTok</li><li>Global App Bundle</li><li>Pangle</li></ul></li></ul></td>
      <td><code>placement_type</code><br><code>placements</code></td>
      <td><ul><li>To use Automatic Placement, do not specify <code>placement_type</code> and <code>placements</code>.<ul><li>The system will automatically configure placements for your Upgraded Smart+ Ad Groups based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored.</li><li>To retrieve the exact placement setting of an Upgraded Smart+ Ad Group, use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a>.</li></ul></li><li>To use specific placements, set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code> and specify one or more of the following placements in <code>placements</code>:<ul><li><code>PLACEMENT_TIKTOK</code></li><li><code>PLACEMENT_PANGLE</code></li><li><code>PLACEMENT_GLOBAL_APP_BUNDLE</code></li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
      <td>N/A</td>
      <td>N/A<br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· User comment</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video download</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video sharing</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Pangle block list</td>
      <td>Disabled</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
  </tbody>
</table>

#### Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App

<table>
  <thead>
    <tr>
      <th style="width:20%">Targeting optimization mode</th>
      <th style="width:20%">Available targeting setting</th>
      <th style="width:20%">Requirement</th>
      <th style="width:20%">Parameter</th>
      <th style="width:20%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="6">Automatic targeting<br>(<code>targeting_optimization_mode</code> as <code>AUTOMATIC</code> or not specified)</td>
      <td>Audience controls <br>· Location</td>
      <td>The location should match or be the subset of the targeting location of your catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
    </tr>
    <tr>
      <td>Audience controls <br>· Minimum age</td>
      <td>Any of the following options:<ul><li>18</li><li>25</li></ul></td>
      <td><code>spc_audience_age</code></td>
      <td>Any of the following values:<ul><li><code>OVER_EIGHTEEN</code> or unspecified</li><li><code>OVER_TWENTY_FIVE</code></li></ul></td>
    </tr>
    <tr>
      <td>Audience controls <br>· Languages <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls <br>· Custom audience <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls <br>· Exclude audience <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Audience controls <br>· Gender <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>gender</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td rowspan="21">Custom targeting<br>(<code>targeting_optimization_mode</code> as <code>MANUAL</code>)</td>
            <td>Retarget<br>· Custom audience<br>(Optional when an E-commerce catalog is used)</td>
            <td>Enabled or disabled.</td>
            <td><code>shopping_ads_retargeting_type</code><br><code>shopping_ads_retargeting_actions_days</code><br><code>included_custom_actions</code><br><code>excluded_custom_actions</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
        <tr>    
            <td>Retarget<br>· Custom audience<br>&nbsp;&nbsp;· Include<br>&nbsp;&nbsp;&nbsp;&nbsp;· Target anyone in catalog or custom audiences<br>(Optional when both a Catalog Include Audience and a custom audience are specified)</td>
            <td>Enabled or disabled</td>
            <td><code>shopping_ads_retargeting_custom_audience_relation</code></td>
            <td>Specify valid values or not specified</td>
        </tr>
    <tr>
      <td>Demographics · Location</td>
      <td>Specify a valid value.<br>If a catalog is specified, the location should match or be the subset of the targeting location of your catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code> or both</td>
      <td>Specify a valid value.<br>If a catalog is specified, specify IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
    </tr>
    <tr>
      <td>Demographics <br>· Age <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>age_groups</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Demographics <br>· Gender <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>gender</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Demographics <br>· Languages <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Custom audience <br>· Include audience <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Custom audience <br>· Smart audience <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>smart_audience_enabled</code></td>
      <td><code>true</code> or <code>false</code></td>
    </tr>
    <tr>
      <td>Custom audience <br>· Exclude audience (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Interests & behaviors <br>&nbsp;&nbsp;· Interests <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Interests & behaviors <br>&nbsp;&nbsp;· Purchase intention <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>purchase_intention_keyword_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Interests & behaviors <br>&nbsp;&nbsp;· Video interactions <br>&nbsp;&nbsp;· Creator interactions <br>&nbsp;&nbsp;· Hashtag interactions <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>actions</code></td>
      <td><code>true</code> or <code>false</code></td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Interests & behaviors <br>&nbsp;&nbsp;· Smart interests & behaviors<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>smart_interest_behavior_enabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Spending power <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>spending_power</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Detailed targeting <br>· Household income<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>household_income</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Operating system (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>operating_systems</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· OS versions<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>min_android_version</code><br><code>min_ios_version</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Device model <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_model_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Connection type<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>network_types</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Carriers <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>carrier_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Internet service provider (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>isp_ids</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Device <br>· Device price <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>device_price_ranges</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Use saved audience <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>saved_audience_id</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "app_id": "{{app_id}}",
    "promotion_type": "APP_IOS",
    "optimization_goal": "IN_APP_EVENT",
    "optimization_event": "LOGIN",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_optimization_mode": "AUTOMATIC",
    "targeting_spec": {
        "location_ids": [
            "{{location_ids}}"
        ],
        "operating_systems": [
            "IOS"
        ]
    },
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
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
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "app_config": [],
        "app_id": "{{app_id}}",
        "attribution_event_count": "ONCE",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "catalog_id": "{{catalog_id}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "deep_bid_type": "AEO",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": {{min_budget}},
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "LOGIN",
        "optimization_event_type": "STANDARD_EVENT",
        "optimization_goal": "IN_APP_EVENT",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_source": "CATALOG",
        "promotion_type": "APP_IOS",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
        "skip_learning_phase": true,
        "targeting_optimization_mode": "AUTOMATIC",
        "targeting_spec": {
            "actions": [],
            "age_groups": [
                "AGE_18_24",
                "AGE_25_34",
                "AGE_35_44",
                "AGE_45_54",
                "AGE_55_100"
            ],
            "app_targeting_type": "PROSPECT",
            "brand_safety_type": "EXPANDED_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
            ],
            "min_ios_version": "8.0",
            "operating_systems": [
                "IOS"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY"
    }
}
(/code)
```

### Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name (Optional)</td>
      <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code>(empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td>Product source details <br>· Products</td>
      <td><ul><li>When "Use RTA to automatically select products" is enabled at the campaign level: All products</li><li>When "Use RTA to automatically select products" is disabled, use any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></li></ul></td>
      <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
      <td><ul><li>When <code>rta_product_selection_enabled</code> is <code>true</code> at the campaign level: <code>ALL</code></li><li>When <code>rta_product_selection_enabled</code> is <code>false</code> or not specified at the campaign level, set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to specify <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to specify <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to specify a maximum of 20 available catalog products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Creative assets <br>· Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
        <tr>
            <td>Creative assets <br>· Autoselect from catalog</td>
            <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Images and videos in your catalog will be used to automatically generate optimized Catalog Ads in all formats for your products.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App">Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App</a>.</li></ul></li><li>Disabled<ul><li>Images and videos in your catalog will not be used to automatically generate optimized Catalog Ads in all formats for your products. The system will automatically create single video ads using your specified content.</li><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</p></li></ul></td>
            <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code><br><code>ad_format</code></td>
            <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code>, specify a valid value for <code>ad_format</code> in each <code>creative_info</code> object, and specify a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App">Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App</a>.<br><p><span style="color:darkred"><b>Note</b></span>: <code>music_info</code> is required when <code>catalog_creative_toggle</code> is <code>true</code>.</li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>.<ul><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>For each creative, specify the corresponding value for <code>ad_format</code> in each <code>creative_info</code> object.</li><li>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>. </li></ul></li></ul></td>
        </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-ons (Optional) (When the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></p><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Destination</td>
      <td><ul><li>When the promoted App is an Android App: Google Play</li><li>When the App is an iOS App: App Store</li></ul></td>
      <td><code>landing_page_url_list</code><br><code>page_list</code></td>
      <td><ul><li>When <code>promotion_type</code> at the ad group level is <code>APP_ANDROID</code>, you can only set the Destination as Google Play.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li><li>When <code>promotion_type</code> at the ad group level is <code>APP_IOS</code>, you can only set the Destination as App Store.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Deferred deeplink (Optional) (When Destination is Google Play/App Store)</td>
      <td rowspan="2">Disabled or enabled with a valid deeplink.<br>A deferred deeplink sends the user to a specific page when they open your app for the first time after installing. It must be used with a third-party tracking link.</td>
      <td><code>deeplink_type</code></td>
      <td>Not specified or specify <code>DEFERRED_DEEPLINK</code></td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td><ul><li>When <code>deeplink_type</code> is not specified:<ul><li>Not specified</li></ul></li><li>When <code>deeplink_type</code> is <code>DEFERRED_DEEPLINK</code>:<ul><li>Specify a custom deeplink</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Build URL parameters (Optional)</td>
      <td><ul><li>When Deferred deeplink is disabled, this setting is not supported.</li><li>When Deferred deeplink is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>deeplink_utm_params</code></td>
      <td><ul><li>When <code>deeplink_type</code> is not specified:<ul><li>Not specified</li></ul></li><li>When <code>deeplink_type</code> is <code>DEFERRED_DEEPLINK</code>, use any of the following configurations :<ul><li>Specify a valid value or not specified</li></ul></li></ul><br>If you set <code>deeplink</code> to a URL that already includes URL parameters, you can optionally specify <code>deeplink_utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>deeplink_utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>deeplink</code> upon ad delivery.</td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

#### Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App

If you enable auto-selection of catalog creatives (<code>catalog_creative_toggle</code> is <code>true</code>), the system will automatically create ads using combinations of three possible formats: catalog carousel, catalog video, and single video.

The possible ad format combinations for your Upgraded Smart+ Catalog Ads for App are illustrated in the following table.

<table>
    <thead>
        <tr>
            <th style="width:17%">Is the catalog carousel format available?</th>
            <th style="width:26%">Is the catalog video format available?</th>
            <th style="width:27%">Is the single video format available?</th>
            <th style="width:30%">Ad format combination for your Upgraded Smart+ Catalog Ads for App</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>✅</td>
            <td>❌</td>
            <td>❌</td>
            <td>Catalog carousel only</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>❌</td>
            <td>✅</td>
            <td>Catalog carousel and single video</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>✅</td>
            <td>❌</td>
            <td>Catalog carousel and catalog video</td>
        </tr>
        <tr>
            <td>✅</td>
            <td>✅</td>
            <td>✅</td>
            <td>Catalog carousel, catalog video, and single video</td>
        </tr>
    </tbody>
</table>

To ensure certain ad formats are available in Upgraded Smart+ Catalog Ads for App, follow the instructions in the following table.

<table>
    <thead>
        <tr>
            <th style="width:17%">Ad format</th>
            <th style="width:26%">Ad format description</th>
            <th style="width:27%">How to ensure the ad format is available</th>
            <th style="width:30%">How to configure the parameters</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td rowspan="2">catalog carousel</td>
            <td rowspan="2">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
            <td>The catalog needs to contain at least two products supported in Catalog Carousel Ads.</td>
            <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Specify a piece of music supported in Catalog Carousel Ads.</td>
            <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122" target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650" target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>catalog video</td>
            <td>Generate a dynamic video based on information from the product catalog</td>
            <td>The catalog needs to be bound to at least one video template or you need to configure a video URL for each catalog product.<br>The system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video or use the video URL for the catalog product.</td>
            <td>Ensure that at least one video template is bound to your catalog (<code>catalog_id</code>) or a video URL (<code>video_url</code>) has been configured for each catalog product.<ul><li>To ensure a video URL has been configured for each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a> and check the returned <code>video_url</code>.<ul><li>If you want to add video URLs for your catalog products, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546" target="_blank">/catalog/product/update/</a>.</li></ul></li><li>To ensure that at least one video template (video package) is bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank">/catalog/video_package/get/</a>.<ul><li>To learn about how to create video templates on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog?lang=en" target="_blank">How to create video packages in a Catalog</a>.</li><li>To retrieve video templates for your catalog via API, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073" target="_blank">/catalog/video_package/get/</a>.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>single video</td>
            <td>Use specified video creatives to promote your products.</td>
            <td>Specify additional videos</td>
            <td>Specify additional videos via <code>video_info</code> and cover images via <code>image_info</code>.</td>
        </tr>
    </tbody>
</table>

#### Example

Using catalog creatives only
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "CATALOG_CAROUSEL",
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "catalog_creative_toggle": true,
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_info": {},
            "catalog_creative_toggle": true,
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "fallback_type": "UNSET",
            "identity_id": "{{identity_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [],
                "tracking_app_id": "{{tracking_app_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}"
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "material_name": "{{material_name}}",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```

Using non-catalog creatives only
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "catalog_creative_toggle": false,
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_info": {},
            "catalog_creative_toggle": false,
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "fallback_type": "UNSET",
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [],
                "tracking_app_id": "{{tracking_app_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}"
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "SINGLE_VIDEO",
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "material_name": "{{material_name}}",
                    "video_info": {
                        "file_name": "{{file_name}}",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```

# Create Upgraded Smart+ Catalog DC Ads for App
Upgraded Smart+ Catalog DC Ads for App are Upgraded Smart+ Sales Campaigns with iOS 14 dedicated campaign setting enabled that use catalog products to deliver ads. For such campaigns, you need to set the `campaign_type` parameter to `IOS14_CAMPAIGN`.


## Prerequisite for creating Upgraded Smart+ Catalog DC Ads for App
Creating Upgraded Smart+ Catalog Ads for App requires setting up a catalog in your Business Center first.

To set up the catalog:
1. Create a catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961). 
	- The catalog type can be any of the following:
		- E-commerce
		- hotel
		- flight
		- destination
		- entertainment
2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889).
  Pass in the `feed_log_id` obtained from Step 2. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
4. (Optional) Create a product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
  If you want to have products selected from a product set, creating a product set is necessary. Otherwise, you can skip this step.
5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505).
  You can also choose `advertiser_role` that you want to assign to the members invited.
6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121).
  Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field.
For all catalog APIs, see [here](https://business-api.tiktok.com/portal/docs?id=1739578477445121).


## Steps
### Create a campaign
Create a campaign using [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>Sales</td>
      <td><code>objective_type</code></td>
      <td><code>WEB_CONVERSIONS</code></td>
    </tr>
    <tr>
      <td>Sales destination</td>
      <td>App</td>
      <td><code>sales_destination</code></td>
      <td><code>APP</code></td>
    </tr>
        <tr>
            <td rowspan="2">Catalog type</td>
            <td rowspan="2">Any of the following types:<ul><li>E-commerce</li><li>Travel and entertainment</li></ul></td>
            <td><code>catalog_enabled</code></td>
            <td><code>true</code></td>
        </tr>
        <tr>
            <td><code>catalog_type</code></td>
            <td>Any of the following values:<ul><li><code>ECOMMERCE</code></li><li><code>TRAVEL_ENTERTAINMENT</code></li></ul></td>
        </tr>
    <tr>
      <td>iOS 14 dedicated campaign</td>
      <td><b>Enabled</b><br><br>To learn about how to create Upgraded Smart+ Catalog Ads for App with iOS 14 Dedicated Campaign disabled, see <a href="#item-link-Create Upgraded Smart+ Catalog non-DC Ads for App">Create Upgraded Smart+ Catalog non-DC Ads for App</a>.</td>
      <td><code>campaign_type</code></td>
      <td><code>IOS14_CAMPAIGN</code></td>
    </tr>
    <tr>
      <td>App</td>
      <td>Specify a valid iOS App</td>
      <td><code>app_id</code></td>
      <td>Specify a valid value.<br>To obtain the list of Apps under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.<br>For iOS Apps, the returned <code>platform</code> will be <code>IOS</code>.</td>
    </tr>
    <tr>
      <td>SKAN attribution (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>disable_skan_campaign</code></td>
      <td>Any of the following values:<ul><li><code>true</code>: To disable SKAN attribution. The campaign will not be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610" target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SAN%20metrics" target="_blank">Self Attribution Network (SAN) metrics</a> for the campaign. However, you cannot retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics" target="_blank">SKAN reporting metrics</a> for the campaign. Learn more about <a href="https://ads.tiktok.com/help/article/about-self-attribution-transition" target="_blank">SAN integration</a>.</li><li><code>false</code> or not specified: To enable SKAN attribution. The campaign will be bound by <a href="https://business-api.tiktok.com/portal/docs?id=1752256248260610" target="_blank">Dedicated Campaign quota limits</a> and you will be able to retrieve <a href="https://business-api.tiktok.com/portal/docs?id=1751443967255553#item-link-SKAN%20metrics" target="_blank">SKAN metrics</a> for the campaign.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Disabling SKAN attribution for Dedicated Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Use app profile page to optimize delivery</td>
      <td>Disabled</td>
      <td><code>campaign_app_profile_page_state</code></td>
      <td><code>OFF</code> (or not specified)</td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories (Optional)</td>
      <td>Disabled or enabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
      <td><code>special_industries</code></td>
      <td>Not specified or specify a valid value</td>
    </tr>
    <tr>
      <td>Campaign budget strategy</td>
      <td>Any of the following types:<ul><li><b>Automatic campaign budget</b>: Your budget will be automatically allocated and optimized across ad groups. All ad groups must share the same budget and optimization settings.</li><li><b>Custom ad group budget</b>: Precisely control spend for each ad group, using different budget and optimization settings for each.</li></ul></td>
      <td><code>budget_optimize_on</code></td>
      <td><ul><li>To set an automatic campaign budget, set this field to <code>true</code> or leave it unspecified.</li><li>To set a custom ad group budget, set this field to <code>false</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Campaign budget mode</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify any of the following modes:<ul><li>Daily</li><li>Lifetime</li></ul></li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget_mode</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, use any of the following values :<ul><li><code>BUDGET_MODE_TOTAL</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Automatic budget increase (when Campaign budget strategy is Automatic campaign budget and campaign budget mode is daily)</td>
      <td>Any of the following options:<ul><li>Enabled</li><li>Disabled</li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>When <code>budget_optimize_on</code> is <code>true</code> or not specified and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>:<ul><li> To enable the setting, use the following value:<ul><li><code>AUTO_BUDGET_INCREASE</code>. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li></ul></li><li>To disable the setting: <ul><li>Not specified</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Campaign budget</td>
      <td><ul><li>When Campaign budget strategy is Automatic campaign budget, specify a valid budget</li><li>When Campaign budget strategy is Custom ad group budget:<ul><li>Disabled</li></ul></li></ul></td>
      <td><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> or not specified, specify a valid value.</li><li>When <code>budget_optimize_on</code> is <code>false</code>:<ul><li>Not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Realtime API (RTA) (Optional)</td>
      <td>Enabled or disabled<br><br><br><p><span style="color:darkred"><b>Note</b></span>: The available placements at the ad group level might vary based on your RTA setting.</td>
      <td><code>rta_id</code></td>
      <td>Specify one of the RTA IDs associated with your ad account.<br><br>To obtain the list of RTA IDs associated with your ad account, contact your TikTok representative.<br><br><br><p><span style="color:darkred"><b>Note</b></span>: The available placement setting (<code>placement_type</code> and <code>placements</code>) at the ad group level might vary based on your <code>rta_id</code> configuration.</td>
    </tr>
    <tr>
      <td>Use RTA to automatically select products (Optional)</td>
      <td><ul><li>When RTA is disabled, this setting is not supported.</li><li>When RTA is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>rta_product_selection_enabled</code></td>
      <td><ul><li>When <code>rta_id</code> is not specified:<ul><li><code>false</code> or not specified</li></ul></li><li>When <code>rta_id</code> is specified, use any of the following configurations :<ul><li><code>true</code></li><li><code>false</code> or not specified</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Use RTA bid (Optional)</td>
      <td><ul><li>When RTA is disabled, this setting is not supported.</li><li>When RTA is enabled, you can either enable or disable this setting.</li></ul></td>
      <td><code>rta_bid_enabled</code></td>
      <td><ul><li>When <code>rta_id</code> is not specified:<ul><li><code>false</code> or not specified</li></ul></li><li>When <code>rta_id</code> is specified, use any of the following configurations :<ul><li><code>true</code></li><li><code>false</code> or not specified</li></ul></li></ul></td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/campaign/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "request_id": "{{request_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "APP",
    "catalog_enabled": true,
    "catalog_type": "ECOMMERCE",
    "campaign_type": "IOS14_CAMPAIGN",
    "app_id": "{{app_id}}",
    "campaign_name": "{{campaign_name}}",
    "budget": {{budget}}
}'
(/code)
```
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_id": "{{advertiser_id}}",
        "app_id": "{{app_id}}",
        "bid_align_type": "SAN",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "budget_optimize_on": true,
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "campaign_type": "IOS14_CAMPAIGN",
        "catalog_enabled": true,
        "catalog_type": "ECOMMERCE",
        "create_time": "{{create_time}}",
        "disable_skan_campaign": false,
        "is_advanced_dedicated_campaign": true,
        "is_promotional_campaign": false,
        "modify_time": "{{modify_time}}",
        "objective_type": "WEB_CONVERSIONS",
        "operation_status": "ENABLE",
        "sales_destination": "APP",
        "secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "smart_plus_adgroup_mode": "MULTIPLE"
    }
}
(/code)
```

### Create an ad group
Create an ad group using [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad-group/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad group name</td>
      <td>Specify a valid name</td>
      <td><code>adgroup_name</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td>Product source details <br>· Catalog</td>
      <td>Specify <b>a catalog with at least two approved products</b><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When RTA is disabled at the campaign level, you can specify any of the following catalog types:<ul><li>E-commerce</li><li>hotel</li><li>flight</li><li>destination</li><li>entertainment</li></ul></li><li>When RTA is enabled at the campaign level, you can specify any of the following catalog types:<ul><li>E-commerce</li><li>entertainment</li></ul></li></ul></p></td>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Specify valid values.<br>The catalog (<code>catalog_id</code>) needs to have at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 2.<br><br><ul><li>To obtain catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740315452868610" target="_blank">/catalog/get/</a>.</li><li>To learn about how to create a catalog, see <a href="#item-link-Prerequisite for creating Upgraded Smart+ Catalog DC Ads for App">Prerequisite for creating Upgraded Smart+ Catalog DC Ads for App</a>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When <code>rta_id</code> is not specified at the campaign level, the <code>catalog_type</code> of the catalog can be any of the following:<ul><li><code>ECOM</code></li><li><code>HOTEL</code></li><li><code>FLIGHT</code></li><li><code>DESTINATION</code></li><li><code>ENTERTAINMENT</code></li></ul></li><li>When <code>rta_id</code> is specified at the campaign level, the <code>catalog_type</code> of the catalog can be any of the following:<ul><li><code>ECOM</code></li><li><code>ENTERTAINMENT</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· App</td>
      <td>iOS App<br><br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
      <td><code>promotion_type</code></td>
      <td><code>APP_IOS</code><br><br><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>promotion_type</code> for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Goal</td>
      <td>Any of the following types:<ul><li>Click<ul><li>We'll deliver your ads to the people most likely to click on your ad.</li></ul></li><li>Install<ul><li>We'll show your ads to the people who are most likely to install your app.</li></ul></li><li>In-app event<ul><li>We'll show your ads to the people who are most likely to perform certain actions within your app.</li></ul></li><li>Value<ul><li>We'll deliver your ads to users that are more likely to make a purchase in order to maximize your total purchase value.</li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting must be the same across ad groups within the same campaign.</td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>INSTALL</code> (with <code>optimization_event</code> as <code>ACTIVE</code>)<ul><li>To use <code>INSTALL</code>, you need to ensure third-party tracking has been set up for the selected App.<ul><li>To check whether third-party tracking (<code>tracking_url</code>) has been configured for an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859272887297" target="_blank">/app/info/</a>.</li><li>To add third-party tracking for an existing App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859300069378" target="_blank">/app/update/</a>. To learn about how to obtain tracking links from a third-party partner, see <a href="https://ads.tiktok.com/help/article/mobile-measurement-partner-mmp-tracking" target="_blank">Mobile Measurement Partner Tracking</a>.</li></ul></li></ul></li><li><code>IN_APP_EVENT</code> (with <code>optimization_event</code> specified)<ul><li>To use <code>IN_APP_EVENT</code>, you need to ensure the specified event (<code>optimization_event</code>) is available for the selected App.</li></ul></li><li><code>VALUE</code> (with <code>optimization_event</code> specified)<ul><li>When you set this field to <code>VALUE</code>, specify the placement parameters (<code>placement_type</code> or both <code>placement_type</code> and <code>placements</code>) at the same time.</li><li>Learn more about <a href="https://business-api.tiktok.com/portal/docs?id=1739381743067137" target="_blank">Value-Based Optimization</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>optimization_goal</code>for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Select in-app event (when Goal is In-app event)</td>
      <td>Any of the following types:<ul><li>Standard Event</li><li>Custom Conversion</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a campaign budget mode, this setting, if specified, must be the same across ad group within the same campaign.</td>
      <td><code>optimization_event</code><br><code>custom_conversion_id</code></td>
      <td>When <code>optimization_goal</code> is <code>IN_APP_EVENT</code>, specify any of the following:<ul><li>A Standard Event through<code>optimization_event</code></li><li>A Custom Conversion along with the associated conversion event through <code>custom_conversion_id</code> and <code>optimization_event</code></li></ul><br><ul><li>To obtain the available optimization events (<code>optimization_event</code>) for the App, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li><li>To obtain the list of Custom Conversions associated with an App, use <a href="https://business-api.tiktok.com/portal/docs?id=1842225174460673" target="_blank">/custom_conversion/list/</a>.<ul><li>To confirm the eligibility of the Custom Conversion for ad group creation:<ul><li>Ensure the returned <code>optimization_event</code> matches the <code>optimization_event</code> specified during ad group creation.</li><li>Ensure the <code>activity_status</code> is <code>NO_RECENT_ACTIVITY</code> or <code>ACTIVE</code>.</li></ul></li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the <code>optimization_event</code> and <code>custom_conversion_id</code>, if specified, must be the same for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Bid Strategy, (Optional) Target CPA, and (Optional) Target ROAS</td>
      <td><ul><li>If Goal is Click, Install, or In-app event, the strategy can only be Maximum Delivery or Cost Cap.<ul><li>For Cost Cap, you need to specify a Target CPA at the same time.</li></ul></li><li>If Goal is Value, the strategy can be Highest value or Minimum ROAS.<ul><li>For Minimum ROAS, you need to specify a Target ROAS at the same time.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The bid strategy must be the same across ad groups within the same campaign.</li><li>When you select a campaign budget mode, the Target CPA or Target ROAS, if specified, must be the same across ad groups within the same campaign.</li><li>Target CPA or Target ROAS is only available when your campaign or ad group budget mode is set to daily.</li></ul></p></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, <code>INSTALL</code>, or <code>IN_APP_EVENT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> (Cost Cap) or <code>BID_TYPE_NO_BID</code> (Maximum Delivery). Do not specify <code>deep_bid_type</code> and <code>roas_bid</code>.<ul><li>If <code>optimization_goal</code> is <code>INSTALL</code> or <code>IN_APP_EVENT</code>and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, specify <code>bid_price</code> at the same time.</li></ul></li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> or <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not specify <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If you set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code>, specify <code>roas_bid</code> at the same time.</li></ul></li></ul></li></ul><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>bid_type</code> must be the same across ad groups within the same campaign.</li><li>When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, the following settings, if specified, must be the same for ad groups within the same campaign:<ul><li><code>conversion_bid_price</code></li><li><code>bid_price</code></li><li><code>deep_bid_type</code></li><li><code>roas_bid</code></li></ul></li><li>You can only set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></li></ul></p></td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Billing event</td>
      <td><ul><li>If Goal is Click, the billing event should be CPC.</li><li>If Goal is Install, In-app event, or Value, the billing event should be oCPM.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>When you select a campaign budget mode, the billing event must be the same across ad groups within the same campaign.</li></ul></td>
      <td><code>billing_event</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>INSTALL</code>, <code>IN_APP_EVENT</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When<code>budget_optimize_on</code> is <code>true</code> at the campaign level, specify the same <code>billing_event</code>for ad groups within the same campaign.</td>
    </tr>
    <tr>
      <td>Optimization and bidding <br>· Delivery Type</td>
      <td>Standard (default)</td>
      <td>N/A</td>
      <td>By default, the Delivery Type for the Upgraded Smart+ Campaign is Standard.</td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Budget</td>
      <td><ul><li>When you select an automatic campaign budget mode:<ul><li>The ad group budget is disabled. The campaign-level budget will be used as the ad group budget.</li></ul></li><li>When you select a custom ad group budget strategy, you can use any of the following ad group budget modes:<ul><li>Lifetime</li><li>Daily</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The ad group budget mode, if specified, must be the same across ad groups within the same campaign.</li></ul></td>
      <td><code>budget_mode</code><br><code>budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> at the campaign level, <code>budget_mode</code> and <code>budget</code>are not supported.</li><li>When <code>budget_optimize_on</code> is <code>false</code> at the campaign level:<ul><li>Set <code>budget_mode</code> to <code>BUDGET_MODE_TOTAL</code> or <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>Specify a valid budget via <code>budget</code></li></ul></li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>The <code>budget_mode</code>, if specified, must be the same across ad groups within the same campaign.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Automatic budget increase (when Goal is Value)</td>
      <td>Enabled or disabled<br><br>To enable this setting, the following conditions should be met:<ul><li>At the campaign level: CBO is disabled.</li><li>At the ad group level:<ul><li>Ad group budget is Daily</li><li>Goal is Value</li><li>Bid Strategy is Target ROAS</li><li>Time window of the bid strategy is Day 0 ROAS</li></ul></li></ul></td>
      <td><code>budget_auto_adjust_strategy</code></td>
      <td>Use any of the following values:<ul><li><code>AUTO_BUDGET_INCREASE</code>: To enable automatic budget increase. Allow your budget to automatically increase when your ads are performing well and target CPA, Day 0 target ROAS, and budget requirements are met. Your daily budget will be allowed to automatically increase by 20%, up to 10 times per day, when your budget utilization reaches 90% or more. Your daily budget will reset to your original daily budget each day.</li><li><code>UNSET</code>: To disable automatic budget increase.</li></ul><br>You can only set this field to <code>AUTO_BUDGET_INCREASE</code> when the following conditions are all met:<ul><li>At the campaign level: <code>budget_optimize_on</code> is <code>false</code>.</li><li>At the ad group level:<ul><li><code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code>.</li><li><code>optimization_goal</code> is <code>VALUE</code>.</li><li><code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>.</li><li><code>vbo_window</code> is <code>ZERO_DAY</code>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Enabling automatic budget increase is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Add spend target per ad group <br>&nbsp;&nbsp;· Daily minimum</td>
      <td><ul><li>When you use a daily campaign budget, you can enable or disable this setting.</li><li>When you use a lifetime campaign budget or an ad group budget, this setting is not supported.</li></ul></td>
      <td><code>min_budget</code></td>
      <td><ul><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code> at the campaign level, you can either specify a valid value through this field or leave it unspecified.</li><li>When <code>budget_optimize_on</code> is <code>true</code> and <code>budget_mode</code> is <code>BUDGET_MODE_TOTAL</code> or when <code>budget_optimize_on</code> is <code>false</code> at the campaign level, this field is not supported.</li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Schedule</td>
      <td>Any of the following types:<ul><li>Set start time and run ad group continuously</li><li>Set start and end time</li></ul><br><br><p><span style="color:darkred"><b>Note</b></span>: You can only choose "Set start time and run ad group continuously" in any of the following scenarios:<ul><li>At the campaign level, a daily campaign budget is set.</li><li>At the ad group level, a daily ad group budget is set.</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To set start time and run the ad group continuously, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not specify <code>schedule_end_time</code>.</li><li>To run the ad group between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: You can only set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> in any of the following scenarios:<ul><li>At the campaign level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li><li>At the ad group level, <code>budget_mode</code> is <code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
    </tr>
    <tr>
      <td>Budget and schedule <br>· Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day (default)</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-1 value, or do not specify this field</li><li>Specify a value that contains 0 to indicate non-delivery</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting</td>
      <td><b>Prospect</b><ul><li>Find new customers who want to install your app</li></ul></td>
      <td><code>app_targeting_type</code></td>
      <td><code>PROSPECT</code></td>
    </tr>
    <tr>
      <td>Audience targeting optimization mode</td>
      <td>Any of the following modes:<ul><li><b>Automatic targeting</b><ul><li>You can use automatic targeting to leverage real-time data and machine learning to target audiences most likely to engage with your ads.</li></ul></li><li><b>Custom targeting</b><ul><li>You can use custom targeting settings to precisely control who sees your ads. This may limit delivery and impact campaign performance.</li></ul></li></ul><br>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App</a>.</td>
      <td><code>targeting_optimization_mode</code></td>
      <td>Any of the following values:<ul><li><code>AUTOMATIC</code></li><li><code>MANUAL</code></li></ul>To learn about the supported targeting settings for each mode, see <a href="#item-link-Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App">Available targeting settings for different targeting optimization modes in Upgraded Smart+ Catalog Ads for App</a>.</td>
    </tr>
    <tr>
      <td rowspan="2">Placements and brand safety <br>· Placement</td>
      <td rowspan="2">TikTok</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>["PLACEMENT_TIKTOK"]</code></td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Brand safety and suitability</td>
      <td>These settings only apply to <b>TikTok in-feed</b> and <b>search</b> ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
      <td>N/A</td>
      <td>N/A<br>To retrieve the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112550443073" target="_blank">/tiktok_inventory_filters/get/</a>.<br>To configure or update the ad account-level brand safety and suitability settings in the brand safety hub, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· User comment</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video download</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Allow video sharing</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Specify valid values or not specified</td>
    </tr>
    <tr>
      <td>Placements and brand safety <br>· Pangle block list</td>
      <td>Disabled</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
  </tbody>
</table>

#### Example
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/adgroup/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "campaign_id": "{{campaign_id}}",
    "request_id": "{{request_id}}",
    "adgroup_name": "{{adgroup_name}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "app_id": "{{app_id}}",
    "promotion_type": "APP_IOS",
    "optimization_goal": "IN_APP_EVENT",
    "optimization_event": "LOGIN",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "targeting_optimization_mode": "AUTOMATIC",
    "targeting_spec": {
        "app_targeting_type": "PROSPECT",
        "location_ids": [
            "{{location_ids}}"
        ],
        "operating_systems": [
            "IOS"
        ]
    },
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
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
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "app_config": [],
        "app_id": "{{app_id}}",
        "attribution_event_count": "ONCE",
        "bid_type": "BID_TYPE_NO_BID",
        "billing_event": "OCPM",
        "budget": {{budget}},
        "budget_mode": "BUDGET_MODE_INFINITE",
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "catalog_id": "{{catalog_id}}",
        "click_attribution_window": "SEVEN_DAYS",
        "comment_disabled": false,
        "conversion_bid_price": {{conversion_bid_price}},
        "create_time": "{{create_time}}",
        "dayparting": "{{dayparting}}",
        "deep_bid_type": "AEO",
        "engaged_view_attribution_window": "SEVEN_DAYS",
        "is_hfss": false,
        "is_lhf_compliance": false,
        "min_budget": {{min_budget}},
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "optimization_event": "LOGIN",
        "optimization_event_type": "STANDARD_EVENT",
        "optimization_goal": "IN_APP_EVENT",
        "pacing": "PACING_MODE_SMOOTH",
        "phone_info": {},
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "product_source": "CATALOG",
        "promotion_type": "APP_IOS",
        "schedule_end_time": "{{schedule_end_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "schedule_type": "SCHEDULE_START_END",
        "search_result_enabled": true,
        "secondary_status": "ADGROUP_STATUS_CREATE",
        "share_disabled": false,
        "skip_learning_phase": true,
        "targeting_optimization_mode": "AUTOMATIC",
        "targeting_spec": {
            "actions": [],
            "age_groups": [
                "AGE_18_24",
                "AGE_25_34",
                "AGE_35_44",
                "AGE_45_54",
                "AGE_55_100"
            ],
            "app_targeting_type": "PROSPECT",
            "brand_safety_type": "EXPANDED_INVENTORY",
            "gender": "GENDER_UNLIMITED",
            "location_ids": [
                "{{location_ids}}"
            ],
            "min_ios_version": "14.0",
            "operating_systems": [
                "IOS"
            ],
            "spc_audience_age": "OVER_EIGHTEEN",
            "spending_power": "ALL"
        },
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY"
    }
}
(/code)
```

### Create an ad
Create an ad using [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

Note that the following requirements must be met. To find a complete list of parameters, see [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs/create-an-upgraded-smart-ad/v1.3).

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Ad name (Optional)</td>
      <td>By default, the system will auto-generate the ad name.<br>You can customize the ad name by specifying a valid ad name.</td>
      <td><code>ad_name</code></td>
      <td><ul><li>To have the system auto-generate the ad name, set this field to <code>""</code>(empty string).</li><li>To customize the ad name, specify a non-empty string value.</li></ul></td>
    </tr>
    <tr>
      <td>Product source details <br>· Products</td>
      <td><ul><li>When "Use RTA to automatically select products" is enabled at the campaign level: All products</li><li>When "Use RTA to automatically select products" is disabled, use any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></li></ul></td>
      <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
      <td><ul><li>When <code>rta_product_selection_enabled</code> is <code>true</code> at the campaign level: <code>ALL</code></li><li>When <code>rta_product_selection_enabled</code> is <code>false</code> or not specified at the campaign level, set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to specify <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to specify <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to specify a maximum of 20 available catalog products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Creative assets <br>· Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), specify values to <code>identity_type</code> and <code>identity_id</code> in the <code>ad_configuration</code> object.</li><li>For Spark Ads identities, specify values to <code>identity_type</code> and <code>identity_id</code> within the <code>creative_info</code> object.</li></ul></p></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Specify a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Specify a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
        <tr>
            <td>Creative assets <br>· Autoselect from catalog</td>
            <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Images and videos in your catalog will be used to automatically generate optimized Catalog Ads in all formats for your products.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App">Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App</a>.</li></ul></li><li>Disabled<ul><li>Images and videos in your catalog will not be used to automatically generate optimized Catalog Ads in all formats for your products. The system will automatically create single video ads using your specified content.</li><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>To learn about the combination of available ad creatives based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>.</li></ul><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</p></li></ul></td>
            <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>ad_text_list</code><br><code>tiktok_item_id</code><br><code>ad_format</code></td>
            <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code>, specify a valid value for <code>ad_format</code> in each <code>creative_info</code> object, and specify a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App">Customize the ad format combinations in Upgraded Smart+ Catalog Ads for App</a>.<br><p><span style="color:darkred"><b>Note</b></span>: <code>music_info</code> is required when <code>catalog_creative_toggle</code> is <code>true</code>.</li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>.<ul><li>For a single ad, you can include one to <b>50 ad creatives</b> that can consist of the following types:<ul><li>Videos</li><li>TikTok video posts</li><li>TikTok photo posts</li><li>Carousel</li></ul></li><li>For each creative, specify the corresponding value for <code>ad_format</code> in each <code>creative_info</code> object.</li><li>To learn about configurations of the available ad creative combinations based on identity type, see <a href="https://business-api.tiktok.com/portal/docs?id=1847839781968897" target="_blank">Ad creative combinations by identity and ad type</a>. </li></ul></li></ul></td>
        </tr>
    <tr>
      <td rowspan="2">Call to action</td>
      <td rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value</td>
    </tr>
    <tr>
      <td><code>call_to_action_list</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Interactive add-ons (Optional) (When the ad creatives include videos or video posts )</td>
      <td>Disabled or enabled with up to one add-on in any of the following types:<ul><li>Display Card</li><li>Countdown Sticker</li><li>Gift Code Sticker</li></ul></td>
      <td><code>interactive_add_on_list</code></td>
      <td>Not specified or specify up to one ID of any of the following add-on types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You can only use add-ons when <code>creative_list</code> contains video creatives, including videos and TikTok video posts.</li></ul></p><br>To create an interactive add-on (creative portfolio), use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to obtain the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li><li>To learn about how to obtain the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177" target="_blank">Stickers</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Destination</td>
      <td><ul><li>When the promoted App is an Android App: Google Play</li><li>When the App is an iOS App: App Store</li></ul></td>
      <td><code>landing_page_url_list</code><br><code>page_list</code></td>
      <td><ul><li>When <code>promotion_type</code> at the ad group level is <code>APP_ANDROID</code>, you can only set the Destination as Google Play.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li><li>When <code>promotion_type</code> at the ad group level is <code>APP_IOS</code>, you can only set the Destination as App Store.<ul><li>Do not specify <code>landing_page_url_list</code> and <code>page_list</code>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Deferred deeplink (When Destination is Google Play/App Store)</td>
      <td rowspan="2">Disabled</td>
      <td><code>deeplink_type</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td><code>deeplink</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Build URL parameters</td>
      <td>Disabled</td>
      <td><code>deeplink_utm_params</code></td>
      <td>Not specified</td>
    </tr>
    <tr>
      <td>Disclaimer <br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>disclaimer</code></td>
      <td>Specify a valid value or not specified</td>
    </tr>
  </tbody>
</table>

#### Example

Using catalog creatives only
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "CATALOG_CAROUSEL",
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "identity_type": "CUSTOMIZED_USER",
        "identity_id": "{{identity_id}}",
        "catalog_creative_toggle": true,
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_info": {},
            "catalog_creative_toggle": true,
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "fallback_type": "UNSET",
            "identity_id": "{{identity_id}}",
            "identity_type": "CUSTOMIZED_USER",
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [],
                "tracking_app_id": "{{tracking_app_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}"
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "CATALOG_CAROUSEL",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "CUSTOMIZED_USER",
                    "material_name": "{{material_name}}",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}

(/code)
```

Using non-catalog creatives only
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}",
    "ad_name": "{{ad_name}}",
    "creative_list": [
        {
            "creative_info": {
                "ad_format": "SINGLE_VIDEO",
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            }
        }
    ],
    "ad_text_list": [
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        },
        {
            "ad_text": "{{ad_text}}"
        }
    ],
    "ad_configuration": {
        "product_specific_type": "ALL",
        "catalog_creative_toggle": false,
        "call_to_action_id": "{{call_to_action_id}}"
    }
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
        "ad_configuration": {
            "call_to_action_id": "{{call_to_action_id}}",
            "catalog_creative_info": {},
            "catalog_creative_toggle": false,
            "dark_post_status": "ON",
            "deeplink_utm_params": [],
            "fallback_type": "UNSET",
            "phone_info": {},
            "product_info": {
                "product_image_list": []
            },
            "product_specific_type": "ALL",
            "tracking_info": {
                "app_tracking_info_list": [],
                "tracking_app_id": "{{tracking_app_id}}",
                "tracking_pixel_id": "{{tracking_pixel_id}}"
            },
            "utm_params": []
        },
        "ad_name": "{{ad_name}}",
        "ad_text_list": [
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            },
            {
                "ad_text": "{{ad_text}}"
            }
        ],
        "adgroup_id": "{{adgroup_id}}",
        "adgroup_name": "{{adgroup_name}}",
        "advertiser_id": "{{advertiser_id}}",
        "auto_message_list": [],
        "call_to_action_list": [],
        "campaign_id": "{{campaign_id}}",
        "campaign_name": "{{campaign_name}}",
        "create_time": "{{create_time}}",
        "creative_list": [
            {
                "ad_material_id": "{{ad_material_id}}",
                "creative_info": {
                    "ad_format": "SINGLE_VIDEO",
                    "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "BC_AUTH_TT",
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "material_name": "{{material_name}}",
                    "video_info": {
                        "file_name": "{{file_name}}",
                        "video_id": "{{video_id}}"
                    }
                },
                "material_operation_status": "ENABLE"
            }
        ],
        "custom_product_page_list": [],
        "deeplink_list": [],
        "disclaimer": {},
        "interactive_add_on_list": [],
        "landing_page_url_list": [],
        "modify_time": "{{modify_time}}",
        "operation_status": "ENABLE",
        "page_list": [],
        "secondary_status": "AD_STATUS_AUDIT",
        "smart_plus_ad_id": "{{smart_plus_ad_id}}"
    }
}
(/code)
```


