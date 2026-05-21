# GET/POSTredeemMpVoucher

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Fvoucher%2FredeemVoucher
> API path: /insurance/voucher/redeemVoucher
> Category: LazPay API
> Scraped: 2026-05-20T23:58:08.548Z

---

Latest update2026-05-21 07:57:55

500

redeemMpVoucher

GET/POST

/insurance/voucher/redeemVoucher

No Authorization Required

Description:商城险域外voucher核销

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
| voucherCode | String | Yes | voucherCode |
| userToken | String | Yes | userToken |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| voucherTemplateId | String | voucherTemplateId |
| traceId | String | traceId |
| resultCode | String | resultCode |
| resultMessage | String | resultMessage |
| brokerName | String | MSIG |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/voucher/redeemVoucher)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/voucher/redeemVoucher

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/voucher/redeemVoucher");
request.addApiParameter("voucherCode", "LAZADA");
request.addApiParameter("userToken", "/AvNEJRIZ9sJkpCWvz65Xg==");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "traceId": "213c72e717502365733074305e39d1",
  "code": "0",
  "resultCode": "0",
  "resultMessage": "success",
  "brokerName": "MSIG",
  "request_id": "0ba2887315178178017221014",
  "voucherTemplateId": "LAZIAAAA"
}
```
