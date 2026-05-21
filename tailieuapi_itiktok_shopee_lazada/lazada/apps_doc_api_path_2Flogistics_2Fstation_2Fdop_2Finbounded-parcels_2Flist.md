# GETDopGetInboundedParcel

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fstation%2Fdop%2Finbounded-parcels%2Flist
> API path: /logistics/station/dop/inbounded-parcels/list
> Category: Logistics Station API
> Scraped: 2026-05-21T00:16:27.236Z

---

Latest update2023-06-12 17:19:00

1228

DopGetInboundedParcel

GET

/logistics/station/dop/inbounded-parcels/list

No Authorization Required

Description:DOP get list scanned parcel

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
| trackingNumbers | String\[\] | Yes | List inbounded tracking number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| data | Object\[\] | Response data |
| trackingNumber | String | Tracking number |
| cageNumber | String | Cage number |
| status | String | Status of parcel: with\_dop\_waiting\_for\_pickup (parcel is still in station), pickup\_successful (parcel is picked-up by lex), auto\_closure (parcel is picked-up by other 3PL), missing (parcel is marked lost) |
| inboundedAt | Number | Inbounded at timestamp in milliseconds |
| lostAt | Number | Lost at timestamp in milliseconds |
| pickupTplSlug | String | Pickup 3PL slug: lex-th for regular parcels, another 3PL in case MPU |
| outboundedAt | Number | Outbounded at timestamp in milliseconds |
| errorCode | String | Error code |
| errorMsg | String | Error message |
| traceId | String | Trace id for debugging |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| UNEXPECTED\_ERROR | NullpointerException | Mostly the stacktrace of unexpected error |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/station/dop/inbounded-parcels/list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/logistics/station/dop/inbounded-parcels/list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/station/dop/inbounded-parcels/list");
request.setHttpMethod("GET");
request.addApiParameter("stationCode", "STATION_123456");
request.addApiParameter("trackingNumbers", "[\"TEST1234VN\", \"TEST1235VN\"]");
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
      "cageNumber": "123",
      "inboundedAt": "1686327372000",
      "outboundedAt": "1686327372000",
      "lostAt": "1686327372000",
      "trackingNumber": "TEST1231124VN",
      "status": "with_dop_waiting_for_pickup",
      "pickupTplSlug": "lex-th"
    }
  ],
  "success": "true",
  "errorCode": "CAGE_NOT_FOUND",
  "request_id": "0ba2887315178178017221014",
  "errorMsg": "Cage 123 is not found"
}
```
