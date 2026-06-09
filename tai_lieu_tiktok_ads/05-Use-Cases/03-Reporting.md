---
title: "Reporting"
doc_id: 1758528841973762
path: "Use Cases / Reporting"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Reporting

This article walks you through the steps to run a synchronous or asynchronous report.

# Introduction
Sometimes it might be hard to integrate fragmented data for different advertising accounts. Reporting API enables you to obtain performance data for different advertising accounts in customized dimensions and granularities. You can run a synchronous or asynchronous report on different report types. 

**You can use Reporting API to pull key data to create dashboards customized to your business needs. There is more efficiency and flexibility in pulling reports with API.**

# Prerequisites
- You've gained access to Tiktok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 
  - To run a report, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out the required permissions for each endpoint and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.    
- Asynchronous report is currently an allowlist-only feature. If you would like to access it, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page.

# Steps
1. Use [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) to run a synchronous report. See [Run a synchronous report](https://ads.tiktok.com/marketing_api/docs?id=1738864778664961&rid=f9r19belyh) for detailed steps. 

> <span style="color: DodgerBlue">**Note**</span><br>Different report types support different dimensions, metrics and filters, and you can see the corresponding articles under Report types for details.                 

Below is an example of querying the total consumption of an advertiser in a certain period of time.
```xcodeblock
(code)
      {
    "advertiser_id":"{{advertiser_id}}",
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
    "start_date": "2020-10-16",
    "end_date": "2020-10-17", 
    "page": 1,
    "page_size": 200
}
(/code)
```

2. Use [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602), [/report/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1740302781443073) and [/report/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1740302808815618) to run an asynchronous report. See [Run an asynchronous report](https://ads.tiktok.com/marketing_api/docs?id=1738864800380930&rid=f9r19belyh) for detailed steps. 

> <span style="color: DodgerBlue">**Note**</span><br>Different report types support different dimensions, metrics and filters, and you can see the corresponding articles under Report types for details. 

 Below is an example of querying the clicks of an ad grouped by hour in a certain period of time.
```xcodeblock
(code)
  {
    "advertiser_id": "{{advertiser_id}}",
    "page":1,
    "data_level":"AUCTION_AD",
    "report_type":"BASIC",
    "dimensions": ["ad_id","stat_time_hour"],
    "metrics": ["clicks"],
    "page_size": 1,
    "start_date": "2022-07-17",
    "end_date": "2022-09-17",
    "output_format": "CSV_DOWNLOAD",
    "file_name": "test"
}
(/code)

```
# Related docs
- [Reporting API](https://ads.tiktok.com/marketing_api/docs?id=1740302665828417)
- [Reporting Guide](https://ads.tiktok.com/marketing_api/docs?id=1738864739862530)
	- [Data latency for reports](https://ads.tiktok.com/marketing_api/docs?id=1738864894606337&rid=jai54xmsgjc)
	- [Best practices to reduce timeouts in synchronous reports](https://ads.tiktok.com/marketing_api/docs?id=1738864817670145&rid=jai54xmsgjc)

