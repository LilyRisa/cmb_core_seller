# GETGetInventoryOperateLog

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finventory_operate_log%2Fget
> API path: /fbl/inventory_operate_log/get
> Category: FBL API
> Scraped: 2026-05-20T23:40:18.944Z

---

Latest update2026-05-21 07:40:05

500

GetInventoryOperateLog

GET

/fbl/inventory\_operate\_log/get

Authorization Required

Description:Use this API to get a sku's inventory operate log

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
| page | Number | No | Operate log list page index |
| per\_page | Number | No | Operate log list perpage size |
| market\_place | String | Yes | market place:LAZADA\_VN,LAZADA\_SG,LAZADA\_MY, LAZADA\_ID,LAZADA\_PH,LAZADA\_TH |
| operate\_time\_from | String | No | Inventory operate time from, GMT+0. |
| operate\_time\_to | String | No | Inventory operate time to, GMT+0. This param is Required. We suggest that operate\_time\_to - operate\_time\_from < 6 months |
| warehouse\_code | String | No | Warehouse code |
| fulfillment\_sku\_id | String | No | Fulfillment Sku Id |
| order\_type\_code | String | No | Order Type Code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| inventory\_operate\_log | Object\[\] | Inventory operate log |
| ref\_order\_code | Object\[\] | ref order |
| type | String | type |
| order\_code | String | order code |
| warehouse\_code | String | Warehouse code |
| warehouse\_name | String | Warehouse name |
| order\_type | String | Order type |
| inventory\_type | String | Inventory type:GOOD,Damaged,ONWAY,TRANSFER\_WAY,Expired,Damaged A,Damaged B,Damaged C. |
| change\_quantity | String | Change quantity |
| result\_quantity | String | Result quantity |
| operate\_time | String | Operate time, GMT+0 |
| order\_type\_code | String | Order Type Code |
| fulfillment\_sku\_id | String | Fulfillment Sku Id |
| customer\_order | String | customer order ,trade order and reverse trade order will not be null |
| success | String | The api request success or not |
| errMessage | String | Error message when success=false |
| errCode | String | Error code when success=false |
| page | Number | Page index |
| per\_page | Number | Per page size |
| total\_count | Number | Total log count |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inventory_operate_log/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/inventory\_operate\_log/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inventory_operate_log/get");
request.setHttpMethod("GET");
request.addApiParameter("page", "1");
request.addApiParameter("per_page", "20");
request.addApiParameter("market_place", "LAZADA_SG");
request.addApiParameter("operate_time_from", "2019-07-23");
request.addApiParameter("operate_time_to", "2019-08-24");
request.addApiParameter("warehouse_code", "OMS-LAZADA-MY-W-1");
request.addApiParameter("fulfillment_sku_id", "322302784_SGAMZ-648014149");
request.addApiParameter("order_type_code", "TRADE_OUT,COORDINATE_OUT,FAILED_DELIVERY_IN,REFOUND_IN,SELLER_INBOUND,OTHER_INBOUND,IMBALANCE_LOCK,CHECK_OUT,CHECK_IN,INVENTORY_STATUS_ADJUST_OUT,INVENTORY_STATUS_ADJUST_IN,OUTBOUND");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "per_page": "20",
  "code": "0",
  "inventory_operate_log": [
    {
      "order_type_code": "TRADE_OUT,COORDINATE_OUT,FAILED_DELIVERY_IN,REFOUND_IN,SELLER_INBOUND,OTHER_INBOUND,IMBALANCE_LOCK,CHECK_OUT,CHECK_IN,INVENTORY_STATUS_ADJUST_OUT,INVENTORY_STATUS_ADJUST_IN,OUTBOUND",
      "ref_order_code": [
        {
          "order_code": "IO022019102523712684546",
          "type": "ioCode"
        }
      ],
      "warehouse_name": "Suban",
      "change_quantity": "5",
      "fulfillment_sku_id": "322302784_SGAMZ-648014149",
      "warehouse_code": "OMS-LAZADA-MY-W-1",
      "customer_order": "1069296969377776",
      "inventory_type": "GOOD",
      "order_type": "Fulfillment Outbound,Stock Transfer Outbound,Stock Transfer Inbound,Failed Delivery Inbound,Fulfillment Reverse Inbound,Seller/Supplier Inbound,Customer Return Inbound,Warehouse lock,Cycle Count Loss,Cycle Count Gain,Good to Defective Inventory Change,Defective to Good Inventory Change,Seller/Supplier Outbound,Adjust out,Adjust in",
      "result_quantity": "10",
      "operate_time": "2019-08-24 10:10:10"
    }
  ],
  "success": "true",
  "errCode": "INVALID PARAM",
  "total_count": "100",
  "page": "1",
  "request_id": "0ba2887315178178017221014",
  "errMessage": "invalid marketplace"
}
```
