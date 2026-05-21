# POSTMcnContentCompleteCreateVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fvideo%2Fblock%2Fcommit
> API path: /content/mcn/video/block/commit
> Category: LazLike API
> Scraped: 2026-05-21T00:11:59.925Z

---

Latest update2026-05-21 08:11:53

500

McnContentCompleteCreateVideo

POST

/content/mcn/video/block/commit

No Authorization Required

Description:After uploading all blocks of the video file, call McnContentCompleteCreateVideo to complete the video uploading process.

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
| uploadId | String | Yes | come from the result of McnContentInitCreateVideo |
| parts | String | Yes | a json string contains e\_tag info of each block |
| title | String | Yes | the video title |
| coverUrl | String | No | optional. cover Image of video，return by calling McnContentUploadImage |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result of api |
| videoId | Number | return video's id for further call |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/video/block/commit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/mcn/video/block/commit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/video/block/commit");
request.addApiParameter("uploadId", "ABCDEF");
request.addApiParameter("parts", "[{\"partNumber\":1,\"eTag\":\"AB693ADF0DF340F50637686D65CC062C\"},{\"partNumber\":2,\"eTag\":\"557C398778A948415C388B347509CE1C\"}]");
request.addApiParameter("title", "hello");
request.addApiParameter("coverUrl", "http://lazada.com/cover");
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
    "videoId": "12345678",
    "result_code": "OK"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
