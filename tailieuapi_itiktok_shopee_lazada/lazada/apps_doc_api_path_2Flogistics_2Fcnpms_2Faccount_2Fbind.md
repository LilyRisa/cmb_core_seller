# GET/POSTLazadaSellerAccountBind

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Faccount%2Fbind
> API path: /logistics/cnpms/account/bind
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:31:06.866Z

---

Latest update2022-07-28 17:07:41

4036

LazadaSellerAccountBind

GET/POST

/logistics/cnpms/account/bind

Authorization Required

Description:Lazada seller account bind for big bag pick up

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
| client | String | No | ISV名称，ISV：ISV-ISV英文或拼音名称、商家ERP：SELLER-商家英文或拼音名称 |
| remark | String | No | 备注 |
| sellerList | Object\[\] | Yes | 授权商家列表，最多一次传50 |
| country | String | Yes | 国家简码，如：MY, TH, VN, SG, ID, PH |
| sellerId | String | No | 商家ID，sellerId与shortCode必填其一 |
| shortCode | String | No | 商家账号，sellerId与shortCode必填其一。如果使用shortCode，则当前sellerList中的country必须一致 |
| sellerName | String | No | 商家名称 |
| locale | String | No | 多语言，默认zh\_CN |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 授权结果 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| errorCode | String | 错误码 |
| errorMsg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| P-088-0000-00-15-195 | query lzd merchant seller not found | 店铺信息不存在 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/account/bind)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/account/bind

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/account/bind");
request.addApiParameter("userInfo", "{\"appUserKey\":\"-\"}");
request.addApiParameter("client", "test");
request.addApiParameter("remark", "\u5907\u6CE8");
request.addApiParameter("sellerList", "[{\"country\":\"MY\",\"sellerId\":\"2143243\",\"sellerName\":\"test\",\"shortCode\":\"MY1234\"}]");
request.addApiParameter("locale", "zh_CN");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {},
    "success": "true",
    "errorCode": "P-088-0000-00-99-001",
    "errorMsg": "网络异常，请稍后重试\t"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
