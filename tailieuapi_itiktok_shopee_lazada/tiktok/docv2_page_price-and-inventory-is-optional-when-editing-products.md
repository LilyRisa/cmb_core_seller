# Price and Inventory is optional when editing products

> Source: https://partner.tiktokshop.com/docv2/page/price-and-inventory-is-optional-when-editing-products
> Section: Changelog
> Scraped: 2026-05-21T00:43:46.802Z

---

## Summary

When calling [Edit Product](edit-product) and [Partial Edit Product APIs](partial-edit-product), the **price** and **inventory** fields are now optional. This change applies to all markets, and both cross-boar and local sellers. Developers integrated with v202309 version of these endpoints can leverage these changes.

## What is changing

The schema of both [Edit Product](edit-product) and [Partial Edit Product APIs](partial-edit-product) have been changed so that `price` and `inventory` are not required anymore. If you omit these objects in the API request, the existing price and inventory will remain unchanged.

## What action is required

No action is required unless you are a developer who will gain flexibility by omitting the above fields.

## What markets and version is affected

This change applies to all markets and all sellers.
