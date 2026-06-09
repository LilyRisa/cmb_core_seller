---
title: "Add a partner to a BC"
doc_id: 1739662756510721
path: "API Reference / BC Partners / Add a partner to a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Add a partner to a BC

Use this endpoint to add a partner and optionally share your assets to a Business Center. A Business Center can only share assets it owns. You need to be an Admin of the Business Center.


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/bc/partner/add/ | /v1.3/bc/partner/add/ |
| Request parameter data type | `bc_id`: number <br>`partner_id`: number <br>`asset_ids`: number[] | `bc_id`: string <br>`partner_id`: string<br> `asset_ids`: string[] |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/partner/add/

**Method** POST

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
|bc_id {Required} | string | ID of your Business Center.|
| partner_id {Required} | string | ID of the Business Center to add as a partner Business Center. |
| asset_type  | string | The type of asset to share with the partner Business Center.<br> <br>Enum value: `ADVERTISER` (ad account).|
| asset_ids | string[] | The list of asset IDs to share with the partner Business Center.<br> <br>Max size: 50. |
| advertiser_role | string | Valid only when `asset_type` is `ADVERTISER`. <br> <br>  Ad account role assigned to the partner Business Center.<br> <br>Enum values:<ul><li>`ADMIN`: Admin. Manage ad account finances, settings, and permissions. Create and edit ad campaigns and view performance data.</li><li>`OPERATOR`: Operator. Manage finances for ad accounts, create and edit ad campaigns, and view performance data.</li><li>`ANALYST`: Analyst. View ads and performance data.</li></ul>Default value when `asset_type` is  `ADVERTISER`: `ANALYST`.|
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "partner_id": "PARTNER_ID",
    "asset_type": "ASSET_TYPE",
    "asset_ids": [
        "ASSET_IDS"
    ],
    "advertiser_role": "ADVERTISER_ROLE",
    "catalog_role": "CATALOG_ROLE"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/partner/add/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
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

