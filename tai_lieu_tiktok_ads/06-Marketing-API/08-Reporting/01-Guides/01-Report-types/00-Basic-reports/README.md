---
title: "Basic reports"
doc_id: 1738864915188737
path: "Marketing API / Reporting / Guides / Report types / Basic reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Basic reports

Basic reports are a type of report from which you can get the most comprehensive data about your ads, including spend and performance data, video play data, engagement data, as well as data for in-app and website events. 

# Supported dimensions
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751443956638721) for supported dimensions of basic reports.

# Supported metrics
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751443967255553) for supported metrics of basic reports.

# Supported filters
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751443975608321) for supported filters of basic reports.

# Lifetime supports
Basic reports support lifetime metrics at the Advertiser, Campaign, Ad group, and Ad levels.

Breakdown by time or location is not supported when querying for lifetime metrics, so when  `query_lifetime` = `True` , `dimensions` can not contain `stat_time_day`, `stat_time_hour` or `country_code`.

# Use cases

**1. Query the total consumption of an advertiser in a certain period of time.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": "{{advertiser_id}}",
    "service_type": "AUCTION",
    "report_type": "BASIC",
    "data_level": "AUCTION_ADVERTISER",
    "dimensions": [
         "advertiser_id"
    ],
    "metrics": [
        "spend",
        "impressions",
        "reach"
    ],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}", 
    "page": 1,
    "page_size": 200
}
(/code)
```



**2. Query the daily reporting data of some ad groups within a certain period of time.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "advertiser_id":  "{{advertiser_id}}",
    "service_type": "AUCTION",
    "report_type": "BASIC",
    "data_level": "AUCTION_ADGROUP",
    "dimensions": [
        "adgroup_id",
        "stat_time_hour"
    ],
    "metrics": [
        "spend",
        "impressions",
        "reach"
    ],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}",
    "filtering": [
        {
            "field_name": "adgroup_ids",
            "filter_type": "IN",
            "filter_value": "[{{adgroup_id}},{{adgroup_id}}]"
        },
        {
            "field_name": "adgroup_status",
            "filter_type": "IN",
            "filter_value": "[\"STATUS_DELIVERY_OK\"]"
        }
    ],
    "query_lifetime": false,
    "page": 1,
    "page_size": 200
}
(/code)
```


