---
title: "Audience reports"
doc_id: 1738864928947201
path: "Marketing API / Reporting / Guides / Report types / Audience reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Audience reports

Audience reports are a type of report from which you can get audience data, including age, gender, region, or interest.  

> <span style="color:DodgerBlue">**Notes**</span><ul><li>The data in audience reports is not real-time data. There is 10-12 hours of processing latency for audience data.</li><li>Running audience reports in CHUNK mode (`query_mode`= `CHUNK`) is not supported. You can only run synchronous basic reports in CHUNK mode.</li><li>We have optimized reporting performance for audience reports with the dimension of `behavior_id` via [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) (without `order_field` and `enable_total_metrics` parameters). This optimization can increase the number of queried IDs to 2000. Please note that the first request may take a long time to generate responses. However, subsequent requests with the same query parameters but different page numbers can be returned quickly.<br>
Currently, this optimization is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. Once you are on the allowlist, there is no need to add a new request parameter, as we will automatically enable it in the downstream.</li></ul>

# Supported dimensions
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751454103714818) for supported dimensions of audience reports.
# Supported metrics
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751454162042882) for supported metrics of audience reports.
# Supported filters
See [here](https://ads.tiktok.com/marketing_api/docs?id=1751454172429314) for supported filters of audience reports.
# Lifetime supports
Lifetime metrics for audience reports are not available in v1.3. 


# Use Cases

**1. Query the overall age distribution of the audience within a period of time, sorted in ascending order of spend.**

**Request Parameters:**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": 66,
    "service_type": "AUCTION",
    "report_type": "AUDIENCE",
    "data_level": "AUCTION_ADVERTISER",
    "dimensions": [
        "age"
    ],
    "metrics": [
        "spend",
        "impressions"
    ],
    "order_field": "spend",
    "order_type": "ASC",
    "start_date": "2020-10-16",
    "end_date": "2020-10-17",
    "page": 1,
    "page_size": 100
}
(/code)
```

**2. Query the region distribution of all undeleted ad groups under certain campaigns over a period of time.**

**Request Parameters:**

``` xcodeblock
(code json JSON)
{
    "advertiser_id": 66,
    "service_type": "AUCTION",
    "report_type": "AUDIENCE",
    "data_level": "AUCTION_ADGROUP",
    "dimensions": [
        "country_code",
        "adgroup_id"
    ],
    "metrics": [
        "spend",
        "impressions"
    ],
    "start_date": "2020-10-16",
    "end_date": "2020-10-16",
    "filtering": [
        {
            "field_name": "campaign_ids",
            "filter_type": "IN",
            "filter_value": "[1678604630215698,1678604630215698]"
        },
        {
            "field_name": "adgroup_status",
            "filter_type": "IN",
            "filter_value": "[\"STATUS_NOT_DELETE\"]"
        }
    ],
    "page": 1,
    "page_size": 100
}
(/code)
```




