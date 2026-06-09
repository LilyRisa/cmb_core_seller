---
title: "Get the partners of a BC"
doc_id: 1739662727395330
path: "API Reference / BC Partners / Get the partners of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the partners of a BC

Use this endpoint to get the list of partners of a Business Center. You need to be an Admin of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path |/v1.2/bc/partner/get/ | /v1.3/bc/partner/get/ |
| Request parameter name | `keyword` | `name` |
| Request parameter data type | `bc_id`: number | `bc_id`: string |
| Response parameter data type | `bc_id`: number | `bc_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/partner/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
| page  | number | Current page number. |
| page_size| number | Page size. |
| filtering | object | Filtering by names. |
#| name | string | Partner name keyword to filter the results. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "filtering={\"name\": \"NAME\"}" \
https://business-api.tiktok.com/open_api/v1.3/bc/partner/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|list|object[]|Partner list.|
##|bc_name|string|Partner name|
##|bc_id|string|Partner ID|
#|page_info|object|Pagination information |
##|page|number|Current page number. |
##|page_size|number|Page size |
##|total_number|number|Total number of results |
##|total_page|number|Total pages of results |
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

