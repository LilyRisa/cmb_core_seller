# GETSubmitSellerReply

> Source: https://open.lazada.com/apps/doc/api?path=%2Freview%2Fseller%2Freply%2Fadd
> API path: /review/seller/reply/add
> Category: Product Review API
> Scraped: 2026-05-20T23:14:51.761Z

---

Latest update2022-07-29 11:59:28

5529

SubmitSellerReply

GET

/review/seller/reply/add

Authorization Required

Description:submit seller reply for customers review

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
| id | Number | Yes | review id that user wants to reply to. Can be obtain from GetProductReviewList |
| content | String | Yes | reply content in text, only support reply in text.max length = 500 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Boolean | reply success or fail |
| success | Boolean | reply success or fail |
| error\_code | String | error code |
| error\_msg | String | error msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| PARAMS\_VALIDATE\_ERROR | NULL\_SELLERID | Cannot recognize sellerid |
| PARAMS\_VALIDATE\_ERROR | NULL\_ID | Cannot recognize id |
| PARAMS\_VALIDATE\_ERROR | NULL\_CONTENT | Empty content |
| PARAMS\_VALIDATE\_ERROR | REPLY\_ALREADY | Already replied. All reply needs go through quality control process. |
| PARAMS\_VALIDATE\_ERROR | NO\_SUCH\_REVIEW | No such review |
| PARAMS\_VALIDATE\_ERROR | REVIEW\_STATUS\_CANNOT\_REPLY | Review status cannot be replied to, review's status may be changed because of being edited or reported |
| PARAMS\_VALIDATE\_ERROR | REVIEW\_TYPE\_DONOT\_SUPPORT\_REPLY | Review type cannot be replied to, only reply to PRODUCT\_REVIEW |
| PARAMS\_VALIDATE\_ERROR | REVIEW\_INFO\_DONOT\_SUPPORT\_REPLY | Review info cannot be replied to, review must have text content or images or video |
| PARAMS\_VALIDATE\_ERROR | REVIEW\_REPORTED\_CANNOT\_REPLY | Review been reported cannot be repied to |
| PARAMS\_VALIDATE\_ERROR | REPLY\_CONTENT\_TOO\_LONG | Reply too long |
| PARAMS\_VALIDATE\_ERROR | BEYOND\_REPLY\_PERIOD | Reply over due |
| TRAFFIC\_CONTROL | TRAFFIC\_CONTROL | Traffic control |
| PARAMS\_VALIDATE\_ERROR | REPLY\_ALREADY | This review has already been replied to and does not support multiple replies. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/review/seller/reply/add)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/review/seller/reply/add

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/review/seller/reply/add");
request.setHttpMethod("GET");
request.addApiParameter("id", "11111111111");
request.addApiParameter("content", "thank you for your reply");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_msg": "error",
  "code": "0",
  "data": "true",
  "success": "true",
  "error_code": "error",
  "request_id": "0ba2887315178178017221014"
}
```
