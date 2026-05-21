# POSTAddOrUpdatePickupStop

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Ftps%2Frunsheets%2Fstops
> API path: /logistics/tps/runsheets/stops
> Category: Logistics API
> Scraped: 2026-05-20T23:28:12.320Z

---

Latest update2022-09-15 11:27:23

7351

AddOrUpdatePickupStop

POST

/logistics/tps/runsheets/stops

No Authorization Required

Description:3PL call TPS to update pickup stops

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
| stopId | String | Yes | Stop ID |
| sellerId | String | Yes | Seller ID (Sent in pickup request) |
| warehouseCode | String | Yes | Warehouse code (Sent in pickup request) |
| dopStationId | String | No | DOP station code |
| dopStationName | String | No | DOP station name |
| pickupType | String | Yes | Type: Pickup/Drop-off |
| status | String | Yes | 1\. planned: when stop is dispatched to courier\\n 2. arrived: when driver arrived at stop and start pickup\\n 3. finished: when driver finished pickup at the stop\\n 4. skipped: when driver selected to skip the stop due to some reason\\n 5. removed: when the stop has 0 RTS |
| statusUpdateTime | Number | Yes | actual process time when reaching the status |
| dispatcherName | String | No | Dispatcher name |
| dispatcherContact | String | No | Dispatcher phone number |
| driverId | String | No | Driver ID |
| driverName | String | Yes | Driver name |
| driverContact | String | No | Driver phone number |
| eta | Number | No | when the ETA is updated, need to update the data to Lazada side, scenario include: 1. without ETA >> with ETA 2. with ETA >> without ETA 3. ETA change from A to B |
| successVolume | String | No | Success count |
| failedVolume | String | No | Failed count |
| failedVolumeList | Object\[\] | No | Failed list |
| volume | Number | Yes | Failed count |
| reason | String | Yes | Failed reason |
| type | String | Yes | Type: Failed/Skipped |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| retryable | Boolean | Is failed request retryable? |
| success | Boolean | Is success? |
| errors | Object\[\] | Error detail |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| field | String | Error field |
| errorMessage | String | Error message |
| errorCode | String | Error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/tps/runsheets/stops)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/tps/runsheets/stops

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/tps/runsheets/stops");
request.addApiParameter("stopId", "Stop001");
request.addApiParameter("sellerId", "200165961111");
request.addApiParameter("warehouseCode", "dropshipping");
request.addApiParameter("dopStationId", "SSG");
request.addApiParameter("dopStationName", "Sai Gon");
request.addApiParameter("pickupType", "Pickup");
request.addApiParameter("status", "planned");
request.addApiParameter("statusUpdateTime", "1659439136265");
request.addApiParameter("dispatcherName", "Geralt");
request.addApiParameter("dispatcherContact", "+841231231123");
request.addApiParameter("driverId", "DriverX");
request.addApiParameter("driverName", "John Wick");
request.addApiParameter("driverContact", "+841231231124");
request.addApiParameter("eta", "1659439136265");
request.addApiParameter("successVolume", "10");
request.addApiParameter("failedVolume", "1");
request.addApiParameter("failedVolumeList", "[{\"volume\":\"1\",\"reason\":\"Seller closed\",\"type\":\"Failed\"}]");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "retryable": "false",
  "code": "0",
  "success": "true",
  "errorMessage": "traceId\u003d0b190023897207ea244",
  "errorCode": "BAD_REQUEST",
  "request_id": "0ba2887315178178017221014",
  "errors": [
    {
      "field": "$.name",
      "errorMessage": "$.name is missing",
      "errorCode": "INVALID_PARAMETER"
    }
  ]
}
```
