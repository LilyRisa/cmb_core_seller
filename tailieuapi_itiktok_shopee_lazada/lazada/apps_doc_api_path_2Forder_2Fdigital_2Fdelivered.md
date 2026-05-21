# POSTDeliverDigital

> Source: https://open.lazada.com/apps/doc/api?path=%2Forder%2Fdigital%2Fdelivered
> API path: /order/digital/delivered
> Category: Fulfillment API
> Scraped: 2026-05-20T23:26:22.156Z

---

Latest update2022-08-09 14:20:23

9559

DeliverDigital

POST

/order/digital/delivered

Authorization Required

Description:Use this API to mark a digital order item as being delivered.

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
| digitalDeliveryReq | Object | Yes | request body |
| orders | Object\[\] | Yes | Batch size is limited to 20, deliver orders |
| order\_item\_list | Number\[\] | Yes | order item list |
| order\_id | Number | Yes | orderId |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| result | Object | resp body |
| data | Object | resp body |
| orders | Object\[\] | deliver order |
| order\_item\_list | Object\[\] | order item deliver result |
| msg | String | error msg |
| order\_item\_id | Number | order item id |
| item\_err\_code | String | 0=success other=error code |
| retry | Boolean | Determine if the order can be retried |
| order\_id | Number | order id |
| success | Boolean | process result,If this is true, it doesn't mean that everything is processed successfully. It is necessary to judge that the item\_err\_code in orders is equal to 0 to determine that the processing is successful. Otherwise, if this is false, this batch must be unsuccessful. |
| errorCode | String | exists when success is false |
| errorMsg | String | exists when success is false |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/order/digital/delivered)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/order/digital/delivered

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/order/digital/delivered");
request.addApiParameter("digitalDeliveryReq", "{\"orders\":[{\"order_item_list\":[\"[]\",\"[]\"],\"order_id\":\"2342342\"},{\"order_item_list\":[\"[]\",\"[]\"],\"order_id\":\"2342342\"}]}");
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
      "orders": [
        {
          "order_item_list": [
            {
              "msg": "order item not found or not belong to digital!",
              "order_item_id": "526170322294184",
              "item_err_code": "700020",
              "retry": "false"
            }
          ],
          "order_id": "526170322194184"
        }
      ]
    },
    "success": "true",
    "errorCode": "700019",
    "errorMsg": "order can\u0027t be null"
  },
  "code": "0",
  "request_id": "0ba2887315178178017221014"
}
```
