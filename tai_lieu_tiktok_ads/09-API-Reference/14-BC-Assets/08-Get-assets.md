---
title: "Get assets"
doc_id: 1739432717798401
path: "API Reference / BC Assets / Get assets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get assets

Use this endpoint to get assets that you or a particular user has access to. 


## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/asset/get/|/v1.3/bc/asset/get/|
|Request parameter data type |`bc_id`: number|`bc_id`: string|
|New request parameters|/|`keyword`: string|
|Response parameter data type |`asset_id`: number|`asset_id`: string|
|New response parameter|/|  `advertiser_account_type`<br>`store_role`<br>`ad_creation_eligible`<br>`business_account_roles`<br>`is_business_verified`<br>`operating_region_code`<br>`tt_asset_handle`<br>`tt_asset_profile_image`|
 ```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/asset/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID. |
|asset_type {Required}|string|Type of asset that you want to get.<br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`CATALOG`: catalog.</li><li>`TIKTOK_SHOP`: TikTok Shop.</li><li>`PIXEL`: Pixel.</li><li>`LEAD`: Leads.</li><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts. This asset type corresponds to the identity type `BC_AUTH_TT`. Learn more from [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|filtering|object|Filtering conditions.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>You cannot use `user_id` and `user_email` at the same time.</li><li>If you don't specify any filter values, the system will pass in to the `user_id` field the user ID of the TikTok for Business account that gives you the authorization code (`auth_code`).</li></ul>|
#|user_id|string|ID of the user that you want to get assets for.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>`user_id` and `user_mail` cannot be set at the same time.</li><li>For users that are in `BOUND` status, use `user_id` to get their assets.</li><li>For users that are in `PENDING` status, use `user_email` to get their assets.</li><li>This filter is not supported when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.</li></ul>|
#|user_email|string|Email of the user that you want to get assets for.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>`user_id` and `user_mail` cannot be set at the same time.</li><li>For users that are in `BOUND` status, use `user_id` to get their assets.</li><li>For users that are in `PENDING` status, use `user_email` to get their assets.</li><li>Use `user_email` as filter only when you've set `asset_type` as `ADVERTISER` and the invitation for the user to join Business Center is still pending.</li><li>This filter is not supported when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.</li></ul>|
#|keyword|string|Keyword of the asset you are looking for.<br><br><p><span style="color:darkred"><b>Note</b></span>: This filter is not supported when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.|
|page|number| Current page number. <br><br>Default value: 1.|
|page_size|number|Page size.<br><br> Value range: 1-50. <br><br>Default value: 10.|
```
### Example

```xcodeblock
(code CURL curl)
curl --location --request GET'https://business-api.tiktok.com/open_api/v1.3/bc/asset/get/?bc_id={{bc_id}}&asset_type=ADVERTISER' \
--header 'Access-Token: {{Access-Token}}
(/code)
```
## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list|object[]|List of assets|
##|asset_type|string|Asset type.<br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`CATALOG`: catalog.</li><li>`TIKTOK_SHOP`: TikTok Shop.</li><li>`PIXEL`: Pixel.</li><li>`LEAD`: Leads.</li><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts. This asset type corresponds to the identity type `BC_AUTH_TT`. Learn more from [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097).</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
##|asset_id|string|Asset ID.<ul><li>If `asset_type` is `ADVERTISER`, this field is the advertiser ID.</li><li>If `asset_type` is `CATALOG`, this field is the catalog ID.</li><li>If `asset_type` is `TIKTOK_SHOP`, this field is the TikTok Shop ID.</li><li>If `asset_type` is `PIXEL`, this field is the Pixel ID.</li><li>If `asset_type` is `LEAD`, this field is the form library ID. To obtain more details about form libraries that you have access to, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).</li><li>If `asset_type` is `TT_ACCOUNT`, this field is the ID of a TikTok account as an ad delivery asset in a Business Center.</li><li>If `asset_type` is `MANAGED_BUSINESS_ACCOUNT`, this field is the ID of a TikTok account for Business Account management in a Business Center.</li></ul>|
##|asset_name|string|Asset name.|
##|advertiser_account_type |string|Type of the ad account. <br><br> Enum values: `RESERVATION` (reservation ad account), `AUCTION`(auction ad account).|
##|advertiser_role |string|Business Center user's permissions to the ad account. <br> <br>Enum values:<ul><li>`ADMIN`: Admin. Manage ad account finances, settings, and permissions. Create and edit ad campaigns and view performance data.</li><li>`OPERATOR`: Operator. Manage finances for ad accounts, create and edit ad campaigns, and view performance data.</li><li>`ANALYST`: Analyst. View ads and performance data.</li></ul><br> This field returns a non-null value when `asset_type` is `ADVERTISER`.|
##|catalog_role|string|Business Center user's permissions to the catalog. <br> <br> Enum values:<ul><li> `ADMIN`: Catalog management. Promote products both organically and in ads, and edit products and catalogs. </li><li>`AD_PROMOTE`: Ad promotion. Create ads using product catalogs.</li></ul><br> This field returns a non-null value when `asset_type` is `CATALOG`. |
##|ad_creation_eligible |string|Returned only when `asset_type` is `CATALOG`. <br><br>Whether the catalog can be used in ads when it contains the number of available products required for the ad format.<br><br>Enum values:<ul><li>`NOT_AVAILABLE`: The catalog cannot be used in ads.</li><li>`AVAILABLE`: The catalog can be used in ads when it contains the number of available products required for the ad format.</li></ul>|
##|store_role|string| Business Center user's permission to the TikTok Shop. <br><br>Enum values:<ul><li>`AD_PROMOTION`: Ad promotion. Create ads promoting products from this TikTok Shop.</li></ul><br> This field returns a non-null value when `asset_type` is `TIKTOK_SHOP`. |
##|tt_account_roles|string[]|Business Center user's ad delivery permission to the TikTok account.<br><br>Enum values:<ul><li>`POST`: Existing posts permissions, including accessing your own profile info, accessing your own public videos, and accessing your own ad-authorized videos.</li><li>`LIVE`: LIVE video permissions (Only available in some regions), including accessing your own profile info and accessing LIVE videos.</li><li>`DIRECT_MESSAGE`: Direct message permissions, including allowing everyone to send you a direct message and reading and managing direct messages in your inbox, in addition to messaging or interacting with other accounts on your behalf.</li></ul>This field returns a non-null value when `asset_type` is `TT_ACCOUNT`.|
##|business_account_roles|string[]|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>Business Center user's Business Account management permission to the TikTok account.<br><br>Enum values:<ul><li>`BUSINESS_ACCOUNT_ADMIN`: Business Account admin. Business Account admins have access to all Business Account features, including verification, member management, and linking to Business Center.</li><li>`BUSINESS_ACCOUNT_OPERATOR`: Business Account operator. Business Account operators can manage TikTok analytics, posts, messages, and profile information.</li><li>`BUSINESS_ACCOUNT_ANALYST` : Business Account analyst. Business Account analysts only have access to analytics and are not able to manage posts, messages, etc.</li></ul>|
##|owner_bc_name|string|The owner Business Center of this asset.<br><br>When `asset_type` is `TT_ACCOUNT` or `MANAGED_BUSINESS_ACCOUNT`, the value of this field will be null.|
##|is_business_verified|boolean|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>Whether the TikTok account is a Verified Business Account.<br>A verified badge shows your account is authentic, based on your activity and the information you provided. With a verified account, you’ll also get extra protection, direct support, and more visibility.<br><br>Supported values: `true`, `false`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Organization Accounts created in Business Center will have access to functions exclusive to Verified Business Accounts such as Business Suite features. For these accounts, business verification will be approved without requiring any business verification documentation. For these accounts, `is_business_verified` will be `true`.|
##|operating_region_code|string|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>The region code of the operating region for the TikTok account.<br><br>Example: `US`.<br><br>For enum values, see [List of values for `operating_region_code`](https://business-api.tiktok.com/portal/docs?id=1855027199571138#item-link-List%20of%20values%20for%20operating_region_code).|
##|tt_asset_handle|string|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>The handle of the TikTok account.|
##|tt_asset_profile_image|string|Returned only when `asset_type` is `MANAGED_BUSINESS_ACCOUNT`.<br><br>The profile image of the TikTok account.<br><br>If no profile image has been specified for the TikTok account, this field will be empty.|
#|page_info|object| Pagination information.|
##|page|number| Current page number.|
##|page_size| number | Page size.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```
### Example
```xcodeblock
(code JSON json)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "total_number": 2,
            "page": 1,
            "page_size": 10,
            "total_page": 1
        },
        "list": [
            {
                "asset_name": "{{asset_name}}",
                "store_role": null,
                "catalog_role": null,
                "advertiser_role": "ADMIN",
                "tt_account_roles": null,
                "owner_bc_name": "{{owner_bc_name}}",
                "advertiser_account_type": "AUCTION",
                "asset_id": "{{asset_id}}",
                "asset_type": "ADVERTISER"
            },
            {
                "asset_name": "{{asset_name}}",
                "store_role": null,
                "catalog_role": null,
                "advertiser_role": "ADMIN",
                "tt_account_roles": null,
                "owner_bc_name": "{{owner_bc_name}}",
                "advertiser_account_type": "AUCTION",
                "asset_id": "{{asset_id}}",
                "asset_type": "ADVERTISER"
            }
        ]
    }
}
(/code)
```

