---
title: "Delete uploaded catalog videos"
doc_id: 1803655103069185
path: "API Reference / Catalog Videos / Delete uploaded catalog videos"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete uploaded catalog videos

Use this endpoint to delete uploaded catalog videos from an E-commerce catalog.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/video/delete/ 

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID.<br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | Catalog ID. <br><br>To obtain the list of E-commerce catalogs within a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610) and retrieve `catalog_id` values with `catalog_type` as `ECOM`. <br><br>You need to have Catalog Management permission for the catalog. To obtain the catalogs for which you have Catalog Management permission, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `CATALOG` to retrieve `asset_id` values with `catalog_role` as `ADMIN`. |
| catalog_video_ids {Required} | string[] | The ID list of catalog videos that you want to delete. <br><br> Max size: 1. <br><br> To obtain the catalog videos within a catalog, use [/catalog/video/get/](https://business-api.tiktok.com/portal/docs?id=1803655082498050).<br><br><p><span style="color:darkred"><b>Note</b></span>: If catalog videos have already been synced to the creative libraries of ad accounts, deleting those videos from the associated catalog will not remove them from the creative libraries. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/video/delete/ ' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "catalog_id": "{{catalog_id}}",
    "catalog_video_ids": ["{{catalog_video_id}}"]
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
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
