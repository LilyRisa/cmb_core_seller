---
title: "Get Business Centers"
doc_id: 1737115687501826
path: "API Reference / BC Management / Get Business Centers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Business Centers

Use this endpoint to obtain the list of Business Centers that a user has access to.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/get/| /v1.3/bc/get/|
|Request parameter data type|`bc_id`: number|`bc_id`: string|
|Response parameter name and data type|`id`: number |`bc_id`: string |
|New response parameters|/|`verification_status`|
```


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/bc/get/

**Method** GET

**Header**

``` xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).
```

**Parameters**

``` xtable
|Field|Type|Description|
|---|---|---|
|bc_id |string|The Business Center ID. When not passed, returns the user's entire list of Business Centers by default, and returns the specified Business Center account when passed in. |
|page |number|Current number of pages. Default value: 1.  Value range : ≥ 1.|
|page_size |number|Page size. Default value: 10. Value range: 1-50.|
```

### Examples

``` xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/get/?page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field{45%}|Data Type{10%}|Description{45%}|
|---|---|---|
|code |number|Return code, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|Return message, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Return data.|
#|list |object[]| Business Center list. |
##|bc_info |object| Business Center. |
###|bc_id |string| Business Center ID. |
###|verification_status|string|Business verification status of the Business Center.<br><br>Enum values:<ul><li>`NOT_SUBMITTED`: The business verification has not been submitted.</li><li>`REVIEWING`: The business verification is currently under review.</li><li>`VERIFIED`: The business has been verified successfully.</li><li>`FAILED`: The business verification has failed.</li><li>`EXPIRED`: The business verification has expired.</li></ul>|
###|name |string| Business Center name. |
###|company |string| Business Center Company Name. |
###|currency |string| Business Center payment currency, the value range is shown in [Appendix-Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). |
###|registered_area |string| Business Center registration code, the value range is shown in [Appendix-Location code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010). |
###|status |string| Business Center status. Enum values：`REVIEWING` (under review), `DENY` (rejected), `ENABLE` (passed), `PUNISH` (disabled), see [Enumeration-Business Center Status](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). |
###|timezone |string| The time zone where the Business Center is located, the value range is shown in [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089). |
###|type |string| Business Center type. Enum values: <ul><li>`NORMAL`: Regular Business Center.</li><li>`DIRECT`: Non-self-serve Direct Business Center.</li><li>`AGENCY`: Non-self-serve Agency Business Center.</li><li>`SELF_SERVICE`: Self-serve Direct Business Center.</li><li>`SELF_SERVICE_AGENCY`: Self-serve Agency Business Center.</li></ul>|
##|user_role |string| Business Center user type. Enum values: `ADMIN` (administrator), `STANDARD` (standard). |
##|ext_user_role|object|Advanced role of the user in the Business Center orther than basic role, this is optional.|
###|finance_role|string|User finance role. Enum values: `MANAGER` (Finance Manager), `ANALYST` (Finance Analyst).|
#|page_info |object| Page information. |
##|page |number| Current page number. |
##|page_size |number| Paging Size. |
##|total_number |number| Total number of results. |
##|total_page |number| Total pages. |
|request_id |string|The log id of the request, which uniquely identifies a request. |
```

### Example

``` xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "{{message}}",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "bc_info": {
                    "bc_id": "{{bc_id}}",
                    "company": "{{company}}",
                    "currency": "SGD",
                    "name": "{{name}}",
                    "organization_type": "STANDARD",
                    "registered_area": "SG",
                    "status": "ENABLE",
                    "timezone": "Asia/Jakarta",
                    "type": "SELF_SERVICE",
                    "verification_status": "NOT_SUBMITTED"
                },
                "ext_user_role": {
                    "finance_role": "MANAGER"
                },
                "user_role": "ADMIN"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 1,
            "total_page": 1
        },
        "parent_bc": {
            "bc_info": {}
        }
    }
}
(/code);
```
