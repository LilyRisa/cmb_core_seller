---
title: "Check the names of files"
doc_id: 1759130033155073
path: "API Reference / Files / Check the names of files"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Check the names of files

Use this endpoint to check whether one or more file names have been used for images or videos. 

><span style="color:DodgerBlue">**Note**</span><br> Images or videos under the same ad account (`advertiser_id`) cannot have duplicate file names. If you don't check the file name and get a duplicate name error when calling [/file/image/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739067433456642) or [/file/video/ad/upload/](https://ads.tiktok.com/marketing_api/docs?id=1737587322856449) to upload an image or video, you need to upload the file again.



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/file/name/check/

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
| advertiser_id {Required} | string | Advertiser ID. |
| files {+Conditional} | object[] |  <ul><li>If you want to check whether a single file name has been used as video or image name, `file_name` and `file_type` are required.</li><li>If you want to check whether multiple file names have been used as video or image names, `files` is required. </li></ul><br>Information about the file names to check.<br><br> Max size: 20. |
#| file_name {+Conditional} | string | Required when `files` is passed. <br><br>File name. |
#| file_type | string | File type.<br><br> Enum values: `VIDEO` (video), `IMAGE` (image). <br> Default value: `VIDEO`. |
| file_name {+Conditional} | string | <ul><li>If you want to check whether a single file name has been used as video or image name, `file_name` and `file_type` are required.</li><li>If you want to check whether multiple file names have been used as video or image names, `files` is required. </li></ul><br>File name. |
| file_type {+Conditional} | string | <ul><li>If you want to check whether a single file name has been used as video or image name, `file_name` and `file_type` are required.</li><li>If you want to check whether multiple file names have been used as video or image names, `files` is required. </li></ul><br>File type. <br><br>Enum values: `VIDEO` (video), `IMAGE` (image).<br> Default value: `VIDEO`. |
```

### Example
#### Check whether a single file name is duplicate
```xcodeblock
(code curl http)
curl --location --request GET  'https://business-api.tiktok.com/open_api/v1.3/file/name/check/?advertiser_id={{advertiser_id}}&file_name={{file_name}}&file_type={{file_type}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
#### Check whether multiple file names are duplicate
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/file/name/check/?advertiser_id={{advertiser_id}}&files=[{"file_name":"{{file_name}}","file_type":"VIDEO"},{"file_name":"{{file_name}}","file_type":"VIDEO"}]' \
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
#| duplicate | boolean | Returned when `files` is not passed in the request. <br><br>Whether the file name is duplicate. <br><br>Supported values: `true`, `false`. |
#| duplicate_material_id | string | Returned when `duplicate` is `true`.<br><br> The material ID of the image or video that already uses the file name. |
#| batch_results | object[] | Returned when `files` is passed in the request.<br><br>The results for the list of file names checked. |
##| file_name | string | File name. |
##| duplicate | boolean | Whether the file name is duplicate.<br><br> Supported values: `true`, `false`. |
##| duplicate_material_id | string | Returned when `duplicate` is `true`. <br><br>The material ID of the image or video that already uses the file name. |
```

### Example
#### Check whether a single file name is duplicate
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "duplicate": false
    }
}
(/code)
```
#### Check whether multiple file names are duplicate
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "batch_results": [
            {
                "duplicate": false,
                "file_name": "{{file_name}}"
            },
            {
                "duplicate_material_id": "{{duplicate_material_id}}",
                "duplicate": true,
                "file_name": "{{file_name}}"
            }
        ]
    }
}
(/code)
```
