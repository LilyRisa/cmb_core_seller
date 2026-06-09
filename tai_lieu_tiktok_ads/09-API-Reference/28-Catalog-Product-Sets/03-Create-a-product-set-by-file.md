---
title: "Create a product set by file"
doc_id: 1846770644217858
path: "API Reference / Catalog Product Sets / Create a product set by file"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a product set by file

Use this endpoint to create an E-commerce product set by uploading a CSV file containing SKU IDs in an E-commerce catalog under your Business Center.

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/set/upload/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed value: `multipart/form-data`.|
```


**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|bc_id {Required}|string|Business Center ID.<br><br>To retrieve the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
|catalog_id {Required}|string|Catalog ID.<br><br>To retrieve the list of E-commerce catalogs within a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). For E-commerce catalogs, `catalog_type` will be `ECOM`.|
|product_set_name {Required}|string|The product set name. <br><br>Length limit: 28 characters.<br><br><p><span style="color:darkred"><b>Note</b></span>: Duplicate product set names are not supported. To retrieve the names of existing product sets created in a catalog, use [/catalog/set/get/](https://business-api.tiktok.com/portal/docs?id=1740570556295169) and check the returned `product_set_name`.|
|file {Required}|file|A file containing product SKU IDs.<br><br>To obtain the list of SKU IDs for products within an E-commerce catalog, use [/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402) and check the returned `sku_id`. <br><br>Recommended settings：<ul><li>Maximum row count: 5,000.</li><li>Format：.csv only.</li></ul>Use [the CSV template file](https://bytedance.sg.larkoffice.com/sheets/GlkOsKz7RhRhfLtN0gblmYcjgjd) for uploading.|
|file_signature {Required}|string|The MD5 hash of the file that is used for server verification to confirm file integrity.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/catalog/set/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'bc_id="{{bc_id}}"' \
--form 'catalog_id="{{catalog_id}}"' \
--form 'product_set_name="{{product_set_name}}"' \
--form 'file=@"/{{file_path}}"' \
--form 'file_signature="{{file_signature}}"'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.|
|data |object|Returned data.|
#|product_set_id |string|The product set ID.|
#|product_set_name |string|The product set name.|
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "product_set_id": "{{product_set_id}}",
        "product_set_name": "{{product_set_name}}"
    }
}
(/code)
```

