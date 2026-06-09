---
title: "(Deprecated) Supported dimensions"
doc_id: 1751631253780482
path: "Marketing API / Reporting / Guides / Report types / (Deprecated) Reservation ad reports / (Deprecated) Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Supported dimensions

A dimension is an attribute to group your data by. Reservation ad reports support the following dimensions. Please note that currently, we don't support all dimensions available on Reservation Ad Platform.

# Supported Dimensions
``` xtable
| Value {20%} | Description {80%} |
| ---- | --- |
| advertiser_id | Group by advertiser ID |
| campaign_id | Group by campaign ID |
| adgroup_id | Group by ad group ID |
| ad_id | Group by ad ID <br><br><p><span style="color:darkred"><b>Note</b></span>: The title for this dimension in the title row of the asynchronous Reservation ad report, after translation, will be `Creative ID`, rather than `Ad ID`.|
| stat_time_day | Group by day |
| stat_time_hour | Group by hour |
| country_code | Group by location code <br> See [Supported metrics for a dimension in reservation ad reports](https://ads.tiktok.com/marketing_api/docs?id=1762405922636802) to learn about the supported metrics for this dimension.  |
```

# Dimension Grouping

The supported dimensions are categorized as ID, time, and location dimensions. 
- ID dimensions: `advertiser_id`, `campaign_id`, `adgroup_id`, and`ad_id`.
- Time dimensions: `stat_time_day` and `stat_time_hour`.
- Location dimension: `country_code`.

In each request, you can only have **one** ID dimension and **one** time dimension. For example, `["campaign_id", "stat_time_day"]`. 

If you want to use location as your grouping filter, you can specify in the `dimensions` field of the request:
- `country_code` only, or
- `country_code` + **one** ID dimension, or 
- `country_code` + **one** ID dimension + `stat_time_day`, or
- `country_code` + `stat_time_day`.


