# GET/POSTMessageRecall

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fmessage%2Frecall
> API path: /im/message/recall
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:45:21.697Z

---

Latest update2022-07-26 11:22:09

4741

MessageRecall

GET/POST

/im/message/recall

Authorization Required

Description:message recall

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
| session\_id | String | Yes | session id;conversation id |
| message\_id | String | Yes | the id of message that need to be recalled;1）Cannot be recalled more than two minutes since the message has been sent 2）system message could not be recalled |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| err\_code | String | error code 0=success |
| success | Boolean | true or false |
| err\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/message/recall)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/im/message/recall

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/message/recall");
request.addApiParameter("session_id", "100094063_2_1011822749_1_103");
request.addApiParameter("message_id", "23Fp7TJ0BtmwA00132");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "success": "true",
  "err_code": "0",
  "request_id": "0ba2887315178178017221014",
  "err_message": "SUCCESS"
}
```
