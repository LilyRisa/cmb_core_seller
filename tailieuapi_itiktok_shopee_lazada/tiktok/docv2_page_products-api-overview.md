# Products API overview

> Source: https://partner.tiktokshop.com/docv2/page/products-api-overview
> Section: API Reference
> Scraped: 2026-05-20T23:24:21.066Z

---

# Context

The Product API helps sellers manage their product catalog at scale. Using the Product API, a seller can create, edit, delete products, and retrieve product details, as well as sync them into Tiktok Shop from an external DTC/OMS/ERP system (such as Shopify) for sales on TikTok Shop.  
A product can be created either as a live product or a draft via Create Product API.  
The steps to create a product varies based on the location of the shop.

**For EU countries**

  
  

**For other regions**

Products may be deactivated or frozen by TikTok for various policy violations. You can obtain the real-time review result of the product via the Product webhook.  
Product details can be retrieved via the Get Product List API and the Get Product Detail API. Product details include product basic information and sales terms. Basic information includes item titles, description, inventory, images, etc. Sales terms include price and inventory, etc.  
A product will be reviewed by TikTok after the product is created or edited. If a live product fails the review after being edited, the live version remains the snapshot of the product before the editing. To retrieve the after-editing version being reviewed, specify True for the need\_audit\_version parameter field when calling the Get Product Detail API.

# Important Concepts

**Product:** A product is an item that a seller lists on TikTok for sale. A product is identified by its unique product ID.  
**Category:** Products on TikTok are grouped into categories predefined by TikTok. All products must belong to a category. Categories are organized in a tree structure. You can retrieve all available categories via Get Category API.  
**Attribute**: Attributes are supplementary content of product information. Attributes are associated with categories. For a particular category, some attributes are required while others are optional. Attributes can be further divided into product attributes and sales attributes.

-   **Product attributes** are general attributes such as manufacturer, country of origin, materials used)
-   **Sales attributes** are attributes that are specific to the variant (aka SKU) of a product such as size, color, length.
-   **Custom attributes** are attributes that a seller can optionally create to supplement the product information. For example, weight is generally a generic product attribute, which is primarily used for shipping calculations. However, if your product is an extremely lightweight product for example performance outdoor clothing, you can list the weight as a custom attribute.

**Product Status**: A product has the following statuses:

| **#** | **Status** | **Definition** |
| --- | --- | --- |
| 
1

 | 

Draft  

 | 

A product in the Draft status is not visible to TikTok Shop users. The seller may need to create a product in Draft mode, then supplement further product information before activating the product for sale.

 |
| 

2

 | 

Pending

 | 

A product is in the Pending status when it is being reviewed by TikTok.

 |
| 

3

 | 

Failed

 | 

A product is in the Failed status if the product fails the review by TikTok.

 |
| 

4

 | 

Activate  

 | 

A product in the Activate status is visible to TikTok Shop users and available for purchase. A product can be live, but out of stock, which will not be available for purchase.

 |
| 

5

 | 

Seller\_deactivated

 | 

The product has been deactivated by the seller. This removes the product from the shelf and from visibility to consumers. You can reactivate this product at any time.

 |
| 

6

 | 

Platform\_deactivated  

 | 

The product has been deactivated by TikTok. The seller may edit the product information and re-submit for review, which will put the product into the Pending status.

 |
| 

7

 | 

Freeze  

 | 

The product has been frozen by TikTok for violating TikTok Shop policies. TikTok Shop policies vary by region. The seller may appeal to unfreeze a frozen product. Once unfrozen, the product will be put back into the Platform\_deactivated status.

 |
| 

8

 | 

Deleted

 | 

The seller has deleted the product. All historical data, including purchases, financial reports...etc will be removed from the platform. Only delete a product if you are 100% sure that you will no longer be selling the product and you have completed all internal reporting/financial analysis.

 |

# TikTok Shop Marketplace Policies

**Brand authorization**: TikTok requires the seller of a product to provide brand authorization for certain brands either as the direct manufacturer or an authorized reseller. Branded products without valid brand authorization cannot be listed on TikTok for sale. To verify whether the seller has authorization for a particular brand, use the Get Brand API.  
**Product certification**: For certain categories, product certification is required to prove the authenticity of the product. These certifications are meant to comply with local laws. Some examples include ingredients in beauty or consumable products.  
**Product upload limit**: To assist new sellers with order fulfillment, **all** **new shops (both individual and corporate)** on TikTok Shop are subject to a **probation period** that limits the number of daily orders sellers can accept and the number of product uploads sellers can list per day.

-   New individual sellers are limited to 100 orders per day.
-   New corporate sellers are limited to 200 orders per day.
-   All new sellers are limited to 100 product uploads per day.

Once sellers graduate from probation, sellers are not subject to order limits but are limited to 1,000 product uploads per day.

# Frequently Asked Questions

**\-** **I can't get my product to list. What is going on?**

-   There are several reasons for this. Please check the following:
    -   Your product has all the required attributes.
        
    -   Your product has all the required certifications, based on the category it is assigned.
        
    -   You have reached your 100 product upload limit per day, as a new Seller. Please try again the next day.
        

**\-** **How do I overcome the Product Upload limit?**

-   New sellers are limited to 100 products per day. If you would like to overcome this limit and graduate from new seller probation, please reach out to your TikTok account manager.
    

**\-** **My product continuously fails TikTok reviews. Help!**

-   Check that your images are high quality.
    
-   Check that your category or product is allowed for sale in the region.
    
-   Check that you have filled in all required product attributes.
    
-   If the above is not resolved, please reach out to your TikTok Account Manager.
    

**\-** **What is the difference between Deactivate and Delete?**

-   Deactivate will temporarily remove your product off the shelf. All back-end data, including sales, reports, product description, will persist. You can reactivate this product at any time, when you receive more inventory etc.
-   Deleting a product will remove the product from your catalog forever. This also removes historical sales data, financial settlements...etc. Only do this if you have tied up all your accounting and reporting matters, and are fully committed to removing the product from your catalog forever.
