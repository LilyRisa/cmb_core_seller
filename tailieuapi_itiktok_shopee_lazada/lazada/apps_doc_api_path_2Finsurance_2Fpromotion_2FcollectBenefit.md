# GET/POSTcollectBenefit

> Source: https://open.lazada.com/apps/doc/api?path=%2Finsurance%2Fpromotion%2FcollectBenefit
> API path: /insurance/promotion/collectBenefit
> Category: LazPay API
> Scraped: 2026-05-20T23:57:15.301Z

---

Latest update2025-07-26 09:19:17

1700

collectBenefit

GET/POST

/insurance/promotion/collectBenefit

No Authorization Required

Description:collect lazada marketplace benefit

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
| data | String | Yes | 主体信息 |
| userToken | String | Yes | userToken |
| serviceName | String | Yes | serviceName |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| trace\_id | String | trace |
| resultCode | Number | resultCode |
| resultMessage | String | resultMessage |
| data | String | data |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/insurance/promotion/collectBenefit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/insurance/promotion/collectBenefit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/insurance/promotion/collectBenefit");
request.addApiParameter("data", "{\"appInfo\":{\"appKey\":\"23867946\",\"appName\":\"lazada\",\"country\":\"my\",\"language\":\"my\",\"platform\":\"android\",\"ttid\":\"600000@lazada_android_7.77.0\",\"userAgent\":\"MTOPSDK/3.1.1.7 (Android;10;vivo;vivo 1938)\",\"utdid\":\"aFIq9ukFbEoDADlfH5MPHSPU\"},\"benefitCollectList\":[{\"benefitIdList\":[1186054,1186053],\"bizParams\":{\"benefit2VoucherPool\":\"{}\",\"exposureId\":\"76746c9a680b4957abfc866c06aad28a\",\"benefitPoolToken\":\"xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx\"},\"bizType\":\"VULCAN\"}],\"source\":\"INSURANCE\",\"tagUnionId\":\"133924101401\"}");
request.addApiParameter("userToken", "Fz3wv11yBDtyhVK0vjHHGA==");
request.addApiParameter("serviceName", "marketplace");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "trace_id": "213c72e717502365733074305e39d1",
  "code": "0",
  "data": "data",
  "resultCode": "0",
  "resultMessage": "success",
  "request_id": "0ba2887315178178017221014"
}
```
