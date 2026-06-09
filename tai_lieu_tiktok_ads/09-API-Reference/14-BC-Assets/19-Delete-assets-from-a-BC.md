---
title: "Delete assets from a BC"
doc_id: 1739657251102722
path: "API Reference / BC Assets / Delete assets from a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete assets from a BC

Use this endpoint to delete assets from a Business Center. You need to be an Admin user of the Business Center.

The type of assets that you can delete includes leads, and TikTok accounts. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/bc/asset/admin/delete/ | /v1.3/bc/asset/admin/delete/ |
| Request parameter data type |`asset_ids`: number[] |`asset_ids`: string[] |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/asset/admin/delete/

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
|bc_id {Required} | string | Business Center ID. |
| asset_ids {Required} | string[] | List of asset IDs that you want to delete. Currently, only one ID is allowed. <ul><li>`library_id` when `asset_type` is `LEAD`, and </li><li>ID of a TikTok account in a Business Center when `asset_type` is `TT_ACCOUNT`.</li></ul>|
| asset_type {Required} | string | Type of asset you want to delete. Enum values: `LEAD`, `TT_ACCOUNT`(corresponds to `BC_AUTH_TT`, and learn more from [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097)).<br><p><span style="color:darkred"><b>Note</b></span><br>When the `asset_type` is `LEAD`, you can only delete a single lead at a time. Once deleted, the lead will be moved back to the ad account that it was placed previously. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "asset_ids": [
        "ASSET_IDS"
    ],
    "asset_type": "ASSET_TYPE"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/asset/admin/delete/
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


