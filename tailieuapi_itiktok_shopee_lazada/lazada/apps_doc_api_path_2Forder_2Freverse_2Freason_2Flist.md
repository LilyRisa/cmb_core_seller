# GETGetReverseOrderReasonList

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Freason%2Flist
> API path: /order/reverse/reason/list
> Category: Return and Refund API
> Scraped: 2026-05-20T23:25:00.818Z

---

Latest update2022-07-28 17:13:33

8148

GetReverseOrderReasonList

GET

/order/reverse/reason/list

Authorization Required

Description:Get the list of reject reason. Need to be used in all refuse refund actions

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
| reverse\_order\_line\_id | Number | Yes | reverse order line,Can be understood as reverse order item id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | data |
| reason\_id | Number | reason id |
| muti\_language\_text | String | multi-language reason |
| text | String | english reason |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 103 | E0103: reverse order line id is empty when query reject reason | E0103: reverse order line id is empty when query reject reason |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
| 116 | E0116: no seller id | E0116: no seller id |
| 117 | E0117: no user id | E0117: no user id |
| 118 | E0118: no user email | E0118: no user email |
| 119 | E0119: cannot find any cancel reasons for these orders | E0119: cannot find any cancel reasons for these orders |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/reason/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/reason/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/reason/list");
request.setHttpMethod("GET");
request.addApiParameter("reverse_order_line_id", "0");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "muti_language_text": "out of stock",
      "text": "out of stock",
      "reason_id": "1000017"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
