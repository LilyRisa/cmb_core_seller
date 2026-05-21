# GET/POSTGetVasOrderByNo4FBL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Fvas%2FgetVasOrderByNo
> API path: /fbl/vas/getVasOrderByNo
> Category: FBL API
> Scraped: 2026-05-20T23:41:33.880Z

---

Latest update2026-05-21 07:41:23

500

GetVasOrderByNo4FBL

GET/POST

/fbl/vas/getVasOrderByNo

Authorization Required

Description:get vasOrder by orderNo

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
| vas\_order\_code | String | Yes | 增值服务单号 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | String | 增值服务信息 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/vas/getVasOrderByNo)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/fbl/vas/getVasOrderByNo

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/vas/getVasOrderByNo");
request.addApiParameter("platform_name", "LAZADA_VN");
request.addApiParameter("vas_order_code", "ZVAS20251217005438003");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": "{\"data\":{\"retryable\":false,\"fail\":false,\"data\":{\"gmtOperated\":1766051362000,\"gmtModified\":1766051362000,\"supplierId\":1000000304751101,\"warehouseCode\":\"OMS-LAZADA-WH3\",\"outerVasOrderNo\":\"LBZ00201820007\",\"features\":{\"financeOrganizationCode\":\"FIN_CSLazSupplyChain\"},\"vasOrderNo\":\"ZVAS20251217005438003\",\"lines\":[{\"quantity\":6,\"operatedQuantity\":6,\"intOperatedQuantity\":6,\"scItemId\":566122254124,\"intQuantity\":6}],\"creator\":\"lzdvn0003(lzdvn0003@gmail.com)\",\"blameType\":\"null\",\"gmtCreate\":1765958945000,\"vasCode\":\"BUNDLING_TEST\",\"targetOrderType\":\"GOODS\",\"tenantId\":\"CSFBL\",\"financeOrganizationCode\":\"FIN_CSLazSupplyChain\",\"status\":\"OPERATED\"},\"success\":true,\"succAndNotNull\":true,\"message\":\"OK\"},\"code\":\"0\",\"request_id\":\"2101773f17679308323726963\",\"_trace_id_\":\"21076dc917679308322066562e1d74\"}",
  "request_id": "0ba2887315178178017221014"
}
```
