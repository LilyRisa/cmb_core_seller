# UNKNOWNMY - Pickupp removeJobFromManifest

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpublic%2Fintegrations%2Flazada%2Fcancel
> API path: /public/integrations/lazada/cancel
> Category: Lazada Logistics API
> Scraped: 2026-05-20T23:51:53.101Z

---

Latest update2022-10-27 09:32:03

2115

MY - Pickupp removeJobFromManifest

UNKNOWN

/public/integrations/lazada/cancel

No Authorization Required

Description:Remove job from manifest

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
| manifestId | Number | Yes | na |
| trackingNumber | String | Yes | trackingNumber |
| traceId | String | Yes | traceId |
| authToken | String | Yes | auth |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| success | Boolean | success or not |
| resCode | Number | 200 |
| resMessage | String | message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/public/integrations/lazada/cancel)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

UNKNOWN

/public/integrations/lazada/cancel

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/public/integrations/lazada/cancel");
request.addApiParameter("manifestId", "1");
request.addApiParameter("trackingNumber", "trackingNumber");
request.addApiParameter("traceId", "traceId");
request.addApiParameter("authToken", "auth");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "success": "true",
    "resCode": "200",
    "resMessage": "message"
  },
  "request_id": "0ba2887315178178017221014"
}
```
