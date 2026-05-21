# GETSearchCustomerReturnParcel

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fv1%2Fdop%2Fcr-parcels%2Fsearch
> API path: /logistics/station/v1/dop/cr-parcels/search
> Category: Logistics Station API
> Scraped: 2026-05-21T00:17:55.354Z

---

Latest update2023-10-31 11:43:14

933

SearchCustomerReturnParcel

GET

/logistics/station/v1/dop/cr-parcels/search

No Authorization Required

Description:Search customer return parcel by at least 4 letters text. This API is to improve user experience, user can search for the tracking number instead of typing the full tracking number.

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
| searchText | String | Yes | Search tracking number text at least 4 letters |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Object\[\] | Response data |
| trackingNumber | String | Tracking number of parcel |
| maskedCustomerName | String | The masked customer name. For example: customer name "John Wick" will be masked to "J\*\*\*\*Wick" |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/v1/dop/cr-parcels/search)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/logistics/station/v1/dop/cr-parcels/search

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/v1/dop/cr-parcels/search");
request.setHttpMethod("GET");
request.addApiParameter("stationId", "1234");
request.addApiParameter("searchText", "TEST12");
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
      "maskedCustomerName": "J****Wick",
      "trackingNumber": "TEST1234VN"
    }
  ],
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
