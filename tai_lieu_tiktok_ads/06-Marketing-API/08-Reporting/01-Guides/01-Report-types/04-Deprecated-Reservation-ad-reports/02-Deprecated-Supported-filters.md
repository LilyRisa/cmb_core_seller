---
title: "(Deprecated) Supported filters"
doc_id: 1751631259350017
path: "Marketing API / Reporting / Guides / Report types / (Deprecated) Reservation ad reports / (Deprecated) Supported filters"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Supported filters

You can use the following filters to narrow down the scope of reporting results. 

Please read the following notes before you start.
  - When passing multiple filters, the filter conditions have `AND` relationships.
  - When using `IN` filter type, `filter_value` needs to be a valid JSON list character string.

> <span style="color:DodgerBlue">**Note**</span><br>
> In asynchronous reservation ad reports, the only supported filter fields are `campaign_ids`, `adgroup_ids`, and `ad_ids`.

# Supported filters

```xtable
| Filter Field   {15%} | Supported Filter Types {20%}     | Supported Data Levels {20%} | Description  {45%}                                                |
| ------------------- | ---------------------------- | --------------------- | ------------------------------------------------------------ |
| campaign_ids        | IN                           | Campaign/Ad group/Ad | Campaign ID, when using this filter, you can pass up to 100 campaign IDs. filter_value example: `"[123,456,789]"`. |
| campaign_name       | MATCH                        |Campaign         | Campaign name.                                                |
| campaign_status     | IN                           | Campaign/Ad group/Ad | Campaign status, optional: `NOTDELIVERY`, `DISABLE`, `DELIVERING`, `DELETE`. No filter indicates all states. |
| adgroup_ids         | IN                           | Ad group/Ad           | Ad Group ID, when using this filter, you can pass up to 100 `adgroup_id`. |
| adgroup_name        | MATCH                        |  Ad group          | Ad Group Name.                                                |
| adgroup_status      | IN                           | Ad group/Ad           | Ad Group status, optional: `COMPLETE`, `FROZEN`, `DELIVERING`, `CLOSED`, `NOTDELIVERY`, `DELETE`. No filtering indicates all states. |
| ad_ids              | IN                           | Ad                    | Ad ID, when using this filter, you can pass up to 100 `ad_id`.  |
| ad_status           | IN                           | Ad                    | Ad status, optional: `DELIVERING`, `NOT_DELIVERY`, `COMPLETE`, `DISABLE`, `DELETE`, `FROZEN`, `CLOSED`. No filtering indicates all states. |
| app                 | IN                           |  Ad group            | Available products: `BUZZ_UP`, `SPICY`, `BABE`, `BABE_PLUS`, `TOP_BUZZ`, `NEWS_REPUBLIC`, `ULIKE`, `BUZZ_VIDEO`, `TIKTOK`. filter_value example: `[\" TIKTOK\, "\" BUZZVIDEO\ "]`. No filter means all products. |
| billing_events      | IN                           | Ad group               | Billing method, optional `GD`, `CPT`.                         |
| platform            | IN                           |  Ad group             | Delivery platform, optional: `UNLIMITED`, `ANDROID`, `IOS`.   |
| locations           | IN                           | Ad group            | Countries or regions that you want to target. For enum values, see [Appendix - Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
| ad_placement        | IN                           |  Ad group          | Ad placement. |
| schedule_start_time | LESS_THAN/GREATER_THAN/RANGE | Ad group          | Ad delivery start time(UTC+0). Format: `YYYY-MM-DD`. For example: `2021-01-01`.       |
| schedule_end_time   | LESS_THAN/GREATER_THAN/RANGE |Ad group           | Ad delivery end time(UTC+0). Format: `YYYY-MM-DD`. For example:  `2021-01-01`.         |
```

  
  ## Examples
  
To get ad groups that start delivery between 2021-04-01 and 2021-04-10, we can use the following filter:
>  {"field_name": "schedule_start_time", "filter_type": "RANGE", "filter_value": "[\"2021-04-01\", \"2021-04-10\"]"}

