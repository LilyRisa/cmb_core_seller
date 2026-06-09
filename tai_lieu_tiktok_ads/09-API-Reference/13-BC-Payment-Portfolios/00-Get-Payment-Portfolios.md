---
title: "Get Payment Portfolios"
doc_id: 1839151931301954
path: "API Reference / BC Payment Portfolios / Get Payment Portfolios"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Payment Portfolios

Use this endpoint to retrieve the list of Payment Portfolios that belong to the same client as the Business Center.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/get/

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
|bc_id{Required}|string|ID of a Business Center.<br><br>By default, the response will include the Payment Portfolios that belong to the same client as the Business Center, even though some of them are not linked to the Business Center.|
|filtering|object|Filtering conditions.|
#|payment_portfolio_ids|string[]|IDs of the Payment Portfolios to filter the results by.<br>Max size: 10.<br>Invalid Payment Portfolio IDs, once specified, will be ignored.|
#|payment_portfolio_type|string|The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li><li>`NON_SHARED`: Standard Payment Portfolio. For Standard Payment Portfolios, funds are individually managed for each linked ad account.</li></ul>|
#|bc_related|boolean|Whether to only return the Payment Portfolios that are linked to the Business Center.<br><br>Supported values: `true`, `false`.|
|page|integer|Current page number.<br><br>Default value: 1.<br>Value range: ≥ 1.|
|page_size|integer|Page size.<br><br>Default value: 10.<br>Value range: 1-50.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/payment_portfolio/get/?bc_id={{bc_id}}&page=1&page_size=50' \
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
#|payment_portfolio_list|object[]|A list of Payment Portfolios.|
##|payment_portfolio_id|string|The ID of the Payment Portfolio.|
##|payment_portfolio_name|string|The name of the Payment Portfolio.|
##|payment_portfolio_type|string|The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li><li>`NON_SHARED`: Standard Payment Portfolio. For Standard Payment Portfolios, funds are individually managed for each linked ad account.</li></ul>|
##|linked_advertiser_account_count|number|The number of ad accounts linked to the Payment Portfolio.|
##|user_count|number|The number of users that have access to the Payment Portfolio.|
##|cash_balance_list|object[]|The list of cash balances within the Payment Portfolio.|
###|currency|string|Currency.|
###|amount|number|The balance amount in the currency.|
##|credit_line_allocation|object|The details of credit line allocation for the Payment Portfolio.|
###|allocated_amount|number|The total allocated credit line amount for the Payment Portfolio.|
###|used_amount|number|The used credit line amount for the Payment Portfolio.|
###|available_amount|number|The available credit line amount for the Payment Portfolio.<br><br>Formula: `available_amount` = `allocated_amount` - `used_amount`.|
###|min_allocation_percentage|number|The minimum allocation percentage, kept to four decimal places.<br>The percentage is equal to the used credit line percentage for the Payment Portfolio.|
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
        "payment_portfolios": [
            {
                "cash_balance_list": [
                    {
                        "amount": 0,
                        "currency": "USD"
                    }
                ],
                "credit_line_allocation": {
                    "allocated_amount": 0,
                    "available_amount": 0,
                    "min_allocation_percentage": 0,
                    "used_amount": 0
                },
                "linked_advertiser_account_count": 0,
                "payment_portfolio_id": "{{payment_portfolio_id}}",
                "payment_portfolio_name": "{{payment_portfolio_name}}",
				"payment_portfolio_type": "SHARED",
                "user_count": 7
            },
            {
                "cash_balance_list": [
                    {
                        "amount": 0,
                        "currency": "USD"
                    }
                ],
                "credit_line_allocation": {
                    "allocated_amount": 0,
                    "available_amount": 0,
                    "min_allocation_percentage": 0,
                    "used_amount": 0
                },
                "linked_advertiser_account_count": 2,
                "payment_portfolio_id": "{{payment_portfolio_id}}",
                "payment_portfolio_name": "{{payment_portfolio_name}}",
				"payment_portfolio_type": "SHARED",
                "user_count": 7
            }
        ]
    }
}
(/code)
```



