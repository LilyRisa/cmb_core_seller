# GET/POSTmodifyAutoTopUpOptionOneConfig

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fwallet%2FmodifyAutoTopUpOptionOneConfig
> API path: /sponsor/solutions/wallet/modifyAutoTopUpOptionOneConfig
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:07:08.796Z

---

Latest update2026-05-21 08:07:02

500

modifyAutoTopUpOptionOneConfig

GET/POST

/sponsor/solutions/wallet/modifyAutoTopUpOptionOneConfig

Authorization Required

Description:Modify auto top up option one config.1. each country has differect tax rate 2. we have minimum and maximam top-up amount limitation.For SG, min=5, max = 8,333,333,330;for PH, min=100,Max=17,895,600;for TH, min=100,max=8,333,333,300;for VN, min=50,000,max=833,333,300,000;for MY,min=10,max=8,333,333,330;for ID,min=25,000,max=8,333,333,000.the api timeout is 3s, max qps is 100, make sure do not over these num, especially qps, otherwise you may be blacklisted or limited request count for a while.

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
| status | Number | Yes | The option one status.1:ON;0:OFF. |
| limitAmount | String | Yes | If balance is lower than this value, auto topUp operation will be done. |
| topupAmount | String | Yes | The amount of topUp for each auto topUp. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Boolean | The detail result, for this api is boolean. |
| success | Boolean | System result for this api call. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/wallet/modifyAutoTopUpOptionOneConfig)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/wallet/modifyAutoTopUpOptionOneConfig

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/wallet/modifyAutoTopUpOptionOneConfig");
request.addApiParameter("status", "1");
request.addApiParameter("limitAmount", "1000");
request.addApiParameter("topupAmount", "1000");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": "true",
  "code": "0",
  "success": "true",
  "analyseTraceId": "-",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid param"
}
```
