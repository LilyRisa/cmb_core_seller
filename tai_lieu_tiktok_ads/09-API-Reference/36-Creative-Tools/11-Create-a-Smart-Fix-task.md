---
title: "Create a Smart Fix task"
doc_id: 1741468875279361
path: "API Reference / Creative Tools / Create a Smart Fix task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Smart Fix task

Use this endpoint to create a task to automatically detect and fix video issues. 

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/video/fix/task/create/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
| Field | Data Type | Description |
|---|---|---|
| advertiser_id {required} | string | Advertiser ID. |
|tasks|object[]|Task info. Max size is 10.|
#|video_id {required}|string|ID of the Video that you want to fix.|
#|auto_bind_enabled|boolean|Whether to automatically upload the fixed video to your creative library. Default value:`False`.  |
```

### Examples
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/open_api/v1.3/video/fix/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "tasks": [
    {
      "video_id": "{{video_id}}"
    },
    {
      "video_id": "{{video_id}}"
    }
  ]
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data|object|The return data|
#|tasks |object[]|Task info. Max size is 10. |
##|video_id|string|Video ID. Returned when there are no issues detected. |
##|fix_task_id|string|Fix task ID. Returned when issues are detected.|
##|flaw_types|string[]|Returned when issues are detected. <br><br>Video issue types. <br><br>Enum values: <ul><li>`LOW_RESOLUTION`: Video resolution is lower than 540x960 px, which doesn't meet our requirements.</li><li>`ILLEGAL_VIDEO_SIZE`: The video size is not correct. Use the standard video size: Square (1:1) / Vertical (9:16) / Horizontal (16:9). </li><li>`NO_BGM`(<span style="color:Red">deprecated</span>): The ad or video has no background audio, or the background audio is incoherent/unclear. </li><li>`BLACK_EDGE`(<span style="color:Red">deprecated</span>) :  A video image contains black bars, which affects user experience and is not allowed. </li><li>`ILLEGAL_DURATION`(<span style="color:Red">deprecated</span>): Video length is either longer than 60s or shorter than 5s, which doesn't meet our requirements.|
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "202208180945000102452480040607B21A",
    "data": {
        "tasks": [
            {
                "flaw_types": [
                    "NO_BGM",
                    "LOW_RESOLUTION"
                ],
                "video_id": "v09033f20000bojg7jbdjls6pfaa9i2g",
                "fix_task_id": "130782063140497479"
            },
            {
                "flaw_types": [
                    "ILLEGAL_DURATION",
                    "NO_BGM",
                    "BLACK_EDGE"
                ],
                "video_id": "v090332c0000bp1th2f7421ibbs4cp10",
                "fix_task_id": "130781343733475399"
            }
        ]
    }
}
(/code);
```
><span style="font-size:14px">Related Links</span> 
>* <span style="font-size:14px">[Smart Fix](https://ads.tiktok.com/marketing_api/docs?id=1741472523309058)</span>

