---
title: "DSA reports"
doc_id: 1738864960144385
path: "Marketing API / Reporting / Guides / Report types / DSA reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# DSA reports

Dynamic Showcase Ads (DSA) reports are a type of report from which you can get [Video Shopping Ads (for Catalog)](https://business-api.tiktok.com/portal/docs?id=1750361698613249) or Dynamic Showcase Ads (also known as Dynamic Product Ads) data. 

> <span style="color: dodgerblue">**Note**</span><ul><li>Running asynchronous DSA reports for Video Shopping Ads with product source as catalog is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>When you run DSA reports for Video Shopping Ads with product source as catalog, only the asynchronous mode is supported.</li><li>For a DSA report to generate data for Dynamic Showcase Ads, you must have access to the Dynamic Showcase Ads API. Otherwise, the report will show no data. Dynamic Showcase Ads API is an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Running DSA reports in CHUNK mode (`query_mode`= `CHUNK`) is not supported. You can only run synchronous basic reports in CHUNK mode.</li></ul>

# Supported dimensions
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751625287345154) for supported dimensions of DSA reports.
# Supported metrics
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751625293044737) for supported metrics of DSA reports.
# Supported filters
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751625296192514) for supported filters of DSA reports. They are the same as the supported filters in **Basic Report**. 
# Lifetime supports
Lifetime metrics for DSA reports are not available in v1.3. 


# Use cases

**1. Query the performance of catalog products in Video Shopping campaigns within a specific time period.** 

**Request Parameters**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": "{{advertiser_id}}",
    "report_type":"CATALOG",
    "data_level":"AUCTION_CAMPAIGN",
    "dimensions": ["campaign_id","sku_id_v2"],
    "metrics": ["campaign_name","product_name_v2", "conversion", "complete_payment", "value_per_complete_payment","total_complete_payment_rate"],
    "start_date": "2023-12-01",
    "end_date": "2023-12-18"
}
(/code)
```
