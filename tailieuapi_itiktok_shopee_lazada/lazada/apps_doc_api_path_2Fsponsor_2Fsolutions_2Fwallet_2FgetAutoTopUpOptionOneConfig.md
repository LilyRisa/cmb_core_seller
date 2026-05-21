# GET/POSTgetAutoTopUpOptionOneConfig

> Source: https://open.lazada.com/apps/doc/api?path=%2Fsponsor%2Fsolutions%2Fwallet%2FgetAutoTopUpOptionOneConfig
> API path: /sponsor/solutions/wallet/getAutoTopUpOptionOneConfig
> Category: Sponsored Solutions API
> Scraped: 2026-05-21T00:04:24.797Z

---

Latest update2026-05-21 08:04:11

500

getAutoTopUpOptionOneConfig

GET/POST

/sponsor/solutions/wallet/getAutoTopUpOptionOneConfig

Authorization Required

Description:Get auto top up option one config.

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
| No Data |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | The detail result, for this api is configuration. |
| status | Number | The option one status.1:ON;0:OFF. |
| limitAmount | String | If balance is lower than this value, auto topUp operation will be done. |
| topUpAmount | String | The amount of topUp for each auto topUp. |
| success | Boolean | System result for this api call. |
| errorMsg | String | If the api call failed, this field will show the detail reason. |
| analyseTraceId | String | If the api call failed, you could find us with this. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/sponsor/solutions/wallet/getAutoTopUpOptionOneConfig)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/sponsor/solutions/wallet/getAutoTopUpOptionOneConfig

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/sponsor/solutions/wallet/getAutoTopUpOptionOneConfig");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "limitAmount": "100",
    "topUpAmount": "100",
    "status": "1"
  },
  "code": "0",
  "success": "true",
  "analyseTraceId": "-",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "invalid param"
}
```
