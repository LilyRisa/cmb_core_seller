---
title: "Supported dimensions"
doc_id: 1751625287345154
path: "Marketing API / Reporting / Guides / Report types / DSA reports / Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions

A dimension is an attribute to group your data by. DSA reports support the following dimensions. Please note that currently, we don't support all dimensions available on TikTok Ads Manager.

# For Catalog Ads

> <span style="color:DodgerBlue">**Note**</span><br>
> When you run DSA reports for Catalog Ads (formerly known as Video Shopping Ads with product source as catalog), only the asynchronous mode is supported.


## Supported dimensions

```xtable
| Dimension Name{20%} | Description {80%}  |
|---|---|---|
| advertiser_id | Group by Advertiser ID. |
| campaign_id | Group by Campaign ID.|
| adgroup_id | Group by Ad Group ID.|
| ad_id_v2 | Group by Ad ID.<br><br>The types of data you can get include:<ul><li>the ad level data for Manual Ads</li><li>the ad level data for Smart+ Campaigns</li><li>the <b>ad (asset group)</b> level data for Upgraded Smart+ Ads</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You cannot use it together with the dimension `ad_id`, the filter `ad_ids`, or the attribute metric `ad_id`.</li></ul> |
| ad_id | Group by Ad ID. <br><br>The types of data you can get include:<ul><li>the ad level data for Manual Ads</li><li>the ad level data for Smart+ Campaigns</li><li>the <b>creative</b> level data for Upgraded Smart+ Ads</li></ul>|
| stat_time_day | Group by day. |
| catalog_id_v2 | Group by Catalog ID |
| product_id_v2 | Group by Catalog product ID. |
| sku_id_v2| Group by SKU ID.|
| brand | Group by Brand.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>See the “<strong>Dimension grouping</strong>” section below to learn about the supported dimension grouping.</li><li>This dimension is supported at all levels (`data_level` = `AUCTION_CAMPAIGN`, `AUCTION_ADGROUP`, `AUCTION_AD`, or `AUCTION_ADVERTISER`).</li><li>The supported metrics for this dimension are: <ul><li>`offline_shopping_events`</li><li>`value_per_offline_shopping_event`</li><li>`offline_shopping_events_value`</li><li>`total_purchase_value`</li><li>`total_complete_payment_rate`</li><li>`conversion`</li></ul></li></ul> |
| content_category | Group by Content Category.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>See the “<strong>Dimension grouping</strong>” section below to learn about the supported dimension grouping.</li><li>This dimension is supported at all levels (`data_level` = `AUCTION_CAMPAIGN`, `AUCTION_ADGROUP`, `AUCTION_AD`, or `AUCTION_ADVERTISER`).</li><li>The supported metrics for this dimension are: <ul><li>`offline_shopping_events`</li><li>`value_per_offline_shopping_event`</li><li>`offline_shopping_events_value`</li><li>`total_purchase_value`</li><li>`total_complete_payment_rate`</li><li>`conversion`</li></ul></li></ul> |
```
## Dimension grouping

The supported dimensions are categorized as ID, time, DSA, and Brand dimensions. 
- ID Dimensions：`advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id`
- Time Dimensions: `stat_time_day`
- DSA Dimensions: `catalog_id_v2`,  `product_id_v2`, `sku_id_v2`
- Brand Dimensions: `brand`, `content_category`.

In your request, if no Brand dimension is used, you should at least have one DSA dimension. ID and time dimensions are optional:
- One or more DSA dimensions (required) + single ID dimension (optional) + single time dimension (optional)

If you want to use a Brand dimension, ensure that your dimension grouping meets the following requirement:
- One Brand dimension (required) + single DSA dimension (optional) + single ID dimension (optional) + single time dimension (optional) 

> <span style="color:DodgerBlue">**Note**</span><br>
> The maximum number of dimensions you can use in one single request is four. Using too many dimensions may result in longer processing times.


# For Dynamic Showcase Ads
## Supported dimensions

```xtable
| Dimension Name{20%} | Description {80%}  |
|---|---|---|
| advertiser_id | Group by Advertiser ID |
| campaign_id | Group by Campaign ID<br><p><span style="color:darkred"><b>Note</b></span>: By default the `campaign_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous DSA reports. If you want to get breakdown data for all campaigns including the deleted ones via synchronous DSA reports, you need to set `campaign_status` to `STATUS_ALL`. |
| adgroup_id | Group by Ad Group ID<br><p><span style="color:darkred"><b>Note</b></span>: By default the `adgroup_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous DSA reports. If you want to get breakdown data for all ad groups including the deleted ones via synchronous DSA reports, you need to set `adgroup_status` to `STATUS_ALL`.|
| ad_id | Group by Ad ID<br><p><span style="color:darkred"><b>Note</b></span>: By default the `ad_status` filter is applied and defaults to `STATUS_NOT_DELETE` in synchronous DSA reports. If you want to get breakdown data for all ads including the deleted ones via synchronous DSA reports, you need to set `ad_status` to `STATUS_ALL`. |
| stat_time_day | Group by day |
| catalog_id | Group by Catalog ID |
| product_set_id | Group by Product Set ID |
| product_id | Group by Product ID |
|sku_id| Group by SKU ID|
```
## Dimension grouping

The supported dimensions are categorized as ID, time and DSA dimensions. 
- ID Dimensions：`advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id`
- Time Dimensions: `stat_time_day`
- DSA Dimensions: `catalog_id`, `product_set_id`, `product_id`, `sku_id`

In your request, you should at least have one DSA dimension. ID and time dimensions are optional. 
- Multiple DSA dimensions (required) + single ID dimension (optional) + single time dimension (optional)

> <span style="color: DodgerBlue">**Note**</span><br>For the DSA dimension, `product_set_id` must not be used with `product_id` or `sku_id`.


