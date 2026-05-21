# POSTRemoveVideo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fmedia%2Fvideo%2Fremove
> API path: /media/video/remove
> Category: Media Center API
> Scraped: 2026-05-20T23:15:59.656Z

---

Latest update2022-07-29 15:34:34

3088

RemoveVideo

POST

/media/video/remove

Authorization Required

Description:You can this api to delete a video file permanently.

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
| success | Boolean | whether the operation succeeds |
| result\_code | String | error code when the operation fails |
| result\_message | String | error message when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| ILLEGAL\_PARAMETER | detail message | illegal parameter |
| FAIL\_TO\_GET\_SHOP\_INFO | detail message | fail to get shop info |
| FAIL\_TO\_GET\_VIDEO | detail message | fail to get video |
| FAIL\_TO\_DELETE\_VIDEO | detail message | fail to delete video |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/media/video/remove)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/media/video/remove

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/media/video/remove");
request.addApiParameter("videoId", "123456");
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
  "request_id": "0ba2887315178178017221014"
}
```
