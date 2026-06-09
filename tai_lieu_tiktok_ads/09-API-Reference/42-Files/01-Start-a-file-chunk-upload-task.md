---
title: "Start a file chunk upload task"
doc_id: 1737721229896705
path: "API Reference / Files / Start a file chunk upload task"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Start a file chunk upload task

Use this endpoint to initiate a file chunk upload task.

After the task is created, use [/file/transfer/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721489945601) to upload one chunk at a time. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/start/upload/|/v1.3/file/start/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```
## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/start/upload/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use “application/json”.|
```

**Parameters**
```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID|
|size {Required}|number|The size of the file that you want to upload, in Bytes.|
|content_type{Required}|string| The content type of the file. Enum values: `image`, `video`, and `music`.|
|name|string|The file name. The maximum length is 100 characters.|
```

### Example
``` xcodeblock
(code Curl curl)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/file/start/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Ccontent-Ttype: application/json' \
    --data-raw '{  
        "advertiser_id": "{{advertiser_id}}", 
        "size": {{size}}, 
        "content_type": "video", 
        "name": "{{name}}"
}'
(/code);
```

## Response
```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|upload_id|string|The upload task ID. It will be needed in all of the following requests for the same task.|
#|file_name|string|The file name. The maximum length is 100 characters.|
#|start_offset|number|The starting offset of the first chunk.|
#|end_offset|number|The ending offset of the first chunk.|
|request_id|string|The unique request ID.  |
```

### Example
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "Message": "OK",
    "Code": 0,
    "Data": {
        "UploadId": "{{upload_id}}",
        "FileName": "{{file_name}}",
        "StartOffset": 0,
        "EndOffset": 20971520
    },
    "RequestId": "{{request_id}}"
}
(/code);
```

