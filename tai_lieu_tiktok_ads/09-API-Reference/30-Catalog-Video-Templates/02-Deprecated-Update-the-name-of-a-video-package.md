---
title: "(Deprecated) Update the name of a video package"
doc_id: 1740665303669761
path: "API Reference / Catalog Video Templates / (Deprecated) Update the name of a video package"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Update the name of a video package

Use this endpoint to update the name of a video package.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{28%}|v1.2{36%}|v1.3{36%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/video_package/update/| /v1.3/catalog/video_package/update/|
|Request parameter name |`dpa_video_package_id`<br>`name`|`shopping_ads_video_package_id`<br>`video_package_name`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/update/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. Allowed format: `"application/json"`  |
```

**Parameters**

```xtable
|Field{40%}|Data Type{15%}|Description{45%}|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|shopping_ads_video_package_id {required}|string| ID of the video package that you want to update.|
|video_package_name {required}|string| Video package name. |
```

### Example
```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/update/' \
--header 'Access-Token: xxxx \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": 6725320885500118022,
    "catalog_id": 6986901069595002626,
    "shopping_ads_video_package_id": "651a86bc-580e-4f7f-85f6-35394af01a45",
    "video_package_name": "green screen rename template video 1"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|  The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "202108101227520102450631992F007842",
    "data": {}
}
(/code)
```
