# POSTReturnOrderCreation

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Freturns%2Fcreate
> API path: /fbl/returns/create
> Category: FBL API
> Scraped: 2026-05-20T23:43:56.780Z

---

Latest update2022-07-29 17:18:27

3032

ReturnOrderCreation

POST

/fbl/returns/create

Authorization Required

Description:Api to create customer returns

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
| tracking | Object | Yes | tracking |
| origin | Object | Yes | origin |
| location | Object | Yes | location |
| address | String | Yes | Address |
| address\_id | String | Yes | Address ID |
| details | String | No | Additional details of the location |
| tracking\_number | String | Yes | Tracking Number |
| platform\_name | String | Yes | Platform Name |
| platform\_order\_creation\_time | String | Yes | Sales order creation time of platform side Datetime format: 2017-11-17T10:14:13.185Z |
| return\_comment | String | Yes | Customer comments accompanying the return order, will be used as reference during quality check |
| return\_delivery\_type | String | Yes | Return delivery type (always return\_by\_customer) |
| return\_order\_number | String | Yes | Return order number from platform; must be unique |
| sales\_order\_number | String | Yes | Sales order number accompanying the original fulfilment order request |
| currency | String | Yes | Currency |
| customer | Object | Yes | customer info |
| phone | String | Yes | Customer phone |
| email | String | No | Customer email |
| name | String | Yes | Customer name |
| platform\_order\_id | String | Yes | Return order id - unique order level Identifier used to send return order and item status notification events |
| parcel | Object | Yes | parcel |
| items | Object\[\] | Yes | items |
| name | String | Yes | Item name |
| paid\_price | String | No | Paid Price Minimum value : 0 |
| platform\_item\_id | String | Yes | Return item id - unique item level Identifier used to send return item status notification events |
| quantity | Number | Yes | Quantity Minimum value : 1 |
| return\_reason | String | Yes | Return reason (please refer to list of return reasons below) |
| return\_type | String | Yes | Return Type (always normal) |
| seller\_return\_policy | String | Yes | Seller return policy (free text) |
| sku | String | Yes | Fulfillment SKU id |
| unit\_price | String | Yes | Price of a single unit Minimum value : 0 |
| weight | String | Yes | Weight of a single unit in grams Minimum value : 0 |
| width | String | Yes | Width in cm Minimum value : 0 |
| delivery\_package\_id | String | Yes | Package indentifier used to deliver original sales order item to customer |
| fulfillment\_type | String | Yes | Fulfillment type (always MCL) |
| height | String | Yes | Height in cm. Minimum value : 0 |
| length | String | Yes | Length in cm. Minimum value : 0 |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | result |
| return\_id | String | Reference return id used for further communication, like updating return cancellation status. It must be saved on client side. |
| success | Boolean | is success |
| error\_code | String | error code |
| error\_message | String | error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/returns/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/returns/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/returns/create");
request.addApiParameter("tracking", "{\"origin\":{\"location\":{\"address\":\"xyz address\",\"address_id\":\"R80160375\",\"details\":\"xyz address\"}},\"tracking_number\":\"611892047371\"}");
request.addApiParameter("platform_name", "SHOPEE_ID");
request.addApiParameter("platform_order_creation_time", "2017-11-17T10:14:13.185Z");
request.addApiParameter("return_comment", "wrong size");
request.addApiParameter("return_delivery_type", "return_by_customer");
request.addApiParameter("return_order_number", "206611892047371");
request.addApiParameter("sales_order_number", "206611892047371");
request.addApiParameter("currency", "PHP");
request.addApiParameter("customer", "{\"phone\":\"999999999\",\"name\":\"John Doe\",\"email\":\"xyz@example.com\"}");
request.addApiParameter("platform_order_id", "4592129765330");
request.addApiParameter("parcel", "{\"items\":[{\"paid_price\":\"10\",\"return_type\":\"normal\",\"return_reason\":\"10505\",\"quantity\":\"1\",\"seller_return_policy\":\"7 days easy return\",\"length\":\"10\",\"weight\":\"1000.0\",\"unit_price\":\"3316.01\",\"fulfillment_type\":\"MCL\",\"delivery_package_id\":\"34abb0e9-05bf-4503-b47f-22ddfe0b8ac8\",\"name\":\"Sample Product 1\",\"width\":\"10.0\",\"sku\":\"308990418\",\"platform_item_id\":\"OF04592182434859\",\"height\":\"10\"},{\"paid_price\":\"10\",\"return_type\":\"normal\",\"return_reason\":\"10505\",\"quantity\":\"1\",\"seller_return_policy\":\"7 days easy return\",\"length\":\"10\",\"weight\":\"1000.0\",\"unit_price\":\"3316.01\",\"fulfillment_type\":\"MCL\",\"delivery_package_id\":\"34abb0e9-05bf-4503-b47f-22ddfe0b8ac8\",\"name\":\"Sample Product 1\",\"width\":\"10.0\",\"sku\":\"308990418\",\"platform_item_id\":\"OF04592182434859\",\"height\":\"10\"}]}");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "bad request",
  "code": "0",
  "data": {
    "return_id": "123e4567-e89b-12d3-a456-426655440000"
  },
  "success": "TRUE",
  "error_code": "400",
  "request_id": "0ba2887315178178017221014"
}
```
