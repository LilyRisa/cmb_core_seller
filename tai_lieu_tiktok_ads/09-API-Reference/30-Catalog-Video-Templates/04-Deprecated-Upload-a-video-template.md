---
title: "(Deprecated) Upload a video template"
doc_id: 1740665346584577
path: "API Reference / Catalog Video Templates / (Deprecated) Upload a video template"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Upload a video template

Use this endpoint to upload a custom video template to Busines Center and associate the template with a group of catalogs. 

To learn about how to design a custom video template, refer to [Custom Template Design Guidelines](https://bytedance.feishu.cn/docs/doccnGxGlxEVZVhvGWyq17QTKNe). In addition, in a custom video template, the image layers and text layers need to be named in formats that are supported by TikTok. For details, see [Layer Naming Guidelines](https://ads.tiktok.com/marketing_api/docs?id=1739470990574594).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/template/upload/| /v1.3/catalog/template/upload/|
|Request parameter data type |`bc_id`: number<br>`catalog_ids`: number[]|`bc_id`: string<br>`catalog_ids`: string[]|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/catalog/template/upload/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `multipart/form-data`. |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID. |
|catalog_ids {Required}|string[]|List of catalog IDs that you want to associate this catalog with. |
|file {Required}| file| Template file in ZIP.|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/template/upload/' \
--header 'access-token: abcd' \
--form 'bc_id="abcd"' \
--form 'catalog_ids="[1234567890]"' \
--form 'file=@"/Users/admin/Downloads/Example.zip"'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|template_id|string|Template ID.|
|catalog_upload_success|number[]|List of catalog IDs that have been successfully associated with this template.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```
