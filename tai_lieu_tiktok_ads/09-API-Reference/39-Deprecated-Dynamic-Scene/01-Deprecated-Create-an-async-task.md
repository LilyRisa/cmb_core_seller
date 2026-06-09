---
title: "(Deprecated) Create an async task"
doc_id: 1740317320231938
path: "API Reference / (Deprecated) Dynamic Scene / (Deprecated) Create an async task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (Deprecated) Create an async task

Use this endpoint to create a task that generates videos. You can get a maximum of 8 videos for each task. 

After the task is created, you need to call [/dynamic_scene/task/get/](https://ads.tiktok.com/marketing_api/docs?id=1740317337705473) to check whether the task completes.

> <span style="color: dodgerblue">**Note**</span><br>The Dynamic Scene API is now in beta. If you would like access to this feature, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page. 

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dynamic_scene/task/create/| /v1.3/dynamic_scene/task/create/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/dynamic_scene/task/create/

**Method** POST

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
|advertiser_id {Required}|string| Advertiser  ID |
|material_package_id {Required}|string|Material package ID, in the response of [/dynamic_scene/material/submit/](https://ads.tiktok.com/marketing_api/docs?id=1740307384545345) endpoint.|
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
