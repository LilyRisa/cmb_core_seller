# GETGetVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmedia%2Fvideo%2Fget
> API path: /media/video/get
> Category: Media Center API
> Scraped: 2026-05-20T23:15:26.509Z

---

Latest update2022-07-29 14:06:26

5945

GetVideo

GET

/media/video/get

Authorization Required

Description:You call this action to get video info after uploading.

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
| videoId | Number | Yes | the previous return value by calling CompleteCreateVideo |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| cover\_url | String | cover url of the video |
| video\_url | String | url of the video |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| state | String | possible values: READY\_FOR\_TRANSCODE, TRANSCODING, TRANSCODE\_FAILED, READY\_FOR\_AUDIT, AUDIT\_FAILED, AUDIT\_SUCCESS, DELETED |
| title | String | title of the video |
| result\_message | String | error message when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| ILLEGAL\_PARAMETER | detail message | illegal parameter |
| FAIL\_TO\_GET\_SHOP\_INFO | detail message | fail to get shop info |
| FAIL\_TO\_GET\_VIDEO | detail message | fail to get video |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/media/video/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/media/video/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/media/video/get");
request.setHttpMethod("GET");
request.addApiParameter("videoId", "123456");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "cover_url": "https://sg-live-02.slatic.net/p/9e134745d2bd9b3eba1cf5d5b47d4b0b.jpg",
  "video_url": "http://lazvideo.alicdn.com/psp/20210725/49d480d9-9b15-40f2-8b9f-2a3905132e47@@ld.mp4?auth_key\u003d1627649365-0-0-b5e6a1e67df8bfbf0a6bcb071b92841d",
  "code": "0",
  "result_message": "ok",
  "success": "true",
  "result_code": "ok",
  "state": "AUDIT_SUCCESS",
  "title": "hello",
  "request_id": "0ba2887315178178017221014"
}
```
