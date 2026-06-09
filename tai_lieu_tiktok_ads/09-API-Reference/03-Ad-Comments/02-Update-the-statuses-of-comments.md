---
title: "Update the statuses of comments"
doc_id: 1738086844585985
path: "API Reference / Ad Comments / Update the statuses of comments"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the statuses of comments

Use this endpoint to change the statuses of a list of comments from public to hidden, or vice versa. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/comment/status/update/|/v1.3/comment/status/update/|
|Request parameter data type|`advertiser_id`: number<br>`comment_ids`: number[]|`advertiser_id`: string<br>`comment_ids`: string[]|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/comment/status/update/

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
|comment_ids {required}|string[]| Comment ID |
|operation {required}|string| Operation type. Enum values: `HIDDEN` (change comments to hidden), `PUBLIC` (make comments public). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST '{{base_url}}/v1.3/comment/status/update/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "advertiser_id": {{advertiser_id}},
  "comment_ids": {{comment_ids}},
  "operation": "PUBLIC"
}'
(/code)
```

## Response

```xtable
|Field|Type|Description|
|-|-|-|
|message|string|The return message. For Details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|code |number|The return code. For details, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
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
