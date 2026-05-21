# POSTConfirmInbound

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Fconfirm-inbound
> API path: /logistics/station/v1/confirm-inbound
> Category: Logistics Station API
> Scraped: 2026-05-21T00:15:00.205Z

---

Latest update2023-10-27 15:30:49

957

ConfirmInbound

POST

/logistics/station/v1/confirm-inbound

No Authorization Required

Description:Confirm inbound. Call this API to inbound the scanned parcel and finish the inbound process

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
| cageNumber | String | No | Cage number. If cage number is present, it will be validated. In case missing cage number, the system will choose default cage number |
| trackingNumbers | String\[\] | Yes | List of tracking number |
| serviceType | String | Yes | Accept values: SELLER\_DROPOFF, CUSTOMER\_DROPOFF (Customer return), CUSTOMER\_COLLECTION (Collection point) |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Boolean | Response data |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/confirm-inbound)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/station/v1/confirm-inbound

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/confirm-inbound");
request.addApiParameter("stationId", "1234");
request.addApiParameter("cageNumber", "123");
request.addApiParameter("trackingNumbers", "[\"TEST1231124VN\", \"TEST1231125VN\"]");
request.addApiParameter("serviceType", "SELLER_DROPOFF");
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
  "data": "Confirm inbound success or not?",
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
