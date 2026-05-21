# GET/POSTQueryAddressInformaiton

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Faddress%2Fquery
> API path: /logistics/cnpms/address/query
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:31:21.789Z

---

Latest update2022-07-29 17:09:32

4191

QueryAddressInformaiton

GET/POST

/logistics/cnpms/address/query

Authorization Required

Description:Query Address Informaiton

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
| country | String | Yes | 国家 |
| zipCode | String | No | 邮编 |
| userInfo | Object | Yes | 用户信息 |
| appUserKey | String | Yes | 由ISV/ERP自定义，用于授权分组 |
| city | String | Yes | 市 |
| remark | String | No | 备注 |
| locale | String | No | 多语言，默认zh\_CN |
| province | String | Yes | 省 |
| street | String | Yes | 街道 |
| district | String | Yes | 区/县 |
| detailAddress | String | Yes | 详细地址 |
| client | String | No | ISV名称，ISV：ISV-ISV英文或拼音名称、商家ERP：SELLER-商家英文或拼音名称 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 查询结果，当success为true时有效 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| errorCode | String | 错误码 |
| errorMsg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| P-088-0101-10-10-152 | address service result error | 地址解析失败，只能输入中国大陆地区 |
| P-088-0000-00-15-213 | param country is null | 请输入中文地址，英文无效 |
| P-088-0000-00-15-214 | param province is null | 请输入中文地址，英文无效 |
| P-088-0000-00-15-215 | param city is null | 请输入中文地址，英文无效 |
| P-088-0000-00-15-216 | param detailAddress is null | 请输入中文地址，英文无效 |
| P-088-0000-00-15-217 | param country is not support | 请输入中文地址，英文无效 |
| P-088-0000-00-15-218 | params is null | 请输入中文地址，英文无效 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/address/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/address/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/address/query");
request.addApiParameter("country", "\u4E2D\u56FD");
request.addApiParameter("zipCode", "3455657");
request.addApiParameter("userInfo", "{\"appUserKey\":\"12345\"}");
request.addApiParameter("city", "\u676D\u5DDE\u5E02");
request.addApiParameter("remark", "\u5907\u6CE8");
request.addApiParameter("locale", "zh_CN");
request.addApiParameter("province", "\u6D59\u6C5F\u7701");
request.addApiParameter("street", "\u848B\u6751\u8857\u9053");
request.addApiParameter("district", "\u897F\u6E56\u533A");
request.addApiParameter("detailAddress", "\u6587\u4E00\u897F\u8DEF\u897F\u6EAA\u9996\u5EA7");
request.addApiParameter("client", "test");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {
      "matchDetailAddress": "上海 上海市 静安区 曹家渡街道 鑫阳公寓古鲁丁家居",
      "addressId": "310106006"
    },
    "success": "true",
    "errorCode": "P-088-0000-00-99-001",
    "errorMsg": "网络异常，请稍后重试"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
