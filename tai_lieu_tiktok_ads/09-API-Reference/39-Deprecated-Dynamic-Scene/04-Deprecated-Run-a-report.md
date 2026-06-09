---
title: "(Deprecated) Run a report"
doc_id: 1740317382510593
path: "API Reference / (Deprecated) Dynamic Scene / (Deprecated) Run a report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Run a report

Use this endpoint to run a report of dynamic scene videos.

> <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dynamic_scene/report/get/| /v1.3/dynamic_scene/report/get/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
```


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/dynamic_scene/report/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field | Data Type | Description |
|---|---|---|
| advertiser_id {Required} |string| Advertiser ID |
| data_level {Required}| string | The data level that you'd like to query in reports. Currently we only support `ADGROUP`. |
| input_ids {Required} | string[] | IDs of the `data level` fields. Currently we only support ad group id.  |
| dimensions {Required} | string[] | Grouping conditions. For example: `["campaign_id", "stat_time_day"]` indicates that both campaign_id and stat_time_day (days) are grouped. <br> Currently, we only support the value of`[advertiser_id, adgroup_id, story_arc, order, video_id]` in this field. Make sure that you've passed all the values of  `[advertiser_id, adgroup_id, story_arc, order, video_id]` in this field.|
| metrics | string[] | Metrics to query. Refer to the Response section for details.  |
| start_time | string | Query start date in the format of `YYYY-MM-DD`. The date is based on the ad account time zone.  |
| end_time | string | Query end date in the format of `YYYY-MM-DD`. The date is based on the ad account time zone. <br><p><span style="color:darkred"><b>Note</b></span>: The start time and end time should be no more than one year apart. |
| order_field | string | Sort field. All supported metrics (excluding attribute metrics) support sorting, not sorting by default.|
| order_type | string | Sort. Enum values: `ASC`, `DESC`. Default value: `DESC` .|
| filters | object[] | Filter criteria. |
#| field_name | string | Filter name. Enum values: `story_arc`, `order`. |
#| filter_type | string | Filter type. We currently support `IN` in this field. |
#| filter_value | string | The value to filter. When `filter_type = IN`, `filter_value` needs to be a valid JSON array character string. <br>If `filter_name` is `order` and you want to pass in all order options, specify `-1` in the field.   |
| page | number | Current number of pages. Default value: `1`. |
| page_size | number | Pagination size. Value range: 1-1000. Default value: `10`. |
```

### Example

``` xcodeblock
(code curl http)
{
    "advertiser_id": 6865632230773882886,
    "data_level": "ADGROUP",
    "input_ids": ["1734945725409298"],
    "dimensions": ["advertiser_id", "adgroup_id", "story_arc", "order", "video_id"],
    "order_field": "complete_plays",
    "start_time": "2022-06-09",
    "end_time": "2022-06-15",
    "filters": [
        {"field_name": "story_arc", "filter_type": "IN", "filter_value": "[\"wing display\", \"wing display\", \"wing display\", \"wing display\", \"Ending CTA\"]"},
        {"field_name": "order", "filter_type": "IN", "filter_value": "[\"-1\"]"}
    ]
}
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|list |object[]|Returned data.|
#|dimension|object|Dimension data.|
##|advertiser_id|string|Advertiser ID.|
##| adgroup_id|string|Ad group ID.|
##|story_arc|string|Refers to the structure and shape of a story, consisting of multiple slots.  |
##|order|number|The order of the story arc, ranging from 0 to 5.|
##|video_id|string|IDs of all videos that have been created. |
#|metric|object|Metric data.|
##| clicks|float|The number of clicks your video receives.|
##| complete_plays|float|The number of times your video is played to completion.|
##| break_plays|float|The number of times that people drop off on your video.|
##| ctr|float|The number of clicks that your video receives divided by the number of times your video is shown.|
##| complete_play_rate|float|The number of times that your video is played to completion divided by the number of times your video starts to play.|
##|  break_play_rate|float|The number of times that people drop off on your video divided by the number of times your video starts to play.|
|request_id |string|The log id of the request, which uniquely identifies a request  |
```

### Example
``` xcodeblock
(code Success-Response http)

{
    "code": 0,
    "message": "OK",
    "request_id": "202206240742360102452460661D20FC4D",
    "data": {
        "list": [
            {
                "metrics": {
                    "scene_play_complete": 3190,
                    "clicks": 172,
                    "complete_play_rate": 0.1,
                    "play_break_count": 29242,
                    "break_play_rate": 0.9,
                    "ctr": 0.53
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "0",
                    "video_id": "v10033g50000c8splk3c77ubudr9fdo0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 419,
                    "clicks": 45,
                    "complete_play_rate": 0.09,
                    "play_break_count": 4091,
                    "break_play_rate": 0.91,
                    "ctr": 1.0
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "0",
                    "video_id": "v10033g50000c8sohe3c77ucmpbk3ts0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 1007,
                    "clicks": 29,
                    "complete_play_rate": 0.32,
                    "play_break_count": 2183,
                    "break_play_rate": 0.68,
                    "ctr": 0.91
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "1",
                    "video_id": "v10033g50000c8sohqbc77u3o2tpa6j0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 112,
                    "clicks": 7,
                    "complete_play_rate": 0.27,
                    "play_break_count": 307,
                    "break_play_rate": 0.73,
                    "ctr": 1.67
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "1",
                    "video_id": "v10033g50000c8sp4rbc77ub2kipqn10"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 614,
                    "clicks": 12,
                    "complete_play_rate": 0.61,
                    "play_break_count": 393,
                    "break_play_rate": 0.39,
                    "ctr": 1.19
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "2",
                    "video_id": "v10033g50000c8sohe3c77ucmpbk3ts0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 54,
                    "clicks": 5,
                    "complete_play_rate": 0.48,
                    "play_break_count": 58,
                    "break_play_rate": 0.52,
                    "ctr": 4.46
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "2",
                    "video_id": "v10033g50000c8sohqbc77u3o2tpa6j0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 331,
                    "clicks": 6,
                    "complete_play_rate": 0.54,
                    "play_break_count": 283,
                    "break_play_rate": 0.46,
                    "ctr": 0.98
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "3",
                    "video_id": "v10033g50000c8sp4rbc77ub2kipqn10"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 44,
                    "clicks": 1,
                    "complete_play_rate": 0.81,
                    "play_break_count": 10,
                    "break_play_rate": 0.19,
                    "ctr": 1.85
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "3",
                    "video_id": "v10033g50000c8splk3c77ubudr9fdo0"
                }
            },
            {
                "metrics": {
                    "scene_play_complete": 0,
                    "clicks": 9,
                    "complete_play_rate": 0.0,
                    "play_break_count": 375,
                    "break_play_rate": 1.0,
                    "ctr": 2.4
                },
                "dimensions": {
                    "advertiser_id": "6865632230773882886",
                    "story_arc": "wing display-wing display-wing display-wing display-Ending CTA",
                    "adgroup_id": "1734945725409298",
                    "order": "4",
                    "video_id": "v10033g50000c8sms2jc77u9e2o0ftr0"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 3,
            "total_number": 25
        }
    }
}
(/code);
```
