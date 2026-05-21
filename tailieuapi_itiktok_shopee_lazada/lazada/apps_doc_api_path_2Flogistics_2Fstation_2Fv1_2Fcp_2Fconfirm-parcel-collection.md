# POSTConfirmParcelCollection

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Fcp%2Fconfirm-parcel-collection
> API path: /logistics/station/v1/cp/confirm-parcel-collection
> Category: Logistics Station API
> Scraped: 2026-05-21T00:15:16.280Z

---

Latest update2023-10-19 17:22:34

919

ConfirmParcelCollection

POST

/logistics/station/v1/cp/confirm-parcel-collection

No Authorization Required

Description:Confirm customer collects or rejects parcel. This API is used after ValidateOTP success.

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
| action | String | Yes | Accept values: COLLECT, REJECT |
| rejectCode | String | No | Reject reason code, required in case REJECT action |
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
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/cp/confirm-parcel-collection)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/station/v1/cp/confirm-parcel-collection

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/cp/confirm-parcel-collection");
request.addApiParameter("stationId", "1234");
request.addApiParameter("trackingNumber", "TEST1231124VN");
request.addApiParameter("otp", "123456");
request.addApiParameter("action", "COLLECT");
request.addApiParameter("rejectCode", "wrong_product");
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
