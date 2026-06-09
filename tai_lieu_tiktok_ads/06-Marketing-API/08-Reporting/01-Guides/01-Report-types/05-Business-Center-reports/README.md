---
title: "Business Center reports"
doc_id: 1775747432712194
path: "Marketing API / Reporting / Guides / Report types / Business Center reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Business Center reports

Business Center reports are a type of report from which you can get comprehensive data about ad accounts in a Business Center, including spend and performance data, video play data, as well as data for in-app events.

> <span style="color:DodgerBlue">**Note**</span><br>Currently, Business Center reports only support synchronous mode. To learn about how to create a synchronous Business Center report, refer to [Run a synchronous report](https://business-api.tiktok.com/portal/docs?id=1738864778664961).<br><br>Business Center reports support data retrieval for up to 365 days using the `start_date` and `end_date` parameters.

## Supported dimensions
See [here](https://business-api.tiktok.com/portal/docs?id=1775747465089026) for supported dimensions of Business Center reports.

## Supported metrics
See [here](https://business-api.tiktok.com/portal/docs?id=1775747476019266) for supported metrics of Business Center reports.

## Supported filters
See [here](https://business-api.tiktok.com/portal/docs?id=1775747484045313) for supported filters of Business Center reports.

## Lifetime supports
Lifetime metrics are not supported for Business Center reports.

## Use cases

> <span style="color:DodgerBlue">**Note**</span><br>
> Business Center reports support data retrieval for up to 365 days using the `start_date` and `end_date` parameters.

**1. Retrieve the reporting data of ad accounts within a specific Business Center, segmented by ad account, during a designated time period.**


**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "bc_id": "{{bc_id}}",
    "report_type": "BC",
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
    "page_size": 1000
}
(/code)
```

**2. Retrieve the reporting data of ad accounts within a specific Business Center, segmented by region, during a designated time period.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "bc_id": "{{bc_id}}",
    "report_type": "BC",
    "dimensions": [
        "advertiser_id",
        "country_code"
    ],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}", 
    "page": 1,
    "page_size": 1000
}

(/code)
```

**3. Retrieve the daily reporting data of ad accounts within a specific Business Center, during a designated time period.**

**Request Parameters：**

``` xcodeblock
(code json JSON)
{
    "bc_id": "{{bc_id}}",
    "report_type": "BC",
    "dimensions": [
        "stat_time_day"
    ],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}", 
    "page": 1,
    "page_size": 1000
}
(/code)
```
