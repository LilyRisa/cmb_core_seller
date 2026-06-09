---
title: "Download synchronously"
doc_id: 1754000198430722
path: "API Reference / BC Invoices / Download the invoices of a BC / Download synchronously"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download synchronously

Use this endpoint to download an individual invoice synchronously. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/download/| /v1.3/bc/invoice/download/|
|Request parameter data type|`invoice_id`: number<br>`bc_id`: number |`invoice_id`: string<br>`bc_id`: string |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/invoice/download/

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
|invoice_id {Required}|string| Invoice ID.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/invoice/download/?bc_id={{bc_id}}&invoice_id={{invoice_id}}
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response
Return pdf file stream. 

If the API call fails, the below parameters will be returned.
``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 40104,
    "message": "The access_token is empty.",
    "request_id": "{{request_id}}"
}
(/code)
```
