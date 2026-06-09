---
title: "Get asynchronous download tasks"
doc_id: 1754000480307265
path: "API Reference / BC Invoices / Download the invoices of a BC / Download asynchronously / Get asynchronous download tasks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get asynchronous download tasks

Use this endpoint to check whether the `BILLING_REPORT`  task has completed. If the tasks have been completed, you will get the Excel file stream. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/task/get/| /v1.3/bc/invoice/task/get/|
|Request parameter data type|`bc_id`: number <br>`task_id`: number |`bc_id`: string<br>`task_id`:  string|
```

## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/get/

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
|task_id {Required}|string| Task ID. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/get/?bc_id={{bc_id}}&task_id={{task_id}}
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
#|download_url|string|A url to download the task file. |
#|status|string|Asynchronous download task status. Enum values: `FAILED`, `CREATED`, `RUNNING`, `SUCCEED`, `POLLING`. If the task is successfully completed, xlsx file streams will be in response.|
#|error_msg|string|If the action failed, this field returns the error message.|
|request_id |string|The log id of the request, which uniquely identifies a request. |
```





