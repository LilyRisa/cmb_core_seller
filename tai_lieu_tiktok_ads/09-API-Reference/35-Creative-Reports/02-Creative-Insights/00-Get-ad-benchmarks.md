---
title: "Get ad benchmarks"
doc_id: 1738824501176321
path: "API Reference / Creative Reports / Creative Insights / Get ad benchmarks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get ad benchmarks

Use this endpoint to get the performance data of ads against benchmarks. 

> <span style="color: DodgerBlue">**Note**</span><br>All data returned via this endpoint has a latency of 30-48 hours. 

## Before you start
Ensure that each of the ads that you want to compare against benchmarks has accumulated at least 1,000 impressions within the comparison window. If an ad has fewer than 1,000 impressions within the comparison window, the result will be empty. To check the impressions of an ad within a specific time range, call [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) to create a report. Pass `start_date` and `end_date` and include the metric `impressions` in the field `metrics`.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/reports/ad_benchmark/get/|/v1.3/report/ad_benchmark/get/|
|Request parameter name|`order_field` <br> `order_type`|`sort_field`<br>`sort_type`|
|Request parameter data type |`advertiser_id`: number <br> `ad_ids`(in filtering): number[] <br> `adgroup_ids`(in filtering): number[] <br> `campaign_ids`(in filtering): number[] |`advertiser_id`: string <br> `ad_ids`(in filtering): string[] <br> `adgroup_ids`(in filtering): string[] <br> `campaign_ids`(in filtering): string[] |
|Response parameter data type|`ad_id`(in info): number<br>`metric_name`(in metrics) : string|`ad_id`(in info) : string<br>`metric_name`(in metrics) : number|
|Response parameter deprecated in v1.3|/|`metric_value`(in metrics) : number|
```


## Supported metrics
```xtable
|Metric{30%}| Description{70%}|
|-|-|
|cost| Cost|
|click|Clicks|
|impression|Impressions|
|conversion|Conversion|
|cpa|Cost per action|
|cpc|Cost per click|
|cpm|Cost per mille|
|cpv|Cost per view|
|ctr|Click-through rate|
|cvr|Conversion rate|
|pvr|Page view rate|
|profile_visits_rate|Profile visit rate|
|video_views| Video views|
|video_watched_2s|Video watched 2s|
|video_watched_6s|Video watched 6s|
|video_views_p25|Video watched 25%|
|video_views_p50| Video watched 50%|
|video_views_p75|Video watched 75%|
|video_views_p100| Video watched through (100%)|
|average_video_play| Average video play rate|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/report/ad_benchmark/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|compare_time_window|string|Time window for comparison (in days). Enum: `7` (default), `14`, `30`, `60`|
|dimensions {Required}|string[]|List of dimensions to compare. 1-4 dimensions are allowed. Enum: `LOCATION`, `AD_CATEGORY`, `EXTERNAL_ACTION`, `PLACEMENT`|
|metrics_fields|string[]|Metrics that you want to get. By default, all metrics will be returned. See the <b>Supported metrics</b> section for supported metrics.|
|filtering {Required}|object| Filtering conditions. You must specify one and only one out of the three conditions allowed|
#|ad_ids|string[]| IDs of the ads that you want to get benchmark results for|
#|adgroup_ids|string[]| IDs of the ad groups that you want to get benchmark results for|
#|campaign_ids|string[]| IDs of the campaigns that you want to get benchmark results for|
|sort_field|string| Field to sort by. By default, results will be sorted by `CREATE_TIME`|
|sort_type|string|Sorting type. Enum: `ASC` (starting from the one that was created earliest), `DES` (starting from the one that was created latest)|
|page|number|Page number|
|page_size|number| Page size|
```
### Example
```xcodeblock
(code Success-Response http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/ad_benchmark/get/?advertiser_id={{advertiser_id}}&dimensions=["LOCATION"]&filtering={{filtering}}&sort_field=AD_ID&sort_type=ASC&page=1&page_size=10&metrics_fields=["cost","impression","click","cpa"]' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}'  
(/code);
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request|
|data |object|Returned data|
#|compare_date|string|Date when the comparison is performed|
#|list|object|List of results. <br><br><p><span style="color:darkred"><b>Note</b></span>: If an ad accumulates fewer than 1,000 impressions within the comparison window (`compare_time_window`), the benchmark metric data will be empty.|
##|info|object|Information about the subject to get benchmark data for|
###|ad_id|string| Ad ID|
###|location|string|Two-letter country or region code|
###|placement|string|Placement|
###|ad_category|number|Level 2 interest category ID. For the list of categories, see [Get interest categories](https://ads.tiktok.com/marketing_api/docs?id=1737174348712961). <br>To determine the corresponding names for gaming-related interest category IDs, see [Interest category](https://business-api.tiktok.com/portal/docs?id=1738865030529025).|
###|external_action|string|External action (conversion event) for the ad|
##|metrics|object|Metrics and values|
###|metric_name|number| The returned metrics, determined by the setting via `metrics_fields` in the request. <br>After the metric name, you can see a number with one decimal place, in the value range of [0.0,1.0]. The number indicates how the current metric of the ad performs compared with other ads in terms of dimensions (`dimensions`) you specify in the request. For instance, `"video_watched_6s": 0.6` means the "video_watched_6s" metric data of the ad outperforms about 60% of all ads in terms of specified dimensions. <br>See the <b>Supported metrics</b> section for the supported metrics.|
#|page_info|object|Pagination information|
##|page|number|Current page number|
##|total_page|number|Total number of pages|
##|page_size|number|Page size. Default: 10|
##|total_number|number|Total number of results|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "2021120912121801024524603910501234",
    "data": {
        "compare_date": "2021-12-05",
        "list": [
            {
                "info": {
                    "placement": "TIKTOK",
                    "ad_id": "1234943678835714",
                    "location": "VN",
                    "ad_category": "0",
                    "external_action": "FORM_BUTTON"
                },
                "metrics": {
                    "video_watched_6s": 0.6,
                    "video_views_p25": 0.4,
                    "click": 0.8,
                    "video_views_p100": 0.4,
                    "average_video_play": 0.2,
                    "video_views_p50": 0.4,
                    "cpm": 0.1,
                    "profile_visits_rate": 0.9,
                    "cost": 0.7,
                    "impression": 0.8,
                    "cpa": 0.8,
                    "video_views_p75": 0.4,
                    "cpv": 0.0,
                    "cpc": 0.1,
                    "cvr": 0.2,
                    "ctr": 0.4,
                    "video_watched_2s": 0.7,
                    "pvr": 0.2,
                    "conversion": 0.6,
                    "video_views": 0.8
                }
            },
            {
                "info": {
                    "ad_id": "1234003068951586"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "total_page": 1,
            "page_size": 10,
            "total_number": 2
        }
    }
}
(/code);
```
