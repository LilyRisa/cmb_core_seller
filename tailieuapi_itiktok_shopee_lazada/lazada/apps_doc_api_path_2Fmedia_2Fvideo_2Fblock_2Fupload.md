# POSTUploadVideoBlock

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmedia%2Fvideo%2Fblock%2Fupload
> API path: /media/video/block/upload
> Category: Media Center API
> Scraped: 2026-05-20T23:16:07.991Z

---

Latest update2022-07-29 15:34:44

5934

UploadVideoBlock

POST

/media/video/block/upload

Authorization Required

Description:The API is used to upload one block of origin video file. The video file can split into multiple files. For example, a 8MB video file can be split into three blocks. 3MB, 3MB and 2MB. These three blocks can be uploaded by calling UploadVideoBlock three times.

## Service Endpoints

| Region | Endpoint |
| --- | --- |
| Vietnam | https://api.lazada.vn/rest |
| Singapore | https://api.lazada.sg/rest |
| Philippines | https://api.lazada.com.ph/rest |
| Malaysia | https://api.lazada.com.my/rest |
| Thailand | https://api.lazada.co.th/rest |
| Indonesia | https://api.lazada.co.id/rest |
## Common Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| app\_key | String | Yes | Unique app ID issued by LAZADA Open Platform console when you apply for an app category |
| timestamp | String | Yes | The time stamp of the request e.g. 1517820392000 (which translates to 5 February 2018 08:46:32) with less than 7200s difference from UTC time |
| access\_token | String | Yes | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| uploadId | String | Yes | return by calling InitCreateVideo |
| blockNo | String | Yes | the current block number, from 0 to N-1 |
| blockCount | String | Yes | total block count of file |
| file | byte\[\] | Yes | binary content of the current block |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| e\_tag | String | return e\_tag for using in commit operation |
| result\_message | String | error message when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| ILLEGAL\_PARAMETER | detail message | illegal parameter |
| FAIL\_TO\_UPLOAD\_BLOCK | detail message | fail to upload block |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/media/video/block/upload)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/media/video/block/upload

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/media/video/block/upload");
request.addApiParameter("uploadId", "123456ABCD");
request.addApiParameter("blockNo", "0");
request.addApiParameter("blockCount", "3");
request.addFileParameter("file",new FileItem("/Users/D ocuments/book.jpg"));
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "result_message": "ok",
  "success": "true",
  "result_code": "ok",
  "e_tag": "FF123456FF",
  "request_id": "0ba2887315178178017221014"
}
```
