---
title: "(Deprecated) Create a Quick Optimization task"
doc_id: 1739355078757377
path: "API Reference / Creative Tools / (Deprecated) Create a Quick Optimization task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create a Quick Optimization task

Use this endpoint to create a video optimization task, which is mainly about HORIZONTAL to VERTICAL transformation now. The execution time is approximately 1/2 of video duration. 

See [Smart Video Soundtrack, Smart Video, and Quick Optimization](https://ads.tiktok.com/marketing_api/docs?id=1749022727865345) to find out the brief introduction of the Quick Optimization feature.

Generated video name is `<orign_video_name>_optimize_<index>.mp4`.

 The rate limits for this endpoint are: 
```xtable
| Level {20%} | Query Per Second{20%}  | Query Per Minute{20%}  | Query Per Day{20%}  |
|---|---|---|
| Basic | 1 | 30 | 5000 |
| Advanced | 2 | 60 | 10000 |
| Premium | 3 | 90 | 15000 |
```

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/quick_optimization/create/|/v1.3/creative/quick_optimization/create/ |
| Request parameter data type | `advertiser_id`: number |`advertiser_id`: string |
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/quick_optimization/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Content-Type {Required}|string|Request content type <br>Accepted Value: `"application/json"`  |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|-|-|-|
| advertiser_id{Required} |string| Advertiser ID |
| video_id{Required}      | string   | Video ID |
| logo | string | Logo image ID, which will be shown in the optimized video |
| title | string | Title, which will be shown in the optimized video. Limit: 2-20 characters. |
| description  | string | Description, which will be shown in the optimized video |
| callback_info | object   | Callback information|
#| callback_url{+Conditional} | string   | Callback address. Required when you set `callback_info`. |
#| callback_extra_info | object   | Extra callback information, which will be returned when call back.|
```


## Response
```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Return code, see "[Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)".|
|message |string|Return message, see "[Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)".|
|data|object|Returned data|
#|task_id |string|Task ID |
|request_id|string|Request ID|
```

### Example
```xcodeblock
(code Success-Response python)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "task_id": "1864502164429996038"
    },
    "request_id": "2020082410541601018904922344E10F07"
}
(/code)
```
