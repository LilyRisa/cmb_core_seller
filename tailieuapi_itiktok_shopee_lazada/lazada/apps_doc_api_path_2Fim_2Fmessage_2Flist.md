# GETGetMessages

> Source: https://open.lazada.com/apps/doc/api?path=%2Fim%2Fmessage%2Flist
> API path: /im/message/list
> Category: Instant Messaging API
> Scraped: 2026-05-20T23:44:46.316Z

---

Latest update2022-07-26 11:22:06

14912

GetMessages

GET

/im/message/list

Authorization Required

Description:Get message list

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
| start\_time | Number | Yes | when request the first page pls input current timestamp，get the next page pls input previous page response field next\_start\_time |
| page\_size | Number | Yes | page size |
| last\_message\_id | String | No | previous page output param \[last\_message\_id\];it could be null when get the first page, get the next page pls input previous page response field last\_message\_id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| err\_code | String | error code |
| data | Object | json |
| message\_list | Object\[\] | object list |
| from\_account\_type | Number | user type 1=buyer 2=seller;sender account type, 1 represents the buyer, 2 represents the seller |
| to\_account\_type | Number | user type 1=buyer 2=seller;receiver account type, 1 represents the buyer, 2 represents the seller |
| from\_account\_id | String | send msg user;sender account id |
| message\_id | String | message id |
| to\_account\_id | String | receiver account id |
| site\_id | String | country code ;SG/MY/TH/VN/PH/ID |
| session\_id | String | session id |
| template\_id | Number | message template;message template id, 1: normal text message 3: picture message 4: emoji message 10006: item message 10007: order message 10008: voucher message 10010: invite buyers to follow the store 6: video message, use this API to upload video (The video duration is greater than 3s and less than 180s) |
| type | Number | 1=userSend 2=systemSend;1: message come from user 2: message come from system |
| content | String | template card json;session summary |
| send\_time | Number | message send time |
| process\_msg | String | If this field is not empty, it means that this message has not passed the security interception verification, which means that this message is only visible to the seller, and the ISV needs to display this prompt information to the seller on the screen |
| status | Number | 0: message status normal, 1: message has been recalled by sender |
| auto\_reply | Boolean | true: it is a auto reply message. false: it is not a auto reply message |
| has\_more | Boolean | has next page |
| next\_start\_time | Number | the begin timestamp of next page，When pulling the next page, it needs to be passed in as an input parameter |
| last\_message\_id | String | The ID of the last message on this page. When pulling the next page, it needs to be passed in as an input parameter |
| success | Boolean | result true or false |
| err\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/im/message/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/im/message/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/im/message/list");
request.setHttpMethod("GET");
request.addApiParameter("session_id", "100094063_2_1011822749_1_103");
request.addApiParameter("start_time", "1623400073000");
request.addApiParameter("page_size", "20");
request.addApiParameter("last_message_id", "24jFlAu0BtRbP47190");
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
    "last_message_id": "24jFlAu0BtRbP47190",
    "message_list": [
      {
        "from_account_type": "2",
        "process_msg": "NOTE: The message has not been sent. Please be polite and aware that you are required to comply with local laws \u0026 policies",
        "session_id": "100094063_2_1011822749_1_103",
        "message_id": "23hR5b20BtRbT00001",
        "type": "1",
        "content": "{\"activeContent\":{},\"txt\":\"THIS IS AUTO REPLY out of working hours\"}",
        "to_account_id": "1011822749",
        "send_time": "1623399917435",
        "auto_reply": "false",
        "to_account_type": "1",
        "site_id": "SG",
        "template_id": "1",
        "from_account_id": "100094063",
        "status": "0"
      }
    ],
    "next_start_time": "1623399917435",
    "has_more": "true"
  },
  "success": "true",
  "err_code": "0",
  "request_id": "0ba2887315178178017221014",
  "err_message": "null"
}
```
