---
title: "(Deprecated) Get the status of a task"
doc_id: 1739351979503618
path: "API Reference / Creative Tools / (Deprecated) Get the status of a task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Get the status of a task

Use this endpoint to get the status of an asynchrnous creative tool task, such as [a Smart Video Soundtrack task](https://ads.tiktok.com/marketing_api/docs?id=1739089635852289), [a Quick Optimization task](https://ads.tiktok.com/marketing_api/docs?id=1739355078757377), and [a Smart Video task](https://ads.tiktok.com/marketing_api/docs?id=1739352294717441).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/creative/status/get/|/v1.3/creative/status/get/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name |`errmsg` |`error_msg` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/creative/status/get/

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|--- |--- |--- |
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|-|-|-|
|advertiser_id {Required}|string|Advertiser ID.|
|task_id {Required} |string|Task ID.|
```

## Response

```xtable
|Field|Data Type|Description|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|status|string| Task status. See [Enumeration-Creative Management-Task Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
#|video_ids{+ Conditional}|string|Video IDs. Valid only when `status` is `SUCCESS`.|
#|error_msg{+ Conditional}|string|Error message.|
#|extra_info{+ Conditional} |object |Extra callback info that is specified when the task was created. |
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
    "status": "SUCCESS",
    "video_ids": [
      "v09033a60000bt1posj6hujpgbgomlo2",
      "v090336c0000bt1pp2ac3teiuedco26s"
    ],
    "extra_info": {
      "name": "test"
    }
  }
}
(/code)
```

