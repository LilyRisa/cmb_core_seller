---
title: "(Deprecated) Create a Smart Video Soundtrack task"
doc_id: 1739089635852289
path: "API Reference / Creative Tools / (Deprecated) Create a Smart Video Soundtrack task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create a Smart Video Soundtrack task

Use this endpoint to create a Smart Video Soundtrack task. The execution time of the task is about 1 / 2 of the video time.

See [Smart Video Soundtrack, Smart Video, and Quick Optimization](https://ads.tiktok.com/marketing_api/docs?id=1749022727865345) to find out the brief introduction of the Smart Video Soundtrack feature.

The rate limits for this endpoint are:
```xtable
| Level {20%} | Query Per Second{20%} | Query Per Minute{20%} | Query Per Day{20%} |
| --- | --- | --- | ---|
| Basic | 1 | 30 | 5000 |
| Advanced | 2 | 60 | 10000 |
| Premium | 3 | 90 | 15000 |
```
> <span style="color:darkred">**Copyright Notice**</span><br>You shall not download, make available, distribute or perform the video and/or the music file incorporated in the video on any media other than TikTok, unless you have obtained separate permission form the music rights holders. You shall be solely responsible for any third party claims and disputes caused by your breach of these terms, and will defend and indemnify TikTok and its affiliates for all losses sustained.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/video_soundtrack/create/|/v1.3/creative/video_soundtrack/create/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/video_soundtrack/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Content-Type {Required}|string|Request content type. <br>Accepted Value: `"application/json"`.  |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id{required} | string | Advertiser ID |
| video_id{required} | string | Video ID |
| music_ids | string[] | Customize the list of music IDs. If you want to use music of your choice, use this field to set it up. If you want to use the music recommended by the system, ignore this field. Quantity limit: < 9 |
| video_volume | float | Video volume. Value range: [0,1]. The larger the value, the louder the volume. Default value: 0.5. |
| music_volume | float | Music volume. Value range: [0,1]. The larger the value, the louder the volume. Default value: 0.5. |
| name_prefix | string | Video name prefix. The video name length must be with [1,90] characters. If the name is not set, we use the original video name as `name_prefix`. Generated video name:  `&lt;name_prefix&gt;_&lt;music_name&gt;`. <br> If the music has no name, the name of the generated video will be `&lt;name_prefix&gt;_&lt;timestamp&gt;`.|
| callback_info | object   | Callback information|
#| callback_url{+Conditional} | string   | Callback address. Required when you set `callback_info` |
#| callback_extra_info | object   | Extra callback information, which will be returned when call back |
```


## Response

```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|task_id |string|Task ID. |
|request_id|string|Request ID.|
```

### Example

```xcodeblock
(code Success-Response python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "task_id": 1864502164429996038
    },
    "request_id": "2020082410541601018904922344E10F07"
}
(/code)
```
