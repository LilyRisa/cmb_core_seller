---
title: "Delete a catalog"
doc_id: 1740310064588801
path: "API Reference / Catalog Management / Delete a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a catalog

Use this endpoint to delete an existing catalog.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/delete/| /v1.3/catalog/delete/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number |`bc_id`: string<br>`catalog_id`: string |
```

## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/delete/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request content type. <br>Accepted Value: `"application/json"`.  |
```

**Parameters**

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| bc_id {Required} | string | ID of the Business Center that the catalog belongs to. |
| catalog_id {Required} | string | Catalog ID. |
```

### Example

```xcodeblock
(code curl http)
    curl -X POST -H "Access-Token:YOUR_ACCESS_TOKEN" \
    -H "Content-Type:application/json" \
    -d '{\"bc_id\": BC_ID,\"catalog_id\": CATALOG_ID}' \
    https://business-api.tiktok.com/open_api/v1.3/catalog/delete/
(/code)
```

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code. For the complete list of response codes and descriptions, see [Appendix - Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| data |object| Returned data. |
# | catalog_id | string | Catalog ID. |
| request_id | string | The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
"message": "OK",
"code": 0,
"data": {
    "catalog_id": "6869649744440854277"
},
"request_id": "2020090707561801023125321410627"
}
(/code);
```
