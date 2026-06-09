---
title: "Get asynchronous download task list"
doc_id: 1754000504437762
path: "API Reference / BC Invoices / Download the invoices of a BC / Download asynchronously / Get asynchronous download task list"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get asynchronous download task list

Use this endpoint to get `BILLING_REPORT`, `INVOICE_LIST` and  `INVOICE_BATCH` tasks. If the tasks have been completed, you can download files via the URL in the response. 

Both Agency and Direct Business Centers are able to perform invoice related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/invoice/task/list/| /v1.3/bc/invoice/task/list/|
|Request parameter data type|`bc_id`: number |`bc_id`: string |
|Response parameter data type|`bc_id`: number |`bc_id`: string |
|Response parameter name|`url` |`download_url` |
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/list/

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
|page|number| Page number. Default value: 1.|
|page_size|number|Page size, in the range of 1-50. Default value: 10.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET https://business-api.tiktok.com/open_api/v1.3/bc/invoice/task/list/?bc_id={{bc_id}}
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log id of the request, which uniquely identifies a request.  |
|data |object|Returned data.|
#|list|object[]|List of tasks.|
##|bc_id|string|Business Center ID.|
##|task_id|string| Task ID.|
##|status|string|Asynchronous download task status. <br><br>Enum values: `FAILED`, `CREATED` (<span style="color:Red">Deprecated</span>), `RUNNING`, `SUCCEED`, `POLLING` (<span style="color:Red">Deprecated</span>).|
##|create_time|string|Created time of the task. |
##|update_time |string|Update time of the task.|
##|download_url|string| If the task has been completed successfully, it will be a url to download the task file. |
##|download_type|string|Download type. <br><br>Enum values: `INVOICE_LIST`, `INVOICE_BATCH`.|
#|page_info|object| Pagination information.|
##|page|number| Current page number.|
##|page_size| number | Page size.|
##|total_number| number | Total number of results. |
##|total_page| number | Total pages of results.|
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
                "bc_id": "{{bc_id}}",
                "create_time": "{{timestamp}}",
                "download_type": "INVOICE_BATCH",
                "download_url": "{{download_url}}",
                "status": "SUCCEED",
                "task_id": "{{task_id}}",
                "update_time": "{{timestamp}}"
            },
            {
                "bc_id": "{{bc_id}}",
                "create_time": "{{timestamp}}",
                "download_type": "INVOICE_BATCH",
                "download_url": null,
                "status": "FAILED",
                "task_id": "{{task_id}}",
                "update_time": "{{timestamp}}"
            },
            {
                "bc_id": "{{bc_id}}",
                "create_time": "{{timestamp}}",
                "download_type": "BILLING_REPORT",
                "download_url": "{{download_url}}",
                "status": "SUCCEED",
                "task_id": "{{task_id}}",
                "update_time": "{{timestamp}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_number": 11,
            "total_page": 2
        }
    }
}
(/code)
```
