# GETGetInboundOrderList

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finbound_orders%2Fget
> API path: /fbl/inbound_orders/get
> Category: FBL API
> Scraped: 2026-05-20T23:39:42.003Z

---

Latest update2022-07-29 17:39:39

3301

GetInboundOrderList

GET

/fbl/inbound\_orders/get

Authorization Required

Description:Use this API to get inbound order list

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
| inbound\_order\_no | String | No | Inbound order number, Multi orders split by ','. Max size is 100 |
| creation\_time\_From | String | No | Order's create time from |
| creation\_time\_To | String | No | Order's create time end |
| inbound\_warehouse | String | No | Inbound warehouse name |
| seller\_sku | String | No | seller sku name |
| fulfillment\_sku | String | No | Fulfilment SKU code |
| marketplace | String | Yes | marketplace:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| page | String | No | Order list page index |
| per\_page | String | No | Order list per page size, Max is 100 |
| reservation\_status | String | No | ReservationStatus: PENDING\_RESERVATION\_ORDER\_CREATE | RESERVATION\_ORDER\_CREATED | RESERVED |ARRIVED. PENDING\_RESERVATION\_ORDER\_CREATE |
| reservation\_order | String | No | Reservation Order number |
| reference\_number | String | No | Reference number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result |
| per\_page | Number | Per page size |
| data | Object\[\] | Orders |
| sku\_approved | Number | Sku Approved quantity |
| inbound\_time | String | Actual inbound Time |
| io\_number | String | Inbound order number |
| estimate\_time | String | Estimate inbound Time |
| marketplace | String | Enum Value:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| item\_inbounded\_good | Number | Items Inbounded Good quantity |
| delivery\_type | String | Delivery Type: drop off/pick up |
| item\_requested | Number | Item Requested quantity |
| created\_at | String | Create time |
| sku\_inbounded | Number | Sku Inbounded quantity |
| sku\_requested | Number | Sku Requested quantity |
| inbound\_warehouse | String | Inbound Warehouse |
| reference\_number | String | Reference number |
| item\_inbounded\_damaged | Number | Item Inbounded damaged quantity |
| updated\_at | String | Latest update time |
| status | String | Order status：Created|Pending Plan Approval|Pending Approval|Approved|Rejected by Lazada|Operate Inventory|Foc Order Created|Request Accepted|Inbound in Process|Partially|Completely|Cancelled by Seller|Cancelled by Lazada|Cancelled by system|Rejected by Warehouse |
| shop\_name | String | Shop name |
| io\_type | String | Order type, normal|reinbound|New Product Launch|Sellable In Transit |
| inbound\_warehouse\_code | String | Inbound warehouse code |
| need\_reservation | Boolean | Whether need inbound reservation, true|false |
| reservation\_status | String | ReservationStatus: PENDING\_RESERVATION\_ORDER\_CREATE | RESERVATION\_ORDER\_CREATED | RESERVED |ARRIVED. PENDING\_RESERVATION\_ORDER\_CREATE |
| reservation\_order | String | Reservation Order number |
| item\_inbounded\_expired | String | Item Inbounded expired quantity |
| page | Number | Page index |
| total\_count | Number | Total order count |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inbound_orders/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inbound\_orders/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inbound_orders/get");
request.setHttpMethod("GET");
request.addApiParameter("inbound_order_no", "IO022019061429XXXXX,IO022019061429YYYY");
request.addApiParameter("creation_time_From", "2019-08-10");
request.addApiParameter("creation_time_To", "2019-08-16");
request.addApiParameter("inbound_warehouse", "yavin test warehouse");
request.addApiParameter("seller_sku", "test-chengxi-icp-yavin-044");
request.addApiParameter("fulfillment_sku", "309958149_SGAMZ-56XXXXX");
request.addApiParameter("marketplace", "LAZADA_SG");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "20");
request.addApiParameter("reservation_status", "PENDING_RESERVATION_ORDER_CREATE");
request.addApiParameter("reservation_order", "RSO00001");
request.addApiParameter("reference_number", "refer");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "per_page": "1",
    "data": [
      {
        "inbound_warehouse": "Default SG Warehouse",
        "inbound_time": "2019-08-27T16:00:00Z",
        "marketplace": "LAZADA_SG",
        "item_inbounded_damaged": "0",
        "sku_approved": "0",
        "reservation_order": "refer001",
        "item_requested": "1",
        "inbound_warehouse_code": "OMS-LAZADA-SG-W-1",
        "created_at": "2019-08-15T08:31:48Z",
        "item_inbounded_expired": "1",
        "shop_name": "MyShop",
        "reference_number": "refer",
        "need_reservation": "true",
        "sku_inbounded": "0",
        "sku_requested": "1",
        "reservation_status": "PENDING_RESERVATION_ORDER_CREATE",
        "updated_at": "2019-08-15T08:31:48Z",
        "estimate_time": "2019-08-27T16:00:00Z",
        "delivery_type": "Dropoff",
        "io_type": "normal",
        "item_inbounded_good": "0",
        "io_number": "IO022019081529321558623",
        "status": "Created"
      }
    ],
    "total_count": "15",
    "page": "1"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
