# GETGetScannedParcel

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Fscanned-parcels%2Flist
> API path: /logistics/station/v1/scanned-parcels/list
> Category: Logistics Station API
> Scraped: 2026-05-21T00:17:39.369Z

---

Latest update2023-10-19 20:38:49

913

GetScannedParcel

GET

/logistics/station/v1/scanned-parcels/list

No Authorization Required

Description:Get a list of scanned parcels. This API is often used for synchronization purposes such as: user refreshes the page, partner system can call this API to get the list of scanned parcels again. This API is not required to call during operations.

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
| cageNumber | String | No | Cage number |
| serviceType | String | Yes | Accept values: SELLER\_DROPOFF, CUSTOMER\_DROPOFF (Customer return), CUSTOMER\_COLLECTION (Collection point) |
| stationId | String | Yes | Station ID in partner system |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Object\[\] | Response data |
| trackingNumber | String | Tracking number |
| cageNumber | String | Cage number |
| sellerName | String | \[SELLER\_DROPOFF/CUSTOMER-DROPOFF\] Seller name |
| pickupTplSlug | String | \[SELLER\_DROPOFF/CUSTOMER-DROPOFF\] Pickup 3PL slug: lex-th for regular parcels, another 3PL in case MPU |
| createdAt | Number | Created at timestamp in milliseconds |
| lastmileTpl | String | \[CUSTOMER\_COLLECTION\] Lastmile 3PL name: LEX TH, LEX VN, etc |
| warningMessage | String | \[CUSTOMER\_COLLECTION\] Warning message in case parcel is SLA breached |
| serviceType | String | Service type |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/scanned-parcels/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/logistics/station/v1/scanned-parcels/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/scanned-parcels/list");
request.setHttpMethod("GET");
request.addApiParameter("cageNumber", "123");
request.addApiParameter("serviceType", "SELLER_DROPOFF");
request.addApiParameter("stationId", "1234");
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
      "createdAt": "1686327372000",
      "cageNumber": "123",
      "sellerName": "Alpha.hardware",
      "warningMessage": "TEST1231124VN is identified as SLA Breach, not allow delivering to customer. Please request 3PL to pick it up.",
      "trackingNumber": "TEST1231124VN",
      "pickupTplSlug": "lex-th",
      "lastmileTpl": "LEX TH"
    }
  ],
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
