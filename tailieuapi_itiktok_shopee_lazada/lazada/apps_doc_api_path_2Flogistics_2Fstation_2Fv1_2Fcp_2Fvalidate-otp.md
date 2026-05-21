# POSTValidateOTP

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Fcp%2Fvalidate-otp
> API path: /logistics/station/v1/cp/validate-otp
> Category: Logistics Station API
> Scraped: 2026-05-21T00:18:24.840Z

---

Latest update2023-10-19 20:38:51

858

ValidateOTP

POST

/logistics/station/v1/cp/validate-otp

No Authorization Required

Description:Validate if OTP of parcel is valid or not. This API is used for checking OTP before confirming collection.

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
| stationId | String | Yes | Station ID in partner system |
| trackingNumber | String | Yes | Tracking number of parcel |
| otp | String | Yes | The parcel OTP is used for collecting parcel |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Boolean | Validate OTP result is success or not? |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/cp/validate-otp)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/station/v1/cp/validate-otp

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/cp/validate-otp");
request.addApiParameter("stationId", "1234");
request.addApiParameter("trackingNumber", "TEST1231124VN");
request.addApiParameter("otp", "123456");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "traceId": "d2d9043316862098123051035df9da",
  "code": "0",
  "data": "true",
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
