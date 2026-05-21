# GETInitReverseOrderCancelDecide

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Fcancel%2Fseller%2Fdecide
> API path: /order/reverse/cancel/seller/decide
> Category: Return and Refund API
> Scraped: 2026-05-20T23:25:34.159Z

---

Latest update2022-07-28 17:13:46

7225

InitReverseOrderCancelDecide

GET

/order/reverse/cancel/seller/decide

Authorization Required

Description:Seller initiates a cancelation

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
| reverse\_order\_id | Number | Yes | The reverse order to be cancelled |
| agree\_cancel | Boolean | Yes | decision |
| reason\_code | Number | No | reason id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | null |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 116 | E0116: no seller id | E0116: no seller id |
| 105 | E0105: reverse order id is empty or invalid | E0105: reverse order id is empty or invalid |
| 131 | E0131: no decision for this reverse order | E0131: no decision for this reverse order |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/cancel/seller/decide)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/cancel/seller/decide

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/cancel/seller/decide");
request.setHttpMethod("GET");
request.addApiParameter("reverse_order_id", "1234567890");
request.addApiParameter("agree_cancel", "false");
request.addApiParameter("reason_code", "0");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
