# UNKNOWNLazPayPaymentNotify

> Source: https://open.lazada.com/apps/doc/api?path=%2Flazpay%2Fv1%2Fpayment%2Fnotify
> API path: /lazpay/v1/payment/notify
> Category: LazPay API
> Scraped: 2026-05-20T23:56:11.959Z

---

Latest update2022-07-18 12:08:29

2639

LazPayPaymentNotify

UNKNOWN

/lazpay/v1/payment/notify

No Authorization Required

Description:Payment Notify

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
| paymentRequestId | String | Yes | paymentRequestId |
| paymentId | String | Yes | paymentId |
| paymentAmount | String | Yes | paymentAmount |
| paymentStatus | String | Yes | paymentStatus |
| paymentApplyTime | Number | Yes | paymentApplyTime |
| paymentFinishTime | Number | No | paymentFinishTime |
| productCode | String | No | productCode |
| merchantInfo | String | No | merchantInfo |
| promotionInfo | String | No | promotionInfo |
| userPaymentAmount | String | No | userPaymentAmount |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| resultStatus | String | resultStatus |
| resultCode | String | resultCode |
| resultMessage | String | resultMessage |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/lazpay/v1/payment/notify)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

UNKNOWN

/lazpay/v1/payment/notify

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/lazpay/v1/payment/notify");
request.addApiParameter("paymentRequestId", "TEST209903900");
request.addApiParameter("paymentId", "20990390032220209000280158010");
request.addApiParameter("paymentAmount", "{\"amount\": \"113\", \"currency\": \"THB\"     }");
request.addApiParameter("paymentStatus", "SUCCESS");
request.addApiParameter("paymentApplyTime", "1646665620233");
request.addApiParameter("paymentFinishTime", "1646665620233");
request.addApiParameter("productCode", "productCode");
request.addApiParameter("merchantInfo", "merchantInfo");
request.addApiParameter("promotionInfo", "promotionInfo");
request.addApiParameter("userPaymentAmount", "userPaymentAmount");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "resultStatus": "resultStatus",
    "resultCode": "resultCode",
    "resultMessage": "resultMessage"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
