---
title: "Mapping between campaign features in TikTok Ads Manager and API configurations"
doc_id: 1740673047618561
path: "Marketing API / Campaign Management / Guides / Mapping between campaign features in TikTok Ads Manager and API configurations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Mapping between campaign features in TikTok Ads Manager and API configurations

This article provides the mapping between TikTok Ads Manager (TTAM) features at the campaign, ad group, and ad levels and the corresponding API configurations. If you are a TikTok Ads Manager user seeking to integrate with TikTok API for Business, we recommend that you read this article beforehand to gain a clear understanding of how campaign features in TikTok Ads Manager are supported in the TikTok API for Business.

Note that the mappings presented below may not cover all possible scenarios. For settings related to features not listed in this document, refer to [Campaign Management](https://business-api.tiktok.com/portal/docs?id=1735713781404673).


## Campaign level
To create a campaign, use [/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602).

The table below lists the settings on TTAM at the campaign level and the corresponding API configurations.

<table>
<thead>
  <tr>
    <th style="width:24%">TTAM Module</th>
    <th style="width:20%">TTAM setting</th>
    <th style="width:30%">UI Reference</th>
    <th style="width:26%">API configuration</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="3">Advertising objective<br></td>
    <td>Advertising objective</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b7aacf70bbdd226a891b63b76b473f44" alt="Advertising objective" style="color:#808080"></td>
    <td><code> objective_type</code></td>
  </tr>
  <tr>
    <td>Advertising objective as Sales</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d19a68c53dcc616b16948429eb1a534d"  alt="Advertising objective as Sales" style="color:#808080"><br></td>
    <td><code>virtual_objective_type</code><br><br>Learn about how to <a href="https://business-api.tiktok.com/portal/docs?id=1834189985160193" target="_blank">upgrade from Product Sales or Website Conversions to the Sales objective</a>.</td>
  </tr>
  <tr>
    <td>Sales destination</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a32db51f1bfd3934377b61bdb7076752"  alt="Sales destination" style="color:#808080"><br></td>
    <td><code>sales_destination</code></td>
  </tr>
  <tr>
    <td  rowspan="2">Campaign type</td>
    <td>App promotion type<br>(when Advertising objective = App promotion)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3421651043ce86f6d76b047c873aaaf7"  alt="App promotion type" style="color:#808080"><br></td>
    <td><code>app_promotion_type</code></td>
  </tr>
    <tr>
    <td>Smart+ campaign</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5b9bcc16faa36175c84f839570a5013d"  alt="Smart+ campaign" style="color:#808080"><br></td>
    <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362" target="_blank">/campaign/spc/create/</a> to create a Smart+ Campaign.</td>
  </tr>
  <tr>
    <td rowspan="5">iOS 14 dedicated campaign</td>
    <td>Use iOS 14 dedicated campaign</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2461558a808beec7b0e3935c9d5ba547"  alt="Use iOS 14 dedicated campaign" style="color:#808080"><br></td>
    <td><code>campaign_type</code></td>
  </tr>
  <tr>
    <td>App</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/88b17829ac1bbffdd25cd1cbe74e4727"  alt="App" style="color:#808080"><br></td>
    <td><code>app_id</code></td>
  </tr>
    <tr>
    <td>Use advanced dedicated campaign</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/974addbf8f018cdf99aa553f8d4eb4b4" alt="Use advanced dedicated campaign" style="color:#808080" ></td>
    <td><code>is_advanced_dedicated_campaign</code></td>
  </tr>
    <tr>
    <td>SKAN attribution</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/63619b9de6ce977d9292355f7535c38a" alt="SKAN attribution" style="color:#808080" ></td>
    <td><code>disable_skan_campaign</code></td>
  </tr>
  <tr>
    <td>Use app profile page to optimize delivery</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/91ddc4180e7b8ce08020c424184d38a2"  alt="Use app profile page to optimize delivery" style="color:#808080"><br></td>
    <td><code>campaign_app_profile_page_state</code></td>
  </tr>
  <tr>
    <td>Campaign type<br>(when Buying type = Reach & Frequency <br>& Advertising objective = Reach)</td>
    <td>Campaign type</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a185bc44debe709d52e22bfd2b33adfe"  alt="Campaign type" style="color:#808080"><br></td>
    <td><code>rf_campaign_type</code></td>
  </tr>
  <tr>
    <td>Product source <br>(when Buying type = Auction<br> & Advertising objective = Product Sales)</td>
    <td>Product source</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/94e738812cf322bfba858ac9d933b674"  alt="Product source" style="color:#808080"><br></td>
    <td><code>campaign_product_source</code></td>
  </tr>
  <tr>
    <td rowspan="6">Setting<br></td>
    <td>Campaign name</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c271fdef8462ffb8359b62ecfc021e49"  alt="Campaign name" style="color:#808080"><br></td>
    <td><code>campaign_name</code></td>
  </tr>
  <tr>
    <td>Special ad categories</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/033fe0d3e5d201e83d3da6d3aeca9327"  alt="Special ad categories" style="color:#808080"></td>
    <td><code>special_industries</code></td>
  </tr>
  <tr>
    <td>Campaign budget optimization</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3c13a7195d4509e8b4463f0d8029d0bc"  alt="Campaign budget optimization" style="color:#808080"></td>
    <td><code>budget_optimize_on</code></td>
  </tr>
  <tr>
    <td>Set campaign budget</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/41515b6686c5af027191251d60ad06f1"  alt="Set campaign budget" style="color:#808080"></td>
    <td><code>budget_mode</code><br><code>budget</code></td>
  </tr>
  <tr>
    <td>Realtime API (RTA)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/347cbd5faaae4007791e204a867adef0"  alt="Realtime API (RTA)" style="color:#808080"></td>
    <td><code>rta_id</code></td>
  </tr>
  <tr>
    <td>Use RTA to automatically select products</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/03bdffef695c06a7202174b0b3c0026b"  alt="Use RTA to automatically select products" style="color:#808080"></td>
    <td><code>rta_product_selection_enabled</code></td>
  </tr>
  <tr>
    <td>Campaign list </td>
    <td>Campaign - On/Off</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/34f69c1dad3d97f2251bec1cfd492d51" alt="Campaign - On/Off" style="color:#808080" ></td>
    <td><code>operation_status</code></td>  
  </tr>
</tbody>
</table>


## Ad group level
To create an ad group, use [/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114).

The table below lists the settings on TTAM at the ad group level and the corresponding API configurations.

<table>
<thead>
  <tr>
    <th style="width:22%">TTAM Module</th>
    <th style="width:20%">TTAM setting</th>
    <th style="width:30%">UI Reference</th>
    <th style="width:28%">API configuration</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Settings</td>
    <td>Ad group name<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/53af7a258f0a43bcdb66dd6add106fd3" alt="Ad group name" style="color:#808080" ></td>
    <td><code>adgroup_name</code></td>
  </tr>
  <tr>
    <td rowspan="2">Shopping ads type<br>(when Buying type = Auction &amp; Advertising objective = Product Sales)</td>
    <td>Shopping ads type<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b8bd343b2e946a0e6980d507eb454bc2" alt="Shopping ads type" style="color:#808080" ></td>
    <td><code>shopping_ads_type</code></td>
  </tr>
  <tr>
    <td>Identity<br>(When Shopping ads type = Live Shopping Ads)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ea4fac99fe3ae62478724a0cfea111b1" alt="Identity" style="color:#808080" ></td>
    <td><code>identity_id</code><br><code>identity_type</code><br><code>identity_authorized_bc_id</code></td>
  </tr>
  <tr>
    <td rowspan="3">Product source details<br>(when Buying type = Auction <br>& Advertising objective = Product Sales <br>& Shopping ads type = Video Shopping Ads)</td>
    <td>Catalog</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13e3a36a9c255a1abb2c9dc9061908e0" alt="Catalog" style="color:#808080" ></td>
    <td><code>product_source</code><br><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
  </tr>
  <tr>
    <td>TikTok Shop</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/35c08671e94a0286df3d2fdb895d65ae" alt="TikTok Shop" style="color:#808080" ></td>
    <td><code>product_source</code><br><code>store_id</code><br><code>store_authorized_bc_id</code></td>
  </tr>
  <tr>
    <td>Showcase</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/45b41087ef846c49a72dc280f3d94dfd" alt="Showcase" style="color:#808080" ></td>
    <td><code>product_source</code><br><code>identity_id</code><br><code>identity_type</code><br><code>identity_authorized_bc_id</code></td>
  </tr>
  <tr>
    <td rowspan="8">Optimization location</td>
    <td>Optimization location<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/fbbd358d48b576270383726d87b6a143" alt="Optimization location" style="color:#808080" ></td>
    <td><code>promotion_type</code></td>
  </tr>
  <tr>
    <td>Optimization location<br> (when Advertising objective = lead generation)<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7f4e1ff88a4a5fd4b6d2e43b889f1df8" alt="Optimization location" style="color:#808080" ></td>
    <td><code>promotion_target_type</code></td>
  </tr>
  <tr>
    <td>Optimization location<br> (when App promotion type = pre-registration)<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f4c92fb29dab26c74211d93ee42a74f" alt="Optimization location" style="color:#808080" ></td>
    <td><code>promotion_website_type</code></td>
  </tr>
  <tr>
    <td>App <br>(when Advertising objective = App promotion)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c6f33015c7b9a48cdc85b266bad2508e" alt="App " style="color:#808080" ></td>
    <td><code>app_id</code> </td>
  </tr>
  <tr>
    <td>TikTok Pixel</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8e196b4d528aafb9f8572e8728866f7d" alt="TikTok Pixel" style="color:#808080" ></td>
    <td><code>pixel_id</code></td>
  </tr>
  <tr>
    <td>Optimization event</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bf5dbde9a4700f847559a11afb4a3132" alt="Optimization event" style="color:#808080" ></td>
    <td><code>optimization_event</code></td>
  </tr>
 <tr>
    <td>Select your Instant messaging app or use a customized URL<br>(when Optimization location is Instant messaging apps) <ul><li>Messenger</li><li>WhatsApp</li><li>Instant Messaging URL</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/66ef4ed80cc18bad1272f4a946d12a57" alt="Select your Instant messaging app or use a customized URL" style="color:#808080" ></td>
    <td><code>messaging_app_type</code><br><code>messaging_app_account_id</code><br><code>phone_region_code</code><br><code>phone_region_calling_code</code><br><code>phone_number</code></td>
  </tr>
  <tr>
    <td>Deep funnel optimization<ul><li>Data connection</li><li>Optimization event</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d1adc1daf70ef7a0b2a1d7ca2dc5b07a" alt="Deep funnel optimization" style="color:#808080" ></td>
    <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
  </tr>
  <tr>
    <td rowspan="7">Placements</td>
    <td>Automatic placement<br>Select placement</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b602f6919ca1fa97934cdace28e488a7" alt="Automatic placement or >Select placement" style="color:#808080" ></td>
    <td><code>placement_type</code><br><code>placements</code></td>
  </tr>
  <tr>
    <td>Include search results</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/845b33aba3a71ae06b3573dad9980c35" alt="Include search results" style="color:#808080" ></td>
    <td><code>search_result_enabled</code></td>
  </tr>
  <tr>
    <td><ul><li>In-feed</li><li>Search feed</li><li>TikTok Lite (Japan & Korea)</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/95122f7f3fec4df0c0a8e302f2363f5f" alt="In-feed, Search feed, TikTok Lite (Japan & Korea)" style="color:#808080" ></td>
    <td><code>tiktok_subplacements</code></td>
  </tr>
  <tr>
    <td>User comment</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/44ddac41f7910afd63890de797f5108d" alt="User comment" style="color:#808080" ></td>
    <td><code>comment_disabled</code></td>
  </tr>
  <tr>
    <td>Video download</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4df90370414e43fc613e7486b7b9ce78" alt="Video download" style="color:#808080" ></td>
    <td><code>video_download_disabled</code></td>
  </tr>
  <tr>
    <td>Video sharing</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e3d4036a54666c50c05e06c34a4bc249" alt="Video sharing" style="color:#808080" ></td>
    <td><code>share_disabled</code></td>
  </tr>
  <tr>
    <td>Pangle block list</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/76ae64f3b6cb240aee7d5895c7c4ef31" alt="Pangle block list" style="color:#808080" ></td>
    <td><code>blocked_pangle_app_ids</code></td>
  </tr>
  <tr>
    <td rowspan="24">Audience targeting</td>
    <td>Saved audience</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4b532c40c2d88b52bee7b5e30bfc617c" alt="Saved audience" style="color:#808080" ></td>
    <td><code>saved_audience_id</code></td>
  </tr>
  <tr>
    <td>Select targeting mode<ul><li>Custom targeting</li><li>Automatic targeting</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c3e0bf556930e4e2b4ef3d73d19cde02" alt="Select targeting mode Custom targeting or Automatic targeting" style="color:#808080" ></td>
    <td><code>auto_targeting_enabled</code></td>
  </tr>
  <tr>
    <td>Find prospective customers <br>/Retarget audience<br>(when Shopping ads type = Video Shopping Ads & Product source = Catalog)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e9daa481cbf3ecdcc465d22c5edd6d6a" alt="Find prospective customers" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7329c9cc18d037b141d490467c2a2f10" alt="Retarget audience" style="color:#808080" ></td>
    <td><code>shopping_ads_retargeting_type</code><br><code>shopping_ads_retargeting_actions_days</code><br><code>included_custom_actions</code><br><code>excluded_custom_actions</code></td>
  </tr>
   <tr>
    <td>Expand / Narrow <br>(when Shopping ads type = Video Shopping Ads & Product source = Catalog & Retarget audience is selected)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/841ceff78e47972742936d560ad51d8c" alt="Expand or Narrow" style="color:#808080" ></td>
    <td><code>shopping_ads_retargeting_custom_audience_relation</code></td>
  </tr>
  <tr>
    <td>Demographics - Location</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/aa781e8f323a5554869319640d50dfab" alt="Demographics - Location" style="color:#808080" ></td>
    <td><code>location_ids</code><br><code>zipcode_ids</code></td>
  </tr>
  <tr>
    <td>Demographics - Languages</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/09ccabffdcf8f36a7c95b6a1d4232a9f" alt="Demographics - Languages" style="color:#808080" ></td>
    <td><code>languages</code></td>
  </tr>
  <tr>
    <td>Demographics - Gender</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6fb1b1939dae9175a43d863b4fdf7099" alt="Demographics - Gender" style="color:#808080" ></td>
    <td><code>gender</code></td>
  </tr>
  <tr>
    <td>Demographics - Age</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d19dba2dcf34365b14020776a374f24d" alt="Demographics - Age" style="color:#808080" ></td>
    <td><code>age_groups</code></td>
  </tr>
  <tr>
    <td>Demographics - Spending power</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3cefa58de797b6640c3c2fccf861b5a2" alt="Demographics - Spending power" style="color:#808080" ></td>
    <td><code>spending_power</code></td>
  </tr>
  <tr>
    <td>Demographics - Household income</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/485d3224f2fe97cfd6cb9e6d0617ead2" alt="Demographics - Household income" style="color:#808080" ></td>
    <td><code>household_income</code></td>
  </tr>
  <tr>
    <td>Audience<ul><li>Include</li><li>Exclude</li></ul></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cecf081ceee11624e458d2f8af6cf73d" alt="Audience Include & Exclude " style="color:#808080" ></td>
    <td><code>audience_ids</code><br><code>excluded_audience_ids</code></td>
  </tr>
    <tr>
    <td>Smart audience</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/320f21c391190188523d50a35380fcbb" alt="Smart audience" style="color:#808080" ></td>
    <td><code>smart_audience_enabled</code></td>
  </tr>
  <tr>
    <td>Interests & Behaviors - Interests</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ce355bcba4ed5275fd1f61ab58cfe3ed" alt="Interests & Behaviors - Interests" style="color:#808080" ></td>
    <td><code>interest_category_ids</code><br><code>interest_keyword_ids</code></td>
  </tr>
  <tr>
    <td>Interests & Behaviors - Purchase intention</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a6b95b18b7bfad5949e538cd558e136f" alt="Interests & Behaviors - Purchase intention" style="color:#808080" ></td>
    <td><code>purchase_intention_keyword_ids</code></td>
  </tr>
  <tr>
    <td>Interests & Behaviors - Video interactions <br>& Creator interactions <br>& Hashtag interactions</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3da1a771b86a359f17593e848537de87" alt="Interests & Behaviors - Video interactions & Creator interactions & Hashtag interactions" style="color:#808080" ></td>
    <td><code>actions</code></td>
  </tr>
    <tr>
    <td>Smart interests & behaviors</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/90fa0ec329f06ecce27ef791de23bbce" alt="Smart interests & behaviors" style="color:#808080" ></td>
    <td><code>smart_interest_behavior_enabled</code></td>
  </tr>
  <tr>
    <td>Pangle audience packages</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3473ee31be1cbf88de6114ecba64e864" alt= "Pangle audience packages" style="color:#808080" ></td>
    <td><code>included_pangle_audience_package_ids</code><br><code>excluded_pangle_audience_package_ids</code></td>
  </tr>
  <tr>
    <td>Device - OS versions</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/34da5873fd1be52716a5f5d426e2c636" alt="Device - OS versions" style="color:#808080" ></td>
    <td><code>operating_systems</code><br><code>min_android_version</code><br><code>min_ios_version</code><br><code>ios14_targeting</code></td>
  </tr>
  <tr>
    <td>Device - Device model</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5fc6e59589867eec6b087db4a71e584f" alt="Device - Device model" style="color:#808080" ></td>
    <td><code>device_model_ids</code></td>
  </tr>
  <tr>
    <td>Device - Connection type</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/94cb00e18f6d239646ae3b102a2699b9" alt="evice - Connection type" style="color:#808080" ></td>
    <td><code>network_types</code></td>
  </tr>
  <tr>
    <td>Device - Carriers</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/32f698054f4604065606c1b410a2a012" alt="Device - Carriers" style="color:#808080" ></td>
    <td><code>carrier_ids</code></td>
  </tr>
  <tr>
    <td>Device - Internet service provider</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/aaf00006ae585b124871983661c23d03" alt="Device - Internet service provider" style="color:#808080" ></td>
    <td><code>isp_ids</code></td>
  </tr>
  <tr>
    <td>Device - Device price</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5610f957fb63140075f7028ed1242e6d" alt="Device - Device price" width="60%" style="color:#808080" ></td>
    <td><code>device_price_ranges</code></td>
  </tr>
  <tr>
    <td>Targeting expansion<br><br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5b371b98884877d17ebb8ca07f26ab2b" alt="Targeting expansion" style="color:#808080" ></td>
    <td><code>targeting_expansion</code></td>
  </tr>
  <tr>
    <td>Contextual Targeting</td>
    <td>Content Topics<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/57df0391296d691568a057843665e59b" alt="Content Topics" style="color:#808080" ></td>
    <td><code>contextual_tag_ids</code></td>
  </tr>
  <tr>
    <td rowspan="3">Content exclusions</td>
    <td>Inventory filter</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c966d8eaf37e20c1572b5e1b4df22187" alt="Inventory filter" style="color:#808080" ></td>
    <td><code>brand_safety_type</code><br><code>brand_safety_partner</code></td>
  </tr>
  <tr>
    <td>Category exclusions</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5452f94be47661256bd5199782882024" alt="Category exclusions" style="color:#808080" ></td>
    <td><code>category_exclusion_ids</code></td>
  </tr>
  <tr>
    <td>Vertical sensitivity control </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d048d790b1d6c4ea883cb89fc4bdabba" alt="Vertical sensitivity control" style="color:#808080" ></td>
    <td><code>vertical_sensitivity_id</code></td>
  </tr>
  <tr>
    <td rowspan="3">Budget & Schedule</td>
    <td>Budget</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/572e2ab233a05cc8b0fc793e22863660" alt="Budget" style="color:#808080" ></td>
    <td><code>budget_mode</code><br><code>budget</code></td>
  </tr>
  <tr>
    <td>Schedule</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8e7e6a342c8787c95a6b5c3fcb34a4bd" alt="Schedule" style="color:#808080" ></td>
    <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
  </tr>
  <tr>
    <td>Dayparting</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e6813ca6e3526e16d3e91bcf61a5576b" alt="Dayparting" style="color:#808080" ></td>
    <td><code>dayparting</code></td>
  </tr>
  <tr>
    <td rowspan="13">Bidding & Optimization</td>
    <td>Optimization goal</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/11774d75e1105eab83b97b7486f19086" alt="Optimization goal" style="color:#808080" ></td>
    <td><code>optimization_goal</code></td>
  </tr>
  <tr>
    <td>Message event set (when Optimization location is Instant messaging apps & Optimization goal is Conversations)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/776aa66d408c5295e0c8ae5bbe7b7d01" alt="Message event set" style="color:#808080" ></td>
    <td><code>message_event_set_id</code></td>
  </tr>
  <tr>
    <td>Frequency cap</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/16bb9797f99b041e968eb7381548e400" alt="Frequency cap" style="color:#808080" ></td>
    <td><code>frequency</code><br><code>frequency_schedule</code></td>
  </tr>
  <tr>
    <td>Select an in-app event to optimize for<br>(when Optimization goal = Install with in-app event)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c9522cd547e3b4bc82280aa2a0c21e1d" alt="Select an in-app event to optimize for" style="color:#808080" ></td>
    <td><code>secondary_optimization_event</code></td>
  </tr>
  <tr>
    <td>Bid strategy</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a5091659249e5509a2acb1405f390437" alt="Bid strategy" style="color:#808080" ></td>
    <td><code>bid_type</code><br><code>bid_price</code></td>
  </tr>
  <tr>
    <td>USD/Conversion</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1c17fad763d67b84059438999c7433c3" alt="USD/Conversion" style="color:#808080" ></td>
    <td><code>conversion_bid_price</code></td>
  </tr>
  <tr>
    <td>Bid strategy</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4b1c2289075c3d742f5ec0a7aa2d0e60" alt="Bid strategy" style="color:#808080" ></td>
    <td><code>deep_bid_type</code><br><code>roas_bid</code></td>
  </tr>
  <tr>
    <td>Day 7 ROAS / Day 0 ROAS</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/dbd166939dfe23e8224d95b4e80b8097" alt="Day 7 ROAS / Day 0 ROAS" style="color:#808080" ></td>
    <td><code>vbo_window</code></td>
  </tr>
  <tr>
    <td>USD/View</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2679e3b0e70c14528ece5a01c7fc834a" alt="USD/View" style="color:#808080" ></td>
    <td><code>bid_display_mode</code></td>
  </tr>
  <tr>
    <td>Retention goal</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a393969b57181e13970e908669fc7953" alt="Retention goal" style="color:#808080" ></td>
    <td><code>next_day_retention</code></td>
  </tr>
  <tr>
    <td>Attribution settings<ul><li>Attribution window</li><li>Event count</li></ul></td> 
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cf373bbbf3730c44105f03f552d11c74" alt="Attribution settings" style="color:#808080" ></td>
    <td><code>click_attribution_window</code><br><code>view_attribution_window</code><br><code>attribution_event_count</code></td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1c0cf452f21082c8d27048994408cfbe" alt="Billing event" style="color:#808080" ></td>
    <td><code>billing_event</code></td>
  </tr>
  <tr>
    <td>Delivery type</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b90c789d6966d917edd6a7fabcea63ed" alt="Delivery type" style="color:#808080" ></td>
    <td><code>pacing</code></td>
  </tr>
  <tr>
    <td>Campaign list </td>
    <td>Ad group - On/Off</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1c6b3db63af311a3eecc8f573cf63f2f" alt="Ad group - On/Off" style="color:#808080" ></td>
    <td><code>operation_status</code></td>  
  </tr>
</tbody>
</table>


## Ad level
To create an ad, use [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354).

The table below lists the settings on TTAM at the ad level and the corresponding API configurations.

<table>
<thead>
  <tr>
    <th style="width:24%">TTAM Module</th>
    <th style="width:20%">TTAM setting</th>
    <th style="width:30%">UI Reference</th>
    <th style="width:26%">API configuration</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Smart Creative ads</td>
    <td>Smart Creative ads</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4b23419fcc8655a8e334750773bebc63" alt="Smart Creative ads" style="color:#808080" ></td>
    <td>To learn about how to create Smart Creative ads, refer to <a href="https://business-api.tiktok.com/portal/docs?id=1767322784934914" target="_blank">Smart Creative</a>.</td>
  </tr>
  <tr>
    <td>Ad name</td>
    <td>Ad name</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c070b06675a928ba57778ddecaffa708" alt="Ad name" style="color:#808080" ></td>
    <td><code>ad_name</code></td>
  </tr>
  <tr>
    <td>Identity</td>
    <td>Identity</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/15b8d19792f6bc241a22be2259ad60fe" alt="Identity" style="color:#808080" ></td>
    <td><code>identity_type</code><br><code>identity_id</code><br><code>identity_authorized_bc_id</code></td>
  </tr>
  <tr>
    <td rowspan="2">Product details</td>
    <td rowspan="2">Products</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f33d2ebabbfeba067d246a59d5330d92" alt="Products" style="color:#808080" ></td>
    <td><code>catalog_id</code><br><code>product_specific_type</code><br><code>item_group_ids</code><br><code>product_set_id</code><br><code>sku_ids</code></td>
  </tr>
  <tr>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c1dac9e391142d185a35bdda264feff9" alt="Products" style="color:#808080" ></td>
    <td><code>showcase_products</code></td>
  </tr>
  <tr>
    <td rowspan="14">Ad details</td>
    <td>Ad format</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c4df028a0ce824318de9bb8498bf57e5" alt="Ad format" style="color:#808080" ></td>
    <td><code>ad_format</code></td>
  </tr>
  <tr>
    <td>Ad format</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e26b3e20a7315e14914a201773ff0bf0" alt="Ad format" style="color:#808080" ></td>
    <td><code>vertical_video_strategy</code></td>
  </tr>
  <tr>
    <td>Ad setup - Dynamic format</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/409019e3b0e86c4ab06968805e74a33e" alt="Ad setup - Dynamic format" style="color:#808080" ></td>
    <td><code>dynamic_format</code></td>
  </tr>
  <tr>
    <td>Ad creative - Video </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/65997381269a6f7dc4c08f0e59d13201" alt="Ad creative - Video" style="color:#808080" ></td>
    <td><code>video_id</code></td>
  </tr>
  <tr>
    <td>Ad creative - Image</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7711fb63e49c37fddc4d3893f4b24cf4" alt="Ad creative - Image" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/68467cc5d8c5083caeb731c2d46e5752" alt="Ad creative - Image" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5d435b44cc9b926f1e48ab8a543ed9f4" alt="Ad creative - Image" style="color:#808080" ></td>
    <td><code>image_ids</code></td>
  </tr>
  <tr>
    <td>Ad creative - Music</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e61fef61caf01ee13962e323fad2f14b" alt="Ad creative - Music" style="color:#808080" ></td>
    <td><code>music_id</code></td>
  </tr>
  <tr>
    <td>Ad creative - Post<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/909588d73dd32cebf362f6cb9ff9bdec" alt="Ad creative - Post" style="color:#808080" ></td>
    <td><code>tiktok_item_id</code></td>
  </tr>
  <tr>
    <td>Ad creative - Post - Music sharing</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ea9bf63c300a54025097ba37449e3357" alt="Ad creative - Post - Music sharing" style="color:#808080" ></td>
    <td><code>promotional_music_disabled</code></td>
  </tr>
  <tr>
    <td>Ad creative - Post - Stitch and Duet</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ef9a3bb09f96841526a65882e79040b8" alt="Ad creative - Post - Stitch and Duet" style="color:#808080" ></td>
    <td><code>item_duet_status</code><br><code>item_stitch_status</code></td>
  </tr>
  <tr>
    <td>Ad creative - Video - Only show as ad</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/86cd6dda5430fd5ae91ffb80e572b30b" alt="Ad creative - Video - Only show as ad" style="color:#808080" ></td>
    <td><code>dark_post_status</code></td>
  </tr>
  <tr>
    <td>Ad creative - Catalog video</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/54d35dd03fdf7e0b805edf1440400517" alt="Ad creative - Catalog video" style="color:#808080" ></td>
    <td><code>shopping_ads_video_package_id</code></td>
  </tr>
  <tr>
    <td>Ad creative - Text</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/14566b76a91f502504eadf507e2edb65" alt="Ad creative - Text" style="color:#808080" ></td>
    <td><code>ad_text</code></td>
  </tr>
  <tr>
    <td>Ad creative - Call to action</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ceaca72ab0c8905201be62f79c164460" alt="Ad creative - Call to action" style="color:#808080" ></td>
    <td><code>call_to_action</code><br><code>call_to_action_id</code></td>
  </tr>
  <tr>
    <td>Interactive add-ons</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c685d2e5c9e3c4844c286e807232bdd5" alt="Interactive add-ons" style="color:#808080" ></td>
    <td><code>card_id</code></td>
  </tr>
  <tr>
    <td rowspan="14">Destination</td>
    <td>URL</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0d3d595c53e236e3cd286eab66f8335e" alt="URL" style="color:#808080" ></td>
    <td><code>landing_page_url</code></td>
  </tr>
  <tr>
    <td>Custom Page</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d1c96b8cb29c2b57b9fd9ed637a3bb4a" alt="Custom Page" style="color:#808080" ></td>
    <td><code>page_id</code></td>
  </tr>
  <tr>
    <td>Add specific custom product page as destination</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/406a6762aaca48ed7fdc184f2b3d6373" alt="Add specific custom product page as destination" style="color:#808080" ></td>
    <td><code>cpp_url</code></td>
  </tr>
  <tr>
    <td>TikTok page type<br></td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4aa2b8230a7eaaa3152aa63fb448dfba" alt="TikTok page type" style="color:#808080" ></td>
    <td><code>tiktok_page_category</code></td>
  </tr>
  <tr>
    <td>Phone number</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8597327870e151e593bbd161ce3d886a" alt="Phone number" style="color:#808080" ></td>
    <td><code>phone_region_code</code><br><code>phone_region_calling_code</code><br><code>phone_number</code></td>
  </tr>
  <tr>
    <td>Direct users to deeplink first <br>& Deferred deeplink</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5c27eb797b90f363c08c5c0e1cf2a92e" alt="Direct users to deeplink first" style="color:#808080" ><br><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c802efe2aceec492327f3df868c6ac66" alt="Deferred deeplink" style="color:#808080" ></td>
    <td><code>deeplink</code><br><code>deeplink_type</code></td>
  </tr>
  <tr>
    <td>Deferred deeplink <br>(for Shopping Ads)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0463e847c37ac765b86354989f9713bd" alt="Deferred deeplink (for Shopping Ads) " style="color:#808080" ></td>
    <td><code>shopping_ads_deeplink_type</code></td>
  </tr>
  <tr>
    <td>Website type<br>(for Shopping Ads)</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b0cdde48249076d67197d656c347cfd5" alt="Website type (for Shopping Ads)" style="color:#808080" ></td>
    <td><code>shopping_ads_fallback_type</code></td>
  </tr>
  <tr>
    <td>Fallback type</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/73d2cfd413528a28c7417833c728d3ac" alt="Fallback type" style="color:#808080" ></td>
    <td><code>fallback_type</code></td>
  </tr>
  <tr>
    <td>Dynamic destination</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/846e2a252577f40f1f035c9e35a045f9" alt="Dynamic destination" style="color:#808080" ></td>
    <td><code>dynamic_destination</code></td>
  </tr>
  <tr>
    <td>Build URL parameters</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5478c61ba29d52ab5ad1d2e4ec9926ba" alt="Build URL parameters" style="color:#808080" ></td>
    <td><code>utm_params</code></td>
  </tr>
  <tr>
    <td>Instant Product Page </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a1a8317c9138dfd6f462fda48d56451b" alt="Instant Product Page" style="color:#808080" ></td>
    <td><code>instant_product_page_used</code></td>
  </tr>
  <tr>
    <td>Welcome message </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6ae5f2c1308e6c95d9f7d884894a8200" alt="Welcome message" style="color:#808080" ></td>
    <td><code>auto_message_id</code></td>
  </tr>
  <tr>
    <td>This ad contains AI-generated content </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4f5d02537b73c8a3d1595e5830d53c39" alt="This ad contains AI-generated content" style="color:#808080" ></td>
    <td><code>aigc_disclosure_type</code></td>
  </tr>
  <tr>
    <td>Disclaimer</td>
    <td>Disclaimer</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9652c840d0a7d0123669d7f206eaae17" alt="Disclaimer" style="color:#808080" ></td>
    <td><code>disclaimer_type</code><br><code>disclaimer_text</code><br><code>disclaimer_clickable_texts</code></td>
  </tr>
  <tr>
    <td rowspan="8">Tracking</td>
    <td>TikTok events tracking - Website events</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/39ead1d8e7514869c015c5790c4eb167" alt="ikTok events tracking - Website events" style="color:#808080" ></td>
    <td><code>tracking_pixel_id</code></td>
  </tr>
  <tr>
    <td>TikTok events tracking - App events</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/159cd0f7b099b80308c90f41354de537" alt="TikTok events tracking - App events" style="color:#808080" ></td>
    <td><code>tracking_app_id</code></td>
  </tr>
  <tr>
    <td>TikTok events tracking - Offline events</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/fc29bd5f81b173ee06b91952a10fe85e" alt="TikTok events tracking - Offline events" style="color:#808080" ></td>
    <td><code>tracking_offline_event_set_ids</code></td>
  </tr>
  <tr>
    <td>TikTok events tracking - Message event set </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ee51f4aad65ef3f21e1c40d217ba8135" alt="TikTok events tracking - Message event set " style="color:#808080" ></td>
    <td><code>tracking_message_event_set_id</code></td>
  </tr>
  <tr>
    <td>TikTok events tracking - Track viewability with third-party partner</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6437eb3d404d6d8a6b23dfffcaf671aa" alt="TikTok events tracking - Track viewability with third-party partner" style="color:#808080" ></td>
    <td><code>viewability_postbid_partner</code><br><code>viewability_vast_url</code></td>
  </tr>
  <tr>
    <td>TikTok events tracking - Measure brand safety with third-party partner</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/728cfabe3dcf047a13b87a7cf9a41fcb" alt="TikTok events tracking - Measure brand safety with third-party partner" style="color:#808080" ></td>
    <td><code>brand_safety_postbid_partner</code><br><code>brand_safety_vast_url</code></td>
  </tr>
  <tr>
    <td>Third party tracking settings - Impression tracking URL</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b3c6bfc9e5c01a4ed38ada4b2235b3bf" alt="Third party tracking settings - Impression tracking URL" style="color:#808080" ></td>
    <td><code>impression_tracking_url</code></td>
  </tr>
  <tr>
    <td>Third party tracking settings - Click tracking URL </td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f824547a5abc00c71effb9677c9b6c57" alt="Third party tracking settings - Click tracking URL " style="color:#808080" ></td>
    <td><code>click_tracking_url</code></td>
  </tr>
  <tr>
    <td>Playable</td>
    <td>Add playable asset</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b50c4451894125e6b9e56ba7fa695e75" alt="Add playable asset" style="color:#808080" ></td>
    <td><code>playable_url</code></td>
  </tr>
  <tr>
    <td>Campaign list </td>
    <td>Ad - On/Off</td>
    <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/de436194a59f044723913ac07ca09c8d" alt="Ad - On/Off" style="color:#808080" ></td>
    <td><code>operation_status</code></td>  
  </tr>
</tbody>
</table>


