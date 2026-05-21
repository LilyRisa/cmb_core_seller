# POSTdigitalServiceCdkCodeReceived

> Source: https://open.lazada.com/apps/doc/api?path=%2Fdigital%2Fservice%2FcdkCodeReceived
> API path: /digital/service/cdkCodeReceived
> Category: Lazada DG API
> Scraped: 2026-05-21T00:02:43.081Z

---

Latest update2026-04-10 17:40:57

566

digitalServiceCdkCodeReceived

POST

/digital/service/cdkCodeReceived

No Authorization Required

Description:接受码商发码请求，给用户发送码。

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
| access\_token | String | No | API interface call credentials |
| sign\_method | String | Yes | The HMAC hash algorithm you are using to calculate your signature |
| sign | String | Yes | Part of the authentication process that is used for identifying and verifying who is sending a request (click [here](https://open.lazada.com/apps/doc/doc?nodeId=10450&docId=108068) for details) |
## Parameters

| Name | Type | Required or not | Description |
| --- | --- | --- | --- |
| tb\_order\_id | String | Yes | 淘天主订单号 |
| cdk\_name | String | No | 商品名称 |
| cdk\_code\_items | Object\[\] | Yes | CDK码对象 |
| cdk\_card\_no | String | No | CDK卡号 |
| cdk\_code\_key | String | Yes | CDK密钥/兑换码 |
| tb\_order\_line\_id | String | Yes | 淘天子订单号 |
| valid\_from | String | No | 有效期起始时间，YYYY-MM-DD格式 |
| cdk\_code\_number | String | Yes | CDK码数量 |
| valid\_end | String | No | 有效期结束时间，YYYY-MM-DD格式 |
| terms\_use | String | No | 使用规则/条款等说明 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result\_code | String | 响应状态码 |
| result\_msg | String | 响应描述信息 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/digital/service/cdkCodeReceived)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/digital/service/cdkCodeReceived

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/digital/service/cdkCodeReceived");
request.addApiParameter("tb_order_id", "2026032600001");
request.addApiParameter("cdk_name", "Steam\u5145\u503C\u5361");
request.addApiParameter("cdk_code_items", "[{\"cdk_card_no\":\"1234567890\",\"cdk_code_key\":\"ABCD-EFGH-IJKL-MNOP\"}]");
request.addApiParameter("tb_order_line_id", "2026032600001");
request.addApiParameter("valid_from", "2027-03-15");
request.addApiParameter("cdk_code_number", "2");
request.addApiParameter("valid_end", "2027-03-15");
request.addApiParameter("terms_use", "\u672CCDK\u4EC5\u9650\u5355\u6B21\u4F7F\u7528...");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result_msg": "success",
  "code": "0",
  "result_code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
