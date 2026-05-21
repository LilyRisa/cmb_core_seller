# POSTCompleteCreateVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmedia%2Fvideo%2Fblock%2Fcommit
> API path: /media/video/block/commit
> Category: Media Center API
> Scraped: 2026-05-20T23:15:11.495Z

---

Latest update2022-07-14 15:11:31

6917

CompleteCreateVideo

POST

/media/video/block/commit

Authorization Required

Description:After uploading all blocks of the video file, call CompleteCreateVideo to complete the video uploading process.

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
| parts | String | Yes | a json string contains e\_tag info of each block |
| title | String | Yes | the video title |
| coverUrl | String | Yes | the url of the video's cover image |
| videoUsage | String | No | the usage of video, "pro\_main\_video" represent prodcut main video, "im" represent chat video |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| video\_id | String | return video\_id for further call |
| result\_message | String | error message when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| ILLEGAL\_PARAMETER | detail message | illegal parameter |
| FAIL\_TO\_BLOCK\_COMPLETE | detail message | fail to complete block upload |
| FAIL\_TO\_VALIDATE | detail message | fail to validate video |
| FAIL\_TO\_ADD\_VIDEO | detail message | fail to add video |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/media/video/block/commit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/media/video/block/commit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/media/video/block/commit");
request.addApiParameter("uploadId", "123456ABCD");
request.addApiParameter("parts", "[{\"partNumber\":1,\"eTag\":\"AB693ADF0DF340F50637686D65CC062C\"},{\"partNumber\":2,\"eTag\":\"557C398778A948415C388B347509CE1C\"}]");
request.addApiParameter("title", "hello");
request.addApiParameter("coverUrl", "https://sg-live-02.slatic.net/p/ae0f37dbf1c0ef8c560a0f0cfbaac3b6.png");
request.addApiParameter("videoUsage", "pro_main_video");
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
  "request_id": "0ba2887315178178017221014",
  "video_id": "30023680909"
}
```
