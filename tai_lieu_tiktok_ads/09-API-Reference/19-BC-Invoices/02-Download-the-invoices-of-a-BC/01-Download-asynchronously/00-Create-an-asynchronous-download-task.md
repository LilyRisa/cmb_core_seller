---
title: "Create an asynchronous download task"
doc_id: 1754000448122882
path: "API Reference / BC Invoices / Download the invoices of a BC / Download asynchronously / Create an asynchronous download task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an asynchronous download task

Use this endpoint to create an asynchronous download task. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/task/create/| /v1.3/bc/invoice/task/create/|
|Request parameter data type|`invoice_id`: number<br>`bc_id`: number |`invoice_id`: string<br>`bc_id`: string |
|Request parameter name and data type|`bg_id`: number |`billing_group_id`: string |
|New request parameters|/|`campaign_id`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/create/

**Method**   POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br>Allowed format: `application/json`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID. |
|download_type {Required}|string| Download type. <br><br>Enum values:<ul><li> `INVOICE_LIST`: Invoice list in Excel format, with up to 100 invoices.</li><li>`INVOICE_BATCH`: Invoice batch download in ZIP format, with uncompressed invoices in PDF format.</li><li>`BILLING_REPORT`: Detailed transaction report in Excel format.</li></ul><br>After you have created an asynchronous download task, you can use [/bc/invoice/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1754000480307265) to get the Excel file stream for `BILLING_REPORT` tasks, and [/bc/invoice/task/list/](https://ads.tiktok.com/marketing_api/docs?id=1754000504437762) to get the download URLs for `BILLING_REPORT`, `INVOICE_LIST` and `INVOICE_BATCH` tasks.|
|invoice_id {+Conditional}|string| <ul><li>When `download_type` is `BILLING_REPORT`, this field is required.</li><li>When `download_type` is `INVOICE_LIST` or `INVOICE_BATCH`, this field is optional and can be specified as a filter.</li></ul><br>Invoice ID. <br><br>To retrieve the IDs of invoices within a Business Center, use [/bc/invoice/get/](https://business-api.tiktok.com/portal/docs?id=1740295890332674).|
|filtering |object| Filtering conditions.|
#|invoice_title|string|Invoice title.<br><br>To retrieve the titles of invoices within a Business Center, use [/bc/invoice/get/](https://business-api.tiktok.com/portal/docs?id=1740295890332674).|
#|serial_number|string|Invoice number.|
#|billing_group_id|string|Billing Group ID.<br><br>To retrieve the list of Billing Groups within a Business Center, use [/bc/billing_group/get/](https://business-api.tiktok.com/portal/docs?id=1738944324279297).|
#|pay_statuses|string[]|Payment statuses of the invoices. <br><br>Enum values: `UNPAID`, `PAID`, `PARTIAL_PAID`, `NO_NEED`.|
#|start_time|string|Start date of the invoice issue date, in the format of `YYYY-MM-DD` (UTC+0).|
#|end_time|string|End date of the invoice issue date, in the format of `YYYY-MM-DD` (UTC+0).|
#|campaign_id|string| Valid only when `download_type` is `INVOICE_LIST` or `INVOICE_BATCH`.<br><br>Campaign ID.<br><br>To retrieve the list of campaigns within an ad account, use [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/create/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "download_type": "INVOICE_BATCH",
    "invoice_id": "{{invoice_id}}"
}'
(/code)
```
## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|task_id|string|Task ID.|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
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
        "task_id": "{{task_id}}"
    }
}
(/code)
```

