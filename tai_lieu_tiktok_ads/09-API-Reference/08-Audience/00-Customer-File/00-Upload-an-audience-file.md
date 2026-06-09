---
title: "Upload an audience file"
doc_id: 1739940567842818
path: "API Reference / Audience / Customer File / Upload an audience file"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Upload an audience file

Use this endpoint to upload a data file. After the data file has been uploaded, a unique file path is provided to you. This file path can be used to create or update an audience.<br>

Refer to [Guidelines for identifier normalization](https://business-api.tiktok.com/portal/docs?id=1850030360880129) to learn about how to normalize your identifiers.

## Notes
See the table below to learn about size limit, format, and encryption, and file upload for this endpoint. For more information on file restrictions and Customer File example template, please refer to the [Customer File](https://ads.tiktok.com/help/article?aid=6721963343558475781). 

``` xtable
| Type {30%}| Details{70%} |
|---|---|
| Size limit | Maximum file upload size is 250MB. <br>Files exceeding 250MB can be divided into smaller files and uploaded separately. You can then use the `file_paths` to include all paths in an array when creating an audience using the [Create audience by file]( https://ads.tiktok.com/marketing_api/docs?rid=4eezrhr6lg4&id=1739940570793985) endpoint. |
| Format | Only support `csv `and `txt` files (file suffix should exactly be `.csv` or `.txt`). |
| Encryption | Encrypt with MD5 or SHA256. SHA256 is recommended. <br><br> You must use the `calculate_type` field to specify the encryption status of the file. For enum values, see [Enumeration - Encryption Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). When you set `calculate_type` as `PHONE_SHA256`, phone numbers must be in the E.164 format before they are hashed. For example: +442071838750. SHA256 hashed data will have 64 digits, made up of the letters a-f, A-F, and numbers. [Learn more](https://ads.tiktok.com/help/article?aid=6721963343558475781) about formatting rules.|
|File upload with identifiers|You can upload a file with one or multiple ID types.<br>To upload files with multiple ID types, `calculate_type` should be `MULTIPLE_TYPES` and headers are required. The supported headers are "MAID", "phone_SHA256", "email_SHA256", "MAID_SHA256", "MAID_MD5". All headers are case insensitive. See [Create Customer File Audiences-Customer File API](https://ads.tiktok.com/marketing_api/docs?id=1747012327159809&rid=w8dfmqpyld9) for detailed steps. 
```

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/file/upload/|/v1.3/dmp/custom_audience/file/upload/|
|Request parameter data type|`advertiser_id`: number<br>|`advertiser_id`: string<br>|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/file/upload/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string| `multipart/form-data`|
```

**Parameters**

Please include the request parameters in the request body, and select the `form-data` format. For the `file` field, use the `FILE` as the data type.

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|file {Required}|file|Data files. Only supports CSV and TXT files. The file suffixes should exactly be `.csv` or `.txt`.|
|file_signature {Required}|string|The file's MD5, which is used for server-side verification. |
|calculate_type {Required} |string |Encryption type. The value for this field must be consistent with the actual file data. Otherwise, your upload will fail or you will not be able to create a valid audience. For enum values, see [Enumeration - Encryption Type](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138).|
```

### Example

``` xcodeblock
(code curl http)
curl --location 'https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/file/upload/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'file=@"/path/to/your/file"' \
--form 'advertiser_id="{{advertiser_id}}"' \
--form 'file_signature="{{file_signature}}"' \
--form 'calculate_type="{{calculate_type}}"'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned Data. |
#|file_path |string| Unique path to the file in the repository. <br><p><span style="color:darkred"><b>Note</b></span>: Only the same advertiser ID that was used to upload the audience file can later use the returned `file_path` to create the audience.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "file_path": "f19fa450136c460f"
    },
    "request_id":"11111111111"
}
(/code);
```
