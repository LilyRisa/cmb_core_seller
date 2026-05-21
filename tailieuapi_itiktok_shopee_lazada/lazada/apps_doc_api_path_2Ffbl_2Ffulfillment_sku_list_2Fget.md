# GETGetFulfillmentSkuListForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_sku_list%2Fget
> API path: /fbl/fulfillment_sku_list/get
> Category: FBL API
> Scraped: 2026-05-20T23:38:07.194Z

---

Latest update2022-07-29 17:16:06

3410

GetFulfillmentSkuListForMCL

GET

/fbl/fulfillment\_sku\_list/get

Authorization Required

Description:Get Fulfillment SKU List for LAZADA Partner

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
| page | Number | Yes | Page Index |
| per\_page | String | Yes | Maximum number of results per page |
| platform\_name | String | Yes | Platform name |
| fulfillment\_sku\_name | String | No | Fulfillment Sku Name |
| seller\_sku | String | No | Seller Sku |
| fulfillment\_sku\_code | String | No | Fulfillment Sku Code |
| barcode | String | No | barcode |
| fulfillment\_sku\_codes | String | No | Fulfillment Sku Codes |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| error\_message | String | Error Message |
| page | Number | Page Index |
| per\_page | Number | Maximum number of results per page |
| total\_count | Number | Total Count |
| data | Object\[\] | Fulfillment sku list |
| seller\_id | Number | Seller ID |
| platform\_name | String | Platform name |
| owner\_id | Number | Fulfillment sku owner ID |
| seller\_skus | String | Seller Sku list |
| fulfillment\_sku\_code | String | Fulfillment Sku Code |
| fulfillment\_sku\_name | String | Fulfillment Sku Name |
| fulfillment\_sku\_id | Number | Fulfillment Sku ID |
| barcodes | String | barcodes |
| serial\_num\_flag | Boolean | Indicates if the SKU has a serial number.(applies mainly for electronic products)​ |
| shelf\_life\_flag | Boolean | Indicates if the SKU has an expiry date​. |
| has\_stock | Boolean | Indicates if the SKU has stock available in the Warehouse |
| min\_stock\_alert | Boolean | Indicates if the SKU has a low stock alert defined |
| platform\_sku\_status | String | Platform sku status list, active/deactive/delete |
| sale\_price | String | fulfillment\_sku sale price |
| currency | String | fulfillment\_sku currency |
| pic\_urls | String | picurls |
| success | Boolean | success flag |
| error\_code | String | Error Code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_sku_list/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/fulfillment\_sku\_list/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_sku_list/get");
request.setHttpMethod("GET");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "50");
request.addApiParameter("platform_name", "LAZADA_TH");
request.addApiParameter("fulfillment_sku_name", "Lenovo Thinkpad");
request.addApiParameter("seller_sku", "Brown-350");
request.addApiParameter("fulfillment_sku_code", "245906966_VNAMZ-315595775");
request.addApiParameter("barcode", "LZD000006614829");
request.addApiParameter("fulfillment_sku_codes", "245906966_VNAMZ-315595775,245906966_VNAMZ-315595776");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "platform name is null",
  "per_page": "50",
  "code": "0",
  "data": [
    {
      "has_stock": "FALSE",
      "fulfillment_sku_id": "222222",
      "serial_num_flag": "FALSE",
      "owner_id": "111111111",
      "min_stock_alert": "FALSE",
      "pic_urls": "[\"ssssssssssssssssssssss\"]",
      "barcodes": "[\"LZD000006614844\"]",
      "sale_price": "32.25",
      "shelf_life_flag": "FALSE",
      "seller_skus": "[\"Brown-350\"]",
      "platform_name": "LAZADA_TH",
      "currency": "MYR",
      "fulfillment_sku_name": "Lenovo Thinkpad",
      "platform_sku_status": "[\"active\"]",
      "fulfillment_sku_code": "245906966_VNAMZ-315595775",
      "seller_id": "6666"
    }
  ],
  "total_count": "88888",
  "success": "TRUE",
  "error_code": "001",
  "page": "1",
  "request_id": "0ba2887315178178017221014"
}
```
