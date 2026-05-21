# POSTCreate3PLStation

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Ftps%2Fstations%2Fcreate
> API path: /logistics/tps/stations/create
> Category: Logistics API
> Scraped: 2026-05-20T23:28:27.780Z

---

Latest update2022-07-28 16:56:22

7157

Create3PLStation

POST

/logistics/tps/stations/create

No Authorization Required

Description:TPS\_CREATE\_STATION\_API External partner call TPS to create station

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
| externalCode | String | Yes | Station code in 3PL system |
| modifier | String | No | Modifier name. if blank will use 3PL name |
| name | String | Yes | Station name in 3PL system |
| functionCodes | String\[\] | Yes | Station functions |
| subTypes | String\[\] | Yes | Y Station subtypes (depends on function) enum: DOP function: MDOP, DOP, OTC,IDOP CP function: COLLECTION\_ON\_POINT, MOBILE\_COLLECTION\_POINT, LOCKER Return function: CUSTOMER\_RETURN |
| codSupport | Boolean | Yes | Support COD or not |
| age | Number | No | Number of days the station can keep packages for (used by LOP station tool). If not withdrawn by the customer within the age value, the package will be picked up from the station by a dedicated 3PL and brought to the warehouse. The package will be marked as failed delivery. Unit: Days |
| firstMileTplSlugs | String\[\] | Yes | Which 3PL can go and pick up the seller dropped-off parcel from the station |
| lastMileTplSlugs | String\[\] | Yes | This is a list of logistics providers which can deliver packages to this station. |
| contact | Object | Yes | Station contact information |
| name | String | Yes | Contact name |
| phone | String | Yes | Contact phone |
| email | String | No | Contact email |
| address | Object | Yes | Station address |
| id | String | Yes | Lazada R code address id |
| details | String | Yes | Address details |
| latitude | String | Yes | Latitude (At most 6 decimal digits) |
| longitude | String | Yes | Longitude (At most 6 decimal digits) |
| timeZone | String | No | Timezone (used to calculate the schedules) If not specified, use default country timezone format: (+/-)XX:XX |
| schedules | Object\[\] | No | Station schedules |
| workDays | String\[\] | Yes | List of working days apply which this schedule applied |
| startTime | String | Yes | the start time of Station schedule adopted by 24 hour system, the pattern is HH:mm:ss. example 07:00:00, 15:05:00 |
| endTime | String | Yes | the end time of Station schedule adopted by 24 hour system, the pattern is HH:mm:ss. example 07:00:00, 15:05:00 |
| cutOffTime | String | Yes | the cutoff time of Station schedule adopted by 24 hour system, the pattern is HH:mm:ss. example 07:00:00, 15:05:00 |
| constraints | Object\[\] | No | Function constraint |
| maxCapacity | Number | Yes | the maximum number of packages processed per day by Station. |
| maxWidth | Number | Yes | the maximum width of packages processed by Station, unit: cm |
| maxHeight | Number | Yes | the maximum height of packages processed by Station, unit: cm |
| maxLength | Number | Yes | the maximum length of packages processed by Station, unit: cm |
| maxWeight | Number | Yes | the maximum weight of packages processed by Station, unit: g |
| functionCode | String | Yes | Function which this constraint applied |
| maxCbm | String | Yes | the maximum cbm of packages processed by Station, unit: m³ |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success? |
| retryable | Boolean | Is failed request retryable? |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| errors | Object\[\] | Error list |
| errorMessage | String | Error message |
| errorCode | String | Error code |
| field | String | Error field |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/tps/stations/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/tps/stations/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/tps/stations/create");
request.addApiParameter("externalCode", "NJV_001");
request.addApiParameter("modifier", "John Wick");
request.addApiParameter("name", "Station 001");
request.addApiParameter("functionCodes", "[\"CP\"]");
request.addApiParameter("subTypes", "[\"COLLECTION_ON_POINT\"]");
request.addApiParameter("codSupport", "true");
request.addApiParameter("age", "10");
request.addApiParameter("firstMileTplSlugs", "[\"ninjavan-id\",\"jne\"]");
request.addApiParameter("lastMileTplSlugs", "[\"ninjavan-id\",\"jne\"]");
request.addApiParameter("contact", "{\"phone\":\"+84000000000\",\"name\":\"Zohan\",\"email\":\"email@gmail.com\"}");
request.addApiParameter("address", "{\"latitude\":\"10.131231\",\"details\":\"08-18, 233 SERANGOON AVENUE 3Singapore, Singapore\",\"id\":\"R80071346\",\"longitude\":\"113.131231\"}");
request.addApiParameter("timeZone", "+08:00");
request.addApiParameter("schedules", "[{\"workDays\":[\"MONDAY\",\"MONDAY\"],\"startTime\":\"08:00:00\",\"endTime\":\"18:00:00\",\"cutOffTime\":\"17:00:00\"}]");
request.addApiParameter("constraints", "[{\"functionCode\":\"CP\",\"maxCbm\":\"3.44\",\"maxHeight\":\"100\",\"maxCapacity\":\"100\",\"maxWeight\":\"100\",\"maxLength\":\"100\",\"maxWidth\":\"100\"}]");
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
  "success": "false",
  "errorMessage": "Bad request",
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
