---
title: "Delete product sets"
doc_id: 1740573143966722
path: "API Reference / Catalog Product Sets / Delete product sets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete product sets

Use this endpoint to delete product sets in a catalog under a Business Center.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/set/delete/| /v1.3/catalog/set/delete/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Request parameter name and data type |`set_ids`: number[]|`product_set_ids`: string[]|
|Response parameter name and data type|`ids`: number[]|`product_set_ids`: string[]|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/catalog/set/delete/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `"application/json"` . |
```

**Parameters**

```xtable
|Field|Type|Description|
|--- |--- |--- |
|bc_id {Required}|string|Business Center ID.|
|catalog_id {Required}|string|The product catalog ID.|
|product_set_ids {Required}|string[]| The IDs of the product sets that you want to delete. You can include 1 to 10 IDs in the list. |
```

### Example

```xcodeblock
(code curl http)
curl -X POST -H "Access-Token:YOUR_ACCESS_TOKEN" \
-H "Content-Type:application/json" \
-d '{\"bc_id\": BC_ID,\"catalog_id\": CATALOG_ID,\"product_set_ids\": SET_IDS}' \
https://business-api.tiktok.com/open_api/v1.3/catalog/set/delete/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|The return code. For the complete list of error codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|The return message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The request ID.|
|data |object| The returned data.|
#|product_set_ids|string[]| The IDs of the product sets that have been deleted.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "product_set_ids": [
            "1865",
            "1863",
            "1791"
        ]
    },
    "request_id": "2021031410042301023101010646846F"
}
(/code);
```
