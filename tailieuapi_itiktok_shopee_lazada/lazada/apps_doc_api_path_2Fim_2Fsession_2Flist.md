# GETGetSessionList

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fsession%2Flist
> API path: /im/session/list
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:45:08.827Z

---

Latest update2022-07-26 11:22:08

9058

GetSessionList

GET

/im/session/list

Authorization Required

Description:query seller session list

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
| last\_session\_id | String | No | previous page output param \[last\_session\_id\];The last session id on this page, it needs to be passed in as an input parameter when pulling the next page |
| start\_time | String | Yes | next page start time;when pull first page pls input current timestamp， when pull next page pls input last page response field next\_start\_time |
| page\_size | String | Yes | page size |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | result true or false |
| err\_message | String | error message |
| err\_code | String | error code, 0=success |
| data | Object | json |
| has\_more | Boolean | has next page |
| next\_start\_time | Number | the begin timestamp of next page，When pulling the next page, it needs to be passed in as an input parameter |
| last\_session\_id | String | it could be null when pull first page，when pull next page pls input last page response field last\_session\_id |
| session\_list | Object\[\] | object list |
| buyer\_id | Number | buyerUserId |
| tags | String\[\] | the tag of session |
| site\_id | String | country code |
| summary | String | the summary of session |
| self\_position | String | self read position |
| to\_position | String | The other party's read time |
| head\_url | String | buyer head url |
| unread\_count | Number | unread count |
| last\_message\_time | Number | last message time |
| last\_message\_id | String | last message id |
| session\_id | String | session id |
| title | String | buyer nick name |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/session/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/im/session/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/session/list");
request.setHttpMethod("GET");
request.addApiParameter("last_session_id", "100094063_2_1011822749_1_103");
request.addApiParameter("start_time", "1623313363102");
request.addApiParameter("page_size", "20");
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
    "session_list": [
      {
        "summary": "hello2",
        "unread_count": "2",
        "last_message_id": "23hR7YH0BtkiN00001",
        "head_url": "https://sg-live-02.slatic.net/p/0dc6fb4898f7e991bf44c45471dca9c9.jpg",
        "self_position": "1623399917434",
        "site_id": "SG",
        "last_message_time": "1623399917434",
        "session_id": "100094063_2_1011822749_1_103",
        "buyer_id": "1011822749",
        "title": "bruce liu",
        "to_position": "1623399917434",
        "tags": [
          "official"
        ]
      }
    ],
    "next_start_time": "1623399917434",
    "has_more": "true",
    "last_session_id": "100094063_2_1011822749_1_103"
  },
  "success": "true",
  "err_code": "0",
  "request_id": "0ba2887315178178017221014",
  "err_message": "SUCCESS"
}
```
