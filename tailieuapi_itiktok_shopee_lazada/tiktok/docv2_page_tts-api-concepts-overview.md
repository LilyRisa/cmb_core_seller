# Overview

> Source: https://partner.tiktokshop.com/docv2/page/tts-api-concepts-overview
> Section: Developer Guide
> Scraped: 2026-05-21T00:23:08.810Z

---

TikTok Shop provides a comprehensive range of APIs known as TTS API, which allows TTS developers access to user data within the app, including catalogs, orders, shipments, payments, and more. Leveraging the capabilities of the TTS API, developers can create apps that extend Seller Center's existing functionalities. These apps can enhance product catalog listings, streamline order fulfillment, minimize operational burdens, and accelerate customer interactions.

### Key Features

With the TikTok Shop API, you can:

-   Set up an [authorization workflow](authorization-guide-202309) that developers initiate from the TTS App & Service Market detail page or from your own website.
-   Configure [webhooks](tts-webhooks-overview) to receive notifications from TikTok Shop Open Platform
-   Test your app functionality in Sandbox or by making API calls using the [API testing tool](https://partner.tiktokshop.com/dev/api-testing-tool).

| **NO.** | **API Category** | **API description** |
| --- | --- | --- |
| 
1

 | 

[Product API](products-api-overview)

 | 

The Product API is one of the most important APIs in TTS. Developers can use the Product API to access rules and attributes information of product categories, upload product images, videos, certifications, and other files, create products, edit product information, update inventory and prices, etc. Additionally, we also provide a set of Global Product APIs (APIs with "global" in their names) for cross-border e-commerce developers. Using the Global Product API, cross-border e-commerce developers can offer unified product management solutions for cross-border sellers selling across multiple regional markets.

 |
| 

2

 | 

[Order API](order-api-overview)

 | 

Developers can use the Order API to retrieve order information from TTS seller shops. Once sellers obtain the order information, they can perform order fulfillment operations as well as actions like order cancellation and returns.

 |
| 

3

 | 

[Fulfillment API](fulfillment-api-overview)

 | 

Developers can utilize the Fulfillment API to synchronize the fulfillment status of TTS orders to TTS (3PL). They can also fulfill orders using TTS-provided shipping label services (4PL). Additionally, the Fulfillment By Tik Tok Shop (FBT) service can be used, and developers can access the fulfillment status of TTS orders through the fulfillment API.

 |
| 

4

 | 

[Return & Refund API](return-refund-and-cancel-api-overview)

 | 

When consumers initiate a Return or Refund request, developers can use the Return & Refund API to access Return or Refund order information and assist sellers in reviewing or rejecting Return or Refund requests initiated by consumers.

 |
| 

5

 | 

[Logistics API](logistic-api-overview)

 | 

When sellers use TTS's provided logistics services, developers can use the Logistics API to assist sellers in retrieving Warehouse List, Global Warehouse List, Subscribed Delivery Options, Shipping Providers.

 |
| 

6

 | 

[Promotion API](promotion-api-overview)

 | 

Developers can use the Promotion API to assist sellers in setting up promotions, discounts, and other offers for specific products.

 |
| 

7

 | 

[Finance API](finance-api-overview)

 | 

Developers can utilize the Financial API to assist sellers in obtaining payment and settlement information for their TTS shop.

 |
| 

8

 | 

[Seller API](seller-api-overview)

 | 

The Seller API exclusively supports cross-border operations, where a single seller can establish shops in multiple regional markets, allowing one seller to possess multiple shops. Developers can utilize the Seller API to retrieve the shop status of cross-border sellers and ascertain whether a specific cross-border market is eligible for the Global Product feature.

 |
| 

9

 | 

[Authorization API](get-authorized-shops)

 | 

Developers can utilize the Authorization API to proceed with the authorization token exchanging process and retrieve authorized shop information for apps .

 |
| 

10

 | 

[Events API](get-shop-webhooks)

 | 

Developers can utilize the Events API to subscribe and unsubscribe Open platform webhooks of TikTok Shop business events for apps;

 |
| 

11

 | 

Data Reconciliation API

 | 

Developers can utilize the data reconciliation API to transfer external data to open platform for Quality Engine to reconcile.

 |
| 

12

 | 

[Supply Chain](confirm-package-shipment)

 | 

This API is for TikTok Shop certified warehouse partners. Developers can utilize Supply Chain API to send back the package fulfillment detail info of the orders fulfilled by the certified warehouse to TTS.

 |

> For a complete list of available APIs, refer to [TTS API reference docs](create-product).

### API Version

The TTS API is versioned, and while some older API versions are still accessible, some are unsupported and can stop working at any time. Developers are strongly encouraged to migrate to the latest API version promptly. For additional details on TTS API versioning, refer to [TTS API versioning](api-versioning).
