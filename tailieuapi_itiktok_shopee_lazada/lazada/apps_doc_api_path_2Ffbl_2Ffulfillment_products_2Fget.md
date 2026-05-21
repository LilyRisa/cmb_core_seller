# GETGetFulfillmentProductDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_products%2Fget
> API path: /fbl/fulfillment_products/get
> Category: FBL API
> Scraped: 2026-05-20T23:37:51.917Z

---

Latest update2026-05-21 07:37:46

500

GetFulfillmentProductDetail

GET

/fbl/fulfillment\_products/get

Authorization Required

Description:GET fulfillment product Detail；Call Get Platform Products for fulfillment\_sku first

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
| per\_page | Number | No | Maximum number of results per page |
| shelf\_life\_flag | Boolean | No | Serial number flag. true or false |
| marketplace | String | Yes | Marketplace should be "LAZADA\_MY","LAZADA\_ID","LAZADA\_VN","LAZADA\_SG","LAZADA\_TH","LAZADA\_PH" |
| fulfillment\_sku | String | No | Fulfillment SKU |
| serial\_number\_flag | Boolean | No | Serial number flag. true or false |
| page | Number | No | Page |
| fulfillment\_sku\_name | String | No | Fulfillment SKU Name used in Lazada fulfilment system |
| barcode | String | No | Barcode |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object\[\] | List of products data |
| shelf\_life\_days | Number | Shelf Life (Days) |
| color | String | Color |
| fulfillment\_sku | String | Fulfillment SKU |
| serial\_number\_flag | Boolean | Serial number flag. true or false |
| length | Number | Length |
| offline\_shelf\_live | Number | Offline before Expiry Date (Days) |
| barcodes | String | Barcodes, list of String |
| net\_weight | Number | Net Weight |
| alert\_shelf\_live | Number | Alert before Expiry Date (Days) |
| shelf\_life\_flag | Boolean | Shelf life flag, true or false |
| reject\_shelf\_live | Number | Reject at Inbound before Expiry Date (Days) |
| sn\_sample\_list | Object\[\] | Serial number Sample List |
| sample\_seq | String | Sample Seq |
| sample\_desc | String | Sample Desc |
| sample\_rule\_list | Object\[\] | Sample Rule List |
| rule\_regular\_expression | String | Rule Regular Expression |
| rule\_desc | String | Rule Desc |
| rule\_img\_url | String | Rule Img Url |
| rule\_sample | String | Rule Sample |
| width | Number | Width |
| shipper\_id | String | Shipper Id is the id used in Lazada internal systms |
| serial\_number\_mode | String | 1 Serial number mode used to indicate when serial number management is required, value can be 0 (Outbound), 1 (Outbound+Inbound), 2 (Outbound+Return). |
| fulfillment\_sku\_name | String | Fulfillment SKU Name used in Lazada fulfilment systems. |
| gross\_weight | Number | Gross Weight |
| height | Number | Height |
| hygroscopic | String | true/false |
| precious | String | true/false |
| product\_type | String | food,liquid,danger,other |
| seller\_skus | String\[\] | seller\_skus |
| temperature\_requirement | String | 1: normal temperature 4: refrigerated 6: frozen |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_products/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/fulfillment\_products/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_products/get");
request.setHttpMethod("GET");
request.addApiParameter("per_page", "50");
request.addApiParameter("shelf_life_flag", "true");
request.addApiParameter("marketplace", "LAZADA_SG");
request.addApiParameter("fulfillment_sku", "245906966_VNAMZ-315595775");
request.addApiParameter("serial_number_flag", "true");
request.addApiParameter("page", "1");
request.addApiParameter("fulfillment_sku_name", "some random name");
request.addApiParameter("barcode", "LZD12315152");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": [
    {
      "shelf_life_days": "365",
      "precious": "true",
      "color": "red",
      "fulfillment_sku": "245906966_VNAMZ-315595775",
      "serial_number_flag": "false",
      "length": "0",
      "offline_shelf_live": "60",
      "barcodes": "[\"LZD15384547802531\"]",
      "net_weight": "0",
      "alert_shelf_live": "60",
      "shelf_life_flag": "true",
      "reject_shelf_live": "0",
      "product_type": "other",
      "seller_skus": [
        "SKU1"
      ],
      "sn_sample_list": [
        {
          "sample_seq": "sample_seq",
          "sample_desc": "sample_desc",
          "sample_rule_list": [
            {
              "rule_regular_expression": "^[a-zA-Z0-9]",
              "rule_desc": "default",
              "rule_img_url": "default",
              "rule_sample": "default"
            }
          ]
        }
      ],
      "width": "0",
      "temperature_requirement": "1",
      "shipper_id": "4188058869",
      "serial_number_mode": "1",
      "hygroscopic": "true",
      "fulfillment_sku_name": "Hạt Điều rang muối đặc biêt 500g",
      "gross_weight": "0",
      "height": "0"
    }
  ],
  "request_id": "0ba2887315178178017221014"
}
```
