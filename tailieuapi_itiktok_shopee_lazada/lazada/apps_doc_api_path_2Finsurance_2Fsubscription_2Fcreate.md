# POSTCreateSubscriptionToFusion

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Fsubscription%2Fcreate
> API path: /insurance/subscription/create
> Category: LazPay API
> Scraped: 2026-05-20T23:54:01.122Z

---

Latest update2025-10-24 13:57:33

1706

CreateSubscriptionToFusion

POST

/insurance/subscription/create

No Authorization Required

Description:Create User Subscription To Fusion

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
| subscriptionStatus | String | Yes | Subscription Status |
| subscribeTime | Number | No | Subscribe Time |
| unsubscribeTime | Number | No | Unsubscribe Time |
| subscribeSource | String | No | Subscribe Source |
| unsubscribeSource | String | No | Unsubscribe Source |
| userToken | String | Yes | User Id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| subscriptionStatus | String | Subscription Status |
| subscribeTime | Number | Subscribe Time |
| unsubscribeTime | Number | Unsubscribe Time |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/subscription/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/insurance/subscription/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/subscription/create");
request.addApiParameter("subscriptionStatus", "SUBSCRIBED");
request.addApiParameter("subscribeTime", "1760075913214");
request.addApiParameter("unsubscribeTime", "1760075913214");
request.addApiParameter("subscribeSource", "NCD");
request.addApiParameter("unsubscribeSource", "NCD");
request.addApiParameter("userToken", "4095214361000");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "subscribeTime": "1760075913214",
  "unsubscribeTime": "1760075913214",
  "subscriptionStatus": "SUBSCRIBED",
  "request_id": "0ba2887315178178017221014"
}
```
