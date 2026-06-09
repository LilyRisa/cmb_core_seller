---
title: "Playable ad reports"
doc_id: 1738864940608513
path: "Marketing API / Reporting / Guides / Report types / Playable ad reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Playable ad reports

Playable ad reports are a type of report from which you can get data about playable creatives. 

> <span style="color: DodgerBlue">**Note**</span><ul><li>Playable ads report returns data of playable creatives. `data_level` is not required in your request. <li>Running playable ad reports in CHUNK mode (`query_mode`= `CHUNK`) is not supported. You can only run synchronous basic reports in CHUNK mode.

# Supported dimensions
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751617879104514) for supported dimensions of playable ad reports.
# Supported metrics
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751617882108929) for supported metrics of playable ad reports.
# Supported filters
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751617884206210) for supported filters of playable ad reports.
# Lifetime supports
Playable ad reports support lifetime metrics at the Advertiser, Campaign, Ad group, and Ad levels.

Breakdown by time or location is not supported when querying for lifetime metrics, so when `query_lifetime` = `True` , `dimensions` cannot contain `stat_time_day`, `stat_time_hour`, or `country_code`.

You can use lifetime metrics in synchronous playable ad reports only, because playable ad reports do not support the asynchronous mode.


# Use Cases

**1. Query data of the playable ads whose spend are between 0 and 100 in a certain period of time.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": 666,
    "service_type": "AUCTION",
    "report_type": "PLAYABLE_MATERIAL",
    "dimensions": [
        "playable_id"
    ],
    "metrics": [
    	"playable_id",
        "playable_name",   
        "spend",
        "cpm",
        "impressions",
        "clicks",
        "ctr"
    ],
    "filtering": [
        {
            "field_name": "spend",
            "filter_type": "RANGE",
            "filter_value": "[0,100]"
        }
    ],
    "start_date": "2020-10-10",
    "end_date": "2020-11-10",
    "page": 1,
    "page_size": 100
}
(/code)
```

**2. Query data of each playable ads in different countries during a certain period of time.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": 666,
    "service_type": "AUCTION",
    "report_type": "PLAYABLE_MATERIAL",
    "dimensions": [
        "playable_id", "country_code"
    ],
    "metrics": [
    	"playable_id",
        "playable_name",   
        "spend",
        "cpm",
        "impressions",
        "clicks",
        "ctr"
    ],
    "start_date": "2020-10-10",
    "end_date": "2020-11-10",
    "page": 1,
    "page_size": 100
}
(/code)
```
