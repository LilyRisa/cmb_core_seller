---
title: "Finish a chunk upload task"
doc_id: 1737721642924033
path: "API Reference / Files / Finish a chunk upload task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Finish a chunk upload task

Use this endpoint to finish a chunk upload task. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/finish/upload/|/v1.3/file/finish/upload/|
|Request parameter data type|`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/finish/upload/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `application/json`.|
```

**Parameters**
```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID|
|upload_id {Required}|string|The ID of the upload task.|
```

### Example

``` xcodeblock
(code Curl curl)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \\
-d '{
    "advertiser_id": "ADVERTISER_ID", 
    "upload_id": "UPLOAD_ID"
}' \\
https://business-api.tiktok.com/open_api/v1.3/file/finish/upload/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|file_id|string|The ID of the file that you have uploaded. You can use this ID to upload the file from the file repository to the creatives library. It is valid for 24 hours. |
#|file_name|string| The name of the file that you have uploaded.
#|create_time|string|The time when the file was uploaded in the format of YYYY-MM-DD HH:MM:SS(UTC+0). Example: 2020-06-10 07:39:14 |
#|size|number|The file size in bytes.|
|request_id|string|The unique ID of the request. |
```

### Example
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "file_id": "444a00c2cdf498ffe4860045",
        "file_name": "test-start-upload",
        "create_time": "2021-01-13 09:49:54",
        "size": 1073741824
    },
    "request_id": "2021011407430001023102002329278"
}
(/code);
```
