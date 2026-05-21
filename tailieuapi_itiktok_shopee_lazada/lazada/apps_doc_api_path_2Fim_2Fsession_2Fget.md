# GETGetSessionDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fsession%2Fget
> API path: /im/session/get
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:44:53.788Z

---

Latest update2022-07-26 11:22:07

7457

GetSessionDetail

GET

/im/session/get

Authorization Required

Description:get session detail by sessionid

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
| session\_id | String | Yes | session id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| err\_code | String | error code 0=success |
| data | Object | json |
| summary | String | the summary of session |
| self\_position | Number | self read time |
| to\_position | Number | The other party's read time |
| head\_url | String | user head picture url |
| unread\_count | Number | unread count |
| last\_message\_time | Number | last message send time of session |
| last\_message\_id | String | last message id of session |
| session\_id | String | session id |
| title | String | buyer nick name |
| buyer\_id | Number | buyer user id |
| tags | String\[\] | the tag of session |
| site\_id | String | country code |
| success | Boolean | result true or false |
| err\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/session/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/im/session/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/session/get");
request.setHttpMethod("GET");
request.addApiParameter("session_id", "100094063_2_1011822749_1_103");
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
    "summary": "hello test",
    "unread_count": "2",
    "last_message_id": "23hR7YH0BtkiN00001",
    "head_url": "https://sg-live-02.slatic.net/p/0dc6fb4898f7e991bf44c45471dca9c9.jpg",
    "self_position": "1623399917434",
    "last_message_time": "1623399917434",
    "site_id": "SG",
    "session_id": "100094063_2_1011822749_1_103",
    "title": "bruce liu",
    "buyer_id": "1011822749",
    "to_position": "1623399917434",
    "tags": [
      "official"
    ]
  },
  "success": "true",
  "err_code": "0",
  "request_id": "0ba2887315178178017221014",
  "err_message": "SUCCESS"
}
```
