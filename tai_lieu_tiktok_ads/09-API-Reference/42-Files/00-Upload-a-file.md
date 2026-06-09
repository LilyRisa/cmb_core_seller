---
title: "Upload a file"
doc_id: 1737720893061122
path: "API Reference / Files / Upload a file"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload a file

Use this endpoint to upload a file. The transfer speed is limited by your network bandwidth. The maximum size of a file that you can upload is 100 MB.

After you upload a file to TikTok’s file repository, a `file_id` will be returned in the response. You can use the `file_id` to retrieve the file in the repository and transfer it to the Asset Library.

>  <span style="color:DodgerBlue">**Note**</span><br>
>  Once a file is uploaded to the file repository, it is kept for 24 hours, after which it will expire. If the file you want to use in your ads expires, you need to re-upload the file to the file repository.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/temporarily/upload/|/v1.3/file/temporarily/upload/|
|Request parameter data type|`advertiser_id`: number<br>|`advertiser_id`: string<br>|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/temporarily/upload/

**Method** POST

**Header**
```xtable
|Field|Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request. <br>Allowed values:`multipart/form-data`,`application/json`. When `upload_type`is set to`FILE`, use `multipart/form-data`.|
```

**Parameters**
``` xtable
|Field|Type|Description|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID|
|upload_type {Required}|string| The upload type. <br>Enum values:`FILE`, `URL`|
|content_type {Required}|string|The file type. <br>Enum values:`image`, `music`, `video`, `playable`|
|file {+Conditional}|file|The file that you want to upload. Required when `upload_type`is set to`FILE`. The maximum size of a file that you can upload is 100 MB.  <br><p><span style="color:darkred"><b>Note</b></span>: To upload a customized music file that can be used in [Carousel Ads](https://ads.tiktok.com/marketing_api/docs?id=1766217791987713), the file that you specify through this field should meet the following specifications requirements:<ul><li>File Type: MP3.</li><li>File Size: no greater than 10 MB.</li></ul>|
|url {+Conditional}|string|The URL of the file that you want to upload, in the format of “http://xxx.xxx”. Required when `upload_type` is `URL`.|
|signature {+Conditional}|string|The MD5 value of the file, used as a checksum to check data integrity by the server side. Required when `upload_type` is `FILE`.|
|name |string|The file name. Character limit is 100. <br><p><span style="color:darkred"><b>Note</b></span>: Each word in Chinese or Japanese counts as three characters, while each letter in English counts as one character.|
```

### Example

``` xcodeblock
(code Curl http)
curl -H "Access-Token:xxx" -H "Content-Type:multipart/form-data;" -X POST \\
--form 'advertiser_id=ADVERTISER_ID' \\
--form 'upload_type=UPLOAD_TYPE' \\
--form 'signature=SIGNATURE' \\
--form 'content_type=CONTENT_TYPE' \\
--form 'file_name=FILE_NAME' \\
--form 'file=@/path/to/your/file' \\
https://business-api.tiktok.com/open_api/v1.3/file/temporarily/upload/
(/code)
```

## Response

```xtable
|Field|Type|Description|
|---|---|---|
|code |number|The response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|The response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|The returned data.  |
#|file_id|string|The ID of the file that you have uploaded. You can use this ID to upload the file from the file library to the creatives library. It is valid for 24 hours. |
#|signature|string|The MD5 value of the file.|
#|file_size|number|The file size in bytes.|
#|create_time|string|The time when the file was uploaded in the format of YYYY-MM-DD HH:MM:SS(UTC+0). Example: 2020-06-10 07:39:14.|
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
        "file_id": "264c544d8d383388383c8a8129b33a74_1609142644",
        "signature": "264c544d8d383388383c8a8129b33a74",
        "create_time": "2020-12-28 08:04:04",
        "file_size": 1645516
    },
    "request_id": "202012280804020101151761271A085384"
}
(/code);
```
