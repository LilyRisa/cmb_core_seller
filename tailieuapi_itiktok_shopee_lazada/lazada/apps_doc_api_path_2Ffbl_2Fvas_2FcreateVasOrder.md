# GET/POSTCreateVasOrder4FBL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fvas%2FcreateVasOrder
> API path: /fbl/vas/createVasOrder
> Category: FBL API
> Scraped: 2026-05-20T23:37:29.836Z

---

Latest update2026-01-09 17:50:43

662

CreateVasOrder4FBL

GET/POST

/fbl/vas/createVasOrder

Authorization Required

Description:FBL增值服务创建

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
| platform\_name | String | Yes | laz店铺所属的前台租户,例如: LAZADA\_VN |
| idempotent\_key | String | Yes | 幂等码 |
| service\_provider\_no | String | No | 物流服务商单据号，比如：LBX |
| target\_order\_no | String | No | 服务目标单据号,比如：CO单号 |
| target\_order\_type | String | No | 服务对象类型：服务对象为入库单，则填写：CO；服务对象为品，则填写:GOODS; |
| vas\_code | String | Yes | 增值服务Code：LABEL\_PRINTING\_PASTING\_FOR\_IB 打印并贴商品条码 LABEL\_PRINTING\_PASTING\_FOR\_ITEM 打印并贴商品条码 REPACKING\_FOR\_IB 重新包装 REPACKING\_FOR\_ITEM 重新包装 BUNDLING 绑定商品 LABEL\_PRINTING\_FOR\_IB 打印商品条码 LABEL\_PRINTING\_FOR\_ITEM 打印商品条码 LABEL\_PASTING\_FOR\_IB 贴商品条码 LABEL\_PASTING\_FOR\_ITEM 贴商品条码 SORTING 分类商品 INBOUND\_QC 收货质检 |
| warehouse\_code | String | Yes | 仓code |
| lines | Object\[\] | Yes | 明细行 |
| quantity | Number | Yes | 计划数量 |
| scItem\_id | Number | Yes | 货品ID |
| bundle\_quantity | Number | No | 绑定数量 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | String | 建单结果 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/vas/createVasOrder)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/vas/createVasOrder

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/vas/createVasOrder");
request.addApiParameter("platform_name", "LAZADA_VN");
request.addApiParameter("idempotent_key", "IOCN2510311706463989472694#VAS001");
request.addApiParameter("service_provider_no", "LBX02254922342245544");
request.addApiParameter("target_order_no", "IOCN2510311706463989472694");
request.addApiParameter("target_order_type", "CO");
request.addApiParameter("vas_code", "VAS001");
request.addApiParameter("warehouse_code", "AET001");
request.addApiParameter("lines", "[{\"quantity\":\"1\",\"bundle_quantity\":\"1\",\"scItem_id\":\"1\"}]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": "{\"data\":{\"retryable\":false,\"fail\":false,\"data\":\"ZVAS20260109005518012\",\"success\":true,\"succAndNotNull\":true,\"message\":\"OK\"},\"code\":\"0\",\"request_id\":\"2101773f17679312998796972\",\"_trace_id_\":\"212b8f2f17679312997072285e1ccf\"}",
  "request_id": "0ba2887315178178017221014"
}
```
