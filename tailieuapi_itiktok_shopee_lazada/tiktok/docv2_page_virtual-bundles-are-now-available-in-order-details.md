# Virtual bundles are now available in order details

> Source: https://partner.tiktokshop.com/docv2/page/virtual-bundles-are-now-available-in-order-details
> Section: Changelog
> Scraped: 2026-05-21T00:36:39.228Z

---

# What is changing?

**Virtual bundles** is a TikTok Shop seller enablement initiative which allows sellers to create a new ProductID (new SKU) by adding multiple existing SKUs and allowing them to share the inventory in real-time. Sellers can set prices for the new SKUs different from the sums of the added SKUs. The new SKUs will become independent new products for shoppers to explore and purchase.  
A new `product_id` and `sku_id` is created when generating a virtual bundle product. From a buyer's perspective in TikTok Shop, it is browsed and purchased as a single product.  
After a virtual bundle is created, the seller can add or remove the "child" products to the virtual bundle, and the `product_id` and `sku_id` will not change.  
This change will allow sellers to use API to map orders that have virtual bundles to be synced back to their systems for fulfillment.  
***The virtual bundle has to be created in Seller Center and is not possible to be created via the API. Only the GET Order Detail (and not GET Order List) will return information about virtual bundles.***

# Which markets are affected?

This change is for all markets and all sellers.

# Who is affected?

All developers who build connects, multi-channel apps and shipping / fulfillment apps are affected by this change.

# Which version is applicable?

This change is applicable to **v202309**

# What action is required?

Please adopt the new `combined_listing_sku` object present in the Get Order Detail API to enable sellers to fulfill bundles that have been sold on TikTok Shop.

## Get Order Detail

### Parameters

No change

### Responses

A new `combined_listing_sku` object added to the `line_item` property, indicating the "child" products/SKUs that compose the virtual bundle.

| Properties |  |  |  |  | Type | Sample | Properties description |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 
data

 | 

 | 

 | 

 | 

 | 

 | 

 | 

 |
| 

└

 | 

 | 

 | 

 | 

orders

 | 

\[\]object

 | 

 | 

order information

 |
| 

└

 | 

└

 | 

 | 

 | 

id

 | 

string

 | 

576461413038785752

 | 

Tiktok shop order id

 |
| 

└

 | 

└

 | 

 | 

 | 

line\_items

 | 

\[\]object

 | 

 | 

Line item info list

 |
| 

└

 | 

└

 | 

└

 | 

 | 

id

 | 

string

 | 

577086512123755123

 | 

Line item ID

 |
| 

└

 | 

└

 | 

└

 | 

 | 

product\_name

 | 

string

 | 

Wellgard Collagen & ACV Gummies Bundle

 | 

Product name

 |
| 

└

 | 

└

 | 

└

 | 

 | 

product\_id

 | 

string

 | 

1729582718312380123

 | 

Product ID

 |
| 

└

 | 

└

 | 

└

 | 

 | 

sku\_id

 | 

string

 | 

2729382476852921560

 | 

Sku id

 |
| 

└

 | 

└

 | 

└

 | 

 | 

combined\_listing\_skus

 | 

\[\]object

 | 

 | 

For a virtual bundle SKU, returns an array of related product SKUs that compose the virtual bundle SKU

 |
| 

└

 | 

└

 | 

└

 | 

└

 | 

sku\_id

 | 

string

 | 

2729382476852921123

 | 

The original sku\_id related to the virtual bundle SKU

 |
| 

└

 | 

└

 | 

└

 | 

└

 | 

sku\_count

 | 

int

 | 

1

 | 

The quantity of original SKUs that compose the virtual bundle SKU

 |
| 

└

 | 

└

 | 

└

 | 

└

 | 

product\_id

 | 

string

 | 

1729582718312380456

 | 

The original product\_id related to the virtual bundle SKU

 |

> For example, a virtual bundle that contains two SKUs will have two elements in the array:

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/151bccb0a972462b827b08b6fde44f0f~tplv-k9wyc2ijk0-image.image)
