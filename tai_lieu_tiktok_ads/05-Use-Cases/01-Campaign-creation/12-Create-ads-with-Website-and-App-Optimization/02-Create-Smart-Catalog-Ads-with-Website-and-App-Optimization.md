---
title: "Create Smart+ Catalog Ads with Website and App Optimization"
doc_id: 1842232769643905
path: "Use Cases / Campaign creation / Create ads with Website and App Optimization / Create Smart+ Catalog Ads with Website and App Optimization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create Smart+ Catalog Ads with Website and App Optimization

This article walks you through the steps to create Smart+ Catalog Ads with Website and App Optimization.

## Prerequisite
* Creating Smart+ Catalog Ads using hotel, flight, destination, or entertainment catalogs are currently separate allowlist-only features. If you would like to access them, contact your TikTok representative. You need to apply for a separate allowlist for each catalog type to be used in Smart+ Catalog Ads.
* Creating Smart+ Catalog Ads with Website and App Optimization (with `sales_destination` set to `WEB_AND_APP`) is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.
* Creating Smart+ Catalog Ads requires setting up an E-commerce, a hotel, a flight, a destination, or an entertainment catalog in your Business Center first.
<br>To set up the catalog:
- 1. Create an E-commerce, a hotel, a flight, a destination, or an entertainment catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961).
- 2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
- 3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889). 
<br>Pass in the `feed_log_id` obtained from Step ii. If the field `error_affected_products` in the response is not null, examine the issue details and return to Step ii to reupload the product.
- 4. (Optional) Create a product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
<br>If you want to have products selected from a product set, creating a product set is necessary. Otherwise, you can skip this step.
- 5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505). 
<br> You can also choose `advertiser_role` that you want to assign to the members invited.
- 6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121). 
<br> Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field. 

For all catalog APIs, see [here](https://business-api.tiktok.com/portal/docs?id=1739578477445121).


## Steps

Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
> Smart+ Catalog ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.


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
      <td><code>virtual_objective_type</code></td>
      <td><code>SALES</code></td>
    </tr>
    <tr>
      <td rowspan="2">Sales destination</td>
      <td rowspan="2">Website and app</td>
      <td><code>objective_type</code></td>
      <td><code>WEB_CONVERSIONS</code></td>
    </tr>
   <tr>
      <td><code>sales_destination</code></td>
      <td><code>WEB_AND_APP</code></td>
    </tr>
    <tr>
      <td>Smart+ Campaign</td>
      <td><b>Smart+ Campaign</b></td>
      <td><code>spc_type</code></td>
      <td><code>WEB_ALL_IN_ONE</code></td>
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
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Pass a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
      <td><code>special_industries</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Campaign Budget Optimization (CBO)</td>
      <td>N/A<br><br>CBO is enabled by default for Smart+ Web Campaigns.</td>
      <td>N/A</td>
      <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart+ Web Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
    </tr>
    <tr>
      <td rowspan="3">Product source details<br>&#183; Use Catalog</td>
      <td rowspan="3"><b>Enabled with an E-commerce, a hotel, a flight, a destination, or an entertainment catalog with at least two approved products</b></td>
      <td><code>web_all_in_one_catalog_status</code></td>
      <td><code>OPEN</code></td>
    </tr>
    <tr>
      <td><code>product_source</code></td>
      <td><code>CATALOG</code></td>
    </tr>
    <tr>
      <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
      <td>Pass valid values.<br>The catalog (<code>catalog_id</code>) needs to have at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 2.<br><br>To obtain E-commerce, hotel, flight, destination, or entertainment catalogs within a Business Center, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/get/</a>. The <code>catalog_type</code> should be <code>ECOM</code>, <code>HOTEL</code>, <code>FLIGHT</code>, <code>DESTINATION</code>, or <code>ENTERTAINMENT</code>.<br><br>To learn about how to create an E-commerce, a hotel, a flight, a destination, or an entertainment catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</td>
    </tr>
    <tr>
      <td>Product source details<br>&#183; Products</td>
      <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you select a destination catalog or an entertainment catalog, this option is not supported.</td>
      <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
      <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 available E-commerce, hotel, or flight catalog products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization location<br>&#183; iOS & App</td>
      <td rowspan="2">Specify any of the following:<ul><li>an Android app</li><li>an iOS app</li><li>an Android app and an iOS app</li></ul></td>
      <td><code>app_config</code></td>
      <td>Specify any of the following:<ul><li>App ID of an Android app</li><li>App ID of iOS app</li><li>App ID of Android app and that of an iOS app</li></ul>To obtain the list of App IDs within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a> and check the returned <code>app_id</code>.</td>
    </tr>
    <tr>
      <td><code>app_id</code></td>
      <td>Pass valid values</td>
    </tr>
    <tr>
      <td>Optimization goal</td>
      <td>Any of the following types:<ul><li>Conversion</li><li>Value</li></ul></td>
      <td><code>optimization_goal</code></td>
      <td>Any of the following values:<ul><li><code>CONVERT</code></li><li><code>VALUE</code></li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Optimization location<br>&#183; Web data connection</td>
      <td rowspan="2">Specify a valid Pixel</td>
      <td><code>pixel_id</code></td>
      <td>Specify a valid value<br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
    </tr>
    <tr>
      <td><code>promotion_type</code></td>
      <td><code>WEBSITE</code></td>
    </tr>
    <tr>
      <td>Optimization event</td>
      <td>Specify a valid optimization event<ul><li>When optimization goal is Value, specify the optimization event as Complete Payment.</li></ul></td>
      <td><code>optimization_event</code></td>
      <td>Specify a valid value.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.<br><br>To learn about the supported event types, see <a href="#item-link-List of values for optimization_event for Smart+ Catalog Ads">List of values for <code>optimization_event</code> for Smart+ Catalog Ads</a>.<br>You need to ensure the specified optimization event is active for both the Pixel and the app or apps.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</li><li>To obtain the active optimization events for an app, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859338750977" target="_blank">/app/optimization_event/</a>.</li></ul></td>
    </tr>
    <tr>
      <td>Bid Strategy</td>
      <td><ul><li>If optimization goal is Conversion, the strategy can be Maximum Delivery or Target CPA.</li><li>If optimization goal is Value, the strategy can only be Highest value.</li></ul></td>
      <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td><ul><li>If <code>optimization_goal</code> is <code>CONVERT</code>, set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code>.<ul><li>If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>, set <code>deep_bid_type</code> to <code>VO_HIGHEST_VALUE</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.</td>
    </tr>
    <tr>
      <td>Attribution settings</td>
      <td>Select from the supported attribution setting options for the specific scenario.</td>
      <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>engaged_view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
      <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Website%20conversions" target="_blank">Attribution window and event count - Website conversions</a>.</td>
    </tr>
    <tr>
      <td>Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td>Budget mode</td>
      <td>Dynamic daily budget</td>
      <td><code>budget_mode</code></td>
      <td><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></td>
    </tr>
    <tr>
      <td>Schedule type</td>
      <td>Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To run the campaign continuously after the scheduled start time, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not pass <code>schedule_end_time</code>.</li><li>To run the campaign between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Dayparting (Optional)</td>
      <td>Any of the following types:<ul><li>All day</li><li>Select specific time</li></ul></td>
      <td><code>dayparting</code></td>
      <td>Any of the following configurations:<ul><li>Specify an all-0 value, an all-1 value, or not specified</li><li>Specify a value that's not an all-0 value or an all-1 value</li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Location</td>
      <td>The location should match or be the subset of the targeting location of your catalog.</td>
      <td><code>location_ids</code> or <code>zipcode_ids</code></td>
      <td>Pass IDs of locations that match or are a subset of the targeting location of <code>catalog_id</code>.<br>To obtain the targeting location of a catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/get/</a> and check the returned <code>country</code>.</td>
    </tr>
    <tr>
      <td rowspan="2">Audience targeting<br>&#183; Minimum age</td>
      <td rowspan="2">Any of the following types:<ul><li>18</li><li>25</li></ul></td>
      <td><code>spc_audience_age</code></td>
      <td>Any of the following values:<ul><li><code>18+</code></li><li><code>25+</code></li></ul></td>
    </tr>
	<tr>
      <td><code>exclude_age_under_eighteen</code></td>
      <td><code>true</code></td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Languages (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Gender (Optional)</td>
      <td>Supported with allowlist<br><br><p><span style="color:darkred"><b>Note</b></span>: Gender targeting in Smart+ Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
      <td><code>gender</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Exclude audience (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Placement</td>
      <td>TikTok placement including search result or Automatic placement</td>
      <td><code>placement_type</code><br><code>placements</code></td>
      <td>Not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated placement selection is a key feature of Smart+ Campaigns. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored. To retrieve the exact placement setting of Smart+ Catalog Ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a>.</td>
    </tr>
    <tr>
      <td>User comment (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video download (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video sharing (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Pangle block list (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>blocked_pangle_app_ids</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Brand safety and suitability</td>
      <td>N/A <br>These settings only apply to TikTok in-feed ads. Any previous account-level settings in the brand safety hub will apply to your campaign.</td>
      <td>N/A</td>
      <td>Any previous account-level settings in the Brand Safety Hub will apply to your Smart+ Campaign.<br>To set the Brand Safety Hub settings of an ad account first, use <a href="https://business-api.tiktok.com/portal/docs?id=1830112569774274" target="_blank">/tiktok_inventory_filters/update/</a>.</td>
    </tr>
    <tr>
      <td>Destination</td>
      <td>Website</td>
      <td>N/A</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td>Deeplink</td>
      <td>Enabled with a custom deeplink</td>
      <td><code>landing_page_url</code></td>
      <td>Specify a deeplink.<br>The deeplink format can be Apple’s universal link or Android App Link starting with <code>http://</code> or <code>https://</code>.</td>
    </tr>
    <tr>
	   <td>URL parameters (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Specify valid values or not specified<br><br>You can manually add URL parameters to the custom link by directly setting <code>landing_page_url</code> to a URL that already includes URL parameters.<ul><li>However, if you use a custom link in your ad and specify <code>utm_params</code> at the same time, the URL parameters will not be automatically appended to the custom link (<code>landing_page_url</code>) upon ad delivery. In such cases, you need to ensure that you specify a <code>landing_page_url</code> with the URL parameters already appended, and that <code>utm_params</code> exactly matches the used URL parameters. Otherwise, an error will occur.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="3">Identity</td>
      <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>For Spark Ads identities (<code>AUTH_CODE</code>, <code>TT_USER</code>, or <code>BC_AUTH_TT</code>), pass values to <code>identity_type</code> and <code>identity_id</code> within the <code>media_info</code> object.</li><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), pass values to <code>identity_type</code> and <code>identity_id</code> outside of the <code>media_info</code> object.</li></ul></td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Pass a valid value<br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
    <tr>
      <td>Ad creative<br>&#183; Auto-selection of creatives from your catalog</td>
      <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>Catalog creatives will be used to generate ads.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Smart+ Catalog Ads">Customize the ad format combinations in Smart+ Catalog Ads</a>.</li></ul></li><li>Disabled<ul><li>Catalog creatives will not be used to generate ads. The system will automatically create single video ads using your specified content.</li><li>Requirements:<ul><li>Specify non-catalog creatives based on identity type:<ul><li>For Custom User identity, specify one to 30 videos with cover images and one to five ad texts.</li><li>For Authorized User identity, or Spark Ads PULL with TikTok User or TikTok Account User in Business Center identity, specify one to 30 TikTok video posts.</li></ul></li><li>Specify 1-5 ad texts</li></ul></li><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</li></ul></li></ul></td>
      <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>title_list</code><br><code>tiktok_item_id</code></td>
      <td><ul><li>To enable auto-selection of catalog creatives:<ul><li>Set <code>catalog_creative_toggle</code> to <code>true</code></li><li>Specify a piece of music via <code>music_info</code>.</li><li>Set <code>identity_type</code> to <code>CUSTOMIZED_USER</code>.</li><li>Specify one to five ad texts (<code>title_list</code>).</li><li>For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Smart+ Catalog Ads">Customize the ad format combinations in Smart+ Catalog Ads</a>.</li></ul></li><p><span style="color:darkred"><b>Note</b></span>: <code>music_info</code> is required when <code>catalog_creative_toggle</code> is <code>true</code>.</li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>, and specify non-catalog creatives based on identity type:<ul><li>If <code>identity_type</code> is <code>CUSTOMIZED_USER</code>, specify one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>).</li><li>If <code>identity_type</code> is not <code>CUSTOMIZED_USER</code>, specify one to 30 TikTok video posts (<code>tiktok_item_id</code>). Do not specify ad texts (<code>title_list</code>).</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Interactive add-ons<br>&#183; Auto-selection of interactive add-ons (Optional)</td>
      <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>The most suitable interactive add-ons will be automatically applied.</li></ul></li><li>Disabled<br><p><span style="color:darkred"><b>Note</b></span>: When you use an E-commerce catalog, you can only disable this setting.</li></ul></td>
      <td><code>automatic_add_on_enabled</code><br><code>card_list</code></td>
      <td>If the single video or catalog video ad format is available, you can either enable (recommended) or disable auto-selection of interactive add-ons in the generated ads.<ul><li>To enable auto-selection of interactive add-ons, set <code>automatic_add_on_enabled</code> to <code>true</code>, and do not pass <code>card_list</code>. The system will automatically generate a Hotel Card, Flight Card, Destination Card, or Movie Card portfolio based on the specified catalog type via the returned <code>card_list</code>.</li><li>To disable auto-selection of interactive add-ons, set <code>automatic_add_on_enabled</code> to <code>false</code>, and do not pass <code>card_list</code>. The returned <code>card_list</code> will be null.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you specify an E-commerce catalog through <code>catalog_id</code>, you can only set <code>automatic_add_on_enabled</code> to <code>false</code>.<br><br>To learn about Hotel Card, Flight Card, Destination Card, or Movie Card portfolios, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058" target="_blank">Cards</a>.</td>
    </tr>
    <tr>
      <td>Call to action</td>
      <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul></td>
      <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
      <td><ul><li>To use dynamic call to action, pass <code>call_to_action_id</code>.</li><li>To use standard call to action, pass <code>call_to_action_list</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Disclaimer (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>disclaimer_info</code></td>
      <td>Not specified or specify valid values</td>
    </tr>
    <tr>
      <td>Third-party tracking settings<br>&#183; Impression tracking URL for iOS (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>app_id</code><br><code>app_type</code><br><code>impression_tracking_url</code></td>
      <td>Any of the following configurations:<ul><li>Not specified</li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for iOS</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Third-party tracking settings<br>&#183; Click tracking URL for iOS (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>app_id</code><br><code>app_type</code><br><code>click_tracking_url</code></td>
      <td>Any of the following configurations:<ul><li>Not specified</li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an iOS app</li><li>Set <code>app_type</code> to <code>APP_IOS</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for iOS</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Third-party tracking settings<br>&#183; Impression tracking URL for Android (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>app_id</code><br><code>app_type</code><br><code>impression_tracking_url</code></td>
      <td>Any of the following configurations:<ul><li>Not specified</li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>impression_tracking_url</code> to the Impression tracking URL for Android</li></ul></li></ul></td>
    </tr>
    <tr>
      <td>Third-party tracking settings<br>&#183; Click tracking URL for Android (Optional)</td>
      <td>Disabled or enabled</td>
      <td><code>app_id</code><br><code>app_type</code><br><code>click_tracking_url</code></td>
      <td>Any of the following configurations:<ul><li>Not specified</li><li>Specify valid values:<ul><li>Set <code>app_id</code> to the App ID of an Android app</li><li>Set <code>app_type</code> to <code>APP_ANDROID</code></li><li>Set <code>click_tracking_url</code> to the Click tracking URL for Android</li></ul></li></ul></td>
    </tr>
  </tbody>
</table>

### List of values for `optimization_event` for Smart+ Catalog Ads

The following tables list the events supported for Smart+ Catalog Ads with Website and App Optimization.
``` xtable
| Event name{20%} | <code>optimization_event</code> value{25%} | Corresponding Pixel event<br>(<code>optimization_event</code>){20%} | Corresponding app event<br>(<code>optimization_event</code> or <code>secondary_optimization_event</code>){35%} |
|---|---|---|---|
| Add payment info | `ADD_BILLING` | `ADD_BILLING` | `ADD_PAYMENT_INFO` |
| Add to Cart | `ON_WEB_CART` | `ON_WEB_CART` | `IN_APP_CART` |
| Add to wishlist | `ON_WEB_ADD_TO_WISHLIST` | `ON_WEB_ADD_TO_WISHLIST` | `ADD_TO_WISHLIST` |
| Initiate Checkout/Checkout | `INITIATE_ORDER` | `INITIATE_ORDER` | `IN_APP_ORDER` |
| Purchase | `SHOPPING` | `SHOPPING` | `ACTIVE_PAY` |
| Search | `ON_WEB_SEARCH` | `ON_WEB_SEARCH` | `SEARCH` |
| Subscribe | `ON_WEB_SUBSCRIBE` | `ON_WEB_SUBSCRIBE` | `SUBSCRIBE` |
| View Content | `ON_WEB_DETAIL` | `ON_WEB_DETAIL` | `IN_APP_DETAIL_UV` |
```
### Customize the ad format combinations in Smart+ Catalog Ads
If you enable auto-selection of catalog creatives (`catalog_creative_toggle` is `true`), the system will automatically create ads using combinations of three possible formats: catalog carousel, catalog video, and single video.

The possible ad format combinations for your Smart+ Catalog Ads are illustrated in the following table.

```xtable
| Is the catalog carousel format available? {20%}| Is the catalog video format available? {20%}| Is the single video format available? {20%}| Ad format combination for your Smart+ Catalog Ads{40%} |
|---|---|---|---|
| ✅ | ❌ | ❌ | Catalog carousel only |
| ✅ | ❌ | ✅ | Catalog carousel and single video |
| ✅ | ✅ | ❌ | Catalog carousel and catalog video |
| ✅ | ✅ | ✅ | Catalog carousel, catalog video, and single video |
```

To ensure certain ad formats are available in Smart+ Catalog Ads, follow the instructions in the following table.
<table>
  <thead>
  <tr>
    <th style="width:15%">Ad format</th>
    <th style="width:20%">Ad format description</th>
    <th style="width:25%">How to ensure the ad format is available</th>
    <th style="width:40%">How to configure the parameters</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td rowspan="2">catalog carousel</td>
    <td rowspan="2">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time</td>
    <td>The catalog needs to contain at least two images supported in Catalog Carousel Ads.</td>
    <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Specify a piece of music supported in Catalog Carousel Ads</td>
    <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122"  target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650"  target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650"  target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>catalog video</td>
    <td>Generate a dynamic video based on information from the product catalog</td>
    <td>The catalog needs to be bound to at least one video template or you need to configure a video URL for each catalog product. <br>The system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video or use the video URL for the catalog product.</td>
    <td>Ensure that at least one video template is bound to your catalog (<code>catalog_id</code>) or a video URL (<code>video_url</code>) has been configured for each catalog product. <ul><li>To ensure a video URL has been configured for each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402"  target="_blank">/catalog/product/get/</a> and check the returned <code>video_url</code>.<ul><li>If you want to add video URLs for your catalog products, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546"  target="_blank">/catalog/product/update/</a>.</li></ul></li><li>To ensure that at least one video template (video package) is bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073"   target="_blank">/catalog/video_package/get/</a>.<ul><li>To learn about how to create video templates on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog"  target="_blank">How to create video packages in a Catalog</a>.</li><li>To retrieve video templates for your catalog via API, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073"  target="_blank">/catalog/video_package/get/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>single video</td>
    <td>Use specified video creatives to promote your products</td>
    <td>Specify additional videos</td>
    <td>Specify one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>).</li></ul></td>
  </tr>
</tbody>
</table>

### Example

Request
```xcodeblock
(code http)
curl --location   --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{AccessToken}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "virtual_objective_type": "SALES",
    "objective_type": "WEB_CONVERSIONS",
    "sales_destination": "WEB_AND_APP",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "web_all_in_one_catalog_status": "OPEN",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    "product_specific_type": "ALL",
    "app_config": [
        {
            "app_id": "{{app_id}}"
        },
        {
            "app_id": "{{app_id}}"
        }
    ],
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "promotion_type": "WEBSITE",
    "optimization_event": "SHOPPING",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "location_ids": [
        "1880251"
    ],
    "spc_audience_age": "18+",
    "exclude_age_under_eighteen": true,
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "catalog_creative_toggle": true,
    "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",
    "media_info_list": [
        {
            "media_info": {
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "automatic_add_on_enabled": true,
    "call_to_action_list": [
        {
            "call_to_action": "LEARN_MORE"
        }
    ]
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "roas_bid": null,
        "app_download_url": null,
        "media_info_list": [
            {
                "media_info": {
                    "aigc_disclosure_type": null,
                    "identity_type": "CUSTOMIZED_USER",
                    "image_info": null,
                    "tiktok_item_id": null,
                    "identity_authorized_bc_id": "0",
                    "music_info": {
                        "music_id": "{{music_id}}"
                    },
                    "identity_id": "{{identity_id}}",
                    "video_info": null
                },
                "ad_material_id": "{{ad_material_id}}",
                "material_operation_status": "ENABLE"
            }
        ],
        "call_to_action_id": null,
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "campaign_id": "{{campaign_id}}",
        "app_promotion_type": null,
        "bid_price": null,
        "tracking_app_id": null,
        "create_time": "{{create_time}}",
        "deep_funnel_optimization_status": null,
        "identity_type": "CUSTOMIZED_USER",
        "utm_params": null,
        "sales_destination": "WEB_AND_APP",
        "app_id": null,
        "spc_audience_age": "18+",
        "disable_skan_campaign": null,
        "identity_id": "{{identity_id}}",
        "click_tracking_url": null,
        "budget": {{budget}},
        "bid_align_type": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "deep_funnel_event_source": null,
        "product_specific_type": "ALL",
        "pixel_id": "{{pixel_id}}",
        "adgroup_secondary_status": "ADVERTISER_ACCOUNT_PUNISH",
        "deeplink": "",
        "click_attribution_window": "SEVEN_DAYS",
        "virtual_objective_type": "SALES",
        "is_advanced_dedicated_campaign": false,
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "deep_funnel_event_source_id": null,
        "exclude_age_under_eighteen": true,
        "product_ids": null,
        "optimization_event": "SHOPPING",
        "engaged_view_attribution_window": "ONE_DAY",
        "promotion_type": "WEBSITE",
        "landing_page_urls": [
            {
                "tiktok_item_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "video_id": null
            }
        ],
        "deep_funnel_optimization_event": null,
        "carousel_image_labels": null,
        "automatic_add_on_enabled": true,
        "carousel_image_index": null,
        "app_type": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "advertiser_id": "{{advertiser_id}}",
        "attribution_event_count": "EVERY",
        "app_name": null,
        "modify_time": "{{modify_time}}",
        "comment_disabled": false,
        "campaign_type": "REGULAR_CAMPAIGN",
        "call_to_action_list": [
            {
                "call_to_action": "LEARN_MORE"
            }
        ],
        "shopping_ads_video_package_id": "",
        "impression_tracking_url": null,
        "campaign_app_profile_page_state": null,
        "product_source": "CATALOG",
        "is_smart_performance_campaign": true,
        "excluded_audience_ids": null,
        "product_set_id": null,
        "promotion_target_type": null,
        "spc_type": "WEB_ALL_IN_ONE",
        "disclaimer_info": null,
        "zipcode_ids": null,
        "app_tracking_info_list": [
            {
                "app_type": "APP_ANDROID",
                "app_id": "{{app_id}}",
                "click_tracking_url": "{{click_tracking_url}}",
                "impression_tracking_url": "{{impression_tracking_url}}"
            },
            {
                "app_type": "APP_IOS",
                "app_id": "{{app_id}}",
                "click_tracking_url": "",
                "impression_tracking_url": ""
            }
        ],
        "share_disabled": false,
        "gender": "GENDER_UNLIMITED",
        "scheduled_budget": 0,
        "vbo_window": null,
        "card_list": [
            {
                "card_id": "{{card_id}}"
            }
        ],
        "shopping_ads_fallback_type": null,
        "view_attribution_window": "ONE_DAY",
        "app_config": [
            {
                "app_id": "{{app_id}}"
            },
            {
                "app_id": "{{app_id}}"
            }
        ],
        "placements": [
            "PLACEMENT_TIKTOK",
            "PLACEMENT_PANGLE"
        ],
        "deeplink_type": null,
        "catalog_creative_toggle": true,
        "brand_safety_type": "EXPANDED_INVENTORY",
        "schedule_type": "SCHEDULE_START_END",
        "objective_type": "WEB_CONVERSIONS",
        "placement_type": "PLACEMENT_TYPE_AUTOMATIC",
        "billing_event": "OCPM",
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "campaign_name": "{{campaign_name}}",
        "blocked_pangle_app_ids": null,
        "product_info": null,
        "languages": [],
        "skip_learning_phase": true,
        "conversion_bid_price": 1.2,
        "operation_status": "ENABLE",
        "special_industries": null,
        "campaign_secondary_status": "ADVERTISER_ACCOUNT_PUNISH",
        "deep_bid_type": null,
        "page_list": null,
        "video_download_disabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "web_all_in_one_catalog_status": "OPEN",
        "catalog_id": "{{catalog_id}}",
        "optimization_goal": "CONVERT",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "location_ids": [
            "1880251"
        ],
        "promotion_website_type": "UNSET"
    }
}
(/code)
```


