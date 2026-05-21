# Instant Mart Integration Guide

> Source: https://open.shopee.com/developer-guide/643
> Category: 
> Scraped: 2026-05-20T20:38:52.718Z

---

This document introduces the process for product listing, order management, income reporting, and OpenAPI integration/testing flow on Instant Mart.

# Instruction of the Instant Mart Project

# Background:

Instant Mart is a special shop structure designed to support retailers who operate both central management and branch-level operations. Under this model:

-   Each Mart Shop (Official Shop) acts as the headquarters, responsible for managing global SKUs, creating items, and viewing financial reports.  
      
      
    
-   Multiple Outlets (Branch Shops) exist under the mart, representing individual shop locations. Outlets are responsible for handling day-to-day operations, such as managing orders, maintaining stock, and packing parcels for riders to pick up.  
      
      
    

With the Open API integration, Mart retailers can choose whether to manage SKUs at the merchant level (shared across all outlets) or at the outlet level (independent per branch).

Before integrating with Open API, retailers must onboard both the mart merchant and its outlet shops in the BDC portal, ensuring they are registered as Instant Mart Shops. This setup allows developers to build API solutions that support the full operational flow of Instant Mart retailers.

# 1\. Sandbox Test Account Setup

## 1.1 Create Sandbox Test Account

  

You can start by accessing the [Test Account](https://open.shopee.com/myconsole/tools/test-account) page on Shopee Open Platform Console >Select Mart&Outlet shops module to create test shops.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=4TzCsTwtDQ7%2BbrHmBiv2wO74k7zl3ocgiObjIuqYeHHm6DP%2BF%2BY1BFilYTGbZ5kJyJk0ZnIJaPE2ymmpoKgxxQ%3D%3D&image_type=png)

Note: Instant Mart sandbox testing requires a two-step whitelist: Sandbox V2 must be enabled before Mart & Outlet Shop whitelist.

  

  

# 2\. Grant authorization

## 2.1 Shop Authorization

Find the test Partner\_id on the App List page:

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=yEKxhEtVO5NjTjcEj0vZW5ft8AW0c2LH%2Ff8lhCtV2kWT8MDAdMQjo6rDYXHYfE%2FCp2FNqzobx8b428sxRKCymA%3D%3D&image_type=png)

Click Authorize → log in with Mart account to complete authorization for Mart and Outlet shop at the same time.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=%2F%2B3zcfqIRFEFtd%2FlWpYcj6N2hOfAkrxSbh2AzkqAhrvRlxlEhxot8OoMyjrzJyp1B6Ht3jk%2FzshoXEXDU1A%2FBQ%3D%3D&image_type=png)

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=U9N3Nqqn2Gq5NgqFeD54EbO9I%2B1ek9mYRf2wfGDDKhCnUAJjcPKaikkbSHf1FUm3iFEWMp4C%2BD23puxgY9%2BOjg%3D%3D&image_type=png)

Refer to the [Authorization and authentication](https://open.shopee.com/developer-guide/20) article for more information.

# 3\. Testing Process

## 3.1 Open API Testing

Use the API Test Tool or use Postman to test open API.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=6z6B8dxwUHyrzbPC2KsRwYC8uH7I%2BE%2BoJ6hHhGIZYhMwcGdEscf3JfzpnzMLwvRQxAqw7a8QI0%2FSmOfLL2yNrA%3D%3D&image_type=png)

  

  

  

  

  

  

## 3.2 Key Open API Functions

  

Key API functionalities required for ID Mart Project:

<table><tbody><tr><td colspan="" rowspan=""><span><span>Section</span></span><span><br><span></span></span></td><td colspan="" rowspan=""><span><span>Sub_Section</span></span></td></tr><tr><td colspan="" rowspan="6"><span><span>Product</span></span></td><td colspan="" rowspan=""><span><span>Preparation for New SKU</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Creating New SKU</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Update Existing SKU</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Creating SKU Variants</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Stock &amp; Price Management</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>General SKU Management</span></span></td></tr><tr><td colspan="" rowspan="5"><span><span>Order</span></span></td><td colspan="" rowspan=""><span><span>Get Order List &amp; Details</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Cancelling Order</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Request Shipment</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Generate Tracking Number</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Generate Airway Bill</span></span></td></tr><tr><td colspan="" rowspan="3"><span><span>Return &amp; Refund</span></span></td><td colspan="" rowspan=""><span><span>Get Return List &amp; Details</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Accept Return/Refund</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Dispute Return/Refund</span></span></td></tr><tr><td colspan="" rowspan="4"><span><span>Financials</span></span></td><td colspan="" rowspan=""><span><span>Get Escrow Detail</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Get Wallet Transaction</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Generate Income Statement</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Generate Income Report</span></span></td></tr><tr><td colspan="" rowspan="2"><span><span>Shop Setting</span></span></td><td colspan="" rowspan=""><span><span>Shop Profile Update</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>Shop Operational Hours</span></span></td></tr></tbody></table>

### 3.2.1 Mart&Outlet shop relationship

Check relationship: v2.shop.get\_shop\_info  
With mart\_shop\_id → returns all outlet shop IDs under the Mart.  
With outlet\_shop\_id → returns the corresponding Mart shop ID.

3.2.1.1 Use v2.shop.get\_shop\_info with a Mart Shop

  

When calling v2.shop.get\_shop\_info with a mart\_shop\_id, it will return a list of all Outlet shop IDs under that Mart shop.

  

Response example:

{

   "auth\_time": 1755082218,

   "error": "",

   "expire\_time": 1786636799,

   "is\_cb": false,

   "is\_direct\_shop": false,

   "is\_main\_shop": false,

  "is\_mart\_shop": true,

   "is\_one\_awb": false,

   "is\_outlet\_shop": false,

   "is\_sip": false,

   "is\_upgraded\_cbsc": false,

   "linked\_direct\_shop\_list": \[\],

   "linked\_main\_shop\_id": 0,

   "merchant\_id": null,

   "message": "",

  "outlet\_shop\_info\_list": \[

       {

           "outlet\_shop\_id": 225622030

       },

       {

           "outlet\_shop\_id": 225622031

       },

       {

           "outlet\_shop\_id": 225622032

       },

       {

           "outlet\_shop\_id": 225622033

       },

       {

           "outlet\_shop\_id": 225622034

       }

   \],

   "region": "TH",

   "request\_id": "e3e3e7f33c4e6283bc127a54263d8c01",

   "shop\_fulfillment\_flag": "Others",

   "shop\_name": "op\_64564962416254514c64",

   "status": "NORMAL"

}

  

3.2.1.2 Use v2.shop.get\_shop\_info with a Outlet Shop

When calling v2.shop.get\_shop\_info with an outlet\_shop\_id, it will return the corresponding Mart shop ID.

  

Response sample:

{

   "auth\_time": 1755158035,

   "error": "",

   "expire\_time": 1786723199,

   "is\_cb": false,

   "is\_direct\_shop": false,

   "is\_main\_shop": false,

   "is\_mart\_shop": false,

   "is\_one\_awb": false,

  "is\_outlet\_shop": true,

   "is\_sip": false,

   "is\_upgraded\_cbsc": false,

   "linked\_direct\_shop\_list": \[\],

   "linked\_main\_shop\_id": 0,

 "mart\_shop\_id": 225621997,

   "merchant\_id": null,

   "message": "",

   "region": "TH",

   "request\_id": "e3e3e7f33c4e97dc9f31cfda55db5101",

   "shop\_fulfillment\_flag": "Others",

   "shop\_name": "op\_51505343495178456857",

   "status": "NORMAL"

}

  

### 3.2.2 Product Management

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=fBmZsM7SsehtYieQ5xO6XkNZcVPvGWTiCnvysTgeRmsz9tNnfh5%2FnpetQRG4S7HMCPcczjn%2FKeR92pQ88%2BEFjA%3D%3D&image_type=png)

Fields distinguish between Mart SKU vs Outlet SKU

<table><tbody><tr><td colspan="" rowspan=""><span><span>Module</span></span></td><td colspan="" rowspan=""><span><span>Field</span></span></td><td colspan="" rowspan=""><span><span>MART SKU Management</span></span></td><td colspan="" rowspan=""><span><span>Outlet SKU Management</span></span></td></tr><tr><td colspan="" rowspan="5"><span><span>Basic information</span></span></td><td colspan="" rowspan=""><span><span>title</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>video</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>image</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>status</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>category</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan="3"><span><span>Specification</span></span></td><td colspan="" rowspan=""><span><span>brand</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>attribute</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>size chart</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan="11"><span><span>Specification</span></span></td><td colspan="" rowspan=""><span><span>variation name</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>variation option</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>variation image</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>price</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>purchase limit</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>wholesales</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>stock</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>model status</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>module seller SKU</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>weight</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>dimension</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan="2"><span><span>Shipping</span></span></td><td colspan="" rowspan=""><span><span>logistics channel</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>installation channel</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan="3"><span><span>Others</span></span></td><td colspan="" rowspan=""><span><span>DTS</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>seller SKU</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>condition</span></span></td><td colspan="" rowspan=""><span><span>Yes</span></span></td><td colspan="" rowspan=""><span><span>No</span></span></td></tr></tbody></table>

Key operations include:  
1.Add Mart SKU → v2.product.add\_item  
2.Update SKU stock/price → v2.product.update\_stock, v2.product.update\_price (only at outlet level)  
3.Publish Outlet SKU → v2.product.publish\_item\_to\_outlet\_shop  
4.Sync Mart SKU to Outlet SKU → v2.product.update\_item  
  

  

3.2.2.1 Add Mart SKU

  

Add mart SKU by calling v2.product.add\_item using mart shop\_id.

  

Refer to the [Creating Product](https://open.shopee.com/developer-guide/211) article for more information.

  

Calling sample:

{

      "original\_price": 123.3,

      "description": "rachel add item from openapi 002",

      "weight": 1.1,

      "item\_name": "rachel add item from openapi item 001",

      "item\_status": "NORMAL",

      "dimension": {

              "package\_height": 2,

              "package\_length": 3,

              "package\_width": 4

      },

      "category\_id": 400091,

      "logistic\_info": \[{

              "size\_id": 0,

              "shipping\_fee": 2.5,

              "enabled": true,

              "logistic\_id": 80018,

              "is\_free": false

      },

      {

              "size\_id": 0,

              "shipping\_fee": 2.5,

              "enabled": true,

              "logistic\_id": 81014,

              "is\_free": false

      }\],

      "image": {

              "image\_id\_list": \["c54265d475b85e00ffb2404585e32b6f", "6fb33d484f232510b5f9b169f2758322", "591ab15ea954b9879374765854595600", "00a2258551b5a2f0a7c283f877330f93", "abf02b19b42e4aa964ef0725491ca9e3", "730f45972377cd4eb9813c6e53e60e9a"\]

      },

      "pre\_order": {

              "is\_pre\_order": false,

              "days\_to\_ship": 2

      },

      "item\_sku": "item sku 001",

      "condition": "NEW",

      "brand": {

              "brand\_id": 0,

              "original\_brand\_name": "no brand"

      },

      "item\_dangerous": 0,

      "description\_info": {

              "extended\_description": {

                      "field\_list": \[{

                              "field\_type": "text",

                              "text": "rachel add item from openapi 001",

                              "image\_info": {

                                      "image\_id": "-"

                              }

                      },

                      {

                              "field\_type": "image",

                              "text": "-",

                              "image\_info": {

                                      "image\_id": "c54265d475b85e00ffb2404585e32b6f"

                              }

                      }\]

              }

      },

      "description\_type": "extended",

      "seller\_stock": \[{

              "location\_id": "IDZ",

              "stock": 555

      }\]

}

3.2.2.2 Update Mart SKU Stock/Price

Update Mart SKU stock and price by calling v2.product.update\_stock or v2.product.update\_price.

Refer to the [Stock & Price Management](https://open.shopee.com/developer-guide/223) article for more information.

Note: Stock and price can only be managed at the outlet shop level. You must provide the shop\_id of the outlet shop when calling the API to update stock or price.

3.2.2.3 Publish Outlet SKU

  

Call v2.product.publish\_item\_to\_outlet\_shop to publish outlet SKU.

  

Calling sample:

{

  "mart\_item\_id": 1234,

  "outlet\_shop\_id": 2345,

  "publish\_item": {

      "model": \[

          {

              "relate\_mart\_model\_id": 1234,

              "original\_price": 123,

              "seller\_stock": \[

                  {

                      "location\_id": "IDZ",

                      "stock": 100

                  }

              \],

              "pre\_order": {

                  "is\_pre\_order": false,

                  "days\_to\_ship": 3

              }

          }

      \],

      "logistic\_info": \[

          {

              "logistic\_id": 12345,

              "enabled": true,

              "shipping\_fee": 3.45,

              "size\_id": 0,

              "is\_free": false

          }

      \]

  }

}

  

call  v2.product.get\_item\_base\_info to check if outlet item info sync  correctly.

  

Refer to the [Product base info management](https://open.shopee.com/developer-guide/221) article to check the item information.

3.2.2.4 Get the Item Mapping Info

  

When the mart SKU has a published outlet SKU, call v2.get\_mart\_item\_mapping\_by\_id to get the item mapping info.

  

Calling sample:

  

{

  "mart\_item\_id": 844087076,

  "outlet\_shop\_id\_list": \[

      225034791

  \]

}

3.2.2.5 Sync Mart SKU to Outlet SKU

  

Sync the mart sku field to the outlet sku.

  

1.Call v2.product.update\_item to update the mart item field.

  

2.Call v2.product.get\_item\_base\_info/enter outlet shop seller center  to check that the outlet item info syncs correctly.

  

Note:Only fields managed by mart sku can be sync to outlet sku.

### 3.2.3 Instant Channel Dependency In ID

3.2.3.1Channel Structure Overview

  

<table><tbody><tr><td colspan="" rowspan=""><span><span>Mask Channel</span></span></td><td colspan="" rowspan=""><span><span>Logistics Channel</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>8000 - Instant</span></span></td><td colspan="" rowspan=""><span><span>80012 - GoSend Instant</span><br><span>80019 - GrabExpress Instant</span><br><span>80044 - SPX Instant</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>8008 - Instant 2 hours</span></span></td><td colspan="" rowspan=""><span><span>80054 - SPX Instant - 2 Jam</span></span><span><span>80061- GrabExpress Instant - 2 Jam</span></span><span><span>80063- GoSend Instant - 2 Jam&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>8007 - Instant 4 hours</span></span></td><td colspan="" rowspan=""><span><span>80053 -SPX Instant - 4 Jam</span></span><span><span>80062- GrabExpress Instant - 4 Jam</span></span><span><span>80064-GoSend Instant - 4 Jam</span></span></td></tr></tbody></table>

\*Developers should understand that sellers are whitelisted separately for these channels.

  

3.2.3.2 Summary of the limitation about ID Instant Channels

Instant 2-hours (8008) and Instant 4-hours (8007) should always be toggled on/off together.

  

<table><tbody><tr><td colspan="" rowspan=""><span><span>Case</span></span></td><td colspan="" rowspan=""><span><span>Current Condition</span></span></td><td colspan="" rowspan=""><span><span>Action</span></span></td><td colspan="" rowspan=""><span><span>Errors</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>1</span></span></td><td colspan="" rowspan=""><span><span>8000 Instant: </span><span>ON</span></span><ul><li><span>80044 SPX Instant: </span><span>ON</span></li><li><span>80012 GoSend Instant: </span><span>OFF</span></li><li><span>80019 GrabExpress Instant: </span><span>OFF</span></li></ul><span><span>8008 Instant 2-hours: </span><span>OFF</span><span></span></span><ul><li><span>80054- SPX Instant - 2 Jam:</span><span> OFF</span></li><li><span>80061-GrabExpress Instant - 2 Jam:</span><span>OFF</span></li><li><span>80063-GoSend Instant - 2 Jam:</span><span>OFF</span><span></span><br><br><span></span><br></li></ul><span><span>8007 Instant 4-hours:</span><span> OFF&nbsp;</span><span></span></span><ul><li><span>80053-SPX Instant - 4 Jam:</span><span> OFF</span></li><li><span>80062-GrabExpress Instant - 4 Jam:</span><span> OFF</span></li><li><span>80064-Gosend Instant - 4 Jam:</span><span> OFF</span></li></ul></td><td colspan="" rowspan=""><span><span>Turn off 80044</span></span></td><td colspan="" rowspan=""><span><span>SPX Instant cannot be turned off. Please enable at least 1 of the following channel(s): {GoSend Instant, GrabExpress Instant, SPX Instant - 2 Jam, GrabExpress Instant - 2 Jam,GoSend Instant - 2 Jam,SPX Instant - 4 Jam,GrabExpress Instant - 4 Jam,GoSend Instant - 4 Jam}</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>2</span></span></td><td colspan="" rowspan=""><span><span>8000 Instant: </span><span>OFF</span></span><ul><li><span>80044 SPX Instant: </span><span>OFF</span></li><li><span>80012 GoSend Instant: </span><span>OFF</span></li><li><span>80019 GrabExpress Instant: </span><span>OFF</span></li></ul><span><span>8008 Instant 2-hours: </span><span>ON</span><span></span></span><ul><li><span>80054- SPX Instant - 2 Jam: </span><span>ON</span></li><li><span>80061-GrabExpress Instant - 2 Jam:</span><span>OFF</span></li><li><span>80063-GoSend Instant - 2 Jam:</span><span>OFF</span><span>&nbsp;</span><br><br><span></span><br></li></ul><span><span>8007 Instant 4-hours: </span><span>ON</span><span>&nbsp;&nbsp;&nbsp;&nbsp;</span></span><ul><li><span>80053- SPX Instant - 4 Jam: </span><span>ON</span></li><li><span>80062-GrabExpress Instant - 4 Jam:</span><span> OFF</span></li><li><span>80064-Gosend Instant - 4 Jam:</span><span> OFF</span></li></ul></td><td colspan="" rowspan=""><span><span>Turn off 80054</span></span></td><td colspan="" rowspan=""><span><span>SPX Instant - 2 Jam channel cannot be turned off. Please enable at least 1 of the following channel(s). {SPX Instant, GoSend Instant, GrabExpress Instant, GrabExpress Instant - 2 Jam, GrabExpress Instant - 4 Jam, GoSend Instant - 2 Jam, GoSend Instant - 4 Jam}</span></span><span><br><span></span></span></td></tr><tr><td colspan="" rowspan=""><span><span>3</span></span></td><td colspan="" rowspan=""><span><span>8000 Instant: </span><span>OFF</span></span><ul><li><span>80044 SPX Instant: </span><span>OFF</span></li><li><span>80012 GoSend Instant: </span><span>OFF</span></li><li><span>80019 GrabExpress Instant: </span><span>OFF</span></li></ul><span><span>8008 Instant 2-hours: </span><span>ON</span><span></span></span><ul><li><span>80054-SPX Instant - 2 Jam : </span><span>ON</span></li><li><span>80061-GrabExpress Instant - 2 Jam:</span><span>OFF</span></li><li><span>80063-GoSend Instant - 2 Jam:</span><span>OFF</span><span>&nbsp;</span><br><br><span></span><br></li></ul><span><span>8007 Instant 4-hours: </span><span>ON</span><span>&nbsp;&nbsp;&nbsp;&nbsp;</span></span><ul><li><span>80053-SPX Instant - 4 Jam: </span><span>ON</span></li><li><span>80062-GrabExpress Instant - 4 Jam:</span><span> OFF</span></li><li><span>80064-Gosend Instant - 4 Jam:</span><span> OFF</span></li></ul></td><td colspan="" rowspan=""><span><span>Turn off 80053</span></span></td><td colspan="" rowspan=""><span><span>SPX Instant - 4 Jam channel cannot be turned off. Please enable at least 1 of the following channel(s). {SPX Instant, GoSend Instant, GrabExpress Instant, GrabExpress Instant - 2 Jam, GrabExpress Instant - 4 Jam, GoSend Instant - 2 Jam, GoSend Instant - 4 Jam,SPX Instant - 2 Jam}</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>4</span></span></td><td colspan="" rowspan=""><span><span>Seller is only whitelisted for Channel in 8000 Instant </span></span><span><span>8000 Instant: </span><span>ON</span></span><ul><li><span>80044 SPX Instant: </span><span>ON</span></li><li><span>80012 GoSend Instant: </span><span>OFF</span></li><li><span>80019 GrabExpress Instant: </span><span>OFF</span></li></ul></td><td colspan="" rowspan=""><span><span>Turn off 80044</span></span></td><td colspan="" rowspan=""><span><span>SPX Instant channel cannot be turned off. Please enable the following channel(s).&nbsp; GoSend Instant, GrabExpress Instant</span></span></td></tr></tbody></table>

  

### 3.2.4 Instant Channel Dependency In TH

3.2.4.1Channel Structure Overview

  

<table><tbody><tr><td colspan="" rowspan=""><span><span>Mask Channel</span></span></td><td colspan="" rowspan=""><span><span>Logistics Channel</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>0</span></span></td><td colspan="" rowspan=""><span><span>70124-Instant Delivery - ส่งทันที (แพ็ก 30 นาที)</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>0</span></span></td><td colspan="" rowspan=""><span><span>70125-Instant Delivery - ส่งทันที (แพ็ก 10 นาทีจากสาขา)</span></span></td></tr></tbody></table>

### 3.2.5 Instant Channel Dependency In VN

3.2.5.1Channel Structure Overview

  

<table><tbody><tr><td colspan="" rowspan=""><span><span>Mask Channel</span></span></td><td colspan="" rowspan=""><span><span>Logistics Channel</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>5115</span></span></td><td colspan="" rowspan=""><span><span>50040-</span><span>SPX Instant - Hỏa Tốc - Ưu Tiên</span></span></td></tr></tbody></table>

### 3.2.6 Instant Channel Dependency In PH

3.2.6.1Channel Structure Overview

<table><tbody><tr><td colspan="" rowspan=""><span><span>Mask Channel</span></span></td><td colspan="" rowspan=""><span><span>Logistics Channel</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>0</span></span></td><td colspan="" rowspan=""><span><span>40079-</span><span>Instant Delivery</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>0</span></span></td><td colspan="" rowspan=""><span><span>40080-Instant Delivery Priority</span></span></td></tr></tbody></table>

### 3.2.7 Order Management

To create a test order, go to the [Test Order](https://open.shopee.com/console/tools/test-order) page on the Shopee Open Platform Console and select Create Test Order.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=K34RFeggDVp1VnSay1ytBxh8tZbcigs%2F4nRMZ2wTVqn%2BzBCtBjya4JYU4bfCXN4m11yj4BS%2FFDpb4n%2BDjrBbng%3D%3D&image_type=png)

Choose the shop, items, and shipping option, then click Create to generate the order.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=rbuiBvf07YCjiFiuQQrgjyZrXU1UA6jUurSUQWL4CLDmPIFs9%2B7wqxOFWU6VfRTlnIoELegnSDWyrDJXhOynKw%3D%3D&image_type=png)

  

The order information includes Order SN, Item ID, Status, Update Time, and Shop ID. You can also pick up, deliver, or delete the order from this page.

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=6I260nsaQWNLCtX1thRKAUX1goP%2BuCiKGZCwoOvfk11vsagH3%2B3m8o7AaesM5ZIm8Lsekst4wgJ466%2BZT36R2w%3D%3D&image_type=png)

  

Refer to the [Order Management](https://open.shopee.cn/developer-guide/229) article for more information about following steps.

### 3.2.8 Return & Refund Management

  

Refer to the [Return Refund Management](https://open.shopee.com/developer-guide/227) article for more information.

### 3.2.9 Finance: Get Income and wallet transactions

You can generate and retrieve the income statement, income report, or wallet transactions by calling the APIs with a Mart shop\_id. When using a Mart shop\_id, the response will return an aggregated document that includes all outlet shops under the Mart shop.

  

<table><tbody><tr><td colspan="" rowspan=""><span><span>Function</span></span></td><td colspan="" rowspan=""><span><span>Open API</span></span></td><td colspan="" rowspan=""><span><span>Remark</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>generate income report</span></span></td><td colspan="" rowspan=""><span><span>v2.payment.generate_income_report</span></span></td><td colspan="" rowspan=""><span><span>Request Parameter: release_time_from and release_time_to.</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>get income report</span></span></td><td colspan="" rowspan=""><span><span>v2.payment.get_income_report</span></span></td><td colspan="" rowspan=""><span><span>To query income report status and provide file link by passing income_report_id.</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>generate income statement</span></span></td><td colspan="" rowspan=""><span><span>v2.payment.generate_income_statement</span></span></td><td colspan="" rowspan=""><span><span>Request Parameter:statement_type=1 means weakly statement;statement_type=2 means monthly statement</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>get income statement</span></span></td><td colspan="" rowspan=""><span><span>v2.payment.get_income_statement</span></span></td><td colspan="" rowspan=""><span><span>To query income statement status and provide a file link by passing income_statement_id</span></span></td></tr><tr><td colspan="" rowspan=""><span><span>get wallet transaction</span></span></td><td colspan="" rowspan=""><span><span>v2.payment.get_wallet_transaction_list</span></span></td><td colspan="" rowspan=""><span><span>Get the transaction records of the wallet.</span></span></td></tr></tbody></table>

### 3.2.10 Shop Setting

1.You can update the shop name, logo, and description by calling v2.shop.update\_profile.  
2.You can update the operating hours by calling v2.logistics.update\_operating\_hours.  
Note:

-   The values provided must comply with the restrictions retrieved from v2.logistics.get\_operating\_hour\_restrictions.  
      
      
    
-   This API performs overwriting updates. When updating pickup operating hours, you must include all segments, even those that remain unchanged.

  

# 4\. Push Mechanism

The push mechanism is a dictionary of system notifications from Shopee, covering product, order, return, marketing, stability, and general marketplace updates.

For Instant Mart, it is recommended to connect to [](https://open.shopee.com/push-mechanism/1)[order\_status\_push](https://open.shopee.com/push-mechanism/1). For more details, please refer to the order\_status\_push article.

  

# 5\. FAQ & Raise for Help

For common questions, please refer to the [FAQs](https://open.shopee.com/faq?categoryId=2010).

  

If you encounter any issues related to Instant Mart APIs (e.g. onboarding, logistics channel configuration, order sync, etc.), please raise a ticket to the Shopee Product Support team \[[here](https://open.shopee.com/console/raise-ticket)\].

When submitting the ticket, kindly select: L1 Question Category → Instant Mart

  

Selecting the correct category helps us identify and prioritize your request more efficiently.
