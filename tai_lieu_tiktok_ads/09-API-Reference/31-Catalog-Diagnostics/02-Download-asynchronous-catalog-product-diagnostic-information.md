---
title: "Download asynchronous catalog product diagnostic information"
doc_id: 1771117294731266
path: "API Reference / Catalog Diagnostics / Download asynchronous catalog product diagnostic information"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download asynchronous catalog product diagnostic information

Use this endpoint to download a CSV file of catalog product diagnostic information.

Note that the CSV file only returns the Product ID (`product_id`), SKU ID (`sku_id` / `hotel_id` / `flight_id` / `destination_id`), Item title (`title`), Issue title (`issue_title`), Severity (`issue_level`), and Source (feed name) for each issue. To obtain more detailed synchronous catalog diagnostic information, use [/diagnostic/catalog/](https://ads.tiktok.com/marketing_api/docs?id=1771117232728066).

## Before you start
You need to ensure you have created a download task for catalog product diagnostic information by using [/diagnostic/catalog/product/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1771117279175682).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/product/task/get/  

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| catalog_id {Required} | string | Catalog ID that you used to create the download task for catalog diagnostic information. |
| bc_id {Required} | string | Business Center ID that you used to create the download task for catalog diagnostic information. |
| task_id {Required} | string | ID of the download task for catalog diagnostic information. <br>To obtain the ID, use [/diagnostic/catalog/product/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1771117279175682). |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/product/task/get/?catalog_id={{catalog_id}}&bc_id={{bc_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| status | string | The status of the download task for catalog diagnostic information. <br>Enum values: <ul><li>`SUCCEED`: The task has succeeded. You can download the file of catalog diagnostic information through the URL returned in the field `diagnostic_file_url`. </li><li>`PROCESSING`: The task is being processed.</li><li> `FAILED`: The task has failed.</li></ul>|
#| diagnostic_file_url | string | The URL to download the CSV file of catalog diagnostic information. <br><p><span style="color:darkred"><b>Note</b></span>: The URL doesn't expire. |
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
        "status": "SUCCEED",
        "diagnostic_file_url": "{{diagnostic_file_url}}"
    }
}
(/code)
```
