# GET/POSTInuranceNotifyLapse

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Finsurance%2Fnotificationlapse
> API path: /digital/insurance/notificationlapse
> Category: Lazada DG API
> Scraped: 2026-05-21T00:02:33.843Z

---

Latest update2023-10-17 11:09:04

1873

InuranceNotifyLapse

GET/POST

/digital/insurance/notificationlapse

Authorization Required

Description:Insurance company push the callback notification to partners once the policy has been cancelled successfully

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
| orderNo | String | Yes | 1234 |
| thirdOrderNo | String | Yes | 12344 |
| policyNo | String | Yes | 1234 |
| lapseTime | String | Yes | 1234 |
| lapseType | String | Yes | enum： expiration: policy expired. end: the customer has used up the sum insured amount, policy end. |
| message | String | No | expire |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| transactionId | String | LZD orderLineId |
| extendInfo | String | extendInfo |
| errorCode | String | result code |
| errorMsg | String | result message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/insurance/notificationlapse)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/digital/insurance/notificationlapse

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/insurance/notificationlapse");
request.addApiParameter("orderNo", "1234");
request.addApiParameter("thirdOrderNo", "1234");
request.addApiParameter("policyNo", "1234");
request.addApiParameter("lapseTime", "1234");
request.addApiParameter("lapseType", "enum\uFF1A expiration: policy expired. end: the customer has used up the sum insured amount, policy end.");
request.addApiParameter("message", "expire");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "errorCode": "result code",
  "extendInfo": "{\"xxx\":\"xxx\"}",
  "request_id": "0ba2887315178178017221014",
  "transactionId": "123\t ",
  "errorMsg": "success"
}
```
