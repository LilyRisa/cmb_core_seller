---
title: "Get members by an asset"
doc_id: 1739659558931458
path: "API Reference / BC Assets / Get members by an asset"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get members by an asset

Use this endpoint to get members that have access to an asset. You need to be an Admin user of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/bc/asset/member/get/ | /v1.3/bc/asset/member/get/ |
| Request parameter data type | `bc_id`: number <br>`asset_id`: number | `bc_id`: string <br>`asset_id`: string |
| Response parameter data type | `user_id`: number | `user_id`: string |
|New response parameters|/|`business_account_roles`|
```

## Request

**Endpoint**https://business-api.tiktok.com/open_api/v1.3/bc/asset/member/get/

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
|asset_type {Required}|string|Asset type.<br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`CATALOG`: catalog.</li><li>`LEAD`: leads.</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|asset_id {Required}|string|Asset ID.<ul><li>If `asset_type` is `ADVERTISER`, this field is the advertiser ID.</li><li>If `asset_type` is `CATALOG`, this field is the catalog ID.</li><li>If `asset_type` is `LEAD`, this field is the form library ID. To obtain more details about form libraries that you have access to, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).</li><li>If `asset_type` is `MANAGED_BUSINESS_ACCOUNT`, this field is the ID of a TikTok account for Business Account management in a Business Center.</li></ul>|
| page | number | Current page number. |
| page_size | number | Page size. |
| filtering | object | Filtering conditions |
#| keyword | string |Keyword to filter by. |

```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "asset_type=ASSET_TYPE" \
--data-urlencode "asset_id=ASSET_ID" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "filtering={\"keyword\": \"KEYWORD\"}" \
https://business-api.tiktok.com/open_api/v1.3/bc/asset/member/get/ 
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of the request, which uniquely identifies a request. |
|data |object| Returned data. |
#|list|object[]|List of members.|
##| user_id | string | User ID. |
##| user_name | string | User name. |
##| user_email | string | User email |
##| advertiser_role | string | Returned only when `asset_type` is `ADVERTISER`.<br><br>Ad account role. <br><br>Enum values: `ADMIN`, `OPERATOR`, `ANALYST`. |
##| catalog_role | string | Returned only when `asset_type` is `CATALOG`.<br><br>Business Center user's permissions to the catalog. <br><br> Enum values: <ul><li>`ADMIN`: The Business Center user has Admin permissions to the catalog. </li><li>`AD_PROMOTE`: The Business Center user can only use the catalog for promotions.  </li></ul> |
##|business_account_roles|string[]|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>Business Center user's Business Account management permission to the TikTok account.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT_ADMIN`: Business Account admin. Business Account admins have access to all Business Account features, including verification, member management, and linking to Business Center.</li><li>`BUSINESS_ACCOUNT_OPERATOR`: Business Account operator. Business Account operators can manage TikTok analytics, posts, messages, and profile information.</li><li>`BUSINESS_ACCOUNT_ANALYST`: Business Account analyst. Business Account analysts only have access to analytics and are not able to manage posts, messages, etc.</li></ul>|
#|page_info|object|Pagination information. |
##|total_number|number|Total number of results. |
##|total_page|number|Total pages. |
##|page_size|number|Page Size. |
##|page|number|Current page number. |
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


