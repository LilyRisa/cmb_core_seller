---
title: "Process a payment"
doc_id: 1739939095321601
path: "API Reference / BC Payments / Process a payment"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Process a payment

Use this endpoint to recharge money to or deduct money from an ad account in a Business Center, or to increase or decrease the credit balance of a Business Center.

Both Agency and Direct Business Centers are able to perform payment-related operations. 

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager permission can process a payment in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/transfer/| /v1.3/bc/transfer/|
|Request parameter data type|`advertiser_id`: number <br>`bc_id`: number |`advertiser_id`: string <br>`bc_id`: string |
|Response parameter data type|`advertiser_id`: number <br>`bc_id`: number |`advertiser_id`: string <br>`bc_id`: string |
| New request parameters | / | `amount_info` <br>`payment_portfolio_id`|
|New response parameters| / | `transaction_infos`<br>`payee_account`<br>`remitter_account` |
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/transfer/

**Method** POST

**Header**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {required}|string| The content type of the request.<br data-tomark-pass>Allowed value: `"application/json"`  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|bc_id {Required}|string| Business Center ID. |
|transfer_level| string | Transfer level. <br><br>Enum values: <ul><li>`ADVERTISER`: Recharge money to or deduct money from an ad account.</li><li> `BC`: Increase or decrease the credit balance of the Business Center.<ul><li> When `transfer_level` is set to `BC`, you need to specify the ID of a Business Center that has enabled Monthly Invoicing via `bc_id`.</li></ul></li></ul> Default value: `ADVERTISER`. |
|advertiser_id {+Conditional}|string| Required when `transfer_level` is set to `ADVERTISER` or not specified. <br><br>Ad account ID. |
|payment_portfolio_id {+Conditional}|string|Required if multiple Payment Portfolios belong to the same client as the Business Center (`bc_id`).<br><br>The ID of the Payment Portfolio.<br><br>To retrieve the list of Payment Portfolios that belong to the same client as the Business Center, use [/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954).|
|transfer_type {Required}|string| How you'd like to process payments for an ad account or the Business Center. <br><br>Enum values:<ul><li>`RECHARGE`: Recharge money to an ad account or increase the credit balance of a Business Center.</li><li>`REFUND`: Deduct money from an ad account or decrease the credit balance of a Business Center.</li></ul> |
| amount_info | object | <ul><li>Valid only when `transfer_level` is `ADVERTISER`.</li><li>You need to specify one of `amount_info`, `cash_amount`, or `grant_amount`.</li></ul>Details of the amount to process.<br><br>When you specify `amount_info`, `cash_amount` and `grant_amount` outside of `amoumt_info` will be ignored. |
#| cash_amount | number | The cash amount to process. |
#| grant_amount | number | The voucher amount to process. |
#| credit_amount | number | The credit amount to process |
|cash_amount {+Conditional} |number| <ul><li>You need to specify one of `amount_info`, `cash_amount`, or `grant_amount`. </li><li>If [Balance Sharing](https://business-api.tiktok.com/portal/docs?id=1754705321657346#item-link-Balance%20Sharing) is enabled and `transfer_level` is `ADVERTISER`, this parameter is not supported.</li></ul><br>The cash or credit amount to process, rounded to two decimal places.<br><br><ul><li>When `transfer_level` is set to `ADVERTISER` or not specified, this field represents the cash amount to process.</li><li>When `transfer_level` is set to `BC` or not specified, this field represents the credit amount to process. </li></ul> <br>Value range: > 0. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you choose `RECHARGE` as the transfer type and then specify a cash amount, there will be a limit on the minimum amount that you can transfer from a Business Center to the ad account. To find the minimum amount limit , use [/advertiser/balance/get/](https://business-api.tiktok.com/portal/docs?id=1739939106470913) and check the returned `cash_amount` within the object `min_transferable_amount`. |
|grant_amount {+Conditional}|number| <ul><li>You need to specify one of `amount_info`, `cash_amount`, or `grant_amount`. </li><li>When `transfer_level` is `BC`, this parameter is not supported.</li></ul><br>Voucher amount to process, rounded to two decimal places. <br><br>Value range: > 0. |
|request_id |string| Valid only when `transfer_level` is `BC` or `ADVERTISER`.<br><br>Request ID.<br>This field supports idempotency to prevent you from sending the same request twice. If you retry requests with the same request ID multiple times, then only one will succeed.<br>It is different from the `request_id` returned in the response parameters, which is used to uniquely identify an HTTP request.<br><br><ul><li>When `transfer_level` is `BC`, the value should be a 32-bit string.</li><li>When `transfer_level` is `ADVERTISER`, the value should be a 32-bit string, with idempotency guaranteed for a 30-minute window.</li></ul><br>Example: `123456789`.<br><br><p><span style="color:darkred"><b>Note</b></span>: Idempotency for ad account level transfers using `request_id` is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</p>     |
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/transfer/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "transfer_level": "ADVERTISER",
    "advertiser_id": "{{advertiser_id}}",
    "transfer_type": "REFUND",
    "cash_amount": {{cash_amount}}
}'
(/code)

```


## Response

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|code |number|Return code, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|message |string|Return message, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097)  |
|request_id |string|The log id of the request, which uniquely identifies a request. |
|data |object|Return data.|
#|bc_id |number|Business Center ID. <br><br>When `transfer_level` is `BC`, this field will not be returned.|
#|advertiser_id|number|Ad account ID.<br><br>When `transfer_level` is `BC`, this field will not be returned.|
#| transaction_infos | object[] | Details of the transfers. <br><br><p><span style="color:darkred"><b>Note</b></span>: When `transfer_level` is `ADVERTISER` or not specified, since you are transferring an amount between the ad account and a Business Center, you will receive two transaction records, one at the ad account level and one at the Business Center level. In such cases, you can map the transactions to the transaction records returned from [/bc/account/transaction/get/](https://business-api.tiktok.com/portal/docs?id=1792849810925569) by sending two calls to `/bc/account/transaction/get/`, one with `transaction_level` set to `BC` and one with `transaction_level` set to `ADVERTISER`.|
##| transaction_id | string | Transaction ID of the transfer.<br><br>Example: `1234567890`. |
##| transaction_type | string | Transaction type of the transfer.<br><br>Enum values: <ul><li>`CASH`: cash.</li><li>`GRANT`: voucher.</li><li>`CREDIT`: credit.</li></ul> |
##|payee_account|object|Information about the payee account.|
###|account_type|string|The type of the payee account.<br><br>Enum values:<ul><li>`BC`: Business Center.</li><li>`ADVERTISER`: ad account.</li><li>`PORTFOLIO`: Payment Portfolio.</li></ul>|
###|account_id|string|The ID of the payee account.|
##|remitter_account|object|Information about the remitter account.|
###|account_type|string|The type of the remitter account.<br><br>Enum values:<ul><li>`BC`: Business Center.</li><li>`ADVERTISER`: ad account.</li><li>`PORTFOLIO`: Payment Portfolio.</li></ul>|
###|account_id|string|The ID of the remitter account.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "advertiser_id": {{advertiser_id}},
        "bc_id": {{bc_id}},
        "transaction_infos": [
            {
                "payee_account": {
                    "account_id": "{{account_id}}",
                    "account_type": "BC"
                },
                "remitter_account": null,
                "transaction_id": "{{transaction_id}}",
                "transaction_type": "CASH"
            },
            {
                "payee_account": null,
                "remitter_account": {
                    "account_id": "{{account_id}}",
                    "account_type": "ADVERTISER"
                },
                "transaction_id": "{{transaction_id}}",
                "transaction_type": "CASH"
            }
        ]
    }
}
(/code);
```



