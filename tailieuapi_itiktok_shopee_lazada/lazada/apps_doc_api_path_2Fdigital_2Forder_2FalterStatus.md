# GET/POSTDigitalAlterOrderStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Forder%2FalterStatus
> API path: /digital/order/alterStatus
> Category: LazPay API
> Scraped: 2026-05-20T23:54:39.621Z

---

Latest update2024-10-04 13:26:00

1788

DigitalAlterOrderStatus

GET/POST

/digital/order/alterStatus

No Authorization Required

Description:Change Lazada Digital Order Status

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
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| requestId | String | Yes | Reuqest id. |
| transactionId | Number | Yes | Third Party's orderId. |
| sellerId | Number | No | Seller id. |
| cancelCode | Number | No | If not null, then will do alarm in DG. |
| cancelMsg | String | No | Sent with the cancelCode. |
| userToken | String | Yes | Lazada user token. |
| serviceName | String | Yes | Lazada user token. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| traceId | String | Lazada traceId. |
| transactionId | Number | Third Party's orderId. |
| orderStatus | String | If have, then order final status. |
| paymentStatus | String | Lazada order payment status. |
| resultCode | Number | Result code from Lazada. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/order/alterStatus)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/order/alterStatus

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/order/alterStatus");
request.addApiParameter("requestId", "1234");
request.addApiParameter("transactionId", "1234");
request.addApiParameter("sellerId", "1234");
request.addApiParameter("cancelCode", "1234");
request.addApiParameter("cancelMsg", "1234");
request.addApiParameter("userToken", "6gaQ5mBV7lHiw1vI0IqhEw==");
request.addApiParameter("serviceName", "agaming");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "traceId": "212cd8df17270744623036160ef6c2",
  "code": "0",
  "resultCode": "0",
  "orderStatus": "delivered",
  "request_id": "0ba2887315178178017221014",
  "transactionId": "1234",
  "paymentStatus": "orderPaid"
}
```
