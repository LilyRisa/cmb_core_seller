# POSTMcnContentReplySchedulePublish

> Source: https://open.lazada.com/apps/doc/api?path=%2Fcontent%2Fmcn%2Fcontent%2FreplySchedulePublish
> API path: /content/mcn/content/replySchedulePublish
> Category: LazLike API
> Scraped: 2026-05-21T00:13:14.545Z

---

Latest update2026-05-21 08:13:01

500

McnContentReplySchedulePublish

POST

/content/mcn/content/replySchedulePublish

No Authorization Required

Description:McnContentReplySchedulePublish

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
| contentId | Number | Yes | contentId |
| publishTimeMillis | Number | Yes | Resume scheduled publishing time |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| api\_result | Object | result of api |
| result | Boolean | api result |
| success | Boolean | whether the operation succeeds |
| errorMessage | String | error message provided when the operation fails |
| errorCode | Number | error code provided when the operation fails |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/content/mcn/content/replySchedulePublish)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/content/mcn/content/replySchedulePublish

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/content/mcn/content/replySchedulePublish");
request.addApiParameter("contentId", "123456");
request.addApiParameter("publishTimeMillis", "1708509600000");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "request_id": "0ba2887315178178017221014",
  "api_result": {
    "result": "true",
    "success": "true",
    "errorMessage": "error",
    "errorCode": "10001"
  }
}
```
