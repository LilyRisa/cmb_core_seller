---
title: "Promote campaign"
doc_id: 1785880454546433
path: "Marketing API / Campaign Management / Guides / Campaign / Promote campaign"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Promote campaign

Promote is an advertising tool available within the TikTok App that enables you to increase exposure for your videos, drive traffic to your website, and enhance your chances of gaining followers. When you activate Promote for a video in the TikTok mobile App, a Promote campaign comprising a single Promote ad group and a single Promote ad will be automatically generated. 

To learn more about Promote on TikTok and how to set up a Promote video, see [Use Promote to grow your TikTok audience](https://support.tiktok.com/en/using-tiktok/growing-your-audience/use-promote-to-grow-your-tiktok-audience).

## Availability
The following table outlines whether a feature is supported for Promote ads in API.
> <span style="color:DodgerBlue">**Note**</span><br>
> [Business Center endpoints](https://business-api.tiktok.com/portal/docs?id=1739562438061058) don't support Promote ads.

```xtable
| Feature {30%}| Available in API {20%}| Related endpoints{50%} |
|---|---|---|
| Creating Promote campaigns, Promote ad groups, or Promote ads | ❌ |<ul><li> /campaign/create/</li><li>/adgroup/create/ </li><li>/ad/create/ </li></ul> |
| Updating Promote campaigns, Promote ad groups, or Promote ads | ❌ | <ul><li>/campaign/update/</li><li>/campaign/status/update/ </li><li>/adgroup/update/</li><li>/adgroup/status/update/</li><li>/adgroup/budget/update/</li><li>/ad/update/ </li><li>/ad/status/update/</li></ul> |
| Retrieving Promote campaigns, Promote ad groups, or Promote ads | ✅ | <ul><li>[/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986)</li><li> [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922)</li><li>[/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770)</li></ul> |
| Running synchronous and asynchronous basic reports and audience reports | ✅ |<ul><li>[/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353)</li><li>[/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602) </li><li> [/report/task/check/](https://business-api.tiktok.com/portal/docs?id=1740302781443073)</li><li>[/report/task/download/](https://business-api.tiktok.com/portal/docs?id=1740302808815618)</li></ul>  |
| Previewing Promote ads | ❌ | <ul><li>/creative/ads_preview/create/</li></ul> |
| Creating or binding automated rules | ❌ | <ul><li>/optimizer/rule/create/</li><li>/optimizer/rule/update/ </li><li>/optimizer/rule/batch_bind/ </li></ul> |
| Changelog | ❌ |<ul><li>/changelog/task/create/</li></ul>  |
| Ad comments | ❌ |<ul><li>/comment/list/</li></ul>  |
| Negative keywords | ❌ |<ul><li>/search_ad/negative_word/add/</li><li>/search_ad/negative_word/update/</li></ul>   |
```

## Statuses of Promote ads
To learn about the statuses of Promote ads at the campaign, ad group, and ad levels, see [Supported secondary statuses for a primary status - For Promote campaigns](https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns).

## Supported filters for Promote ads
The following table outlines whether a filter is supported for Promote Ads in [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986), [/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922), and [/ad/get](https://business-api.tiktok.com/portal/docs?id=1735735588640770). Note that the data for Promote ads are filtered out by default (`campaign_system_origins` is `["TT_ADS_PLATFORM"]`) in these endpoints. To retrieve the data for Promote ads, you can manually set `campaign_system_origins` to `["PROMOTE"]`.

<table>
<thead>
  <tr>
    <th style="width:20%">Endpoint</th>
    <th style="width:30%">Filter</th>
    <th style="width:50%">Supported for Promote ads? </th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="12">/campaign/get/</td>
    <td><code>campaign_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>campaign_name</code></td>
    <td>✅<br>Only full name searches are supported.</td>
  </tr>
  <tr>
    <td><code>primary_status</code></td>
    <td>✅<br>To find out the supported campaign primary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>secondary_status</code></td>
    <td>✅<br>To find out the supported campaign secondary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>objective_type</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>app_promotion_type</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>is_smart_performance_campaign</code></td>
    <td>✅<br>Promote campaigns don't support Smart+ Campaign. Therefore, the only supported value is <code>false</code>. </td>
  </tr>
  <tr>
    <td><code>campaign_product_source</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>optimization_goal</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>campaign_type</code></td>
    <td>✅<br>Promote campaigns are all regular campaigns. Therefore, the only supported value is <code>REGULAR_CAMPAIGN</code>.</td>
  </tr>
  <tr>
    <td><code>creation_filter_start_time</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>creation_filter_end_time</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td rowspan="13">/adgroup/get/</td>
    <td><code>campaign_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>adgroup_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>adgroup_name</code></td>
    <td>✅<br>Only full name searches are supported.</td>
  </tr>
  <tr>
    <td><code>primary_status</code></td>
    <td>✅<br>To find out the supported ad group primary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>secondary_status</code></td>
    <td>✅<br>To find out the supported ad group secondary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>objective_type</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>optimization_goal</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>promotion_type</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>bid_strategy</code></td>
    <td>✅<br>Promote ad groups only support the bidding strategy Maximum Delivery. Therefore, the only supported value is <code>BID_TYPE_NO_BID</code>.</td>
  </tr>
  <tr>
    <td><code>creative_material_mode</code></td>
    <td>✅<br>Promote ad groups don't support Automated Creative Optimization. Therefore, the only supported value is <code>CUSTOM</code>. </td>
  </tr>
  <tr>
    <td><code>billing_events</code></td>
    <td>✅<br>Promote ad groups only support the billing events CPC and oCPM. Therefore, the only supported values are <code>CPC</code> and <code>OCPM</code>.</td>
  </tr>
  <tr>
    <td><code>creation_filter_start_time</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>creation_filter_end_time</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td rowspan="11">/ad/get/</td>
    <td><code>campaign_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>adgroup_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>ad_ids</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>primary_status</code></td>
    <td>✅<br>To find out the supported ad primary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>secondary_status</code></td>
    <td>✅<br>To find out the supported ad secondary statuses, see <a href="https://business-api.tiktok.com/portal/docs?id=1757239620352002#item-link-For%20Promote%20campaigns" target="_blank">Supported secondary statuses for a primary status - For Promote campaigns</a>.</td>
  </tr>
  <tr>
    <td><code>objective_type</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>optimization_goal</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>creative_material_mode</code></td>
    <td>✅<br>Promote ad groups don't support Automated Creative Optimization. Therefore, the only supported value is <code>CUSTOM</code>. </td>
  </tr>
  <tr>
    <td><code>destination</code></td>
    <td>❌</td>
  </tr>
  <tr>
    <td><code>creation_filter_start_time</code></td>
    <td>✅</td>
  </tr>
  <tr>
    <td><code>creation_filter_end_time</code></td>
    <td>✅</td>
  </tr>
</tbody>
</table>

## Reporting data for Promote ads
You can run a [basic report](https://business-api.tiktok.com/portal/docs?id=1738864915188737) or an [audience report](https://business-api.tiktok.com/portal/docs?id=1738864928947201) at the campaign, ad group, or ad level for Promote ads. Note that the reporting data for Promote ads in synchronous basic reports and synchronous audience reports are filtered out by default (with `campaign_system_origins` set to `"[\"TT_ADS_PLATFORM\"]"` in `filtering`). To retrieve the synchronous reporting data for Promote ads, you can manually set `filtering` to `[{"field_name":"campaign_system_origins","filter_type": "IN","filter_value": "[\"PROMOTE\"]"}]` when making requests to [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353). However, in asynchronous basic reports and asynchronous audience reports, the reporting data for Promote ads is NOT filtered out by default. You can retrieve the asynchronous reporting data for Promote ads without specifying any filters.

### Supported dimensions
The following table outlines the supported dimensions for Promote ads. To find out the detailed descriptions of the dimensions, see [Basic reports - Supported dimensions](https://business-api.tiktok.com/portal/docs?id=1751443956638721) and [Audience reports - Supported dimensions](https://business-api.tiktok.com/portal/docs?id=1751454103714818).
```xtable
| Dimension {40%} | Supported in Basic reports?  {30%}| Supported in Audience reports?  {30%}|
|---|---|---|
| `advertiser_id` | ✅ | ✅ |
| `campaign_id` | ✅ | ✅ |
| `adgroup_id` | ✅ | ✅ |
| `ad_id` | ✅ | ✅ |
| `stat_time_day` | ✅ | ✅ |
| `gender` | ❌ | ✅ |
| `age` | ❌ | ✅ |
| `country_code` | ❌ | ✅ |
| `province_id` | ❌ | ✅ |
| `dma_id` | ❌ | ✅ |
| `ac` | ❌ | ✅ |
| `platform` | ❌ | ✅ |
| `interest_category` | ❌ | ✅ |
| `placement` | ❌ | ✅ |
| `ad_type` | ❌ | ✅ |
| `device_brand_id` | ❌ | ✅ |
```


### Supported metrics
The following table outlines the supported metrics for Promote ads. To find out the detailed descriptions of the metrics, see [Basic reports - Supported metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553) and [Audience reports - Supported metrics](https://business-api.tiktok.com/portal/docs?id=1751454162042882).
> <span style="color:DodgerBlue">**Note**</span><br>
> If you query unsupported metrics, the results may not provide meaningful information. For instance, you might get the value `"0"` for the metric `"vta_app_install"`.
```xtable
| Field {30%}| Description {30%} | Supported in Basic reports? {20%}| Supported in Audience reports? {20%}|
|---|---|---|---|
| Attribute metrics  | |
#| `advertiser_name` | Advertiser account name | ✅ | ✅ |
#| `advertiser_id` | Advertiser account ID | ✅ | ✅ |
#| `campaign_name` | Campaign name | ✅ | ✅ |
#| `campaign_id` | Campaign ID | ✅ | ✅ |
#| `campaign_budget` | Campaign budget | ✅ | ✅ |
#| `adgroup_name` | Ad group name | ✅ | ✅ |
#| `adgroup_id` | Ad group ID | ✅ | ✅ |
#| `placement_type` | Placement type | ✅ | ✅ |
#| `promotion_type` | Promotion type (Optimization location) <br>For Promote ads, the value will be `"Other"`.| ✅ | ✅ |
#| `opt_status` | Automated creative optimization | ✅ | ✅ |
#| `budget` | Ad group budget | ✅ | ✅ |
#| `smart_target` | Optimization goal | ✅ | ✅ |
#| `billing_event` | Billing Event | ✅ | ✅ |
#| `bid_strategy` | Bid strategy | ✅ | ✅ |
#| `ad_name` | Ad name | ✅ | ✅ |
#| `ad_id` | Ad ID | ✅ | ✅ |
#| `image_mode` | Format | ✅ | ❌ |
| Basic data metrics   |  |
#| `spend` | Total Cost | ✅ | ✅ |
#| `cpc` | CPC (Destination) | ✅ | ✅ |
#| `cpm` | CPM | ✅ | ✅ |
#| `impressions` | Impressions | ✅ | ✅ |
#| `clicks` | Clicks (Destination) | ✅ | ✅ |
#| `ctr` | CTR (Destination) | ✅ | ✅ |
#| `reach` | Reach | ✅ | ❌ |
#| `cost_per_1000_reached` | Cost per 1,000 people reached | ✅ | ❌ |
#| `conversion` | Conversions | ✅ | ✅ |
#| `cost_per_conversion` | CPA | ✅ | ✅ |
#| `conversion_rate` | CVR (Clicks) | ✅ | ✅ |
#| `conversion_rate_v2` | CVR (Impressions) | ✅ | ✅ |
#| `real_time_conversion` | Real-time Conversions | ✅ | ✅ |
#| `real_time_cost_per_conversion` | Real-time CPA | ✅ | ✅ |
#| `real_time_conversion_rate` | Real-time CVR (Clicks) | ✅ | ✅ |
#| `real_time_conversion_rate_v2` | Real-time CVR (Impressions) | ✅ | ✅ |
#| `result` | Result | ✅ | ✅ |
#| `cost_per_result` | Cost Per Result | ✅ | ✅ |
#| `result_rate` | Result Rate (%) | ✅ | ✅ |
#| `real_time_result` | Real-time Result | ✅ | ✅ |
#| `real_time_cost_per_result` | Real-time Cost Per Result | ✅ | ✅ |
#| `real_time_result_rate` | Real-time Result Rate (%) | ✅ | ✅ |
| Video play metrics   |   |
#| `video_play_actions` | Video Views | ✅ | ✅ |
#| `video_watched_2s` | 2-Second Video Views | ✅ | ✅ |
#| `video_watched_continuous_2s` | 2-second continuous video views | ✅ | ✅ |
#| `video_watched_6s` | 6-Second Video Views | ✅ | ✅ |
#| `engaged_view` | 6-second views (Focused view) | ✅ | ❌ |
#| `engaged_view_15s` | 15-second views (Focused view) | ✅ | ❌ |
| Engagement metrics   |   |
#| `engagements` | Clicks (All) | ✅ | ❌ |
#| `engagement_rate` | CTR (All) | ✅ | ❌ |
#| `profile_visits` | Paid Profile Visit | ✅ | ✅ |
#| `profile_visits_rate` | Paid Profile Visit Rate | ✅ | ✅ |
#| `likes` | Paid Likes | ✅ | ✅ |
#| `comments` | Paid Comments | ✅ | ✅ |
#| `shares` | Paid Shares | ✅ | ✅ |
#| `follows` | Paid Followers | ✅ | ✅ |
| LIVE metrics   |    |    |
#| `live_views` | LIVE Views | ✅ | ❌ |
#| `live_unique_views` | LIVE Unique Views | ✅ | ❌ |
#| `live_effective_views` | Effective LIVE Views | ✅ | ❌ |
#| `live_product_clicks` | LIVE Product Clicks | ✅ | ❌ |
| In-App Event metrics   |    |
#| `total_purchase` | Total purchase | ✅ | ❌ |
#| `cost_per_total_purchase` | Cost per Purchase | ✅ | ❌ |
#| `value_per_total_purchase` | Value per Purchase | ✅ | ❌ |
#| `total_purchase_value` | Total Purchase Value | ✅ | ❌ |
#| `total_active_pay_roas` | Purchase ROAS | ✅ | ❌ |
```

### Supported filters
The supported filters for Promote ads are:
- `campaign_ids`
- `adgroup_ids`
- `ad_ids`
- `campaign_status`
- `adgroup_status`
- `ad_status`
- `billing_event`
- `image_mode`
- `create_time`

To find out the detailed descriptions of the filters, see [Basic reports - Supported filters](https://business-api.tiktok.com/portal/docs?id=1751443975608321) and [Audience reports - Supported filters](https://business-api.tiktok.com/portal/docs?id=1751454172429314).

