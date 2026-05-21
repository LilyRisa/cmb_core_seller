# POSTMcnContentUploadVideoBlock

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fvideo%2Fblock%2Fupload
> API path: /content/mcn/video/block/upload
> Category: LazLike API
> Scraped: 2026-05-21T00:13:36.051Z

---

Latest update2026-05-21 08:13:23

500

McnContentUploadVideoBlock

POST

/content/mcn/video/block/upload

No Authorization Required

Description:upload one block of video file

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
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| uploadId | String | Yes | upload id |
| blockNo | Number | Yes | block number |
| blockCount | Number | Yes | block count |
| file | byte\[\] | Yes | block content |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result of api |
| eTag | String | return e\_tag for using in commit operation |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/video/block/upload)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/mcn/video/block/upload

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/video/block/upload");
request.addApiParameter("uploadId", "ABCD");
request.addApiParameter("blockNo", "0");
request.addApiParameter("blockCount", "1");
request.addFileParameter("file",new FileItem("/Users/D ocuments/book.jpg"));
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "result_message": "success",
    "success": "true",
    "eTag": "ABCDEFGH",
    "result_code": "OK"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
