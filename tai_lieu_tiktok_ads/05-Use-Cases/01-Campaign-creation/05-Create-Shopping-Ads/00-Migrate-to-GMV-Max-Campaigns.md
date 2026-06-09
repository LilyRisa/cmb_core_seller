---
title: "Migrate to GMV Max Campaigns"
doc_id: 1837161048383489
path: "Use Cases / Campaign creation / Create Shopping Ads / Migrate to GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Migrate to GMV Max Campaigns

**If you are creating TikTok Shop Ads, read this document to find out the changes and actions you should take.**

## Background
Starting Q3, 2025, [GMV Max](https://business-api.tiktok.com/portal/docs?id=1822009058467842) will be the default and only supported campaign type for TikTok Shop Ads. If you create ads using the Product Sales objective and TikTok Shop as your sales destination, you will no longer be able to use the API to create, edit, or duplicate LIVE Shopping Ads, Product Shopping Ads, or Video Shopping Ads. Learn more [about GMV Max migration for TikTok Shop Ads](https://ads.tiktok.com/help/article/gmv-max-migration-tiktok-shop-ads).

## What's changing

### Impact by TikTok Shop Ads types
The following table outlines the impact by TikTok Shop Ads types and their corresponding configurations.
<table>
  <thead>
  <tr>
    <th rowspan="2"  style="width:30%">Type of TikTok Shop Ad<br></th>
    <th rowspan="2"  style="width:10%">Impacted?</th>
    <th colspan="4"  style="width:60%; text-align: center">Configuration</th>
  </tr>
  <tr>
    <th colspan="2"  style="width:30%">Campaign level</th>
    <th colspan="2"  style="width:30%">Ad group level</th>
  </tr></thead>
<tbody>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1750361719059457"  target="_blank">Video Shopping Ads (with TikTok Shop as product source)</a></td>
    <td>✅</td>
    <td colspan="2"><code>objective_type</code> is <code>PRODUCT_SALES</code><br><code>campaign_product_source</code> is <code>STORE</code></td>
    <td colspan="2"><code>product_source</code> is <code>STORE</code> <br><code>shopping_ads_type</code> is <code>VIDEO</code><br><code>promotion_type</code> is <code>VIDEO_SHOPPING</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1785886237030401"  target="_blank">Product Shopping Ads</a></td>
    <td>✅</td>
    <td colspan="2"><code>objective_type</code> is <code>PRODUCT_SALES</code><br><code>campaign_product_source</code> is <code>STORE</code></td>
    <td colspan="2"><code>product_source</code> is <code>STORE</code><br><code>shopping_ads_type</code> is <code>RODUCT_SHOPPING_ADS</code><br><code>promotion_type</code> is <code>PSA_PRODUCT</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1754162402455554"  target="_blank">Live Shopping Ads</a> (with TikTok Shop specified)</td>
    <td>✅</td>
    <td colspan="2"><code>objective_type</code> is <code>PRODUCT_SALES</code><br><code>campaign_product_source</code> is <code>STORE</code></td>
    <td colspan="2"><code>product_source</code> is <code>STORE</code><br><code>shopping_ads_type</code> is <code>LIVE</code><br><code>promotion_type</code> is <code>LIVE_SHOPPING</code><br><code>store_id</code> is specified</td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1759232259360770"  target="_blank">Video Shopping Ads (with Showcase as product source)</a><br></td>
    <td>❌</td>
    <td colspan="2"><code>objective_type</code> is <code>PRODUCT_SALES</code><br><code>campaign_product_source</code> is <code>STORE</code></td>
    <td colspan="2"><code>product_source</code> is <code>SHOWCASE</code><br><code>shopping_ads_type</code> is <code>VIDEO</code><br><code>promotion_type</code> is <code>VIDEO_SHOPPING</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1754162402455554"  target="_blank">Live Shopping Ads</a> (with TikTok Shop not specified)</td>
    <td>❌</td>
    <td colspan="2"><code>objective_type</code> is <code>PRODUCT_SALES</code><br><code>campaign_product_source</code> is <code>STORE</code></td>
    <td colspan="2"><code>product_source</code> is <code>STORE</code><br><code>shopping_ads_type</code> is <code>LIVE</code><br><code>promotion_type</code> is <code>LIVE_SHOPPING</code><br><code>store_id</code> is not specified</td>
  </tr>
</tbody>
</table>

### Impact by endpoint
<table><thead>
  <tr>
    <th  style="width:10%">Module</th>
    <th  style="width:20%">Endpoints</th>
    <th  style="width:40%">Details</th>
    <th  style="width:30%">Error message</th>
  </tr></thead>
<tbody>
  <tr>
    <td rowspan="3">Ad group</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1739499616346114"   target="_blank">/adgroup/create/</a><br><a href="https://business-api.tiktok.com/portal/docs?id=1739586761631745"   target="_blank">/adgroup/update/</a></td>
    <td>Starting Q3, 2025, you cannot use these endpoints to create or update any <a href="#item-link-Impact by TikTok Shop Ads types">impacted TikTok Shop Ads types</a>. </td>
    <td><code>Custom shop ads are no longer available</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1739591133130817"   target="_blank">/adgroup/budget/update/</a></td>
    <td>Starting Q3, 2025, you cannot use this endpoint to update the budgets of any impacted TikTok Shop Ads types.</td>
    <td><code>This ad can't be turned on because custom shop ads are no longer available</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1739591716326402"   target="_blank">/adgroup/status/update/</a></td>
    <td>Starting Q3, 2025, you cannot use this endpoint to change the statuses of any impacted TikTok Shop Ads types. </td>
    <td><code>Custom shop ads are no longer available</code></td>
  </tr>
  <tr>
    <td rowspan="2">Ad <br></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1739953377508354"   target="_blank">/ad/create/</a><br><a href="https://business-api.tiktok.com/portal/docs?id=1739953405142018"   target="_blank">/ad/update/</a></td>
    <td>Starting Q3, 2025, you cannot use these endpoints to create or update any impacted TikTok Shop Ads types. </td>
    <td><code>This ad can't be edited because custom shop ads are no longer available</code></td>
  </tr>
  <tr>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1739953422970882"   target="_blank">/ad/status/update/</a><br></td>
    <td>Starting Q3, 2025, you cannot use this endpoint to change the statuses of any impacted TikTok Shop Ads types. </td>
    <td><code>Custom shop ads are no longer available</code></td>
  </tr>
</tbody></table>




## What actions you should take
- Migrate to [GMV Max API](https://business-api.tiktok.com/portal/docs?id=1822000911166465) and start creating new GMV Max Campaigns. To find out the detailed steps for creating such campaigns via API, see [Create GMV Max Campaigns](https://business-api.tiktok.com/portal/docs?id=1822009058467842).

## Why GMV Max API?
- **Automation at scale**: Increase the effectiveness of your Shop Ads Campaigns at scale. The GMV Max API allows you to increase **operational efficiency** and reduce manual workload.
- **Deep customization**: Build tailored advertising solutions that fit your unique advertising needs. With flexible API endpoints, you can **customize your campaign settings** based on your specific needs. Adapt quickly to market changes and optimize for the best results, all through programmatic control.
- **Seamless API integration**: Connect directly to the TikTok Shop ecosystem with robust, developer-friendly APIs. **Manage your entire campaign lifecycle**—from creation and optimization to reporting—within your own systems and workflows. Enjoy real-time data access and streamlined campaign management, powered by API-driven efficiency.
