---
title: "Get the status of a comment export task"
doc_id: 1738088242659330
path: "API Reference / Ad Comments / Get the status of a comment export task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the status of a comment export task

Use this endpoint to check the status of a comment export task.

When the task completes, you can call [/comment/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1738088341958658) to download the exported data. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/task/check/|/v1.3/comment/task/check/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/comment/task/check/

**Method** GET

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID|
|task_id {Required}|string| Task ID |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/comment/task/check/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|-|-|-|
|message|string|The return message. For Details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|data |object| Data  |
#|task_id |string| Task ID |
#|status |string| Task status. Enum values: `RUNNING`, `SUCCEED`, `FAILED`. |
|request_id |string|Unique ID of the request  |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
