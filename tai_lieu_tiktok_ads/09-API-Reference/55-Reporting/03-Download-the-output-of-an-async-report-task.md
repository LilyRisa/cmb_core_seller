---
title: "Download the output of an async report task"
doc_id: 1740302808815618
path: "API Reference / Reporting / Download the output of an async report task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download the output of an async report task

Use this endpoint to  download the output of the report after an asynchronous report task has completed.

## Comparing v1.2 and v1.3 

The following table outlines the differences between v1.2 and v1.3 endpoints. 

```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
| Endpoint path | /v1.2/reports/task/download/ | /v1.3/report/task/download/ |
| Request parameter data type | `advertiser_id`: number | `advertiser_id`: string |
| New respone parameter | / | `code`: number<br>`message`: string<br>`data`: object<br>`download_url`: string<br>`file_name`: string<br>`output_format`: string<br>`request_id`: string |
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/report/task/download/

**Method** GET

**Header**

```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. Please use the same access token as the [Create an asynchronous report task](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) operation.|
```
**Parameters**

```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}| string| Advertiser ID. <br><p><span style="color:darkred"><b>Note</b></span>: If you use `advertiser_ids` when calling [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) (POST method), you need to set this field to one of the Advertiser IDs specified via `advertiser_ids`.    |
|task_id {Required}|string| The ID of the asynchronous report task. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "task_id=TASK_ID" \
https://business-api.tiktok.com/open_api/v1.3/report/task/download/
(/code)
```

## Response
CSV file streams or Json format. 
<br>The below is json format response.
> <span style="color: DodgerBlue">**Note**</span><br>If `output_format` is set to `CSV_DOWNLOAD` or `XLSX_DOWNLOAD` in the [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) endpoint, the response will be json format.

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| download_url | string | The URL to download the file, valid for 1 hour. You can use the [/report/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1740302808815618) endpoint to query a new URL if the previous URL has expired. |
#| file_name | string | The name of the downloaded file. |
#| output_format | string | Format of output. Enum values: `CSV_DOWNLOAD`, `XLSX_DOWNLOAD`. |
|request_id|string|The log ID of the request, which uniquely identifies a request. |
```


### Example
File stream response:

```xcodeblock
(code Success-Response http)
Campaign ID,Campaign Name,Ad Group ID,Ad Group Name,Age,Gender,Cost,Impression,Click
482,Test Campaign 1,578,Test Ad Group 1,13-17,female,107.56,29056,280
482,Test Campaign 1,578,Test Ad Group 1,18-24,female,101.74,22992,203
482,Test Campaign 1,578,Test Ad Group 1,13-17,male,58.78,17063,126
482,Test Campaign 1,578,Test Ad Group 1,18-24,male,57.36,18467,115
622,Test Campaign 2,765,Test Ad Group 2,18-24,male,32.9,267635,1172
482,Test Campaign 1,578,Test Ad Group 1,25-34,female,25.97,8143,105
810,Test Campaign 3,746,Test Ad Group 3,35-44,female,20.31,119359,3214
···
(/code)
```
JSON response:
```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "202207282320310102452431071406594F",
    "data": {
        "output_format": "XLSX_DOWNLOAD",
        "file_name": "test_file_name_07_26.xlsx",
        "download_url": "https://ads.tiktok.com/wsos_v2/statistics/object/wsos62e082e99504cb02?expire=1659054031&skipCookie=true&timeStamp=1659050431&sign=1ccc8bd4665a65a941b168652ccd392707798a1dcb9f1eec2229123aca5a0bff"
    }
}
(/code)
```

