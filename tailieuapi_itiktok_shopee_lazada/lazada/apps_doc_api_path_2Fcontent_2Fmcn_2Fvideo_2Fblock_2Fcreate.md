# GET/POSTMcnContentInitCreateVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fvideo%2Fblock%2Fcreate
> API path: /content/mcn/video/block/create
> Category: LazLike API
> Scraped: 2026-05-21T00:12:30.941Z

---

Latest update2026-05-21 08:12:17

500

McnContentInitCreateVideo

GET/POST

/content/mcn/video/block/create

No Authorization Required

Description:Initial an upload video process, this API will return the corresponding UploadID

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
| kolUserId | Number | Yes | buyer account of kol |
| fileName | String | Yes | local filename, should be less than 20 chars |
| fileBytes | Number | Yes | video file's bytes, should be less than 100M |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result of api |
| upload\_id | String | The temporary ID used during the video uploading process corresponds to the upload of a video. |
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code provided when the operation fails |
| result\_message | String | error message provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/video/block/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/content/mcn/video/block/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/video/block/create");
request.addApiParameter("kolUserId", "123456");
request.addApiParameter("fileName", "mountain.mp4");
request.addApiParameter("fileBytes", "6310731");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "upload_id": "ABCDEFGH",
    "result_message": "success",
    "success": "true",
    "result_code": "OK"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
