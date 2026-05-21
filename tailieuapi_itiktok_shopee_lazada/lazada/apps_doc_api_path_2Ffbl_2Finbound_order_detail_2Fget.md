# GETGetInboundOrderDetail

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finbound_order_detail%2Fget
> API path: /fbl/inbound_order_detail/get
> Category: FBL API
> Scraped: 2026-05-20T23:39:25.826Z

---

Latest update2022-07-29 17:18:20

3082

GetInboundOrderDetail

GET

/fbl/inbound\_order\_detail/get

Authorization Required

Description:Use this API to get the Inbound Order Detail

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
| inbound\_order\_no | String | Yes | Inbound ouder number |
| marketplace | String | Yes | Enum Value:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Order detail |
| reservation\_status | String | ReservationStatus: PENDING\_RESERVATION\_ORDER\_CREATE | RESERVATION\_ORDER\_CREATED | RESERVED |ARRIVED. PENDING\_RESERVATION\_ORDER\_CREATE |
| reservation\_order | String | Reservation Order number |
| seller\_city | String | Seller's city |
| seller\_address | String | Seller's address |
| seller\_postcode | String | Seller's postcode |
| seller\_country | String | Seller's country |
| seller\_contact | String | Seller's contact for this order |
| seller\_mobile | String | Seller's mobile for this order |
| fulfillment\_order\_number | String | Fulfillment order number |
| inbound\_warehouse\_code | String | Inbound warehouse code |
| inbound\_time | String | Actually inbound time |
| skus | Object\[\] | Sku list |
| item\_inbounded\_expired | String | Item Inbounded-Expired |
| seller\_sku | String\[\] | Seller Sku Id List |
| item\_inbounded\_good | Number | Items Inbounded-Good |
| serial\_number\_flag | Boolean | Serial Number Flag |
| sku\_status | String | Sku status |
| item\_inbounded\_damaged | Number | Item Inbounded-Damaged |
| fulfillment\_sku\_name | String | Fulfillment sku name |
| requested\_quantity | Number | Requested quantity |
| shelf\_life\_flag | Boolean | Shelf Life Flag |
| barcodes | String\[\] | Sku barcode list |
| fulfillment\_sku | String | Fulfillment sku |
| comments | String | Item comment |
| comments | String | Order comment |
| io\_number | String | Inbound order number |
| estimate\_time | String | Estimate inbound time |
| marketplace | String | marketplace |
| delivery\_type | String | Seller drop off to warehouse Lazada Pickup |
| created\_at | String | Order create time |
| inbound\_warehouse | String | Inbound warehouse name |
| reference\_number | String | Reference number |
| updated\_at | String | Order update time |
| io\_status | String | Order status: Created|Pending Plan Approval|Pending Approval|Approved|Rejected by Lazada|Operate Inventory|Foc Order Created|Request Accepted|Inbound in Process|Partially|Completely|Cancelled by Seller|Cancelled by Lazada|Cancelled by system|Rejected by Warehouse |
| shop\_name | String | Shop name |
| io\_type | String | Order type, normal|reinbound|New Product Launch|Sellable In Transit |
| warehouse\_name | String | Lazada's warehouse name |
| warehouse\_address | String | Lazada's warehouse adress |
| seller\_warehouse\_name | String | Seller's warehouse name |
| need\_reservation | Boolean | Whether need inbound reservation, true|false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inbound_order_detail/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inbound\_order\_detail/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inbound_order_detail/get");
request.setHttpMethod("GET");
request.addApiParameter("inbound_order_no", "IO3215vsdfXXXXX");
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
    "inbound_warehouse": "yavin test warehouse",
    "skus": [
      {
        "shelf_life_flag": "false",
        "comments": "comment",
        "item_inbounded_damaged": "1",
        "requested_quantity": "2",
        "serial_number_flag": "false",
        "fulfillment_sku": "309958152_SGAMZ-563816132",
        "seller_sku": [
          "test-chengxi-icp-yavin-051"
        ],
        "item_inbounded_expired": "1",
        "item_inbounded_good": "1",
        "sku_status": "Completed",
        "fulfillment_sku_name": "Small Convertible Water Resistant Baby Diaper Bag Backpack Crossbody Bag",
        "barcodes": [
          "LZD155231458828610033"
        ]
      }
    ],
    "inbound_time": "2019-06-14T06:19:38Z",
    "inbound_warehouse_code": "OMS-LAZADA-SG-W-1",
    "created_at": "2019-06-14T06:11:05Z",
    "seller_mobile": "87654321",
    "seller_country": "R536780",
    "fulfillment_order_number": "LBX22222",
    "need_reservation": "true",
    "seller_postcode": "999002",
    "seller_warehouse_name": "my warehouse",
    "updated_at": "2019-06-14T06:19:44Z",
    "estimate_time": "2019-06-15T02:00:00Z",
    "delivery_type": "Dropoff",
    "seller_contact": "stresstest273-modified",
    "io_status": "Completely",
    "comments": "comment",
    "marketplace": "LAZADA_SG",
    "warehouse_address": "axa tower",
    "reservation_order": "RSO00001",
    "shop_name": "MyShop",
    "reference_number": "asdasd123123213",
    "seller_address": "beijing no.1",
    "seller_city": "Beijing",
    "reservation_status": "PENDING_RESERVATION_ORDER_CREATE",
    "warehouse_name": "yavin test warehouse",
    "io_type": "normal",
    "io_number": "IO02201906142932643008"
  },
  "request_id": "0ba2887315178178017221014"
}
```
