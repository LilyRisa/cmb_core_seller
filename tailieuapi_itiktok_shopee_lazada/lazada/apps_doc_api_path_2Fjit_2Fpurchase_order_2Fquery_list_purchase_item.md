# GET/POSTQueryListPurchaseItem

> Source: https://open.lazada.com/apps/doc/api?path=%2Fjit%2Fpurchase_order%2Fquery_list_purchase_item
> API path: /jit/purchase_order/query_list_purchase_item
> Category: Choice Customized API
> Scraped: 2026-05-21T00:11:03.731Z

---

Latest update2024-01-24 17:57:55

1909

QueryListPurchaseItem

GET/POST

/jit/purchase\_order/query\_list\_purchase\_item

Authorization Required

Description:Query List Purchase Item.

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
| purchase\_order\_no | String | Yes | JIT采购单号 |
| page\_index | Number | No | 当前页，默认1。 |
| page\_size | Number | No | 分页大小，最大200个，默认20。 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| product\_id | String | 商品id |
| sc\_item\_code | String | 货品编码 |
| buyer\_qty | Number | 下单数量 |
| sc\_item\_id | Number | 货品id |
| barcodes | String\[\] | 条形码 |
| received\_normal\_qty | Number | 实收正品数量 |
| img\_url | String | 商品预览图 |
| purchase\_order\_no | String | 采购单号 |
| product\_title | String | 商品名称 |
| sc\_item\_name | String | 货品名称 |
| seller\_sku | String | 商品sellerSku |
| sku\_id | String | 商品sku id |
| received\_defective\_qty | Number | 实收残品数量 |
| page\_index | Number | 当前页 |
| total\_page | Number | 总页数 |
| success | Boolean | is success |
| error\_message | String | error msg |
| page\_size | Number | 每页大小 |
| error\_code | String | error code |
| total\_count | Number | 总记录数 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/jit/purchase_order/query_list_purchase_item)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/jit/purchase\_order/query\_list\_purchase\_item

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/jit/purchase_order/query_list_purchase_item");
request.addApiParameter("purchase_order_no", "POJ1001");
request.addApiParameter("page_index", "1");
request.addApiParameter("page_size", "20");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_message": "null",
    "data": [
      {
        "received_defective_qty": "0",
        "sku_id": "10060",
        "barcodes": [],
        "product_title": "Test",
        "buyer_qty": "10",
        "sc_item_code": "test",
        "img_url": "null",
        "sc_item_name": "test",
        "product_id": "10086",
        "seller_sku": "10001",
        "received_normal_qty": "10",
        "purchase_order_no": "null",
        "sc_item_id": "10010"
      }
    ],
    "success": "true",
    "total_count": "200",
    "page_index": "1",
    "total_page": "1",
    "error_code": "null",
    "page_size": "1"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
