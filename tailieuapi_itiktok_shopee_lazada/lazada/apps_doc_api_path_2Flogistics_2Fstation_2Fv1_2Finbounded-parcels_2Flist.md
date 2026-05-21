# GETGetInboundedParcel

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Finbounded-parcels%2Flist
> API path: /logistics/station/v1/inbounded-parcels/list
> Category: Logistics Station API
> Scraped: 2026-05-21T00:17:05.203Z

---

Latest update2023-10-19 20:38:45

922

GetInboundedParcel

GET

/logistics/station/v1/inbounded-parcels/list

No Authorization Required

Description:Get a list of inbounded parcels by a list of tracking numbers. This API is used for checking the status of inbounded parcels such as parcels picked up by LEX, picked up by 3PL, or collected by a customer.

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
| trackingNumbers | String\[\] | Yes | List of tracking number |
| serviceType | String | Yes | Accept values: SELLER\_DROPOFF, CUSTOMER\_DROPOFF (Customer return), CUSTOMER\_COLLECTION (Collection point) |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Object\[\] | Response data |
| trackingNumber | String | Tracking number |
| cageNumber | String | Cage number |
| pickupTplSlug | String | \[SELLER\_DROPOFF/CUSTOMER-DROPOFF\] Pickup 3PL slug: lex-th for regular parcels, another 3PL in case MPU |
| lastmileTpl | String | \[CUSTOMER\_COLLECTION\] Lastmile 3PL name: LEX TH, LEX VN, etc |
| warningMessage | String | \[CUSTOMER\_COLLECTION\] Warning message in case parcel is SLA breached |
| serviceType | String | Service type |
| inboundedAt | String | Inbounded at timestamp in milliseconds |
| outboundedAt | String | Outbounded at timestamp in milliseconds |
| lostAt | String | Lost at timestamp in milliseconds |
| status | String | Status of parcel: with\_dop\_waiting\_for\_pickup (DOP status, inbound success), pickup\_successful (DOP status, picked-up by LEX), auto\_closure (DOP status, picked-up by other 3PL), missing (lost), waiting\_for\_customer\_to\_collect (CP status, inbound success), customer\_collected (CP status, collected by customer), customer\_rejected (CP status, rejected by customer), cp\_parcel\_expired (CP status, expired after some days without customer collect), cp\_waiting\_for\_pickup (CP status, waiting driver to collect expired/rejected parcel), cp\_handover\_to\_3pl (CP status, handed over expired/rejected parcel to driver) |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/inbounded-parcels/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/logistics/station/v1/inbounded-parcels/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/inbounded-parcels/list");
request.setHttpMethod("GET");
request.addApiParameter("stationId", "1234");
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
  "data": [
    {
      "serviceType": "SELLER_DROPOFF",
      "cageNumber": "123",
      "inboundedAt": "1686327372000",
      "outboundedAt": "1686327372000",
      "warningMessage": "TEST1231124VN is identified as SLA Breach, not allow delivering to customer. Please request 3PL to pick it up",
      "lostAt": "1686327372000",
      "trackingNumber": "TEST1231124VN",
      "pickupTplSlug": "lex-th",
      "lastmileTpl": "LEX TH",
      "status": "with_dop_waiting_for_pickup"
    }
  ],
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
