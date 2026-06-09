---
title: "(Deprecated) Preview video templates"
doc_id: 1740665368159233
path: "API Reference / Catalog Video Templates / (Deprecated) Preview video templates"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Preview video templates

Use this endpoint to preview video templates.

An iframe code snippet with a link will be returned in the response. You can either click the link or embed the iframe code snippet in an HTML file. 

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/template_preview/create/| /v1.3/catalog/template_preview/create/|
|Request parameter data type |`bc_id`: number<br>`catalog_ids`: number[]|`bc_id`: string<br>`catalog_ids`: string[]|
```
## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/template_preview/create/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type. Allowed format: `"application/json"`  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_ids {required}|string[]| Catalog IDs. |
```
### Example
```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/template_preview/create/' \
--header 'Access-Token: xxxx \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "6725320885500118022",
    "catalog_ids": ["6986901069595002626"]
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data|object|Returned data.|
#|iframe|string| An iframe code snippet that includes the preview link. Click the link to preview the video or embed the iframe code to your HTML file.|
|request_id|string| The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "20210810121217010245016027560AC221",
    "data": {
        "iframe": "<iframe src=\"https://ads.tiktok.com/ad_preview_tool?ad_preview_id=34db6ab0f9d411eb89bb6b94305dff44\"></iframe>"
    }
}
(/code)
```

