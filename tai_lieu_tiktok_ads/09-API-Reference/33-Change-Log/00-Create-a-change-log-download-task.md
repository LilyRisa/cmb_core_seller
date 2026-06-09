---
title: "Create a change log download task"
doc_id: 1738811219460097
path: "API Reference / Change Log / Create a change log download task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a change log download task

Use this endpoint to initiate a change log download task for an ad account based on the parameters that you specify. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/activities/task/create/|/v1.3/changelog/task/create/|
|Request parameter name|`opt_types`<br>`order_field`|`operation_types`<br>`order_fields`|
|Request parameter data type |`advertiser_id`:number<br>`order_fields`:string|`advertiser_id`: string<br>`order_fields `:string[]|
|Request parameters deprecated in v1.3|/|`page`<br>`page_size`|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/changelog/task/create/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. <br data-tomark-pass> Allowed values: `"application/json"`. |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID |
|start_time |string| Query start time, in the format of `YYYY-MM-DD HH:MM:SS`.<br>Example: `2025-09-01 00:00:00`. |
|end_time |string| Query end time, in the format of `YYYY-MM-DD HH:MM:SS` <br>Example: `2025-09-01 00:00:00`.<br><p><span style="color:darkred"><b>Note</b></span>: The largest time gap allowed is 30 days, so please specify a date within 30 days after the start_time as end_time. |
|timezone |string| Timezone of returned value, refer to [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089) for optional values. |
|module |string| Module to be queried, optional values: <ul><li>`BIDDING_AND_OPTIMIZATION`</li> <li>`BUDGET`</li><li>`STATUS`</li><li>`TARGETING`</li></ul> |
|object_ids |string[]| Query object ID |
|object_type |string| Query object type, optional values: `AD`,`ADGROUP`,`ADVERTISER`,`CAMPAIGN`,`INSTANT_FORM`  |
|operation_types |string[]| Operation type, optional values: `CREATE`, `AUDIT`, `STATUS`, `UPDATE`, `DELETE`, `DOWNLOAD_LEADS`, `SUBSCRIBE_FORM`, `UNSUBSCRIBE_FORM` |
|order_fields |string[]| Order by fields |
```

### Example

``` xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "order_type": "ORDER_TYPE", 
    "order_fields": "ORDER_FIELDS", 
    "operation_types": [
        "OPT_TYPES"
    ], 
    "start_time": "START_TIME", 
    "object_type": "OBJECT_TYPE", 
    "object_ids": [
        "OBJECT_IDS"
    ], 
    "module": "MODULE", 
    "advertiser_id": "ADVERTISER_ID", 
    "end_time": "END_TIME", 
    "timezone": "TIMEZONE", 
}' \
https://business-api.tiktok.com/open_api/v1.3/changelog/task/create/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097) |
|message |string|Return messages, see [Appendix-Return information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)|
|data |object|Return data|
#|task_id |string| Task ID |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "code": 0,
  "data": {
    "task_id": "6917870916856709125"
  },
  "message": "OK"
}
(/code);
```

