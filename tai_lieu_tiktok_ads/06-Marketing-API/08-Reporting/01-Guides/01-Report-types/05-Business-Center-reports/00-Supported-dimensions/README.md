---
title: "Supported dimensions"
doc_id: 1775747465089026
path: "Marketing API / Reporting / Guides / Report types / Business Center reports / Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions

A dimension is an attribute to group your data by. Business Center reports support the following dimensions. 

## Supported dimensions

```xtable
| Dimension Name {20%} |Description {70%}|
|---|---|
| advertiser_id | Group by Advertiser ID. <br><br>All metrics in Business Center reports are supported for this dimension. To find out the complete list of metrics in Business Center reports, refer to [Business Center reports - Supported metrics](https://business-api.tiktok.com/portal/docs?id=1775747476019266). |
| stat_time_day | Group by day (date). <br><br> To learn about the supported metrics for this dimension, refer to [Supported metrics for a dimension in Business Center reports - Supported metrics for `stat_time_day`, `stat_time_week` or `stat_time_month`](https://business-api.tiktok.com/portal/docs?id=1775747492072449#item-link-Supported%20metrics%20for%20stat_time_day%2C%20stat_time_week%20or%20stat_time_month). |
| stat_time_week | Group by week. <br><br>To learn about the supported metrics for this dimension, refer to [Supported metrics for a dimension in Business Center reports - Supported metrics for `stat_time_day`, `stat_time_week` or `stat_time_month`](https://business-api.tiktok.com/portal/docs?id=1775747492072449#item-link-Supported%20metrics%20for%20stat_time_day%2C%20stat_time_week%20or%20stat_time_month).  |
| stat_time_month | Group by month. <br><br>To learn about the supported metrics for this dimension, refer to [Supported metrics for a dimension in Business Center reports - Supported metrics for `stat_time_day`, `stat_time_week` or `stat_time_month`](https://business-api.tiktok.com/portal/docs?id=1775747492072449#item-link-Supported%20metrics%20for%20stat_time_day%2C%20stat_time_week%20or%20stat_time_month).  |
| placement | Group by placement. <br><br> To learn about the supported metrics for this dimension, refer to [Supported metrics for a dimension in Business Center reports - Supported metrics for `placement`](https://business-api.tiktok.com/portal/docs?id=1775747492072449#item-link-Supported%20metrics%20for%20placement). |
| country_code | Group by country or region code. <br><br>To learn about the supported metrics for this dimension, refer to [Supported metrics for a dimension in Business Center reports - Supported metrics for `country_code`](https://business-api.tiktok.com/portal/docs?id=1775747492072449#item-link-Supported%20metrics%20for%20country_code). |
```
  
  
 ## Dimension grouping
You can specify in the `dimensions` field of the request any of the groupings below:
- `["advertiser_id"]`
- `["stat_time_day"]`
- `["stat_time_week"]`
- `["stat_time_month"]`
- `["advertiser_id", "placement"]`
- `["advertiser_id", "country_code"]`
