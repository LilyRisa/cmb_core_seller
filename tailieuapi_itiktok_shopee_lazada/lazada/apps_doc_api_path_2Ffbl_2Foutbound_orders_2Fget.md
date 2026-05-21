# GETGetOutboundOrderList

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Foutbound_orders%2Fget
> API path: /fbl/outbound_orders/get
> Category: FBL API
> Scraped: 2026-05-20T23:40:50.105Z

---

Latest update2026-05-21 07:40:37

500

GetOutboundOrderList

GET

/fbl/outbound\_orders/get

Authorization Required

Description:Use this API to get outbound order list

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
| outbound\_order\_no | String | No | Outbound order number,Multi orders split by ','. Max size is 100 |
| creation\_time\_from | String | No | Order's create time from |
| creation\_time\_to | String | No | Order's create time end |
| outbound\_warehouse | String | No | Outbound warehouse name |
| seller\_sku | String | No | seller sku name |
| fulfillment\_sku | String | No | Fulfilment SKU code |
| marketplace | String | Yes | marketplace:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| page | String | No | Order list page index |
| per\_page | String | No | Order list per page size |
| reference\_number | String | No | Reference number |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | Result |
| per\_page | Number | Per page size |
| data | Object\[\] | Orders |
| sku\_approved | Number | Sku Approved quantity |
| outbound\_time | String | Actual outbound Time |
| oo\_number | String | Outbound order number |
| estimate\_time | String | Estimate outbound Time |
| marketplace | String | Enum Value:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| delivery\_type | String | Delivery Type: drop off/pick up |
| item\_requested | Number | Item Requested quantity |
| created\_at | String | Create time |
| sku\_outbounded | Number | Sku outbounded quantity |
| sku\_requested | Number | Sku Requested quantity |
| outbound\_warehouse | String | Outbound Warehouse |
| reference\_number | String | Reference number |
| item\_outbounded | Number | Item outbounded quantity |
| updated\_at | String | Latest update time |
| status | String | Order status: Created|Pending Plan Approval|Pending Approval|Approved|Rejected by Lazada|Operate Inventory|Foc Order Created|Request Accepted|Outbound in Process|Partially|Completely|Cancelled by Seller|Cancelled by Lazada|Cancelled by system|Rejected by Warehouse|Re-inbounded Accepted |
| shop\_name | String | Shop name |
| created\_by | String | Who create the order, Seller|Lazada|Daraz|Retail |
| outbound\_reason | String | Outbound reason, RTS|Sold-Offline|Scrapped |
| fulfillment\_order\_number | String | Fulfillment order number |
| outbound\_warehouse\_code | String | Outbound Warehouse code |
| page | Number | Page index |
| total\_count | Number | Total order count |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/outbound_orders/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/outbound\_orders/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/outbound_orders/get");
request.setHttpMethod("GET");
request.addApiParameter("outbound_order_no", "OO022019061429XXXXX");
request.addApiParameter("creation_time_from", "2019-08-10");
request.addApiParameter("creation_time_to", "2019-08-16");
request.addApiParameter("outbound_warehouse", "yavin test warehouse");
request.addApiParameter("seller_sku", "test-chengxi-icp-yavin-044");
request.addApiParameter("fulfillment_sku", "309958149_SGAMZ-56XXXXX");
request.addApiParameter("marketplace", "LAZADA_SG");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "20");
request.addApiParameter("reference_number", "refer001");
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
        "marketplace": "LAZADA_SG",
        "sku_approved": "0",
        "item_requested": "1",
        "created_at": "2019-08-15T08:31:48Z",
        "outbound_time": "2019-08-27T16:00:00Z",
        "shop_name": "name",
        "fulfillment_order_number": "LBXxxxxx",
        "reference_number": "refer",
        "created_by": "Seller",
        "item_outbounded": "0",
        "sku_requested": "1",
        "updated_at": "2019-08-15T08:31:48Z",
        "estimate_time": "2019-08-27T16:00:00Z",
        "delivery_type": "Dropoff",
        "outbound_warehouse": "Default SG Warehouse",
        "outbound_warehouse_code": "OMS-LAZADA-WH3",
        "sku_outbounded": "0",
        "outbound_reason": "RTS",
        "oo_number": "IO022019081529321558623",
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
