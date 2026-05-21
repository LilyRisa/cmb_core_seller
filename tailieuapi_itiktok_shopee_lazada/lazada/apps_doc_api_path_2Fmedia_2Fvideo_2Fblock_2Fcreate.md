# POSTInitCreateVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmedia%2Fvideo%2Fblock%2Fcreate
> API path: /media/video/block/create
> Category: Media Center API
> Scraped: 2026-05-20T23:15:46.251Z

---

Latest update2022-07-29 14:03:30

6822

InitCreateVideo

POST

/media/video/block/create

Authorization Required

Description:A seller starts to upload a video file

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
| fileName | String | Yes | local file name of vedio file |
| fileBytes | Number | Yes | video file's bytes, should be less than 100M |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| upload\_id | String | return upload\_id for further operation |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| ILLEGAL\_PARAMETER | detail message | illegal parameter |
| FAIL\_TO\_GET\_SHOP\_INFO | detail message | fail to get shop info |
| FAIL\_TO\_BLOCK\_INIT | detail message | fail to create block upload |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/media/video/block/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/media/video/block/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/media/video/block/create");
request.addApiParameter("fileName", "show.mp4");
request.addApiParameter("fileBytes", "123456");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "upload_id": "123456ABCD",
  "code": "0",
  "result_message": "file size is too big",
  "success": "true",
  "result_code": "ok",
  "request_id": "0ba2887315178178017221014"
}
```
