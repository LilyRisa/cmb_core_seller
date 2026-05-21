# GETOrderCancelValidate

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Freverse%2Fcancel%2Fvalidate
> API path: /order/reverse/cancel/validate
> Category: Order API
> Scraped: 2026-05-20T23:24:24.017Z

---

Latest update2022-08-01 18:01:08

13453

OrderCancelValidate

GET

/order/reverse/cancel/validate

Authorization Required

Description:Seller can check whether the order can be canceled through this API and get corresponding reasons if not.

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
| order\_id | String | Yes | order id |
| order\_item\_id\_list | String\[\] | Yes | all order items need to be cancel |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | data |
| tip\_content | String | tip content when cancel order |
| tip\_type | String | warn or error |
| reason\_options | Object\[\] | reason options |
| reason\_name | String | reason name |
| reason\_id | String | Since the cancellation reasons supported by each order may be different, if you want reason\_id, you need to call this interface to get |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 102 | E0102: trade order line id is empty or invalid | E0102: trade order line id is empty or invalid |
| 106 | E0106: ROC internal error | E0106: ROC internal error |
| 115 | E0115: order id is null | E0115: order id is null |
| 116 | E0116: no seller id | E0116: no seller id |
| 117 | E0117: no user id | E0117: no user id |
| 118 | E0118: no user email | E0118: no user email |
| 122 | E0122: invalid trade order | E0122: invalid trade order |
| 123 | E0123: invalid trade order lines %s | E0123: invalid trade order lines %s |
| 124 | E0124: invalid seller id for this order line %s | E0124: invalid seller id for this order line %s |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/reverse/cancel/validate)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/order/reverse/cancel/validate

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/reverse/cancel/validate");
request.setHttpMethod("GET");
request.addApiParameter("order_id", "0");
request.addApiParameter("order_item_id_list", "[]");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "code": "0",
  "data": {
    "tip_content": "stock will be set as 0",
    "reason_options": [
      {
        "reason_name": "out of stock",
        "reason_id": "1000017"
      }
    ],
    "tip_type": "warn"
  },
  "request_id": "0ba2887315178178017221014"
}
```
