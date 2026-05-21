# GETQueryFulfillmentOrderForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_order_list%2Fget
> API path: /fbl/fulfillment_order_list/get
> Category: FBL API
> Scraped: 2026-05-20T23:42:47.251Z

---

Latest update2026-05-21 07:42:34

500

QueryFulfillmentOrderForMCL

GET

/fbl/fulfillment\_order\_list/get

Authorization Required

Description:Query list of Fulfillment Orders by shipper

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
| platform\_order\_id | String | No | Order level identifier for fulfilment order, unique for idempotence |
| platform\_name | String | Yes | Trade platform name |
| per\_page | Number | Yes | Page size |
| page | Number | Yes | Page index |
| sales\_order\_number | String | No | Sales order number from platform |
| status | String | No | Status |
| create\_start\_time | String | Yes | Order create time lower bound |
| create\_end\_time | String | Yes | Order create time upper bound |
| delivery\_type | String | No | Delivery type |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success |
| error\_code | String | Error code |
| error\_message | String | Error message |
| per\_page | Number | Page size |
| page | Number | Page index |
| total\_count | Number | Total count |
| data | Object\[\] | Result order list |
| sales\_order\_number | String | Sales order number from platform |
| platform\_order\_id | String | Unique order level identifier for fulfilment order |
| create\_time | String | Create time |
| items | Object\[\] | Result item list |
| platform\_item\_id | String | Unique item level identifier for fulfillment order |
| fulfillment\_sku\_id | String | Item id |
| status | String | logistics status: created created\_failed send\_to\_warehouse handled\_by\_warehouse ready\_to\_shipped shipped inbound delivery\_failed failed delivered canceled request\_failed oos |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_order_list/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/fbl/fulfillment\_order\_list/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_order_list/get");
request.setHttpMethod("GET");
request.addApiParameter("platform_order_id", "OF02282036214681");
request.addApiParameter("platform_name", "LAZADA_TH");
request.addApiParameter("per_page", "10");
request.addApiParameter("page", "1");
request.addApiParameter("sales_order_number", "orderxxxx");
request.addApiParameter("status", "created");
request.addApiParameter("create_start_time", "2019-12-04T18:18:32Z");
request.addApiParameter("create_end_time", "2019-12-04T18:18:32Z");
request.addApiParameter("delivery_type", "standard");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Error message",
  "per_page": "20",
  "code": "0",
  "data": [
    {
      "sales_order_number": "orderxxxx",
      "platform_order_id": "OF02282036214681",
      "create_time": "2019-12-04T18:18:32Z",
      "items": [
        {
          "fulfillment_sku_id": "1234",
          "platform_item_id": "OF04292067556371",
          "status": "send_to_warehouse"
        }
      ]
    }
  ],
  "success": "TRUE",
  "total_count": "100",
  "error_code": "Error code",
  "page": "1",
  "request_id": "0ba2887315178178017221014"
}
```
