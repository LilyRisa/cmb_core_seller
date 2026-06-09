---
title: "Get the balance of a BC"
doc_id: 1739939128198145
path: "API Reference / BC Payments / Get the balance of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the balance of a BC

Use this endpoint to obtain the balance of a Business Center. 

Both Agency and Direct Business Centers are able to perform payment-related operations.

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/balance/get/| /v1.3/bc/balance/get/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
|New request parameters|/|`payment_portfolio_id`|
|Response parameter data type|`bc_id`: number |`bc_id`: string |
|New response parameters|/|`payment_portfolio_type`|
```


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/bc/balance/get/

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
|bc_id {Required}|string| Business Center ID。 |
|payment_portfolio_id|string|The ID of the Payment Portfolio.<br><br>To retrieve the list of Payment Portfolios that belong to the same client as the Business Center, use [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954).|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
https://business-api.tiktok.com/open_api/v1.3/bc/balance/get/
(/code)
```

## Response

``` xtable
|Field {40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of the request, which uniquely identifies a request  |
|data |object|Return data.<br><br><p><span style="color:darkred"><b>Note</b></span>: If the Business Center has enabled Payment Portfolio, the following parameters will be for the Payment Portfolio rather than the Business Center:<ul><li>`account_balance`</li><li>`valid_account_balance`</li><li>`frozen_balance`</li><li>`tax`</li><li> `cash_balance`</li><li>`valid_cash_balance` </li><li>`payment_portfolio_type`</li></ul>|
#|bc_id |string| Business Center ID. |
#|currency |string|The currency of the Business Center. For supported currencies, see [Appendix-Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433)  |
#|account_balance |number|Business Center total balance, rounded to two decimal places |
#|valid_account_balance |number|Business Center valid account balance, rounded to two decimal places |
#|frozen_balance |number| Business Center freeze balance, rounded to two decimal places |
#|tax |number| Business Center tax, rounded to two decimal places |
#|cash_balance|number| Business Center cash balance, rounded to two decimal places |
#|valid_cash_balance|number| Business Center valid cash balance, rounded to two decimal places |
#|grant_balance |number| Business Center coupon/voucher balance, rounded to two decimal places |
#|valid_grant_balance |number| Business Center valid coupon/voucher balance, rounded to two decimal places |
#|payment_portfolio_type|string|The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li><li>`NON_SHARED`: Standard Payment Portfolio. For Standard Payment Portfolios, funds are individually managed for each linked ad account.</li></ul>|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```



