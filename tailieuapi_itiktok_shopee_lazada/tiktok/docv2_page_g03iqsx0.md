# Draft editing support for published listings

> Source: https://partner.tiktokshop.com/docv2/page/g03iqsx0
> Section: Changelog
> Scraped: 2026-05-21T00:45:17.068Z

---

## Summary

Previously, the TikTok Shop Seller Center only allowed draft editing prior to publishing a listing. We've recently expanded this feature to additionally enable sellers to edit and save drafts for products that have already been listed, and are now ensuring it is reflected in our APIs.

## Impact

|  |  |
| --- | --- |
| 
Impacted market(s)

 | 

\* United States (US) - Local  
\* United Kingdom (UK) - Local  
\* Europe (EU) - Local  
\* Southeast Asia (SEA) - Local  
\* Japan (JP) - Local  
\* Platform Open Plan (POP) - US, UK, EU, SEA

 |
| 

Impacted version(s)

 | 

202309 (and later)

 |

## Changes

### Product APIs

| **Endpoint(s)** | **Change(s)** |
| --- | --- |
| 
\* [PUT Edit Product](https://partner.tiktokshop.com/docv2/page/edit-product-202309)  
\* [POST Partial Edit Product](https://partner.tiktokshop.com/docv2/page/partial-edit-product-202309)

 | 

New request parameter: `save_mode`  
Possible values:  
  
\* `AS_DRAFT`: Save the product as a draft for future editing.  
\* `LISTING`: Immediately list the product in the shop.

 |
| 

\* [GET Product](https://partner.tiktokshop.com/docv2/page/get-product-202309)  
\* [POST Search Products](https://partner.tiktokshop.com/docv2/page/search-products-202502)

 | 

New request parameter: `return_draft_version`  
Filter products to show only those that have a draft.  
  
\* `true`: Returns products in their draft version only. Excludes those without a draft.  
\* `false`: Returns all products regardless of whether they have a draft.

 |
| 

\* [POST Search Products](https://partner.tiktokshop.com/docv2/page/search-products-202502)

 | 

New response parameter: `has_draft`  
Indicates whether the product has a draft.  
  
\* `true`: It has a draft.  
\* `false`: It does not have a draft.

 |

## Next steps

For more details on the new parameters, please visit our individual API documentation for each endpoint, as linked above.
