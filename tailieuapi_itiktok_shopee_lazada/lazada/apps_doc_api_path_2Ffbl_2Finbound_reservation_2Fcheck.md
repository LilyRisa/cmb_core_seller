# GETCheckInboundReservationSlot

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finbound_reservation%2Fcheck
> API path: /fbl/inbound_reservation/check
> Category: FBL API
> Scraped: 2026-05-20T23:35:47.255Z

---

Latest update2026-05-21 07:35:40

500

CheckInboundReservationSlot

GET

/fbl/inbound\_reservation/check

Authorization Required

Description:Check Available Reservation Slots for Inbound Order

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
| access\_token | String | Yes | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| inbound\_orders | String | Yes | inbound order list |
| date | String | Yes | date |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| error\_code | String | error code |
| error\_message | String | error message |
| data | Object | data |
| slots | String\[\] | data slot list |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inbound_reservation/check)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inbound\_reservation/check

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inbound_reservation/check");
request.setHttpMethod("GET");
request.addApiParameter("inbound_orders", "IO1234,IO5678");
request.addApiParameter("date", "2021-12-01");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "shipper error",
  "code": "0",
  "data": {
    "slots": [
      "2021-12-01T00:30:00Z",
      "2021-12-01T01:00:00Z"
    ]
  },
  "success": "true",
  "error_code": "SHIPPER_ERROR",
  "request_id": "0ba2887315178178017221014"
}
```
