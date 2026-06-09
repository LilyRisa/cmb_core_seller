---
title: "Supported dimensions"
doc_id: 1803073899092993
path: "Marketing API / Reporting / Guides / Report types / GMV max ads reports / Supported dimensions"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Supported dimensions

A dimension is an attribute to group your data by. GMV max ads reports support the following dimensions.

## Supported dimensions

```xtable
| Dimension Name {20%} |Description {70%}|
|---|---|
| advertiser_id | Group by Advertiser ID. |
| campaign_id | Group by Campaign ID. |
| stat_time_day | Group by day (date). |
| country_code | Group by country or region code. |
```
  
  
 ## Dimension grouping
You can specify in the `dimensions` field of the request any of the groupings below:
- `["advertiser_id"]`
- `["advertiser_id", "country_code"]`
- `["advertiser_id", "country_code","stat_time_day"]`
- `["campaign_id", "country_code"]`
- `["campaign_id", "country_code","stat_time_day"]`

