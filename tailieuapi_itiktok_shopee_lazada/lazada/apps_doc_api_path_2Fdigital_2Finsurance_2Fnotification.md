# GET/POSTInuranceNotication

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Finsurance%2Fnotification
> API path: /digital/insurance/notification
> Category: Lazada DG API
> Scraped: 2026-05-21T00:02:01.337Z

---

Latest update2026-05-21 08:01:48

500

InuranceNotication

GET/POST

/digital/insurance/notification

Authorization Required

Description:Third party insurance company callback interface

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
| orderNo | String | Yes | Insurance company order number |
| thirdOrderNo | String | Yes | lazada orderId |
| premium | String | Yes | premium |
| ePolicyLink | String | Yes | ePolicy Link |
| policyNo | String | Yes | Policy No |
| underwritingStatus | String | Yes | Order Status |
| underwritingReason | String | No | Order Message |
| expirationDate | String | Yes | expirationDate |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| errorCode | String | 错误码 |
| errorMsg | String | 错误信息 |
| transactionId | String | 交易Id |
| extendInfo | String | 拓展信息 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 11 | policyNo is empty | policyNo is empty |
| 12 | orderNo is empty | orderNo is empty |
| 13 | thirdOrderNo is empty | lazada orderId |
| 14 | ePolicyLink is empty | Insurance information link |
| 15 | underwritingStatus is empty | Insurance status |
| 16 | premium is empty | premium is empty |
| 21 | order processing | The order is already being processed |
| 00 | success | success |
| 99 | fail | fail |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/insurance/notification)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/insurance/notification

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/insurance/notification");
request.addApiParameter("orderNo", "12332323");
request.addApiParameter("thirdOrderNo", "43434123");
request.addApiParameter("premium", "1000");
request.addApiParameter("ePolicyLink", "https://xxxx.com/xxxx");
request.addApiParameter("policyNo", "1234343");
request.addApiParameter("underwritingStatus", "123");
request.addApiParameter("underwritingReason", "123");
request.addApiParameter("expirationDate", "expirationDate");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "errorCode": "123",
  "extendInfo": "123",
  "request_id": "0ba2887315178178017221014",
  "transactionId": "123",
  "errorMsg": "123"
}
```
