# POSTDopConfirmInbound

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fdop%2Fconfirm-inbound
> API path: /logistics/station/dop/confirm-inbound
> Category: Logistics Station API
> Scraped: 2026-05-21T00:15:51.784Z

---

Latest update2023-06-12 16:48:25

1276

DopConfirmInbound

POST

/logistics/station/dop/confirm-inbound

No Authorization Required

Description:DOP confirm inbound

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
| stationCode | String | Yes | Station code/ID |
| scannedParcels | Object\[\] | Yes | List scanned parcels |
| cageNumber | String | Yes | Cage number |
| trackingNumber | String | Yes | Tracking number of parcel |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | String | Confirm inbound success or not? |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| DOP\_RESERVED\_PARCEL\_NOT\_FOUND | No parcel info for tracking number {trackingNumber}. Please scan again or manually input the tracking number. | Cannot find parcel info with provided tracking number |
| CAGE\_NOT\_FOUND | Cage not found: {cageNumber} | Cage not found |
| STATION\_NOT\_ACTIVE | Station \[{stationCode}\] is not active | Station is not active |
| PARCEL\_ALREADY\_INBOUND | Parcel has already inbounded: {trackingNumber} | Parcel has already inbounded |
| STATION\_IS\_NOT\_DOP | Station {stationCode} is not a DOP. You can not drop-off here. | Station is not DOP type |
| DOP\_PARCEL\_STATUS\_NOT\_WHITELIST | Parcel is not at correct status to dropoff, parcel {trackingNumber} is now {status} | Invalid status to inbound |
| CANNOT\_INBOUND\_CANCELLED\_TASK | Tracking number {trackingNumber} is cancelled. Please remove out of list | Parcel is cancelled |
| DOP\_MERCHANT\_MDOP | Seller is a MDOP, your parcel cannot be dropped-off to any station. DOP Merchant={sellerName}, and TN={trackingNumber} | The seller of the parcel is MDOP |
| DUPLICATE\_REQUEST | Your request is processing | Client submit duplicate request at the same time |
| UNEXPECTED\_ERROR | NullpointerException | Mostly the stacktrace of unexpected error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/dop/confirm-inbound)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/station/dop/confirm-inbound

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/dop/confirm-inbound");
request.addApiParameter("stationCode", "STATION_123456");
request.addApiParameter("scannedParcels", "[{\"cageNumber\":\"123\",\"trackingNumber\":\"TEST1231124VN\"}]");
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
