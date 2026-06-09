---
title: "Get in-second performance"
doc_id: 1738825259075586
path: "API Reference / Creative Reports / Creative Insights / Get in-second performance"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get in-second performance

Use this endpoint to get in-second performance data about  ads, or Video Insights data about a video.  

To find out about how Video Insights data can be used, see [Creative insights-Video insights data](https://ads.tiktok.com/marketing_api/docs?id=1749023065231362). 

> <span style="color:DodgerBlue">**Note**</span><br>
> - Generally an ad video's duration is not longer than 60 seconds, and a video normally has the best performance within 60 seconds, so the performance data are provided for the first 60 seconds of a video. If a video is longer than 60 seconds, which may be the case for Pangle videos, all data after the 60th second will be attributed (added on) to the 60th second.
> - All data returned via this endpoint has a latency of one hour.



## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/reports/video_performance/get/|/v1.3/report/video_performance/get/|
|Request parameter name|`order_field` <br> `order_type`|`sort_field`<br>`sort_type`|
|Request parameter data type |`advertiser_id`: number <br> `ad_ids`(in `filtering`): number[] <br> `adgroup_ids`(in `filtering`): number[] <br> `campaign_ids`(in `filtering`): number[] |`advertiser_id`: string <br> `ad_ids`(in `filtering`): string[] <br> `adgroup_ids`(in `filtering`): string[] <br> `campaign_ids`(in `filtering`): string[] |
|New request parameters|/|`report_type`<br>`material_ids`<br>`video_ids`<br>`start_time`<br>`end_time`<br>`lifetime`|
|Response parameter name|`second`(in `info`)|`duration`(in `info`)|
|Response parameter data type|`ad_id`(in `info`): number|`ad_id`(in `info`): string|
|New metrics|/|`clicks`<br>`conversions`<br>`drop_off`<br>`retain`<br>`ctr`<br>`cvr`|
```


## Supported metrics
```xtable
| Report type <br> (<code>report_type</code>) {20%}| Metric {20%} | Type {15%}| Description {45%}|
|---|---|---|---|
| `AD` | `view_count` | number[] | Number of views the ad receives within the current second. |
| `AD` | `click_count` | number[] | Number of clicks on the ad within the current second. |
| `AD` | `slide_count` | number[] | Number of times a viewer slides from the ad to the profile page within the current second. |
| `AD` | `convert_count` | number[] | Number of times a viewer of the ad completes a conversion event within the current second. |
| `AD` | `break_count` | number[] | Number of viewers that stop viewing the ad within the current second. |
| `AD` | `like_count` | number[] | Number of likes the ad has received within the current second. |
| `AD` | `share_count` | number[] | Number of shares the ad has received within the current second. |
| `AD` | `comment_count` | number[] | Number of comments the ad has received within the current second. |
| `VIDEO` | `clicks` | integer[] | Number of clicks on the video within the current second. |
| `VIDEO` | `conversions` | integer[] | Number of times a viewer of the video completes a conversion event within the current second. |
| `VIDEO` | `drop_off` | integer[] | Number of viewers that stop viewing the video within the current second. |
| `VIDEO` | `retain` | integer[] | Number of viewers that continue viewing the video within the current second. |
| `VIDEO` | `ctr` | number[] | The percentage of clicks received out of all the views of the video within the current second. |
| `VIDEO` | `cvr` | number[] | The percentage of conversions received out of all the clicks on the video within the current second. |
```



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/report/video_performance/get/

**Method** GET

**Header**

```xtable
|Field {25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field{25%}|Data Type{15%}|Description{58%}|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
| report_type | string | Report type. <br>Enum values:  <ul><li>`AD` <span style="color:red">to-be-deprecated</span>: A report that provides in-seconds performance data on ads. </li><li>`VIDEO`: A Video Insights report on a video. </li></ul> Default value: `AD`.  <br><br><p><span style="color:darkred"><b>Note</b></span>: The enum value `AD` will be deprecated in the next API version. We recommend that you use `VIDEO` instead. |
|metrics_fields|string[]|Metrics that you want to get. By default, all metrics supported for the `report_type` that you specify will be returned. See the <b>Supported metrics</b> section for supported metrics.|
|filtering {Required}|object| Filtering conditions. <ul><li> When `report_type` is set to `AD` or not passed, you need to specify `ad_ids`, `adgroup_ids`, or `campaign_ids`. </li><li> When `report_type` is set to `VIDEO`, you need to specify either `material_ids` or `video_ids`.</li></ul>|
#|ad_ids {+Conditional} |string[]| When `report_type` is set to `AD` or not passed, you  need to specify `ad_ids`, `adgroup_ids`, or `campaign_ids`. <br> IDs of the ads that you want to get in-second performance data for.<br><p><span style="color:darkred"><b>Note</b></span>: Currently, the limit of the `ad_ids` you pass is 200.|
#|adgroup_ids {+Conditional} |string[]|When `report_type` is set to `AD` or not passed, you  need to specify `ad_ids`, `adgroup_ids`, or `campaign_ids`. <br>  IDs of the ad groups that you want to get in-second performance data for.|
#|campaign_ids {+Conditional} |string[]| When `report_type` is set to `AD` or not passed, you  need to specify `ad_ids`, `adgroup_ids`, or `campaign_ids`. <br> IDs of the campaigns that you want to get in-second performance data for.|
#| material_ids {+Conditional} | string[] | When `report_type` is set to `VIDEO`, you need to specify either `material_ids` or `video_ids`. <br>Material IDs of the videos that you want to get Video Insights data on.  <br>Max size: 1.   |
#| video_ids{+Conditional} | string[] | When `report_type` is set to `VIDEO`, you need to specify either `material_ids` or `video_ids`. <br>IDs of the videos that you want to get Video Insights data on.  <br>Max size: 1.   |
#| start_time | string | Valid only when `report_type` is set to `VIDEO`. <br>If you want to specify a time range for Video Insights data, you need to pass in both `start_time` and `end_time`, and specify `lifetime` as `false`.  <br>Query start time (closed interval) in the format of `YYYY-MM-DD hh:mm:ss` (UTC+0 Time).   |
#| end_time | string | Valid only when `report_type` is set to `VIDEO`.  <br> If you want to specify a time range for Video Insights data, you need to pass in both `start_time` and `end_time`, and specify `lifetime` as `false`.  <br>Query end time (closed interval) in the format of `YYYY-MM-DD hh:mm:ss`(UTC+0 Time).   |
#| lifetime | boolean | Valid only when `report_type` is set to `VIDEO`.  <br>If you want to specify a time range for Video Insights data, you need to pass in both `start_time` and `end_time`, and specify `lifetime` as `false`.  <br>Whether to request lifetime data for metrics.  <br> Default value: `true`.   |
|sort_field|string| Field to sort by. By default, results will be sorted by `CREATE_TIME`.|
|sort_type|string|Sorting type. Enum values: `ASC` (starting from the one that was created earliest), `DES` (starting from the one that was created latest).|
|page|number|Page number. Default value: 1.|
|page_size|number| Page size. Value range: 1-500. Default value: 10.|
```


### Example

```xcodeblock
(code Request http)
curl --location -g --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/video_performance/get/?filtering={"ad_ids":[<ad_id>]}&advertiser_id=<advertiser_id>' \
--header 'Access-Token: <ACCESS-TOKEN>'
(/code)
```

## Response

```xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|code |number|Response code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request.|
|data |object|Returned data.|
#|list|object|List of results.|
##|info|object|Information about the subject to get benchmark data for.|
###|ad_id|string| Ad ID.|
###|video_id|string|Video ID.|
###|duration|number|Video duration, in seconds.|
##|metrics|object|Metrics and values.|
###|metric_name|number[]| Metric name and the sequential in-seconds values. <br>For instance, `"share_count": [0, 0, 0, 1]` means the ad receives 0 shares in the first two seconds, and one share in the third second. Note the first value is for the 0th second. <br>See the <b>Supported metrics</b> section for the supported metrics.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|total_page|number|Total number of pages.|
##|page_size|number|Page size. Default: 10.|
##|total_number|number|Total number of results.|
```


### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202112091214160102452460391351BB20",
    "data": {
        "list": [
            {
                "info": {
                    "video_id": "123425gc0000c695b6bc77u1sho1s8g0",
                    "ad_id": "1234647413898242",
                    "duration": 18
                },
                "metrics": {
                    "slide_count": [
                        21,
                        7,
                        11,
                        2,
                        2,
                        1,
                        2,
                        0,
                        1,
                        0,
                        0,
                        1,
                        1,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "share_count": [
                        0,
                        0,
                        1,
                        0,
                        0,
                        0,
                        0,
                        0,
                        1,
                        0,
                        0,
                        0,
                        1,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "comment_count": [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "convert_count": [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "click_count": [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "view_count": [
                        19570,
                        14410,
                        6749,
                        4791,
                        3707,
                        2841,
                        2415,
                        1985,
                        1484,
                        1119,
                        858,
                        738,
                        658,
                        579,
                        505,
                        417,
                        311,
                        218
                    ],
                    "break_count": [
                        5160,
                        7661,
                        1958,
                        1084,
                        866,
                        426,
                        430,
                        501,
                        365,
                        261,
                        120,
                        80,
                        79,
                        74,
                        88,
                        106,
                        93,
                        92
                    ],
                    "like_count": [
                        65,
                        3567,
                        3981,
                        972,
                        413,
                        173,
                        140,
                        80,
                        86,
                        58,
                        26,
                        25,
                        14,
                        16,
                        14,
                        7,
                        14,
                        9
                    ]
                }
            }
        ],
        "page_info": {
            "page": 1,
            "total_page": 1,
            "page_size": 10,
            "total_number": 1
        }
    }
}
(/code);
```
