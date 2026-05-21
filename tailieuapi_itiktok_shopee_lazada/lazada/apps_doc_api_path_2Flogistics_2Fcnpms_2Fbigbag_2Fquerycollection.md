# GET/POSTLazadaBigbagCollectionPoints

> Source: https://open.lazada.com/apps/doc/api?path=%2Flogistics%2Fcnpms%2Fbigbag%2Fquerycollection
> API path: /logistics/cnpms/bigbag/querycollection
> Category: FirstMile Bigbag(only for CN)
> Scraped: 2026-05-20T23:30:27.976Z

---

Latest update2022-07-29 14:38:25

3230

LazadaBigbagCollectionPoints

GET/POST

/logistics/cnpms/bigbag/querycollection

Authorization Required

Description:Lazada bigbag query collection points

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
| pageSize | String | No | 每页N条 |
| currentPage | String | No | 当前第N页 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | 同步响应结果 |
| data | Object | 返回结构体 |
| currentPageIndex | Number | 当前页 |
| pageTotalNum | Number | 总页数 |
| pageSize | Number | 页大小 |
| totalCount | Number | 集货点总量 |
| itemList | Object\[\] | 返回集货点信息 |
| success | Boolean | 是否成功，true:成功，false:失败 |
| errorCode | String | 错误码 |
| erroMsg | String | 错误描述 |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| IllegalAccessToken | The specified access token is invalid or expired | Token过期或输入有误 |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/logistics/cnpms/bigbag/querycollection)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/logistics/cnpms/bigbag/querycollection

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/logistics/cnpms/bigbag/querycollection");
request.addApiParameter("pageSize", "1");
request.addApiParameter("currentPage", "10");
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
    "data": {
      "pageSize": "10",
      "itemList": [],
      "totalCount": "10",
      "currentPageIndex": "1",
      "pageTotalNum": "1"
    },
    "success": "true",
    "errorCode": "P-088-0000-00-99-001"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
