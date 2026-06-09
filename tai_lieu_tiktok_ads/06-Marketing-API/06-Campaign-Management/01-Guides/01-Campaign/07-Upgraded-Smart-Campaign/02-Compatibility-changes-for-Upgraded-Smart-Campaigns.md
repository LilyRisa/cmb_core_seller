---
title: "Compatibility changes for Upgraded Smart+ Campaigns"
doc_id: 1854112742370306
path: "Marketing API / Campaign Management / Guides / Campaign / Upgraded Smart+ Campaign / Compatibility changes for Upgraded Smart+ Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Compatibility changes for Upgraded Smart+ Campaigns

The following table outlines the compatibility changes for Upgraded Smart+ Campaigns in various endpoints.

<table>
  <thead>
    <tr>
      <th style="width:15%">Module</th>
      <th style="width:25%">Endpoints</th>
      <th style="width:60%">Main Compatibility Changes</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="4">Campaign</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a></td>
      <td><ul><li>Upgraded Smart+ Campaigns are returned by default.</li><li>The <code>campaign_automation_type</code> parameter is available in the request and response for retrieving campaign types, including Manual, Smart+, and Upgraded Smart+ campaigns.</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739318962329602" target="_blank">/campaign/create/</a></td>
      <td rowspan="3">You cannot use these endpoints to create, update or copy an Upgraded Smart+ Campaign.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843310518576130" target="_blank">Upgraded Smart+ Campaigns endpoints</a> instead.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739320422086657" target="_blank">/campaign/update/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1788434394151938" target="_blank">/campaign/copy/task/create/</a></td>
    </tr>
    <tr>
      <td rowspan="5">Ad group</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a></td>
      <td><ul><li>Upgraded Smart+ Ad Groups are returned by default.</li><li>The <code>campaign_automation_type</code> parameter is available in the request and response so you can tell whether an ad group belongs to a Manual, a Smart+, or an Upgraded Smart+ Campaign.</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739499616346114" target="_blank">/adgroup/create/</a></td>
      <td rowspan="4">You cannot use these endpoints to create or update an Upgraded Smart+ Ad Group.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843310524500993" target="_blank">Upgraded Smart+ Ad Groups endpoints</a> instead.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739586761631745" target="_blank">/adgroup/update/</a></td>
    </tr>
    <tr>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739591133130817" target="_blank">/adgroup/budget/update/</a></td>
    </tr>
    <tr>
      <td rowspan="3">Ad</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a></td>
      <td><ul><li>You can retrieve Upgraded Smart+ Ads. </li><li>The <code>campaign_automation_type</code> parameter is available in the request and response for retrieving campaign types that the ads belong to, including Manual, Smart+, and Upgraded Smart+ Campaigns.</li><li>You can retrieve the ad ID of Upgraded Smart+ Ads through the new response parameter <code>smart_plus_ad_id</code>.</li><li>You can use the <code>ad_ids_v2</code> filter to efficiently filter ads across Manual, Smart+, and Upgraded Smart+ Campaigns, ensuring focused analysis.</li><li>Use the <code>ad_id_v2</code> response parameter to identify specific ads within Manual, Smart+, and Upgraded Smart+ Campaigns with ease.</li><li>The new enum value <code>UPGRADED_SMART_PLUS</code> for the <code>campaign_automation_type</code> response parameter helps you distinguish ads within Upgraded Smart+ Campaigns, aiding in targeted analysis.</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739953377508354" target="_blank">/ad/create/</a></td>
      <td rowspan="2">You cannot use these endpoints to create or update an Upgraded Smart+ Ad.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843310530260097" target="_blank">Upgraded Smart+ Ads endpoints</a> instead.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739953405142018" target="_blank">/ad/update/</a></td>
    </tr>
	<tr>
      <td>Ads Preview</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739403070695426" target="_blank">/creative/ads_preview/create/</a></td>
      <td>You cannot use this endpoint to preview Upgraded Smart+ Ads.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843317445798914" target="_blank">/smart_plus/ad/preview/</a> instead.</td>
    </tr>
    <tr>
      <td rowspan="3">Smart Creative</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739473063234626" target="_blank">/ad/aco/create/</a></td>
      <td rowspan="3">You cannot use these endpoints to create or update Smart Creative ads using Upgraded Smart+ Ad Groups.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739473077112833" target="_blank">/ad/aco/update/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739506701165570" target="_blank">/ad/aco/material_status/update/</a></td>
    </tr>
    <tr>
      <td rowspan="4">Smart+</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362" target="_blank">/campaign/spc/create/</a></td>
      <td rowspan="4">You cannot use these endpoints to create, update, or retrieve Upgraded Smart+ Campaigns.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843310518576130" target="_blank">Upgraded Smart+ Campaigns endpoints</a> instead.</td>
    </tr>
	<tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1767334250066945" target="_blank">/campaign/spc/update/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1837969177953361" target="_blank">/campaign/spc/material_status/update/</a></td>
    </tr>
	    <tr>
      <td rowspan="3">Reporting</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a></td>
      <td rowspan="2"><ul><li>By default, the data for creatives in Upgraded Smart+ Ads is included.</li><li>You <b>can</b> get Upgraded Smart+ Ads data (with <code>ad_id</code> representing the creative ID) when you use the <code>ad_ids</code> filter, specify <code>ad_id</code> in <code>dimensions</code>, or set <code>data_level</code> to <code>AUCTION_AD</code>.</li><li>The <code>ad_id_v2</code> dimension is now available to group ads across Manual, Smart+, and Upgraded Smart+ Campaigns. This allows for a more refined analysis of your advertising efforts.</li><li>Gain deeper insights with these attribute metrics associated with the <code>ad_id_v2</code> dimension:<ul><li><code>ad_id_v2</code></li><li><code>ad_text_list</code></li><li><code>call_to_action_list</code></li><li><code>ad_profile_image_list</code></li><li><code>ad_url_list</code></li><li><code>image_mode_list</code></li><li><code>image_info_list</code></li><li><code>ad_display_name_list</code></li><li><code>identity_type_list</code></li><li><code>profile_image_list</code></li></ul></li><li>Use the filters <code>ad_id_v2</code> and <code>campaign_automation_type</code> to efficiently filter data across Manual, Smart+, and Upgraded Smart+ Campaigns, ensuring focused analysis.</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1776454619412481" target="_blank">/campaign/spc/report/get/</a></td>
      <td>You cannot use this endpoint to get the reporting data on creatives within Upgraded Smart+ Ads.<br>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843317489576961" target="_blank">/smart_plus/material_report/overview/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1843317510389761" target="_blank">/smart_plus/material_report/breakdown/</a> instead.</td>
    </tr>
	<tr>
      <td rowspan="2">Creative Report</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738824501176321" target="_blank">/report/ad_benchmark/get/</a></td>
      <td rowspan="2">You cannot use these endpoints to get Creative Report data for Upgraded Smart+ Ads.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738825259075586" target="_blank">/report/video_performance/get/</a></td>
    </tr>
	<tr>
      <td rowspan="4">Reach & Frequency</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738235338194945" target="_blank">/adgroup/rf/create/</a></td>
      <td rowspan="4">You cannot use these endpoints to create, update, cancel, or get the estimated daily cost and frequency distribution for an Upgraded Smart+ ad group.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738235402874882" target="_blank">/adgroup/rf/update/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1740489099367425" target="_blank">/adgroup/rf/order/cancel/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1740489551354881" target="_blank">/adgroup/rf/estimated/info/</a></td>
    </tr>
    <tr>
      <td rowspan="3">Tools</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1746463695763458" target="_blank">/delivery/budget/recommend/</a></td>
      <td rowspan="2">You cannot use these endpoints to get budget and bid estimated delivery results for Upgraded Smart+ Ad Groups.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1746463732779009" target="_blank">/delivery/bid/recommend/</a></td>
    </tr>
	<tr>
	  <td><a href="https://business-api.tiktok.com/portal/docs?id=1770016073586753" target="_blank">/tool/vbo_status/</a></td>
      <td>You can check the Value-Based Optimization eligibility of settings for creating <a href="https://business-api.tiktok.com/portal/docs?id=1853377811982657" target="_blank">Upgraded Smart+ Minis Campaigns</a> via the <code>campaign_automation_type</code> parameter and the <code>MINIS</code> value for the request parameter <code>app_promotion_type</code>.</td>
	</tr>
    <tr>
      <td>Subscription</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1739092028876801" target="_blank">/subscription/subscibe/</a></td>
      <td>You cannot use this endpoint to subscribe to review or fatigue status events for Upgraded Smart+ Ad Groups or Ads.</td>
    </tr>
    <tr>
      <td>Ad Diagnosis</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738674986981378" target="_blank">/tool/diagnosis/get/</a></td>
      <td>You cannot use this endpoint to get diagnoses for Upgraded Smart+ Ad Groups.</td>
    </tr>
    <tr>
      <td rowspan="4">Ad Comment</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738086301876225" target="_blank">/comment/list/</a></td>
      <td>You cannot get the ad ID, ad name, ad group ID, and ad group name for a comment on an Upgraded Smart+ Ad.</td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738957702411265" target="_blank">/comment/post/</a></td>
      <td rowspan="3">You cannot use these endpoints to:<ul><li>Reply to a comment on an Upgraded Smart+ Ad,</li><li>Delete a comment from an Upgraded Smart+ Ad, or</li><li>Create a comment export task for an Upgraded Smart+ Ad Group</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738957772267522" target="_blank">/comment/delete/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738088144215041" target="_blank">/comment/task/create/</a></td>
    </tr>
    <tr>
      <td rowspan="3">Automated Rules</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738767670852610" target="_blank">/optimizer/rule/create/</a></td>
      <td rowspan="3">You cannot use these endpoints to:<ul><li>Create automated rules for Upgraded Smart+ Campaigns, Ad Groups, and Ads,</li><li>Update automated rules for Upgraded Smart+ Campaigns, Ad Groups, and Ads, or</li><li>Bind automated rules to or unbind automated rules from Upgraded Smart+ Campaigns, Ad Groups, and Ads</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738769164954626" target="_blank">/optimizer/rule/batch_bind/</a></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1738769123170306" target="_blank">/optimizer/rule/update/</a></td>
    </tr>
    <tr>
      <td>Creative Tools</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1767568466842626" target="_blank">/creative_fatigue/get/</a></td>
      <td>You cannot use this endpoint to get Creative Fatigue Detection results for Upgraded Smart+ Ads.</td>
    </tr>
	 <tr>
      <td rowspan="2">Business Messaging</td>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1832184415059970" target="_blank">/business/message/conversation/list/</a></td>
      <td><ul><li>You can retrieve the IDs of creatives in Upgraded Smart+ Ads through the existing <code>ad_id</code> field.</li><li>You can retrieve encoded creative IDs for Upgraded Smart+ Ads through the new <code>message_material_id</code> field.</li></ul></td>
    </tr>
    <tr>
      <td><a href="https://business-api.tiktok.com/portal/docs?id=1832190670631937#item-link-im_referral_msg" target="_blank">Webhook schema for <code>im_referral_msg</code></a></td>
      <td><ul><li>You can retrieve the IDs of creatives in Upgraded Smart+ Ads through the existing <code>ad_id</code> field.</li><li>You can retrieve encoded creative IDs for Upgraded Smart+ Ads through the new <code>message_material_id</code> field.</li></ul></td>
    </tr>
  </tbody>
</table>

