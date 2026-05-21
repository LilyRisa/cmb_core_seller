# GET/POSTInsuranceQueryOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Forder%2FgetStatus
> API path: /insurance/order/getStatus
> Category: LazPay API
> Scraped: 2026-05-20T23:56:04.292Z

---

Latest update2024-08-20 15:40:36

1768

InsuranceQueryOrder

GET/POST

/insurance/order/getStatus

No Authorization Required

Description:Query Lazada Insurance Order Status

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
| transactionId | Number | Yes | Fusion's transactionId. |
| sellerId | Number | No | Seller id. |
| serviceName | String | Yes | Service name. |
| userToken | String | Yes | Lazada user token. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| transactionId | Number | Fusion's transactionId. |
| orderStatus | String | If have, then order final status. |
| paymentStatus | String | Lazada order payment status |
| resultCode | Number | Result code from Lazada. |
| traceId | String | Lazada traceId. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/order/getStatus)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/order/getStatus

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/order/getStatus");
request.addApiParameter("requestId", "1234");
request.addApiParameter("transactionId", "1234");
request.addApiParameter("sellerId", "1234");
request.addApiParameter("serviceName", "marketplace");
request.addApiParameter("userToken", "1234");
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
