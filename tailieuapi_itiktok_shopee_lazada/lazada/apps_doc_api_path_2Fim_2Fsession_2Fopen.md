# GET/POSTOpenSession

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fsession%2Fopen
> API path: /im/session/open
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:45:37.496Z

---

Latest update2022-07-29 12:36:15

5365

OpenSession

GET/POST

/im/session/open

Authorization Required

Description:open a new conversation

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
| order\_id | Number | Yes | orderId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| session\_id | String | unique id of conversation |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| \-22 | order out of day limit: 30 | Order timeout, only order IDs created within 30 days can be used to create a session |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/session/open)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/im/session/open

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/session/open");
request.addApiParameter("order_id", "465423342423");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "session_id": "100094063_2_1011822749_1_103",
  "request_id": "0ba2887315178178017221014"
}
```
