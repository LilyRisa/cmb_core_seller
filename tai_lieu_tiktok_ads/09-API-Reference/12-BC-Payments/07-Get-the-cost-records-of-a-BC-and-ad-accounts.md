---
title: "Get the cost records of a BC and ad accounts"
doc_id: 1829079287639041
path: "API Reference / BC Payments / Get the cost records of a BC and ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the cost records of a BC and ad accounts

Use this endpoint to retrieve the cost records at the ad account and Business Center levels.

> <span style="color:DodgerBlue">**Note**</span><br>
> - Only members with Finance Manager or Finance Analyst role can retrieve the cost records of a Business Center. To configure or update the Finance roles for members, use [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505) and [/bc/member/update/](https://business-api.tiktok.com/portal/docs?id=1739696704424961).
> - The cost data typically has a latency of up to 10 hours. API responses are immediate but reflect cost data from approximately 10 hours ago.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/account/cost/get/

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
| bc_id {Required}| string | Business Center ID.<br><br> To obtain the list of Business Centers that you have access to, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| filtering | object | Filtering conditions. |
#| keyword | string | Search keyword. <br><br>The fuzzy match is supported. <br><br>You can specify an advertiser ID or name as search keyword. |
#| start_date | string | Query start date, in the format of `YYYY-MM-DD` (ad account time zone). <br><br> You can either specify `start_date` and `end_date` simultaneously or leave both `start_date` and `end_date` unspecified. <ul><li> If you specify `start_date` and `end_date` simultaneously, the maximum time range is 365 days. </li><li> If you leave both `start_date` and `end_date` unspecified, the results for the last 90 days will be returned by default. </li></ul> |
#| end_date | string | Query end date, in the format of `YYYY-MM-DD` (ad account time zone). <br><br> You can either specify `start_date` and `end_date` simultaneously or leave both `start_date` and `end_date` unspecified. <ul><li> If you specify `start_date` and `end_date` simultaneously, the maximum time range is 365 days. </li><li> If you leave both `start_date` and `end_date` unspecified, the results for the last 90 days will be returned by default. </li></ul> |
| page | integer | Current page number. <br><br> Value range : ≥ 1.<br> Default value: 1. |
| page_size | integer | Page size. <br><br> Value range: 1-50. <br>Default value: 10. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/bc/account/cost/get/?bc_id={{bc_id}}&filtering={"start_date": "{{start_date}}", "end_date": "{{end_date}}"}&page=1&page_size=50' \
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
#| cost_list | object[] | The list of cost records for each advertiser. |
##| advertiser_id | string | Advertiser ID. |
##| advertiser_name | string | Advertiser name. |
##| amount | number | Total cost amount for the advertiser. <br><br> Example: -1376616. |
##| cash_amount | number | Cash cost amount for the advertiser. <br><br> Example: -1376616. |
##| grant_amount | number | Ad credit cost amount for the advertiser. <br><br> Example: 0. |
##| tax_amount | number | Estimated tax amount for the advertiser. <br><br> Example: -68831. |
##| currency | string | The currency of the advertiser. <br><br> Example: `USD`. |
#| transaction_summary | object | A summary of cost records for the Business Center. |
##| amount | number | Total cost amount for the Business Center. <br><br> Example: -484400000. |
##| cash_amount | number | Cash cost amount for the Business Center. <br><br> Example: -484400000. |
##| grant_amount | number | Ad credit cost amount for the Business Center. <br><br> Example: 0. |
##| tax_amount | number | Estimated tax amount for the Business Center. <br><br> Example: -2400000. |
##| currency | string | The currency of the Business Center. <br><br> Example: `USD`. |
#| page_info | object | Pagination information. |
##| page | integer | Current page number. |
##| page_size | integer | Page size. |
##| total_number | integer | Total number of results. |
##| total_page | integer | Total number of pages. |
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
        "cost_list": [
            {
                "advertiser_id": "{{advertiser_id}}",
                "advertiser_name": "{{advertiser_name}}",
                "amount": {{amount}},
                "cash_amount": {{cash_amount}},
                "currency": "VND",
                "grant_amount": {{grant_amount}},
                "tax_amount": {{tax_amount}}
            },
            {
                "advertiser_id": "{{advertiser_id}}",
                "advertiser_name": "{{advertiser_name}}",
                "amount": {{amount}},
                "cash_amount": {{cash_amount}},
                "currency": "VND",
                "grant_amount": 0,
                "tax_amount": {{tax_amount}}
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_number": 2,
            "total_page": 1
        },
        "transaction_summary": {
            "amount": {{amount}},
            "cash_amount": {{cash_amount}},
            "currency": "VND",
            "grant_amount": {{grant_amount}},
            "tax_amount": {{tax_amount}}
        }
    }
}
(/code)
```
