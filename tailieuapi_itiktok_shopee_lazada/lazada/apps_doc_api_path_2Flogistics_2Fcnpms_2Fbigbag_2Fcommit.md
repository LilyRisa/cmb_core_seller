# GET/POSTLazadaBigbagCommit

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Fbigbag%2Fcommit
> API path: /logistics/cnpms/bigbag/commit
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:30:36.671Z

---

Latest update2022-07-29 14:38:27

6592

LazadaBigbagCommit

GET/POST

/logistics/cnpms/bigbag/commit

Authorization Required

Description:Lazada bigbag commit

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
| userInfo | Object | Yes | Lazada开放平台信息 |
| appUserKey | String | Yes | Lazada开放平台appUserKey |
| orderCodeList | String\[\] | Yes | 要创建交接单的小包编码集合，数量上限1000 |
| weight | String | Yes | 重量 |
| client | String | Yes | ISV名称，ISV：ISV-ISV英文或拼音名称、商家ERP：SELLER-商家英文或拼音名称 |
| collectionInfo | Object | No | 集货点信息 |
| pickUpCode | String | Yes | 集货点编码 |
| remark | String | No | 备注 |
| pickupInfo | Object | Yes | 揽收信息 |
| courierCompany | String | No | 快递公司 |
| receiverPhone | String | No | 收件人手机号 |
| address | Object | Yes | 揽收地址信息 |
| country | String | Yes | 国家 |
| zipCode | String | Yes | 邮编 |
| city | String | Yes | 市 |
| province | String | Yes | 省 |
| street | String | Yes | 街道 |
| district | String | Yes | 区 |
| detailAddress | String | Yes | 详细地址 |
| phone | String | No | 移动电话, 校验格式：^1(3|4|5|6|7|8|9)\\d{9}$ |
| name | String | Yes | 揽收联系人名称，必须包含中文字符 |
| mobile | String | Yes | 固定电话，可空，校验格式：(^0\[\\d\]{2,3}-\[\\d\]{7,8}$)|(^400\[\\d\]{3,4}\[\\d\]{3,4}$)|(400-\[\\d\]{3,4}-\[\\d\]{3,4}$) |
| email | String | Yes | 邮箱 |
| addressId | Number | Yes | 揽收地址ID |
| locale | String | No | 多语言，默认zh\_CN |
| weightUnit | String | Yes | 重量单位，克:g, 千克:kg，默认g |
| type | String | Yes | 类型：cainiao\_pickup(菜鸟揽收)、self\_post(自寄)、pickup\_collection(集货) |
| sellerTrackingNumber | String | No | 商家定义的大包标签号，一般不传，需要将自有大包号作为菜鸟面单号时才传 |
| returnInfo | Object | Yes | 退件信息 |
| phone | String | No | 固定电话，可空，校验格式：(^0\[\\d\]{2,3}-\[\\d\]{7,8}$)|(^400\[\\d\]{3,4}\[\\d\]{3,4}$)|(400-\[\\d\]{3,4}-\[\\d\]{3,4}$) |
| name | String | Yes | 退件联系人名称，必须包含中文字符 |
| mobile | String | Yes | 手机号 |
| email | String | Yes | 邮箱 |
| addressId | Number | Yes | 退件地址ID |
| fmReverseOption | String | No | 退件方式 1-退回，2-销毁，3-自提 |
| address | Object | Yes | 退件地址 |
| province | String | Yes | 省 |
| street | String | Yes | 街道 |
| district | String | Yes | 区 |
| detailAddress | String | Yes | 详细地址 |
| country | String | Yes | 国家 |
| zipCode | String | Yes | 退件地址ID |
| city | String | Yes | 市 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 响应信息，只有当success为true时才有效 |
| handoverOrderId | Number | 交接单ID |
| handoverContentId | Number | 大包ID |
| handoverContentCode | String | 大包交接单号，即大包LP号 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| errorCode | String | 错误码 |
| errorMsg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| P-088-0101-10-10-140 | all parcel order not found | 选择的所有小包都找不到，请核对后重试 |
| P-088-0101-10-10-191 | query across store account not found | 跨店铺组包账号不存在 |
| P-088-0000-00-15-170 | seller has stores that are not packaged across stores | 商家存在未跨店铺组包的店铺 |
| InvalidParameter | The specified parameter “null#addressId” is not valid | addressId是必填的 |
| UnknownRuntimeException | The request has failed due to RPC runtime failure | weight需要填整数 |
| P-088-0000-00-15-231 | pick up collection point info missing | pickup\_collection的条件下pickUpCode必填 |
| P-088-0000-00-15-205 | param is null | self\_post的条件下sellerTrackingNumber必填 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/bigbag/commit)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/bigbag/commit

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/bigbag/commit");
request.addApiParameter("userInfo", "{\"appUserKey\":\"Lazada\u5F00\u653E\u5E73\u53F0\u4FE1\u606F\"}");
request.addApiParameter("orderCodeList", "[\"LZD1001\",\"LZD1002\",\"LZD1003\"]");
request.addApiParameter("weight", "100");
request.addApiParameter("client", "test");
request.addApiParameter("collectionInfo", "{\"pickUpCode\":\"pickupCode_001\"}");
request.addApiParameter("remark", "test");
request.addApiParameter("pickupInfo", "{\"courierCompany\":\"\u7533\u901A\",\"receiverPhone\":\"1888888888\",\"address\":{\"country\":\"\u4E2D\u56FD\",\"zipCode\":\"310012\",\"province\":\"\u6D59\u6C5F\u7701\",\"city\":\"\u676D\u5DDE\u5E02\",\"street\":\"\u848B\u6751\u8857\u9053\",\"district\":\"\u897F\u6E56\u533A\",\"detailAddress\":\"\u6587\u4E00\u897F\u8DEF680\u83DC\u9E1F\"},\"phone\":\"1760x000007\",\"name\":\"\u5F20\u4E09\",\"mobile\":\"098-234234\",\"email\":\"123@abc.com\",\"addressId\":\"3455657\"}");
request.addApiParameter("locale", "zh_CN");
request.addApiParameter("weightUnit", "g");
request.addApiParameter("type", "cainiao_pickup");
request.addApiParameter("sellerTrackingNumber", "B20127000438");
request.addApiParameter("returnInfo", "{\"fmReverseOption\":\"1\",\"address\":{\"country\":\"\u4E2D\u56FD\",\"zipCode\":\"3455657\",\"province\":\"\u6D59\u6C5F\u7701\",\"city\":\"\u676D\u5DDE\u5E02\",\"street\":\"\u848B\u6751\u8857\u9053\",\"district\":\"\u897F\u6E56\u533A\",\"detailAddress\":\"\u6587\u4E00\u897F\u8DEF680\u83DC\u9E1F\"},\"phone\":\"098-234234\",\"name\":\"\u5F20\u4E09\",\"mobile\":\"1760x000007\",\"email\":\"123@abc.com\",\"addressId\":\"3455657\"}");
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
      "handoverContentId": "20000001",
      "handoverContentCode": "LP000000123",
      "handoverOrderId": "10000001"
    },
    "success": "true",
    "errorCode": "P-088-0000-00-99-001",
    "errorMsg": "网络异常，请稍后重试"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
