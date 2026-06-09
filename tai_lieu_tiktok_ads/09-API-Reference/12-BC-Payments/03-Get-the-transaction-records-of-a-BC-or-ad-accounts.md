---
title: "Get the transaction records of a BC or ad accounts"
doc_id: 1792849810925569
path: "API Reference / BC Payments / Get the transaction records of a BC or ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the transaction records of a BC or ad accounts

Use this endpoint to retrieve the transaction records of your Business Center (BC) or ad accounts within the Business Center.

## Before you start
Ensure that you have the necessary Finance role permissions (Finance Manager or Finance Analyst) for the Business Center. To confirm your Finance permissions, call [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and examine the returned `finance_role`.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/account/transaction/get/ 

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. <br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| transaction_level | string | The transaction level that the transaction records will be retrieved at. <br><br> Enum values: <ul><li>`BC`: The Business Center (`bc_id`). </li><li>`ADVERTISER`: The ad accounts within the Business Center (`bc_id`).</li><li>`PAYMENT_PORTFOLIO`: The Payment Portfolio. </li></ul><br> Default value: `ADVERTISER`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If a Business Center has enabled Payment Portfolio, the transactions of ad accounts shared by other Business Centers won't be returned. |
| filtering | object | Filtering conditions. |
#| transaction_types | string[] | Transaction types. <br><br>  <ul><li>`BILL_PAYMENT`: Bill payment. </li><li>`ADD_BALANCE`: Add balance. </li><li>`CANCELLATION`: Cancellation.</li><li>`PROMOTION_ISSUED`: Issued promotion, which indicates the issuance of ad credit. </li><li>`PROMOTION_EXPIRED`: Expired promotion, which indicates the expiration of ad credit. </li><li>`INCREASE_BALANCE`: Increase of balance.</li><li>`DECREASE_BALANCE`: Decrease of balance.</li><li> `CARD_VERIFICATION`: Card verification. </li><li>`CARD_VERIFICATION_REFUND`: Card verification refund. </li><li>`ORDER_CREATION_PAYMENT`: Order creation payment.</li><li>`ORDER_EDIT_PAYMENT`: Order edit payment. </li><li>`RETURN_OF_REMAINING_BUDGET`: Return of remaining budget.</li><li>`REFUND`: Refund. </li></ul>|
#| owner_of_account | string[] | Valid only when `transaction_level` is `ADVERTISER` or not passed. <br><br> Owner of ad accounts. <br><br>Enum values: <ul><li>`OWNED_BY_CURRENT_BUSINESS_CENTER`: Owned by the current Business Center. </li><li> `SHARED_BY_OTHER_BUSINESS_CENTER`: Shared by another Business Center.</li></ul><br> Default value: `OWNED_BY_CURRENT_BUSINESS_CENTER`. <br><br>If you want to obtain the transaction records of ad accounts shared by another Business Center, you need to have Admin access to these accounts. To confirm if you have Admin access to these accounts, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). The returned `advertiser_role` for the `asset_id` with `asset_type` as `ADVERTISER` should be `ADMIN`. <br><br><p><span style="color:darkred"><b>Note</b></span>: If your Business Center has more than 200 ad accounts shared by another Business Center, you cannot set this field to `SHARED_BY_OTHER_BUSINESS_CENTER` to filter shared ad accounts. In such cases, we recommend that you specify the `account_ids` filter or both `account_ids` and `account_name` filters simultaneously to narrow down the scope of your query. |
#| account_ids | string[] | Valid only when `transaction_level` is `ADVERTISER` or not passed. <br><br> A list of ad account IDs. <br><br>Max size: 100. |
#| account_name | string | Valid only when `transaction_level` is `ADVERTISER` or not passed. <br><br> Ad account name. <br><br>Fuzzy match is supported. |
#| start_time | string | Query start time, closed interval, in the format of `YYYY-MM-DD HH:MM:SS` (ad account time zone or Business Center time zone). <br><br>Example: `2024-01-01 00:00:00`. <br><br>The maximum query time range that you can define through `start_date` and `end_date` is 365 days. <br>If `start_time` and `end_time` are not passed, the transaction records of the last 90 days will be returned by default. |
#| end_time | string | Query end time, closed interval, in the format of `YYYY-MM-DD HH:MM:SS` (ad account time zone or Business Center time zone). <br><br>Example: `2024-01-01 00:00:00`. <br><br> The maximum query time range that you can define through `start_time` and `end_time` is 365 days. <br>If `start_time` and `end_time` are not passed, the transaction records of the last 90 days will be returned by default. |
#| billing_types | string[] | Billing types. <br><br>Enum values: <ul><li>`CASH`: cash. </li><li>`CREDIT`: ad credit.</li></ul> |
| page | integer | Current number of pages. <br><br>Default value: 1. <br><br>Value range: ≥ 1. |
| page_size | integer | Page size. <br><br>Default value: 10. <br><br>Value range: 1-50. |
```
### Example

#### Get the transaction records of a BC
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/account/transaction/get/?bc_id={{bc_id}}&transaction_level=BC&filtering={"start_time": "2023-01-01 00:00:00","end_time": "2024-01-01 00:00:00"}&page_size=50' \
--header 'Access-Token: {{Access-Token}}' 
(/code)
```
#### Get the transaction records of ad accounts 
```xcodeblock
(code curl http)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/bc/account/transaction/get/?bc_id={{bc_id}}&filtering={"start_time": "2023-01-01 00:00:00","end_time": "2024-01-01 00:00:00"}&page_size=50' \
--header 'Access-Token: {{Access-Token}}' 
(/code)
```

#### Get the transaction records of a Payment Portfolio
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/account/transaction/get/?bc_id={{bc_id}}&filtering={"start_time":"2024-10-01 00:00:00","end_time":"2024-11-01 23:59:59","billing_types": ["CASH"]}&transaction_level=PAYMENT_ACCOUNT' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```


## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| transaction_list | object[] | The list of transaction records. |
##| transaction_id | string | Transaction ID, the unique identifier of the translation. |
##| payment_portfolio_id | string | The ID of the Payment Portfolio associated with the transaction. <br>This field returns a non-null value only when `transaction_level` is `PAYMENT_PORTFOLIO`. <br><br><p><span style="color:darkred"><b>Note</b></span>: To manage the budget of a Payment Portfolio, use `advertiser_budgets` in [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434). The default `budget_mode` within `advertiser_budgets` will be `CUSTOM`. |
##| payment_portfolio_name | string | The name of the Payment Portfolio associated with the transaction.  <br>This field returns a non-null value only when `transaction_level` is `PAYMENT_PORTFOLIO`.  |
##| account_id | string | The ID of the ad account associated with the transaction. <br> <br>When `transaction_level` is set to `BC` in the request, the value of this field will be null. |
##| account_name | string | The name of the ad account associated with the transaction. <br><br> When `transaction_level` is set to `BC` in the request, the value of this field will be null. |
##| bc_id | string | Business Center ID. |
##| bc_name | string | Business Center name. |
##| amount | number | The total amount of the transaction, which includes the subtotal and tax amount. <br><br> For example, if the amount excluding tax is 50 USD and the tax is 5 USD, the total amount will be 55 USD. <br><br>Example: 55. |
##| subtotal | number | The subtotal amount (the amount excluding taxes) of the transaction. <br><br> Example: 50. |
##| tax_amount | number | The amount of tax included in the transaction. <br><br>Example: 5. |
##| currency | string | The currency code in which the amounts (`amount`, `subtotal`, and `tax_amount`) are calculated. <br><br>Example: `USD`. |
##| amount_type | string | Amount type. <br><br>Enum values: <ul><li> `POSITIVE`: This type represents that a positive amount is associated with the transaction, such as a balance addition. </li><li> `NEGATIVE`: This type represents that a negative amount is associated with the transaction, such as an order creation payment. </li><li>`OTHER`: This type represents that neither a positive amount nor a negative amount is associated with the transaction, such as a card verification refund.</li></ul>|
##| transaction_type | string | Transaction type. <br><br>  <ul><li>`BILL_PAYMENT`: Bill payment. </li><li>`ADD_BALANCE`: Add balance. </li><li>`CANCELLATION`: Cancellation.</li><li>`PROMOTION_ISSUED`: Issued promotion, which indicates the issuance of ad credit. </li><li>`PROMOTION_EXPIRED`: Expired promotion, which indicates the expiration of ad credit. </li><li>`INCREASE_BALANCE`: Increase of balance.</li><li>`DECREASE_BALANCE`: Decrease of balance.</li><li> `CARD_VERIFICATION`: Card verification. </li><li>`CARD_VERIFICATION_REFUND`: Card verification refund. </li><li>`ORDER_CREATION_PAYMENT`: Order creation payment.</li><li>`ORDER_EDIT_PAYMENT`: Order edit payment. </li><li>`RETURN_OF_REMAINING_BUDGET`: Return of remaining budget.</li><li>`REFUND`: Refund. </li></ul>  |
##| billing_type | string | Billing type. <br><br>Enum values: <ul><li>`CASH`: cash. </li><li>`CREDIT`: ad credit.</li></ul> |
##| timezone | string | The time zone where the transaction occurred, in the format of `UTC±HH:MM`.<br><br>Example: `"UTC+07:00"`. |
##| create_time | string | The time when the transaction record was generated, in the format of `"YYYY-MM-DD HH:MM:SS"`. <br><br>Example: `2024-01-01 00:00:01` |
##| invoice_id | string | Invoice ID. <ul><li> To download the invoice synchronously, pass the invoice ID to [/bc/invoice/download/](https://business-api.tiktok.com/portal/docs?id=1754000198430722). </li><li> To download the invoice asynchronously, pass the invoice ID to [/bc/invoice/task/create/](https://business-api.tiktok.com/portal/docs?id=1754000448122882).</li></ul> |
##| serial_number | string | Invoice serial number. |
#| page_info | object | Pagination information. |
##| page | number | Current page number. |
##| page_size | number | Page size. |
##| total_number | number | Total number of results. |
##| total_page | number | Total pages of results. |
```

### Example
#### Get the transaction records of a BC
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "transaction_list": [
            {
                "transaction_id": "{{transaction_id}}",
                "subtotal": 280000,
                "amount_type": "NEGATIVE",
                "billing_type": "CASH",
                "serial_number": "",
                "bc_name": "{{bc_name}}",
                "tax_amount": 0,
                "amount": 280000,
                "transaction_type": "DECREASE_BALANCE",
                "invoice_id": "",
                "create_time": "{{create_time}}",
                "account_name": null,
                "bc_id": "{{bc_id}}",
                "timezone": "UTC+07:00",
                "currency": "VND",
                "account_id": null
            },
            {
                "transaction_id": "{{transaction_id}}",
                "subtotal": 280000,
                "amount_type": "POSITIVE",
                "billing_type": "CASH",
                "serial_number": "{{serial_number}}",
                "bc_name": "Phụ kiện oto VIP",
                "tax_amount": 30249,
                "amount": 310249,
                "transaction_type": "ADD_BALANCE",
                "invoice_id": "{{invoice_id}}",
                "create_time": "{{create_time}}",
                "account_name": null,
                "bc_id": "{{bc_id}}",
                "timezone": "UTC+07:00",
                "currency": "VND",
                "account_id": null
            }
        ],
        "page_info": {
            "total_number": 2,
            "total_page": 1,
            "page_size": 50,
            "page": 1
        }
    }
}
(/code)
```

#### Get the transaction records of ad accounts 
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "transaction_list": [
            {
                "transaction_id": "{{transaction_id}}",
                "subtotal": 200000,
                "amount_type": "POSITIVE",
                "billing_type": "CASH",
                "serial_number": "{{serial_number}}",
                "bc_name": "{{bc_name}}",
                "tax_amount": 21607,
                "amount": 221607,
                "transaction_type": "ADD_BALANCE",
                "invoice_id": "{{invoice_id}}",
                "create_time": "{{create_time}}",
                "account_name": "{{account_name}}",
                "bc_id": "{{bc_id}}",
                "timezone": "UTC-05:00",
                "currency": "VND",
                "account_id": "{{account_id}}"
            },
            {
                "transaction_id": "{{transaction_id}}",
                "subtotal": 200000,
                "amount_type": "POSITIVE",
                "billing_type": "CASH",
                "serial_number": "{{serial_number}}",
                "bc_name": "{{bc_name}}",
                "tax_amount": 21607,
                "amount": 221607,
                "transaction_type": "ADD_BALANCE",
                "invoice_id": "{{invoice_id}}",
                "create_time": "{{create_time}}",
                "account_name": "{{account_name}}",
                "bc_id": "{{bc_id}}",
                "timezone": "UTC-05:00",
                "currency": "VND",
                "account_id": "{{account_id}}"
            }
        ],
        "page_info": {
            "total_page": 1,
            "page_size": 50,
            "page": 1,
            "total_number": 2
        }
    }
}
(/code)
```

#### Get the transaction records of a Payment Portfolio
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "transaction_list": [
            {
                "payment_account_id": "{{payment_account_id}}",
                "transaction_id": "{{transaction_id}}",
                "account_name": null,
                "tax_amount": 0.94,
                "subtotal": 9.41,
                "account_id": null,
                "bc_id": null,
                "payment_account_name": "{{payment_account_name}}",
                "invoice_id": "",
                "timezone": "UTC+12:00",
                "billing_type": "CASH",
                "currency": "AUD",
                "serial_number": "",
                "amount_type": "POSITIVE",
                "amount": 10.35,
                "bc_name": null,
                "create_time": "{{create_time}}",
                "transaction_type": "INCREASE_BALANCE"
            }
        ],
        "page_info": {
            "page_size": 10,
            "total_page": 1,
            "page": 1,
            "total_number": 1
        }
    }
}
(/code)
```

