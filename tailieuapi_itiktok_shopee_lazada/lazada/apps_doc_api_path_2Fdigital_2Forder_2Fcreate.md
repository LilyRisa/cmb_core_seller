# GET/POSTDigitalCreateOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Forder%2Fcreate
> API path: /digital/order/create
> Category: LazPay API
> Scraped: 2026-05-20T23:54:54.645Z

---

Latest update2024-09-26 14:06:42

1917

DigitalCreateOrder

GET/POST

/digital/order/create

No Authorization Required

Description:Create Digital Virtual Order

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
| requestId | String | Yes | Request id. |
| itemPrice | Number | Yes | Item price. |
| currency | String | Yes | Currency. |
| transactionId | Number | Yes | Third party's transactionId. |
| sellerId | Number | No | Seller id. |
| userToken | String | Yes | Token for Lazada User. |
| serviceName | String | Yes | Service name. |
| skuId | Number | Yes | Lazada sku id. |
| itemId | Number | Yes | Lazada item id. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| transactionId | Number | Third party's transactionId. |
| paymentLink | String | PaymentLink. |
| resultCode | Number | ResultCode. |
| tradeOrderLineId | String | Lazada's tradeOrderLine id. |
| traceId | String | Lazada's traceId. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/order/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/order/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/order/create");
request.addApiParameter("requestId", "1234");
request.addApiParameter("itemPrice", "100");
request.addApiParameter("currency", "IDR");
request.addApiParameter("transactionId", "1234");
request.addApiParameter("sellerId", "1234");
request.addApiParameter("userToken", "6gaQ5mBV7lHiw1vI0IqhEw==");
request.addApiParameter("serviceName", "agaming");
request.addApiParameter("skuId", "14664382001");
request.addApiParameter("itemId", "8255386001");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "traceId": "1234",
  "code": "0",
  "resultCode": "0",
  "paymentLink": "1234",
  "request_id": "0ba2887315178178017221014",
  "transactionId": "1234",
  "tradeOrderLineId": "1234"
}
```
