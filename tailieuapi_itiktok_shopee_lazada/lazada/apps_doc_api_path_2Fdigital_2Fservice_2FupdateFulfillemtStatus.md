# GET/POSTDGUtilityPreUpdateFulfillemtStatus

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Fservice%2FupdateFulfillemtStatus
> API path: /digital/service/updateFulfillemtStatus
> Category: LazPay API
> Scraped: 2026-05-20T23:54:32.427Z

---

Latest update2023-02-27 14:57:21

2022

DGUtilityPreUpdateFulfillemtStatus

GET/POST

/digital/service/updateFulfillemtStatus

No Authorization Required

Description:update fulfillemt status

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
| miniappId | String | Yes | miniappId |
| signature | String | Yes | signature |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | true/false |
| resultCode | String | resultCode |
| resultMsg | String | resultMsg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/service/updateFulfillemtStatus)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/service/updateFulfillemtStatus

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/service/updateFulfillemtStatus");
request.addApiParameter("paymentRequestId", "123456");
request.addApiParameter("miniappId", "123456");
request.addApiParameter("signature", "123456");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "success": "true",
  "resultCode": "success",
  "request_id": "0ba2887315178178017221014",
  "resultMsg": "success"
}
```
