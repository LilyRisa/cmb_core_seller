# EU Markets: Category Rules Update – Responsible Person Attribute Now Mandatory

> Source: https://partner.tiktokshop.com/docv2/page/r1wchdjn
> Section: Changelog
> Scraped: 2026-05-21T00:46:23.345Z

---

# What is changing?

Starting **February 28, 2026**, the **Responsible Person (RP)** attribute will change from **optional to mandatory** in **selected existing product categories** where it was previously optional.  
Sellers must update existing listings to include the Responsible Person attribute by **March 28, 2026**. Listings that are not updated by this date may be **deactivated by the platform**.

Updated API Endpoint(s):

-   [Get Category Rules](https://partner.tiktokshop.com/docv2/page/get-category-rules-202309)
    

The category rules response will reflect the updated requirement (the Responsible Person attribute will be marked as mandatory for affected categories).

# Which markets are affected?

-   All EU markets
-   Local and cross border sellers

# Who is affected?

Developers with applications that are used for product listing and management.

# What action is required?

1.  Sync product category fields with the latest category rules.
2.  Ensure the Responsible Person attribute is mandatory and populated for all affected categories to avoid:

-   Product creation errors
    
-   Product update errors
    
-   Listing deactivations
    

\*General recommendation: Product categories are updated frequently, so it's recommended to call the API in real time to ensure you are using the latest category data. Caching category data locally may result in using outdated information, leading to errors when creating products.
