# POSTUpdateFulfillmentSkuDecouple

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku%2Fupdate
> API path: /fbl/fulfillment_sku/update
> Category: FBL API
> Scraped: 2026-05-20T23:44:28.011Z

---

Latest update2026-05-21 07:44:15

500

UpdateFulfillmentSkuDecouple

POST

/fbl/fulfillment\_sku/update

Authorization Required

Description:update fulfillment sku without product

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
| barcodes | String\[\] | No | barcode list |
| hygroscopic | Boolean | No | true/false |
| precious | Boolean | No | true/false |
| product\_type | String | No | food,liquid,danger,other |
| temperature\_requirement | String | No | 1: normal temperature 4: refrigerated 6: frozen |
| pic\_urls | String\[\] | No | at most 6 pictures url |
| serial\_number\_flag | Boolean | No | true/false |
| shelf\_life\_flag | Boolean | No | true/false |
| shelf\_life\_days | Number | No | required if shelf\_life\_day is life\_mgnt |
| reject\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| alert\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| offline\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| sale\_price | String | No | sale price |
| fulfillment\_sku\_id | Number | Yes | fulfillment\_sku\_id |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | is success |
| error\_code | String | error\_code |
| error\_message | String | error\_msg |
| data | Boolean | is success |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/fulfillment\_sku/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku/update");
request.addApiParameter("barcodes", "[\"LZD000000063206\",\"8859295109033\"]");
request.addApiParameter("hygroscopic", "true");
request.addApiParameter("precious", "true");
request.addApiParameter("product_type", "food");
request.addApiParameter("temperature_requirement", "1");
request.addApiParameter("pic_urls", "[\"https://th-live-02.slatic.net/p/sportland-inailn-sekt-in-line-skate-run-sl-120-gray-yellow-s-7899-583337-d61a153af3f15bc83f31fa2aeec6db4d-catalog.jpg\"]");
request.addApiParameter("serial_number_flag", "true");
request.addApiParameter("shelf_life_flag", "true");
request.addApiParameter("shelf_life_days", "1825");
request.addApiParameter("reject_shelf_live", "180");
request.addApiParameter("alert_shelf_live", "60");
request.addApiParameter("offline_shelf_live", "30");
request.addApiParameter("sale_price", "32.25");
request.addApiParameter("fulfillment_sku_id", "656853096987");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "null",
  "code": "0",
  "data": "true",
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
