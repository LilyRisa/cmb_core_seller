---
title: "Get the invoices of a BC"
doc_id: 1740295890332674
path: "API Reference / BC Invoices / Get the invoices of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the invoices of a BC

Use this endpoint to get invoices of a Business Center account. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   
> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{20%}|v1.2{30%}|v1.3{50%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/get/| /v1.3/bc/invoice/get/|
|Request parameter data type|`bc_id`: number<br>`invoice_id`: number |`bc_id`: string<br>`invoice_id`: string |
|Response parameter data type|`invoice_id`: number|`invoice_id`: string|
|Response parameter name |`bg_id`<br>`area_code` |`billing_group_id` <br>`country_code`|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/invoice/get/?bc_id={{bc_id}}&invoice_types=["CREDIT"]&page_size=50
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json'
(/code)
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/invoice/get/

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
|bc_id {Required}|string| Business Center ID. |
|invoice_id |string| Invoice ID.|
|invoice_title|string|Invoice title.|
|invoice_types{Required}|string[]| Invoice types. <br><br>Enum values:<ul><li>`RECON`: Reconciliation Invoice.</li><li>`CREDIT`: Credit Invoice. </li><li>`AUTO_PAY`: Automatic Payment Invoice.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: `AUTO_PAY` cannot be used with other invoice types. To filter Automatic Payment Invoices, set `invoice_types` to `["AUTO_PAY"]`. |
|pay_statuses|string[]| Payment statuses of the invoices. Enum values: `UNPAID`, `PAID`, `PARTIAL_PAID`, `NO_NEED`(No need to pay).|
|start_time|string|The start time for the invoice issuance, in the format of `YYYY-MM-DD HH:MM:SS`. <br><br>Example: `2024-01-01 00:00:00`. <br><br>You must specify both `start_time` and `end_time` or neither.|
|end_time|string|The end time for the invoice issuance, in the format of `YYYY-MM-DD HH:MM:SS`. <br><br>Example: `2024-02-01 00:00:00`. <br><br>You must specify both `start_time` and `end_time` or neither.|
|page|number| Page number. Default value: 1.|
|page_size|number|Page size, in the range of 1-50. Default value: 10.|
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|list|object[]|List of invoices.|
##|invoice_id|string|Invoice ID.|
##|serial_number|string| Invoice serial number. |
##|subject_id|number|Contract subject ID.|
##|send_date|string|Invoice issue date, in the format of YYYY-MM-DD. |
##|currency_code |string|Currency.|
##|status|string| Invoice approval status. Enum values: `CREATED`, `SUBMITTED`, `APPROVED`, `DELETED`.|
##|due_date |string|Invoice due date.|
##|invoice_type |string| Invoice type. <br><br>Enum values:<ul><li>`RECON`: Reconciliation Invoice.</li><li>`CREDIT`: Credit Invoice. </li><li>`AUTO_PAY`: Automatic Payment Invoice.</li></ul>|
##|entity_serials |string[]|Entity serial numbers associated with the invoice.|
##|negative_invoice_ids |object[]|ID and serial number of the associated negative invoices that has been approved, returned when the current invoice is a positive invoice and has been reversed.|
###|invoice_id|string|Invoice ID of the associated negative invoices.|
###|serial_number|string| Invoice serial number of the associated negative invoices. |
##|positive_invoice_ids |object[]|ID and serial number of the associated positive invoices, returned when the current invoice is a negative invoice.|
###|invoice_id|string|Invoice ID of the associated positive invoices.|
###|serial_number|string| Invoice serial number of the associated positive invoices. |
##|billing_group_id |string|Billing Group ID of the invoice.|
##|account_name |string|Account name.|
##|invoice_title |string|Invoice title.|
##|country_code |string|Location code.|
##|address |string|Account billing address.|
##|emails |string[]|A list of email addressess from invoice for billing. |
##|tax_id1 |string|ID number of the first tax type of the invoice.|
##|tax_id2 |string|ID number of the second tax type of the invoice.|
##|amount |number|Total amount, including tax.|
##|amount_excluding_tax |number|Total amount, excluding tax|
##|total_tax_amount|number|Total tax amount, in the same currency of the invoice.|
##|tax_type1|string|The first tax type of the invoice.|
##|tax_rate1|number|Rate of the first tax type.|
##|tax_amount1|number|Amount of the first tax type.|
##|tax_type2|string|The second tax type of the invoice.|
##|tax_rate2|number|Rate of the second tax type.|
##|tax_amount2|number|Amount of the second tax type.|
##| pay_status|string|Payment statuses of the invoices.|
##|overdue_status|string|Overdue status. Enum values: `INITIALIZED`, `YES`, `NO`.|
##| unpaid_amount|number|Unpaid amount.|
#|page_info|object| Pagination information.|
##|page|number| Current page number.|
##|page_size| number | Page size.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
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
        "list": [
            {
                "account_name": "{{account_name}}",
                "address": "{{address}}",
                "amount_excluding_tax": 293.5,
                "status": "APPROVED",
                "amount": 293.5,
                "positive_invoice_ids": [
                    {
                        "invoice_id": "{{invoice_id}}",
                        "serial_number": "{{serial_number}}"
                    }
                ],
                "country_code": "VG",
                "invoice_type": "CREDIT",
                "invoice_id": "{{invoice_id}}",
                "serial_number": "{{serial_number}}-CN",
                "invoice_title": "{{account_name}}",
                "currency_code": "USD",
                "unpaid_amount": 293.5,
                "emails": [
                    "{{email}}",
                    "{{email}}"
                ],
                "billing_group_id": "{{billing_group_id}}",
                "entity_serials": [
                    "{{entity_serial}}"
                ]
            },
            {
                "account_name": "{{account_name}}",
                "address": "{{address}}",
                "amount_excluding_tax": 179,
                "status": "APPROVED",
                "amount": 179,
                "positive_invoice_ids": [
                    {
                        "invoice_id": "{{invoice_id}}",
                        "serial_number": "{{serial_number}}"
                    }
                ],
                "country_code": "VG",
                "invoice_type": "CREDIT",
                "invoice_id": "{{invoice_id}}",
                "serial_number": "{{serial_number}}-CN",
                "invoice_title": "{{account_name}}",
                "currency_code": "USD",
                "unpaid_amount": 179,
                "emails": [
                    "{{email}}",
                    "{{email}}"
                ],
                "billing_group_id": "{{billing_group_id}}",
                "entity_serials": [
                    "{{entity_serial}}"
                ]
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 50,
            "total_page": 1,
            "total_number": 8
        }
    }
}
(/code)
```

