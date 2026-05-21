# For the SEA Market Partner & Developer: Add Expire Date on Product Certifications

> Source: https://partner.tiktokshop.com/docv2/page/for-sea-market-partner-developer-add-expire-date-on-product-certifications
> Section: Changelog
> Scraped: 2026-05-21T00:39:16.511Z

---

# What is changing?

A new attribute expiration\_date was added under product certifications in APIs Check Product Listing, Create Product, Partial Edit Products and Edit Product. Before creating, publishing or editing products, please call Get Category Rules to make sure whether expiration\_date is mandatory after 2025-6-28.  
There would be an expiration\_date field under product certifications in the response body when getting category rules or getting product. The APIs include Get Category Rules and Get Product.

# Which markets are affected?

The updates of the requirements apply to the local sellers in Singapore, Philippines, Vietnam, Thailand, Indonesia and Malaysia markets.

# Who is affected?

Developers with applications that use Check Product Listing, Create Product, Partial Edit Products, Edit Product, Get Category Rules and Get Product.

# Which version is applicable?

The updates of the requirements apply to all versions of the related APIs

# What action is required?

If expiration\_date is mandatory, without this attribute sellers will not be able to create or edit products.  
Use the Get Attributes or Get Category Rules to check what attributes are mandatory and pass attribute values via Check Product Listing, Create Product, Partial Edit Products and Edit Product to help sellers fill in the attribute values.
