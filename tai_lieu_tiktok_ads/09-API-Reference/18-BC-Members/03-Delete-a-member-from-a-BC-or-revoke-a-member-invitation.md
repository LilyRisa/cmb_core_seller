---
title: "Delete a member from a BC or revoke a member invitation"
doc_id: 1739939482851329
path: "API Reference / BC Members / Delete a member from a BC or revoke a member invitation"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete a member from a BC or revoke a member invitation

Use this endpoint to delete a member in a Business Center or revoke a member invitation. You need to be an Admin of the Business Center.



## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/member/delete/| /v1.3/bc/member/delete/|
|Request parameter data type|`bc_id`: number<br>`user_id`: number |`bc_id`: string<br>`user_id`: string |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/member/delete/

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
|bc_id {Required} | string | Business Center ID.|
| user_id {+Conditional}| string| Required when you want to remove existing members.<br><br>User ID.<br><br>To obtain the user IDs of existing members in a Business Center, use [/bc/member/get/](https://business-api.tiktok.com/portal/docs?id=1739939404802049) and check the returned `user_id`. |
| user_email {+Conditional}| string |  Required when you want to revoke the member invitation for a user.<br><br>User email. <br><br>To obtain the user emails of invited members for a Business Center, use [/bc/member/get/](https://business-api.tiktok.com/portal/docs?id=1739939404802049) and check the returned `user_email`. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "user_id": "USER_ID",
    "user_email": "USER_EMAIL"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/member/delete/
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


