---
title: "Assign an asset"
doc_id: 1739438211077121
path: "API Reference / BC Assets / Assign an asset"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Assign an asset

Use this endpoint to assign an asset in the Business Center to a TikTok For Business user that is already in the Business Center. You need to be an Admin user of the Business Center.

## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/asset/assign/|/v1.3/bc/asset/assign/|
|Request parameter data type |`bc_id`: number <br> `user_id`: number<br> `asset_id`: number|`bc_id`: string<br> `user_id`: string<br> `asset_id`: string|
|New request parameter |/|`store_role`<br>`business_account_roles`|
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/bc/asset/assign/>

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
|bc_id {Required}|string| Business Center ID. |
|user_id {Required}|string| The ID of the Business Center user that you want to assign the asset to. <br>You can obtain the Business Center user ID via `user_id` returned by [/bc/member/get/](https://ads.tiktok.com/marketing_api/docs?id=1739939404802049).|
|asset_type {Required}|string|Asset type.<br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`CATALOG`: catalog.</li><li>`TIKTOK_SHOP`: TikTok Shop.</li><li>`LEAD`: Leads.</li><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts. This asset type corresponds to the identity type `BC_AUTH_TT`. Learn more from [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|asset_id {Required}|string|Asset ID.<ul><li>If `asset_type` is `ADVERTISER`, this field is the advertiser ID.</li><li>If `asset_type` is `CATALOG`, this field is the catalog ID.</li><li>If `asset_type` is `TIKTOK_SHOP`, this field is the TikTok Shop ID.</li><li>If `asset_type` is `LEAD`, this field is the form library ID. To obtain more details about form libraries that you have access to, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).</li><li>If `asset_type` is `TT_ACCOUNT`, this field is the ID of a TikTok account as an ad delivery asset in a Business Center.</li><li>If `asset_type` is `MANAGED_BUSINESS_ACCOUNT`, this field is the ID of a TikTok account for Business Account management in a Business Center.</li></ul>|
|advertiser_role{+Conditional} |string|Required when `asset_type` is `ADVERTISER`.  <br> <br>Advertiser role. This is equivalent to Layer 2 of Ad Account Permissions. Enum values: `ADMIN`, `OPERATOR`, `ANALYST`. For details about these permissions, see [Manage Permissions on TikTok Business Center](https://ads.tiktok.com/help/article?aid=12791).|
|catalog_role{+Conditional} |string| Required when `asset_type` is `CATALOG`.  <br> <br>Business Center user's permissions to the catalog.  <br> <br>Enum values:<ul><li> `ADMIN`: The Business Center user has Admin permissions to the catalog. </li><li>`AD_PROMOTE`: The Business Center user can only use the catalog for promotions. </li>|
|form_library_role{+Conditional} |string| Required when `asset_type` is `LEAD`. <br> <br>Form library role assigned to the user. Currently, only `ADMIN` is supported.|
|tt_account_roles {+Conditional}|string[]|Required when `asset_type` is `TT_ACCOUNT`.<br><br>Business Center user's ad delivery permission to the TikTok account.<br><br>Enum values:<ul><li>`POST`: Existing posts permissions, including accessing your own profile info, accessing your own public videos, and accessing your own ad-authorized videos.</li><li>`LIVE`: LIVE video permissions (Only available in some regions), including accessing your own profile info and accessing LIVE videos.</li><li>`DIRECT_MESSAGE`: Direct message permissions, including allowing everyone to send you a direct message and reading and managing direct messages in your inbox, in addition to messaging or interacting with other accounts on your behalf.</li></ul>|
|business_account_roles {+Conditional}|string[]|Required only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>Business Center user's Business Account management permission to the TikTok account.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT_ADMIN`: Business Account admin. Business Account admins have access to all Business Account features, including verification, member management, and linking to Business Center.</li><li>`BUSINESS_ACCOUNT_OPERATOR`: Business Account operator. Business Account operators can manage TikTok analytics, posts, messages, and profile information.</li><li>`BUSINESS_ACCOUNT_ANALYST`: Business Account analyst. Business Account analysts only have access to analytics and are not able to manage posts, messages, etc.</li></ul><br>Max size: 1.|
|store_role{+Conditional} |string|Required when `asset_type` is `TIKTOK_SHOP`.<br><br>Business Center user's permission to a TikTok Shop. <br><br>Enum values:<ul><li>`AD_PROMOTION`: The Business Center user can only use the TikTok Shop for promotions.</li></ul>|
```

### Example
```xcodeblock
(code cURL cURL)
curl --location --request POST 'business-api.tiktok.com/open_api/v1.3/bc/asset/assign/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "user_id": "{{user_id}}",
    "asset_type": "ADVERTISER",
    "asset_id": "{{asset_id}}",
    "advertiser_role": "ADMIN"
}'
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). <br><br><p><span style="color:darkred"><b>Note</b></span>: If the response code is `40002`, and the response message is "Authorization roles exceed the role range that the BC can manage.", then you need to check whether the value you pass in for `advertiser_role`, `catalog_role`,  `form_library_role`, or `tt_account_roles` in the request is allowed.|
|request_id|string| Unique ID of the request.|
|data| object | Returned data.|
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

