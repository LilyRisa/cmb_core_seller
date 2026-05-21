# POSTCreateFulfillmentSkuForFBL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku_fbl%2Fcreate
> API path: /fbl/fulfillment_sku_fbl/create
> Category: FBL API
> Scraped: 2026-05-20T23:36:41.778Z

---

Latest update2026-05-21 07:36:28

500

CreateFulfillmentSkuForFBL

POST

/fbl/fulfillment\_sku\_fbl/create

Authorization Required

Description:create fulfillment sku for specified platform product

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
| sku\_id | Number | Yes | platform sku sku\_id |
| barcodes | String\[\] | Yes | barcode list |
| hygroscopic | Boolean | Yes | is product hygroscopic? |
| product\_type | String | Yes | food / liquid / danger / other |
| temperature\_requirement | String | Yes | "1": normal temperature "4": refrigerated "6": frozen |
| serial\_number\_flag | Boolean | Yes | is serial number management enabled? |
| shelf\_life\_flag | Boolean | Yes | is shelf life management enabled? |
| shelf\_life\_days | Number | No | days of shelf life, required if shelf\_life\_flag is true. |
| reject\_shelf\_live | Number | No | days to reject at inbound before expiry, required if shelf\_life\_flag is true. |
| alert\_shelf\_live | Number | No | days to alert before expiry, required if shelf\_life\_flag is true. |
| offline\_shelf\_live | Number | No | days to take offline before expiry, required if shelf\_life\_flag is true. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | request result |
| error\_code | String | error code |
| error\_message | String | error message |
| data | Object | data |
| fulfillment\_sku\_id | Number | fulfillment sku id |
| fulfillment\_sku\_code | String | fulfillment sku code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku_fbl/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/fulfillment\_sku\_fbl/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku_fbl/create");
request.addApiParameter("sku_id", "45679245789");
request.addApiParameter("barcodes", "[\"barcode1\", \"barcode2\"]");
request.addApiParameter("hygroscopic", "true");
request.addApiParameter("product_type", "food");
request.addApiParameter("temperature_requirement", "1");
request.addApiParameter("serial_number_flag", "true");
request.addApiParameter("shelf_life_flag", "true");
request.addApiParameter("shelf_life_days", "100");
request.addApiParameter("reject_shelf_live", "20");
request.addApiParameter("alert_shelf_live", "10");
request.addApiParameter("offline_shelf_live", "5");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "seller info not found",
  "code": "0",
  "data": {
    "fulfillment_sku_id": "786543234",
    "fulfillment_sku_code": "234612356_ID-45679245789"
  },
  "success": "true",
  "error_code": "INVALID_PARAM",
  "request_id": "0ba2887315178178017221014"
}
```
