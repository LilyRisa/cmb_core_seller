---
title: "(Deprecated) Delete a video package"
doc_id: 1740665332455426
path: "API Reference / Catalog Video Templates / (Deprecated) Delete a video package"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Delete a video package

Use this endpoint to delete a video package.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{28%}|v1.2{36%}|v1.3{36%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/video_package/delete/ | /v1.3/catalog/video_package/delete/ |
|Request parameter name |`dpa_video_package_id`|`shopping_ads_video_package_id`|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/video_package/delete/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field{40%}|Data Type{15%}|Description{45%}|
|---|---|---|
|bc_id {required}|string| Business Center ID. |
|catalog_id {required}|string| Catalog ID.|
|shopping_ads_video_package_id {required}|string| ID of the video package that you want to update.|
```

### Example

```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.2/catalog/video_package/delete/' \
--header 'Access-Token: xxxx \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": 6725320885500118022,
    "catalog_id": 6986901069595002626,
    "shopping_ads_video_package_id": "491e6b86-c5b4-441c-a390-365c47d29cfd"
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
```

### Example

```xcodeblock
(code JSON JSON)
{
    "code": 0,
    "message": "OK",
    "request_id": "202108101230590102450160272100ABE0",
    "data": {}
}
(/code)
```

