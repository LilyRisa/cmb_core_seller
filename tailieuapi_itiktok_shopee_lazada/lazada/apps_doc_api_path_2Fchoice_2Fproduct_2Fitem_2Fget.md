# GETGetChoiceProductItem

> Source: https://open.lazada.com/apps/doc/api?path=%2Fchoice%2Fproduct%2Fitem%2Fget
> API path: /choice/product/item/get
> Category: Choice Customized API
> Scraped: 2026-05-21T00:09:49.259Z

---

Latest update2026-05-21 08:09:40

500

GetChoiceProductItem

GET

/choice/product/item/get

Authorization Required

Description:Get single product by ItemId or SellerSku.

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
| item\_id | Number | No | Call this API; Either "Item Id" or "Seller Sku" must be selected as the request parameter |
| seller\_sku | String | No | Call this API; Either "Item Id" or "Seller Sku" must be selected as the request parameter |
| site | String | Yes | The country site of the queried Product |
## Response Parameters

| Name | Type | Description |
| --- | --- | --- |
| data | Object | Response body |
| subStatus | String | product reject status |
| suspendedSkus | Object\[\] | An array contains at least one Suspended SKU. |
| variation | Object | self define attributes |
| variation1 | Object | self define attributes |
| name | String | self define attributes |
| has\_image | Boolean | self define attributes |
| customize | Boolean | self define attributes |
| options | String\[\] | self define attributes |
| label | String | self define attributes |
| variation2 | Object | self define attributes |
| name | String | self define attributes |
| has\_image | Boolean | self define attributes |
| customize | Boolean | self define attributes |
| options | String\[\] | self define attributes |
| label | String | self define attributes |
| variation3 | Object | self define attributes |
| name | String | self define attributes |
| has\_image | Boolean | self define attributes |
| customize | Boolean | self define attributes |
| options | String\[\] | self define attributes |
| label | String | self define attributes |
| variation4 | Object | self define attributes |
| name | String | self define attributes |
| has\_image | Boolean | self define attributes |
| customize | Boolean | self define attributes |
| options | String\[\] | self define attributes |
| label | String | self define attributes |
| primary\_category | Number | CategoryId |
| attributes | Object | Item attributes. |
| skus | Object\[\] | Sku List |
| item\_id | Number | Item Id |
| created\_time | String | create time |
| updated\_time | String | update time |
| images | String | product images List |
| marketImages | String | product market images |
| status | String | product status |
| trialProduct | Boolean | trial product |
| rejectReason | Object\[\] | rejectReason |
| hiddenReason | String | hiddenReason |
| hiddenStatus | String | hiddenStatus |
| bizSupplement | Object | bizSupplement |
## Error Code

| Error Code | Error Message | Solution |
| --- | --- | --- |
| No Data |
[API Testing Tool](//isvconsole.lazada.com/apps/console/test_api#/choice/product/item/get)[SDK Download](//isvconsole.lazada.com/apps/console/sdk_download)

GET

/choice/product/item/get

-   JAVA
    
-   PHP
    
-   .NET
    
-   RUBY
    
-   PYTHON
    
-   CURL
    

```
LazopClient client = new LazopClient(url, appkey, appSecret);
LazopRequest request = new LazopRequest();
request.setApiName("/choice/product/item/get");
request.setHttpMethod("GET");
request.addApiParameter("item_id", "2616344300");
request.addApiParameter("seller_sku", "Apple-6S-Black");
request.addApiParameter("site", "SG");
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
        "currency_unit": "CNY",
        "multiWarehouseInventories": [
          {
            "quantity": 20,
            "warehouseCode": "warehouseTest1"
          },
          {
            "quantity": 30,
            "warehouseCode": "warehouseTest2"
          }
        ],
        "sku_supply_price": 10.0,
        "package_width": "10.00",
        "special_to_time": "2020-02-0300:00",
        "special_from_time": "2015-07-3100:00",
        "package_height": "4.00",
        "package_length": "10.00",
        "package_weight": "0.04",
        "Available": 0,
        "SkuId": 314525867,
        "special_to_date": "2020-02-03"
      }
    ],
    "item_id": "234222211",
    "hiddenStatus": "Android \u0026 IOS",
    "bizSupplement": {
      "item_type": 1
    },
    "suspendedSkus": [],
    "subStatus": "Lock,Reject,Live_Reject,Admin",
    "variation": {
      "variation3": {
        "has_image": "false",
        "name": "Volume",
        "options": [],
        "label": "color",
        "customize": "false"
      },
      "variation4": {
        "has_image": "false",
        "name": "Size",
        "options": [],
        "label": "color",
        "customize": "false"
      },
      "variation1": {
        "has_image": "red",
        "name": "color_family",
        "options": [],
        "label": "color",
        "customize": "true"
      },
      "variation2": {
        "has_image": "false",
        "name": "SizeX",
        "options": [],
        "label": "color",
        "customize": "true"
      }
    },
    "trialProduct": "true,false",
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
      "brand": "Asante"
    },
    "hiddenReason": "The product cannot be displayed in the IOS system",
    "status": "Active,InActive,Pending QC,Suspended,Deleted"
  },
  "request_id": "0ba2887315178178017221014"
}
```
