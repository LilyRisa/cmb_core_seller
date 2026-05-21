# GET/POSTInsuranceCreateOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Forder%2Fcreate
> API path: /insurance/order/create
> Category: LazPay API
> Scraped: 2026-05-20T23:55:48.981Z

---

Latest update2024-08-06 12:49:49

1843

InsuranceCreateOrder

GET/POST

/insurance/order/create

No Authorization Required

Description:Lazada Insurance Create Order

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
| requestId | String | Yes | Request ID, unique for each request.aRequest ID, unique for each request.Fusion's product ID. |
| productCode | String | Yes | Fusion's product ID. |
| itemPrice | Number | Yes | Price that user need to pay. (Totally price) |
| sstFee | Number | Yes | SST amount. |
| stampDuty | Number | Yes | Stamp Duty amont. |
| currency | String | Yes | Currency Type. |
| transactionId | Number | Yes | Fusion's order ID. |
| sellerId | Number | No | Seller ID. |
| serviceName | String | Yes | Service name. |
| userToken | String | Yes | Token for Lazada User. |
| orderExistTime | String | No | Lazada order persit time. |
| subProductCode | String | No | Road tax's product code. |
| subItemPrice | String | No | Road tax's item price. (Totally price) |
| subServiceFee | String | No | Road tax's service fee. |
| subTransactionId | String | No | Road tax's transactionId. |
| insuranceType | String | No | Marketplace insurance type. |
| partnerCode | String | No | Traffic source. |
| plateNo | String | No | Car plate no. |
| planCode | String | No | planCode |
| subPlanCode | String | No | subPlanCode |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| tradeOrderLineId | String | Lazada tradeOrderLine ID. |
| transactionId | Number | Fusion's order ID. |
| paymentLink | String | Lazada Independent Paymen Link. |
| resultCode | Number | Result code from Lazada. |
| traceId | String | Lazada traceId. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/order/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/order/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/order/create");
request.addApiParameter("requestId", "1234");
request.addApiParameter("productCode", "LZD202408071");
request.addApiParameter("itemPrice", "100");
request.addApiParameter("sstFee", "3");
request.addApiParameter("stampDuty", "1");
request.addApiParameter("currency", "MYR");
request.addApiParameter("transactionId", "202408071");
request.addApiParameter("sellerId", "1234");
request.addApiParameter("serviceName", "marketplace");
request.addApiParameter("userToken", "gQk/8THS7TSQlVj42JP1lg==");
request.addApiParameter("orderExistTime", "18000000");
request.addApiParameter("subProductCode", "LZD202408072");
request.addApiParameter("subItemPrice", "10");
request.addApiParameter("subServiceFee", "5");
request.addApiParameter("subTransactionId", "202408072");
request.addApiParameter("insuranceType", "car_insurance");
request.addApiParameter("partnerCode", "DG_HP");
request.addApiParameter("plateNo", "EF12345");
request.addApiParameter("planCode", "EF12345");
request.addApiParameter("subPlanCode", "EF12345");
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
  "paymentLink": "https://www.lazada.com",
  "request_id": "0ba2887315178178017221014",
  "tradeOrderLineId": "1234",
  "transactionId": "1234"
}
```
