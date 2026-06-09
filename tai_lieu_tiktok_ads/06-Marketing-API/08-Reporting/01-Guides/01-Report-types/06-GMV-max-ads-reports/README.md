---
title: "GMV max ads reports"
doc_id: 1803073629472770
path: "Marketing API / Reporting / Guides / Report types / GMV max ads reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# GMV max ads reports

GMV max ads reports are a type of report from which you can get data on [Product GMV max ads](https://ads.tiktok.com/help/article/how-to-create-product-gmv-max-ads-in-seller-center) and LIVE GMV max ads created from TikTok Shop.

> <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>When you run GMV max ads reports, only the synchronous mode is supported. To learn about how to create a synchronous GMV max ads report, refer to [Run a synchronous report](https://business-api.tiktok.com/portal/docs?id=1738864778664961#item-link-For%20GMV%20max%20ads%20reports).</li><li>Running GMV max ads reports in CHUNK mode (`query_mode` is  `CHUNK`) is not supported. You can only run synchronous basic reports in CHUNK mode.</li></ul>

# Supported dimensions
See [here](https://business-api.tiktok.com/portal/docs?id=1803073899092993) for supported dimensions of GMV max ads reports.

# Supported metrics
See [here](https://business-api.tiktok.com/portal/docs?id=1803073913733121) for supported metrics of GMV max ads reports.

# Supported filters
Filters for GMV max ads reports are not available.

# Lifetime supports
Lifetime metrics for GMV max ads reports are not available.


# Use cases

**1. Retrieve the consumption of GMV max ads within an ad account, segmented by region, during a designated time period.** 

Example Request

``` xcodeblock
(code json JSON)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&service_type=AUCTION&report_type=TT_SHOP&data_level=AUCTION_ADVERTISER&dimensions=["advertiser_id","country_code"]&metrics=["spend","billed_cost"]&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

**2. Retrieve the daily consumption of GMV max ads within an ad account, segmented by region, during a designated time period.** 

Example Request

``` xcodeblock
(code json JSON)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&service_type=AUCTION&report_type=TT_SHOP&data_level=AUCTION_ADVERTISER&dimensions=["advertiser_id","country_code","stat_time_day"]&metrics=["spend","billed_cost"]&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```

