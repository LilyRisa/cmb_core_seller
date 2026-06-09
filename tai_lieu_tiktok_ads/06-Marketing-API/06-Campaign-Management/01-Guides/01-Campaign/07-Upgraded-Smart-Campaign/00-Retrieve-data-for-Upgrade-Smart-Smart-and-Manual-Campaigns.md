---
title: "Retrieve data for Upgrade Smart+, Smart+, and Manual Campaigns"
doc_id: 1854103649826817
path: "Marketing API / Campaign Management / Guides / Campaign / Upgraded Smart+ Campaign / Retrieve data for Upgrade Smart+, Smart+, and Manual Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Retrieve data for Upgrade Smart+, Smart+, and Manual Campaigns

This guide provides an overview of data levels for three distinct campaign types: Manual Campaigns, Smart+ Campaigns, and Upgrade Smart+ Campaigns (also known as Upgraded Smart+ experience). It outlines detailed procedures to retrieve data for each of these campaign types, ensuring a comprehensive understanding of their structures.

# Overall data levels

Upgrade Smart+ Campaigns unify Manual and Smart+ experiences into a single streamlined process, offering a flexible ad buying experience. This enhancement allows for fine-tuning campaigns with various levels of control, including full automation, partial automation, or complete manual operation.

<table>
  <thead>
    <tr>
      <th style="width:20%">Data level</th>
      <th style="width:20%">Manual Campaign</th>
      <th style="width:30%">Smart+ Campaign</th>
      <th style="width:30%">Upgrade Smart+ Campaign</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Campaign</td>
      <td>✅</td>
      <td>✅</td>
      <td>✅</td>
    </tr>
    <tr>
      <td>Ad group</td>
      <td>✅</td>
      <td>✅</td>
      <td>✅</td>
    </tr>
    <tr>
      <td>Ad</td>
      <td>✅</td>
      <td>⚠️ (returns limited data)</td>
      <td>✅</td>
    </tr>
    <tr>
      <td>Creative</td>
      <td>❌</td>
      <td>✅</td>
      <td>✅</td>
    </tr>
  </tbody>
</table>

## Retrieve data for different campaign types
This section guides you through the process of retrieving data for Manual Campaigns, Smart+ Campaigns, and Upgrade Smart+ Campaigns separately.

## Data retrieval landscape

<div style="overflow-x:auto;">
 <table style="width:1550px">
  <thead>
    <tr>
      <th style="width:12%">Automation Type</th>
      <th  style="width:58%", colspan="4"><b>Get Endpoints</b></th>
      <th  style="width:30%", colspan="4"><b>Reporting API</b></th>
    </tr>
    <tr>
      <th></th>
      <th style="width:14%">Get Campaign</th>
      <th style="width:16%">Get Ad group</th>
      <th style="width:12%">Get Ad <br> (Asset Group in Upgraded Smart+)</th>
      <th style="width:11%">Get Creative</th>
      <th style="width:7%">Campaign</th>
      <th style="width:6%">Ad Group</th>
      <th style="width:8%">Ad<br> (Asset Group in Upgraded Smart+)</th>
      <th style="width:13%">Creative</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><b>Manual Flow</b></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a></td>
      <td>N/A</td>
      <td colspan="3" rowspan="3">✅ <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a><br>Reporting API</td>
      <td>N/A</td>
    </tr>
    <tr>
      <td><b>Smart+ API</b></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a><br>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a></td>
      <td>N/A<br>(there's no ad group in legacy Smart+ campaigns)</td>
      <td>⚠️ <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a><br> (returns limited data)</td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1776454619412481" target="_blank">/campaign/spc/report/get/</a></td>
    </tr>
    <tr>
      <td><b>Upgraded Smart+ API</b></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a><br>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1843312818332930" target="_blank">/smart_plus/campaign/get/</a></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a><br>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a></td>
      <td colspan="2">✅ <a href="https://business-api.tiktok.com/portal/docs?id=1843317378982914" target="_blank">/smart_plus/ad/get/</a><br>⚠️ <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a> <br>(returns limited data at ad & creative levels)<br><br><b>ID in Upgraded Smart+:</b><br>Asset group (L3):<ul><li><code>smart_plus_ad_id</code> is returned when you pull the creative level and is used to identify the ad for the creative.</li><li><code>ad_id_v2</code> is returned when you pull the ad (asset group) level and is used to filter and display the asset group level data.</li></ul>Creative (L4): <code>ad_id</code></td>
      <td>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1843317489576961" target="_blank">/smart_plus/material_report/overview/</a><br>✅ <a href="https://business-api.tiktok.com/portal/docs?id=1843317510389761" target="_blank">/smart_plus/material_report/breakdown/</a></td>
    </tr>
  </tbody>
</table>
</div>

### Retrieve the data for Manual Campaigns

<table>
  <thead>
    <tr>
      <th style="width:10%">Data level</th>
      <th style="width:45%">Through Campaign Management API endpoints</th>
      <th style="width:45%">Through Reporting API endpoints</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Campaign</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a> to retrieve all Manual Campaigns within an ad account.<ul><li>Optionally, specify up to 100 IDs of Manual Campaigns in <code>campaign_ids</code> to retrieve these campaigns.</li><li>The returned <code>campaign_automation_type</code> will be <code>MANUAL</code>.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_CAMPAIGN</code> and include <code>campaign_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad group</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a> to retrieve all Manual Ad Groups within an ad account.<ul><li>Optionally, you can specify up to 100 IDs of Manual Ad Groups in <code>adgroup_ids</code> to retrieve these ad groups.</li><li>The returned <code>campaign_automation_type</code> will be <code>MANUAL</code>.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_ADGROUP</code> and include <code>adgroup_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a> to retrieve all Manual Ads.<ul><li>Optionally, specify up to 100 IDs of Manual Ads in <code>ad_ids_v2</code> (recommended) or <code>ad_ids</code> to retrieve these ads.</li><li>The returned <code>campaign_automation_type</code> will be <code>MANUAL</code>. The returned <code>ad_ids_v2</code> or <code>ad_id</code> represents the ID of a Manual Ad.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id_v2</code> (recommended) or <code>ad_id</code> in <code>dimensions</code>.</li><li>Optionally, specify up to 100 IDs of Manual Ads in the <code>ad_ids_v2</code> (recommended) or <code>ad_ids</code> filter to retrieve the details of these ads.</li></ul></td>
    </tr>
    <tr>
      <td>Creative</td>
      <td>❌</td>
      <td>❌</td>
    </tr>
  </tbody>
</table>
</div>
  
### Retrieve the data for Smart+ Campaigns

<table>
  <thead>
    <tr>
      <th style="width:10%">Data level</th>
      <th style="width:30%">Through Campaign Management API endpoints</th>
      <th style="width:30%">Through Smart+ API endpoints</th>
      <th style="width:30%">Through Reporting API endpoints</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Campaign</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a> and set <code>is_smart_performance_campaign</code> to <code>true</code> to retrieve all Smart+ Campaigns within an ad account.<ul><li>Optionally, specify up to 100 IDs of Smart+ Campaigns in <code>campaign_ids</code> to retrieve these campaigns.</li><li>The returned <code>campaign_automation_type</code> will be <code>SMART_PLUS</code>.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a> and specify up to 100 IDs of Smart+ Campaigns in <code>campaign_ids</code> to retrieve the details of these campaigns.<ul><li><p><span style="color:darkred"><b>Note</b></span>: You cannot retrieve all Smart+ Campaigns within your ad account through this endpoint.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_CAMPAIGN</code> and include <code>campaign_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad group</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a> to retrieve all ad groups within Smart+ Campaigns.<ul><li>Optionally, specify up to 100 IDs of ad groups within Smart+ Campaigns in <code>adgroup_ids</code> to retrieve these ad groups.</li><li>The returned <code>campaign_automation_type</code> will be <code>SMART_PLUS</code>.</li></ul></td>
      <td>❌</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_ADGROUP</code> and include <code>adgroup_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a> to retrieve all ads within Smart+ Campaigns.<ul><li>Optionally, specify up to 100 IDs of ads within Smart+ Campaigns in <code>ad_ids</code> to retrieve these ads.</li><li>The returned <code>campaign_automation_type</code> will be <code>SMART_PLUS</code>.</li></ul></td>
      <td>❌</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Creative</td>
      <td>❌</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1776454619412481" target="_blank">/campaign/spc/report/get/</a> to retrieve the creative-level data of one Smart+ Campaign.</td>
      <td>❌</td>
    </tr>
  </tbody>
</table>

### Retrieve the data for Upgraded Smart+ Campaigns

<table>
  <thead>
    <tr>
      <th style="width:10%">Data level</th>
      <th style="width:30%">Through Campaign Management API endpoints</th>
      <th style="width:30%">Through Upgraded Smart+ API endpoints</th>
      <th style="width:30%">Through Reporting API endpoints</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Campaign</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a>.<ul><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Campaigns in <code>campaign_ids</code> to retrieve these campaigns.</li><li>The returned <code>campaign_automation_type</code> will be <code>UPGRADED_SMART_PLUS</code>.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843312818332930" target="_blank">/smart_plus/campaign/get/</a> to retrieve all Upgraded Smart+ Campaigns within an ad account.<ul><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Campaigns in <code>campaign_ids</code> to retrieve these campaigns.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_CAMPAIGN</code> and include <code>campaign_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad group</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1739314558673922" target="_blank">/adgroup/get/</a>.<ul><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Ad Groups in <code>adgroup_ids</code> to retrieve these ad groups.</li><li>The returned <code>campaign_automation_type</code> will be <code>UPGRADED_SMART_PLUS</code>.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843314879617026" target="_blank">/smart_plus/adgroup/get/</a> to retrieve all Upgraded Smart+ Ad Groups within an ad account.<ul><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Ad Groups in <code>adgroup_ids</code> to retrieve these ad groups.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_ADGROUP</code> and include <code>adgroup_id</code> in <code>dimensions</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Ad</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a> and specify up to 100 IDs of Upgraded Smart+ Ads in the <code>ad_ids_v2</code> filter to retrieve the details of these ads.<ul><li>For Upgraded Smart+ Ads, the returned <code>campaign_automation_type</code> will be <code>UPGRADED_SMART_PLUS</code>.</li><li>The returned <code>ad_id_v2</code> represents the ID of the Upgraded Smart+ Ad.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843317378982914" target="_blank">/smart_plus/ad/get/</a> to retrieve all Upgraded Smart+ Ads within an ad account.<ul><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Ads in <code>smart_plus_ad_ids</code> to retrieve these ads.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id_v2</code> in <code>dimensions</code>.</li><li>Optionally, specify up to 100 IDs of Upgraded Smart+ Ads in the <code>ad_ids_v2</code> filter to retrieve the details of these ads.</li></ul></td>
    </tr>
    <tr>
      <td>Creative</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a> with the <code>ad_ids_v2</code> filter not specified.<ul><li>For creatives within Upgraded Smart+ Ads, the returned <code>campaign_automation_type</code> will be <code>UPGRADED_SMART_PLUS_CREATIVE</code>.</li><li>The returned <code>ad_id</code> represents the ID of a creative in the Upgraded Smart+ Ad and the returned <code>smart_plus_ad_id</code> represents the ID of the Upgraded Smart+ Ad.</li></ul></td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1843317489576961" target="_blank">/smart_plus/material_report/overview/</a> to retrieve an Upgraded Smart+ Creative Overview Report or <a href="https://business-api.tiktok.com/portal/docs?id=1843317510389761" target="_blank">/smart_plus/material_report/breakdown/</a> to retrieve an Upgraded Smart+ Creative Breakdown Report.</td>
      <td>Use <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> for synchronous reports or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a> for asynchronous reports.<ul><li>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id</code> in <code>dimensions</code>.</li><li>Optionally, specify up to 100 IDs of creatives within Upgraded Smart+ Ads in the <code>ad_ids</code> filter to retrieve the details of these creatives.</li></ul></td>
    </tr>
  </tbody>
</table>


