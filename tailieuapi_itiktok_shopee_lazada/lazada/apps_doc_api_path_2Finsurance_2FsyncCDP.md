# GET/POSTinsuranceRealTimeCDP

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2FsyncCDP
> API path: /insurance/syncCDP
> Category: LazPay API
> Scraped: 2026-05-20T23:57:25.821Z

---

Latest update2026-05-21 07:57:17

500

insuranceRealTimeCDP

GET/POST

/insurance/syncCDP

No Authorization Required

Description:用户完成操作后，实时更新CDP人群

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
| userToken | String | Yes | Token for Lazada User. |
| bizCode | String | Yes | business code |
| serviceName | String | Yes | business type |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | String | 接口成功=true，接口失败=true， 系统失败=false |
| resultCode | String | 业务成功=SUCCESS， 业务失败=SUCCESS ，系统失败=SYSTEM\_ERROR |
| resultMessage | String | 接口成功=Success ，接口失败=Success ，系统失败=System Error |
| data | Boolean | 业务成功=true，业务失败=false，系统失败=false |
| redirectUrl | String | 无 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/syncCDP)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/syncCDP

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/syncCDP");
request.addApiParameter("userToken", "gQk/8THS7TSQlVj42JP1lg==");
request.addApiParameter("bizCode", "NCD");
request.addApiParameter("serviceName", "marketplace");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "redirectUrl": "null",
  "code": "0",
  "data": "true",
  "success": "true",
  "resultCode": "SUCCESS",
  "resultMessage": "Success",
  "request_id": "0ba2887315178178017221014"
}
```
