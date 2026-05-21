# GET/POSTStationDopScan

> Source: https://open.lazada.com/apps/doc/api?path=%2Fstations%2Fdop%2Fscan
> API path: /stations/dop/scan
> Category: Logistics API
> Scraped: 2026-05-20T23:29:06.219Z

---

Latest update2023-04-03 13:51:17

3477

StationDopScan

GET/POST

/stations/dop/scan

No Authorization Required

Description:StationDopScan

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
| cageNumber | String | Yes | test |
| trackingNumber | String | Yes | test |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | test |
| data | Object | test |
| trackingNumber | String | test |
| error | Object | test |
| errorCode | String | test |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/stations/dop/scan)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/stations/dop/scan

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/stations/dop/scan");
request.addApiParameter("cageNumber", "CASE1");
request.addApiParameter("trackingNumber", "TRACKING1");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "trackingNumber": "TRACKING1"
  },
  "success": "true",
  "error": {
    "errorCode": "ERROR"
  },
  "request_id": "0ba2887315178178017221014"
}
```
