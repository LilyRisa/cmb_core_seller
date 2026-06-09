---
title: "(Deprecated) Reservation ad reports"
doc_id: 1738864971714562
path: "Marketing API / Reporting / Guides / Report types / (Deprecated) Reservation ad reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Reservation ad reports

> <span style="color:DarkRed">**Important**</span><br>
> To retrieve the reporting data of TopView ads, use the [basic reports](https://business-api.tiktok.com/portal/docs?id=1738864915188737) or [audience reports](https://business-api.tiktok.com/portal/docs?id=1738864928947201) and specify the value `RESERVATION_TOP_VIEW` for the `buying_type` [filter](https://business-api.tiktok.com/portal/docs?id=1751443975608321).


Reservation ad reports are a type of report from which you can get spending and performance data of ads in your **reservation** ad accounts. Currently, to query reservation ads reports, you need to set `service_type` to `RESERVATION` and the only supported `report_type` is `BASIC`.

If you want to retrieve the reporting data of reservation ads in your **auction** ad accounts (TikTok Ads Manager accounts), set `service_type` to `AUCTION` and `report_type` to `BASIC `to run a [basic report](https://business-api.tiktok.com/portal/docs?id=1738864915188737).

> <span style="color:dodgerblue">**Note**</span><br>
> Running reservation ads reports in CHUNK mode (`query_mode`= `CHUNK`) is not supported. You can only run synchronous basic reports in CHUNK mode.


# Supported dimensions
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751631253780482) for supported dimensions of reservation ads reports.
# Supported metrics
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751631257050114) for supported metrics of reservation ads reports.
# Supported filters
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751631259350017) for supported filters of reservation ads reports.

# Lifetime supports
Lifetime metrics for reservation ad reports are not available in v1.3. 


# **Use Cases**

**1. Query the total consumption of advertiser in a certain period of time.**

**Request Parameters：**

```xcodeblock
(code json JSON)
{
    "advertiser_id": 66,
    "service_type": "RESERVATION",
    "report_type": "BASIC",
    "data_level": "RESERVATION_ADVERTISER",
    "dimensions": [
        "advertiser_id"
    ],
    "metrics": [
        "spend",
        "impressions"
    ],
    "start_date": "2020-10-25",
    "end_date": "2020-11-23",
    "page": 1,
    "page_size": 100
}
(/code)
```



**2. Query the daily reporting data of advertiser in a certain period of time.**

**Request Parameters：**
```xcodeblock
(code json JSON)
{
    "advertiser_id": 66,
    "service_type": "RESERVATION",
    "report_type": "BASIC",
    "data_level": "RESERVATION_ADVERTISER",
    "dimensions": [
        "advertiser_id", "stat_time_day"
    ],
    "metrics": [
        "spend",
        "impressions"
    ],
    "start_date": "2020-10-25",
    "end_date": "2020-11-23",
    "page": 1,
    "page_size": 100
}
(/code)
```



**3. Query the daily reporting data of some Ad Groups within a certain period of time.**

**Request Parameters：**

```xcodeblock
(code json JSON)
{
    "advertiser_id": 66,
    "service_type": "RESERVATION",
    "report_type": "BASIC",
    "data_level": "RESERVATION_ADGROUP",
    "dimensions": [
        "adgroup_id",
        "stat_time_day"
    ],
    "metrics": [
        "spend",
        "impressions"
    ],
    "filtering": [
        {
            "field_name": "adgroup_ids",
            "filter_type": "IN",
            "filter_value": "[123, 456]"
        },
        {
            "field_name": "adgroup_status",
            "filter_type": "IN",
            "filter_value": "[\"COMPLETE\", \"DELIVERING\"]"
        }
    ],
    "start_date": "2020-10-25",
    "end_date": "2020-11-23",
    "page": 1,
    "page_size": 100
}
(/code)
```
