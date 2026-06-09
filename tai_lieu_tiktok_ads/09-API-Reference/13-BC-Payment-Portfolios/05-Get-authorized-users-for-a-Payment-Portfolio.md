---
title: "Get authorized users for a Payment Portfolio"
doc_id: 1839152031645121
path: "API Reference / BC Payment Portfolios / Get authorized users for a Payment Portfolio"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get authorized users for a Payment Portfolio

Use this endpoint to retrieve the list of users that have access to a Payment Portfolio.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/user/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
|payment_portfolio_id{Required}|string|The ID of the Payment Portfolio.<br><br>To obtain a list of Payment Portfolio IDs, use [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954).|
|page|integer|Current page number.<br><br>Default value: 1.<br>Value range: ≥ 1.|
|page_size|integer|Page size.<br><br>Default value: 10.<br>Value range: 1-50.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/user/get/?payment_portfolio_id={{payment_portfolio_id}}&page=1&page_size=50' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|users|object[]|The list of users that have access to a Payment Portfolio.|
##|user_id|string|The ID of the user.|
##|user_name|string|The name of the user.|
#|page_info|object|Pagination information.|
##|page|number|Current page number.|
##|page_size|number|Page size.|
##|total_number|number|Total number of results.|
##|total_page|number|Total pages of results.|
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
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 2,
            "total_page": 1
        },
        "users": [
            {
                "user_id": "{{user_id}}",
                "user_name": "{{user_name}}"
            },
            {
                "user_id": "{{user_id}}",
                "user_name": "{{user_name}}"
            }
        ]
    }
}
(/code)
```
