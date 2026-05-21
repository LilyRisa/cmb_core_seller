# GET/POSTQueryListJitPurchaseOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fjit%2Fpurchase_order%2Fquery_list
> API path: /jit/purchase_order/query_list
> Category: Choice Customized API
> Scraped: 2026-05-21T00:10:55.949Z

---

Latest update2024-01-24 15:21:19

2687

QueryListJitPurchaseOrder

GET/POST

/jit/purchase\_order/query\_list

Authorization Required

Description:Query List Jit Purchase Order.

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
| gmt\_create\_begin | String | No | 单据创建开始时间，建单时间范围(即end-begin)需要在90天内。{yyyy-MM-dd HH:mm:ss} |
| gmt\_create\_end | String | No | 单据创建结束时间，建单时间范围(即end-begin)需要在90天内。{yyyy-MM-dd HH:mm:ss} |
| purchase\_order\_no\_list | String\[\] | No | 采购单列表，最大20个。{\["POJ1001","POJ1002"\]} |
| logistics\_no\_list | String\[\] | No | 物流单列表，最大10个。{\["LBX1001","LBX1002"\]} |
| order\_status | String | No | 单据状态 10:待打包; 20:待发货; 22:待收货; 25:已到仓; 40:已完成; -100610:超时关闭; -100:买家取消；不传则返回所有状态的采购单； |
| page\_index | Number | No | 当前页，默认1。 |
| page\_size | Number | No | 分页大小，最大50个，默认20。 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object\[\] | data |
| supplier\_name | String | 供应商名称 |
| consign\_order\_no\_list | String | 发货单号列表 |
| gmt\_modified | Number | 更新时间 |
| creator | String | 创建人 |
| supplier\_id | Number | 供应商ID |
| delivery\_method | String | 发货方式 parcel:快递; truck:卡车派送或其他; pickup:上门揽收; |
| store\_contact\_name | String | 仓库联系人 |
| supplier\_code | String | 供应商编码 |
| gmt\_create | Number | 创建时间 |
| gmt\_except\_arrive\_time | Number | 期望到仓时间 |
| purchase\_order\_no | String | 采购单号 |
| gmt\_arrive\_time | Number | 实际到仓时间 |
| trade\_order\_id\_list | String\[\] | 交易单号 |
| pickup\_order\_no | String | 揽收单号 |
| store\_contact\_phone | String | 仓库联系电话 |
| logistics\_no\_list | String | 物流单号列表 |
| seller\_id | String | 站点店铺ID |
| total\_quantity | Number | 采购数量 |
| store\_address | String | 仓库地址 |
| total\_sku\_count | Number | SKU数量 |
| site\_id | String | 收件人国家 |
| store\_name | String | 仓库名称 |
| biz\_status | String | 状态 |
| store\_code | String | 仓库Code |
| fulfillment\_cancel\_status | String | 履约取消状态 |
| ext\_fields | String | 采购单扩展字段 |
| page\_index | Number | 当前页 |
| total\_page | Number | 总页数 |
| success | Boolean | isSuccess |
| error\_message | String | error msg |
| page\_size | Number | 每页大小 |
| error\_code | String | error code |
| total\_count | Number | 总记录数 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/jit/purchase_order/query_list)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/jit/purchase\_order/query\_list

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/jit/purchase_order/query_list");
request.addApiParameter("gmt_create_begin", "2023-10-01 00:00:00");
request.addApiParameter("gmt_create_end", "2023-10-10 00:00:00");
request.addApiParameter("purchase_order_no_list", "[\"POJ1001\",\"POJ1002\"]");
request.addApiParameter("logistics_no_list", "[\"LBX1001\",\"LBX1002\"]}");
request.addApiParameter("order_status", "10");
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
        "gmt_create": "1697611794000",
        "store_address": "1 Road",
        "gmt_modified": "1697611794000",
        "fulfillment_cancel_status": "CANCELED",
        "trade_order_id_list": [],
        "store_contact_name": "test",
        "delivery_method": "truck",
        "gmt_arrive_time": "1697853918000",
        "total_quantity": "1",
        "store_name": "Test",
        "store_contact_phone": "null",
        "supplier_name": "test",
        "ext_fields": "{\"abc\": \"123\"}",
        "seller_id": "500",
        "store_code": "TEST-1",
        "creator": "50000",
        "biz_status": "20",
        "consign_order_no_list": "LBX1001",
        "total_sku_count": "1",
        "gmt_except_arrive_time": "1697853918000",
        "pickup_order_no": "FO2023",
        "site_id": "PH",
        "logistics_no_list": "null",
        "supplier_id": "1000000000",
        "supplier_code": "10086",
        "purchase_order_no": "POJ1001"
      }
    ],
    "success": "true",
    "total_count": "300",
    "page_index": "1",
    "total_page": "16",
    "error_code": "null",
    "page_size": "20"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
