# POSTCreateFulfillmentSkuDecouple

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku%2Fcreate
> API path: /fbl/fulfillment_sku/create
> Category: FBL API
> Scraped: 2026-05-20T23:36:26.811Z

---

Latest update2022-07-29 18:02:03

3266

CreateFulfillmentSkuDecouple

POST

/fbl/fulfillment\_sku/create

Authorization Required

Description:create fulfillment sku without product

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
| fulfillment\_sku\_name | String | Yes | title |
| barcodes | String\[\] | Yes | barcode list |
| hygroscopic | Boolean | Yes | true/false |
| precious | Boolean | Yes | true/false |
| product\_type | String | Yes | food,liquid,danger,other |
| temperature\_requirement | String | Yes | 1: normal temperature 4: refrigerated 6: frozen |
| pic\_urls | String\[\] | Yes | at most 6 pictures url |
| serial\_number\_flag | Boolean | Yes | true/false |
| shelf\_life\_flag | Boolean | Yes | true/false |
| shelf\_life\_days | Number | No | required if shelf\_life\_day is life\_mgnt |
| reject\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| alert\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| offline\_shelf\_live | Number | No | required if shelf\_life\_day is life\_mgnt |
| seller\_sku | String | Yes | erp sku code |
| sale\_price | String | Yes | sale price |
| length | Number | No | length(mm) |
| width | Number | No | width(mm) |
| height | Number | No | height(mm) |
| weight | Number | No | weight(g) |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| fulfillment\_sku\_id | Number | fulfillment\_sku\_id |
| fulfillment\_sku\_code | String | fulfillment\_sku\_code |
| success | Boolean | is success |
| error\_code | String | error\_code |
| error\_message | String | error\_msg |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/fulfillment\_sku/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku/create");
request.addApiParameter("fulfillment_sku_name", "SPORTLAND \u0E2D\u0E34\u0E19\u0E44\u0E25\u0E19\u0E4C \u0E2A\u0E40\u0E01\u0E47\u0E15 In-line Skate \u0E23\u0E38\u0E48\u0E19 SL-120 ");
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
request.addApiParameter("seller_sku", "xxxxxxx");
request.addApiParameter("sale_price", "32.25");
request.addApiParameter("length", "100");
request.addApiParameter("width", "100");
request.addApiParameter("height", "100");
request.addApiParameter("weight", "300");
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
  "data": {
    "fulfillment_sku_id": "634523827682",
    "fulfillment_sku_code": "xxxxxx_LAZOP-LZD000000063206"
  },
  "success": "true",
  "error_code": "null",
  "request_id": "0ba2887315178178017221014"
}
```
