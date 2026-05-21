# POSTCreateProductReinboundOrderForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fproduct_reinbound%2Fcreate
> API path: /fbl/product_reinbound/create
> Category: FBL API
> Scraped: 2026-05-20T23:37:21.950Z

---

Latest update2022-07-29 17:09:32

2490

CreateProductReinboundOrderForMCL

POST

/fbl/product\_reinbound/create

Authorization Required

Description:Create Product Reinbound Order on Failed Delivery for MCL

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
| platform\_name | String | Yes | Trade platform name |
| sales\_order\_number | String | Yes | Sales order number from platform |
| platform\_order\_id | String | Yes | Unique order level identifier for fulfilment order |
| reinbound\_order\_id | String | Yes | Package level identifier for product reinbound request, unique for idempotence |
| tracking\_number | String | Yes | Tracking number for original package |
| reason | String | No | Failed delivery reason |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success |
| error\_code | String | Error code |
| error\_message | String | Error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/product_reinbound/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/product\_reinbound/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/product_reinbound/create");
request.addApiParameter("platform_name", "LAZADA_TH");
request.addApiParameter("sales_order_number", "LP666666");
request.addApiParameter("platform_order_id", "LP201912131233");
request.addApiParameter("reinbound_order_id", "THQCC05-20112704798831");
request.addApiParameter("tracking_number", "JNAT-0000494020");
request.addApiParameter("reason", "Address unreachable");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Error message",
  "code": "0",
  "success": "TRUE",
  "error_code": "Error code",
  "request_id": "0ba2887315178178017221014"
}
```
