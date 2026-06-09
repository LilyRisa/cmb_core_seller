---
title: "Update the name of an image"
doc_id: 1740039806891010
path: "API Reference / Images / Update the name of an image"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the name of an image

Use this endpoint to update the name of an image.

 ## Comparing v1.2 and v1.3
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/file/image/ad/update/|/v1.3/file/image/ad/update/|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
```

## Request

**Endpoint**
https://business-api.tiktok.com/open_api/v1.3/file/image/ad/update/

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `multipart/form-data`,`application/json`. <br>Use `multipart/form-data` when the `upload_type` is `UPLOAD_BY_FILE`. |
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID.|
|file_name {Required}|string|Image name. Length limit: 1 - 100 characters. If another image uses the same file name under the same `advertiser_id`, there will a millisecond timestamp added at the end. If the file name contains more than 100 characters, only the first 100 characters will be used.|
|image_id {Required}|string|Image ID. |
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "file_name": "FILE_NAME",
    "image_id": "IMAGE_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/file/image/ad/update/
(/code)

```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|message |string|Return message. For details, see [Appendix-Return Information](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned Data. |
|request_id |string|The log id of a request, which uniquely identifies the request. |
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "data": {},
  "request_id": "2020063011512801023125104012348"
}
(/code);
```
