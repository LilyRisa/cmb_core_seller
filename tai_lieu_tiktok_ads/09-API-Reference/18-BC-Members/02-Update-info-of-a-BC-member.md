---
title: "Update info of a BC member"
doc_id: 1739696704424961
path: "API Reference / BC Members / Update info of a BC member"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update info of a BC member

Use this endpoint to update a member's user name and their role in the Business Center. You need to be an Admin of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/member/update/|/v1.3/bc/member/update/|
|Request parameter data type|`bc_id`: number<br>`user_id`: number|`bc_id`: string <br>`user_id`: string|
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/member/update/

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
|bc_id {Required} | string | Business Center ID |
| user_id {Required}| string | User ID of the Business Center's member. |
| user_role | string | New basic role for the user in this Business Center. Enum values: <ul><li>`ADMIN`: Admins can manage the Business Center's members and assets. <li>`STANDARD`: Standard members can only work on the ad accounts they've been assigned to. |
| user_name | string | New name of the user |
| ext_user_role | object | Advanced role of the user in the Business Center other than basic role, this is optional. |
#|finance_role | string | New finance role of the user. Enum values: <ul><li>`ANALYST`: Finance Analysts can view, export, and download payment transaction records for all ad accounts owned or created by the Business Center.<li>`MANAGER`: Finance Managers have all the permissions as the Finance Analyst, and have the access to a series of editing permissions, which they can use to make payments, top up ad accounts, and modify contact information for billing and invoicing, for all of the ad accounts owned or created by the Business Center. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "user_id": "USER_ID",
    "user_role": "USER_ROLE",
    "user_name": "USER_NAME",
    "ext_user_role": {
        "finance_role": "FINANCE_ROLE"
    }
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/member/update/
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


