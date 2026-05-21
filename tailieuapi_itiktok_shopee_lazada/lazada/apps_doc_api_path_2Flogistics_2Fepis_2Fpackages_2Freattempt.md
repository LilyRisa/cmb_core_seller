# POSTEpisPackageReAttempt

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fpackages%2Freattempt
> API path: /logistics/epis/packages/reattempt
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:49:37.881Z

---

Latest update2025-01-17 18:53:47

1845

EpisPackageReAttempt

POST

/logistics/epis/packages/reattempt

No Authorization Required

Description:Send re-attempt package request

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
| packageCode | String | Yes | Package code |
| reAttemptDateTime | Number | No | Re attempt time |
| sellerNote | String | No | Seller note |
| feedbackType | String | Yes | REATTEMPT or RETURN |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| traceId | String | trace id for debug |
| success | String | is success? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/packages/reattempt)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/epis/packages/reattempt

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/packages/reattempt");
request.addApiParameter("packageCode", "FU2520016900000000000005515757120");
request.addApiParameter("reAttemptDateTime", "1699231200000");
request.addApiParameter("sellerNote", "Seller note");
request.addApiParameter("feedbackType", "REATTEMPT");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "0ba2887315172940728551014",
  "code": "0",
  "success": "true",
  "errorMessage": "Bad request",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014"
}
```
