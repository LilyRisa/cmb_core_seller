---
title: "Get the balance and budget of ad accounts"
doc_id: 1739939106470913
path: "API Reference / BC Payments / Get the balance and budget of ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the balance and budget of ad accounts

Use this endpoint to obtain the balance of ad accounts in the Business Center. You can also use this endpoint to obtain the budget of the ad accounts owned by the Business Center in auto-allocation mode.

This endpoint only returns the ad accounts that the Business Center has administrator permissions over.

Both Agency and Direct Business Centers are able to perform payment-related operations. 

> <span style="color: dodgerblue">**Note**</span>
> - Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.
> - Reservation ad accounts CANNOT use monthly budget, daily budget or custom budget for Balance Sharing.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/advertiser/balance/get/| /v1.3/advertiser/balance/get/|
|Request parameter data type |`bc_id`: number|`bc_id`: string|
|Request parameter name|`advertiser_show_status`|`advertiser_status`|
|New request parameter|/|`fields`  <br>`payment_portfolio_id`|
|Response parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name| `list`<br>`account_days` <br>`first_recharge_cash`| `advertiser_account_list` <br>`account_open_days` <br>`first_recharge_amount`|
|New response parameter|/|`transferable_amount`<br>`budget_remaining` <br>`budget_frequency_restriction`<br>`budget_amount_restriction`<br>`min_transferable_amount`<br>`payment_portfolio_id`<br>`payment_portfolio_name`<br>`max_transferable_amount`<br>`balance_info`<br>`payment_portfolio_type`|
```

## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/advertiser/balance/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID. |
| fields | string[] | A list of additional fields to return in the response.<br><br>Supported values: <ul><li>`budget_remaining`: The remaining budget.</li><li>`budget_frequency_restriction`: Restrictions on the number of budget changes allowed for the current day.</li><li>`budget_amount_restriction`: Restrictions on the minimum amount that can be changed for the budget.</li><li>`min_transferable_amount`: Details of the minimal amount that you can transfer to the ad account.</li><li>`max_transferable_amount`: Details of the maximum amounts that you can transfer from the ad account to the Business Center.</li><li>`balance_info`: Details of the balances for the ad account.</li></ul></ul><br><ul><li>If this field is not specified, all information will be returned by default excluding `budget_remaining`, `budget_frequency_restriction`, `min_transferable_amount`, `max_transferable_amount`, and `balance_info`.</li><li>If you want to retrieve the additional fields (`budget_remaining`, `budget_frequency_restriction`, `budget_amount_restriction`, `min_transferable_amount`, `max_transferable_amount`, and `balance_info`) together with other fields, set `fields` to `["budget_remaining", "budget_frequency_restriction", "budget_amount_restriction", "min_transferable_amount", "max_transferable_amount", "balance_info"]` and `page_size` to 1.</li></ul> |
|filtering |object| Filtering conditions. |
#|keyword |string| Keywords, you can search for ad account name or ad account ID. |
#|advertiser_status |string[]| Ad Account display status. <br><br>Enum values：<ul><li>`SHOW_ACCOUNT_STATUS_NOT_APPROVED`: failed.</li><li> `SHOW_ACCOUNT_STATUS_APPROVED`: passed.</li><li>`SHOW_ACCOUNT_STATUS_IN_REVIEW`: under review.</li><li>`SHOW_ACCOUNT_STATUS_PUNISHED`: punishment.</li></ul>|
#|payment_portfolio_id|string|Valid when you have enabled the multiple Payment Portfolios feature.<br><br>The ID of the Payment Portfolio to filter the results by.<br><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Multiple Payment Portfolios for one client is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>If this field is specified, the balance information in the response will be for the ad account linked to the Payment Portfolio (`payment_portfolio_id`).</li></ul>|
|page |number|Current number of pages. <br><br>Default value: `1`. Value range : ≥ 1.|
|page_size |number|Page size. <br><br>Default value: `10`. Value range: 1-50.|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "filtering={\"keyword\": \"KEYWORD\", \"status\": [\"STATUS\"]}" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/advertiser/balance/get/
(/code)
```

## Response

``` xtable
|Field {40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of a request, which uniquely identifies the request. |
|data |object|Return data.|
#|advertiser_account_list |object[]| Advertiser account balance list. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the ad account belongs to a Business Center that has enabled Payment Portfolio, the following parameters will be for the Payment Portfolio rather than the ad account: <ul><li>`account_balance`</li><li>`valid_account_balance`</li><li>`frozen_balance`</li><li>`tax`</li><li> `cash_balance`</li><li>`valid_cash_balance` </li><li>`transferable_amount` </li><li>`payment_portfolio_type`</li></ul>|
##|advertiser_id |string| Advertiser account ID. |
##|advertiser_name |string| Advertiser account name.|
##|advertiser_status |string| Advertiser account display status. <br><br>Enum values：<ul><li>`SHOW_ACCOUNT_STATUS_NOT_APPROVED`: failed.</li><li> `SHOW_ACCOUNT_STATUS_APPROVED`: passed.</li><li>`SHOW_ACCOUNT_STATUS_IN_REVIEW`: under review.</li><li>`SHOW_ACCOUNT_STATUS_PUNISHED`: punishment.</li></ul>|
##|advertiser_type |string| Advertiser account type. <br><br>Enum values：`AUCTION`(auction), `RESERVATION`(reservation). |
##|timezone |string| Advertiser account time zone. For enum values, see [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089).|
##|currency |string| Advertiser account currency. For enum values, see [Appendix-Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). |
##|account_open_days |number| Advertiser account opening days. |
##|balance_reminder |boolean| Balance hit the line, when the balance hit the line is yes, it means that you should add money to the main account of the advertisement. |
##|company |string| Advertiser account company name. |
##|contact_name |string| Advertiser account contact name. |
##|contact_email |string| Advertiser account contact email. |
##|create_time |string| Advertiser account opening time (UTC+0). <br><br>Example: `2017-01-01 00:00:00`. |
##|first_recharge_amount{-Deprecated}|number| First recharge amount, kept to two decimal places. |
##|first_recharge_time{-Deprecated}|string| First recharge time (UTC+0). <br><br>Example: `2017-01-01 00:00:00`. |
##|latest_recharge_time{-Deprecated}|string| Last recharge time (UTC+0). <br><br>Example: `2017-01-01 00:00:00`. |
##|recharge_amount{-Deprecated}|number| Recharge the total amount, kept to two decimal places. |
##|recharge_count{-Deprecated}|number| Total number of recharges. |
##|account_balance |number| Advertiser account total balance, kept to two decimal places. |
##|valid_account_balance|number| Advertiser account valid account balance, kept to two decimal places. |
##|frozen_balance|number| Advertiser account frozen balance, kept to two decimal places. |
##|tax |number| Advertiser account taxes, kept to two decimal places. The default value is `0`. |
##|cash_balance |number| Advertiser account cash balance, kept to two decimal places. |
##|valid_cash_balance |number| Advertiser account valid cash balance, kept to two decimal places. |
##|grant_balance |number| Advertiser account coupon/voucher balance, kept to two decimal places. |
##|valid_grant_balance |number| Advertiser account valid coupon/voucher balance, kept to two decimal places. |
##|payment_portfolio_id|number|Returned only for a Business Center that has enabled Payment Portfolio.<br><br>The ID of the Payment Portfolio linked to the ad account.|
##|payment_portfolio_name|string|Returned only for a Business Center that has enabled Payment Portfolio.<br><br>The name of the Payment Portfolio linked to the ad account.|
##|payment_portfolio_type|string|Returned only for a Business Center that has enabled Payment Portfolio.<br><br>The type of the Payment Portfolio.<br><br>Enum values:<ul><li>`SHARED`: Advanced Payment Portfolio. For Advanced Payment Portfolios, funds are centrally managed and jointly used by all linked ad accounts.</li><li>`NON_SHARED`: Standard Payment Portfolio. For Standard Payment Portfolios, funds are individually managed for each linked ad account.</li></ul>|
##| transferable_amount |number|Amount that you can transfer from the advertiser account, based on the currency used in your advertiser account, and kept to two decimal places. <br><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li> The transferable amount excludes the frozen balance (`frozen_balance`) and the deposit for ad delivery. Therefore, the `transferable_amount` might be lower than both `valid_cash_balance` and `valid_account_balance`. To ensure uninterrupted ad delivery, we recommend transferring an amount not greater than the `transferable_amount`.</li><li> This field will only be returned when `total_number` is 1.</li></ul>|
##|max_transferable_amount|object|Details of the maximum amount that you can transfer from the ad account to the Business Center.|
###|cash_amount|number|The maximum cash amount that you can transfer from the ad account to the Business Center, based on the currency used in your advertiser account, and kept to two decimal places.|
###|grant_amount|number|The maximum voucher amount that you can transfer from the ad account to the Business Center, based on the currency used in your advertiser account, and kept to two decimal places.|
##|balance_info|object|Details of the balances for the ad account.|
###|account_balance|number|The total balance of the ad account, kept to two decimal places.<br><br>Formula: `account_balance` = `valid_account_balance` + `frozen_balance` + `tax`.|
###|frozen_balance|number|The frozen balance of the ad account, kept to two decimal places.|
###|valid_account_balance|number|The available balance of the ad account, kept to two decimal places.|
###|tax|number|The taxes of the ad account, kept to two decimal places.|
###|cash_balance|number|The total cash balance of the ad account, kept to two decimal places.|
###|frozen_cash_balance|number|The frozen cash balance of the ad account, kept to two decimal places.|
###|cash_tax|number|The cash taxes of the ad account, kept to two decimal places.|
###|valid_cash_balance|number|The available cash balance of the ad account, kept to two decimal places.<br><br>Formula: `valid_cash_balance` = `cash_balance` - (`frozen_cash_balance` + `cash_tax`).|
###|grant_balance|number|The total voucher balance of the ad account, kept to two decimal places.|
###|valid_grant_balance|number|The available voucher balance of the ad account, kept to two decimal places.|
###|frozen_grant_balance|number|The frozen voucher balance of the ad account, kept to two decimal places.|
###|credit_balance|number|The total credit balance of the ad account, kept to two decimal places.|
###|valid_credit_balance|number|The available credit balance of the ad account, kept to two decimal places.|
###|frozen_credit_balance|number|The frozen credit balance of the ad account, kept to two decimal places.|
##|budget_mode|string|Budget mode of the ad account.<br><br>Enum values:  <ul><li> `UNLIMITED`: No budget limit for the ad account.</li><li> `MONTHLY_BUDGET`: The ad account consumes BC credit balance within the monthly budget.</li><li>`DAILY_BUDGET`: The ad account consumes BC credit balance within the daily budget.</li><li>`CUSTOM_BUDGET`: The ad account consumes BC credit balance within the one-time custom budget.</li></ul><p><span style="color:darkred"><b>Note</b></span>: <ul><li>If the BC is in manual allocation mode, the returned value is always `UNLIMITED`.</li><li>The default budget mode for each ad account is `UNLIMITED` unless you customize your budget mode. </li></ul> |
##|budget|float|<ul><li>If `budget_mode`=`MONTHLY_BUDGET`, the monthly budget of the advertiser account will be returned. </li><li>If `budget_mode`=`DAILY_BUDGET`, the daily budget of the advertiser account will be returned.</li><li>If `budget_mode`=`CUSTOM_BUDGET`, the one-time custom budget of the advertiser account will be returned.</li><li>If `budget_mode`=`UNLIMITED`, returns `0`.</li></ul>  |
##|budget_cost|float|<ul><li>If `budget_mode` is `MONTHLY_BUDGET`, `DAILY_BUDGET`, or `CUSTOM_BUDGET`, spent budget of the ad account will be returned.</li><li>If `budget_mode`=`UNLIMITED`, returns `0`. </li></ul>  |
##| budget_remaining | float | The remaining budget.<br><br>Formula: `budget_remaining` = `budget` - `budget_cost`. |
##| budget_frequency_restriction | object | Restrictions on the number of budget changes allowed for the current day.<br><br>You can make up to 10 updates per day, including both budget mode and budget amount changes or only budget amount changes for the ad account. |
###| total_count | number | The maximum number of updates allowed.<br><br>Example: 10. |
###| used_count | number | The number of updates that have been made.<br><br>Example: 0. |
###| remaining_count | number | The remaining number of updates allowed.<br><br>Example: 10. |
###| effective_start_time | string | The time when the restriction starts, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time).<br><br>Example: `2025-01-01 00:00:01`. |
###| effective_end_time | string | The time when the restriction ends, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time).<br><br>Example: `2025-01-01 23:59:59`. |
##| budget_amount_restriction | object | Restrictions on the minimum amount that can be changed for the budget.|
###|minimum_amount| string |The minimum amount that you can change for the budget.<br> The amount is based on the budget that has been consumed during the budget period. The change amount should be equal to or greater than this value.|
##| min_transferable_amount | object | Returned only when `page_size` is set to 1 in the request.<br><br>Details of the minimal amount that you can transfer to the ad account.|
###|cash_amount| string |The minimum cash amount that you can transfer to the ad account.|
###|grant_amount| string |The minimum voucher amount that you can transfer to the ad account.|
###|credit_amount| string | The minimum credit amount that you can transfer to the ad account. |
#|page_info |object| Pagination information. |
##|page|number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number| Total number of results.|
##|total_page |number| Total number of pages. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "LOG_ID",
    "data": {
        "page_info": {
            "page": 1,
            "total_number": 2,
            "page_size": 5,
            "total_page": 1
        },
        "advertiser_account_list": [
            {
                "advertiser_type": "AUCTION",
                "frozen_balance": 0,
                "grant_balance": 0,
                "account_open_days": 282,
                "valid_cash_balance": 0,
                "company": "tiktok",
                "advertiser_id": "ADVERTISER_ID",
                "latest_recharge_time": null,
                "cash_balance": 0,
                "timezone": "Etc/GMT-12",
                "balance_reminder": false,
                "create_time": "2021-11-02 02:15:32",
                "first_recharge_amount": 0,
                "contact_name": "CONTACT_NAME",
                "account_balance": 0,
                "valid_grant_balance": 0,
                "recharge_amount": 0,
                "valid_account_balance": 0,
                "contact_email": ""
                "recharge_count": 0,
                "currency": "USD",
                "advertiser_status": null,
                "tax": 0,
                "advertiser_name": "ADVERTISER_NAME",
                "budget_mode": "UNLIMITED",
                "budget": 0,
                "budget_cost": 0
            }
        ]
    }
}
(/code);
```

