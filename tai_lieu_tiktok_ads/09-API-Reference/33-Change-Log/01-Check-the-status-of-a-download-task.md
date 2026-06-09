---
title: "Check the status of a download task"
doc_id: 1739916077352961
path: "API Reference / Change Log / Check the status of a download task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the status of a download task

Use this endpoint to check whether a change log download task has completed or not.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/activities/task/check/|/v1.3/changelog/task/check/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/changelog/task/check/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|task_id {Required}|string| Task ID. |
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/changelog/task/check/
(/code)
```

## Response
``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return messages, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data|
#|status |string| Task status values: <ul><li>`PROCESSING`</li> <li>`SUCCESS`</li><li>`FAILED`</li></ul>|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "status": "SUCCESS"
    },
    "request_id": "2021011514371101023125009010565"
}
(/code);
```
