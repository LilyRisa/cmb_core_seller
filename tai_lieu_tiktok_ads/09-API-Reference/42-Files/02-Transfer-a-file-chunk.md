---
title: "Transfer a file chunk"
doc_id: 1737721489945601
path: "API Reference / Files / Transfer a file chunk"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Transfer a file chunk

Use this endpoint to transfer a file chunk. You need to segment your file to chunks based on the `start_offset` and `end_offset` values in the response of the [/file/start/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721229896705) endpoint. 

When the `start_offset` value is equal to the `end_offset` value returned in the response of this endpoint, the last chunk has been uploaded. Then you can call [/file/finish/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721642924033) to finish the upload. 

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/transfer/upload/|/v1.3/file/transfer/upload/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/transfer/upload/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string| The content type of the request. For this endpoint, use `multipart/form-data`.|
```

**Parameters**
```xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID|
|upload_id {Required}|string|The ID of the upload task. |
|signature {Required}|string|The MD5 of the chunk, used as a checksum to verify data integrity.|
|start_offset {Required}|number|The starting offset of the current chunk.|
|file {Required}|file|The file chunk|
```

### Example

``` xcodeblock
(code Curl curl)
curl -H "Access-Token:xxx" -H "Content-Type:multipart/form-data;" -X POST \\
--form 'advertiser_id=ADVERTISER_ID' \\
--form 'upload_id=UPLOAD_ID' \\
--form 'signature=SIGNATURE' \\
--form 'start_offset=START_OFFSET' \\
--form 'file=@/path/to/your/file' \\
https://business-api.tiktok.com/open_api/v1.3/file/transfer/upload/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|start_offset|number|The starting offset of the uploaded chunk.|
#|end_offset|number|The ending offset of the uploaded chunk.<br>When the `start_offset` value is equal to the `end_offset` value, the last chunk has been uploaded. Then you can call [/file/finish/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737721642924033) to finish the upload. |
|request_id|string|The unique request ID.  |
```

### Example
``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "start_offset": 20971520,
        "end_offset": 41943040
    },
    "request_id": "2021011407430001023102002329278"
}
(/code);
```

