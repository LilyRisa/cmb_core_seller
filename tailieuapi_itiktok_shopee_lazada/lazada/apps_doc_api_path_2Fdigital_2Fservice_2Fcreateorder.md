# GET/POSTDGUtiityPreCreateOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Fservice%2Fcreateorder
> API path: /digital/service/createorder
> Category: LazPay API
> Scraped: 2026-05-20T23:54:12.406Z

---

Latest update2023-02-06 20:42:52

2608

DGUtiityPreCreateOrder

GET/POST

/digital/service/createorder

No Authorization Required

Description:This API provides an open interface for partner users to create DG orders

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
| miniToken | String | Yes | mini token |
| miniappId | String | Yes | minapp id |
| paymentRequestId | String | Yes | partner order id |
| extendInfo | String | No | extend message |
| signature | String | No | md5 signture |
| value | String | Yes | price |
| currency | String | Yes | currency |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | true or false |
| resultCode | String | result code |
| resultMsg | String | result message |
| tradeNo | String | trade no |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 00 | sucess | sueccss |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/service/createorder)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/service/createorder

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/service/createorder");
request.addApiParameter("miniToken", "123456");
request.addApiParameter("miniappId", "123456");
request.addApiParameter("paymentRequestId", "888888");
request.addApiParameter("extendInfo", "{\"123\":\"123\"}");
request.addApiParameter("signature", "hgfjpodmnvlirer");
request.addApiParameter("value", "100");
request.addApiParameter("currency", "PHP");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "tradeNo": "123456",
  "success": "true",
  "resultCode": "00",
  "request_id": "0ba2887315178178017221014",
  "resultMsg": "success"
}
```
