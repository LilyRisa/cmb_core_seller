# GET/POSTServiceMarketAppKeyOrderQuery

> Source: https://open.lazada.com/apps/doc/api?path=%2Fservice%2Fmarket%2Forder%2Fquery
> API path: /service/market/order/query
> Category: Service Market API
> Scraped: 2026-05-21T00:09:07.424Z

---

Latest update2026-05-21 08:08:54

500

ServiceMarketAppKeyOrderQuery

GET/POST

/service/market/order/query

No Authorization Required

Description:Query user order list for specific App on Service Market

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
| endCreated | String | No | order create time range end |
| bizType | Number | No | biz type |
| bizOrderId | Number | No | bi order id |
| orderId | Number | No | order\_id |
| pageNo | Number | Yes | page no |
| itemCode | String | No | service market item code |
| pageSize | Number | Yes | page size |
| startCreated | String | No | order create time range start |
| articleCode | String | Yes | service market article code |
| shortCode | String | No | seller short code |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | null |
| data | Object | null |
| totalItem | String | total order count |
| articleBizOrders | Object\[\] | null |
| orderCycleStart | String | order cycle start |
| refundFee | String | refund fee |
| articleItemName | String | article item name |
| bizType | String | biz type |
| articleName | String | article name |
| totalPayFee | String | total pay fee |
| orderId | String | order id |
| orderCycleEnd | String | order cycle end |
| itemCode | String | item code |
| fee | String | fee |
| nick | String | seller nick |
| activityCode | String | promotion activity code |
| itemName | String | item name |
| orderCycle | String | order cycle |
| bizOrderId | String | biz order id |
| promFee | String | prom fee |
| create | String | order create time |
| articleCode | String | article code |
| userId | String | seller id |
| success | Boolean | is success |
| resultCode | String | result code |
| remark | String | remark |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/service/market/order/query)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET/POST

/service/market/order/query

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/service/market/order/query");
request.addApiParameter("endCreated", "2000-01-01 00:00:00");
request.addApiParameter("bizType", "1");
request.addApiParameter("bizOrderId", "123123");
request.addApiParameter("orderId", "1451231241");
request.addApiParameter("pageNo", "1");
request.addApiParameter("itemCode", "ts-1234-1");
request.addApiParameter("pageSize", "10");
request.addApiParameter("startCreated", "2000-01-01 00:00:00");
request.addApiParameter("articleCode", "ts-1234");
request.addApiParameter("shortCode", "TH123124");
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
      "totalItem": "2",
      "articleBizOrders": [
        {
          "orderCycleStart": "1655222400000",
          "refundFee": "null",
          "articleItemName": "默认收费项目",
          "bizType": "1",
          "articleName": "类目测试1",
          "totalPayFee": "0",
          "orderId": "317230195180544",
          "orderCycleEnd": "1656518400000",
          "itemCode": "FW_GOODS-1001204320-1",
          "fee": "3000",
          "userId": "31325235325",
          "nick": "aliqatest01",
          "activityCode": "null",
          "itemName": "null",
          "orderCycle": "0个月",
          "bizOrderId": "0",
          "promFee": "3000",
          "create": "1655292812000",
          "articleCode": "FW_GOODS-1001204320"
        }
      ]
    },
    "success": "true",
    "resultCode": "test",
    "remark": "test"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
