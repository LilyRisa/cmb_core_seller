# POSTCreateInboundOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Finbound_order%2Fcreate
> API path: /fbl/inbound_order/create
> Category: FBL API
> Scraped: 2026-05-20T23:36:54.975Z

---

Latest update2022-07-29 17:09:32

3419

CreateInboundOrder

POST

/fbl/inbound\_order/create

Authorization Required

Description:Create inbound order

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
| warehouse\_code | String | Yes | Inbound warehouse code. |
| delivery\_type | String | No | Delivery type,Enum: Dropoff / Pickup. |
| seller\_warehouse\_code | String | No | Seller warehouse code. Default value is seller's first sellerWarehouse, usually it's seller's address in asc. You can get the warehouse list by openApi listIcpWarehouse. |
| estimate\_time | String | Yes | Estimated Arrival Time in UTC+0. format is "yyyy-MM-ddTHH:mm:ssZ". |
| comment | String | No | Inbound comment. |
| reference\_number | String | No | Reference number. |
| skus | Object\[\] | Yes | List of inbound skus. Max list size is 100. |
| seller\_sku | String | No | Seller sku. |
| fulfillment\_sku | String | No | Fulfillment sku code. You should use at least one of params seller\_sku and fulfillment\_sku. If you send them both, we will use fulfillment\_sku to find your sku and ignore param seller\_sku. |
| requested\_quantity | Number | Yes | Requested inbound quantity. The quantity must be greater than 0. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Create success or not. |
| error\_code | String | Error code. |
| error\_message | String | Error message. |
| inbound\_order\_no | String | Inbound order number. |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/inbound_order/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/inbound\_order/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/inbound_order/create");
request.addApiParameter("warehouse_code", "OMS-LAZADA-SG-W-1");
request.addApiParameter("delivery_type", "Dropoff");
request.addApiParameter("seller_warehouse_code", "Seller_warehouse_1");
request.addApiParameter("estimate_time", "2020-12-03T11:00:00Z");
request.addApiParameter("comment", "Inbound to join D11");
request.addApiParameter("reference_number", "RSO1001");
request.addApiParameter("skus", "[{\"requested_quantity\":\"100\",\"fulfillment_sku\":\"3099xxxx_SGAMZ-5638xxxx\",\"seller_sku\":\"test-mysellerSKu\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Create inbound failed!",
  "code": "0",
  "success": "true",
  "inbound_order_no": "IOXXXXX1",
  "error_code": "ERROR_SYSTEM",
  "request_id": "0ba2887315178178017221014"
}
```
