---
title: "Invite members to a BC"
doc_id: 1739939455765505
path: "API Reference / BC Members / Invite members to a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Invite members to a BC

Use this endpoint to invite members to a Business Center and assign roles and assets to members. You need to be an Admin of the Business Center. 

For each Business Center, the maximum number of members is 4000, and the maximum number of admins online is 20.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/member/invite/| /v1.3/bc/member/invite/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/member/invite/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request.<br>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
| emails {Required} | string[] | A list of user emails. <br>User emails that have been invited earlier will be ignored.  <br> <br>Max size: 50.|
| user_role {Required}| string | Basic role within the Business Center. <br><br>Enum values: <ul><li>`ADMIN`: Admins have full access to all features in Business Center. </li><li>`STANDARD`: Standard members can only work on accounts or assets they've been assigned to.</li></ul>|
| asset_ids | string[] | List of ad account IDs assigned to the invited users.<br> <br>Max size: 50.<br> <br>To obtain ad accounts within a Business Center, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `ADVERTISER`.|
| advertiser_role | string | Ad account role assigned to the invited users. <br> <br>Enum values:<ul><li>`ADMIN`: Admin. Manage ad account finances, settings, and permissions. Create and edit ad campaigns and view performance data.</li><li>`OPERATOR`: Operator. Manage finances for ad accounts, create and edit ad campaigns, and view performance data.</li><li>`ANALYST`: Analyst. View ads and performance data.</li></ul>|
| ext_user_role | object | Advanced role within the Business Center. |
#| finance_role | string | Finance role within Business Center. <br> <br>Enum values: <ul><li>`MANAGER`: Finance manager. Members with finance manager access have full control of all features related to finance and payments. </li><li>`ANALYST`: Finance analyst. Members with finance analyst access can view and download financial data.</li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "emails": [
        "EMAILS"
    ],
    "user_role": "USER_ROLE",
    "asset_ids": [
        "ASSET_IDS"
    ],
    "advertiser_role": "ADVERTISER_ROLE",
    "ext_user_role": {
        "finance_role": "FINANCE_ROLE"
    }
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/member/invite/
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


