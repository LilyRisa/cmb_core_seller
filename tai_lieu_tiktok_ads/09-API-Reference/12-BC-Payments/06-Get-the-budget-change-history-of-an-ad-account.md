---
title: "Get the budget change history of an ad account"
doc_id: 1830173979548674
path: "API Reference / BC Payments / Get the budget change history of an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the budget change history of an ad account

Use this endpoint to retrieve the budget change history of an ad account within a Business Center.

><span style="color:DodgerBlue">**Note**</span><br> 
> Only members with Finance Manager or Finance Analyst role can retrieve the budget change history of ad accounts within the Business Center. To configure or update the Finance roles for members, use [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505) and [/bc/member/update/](https://business-api.tiktok.com/portal/docs?id=1739696704424961).


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/account/budget/changelog/get//

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
| bc_id {Required}| string | Business Center ID.<br><br>To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| advertiser_id {Required}| string | Ad account ID.<br><br>To obtain the list of ad accounts that you have access to, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401). Set `asset_type` to `ADVERTISER` and select ad accounts with the returned `advertiser_role` as `ADMIN`, `OPERATOR`, or `ANALYST`. |
| filtering | object | Filtering conditions. |
#|start_date | string | Query start date, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br>You can either specify `start_date` and `end_date` simultaneously or leave both `start_date` and `end_date` unspecified.<ul><li>If you specify `start_date` and `end_date` simultaneously, the maximum time range is 365 days.</li><li>If you leave both `start_date` and `end_date` unspecified, the results for the last seven days will be returned by default.</li></ul><br>Example: `2025-01-01`. |
#|end_date | string | Query end date, in the format of `YYYY-MM-DD` (UTC+0 time).<br><br>You can either specify `start_date` and `end_date` simultaneously or leave both `start_date` and `end_date` unspecified.<ul><li>If you specify `start_date` and `end_date` simultaneously, the maximum time range is 365 days.</li><li>If you leave both `start_date` and `end_date` unspecified, the results for the last seven days will be returned by default.</li></ul><br>Example: `2025-02-01`. |
| page | integer | Current page number.<br><br>Value range: ≥ 1.<br>Default value: 1. |
| page_size | integer | Page size.<br><br>Value range: 1-50.<br>Default value: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/bc/account/budget/changelog/get/?bc_id={{bc_id}}&advertiser_id={{advertiser_id}}&filtering={"start_date":"2024-04-22","end_date":"2025-04-22"}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#|changelog_list | object[] | The budget change history details. |
##|operation_time | string | The time when the budget change occurred, in the format of `YYYY-MM-DD HH:MM:SS` (UTC+0 time).<br><br>Example: `2025-01-01 00:00:01`. |
##|activity_type | string | Change type.<br><br>Enum values:<ul><li>`RESET`: Reset the one-time budget consumption of the ad account to 0.</li><li>`BUDGET_MODE_UPDATE`: Change the budget mode to monthly, daily, or one-time.</li><li>`INCREASE_BUDGET`: Increase budget without changing budget mode.</li><li>`DECREASE_BUDGET`: Decrease budget without changing budget mode.</li><li>`REMOVE_BUDGET`: Change the budget mode to unlimited.</li></ul> |
##|previous_budget | float | The previous budget amount of the ad account before the change, kept to two decimal places. |
##|previous_budget_mode | string | The previous budget mode of the ad account.<br><br>Enum values:<ul><li>`UNLIMITED`: Unlimited budget. No budget limit for the ad account.</li><li>`MONTHLY_BUDGET`: Monthly budget. The ad account consumes Business Center credit line within the monthly budget.</li><li>`DAILY_BUDGET`: Daily budget. The ad account consumes Business Center credit line within the daily budget.</li><li>`CUSTOM_BUDGET`: One-time budget (custom budget). The ad account consumes Business Center credit line within the one-time custom budget.</li></ul> |
##|current_budget | float | The current budget amount of the ad account after the change, kept to two decimal places. |
##|current_budget_mode | string | The current budget mode of the ad account.<br><br>Enum values:<ul><li>`UNLIMITED`: Unlimited budget. No budget limit for the ad account.</li><li>`MONTHLY_BUDGET`: Monthly budget. The ad account consumes Business Center credit line within the monthly budget.</li><li>`DAILY_BUDGET`: Daily budget. The ad account consumes Business Center credit line within the daily budget.</li><li>`CUSTOM_BUDGET`: One-time budget (custom budget). The ad account consumes Business Center credit line within the one-time custom budget.</li></ul> |
##|currency | string | The currency for the current budget, in [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) code format.<br><br>Example: `EUR`. |
##|operator_id | string | The user ID of the operator. |
##|operator_name | string | The user name of the operator. |
#|page_info | object | Pagination information. |
##|page | integer | Current page number. |
##|page_size | integer | Page size. |
##|total_number | integer | Total number of results. |
##|total_page | integer | Total number of pages. |
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
        "changelog_list": [
            {
                "activity_type": "BUDGET_MODE_UPDATE",
                "currency": "USD",
                "current_budget": "{{current_budget}}",
                "current_budget_mode": "DAILY_BUDGET",
                "operation_time": "{{operation_time}}",
                "operator_id": "{{operator_id}}",
                "operator_name": "{{operator_name}}",
                "previous_budget_mode": "UNLIMITED"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```
