# POSTPack

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Ffulfill%2Fpack
> API path: /order/fulfill/pack
> Category: Fulfillment API
> Scraped: 2026-05-20T23:26:58.154Z

---

Latest update2022-08-09 14:20:34

35061

Pack

POST

/order/fulfill/pack

Authorization Required

Description:Use this API to mark an order item as being packed.

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
| packReq | Object | Yes | request body |
| pack\_order\_list | Object\[\] | Yes | Batch size is limited to 20，Orders that need to be packed，Sub-orders of the same order will be processed together |
| order\_item\_list | Number\[\] | Yes | order\_item\_ids that need to be packed |
| order\_id | Number | Yes | order that need to be packed |
| delivery\_type | String | Yes | dropship |
| shipment\_provider\_code | String | No | If it is a local store (TFs), this field cannot be transferred; If it is a cross-border store must pass (NTFS); This field cannot be transferred to DBS orders (including local stores and cross-border stores) If you want to get the available values, you can call the [getshipmentprovider](https://open.lazada.com/apps/doc/api?path=%2Forder%2Fshipment%2Fproviders%2Fget) API |
| shipping\_allocate\_type | String | Yes | If you want to get the available values, you can call the [getshipmentprovider](https://open.lazada.com/apps/doc/api?path=%2Forder%2Fshipment%2Fproviders%2Fget) API |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | resp body |
| data | Object | resp data |
| pack\_order\_list | Object\[\] | pack order process result list |
| order\_item\_list | Object\[\] | order item pack result |
| order\_item\_id | Number | orderItemId |
| msg | String | errMsg when item\_err\_code!=0 |
| item\_err\_code | String | 0=success other=error code，The final processing result of the order |
| tracking\_number | String | tracking\_number |
| shipment\_provider | String | shipment\_provider |
| package\_id | String | package\_id |
| retry | Boolean | Determine if the package can be retried |
| order\_id | Number | order id |
| success | Boolean | If this is true, it doesn't mean that everything is processed successfully. It is necessary to judge that the item\_err\_code in orders is equal to 0 to determine that the processing is successful. Otherwise, if this is false, this batch must be unsuccessful. |
| error\_code | String | exists when success is false |
| error\_msg | String | exists when success is false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 6 | SYSTEM\_ERROR | system is busy now ,pls try later |
| 1003 | E1003\_3PL\_ALLOCATION\_FAIL | 3pl allocation failed |
| 40011 | RPC\_ERROR | system is busy now ,pls try later |
| 700000 | PACKAGE\_STATUS\_NOT\_ALLOW\_TO\_OP | current package status not allow to operation |
| 700001 | DBS\_SHIPMENT\_PROVIDER\_CODE\_NOT\_EXITS | shipment provider code not exits |
| 700004 | PARAM\_ILLEGAL | param illegal |
| 700013 | OP\_NOT\_SUPPORT | operation is no support |
| 700016 | NOT\_AVAILABLE\_NTFS\_3PL | seller not available 3pl , pls contact us to subscription 3pl |
| 700017 | PARAM\_IS\_NULL | param can't be null |
| 700018 | PARAM\_SIZE\_ERROR | param size not match |
| 700019 | PARAM\_MIN\_ERROR | param min not match |
| 700020 | ORDER\_ITEM\_NOT\_FOUND\_OR\_NOT\_BELONG\_DIGITAL | order item not found or not belong to digital |
| 700021 | ORDER\_NOT\_FOUND | order not found |
| 700022 | BATCH\_SIZE\_OUT\_OF\_LIMIT | batch size out of limit |
| 700023 | PICKUP\_IN\_STORE\_NO\_SUPPORT | pickup in store order no allow to operation |
| 700024 | GET\_LOCK\_FAILED | failed get lock,pls try later |
| 700025 | ORDER\_ITEM\_NOT\_FOUND | order item not found |
| 700026 | FO\_ITEM\_NOT\_ALLOW\_TO\_PACK | item current status not allow to pack |
| 700027 | NOT\_SUPPORT\_FBL\_TO\_PACK | Does not support FBL order to pack |
| 700028 | NOT\_SUPPORT\_PACK\_UP\_IN\_STORE\_TO\_PACK | Does not support pickup\_in\_store order to pack |
| 700029 | ITEM\_MUST\_BELONG\_SAME\_WAREHOUSE | item must belong same warehouse |
| 700030 | NOT\_SUPPORT\_DG\_SERVICE\_TO\_PACK | digital or service order not need to pack |
| 700031 | ITEM\_NOT\_READY\_TO\_FULFILL | item not ready to fulfill |
| 700032 | SELLER\_NOT\_FOUND | can't found seller |
| 700033 | TRANSFERRING\_WAREHOUSE\_PROVIDER | transferringWarehouseCode cannot found |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/fulfill/pack)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/order/fulfill/pack

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/fulfill/pack");
request.addApiParameter("packReq", "{\"pack_order_list\":[{\"order_item_list\":[\"[]\",\"[]\"],\"order_id\":\"23423423\"},{\"order_item_list\":[\"[]\",\"[]\"],\"order_id\":\"23423423\"}],\"delivery_type\":\"dropship\",\"shipment_provider_code\":\"FM49\",\"shipping_allocate_type\":\"TFS\"}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "order not found",
    "data": {
      "pack_order_list": [
        {
          "order_item_list": [
            {
              "order_item_id": "560694402292001",
              "msg": "success",
              "item_err_code": "0",
              "tracking_number": "TH340231JV0W0A",
              "shipment_provider": "Flash Express",
              "package_id": "FP022511752246001",
              "retry": "false"
            }
          ],
          "order_id": "560694402192001"
        }
      ]
    },
    "success": "true",
    "error_code": "700100"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
