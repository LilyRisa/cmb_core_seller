---
title: "Get the members of a BC"
doc_id: 1739939404802049
path: "API Reference / BC Members / Get the members of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the members of a BC

Use this endpoint to get members in a Business Center. You need to be an Admin of the Business Center.

If you need to get assets for a member, use the [/bc/asset/get/](https://ads.tiktok.com/marketing_api/docs?id=1739432717798401) endpoint. You need to pass in either `user_id` or `user_email`.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/member/get/| /v1.3/bc/member/get/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
|Response parameter data type|`user_id`: number |`user_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/member/get/

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
|bc_id {Required} | string | Business Center ID |
|page|	number|	Current page. |
|page_size|	number| Page size.	The maximum page size is 20. |
|filtering	|object|	Filtering conditions|
#| keyword | string | Keyword to filter by |
#| user_role | string | Basic roles within Business Center. Enum values: <ul><li>`ADMIN`: Admins can manage the Business Center's members and assets. <li>`STANDARD`: Standard members can only work on the ad accounts they've been assigned to. |
#| relation_status | string | User's relation to the Business Center. If the invitation to join the Business Center is still pending, or has been accepted. Enum values: <ul><li>`BOUND`: The request to manage the asset has been approved by the owner. </li><li>`PENDING`: The request to manage the asset is pending on the owner's approval. </li><li>`REJECTED`: The request to manage the asset has been rejected by the owner. </li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "filtering={\"keyword\": \"KEYWORD\", \"user_role\": \"USER_ROLE\", \"relation_status\": \"RELATION_STATUS\"}" \
https://business-api.tiktok.com/open_api/v1.3/bc/member/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|list|object[]|Member information.|
##| user_id | string | User ID. |
##| user_name | string | User name. |
##| user_role | string | Basic roles within Business Center.|
##| relation_status | string | User's relation to the Business Center. If the invitation to join the Business Center is still pending, or has been accepted. |
##| user_email | string | User email address.<br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>To protect the personal information of members, some email addresses are masked by default. For instance, an email address of `user@example.com` will be returned as `u**r@example.com`. You can apply for an allowlist to access the unmasked email addresses of all members.</li><li>Accessing unmasked email addresses of all members within your Business Center is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li></ul> |
##| ext_user_role | object | Advanced role of the user in the Business Center other than basic role. |
###| finance_role | string | User finance role. Enum values: `MANAGER`, `ANALYST`. |
#|page_info|object|Pagination information. |
##|total_number|number|Total number of results. |
##|total_page|number|Total pages of results. |
##|page_size|number|Page size. |
##|page|number|Current page number. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "user_id": null,
                "ext_user_role": {
                    "finance_role": null
                },
                "relation_status": "BOUND",
                "user_name": null,
                "user_role": "ADMIN",
                "user_email": "{{user_email}}"
            },
            {
                "user_id": null,
                "ext_user_role": {
                    "finance_role": null
                },
                "relation_status": "PENDING",
                "user_name": null,
                "user_role": "STANDARD",
                "user_email": "{{user_email}}"
            }
        ],
        "page_info": {
            "total_number": 2,
            "page": 1,
            "page_size": 20,
            "total_page": 1
        }
    }
}
(/code)
```


