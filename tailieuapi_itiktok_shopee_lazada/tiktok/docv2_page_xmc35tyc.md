# US market: Backorder Quantity Thresholds

> Source: https://partner.tiktokshop.com/docv2/page/xmc35tyc
> Section: Changelog
> Scraped: 2026-05-21T00:46:02.289Z

---

## Summary

We've introduced inventory `backorder_quantity` functionality, which allows sellers to define a backorder quantity **threshold** to continue to capture sales, in the event the physical inventory is temporarily out-of-stock. There also needs to be a `handling_time` defined, which dictates how many days the seller needs to process the backorder and sets the SLA for sending a fulfillment event update to TikTok Shop.  
[Backorder(BO) Seller Academy article](https://seller-us.tiktok.com/university/essay?knowledge_id=1242390120679211)

**Important note**: Currently, only select qualified sellers can use the backorder functionality and need to go through TikTok account management for approval. Once the sellers are approved, they **must** activate the backorder functionality on their product detail page within their seller center interface first. There is currently no API functionality to activate the backorder flag on a `product_id` / `sku level`.

Updating `backorder_quantity` thresholds and `handling_time` can only be done through the API after the seller has completed the backorder activation.

## Affected markets and versions

This change applies to the following market(s):

-   United States (US)

This change applies to the following API version(s):

-   Please see table below

## What's changing?

### Product APIs

| **Endpoint(s)** | **Version(s) Affected** | **Change(s)** |
| --- | --- | --- |
| 
[Check Product Listing](<check-product-listing#Back To Top>)

 | 

`202309`

 | 

New response parameters within the`inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Create Product](https://partner.tiktokshop.com/docv2/page/create-product-202309)

 | 

`202309`

 | 

New body parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Search Products](search-products)

 | 

`202502`

 | 

New response parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Get Product](get-product)

 | 

`202309`

 | 

New response parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Update Inventory](https://partner.tiktokshop.com/docv2/page/update-inventory-202309)

 | 

`202309`

 | 

New body parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Edit Product](https://partner.tiktokshop.com/docv2/page/edit-product-202509)

 | 

`202509`

 | 

New body parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
| 

[Partial Edit Product](<https://partner.tiktokshop.com/docv2/page/partial-edit-product-202509#Back To Top>)

 | 

`202509`

 | 

New body parameters within the `inventory` object: `backorder_quantity`  
`handling_time`

 |
