# US market: Updates to mandatory attributes

> Source: https://partner.tiktokshop.com/docv2/page/um8sunwp
> Section: Changelog
> Scraped: 2026-05-21T00:43:23.044Z

---

## Summary

To meet compliance requirements, TikTok Shop is making compliance-related attributes mandatory across various product categories.  
Starting from **2026-04-06**, the Products API will require these compliance-related attributes to be provided.

## Impact

| Impacted market(s) | United States (US) |
| --- | --- |
| 
Impacted version(s)

 | 

202309 (and later)

 |

## Changes

The following attributes in the specified categories will become mandatory.

| **Compliance scenario** | **Attributes affected** | **Categories affected** |
| --- | --- | --- |
| 
Food Safety

 | 

The following level 1 attributes will become mandatory:  
\* Attribute ID 100346

 | 

All Categories of Food and Beverage

 |

### Affect APIs

| **Endpoint(s)** | **Change(s)** |
| --- | --- |
| 
[Get Attributes](https://partner.tiktokshop.com/docv2/page/get-attributes)

 | 

The response body for attribute ID 100346 will now reflect the latest compliance rules (specifically in is\_required and requirement\_conditions fields).  
Please call this endpoint to retrieve the latest rules and update your locally stored attribute list, if any.

 |
| 

[Check Product Listing](https://partner.tiktokshop.com/docv2/page/check-product-listing)  
[Create Product](https://partner.tiktokshop.com/docv2/page/create-product)  
[Edit Product](https://partner.tiktokshop.com/docv2/page/edit-product)  
[Partial Edit Product](https://partner.tiktokshop.com/docv2/page/partial-edit-product)

 | 

If no values are provided for attribute ID 100346, the API call will fail and the operation cannot proceed.

 |

## Next steps

Sellers will be unable to create or update products in the stated categories without providing the required compliance-related attributes. Use [Get Attributes](https://partner.tiktokshop.com/docv2/page/get-attributes) to identify the mandatory attributes and provide the appropriate values in [Check Product Listing](https://partner.tiktokshop.com/docv2/page/check-product-listing), [Create Product](https://partner.tiktokshop.com/docv2/page/create-product), [Edit Product](https://partner.tiktokshop.com/docv2/page/edit-product), and [Partial Edit Product](https://partner.tiktokshop.com/docv2/page/partial-edit-product) to help sellers fill in the attribute values.
