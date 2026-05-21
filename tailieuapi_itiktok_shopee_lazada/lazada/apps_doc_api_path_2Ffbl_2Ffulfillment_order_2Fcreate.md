# POSTCreateFulfillmentOrderForMCL

> Source: https://open.lazada.com/apps/doc/api?path=%2Ffbl%2Ffulfillment_order%2Fcreate
> API path: /fbl/fulfillment_order/create
> Category: FBL API
> Scraped: 2026-05-20T23:36:02.810Z

---

Latest update2022-07-26 00:16:43

3751

CreateFulfillmentOrderForMCL

POST

/fbl/fulfillment\_order/create

Authorization Required

Description:Create Fulfillment Order

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
| platform\_payment\_method | String | Yes | Payment method, mainly check cod type |
| remark | String | No | Remark |
| currency | String | Yes | Currency |
| items | Object\[\] | Yes | Fulfillment order line list, contains no more than 300 items |
| paid\_price | String | Yes | Item paid price |
| platform\_delivery\_type | String | Yes | Delivery type (this is always standard for now) |
| platform\_item\_id | String | Yes | Unique item level identifier for fulfilment order |
| sku | String | No | Sku |
| owner\_id | String | Yes | Shipper id |
| shipping\_type | String | Yes | Distribution type (this is always warehouse) |
| fulfillment\_sku\_id | String | Yes | Fulfillment sku id |
| quantity | Number | Yes | Quantity (this is always 1) |
| store\_code | String | Yes | Distribution of warehouse |
| unit\_price | String | Yes | Item unit price |
| warehouse\_promised\_time | String | No | Warehouse promised estimated arrival time in UTC |
| promised\_max\_time | String | No | Promised max estimated arrival time in UTC |
| promised\_min\_time | String | No | Promised min estimated arrival time in UTC |
| platform\_sub\_trade\_id | String | No | Trade platform sub trade order id |
| category\_name | String | No | Item category name |
| fulfillment\_priority | Boolean | No | Fulfillment priority |
| receiver | Object | Yes | Receiver info |
| zip\_code | String | No | Zip code |
| country\_iso | String | Yes | iso-3166-1 country code |
| country | String | No | Receiver country |
| province | String | No | Receiver province |
| city | String | No | Receiver city |
| district | String | No | Receiver district |
| town | String | No | Receiver town |
| detail\_address | String | Yes | Receiver detail address |
| area\_id | String | No | Receiver area id from LEL |
| division\_id | String | No | Receiver division id from LEL |
| address\_id | String | Yes | Receiver address id from LEL |
| mobile\_phone | String | Yes | Receiver mobile phone |
| telephone | String | No | Receiver telephone |
| company\_name | String | No | Receiver company name |
| contact\_name | String | Yes | Receiver cantact name |
| email | String | Yes | Receiver email |
| platform\_name | String | Yes | Trade platform name |
| fulfillment\_finish\_time | String | No | Estimated warehouse outbound time in UTC |
| platform\_order\_creation\_time | String | Yes | Trade order create time in UTC |
| sales\_order\_number | String | Yes | Sales order number from platform |
| platform\_order\_id | String | Yes | Unique order level identifier for fulfilment order |
| out\_order\_creation\_time | String | No | Out fulfillment order create time in UTC |
| is\_platform\_nominated\_fleet | Boolean | No | Whether platform nominated fleet |
| seller\_store\_id | String | No | seller store id |
| seller\_store\_name | String | No | seller store name |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| success | Boolean | Is success |
| error\_code | String | Error code |
| error\_message | String | Error message |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/fbl/fulfillment_order/create)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

POST

/fbl/fulfillment\_order/create

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/fbl/fulfillment_order/create");
request.addApiParameter("platform_payment_method", "COD");
request.addApiParameter("remark", "remark");
request.addApiParameter("currency", "THB");
request.addApiParameter("items", "[{\"paid_price\":\"1.2\",\"platform_delivery_type\":\"standard\",\"store_code\":\"123\",\"quantity\":\"1\",\"category_name\":\"Phone\",\"fulfillment_sku_id\":\"1234\",\"owner_id\":\"1234\",\"platform_sub_trade_id\":\"1211228761\",\"fulfillment_priority\":\"FALSE\",\"shipping_type\":\"warehouse\",\"unit_price\":\"123.3\",\"warehouse_promised_time\":\"2018-12-13T18:18:06Z\\t\",\"promised_min_time\":\"2018-12-13T18:18:06Z\\t\",\"promised_max_time\":\"2018-12-13T18:18:06Z\\t\",\"sku\":\"sku\",\"platform_item_id\":\"OF04292067556371\"}]");
request.addApiParameter("receiver", "{\"country\":\"China\",\"contact_name\":\"Nerom\",\"town\":\"Downtown\",\"city\":\"Shangqiu\",\"detail_address\":\"Chunshui Road No. 1\\t\",\"address_id\":\"1234\",\"division_id\":\"1234\",\"telephone\":\"1234567890\",\"area_id\":\"1234\",\"zip_code\":\"101100\",\"province\":\"Henan\",\"mobile_phone\":\"123456789\\t\",\"district\":\"Zhecheng\",\"company_name\":\"Lazada\",\"country_iso\":\"CN\",\"email\":\"nerom@email.com\"}");
request.addApiParameter("platform_name", "TEST_TH");
request.addApiParameter("fulfillment_finish_time", "2018-12-14T18:18:32Z");
request.addApiParameter("platform_order_creation_time", "2019-12-11T11:40:53Z");
request.addApiParameter("sales_order_number", "LP666666");
request.addApiParameter("platform_order_id", "LP201912131233");
request.addApiParameter("out_order_creation_time", "2018-12-14T18:18:32Z");
request.addApiParameter("is_platform_nominated_fleet", "false");
request.addApiParameter("seller_store_id", "001");
request.addApiParameter("seller_store_name", "TEST001");
LazopResponse response = client.execute(request, accessToken);
System.out.println(response.getBody());
Thread.sleep(10);
```

Response

* * *

```
{
  "error_message": "Error message",
  "code": "0",
  "success": "TRUE",
  "error_code": "Error code",
  "request_id": "0ba2887315178178017221014"
}
```
