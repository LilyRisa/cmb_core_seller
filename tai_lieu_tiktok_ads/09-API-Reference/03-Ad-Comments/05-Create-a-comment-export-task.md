---
title: "Create a comment export task"
doc_id: 1738088144215041
path: "API Reference / Ad Comments / Create a comment export task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a comment export task

Use this endpoint to create a comment export task. 

After the task is created, you can use [/comment/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1738088242659330) to check the status of the task. When the task completes, you can call [/comment/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1738088341958658) to download the exported data. 

Exported data has the following headers: Content, Likes, Replies, Comment Type, Create Time, Campaign Name, Ad Group Name, Ad Name. If a comment contains mentions of user names, the mentions will be processed as  "@user_name" in the exported table.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/task/create/|/v1.3/comment/task/create/|
|Request parameter data type|`advertiser_id`: number<br>`comment_ids`: number[]|`advertiser_id`: string<br>`comment_ids`: string[]|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/comment/task/create/

**Method** POST

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
|Content-Type {required}|string| Content type of the request. Allowed value: `application/json`|
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|advertiser_id {required}|string| Advertiser ID|
|comment_status |string[]| Comment status. Enum values: `ALL`, `PUBLIC`, `HIDDEN`. Default: `["ALL"]`|
|comment_type |string[]|  Comment type. Enum values: `ALL`, `COMMENT`, `REPLY`. Default: `["ALL"]`|
|search_field |string| Field to search by. Enum values: `ADGROUP_ID` |
|search_value |string| Value to search |
|comment_ids|string[]|IDs of the comments that you want to export|
|sort_field |string| Field to sort by. Enum values: `CREATE_TIME`, `LIKES`, `REPLIES` |
|sort_type |string| Sorting order. Enum values: `ASC`,`DESC` |
|start_time |string| Start date of the time range that you want to get results, in the format of `YYYY-MM-DD`. |
|end_time |string| End date of the time range that you want to get results, in the format of `YYYY-MM-DD`. |
|lang|string| Language of the headers. Enum values: `EN`, `JA`, `ZH`. Default: `EN`.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/comment/task/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": "{{advertiser_id}}",
  "search_field": "ADGROUP_ID",
  "search_value": "{{search_value}}"
}'
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
|request_id |string| Unique ID of the request |
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
