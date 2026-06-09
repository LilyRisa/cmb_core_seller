---
title: "Get the details of an Asset Group"
doc_id: 1749001669670913
path: "API Reference / BC Asset Groups / Get the details of an Asset Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the details of an Asset Group

Use this endpoint to get the assets or members of an Asset Group in a Business Center. You need to be an Admin user of the Business Center.

# Request
**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/get/

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
|asset_group_id  {Required} | string | Asset Group ID. |
|query_entity {Required} | string | The entity that you want to query.<br>Enum values: `ASSET`, `MEMBER`.|
|filtering | object | Filtering conditions. |
#|keyword | string | Keyword to filter by. <li>If the `query_entity` is `ASSET`, you can either use fuzzy search by specifying `asset_name` related keywords or use exact search  by specifying  `asset_id` to filter the results.<li>If the `query_entity`  is `MEMBER`, you can either use fuzzy search by specifying `user_name` related keywords or use exact search  by specifying `user_email` to filter the results.|
|page | number | Page number. Default value: 1. |
|page_size | number | Page size. Default value: 10.|
```

# Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Response message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|request_id |string|The log ID of a request, which uniquely identifies the request. |
|data |object|Returned Data. |
#| asset_group | object[] | Asset Group information.|
##| asset_group_name  | string | Asset Group name. |
##| asset_group_id | string | Asset Group ID.|
##| assets | object[] | Asset information. Returned when `query_entity` is `ASSET`.|
###| asset_type| string | Asset type. |
###| asset_id |string | Asset ID.|
##| members | object[] | Member information. Returned when `query_entity` is `MEMBER`.|
###| asset_roles| map < string, string > | The role that you have assigned to the member according to the asset type. |
###| member_id |string | Member ID.|
#|page_info|object| Pagination information.|
##|page|number| Current page number. Default value:1.|
##|page_size| number | Page size. Default value: 10.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
```

## Example
```xcodeblock
(code)
{
    "code": 0,
    "message": "OK",
    "request_id": "20221109082332010251079116030440F4",
    "data": {
        "page_info": {
            "total_page": 1,
            "total_number": 1,
            "page": 1,
            "page_size": 10
        },
        "asset_group": {
            "asset_group_name": "Asset group API test_",
            "asset_group_id": "7161797128677752833",
            "assets": [
                {
                    "asset_type": "ADVERTISER",
                    "asset_id": "7069762178752004097"
                }
            ]
        }
    }
}

{
    "code": 0,
    "message": "OK",
    "request_id": "2022110908240201025107911603044764",
    "data": {
        "asset_group": {
            "members": [
                {
                    "asset_roles": {
                        "ADVERTISER": "ADVERTISER_ROLE_ADMIN"
                    },
                    "member_id": "7020983073270547458"
                }
            ],
            "asset_group_name": "Asset group API test_",
            "asset_group_id": "7161797128677752833"
        },
        "page_info": {
            "total_page": 1,
            "page_size": 10,
            "total_number": 1,
            "page": 1
        }
    }
}
(/code)
```
