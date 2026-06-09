---
title: "Get partners by an asset"
doc_id: 1739663268166722
path: "API Reference / BC Assets / Get partners by an asset"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get partners by an asset

Use this endpoint to get the partners that you have shared a particular asset with. You need to be an Admin user of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/bc/asset/partner/get/ | /v1.3/bc/asset/partner/get/ |
| Request parameter data type | `bc_id`: number <br>`asset_id`: number | `bc_id`: string <br>`asset_id`: string |
| Response parameter data type | `bc_id`: number | `bc_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset/partner/get/

**Method** GET

**Header**

``` xtable
|Field{30%}|Data Type{35%}|Description{35%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
| asset_type {Required} | string | Asset type. Enum values: `ADVERTISER`, `CATALOG`. |
| asset_id {Required} | string | Asset ID. Ad account ID when `asset_type` is `ADVERTISER`. Catalog ID when `asset_type` is` CATALOG`. |
| filtering | object | Filtering object |
#| keyword {Required} | string | Partner name keyword that you want to filter by. |
| page | number | Current page number. Default value: 1. |
| page_size | number | Page size. Default value: 10. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "asset_type=ASSET_TYPE" \
--data-urlencode "asset_id=ASSET_ID" \
--data-urlencode "filtering={\"keyword\": \"KEYWORD\"}" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/bc/asset/partner/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|list|object[]|List of partners.|
##|bc_name|string|Partner name. |
##|bc_id|string|Partner ID.|
#|page_info|object|Pagination information. |
##|page|number|Current page number. |
##|page_size|number|Page Size. |
##|total_number|number|Total number of results. |
##|total_page|number|Total pages of results.|
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```


