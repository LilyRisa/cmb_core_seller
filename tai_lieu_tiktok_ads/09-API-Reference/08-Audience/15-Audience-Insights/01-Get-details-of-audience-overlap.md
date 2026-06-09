---
title: "Get details of audience overlap"
doc_id: 1797023590780930
path: "API Reference / Audience / Audience Insights / Get details of audience overlap"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of audience overlap

Use this endpoint to get the details of the audience overlap between the benchmark audience and up to four other TikTok Custom Audience segments.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/audience/insight/overlap/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|benchmark_custom_audience_id {Required}|string|The ID of the custom audience that you select as the benchmark audience. <br><p><span style="color:darkred"><b>Note</b></span>: The audience ID that you input here must be accessible by your advertiser ID. Otherwise, an error will occur.|
|comparison_custom_audience_ids|string[]|The IDs of the custom audience that you select as the comparison audience.<br>Max size: 4.<br><p><span style="color:darkred"><b>Note</b></span>: The audience IDs that you input here must be accessible by your advertiser ID. Otherwise, an error will occur.|
```

## Example
``` xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/audience/insight/overlap/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "benchmark_custom_audience_id": "{{benchmark_custom_audience_id}}",
    "comparison_custom_audience_ids": [
        "{{comparison_custom_audience_ids}}",
        "{{comparison_custom_audience_ids}}",
        "{{comparison_custom_audience_ids}}",
        "{{comparison_custom_audience_ids}}"
    ]
}'
(/code)
```

## Response
``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of a request, which uniquely identifies the request.|
|data |object|Returned Data. <br><p><span style="color:darkred"><b>Note</b></span>: Audience sizes fewer than 1,000 will not be displayed.|
#|benchmark_audience|object|Benchmark audience data.|
##|audience_id|string|Audience ID.|
##|audience_name|string|Audience name.|
##|audience_size|int|Audience size.|
##|targetable_users_count_range|string|The size range of users in the custom audience who have been active in the last 30 days and can be targeted.|
#|comparison_audiences|object[]|Comparison audience data.<br>Returned if you have input `comparison_custom_audience_ids` in your request.|
##|audience_id|string|Audience ID.|
##|audience_name|string|Audience name.|
##|audience_size|int|Audience size.|
##|targetable_users_count_range|string|The size range of users in the custom audience who have been active in the last 30 days and can be targeted.|
##|benchmark_overlap_rate|string|The base percentage of users from the benchmark audience that are available in the comparison audience.|
##|benchmark_overlap_rate_range|string|The percentage range of users from the benchmark audience that are available in the comparison audience.|
##|benchmark_overlap_count_range|string|The size range of users from the benchmark audience that are available in the comparison audience.|
```
## Example
``` xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "benchmark_audience": {
            "audience_id": "{{audience_id}}",
            "audience_name": "{{audience_name}}",
            "audience_size": 29690526,
            "targetable_users_count_range": "15,428,000-18,857,000"
        },
        "comparison_audiences": [
            {
                "audience_id": "{{audience_id}}",
                "audience_name": "{{audience_name}}",
                "audience_size": 4754,
                "targetable_users_count_range": "1,000-2,000",
                "benchmark_overlap_rate": "3%",
                "benchmark_overlap_rate_range": "0%",
                "benchmark_overlap_count_range": "< 1,000"
            },
            {
                "audience_id": "{{audience_id}}",
                "audience_name": "{{audience_name}}",
                "audience_size": 504,
                "targetable_users_count_range": "1,000-2,000",
                "benchmark_overlap_rate": "3%",
                "benchmark_overlap_rate_range": "0%",
                "benchmark_overlap_count_range": "< 1,000"
            },
            {
                "audience_id": "{{audience_id}}",
                "audience_name": "{{audience_name}}",
                "audience_size": 51090,
                "targetable_users_count_range": "25,000-31,000",
                "benchmark_overlap_rate": "3%",
                "benchmark_overlap_rate_range": "0%",
                "benchmark_overlap_count_range": "< 1,000"
            },
            {
                "audience_id": "{{audience_id}}",
                "audience_name": "{{audience_name}}",
                "audience_size": 19449461,
                "targetable_users_count_range": "12,179,000-14,885,000",
                "benchmark_overlap_rate": "7%",
                "benchmark_overlap_rate_range": "6%-9%",
                "benchmark_overlap_count_range": "1,156,000-1,413,000"
            }
        ]
    }
}'
(/code)
```

