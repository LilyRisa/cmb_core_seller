# GETGetProducts

> Source: https://open.lazada.com/apps/doc/api?path=%2Fproducts%2Fget
> API path: /products/get
> Category: Product API
> Scraped: 2026-05-20T23:09:11.040Z

---

Latest update2022-07-28 17:06:23

77424

GetProducts

GET

/products/get

Authorization Required

Description:Use this API to get detailed information of the specified products.

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
| filter | String | No | Returns the products with the status matching this parameter. Possible values are all, live, inactive, deleted, pending, rejected, sold-out. Mandatory. |
| update\_before | String | No | Limits the returned product list to those updated before or on a specified date, given in ISO 8601 date format. Optional |
| create\_before | String | No | Limits the returned products to those created before or on the specified date, given in ISO 8601 date format. Optional |
| offset | String | No | Deprecated(The number of Items you want to skip before you start counting),It is recommended to use date for scrolling query.The maximum offset is 10000 |
| create\_after | String | No | Limits the returned products to those created after or on the specified date, given in ISO 8601 date format. Optional |
| update\_after | String | No | Limits the returned products to those updated after or on the specified date, given in ISO 8601 date format. Optional |
| limit | String | No | The number of Items you would like to fetch from every response,The maximum is 50. |
| options | String | No | This value can be used to get more stock information. e.g., Options=1 means contain ReservedStock, RtsStock, PendingStock, RealTimeStock, FulfillmentBySellable. |
| sku\_seller\_list | String | No | Only products that have the Seller SKU in this list will be returned. Input should be a JSON array. For example, \["Apple 6S Gold", "Apple 6S Black"\]. It only matches the whole words. A maximum of 100 SKUs can be returned. |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| total\_products | Number | The number of total Items, it's item level. |
| products | Object\[\] | An array contains at least one Product. |
| primary\_category | Number | The ID of the primary category for his product. |
| attributes | Object | Contains several product attributes. |
| skus | Object\[\] | An array contains at least one SKU. |
| item\_id | Number | The ID of this product |
| created\_time | String | create time |
| updated\_time | String | update time |
| images | String | product images |
| marketImages | String | product market images |
| status | String | product status |
| subStatus | String | product reject status |
| suspendedSkus | Object\[\] | An array contains at least one Suspended SKU. |
| trialProduct | Boolean | Whether product is trial product |
| rejectReason | Object\[\] | rejectReason |
| hiddenReason | String | hiddenReason |
| hiddenStatus | String | hiddenStatus |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| 5 | E005: Invalid Request Format | The request URL is not valid. |
| 6 | E006: Unexpected internal error | Unexpected internal error. |
| 14 | E014: "%s" Invalid Offset | The value for the offset parameter is not valid. |
| 17 | E017: "%s" Invalid Date Format | The date format is not valid. |
| 19 | E019: "%s" Invalid Limit | The value for the limit parameter is not valid. |
| 36 | E036: Invalid status filter | The specified status filter is not valid. |
| 70 | E070: You have corrupt data in your sku seller list. | Data in the SKU list are not valid. |
| 506 | E506: Get product failed | Failed to get the product information. |
| 901 | E901: The request is too frequent, or the requested functionality is temporarily disabled. | Failed to return the requested data due to high calling frequency or disabled functionality. Please try again later. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| SellerNotVerified | Seller not verified,please check seller status | The seller's store opening process has not been completed, please log in to the Seller Center, check the store information that needs to be improved on the home page and submit it for review. |
| 901 | Limit service request speed in server side temporarily. | API level QPS limiting flow, please retry in the next second when you encounter this error. |
| 19 | Invalid Limit | The limit field value is incorrect and should not exceed a maximum of 50. |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/products/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/products/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/products/get");
request.setHttpMethod("GET");
request.addApiParameter("filter", "live");
request.addApiParameter("update_before", "2018-01-01T00:00:00+0800");
request.addApiParameter("create_before", "2018-01-01T00:00:00+0800");
request.addApiParameter("offset", "0");
request.addApiParameter("create_after", "2010-01-01T00:00:00+0800");
request.addApiParameter("update_after", "2010-01-01T00:00:00+0800");
request.addApiParameter("limit", "10");
request.addApiParameter("options", "1");
request.addApiParameter("sku_seller_list", " [\"39817:01:01\", \"Apple 6S Black\"]");
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
    "total_products": "10",
    "products": [
      {
        "created_time": "1611554725000",
        "updated_time": "1611554725000",
        "images": "[     \"https://my-live.slatic.net/p/540bc796d1eadf316018038d8840f20a.jpg\",     \"https://my-live.slatic.net/p/8913fc357e139ef78ad2f071e9586334.jpg\" ]",
        "skus": [
          {
            "Status": "active",
            "quantity": 0,
            "product_weight": "0.03",
            "Images": [
              "http://sg-live-01.slatic.net/p/BUYI1-catalog.jpg",
              "",
              "",
              "",
              "",
              "",
              "",
              ""
            ],
            "SellerSku": "39817:01:01",
            "ShopSku": "BU565ELAX8AGSGAMZ-1104491",
            "Url": "https://alice.lazada.sg/asd-1083832.html",
            "package_width": "10.00",
            "special_to_time": "2020-02-0300:00",
            "special_from_time": "2015-07-3100:00",
            "package_height": "4.00",
            "special_price": 9,
            "price": 32,
            "package_length": "10.00",
            "package_weight": "0.04",
            "Available": 0,
            "SkuId": 314525867,
            "special_to_date": "2020-02-03"
          }
        ],
        "item_id": "180226526",
        "hiddenStatus": "Android \u0026 IOS",
        "suspendedSkus": [],
        "subStatus": "Lock,Reject,Live Reject,Admin",
        "trialProduct": "false",
        "rejectReason": [
          {
            "suggestion": "",
            "violationDetail": "Wrong Description,Price Not Reasonable,Wrong Image, No White Background:Wrong image resolution"
          }
        ],
        "primary_category": "10000211",
        "marketImages": "[     \"https://my-live.slatic.net/p/540bc796d1eadf316018038d8840f20a.jpg\",     \"https://my-live.slatic.net/p/8913fc357e139ef78ad2f071e9586334.jpg\" ]",
        "attributes": {
          "short_description": "\u003cul\u003e\u003cli\u003easdasd\u003c/li\u003e\u003c/ul\u003e",
          "name": "asd",
          "description": "\u003cp\u003easd\u003c/p\u003e\n",
          "name_engravement": "No",
          "warranty_type": "International Manufacturer",
          "gift_wrapping": "No",
          "preorder_days": 25,
          "brand": "Asante",
          "preorder": "Yes"
        },
        "hiddenReason": "The product cannot be displayed in the IOS system",
        "status": "Active,InActive,Pending QC,Suspended,Deleted"
      }
    ]
  },
  "request_id": "0ba2887315178178017221014"
}
```
