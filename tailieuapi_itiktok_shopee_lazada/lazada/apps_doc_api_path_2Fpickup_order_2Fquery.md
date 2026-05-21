# GET/POSTQueryPickupOrder

> Source: https://open.lazada.com/apps/doc/api?path=%2Fpickup_order%2Fquery
> API path: /pickup_order/query
> Category: Choice Customized API
> Scraped: 2026-05-21T00:11:18.678Z

---

Latest update2024-01-24 17:57:54

1597

QueryPickupOrder

GET/POST

/pickup\_order/query

Authorization Required

Description:Query Pickup Order.

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
| pickup\_order\_no | String | Yes | 揽收单号 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | data |
| reason | String | 原因 |
| actual\_arrive\_time | String | 实际送达时间 |
| shipper\_name | String | 发货人姓名 |
| update\_time | Number | 更新时间 |
| car\_driver\_name | String | 司机姓名 |
| receive\_store\_code | String | 收货仓库编码 |
| estimated\_volume | String | 预估体积(m3) {#.###} |
| shipper\_address | String | 发货地址 |
| actual\_pickup\_time | String | 实际揽收时间 |
| car\_number | String | 车牌号 |
| pickup\_order\_no | String | 揽收单号 |
| actual\_weight | String | 实际重量(KG) {#.###} |
| purchase\_order\_no\_list | String\[\] | 关联发货单号列表 |
| shipper\_phone | String | 发货人联系方式 |
| estimated\_weight | String | 预估重量(KG) {#.###} |
| create\_time | Number | 创建时间 |
| estimated\_box\_number | Number | 预估揽收箱数 |
| logistics\_no\_list | String\[\] | 关联物流单号列表 |
| estimated\_pickup\_time | Number | 预约揽收上门日期 |
| receive\_store\_address | String | 收货仓库地址 |
| car\_driver\_phone | String | 司机联系电话 |
| status | String | 揽收单状态code。10: 待揽收; 20: 已派车; 30: 已揽收; 40: 已送达; 50: 已完成; -10: 已取消; -20: 揽收失败; -30: 已删除; |
| actual\_logistics\_no\_list | String\[\] | 揽收真实物流单号 |
| success | Boolean | true |
| error\_message | String | error msg |
| error\_code | String | error code |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/pickup_order/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/pickup\_order/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/pickup_order/query");
request.addApiParameter("pickup_order_no", "FO20231010");
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
    "data": {
      "actual_pickup_time": "1697551100000",
      "reason": "null",
      "estimated_volume": "1000",
      "purchase_order_no_list": [
        "POJ1001"
      ],
      "create_time": "1697611111000",
      "car_driver_phone": "10086",
      "actual_arrive_time": "1697551100000",
      "shipper_phone": "18736008156",
      "car_driver_name": "car driver name",
      "estimated_pickup_time": "1697551100000",
      "actual_weight": "1325",
      "update_time": "1697611111000",
      "receive_store_code": "TEST",
      "pickup_order_no": "FO20231010",
      "receive_store_address": "Dummy, Fuyuan 1 Road, Tangwei Street, Fuyong Town",
      "car_number": "P1001",
      "estimated_weight": "1.650",
      "logistics_no_list": [
        "LBX0246854485209"
      ],
      "actual_logistics_no_list": [
        "LBX0246854485209"
      ],
      "estimated_box_number": "1",
      "shipper_name": "AET001",
      "shipper_address": "test",
      "status": "10"
    },
    "success": "true",
    "error_code": "null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
