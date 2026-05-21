# GET/POSTLazadaBigbagCancel

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Fbigbag%2Fcancel
> API path: /logistics/cnpms/bigbag/cancel
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:30:12.510Z

---

Latest update2022-07-29 14:14:05

3162

LazadaBigbagCancel

GET/POST

/logistics/cnpms/bigbag/cancel

Authorization Required

Description:Lazada Bigbag cancel

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
| userInfo | Object | Yes | 用户信息 |
| appUserKey | String | Yes | ISV用户Id |
| client | String | Yes | ISV名称，ISV：ISV-ISV英文或拼音名称、商家ERP：SELLER-商家英文或拼音名称 |
| orderCode | String | No | 大包单号，即大包LP号，同handoverContentCode，orderCode、trackingNumber二者选其一 |
| remark | String | No | 备注 |
| locale | String | No | 多语言，默认zh\_CN |
| trackingNumber | String | No | 大包运单号，orderCode、trackingNumber二者选其一 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 取消结果 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| error\_code | String | 错误码 |
| error\_msg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| P-088-0101-10-10-191 | query across store account not found | 跨店铺组包账号不存在 |
| P-088-0000-00-15-209 | handover content not found | 未找到指定的大包 |
| P-088-0000-00-15-209 | handover content is not found | trackingNumber输入无效 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/bigbag/cancel)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/bigbag/cancel

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/bigbag/cancel");
request.addApiParameter("userInfo", "{\"appUserKey\":\"-\"}");
request.addApiParameter("client", "test");
request.addApiParameter("orderCode", "LP000000123");
request.addApiParameter("remark", "test");
request.addApiParameter("locale", "zh_CN");
request.addApiParameter("trackingNumber", "ST0000123");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "error_msg": "网络异常，请稍后重试\t",
    "data": {},
    "success": "true",
    "error_code": "P-088-0000-00-99-001"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
