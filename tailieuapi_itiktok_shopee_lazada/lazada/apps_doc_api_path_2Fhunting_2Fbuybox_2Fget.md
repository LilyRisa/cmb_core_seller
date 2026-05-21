# GET/POSTqueryBuyboxHuntingInfo

> Source: https://open.lazada.com/apps/doc/api?path=%2Fhunting%2Fbuybox%2Fget
> API path: /hunting/buybox/get
> Category: Seller API
> Scraped: 2026-05-20T23:05:46.799Z

---

Latest update2024-10-17 13:58:46

2068

queryBuyboxHuntingInfo

GET/POST

/hunting/buybox/get

No Authorization Required

Description:SPU竞价接口

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
| HuntingQueryParam | Object | Yes | param |
| venture | String | Yes | venture |
| skuId | String | Yes | skuId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | result |
| data | Object | data |
| venture | String | venture |
| itemId | String | itemId |
| skuId | String | skuId |
| isValid | String | 是否符合规则 0不符合 1符合 |
| priceRank | String | 价格在簇内排名 |
| retSuccess | Boolean | retSuccess |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/hunting/buybox/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/hunting/buybox/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/hunting/buybox/get");
request.addApiParameter("HuntingQueryParam", "{\"venture\":\"PH\",\"skuId\":\"12345\"}");
LazopResponse response = client.execute(request);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "result": {
    "data": {
      "itemId": "123456",
      "isValid": "1",
      "venture": "PH",
      "skuId": "567890",
      "priceRank": "2"
    },
    "retSuccess": "true"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
