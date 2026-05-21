# POSTSendMessage

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fmessage%2Fsend
> API path: /im/message/send
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:46:10.402Z

---

Latest update2022-07-26 11:22:10

10464

SendMessage

POST

/im/message/send

Authorization Required

Description:send message

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
| session\_id | String | Yes | conversation id |
| template\_id | String | Yes | message template id, 1: normal text message 3: picture message 4: emoji message 10006: item message 10007: order message 10008: voucher message 10010: invite buyers to follow the store 6: video message, use this API to upload video (The video duration is greater than 3s and less than 180s) |
| txt | String | No | template\_id=1 required |
| img\_url | String | No | template\_id=3 required |
| width | Number | No | template\_id=3/6 required |
| height | Number | No | template\_id=3/6 required |
| item\_id | String | No | template\_id=10006 required |
| order\_id | String | No | template\_id=10007 required |
| promotion\_id | String | No | template\_id=10008 required |
| video\_id | String | No | template\_id=6 required |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| err\_code | String | error code 0=success |
| data | Object | json |
| current\_time | Number | send time |
| message\_id | String | message id |
| template\_id | Number | message template id, 1: normal text message 3: picture message 4: emoji message 10006: item message 10007: order message 10008: voucher message 10010: invite buyers to follow the store 6: video message, use this API to upload video (The video duration is greater than 3s and less than 180s) |
| success | Boolean | true or false |
| err\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/message/send)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/im/message/send

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/message/send");
request.addApiParameter("session_id", "100094063_2_1011822749_1_103");
request.addApiParameter("template_id", "1");
request.addApiParameter("txt", "test message");
request.addApiParameter("img_url", "https://sg-live-02.slatic.net/p/0dc6fb4898f7e991bf44c45471dca9c9.jpg");
request.addApiParameter("width", "100");
request.addApiParameter("height", "100");
request.addApiParameter("item_id", "1762013406");
request.addApiParameter("order_id", "1762013406");
request.addApiParameter("promotion_id", "91471122422003");
request.addApiParameter("video_id", "3678332");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "message_id": "23hR7YH0BtkiN00001",
    "template_id": "1",
    "current_time": "1623399917434"
  },
  "success": "true",
  "err_code": "0",
  "request_id": "0ba2887315178178017221014",
  "err_message": "SUCCESS"
}
```
