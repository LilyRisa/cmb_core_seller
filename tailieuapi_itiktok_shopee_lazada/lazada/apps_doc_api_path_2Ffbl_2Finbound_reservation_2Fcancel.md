# POSTCancelInboundReservation

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finbound_reservation%2Fcancel
> API path: /fbl/inbound_reservation/cancel
> Category: FBL API
> Scraped: 2026-05-20T23:34:59.291Z

---

Latest update2022-07-29 13:47:56

2277

CancelInboundReservation

POST

/fbl/inbound\_reservation/cancel

Authorization Required

Description:cancel reservation order

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
| reservation\_order | String | Yes | reservation order code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | success |
| error\_code | String | error code |
| error\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inbound_reservation/cancel)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/inbound\_reservation/cancel

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inbound_reservation/cancel");
request.addApiParameter("reservation_order", "RSO1234");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "seller error",
  "code": "0",
  "success": "true",
  "error_code": "SELLER_ERROR",
  "request_id": "0ba2887315178178017221014"
}
```
