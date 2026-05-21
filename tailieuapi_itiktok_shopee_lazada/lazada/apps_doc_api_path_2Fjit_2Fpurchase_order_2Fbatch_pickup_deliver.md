# GET/POSTBatchDeliverJitPurchaseOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fjit%2Fpurchase_order%2Fbatch_pickup_deliver
> API path: /jit/purchase_order/batch_pickup_deliver
> Category: Choice Customized API
> Scraped: 2026-05-21T00:09:23.154Z

---

Latest update2026-05-21 08:09:18

500

BatchDeliverJitPurchaseOrder

GET/POST

/jit/purchase\_order/batch\_pickup\_deliver

Authorization Required

Description:Batch Pickup Deliver Jit Purchase Order.

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
| purchaseOrderNoList | String\[\] | Yes | 采购单号列表，最大100个。{\["POJ1001","POJ1002"\]} |
| shipperAreaCode | String | Yes | 揽收联系人地址区域，如：CN： 当前支持CN，VN，TH，PH，ID，MY一共6个地区。必填。 |
| shipperAddressId | Number | Yes | 揽收联系人地址id。必填。 |
| shipperAddressDetail | String | Yes | 揽收详细地址。必填。 |
| shipperMobilePhone | String | Yes | 揽收联系人电话。必填。 |
| shipperName | String | Yes | 揽收联系人姓名。必填。 |
| estimatedPickupDate | String | No | 预约揽收日期 {yyyy-MM-dd}。非必填 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| status | String | success |
| pickup\_no | String | 揽收单号。发货方式=上门揽收时 返回。 |
| allow\_date\_range | String\[\] | 允许的揽收日期范围 |
| purchase\_order\_no | String | 采购单号 |
| error\_message | String | 错误信息 |
| success | Boolean | true |
| error\_message | String | error msg |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| INVALID\_STATUS\_FORBIDDEN\_PICK\_UP | INVALID\_STATUS\_FORBIDDEN\_PICK\_UP | This API can only be called if the order is in “Ready To Ship (biz\_status = 20)” status, please call QueryListJitPurchaseOrder API first to confirm the PO status in the request. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/jit/purchase_order/batch_pickup_deliver)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/jit/purchase\_order/batch\_pickup\_deliver

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/jit/purchase_order/batch_pickup_deliver");
request.addApiParameter("purchaseOrderNoList", "[\"POJ1\",\"POJ2\"]");
request.addApiParameter("shipperAreaCode", "CN");
request.addApiParameter("shipperAddressId", "1001");
request.addApiParameter("shipperAddressDetail", "1 road");
request.addApiParameter("shipperMobilePhone", "10086");
request.addApiParameter("shipperName", "test");
request.addApiParameter("estimatedPickupDate", "2023-10-10");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_message": "error msg",
    "data": [
      {
        "error_message": "error msg",
        "pickup_no": "FO1001",
        "allow_date_range": [],
        "purchase_order_no": "FO1001",
        "status": "success"
      }
    ],
    "success": "true",
    "error_code": "error code"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
