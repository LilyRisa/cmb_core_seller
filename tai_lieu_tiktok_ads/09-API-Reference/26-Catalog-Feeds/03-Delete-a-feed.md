---
title: "Delete a feed"
doc_id: 1740665210863617
path: "API Reference / Catalog Feeds / Delete a feed"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a feed

Use this endpoint to delete a feed.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/feed/delete/| /v1.3/catalog/feed/delete/|
|Request parameter data type |`bc_id`: number <br>`catalog_id`: number <br>`feed_id`: number |`bc_id`: string  <br>`catalog_id`: string <br> `feed_id`: string|
```

## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/catalog/feed/delete/

**Method** POST


**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|feed_id {required}| string| Feed ID.|
```

### Example

```xcodeblock
(code cURL cURL)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/feed/delete/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "feed_id": "{{feed_id}}"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| The log id of a request, which uniquely identifies the request.|
|data|object| Returned data.|
#|feed_id|number| Feed ID.|
```

### Example
```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "feed_id": {{feed_id}}
    }
}
(/code)
```
