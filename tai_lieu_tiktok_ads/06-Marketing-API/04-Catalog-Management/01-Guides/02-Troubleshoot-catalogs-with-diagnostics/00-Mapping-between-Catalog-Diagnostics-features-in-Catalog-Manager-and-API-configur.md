---
title: "Mapping between Catalog Diagnostics features in Catalog Manager and API configurations"
doc_id: 1780803525320706
path: "Marketing API / Catalog Management / Guides / Troubleshoot catalogs with diagnostics / Mapping between Catalog Diagnostics features in Catalog Manager and API configurations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Mapping between Catalog Diagnostics features in Catalog Manager and API configurations

This article provides the mapping between Catalog Diagnostics features in Catalog Manager, a part of TikTok Ads Manager, and the corresponding API configurations. If you are a Catalog Manager user seeking to integrate with TikTok API for Business, we recommend that you read this article beforehand to gain a clear understanding of how Catalog Diagnostics features in Catalog Manager are supported in the TikTok API for Business.

## Diagnostics for catalog products
The following table lists the features for diagnosing catalog products in Catalog Manager and the corresponding API configurations.
<table>
<thead>
  <tr>
    <th style="width:14%">Catalog Manager <br>module</th>
    <th style="width:16%">Catalog Manager <br>feature</th>
    <th style="width:20%">UI Reference</th>
    <th style="width:27%">API endpoint</th>
    <th style="width:23%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Diagnostics<br></td>
    <td>Issue type (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5ddf30de9fd9278055d8f116aa5a0a97" alt="Issue type (filter)"  style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1771117232728066"  target="_blank">/diagnostic/catalog/</a></td>
    <td>Request parameter <br><code>issue_level</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Issue category (filter)</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/43fa7fce861d900164743e64b0b4f16a" alt="Issue category (filter)"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</code></td>
    <td>Request parameter <br> <code>issue_category</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Date</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4ba102965ad3ab99f4dfd7a2e422381e" alt="Date"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter  <br><code>diagnostic_date</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Issue type</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f38ac94e974915e3f93556c06c510875" alt="Issue type"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter  <br><code>issue_title</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Issue level</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4e5bd56b543ffb23f3d93e2d12c14178" alt="Issue level"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter  <br><code>issue_level</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Reason and suggestion</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a3927e05fd3cb59e72cd5de890eea352" alt="Reason and suggestiont"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter <br><code>reason_and_suggestion</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Issue category</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/af2fa2d7939c69e41337e60e6aa1157c" alt="Issue category"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter <br><code>issue_category</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Affected items</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/997835a30c7dd4905e579fa627d62d92" alt="Affected items"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter <br><code>affected_product_count</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>% affected</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/abe35db84af1015ac791ee8ec1d5f2bb" alt="% affected"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter  <br><code>affected_product_percentage</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Affected items <ul><li>View examples<View examples/li></ul> </td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/66bb165ede1e84ecf01e700738496a9f" alt="Affected items - View examples"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/</td>
    <td>Response parameter <br><code>example_affected_products</code></td>
  </tr>
  <tr>
    <td>Diagnostics</td>
    <td>Download</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0336058a8bb451a99d59ea734c148da0" alt="Download"  style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1771117279175682"  target="_blank">/diagnostic/catalog/product/task/create/</a><br><a href="https://business-api.tiktok.com/portal/docs?id=1771117294731266"  target="_blank">/diagnostic/catalog/product/task/get/</a></td>
    <td></td>
  </tr>
</tbody>
</table>

## Diagnostics for catalog event sources
The following table lists the features for diagnosing catalog event sources in Catalog Manager and the corresponding API configurations.
   	
<table>
<thead>
  <tr>
    <th style="width:14%">Catalog Manager <br>module</th>
    <th style="width:20%">Catalog Manager <br>feature</th>
    <th style="width:17%">UI Reference</th>
    <th style="width:31%">API endpoint</th>
    <th style="width:18%">API configurations</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>Event sources</td>
    <td>App ID or Pixel ID</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6a17a77bec59a462b5c819147a0a1d28" alt="App ID"  style="color:#808080" /><br><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0eff693eb9b2e4c63991e8f69b1f9d66" alt="Pixel ID"  style="color:#808080" /></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1780799405041665" target="_blank">/diagnostic/catalog/eventsource/issue/</a></td>
    <td>The request parameters:<ul><li><code>event_source_type</code></li><li><code>app_id</code></li><li><code>pixel_code</code></li></ul></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Issues and suggestions <ul><li>View Content</li><li>Add to Cart</li><li>Complete Payment</li></ul></td>
    <td> <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c291b909265a7ff7de2cbbb8e1d5b287" alt="Issues and suggestions View Content Add to Cart Complete Payment"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/issue/</code></td>
    <td>Request parameter <br><code>event_type</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Issues and suggestions <ul><li>Issues</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/92020fec734898805293cbcafa91f69c" alt="Issues"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/issue/</code></td>
    <td>Response parameter <br><code>diagnostic_result</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Issues and suggestions <ul><li>Issue level</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9b4f9c507cf5e89f5712d827ce8cb2a0" alt="Issue level"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/issue/</code></td>
    <td>Response parameter <br><code>level</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Issues and suggestions<ul><li>Solutions</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d5b59821852663748a9fd526f078990c" alt="Solutions"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/issue/</code></td>
    <td>Response parameter <br><code>diagnostic_solution</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>App ID or Pixel ID</td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7525f58bbfc1b062bcc416c860fed387" alt="App ID"  style="color:#808080" /><br> <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0eff693eb9b2e4c63991e8f69b1f9d66" alt="Pixel ID"  style="color:#808080" /></td>
    <td><a href="lhttps://business-api.tiktok.com/portal/docs?id=1780802101608450" target="_blank">/diagnostic/catalog/eventsource/metric/</a></td>
    <td>The request parameters:<ul><li><code>event_source_type</code></li><li><code>app_id</code></li><li><code>pixel_code</code></li></ul></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>View Content</li><li>Add to Cart</li><li>Complete Payment</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/39cfde57eb70f39cc93c01171eacf24c" alt="Events received from data sources View Content Add to Cart Complete Payment"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Request parameter <br><code>event_type</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>Yesterday</li><li>Last 7 days</li><li>Last 30 days</li></ul></td>
    <td> <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1e59248bda14291dbe762222517de629" alt="Yesterday Last 7 days Last 30 days"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Request parameter <br><code>time_range</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>All events</li><li>Events with content IDs</li><li>Events matching content IDs</li></ul></td>
    <td> <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5037f2dec0af23f1b77a5ee7996332d0" alt="All events Events with content IDs Events matching content IDs"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Response parameter <br><code>available_type</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>Date</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c49be8d3851eb1d4b1ac247ded4c58d" alt="Date"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Response parameter <br><code>date</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>Number</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/dca7d72c2d1635651247f7db9f55bf47" alt="Number"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Response parameter <br><code>count</code></td>
  </tr>
  <tr>
    <td>Event sources</td>
    <td>Events received from data sources <ul><li>Percentage</li></ul></td>
    <td><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5c044b1ef34342cfcda42ca65dbd72f3" alt="Percentage"  style="color:#808080" /></td>
    <td><code>/diagnostic/catalog/eventsource/metric/</code></td>
    <td>Response parameter <br><code>percentage</code></td>
  </tr>
</tbody>
</table>

     
