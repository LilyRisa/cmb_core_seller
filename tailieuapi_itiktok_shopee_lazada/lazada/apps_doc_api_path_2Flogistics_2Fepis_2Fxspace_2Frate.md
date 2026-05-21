# GET/POSTEpisXspaceRateTicket

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fxspace%2Frate
> API path: /logistics/epis/xspace/rate
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:51.884Z

---

Latest update2025-08-07 18:45:00

1726

EpisXspaceRateTicket

GET/POST

/logistics/epis/xspace/rate

No Authorization Required

Description:Rate Xspace ticket

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
| platformName | String | Yes | platformName |
| externalSellerId | String | Yes | externalSellerId |
| caseId | Number | Yes | caseId |
| ratingStar | Number | Yes | ratingStar |
| ratingReasons | String\[\] | No | ratingReasons |
| ratingRemark | String | No | ratingRemark |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | retryable |
| success | Boolean | success |
| traceId | String | traceId |
| errorCode | String | errorCode |
| errorMessage | String | errorMessage |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/xspace/rate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/epis/xspace/rate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/xspace/rate");
request.addApiParameter("platformName", "OneLink");
request.addApiParameter("externalSellerId", "109001");
request.addApiParameter("caseId", "2500000152175706");
request.addApiParameter("ratingStar", "5");
request.addApiParameter("ratingReasons", "[\"REASON_1\"]");
request.addApiParameter("ratingRemark", "remark ");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "traceId": "traceId",
  "code": "0",
  "success": "true",
  "errorMessage": "errorMessage",
  "errorCode": "errorCode",
  "request_id": "0ba2887315178178017221014"
}
```
