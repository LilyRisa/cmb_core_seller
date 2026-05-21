# JP market: Updated APIs for Products, Orders, Logistics, and Customer service

> Source: https://partner.tiktokshop.com/docv2/page/jp-market-updated-apis-for-products-orders-logistics-and-customer-service
> Section: Changelog
> Scraped: 2026-05-21T00:50:57.040Z

---

## Summary

As of today, developers can start using TikTok Shop APIs to build apps for the Japan market. The APIs and workflows are generally consistent with other regions/markets, but there are a few JP-specific updates worth calling out.  
**Note**: Before building order management modules, developers should familiarize themselves with our [Orders API overview](order-api-overview) and our [Fulfillment API overview](fulfillment-api-overview).

## Affected markets and versions

This change applies to the following market(s):

-   Japan (JP)

This change applies to the following API version(s):

-   202309 (and later)

## API changes

### Product APIs

| **Endpoint(s)** | **Change(s)** |
| --- | --- |
| 
\* Create Product  
\* Edit Product  
\* Partial Edit Product  
\* Check Product Listing  
\* Diagnose and Optimize Product  
\* Recommend Category

 | 

\* Each product can contain up to 300 SKUs  
\* The product price range should be within \[1, 500,000\] JPY  
\* The characters of the product title should be within \[1,255\]  
\* Use GRAM as the package weight unit for product listing

 |
| 

\* Publish Global Product  
\* Edit Global Product  
\* Edit Product  
\* Partial Edit Product  
\* Update Price  
\* Get Global Product

 | 

Added **mandatory** parameter `sale_price`, indicating the SKU's local selling price displayed on the product page before any discounts, inclusive of taxes. The `sale_price` parameter is only applicable to cross-border (GS-POP) sellers, and is optional in other markets.

 |
| 

\* Get Categories  
\* Get Category Rules  
\* Get Attributes  
\* Update Price  
\* Search Size Chart  
\* Search Products

 | 

Added parameter values related to locale `ja-JP`, currency `JPY`, and regions `JP` have been added.

 |
| 

\* Get Global Categories  
\* Get Global Category Rules  
\* Get Global Attributes

 | 

Added parameter values related to locale `ja-JP` and region `JP`.

 |

### Get Order Detail, Get Order List

Two response parameters have been added under `recipient_address`:

-   `first_name_local_script`: first name in katakana
-   `last_name_local_script`: last name in katakana

### Manage Shipments

Four response parameters have been added:

-   `first_name`: first name in kanji
-   `last_name`: last name in kanji
-   `first_name_local_script`: first name in katakana
-   `last_name_local_script`: last name in katakana

### Customer service APIs

All customer service API endpoints have been updated to support sending and receiving messages in Japanese.

### Fulfillment APIs

#### Local sellers

By default, local sellers in the Japan market should follow the seller shipping (3PL) workflow (see the [Fulfillment API overview](fulfillment-api-overview) for more details). Sellers must use the following integrated carriers for shipping:

-   Yamato
-   Sagawa Express
-   Japan Post
-   Seino Transportation
-   Seino Super Express

For local sellers, the seller shipping workflow should generally follow these steps:

1.  The seller ships the package and obtains the shipment tracking number from the shipping carrier.
2.  Use the [Get Shipping Providers](get-shipping-providers) endpoint to retrieve the shipping service provider IDs supported by TikTok Shop.
3.  Use the [Mark Package as Shipped](mark-package-as-shipped) endpoint to upload the tracking number and shipping provider ID.

#### China cross-border (GS-POP) sellers

China cross-border sellers should use the following fulfillment workflows:

| **Shipping type** | **Endpoint(s)** |
| --- | --- |
| 
Oversea warehouse

 | 

1\. The seller ships the package and obtains the shipment tracking number from the shipping carrier.  
2\. Use the [Get Shipping Providers](get-shipping-providers) endpoint to retrieve the shipping service provider IDs supported by TikTok Shop.  
3\. Use the [Mark Package as Shipped](mark-package-as-shipped) endpoint to upload the tracking number and shipping provider ID.

 |
| 

Direct shipping

 | 

1\. The seller schedules the package handover time and method with the shipping provider.  
2\. Use the [Ship Package](ship-package) endpoint to schedule the package handover time and method.  
3\. Use the [Get Package Shipping Document](get-package-shipping-document) endpoint to get the shipping label.

 |
