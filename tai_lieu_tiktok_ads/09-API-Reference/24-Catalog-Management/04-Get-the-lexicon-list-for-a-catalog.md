---
title: "Get the lexicon list for a catalog"
doc_id: 1740488375815169
path: "API Reference / Catalog Management / Get the lexicon list for a catalog"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the lexicon list for a catalog

Use this endpoint to get the lexicon for your catalog. A lexicon means a list of variables that you can use in ad texts when promoting your catalog.

## Comparing v1.2 and v1.3
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/catalog/lexicon/get/| /v1.3/catalog/lexicon/get/|
|Request parameter data type |`bc_id`: number<br>`catalog_id`: number|`bc_id`: string<br>`catalog_id`: string|
|Response parameter name |`meta_field`|`metadata`|
|Response parameter data type|`catalog_id`: number|`catalog_id`: string|
|Response parameter name and data type|`id`: number|`lexicon_id`: string|
```
## Request

**Endpoint** 
https://business-api.tiktok.com/open_api/v1.3/catalog/lexicon/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. |
| catalog_id {Required} | string | Catalog ID. |
```

### Example

```xcodeblock
(code curl http)

curl -X GET -H "Access-Token:YOUR_ACCESS_TOKEN" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "catalog_id=CATALOG_ID" \

https://business-api.tiktok.com/open_api/v1.3/catalog/lexicon/get/
(/code)
```

## Response

``` xtable
| Field | Type | Description |
|--- |--- |--- |
| code | number  |Return code. For the complete list of response codes and descriptions, see [Appendix - Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
| data | object | Returned data. |
# | list | object [] | Catalog Lexicon list. |
## | lexicon_id | string | Lexicon ID. |
## | catalog_id | string | Catalog ID. |
## | name | string | Lexicon name. |
## | type | string | Lexicon type.|
## | metadata | string | Metadata field. |
## | default_value | string | Default word. |
## | update_time | string | Update time. |
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
        "list": [
            {
                "default_value": "",
                "update_time": "2020-09-16 13:31:59",
                "metadata": "brand.brandName",
                "name": "brand",
                "catalog_id": "6873077759237687045",
                "type": "META",
                "lexicon_id": "11375"
            },
            {
                "default_value": "",
                "update_time": "2020-09-16 13:31:59",
                "metadata": "description",
                "name": "description",
                "catalog_id": "6873077759237687045",
                "type": "META",
                "lexicon_id": "11382"
            }
        ]
    },
    "request_id": "2020120309373701023125321495995"
}
(/code);
```
