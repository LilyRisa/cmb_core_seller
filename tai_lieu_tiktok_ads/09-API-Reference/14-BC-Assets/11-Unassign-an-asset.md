---
title: "Unassign an asset"
doc_id: 1739448126749698
path: "API Reference / BC Assets / Unassign an asset"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unassign an asset

Use this endpoint to revoke the access to an asset from a user. You need to be an Admin user of the Business Center.

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/asset/unassign/|/v1.3/bc/asset/unassign/|
|Request parameter data type |`bc_id`: number <br> `user_id`: number<br>  `asset_id`: number|`bc_id`: string <br> `user_id`: string<br>  `asset_id`: string|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/bc/asset/unassign/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID |
|user_id {Required}|string| The ID of the Business Center user that you want to revoke the access to the asset from.<br>You can obtain the Business Center user ID via `user_id` returned by [/bc/member/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939404802049).|
|asset_type {Required}|string|Asset type.<br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`CATALOG`: catalog.</li><li>`LEAD`: Leads.</li><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts. This asset type corresponds to the identity type `BC_AUTH_TT`. Learn more from [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|asset_id {Required}|string|Asset ID.<ul><li>If `asset_type` is `ADVERTISER`, this field is the advertiser ID.</li><li>If `asset_type` is `CATALOG`, this field is the catalog ID.</li><li>If `asset_type` is `LEAD`, this field is the form library ID. To obtain more details about form libraries that you have access to, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).</li><li>If `asset_type` is `TT_ACCOUNT`, this field is the ID of a TikTok account as an ad delivery asset in a Business Center.</li><li>If `asset_type` is `MANAGED_BUSINESS_ACCOUNT`, this field is the ID of a TikTok account for Business Account management in a Business Center.</li></ul>|
```

### Example

```xcodeblock
(code cURL cURL)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/asset/unassign/' \
--header 'Content-Type: application/json' \
--header 'Access-Token;' \
--data '{
    "bc_id" : {{bc_id}},
    "user_id": {{user_id}},
    "asset_type": "ADVERTISER",
    "asset_id": {{asset_id}}
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string| Unique ID of the request|
|data|object|Returned data.<br><br>For a successful request, this field will be an empty object (`{}`).|
```
### Example

```xcodeblock
(code cURL cURL)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```

