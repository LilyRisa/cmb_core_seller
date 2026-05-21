# UNKNOWNEpisXspaceTicketStatusUpdate

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fepis%2Fxspace%2Fticket%2Fstatus
> API path: /logistics/epis/xspace/ticket/status
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:50:59.521Z

---

Latest update2025-07-20 23:26:15

1744

EpisXspaceTicketStatusUpdate

UNKNOWN

/logistics/epis/xspace/ticket/status

No Authorization Required

Description:EPIS update xspace status to external

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
| caseId | String | Yes | caseId |
| subject | String | Yes | subject |
| description | String | No | description |
| status | String | Yes | status |
| processedTime | String | No | processedTime |
| lexMsgType | String | Yes | lexMsgType |
| externalSellerId | String | Yes | externalSellerId |
| eposSellerId | String | No | OneLinkAccountId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| retryable | Boolean | retryable |
| traceId | String | traceId |
| errorCode | String | errorCode |
| errorMessage | String | errorMessage |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/epis/xspace/ticket/status)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

UNKNOWN

/logistics/epis/xspace/ticket/status

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/epis/xspace/ticket/status");
request.addApiParameter("caseId", "2500000177197246");
request.addApiParameter("subject", "Hurry Delivery");
request.addApiParameter("description", "Support to delivery soon");
request.addApiParameter("status", "RESOLVED");
request.addApiParameter("processedTime", "1752822302000");
request.addApiParameter("lexMsgType", "xspaceTicketStatusUpdate");
request.addApiParameter("externalSellerId", "2400000347014");
request.addApiParameter("eposSellerId", "2400000347014");
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
  "errorMessage": "errorMessage",
  "errorCode": "errorCode",
  "request_id": "0ba2887315178178017221014"
}
```
