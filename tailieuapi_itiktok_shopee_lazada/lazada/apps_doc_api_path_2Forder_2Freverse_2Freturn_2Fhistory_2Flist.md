# GETGetReverseOrderHistoryList

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Freturn%2Fhistory%2Flist
> API path: /order/reverse/return/history/list
> Category: Return and Refund API
> Scraped: 2026-05-20T23:24:53.465Z

---

Latest update2022-07-28 17:13:25

8702

GetReverseOrderHistoryList

GET

/order/reverse/return/history/list

Authorization Required

Description:Get the communication history of the reverse order

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
| reverse\_order\_line\_id | Number | Yes | reverse order line id |
| page\_size | Number | No | default 10 |
| page\_number | Number | No | default 1 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | {} |
| list | Object\[\] | history |
| operator | String | operator |
| picture | String\[\] | picture url |
| time | Number | timestamp |
| page\_info | Object | page info |
| page\_size | Number | page size |
| current\_page\_number | Number | current page number |
| total | Number | total number |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 103 | E0103: reverse order line id is empty when query reject reason | E0103: reverse order line id is empty when query reject reason |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
| 116 | E0116: no seller id | E0116: no seller id |
| 117 | E0117: no user id | E0117: no user id |
| 118 | E0118: no user email | E0118: no user email |
| 120 | E0120: page size invalid | E0120: page size invalid |
| 121 | E0121: page number invalid | E0121: page number invalid |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/return/history/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/return/history/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/return/history/list");
request.setHttpMethod("GET");
request.addApiParameter("reverse_order_line_id", "0");
request.addApiParameter("page_size", "10");
request.addApiParameter("page_number", "1");
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
    "page_info": {
      "total": "10",
      "page_size": "10",
      "current_page_number": "1"
    },
    "list": [
      {
        "time": "1627562669235",
        "operator": "Jason",
        "picture": []
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```
