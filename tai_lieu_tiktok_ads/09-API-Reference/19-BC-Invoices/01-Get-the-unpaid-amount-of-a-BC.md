---
title: "Get the unpaid amount of a BC"
doc_id: 1740295904843777
path: "API Reference / BC Invoices / Get the unpaid amount of a BC"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the unpaid amount of a BC

Use this endpoint to get total unpaid amount of your Business Center account. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   
> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/unpaid/get/| /v1.3/bc/invoice/unpaid/get/|
|Request parameter data type|` bc_id`: number |`bc_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/invoice/unpaid/get/

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
|invoice_type{Required}|string| Invoice type. Enum values: `RECON` (Reconciliation Invoice).|
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/bc/invoice/unpaid/get/
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "invoice_type": "RECON"
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
#|result|object|The result of the currency and the unpaid amount.  |
##|currency_code|string|Currency code.|
##|amount|number|Total unpaid amount in related currency. |
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 40002,
    "message": "ReadObjectCB: expect \" after {, but found {, error found in #10 byte of ...|bc_id\": {{advertiser|..., bigger context ...|{\n\t\"bc_id\": {{advertiser_id}},\n    \"invoice_type\": \"RECON\"\n}|...",
    "request_id": "{{request_id}}"
}
(/code)
```


