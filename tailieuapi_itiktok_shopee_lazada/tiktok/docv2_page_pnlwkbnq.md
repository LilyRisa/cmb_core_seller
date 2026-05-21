# EU Markets: Introducing VAT Calculation Service (VCS) — New Product and Order API Fields

> Source: https://partner.tiktokshop.com/docv2/page/pnlwkbnq
> Section: Changelog
> Scraped: 2026-05-21T00:47:09.074Z

---

# What is changing?

TikTok Shop has introduced the VAT Calculation Service (VCS), a feature that automatically calculates the correct VAT on every sale made to customers in the European Union.

When a seller enables VCS, two areas of the API are affected:

1.  **Product listing — Product Tax Code (PTC)**

A new product attribute has been added to support PTC. Use the [Get Attributes](https://partner.tiktokshop.com/docv2/page/get-attributes-202309) endpoint to retrieve the available `Product Tax Code (PTC)` options by category.

-   PTC can be set at the shop level (default) or overridden at the product level.
    
-   Product-level `PTC` takes precedence over shop-level PTC.
    
-   Shop Level PTC can only be configured and viewed in the TikTok Shop Seller Center.
    

1.  **Orders — VAT fields**

The following fields will return VAT values in the [Get Order Detail](https://partner.tiktokshop.com/docv2/page/get-order-detail-202507) endpoint when VCS is enabled:

| **Field** | **Description** |
| --- | --- |
| 
`item_tax`

 | 

VAT applied based on the PTC at shop or product level

 |
| 

`shipping_tax_amount`

 | 

Shipping tax configured in Seller Center tax settings

 |
| 

`shipping_tax_rate`

 | 

Shipping tax rate configured in Seller Center tax settings

 |

**Seller configuration requirements:**  
For VCS to work, sellers must configure it in Seller Center. For requirements and setup instructions, refer to the guides by market:

-   [Ireland TikTok Shop Academy](https://seller-ie.tiktok.com/university/essay?identity=1&role=1&knowledge_id=8455415202449174&from=feature_guide)
-   [Germany TikTok Shop Academy](https://seller-de.tiktok.com/university/essay?identity=1&role=1&knowledge_id=8476689265035030&from=feature_guide)
-   [France TikTok Shop Academy](https://seller-fr.tiktok.com/university/essay?identity=1&role=1&knowledge_id=8454779321419543&from=feature_guide)
-   [Spain TikTok Shop Academy](https://seller-es.tiktok.com/university/essay?identity=1&role=1&knowledge_id=8454779322730263&from=feature_guide)
-   [Italy TikTok Shop Academy](https://seller-it.tiktok.com/university/essay?identity=1&role=1&knowledge_id=8455415202629398&from=feature_guide)

# Which markets are affected?

This change applies to EU markets: DE, ES, FR, IE, IT.

# Who is affected?

Developers with applications that are used for product catalog and order management.

# What action is required?

1.  Product listing and management
    -   Add a PTC selector field in your product listing UI
    -   Populate available PTC options using the Get Attributes endpoint (attribute ID: `103356`)
2.  Order management

Update your order handling logic to read and display the new fields and values:

-   `item_tax`
-   `shipping_tax_amount`
-   `shipping_tax_rate`

1.  Seller guidance

Guide sellers to complete the following steps in Seller Center before using VCS:

-   Configure VCS: set a default PTC and shipping tax rate.
-   Review the PTC reference table in TikTok Shop Academy to understand applicable tax rates per PTC.
