---
title: "Get all Asset Groups"
doc_id: 1749001665910786
path: "API Reference / BC Asset Groups / Get all Asset Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get all Asset Groups

Use this endpoint to get all Asset Groups in your Business Center. You need to be an Admin user of the Business Center.

# Request
**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/list/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`. |
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
|filtering | object | Filtering conditions. |
#|keyword | string | Keyword to filter by. Currently you can only use exact matching by specifying `asset_group_name` or `asset_group_id` here. |
|page | number | Page number. Default value: 1. |
|page_size | number | Page size. Default value: 10.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/catalog/overview/?bc_id={{bc_id}}&catalog_id={{catalog_id}}
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json'
(/code)
```

# Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|request_id |string|The log ID of a request, which uniquely identifies the request. |
|data |object|Returned Data. |
#| asset_groups | object[] | Asset Group information.|
##| asset_group_id | string | Asset Group ID.|
##| asset_group_name  | string | Asset Group name. |
#|page_info|object| Pagination information.|
##|page|number| Current page number. Default value:1.|
##|page_size| number | Page size. Default value: 10.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
```

## Example
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "2022110908243201025107911603044DD2",
    "data": {
        "page_info": {
            "total_page": 1,
            "page_size": 10,
            "total_number": 1,
            "page": 1
        },
        "asset_groups": [
            {
                "asset_group_id": "7161375800388747266",
                "asset_group_name": "测试asset group5"
            }
        ]
    }
}
(/code)
```
