# POSTUpdatePickupTimeSlot

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Ftps%2Fsellers%2Fpickup_timeslot
> API path: /logistics/tps/sellers/pickup_timeslot
> Category: Logistics API
> Scraped: 2026-05-20T23:29:25.587Z

---

Latest update2022-08-30 10:00:23

4358

UpdatePickupTimeSlot

POST

/logistics/tps/sellers/pickup\_timeslot

No Authorization Required

Description:3PL call TPS to update pickup timeslot

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
| sellerId | String | Yes | Seller ID (Sent in pickup request) |
| warehouseCode | String | Yes | Warehouse code (Sent in pickup request) |
| pickupTimeslots | String\[\] | Yes | Format: HH:mm, separate by comma |
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
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/tps/sellers/pickup_timeslot)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/logistics/tps/sellers/pickup\_timeslot

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/tps/sellers/pickup_timeslot");
request.addApiParameter("sellerId", "200165961111");
request.addApiParameter("warehouseCode", "dropshipping");
request.addApiParameter("pickupTimeslots", "[\"08:00-12:00\",\"13:00-15:00\"]");
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
