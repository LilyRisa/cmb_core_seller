---
title: "Delete Asset Groups"
doc_id: 1749001673406466
path: "API Reference / BC Asset Groups / Delete Asset Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete Asset Groups

Use this endpoint to delete Asset Groups from your Business Center. You need to be an Admin user of the Business Center.

Please note that the deletion will remove members' access to assets in an Asset Group, and these assets and members will not be deleted. 

# Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/asset_group/delete/

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
| asset_group_ids {Required} | string[] |ID of the Asset Group that you want to delete. Currently, only one ID is allowed.  |
```

# Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log ID of the request, which uniquely identifies a request. |
```
