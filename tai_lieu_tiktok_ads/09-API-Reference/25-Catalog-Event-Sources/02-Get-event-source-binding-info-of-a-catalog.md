---
title: "Get event source binding info of a catalog"
doc_id: 1740492531343362
path: "API Reference / Catalog Event Sources / Get event source binding info of a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get event source binding info of a catalog

Use this endpoint to get the binding information about app or web event sources of a catalog in a Business Center.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/catalog/eventsource_bind/get/|/v1.3/catalog/eventsource_bind/get/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Response parameter data type|`app_id`: number|`app_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/eventsource_bind/get/

**Method** GET

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
|bc_id{Required}|string|Business Center ID.|
|catalog_id{Required}|string|Catalog ID.|
```

### Example

```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/catalog/eventsource_bind/get/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": {{bc_id}},
    "catalog_id": {{catalog_id}}
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data.|
#|catalog_id|integer|Catalog ID.|
#|event_sources|object[]|Event sources. Each event source is an object.|
##|event_source_name|string|Event source name.|
##|app_id|string|Mobile application ID.|
##|pixel_code|string| Pixel code.|
|request_id |string|The log id of a request, which uniquely identifies the request.  |
```

### Example
```xcodeblock
(code JSON JSON)
{
  "code": 0,
  "message": "OK",
  "request_id": "{{request_id}}",
  "data": {
    "catalog_id": "{{catalog_id}}",
    "event_sources": [
      {
        "app_id": "{{app_id}}",
        "event_source_name": "{{event_source_name}}",
        "pixel_code": null
      }
    ]
  }
}
(/code)
```
