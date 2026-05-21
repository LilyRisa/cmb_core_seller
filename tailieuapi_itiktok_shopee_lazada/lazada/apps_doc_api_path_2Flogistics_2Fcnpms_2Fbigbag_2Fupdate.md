# GET/POSTLazadaBigbagUpdate

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Fbigbag%2Fupdate
> API path: /logistics/cnpms/bigbag/update
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:30:51.856Z

---

Latest update2022-07-28 17:08:05

3091

LazadaBigbagUpdate

GET/POST

/logistics/cnpms/bigbag/update

Authorization Required

Description:Lazada bigbag update

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
| appUserKey | String | Yes | 由ISV/ERP自定义，用于授权分组 |
| weight | Number | Yes | 重量 |
| locale | String | No | 多语言，默认zh\_CN |
| orderCodeList | String\[\] | Yes | 要创建交接单的小包编码集合，数量上限300 |
| client | String | Yes | ISV名称，ISV：ISV-ISV英文或拼音名称、商家ERP：SELLER-商家英文或拼音名称 |
| orderCode | String | No | 大包单号，即大包LP号，orderCode、trackingNumber二者选其一 |
| trackingNumber | String | No | 大包运单号，orderCode、trackingNumber二者选其一 |
| weightUnit | String | Yes | 重量单位，克:g, 千克:kg，默认g |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 返回更新结果 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| errorCode | String | 错误码 |
| erroMsg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| P-088-0000-00-15-300 | handover content status not committed、awaiting\_tracking\_number or awaiting\_pickup, can not update | 大包状态非已提交、等待分配运单号、待揽收，不能更新该大包 |
| P-088-0000-00-15-209 | handover content not found | 未找到指定的大包 |
| P-088-0101-10-10-140 | all parcel order not found | 选择的所有小包都找不到，请核对后重试 |
| P-088-0101-10-10-191 | query across store account not found | 跨店铺组包账号不存在 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/bigbag/update)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/bigbag/update

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/bigbag/update");
request.addApiParameter("userInfo", "{\"appUserKey\":\"12345\"}");
request.addApiParameter("weight", "100");
request.addApiParameter("locale", "zh_CN");
request.addApiParameter("orderCodeList", "[\"LZD1001\",\"LZD1002\",\"LZD1003\"]");
request.addApiParameter("client", "test");
request.addApiParameter("orderCode", "LP000000123");
request.addApiParameter("trackingNumber", "ST0000123");
request.addApiParameter("weightUnit", "g");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "erroMsg": "网络异常，请稍后重试",
    "data": {},
    "success": "true",
    "errorCode": "P-088-0000-00-99-001"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
