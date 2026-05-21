# GETGetOutboundOrderDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Foutbound_order_detail%2Fget
> API path: /fbl/outbound_order_detail/get
> Category: FBL API
> Scraped: 2026-05-20T23:40:34.623Z

---

Latest update2022-08-01 19:41:16

2593

GetOutboundOrderDetail

GET

/fbl/outbound\_order\_detail/get

Authorization Required

Description:Use this API to Get outbound order detail; shoud call GetOutboundOrderList for outbound\_order\_no first

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
| outbound\_order\_no | String | Yes | order number |
| marketplace | String | Yes | Enum Value:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Order detail |
| outbound\_time | String | Outbound time |
| comments | String | Comments |
| skus | Object\[\] | Sku list |
| seller\_sku | String\[\] | Seller sku id list |
| item\_outbounded | Number | Item outbounded |
| serial\_number\_flag | Boolean | Serial number flag |
| sku\_status | String | Sku status |
| requested\_quantity | Number | Requested quantity |
| fulfillment\_sku\_name | String | Fulfillment sku name |
| shelf\_life\_flag | Boolean | Shelf life flag |
| barcodes | String\[\] | Barcode list |
| fulfillment\_sku | String | Fulfillment sku |
| comments | String | item comments |
| estimate\_time | String | Estimate outbound time |
| marketplace | String | marketplace |
| outbound\_warehouse | String | Outbound warehouse name |
| delivery\_type | String | Seller drop off to warehouse Lazada Pickup |
| created\_at | String | Order create time |
| reference\_number | String | Reference number |
| item\_outbounded | Number | Items outbound num |
| outbound\_order\_no | String | Outbound order no |
| updated\_at | String | Order update time |
| status | String | Order Status: Created|Pending Plan Approval|Pending Approval|Approved|Rejected by Lazada|Operate Inventory|Foc Order Created|Request Accepted|Outbound in Process|Partially|Completely|Cancelled by Seller|Cancelled by Lazada|Cancelled by system|Rejected by Warehouse|Re-inbounded Accepted |
| shop\_name | String | Shop name |
| created\_by | String | Creator of the order, Seller|Lazada|Daraz|Retail |
| outbound\_reason | String | RTS, Sold-Offline or Scrapped |
| inventory\_type | String | Outbound sku's inventory type, good or defective |
| warehouse\_name | String | Lazada's warehouse name |
| warehouse\_address | String | Lazada's warehouse adress |
| seller\_warehouse\_name | String | Seller's warehouse name |
| seller\_city | String | Seller's city |
| seller\_address | String | Seller's address |
| seller\_postcode | String | Seller's postcode |
| seller\_country | String | Seller's country |
| seller\_contact | String | Seller's contact for this order |
| seller\_mobile | String | Seller's mobile for this order |
| fulfillment\_order\_number | String | Fulfillment order number |
| outbound\_warehouse\_code | String | Outbound warehouse code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/outbound_order_detail/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/outbound\_order\_detail/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/outbound_order_detail/get");
request.setHttpMethod("GET");
request.addApiParameter("outbound_order_no", "OO0120190614XXXX");
request.addApiParameter("marketplace", "LAZADA_SG");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "skus": [
      {
        "item_outbounded": "0",
        "shelf_life_flag": "false",
        "comments": "comments",
        "requested_quantity": "1",
        "serial_number_flag": "false",
        "fulfillment_sku": "309958152_SGAMZ-563816132",
        "seller_sku": [
          "test-chengxi-icp-yavin-051"
        ],
        "sku_status": "Approve",
        "fulfillment_sku_name": "Small Convertible Water Resistant Baby Diaper Bag Backpack Crossbody Bag",
        "barcodes": [
          "LZD155231458828610033"
        ]
      }
    ],
    "created_at": "2019-06-14T06:26:01Z",
    "seller_mobile": "87654321",
    "seller_country": "R536780",
    "fulfillment_order_number": "LBX22222",
    "seller_postcode": "999002",
    "outbound_order_no": "OO01201906142932646011",
    "seller_warehouse_name": "my warehouse",
    "updated_at": "2019-06-14T06:27:17Z",
    "estimate_time": "2019-06-14T19:00:00Z",
    "outbound_warehouse": "yavin test warehouse",
    "delivery_type": "Seller pickup",
    "seller_contact": "stresstest273-modified",
    "outbound_warehouse_code": "OMS-LAZADA-SG-W-1",
    "outbound_reason": "RTS",
    "comments": "kangqiao test 1",
    "marketplace": "LAZADA_SG",
    "warehouse_address": "axa tower",
    "outbound_time": "2019-06-14T19:00:00Z",
    "shop_name": "MyShop",
    "reference_number": "reference_number",
    "created_by": "Seller",
    "seller_address": "beijing no.1",
    "seller_city": "Beijing",
    "item_outbounded": "100",
    "warehouse_name": "yavin test warehouse",
    "inventory_type": "good",
    "status": "Request Accepted"
  },
  "request_id": "0ba2887315178178017221014"
}
```
