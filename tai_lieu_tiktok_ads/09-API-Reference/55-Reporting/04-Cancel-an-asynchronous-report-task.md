---
title: "Cancel an asynchronous report task"
doc_id: 1803615367145537
path: "API Reference / Reporting / Cancel an asynchronous report task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cancel an asynchronous report task

Use this endpoint to cancel an asynchronous report task that is either queued or currently being processed. 

Canceling an incorrect or long-running task can help save you time by allowing other queued tasks to proceed to the processing stage.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/report/task/cancel/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br><br><p><span style="color:darkred"><b>Note</b></span>: Specify the same access token that you used to create the asynchronous report task. Otherwise, the request might fail.|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string |  Advertiser ID.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you use `advertiser_ids` when calling [/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602) (POST method), you need to set this field to one of the Advertiser IDs specified via `advertiser_ids`.   |
|task_id {Required}|string| The ID of an asynchronous report task that is either queued or currently being processed. <br><br>To obtain such a task ID, first use [/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602) to create an asynchronous report task. Then use [/report/task/check/](https://business-api.tiktok.com/portal/docs?id=1740302781443073) to confirm that the status of the task is `QUEUING` or `PROCESSING` before attempting to cancel it. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/report/task/cancel/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "task_id":"{{task_id}}"
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|status |string| The status of the task. <br><br>Enum values: `CANCELED`. <br><br>If the task cancellation succeeds, the value of this field will be `CANCELED`. <br>If the task cancellation fails, an error will occur.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "status": "CANCELED"
    }
}
(/code)
```
